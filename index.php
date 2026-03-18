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
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; object-src 'none'; frame-ancestors 'self';");

require_once __DIR__ . '/controllers/ReporteController.php';
require_once __DIR__ . '/controllers/CampanaController.php';

$action = $_GET['action'] ?? 'dashboard';

try {
    $controller = new ReporteController();
    $campanaController = new CampanaController();
} catch (Throwable $exception) {
    if (in_array($action, ['dashboard', 'plotter'], true)) {
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

    default:
        http_response_code(404);
        echo 'Acción no válida.';
}
