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
            max-width: 1260px;
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
            margin-bottom: 26px;
            font-weight: 300;
            color: var(--primary-dark);
        }
        .table thead {
            background-color: #f8f9fa;
        }
        .table th {
            text-transform: uppercase;
            font-size: 0.78rem;
            font-weight: 600;
            color: #6c757d;
            border-bottom: 2px solid var(--border-light);
            vertical-align: middle;
            text-align: center;
            white-space: nowrap;
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
            background-color: #198754;
            border: 1px solid #198754;
            color: #fff;
            padding: 10px 40px;
            font-weight: 600;
            margin-top: 30px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            transition: all 0.2s;
        }
        .btn-save:hover {
            background-color: #157347;
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
        <h1>REPORTE DE JORNADA - PLOTTERS</h1>

        <?php if (!empty($loadError)): ?>
            <div class="alert alert-warning" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <?= htmlspecialchars((string) $loadError) ?>
            </div>
        <?php endif; ?>

        <form action="index.php?action=store_bulk" method="POST" id="reportForm"
              data-plotters='<?= json_encode($plotters) ?>'
              data-campanas='<?= json_encode($campanas, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>'
              data-asignaciones='<?= json_encode($asignacionesJornada ?? [], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>'>
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="submit_mode" value="pdf">
            <input type="hidden" name="jornada_operator" id="jornadaOperator">
            <input type="hidden" name="jornada_start" id="jornadaStart">
            <input type="hidden" name="jornada_end" id="jornadaEnd">

            <div class="card border-0 bg-light mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Operador</label>
                            <input type="text" class="form-control" id="operatorName" placeholder="Nombre del operador..." required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Inicio jornada (auto)</label>
                            <input type="text" class="form-control" id="shiftStartDisplay" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Fin jornada (auto)</label>
                            <input type="text" class="form-control" id="shiftEndDisplay" readonly>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="button" id="startShiftBtn" class="btn btn-primary">
                                <i class="bi bi-play-circle"></i> Iniciar jornada
                            </button>
                        </div>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <div class="small" id="shiftStatus"></div>
                        <button type="button" id="endShiftBtn" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-stop-circle"></i> Cerrar jornada
                        </button>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-start">
                <button type="button" class="btn btn-add rounded-0" id="addRow">
                    <i class="bi bi-plus-lg"></i> Agregar trabajo
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="plotterTable">
                    <thead>
                        <tr>
                            <th style="width: 13%;">Plotter</th>
                            <th style="width: 17%;">Campaña / Trabajo</th>
                            <th style="width: 20%;">Trabajo (descripción)</th>
                            <th style="width: 14%;">Material</th>
                            <th style="width: 9%;">Tirajes</th>
                            <th style="width: 9%;">Producido</th>
                            <th style="width: 10%;">Progreso del trabajo</th>
                            <th style="width: 8%;">CRUD</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be added here -->
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-save rounded-0" id="generatePdfBtn">
                <i class="bi bi-file-earmark-pdf"></i> Generar reporte
            </button>
        </form>
    </div>

    <div class="text-center mt-3">
        <a href="index.php?action=campanas_list" class="text-muted text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a Gestión de Campañas
        </a>
    </div>
</div>

<script src="js/shift_session.js?v=<?= time() ?>"></script>
<script src="js/reporte_plotter.js?v=<?= time() ?>"></script>
</body>
</html>
