/**
 * MÓDULO DE CÁLCULOS PARA CAMPAÑAS
 * El selector de material lee ancho_cm y largo_rollo_m de la BD.
 * data-ancho = ancho en cm | data-largo = largo del rollo en cm (m * 100)
 * Réplica exacta de calcular.html
 * Integrada en el modal "Nuevo Item de Trabajo" de detalle.php
 * Versión 2.1 (Cache Busted)
 */

console.log('campanas_calculos.js cargado correctamente.');

document.addEventListener('DOMContentLoaded', () => {

    // ===== PRIORITY SELECT COLOR =====
    const PRIORITY_COLORS = { 1: '#22c55e', 2: '#eab308', 3: '#f97316', 4: '#ef4444' };

    function applyPriority(val) {
        const sel = document.getElementById('field_prioridad');
        if (!sel) return;
        sel.value = val;
        sel.style.borderLeftColor = PRIORITY_COLORS[val] || '#6c757d';
    }

    const prioSel = document.getElementById('field_prioridad');
    if (prioSel) {
        prioSel.addEventListener('change', () => applyPriority(prioSel.value));
        applyPriority(prioSel.value);
    }
    // =================================

    // mostrar panelado config
    const usarPanelado = document.getElementById('usarPanelado');
    const panelConfig = document.getElementById('panelConfig');
    if (usarPanelado && panelConfig) {
        usarPanelado.onchange = function () {
            panelConfig.style.display = this.checked ? 'block' : 'none';
        };
    }

    // mostrar/ocultar sintra
    const usarSintra = document.getElementById('usarSintra');
    const resultadoSintra = document.getElementById('resultadoSintra');
    if (usarSintra && resultadoSintra) {
        usarSintra.onchange = function () {
            resultadoSintra.style.display = this.checked ? 'block' : 'none';
        };
    }

    // Botón Calcular
    const btnCalcular = document.getElementById('btnCalcular');
    if (btnCalcular) {
        btnCalcular.onclick = () => {
            console.log('Botón Calcular pulsado.');
            calcular();
        };
    }

    // Auto-cálculo opcional en cambios
    document.querySelectorAll('.calc-trigger').forEach(el => {
        el.addEventListener('change', () => {
            console.log('Cambio detectado en:', el.id);
            calcular();
        });
        if (el.type === 'number') {
            el.addEventListener('input', calcular);
        }
    });

    // ── Info de material seleccionado ──────────────────────────────
    const matSelect = document.getElementById('field_material_id');
    const matInfo   = document.createElement('small');
    matInfo.id = 'matInfoBadge';
    matInfo.className = 'text-muted ms-2';
    if (matSelect) {
        matSelect.parentNode.insertBefore(matInfo, matSelect.nextSibling);
        const actualizarInfoMat = () => {
            const opt = matSelect.options[matSelect.selectedIndex];
            if (opt && matSelect.value !== '') {
                const anchoMat = parseFloat(opt.dataset.ancho) || 0;
                const largoMat = parseFloat(opt.dataset.largo) || 0;
                matInfo.innerHTML = `<span class="badge bg-info text-dark"><i class="bi bi-rulers"></i> ${anchoMat} cm × ${largoMat / 100} m por rollo</span>`;
            } else {
                matInfo.textContent = '';
            }
        };
        matSelect.addEventListener('change', actualizarInfoMat);
        actualizarInfoMat(); // ejecutar al cargar por si ya hay un valor seleccionado
    }

    // Evento de apertura del modal
    const modalEl = document.getElementById('modalTrabajo');
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', () => {
            console.log('Modal abierto (shown). Calculando...');
            calcular();
        });
    }

    // Editar trabajo existente
    document.querySelectorAll('.btn-edit-trabajo').forEach(btn => {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.dataset.trabajo);
            console.log('Cargando datos de edición:', d);
            document.getElementById('modalTitle').textContent = 'Editar Item de Trabajo';
            document.getElementById('field_trabajo_id').value = d.id;
            document.getElementById('field_descripcion').value = d.descripcion;
            document.getElementById('field_cantidad').value = d.cantidad;
            if (document.getElementById('field_caras')) document.getElementById('field_caras').value = d.caras || 1;
            document.getElementById('field_ancho_panel').value = d.ancho_panel;
            document.getElementById('field_alto_panel').value = d.alto_panel;
            document.getElementById('field_material_id').value = d.material_id;
            document.getElementById('field_material_id').dispatchEvent(new Event('change'));

            document.getElementById('field_orientacion').value = d.orientacion || 'auto';
            document.getElementById('usarPanelado').checked = parseInt(d.usar_panelado) === 1;
            document.getElementById('panelConfig').style.display = document.getElementById('usarPanelado').checked ? 'block' : 'none';
            document.getElementById('field_panel_ancho').value = d.panel_ancho || 120;
            document.getElementById('field_panel_gap').value = d.panel_gap || 2;
            document.getElementById('usarSintra').checked = parseInt(d.usar_sintra) === 1;
            document.getElementById('resultadoSintra').style.display = document.getElementById('usarSintra').checked ? 'block' : 'none';

            // Cargar prioridad
            applyPriority(d.prioridad || 1);

            // Ejecutar con leve retraso
            setTimeout(calcular, 200);
        });
    });

    // Nuevo trabajo
    const btnNuevoTrabajo = document.getElementById('btnNuevoTrabajo');
    if (btnNuevoTrabajo) {
        btnNuevoTrabajo.addEventListener('click', () => {
            document.getElementById('formTrabajo').reset();
            document.getElementById('field_trabajo_id').value = '';
            document.getElementById('resultado').innerHTML = '';
            document.getElementById('resultadoSintra').style.display = 'none';
            document.getElementById('panelConfig').style.display = 'none';
            applyPriority(1); // reset al crear nuevo
            let pv = document.getElementById('preview');
            if (pv) {
                pv.innerHTML = '';
                pv.style.display = 'none';
            }
        });
    }

    function calcular() {
        console.log('Iniciando función calcular()...');

        const resDiv = document.getElementById('resultado');
        if (!resDiv) {
            console.error('Error: No se encontró el elemento "resultado".');
            return;
        }

        let ancho = parseFloat(document.getElementById('field_ancho_panel').value) || 0;
        let alto = parseFloat(document.getElementById('field_alto_panel').value) || 0;
        let copias = parseInt(document.getElementById('field_cantidad').value) || 1;
        let caras = parseInt(document.getElementById('field_caras')?.value) || 1;

        const matSel = document.getElementById('field_material_id');
        const matOpt = matSel.options[matSel.selectedIndex];

        if (!matOpt || matSel.value === '') {
            resDiv.innerHTML = '<span class="text-muted">Selecciona un material...</span>';
            console.log('Abortando cálculo: No se ha seleccionado material.');
            return;
        }

        let anchoMat = parseFloat(matOpt.dataset.ancho) || 0;
        let largoMat = parseFloat(matOpt.dataset.largo) || 0;

        if (!ancho || !alto || !anchoMat) {
            resDiv.innerHTML = '<span class="text-muted">Completa Ancho y Alto para calcular...</span>';
            return;
        }

        let orient = document.getElementById('field_orientacion').value;
        let usarPanel = document.getElementById('usarPanelado').checked;
        let usarSintra = document.getElementById('usarSintra').checked;

        let preview = document.getElementById('preview');
        if (preview) {
            preview.innerHTML = '';
            preview.style.display = 'none';
        }

        let escala = 0.4;

        // ===============================
        // ORIENTACIÓN
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
            if (preview) preview.style.display = 'block';

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

            resDiv.innerHTML = `
                <b>Modo:</b> Panelado<br>
                <b>Orientación:</b> ${modoFinal}<br>
                Paneles por copia: ${paneles}<br><br>
                <b>Material:</b><br>${textoMaterial}
            `;

            if (preview) {
                let anchoTotalPreview = (paneles * panelAncho) + ((paneles - 1) * gap);
                preview.style.width = (anchoTotalPreview * escala) + 'px';
                preview.style.height = (h * escala) + 'px';

                for (let i = 0; i < paneles; i++) {
                    let div = document.createElement('div');
                    div.className = 'panel';
                    div.style.cssText = `
                        position: absolute;
                        border: 1px solid red;
                        background: rgba(255,0,0,0.1);
                        width: ${panelAncho * escala}px;
                        height: ${h * escala}px;
                        left: ${(i * (panelAncho + gap)) * escala}px;
                    `;
                    preview.appendChild(div);
                }
            }

        } else {
            // ===============================
            // SIN PANELADO (Nesting con caras)
            // ===============================
            let piezasPorFila = Math.floor(anchoMat / w);
            if (piezasPorFila === 0) {
                resDiv.innerHTML = `
                    <div class="alert alert-warning py-2 mb-0 small">
                        Material angosto (${anchoMat}cm) para diseño (${w}cm). Activa Panelado.
                    </div>
                `;
                return;
            }

            let cantidad_tirajes = (copias * caras) / piezasPorFila;
            let tirajes_por_rollo = Math.floor(largoMat / h);
            
            if (tirajes_por_rollo === 0) {
                 tirajes_por_rollo = 1; 
            }

            let num_rollos = Math.floor(cantidad_tirajes / tirajes_por_rollo);
            let tirajes_adicionales = cantidad_tirajes - (num_rollos * tirajes_por_rollo);

            let ancho_tiraje = piezasPorFila * w;
            let alto_tiraje = h;

            let longitud_total = cantidad_tirajes * h;

            // Mantener variables para compatibility 
            rollos = num_rollos;
            sobrante = tirajes_adicionales * h;

            if (rollos === 0) {
                textoMaterial = `${cantidad_tirajes} tirajes. Tamaño del tiraje: ${ancho_tiraje} cm x ${alto_tiraje} cm<br>Longitud total: ${longitud_total} cm`;
            } else {
                textoMaterial = `${cantidad_tirajes} tirajes. Para este trabajo se necesita ${num_rollos} rollos + ${tirajes_adicionales} tirajes adicionales.<br>(Tamaño del tiraje: ${ancho_tiraje} cm x ${alto_tiraje} cm)<br>Longitud total: ${longitud_total} cm`;
            }

            resDiv.innerHTML = `
                <b>Modo:</b> Sin panelado (Nesting)<br>
                <b>Orientación:</b> ${modoFinal}<br>
                <b>Material:</b><br>${textoMaterial}
            `;
        }

        // ===============================
        // 🧱 SINTRA
        // ===============================
        const sRes = document.getElementById('resultadoSintra');
        if (usarSintra && sRes) {
            let n1 = Math.floor(122 / w) * Math.floor(244 / h);
            let n2 = Math.floor(122 / h) * Math.floor(244 / w);
            let mejor = Math.max(n1, n2);
            let planchas = mejor > 0 ? Math.ceil(copias / mejor) : 0;

            sRes.innerHTML = `
                🧱 <b>Sintra 122x244</b><br><br>
                Piezas por plancha: ${mejor}<br>
                Planchas necesarias: ${planchas}<br>
                Orientación: ${n2 > n1 ? 'Rotado' : 'Normal'}
            `;
        }

        // Hidden fields
        const fMetros = document.getElementById('field_total_metros');
        if (fMetros) fMetros.value = ((rollos * largoMat + sobrante) / 100).toFixed(4);

        const fPlanchas = document.getElementById('field_total_planchas');
        if (fPlanchas && usarSintra) {
            let n1 = Math.floor(122 / w) * Math.floor(244 / h);
            let n2 = Math.floor(122 / h) * Math.floor(244 / w);
            fPlanchas.value = Math.max(n1, n2) > 0 ? Math.ceil(copias / Math.max(n1, n2)) : 0;
        }

        const fDist = document.getElementById('field_distribucion_texto');
        if (fDist) fDist.value = textoMaterial.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();

        // Dibujar preview
        if (usarPanel) {
            const panelAncho2 = parseFloat(document.getElementById('field_panel_ancho').value) || 120;
            const gap2 = parseFloat(document.getElementById('field_panel_gap').value) || 0;
            const paneles2 = Math.ceil(w / panelAncho2);
            dibujarPreview({ modo: 'panel', anchoMat, w, h, panelAncho: panelAncho2, gap: gap2, paneles: paneles2, copias, modoFinal });
        } else {
            const piezasPorFila2 = Math.floor(anchoMat / w);
            const cantidad_tirajes2 = piezasPorFila2 > 0 ? Math.ceil((copias * caras) / piezasPorFila2) : 0;
            dibujarPreview({ modo: 'nesting', anchoMat, w, h, piezasPorFila: piezasPorFila2, cantidad_tirajes: cantidad_tirajes2, copias, modoFinal });
        }

        console.log('Cálculo finalizado con éxito.');
    }

    // ── PREVIEW CANVAS ──────────────────────────────────────────────────────
    function dibujarPreview({ modo, anchoMat, w, h, piezasPorFila, cantidad_tirajes,
                               panelAncho, gap, paneles, copias, modoFinal }) {

        const wrap = document.getElementById('previewWrap');
        const canvas = document.getElementById('previewCanvas');
        const leyenda = document.getElementById('previewLeyenda');
        if (!wrap || !canvas) return;

        const ctx = canvas.getContext('2d');

        // Paleta
        const COLOR_ROLLO_BG  = '#f0f4ff';
        const COLOR_ROLLO_BDR = '#94a3b8';
        const COLOR_PIEZA_BG  = '#dbeafe';
        const COLOR_PIEZA_BDR = '#2563eb';
        const COLOR_PANEL_BG  = '#fef3c7';
        const COLOR_PANEL_BDR = '#d97706';
        const COLOR_GAP       = '#e2e8f0';
        const COLOR_TEXTO     = '#1e293b';
        const COLOR_DIM       = '#64748b';

        const MAX_CANVAS_W = 680;
        const PAD = 32;         // padding interior
        const MAX_FILAS = 4;    // cuántas filas mostrar

        if (modo === 'nesting') {
            // ─── MODO NESTING ───────────────────────────────────────────────
            if (!piezasPorFila || piezasPorFila === 0) { wrap.style.display = 'none'; return; }

            const filasMostrar = Math.min(MAX_FILAS, Math.ceil(cantidad_tirajes) || 1);
            const escX = (MAX_CANVAS_W - PAD * 2) / anchoMat;
            const piezaW_px = w * escX;
            const piezaH_px = h * escX;
            const rollW_px  = anchoMat * escX;

            canvas.width  = MAX_CANVAS_W;
            canvas.height = PAD * 2 + filasMostrar * piezaH_px + 28; // +28 para cota ancho

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Fondo rollo
            ctx.fillStyle = COLOR_ROLLO_BG;
            ctx.strokeStyle = COLOR_ROLLO_BDR;
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.roundRect(PAD, PAD, rollW_px, filasMostrar * piezaH_px, 4);
            ctx.fill(); ctx.stroke();

            // Cota ancho material
            const yCotas = PAD - 18;
            ctx.strokeStyle = COLOR_DIM; ctx.lineWidth = 1;
            ctx.setLineDash([4,3]);
            ctx.beginPath(); ctx.moveTo(PAD, yCotas + 6); ctx.lineTo(PAD + rollW_px, yCotas + 6); ctx.stroke();
            ctx.setLineDash([]);
            ctx.fillStyle = '#fff'; ctx.fillRect(PAD + rollW_px/2 - 40, yCotas - 2, 80, 16);
            ctx.fillStyle = COLOR_DIM; ctx.font = 'bold 11px sans-serif'; ctx.textAlign = 'center';
            ctx.fillText(`↔ ${anchoMat} cm (ancho rollo)`, PAD + rollW_px / 2, yCotas + 10);

            // Piezas
            let pieza = 0;
            const totalPiezas = copias;
            for (let fila = 0; fila < filasMostrar; fila++) {
                for (let col = 0; col < piezasPorFila; col++) {
                    const x = PAD + col * piezaW_px;
                    const y = PAD + fila * piezaH_px;
                    const esUltima = pieza >= totalPiezas - 1;
                    const llena = pieza < totalPiezas;

                    // Fondo pieza
                    ctx.fillStyle = llena ? COLOR_PIEZA_BG : '#f1f5f9';
                    ctx.fillRect(x, y, piezaW_px, piezaH_px);

                    // Borde pieza
                    ctx.strokeStyle = llena ? COLOR_PIEZA_BDR : '#cbd5e1';
                    ctx.lineWidth = 1.5;
                    ctx.strokeRect(x, y, piezaW_px, piezaH_px);

                    // Trazos de corte (marcas de esquina)
                    const m = 8;
                    ctx.strokeStyle = '#1e40af'; ctx.lineWidth = 1;
                    [[x,y],[x+piezaW_px,y],[x,y+piezaH_px],[x+piezaW_px,y+piezaH_px]].forEach(([cx,cy]) => {
                        const dx = cx === x ? 1 : -1;
                        const dy = cy === y ? 1 : -1;
                        ctx.beginPath(); ctx.moveTo(cx + dx*m, cy); ctx.lineTo(cx, cy); ctx.lineTo(cx, cy + dy*m); ctx.stroke();
                    });

                    // Etiqueta en centro
                    if (piezaW_px > 28 && piezaH_px > 18 && llena) {
                        ctx.fillStyle = '#1e40af';
                        ctx.font = `bold ${Math.min(10, piezaH_px * 0.22)}px sans-serif`;
                        ctx.textAlign = 'center';
                        ctx.fillText(`${w}×${h}`, x + piezaW_px/2, y + piezaH_px/2 - 4);
                        ctx.font = `${Math.min(9, piezaH_px * 0.18)}px sans-serif`;
                        ctx.fillStyle = '#3b82f6';
                        ctx.fillText(`#${pieza + 1}`, x + piezaW_px/2, y + piezaH_px/2 + 8);
                    }

                    pieza++;
                    if (pieza > Math.ceil(cantidad_tirajes) * piezasPorFila) break;
                }
            }

            // Cota alto pieza (lado derecho)
            const xCota = PAD + rollW_px + 6;
            ctx.strokeStyle = COLOR_DIM; ctx.lineWidth = 1; ctx.setLineDash([3,3]);
            ctx.beginPath(); ctx.moveTo(xCota + 6, PAD); ctx.lineTo(xCota + 6, PAD + piezaH_px); ctx.stroke();
            ctx.setLineDash([]);
            ctx.fillStyle = COLOR_DIM; ctx.font = '10px sans-serif'; ctx.textAlign = 'left';
            ctx.save(); ctx.translate(xCota + 18, PAD + piezaH_px/2);
            ctx.rotate(-Math.PI/2); ctx.fillText(`${h} cm`, 0, 0); ctx.restore();

            // Indicador si hay más filas
            if (cantidad_tirajes > filasMostrar) {
                ctx.fillStyle = 'rgba(248,250,252,0.85)';
                ctx.fillRect(PAD, PAD + (filasMostrar-0.5) * piezaH_px, rollW_px, piezaH_px * 0.5);
                ctx.fillStyle = COLOR_DIM; ctx.font = 'italic 11px sans-serif'; ctx.textAlign = 'center';
                ctx.fillText(`... y ${Math.ceil(cantidad_tirajes) - filasMostrar} fila(s) más`, PAD + rollW_px/2, canvas.height - 8);
            }

            leyenda.innerHTML = `
                <span style="display:inline-block;width:12px;height:12px;background:${COLOR_PIEZA_BG};border:1.5px solid ${COLOR_PIEZA_BDR};border-radius:2px;margin-right:4px;vertical-align:middle;"></span>Pieza ${w}×${h} cm &nbsp;|&nbsp;
                <strong>${piezasPorFila}</strong> pieza(s) por tiraje &nbsp;|&nbsp;
                <strong>${Math.ceil(cantidad_tirajes)}</strong> tiraje(s) total &nbsp;|&nbsp;
                Orientación: <em>${modoFinal}</em>
            `;

        } else if (modo === 'panel') {
            // ─── MODO PANELADO ───────────────────────────────────────────────
            const escX = Math.min(1.8, (MAX_CANVAS_W - PAD * 2) / (paneles * panelAncho + (paneles-1) * gap));
            const totalAnchoReal = paneles * panelAncho + (paneles-1) * gap;
            const panW_px = panelAncho * escX;
            const gapW_px = gap * escX;
            const altH_px = Math.min(200, h * escX);

            canvas.width  = PAD * 2 + totalAnchoReal * escX + 30;
            canvas.height = PAD + altH_px + 50;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Etiqueta ancho total
            const anchoTotal_px = totalAnchoReal * escX;
            ctx.fillStyle = COLOR_DIM; ctx.font = 'bold 11px sans-serif'; ctx.textAlign = 'center';
            ctx.fillText(`↔ ${totalAnchoReal.toFixed(0)} cm total (${paneles} panel${paneles>1?'es':''})`, PAD + anchoTotal_px/2, PAD - 10);

            for (let i = 0; i < paneles; i++) {
                const x = PAD + i * (panW_px + gapW_px);
                const y = PAD;

                // Fondo panel
                ctx.fillStyle = COLOR_PANEL_BG;
                ctx.fillRect(x, y, panW_px, altH_px);

                // Borde
                ctx.strokeStyle = COLOR_PANEL_BDR; ctx.lineWidth = 2;
                ctx.strokeRect(x, y, panW_px, altH_px);

                // Trazos de corte en esquinas
                const m = 10;
                ctx.strokeStyle = '#92400e'; ctx.lineWidth = 1.2;
                [[x,y],[x+panW_px,y],[x,y+altH_px],[x+panW_px,y+altH_px]].forEach(([cx,cy]) => {
                    const dx = cx === x ? 1 : -1;
                    const dy = cy === y ? 1 : -1;
                    ctx.beginPath(); ctx.moveTo(cx + dx*m, cy); ctx.lineTo(cx, cy); ctx.lineTo(cx, cy + dy*m); ctx.stroke();
                });

                // Diagonal de referencia (estilo blueprint)
                ctx.strokeStyle = 'rgba(217,119,6,0.25)'; ctx.lineWidth = 1; ctx.setLineDash([5,4]);
                ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x + panW_px, y + altH_px); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(x + panW_px, y); ctx.lineTo(x, y + altH_px); ctx.stroke();
                ctx.setLineDash([]);

                // Etiqueta panel
                ctx.fillStyle = '#92400e';
                ctx.font = `bold ${Math.min(11, panW_px * 0.12)}px sans-serif`;
                ctx.textAlign = 'center';
                ctx.fillText(`Panel ${i+1}`, x + panW_px/2, y + altH_px/2 - 6);
                ctx.font = `${Math.min(10, panW_px * 0.1)}px sans-serif`;
                ctx.fillStyle = '#b45309';
                ctx.fillText(`${panelAncho}×${h} cm`, x + panW_px/2, y + altH_px/2 + 10);

                // Gap
                if (gap > 0 && i < paneles - 1) {
                    ctx.fillStyle = COLOR_GAP;
                    ctx.fillRect(x + panW_px, y, gapW_px, altH_px);
                    ctx.strokeStyle = '#94a3b8'; ctx.lineWidth = 0.5;
                    ctx.strokeRect(x + panW_px, y, gapW_px, altH_px);
                    if (gapW_px > 12) {
                        ctx.fillStyle = '#94a3b8'; ctx.font = '9px sans-serif'; ctx.textAlign = 'center';
                        ctx.save(); ctx.translate(x + panW_px + gapW_px/2, y + altH_px/2);
                        ctx.rotate(-Math.PI/2); ctx.fillText(`${gap}cm`, 0, 3); ctx.restore();
                    }
                }

                // Cota alto (al lado del último panel)
                if (i === paneles - 1) {
                    const xc = x + panW_px + 8;
                    ctx.strokeStyle = COLOR_DIM; ctx.lineWidth = 1; ctx.setLineDash([3,3]);
                    ctx.beginPath(); ctx.moveTo(xc + 4, y); ctx.lineTo(xc + 4, y + altH_px); ctx.stroke();
                    ctx.setLineDash([]);
                    ctx.fillStyle = COLOR_DIM; ctx.font = '10px sans-serif'; ctx.textAlign = 'left';
                    ctx.save(); ctx.translate(xc + 16, y + altH_px/2);
                    ctx.rotate(-Math.PI/2); ctx.fillText(`${h} cm`, 0, 0); ctx.restore();
                }
            }

            leyenda.innerHTML = `
                <span style="display:inline-block;width:12px;height:12px;background:${COLOR_PANEL_BG};border:1.5px solid ${COLOR_PANEL_BDR};border-radius:2px;margin-right:4px;vertical-align:middle;"></span>
                ${paneles} panel(es) de ${panelAncho}×${h} cm &nbsp;${gap>0?`| Gap: ${gap} cm`:''} &nbsp;|&nbsp;
                Total por copia: ${totalAnchoReal.toFixed(0)}×${h} cm &nbsp;|&nbsp; <em>${modoFinal}</em>
            `;
        }

        wrap.style.display = 'block';
    }
    // ── FIN DEL MODULO ──
});
