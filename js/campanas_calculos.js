/**
 * Lógica de cálculo industrial para panelado y consumos reales.
 *
 * Fórmulas principales:
 *   piezasPorFila       = floor(anchoMaterial / (anchoPieza + gapH))
 *   altoUsado           = altoPieza + gapV
 *   copiasPorRollo      = floor(largoMaterial / altoUsado) * piezasPorFila
 *   rollos              = floor(largoTotal / largoMaterial)
 *   sobrante            = largoTotal % largoMaterial              (cm)
 *   copiasExtra         = floor(sobrante / altoUsado) * piezasPorFila
 *   panelesPorCopia     = ceil(anchoUsado / anchoPanel)            (normalmente 1)
 *   totalCopias         = (rollos * copiasPorRollo) + copiasExtra
 */

document.addEventListener('DOMContentLoaded', () => {
    const modalTrabajo = document.getElementById('modalTrabajo');
    const triggers = document.querySelectorAll('.calc-trigger');
    const checkRotar = document.getElementById('checkRotar');
    
    triggers.forEach(el => {
        el.addEventListener('input', runCalculations);
        if (el.type === 'checkbox' || el.tagName === 'SELECT') {
            el.addEventListener('change', runCalculations);
        }
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
            if (checkRotar) checkRotar.checked = false;
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
        if (checkRotar) checkRotar.checked = false;
        document.getElementById('resultsRotated').style.display = 'none';
        document.getElementById('mejorOpcionMsg').style.display = 'none';
    });

    // Botón para intercambiar medidas (Swap)
    document.getElementById('btnSwapOrientacion')?.addEventListener('click', function() {
        const ancho = document.getElementById('field_ancho_panel');
        const alto = document.getElementById('field_alto_panel');
        const temp = ancho.value;
        ancho.value = alto.value;
        alto.value = temp;
        runCalculations();
    });

    /**
     * Calcula la distribución y consumo según el tipo de material.
     *
     * Para ROLLO usa las fórmulas:
     *   piezasPorFila  = floor(materialAncho(mm) / (anchoPieza + gapH))
     *   altoUsado      = altoPieza + gapV
     *   filasPorRollo  = floor(materialLargo(mm) / altoUsado)   — largo de rollo en mm
     *   copiasPorRollo = piezasPorFila * filasPorRollo
     *   rollos         = floor(totalUds / copiasPorRollo)
     *   sobrante       = totalUds % copiasPorRollo              — unidades restantes
     *   filasRestantes = ceil(sobrante / piezasPorFila)
     *   cmAdicionales  = filasRestantes * altoUsado / 10        — convertir mm → cm
     *
     * Para PLANCHA:
     *   panelesAncho   = floor(materialAncho(mm) / (anchoPieza + gapH))
     *   panelesAlto    = floor(materialLargo(mm) / (altoPieza + gapV))
     *   udsPorPlancha  = panelesAncho * panelesAlto
     *   planchas       = ceil(totalUds / udsPorPlancha)
     */
    function calculateData(params) {
        const { tipoMaterial, materialAncho, materialLargo, anchoPieza, altoPieza, gapH, gapV, totalUds } = params;
        
        // Material viene en cm → convertir a mm para calcular contra piezas en mm
        const matAnchoMM = materialAncho * 10;
        const matLargoMM = materialLargo * 10;

        let res = {
            panelesAncho: 0,
            udsPorUnidad: 0,
            consumoTotal: 0,
            distribucion: '',
            valConsumo: 0,
            error: null
        };

        if (tipoMaterial === 'ROLLO') {
            // piezasPorFila = floor(anchoMaterial / (anchoPieza + gapH))
            const piezasPorFila = Math.floor(matAnchoMM / (anchoPieza + gapH));
            res.panelesAncho = piezasPorFila;

            if (piezasPorFila > 0) {
                const altoUsado = altoPieza + gapV;

                // copiasPorRollo = floor(largoMaterial / altoUsado) * piezasPorFila
                const filasPorRollo = Math.floor(matLargoMM / altoUsado);
                const copiasPorRollo = piezasPorFila * filasPorRollo;

                // rollos = floor(totalUds / copiasPorRollo)
                // sobrante = totalUds % copiasPorRollo  (unidades restantes)
                const rollosCompletos = Math.floor(totalUds / copiasPorRollo);
                const copiasRestantes = totalUds % copiasPorRollo;

                // copiasExtra = floor(sobrante / altoUsado) * piezasPorFila
                // Aquí sobrante es en unidades, así que calculamos cuántas filas extras
                const filasRestantes = Math.ceil(copiasRestantes / piezasPorFila);
                const cmAdicionales = (filasRestantes * altoUsado) / 10; // mm → cm

                // Material total en metros
                const metrosTotales = (rollosCompletos * (materialLargo / 100)) + (cmAdicionales / 100);
                
                res.valConsumo = metrosTotales;
                res.consumoTotal = metrosTotales.toFixed(2) + ' m';
                res.distribucion = `${rollosCompletos} rollo(s) + ${Math.round(cmAdicionales)} cm`;
                res.udsPorUnidad = copiasPorRollo;
            } else {
                res.error = 'Pieza excede ancho del material';
            }
        } else if (tipoMaterial === 'PLANCHA') {
            // panelesPorCopia = ceil(anchoPieza / anchoPlancha)  — normalmente 1
            const panelesAncho = Math.floor(matAnchoMM / (anchoPieza + gapH));
            const panelesAlto = Math.floor(matLargoMM / (altoPieza + gapV));
            const udsPorPlancha = panelesAncho * panelesAlto;
            
            res.panelesAncho = panelesAncho;
            res.udsPorUnidad = udsPorPlancha;
            
            if (udsPorPlancha > 0) {
                const planchas = totalUds / udsPorPlancha;
                res.valConsumo = planchas;
                res.consumoTotal = Math.ceil(planchas) + ' plancha(s)';
                res.distribucion = `${Math.ceil(planchas)} plancha(s) — ${udsPorPlancha} uds/plancha`;
            } else {
                res.error = 'Pieza excede tamaño de plancha';
            }
        }
        return res;
    }

    function runCalculations() {
        const materialSelect = document.getElementById('field_material_id');
        const selectedOption = materialSelect.options[materialSelect.selectedIndex];
        
        if (!selectedOption || materialSelect.value === '') return;

        const baseParams = {
            tipoMaterial: selectedOption.dataset.tipo,
            materialAncho: parseFloat(selectedOption.dataset.ancho),
            materialLargo: parseFloat(selectedOption.dataset.largo),
            anchoPieza: parseFloat(document.getElementById('field_ancho_panel').value) || 0,
            altoPieza: parseFloat(document.getElementById('field_alto_panel').value) || 0,
            gapH: parseFloat(document.getElementById('field_separacion_h').value) || 0,
            gapV: parseFloat(document.getElementById('field_separacion_v').value) || 0,
            totalUds: parseInt(document.getElementById('field_cantidad').value) || 0
        };

        if (baseParams.anchoPieza <= 0 || baseParams.altoPieza <= 0 || baseParams.totalUds <= 0) return;

        // — Orientación 1 (Original) —
        const res1 = calculateData(baseParams);
        
        if (res1.error) {
            showError(res1.error);
            document.getElementById('resultsRotated').style.display = 'none';
        } else {
            document.getElementById('res_unidades_unidad').textContent = res1.udsPorUnidad;
            document.getElementById('res_consumo_total').textContent = res1.consumoTotal;
            document.getElementById('res_distribucion').textContent = res1.distribucion;

            // Set hidden fields para guardar en BD
            document.getElementById('field_total_metros').value = (baseParams.tipoMaterial === 'ROLLO') ? res1.valConsumo : 0;
            document.getElementById('field_total_planchas').value = (baseParams.tipoMaterial === 'PLANCHA') ? res1.valConsumo : 0;
            document.getElementById('field_distribucion_texto').value = res1.distribucion;
            document.getElementById('field_unidades_por_rollo').value = res1.udsPorUnidad;

            // Actualizar etiqueta según tipo
            const labelUnidad = document.getElementById('res_label_unidad');
            const labelConsumo = document.getElementById('res_label_consumo');
            if (labelUnidad) {
                labelUnidad.textContent = baseParams.tipoMaterial === 'ROLLO' ? 'uds por rollo' : 'uds por plancha';
            }
            if (labelConsumo) {
                labelConsumo.textContent = baseParams.tipoMaterial === 'ROLLO' ? 'Metros lineales' : 'Planchas';
            }
        }

        // — Orientación 2 (Rotada) si el switch está ON —
        if (checkRotar && checkRotar.checked) {
            const rotParams = { ...baseParams, anchoPieza: baseParams.altoPieza, altoPieza: baseParams.anchoPieza };
            const res2 = calculateData(rotParams);

            if (!res2.error) {
                document.getElementById('resultsRotated').style.display = 'block';
                document.getElementById('res_unidades_unidad_rot').textContent = res2.udsPorUnidad;
                document.getElementById('res_consumo_total_rot').textContent = res2.consumoTotal;
                
                // Comparar eficiencia entre ambas orientaciones
                const msg = document.getElementById('mejorOpcionMsg');
                if (res2.valConsumo < res1.valConsumo - 0.01) { 
                    msg.style.display = 'block';
                    msg.innerHTML = `<i class="bi bi-lightning-fill"></i> ¡La <b>Orientación Rotada</b> ahorraría ${(res1.valConsumo - res2.valConsumo).toFixed(2)} ${baseParams.tipoMaterial === 'ROLLO' ? 'm' : 'planchas'}!`;
                    msg.className = "alert alert-warning mt-2 py-1 small text-center";
                } else if (res1.valConsumo < res2.valConsumo - 0.01) {
                    msg.style.display = 'block';
                    msg.innerHTML = `<i class="bi bi-star-fill"></i> La <b>Orientación Actual</b> es la más eficiente.`;
                    msg.className = "alert alert-success mt-2 py-1 small text-center";
                } else {
                    msg.style.display = 'block';
                    msg.innerHTML = `<i class="bi bi-info-circle"></i> Ambas orientaciones consumen lo mismo.`;
                    msg.className = "alert alert-info mt-2 py-1 small text-center";
                }
            } else {
                document.getElementById('resultsRotated').style.display = 'none';
                document.getElementById('mejorOpcionMsg').style.display = 'none';
            }
        } else {
            document.getElementById('resultsRotated').style.display = 'none';
            document.getElementById('mejorOpcionMsg').style.display = 'none';
        }
    }

    function showError(msg) {
        document.getElementById('res_unidades_unidad').textContent = 'ERR';
        document.getElementById('res_consumo_total').textContent = msg;
        document.getElementById('res_distribucion').textContent = '--';
    }
});
