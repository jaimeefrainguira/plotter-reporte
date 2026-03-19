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
                        <th>TAMAÑO (mm)</th>
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
                        <td><?= (float)$trabajo['ancho_panel'] ?> x <?= (float)$trabajo['alto_panel'] ?></td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($trabajo['material_nombre']) ?></span></td>
                        <td>
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
                    <!-- Fila 1: Descripción + Cantidad -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Descripción</label>
                            <input type="text" name="descripcion" id="field_descripcion" class="form-control" placeholder="Ej: Carteles Navidad 60x120" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Copias / Cantidad</label>
                            <input type="number" name="cantidad" id="field_cantidad" class="form-control calc-trigger" value="0" min="1" required>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 mb-3"><i class="bi bi-rulers"></i> Configuración de Material y Medidas</div>

                    <!-- Fila 2: Configuración principal -->
                    <div class="row g-3 mb-3">
                        <!-- COL IZQUIERDA: Medidas pieza + Material -->
                        <div class="col-md-6 border-end">
                            <label class="form-label fw-bold">Medidas de la Pieza (cm)</label>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Ancho</span>
                                        <input type="number" step="0.1" name="ancho_panel" id="field_ancho_panel" class="form-control calc-trigger" placeholder="0">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Alto</span>
                                        <input type="number" step="0.1" name="alto_panel" id="field_alto_panel" class="form-control calc-trigger" placeholder="0">
                                    </div>
                                </div>
                            </div>

                            <label class="form-label mt-2">Material</label>
                            <select name="material_id" id="field_material_id" class="form-select form-select-sm calc-trigger" required>
                                <option value="">-- SELECCIONAR --</option>
                                <?php foreach ($materiales as $mat): ?>
                                    <option value="<?= $mat['id'] ?>" 
                                        data-tipo="<?= $mat['tipo'] ?>" 
                                        data-ancho="<?= (float)$mat['medida_ancho'] ?>" 
                                        data-largo="<?= (float)$mat['medida_largo'] ?>">
                                        <?= htmlspecialchars($mat['nombre']) ?> (<?= $mat['tipo'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label class="form-label mt-2">Orientación</label>
                            <select id="field_orientacion" class="form-select form-select-sm calc-trigger">
                                <option value="auto">Automático (mejor aprovechamiento)</option>
                                <option value="horizontal">Horizontal (Normal)</option>
                                <option value="vertical">Vertical (Rotado 90°)</option>
                            </select>
                        </div>

                        <!-- COL DERECHA: Separación + Panelado + Sintra -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Separación entre piezas (cm)</label>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">H</span>
                                        <input type="number" step="0.1" name="separacion_h" id="field_separacion_h" class="form-control calc-trigger" value="0">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">V</span>
                                        <input type="number" step="0.1" name="separacion_v" id="field_separacion_v" class="form-control calc-trigger" value="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Panelado -->
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" id="usarPanelado">
                                <label class="form-check-label fw-bold" for="usarPanelado">Usar Panelado</label>
                            </div>
                            <div id="panelConfig" style="display:none;" class="mt-2 p-2 border rounded bg-white">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Ancho Panel</span>
                                            <input type="number" step="0.1" id="field_panel_ancho" class="form-control calc-trigger" value="150">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Gap</span>
                                            <input type="number" step="0.1" id="field_panel_gap" class="form-control calc-trigger" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sintra -->
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" id="usarSintra">
                                <label class="form-check-label fw-bold" for="usarSintra">Calcular Sintra</label>
                            </div>
                            <div id="sintraConfig" style="display:none;" class="mt-2 p-2 border rounded bg-white">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Ancho</span>
                                            <input type="number" step="0.1" id="field_sintra_ancho" class="form-control calc-trigger" value="122">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Largo</span>
                                            <input type="number" step="0.1" id="field_sintra_largo" class="form-control calc-trigger" value="244">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RESULTADOS -->
                    <div class="result-panel mt-3">
                        <div class="row g-3">
                            <!-- Texto resultado -->
                            <div class="col-md-7">
                                <h6 class="mb-2"><i class="bi bi-clipboard-data"></i> Resultado del Cálculo</h6>
                                <div id="resultado" class="p-3 bg-white border rounded small" style="min-height:120px;">
                                    <span class="text-muted">Ingresa los datos y se calculará automáticamente...</span>
                                </div>

                                <!-- Tarjetas de fórmulas -->
                                <div class="row g-2 mt-2" id="formulaCards">
                                    <div class="col-4 col-md-2">
                                        <div class="summary-card text-center py-1">
                                            <div style="font-size:.6rem;opacity:.7;">Piezas/Fila</div>
                                            <div class="h6 mb-0" id="res_piezas_fila">--</div>
                                        </div>
                                    </div>
                                    <div class="col-4 col-md-2">
                                        <div class="summary-card text-center py-1">
                                            <div style="font-size:.6rem;opacity:.7;">Copias/Rollo</div>
                                            <div class="h6 mb-0" id="res_copias_rollo">--</div>
                                        </div>
                                    </div>
                                    <div class="col-4 col-md-2">
                                        <div class="summary-card text-center py-1">
                                            <div style="font-size:.6rem;opacity:.7;">Rollos</div>
                                            <div class="h6 mb-0" id="res_rollos">--</div>
                                        </div>
                                    </div>
                                    <div class="col-4 col-md-2">
                                        <div class="summary-card text-center py-1">
                                            <div style="font-size:.6rem;opacity:.7;">Sobrante</div>
                                            <div class="h6 mb-0" id="res_sobrante">--</div>
                                        </div>
                                    </div>
                                    <div class="col-4 col-md-2">
                                        <div class="summary-card text-center py-1">
                                            <div style="font-size:.6rem;opacity:.7;">C. Extra</div>
                                            <div class="h6 mb-0" id="res_copias_extra">--</div>
                                        </div>
                                    </div>
                                    <div class="col-4 col-md-2">
                                        <div class="summary-card text-center py-1">
                                            <div style="font-size:.6rem;opacity:.7;">Paneles/Cop</div>
                                            <div class="h6 mb-0" id="res_paneles_copia">--</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sintra resultado -->
                                <div id="resultadoSintra" style="display:none;" class="mt-2 p-2 border rounded bg-white small">
                                    <strong><i class="bi bi-grid-3x3"></i> Sintra:</strong>
                                    <span id="sintraTexto">--</span>
                                </div>
                            </div>

                            <!-- Preview Visual -->
                            <div class="col-md-5">
                                <h6 class="mb-2"><i class="bi bi-grid"></i> Vista Previa</h6>
                                <div class="border rounded bg-white p-2 text-center" style="min-height:150px;overflow:auto;">
                                    <div id="preview" style="position:relative;margin:0 auto;border:2px dashed #adb5bd;display:none;"></div>
                                    <small class="text-muted d-block mt-1" id="previewLabel">--</small>
                                </div>
                            </div>
                        </div>
                    </div>

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
