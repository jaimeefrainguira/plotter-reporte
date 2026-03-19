/**
 * Réplica exacta de calcular.html
 * integrada en el modal "Nuevo Item de Trabajo" de detalle.php
 */

document.addEventListener('DOMContentLoaded', () => {

    // mostrar panelado config
    document.getElementById('usarPanelado').onchange = function () {
        document.getElementById('panelConfig').style.display = this.checked ? 'block' : 'none';
        calcular();
    };

    // mostrar/ocultar sintra
    document.getElementById('usarSintra').onchange = function () {
        document.getElementById('resultadoSintra').style.display = this.checked ? 'block' : 'none';
        calcular();
    };

    // Auto-cálculo en cualquier cambio
    document.querySelectorAll('.calc-trigger').forEach(el => {
        el.addEventListener('input', calcular);
        el.addEventListener('change', calcular);
    });

    // Trigger inicial al abrir el modal (Bootstrap event)
    const modalEl = document.getElementById('modalTrabajo');
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', calcular);
    }

    // Editar trabajo existente
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
            
            // Nuevos campos
            document.getElementById('field_orientacion').value = d.orientacion || 'auto';
            document.getElementById('usarPanelado').checked = parseInt(d.usar_panelado) === 1;
            document.getElementById('panelConfig').style.display = document.getElementById('usarPanelado').checked ? 'block' : 'none';
            document.getElementById('field_panel_ancho').value = d.panel_ancho || 120;
            document.getElementById('field_panel_gap').value   = d.panel_gap || 2;
            document.getElementById('usarSintra').checked   = parseInt(d.usar_sintra) === 1;
            document.getElementById('resultadoSintra').style.display = document.getElementById('usarSintra').checked ? 'block' : 'none';

            calcular();
        });
    });

    // Nuevo trabajo
    document.getElementById('btnNuevoTrabajo').addEventListener('click', () => {
        document.getElementById('modalTitle').textContent = 'Nuevo Item de Trabajo';
        document.getElementById('formTrabajo').reset();
        document.getElementById('field_trabajo_id').value = '';
        document.getElementById('resultado').innerHTML = '';
        document.getElementById('resultadoSintra').style.display = 'none';
        document.getElementById('panelConfig').style.display = 'none';
        document.getElementById('usarPanelado').checked = false;
        document.getElementById('usarSintra').checked = false;
        let pv = document.getElementById('preview');
        pv.innerHTML = '';
        pv.style.display = 'none';
    });

    function calcular() {
        const resEl = document.getElementById('resultado');
        if (!resEl) return;

        let ancho  = parseFloat(document.getElementById('field_ancho_panel').value) || 0;
        let alto   = parseFloat(document.getElementById('field_alto_panel').value) || 0;
        let copias = parseInt(document.getElementById('field_cantidad').value) || 0;

        // Leer material del select
        const matSel = document.getElementById('field_material_id');
        const matOpt = matSel.options[matSel.selectedIndex];
        
        if (!matOpt || matSel.value === '') {
            resEl.innerHTML = '<span class="text-muted">Selecciona un material para calcular...</span>';
            return;
        }

        let anchoMat = parseFloat(matOpt.dataset.ancho) || 0;
        let largoMat = parseFloat(matOpt.dataset.largo) || 0;

        if (!ancho || !alto || !copias || !anchoMat) {
            resEl.innerHTML = '<span class="text-muted">Completa los datos (Ancho, Alto, Copias) para calcular...</span>';
            return;
        }

        let orient     = document.getElementById('field_orientacion').value;
        let usarPanel  = document.getElementById('usarPanelado').checked;
        let usarSintra = document.getElementById('usarSintra').checked;

        let preview = document.getElementById('preview');
        preview.innerHTML = '';
        preview.style.display = 'none';

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
            let totalRotado = Math.floor(anchoMat / alto) * Math.floor(largoMat / ancho);

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

        // ===============================
        // PANELADO
        // ===============================
        if (usarPanel) {

            preview.style.display = 'block';

            let panelAncho = parseFloat(document.getElementById('field_panel_ancho').value) || 120;
            let gap = parseFloat(document.getElementById('field_panel_gap').value) || 0;

            let paneles = Math.ceil(w / panelAncho);
            let altoCopia = paneles * h;
            let largoTotal = altoCopia * copias;

            rollos = Math.floor(largoTotal / largoMat);
            sobrante = largoTotal % largoMat;

            copiasPorRollo = Math.floor(largoMat / (altoCopia || 1));
            copiasExtra = Math.floor(sobrante / (altoCopia || 1));

            if (rollos === 0) {
                textoMaterial = `${sobrante.toFixed(2)} cm (${copiasExtra} copias)`;
            } else {
                textoMaterial = `${rollos} rollo(s) + ${sobrante.toFixed(2)} cm
            (${copiasPorRollo} copias/rollo + ${copiasExtra} copias adicionales)`;
            }

            resEl.innerHTML = `
                <b>Modo:</b> Panelado<br>
                <b>Orientación:</b> ${modoFinal}<br>
                Paneles por copia: ${paneles}<br><br>
                <b>Material:</b><br>${textoMaterial}
            `;

            let anchoTotal = (paneles * panelAncho) + ((paneles - 1) * gap);

            preview.style.width = (anchoTotal * escala) + 'px';
            preview.style.height = (h * escala) + 'px';

            for (let i = 0; i < paneles; i++) {
                let div = document.createElement('div');
                div.className = 'panel';
                div.style.position = 'absolute';
                div.style.border = '2px solid red';
                div.style.background = 'rgba(255,0,0,0.1)';
                div.style.width = (panelAncho * escala) + 'px';
                div.style.height = (h * escala) + 'px';
                div.style.left = ((i * (panelAncho + gap)) * escala) + 'px';
                preview.appendChild(div);
            }

        } else {

            // ===============================
            // SIN PANELADO
            // ===============================
            let piezasPorFila = Math.floor(anchoMat / w);

            if (piezasPorFila === 0) {
                resEl.innerHTML = `
                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="bi bi-exclamation-triangle"></i> El material es más angosto (${anchoMat}cm) que el diseño (${w}cm). 
                        <b>Activa el Panelado</b> o rota la pieza.
                    </div>
                `;
                return;
            }

            let filas = Math.ceil(copias / piezasPorFila);
            let largoTotal = filas * h;

            rollos = Math.floor(largoTotal / largoMat);
            sobrante = largoTotal % largoMat;

            copiasPorRollo = Math.floor(largoMat / h) * piezasPorFila;
            copiasExtra = Math.floor(sobrante / h) * piezasPorFila;

            if (rollos === 0) {
                textoMaterial = `${sobrante.toFixed(2)} cm (${copiasExtra} copias)`;
            } else {
                textoMaterial = `${rollos} rollo(s) + ${sobrante.toFixed(2)} cm
            (${copiasPorRollo} copias/rollo + ${copiasExtra} copias adicionales)`;
            }

            resEl.innerHTML = `
                <b>Modo:</b> Sin panelado<br>
                <b>Orientación:</b> ${modoFinal}<br>
                <b>Material:</b><br>${textoMaterial}
            `;
        }

        // ===============================
        // 🧱 SINTRA
        // ===============================
        if (usarSintra) {
            let anchoPl = 122;
            let altoPl = 244;

            let n1 = Math.floor(anchoPl / w) * Math.floor(altoPl / h);
            let n2 = Math.floor(anchoPl / h) * Math.floor(altoPl / w);

            let mejor = Math.max(n1, n2) || 0;
            let orientSintra = (n2 > n1) ? 'Rotado' : 'Normal';
            let planchas = mejor > 0 ? Math.ceil(copias / mejor) : 0;

            document.getElementById('sintraTexto').innerHTML = `
                Piezas por plancha: ${mejor}<br>
                Planchas necesarias: ${planchas}<br>
                Orientación: ${orientSintra}
            `;
        }

        // ─── Hidden fields para guardar en BD ───
        let metrosTotales = (rollos * largoMat + sobrante) / 100;
        document.getElementById('field_total_metros').value = metrosTotales.toFixed(4);
        
        let planchasTotales = 0;
        if (usarSintra) {
            let n1 = Math.floor(122 / w) * Math.floor(244 / h);
            let n2 = Math.floor(122 / h) * Math.floor(244 / w);
            let mejor = Math.max(n1, n2) || 0;
            planchasTotales = mejor > 0 ? Math.ceil(copias / mejor) : 0;
        }
        document.getElementById('field_total_planchas').value = planchasTotales;

        document.getElementById('field_distribucion_texto').value = textoMaterial.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
        document.getElementById('field_unidades_por_rollo').value = copiasPorRollo;
    }
});
