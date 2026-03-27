<?php
/* Campos compartidos entre modal de creación y página de edición.
   Variables disponibles:
   - $material  (array|null) – cuando se edita
   - $tipos     (array)      – tipos existentes
*/
$m   = $material ?? [];
$isEdit = !empty($m);
$tiposComunes = ['lona', 'adhesivo', 'vinilo', 'papel', 'tela', 'foamy', 'microporoso', 'otro'];
?>

<div class="row g-3">

    <!-- Nombre -->
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="nombre">
            <i class="bi bi-tag me-1 text-primary"></i>Nombre del material <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="120"
               placeholder="Ej: Lona 150, Adhesivo Blanco…"
               value="<?= htmlspecialchars($m['nombre'] ?? '') ?>">
    </div>

    <!-- Tipo -->
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="tipo">
            <i class="bi bi-collection me-1 text-primary"></i>Tipo / Categoría <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <input type="text" class="form-control" id="tipo" name="tipo" required maxlength="80"
                   placeholder="lona, adhesivo, papel…" list="listaTipos"
                   value="<?= htmlspecialchars($m['tipo'] ?? '') ?>">
            <datalist id="listaTipos">
                <?php foreach (array_unique(array_merge($tiposComunes, $tipos)) as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="form-text">Puedes escribir un tipo nuevo o elegir uno existente.</div>
    </div>

    <!-- Ancho -->
    <div class="col-md-4">
        <label class="form-label fw-semibold" for="ancho_cm">
            <i class="bi bi-arrows-expand me-1 text-info"></i>Ancho del rollo (cm) <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <input type="number" class="form-control" id="ancho_cm" name="ancho_cm"
                   min="1" max="9999" step="0.5" required
                   value="<?= htmlspecialchars($m['ancho_cm'] ?? '122') ?>">
            <span class="input-group-text">cm</span>
        </div>
    </div>

    <!-- Largo rollo -->
    <div class="col-md-4">
        <label class="form-label fw-semibold" for="largo_rollo_m">
            <i class="bi bi-rulers me-1 text-info"></i>Largo del rollo (m) <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <input type="number" class="form-control" id="largo_rollo_m" name="largo_rollo_m"
                   min="1" max="9999" step="0.5" required
                   value="<?= htmlspecialchars($m['largo_rollo_m'] ?? '50') ?>">
            <span class="input-group-text">m</span>
        </div>
    </div>

    <!-- Precio por rollo -->
    <div class="col-md-4">
        <label class="form-label fw-semibold" for="precio_rollo">
            <i class="bi bi-currency-dollar me-1 text-warning"></i>Precio por rollo
        </label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" class="form-control" id="precio_rollo" name="precio_rollo"
                   min="0" step="0.01"
                   value="<?= htmlspecialchars($m['precio_rollo'] ?? '0') ?>">
        </div>
    </div>

    <!-- Stock actual -->
    <div class="col-md-4">
        <label class="form-label fw-semibold" for="stock_rollos">
            <i class="bi bi-layers me-1 text-success"></i>Stock actual (rollos)
        </label>
        <div class="input-group">
            <input type="number" class="form-control" id="stock_rollos" name="stock_rollos"
                   min="0" step="0.5"
                   value="<?= htmlspecialchars($m['stock_rollos'] ?? '0') ?>">
            <span class="input-group-text">rollos</span>
        </div>
    </div>

    <!-- Stock mínimo -->
    <div class="col-md-4">
        <label class="form-label fw-semibold" for="stock_minimo">
            <i class="bi bi-exclamation-triangle me-1 text-warning"></i>Stock mínimo (alerta)
        </label>
        <div class="input-group">
            <input type="number" class="form-control" id="stock_minimo" name="stock_minimo"
                   min="0" step="0.5"
                   value="<?= htmlspecialchars($m['stock_minimo'] ?? '1') ?>">
            <span class="input-group-text">rollos</span>
        </div>
    </div>

    <!-- Activo -->
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch"
                   id="activo" name="activo" value="1"
                   <?= ($m['activo'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="activo">Material activo</label>
        </div>
    </div>

    <!-- Notas -->
    <div class="col-12">
        <label class="form-label fw-semibold" for="notas">
            <i class="bi bi-chat-left-text me-1 text-muted"></i>Notas / Observaciones
        </label>
        <textarea class="form-control" id="notas" name="notas" rows="2" maxlength="1000"
                  placeholder="Información adicional, proveedor, código interno…"><?= htmlspecialchars($m['notas'] ?? '') ?></textarea>
    </div>

</div>
