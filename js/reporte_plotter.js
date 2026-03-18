document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#plotterTable tbody');
    const addRowBtn = document.getElementById('addRow');
    const reportForm = document.getElementById('reportForm');
    
    if (!tableBody || !addRowBtn || !reportForm) return;

    let rowCount = 0;

    const plotters = JSON.parse(reportForm.dataset.plotters || '[]');
    const campanas = JSON.parse(reportForm.dataset.campanas || '[]');

    function createRow() {
        const tr = document.createElement('tr');
        tr.dataset.index = rowCount;

        // Plotter Select
        let plotterOptions = plotters.map(p => `<option value="${p}">${p}</option>`).join('');

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
                <input type="number" name="rows[${rowCount}][cantidad]" class="form-control form-control-sm cantidad-input" min="1" value="0" required>
            </td>
            <td>
                <input type="number" name="rows[${rowCount}][cantidad_impreso]" class="form-control form-control-sm impreso-input" min="0" value="0" required>
            </td>
            <td class="percentage-cell text-center">
                <span class="percentage-text">0%</span>
                <input type="hidden" name="rows[${rowCount}][porcentaje_impresion]" class="percentage-hidden" value="0">
            </td>
            <td class="text-center">
                <button type="button" class="btn-crud reset-row"><i class="bi bi-arrow-clockwise"></i></button>
                <button type="button" class="btn-crud delete-row"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tableBody.appendChild(tr);

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
                else if (input.type !== 'hidden') input.value = '';
            });
            updatePercentage();
        });

        rowCount++;
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
