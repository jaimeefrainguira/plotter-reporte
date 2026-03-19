/**
 * Calculadora industrial de panelado y consumo de material.
 *
 * ┌──────────────────────────────────────────────────────────┐
 * │  FÓRMULAS PRINCIPALES                                    │
 * │                                                          │
 * │  piezasPorFila   = floor(anchoPanel / (anchoPieza+gapH)) │
 * │  altoUsado       = altoPieza + gapV                       │
 * │  anchoUsado      = anchoPieza + gapH                      │
 * │                                                          │
 * │  copiasPorRollo  = floor(largoMaterial / altoUsado)       │
 * │                    × piezasPorFila                        │
 * │                                                          │
 * │  filasNecesarias = ceil(totalUds / piezasPorFila)         │
 * │  largoTotal      = filasNecesarias × altoUsado   (cm)     │
 * │                                                          │
 * │  rollos          = floor(largoTotal / largoMaterial)       │
 * │  sobrante        = largoTotal % largoMaterial     (cm)     │
 * │                                                          │
 * │  copiasExtra     = floor(sobrante / altoUsado)            │
 * │                    × piezasPorFila                        │
 * │                                                          │
 * │  panelesPorCopia = ceil(anchoUsado / anchoPanel)          │
 * │                                                          │
 * │  materialSobrante = sobrante % altoUsado          (cm)    │
 * └──────────────────────────────────────────────────────────┘
 *
 * Notas sobre unidades:
 *   - Piezas (ancho_panel, alto_panel) y gaps: mm (desde el formulario)
 *   - Material (medida_ancho, medida_largo): cm (desde la BD)
 *   - Se convierte material a mm internamente para operar.
 */

document.addEventListener('DOMContentLoaded', () => {
    const triggers = document.querySelectorAll('.calc-trigger');
    const checkRotar = document.getElementById('checkRotar');

    triggers.forEach(el => {
        el.addEventListener('input', runCalculations);
        if (el.type === 'checkbox' || el.tagName === 'SELECT') {
            el.addEventListener('change', runCalculations);
        }
    });

    // ── Edición de trabajo existente ──
    document.querySelectorAll('.btn-edit-trabajo').forEach(btn => {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.dataset.trabajo);
            document.getElementById('modalTitle').textContent = 'Editar Item de Trabajo';
            document.getElementById('field_trabajo_id').value   = d.id;
            document.getElementById('field_descripcion').value   = d.descripcion;
            document.getElementById('field_cantidad').value      = d.cantidad;
            document.getElementById('field_ancho_panel').value   = d.ancho_panel;
            document.getElementById('field_alto_panel').value    = d.alto_panel;
            document.getElementById('field_material_id').value   = d.material_id;
            document.getElementById('field_separacion_h').value  = d.separacion_h;
            document.getElementById('field_separacion_v').value  = d.separacion_v;
            if (checkRotar) checkRotar.checked = false;
            runCalculations();
        });
    });

    // ── Nuevo trabajo ──
    document.getElementById('btnNuevoTrabajo').addEventListener('click', () => {
        document.getElementById('modalTitle').textContent = 'Nuevo Item de Trabajo';
        document.getElementById('formTrabajo').reset();
        document.getElementById('field_trabajo_id').value        = '';
        document.getElementById('field_unidades_por_rollo').value = '';
        resetUI();
        if (checkRotar) checkRotar.checked = false;
        document.getElementById('resultsRotated').style.display = 'none';
        document.getElementById('mejorOpcionMsg').style.display = 'none';
    });

    // ── Swap orientación (USAR ESTA) ──
    document.getElementById('btnSwapOrientacion')?.addEventListener('click', () => {
        const ancho = document.getElementById('field_ancho_panel');
        const alto  = document.getElementById('field_alto_panel');
        [ancho.value, alto.value] = [alto.value, ancho.value];
        runCalculations();
    });

    // ═══════════════════════════════════════════
    //  calculateData — implementa las fórmulas
    // ═══════════════════════════════════════════
    function calculateData(params) {
        const {
            tipoMaterial,
            materialAnchoCM, materialLargoCM,
            anchoPiezaMM, altoPiezaMM,
            gapH_MM, gapV_MM,
            totalUds
        } = params;

        // Convertir material cm → mm
        const matAnchoMM = materialAnchoCM * 10;
        const matLargoMM = materialLargoCM * 10;

        const anchoUsado = anchoPiezaMM + gapH_MM;   // mm por pieza+gap horizontal
        const altoUsado  = altoPiezaMM  + gapV_MM;   // mm por pieza+gap vertical

        let r = {
            piezasPorFila: 0,
            copiasPorRollo: 0,
            rollos: 0,
            sobrante: 0,        // cm
            copiasExtra: 0,
            materialSobrante: 0,// cm
            panelesPorCopia: 0,
            udsPorUnidad: 0,
            consumoTotal: '',
            distribucion: '',
            valConsumo: 0,
            error: null
        };

        if (tipoMaterial === 'ROLLO') {
            // ── piezasPorFila = floor(anchoPanel / anchoUsado) ──
            const piezasPorFila = Math.floor(matAnchoMM / anchoUsado);
            if (piezasPorFila <= 0) { r.error = 'Pieza excede ancho del material'; return r; }

            // ── copiasPorRollo = floor(largoMaterial / altoUsado) × piezasPorFila ──
            const filasPorRollo  = Math.floor(matLargoMM / altoUsado);
            const copiasPorRollo = filasPorRollo * piezasPorFila;

            // ── largo total necesario ──
            const filasNecesarias = Math.ceil(totalUds / piezasPorFila);
            const largoTotalMM   = filasNecesarias * altoUsado;
            const largoTotalCM   = largoTotalMM / 10;

            // ── rollos = floor(largoTotal / largoMaterial) ──
            const rollos = Math.floor(largoTotalCM / materialLargoCM);

            // ── sobrante = largoTotal % largoMaterial  (cm) ──
            const sobranteCM = largoTotalCM % materialLargoCM;

            // ── copiasExtra = floor(sobrante / altoUsado) × piezasPorFila ──
            const sobranteMM  = sobranteCM * 10;
            const copiasExtra = Math.floor(sobranteMM / altoUsado) * piezasPorFila;

            // ── materialSobrante (cm) = lo que queda después de las copiasExtra ──
            const materialSobranteCM = (sobranteMM % altoUsado) / 10;

            // ── panelesPorCopia = ceil(anchoUsado / anchoPanel) ──
            const panelesPorCopia = Math.ceil(anchoUsado / matAnchoMM);

            // Consumo total en metros
            const metrosTotales = largoTotalCM / 100;

            r.piezasPorFila    = piezasPorFila;
            r.copiasPorRollo   = copiasPorRollo;
            r.rollos           = rollos;
            r.sobrante         = sobranteCM;
            r.copiasExtra      = copiasExtra;
            r.materialSobrante = materialSobranteCM;
            r.panelesPorCopia  = panelesPorCopia;
            r.udsPorUnidad     = copiasPorRollo;
            r.valConsumo       = metrosTotales;
            r.consumoTotal     = metrosTotales.toFixed(2) + ' m';

            if (rollos > 0) {
                r.distribucion = `${rollos} rollo(s) + ${sobranteCM.toFixed(1)} cm`;
            } else {
                r.distribucion = `${sobranteCM.toFixed(1)} cm de rollo`;
            }

        } else if (tipoMaterial === 'PLANCHA') {
            const panelesAncho   = Math.floor(matAnchoMM / anchoUsado);
            const panelesAlto    = Math.floor(matLargoMM / altoUsado);
            const udsPorPlancha  = panelesAncho * panelesAlto;

            if (udsPorPlancha <= 0) { r.error = 'Pieza excede tamaño de plancha'; return r; }

            const planchasExactas = totalUds / udsPorPlancha;
            const planchas        = Math.ceil(planchasExactas);
            const panelesPorCopia = Math.ceil(anchoUsado / matAnchoMM);

            r.piezasPorFila    = panelesAncho;
            r.copiasPorRollo   = 0;          // no aplica
            r.rollos           = 0;           // no aplica
            r.sobrante         = 0;
            r.copiasExtra      = 0;
            r.materialSobrante = 0;
            r.panelesPorCopia  = panelesPorCopia;
            r.udsPorUnidad     = udsPorPlancha;
            r.valConsumo       = planchas;
            r.consumoTotal     = planchas + ' plancha(s)';
            r.distribucion     = `${planchas} plancha(s) — ${udsPorPlancha} uds/plancha`;
        }

        return r;
    }

    // ═══════════════════════════════════════════
    //  runCalculations — orquestador principal
    // ═══════════════════════════════════════════
    function runCalculations() {
        const sel = document.getElementById('field_material_id');
        const opt = sel.options[sel.selectedIndex];
        if (!opt || sel.value === '') return;

        const base = {
            tipoMaterial:   opt.dataset.tipo,
            materialAnchoCM: parseFloat(opt.dataset.ancho),
            materialLargoCM: parseFloat(opt.dataset.largo),
            anchoPiezaMM:   parseFloat(document.getElementById('field_ancho_panel').value)  || 0,
            altoPiezaMM:    parseFloat(document.getElementById('field_alto_panel').value)   || 0,
            gapH_MM:        parseFloat(document.getElementById('field_separacion_h').value) || 0,
            gapV_MM:        parseFloat(document.getElementById('field_separacion_v').value) || 0,
            totalUds:       parseInt(document.getElementById('field_cantidad').value)       || 0
        };

        if (base.anchoPiezaMM <= 0 || base.altoPiezaMM <= 0 || base.totalUds <= 0) return;

        // ── Orientación 1 (original) ──
        const r1 = calculateData(base);

        if (r1.error) {
            showError(r1.error);
            document.getElementById('resultsRotated').style.display = 'none';
            document.getElementById('mejorOpcionMsg').style.display = 'none';
            return;
        }

        // Mostrar desglose de fórmulas
        fillResults(r1, '');

        // Guardar en campos ocultos para enviar al servidor
        document.getElementById('field_total_metros').value       = (base.tipoMaterial === 'ROLLO')   ? r1.valConsumo : 0;
        document.getElementById('field_total_planchas').value     = (base.tipoMaterial === 'PLANCHA') ? r1.valConsumo : 0;
        document.getElementById('field_distribucion_texto').value = r1.distribucion;
        document.getElementById('field_unidades_por_rollo').value = r1.udsPorUnidad;

        // Etiquetas dinámicas según tipo
        const lbl = document.getElementById('res_label_unidad');
        const lblC = document.getElementById('res_label_consumo');
        if (lbl)  lbl.textContent  = base.tipoMaterial === 'ROLLO' ? 'uds por rollo' : 'uds por plancha';
        if (lblC) lblC.textContent = base.tipoMaterial === 'ROLLO' ? 'Metros lineales' : 'Planchas';

        const panelTxt = document.getElementById('res_paneles_copia');
        if (panelTxt) panelTxt.textContent = `Paneles/copia: ${r1.panelesPorCopia}`;

        // Ocultar/mostrar tarjetas de rollo según tipo
        const fd = document.getElementById('formulaDetails');
        if (fd) fd.style.display = (base.tipoMaterial === 'ROLLO') ? '' : 'none';

        // ── Orientación 2 (rotada) ──
        if (checkRotar && checkRotar.checked) {
            const rotP = { ...base, anchoPiezaMM: base.altoPiezaMM, altoPiezaMM: base.anchoPiezaMM };
            const r2 = calculateData(rotP);

            if (!r2.error) {
                document.getElementById('resultsRotated').style.display = 'block';
                fillResults(r2, '_rot');

                // Comparar eficiencia
                const msg = document.getElementById('mejorOpcionMsg');
                if (r2.valConsumo < r1.valConsumo - 0.01) {
                    msg.style.display = 'block';
                    msg.innerHTML = `<i class="bi bi-lightning-fill"></i> ¡La <b>Orientación Rotada</b> ahorraría ${(r1.valConsumo - r2.valConsumo).toFixed(2)} ${base.tipoMaterial === 'ROLLO' ? 'm' : 'planchas'}!`;
                    msg.className = 'alert alert-warning mt-2 py-1 small text-center';
                } else if (r1.valConsumo < r2.valConsumo - 0.01) {
                    msg.style.display = 'block';
                    msg.innerHTML = `<i class="bi bi-star-fill"></i> La <b>Orientación Actual</b> es la más eficiente.`;
                    msg.className = 'alert alert-success mt-2 py-1 small text-center';
                } else {
                    msg.style.display = 'block';
                    msg.innerHTML = `<i class="bi bi-info-circle"></i> Ambas orientaciones consumen lo mismo.`;
                    msg.className = 'alert alert-info mt-2 py-1 small text-center';
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

    // ═══════════════════════════════════════════
    //  Helpers de UI
    // ═══════════════════════════════════════════
    function fillResults(r, suffix) {
        const s = suffix;  // '' ó '_rot'

        // Tarjetas de fórmulas
        setTxt('res_piezas_fila'   + s, r.piezasPorFila);
        setTxt('res_copias_rollo'  + s, r.copiasPorRollo);
        setTxt('res_rollos'        + s, r.rollos);
        setTxt('res_sobrante'      + s, r.sobrante.toFixed(1) + ' cm');
        setTxt('res_copias_extra'  + s, r.copiasExtra);
        setTxt('res_mat_sobrante'  + s, r.materialSobrante.toFixed(1) + ' cm');

        // Totales principales
        setTxt('res_unidades_unidad' + s, r.udsPorUnidad);
        setTxt('res_consumo_total'   + s, r.consumoTotal);
        if (!suffix) setTxt('res_distribucion', r.distribucion);
    }

    function setTxt(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    function showError(msg) {
        setTxt('res_unidades_unidad', 'ERR');
        setTxt('res_consumo_total',   msg);
        setTxt('res_distribucion',    '--');
        // Limpiar tarjetas
        ['res_piezas_fila','res_copias_rollo','res_rollos','res_sobrante','res_copias_extra','res_mat_sobrante'].forEach(id => setTxt(id, '--'));
    }

    function resetUI() {
        ['res_piezas_fila','res_copias_rollo','res_rollos','res_sobrante','res_copias_extra','res_mat_sobrante',
         'res_unidades_unidad','res_consumo_total','res_distribucion'].forEach(id => setTxt(id, '--'));
        const p = document.getElementById('res_paneles_copia');
        if (p) p.textContent = '';
    }
});
