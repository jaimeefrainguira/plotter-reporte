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

        <form action="index.php?action=store_bulk" method="POST" id="reportForm">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#plotterTable tbody');
    const addRowBtn = document.getElementById('addRow');
    let rowCount = 0;

    const plotters = <?= json_encode($plotters) ?>;
    const campanas = <?= json_encode($campanas) ?>;

    function createRow() {
        const tr = document.createElement('tr');
        tr.dataset.index = rowCount;

        // Plotter Select
        let plotterOptions = plotters.map(p => `<option value="${p}">${p}</option>`).join('');
        
        // Campaña Select/Text
        let campanaOptions = campanas.map(c => `<option value="${c.nombre}">${c.nombre}</option>`).join('');

        tr.innerHTML = `
            <td>
                <select name="rows[${rowCount}][plotter]" class="form-select form-select-sm" required>
                    <option value="">Seleccionar...</option>
                    ${plotterOptions}
                </select>
            </td>
            <td>
                <input list="campanasList" name="rows[${rowCount}][campana]" class="form-control form-control-sm" required placeholder="Campaña...">
                <datalist id="campanasList">
                    ${campanaOptions}
                </datalist>
            </td>
            <td>
                <input type="text" name="rows[${rowCount}][descripcion]" class="form-control form-control-sm" required placeholder="Descripción...">
            </td>
            <td>
                <input type="number" name="rows[${rowCount}][cantidad]" class="form-control form-control-sm cantidad-input" min="1" value="0" required>
            </td>
            <td>
                <input type="number" name="rows[${rowCount}][cantidad_impreso]" class="form-control form-control-sm impreso-input" min="0" value="0" required>
            </td>
            <td class="percentage-cell">
                <span class="percentage-text">0%</span>
                <input type="hidden" name="rows[${rowCount}][porcentaje_impresion]" class="percentage-hidden" value="0">
            </td>
            <td class="text-center">
                <button type="button" class="btn-crud reset-row"><i class="bi bi-arrow-clockwise"></i></button>
                <button type="button" class="btn-crud delete-row"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tableBody.appendChild(tr);

        // Event listeners for calculations
        const cantInput = tr.querySelector('.cantidad-input');
        const impInput = tr.querySelector('.impreso-input');
        const percentText = tr.querySelector('.percentage-text');
        const percentHidden = tr.querySelector('.percentage-hidden');

        const updatePercentage = () => {
            const cant = parseFloat(cantInput.value) || 0;
            const imp = parseFloat(impInput.value) || 0;
            let percent = 0;
            if (cant > 0) {
                percent = Math.round((imp / cant) * 100);
            }
            if (percent > 100) percent = 100;
            percentText.textContent = percent + '%';
            percentHidden.value = percent;
        };

        cantInput.addEventListener('input', updatePercentage);
        impInput.addEventListener('input', updatePercentage);

        // Delete row
        tr.querySelector('.delete-row').addEventListener('click', function() {
            tr.remove();
        });

        // Reset row
        tr.querySelector('.reset-row').addEventListener('click', function() {
            tr.querySelectorAll('input, select').forEach(input => {
                if (input.type === 'number') input.value = 0;
                else if (input.tagName === 'SELECT') input.selectedIndex = 0;
                else input.value = '';
            });
            updatePercentage();
        });

        rowCount++;
    }

    addRowBtn.addEventListener('click', createRow);

    // Add first row by default
    createRow();
});
</script>

</body>
</html>
