<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
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
        $stats = $this->reporteModel->getDashboardStats();
        $plotters = $this->getPlotterOptions();

        $plotterFilter = trim((string) ($_GET['plotter'] ?? ''));
        $fechaFilter = trim((string) ($_GET['fecha'] ?? ''));

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

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;

        $result = $this->reporteModel->getPaginated($filters, $page, $perPage);
        $reportes = $result['items'];
        $totalRows = $result['totalRows'];
        $totalPages = (int) max(1, ceil($totalRows / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $result = $this->reporteModel->getPaginated($filters, $page, $perPage);
            $reportes = $result['items'];
        }

        $dashboardRows = $this->reporteModel->getByDateAndPlotter($fechaFilter !== '' ? $fechaFilter : null, null);
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

    public function destroy(int $id): void
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

        $deleted = $this->reporteModel->delete($id);
        $this->rotateCsrfToken();

        if (!$deleted) {
            $this->redirectWithMessage('No fue posible eliminar el reporte.', 'danger');
            return;
        }

        $this->redirectWithMessage('Reporte eliminado correctamente.');
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
        $reportes = $this->reporteModel->getAllForPdf($reportId);
        $html = $this->buildPdfHtml($reportes, $reportId === null);
        $plotter = trim((string) ($_GET['plotter'] ?? ''));
        $fecha = trim((string) ($_GET['fecha'] ?? ''));

        if ($plotter !== '' && !in_array($plotter, $this->getPlotterOptions(), true)) {
            $plotter = '';
        }

        if ($fecha !== '' && !$this->isValidDate($fecha)) {
            $fecha = '';
        }

        $reportes = $this->reporteModel->getAllForPdf($reportId, $plotter, $fecha);
        $html = $this->buildPdfHtml($reportes, $plotter, $fecha);

        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('reporte-impresiones-plotter.pdf', ['Attachment' => false]);
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

        ob_start();
        ?>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
                h2 { text-align: center; margin-bottom: 16px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #444; padding: 6px; }
                th { background: #eee; }
            </style>
        </head>
        <body>
            <h2>Reporte de Impresiones Plotter</h2>
            <?php if ($subtitle): ?>
                <p><strong><?= htmlspecialchars(implode(' | ', $subtitle)) ?></strong></p>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>                       
                        <th>Observación</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Cantidad Impreso</th>
                        <th>% Impresión</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reportes as $reporte): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $reporte['observacion']) ?></td>
                        <td><?= htmlspecialchars((string) $reporte['descripcion']) ?></td>
                        <td><?= (int) $reporte['cantidad'] ?></td>
                        <td><?= (int) ($reporte['cantidad_impreso'] ?? 0) ?></td>
                        <td><?= (int) $reporte['porcentaje_impresion'] ?>%</td>
                        <td><?= htmlspecialchars((string) $reporte['fecha']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$reportes): ?>
                    <tr>
                        <td colspan="7">No hay reportes disponibles.</td>
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
            'observacion' => trim((string) ($input['observacion'] ?? '')),
            'descripcion' => trim((string) ($input['descripcion'] ?? '')),
            'cantidad' => (int) ($input['cantidad'] ?? 0),
            'cantidad_impreso' => (int) ($input['cantidad_impreso'] ?? 0),
            'porcentaje_impresion' => (int) ($input['porcentaje_impresion'] ?? 0),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if (!in_array($data['plotter'], $this->getPlotterOptions(), true)) {
            $errors[] = 'Selecciona un plotter válido.';
        }

        if ($data['observacion'] === '') {
            $errors[] = 'La observación es obligatoria.';
        }

        if ($data['descripcion'] === '') {
            $errors[] = 'La descripción es obligatoria.';
        }

        if (mb_strlen($data['descripcion']) > 255) {
            $errors[] = 'La descripción no puede superar 255 caracteres.';
        }

        if ($data['cantidad'] <= 0) {
            $errors[] = 'La cantidad debe ser mayor a 0.';
        }

        if ($data['cantidad_impreso'] < 0) {
            $errors[] = 'La cantidad impreso no puede ser negativa.';
        }

        if ($data['cantidad_impreso'] > $data['cantidad']) {
            $errors[] = 'La cantidad impreso no puede ser mayor a la cantidad.';
        }

        if ($data['porcentaje_impresion'] < 0 || $data['porcentaje_impresion'] > 100) {
            $errors[] = 'El porcentaje de impresión debe estar entre 0 y 100.';
        }

        return $errors;
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
