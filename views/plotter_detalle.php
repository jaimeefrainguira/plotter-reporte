<?php
declare(strict_types=1);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($plotter) ?> | Reportes Plotter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="public/css/styles.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-printer"></i> <?= htmlspecialchars($plotter) ?></span>
        <div class="d-flex gap-2">
            <a href="index.php?action=dashboard&fecha=<?= urlencode($fecha) ?>" class="btn btn-outline-light">
                <i class="bi bi-arrow-left"></i> Volver al dashboard
            </a>
            <a href="index.php?action=create" class="btn btn-success"><i class="bi bi-plus-circle"></i> Nuevo reporte</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="action" value="plotter">
                <input type="hidden" name="plotter" value="<?= htmlspecialchars($plotter) ?>">
                <div class="col-md-4">
                    <label class="form-label">Fecha del reporte</label>
                    <input type="date" class="form-control" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
                </div>
                <div class="col-md-8 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" type="submit">Buscar</button>
                    <?php if ($fecha !== ''): ?>
                        <a class="btn btn-outline-secondary" href="index.php?action=plotter&plotter=<?= urlencode($plotter) ?>">Ver todos</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Observación</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Cant. impreso</th>
                        <th>% impreso</th>
                        <th>Fecha</th>
                        <th>CRUD</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reportes as $reporte): ?>
                        <tr>
                            <td><?= (int) $reporte['id'] ?></td>
                            <td><?= htmlspecialchars($reporte['observacion']) ?></td>
                            <td><?= htmlspecialchars($reporte['descripcion']) ?></td>
                            <td><?= (int) $reporte['cantidad'] ?></td>
                            <td><?= (int) ($reporte['cantidad_impreso'] ?? 0) ?></td>
                            <td><?= (int) $reporte['porcentaje_impresion'] ?>%</td>
                            <td><?= htmlspecialchars($reporte['fecha']) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="index.php?action=edit&id=<?= (int) $reporte['id'] ?>" class="btn btn-warning" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="index.php?action=pdf&id=<?= (int) $reporte['id'] ?>" class="btn btn-info" title="PDF por reporte">
                                        <i class="bi bi-filetype-pdf"></i>
                                    </a>
                                    <form action="index.php?action=delete" method="post" class="d-inline form-delete">
                                        <input type="hidden" name="id" value="<?= (int) $reporte['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit" class="btn btn-danger" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$reportes): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No hay reportes para este plotter<?= $fecha !== '' ? ' en la fecha seleccionada' : '' ?>.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>
