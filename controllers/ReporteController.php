<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Campana.php';
require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../models/Asignacion.php';
require_once __DIR__ . '/../models/Reporte.php';

class ReporteController
{
    private Reporte $reporteModel;

    public function __construct()
    {
        $database = new Database();
        $this->reporteModel = new Reporte($database->getConnection());
    }

    public function dashboard(): void
    {
        $database = new Database();
        $conn = $database->getConnection();
        $this->reporteModel = new Reporte($conn);
        $campanaModel = new Campana($conn);

        $stats = $this->reporteModel->getDashboardStats();
        $plotters = $this->getPlotterOptions();

        $campanasActivas = $campanaModel->getAll();
        foreach ($campanasActivas as &$c) {
            $c['progreso'] = $campanaModel->getProgresoGlobal((int) $c['id']);
        }

        $plotterFilter = trim((string) ($_GET['plotter'] ?? ''));
        $fechaFilter = isset($_GET['fecha']) ? trim((string) $_GET['fecha']) : '';

        if ($plotterFilter !== '' && !in_array($plotterFilter, $plotters, true)) {
            $plotterFilter = '';
        }

        if ($fechaFilter !== '' && !$this->isValidDate($fechaFilter)) {
            $fechaFilter = '';
        }

        $filters = [
            'plotter' => $plotterFilter,
            'fecha' => $fechaFilter,
        ];

        if ($plotterFilter === '' && $fechaFilter === '' && !isset($_GET['page'])) {
            $latestMasterId = $this->reporteModel->getLatestMasterId();
            $dashboardRows = $latestMasterId ? $this->reporteModel->getByMasterId($latestMasterId) : [];
        } else {
            $dashboardRows = $this->reporteModel->getByDateAndPlotter($fechaFilter !== '' ? $fechaFilter : null, $plotterFilter !== '' ? $plotterFilter : null);
        }

        $reportesByPlotter = array_fill_keys($plotters, []);
        foreach ($dashboardRows as $reporte) {
            $plotterName = (string) ($reporte['plotter'] ?? '');
            if (!array_key_exists($plotterName, $reportesByPlotter)) {
                continue;
            }
            $reportesByPlotter[$plotterName][] = $reporte;
        }

        $csrfToken = $this->getCsrfToken();
        include __DIR__ . '/../views/dashboard.php';
    }

    public function showPlotterDetail(): void
    {
        $plotters = $this->getPlotterOptions();

        $plotter = trim((string) ($_GET['plotter'] ?? ''));
        if (!in_array($plotter, $plotters, true)) {
            $this->redirectWithMessage('Plotter inválido.', 'danger');
            return;
        }

        $fecha = trim((string) ($_GET['fecha'] ?? ''));
        if ($fecha !== '' && !$this->isValidDate($fecha)) {
            $fecha = '';
        }

        $reportes = $this->reporteModel->getByDateAndPlotter($fecha, $plotter);
        $csrfToken = $this->getCsrfToken();
        include __DIR__ . '/../views/plotter_detalle.php';
    }

    public function showCreateForm(array $oldData = [], array $errors = []): void
    {
        $plotters = $this->getPlotterOptions();
        $csrfToken = $this->getCsrfToken();
        include __DIR__ . '/../views/formulario_reporte.php';
    }

    public function store(): void
    {
        if (!$this->isValidCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->redirectWithMessage('Sesión expirada. Intenta nuevamente.', 'danger');
            return;
        }

        $data = $this->sanitizeData($_POST);
        $errors = $this->validate($data);

        if ($errors) {
            $this->showCreateForm($data, $errors);
            return;
        }

        try {
            $this->reporteModel->create($data);
        } catch (Throwable $exception) {
            $this->showCreateForm($data, ['Error al guardar reporte. ' . $exception->getMessage()]);
            return;
        }

        $this->rotateCsrfToken();
        $this->redirectWithMessage('Reporte creado correctamente.');
    }

    public function showPlotterReportForm(): void
    {
        $plotters = $this->getPlotterOptions();
        $loadError = '';
        $campanas = [];
        $materiales = [];
        $asignacionesJornada = [];

        try {
            $database = new Database();
            $conn = $database->getConnection();
            $campanaModel = new Campana($conn);
            $materialModel = new Material($conn);
            $asignacionModel = new Asignacion($conn);
            $campanas = $campanaModel->getAll();
            $materiales = $materialModel->getAll();
            $asignacionesJornada = $asignacionModel->getResumenJornada();
        } catch (Throwable $exception) {
            $loadError = 'No se pudieron cargar los trabajos del módulo de producción: ' . $exception->getMessage();
        }

        $csrfToken = $this->getCsrfToken();
        include __DIR__ . '/../views/reporte_plotter_crear.php';
    }

    public function storeBulk(): void
    {
        if (!$this->isValidCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->redirectWithMessage('Sesión expirada. Intenta nuevamente.', 'danger');
            return;
        }

        $rows = $_POST['rows'] ?? [];
        if (empty($rows)) {
            $this->redirectWithMessage('No hay filas para guardar.', 'warning');
            return;
        }

        $operador = trim((string) ($_POST['jornada_operator'] ?? ''));
        $jornadaInicio = trim((string) ($_POST['jornada_start'] ?? ''));
        $jornadaFin = trim((string) ($_POST['jornada_end'] ?? ''));

        if ($operador === '' || $jornadaInicio === '') {
            $this->redirectWithMessage('Debes iniciar la jornada con nombre de operador.', 'warning');
            return;
        }

        $metadata = $this->buildJornadaMetadata($operador, $jornadaInicio, $jornadaFin);

        try {
            $maestroId = $this->reporteModel->createMaster($metadata);

            foreach ($rows as $row) {
                $data = $this->sanitizeData($row);
                $errors = $this->validate($data);
                if (!empty($errors)) {
                    throw new RuntimeException(implode(' ', $errors));
                }

                $data['maestro_id'] = $maestroId;
                $this->reporteModel->create($data);
            }
        } catch (Throwable $exception) {
            $this->redirectWithMessage('Error al guardar el reporte masivo: ' . $exception->getMessage(), 'danger');
            return;
        }

        $this->rotateCsrfToken();

        $submitMode = (string) ($_POST['submit_mode'] ?? 'save');
        if ($submitMode === 'pdf') {
            $this->renderJornadaPdf($maestroId);
            return;
        }

        $this->redirectWithMessage('Reporte guardado con éxito.');
    }

    public function showEditForm(int $id, array $oldData = [], array $errors = []): void
    {
        if ($id <= 0) {
            $this->redirectWithMessage('ID de reporte inválido.', 'danger');
            return;
        }

        $plotters = $this->getPlotterOptions();
        $reporte = $this->reporteModel->getById($id);

        if (!$reporte) {
            $this->redirectWithMessage('Reporte no encontrado.', 'danger');
            return;
        }

        if ($oldData) {
            $reporte = array_merge($reporte, $oldData);
        }

        $csrfToken = $this->getCsrfToken();
        include __DIR__ . '/../views/editar_reporte.php';
    }

    public function update(int $id): void
    {
        if ($id <= 0) {
            $this->redirectWithMessage('ID de reporte inválido.', 'danger');
            return;
        }

        if ($this->reporteModel->getById($id) === null) {
            $this->redirectWithMessage('Reporte no encontrado.', 'danger');
            return;
        }

        if (!$this->isValidCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->redirectWithMessage('Sesión expirada. Intenta nuevamente.', 'danger');
            return;
        }

        $data = $this->sanitizeData($_POST);
        $errors = $this->validate($data);

        if ($errors) {
            $this->showEditForm($id, $data, $errors);
            return;
        }

        try {
            $updated = $this->reporteModel->update($id, $data);
        } catch (Throwable $exception) {
            $this->showEditForm($id, $data, ['Error al actualizar reporte. ' . $exception->getMessage()]);
            return;
        }

        $this->rotateCsrfToken();

        if (!$updated) {
            $this->redirectWithMessage('No hubo cambios para actualizar en el reporte.', 'warning');
            return;
        }

        $this->redirectWithMessage('Reporte actualizado correctamente.');
    }

    public function updatePercentage(int $id, int $percentage): void
    {
        if ($id <= 0) {
            $this->redirectWithMessage('ID inválido.', 'danger');
            return;
        }
        $this->reporteModel->updatePercentage($id, $percentage);
        $this->redirectWithMessage('Porcentaje actualizado.');
    }

    public function generatePdf(?int $id = null): void
    {
        if (!$this->loadDompdfLibrary()) {
            $this->redirectWithMessage(
                'No se encontró DomPDF. Sube la carpeta vendor o la carpeta dompdf en la raíz del proyecto.',
                'danger'
            );
            return;
        }

        if (!class_exists('Dompdf\\Dompdf')) {
            $this->redirectWithMessage('DomPDF no está disponible. Verifica la instalación de la librería.', 'danger');
            return;
        }

        $reportId = ($id !== null && $id > 0) ? $id : null;
        $plotter = trim((string) ($_GET['plotter'] ?? ''));
        $fecha = trim((string) ($_GET['fecha'] ?? ''));

        if ($plotter !== '' && !in_array($plotter, $this->getPlotterOptions(), true)) {
            $plotter = '';
        }

        if ($fecha !== '' && !$this->isValidDate($fecha)) {
            $fecha = '';
        }

        if ($reportId !== null) {
            $reportes = $this->reporteModel->getAllForPdf($reportId, $plotter, $fecha);
        } elseif ($plotter !== '' || $fecha !== '') {
            $reportes = $this->reporteModel->getAllForPdf(null, $plotter, $fecha);
        } else {
            $latestMasterId = $this->reporteModel->getLatestMasterId();
            $reportes = $latestMasterId ? $this->reporteModel->getByMasterId($latestMasterId) : [];
        }

        $html = $this->buildPdfHtml($reportes, $plotter, $fecha);

        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('reporte-impresiones-plotter.pdf', ['Attachment' => false]);
    }

    private function renderJornadaPdf(int $maestroId): void
    {


         if (!$this->loadDompdfLibrary() || !class_exists('Dompdf\\Dompdf')) {
            $this->redirectWithMessage('No se pudo generar PDF. Verifica instalación de DomPDF.', 'danger');
            return;
        }

        $master = $this->reporteModel->getMasterById($maestroId);
        $reportes = $this->reporteModel->getByMasterId($maestroId);
        $meta = $this->parseJornadaMetadata((string) ($master['observacion_general'] ?? ''));

        $html = $this->buildJornadaPdfHtml($reportes, $meta);

        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('reporte-jornada-plotters.pdf', ['Attachment' => false]);
        
        
    }

    private function loadDompdfLibrary(): bool
    {
        $autoloadCandidates = [
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../dompdf/autoload.inc.php',
            __DIR__ . '/../dompdf/vendor/autoload.php',
        ];

        foreach ($autoloadCandidates as $autoloadFile) {
            if (file_exists($autoloadFile)) {
                require_once $autoloadFile;
                return true;
            }
        }

        return false;
    }

    private function buildPdfHtml(array $reportes, string $plotter = '', string $fecha = ''): string
    {
        $subtitle = [];
        if ($plotter !== '') {
            $subtitle[] = $plotter;
        }
        if ($fecha !== '') {
            $subtitle[] = 'Fecha: ' . $fecha;
        }

        $isGeneralPdf = $plotter === '';
        $reportesByPlotter = [];
        if ($isGeneralPdf) {
            $reportesByPlotter = array_fill_keys($this->getPlotterOptions(), []);
            foreach ($reportes as $reporte) {
                $plotterName = (string) ($reporte['plotter'] ?? '');
                if (!array_key_exists($plotterName, $reportesByPlotter)) {
                    $reportesByPlotter[$plotterName] = [];
                }

                $reportesByPlotter[$plotterName][] = $reporte;
            }
        }

        ob_start();
        ?>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
                h2 { text-align: center; margin-bottom: 16px; }
                p { margin: 0 0 10px; }
                .general-grid { margin: 0; }
                .general-grid__item { display: block; width: 100%; padding-bottom: 20px; box-sizing: border-box; font-size: 12px; page-break-inside: avoid; }
                .plotter-title { font-weight: bold; text-align: center; border: 1px solid #333; background: #f2f2f2; padding: 6px; margin-bottom: 4px; font-size: 14px; }
                table { width: 100%; border-collapse: collapse; font-size: 11px; }
                th, td { border: 1px solid #444; padding: 4px; }
                th { background: #eee; text-align: left; }
            </style>
        </head>
        <body>
            <h2>Reporte de Impresiones Plotter</h2>
            <?php if ($subtitle): ?>
                <p><strong><?= htmlspecialchars(implode(' | ', $subtitle)) ?></strong></p>
            <?php endif; ?>
            <?php if ($isGeneralPdf): ?>
                <div class="general-grid">
                    <?php foreach ($reportesByPlotter as $plotterName => $plotterRows): ?>
                        <div class="general-grid__item">
                            <div class="plotter-title"><?= htmlspecialchars((string) $plotterName) ?></div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>CAMPAÑA / TRABAJO</th>
                                        <th>MATERIAL</th>
                                        <th>ASIGNADO</th>
                                        <th>PRODUCIDO</th>
                                        <th>PROGRESO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($plotterRows as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($row['observacion'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string) ($row['material'] ?? '')) ?></td>
                                        <td><?= (int) ($row['cantidad'] ?? 0) ?></td>
                                        <td><?= (int) ($row['cantidad_impreso'] ?? 0) ?></td>
                                        <td><?= (int) ($row['porcentaje_impresion'] ?? 0) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($plotterRows)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #666;">Sin reportes registrados.</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Plotter</th>
                            <th>Campaña / Trabajo</th>
                            <th>Material</th>
                            <th>Asignado</th>
                            <th>Producido</th>
                            <th>Progreso</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reportes as $reporte): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($reporte['plotter'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string) ($reporte['observacion'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string) ($reporte['material'] ?? '')) ?></td>
                            <td><?= (int) ($reporte['cantidad'] ?? 0) ?></td>
                            <td><?= (int) ($reporte['cantidad_impreso'] ?? 0) ?></td>
                            <td><?= (int) ($reporte['porcentaje_impresion'] ?? 0) ?>%</td>
                            <td><?= htmlspecialchars((string) ($reporte['fecha'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$reportes): ?>
                        <tr>
                            <td colspan="7">No hay reportes disponibles.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </body>
        </html>
        <?php

        return (string) ob_get_clean();
    }

    private function buildJornadaPdfHtml(array $reportes, array $meta): string
    {
        $inicio = $this->formatJornadaDate((string) ($meta['inicio'] ?? ''));
        $fin = $this->formatJornadaDate((string) ($meta['fin'] ?? ''));
        $operador = (string) ($meta['operador'] ?? 'Sin operador');

        ob_start();
        ?>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
                h2 { text-align: center; margin-bottom: 8px; }
                .meta { margin-bottom: 12px; }
                .meta span { display: inline-block; margin-right: 20px; }
                table { width: 100%; border-collapse: collapse; font-size: 11px; }
                th, td { border: 1px solid #444; padding: 5px; }
                th { background: #efefef; text-align: left; }
            </style>
        </head>
        <body>
            <h2>Reporte de Jornada de Plotters</h2>
            <div class="meta">
                <span><strong>Operador:</strong> <?= htmlspecialchars($operador) ?></span>
                <span><strong>Inicio:</strong> <?= htmlspecialchars($inicio) ?></span>
                <span><strong>Fin:</strong> <?= htmlspecialchars($fin) ?></span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Plotter</th>
                        <th>Campaña / Trabajo</th>
                        <th>Material</th>
                        <th>Asignado</th>
                        <th>Producido</th>
                        <th>Progreso del Trabajo</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reportes as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($row['plotter'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($row['observacion'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($row['material'] ?? '')) ?></td>
                        <td><?= (int) ($row['cantidad'] ?? 0) ?></td>
                        <td><?= (int) ($row['cantidad_impreso'] ?? 0) ?></td>
                        <td><?= (int) ($row['porcentaje_impresion'] ?? 0) ?>%</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reportes)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Sin trabajos registrados en la jornada.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php

        return (string) ob_get_clean();
    }

    private function sanitizeData(array $input): array
    {
        return [
            'plotter' => trim((string) ($input['plotter'] ?? '')),
            'observacion' => trim((string) ($input['campana'] ?? $input['observacion'] ?? '')),
            'descripcion' => trim((string) ($input['descripcion'] ?? '')),
            'material' => trim((string) ($input['material'] ?? '')),
            'cantidad' => (int) ($input['cantidad'] ?? 0),
            'cantidad_impreso' => (int) ($input['cantidad_impreso'] ?? 0),
            'porcentaje_impresion' => (int) ($input['porcentaje_impresion'] ?? 0),
            'material_sobrante' => (int) ($input['material_sobrante'] ?? 0),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if (!in_array($data['plotter'], $this->getPlotterOptions(), true)) {
            $errors[] = 'Selecciona un plotter válido.';
        }

        if ($data['observacion'] === '') {
            $errors[] = 'La campaña / trabajo es obligatoria.';
        }

        if ($data['descripcion'] === '') {
            $errors[] = 'La descripción del trabajo es obligatoria.';
        }

        if ($data['material'] === '') {
            $errors[] = 'El material es obligatorio.';
        }

        if (mb_strlen($data['descripcion']) > 255) {
            $errors[] = 'La descripción no puede superar 255 caracteres.';
        }

        if (mb_strlen($data['material']) > 120) {
            $errors[] = 'El material no puede superar 120 caracteres.';
        }

        if ($data['cantidad'] <= 0) {
            $errors[] = 'La cantidad asignada debe ser mayor a 0.';
        }

        if ($data['cantidad_impreso'] < 0) {
            $errors[] = 'La cantidad producida no puede ser negativa.';
        }

        if ($data['cantidad_impreso'] > $data['cantidad']) {
            $errors[] = 'La cantidad producida no puede ser mayor a la asignada.';
        }

        if ($data['porcentaje_impresion'] < 0 || $data['porcentaje_impresion'] > 100) {
            $errors[] = 'El progreso del trabajo debe estar entre 0 y 100.';
        }

        return $errors;
    }

    private function buildJornadaMetadata(string $operador, string $inicio, string $fin): string
    {
        return json_encode([
            'operador' => $operador,
            'inicio' => $inicio,
            'fin' => $fin,
        ], JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function parseJornadaMetadata(string $metadata): array
    {
        if ($metadata === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    private function formatJornadaDate(string $value): string
    {
        if ($value === '') {
            return '—';
        }

        try {
            $date = new DateTime($value);
            return $date->format('d/m/Y H:i:s');
        } catch (Throwable) {
            return $value;
        }
    }

    private function redirectWithMessage(string $message, string $type = 'success'): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
        ];

        header('Location: index.php?action=dashboard');
        exit;
    }

    private function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    private function isValidCsrfToken(string $token): bool
    {
        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

        return $sessionToken !== '' && hash_equals($sessionToken, $token);
    }

    private function rotateCsrfToken(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        if ($parsed === false) {
            return false;
        }

        $errors = DateTime::getLastErrors();
        if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return false;
        }

        return $parsed->format('Y-m-d') === $date;
    }

    private function getPlotterOptions(): array
    {
        return [
            'PLOTTER 1',
            'PLOTTER 2',
            'PLOTTER 3',
            'PLOTTER 4',
            'PLOTTER 5',
            'PLOTTER 6',
        ];
    }
}
