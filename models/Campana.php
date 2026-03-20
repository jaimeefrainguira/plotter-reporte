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
