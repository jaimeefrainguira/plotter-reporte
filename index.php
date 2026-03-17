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
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; object-src 'none'; frame-ancestors 'self';");

require_once __DIR__ . '/controllers/ReporteController.php';

$action = $_GET['action'] ?? 'dashboard';

try {
    $controller = new ReporteController();
} catch (Throwable $exception) {
    if (in_array($action, ['dashboard', 'plotter'], true)) {
        $errorMessage = 'No fue posible conectar con la base de datos. Verifica la configuración para habilitar todos los reportes.';
        include __DIR__ . '/views/error_conexion.php';
        exit;
    }

    http_response_code(500);
    echo 'Error de configuración: verifica los datos de conexión a la base de datos.';
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

    case 'pdf':
        $controller->generatePdf((int) ($_GET['id'] ?? 0));
        break;

    case 'plotter':
        $controller->showPlotterDetail();
        break;

    default:
        http_response_code(404);
        echo 'Acción no válida.';
}
