<?php
declare(strict_types=1);
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$estados = ['PENDIENTE', 'EN PROCESO', 'COMPLETADO', 'CANCELADO'];
$estadoColor = [
    'PENDIENTE'   => 'secondary',
    'EN PROCESO'  => 'primary',
    'COMPLETADO'  => 'success',
    'CANCELADO'   => 'danger',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Campañas | Industrial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/campanas.css">
    <style>
        body { background: #f0f4f8; }

        /* ── Navbar ── */
        .camp-nav { background: linear-gradient(135deg, #0a1628 0%, #1a3a5c 100%); }
        .camp-nav .navbar-brand { color:#fff; font-weight:700; }

        /* ── Cards ── */
        .camp-card {
            border: none; border-radius: 16px;
            box-shadow: 0 4px 18px rgba(0,0,0,.09);
            transition: transform .2s, box-shadow .2s;
            overflow: hidden;
        }
        .camp-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,.14); }
        .camp-card .card-header {
            background: linear-gradient(135deg, #1a3a5c, #243b5e);
            color: #fff; padding: .85rem 1.2rem; border: none;
        }
        .camp-card .card-header .badge { font-size: .72rem; }

        /* ── Action bar en cada card ── */
        .camp-actions { background: #f8f9ff; border-top: 1px solid #e5e7ff; padding: .6rem 1rem; }
        .camp-actions .btn { border-radius: 8px; font-size: .8rem; }

        /* ── Info row ── */
        .info-row { font-size: .82rem; color: #555; }

        /* ── Modales ── */
        .modal-content { border-radius: 16px; border: none; }
        .modal-header  { border-bottom: 2px solid #e5e7ff; }

        /* ── Empty state ── */
        .empty-state .bi { font-size: 3.5rem; opacity: .3; }

        /* ── Flash ── */
        .flash-bar { border-radius: 12px; }
    </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar camp-nav shadow-sm mb-4 px-3">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-megaphone-fill text-warning fs-5"></i>
        <span class="navbar-brand mb-0">Gestor de Campañas</span>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?action=materiales_list" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-boxes"></i> Materia Prima
        </a>
        <a href="index.php?action=dashboard" class="btn btn-outline-light btn-sm">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </div>
</nav>

<div class="container py-2 pb-5">

    <!-- ── FLASH ── -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show flash-bar mb-4" role="alert">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= $flash['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ── HEADER ── -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 fw-bold">Campañas</h2>
            <p class="text-muted small mb-0"><?= count($campanas) ?> campaña(s) registrada(s)</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaCampana">
            <i class="bi bi-plus-circle me-1"></i> Nueva Campaña
        </button>
    </div>

    <!-- ── GRID DE CAMPAÑAS ── -->
    <div class="row g-4">
        <?php if (empty($campanas)): ?>
            <div class="col-12 text-center py-5 empty-state">
                <i class="bi bi-megaphone d-block mb-3"></i>
                <p class="text-muted">No hay campañas registradas aún.</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaCampana">
                    <i class="bi bi-plus-circle"></i> Crear la primera
                </button>
            </div>
        <?php else: ?>
        <?php foreach ($campanas as $c):
            $badgeColor = $estadoColor[$c['estado']] ?? 'secondary';
            $fecha = (new DateTime($c['fecha_creacion']))->format('d/m/Y');
        ?>
            <div class="col-xl-3 col-md-4 col-sm-6">
                <div class="card camp-card h-100">

                    <!-- Header con nombre y estado -->
                    <div class="card-header d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold" style="line-height:1.2">
                                <?= htmlspecialchars($c['nombre']) ?>
                            </div>
                            <small class="opacity-75">Req: <?= htmlspecialchars($c['requerimiento_nro'] ?: '—') ?></small>
                        </div>
                        <span class="badge bg-<?= $badgeColor ?> ms-2 mt-1">
                            <?= htmlspecialchars($c['estado']) ?>
                        </span>
                    </div>

                    <!-- Body -->
                    <div class="card-body py-3">
                        <div class="info-row d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-calendar3 text-muted"></i>
                            <span>Creada: <strong><?= $fecha ?></strong></span>
                        </div>
                        <a href="index.php?action=campana_detail&id=<?= (int)$c['id'] ?>"
                           class="btn btn-primary w-100 btn-sm">
                            <i class="bi bi-eye me-1"></i> Ver Detalle
                        </a>
                    </div>

                    <!-- Acciones editar / borrar -->
                    <div class="camp-actions d-flex gap-2">
                        <!-- Editar -->
                        <button class="btn btn-outline-primary btn-sm flex-fill btn-editar-campana"
                                data-id="<?= (int)$c['id'] ?>"
                                data-nombre="<?= htmlspecialchars($c['nombre'], ENT_QUOTES) ?>"
                                data-req="<?= htmlspecialchars($c['requerimiento_nro'] ?? '', ENT_QUOTES) ?>"
                                data-estado="<?= htmlspecialchars($c['estado'], ENT_QUOTES) ?>"
                                title="Editar campaña">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        <!-- Borrar -->
                        <button class="btn btn-outline-danger btn-sm flex-fill btn-borrar-campana"
                                data-id="<?= (int)$c['id'] ?>"
                                data-nombre="<?= htmlspecialchars($c['nombre'], ENT_QUOTES) ?>"
                                title="Eliminar campaña">
                            <i class="bi bi-trash"></i> Borrar
                        </button>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div><!-- /row -->

</div><!-- /container -->


<!-- ═══════════════ MODAL: NUEVA CAMPAÑA ═══════════════ -->
<div class="modal fade" id="modalNuevaCampana" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="index.php?action=campana_store">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-megaphone me-2 text-primary"></i>Nueva Campaña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre de la Campaña <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required
                               placeholder="Ej: Navidad 2025">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Número de Requerimiento</label>
                        <input type="text" name="requerimiento_nro" class="form-control"
                               placeholder="REQ-001">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Crear Campaña
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- ═══════════════ MODAL: EDITAR CAMPAÑA ═══════════════ -->
<div class="modal fade" id="modalEditarCampana" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="index.php?action=campana_update">
            <input type="hidden" name="id" id="editCampanaId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2 text-primary"></i>Editar Campaña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="editCampanaNombre"
                               class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">N° de Requerimiento</label>
                        <input type="text" name="requerimiento_nro" id="editCampanaReq"
                               class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select name="estado" id="editCampanaEstado" class="form-select">
                            <?php foreach ($estados as $e): ?>
                                <option value="<?= $e ?>"><?= $e ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- ═══════════════ MODAL: CONFIRMAR BORRAR ═══════════════ -->
<div class="modal fade" id="modalBorrarCampana" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="index.php?action=campana_delete">
            <input type="hidden" name="id" id="deleteCampanaId">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Eliminar Campaña</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-exclamation-triangle-fill text-danger d-block mb-2" style="font-size:2.8rem"></i>
                    <p class="mb-1">¿Eliminar la campaña</p>
                    <p class="fw-bold fs-6" id="deleteCampanaNombre"></p>
                    <p class="text-muted small mb-0">
                        Se eliminarán también <strong>todos los trabajos e ítems</strong>
                        asociados. Esta acción <strong>no se puede deshacer</strong>.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Sí, eliminar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── Modal editar ── */
document.querySelectorAll('.btn-editar-campana').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('editCampanaId').value     = this.dataset.id;
        document.getElementById('editCampanaNombre').value = this.dataset.nombre;
        document.getElementById('editCampanaReq').value    = this.dataset.req;
        document.getElementById('editCampanaEstado').value = this.dataset.estado;
        new bootstrap.Modal(document.getElementById('modalEditarCampana')).show();
    });
});

/* ── Modal borrar ── */
document.querySelectorAll('.btn-borrar-campana').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('deleteCampanaId').value      = this.dataset.id;
        document.getElementById('deleteCampanaNombre').textContent = `"${this.dataset.nombre}"`;
        new bootstrap.Modal(document.getElementById('modalBorrarCampana')).show();
    });
});
</script>
</body>
</html>
