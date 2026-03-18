<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Campañas | Industrial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/campanas.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark shadow-sm mb-4">
    <div class="container">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-megaphone"></i> Gestor de Campañas</span>
        <div class="d-flex gap-2">
            <a href="index.php?action=plotter_report" class="btn btn-primary btn-sm"><i class="bi bi-file-earmark-plus"></i> CREAR REPORTE PLOTTER</a>
            <a href="index.php?action=dashboard" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Reportes Plotter</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Campañas Activas</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaCampana">
            <i class="bi bi-plus-circle"></i> Nueva Campaña
        </button>
    </div>

    <div class="row g-4">
        <?php foreach ($campanas as $campana): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title text-primary"><?= htmlspecialchars($campana['nombre']) ?></h5>
                            <span class="badge bg-secondary"><?= htmlspecialchars($campana['estado']) ?></span>
                        </div>
                        <p class="text-muted small mb-3">Req: <?= htmlspecialchars($campana['requerimiento_nro']) ?></p>
                        
                        <div class="progress mb-3" style="height: 10px;">
                            <div class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        
                        <div class="d-grid">
                            <a href="index.php?action=campana_detail&id=<?= $campana['id'] ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye"></i> VER DETALLE
                            </a>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-muted small">
                        Iniciado: <?= (new DateTime($campana['fecha_creacion']))->format('d/m/Y') ?>
                    </div>
                </div>
            </div>
        <?php endforeach; if(empty($campanas)): ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No hay campañas registradas aún.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nueva Campaña -->
<div class="modal fade" id="modalNuevaCampana" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="index.php?action=campana_store">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Nueva Campaña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Campaña</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej: Navidad 2024">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Número de Requerimiento</label>
                        <input type="text" name="requerimiento_nro" class="form-control" placeholder="REQ-001">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Campaña</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
