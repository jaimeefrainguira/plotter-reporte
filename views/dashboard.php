<?php
declare(strict_types=1);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$today = date('Y-m-d');
$defaultModalDate = $filters['fecha'] !== '' ? $filters['fecha'] : $today;
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
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-speedometer2"></i> Panel de Reportes Plotter</span>
        <div class="d-flex gap-2">
            <a href="index.php?action=pdf" class="btn btn-outline-light"><i class="bi bi-file-earmark-pdf"></i> PDF general</a>
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

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Total de reportes</h5>
                    <p class="display-6 mb-0"><?= (int) $stats['total'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Último reporte</h5>
                    <?php if ($stats['latest']): ?>
                        <p class="mb-1"><strong><?= htmlspecialchars($stats['latest']['plotter']) ?></strong></p>
                        <small><?= htmlspecialchars($stats['latest']['descripcion']) ?> - <?= htmlspecialchars($stats['latest']['fecha']) ?></small>
                    <?php else: ?>
                        <p class="text-muted mb-0">Sin registros todavía.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Reportes por plotter</h5>
                    <ul class="list-unstyled mb-0 small">
                        <?php foreach ($stats['perPlotter'] as $row): ?>
                            <li><?= htmlspecialchars($row['plotter']) ?>: <strong><?= (int) $row['total'] ?></strong></li>
                        <?php endforeach; ?>
                        <?php if (!$stats['perPlotter']): ?>
                            <li class="text-muted">Sin datos para mostrar.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="action" value="dashboard">
                <div class="col-md-4">
                    <label class="form-label">Filtrar tablero por fecha</label>
                    <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($filters['fecha']) ?>">
                </div>
                <div class="col-md-8 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
                    <a href="index.php?action=dashboard" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="plotter-grid">
        <?php foreach ($plotters as $plotter): ?>
            <?php $plotterRows = $reportesByPlotter[$plotter] ?? []; ?>
            <div class="plotter-box">
                <div class="plotter-box__title">
                    <a class="plotter-box__link" href="index.php?action=dashboard&modal_plotter=<?= urlencode($plotter) ?>&modal_fecha=<?= urlencode($defaultModalDate) ?>">
                        <?= htmlspecialchars($plotter) ?>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table plotter-table mb-0">
                        <thead>
                        <tr>
                            <th>CAMPAÑA</th>
                            <th>DESCRIPCIÓN</th>
                            <th>CANT. IMPRESO</th>
                            <th>% IMPRESO</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach (array_slice($plotterRows, 0, 4) as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['observacion']) ?></td>
                                <td><?= htmlspecialchars($row['descripcion']) ?></td>
                                <td><?= (int) ($row['cantidad_impreso'] ?? 0) ?></td>
                                <td><?= (int) ($row['porcentaje_impresion'] ?? 0) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                        <?php for ($i = count($plotterRows); $i < 4; $i++): ?>
                            <tr>
                                <td>&nbsp;</td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="plotterModal" tabindex="-1" aria-hidden="true" data-open-on-load="<?= $modalShouldOpen ? '1' : '0' ?>">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?= $modalPlotter !== '' ? 'Reportes de ' . htmlspecialchars($modalPlotter) : 'Reportes por plotter' ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="get" class="row g-2 mb-3">
                    <input type="hidden" name="action" value="dashboard">
                    <input type="hidden" name="modal_plotter" value="<?= htmlspecialchars($modalPlotter) ?>">
                    <div class="col-md-4">
                        <label class="form-label">Fecha del reporte</label>
                        <input type="date" class="form-control" name="modal_fecha" value="<?= htmlspecialchars($modalDate) ?>">
                    </div>
                    <div class="col-md-8 d-flex align-items-end gap-2">
                        <button class="btn btn-primary" type="submit">Buscar</button>
                        <?php if ($modalPlotter !== ''): ?>
                            <a class="btn btn-success" href="index.php?action=create">Nuevo reporte para <?= htmlspecialchars($modalPlotter) ?></a>
                        <?php endif; ?>
                    </div>
                </form>

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
                        <?php foreach ($modalReportes as $reporte): ?>
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
                        <?php if (!$modalReportes): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No hay reportes para este plotter y fecha.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="public/js/app.js"></script>
<script src="js/app.js"></script>
</body>
</html>
