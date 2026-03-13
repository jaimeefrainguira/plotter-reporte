document.querySelectorAll('.form-delete').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm('¿Deseas eliminar este reporte? Esta acción no se puede deshacer.')) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('.js-plotter-table').forEach((table) => {
    table.addEventListener('click', () => {
        const url = table.getAttribute('data-url');
        if (url) {
            window.location.href = url;
        }
    });
});
