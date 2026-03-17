<?php
declare(strict_types=1);

class Trabajo {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function create(array $data): int {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO trabajos (campana_id, descripcion, cantidad, ancho_panel, alto_panel, material_id, separacion_h, separacion_v)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['campana_id'],
                $data['descripcion'],
                $data['cantidad'],
                $data['ancho_panel'],
                $data['alto_panel'],
                $data['material_id'],
                $data['separacion_h'],
                $data['separacion_v']
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
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE trabajos 
                SET descripcion = ?, cantidad = ?, ancho_panel = ?, alto_panel = ?, material_id = ?, separacion_h = ?, separacion_v = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['descripcion'],
                $data['cantidad'],
                $data['ancho_panel'],
                $data['alto_panel'],
                $data['material_id'],
                $data['separacion_h'],
                $data['separacion_v'],
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
        // Upsert logically
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
