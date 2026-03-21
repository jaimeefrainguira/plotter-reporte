<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Campaña | Industrial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/campanas.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark shadow-sm mb-4">
    <div class="container">
        <span class="navbar-brand h1 mb-0"><i class="bi bi- megaphone"></i> <?= htmlspecialchars($campana['nombre']) ?></span>
        <div class="d-flex gap-2">
            <a href="index.php?action=campanas_list" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Volver a Lista</a>
            <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalMultiIA">
                <i class="bi bi-robot"></i> AÑADIR MÚLTIPLES (IA)
            </button>
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
        <h4><i class="bi bi-calculator"></i> Resumen Global de Consumos</h4>

        <?php
        /* ── Agrupar trabajos por material ─────────────────────────────── */
        $resumenMat  = [];   // consumo m² por material
        $totalSintra = 0;    // planchas sintra globales

        foreach ($trabajos as $t) {
            $metros   = (float)($t['total_metros']   ?? 0);
            $planchas = (int)  ($t['total_planchas'] ?? 0);
            $totalSintra += $planchas;

            if ($metros <= 0 && $planchas <= 0) continue; // trabajo sin consumo calculado

            $mid = $t['material_id'] ?: 0;
            $nombre = $t['material_nombre'] ?? 'Sin material';

            if (!isset($resumenMat[$mid])) {
                $largoRollo = (float)($t['largo_rollo_m'] ?? 50);
                if ($largoRollo <= 0) $largoRollo = 50; // fallback
                $resumenMat[$mid] = [
                    'nombre'       => $nombre,
                    'largo_rollo'  => $largoRollo,        // metros
                    'total_metros' => 0.0,
                    'trabajos'     => 0,
                ];
            }
            $resumenMat[$mid]['total_metros'] += $metros;
            $resumenMat[$mid]['trabajos']++;
        }

        $hayConsumo = !empty($resumenMat) || $totalSintra > 0;
        ?>

        <?php if (!$hayConsumo): ?>
            <div class="alert alert-secondary">
                <i class="bi bi-info-circle"></i>
                No hay consumos calculados aún. Añade trabajos y presiona <strong>CALCULAR</strong> en cada ítem.
            </div>
        <?php else: ?>

        <div class="row g-3">

            <!-- ── Tarjetas por material ───────────────────────────────── -->
            <?php foreach ($resumenMat as $mid => $rm):
                $largoRollo  = $rm['largo_rollo'];      // en metros
                $totalM      = $rm['total_metros'];     // en metros
                $rollosEnt   = floor($totalM / $largoRollo);
                $sobranteM   = fmod($totalM, $largoRollo);
                $sobranteCm  = round($sobranteM * 100, 1);
                $pct         = $largoRollo > 0 ? min(100, ($totalM / $largoRollo) * 100) : 0;

                // Color según consumo
                if ($pct >= 90)      $color = 'danger';
                elseif ($pct >= 50)  $color = 'warning';
                else                  $color = 'success';
            ?>
            <div class="col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-dark text-white py-2">
                        <i class="bi bi-layers me-1"></i>
                        <strong><?= htmlspecialchars($rm['nombre']) ?></strong>
                        <span class="badge bg-secondary ms-1 small"><?= (int)$rm['trabajos'] ?> ítem(s)</span>
                    </div>
                    <div class="card-body pb-2">

                        <!-- Total en metros -->
                        <div class="d-flex justify-content-between align-items-baseline">
                            <span class="text-muted small">Total consumido:</span>
                            <span class="fs-5 fw-bold text-<?= $color ?>">
                                <?= number_format($totalM, 2) ?> m
                            </span>
                        </div>

                        <!-- Barra de progreso por roller -->
                        <div class="progress my-2" style="height:10px;" title="<?= round($pct, 1) ?>% del rollo">
                            <div class="progress-bar bg-<?= $color ?> progress-bar-striped"
                                 role="progressbar" style="width:<?= min(100, $pct) ?>%"></div>
                        </div>

                        <!-- Desglose rollos -->
                        <?php if ($rollosEnt >= 1): ?>
                        <div class="summary-card mb-2">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-circle-fill text-warning me-1" style="font-size:.6rem"></i>Rollos completos</span>
                                <strong><?= (int)$rollosEnt ?> × <?= (int)$largoRollo ?>m</strong>
                            </div>
                            <?php if ($sobranteM > 0.01): ?>
                            <div class="d-flex justify-content-between mt-1">
                                <span><i class="bi bi-circle me-1" style="font-size:.6rem"></i>Sobrante</span>
                                <strong>
                                    <?php if ($sobranteM >= 1): ?>
                                        <?= number_format($sobranteM, 2) ?> m
                                    <?php else: ?>
                                        <?= number_format($sobranteCm, 0) ?> cm
                                    <?php endif; ?>
                                </strong>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="summary-card mb-2">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-circle me-1" style="font-size:.6rem"></i>Uso parcial de rollo</span>
                                <strong>
                                    <?php if ($totalM >= 1): ?>
                                        <?= number_format($totalM, 2) ?> m
                                    <?php else: ?>
                                        <?= number_format($totalM * 100, 1) ?> cm
                                    <?php endif; ?>
                                </strong>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Resumen texto -->
                        <p class="mb-0 small text-muted">
                            <?php if ($rollosEnt >= 1 && $sobranteM > 0.01): ?>
                                <?= (int)$rollosEnt ?> rollo(s) de <?= (int)$largoRollo ?>m
                                + <?= number_format($sobranteM >= 1 ? $sobranteM : $sobranteCm, $sobranteM >= 1 ? 2 : 0) ?>
                                <?= $sobranteM >= 1 ? 'm' : 'cm' ?> adicionales
                            <?php elseif ($rollosEnt >= 1): ?>
                                <?= (int)$rollosEnt ?> rollo(s) de <?= (int)$largoRollo ?>m exactos
                            <?php else: ?>
                                Menos de 1 rollo (<?= (int)$largoRollo ?>m/rollo)
                            <?php endif; ?>
                        </p>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- ── Tarjeta Sintra (si aplica) ───────────────────────────── -->
            <?php if ($totalSintra > 0): ?>
            <div class="col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 border-top border-3 border-info">
                    <div class="card-header text-white py-2" style="background:#0d5564;">
                        <i class="bi bi-grid-3x2-gap me-1"></i>
                        <strong>Sintra 122×244 cm</strong>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <span class="text-muted small">Total planchas:</span>
                            <span class="fs-3 fw-bold text-info"><?= $totalSintra ?></span>
                        </div>
                        <p class="mb-0 small text-muted">
                            <?= $totalSintra ?> plancha(s) de 122×244 cm
                        </p>

                        <!-- Desglose por ítem -->
                        <hr class="my-2">
                        <p class="small fw-semibold mb-1">Detalle por ítem:</p>
                        <ul class="list-unstyled small mb-0">
                        <?php foreach ($trabajos as $t):
                            $p = (int)($t['total_planchas'] ?? 0);
                            if ($p <= 0) continue;
                        ?>
                            <li class="d-flex justify-content-between">
                                <span class="text-truncate me-2"><?= htmlspecialchars($t['descripcion']) ?></span>
                                <strong><?= $p ?> plancha(s)</strong>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Totalizador global ────────────────────────────────────── -->
            <?php
            $totalMetrosGlobal = array_sum(array_column($resumenMat, 'total_metros'));
            ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#0a1628,#1a3a5c); color:#fff;">
                    <div class="card-body py-3">
                        <div class="row align-items-center g-3">
                            <div class="col-auto">
                                <i class="bi bi-bar-chart-fill" style="font-size:2rem; opacity:.8;"></i>
                            </div>
                            <div class="col">
                                <div class="small opacity-75 text-uppercase letter-spacing-1">Consumo total acumulado (todos los materiales)</div>
                                <div class="fs-4 fw-bold">
                                    <?= number_format($totalMetrosGlobal, 2) ?> m lineales
                                    <?php if ($totalSintra > 0): ?>
                                        <span class="fs-6 ms-3 text-info">+ <?= $totalSintra ?> planchas Sintra</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-auto text-end">
                                <div class="small opacity-75">Ítems con consumo</div>
                                <div class="fs-5 fw-bold"><?= array_sum(array_column($resumenMat, 'trabajos')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /row -->
        <?php endif; ?>
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

                    <h6 class="fw-bold mt-3"><i class="bi bi-boxes me-1"></i>Material Rollos</h6>
                    <select name="material_id" id="field_material_id" class="form-select form-select-sm mb-3 calc-trigger" required style="max-width:380px;">
                        <option value="">-- SELECCIONAR MATERIAL --</option>
                        <?php foreach ($materiales as $mat): ?>
                            <option value="<?= (int)$mat['id'] ?>"
                                data-ancho="<?= (float)$mat['ancho_cm'] ?>"
                                data-largo="<?= (float)$mat['largo_rollo_m'] * 100 ?>">
                                <?= htmlspecialchars($mat['nombre']) ?> &mdash; <?= (float)$mat['ancho_cm'] ?>cm × <?= (float)$mat['largo_rollo_m'] ?>m
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

<!-- ═══════════════ MODAL: AÑADIR MÚLTIPLES (IA) ═══════════════ -->
<div class="modal fade" id="modalMultiIA" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-robot me-2 text-primary"></i>Carga Masiva con OCR e IA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <style>
                    .loader { display: none; color: #2563eb; font-weight: bold; margin: 20px 0; text-align: center; }
                    .loader::after { content: "..."; animation: dots 1.5s steps(5, end) infinite; }
                    .upload-box { border: 2px dashed #cbd5e1; padding: 40px; border-radius: 12px; background: #f1f5f9; cursor: pointer; transition: all 0.3s; margin-bottom: 20px; text-align: center; }
                    .upload-box:hover { border-color: #2563eb; background: #eff6ff; }
                    #preview { max-width: 100%; max-height: 300px; border-radius: 8px; display: none; margin: 20px auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
                    .error-msg { color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 6px; display: none; margin-top: 20px; text-align: center; }
                    @keyframes dots { 0%, 20% { color: rgba(0,0,0,0); text-shadow: .25em 0 0 rgba(0,0,0,0), .5em 0 0 rgba(0,0,0,0); } 40% { color: #2563eb; text-shadow: .25em 0 0 rgba(0,0,0,0), .5em 0 0 rgba(0,0,0,0); } 60% { text-shadow: .25em 0 0 #2563eb, .5em 0 0 rgba(0,0,0,0); } 80%, 100% { text-shadow: .25em 0 0 #2563eb, .5em 0 0 #2563eb; } }
                </style>

                <div class="text-center mb-3">
                    <img id="preview" style="max-width: 100%; max-height: 250px; border-radius: 8px; display: none; margin: 0 auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                </div>

                <div id="multiIA-step-upload">
                    <div class="upload-box" onclick="document.getElementById('fileInput').click()">
                        <p>Sube una imagen o <b>pega (Ctrl + V)</b> para extraer Descripción y Cantidad.</p>
                        <input type="file" id="fileInput" accept="image/*" style="display:none">
                    </div>
                    
                    <div id="loader" class="loader">IA Analizando y Estructurando datos</div>
                    <div id="errorBox" class="error-msg"></div>
                </div>

                <!-- Paso 2: Revisión -->
                <div id="multiIA-step-review" style="display:none;">
                    <table class="table table-sm table-bordered" id="tablaResultados" style="width: 100%; margin-top: 25px;">
                        <thead class="table-dark">
                            <tr>
                                <th>Descripción del Producto / Servicio</th>
                                <th style="width: 80px; text-align: center;">Cant.</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTabla">
                            <!-- Los datos de la IA se insertarán aquí -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-outline-primary d-none" id="btnRecargarIA"><i class="bi bi-arrow-repeat"></i> Volver a Cargar</button>
                <button type="button" class="btn btn-success d-none" id="btnConfirmarIA"><i class="bi bi-check-circle"></i> Confirmar y Cargar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const fileInput = document.getElementById("fileInput");
        const preview = document.getElementById("preview");
        const loader = document.getElementById("loader");
        const tabla = document.getElementById("multiIA-step-review");
        const cuerpoTabla = document.getElementById("cuerpoTabla");
        const errorBox = document.getElementById("errorBox");
        
        const btnConfirmarIA = document.getElementById('btnConfirmarIA');
        const btnRecargarIA = document.getElementById('btnRecargarIA');
        
        let currentFile = null;

        // 1. Capturar archivo por input
        if (fileInput) {
            fileInput.addEventListener("change", (e) => {
                if (e.target.files[0]) procesarImagen(e.target.files[0]);
            });
        }

        // 2. Capturar imagen pegada (Ctrl + V)
        document.addEventListener("paste", (e) => {
            const item = Array.from(e.clipboardData.items).find(x => x.type.indexOf("image") !== -1);
            if (item) {
                // Abrir modal si no está abierto
                const modalEl = document.getElementById('modalMultiIA');
                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.show();
                procesarImagen(item.getAsFile());
            }
        });

        function procesarImagen(file) {
            if (!file) return;
            currentFile = file;
            
            // Resetear UI
            errorBox.style.display = "none";
            tabla.style.display = "none";
            btnConfirmarIA.classList.add('d-none');
            btnRecargarIA.classList.add('d-none');
            document.getElementById('multiIA-step-upload').style.display = 'block';
            cuerpoTabla.innerHTML = "";
            
            // Mostrar Vista Previa
            const reader = new FileReader();
            reader.onload = (e) => { 
                preview.src = e.target.result; 
                preview.style.display = "block"; 
            };
            reader.readAsDataURL(file);

            // COMPRIMIR IMAGEN 
            const img = new Image();
            img.src = URL.createObjectURL(file);
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;
                const MAX = 1000;
                
                if (width > height && width > MAX) { height *= MAX / width; width = MAX; }
                else if (height > MAX) { width *= MAX / height; height = MAX; }
                
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    enviarAPHP(blob);
                }, 'image/jpeg', 0.8);
            };
        }

        async function enviarAPHP(blob) {
            loader.style.display = "block";
            
            const formData = new FormData();
            formData.append("imagen", blob, "captura.jpg");

            try {
                const res = await fetch("procesar.php", { method: "POST", body: formData });
                const rawText = await res.text();
                
                let data = [];
                try {
                    const parsed = JSON.parse(rawText);
                    // Adaptado exactamente a la estructura original (usa 'data' o 'items')
                    if (parsed.status === "success" && parsed.data) {
                        data = parsed.data;
                    } else {
                        data = Array.isArray(parsed) ? parsed : (parsed.items || parsed.data || []);
                    }
                } catch(e) {
                    throw new Error("Respuesta no válida del servidor: " + rawText.substring(0, 100));
                }

                if(data.length === 0) {
                    throw new Error("No se encontraron ítems.");
                }

                data.forEach(it => {
                    const desc = it.descripcion || it.desc || it.nombre || "";
                    const cant = it.cantidad || it.cant || it.qty || 1;

                    const tr = document.createElement('tr');
                    // Usamos las clases ia-desc y ia-cant para que la función de Guardar funcione
                    tr.innerHTML = `
                        <td><input type="text" class="form-control form-control-sm ia-desc" value="${desc}"></td>
                        <td><input type="number" class="form-control form-control-sm ia-cant" value="${cant}"></td>
                        <td><button class="btn btn-sm btn-danger py-0 px-1" onclick="this.closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                    `;
                    cuerpoTabla.appendChild(tr);
                });

                loader.style.display = "none";
                document.getElementById('multiIA-step-upload').style.display = 'none';
                tabla.style.display = "block";
                
                btnConfirmarIA.classList.remove('d-none');
                btnRecargarIA.classList.remove('d-none');
                
            } catch (err) {
                loader.style.display = "none";
                errorBox.innerText = err.message || "Error de conexión.";
                errorBox.style.display = "block";
            }
        }

        // --- LÓGICA DE LOS BOTONES ---
        if (btnRecargarIA) {
            btnRecargarIA.onclick = () => {
                document.getElementById('multiIA-step-upload').style.display = 'block';
                tabla.style.display = 'none';
                btnConfirmarIA.classList.add('d-none');
                btnRecargarIA.classList.add('d-none');
                preview.style.display = 'none';
                errorBox.style.display = 'none';
                currentFile = null;
            };
        }

        if (btnConfirmarIA) {
            btnConfirmarIA.onclick = async () => {
                const descs = [...document.querySelectorAll('.ia-desc')].map(i => i.value);
                const cants = [...document.querySelectorAll('.ia-cant')].map(i => i.value);
                
                if (descs.length === 0) { alert("No hay ítems para cargar."); return; }

                const campanaId = document.querySelector('input[name="campana_id"]')?.value || 0;
                const items = descs.map((d, i) => ({ descripcion: d, cantidad: cants[i] }));
                
                try {
                    btnConfirmarIA.disabled = true;
                    btnConfirmarIA.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

                    // Subir la imagen original al servidor localmente
                    if (currentFile) {
                        const fd = new FormData();
                        fd.append('imagen_adjunta', currentFile, currentFile.name || 'pasted_image.jpg');
                        fd.append('campana_id', campanaId);
                        fetch('index.php?action=campana_upload_imagen', { method: 'POST', body: fd })
                            .catch(err => console.error("Error capa de red al subir la imagen:", err));
                    }

                    const resp = await fetch('index.php?action=campana_bulk_save', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ campana_id: campanaId, items: items })
                    });

                    if (resp.ok) {
                        location.reload();
                    } else {
                        alert("Error al guardar los items en el servidor.");
                    }
                } catch (e) {
                    alert("Error: " + e.message);
                } finally {
                    btnConfirmarIA.disabled = false;
                    btnConfirmarIA.innerHTML = '<i class="bi bi-check-circle"></i> Confirmar y Cargar';
                }
            };
        }
    });
</script>
<script src="js/campanas_calculos.js?v=<?= time() ?>"></script>
</body>
</html>
