<?php
/**
 * setup_materiales.php - v2
 * Crea tabla, corrige columnas viejas y actualiza registros con dimensiones en 0.
 * ELIMINAR del hosting tras ejecutar.
 */
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

$log = [];

try {
    $db = (new Database())->getConnection();
    $dbName = $db->query('SELECT DATABASE()')->fetchColumn();
    $log[] = ['ok', "Conectado a: <code>{$dbName}</code>"];

    /* ── 1. Crear tabla si no existe ─────────────────────────────────── */
    $db->exec("
        CREATE TABLE IF NOT EXISTS materiales (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            nombre         VARCHAR(120)  NOT NULL,
            tipo           VARCHAR(80)   NOT NULL DEFAULT 'lona',
            ancho_cm       DECIMAL(8,2)  NOT NULL DEFAULT 0,
            largo_rollo_m  DECIMAL(8,2)  NOT NULL DEFAULT 50.00,
            precio_rollo   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            stock_rollos   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            stock_minimo   DECIMAL(10,2) NOT NULL DEFAULT 1.00,
            activo         TINYINT(1)    NOT NULL DEFAULT 1,
            notas          TEXT          NULL,
            creado_en      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");
    $log[] = ['ok', 'Tabla <code>materiales</code> verificada/creada.'];

    /* ── 2. Detectar columnas actuales ────────────────────────────────── */
    $cols = $db->query("
        SELECT COLUMN_NAME FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'materiales'
    ")->fetchAll(PDO::FETCH_COLUMN);

    $log[] = ['info', 'Columnas encontradas: <code>' . implode('</code>, <code>', $cols) . '</code>'];

    /* ── 3. Renombrar columnas viejas si existen ──────────────────────── */
    if (in_array('medida_ancho', $cols) && !in_array('ancho_cm', $cols)) {
        $db->exec("ALTER TABLE materiales CHANGE medida_ancho ancho_cm DECIMAL(8,2) NOT NULL DEFAULT 0");
        $log[] = ['warn', 'Renombrada columna <code>medida_ancho</code> → <code>ancho_cm</code>'];
    }
    if (in_array('medida_largo', $cols) && !in_array('largo_rollo_m', $cols)) {
        $db->exec("ALTER TABLE materiales CHANGE medida_largo largo_rollo_m DECIMAL(8,2) NOT NULL DEFAULT 50");
        $log[] = ['warn', 'Renombrada columna <code>medida_largo</code> → <code>largo_rollo_m</code>'];
    }
    // Añadir columna si faltara por completo
    if (!in_array('ancho_cm', $cols) && !in_array('medida_ancho', $cols)) {
        $db->exec("ALTER TABLE materiales ADD COLUMN ancho_cm DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER tipo");
        $log[] = ['warn', 'Columna <code>ancho_cm</code> añadida (no existía).'];
    }
    if (!in_array('largo_rollo_m', $cols) && !in_array('medida_largo', $cols)) {
        $db->exec("ALTER TABLE materiales ADD COLUMN largo_rollo_m DECIMAL(8,2) NOT NULL DEFAULT 50 AFTER ancho_cm");
        $log[] = ['warn', 'Columna <code>largo_rollo_m</code> añadida (no existía).'];
    }

    /* ── 4. Leer todos los registros actuales ─────────────────────────── */
    $registros = $db->query("SELECT * FROM materiales ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $log[] = ['info', count($registros) . ' registros encontrados en la tabla.'];

    /* ── 5. Actualizar dimensiones en 0 según nombre ──────────────────── */
    // Mapa nombre_parcial → [ancho_cm, largo_rollo_m]
    $mapaAncho = [
        '150'         => [150, 50],
        '160'         => [160, 50],
        '200'         => [200, 50],
        '250'         => [250, 50],
        '320'         => [320, 50],
        '100'         => [100, 50],
        '122'         => [122, 50],
        '137'         => [137, 50],
        'translucida' => [320, 50],
        'fotogr'      => [122, 30],
        'bond'        => [100, 50],
        'adhesivo'    => [122, 50],
        'lona'        => [150, 50], // fallback lona
    ];

    $actualizados = 0;
    $stmt = $db->prepare("UPDATE materiales SET ancho_cm = ?, largo_rollo_m = ? WHERE id = ?");

    foreach ($registros as $r) {
        // Solo corregir si tienen 0 en alguna dimensión
        if ((float)$r['ancho_cm'] > 0 && (float)$r['largo_rollo_m'] > 0) continue;

        $nombreLower = strtolower($r['nombre'] . ' ' . $r['tipo']);
        $ancho = 0;
        $largo = 50;

        // Buscar coincidencia en el mapa
        foreach ($mapaAncho as $clave => [$a, $l]) {
            if (str_contains($nombreLower, strtolower($clave))) {
                $ancho = $a;
                $largo = $l;
                break;
            }
        }

        if ($ancho > 0) {
            $stmt->execute([$ancho, $largo, $r['id']]);
            $actualizados++;
            $log[] = ['fix', "ID #{$r['id']} <strong>" . htmlspecialchars($r['nombre']) . "</strong>: asignado {$ancho}cm × {$largo}m"];
        } else {
            $log[] = ['warn', "ID #{$r['id']} <strong>" . htmlspecialchars($r['nombre']) . "</strong>: no se pudo detectar ancho automáticamente — actualiza manualmente en Materia Prima."];
        }
    }

    if ($actualizados === 0) {
        $log[] = ['info', 'Ningún registro tenía dimensiones en 0, o no fue posible detectarlas automáticamente.'];
    } else {
        $log[] = ['ok', "{$actualizados} registros actualizados con dimensiones correctas."];
    }

    /* ── 6. Resultado final ───────────────────────────────────────────── */
    $materiales = $db->query("SELECT id, nombre, tipo, ancho_cm, largo_rollo_m, activo FROM materiales ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $log[] = ['err', 'ERROR FATAL: ' . $e->getMessage()];
    $materiales = [];
}

$hayError = !empty(array_filter($log, fn($l) => $l[0] === 'err'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Setup Materiales v2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>body{background:#f0f4f8;}</style>
</head>
<body class="p-4">
<div class="container" style="max-width:860px">

<h3 class="mb-4">
    <i class="bi bi-database-gear me-2 text-primary"></i>
    Setup Materiales — Corrección de Dimensiones
</h3>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header <?= $hayError ? 'bg-danger' : 'bg-success' ?> text-white">
        <i class="bi bi-<?= $hayError ? 'x-circle' : 'check-circle' ?>"></i>
        <?= $hayError ? 'Proceso con errores' : 'Proceso completado' ?>
    </div>
    <div class="card-body p-3">
        <?php
        $iconMap = [
            'ok'   => 'check-circle-fill text-success',
            'fix'  => 'wrench-adjustable-circle-fill text-primary',
            'warn' => 'exclamation-triangle-fill text-warning',
            'info' => 'info-circle-fill text-info',
            'err'  => 'x-circle-fill text-danger',
        ];
        ?>
        <ul class="list-unstyled mb-0 small">
        <?php foreach ($log as [$tipo, $msg]): ?>
            <li class="mb-1">
                <i class="bi bi-<?= $iconMap[$tipo] ?? 'circle' ?>"></i>
                <?= $msg ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php if (!empty($materiales)): ?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white">
        <i class="bi bi-table me-2"></i>Estado final de materiales (<?= count($materiales) ?> registros)
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-primary">
                <tr>
                    <th>id</th><th>nombre</th><th>tipo</th>
                    <th>ancho_cm</th><th>largo_rollo_m</th>
                    <th>data-largo JS</th><th>activo</th><th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($materiales as $m):
                $ok = (float)$m['ancho_cm'] > 0 && (float)$m['largo_rollo_m'] > 0;
            ?>
            <tr class="<?= $ok ? '' : 'table-danger' ?>">
                <td><?= $m['id'] ?></td>
                <td><strong><?= htmlspecialchars($m['nombre']) ?></strong></td>
                <td><?= htmlspecialchars($m['tipo']) ?></td>
                <td class="<?= (float)$m['ancho_cm'] > 0 ? 'text-success fw-bold' : 'text-danger fw-bold' ?>">
                    <?= $m['ancho_cm'] ?> cm
                </td>
                <td><?= $m['largo_rollo_m'] ?> m</td>
                <td class="text-info fw-bold"><?= (float)$m['largo_rollo_m'] * 100 ?> cm</td>
                <td><?= $m['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                <td><?= $ok ? '✅' : '⚠️ Editar en Materia Prima' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!$hayError): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <strong>¡Elimina este archivo del hosting!</strong> (<code>diagnostico_materiales.php</code>)
</div>
<div class="d-flex gap-2 flex-wrap">
    <a href="index.php?action=campana_detail&id=1" class="btn btn-primary">
        <i class="bi bi-arrow-left"></i> Ir a Campaña y probar modal
    </a>
    <a href="index.php?action=materiales_list" class="btn btn-outline-secondary">
        <i class="bi bi-boxes"></i> Ver / editar Materia Prima
    </a>
</div>
<?php endif; ?>

</div>
</body>
</html>
