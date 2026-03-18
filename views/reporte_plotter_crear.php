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
              data-campanas='<?= json_encode($campanas, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>'>
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div class="table-responsive">
                <table class="table table-bordered" id="plotterTable">
                    <thead>
                        <tr>
                            <th style="width: 15%;">PLOTTER</th>
                            <th style="width: 20%;">CAMPAÑA</th>
                            <th style="width: 25%;">DESCRIPCIÓN</th>
                            <th style="width: 10%;">CANTIDAD</th>
                            <th style="width: 10%;">CANT. IMPRESO</th>
                            <th style="width: 10%;">% IMPRESO</th>
                            <th style="width: 10%;">CRUD</th>
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
    
    <div class="text-center mt-3">
        <a href="index.php?action=campanas_list" class="text-muted text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a Gestión de Campañas
        </a>
    </div>
</div>

<script src="js/reporte_plotter.js"></script>
</body>
</html>
