document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#plotterTable tbody');
    const addRowBtn = document.getElementById('addRow');
    const reportForm = document.getElementById('reportForm');
    
    if (!tableBody || !addRowBtn || !reportForm) return;

    let rowCount = 0;

    const plotters = JSON.parse(reportForm.dataset.plotters || '[]');
    const campanas = JSON.parse(reportForm.dataset.campanas || '[]');
    const materiales = JSON.parse(reportForm.dataset.materiales || '[]');

    function createRow() {
        const tr = document.createElement('tr');
        tr.dataset.index = rowCount;

        // Plotter Select
        let plotterOptions = plotters.map(p => `<option value="${p}">${p}</option>`).join('');
        // Material Select
        let materialOptions = materiales.map(m => `<option value="${m.id}" data-tipo="${m.tipo}" data-ancho="${m.medida_ancho}" data-largo="${m.medida_largo}">${m.nombre}</option>`).join('');

        tr.innerHTML = `
            <td>
                <select name="rows[${rowCount}][plotter]" class="form-select form-select-sm" required>
                    <option value="">Seleccionar...</option>
                    ${plotterOptions}
                </select>
            </td>
            <td>
                <input list="campanasList" name="rows[${rowCount}][campana]" class="form-control form-control-sm" required placeholder="Campaña...">
            </td>
            <td>
                <input type="text" name="rows[${rowCount}][descripcion]" class="form-control form-control-sm" required placeholder="Descripción...">
            </td>
            <td>
                <select name="rows[${rowCount}][material_id]" class="form-select form-select-sm material-select calc-trigger">
                    <option value="">--</option>
                    ${materialOptions}
                </select>
            </td>
            <td>
                <div class="row g-1">
                    <div class="col-4"><input type="number" step="0.1" name="rows[${rowCount}][ancho]" class="form-control form-control-sm calc-trigger" placeholder="Ancho"></div>
                    <div class="col-4"><input type="number" step="0.1" name="rows[${rowCount}][alto]" class="form-control form-control-sm calc-trigger" placeholder="Alto"></div>
                    <div class="col-4"><input type="number" step="0.1" name="rows[${rowCount}][gap]" class="form-control form-control-sm calc-trigger" value="0.5"></div>
                </div>
            </td>
            <td>
                <input type="number" name="rows[${rowCount}][cantidad]" class="form-control form-control-sm cantidad-input calc-trigger" min="1" value="0" required>
            </td>
            <td>
                <input type="number" name="rows[${rowCount}][cantidad_impreso]" class="form-control form-control-sm impreso-input" min="0" value="0" required>
            </td>
            <td>
                <div class="p-1 small text-info text-center fw-bold result-cell" style="min-height: 1.5rem; line-height: 1.2;">--</div>
                <input type="hidden" name="rows[${rowCount}][porcentaje_impresion]" class="form-control text-center" value="0">
                <input type="hidden" name="rows[${rowCount}][distribucion_texto]" class="distribucion-input">
            </td>
            <td class="text-center">
                <button type="button" class="btn-crud reset-row"><i class="bi bi-arrow-clockwise"></i></button>
                <button type="button" class="btn-crud delete-row"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tableBody.appendChild(tr);

        // Add calc triggers
        tr.querySelectorAll('.calc-trigger').forEach(el => {
            el.addEventListener('input', () => updateCalculations(tr));
        });

        // Sync percentage and hidden fields
        const cantInput = tr.querySelector('.cantidad-input');
        const imprInput = tr.querySelector('.impreso-input');
        const percInput = tr.querySelector('input[name*="porcentaje_impresion"]');

        const updatePercentage = () => {
            const total = parseInt(cantInput.value) || 0;
            const done = parseInt(imprInput.value) || 0;
            if (total > 0) {
                percInput.value = Math.min(100, Math.max(0, Math.round((done / total) * 100)));
            }
        };

        cantInput.addEventListener('input', updatePercentage);
        imprInput.addEventListener('input', updatePercentage);

        // Delete row
        tr.querySelector('.delete-row').addEventListener('click', function() {
            tr.remove();
        });

        // Reset row
        tr.querySelector('.reset-row').addEventListener('click', function() {
            tr.querySelectorAll('input, select').forEach(input => {
                if (input.type === 'number') input.value = input.name.includes('[gap]') ? 0.5 : 0;
                else if (input.tagName === 'SELECT') input.selectedIndex = 0;
                else if (input.type !== 'hidden') input.value = '';
            });
            updateCalculations(tr);
        });

        rowCount++;
    }

    function updateCalculations(tr) {
        const materialSelect = tr.querySelector('.material-select');
        const selectedOption = materialSelect.options[materialSelect.selectedIndex];
        
        const resultCell = tr.querySelector('.result-cell');
        const distribucionInput = tr.querySelector('.distribucion-input');

        if (!selectedOption || materialSelect.value === '') {
            resultCell.textContent = '--';
            distribucionInput.value = '';
            return;
        }

        const tipoMaterial = selectedOption.dataset.tipo; // ROLLO o PLANCHA
        const materialAncho = parseFloat(selectedOption.dataset.ancho);
        const materialLargo = parseFloat(selectedOption.dataset.largo); // cm

        const anchoPieza = parseFloat(tr.querySelector('input[name*="[ancho]"]').value) || 0;
        const altoPieza = parseFloat(tr.querySelector('input[name*="[alto]"]').value) || 0;
        const gap = parseFloat(tr.querySelector('input[name*="[gap]"]').value) || 0;
        const totalUds = parseInt(tr.querySelector('input[name*="[cantidad]"]').value) || 0;

        if (anchoPieza <= 0 || altoPieza <= 0 || totalUds <= 0) {
            resultCell.textContent = '--';
            distribucionInput.value = '';
            return;
        }

        let distribucion = '';

        if (tipoMaterial === 'ROLLO') {
            const matAnchoEffective = materialAncho; // cm
            const panelesAncho = Math.floor(matAnchoEffective / (anchoPieza + gap));
            
            if (panelesAncho > 0) {
                const materialLargoCM = materialLargo;
                const avanceFilaCM = altoPieza + gap;
                
                const filasPorRollo = Math.floor(materialLargoCM / avanceFilaCM);
                const copiasPorRollo = panelesAncho * filasPorRollo;
                
                const rollosCompletos = Math.floor(totalUds / copiasPorRollo);
                const copiasRestantes = totalUds % copiasPorRollo;
                
                const filasRestantes = Math.ceil(copiasRestantes / panelesAncho);
                const cmAdicionales = filasRestantes * avanceFilaCM;
                
                distribucion = `${rollosCompletos} rollo(s) + ${Math.round(cmAdicionales)} cm (${copiasPorRollo} copias/rollo + ${copiasRestantes} adicionales)`;
                resultCell.textContent = distribucion;
                distribucionInput.value = distribucion;
            } else {
                resultCell.textContent = 'Pieza > Ancho';
                distribucionInput.value = '';
            }

        } else if (tipoMaterial === 'PLANCHA') {
            const panelesAncho = Math.floor(materialAncho / (anchoPieza + gap));
            const panelesLargo = Math.floor(materialLargo / (altoPieza + gap));
            
            const udsPorUnidad = panelesAncho * panelesLargo;
            
            if (udsPorUnidad > 0) {
                const planchas = totalUds / udsPorUnidad;
                distribucion = `${Math.ceil(planchas)} plancha(s) (${udsPorUnidad} por pza)`;
                resultCell.textContent = distribucion;
                distribucionInput.value = distribucion;
            } else {
                resultCell.textContent = 'Pieza > Plancha';
                distribucionInput.value = '';
            }
        }
    }

    // Add datalist once to the form
    const datalist = document.createElement('datalist');
    datalist.id = 'campanasList';
    datalist.innerHTML = campanas.map(c => `<option value="${c.nombre}">${c.nombre}</option>`).join('');
    reportForm.appendChild(datalist);

    addRowBtn.addEventListener('click', createRow);

    // Add first row by default
    createRow();
});
