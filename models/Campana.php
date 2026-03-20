<?php
declare(strict_types=1);

class Campana {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM campanas ORDER BY fecha_creacion DESC");
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM campanas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO campanas (nombre, requerimiento_nro, estado) VALUES (?, ?, ?)");
        $stmt->execute([
            $data['nombre'],
            $data['requerimiento_nro'] ?? null,
            $data['estado'] ?? 'PENDIENTE'
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE campanas
            SET nombre = ?, requerimiento_nro = ?, estado = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['requerimiento_nro'] ?? null,
            $data['estado'] ?? 'PENDIENTE',
            $id,
        ]);
    }

    public function delete(int $id): bool {
        // Borrar consumos → trabajos → campaña (orden por FK)
        $this->db->prepare("
            DELETE c FROM consumos c
            INNER JOIN trabajos t ON c.trabajo_id = t.id
            WHERE t.campana_id = ?
        ")->execute([$id]);

        $this->db->prepare("DELETE FROM trabajos WHERE campana_id = ?")->execute([$id]);
        $stmt = $this->db->prepare("DELETE FROM campanas WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getTrabajos(int $campanaId): array {
        $stmt = $this->db->prepare("
            SELECT t.*, m.nombre as material_nombre, m.tipo as material_tipo,
                   m.ancho_cm, m.largo_rollo_m,
                   c.total_metros, c.total_planchas, c.distribucion_texto, c.unidades_por_unidad_venta
            FROM trabajos t
            LEFT JOIN materiales m ON t.material_id = m.id
            LEFT JOIN consumos c ON t.id = c.trabajo_id
            WHERE t.campana_id = ?
            ORDER BY t.id ASC
        ");
        $stmt->execute([$campanaId]);
        return $stmt->fetchAll();
    }
}
