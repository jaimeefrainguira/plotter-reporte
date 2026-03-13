<?php

declare(strict_types=1);

class ReporteController
{
    public function __construct()
    {
        if (!isset($_SESSION['reportes_mock']) || !is_array($_SESSION['reportes_mock'])) {
            $_SESSION['reportes_mock'] = $this->getSeedReports();
        }

        if (!isset($_SESSION['reportes_next_id'])) {
            $ids = array_column($_SESSION['reportes_mock'], 'id');
            $_SESSION['reportes_next_id'] = $ids ? (max($ids) + 1) : 1;
        }
    }

    public function dashboard(): void
    {
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

        $all = $this->getReports();
        $filtered = array_values(array_filter($all, function (array $row) use ($filters): bool {
            if ($filters['plotter'] !== '' && $row['plotter'] !== $filters['plotter']) {
                return false;
            }

            if ($filters['fecha'] !== '' && substr((string) $row['fecha'], 0, 10) !== $filters['fecha']) {
                return false;
            }

            return true;
        }));

        usort($filtered, static function (array $a, array $b): int {
            if ($a['fecha'] === $b['fecha']) {
                return ((int) $b['id']) <=> ((int) $a['id']);
            }

            return strcmp((string) $b['fecha'], (string) $a['fecha']);
        });

        $stats = $this->buildStats($all);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;
        $totalRows = count($filtered);
        $totalPages = (int) max(1, (int) ceil($totalRows / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $reportes = array_slice($filtered, $offset, $perPage);

        $csrfToken = $this->getCsrfToken();
        $loadError = null;
        include __DIR__ . '/../views/dashboard.php';
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

        $reports = $this->getReports();
        $data['id'] = (int) $_SESSION['reportes_next_id'];
        $_SESSION['reportes_next_id'] = $data['id'] + 1;
        $data['fecha'] = date('Y-m-d H:i:s');
        $reports[] = $data;
        $this->saveReports($reports);

        $this->rotateCsrfToken();
        $this->redirectWithMessage('Reporte creado correctamente (modo datos fijos).');
    }

    public function showEditForm(int $id, array $oldData = [], array $errors = []): void
    {
        if ($id <= 0) {
            $this->redirectWithMessage('ID de reporte inválido.', 'danger');
            return;
        }

        $plotters = $this->getPlotterOptions();
        $reporte = $this->findReportById($id);

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

        if (!$this->isValidCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->redirectWithMessage('Sesión expirada. Intenta nuevamente.', 'danger');
            return;
        }

        $existing = $this->findReportById($id);
        if ($existing === null) {
            $this->redirectWithMessage('Reporte no encontrado.', 'danger');
            return;
        }

        $data = $this->sanitizeData($_POST);
        $errors = $this->validate($data);

        if ($errors) {
            $this->showEditForm($id, $data, $errors);
            return;
        }

        $reports = $this->getReports();
        $updated = false;
        foreach ($reports as &$row) {
            if ((int) $row['id'] === $id) {
                $data['id'] = $id;
                $data['fecha'] = (string) $row['fecha'];
                $updated = ($row != $data);
                $row = $data;
                break;
            }
        }
        unset($row);

        $this->saveReports($reports);
        $this->rotateCsrfToken();

        if (!$updated) {
            $this->redirectWithMessage('No hubo cambios para actualizar en el reporte.', 'warning');
            return;
        }

        $this->redirectWithMessage('Reporte actualizado correctamente (modo datos fijos).');
    }

    public function destroy(int $id): void
    {
        if ($id <= 0) {
            $this->redirectWithMessage('ID de reporte inválido.', 'danger');
            return;
        }

        if (!$this->isValidCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->redirectWithMessage('Sesión expirada. Intenta nuevamente.', 'danger');
            return;
        }

        $reports = $this->getReports();
        $before = count($reports);
        $reports = array_values(array_filter($reports, static fn(array $row): bool => (int) $row['id'] !== $id));
        $after = count($reports);

        $this->saveReports($reports);
        $this->rotateCsrfToken();

        if ($before === $after) {
            $this->redirectWithMessage('Reporte no encontrado.', 'danger');
            return;
        }

        $this->redirectWithMessage('Reporte eliminado correctamente (modo datos fijos).');
    }

    public function generatePdf(?int $id = null): void
    {
        if (!$this->loadDompdfLibrary()) {
            $this->redirectWithMessage(
                'No se encontró DomPDF. Verifica que exista vendor/autoload.php o una carpeta dompdf (ej: dompdf/) con autoload.inc.php en la raíz del proyecto.',
                'danger'
            );
            return;
        }

        if (!class_exists('Dompdf\\Dompdf')) {
            $this->redirectWithMessage('DomPDF no está disponible. Verifica la instalación de la librería.', 'danger');
            return;
        }

        $reportes = $this->getReports();
        if ($id !== null && $id > 0) {
            $reportes = array_values(array_filter($reportes, static fn(array $row): bool => (int) $row['id'] === $id));
        }

        usort($reportes, static function (array $a, array $b): int {
            return strcmp((string) $b['fecha'], (string) $a['fecha']);
        });

        $html = $this->buildPdfHtml($reportes);

        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('reporte-impresiones-plotter.pdf', ['Attachment' => false]);
    }

    private function getReports(): array
    {
        $rows = $_SESSION['reportes_mock'] ?? [];

        return is_array($rows) ? $rows : [];
    }

    private function saveReports(array $rows): void
    {
        $_SESSION['reportes_mock'] = array_values($rows);
    }

    private function findReportById(int $id): ?array
    {
        foreach ($this->getReports() as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }

        return null;
    }

    private function buildStats(array $all): array
    {
        $latest = null;
        if ($all) {
            usort($all, static function (array $a, array $b): int {
                if ($a['fecha'] === $b['fecha']) {
                    return ((int) $b['id']) <=> ((int) $a['id']);
                }

                return strcmp((string) $b['fecha'], (string) $a['fecha']);
            });
            $latest = $all[0] ?? null;
        }

        $totals = [];
        foreach ($all as $row) {
            $key = (string) $row['plotter'];
            $totals[$key] = ($totals[$key] ?? 0) + 1;
        }
        ksort($totals);

        $perPlotter = [];
        foreach ($totals as $plotter => $total) {
            $perPlotter[] = [
                'plotter' => $plotter,
                'total' => $total,
            ];
        }

        return [
            'total' => count($all),
            'latest' => $latest,
            'perPlotter' => $perPlotter,
        ];
    }

    private function getSeedReports(): array
    {
        return [
            [
                'id' => 1,
                'plotter' => 'PLOTTER 1',
                'observacion' => 'Prueba inicial sin base de datos',
                'descripcion' => 'Impresión de plano A1',
                'cantidad' => 10,
                'cantidad_impreso' => 8,
                'porcentaje_impresion' => 80,
                'fecha' => date('Y-m-d H:i:s', time() - 3600),
            ],
            [
                'id' => 2,
                'plotter' => 'PLOTTER 2',
                'observacion' => 'Segundo registro demo',
                'descripcion' => 'Impresión de banner',
                'cantidad' => 5,
                'cantidad_impreso' => 5,
                'porcentaje_impresion' => 100,
                'fecha' => date('Y-m-d H:i:s', time() - 1800),
            ],
        ];
    }

    private function loadDompdfLibrary(): bool
    {
        $autoloadCandidates = [
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../dompdf/autoload.inc.php',
            __DIR__ . '/../dompdf/autoload.php',
        ];

        $dynamicCandidates = glob(__DIR__ . '/../dompdf*/autoload.inc.php') ?: [];
        $dynamicCandidates = array_merge(
            $dynamicCandidates,
            glob(__DIR__ . '/../dompdf*/autoload.php') ?: []
        );

        $autoloadCandidates = array_merge($autoloadCandidates, $dynamicCandidates);

        foreach ($autoloadCandidates as $autoloadFile) {
            if (is_string($autoloadFile) && file_exists($autoloadFile)) {
                require_once $autoloadFile;
                return true;
            }
        }

        return false;
    }

    private function buildPdfHtml(array $reportes): string
    {
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
            <table>
                <thead>
                    <tr>
                        <th>Plotter</th>
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
                        <td><?= htmlspecialchars((string) $reporte['plotter']) ?></td>
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
