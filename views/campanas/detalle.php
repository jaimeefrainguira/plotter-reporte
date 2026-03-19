<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Campaña | Industrial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/campanas.css">
    <style>
        .result-panel {
            background-color: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .summary-card {
            background: #1e252d;
            color: #fff;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark shadow-sm mb-4">
    <div class="container">
        <span class="navbar-brand h1 mb-0"><i class="bi bi- megaphone"></i> <?= htmlspecialchars($campana['nombre']) ?></span>
        <div class="d-flex gap-2">
            <a href="index.php?action=campanas_list" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Volver a Lista</a>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalTrabajo" id="btnNuevoTrabajo">
                <i class="bi bi-plus-circle"></i> AÑADIR TRABAJO
            </button>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h5 class="card-title text-muted small text-uppercase">Información de Campaña</h5>
            <div class="row align-items-center">
                <div class="col-md-3 border-end">
                    <p class="mb-0 text-muted">Req #</p>
                    <p class="h5"><?= htmlspecialchars($campana['requerimiento_nro']) ?></p>
                </div>
                <div class="col-md-3 border-end">
                    <p class="mb-0 text-muted">Fecha Inicio</p>
                    <p class="h5"><?= (new DateTime($campana['fecha_creacion']))->format('d/m/Y') ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-0 text-muted">Progreso Global de Producción</p>
                    <div class="progress mt-2" style="height: 15px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 45%;" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100">45%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Dinámica Editable -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <h4 class="mb-0">Lista de Trabajos / ítems</h4>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>DESCRIPCIÓN</th>
                        <th>CANTIDAD</th>
                        <th>TAMAÑO (cm)</th>
                        <th>MATERIAL</th>
                        <th>CONSUMO ESTIMADO</th>
                        <th class="text-center">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trabajos as $trabajo): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($trabajo['descripcion']) ?></strong></td>
                        <td><?= $trabajo['cantidad'] ?> uds</td>
                        <td><?= (float)$trabajo['ancho_panel'] ?> × <?= (float)$trabajo['alto_panel'] ?> cm</td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($trabajo['material_nombre'] ?? 'Sin material') ?></span></td>
                        <td>
                            <?php if (!empty($trabajo['total_metros']) && $trabajo['total_metros'] > 0): ?>
                                <strong><?= number_format((float)$trabajo['total_metros'], 2) ?> m</strong><br>
                            <?php endif; ?>
                            <small class="text-muted"><?= htmlspecialchars($trabajo['distribucion_texto'] ?: 'No calculado') ?></small>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-warning btn-edit-trabajo" 
                                    data-trabajo='<?= json_encode($trabajo) ?>'
                                    data-bs-toggle="modal" data-bs-target="#modalTrabajo">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="index.php?action=campana_delete_trabajo" class="d-inline" onsubmit="return confirm('¿Seguro?')">
                                    <input type="hidden" name="id" value="<?= $trabajo['id'] ?>">
                                    <input type="hidden" name="campana_id" value="<?= $campana['id'] ?>">
                                    <button class="btn btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; if(empty($trabajos)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No hay trabajos registrados. Pulsa el botón [AÑADIR] arriba.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Panel de Consumos Global -->
    <div class="mt-4">
        <h4><i class="bi bi-calc"></i> Resumen de Consumos Global</h4>
        <div id="consumosGlobales" class="row g-2">
            <!-- Se llena dinámicamente con JS si se desea o se puede pre-generar en PHP -->
        </div>
    </div>
</div>

<!-- Modal Detallado de Consumo y Trabajo -->
<div class="modal fade" id="modalTrabajo" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form id="formTrabajo" method="POST" action="index.php?action=campana_save_trabajo">
            <input type="hidden" name="campana_id" value="<?= $campana['id'] ?>">
            <input type="hidden" name="trabajo_id" id="field_trabajo_id" value="">
            <input type="hidden" name="total_metros" id="field_total_metros">
            <input type="hidden" name="total_planchas" id="field_total_planchas">
            <input type="hidden" name="distribucion_texto" id="field_distribucion_texto">
            <input type="hidden" name="unidades_por_rollo" id="field_unidades_por_rollo">

            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalTitle">Nuevo Item de Trabajo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Descripción (solo para BD, no en calcular.html) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <input type="text" name="descripcion" id="field_descripcion" class="form-control form-control-sm" placeholder="Ej: Carteles Navidad 60x120" required>
                    </div>

                    <!-- ===== DISEÑO (igual que calcular.html) ===== -->
                    <h6 class="fw-bold mt-3">Diseño</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-auto">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Ancho:</span>
                                <input type="number" name="ancho_panel" id="field_ancho_panel" class="form-control calc-trigger" value="300" style="width:80px;">
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Alto:</span>
                                <input type="number" name="alto_panel" id="field_alto_panel" class="form-control calc-trigger" value="120" style="width:80px;">
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Copias:</span>
                                <input type="number" name="cantidad" id="field_cantidad" class="form-control calc-trigger" value="1" min="1" required style="width:80px;">
                            </div>
                        </div>
                    </div>

                    <!-- ===== ACTIVAR PANELADO ===== -->
                    <h6 class="fw-bold mt-3">
                        <input type="checkbox" name="usar_panelado" id="usarPanelado" value="1" class="form-check-input me-1"> Activar Panelado
                    </h6>
                    <div id="panelConfig" style="display:none;" class="mb-3 ps-3">
                        <div class="row g-2">
                            <div class="col-auto">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Ancho panel:</span>
                                    <input type="number" name="panel_ancho" id="field_panel_ancho" class="form-control calc-trigger" value="120" style="width:80px;">
                                    <span class="input-group-text">cm</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Gap:</span>
                                    <input type="number" name="panel_gap" id="field_panel_gap" class="form-control calc-trigger" value="2" style="width:80px;">
                                    <span class="input-group-text">cm</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== MATERIAL ROLLOS ===== -->
                    <h6 class="fw-bold mt-3">Material Rollos</h6>
                    <select name="material_id" id="field_material_id" class="form-select form-select-sm mb-3 calc-trigger" required style="max-width:300px;">
                        <option value="">-- SELECCIONAR --</option>
                        <?php foreach ($materiales as $mat): ?>
                            <option value="<?= $mat['id'] ?>" 
                                data-tipo="<?= $mat['tipo'] ?>" 
                                data-ancho="<?= (float)$mat['medida_ancho'] ?>" 
                                data-largo="<?= (float)$mat['medida_largo'] ?>">
                                <?= htmlspecialchars($mat['nombre']) ?> <?= (float)$mat['medida_ancho'] ?>x<?= (float)$mat['medida_largo'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- ===== ORIENTACIÓN ===== -->
                    <h6 class="fw-bold mt-3">Orientación</h6>
                    <select name="orientacion" id="field_orientacion" class="form-select form-select-sm mb-3 calc-trigger" style="max-width:250px;">
                        <option value="auto">Automático</option>
                        <option value="vertical">Forzar Vertical</option>
                        <option value="horizontal">Forzar Horizontal</option>
                    </select>

                    <!-- ===== SINTRA ===== -->
                    <h6 class="fw-bold mt-3">
                        <input type="checkbox" name="usar_sintra" id="usarSintra" value="1" class="form-check-input me-1"> Usar Sintra 122x244
                    </h6>

                    <br>
                    <button type="button" id="btnCalcular" class="btn btn-dark w-100 py-2">CALCULAR</button>
                    <br><br>

                    <!-- ===== RESULTADO ===== -->
                    <div class="mt-3 p-3 bg-light border rounded" id="resultado">
                        <span class="text-muted">Completa los datos para ver el resultado...</span>
                    </div>

                    <!-- Sintra resultado -->
                    <div id="resultadoSintra" style="display:none;" class="mt-2 p-3 bg-light border rounded"></div>

                    <!-- ===== PREVIEW ===== -->
                    <div id="preview" style="margin-top:20px; border:2px solid #333; position:relative; background:#eee; display:none;"></div>

                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-primary px-4">GUARDAR CAMBIOS</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/campanas_calculos.js"></script>
</body>
</html>
