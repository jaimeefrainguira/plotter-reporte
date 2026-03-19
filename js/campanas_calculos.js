/**
 * Lógica de cálculo — replica exacta de calcular.html
 * integrada en el modal "Nuevo Item de Trabajo" de detalle.php
 *
 * Todas las medidas en cm.
 */

document.addEventListener('DOMContentLoaded', () => {

    // ─── Toggles ───
    document.getElementById('usarPanelado').onchange = function () {
        document.getElementById('panelConfig').style.display = this.checked ? 'block' : 'none';
        calcular();
    };
    document.getElementById('usarSintra').onchange = function () {
        document.getElementById('sintraConfig').style.display = this.checked ? 'block' : 'none';
        document.getElementById('resultadoSintra').style.display = this.checked ? 'block' : 'none';
        calcular();
    };

    // ─── Auto-cálculo ───
    document.querySelectorAll('.calc-trigger').forEach(el => {
        el.addEventListener('input', calcular);
        if (el.tagName === 'SELECT') el.addEventListener('change', calcular);
    });

    // ─── Editar trabajo existente ───
    document.querySelectorAll('.btn-edit-trabajo').forEach(btn => {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.dataset.trabajo);
            document.getElementById('modalTitle').textContent = 'Editar Item de Trabajo';
            document.getElementById('field_trabajo_id').value  = d.id;
            document.getElementById('field_descripcion').value  = d.descripcion;
            document.getElementById('field_cantidad').value     = d.cantidad;
            document.getElementById('field_ancho_panel').value  = d.ancho_panel;
            document.getElementById('field_alto_panel').value   = d.alto_panel;
            document.getElementById('field_material_id').value  = d.material_id;
            document.getElementById('field_separacion_h').value = d.separacion_h;
            document.getElementById('field_separacion_v').value = d.separacion_v;
            calcular();
        });
    });

    // ─── Nuevo trabajo ───
    document.getElementById('btnNuevoTrabajo').addEventListener('click', () => {
        document.getElementById('modalTitle').textContent = 'Nuevo Item de Trabajo';
        document.getElementById('formTrabajo').reset();
        document.getElementById('field_trabajo_id').value = '';
        document.getElementById('resultado').innerHTML =
            '<span class="text-muted">Ingresa los datos y se calculará automáticamente...</span>';
        resetCards();
        const pv = document.getElementById('preview');
        pv.innerHTML = '';
        pv.style.display = 'none';
        document.getElementById('previewLabel').textContent = '--';
        document.getElementById('panelConfig').style.display = 'none';
        document.getElementById('sintraConfig').style.display = 'none';
        document.getElementById('resultadoSintra').style.display = 'none';
    });

    // ═══════════════════════════════════════
    //  FUNCIÓN PRINCIPAL — calcular()
    // ═══════════════════════════════════════
    function calcular() {
        let ancho  = parseFloat(document.getElementById('field_ancho_panel').value)  || 0;
        let alto   = parseFloat(document.getElementById('field_alto_panel').value)   || 0;
        let copias = parseInt(document.getElementById('field_cantidad').value)        || 0;

        // Leer material del select
        const matSel = document.getElementById('field_material_id');
        const matOpt = matSel.options[matSel.selectedIndex];
        if (!matOpt || matSel.value === '') return;

        let anchoMat = parseFloat(matOpt.dataset.ancho) || 0;
        let largoMat = parseFloat(matOpt.dataset.largo) || 0;

        let orient     = document.getElementById('field_orientacion').value;
        let usarPanel  = document.getElementById('usarPanelado').checked;
        let usarSintra = document.getElementById('usarSintra').checked;

        let preview = document.getElementById('preview');
        preview.innerHTML = '';
        preview.style.display = 'none';

        if (ancho <= 0 || alto <= 0 || copias <= 0 || anchoMat <= 0) return;

        let escala = 0.4;

        // ===============================
        // ORIENTACIÓN VICEVERSA
        // ===============================
        let w, h, modoFinal;

        if (orient === 'vertical') {
            w = alto;
            h = ancho;
            modoFinal = 'Vertical (Rotado)';
        }
        else if (orient === 'horizontal') {
            w = ancho;
            h = alto;
            modoFinal = 'Horizontal (Normal)';
        }
        else {
            let totalNormal = Math.floor(anchoMat / ancho) * Math.floor(largoMat / alto);
            let totalRotado = Math.floor(anchoMat / alto)  * Math.floor(largoMat / ancho);

            if (totalRotado > totalNormal) {
                w = alto;
                h = ancho;
                modoFinal = 'Automático → Rotado';
            } else {
                w = ancho;
                h = alto;
                modoFinal = 'Automático → Normal';
            }
        }

        let rollos = 0, sobrante = 0;
        let copiasPorRollo = 0, copiasExtra = 0;
        let textoMaterial = '';
        let piezasPorFila = 0;
        let panelesPorCopia = 1;

        // ===============================
        // PANELADO
        // ===============================
        if (usarPanel) {

            preview.style.display = 'block';

            let panelAncho = parseFloat(document.getElementById('field_panel_ancho').value) || 0;
            let gap        = parseFloat(document.getElementById('field_panel_gap').value)   || 0;

            if (panelAncho <= 0) return;

            let paneles    = Math.ceil(w / panelAncho);
            let altoCopia  = paneles * h;
            let largoTotal = altoCopia * copias;

            rollos         = Math.floor(largoTotal / largoMat);
            sobrante       = largoTotal % largoMat;

            copiasPorRollo = Math.floor(largoMat / altoCopia);
            copiasExtra    = Math.floor(sobrante / altoCopia);

            piezasPorFila   = paneles;
            panelesPorCopia = paneles;

            if (rollos === 0) {
                textoMaterial = `${sobrante} cm (${copiasExtra} copias)`;
            } else {
                textoMaterial = `${rollos} rollo(s) + ${sobrante} cm
                (${copiasPorRollo} copias/rollo + ${copiasExtra} copias adicionales)`;
            }

            document.getElementById('resultado').innerHTML = `
                <b>Modo:</b> Panelado<br>
                <b>Orientación:</b> ${modoFinal}<br>
                Paneles por copia: ${paneles}<br><br>
                <b>Material:</b><br>${textoMaterial}
            `;

            let anchoTotal = (paneles * panelAncho) + ((paneles - 1) * gap);

            preview.style.width  = (anchoTotal * escala) + 'px';
            preview.style.height = (h * escala) + 'px';

            for (let i = 0; i < paneles; i++) {
                let div = document.createElement('div');
                div.style.cssText = `
                    position: absolute;
                    width: ${panelAncho * escala}px;
                    height: ${h * escala}px;
                    left: ${(i * (panelAncho + gap)) * escala}px;
                    top: 0;
                    border: 2px solid red;
                    background: rgba(255,0,0,0.1);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 10px;
                    color: #c00;
                    font-weight: bold;
                `;
                div.textContent = `P${i + 1}`;
                preview.appendChild(div);
            }
            document.getElementById('previewLabel').textContent =
                `${anchoTotal.toFixed(1)} × ${h.toFixed(1)} cm — ${paneles} panel(es)`;

        } else {

            // ===============================
            // SIN PANELADO
            // ===============================
            piezasPorFila = Math.floor(anchoMat / w);

            if (piezasPorFila <= 0) {
                document.getElementById('resultado').innerHTML =
                    '<span class="text-danger"><b>Error:</b> La pieza excede el ancho del material.</span>';
                resetCards();
                return;
            }

            let filas      = Math.ceil(copias / piezasPorFila);
            let largoTotal = filas * h;

            rollos         = Math.floor(largoTotal / largoMat);
            sobrante       = largoTotal % largoMat;

            copiasPorRollo = Math.floor(largoMat / h) * piezasPorFila;
            copiasExtra    = Math.floor(sobrante / h) * piezasPorFila;

            panelesPorCopia = 1;

            if (rollos === 0) {
                textoMaterial = `${sobrante} cm (${copiasExtra} copias)`;
            } else {
                textoMaterial = `${rollos} rollo(s) + ${sobrante} cm
                (${copiasPorRollo} copias/rollo + ${copiasExtra} copias adicionales)`;
            }

            document.getElementById('resultado').innerHTML = `
                <b>Modo:</b> Sin panelado<br>
                <b>Orientación:</b> ${modoFinal}<br>
                Piezas por fila: ${piezasPorFila}<br><br>
                <b>Material:</b><br>${textoMaterial}
            `;

            // Preview sin panelado — 1 fila de piezas
            preview.style.display = 'block';
            preview.style.width  = (anchoMat * escala) + 'px';
            preview.style.height = (h * escala) + 'px';

            for (let i = 0; i < piezasPorFila; i++) {
                let div = document.createElement('div');
                div.style.cssText = `
                    position: absolute;
                    width: ${w * escala}px;
                    height: ${h * escala}px;
                    left: ${(i * w) * escala}px;
                    top: 0;
                    border: 2px solid red;
                    background: rgba(255,0,0,0.1);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 10px;
                    color: #c00;
                    font-weight: bold;
                `;
                div.textContent = `${i + 1}`;
                preview.appendChild(div);
            }
            document.getElementById('previewLabel').textContent =
                `${anchoMat} × ${h.toFixed(1)} cm — ${piezasPorFila} pieza(s)/fila`;
        }

        // ===============================
        // 🧱 SINTRA
        // ===============================
        if (usarSintra) {
            let anchoPl = parseFloat(document.getElementById('field_sintra_ancho').value) || 122;
            let altoPl  = parseFloat(document.getElementById('field_sintra_largo').value) || 244;

            let n1 = Math.floor(anchoPl / w) * Math.floor(altoPl / h);
            let n2 = Math.floor(anchoPl / h) * Math.floor(altoPl / w);

            let mejor = Math.max(n1, n2);
            let orientSintra = (n2 > n1) ? 'Rotado' : 'Normal';
            let planchas = Math.ceil(copias / mejor);

            document.getElementById('sintraTexto').innerHTML = `
                <br>Piezas por plancha: <b>${mejor}</b><br>
                Planchas necesarias: <b>${planchas}</b><br>
                Orientación: ${orientSintra}
            `;
            document.getElementById('resultadoSintra').style.display = 'block';
        }

        // ─── Tarjetas de fórmulas ───
        setTxt('res_piezas_fila',   piezasPorFila);
        setTxt('res_copias_rollo',  copiasPorRollo);
        setTxt('res_rollos',        rollos);
        setTxt('res_sobrante',      sobrante + ' cm');
        setTxt('res_copias_extra',  copiasExtra);
        setTxt('res_paneles_copia', panelesPorCopia);

        // ─── Hidden fields para guardar en BD ───
        let metrosTotales = (rollos * largoMat + sobrante) / 100;
        document.getElementById('field_total_metros').value       = metrosTotales.toFixed(4);
        document.getElementById('field_total_planchas').value     = 0;
        document.getElementById('field_distribucion_texto').value = textoMaterial.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
        document.getElementById('field_unidades_por_rollo').value = copiasPorRollo;
    }

    // ─── Helpers ───
    function setTxt(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }
    function resetCards() {
        ['res_piezas_fila','res_copias_rollo','res_rollos','res_sobrante','res_copias_extra','res_paneles_copia']
            .forEach(id => setTxt(id, '--'));
    }
});
