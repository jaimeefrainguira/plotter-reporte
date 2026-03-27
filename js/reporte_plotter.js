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
    const asignaciones = JSON.parse(reportForm.dataset.asignaciones || '[]');

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

    function createRow(initialData = null) {
        const tr = document.createElement('tr');
        tr.dataset.index = rowCount;

        const plotterOptions = plotters.map(p => `<option value="${p}">${p}</option>`).join('');

        const rowData = initialData || {};
        const cantidadAsignada = Number(rowData.cantidad || 0);
        const cantidadProducida = Number(rowData.cantidad_impreso || 0);
        const progreso = Number.isFinite(Number(rowData.porcentaje_impresion))
            ? Number(rowData.porcentaje_impresion)
            : (cantidadAsignada > 0 ? Math.round((cantidadProducida / cantidadAsignada) * 100) : 0);

        tr.innerHTML = `
            <td>
                <select name="rows[${rowCount}][plotter]" class="form-select form-select-sm" required>
                    <option value="">Seleccionar...</option>
                    ${plotterOptions}
                </select>
            </td>
            <td>
                <input list="campanasList" name="rows[${rowCount}][campana]" class="form-control form-control-sm" required placeholder="Campaña..." value="${rowData.campana || ''}">
            </td>
            <td>
                <input type="text" name="rows[${rowCount}][descripcion]" class="form-control form-control-sm" required placeholder="Trabajo..." value="${rowData.descripcion || ''}">
            </td>
            <td>
                <input type="text" name="rows[${rowCount}][material]" class="form-control form-control-sm" required placeholder="Material..." value="${rowData.material || ''}">
            </td>
            <td>
                <input type="number" name="rows[${rowCount}][cantidad]" class="form-control form-control-sm cantidad-input" min="1" value="${cantidadAsignada > 0 ? cantidadAsignada : 0}" required>
            </td>
            <td>
                <input type="number" name="rows[${rowCount}][cantidad_impreso]" class="form-control form-control-sm impreso-input" min="0" value="${cantidadProducida > 0 ? cantidadProducida : 0}" required>
            </td>
            <td class="percentage-cell">
                <div class="input-group input-group-sm">
                    <input type="number" name="rows[${rowCount}][porcentaje_impresion]" class="form-control text-center" min="0" max="100" value="${progreso >= 0 ? progreso : 0}" required>
                    <span class="input-group-text">%</span>
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn-crud reset-row"><i class="bi bi-arrow-clockwise"></i></button>
                <button type="button" class="btn-crud delete-row"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tableBody.appendChild(tr);

        const selectPlotter = tr.querySelector(`select[name="rows[${rowCount}][plotter]"]`);
        if (rowData.plotter && selectPlotter) {
            selectPlotter.value = rowData.plotter;
        }

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

        if (Array.isArray(asignaciones) && asignaciones.length > 0) {
            asignaciones.forEach((asig) => {
                const plotterNumero = Number(asig.plotter_id || 0);
                const asignado = Number(asig.tirajes_asignados || 0);
                const producido = Number(asig.tirajes_producidos || 0);
                const progresoRow = asignado > 0 ? Math.round((producido / asignado) * 100) : 0;
                createRow({
                    plotter: plotterNumero > 0 ? `PLOTTER ${plotterNumero}` : '',
                    campana: `${asig.campana_nombre || ''} / ${asig.trabajo_nombre || ''}`.trim(),
                    descripcion: asig.trabajo_nombre || '',
                    material: asig.material_nombre || '',
                    cantidad: asignado,
                    cantidad_impreso: producido,
                    porcentaje_impresion: progresoRow,
                });
            });
        } else {
            createRow();
        }
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
