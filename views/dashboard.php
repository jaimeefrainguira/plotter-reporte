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
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-speedometer2"></i> Panel de Reportes Plotter</span>
        <div class="d-flex gap-2">
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

    <style>
        .plotter-container {
            margin-bottom: 2rem;
            border: 1px solid #dee2e6;
            background: #fff;
        }
        .plotter-header {
            background-color: #1e252d;
            color: #fff;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .table-custom thead {
            background-color: #2c5d8f;
            color: #fff;
        }
        .table-custom th {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
            border: none;
            padding: 10px;
        }
        .table-custom td {
            font-size: 0.9rem;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }
        .percentage-badge {
            font-weight: bold;
            color: #2c5d8f;
        }
    </style>

    <div class="row">
        <?php foreach ($plotters as $plotter): ?>
            <?php $plotterRows = $reportesByPlotter[$plotter] ?? []; ?>
            <div class="col-12">
                <div class="plotter-container shadow-sm">
                    <div class="plotter-header">
                        <?= htmlspecialchars($plotter) ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">CAMPAÑA</th>
                                    <th style="width: 35%;">DESCRIPCIÓN</th>
                                    <th style="width: 15%; text-align: center;">CANT. IMPRESO</th>
                                    <th style="width: 15%; text-align: center;">% IMPRESO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plotterRows as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['observacion']) ?></td>
                                        <td><?= htmlspecialchars($row['descripcion']) ?></td>
                                        <td class="text-center"><?= (int) ($row['cantidad_impreso'] ?? 0) ?></td>
                                        <td class="text-center">
                                            <span class="percentage-badge"><?= (int) ($row['porcentaje_impresion'] ?? 0) ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($plotterRows)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3 small italic">No hay registros para este plotter en el reporte actual.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>
