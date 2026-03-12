<?php

declare(strict_types=1);

$useSecureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $useSecureCookie,
    ]);
} else {
    session_set_cookie_params(0, '/; samesite=Lax', '', $useSecureCookie, true);
}

session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; object-src 'none'; frame-ancestors 'self';");

require_once __DIR__ . '/controllers/ReporteController.php';

function renderFatalError(string $message, ?Throwable $exception = null): void
{
    $debugEnabled = (string) getenv('APP_DEBUG') === '1';
    $detail = '';

    if ($exception !== null) {
        error_log('[plotter-reporte] ' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
        if ($debugEnabled) {
            $detail = '<pre style="white-space:pre-wrap;background:#f8f9fa;border:1px solid #dee2e6;padding:12px;border-radius:8px;">'
                . htmlspecialchars($exception->getMessage() . "\n\n" . $exception->getTraceAsString(), ENT_QUOTES, 'UTF-8')
                . '</pre>';
        }
    }

    http_response_code(500);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Error del sistema</title>'
        . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light">'
        . '<div class="container py-5"><div class="alert alert-danger">'
        . '<h4 class="alert-heading">Error de configuración</h4>'
        . '<p class="mb-0">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</div>'
        . '<p class="text-muted small">Verifica credenciales MySQL, existencia de la base de datos y versión de PHP (8+ recomendado).</p>'
        . $detail
        . '</div></body></html>';
    exit;
}

try {
    $controller = new ReporteController();
} catch (Throwable $exception) {
    renderFatalError('No fue posible inicializar la aplicación. Revisa la conexión a la base de datos.', $exception);
}

$action = $_GET['action'] ?? 'dashboard';

try {
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
} catch (Throwable $exception) {
    renderFatalError('Ocurrió un error inesperado al procesar la solicitud.', $exception);
}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Primera Página Web</title>
</head>
<body>

    <h1>Bienvenido a mi página web</h1>
    <p>Esta es una página creada solo con HTML.</p>

    <h2>Sobre mí</h2>
    <p>Estoy aprendiendo a crear páginas web.</p>

    <h2>Mis servicios</h2>
    <ul>
        <li>Impresión de documentos</li>
        <li>Diseño básico</li>
        <li>Venta de tazas personalizadas</li>
    </ul>

    <h2>Imagen de ejemplo</h2>a
    <img src="https://via.placeholder.com/400" alt="Imagen de ejemplo">

    <h2>Formulario de contacto</h2>
    <form>
        <label>Nombre:</label><br>
        <input type="text" name="nombre"><br><br>

        <label>Email:</label><br>
        <input type="email" name="email"><br><br>

        <label>Mensaje:</label><br>
        <textarea name="mensaje"></textarea><br><br>

        <button type="submit">Enviar</button>
    </form>

    <hr>

    <footer>
        <p>© 2026 Mi Página Web</p>
    </footer>

</body>
</html>
