<?php
declare(strict_types=1);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Determinar categorías de color de badge
$tipoBadge = [
    'lona'     => 'primary',
    'adhesivo' => 'warning text-dark',
    'papel'    => 'info text-dark',
    'tela'     => 'success',
    'vinilo'   => 'danger',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materia Prima | Plotter Reportes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        :root {
            --mp-primary:   #0d6efd;
            --mp-success:   #198754;
            --mp-warning:   #ffc107;
            --mp-danger:    #dc3545;
            --mp-card-bg:   #fff;
            --mp-muted:     #6c757d;
        }

        body { background: #f0f4f8; }

        /* ── navbar ── */
        .mp-navbar { background: linear-gradient(135deg, #0a1628 0%, #1a2f5a 100%); }
        .mp-navbar .navbar-brand { color: #fff; font-weight: 700; letter-spacing: .5px; }

        /* ── stat cards ── */
        .stat-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.13); }
        .stat-card .stat-icon {
            width: 52px; height: 52px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: #fff;
        }
        .stat-card .stat-value { font-size: 2rem; font-weight: 700; line-height: 1; }
        .stat-card .stat-label { font-size: .8rem; color: var(--mp-muted); text-transform: uppercase; letter-spacing: .05em; }

        /* ── table card ── */
        .table-card {
            border: none; border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            overflow: hidden;
        }
        .table-card .card-header {
            background: linear-gradient(135deg, #0a1628, #1a2f5a);
            color: #fff; border: none; padding: 1rem 1.5rem;
        }
        .mp-table thead th {
            background: #f8f9ff; color: #374151;
            font-size: .75rem; text-transform: uppercase;
            letter-spacing: .08em; border-bottom: 2px solid #e5e7ff;
            padding: .75rem 1rem; white-space: nowrap;
        }
        .mp-table tbody tr {
            transition: background .15s;
        }
        .mp-table tbody tr:hover { background: #f0f4ff; }
        .mp-table td { vertical-align: middle; padding: .65rem 1rem; }

        /* ── stock badge ── */
        .stock-ok   { color: var(--mp-success); font-weight: 600; }
        .stock-low  { color: var(--mp-danger);  font-weight: 700; }
        .stock-warn { color: #e67e00;            font-weight: 600; }

        /* ── action btns ── */
        .btn-action { padding: .25rem .55rem; font-size: .8rem; border-radius: 8px; }

        /* ── modal ── */
        .modal-content { border-radius: 16px; border: none; }
        .modal-header  { border-bottom: 2px solid #e5e7ff; }

        /* ── empty state ── */
        .empty-state { padding: 3rem 1rem; color: var(--mp-muted); }
        .empty-state .bi { font-size: 3rem; opacity: .4; }

        /* ── filter bar ── */
        .filter-bar { background: #fff; border-radius: 12px; padding: 1rem 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,.06); margin-bottom: 1.5rem; }
    </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar mp-navbar px-3">
    <div class="d-flex align-items-center gap-3">
        <span class="navbar-brand mb-0">
            <i class="bi bi-boxes me-2"></i>Materia Prima
        </span>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?action=dashboard" class="btn btn-outline-light btn-sm">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrear">
            <i class="bi bi-plus-circle"></i> Nuevo Material
        </button>
    </div>
</nav>

<div class="container-fluid px-4 py-4">

    <!-- ── FLASH ── -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
            <?= $flash['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ── STAT CARDS ── -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#0d6efd22; color:#0d6efd; font-size:1.6rem;">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div>
                        <div class="stat-value text-primary"><?= (int)$stats['total'] ?></div>
                        <div class="stat-label">Total materiales</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#19875422; color:#198754; font-size:1.6rem;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-value text-success"><?= (int)$stats['activos'] ?></div>
                        <div class="stat-label">Activos</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#dc354522; color:#dc3545; font-size:1.6rem;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="stat-value text-danger"><?= (int)$stats['stock_bajo'] ?></div>
                        <div class="stat-label">Stock bajo</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#ffc10722; color:#b8860b; font-size:1.6rem;">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <div class="stat-value" style="color:#b8860b; font-size:1.4rem;">
                            $<?= number_format((float)$stats['valor_inventario'], 2) ?>
                        </div>
                        <div class="stat-label">Valor inventario</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── FILTER BAR ── -->
    <div class="filter-bar d-flex flex-wrap gap-2 align-items-center">
        <i class="bi bi-funnel text-muted"></i>
        <input type="text" id="searchInput" class="form-control form-control-sm" style="max-width:220px"
               placeholder="Buscar material…">
        <select id="filterTipo" class="form-select form-select-sm" style="max-width:180px">
            <option value="">Todos los tipos</option>
            <?php foreach ($tipos as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars(ucfirst($t)) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="filterEstado" class="form-select form-select-sm" style="max-width:160px">
            <option value="">Todo estado</option>
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
            <option value="bajo">Stock bajo</option>
        </select>
        <button class="btn btn-outline-secondary btn-sm" id="clearFilters">
            <i class="bi bi-x-circle"></i> Limpiar
        </button>
        <span class="ms-auto text-muted small" id="countLabel"></span>
    </div>

    <!-- ── TABLE ── -->
    <div class="card table-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-table me-2"></i>Listado de Materiales</span>
            <span class="badge bg-light text-dark"><?= count($materiales) ?> registros</span>
        </div>
        <div class="table-responsive">
            <table class="table mp-table mb-0" id="materialesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Ancho (cm)</th>
                        <th>Rollo (m)</th>
                        <th>Precio/Rollo</th>
                        <th>Stock Rollos</th>
                        <th>Stock Mín.</th>
                        <th>Estado</th>
                        <th>Notas</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($materiales)): ?>
                    <tr>
                        <td colspan="11">
                            <div class="empty-state text-center">
                                <i class="bi bi-inbox d-block mb-2"></i>
                                No hay materiales registrados.<br>
                                <small>Haz clic en <strong>Nuevo Material</strong> para empezar.</small>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($materiales as $m): ?>
                    <?php
                        $stockOk = (float)$m['stock_rollos'] > (float)$m['stock_minimo'];
                        $stockEq = (float)$m['stock_rollos'] == (float)$m['stock_minimo'];
                        $stockClass = $stockOk ? 'stock-ok' : ($stockEq ? 'stock-warn' : 'stock-low');
                        $stockIcon  = $stockOk ? 'check-circle' : ($stockEq ? 'dash-circle' : 'exclamation-triangle-fill');
                        $badge = $tipoBadge[strtolower($m['tipo'])] ?? 'secondary';
                    ?>
                    <tr data-tipo="<?= htmlspecialchars($m['tipo']) ?>"
                        data-activo="<?= $m['activo'] ? 'activo' : 'inactivo' ?>"
                        data-stock="<?= (float)$m['stock_rollos'] <= (float)$m['stock_minimo'] ? 'bajo' : 'ok' ?>">
                        <td class="text-muted small"><?= (int)$m['id'] ?></td>
                        <td><strong><?= htmlspecialchars($m['nombre']) ?></strong></td>
                        <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars(ucfirst($m['tipo'])) ?></span></td>
                        <td><?= number_format((float)$m['ancho_cm'], 0) ?></td>
                        <td><?= number_format((float)$m['largo_rollo_m'], 0) ?></td>
                        <td><?= (float)$m['precio_rollo'] > 0 ? '$' . number_format((float)$m['precio_rollo'], 2) : '<span class="text-muted">—</span>' ?></td>
                        <td>
                            <span class="<?= $stockClass ?>">
                                <i class="bi bi-<?= $stockIcon ?> me-1"></i>
                                <?= number_format((float)$m['stock_rollos'], 2) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= number_format((float)$m['stock_minimo'], 2) ?></td>
                        <td>
                            <?php if ($m['activo']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-circle-fill" style="font-size:.5rem"></i> Activo
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    <i class="bi bi-circle" style="font-size:.5rem"></i> Inactivo
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small" style="max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                            title="<?= htmlspecialchars($m['notas'] ?? '') ?>">
                            <?= htmlspecialchars(mb_strimwidth($m['notas'] ?? '', 0, 40, '…')) ?>
                        </td>
                        <td class="text-center" style="white-space:nowrap;">
                            <!-- Ajustar stock -->
                            <button class="btn btn-outline-info btn-action"
                                    title="Ajustar stock"
                                    onclick="abrirAjusteStock(<?= (int)$m['id'] ?>, '<?= htmlspecialchars($m['nombre'], ENT_QUOTES) ?>', <?= number_format((float)$m['stock_rollos'], 2, '.', '') ?>)">
                                <i class="bi bi-layers"></i>
                            </button>
                            <!-- Editar -->
                            <a href="index.php?action=material_edit&id=<?= (int)$m['id'] ?>"
                               class="btn btn-outline-primary btn-action" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <!-- Eliminar -->
                            <button class="btn btn-outline-danger btn-action"
                                    title="Eliminar"
                                    onclick="confirmarEliminar(<?= (int)$m['id'] ?>, '<?= htmlspecialchars($m['nombre'], ENT_QUOTES) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div><!-- /table-card -->

</div><!-- /container -->

<!-- ═══════════════════ MODAL: CREAR MATERIAL ═══════════════════ -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-success"></i>Nuevo Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="index.php?action=material_store">
                <div class="modal-body">
                    <?php include __DIR__ . '/form_fields.php'; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-floppy"></i> Guardar Material
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════ MODAL: AJUSTAR STOCK ═══════════════════ -->
<div class="modal fade" id="modalStock" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-layers me-2 text-info"></i>Ajustar Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="index.php?action=material_stock">
                <input type="hidden" name="id" id="stockId">
                <div class="modal-body">
                    <p class="mb-1 text-muted small">Material:</p>
                    <p class="mb-3 fw-bold" id="stockNombre">—</p>
                    <p class="mb-1 text-muted small">Stock actual: <strong id="stockActual">—</strong> rollos</p>
                    <label class="form-label mt-2">Ajuste (+ añadir / - restar rollos)</label>
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary" onclick="cambiarSigno()">±</button>
                        <input type="number" name="delta" id="stockDelta" class="form-control"
                               step="0.5" min="-999" max="999" value="1" required>
                        <span class="input-group-text">rollos</span>
                    </div>
                    <div class="d-flex gap-2 mt-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="setDelta(1)">+1</button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="setDelta(5)">+5</button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="setDelta(10)">+10</button>
                        <button type="button" class="btn btn-sm btn-outline-danger"  onclick="setDelta(-1)">-1</button>
                        <button type="button" class="btn btn-sm btn-outline-danger"  onclick="setDelta(-5)">-5</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white"><i class="bi bi-check2"></i> Aplicar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════ MODAL: CONFIRMAR ELIMINAR ═══════════════════ -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Eliminar Material</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="index.php?action=material_delete">
                <input type="hidden" name="id" id="deleteId">
                <div class="modal-body text-center py-3">
                    <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:2.5rem"></i>
                    <p class="mt-2">¿Eliminar el material <strong id="deleteNombre"></strong>?</p>
                    <p class="text-muted small">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── Filtros de tabla ── */
const rows        = document.querySelectorAll('#materialesTable tbody tr[data-tipo]');
const searchInput = document.getElementById('searchInput');
const filterTipo  = document.getElementById('filterTipo');
const filterEst   = document.getElementById('filterEstado');
const countLabel  = document.getElementById('countLabel');

function applyFilters() {
    const q    = searchInput.value.toLowerCase();
    const tipo = filterTipo.value.toLowerCase();
    const est  = filterEst.value.toLowerCase();
    let visible = 0;
    rows.forEach(r => {
        const text   = r.textContent.toLowerCase();
        const rTipo  = (r.dataset.tipo  || '').toLowerCase();
        const rAct   = (r.dataset.activo || '').toLowerCase();
        const rStock = (r.dataset.stock  || '').toLowerCase();

        const matchQ   = !q    || text.includes(q);
        const matchT   = !tipo || rTipo === tipo;
        let   matchE   = true;
        if (est === 'activo')   matchE = rAct === 'activo';
        if (est === 'inactivo') matchE = rAct === 'inactivo';
        if (est === 'bajo')     matchE = rStock === 'bajo';

        const show = matchQ && matchT && matchE;
        r.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    countLabel.textContent = `${visible} de ${rows.length} registros`;
}

searchInput.addEventListener('input', applyFilters);
filterTipo.addEventListener('change', applyFilters);
filterEst.addEventListener('change', applyFilters);
document.getElementById('clearFilters').addEventListener('click', () => {
    searchInput.value = '';
    filterTipo.value  = '';
    filterEst.value   = '';
    applyFilters();
});
applyFilters();

/* ── Modal stock ── */
function abrirAjusteStock(id, nombre, actual) {
    document.getElementById('stockId').value     = id;
    document.getElementById('stockNombre').textContent = nombre;
    document.getElementById('stockActual').textContent = actual;
    document.getElementById('stockDelta').value  = 1;
    new bootstrap.Modal(document.getElementById('modalStock')).show();
}
function cambiarSigno() {
    const el = document.getElementById('stockDelta');
    el.value = (parseFloat(el.value) * -1).toString();
}
function setDelta(v) {
    document.getElementById('stockDelta').value = v;
}

/* ── Modal eliminar ── */
function confirmarEliminar(id, nombre) {
    document.getElementById('deleteId').value          = id;
    document.getElementById('deleteNombre').textContent = nombre;
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>
</body>
</html>
