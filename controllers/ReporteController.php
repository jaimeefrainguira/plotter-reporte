<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reporte.php';

class ReporteController
{
    private ?Reporte $reporteModel = null;
    private ?Throwable $dbInitError = null;

    public function __construct()
    {
        try {
            $database = new Database();
            $this->reporteModel = new Reporte($database->getConnection());
        } catch (Throwable $exception) {
            $this->dbInitError = $exception;
        }
    }

    public function dashboard(): void
    {
        $plotters = $this->getPlotterOptions();
        $loadError = null;
        $stats = [
            'total' => 0,
            'latest' => null,
            'perPlotter' => [],
        ];

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
        $reportes = [];
        $totalRows = 0;
        $totalPages = 1;

        if ($this->reporteModel === null) {
            $loadError = 'No se pudo conectar a MySQL. Revisa DB_HOST, DB_NAME, DB_USER y DB_PASS.';
            if ($this->dbInitError !== null) {
                error_log('[plotter-reporte] dashboard DB init error: ' . $this->dbInitError->getMessage());
            }
        } else {
            try {
                $stats = $this->reporteModel->getDashboardStats();
                $result = $this->reporteModel->getPaginated($filters, $page, $perPage);
                $reportes = $result['items'];
                $totalRows = $result['totalRows'];
                $totalPages = (int) max(1, ceil($totalRows / $perPage));

                if ($page > $totalPages) {
                    $page = $totalPages;
                    $result = $this->reporteModel->getPaginated($filters, $page, $perPage);
                    $reportes = $result['items'];
                }
            } catch (Throwable $exception) {
                $loadError = 'No se pudo cargar la información de reportes. Revisa la tabla `reportes` y permisos en MySQL.';
                error_log('[plotter-reporte] dashboard query error: ' . $exception->getMessage());
            }
        }

        $csrfToken = $this->getCsrfToken();
        include __DIR__ . '/../views/dashboard.php';
    }

    public function showCreateForm(array $oldData = [], array $errors = []): void
    {
        if (!$this->ensureModelAvailable()) {
            return;
        }

        $plotters = $this->getPlotterOptions();
        $csrfToken = $this->getCsrfToken();
        include __DIR__ . '/../views/formulario_reporte.php';
    }

    public function store(): void
    {
        if (!$this->ensureModelAvailable()) {
            return;
        }

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
        if (!$this->ensureModelAvailable()) {
            return;
        }

        if ($id <= 0) {
            $this->redirectWithMessage('ID de reporte inválido.', 'danger');
            return;
        }

        try {
            $reporte = $this->reporteModel->getById($id);
        } catch (Throwable $exception) {
            error_log('[plotter-reporte] showEditForm error: ' . $exception->getMessage());
            $this->redirectWithMessage('No se pudo cargar el reporte para edición.', 'danger');
            return;
        }

        if ($reporte === null) {
            $this->redirectWithMessage('Reporte no encontrado.', 'danger');
            return;
        }

        if ($oldData) {
            $reporte = array_merge($reporte, $oldData);
        }

        $plotters = $this->getPlotterOptions();
        $csrfToken = $this->getCsrfToken();
        include __DIR__ . '/../views/editar_reporte.php';
    }

    public function update(int $id): void
    {
        if (!$this->ensureModelAvailable()) {
            return;
        }

        if ($id <= 0) {
            $this->redirectWithMessage('ID de reporte inválido.', 'danger');
            return;
        }

        if (!$this->isValidCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->redirectWithMessage('Sesión expirada. Intenta nuevamente.', 'danger');
            return;
        }

        try {
            $existing = $this->reporteModel->getById($id);
        } catch (Throwable $exception) {
            error_log('[plotter-reporte] update precheck error: ' . $exception->getMessage());
            $this->redirectWithMessage('No se pudo validar el reporte a actualizar.', 'danger');
            return;
        }

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
        if (!$this->ensureModelAvailable()) {
            return;
        }

        if ($id <= 0) {
            $this->redirectWithMessage('ID de reporte inválido.', 'danger');
            return;
        }

        if (!$this->isValidCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->redirectWithMessage('Sesión expirada. Intenta nuevamente.', 'danger');
            return;
        }

        try {
            $existing = $this->reporteModel->getById($id);
        } catch (Throwable $exception) {
            error_log('[plotter-reporte] destroy precheck error: ' . $exception->getMessage());
            $this->redirectWithMessage('No se pudo validar el reporte a eliminar.', 'danger');
            return;
        }

        if ($existing === null) {
            $this->redirectWithMessage('Reporte no encontrado.', 'danger');
            return;
        }

        try {
            $deleted = $this->reporteModel->delete($id);
        } catch (Throwable $exception) {
            error_log('[plotter-reporte] destroy delete error: ' . $exception->getMessage());
            $this->redirectWithMessage('No se pudo eliminar el reporte.', 'danger');
            return;
        }

        $this->rotateCsrfToken();

        if (!$deleted) {
            $this->redirectWithMessage('No fue posible eliminar el reporte.', 'danger');
            return;
        }

        $this->redirectWithMessage('Reporte eliminado correctamente.');
    }

    private function ensureModelAvailable(): bool
    {
        if ($this->reporteModel !== null) {
            return true;
        }

        $this->redirectWithMessage(
            'No hay conexión a la base de datos. Verifica la configuración MySQL del hosting.',
            'danger'
        );

        return false;
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

        if ($data['plotter'] === '') {
            $errors[] = 'Debes seleccionar un plotter.';
        }

        if ($data['observacion'] === '') {
            $errors[] = 'La observación es obligatoria.';
        }

        if ($data['descripcion'] === '') {
            $errors[] = 'La descripción es obligatoria.';
        } elseif (mb_strlen($data['descripcion']) > 255) {
            $errors[] = 'La descripción no puede superar 255 caracteres.';
        }

        if ($data['cantidad'] <= 0) {
            $errors[] = 'La cantidad debe ser mayor a 0.';
        }

        if ($data['cantidad_impreso'] < 0) {
            $errors[] = 'La cantidad impreso no puede ser negativa.';
        }

        if ($data['porcentaje_impresion'] < 0 || $data['porcentaje_impresion'] > 100) {
            $errors[] = 'El porcentaje de impresión debe estar entre 0 y 100.';
        }

        return $errors;
    }

    private function getPlotterOptions(): array
    {
        return ['PLOTTER 1', 'PLOTTER 2', 'PLOTTER 3'];
    }

    private function isValidDate(string $date): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt !== false && $dt->format('Y-m-d') === $date;
    }

    private function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    private function rotateCsrfToken(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    private function isValidCsrfToken(string $token): bool
    {
        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

        return $sessionToken !== '' && hash_equals($sessionToken, $token);
    }

    private function redirectWithMessage(string $message, string $type = 'success'): void
    {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type,
        ];

        header('Location: index.php?action=dashboard');
        exit;
    }
}
