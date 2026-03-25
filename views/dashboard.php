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
    <link href="css/styles.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-speedometer2"></i> Panel de Reportes Plotter</span>
        <div class="d-flex gap-2 flex-wrap">
            <a href="index.php?action=materiales_list" class="btn btn-outline-warning"><i class="bi bi-boxes"></i> Materia Prima</a>
            <a href="index.php?action=campanas_list" class="btn btn-outline-info"><i class="bi bi-megaphone"></i> Gestionar Campañas</a>
            <a href="index.php?action=pdf<?= $filters['fecha'] !== '' ? '&fecha=' . urlencode($filters['fecha']) : '' ?>" class="btn btn-outline-light"><i class="bi bi-file-earmark-pdf"></i> PDF general</a>
            <a href="index.php?action=plotter_report" class="btn btn-success"><i class="bi bi-file-earmark-plus"></i> CREAR REPORTE PLOTTER</a>
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

    <!-- MÓDULO MES: Progreso de Campañas -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-megaphone-fill me-2"></i> Estado Global de Campañas</h5>
            <a href="index.php?action=campanas_list" class="btn btn-sm btn-outline-info">Ver todas</a>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php foreach ($campanasActivas as $c): 
                    $p = $c['progreso'];
                    $color = $p['porcentaje'] >= 100 ? 'success' : ($p['porcentaje'] > 0 ? 'primary' : 'secondary');
                ?>
                <div class="list-group-item p-3">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h6 class="mb-0 fw-bold">
                                <a href="index.php?action=campana_detail&id=<?= $c['id'] ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </a>
                            </h6>
                            <small class="text-muted"><?= htmlspecialchars($c['requerimiento_nro']) ?></small>
                        </div>
                        <div class="col-md-6">
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-<?= $color ?> progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: <?= $p['porcentaje'] ?>%"></div>
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <span class="fw-bold fs-5 text-<?= $color ?>"><?= $p['porcentaje'] ?>%</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; if(empty($campanasActivas)): ?>
                <div class="p-4 text-center text-muted small">No hay campañas registradas.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MÓDULO REPORTES: Stats originales -->
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

    <!-- ACCESOS RÁPIDOS A PLOTTERS (OPERADOR) -->
    <div class="row g-3 mb-4">
        <div class="col-12"><h5 class="fw-bold text-muted text-uppercase small">Panel de Operador (Planta)</h5></div>
        <?php foreach ([1,2,3,4,5,6] as $pid): ?>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="index.php?action=campana_ver_produccion&plotter_id=<?= $pid ?>" class="btn btn-outline-dark w-100 py-3 shadow-sm border-2">
                <i class="bi bi-cpu fs-4 d-block mb-1"></i>
                <small class="fw-bold">PLOTTER <?= $pid ?></small>
            </a>
        </div>
        <?php endforeach; ?>
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
    </div>    <div class="plotter-grid">
        <?php foreach ($plotters as $plotter): ?>
            <?php $plotterRows = $reportesByPlotter[$plotter] ?? []; ?>
            <div class="plotter-box">
                <div class="plotter-box__title">
                    <a class="plotter-box__link" href="index.php?action=plotter&plotter=<?= urlencode($plotter) ?>&fecha=<?= urlencode($filters['fecha']) ?>" title="Ver reportes de <?= htmlspecialchars($plotter) ?>">
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
                        <?php foreach ($plotterRows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['observacion']) ?></td>
                                <td><?= htmlspecialchars($row['descripcion']) ?></td>
                                <td><?= (int) ($row['cantidad_impreso'] ?? 0) ?></td>
                                <td class="text-center">
                                    <strong><?= (int) ($row['porcentaje_impresion'] ?? 0) ?>%</strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($plotterRows)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted small">Sin reportes registrados.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>
