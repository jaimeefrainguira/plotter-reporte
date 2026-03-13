document.querySelectorAll('.form-delete').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm('¿Deseas eliminar este reporte? Esta acción no se puede deshacer.')) {
            event.preventDefault();
        }
    });
});
