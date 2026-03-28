document.querySelectorAll('.form-delete').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm('¿Deseas eliminar este reporte? Esta acción no se puede deshacer.')) {
            event.preventDefault();
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const startBtn = document.getElementById('dashboardStartShiftBtn');
    const endBtn = document.getElementById('dashboardEndShiftBtn');
    const operatorInput = document.getElementById('dashboardOperatorName');
    const startInput = document.getElementById('dashboardShiftStart');
    const status = document.getElementById('dashboardShiftStatus');

    if (!startBtn || !endBtn || !operatorInput || !startInput || !status || typeof ShiftSession === 'undefined') {
        return;
    }

    const formatDateTime = (date) => date.toLocaleString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });

    const loadSessionState = () => {
        const session = ShiftSession.getSession();
        if (!session) {
            operatorInput.readOnly = false;
            operatorInput.value = '';
            startInput.value = '';
            startBtn.disabled = false;
            status.className = 'small mt-2 text-muted';
            status.textContent = 'Sin jornada iniciada.';
            return;
        }

        operatorInput.value = session.operator;
        operatorInput.readOnly = true;
        startInput.value = formatDateTime(new Date(session.startIso));
        startBtn.disabled = true;
        status.className = 'small mt-2 text-success fw-semibold';
        status.textContent = 'Jornada activa guardada en este equipo.';
    };

    startBtn.addEventListener('click', () => {
        const operator = operatorInput.value.trim();
        if (!operator) {
            status.className = 'small mt-2 text-danger fw-semibold';
            status.textContent = 'Ingresa el nombre del operador para iniciar la jornada.';
            operatorInput.focus();
            return;
        }

        ShiftSession.saveSession(operator, new Date().toISOString());
        loadSessionState();
    });

    endBtn.addEventListener('click', () => {
        ShiftSession.clearSession();
        loadSessionState();
    });

    loadSessionState();
});
