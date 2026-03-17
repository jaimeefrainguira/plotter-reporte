<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conexión de base de datos | Reportes Plotter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-speedometer2"></i> Panel de Reportes Plotter</span>
    </div>
</nav>

<div class="container py-5">
    <div class="alert alert-warning shadow-sm" role="alert">
        <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Conexión no disponible</h4>
        <p class="mb-2"><?= htmlspecialchars($errorMessage ?? 'No fue posible conectar con la base de datos.') ?></p>
        <hr>
        <p class="mb-0">Cuando la base de datos esté disponible, recarga la página para volver a operar normalmente.</p>
    </div>
</div>
</body>
</html>
