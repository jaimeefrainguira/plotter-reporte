<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Producción Plotter | Panel de Operador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --panel-bg: #0f172a;
            --card-bg: #1e293b;
            --accent: #3b82f6;
            --success: #10b981;
        }
        body { background-color: #020617; color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background-color: var(--panel-bg) !important; border-bottom: 1px solid #334155; }
        .card { background-color: var(--card-bg); border: 1px solid #334155; color: #f8fafc; }
        .table { color: #f8fafc; }
        .table thead { background-color: #0f172a; }
        .btn-primary { background-color: var(--accent); border: none; }
        .btn-success { background-color: var(--success); border: none; }
        .progress { background-color: #334155; border-radius: 10px; }
        .form-control, .form-select { background-color: #0f172a; border: 1px solid #334155; color: #fff; }
        .form-control:focus, .form-select:focus { background-color: #0f172a; color: #fff; border-color: var(--accent); box-shadow: none; }
        .status-badge { font-size: 0.7rem; padding: 4px 8px; border-radius: 20px; text-transform: uppercase; border: 1px solid transparent; }
        .status-pending { background: rgba(59, 130, 246, 0.1); color: #60a5fa; border-color: rgba(59, 130, 246, 0.3); }
        .input-produccion { width: 80px; text-align: center; font-weight: bold; font-size: 1.1rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark shadow-sm mb-4">
    <div class="container-fluid px-4">
        <span class="navbar-brand h1 mb-0"><i class="bi bi-cpu-fill me-2"></i> MÓDULO DE PRODUCCIÓN</span>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">Cambiar Plotter:</span>
            <select class="form-select form-select-sm" style="width: 150px;" onchange="location.href='index.php?action=campana_ver_produccion&plotter_id=' + this.value">
                <?php foreach ($plotters as $pid => $pname): ?>
                    <option value="<?= $pid ?>" <?= $pid == $plotterId ? 'selected' : '' ?>><?= $pname ?></option>
                <?php endforeach; ?>
            </select>
            <a href="index.php?action=campanas_list" class="btn btn-outline-light btn-sm"><i class="bi bi-house"></i></a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row g-4">
        <!-- Plotter Info -->
        <div class="col-md-12">
            <div class="card shadow-sm border-0 mb-4 bg-gradient" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-4 text-primary">
                                <i class="bi bi-printer-fill fs-1"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h2 class="mb-0 fw-bold"><?= htmlspecialchars($plotterName) ?></h2>
                            <p class="text-muted mb-0">Panel de Control de Producción en Vivo</p>
                        </div>
                        <div class="col-auto text-end">
                            <div class="px-3 py-2 rounded bg-dark border border-secondary">
                                <span class="d-block text-muted small text-uppercase">Pendientes</span>
                                <span class="h4 mb-0"><?= count($asignaciones) ?> Trabajos</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asignaciones List -->
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h5 class="mb-0"><i class="bi bi-list-task me-2"></i>Trabajos Pendientes de Impresión</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="text-muted small text-uppercase">
                            <tr>
                                <th style="width: 25%;">Campaña / Trabajo</th>
                                <th>Material</th>
                                <th class="text-center">Asignado</th>
                                <th class="text-center">Producido</th>
                                <th style="width: 20%;">Progreso del Trabajo</th>
                                <th class="text-center">Registrar Producción</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asignaciones as $asig): 
                                $pct = $asig['tirajes_asignados'] > 0 ? ($asig['tirajes_producidos'] / $asig['tirajes_asignados']) * 100 : 0;
                            ?>
                            <tr id="row-<?= $asig['id'] ?>">
                                <td>
                                    <div class="fw-bold fs-5"><?= htmlspecialchars($asig['trabajo_nombre']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($asig['campana_nombre']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary"><?= htmlspecialchars($asig['material_nombre'] ?? 'Genérico') ?></span>
                                </td>
                                <td class="text-center fw-bold fs-5 text-accent"><?= $asig['tirajes_asignados'] ?></td>
                                <td class="text-center fw-bold fs-5 text-success" id="prod-<?= $asig['id'] ?>"><?= $asig['tirajes_producidos'] ?></td>
                                <td>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="progress-<?= $asig['id'] ?>" role="progressbar" style="width: <?= $pct ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1 text-muted small">
                                        <span id="pct-<?= $asig['id'] ?>"><?= round($pct, 1) ?>%</span>
                                        <span>Total Completo: <?= $asig['trabajo_completados'] ?> / <?= $asig['trabajo_total'] ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="input-group input-group-lg justify-content-center">
                                        <button class="btn btn-outline-secondary" type="button" onclick="adjustCount(<?= $asig['id'] ?>, -1)">-</button>
                                        <input type="number" id="input-<?= $asig['id'] ?>" class="form-control input-produccion" value="1" min="1" max="<?= $asig['tirajes_asignados'] - $asig['tirajes_producidos'] ?>">
                                        <button class="btn btn-outline-secondary" type="button" onclick="adjustCount(<?= $asig['id'] ?>, 1)">+</button>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-success btn-lg px-4" onclick="registerProd(<?= $asig['id'] ?>)">
                                        <i class="bi bi-check2-circle me-1"></i> REGISTRAR
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; if(empty($asignaciones)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-emoji-smile fs-1 text-muted d-block mb-2"></i>
                                    <p class="text-muted mb-0">¡No hay trabajos pendientes para este plotter!</p>
                                    <small class="text-secondary">Asigne trabajos desde el detalle de la campaña.</small>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function adjustCount(id, delta) {
        const input = document.getElementById('input-' + id);
        let val = parseInt(input.value) + delta;
        if (val < 1) val = 1;
        input.value = val;
    }

    async function registerProd(asigId) {
        const input = document.getElementById('input-' + asigId);
        const cantidad = parseInt(input.value);
        if (cantidad <= 0) return;

        const btn = document.querySelector(`#row-${asigId} .btn-success`);
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const resp = await fetch('index.php?action=campana_registrar_produccion', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ asignacion_id: asigId, cantidad: cantidad })
            });
            const data = await resp.json();

            if (data.ok) {
                // Actualizar UI sin recargar
                const prodEl = document.getElementById('prod-' + asigId);
                const currentProd = parseInt(prodEl.innerText);
                const newProd = currentProd + cantidad;
                prodEl.innerText = newProd;

                const tr = document.getElementById('row-' + asigId);
                const totalAsig = parseInt(tr.querySelector('.text-accent').innerText);
                const newPct = (newProd / totalAsig) * 100;
                
                document.getElementById('progress-' + asigId).style.width = newPct + '%';
                document.getElementById('pct-' + asigId).innerText = newPct.toFixed(1) + '%';

                if (newProd >= totalAsig) {
                    tr.classList.add('table-success', 'opacity-50');
                    setTimeout(() => tr.remove(), 1000);
                }

                input.value = 1;
            } else {
                alert("Error: " + data.error);
            }
        } catch (e) {
            alert("Error de conexión");
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
</script>

</body>
</html>
