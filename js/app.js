document.querySelectorAll('.form-delete').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm('¿Deseas eliminar este reporte? Esta acción no se puede deshacer.')) {
            event.preventDefault();
        }
    });
});

const modalElement = document.getElementById('plotterModal');
if (modalElement && window.bootstrap) {
    const shouldOpen = modalElement.dataset.openOnLoad === '1';
    if (shouldOpen) {
if (window.DASHBOARD_MODAL?.shouldOpen) {
    const modalElement = document.getElementById('plotterModal');
    if (modalElement && window.bootstrap) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}
