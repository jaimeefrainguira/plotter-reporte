<?php
declare(strict_types=1);

class Trabajo {
    private PDO $db;
    private bool $schemaChecked = false;
    private array $requiredColumns = [
        "caras" => "ALTER TABLE trabajos ADD COLUMN caras INT DEFAULT 1 AFTER cantidad",
        "orientacion" => "ALTER TABLE trabajos ADD COLUMN orientacion VARCHAR(20) DEFAULT 'auto' AFTER separacion_v",
        "usar_panelado" => "ALTER TABLE trabajos ADD COLUMN usar_panelado TINYINT(1) DEFAULT 0 AFTER orientacion",
        "panel_ancho" => "ALTER TABLE trabajos ADD COLUMN panel_ancho DECIMAL(10,2) DEFAULT 0 AFTER usar_panelado",
        "panel_gap" => "ALTER TABLE trabajos ADD COLUMN panel_gap DECIMAL(10,2) DEFAULT 0 AFTER panel_ancho",
        "usar_sintra" => "ALTER TABLE trabajos ADD COLUMN usar_sintra TINYINT(1) DEFAULT 0 AFTER panel_gap",
        "prioridad" => "ALTER TABLE trabajos ADD COLUMN prioridad INT DEFAULT 1 AFTER usar_sintra"
    ];

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    private function ensureSchema(): void {
        if ($this->schemaChecked) return;
        $this->schemaChecked = true;
        foreach ($this->requiredColumns as $col => $sql) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajos' AND COLUMN_NAME = ?");
            $stmt->execute([$col]);
            if ($stmt->fetchColumn() == 0) {
                try {
                    $this->db->exec($sql);
                } catch (Exception $e) {
                    // Ignorar si ya existe o hay error menor
                }
            }
        }
    }

    public function create(array $data): int {
        $this->ensureSchema();
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO trabajos (campana_id, descripcion, cantidad, caras, ancho_panel, alto_panel, material_id, separacion_h, separacion_v, orientacion, usar_panelado, panel_ancho, panel_gap, usar_sintra, prioridad)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['campana_id'],
                $data['descripcion'],
                $data['cantidad'],
                $data['caras'] ?? 1,
                $data['ancho_panel'],
                $data['alto_panel'],
                empty($data['material_id']) ? null : $data['material_id'],
                $data['separacion_h'],
                $data['separacion_v'],
                $data['orientacion'] ?? 'auto',
                $data['usar_panelado'] ?? 0,
                $data['panel_ancho'] ?? 0,
                $data['panel_gap'] ?? 0,
                $data['usar_sintra'] ?? 0,
                $data['prioridad'] ?? 1
            ]);
            $trabajoId = (int)$this->db->lastInsertId();

            if (isset($data['consumo'])) {
                $this->saveConsumo($trabajoId, $data['consumo']);
            }

            $this->db->commit();
            return $trabajoId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): bool {
        $this->ensureSchema();
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE trabajos 
                SET descripcion = ?, cantidad = ?, caras = ?, ancho_panel = ?, alto_panel = ?, material_id = ?, separacion_h = ?, separacion_v = ?,
                    orientacion = ?, usar_panelado = ?, panel_ancho = ?, panel_gap = ?, usar_sintra = ?, prioridad = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['descripcion'],
                $data['cantidad'],
                $data['caras'] ?? 1,
                $data['ancho_panel'],
                $data['alto_panel'],
                empty($data['material_id']) ? null : $data['material_id'],
                $data['separacion_h'],
                $data['separacion_v'],
                $data['orientacion'] ?? 'auto',
                $data['usar_panelado'] ?? 0,
                $data['panel_ancho'] ?? 0,
                $data['panel_gap'] ?? 0,
                $data['usar_sintra'] ?? 0,
                $data['prioridad'] ?? 1,
                $id
            ]);

            if (isset($data['consumo'])) {
                $this->saveConsumo($id, $data['consumo']);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function saveConsumo(int $trabajoId, array $consumo): void {
        $stmtCheck = $this->db->prepare("SELECT id FROM consumos WHERE trabajo_id = ?");
        $stmtCheck->execute([$trabajoId]);
        
        if ($stmtCheck->fetch()) {
            $stmt = $this->db->prepare("
                UPDATE consumos 
                SET total_metros = ?, total_planchas = ?, distribucion_texto = ?, unidades_por_unidad_venta = ?
                WHERE trabajo_id = ?
            ");
            $stmt->execute([
                $consumo['total_metros'] ?? 0,
                $consumo['total_planchas'] ?? 0,
                $consumo['distribucion_texto'] ?? '',
                $consumo['unidades_por_unidad_venta'] ?? 0,
                $trabajoId
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO consumos (trabajo_id, total_metros, total_planchas, distribucion_texto, unidades_por_unidad_venta)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $trabajoId,
                $consumo['total_metros'] ?? 0,
                $consumo['total_planchas'] ?? 0,
                $consumo['distribucion_texto'] ?? '',
                $consumo['unidades_por_unidad_venta'] ?? 0
            ]);
        }
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM trabajos WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
