<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Reporte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="card shadow-sm mx-auto" style="max-width: 720px;">
        <div class="card-body">
            <h4 class="mb-3">Registrar reporte de impresión</h4>
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="index.php?action=store">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="mb-3">
                    <label class="form-label">PLOTTER</label>
                    <select name="plotter" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($plotters as $plotter): ?>
                            <option value="<?= htmlspecialchars($plotter) ?>" <?= (($oldData['plotter'] ?? '') === $plotter) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($plotter) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">OBSERVACIÓN</label>
                    <textarea name="observacion" class="form-control" rows="3" required><?= htmlspecialchars($oldData['observacion'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">DESCRIPCIÓN</label>
                    <input type="text" name="descripcion" class="form-control" value="<?= htmlspecialchars($oldData['descripcion'] ?? '') ?>" required>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">CANTIDAD</label>
                        <input type="number" min="1" name="cantidad" class="form-control" value="<?= htmlspecialchars((string) ($oldData['cantidad'] ?? '')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">% DE IMPRESIÓN</label>
                        <input type="number" min="0" max="100" name="porcentaje_impresion" class="form-control" value="<?= htmlspecialchars((string) ($oldData['porcentaje_impresion'] ?? '')) ?>" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php?action=dashboard" class="btn btn-outline-secondary">Volver</a>
                    <button type="submit" class="btn btn-primary">Guardar reporte</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
