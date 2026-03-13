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
    <title>Dashboard | Reportes Plotter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="public/css/styles.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid d-flex justify-content-between">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-speedometer2"></i> Panel de Reportes Plotter</span>
        <div class="d-flex gap-2">
            <a href="index.php?action=pdf" class="btn btn-outline-light"><i class="bi bi-file-earmark-pdf"></i> Generar PDF</a>
            <a href="index.php?action=create" class="btn btn-success"><i class="bi bi-plus-circle"></i> Nuevo reporte</a>
        </div>
    </div>
</nav>

<div class="container-fluid py-4">
    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body"><h5 class="card-title">Total de reportes</h5><p class="display-6 mb-0"><?= (int) $stats['total'] ?></p></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body"><h5 class="card-title">Último reporte</h5><?php if ($stats['latest']): ?><p class="mb-1"><strong><?= htmlspecialchars($stats['latest']['plotter']) ?></strong></p><small><?= htmlspecialchars($stats['latest']['descripcion']) ?> - <?= htmlspecialchars($stats['latest']['fecha']) ?></small><?php else: ?><p class="text-muted mb-0">Sin registros todavía.</p><?php endif; ?></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body"><h5 class="card-title">Reportes por plotter</h5><ul class="list-unstyled mb-0 small"><?php foreach ($stats['perPlotter'] as $row): ?><li><?= htmlspecialchars($row['plotter']) ?>: <strong><?= (int) $row['total'] ?></strong></li><?php endforeach; ?><?php if (!$stats['perPlotter']): ?><li class="text-muted">Sin datos para mostrar.</li><?php endif; ?></ul></div></div></div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Reportes por plotter (día <?= htmlspecialchars($dailyDate) ?>)</h5>
        <?php if ($selectedPlotter !== ''): ?>
            <a href="index.php?action=dashboard" class="btn btn-sm btn-outline-secondary">Limpiar selección</a>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ($plotters as $plotter): ?>
            <?php $rows = $dailyReportsByPlotter[$plotter] ?? []; ?>
            <?php $url = 'index.php?action=dashboard&selected_plotter=' . urlencode($plotter); ?>
            <div class="col-12 col-lg-4">
                <div class="plotter-board h-100 <?= $selectedPlotter === $plotter ? 'plotter-board--active' : '' ?>">
                    <div class="plotter-board__title"><?= htmlspecialchars($plotter) ?></div>
                    <div class="table-responsive">
                        <table class="table table-bordered plotter-board__table mb-0 js-plotter-table" data-url="<?= htmlspecialchars($url) ?>">
                            <thead><tr><th>CAMPAÑA</th><th>DESCRIPCIÓN</th><th>CANT. IMPRESO</th><th>% IMPRESO</th></tr></thead>
                            <tbody>
                            <?php if ($rows): ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr><td><?= htmlspecialchars((string) $row['observacion']) ?></td><td><?= htmlspecialchars((string) $row['descripcion']) ?></td><td><?= (int) ($row['cantidad_impreso'] ?? 0) ?></td><td><?= (int) ($row['porcentaje_impresion'] ?? 0) ?>%</td></tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted">Sin reportes de hoy.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="plotter-board__footer">
                        <a href="<?= htmlspecialchars($url) ?>" class="btn btn-sm btn-primary">Ver todos los reportes</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($selectedPlotter !== ''): ?>
        <div class="card shadow-sm elegant-table-card">
            <div class="card-body">
                <h5 class="mb-3">Reportes de <?= htmlspecialchars($selectedPlotter) ?> (todos)</h5>
                <div class="table-responsive">
                    <table class="table elegant-table align-middle">
                        <thead>
                            <tr>
                                <th>CAMPAÑA</th>
                                <th>DESCRIPCIÓN</th>
                                <th>CANT. IMPRESO</th>
                                <th>% IMPRESO</th>
                                <th>FECHA</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($selectedPlotterReports): ?>
                            <?php foreach ($selectedPlotterReports as $reporte): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $reporte['observacion']) ?></td>
                                    <td><?= htmlspecialchars((string) $reporte['descripcion']) ?></td>
                                    <td><?= (int) ($reporte['cantidad_impreso'] ?? 0) ?></td>
                                    <td><?= (int) ($reporte['porcentaje_impresion'] ?? 0) ?>%</td>
                                    <td><?= htmlspecialchars((string) $reporte['fecha']) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?action=edit&id=<?= (int) $reporte['id'] ?>" class="btn btn-warning" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                            <a href="index.php?action=pdf&id=<?= (int) $reporte['id'] ?>" class="btn btn-info" title="Generar PDF por reporte"><i class="bi bi-filetype-pdf"></i></a>
                                            <form action="index.php?action=delete" method="post" class="d-inline form-delete">
                                                <input type="hidden" name="id" value="<?= (int) $reporte['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <button type="submit" class="btn btn-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No hay reportes para este plotter.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="public/js/app.js"></script>
<script src="js/app.js"></script>
</body>
</html>
