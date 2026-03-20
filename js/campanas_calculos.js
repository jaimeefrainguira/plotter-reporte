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
            // SIN PANELADO
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

            let filas = Math.ceil(copias / piezasPorFila);
            let largoTotal = filas * h;

            rollos = Math.floor(largoTotal / largoMat);
            sobrante = largoTotal % largoMat;

            copiasPorRollo = Math.floor(largoMat / (h || 1)) * piezasPorFila;
            copiasExtra = Math.floor(sobrante / (h || 1)) * piezasPorFila;

            if (rollos === 0) {
                textoMaterial = `${sobrante.toFixed(2)} cm (${copiasExtra} copias)`;
            } else {
                textoMaterial = `${rollos} rollo(s) + ${sobrante.toFixed(2)} cm 
            (${copiasPorRollo} copias/rollo + ${copiasExtra} copias adicionales)`;
            }

            resDiv.innerHTML = `
                <b>Modo:</b> Sin panelado<br>
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

        console.log('Cálculo finalizado con éxito.');
    }

    // ════════════════════════════════════════════════════════════════════════
    // ── CONFIGURACIÓN IA: Pon tu API Key aquí ────────────────────────────────
    // ════════════════════════════════════════════════════════════════════════
    const GEMINI_API_KEY = "AIzaSyCpvNI9GiPas9p-hKrZaCGipJkR2_YN4hw"; // Configurada por Antigravity

    const dropAreaIA   = document.getElementById('dropAreaIA');
    const fileInputIA  = document.getElementById('fileInputIA');
    const btnProcesar  = document.getElementById('btnProcesarIA');
    const btnConfirmar = document.getElementById('btnConfirmarIA');
    const btnRecargar  = document.getElementById('btnRecargarIA');
    const stepUpload   = document.getElementById('multiIA-step-upload');
    const stepReview   = document.getElementById('multiIA-step-review');
    const tableBodyIA  = document.getElementById('iaTableBody');
    const previewImgIA = document.getElementById('imgPreviewIA');

    let base64ImageIA = ""; // Para guardar la imagen seleccionada

    if (dropAreaIA) {
        dropAreaIA.onclick = () => fileInputIA.click();
        fileInputIA.onchange = (e) => handleFilesIA(e.target.files);
        dropAreaIA.ondragover = (e) => { e.preventDefault(); dropAreaIA.classList.add('bg-primary-subtle'); };
        dropAreaIA.ondragleave = () => { dropAreaIA.classList.remove('bg-primary-subtle'); };
        dropAreaIA.ondrop = (e) => { e.preventDefault(); dropAreaIA.classList.remove('bg-primary-subtle'); handleFilesIA(e.dataTransfer.files); };
    }

    function handleFilesIA(files) {
        if (!files.length) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            base64ImageIA = e.target.result.split(',')[1]; // Extraer solo el base64
            previewImgIA.querySelector('img').src = e.target.result;
            previewImgIA.classList.remove('d-none');
            btnProcesar.disabled = false;
        };
        reader.readAsDataURL(files[0]);
    }

    btnProcesar.onclick = async () => {
        if (GEMINI_API_KEY === "TU_API_KEY_AQUI") {
            alert("Error: Debes configurar tu API KEY de Google Gemini al inicio de js/campanas_calculos.js");
            return;
        }

        const spin = document.getElementById('spinIA');
        const txt  = document.getElementById('txtIA');
        spin.classList.remove('d-none');
        txt.textContent = 'Antigravity está analizando la imagen...';
        btnProcesar.disabled = true;

        const PROMPT = `Analiza la imagen adjunta que contiene una tabla de trabajos/ítems. 
        Debes extraer exclusivamente la información de dos columnas: Descripción (descripcion) y Cantidad (cantidad). 
        Ignora cualquier otra columna. 
        No añadas comentarios, introducciones ni explicaciones.
        Devuelve los resultados únicamente en un formato de arreglo JSON válido:
        [{"descripcion": "Nombre del item", "cantidad": 10}, ...]`;

        try {
            const endpoint = `https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=${GEMINI_API_KEY}`;
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contents: [{
                        parts: [
                            { text: PROMPT },
                            { inline_data: { mime_type: "image/jpeg", data: base64ImageIA } }
                        ]
                    }]
                })
            });

            const result = await response.json();
            
            // Extraer el texto del JSON que devuelve Gemini y limpiarlo
            let rawText = result.candidates[0].content.parts[0].text;
            // Limpiar posibles bloques de código triple backtick
            rawText = rawText.replace(/```json/g, '').replace(/```/g, '').trim();

            const data = JSON.parse(rawText);
            renderReviewIA(data);

        } catch (error) {
            console.error(error);
            alert("Error al procesar con IA: " + error.message);
            resetModalIA();
        } finally {
            spin.classList.add('d-none');
            txt.textContent = 'PROCESAR CON IA';
        }
    };

    function renderReviewIA(items) {
        tableBodyIA.innerHTML = '';
        items.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="text" class="form-control form-control-sm ia-desc" value="${item.descripcion}"></td>
                <td><input type="number" class="form-control form-control-sm ia-cant" value="${item.cantidad}"></td>
                <td><button class="btn btn-sm btn-danger py-0 px-1" title="Eliminar ítem" onclick="this.closest('tr').remove()"><i class="bi bi-x"></i></button></td>
            `;
            tableBodyIA.appendChild(tr);
        });
        stepUpload.classList.add('d-none');
        stepReview.classList.remove('d-none');
        btnConfirmar.classList.remove('d-none');
        btnRecargar.classList.remove('d-none');
        btnProcesar.classList.add('d-none');
    }

    function resetModalIA() {
        stepUpload.classList.remove('d-none');
        stepReview.classList.add('d-none');
        btnConfirmar.classList.add('d-none');
        btnRecargar.classList.add('d-none');
        btnProcesar.classList.remove('d-none');
        btnProcesar.disabled = false;
        previewImgIA.classList.add('d-none');
        base64ImageIA = "";
    }

    btnRecargar.onclick = resetModalIA;

    btnConfirmar.onclick = async () => {
        const items = Array.from(document.querySelectorAll('#iaTableBody tr')).map(tr => ({
            descripcion: tr.querySelector('.ia-desc').value,
            cantidad: parseInt(tr.querySelector('.ia-cant').value) || 1
        }));
        if (!items.length) return;
        btnConfirmar.disabled = true;
        const campanaId = new URLSearchParams(window.location.search).get('id');
        const resp = await fetch('index.php?action=campana_bulk_save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ campana_id: campanaId, items: items })
        });
        const res = await resp.json();
        if (res.ok) location.reload();
        else { alert("Error: " + res.error); btnConfirmar.disabled = false; }
    };
});
