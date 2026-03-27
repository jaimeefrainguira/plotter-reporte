<?php
declare(strict_types=1);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
// $material y $tipos son inyectados por el controller
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Material | Plotter Reportes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .edit-card { border: none; border-radius: 16px; box-shadow: 0 6px 28px rgba(0,0,0,.1); }
        .edit-card .card-header {
            background: linear-gradient(135deg, #0a1628, #1a2f5a);
            color: #fff; border-radius: 16px 16px 0 0; padding: 1.2rem 1.5rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-3 py-2" style="background:linear-gradient(135deg,#0a1628,#1a2f5a);">
    <span class="navbar-brand mb-0 fw-bold">
        <i class="bi bi-pencil-square me-2"></i>Editar Material
    </span>
    <a href="index.php?action=materiales_list" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</nav>

<div class="container py-5" style="max-width:820px">

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
            <?= $flash['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card edit-card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-boxes me-2"></i><?= htmlspecialchars($material['nombre']) ?></h5>
            <small class="opacity-75">ID #<?= (int)$material['id'] ?> &mdash; Actualizado: <?= htmlspecialchars($material['actualizado_en']) ?></small>
        </div>
        <div class="card-body p-4">
            <form method="post" action="index.php?action=material_update&id=<?= (int)$material['id'] ?>">
                <?php include __DIR__ . '/form_fields.php'; ?>
                <div class="d-flex gap-2 mt-4 justify-content-end">
                    <a href="index.php?action=materiales_list" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
