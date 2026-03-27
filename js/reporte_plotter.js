document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#plotterTable tbody');
    const addRowBtn = document.getElementById('addRow');
    const reportForm = document.getElementById('reportForm');
    const startShiftBtn = document.getElementById('startShiftBtn');
    const shiftStatus = document.getElementById('shiftStatus');
    const operatorInput = document.getElementById('operatorName');
    const shiftStartInput = document.getElementById('shiftStartDisplay');
    const shiftEndInput = document.getElementById('shiftEndDisplay');
    const jornadaOperatorHidden = document.getElementById('jornadaOperator');
    const jornadaStartHidden = document.getElementById('jornadaStart');
    const jornadaEndHidden = document.getElementById('jornadaEnd');
    const generatePdfBtn = document.getElementById('generatePdfBtn');

    if (!tableBody || !addRowBtn || !reportForm) return;

    let rowCount = 0;
    let jornadaIniciada = false;

    const plotters = JSON.parse(reportForm.dataset.plotters || '[]');
    const campanas = JSON.parse(reportForm.dataset.campanas || '[]');

    function formatDateTime(now) {
        return now.toLocaleString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        });
    }

    function setShiftStatus(message, cssClass = 'text-muted') {
        if (!shiftStatus) return;
        shiftStatus.className = cssClass;
        shiftStatus.textContent = message;
    }

    function createRow() {
        const tr = document.createElement('tr');
        tr.dataset.index = rowCount;

        const plotterOptions = plotters.map(p => `<option value="${p}">${p}</option>`).join('');

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
                <input type="text" name="rows[${rowCount}][descripcion]" class="form-control form-control-sm" required placeholder="Trabajo...">
            </td>
            <td>
                <input type="text" name="rows[${rowCount}][material]" class="form-control form-control-sm" required placeholder="Material...">
            </td>
            <td>
                <input type="number" name="rows[${rowCount}][cantidad]" class="form-control form-control-sm cantidad-input" min="1" value="0" required>
            </td>
            <td>
                <input type="number" name="rows[${rowCount}][cantidad_impreso]" class="form-control form-control-sm impreso-input" min="0" value="0" required>
            </td>
            <td class="percentage-cell">
                <div class="input-group input-group-sm">
                    <input type="number" name="rows[${rowCount}][porcentaje_impresion]" class="form-control text-center" min="0" max="100" value="0" required>
                    <span class="input-group-text">%</span>
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn-crud reset-row"><i class="bi bi-arrow-clockwise"></i></button>
                <button type="button" class="btn-crud delete-row"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tableBody.appendChild(tr);

        tr.querySelector('.delete-row').addEventListener('click', function() {
            tr.remove();
        });

        tr.querySelector('.reset-row').addEventListener('click', function() {
            tr.querySelectorAll('input, select').forEach(input => {
                if (input.type === 'number') input.value = 0;
                else if (input.tagName === 'SELECT') input.selectedIndex = 0;
                else if (input.type !== 'hidden') input.value = '';
            });
        });

        rowCount++;
    }

    function activarControlesJornada() {
        addRowBtn.disabled = false;
        generatePdfBtn.disabled = false;
        reportForm.querySelectorAll('input, select, button').forEach(el => {
            if (el === startShiftBtn || el.type === 'hidden') return;
            if (el.classList.contains('delete-row') || el.classList.contains('reset-row')) {
                el.disabled = false;
                return;
            }

            if (el.closest('#plotterTable') || el === addRowBtn || el === generatePdfBtn) {
                el.disabled = false;
            }
        });
    }

    const datalist = document.createElement('datalist');
    datalist.id = 'campanasList';
    datalist.innerHTML = campanas.map(c => `<option value="${c.nombre}">${c.nombre}</option>`).join('');
    reportForm.appendChild(datalist);

    addRowBtn.addEventListener('click', createRow);

    startShiftBtn?.addEventListener('click', function() {
        if (jornadaIniciada) {
            setShiftStatus('La jornada ya está iniciada.', 'text-success fw-semibold');
            return;
        }

        const nombre = operatorInput.value.trim();
        if (!nombre) {
            setShiftStatus('Ingresa el nombre del operador para iniciar.', 'text-danger fw-semibold');
            operatorInput.focus();
            return;
        }

        const now = new Date();
        const nowIso = now.toISOString();
        const formatted = formatDateTime(now);

        jornadaIniciada = true;
        operatorInput.readOnly = true;
        jornadaOperatorHidden.value = nombre;
        jornadaStartHidden.value = nowIso;
        shiftStartInput.value = formatted;
        startShiftBtn.disabled = true;

        createRow();
        activarControlesJornada();
        setShiftStatus('Jornada iniciada. Ya puedes registrar trabajos por plotter.', 'text-success fw-semibold');
    });

    reportForm.addEventListener('submit', function(event) {
        if (!jornadaIniciada) {
            event.preventDefault();
            setShiftStatus('Debes iniciar la jornada antes de generar el reporte.', 'text-danger fw-semibold');
            return;
        }

        const now = new Date();
        jornadaEndHidden.value = now.toISOString();
        shiftEndInput.value = formatDateTime(now);

        const hasRows = tableBody.querySelectorAll('tr').length > 0;
        if (!hasRows) {
            event.preventDefault();
            setShiftStatus('Debes agregar al menos un trabajo para generar el reporte.', 'text-danger fw-semibold');
            return;
        }

        setShiftStatus('Generando reporte PDF de la jornada...', 'text-primary fw-semibold');
    });

    addRowBtn.disabled = true;
    generatePdfBtn.disabled = true;
    setShiftStatus('Esperando inicio de jornada.', 'text-muted');
});
