/**
 * Lógica de cálculo industrial para panelado y consumos reales
 * (Inspirado en sistemas industriales de impresión y corte)
 */

document.addEventListener('DOMContentLoaded', () => {
    const modalTrabajo = document.getElementById('modalTrabajo');
    const triggers = document.querySelectorAll('.calc-trigger');
    
    triggers.forEach(el => {
        el.addEventListener('input', runCalculations);
    });

    // Manejo de edición de trabajos
    document.querySelectorAll('.btn-edit-trabajo').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.dataset.trabajo);
            document.getElementById('modalTitle').textContent = 'Editar Item de Trabajo';
            document.getElementById('field_trabajo_id').value = data.id;
            document.getElementById('field_descripcion').value = data.descripcion;
            document.getElementById('field_cantidad').value = data.cantidad;
            document.getElementById('field_ancho_panel').value = data.ancho_panel;
            document.getElementById('field_alto_panel').value = data.alto_panel;
            document.getElementById('field_material_id').value = data.material_id;
            document.getElementById('field_separacion_h').value = data.separacion_h;
            document.getElementById('field_separacion_v').value = data.separacion_v;
            runCalculations();
        });
    });

    document.getElementById('btnNuevoTrabajo').addEventListener('click', () => {
        document.getElementById('modalTitle').textContent = 'Nuevo Item de Trabajo';
        document.getElementById('formTrabajo').reset();
        document.getElementById('field_trabajo_id').value = '';
        document.getElementById('field_unidades_por_rollo').value = '';
        document.getElementById('res_unidades_unidad').textContent = '--';
        document.getElementById('res_consumo_total').textContent = '--';
        document.getElementById('res_distribucion').textContent = '--';
    });

    function runCalculations() {
        const materialSelect = document.getElementById('field_material_id');
        const selectedOption = materialSelect.options[materialSelect.selectedIndex];
        
        if (!selectedOption || materialSelect.value === '') return;

        const tipoMaterial = selectedOption.dataset.tipo; // ROLLO o PLANCHA
        const materialAncho = parseFloat(selectedOption.dataset.ancho);
        const materialLargo = parseFloat(selectedOption.dataset.largo); // 5000 para rollos (50m)

        const anchoPieza = parseFloat(document.getElementById('field_ancho_panel').value) || 0;
        const altoPieza = parseFloat(document.getElementById('field_alto_panel').value) || 0;
        const gapH = parseFloat(document.getElementById('field_separacion_h').value) || 0;
        const gapV = parseFloat(document.getElementById('field_separacion_v').value) || 0;
        const totalUds = parseInt(document.getElementById('field_cantidad').value) || 0;

        if (anchoPieza <= 0 || altoPieza <= 0 || totalUds <= 0) return;

        let panelesAncho = 0;
        let consumoUnidad = 0; // Metros si es ROLLO, Planchas si es PLANCHA
        let udsPorUnidad = 0;
        let distribucion = '';

        if (tipoMaterial === 'ROLLO') {
            // Un rollo de 122cm (1220mm). Medidas en mm en el frontend
            const materialAnchoMM = materialAncho * 10; // Convertimos cm a mm si hace falta
            // Asumimos que el ancho del material está en cm en la DB y el panel en mm
            const matAnchoEffective = materialAncho * 10; 
            
            panelesAncho = Math.floor(matAnchoEffective / (anchoPieza + gapH));
            
            if (panelesAncho > 0) {
                // Cantidad de piezas por cada metro (1000mm)
                // Se calcula según el avance del ALTO del panel
                udsPorUnidad = Math.floor(panelesAncho * (1000 / (altoPieza + gapV)));
                
                // Metros totales
                const metros = (totalUds / panelesAncho) * (altoPieza + gapV) / 1000;
                
                const rollos = Math.floor(metros / (materialLargo / 100)); // largo está en cm (5000cm = 50m)
                const metrosSobrantes = metros % (materialLargo / 100);
                
                consumoUnidad = metros.toFixed(2);
                distribucion = `${rollos} rollo(s) + ${metrosSobrantes.toFixed(2)} m`;
                
                document.getElementById('res_unidades_unidad').textContent = udsPorUnidad;
                document.getElementById('res_label_unidad').textContent = 'piezas por m lineal';
                document.getElementById('res_consumo_total').textContent = consumoUnidad + ' m';
                document.getElementById('res_label_consumo').textContent = 'Metros totales';
                document.getElementById('res_distribucion').textContent = distribucion;

                // Set hidden fields for POST
                document.getElementById('field_total_metros').value = consumoUnidad;
                document.getElementById('field_distribucion_texto').value = distribucion;
                document.getElementById('field_unidades_por_rollo').value = udsPorUnidad;
            } else {
                showError('La pieza es más ancha que el material.');
            }

        } else if (tipoMaterial === 'PLANCHA') {
            // Planchas de 1220x2440mm
            panelesAncho = Math.floor(materialAncho / (anchoPieza + gapH));
            const panelesLargo = Math.floor(materialLargo / (altoPieza + gapV));
            
            udsPorUnidad = panelesAncho * panelesLargo;
            
            if (udsPorUnidad > 0) {
                const planchas = totalUds / udsPorUnidad;
                consumoUnidad = planchas.toFixed(2);
                distribucion = `${Math.ceil(planchas)} plancha(s)`;
                
                document.getElementById('res_unidades_unidad').textContent = udsPorUnidad;
                document.getElementById('res_label_unidad').textContent = 'por plancha';
                document.getElementById('res_consumo_total').textContent = consumoUnidad;
                document.getElementById('res_label_consumo').textContent = 'Planchas requeridas';
                document.getElementById('res_distribucion').textContent = distribucion;

                document.getElementById('field_total_planchas').value = consumoUnidad;
                document.getElementById('field_distribucion_texto').value = distribucion;
            } else {
                showError('La pieza es más grande que la plancha.');
            }
        }
    }

    function showError(msg) {
        document.getElementById('res_unidades_unidad').textContent = 'ERR';
        document.getElementById('res_consumo_total').textContent = msg;
        document.getElementById('res_distribucion').textContent = '--';
    }
});
