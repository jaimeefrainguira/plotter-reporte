<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; object-src 'none'; frame-ancestors 'self';");

require_once __DIR__ . '/controllers/ReporteController.php';

try {
    $controller = new ReporteController();
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Error de configuración: verifica los datos de conexión a la base de datos.';
    exit;
}

$action = $_GET['action'] ?? 'dashboard';

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

    default:
        http_response_code(404);
        echo 'Acción no válida.';
}
