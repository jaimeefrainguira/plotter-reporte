<?php

declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline' blob: 'wasm-unsafe-eval' https://unpkg.com; img-src 'self' data: blob: https://aeonfree.com; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self' https://cdn.jsdelivr.net https://tessdata.projectnaptha.com https://unpkg.com data: https://api.ocr.space https://generativelanguage.googleapis.com; worker-src 'self' blob: data:; object-src 'none'; frame-ancestors 'self';");

require_once __DIR__ . '/controllers/ReporteController.php';
require_once __DIR__ . '/controllers/CampanaController.php';
require_once __DIR__ . '/controllers/MaterialController.php';

$action = $_GET['action'] ?? 'dashboard';

try {
    $controller = new ReporteController();
    $campanaController = new CampanaController();
    $materialController = new MaterialController();
} catch (Throwable $exception) {
    if (in_array($action, ['dashboard', 'plotter', 'plotter_report', 'create', 'edit', 'pdf'], true)) {
        $errorMessage = 'No fue posible conectar con la base de datos. Verifica la configuración para habilitar todos los reportes.';
        include __DIR__ . '/views/error_conexion.php';
        exit;
    }

    http_response_code(500);
    echo 'Error de configuración: ' . $exception->getMessage();
    exit;
}

switch ($action) {
    case 'dashboard':
        $controller->dashboard();
        break;

    case 'create':
        $controller->showCreateForm();
        break;

    case 'store':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->store();
            break;
        }

        header('Location: index.php?action=dashboard');
        break;

    case 'edit':
        $controller->showEditForm((int) ($_GET['id'] ?? 0));
        break;

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->update((int) ($_GET['id'] ?? 0));
            break;
        }

        header('Location: index.php?action=dashboard');
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->destroy((int) ($_POST['id'] ?? 0));
            break;
        }
        header('Location: index.php?action=dashboard');
        break;

    case 'update_percentage':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->updatePercentage((int) ($_POST['id'] ?? 0), (int) ($_POST['percentage'] ?? 0));
            break;
        }
        header('Location: index.php?action=dashboard');
        break;

    case 'pdf':
        $controller->generatePdf((int) ($_GET['id'] ?? 0));
        break;

    case 'plotter':
        $controller->showPlotterDetail();
        break;

    case 'plotter_report':
        $controller->showPlotterReportForm();
        break;

    case 'store_bulk':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->storeBulk();
            break;
        }
        header('Location: index.php?action=dashboard');
        break;

    // --- Módulo de Campañas ---
    case 'campanas_list':
        $campanaController->list();
        break;

    case 'campana_detail':
        $campanaController->show((int)($_GET['id'] ?? 0));
        break;

    case 'campana_store':
        $campanaController->store();
        break;

    case 'campana_save_trabajo':
        $campanaController->saveTrabajo();
        break;

    case 'campana_delete_trabajo':
        $campanaController->deleteTrabajo();
        break;

    case 'campana_update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $campanaController->updateCampana((int)($_POST['id'] ?? 0));
            break;
        }
        header('Location: index.php?action=campanas_list');
        break;

    case 'campana_delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $campanaController->deleteCampana((int)($_POST['id'] ?? 0));
            break;
        }
        header('Location: index.php?action=campanas_list');
        break;

    case 'campana_bulk_save':
        $campanaController->bulkSaveTrabajos();
        break;

    case 'campana_upload_imagen':
        $campanaController->uploadImagen();
        break;

    case 'campana_asignar_plotter':
        $campanaController->asignarPlotter();
        break;

    case 'campana_actualizar_asignacion':
        $campanaController->actualizarAsignacionPlotter();
        break;

    case 'campana_eliminar_asignacion':
        $campanaController->eliminarAsignacionPlotter();
        break;

    case 'campana_auto_asignar':
        $campanaController->autoAsignarPlotters();
        break;

    case 'campana_ver_produccion':
        $campanaController->verProduccionPlotter();
        break;

    case 'campana_registrar_produccion':
        $campanaController->registrarProduccion();
        break;

    // --- Módulo de Materia Prima ---
    case 'materiales_list':
        $materialController->list();
        break;

    case 'material_create':
        $materialController->showCreateForm();
        break;

    case 'material_store':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $materialController->store();
            break;
        }
        header('Location: index.php?action=materiales_list');
        break;

    case 'material_edit':
        $materialController->showEditForm((int)($_GET['id'] ?? 0));
        break;

    case 'material_update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $materialController->update((int)($_GET['id'] ?? 0));
            break;
        }
        header('Location: index.php?action=materiales_list');
        break;

    case 'material_delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $materialController->destroy((int)($_POST['id'] ?? 0));
            break;
        }
        header('Location: index.php?action=materiales_list');
        break;

    case 'material_stock':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $materialController->adjustStock();
            break;
        }
        header('Location: index.php?action=materiales_list');
        break;

    case 'debug_pdf':
        $controller->debugPdf();
        break;

    default:
        http_response_code(404);
        echo 'Acción no válida.';
}
