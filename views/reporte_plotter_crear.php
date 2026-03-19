<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Plotters | Industrial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1e252d;
            --accent-color: #0d6efd;
            --border-light: #dee2e6;
        }
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        h1 {
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 40px;
            font-weight: 300;
            color: var(--primary-dark);
        }
        .table thead {
            background-color: #f8f9fa;
        }
        .table th {
            text-transform: uppercase;
            font-size: 0.85rem;
            font-weight: 600;
            color: #6c757d;
            border-bottom: 2px solid var(--border-light);
            vertical-align: middle;
            text-align: center;
        }
        .table td {
            vertical-align: middle;
            padding: 12px;
        }
        .btn-add {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            color: #495057;
            padding: 8px 20px;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.2s;
        }
        .btn-add:hover {
            background-color: #dee2e6;
        }
        .btn-save {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            color: #495057;
            padding: 10px 40px;
            font-weight: 600;
            margin-top: 30px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            transition: all 0.2s;
        }
        .btn-save:hover {
            background-color: #dee2e6;
        }
        .form-control, .form-select {
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .btn-crud {
            background: none;
            border: none;
            color: #1e252d;
            font-size: 1.2rem;
            padding: 0 5px;
        }
        .btn-crud:hover {
            color: var(--accent-color);
        }
        .percentage-cell {
            text-align: center;
            font-weight: bold;
            color: var(--accent-color);
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="main-container">
        <h1>REPORTE DE PLOTTERS</h1>

        <div class="d-flex justify-content-start">
            <button type="button" class="btn btn-add rounded-0" id="addRow">AGREGAR FILA</button>
        </div>

        <form action="index.php?action=store_bulk" method="POST" id="reportForm" 
              data-plotters='<?= json_encode($plotters) ?>' 
              data-campanas='<?= json_encode($campanas, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>'
              data-materiales='<?= json_encode($materiales) ?>'>
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div class="table-responsive">
                <table class="table table-bordered" id="plotterTable">
                    <thead>
                        <tr>
                            <th style="width: 12%;">PLOTTER</th>
                            <th style="width: 15%;">CAMPAÑA</th>
                            <th style="width: 15%;">DESCRIPCIÓN</th>
                            <th style="width: 10%;">MATERIAL</th>
                            <th style="width: 18%;">MEDIDAS (cm)</th>
                            <th style="width: 5%;">CANT.</th>
                            <th style="width: 8%;">IMPRESO</th>
                            <th style="width: 17%;">RESULTADO</th>
                            <th style="width: 5%;">CRUD</th>
                        </tr>
                        <tr class="table-light small">
                            <th colspan="4"></th>
                            <th class="p-1">
                                <div class="row g-1">
                                    <div class="col-4">Ancho</div>
                                    <div class="col-4">Alto</div>
                                    <div class="col-4">Gap</div>
                                </div>
                            </th>
                            <th colspan="2"></th>
                            <th>Consumo Material</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be added here -->
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-save rounded-0">GUARDAR REPORTE</button>
        </form>
    </div>
    
    </div>
    
    <!-- Modal Calculadora -->
    <div class="modal fade" id="calculatorModal" tabindex="-1" aria-labelledby="calculatorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="calculatorModalLabel">Calculadora de Producción Plotter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Dimensiones del Material (cm)</h6>
                            <div class="mb-2">
                                <label class="form-label small">Ancho del Panel (Media Width)</label>
                                <input type="number" id="calcAnchoPanel" class="form-control" value="150">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Largo del Rollo (opcional)</label>
                                <input type="number" id="calcLargoMaterial" class="form-control" value="5000">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Dimensiones de la Pieza (cm)</h6>
                            <div class="mb-2">
                                <label class="form-label small">Ancho de Pieza</label>
                                <input type="number" id="calcAnchoPieza" class="form-control" value="10">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Alto de Pieza</label>
                                <input type="number" id="calcAltoPieza" class="form-control" value="10">
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-info w-100 mt-2" id="rotatePiece">
                                <i class="bi bi-arrow-repeat"></i> Rotar Orientación
                            </button>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h6>Producción</h6>
                            <div class="mb-2">
                                <label class="form-label small">Largo Total Impreso (cm)</label>
                                <input type="number" id="calcLargoTotal" class="form-control" value="100">
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Resultados</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1 small">Rollos Completos: <span id="resRollos" class="fw-bold">0</span></p>
                                    <p class="mb-1 small">Sobrante en cm: <span id="resSobrante" class="fw-bold">0</span> cm</p>
                                    <p class="mb-1 small">Piezas por Fila: <span id="resPiezasFila" class="fw-bold">0</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 small">Copias por Rollo: <span id="resCopiasRollo" class="fw-bold">0</span></p>
                                    <p class="mb-1 small">Copias Extra: <span id="resCopiasExtra" class="fw-bold">0</span></p>
                                    <p class="mb-1 small">Material Sobrante: <span id="resSobranteFinal" class="fw-bold">0</span> cm</p>
                                </div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <h3>Total Copias: <span id="resTotalCopias" class="text-primary">0</span></h3>
                                <p class="text-muted small">Paneles por Copia: <span id="resPanelesCopia">0</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="applyCalculation">Aplicar a Cantidad Impreso</button>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="index.php?action=campanas_list" class="text-muted text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a Gestión de Campañas
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/reporte_plotter.js?v=<?= time() ?>"></script>
</body>
</html>
