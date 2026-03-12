<?php
declare(strict_types=1);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$queryBase = [
    'action' => 'dashboard',
    'plotter' => $filters['plotter'] ?? '',
    'fecha' => $filters['fecha'] ?? '',
];
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
        <a href="index.php?action=create" class="btn btn-success"><i class="bi bi-plus-circle"></i> Nuevo reporte</a>
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

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h5 class="mb-0">Listado de reportes</h5>
                <a href="index.php?action=pdf" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf"></i> Generar PDF</a>
            </div>

            <form method="get" class="row g-2 mb-3">
                <input type="hidden" name="action" value="dashboard">
                <div class="col-md-4">
                    <select name="plotter" class="form-select">
                        <option value="">Filtrar por plotter</option>
                        <?php foreach ($plotters as $plotter): ?>
                            <option value="<?= htmlspecialchars($plotter) ?>" <?= ($filters['plotter'] === $plotter) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($plotter) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($filters['fecha']) ?>">
                </div>
                <div class="col-md-5 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
                    <a href="index.php?action=dashboard" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>Plotter</th>
                        <th>Observación</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>% de impresión</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reportes as $reporte): ?>
                        <tr>
                            <td><?= htmlspecialchars($reporte['plotter']) ?></td>
                            <td><?= nl2br(htmlspecialchars($reporte['observacion'])) ?></td>
                            <td><?= htmlspecialchars($reporte['descripcion']) ?></td>
                            <td><?= (int) $reporte['cantidad'] ?></td>
                            <td><?= (int) $reporte['porcentaje_impresion'] ?>%</td>
                            <td><?= htmlspecialchars($reporte['fecha']) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="index.php?action=edit&id=<?= (int) $reporte['id'] ?>" class="btn btn-warning" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="index.php?action=pdf&id=<?= (int) $reporte['id'] ?>" class="btn btn-info" title="Generar PDF por reporte">
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
                            <td colspan="7" class="text-center text-muted">No hay reportes registrados.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <nav>
                <ul class="pagination justify-content-end mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php $queryBase['page'] = $i; ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?<?= http_build_query($queryBase) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="public/js/app.js"></script>
<script src="js/app.js"></script>
</body>
</html>
