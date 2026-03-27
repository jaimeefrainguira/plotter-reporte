<?php
declare(strict_types=1);

class Material
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /* ─── READ ─────────────────────────────────────────────────────────── */

    public function getAll(bool $soloActivos = false): array
    {
        $sql = 'SELECT * FROM materiales';
        if ($soloActivos) {
            $sql .= ' WHERE activo = 1';
        }
        $sql .= ' ORDER BY tipo ASC, nombre ASC';
        return $this->db->query($sql)->fetchAll();
    }

    public function getById(int $id)
    {
        $stmt = $this->db->prepare('SELECT * FROM materiales WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getTipos(): array
    {
        return $this->db->query(
            'SELECT DISTINCT tipo FROM materiales ORDER BY tipo ASC'
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getStats(): array
    {
        $row = $this->db->query('
            SELECT
                COUNT(*)                            AS total,
                SUM(activo = 1)                     AS activos,
                SUM(stock_rollos <= stock_minimo)    AS stock_bajo,
                SUM(stock_rollos * precio_rollo)     AS valor_inventario
            FROM materiales
        ')->fetch();

        return $row ?: ['total' => 0, 'activos' => 0, 'stock_bajo' => 0, 'valor_inventario' => 0];
    }

    /* ─── CREATE ────────────────────────────────────────────────────────── */

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO materiales
                (nombre, tipo, ancho_cm, largo_rollo_m, precio_rollo, stock_rollos, stock_minimo, activo, notas)
            VALUES
                (:nombre, :tipo, :ancho_cm, :largo_rollo_m, :precio_rollo, :stock_rollos, :stock_minimo, :activo, :notas)
        ');
        $stmt->execute($this->sanitize($data));
        return (int) $this->db->lastInsertId();
    }

    /* ─── UPDATE ────────────────────────────────────────────────────────── */

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('
            UPDATE materiales SET
                nombre        = :nombre,
                tipo          = :tipo,
                ancho_cm      = :ancho_cm,
                largo_rollo_m = :largo_rollo_m,
                precio_rollo  = :precio_rollo,
                stock_rollos  = :stock_rollos,
                stock_minimo  = :stock_minimo,
                activo        = :activo,
                notas         = :notas
            WHERE id = :id
        ');
        $params = $this->sanitize($data);
        $params[':id'] = $id;
        return $stmt->execute($params);
    }

    public function adjustStock(int $id, float $delta): bool
    {
        $stmt = $this->db->prepare('
            UPDATE materiales
            SET stock_rollos = GREATEST(0, stock_rollos + :delta)
            WHERE id = :id
        ');
        return $stmt->execute([':delta' => $delta, ':id' => $id]);
    }

    /* ─── DELETE ────────────────────────────────────────────────────────── */

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM materiales WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /* ─── HELPERS ───────────────────────────────────────────────────────── */

    private function sanitize(array $data): array
    {
        return [
            ':nombre'        => mb_substr(trim((string)($data['nombre']        ?? '')), 0, 120),
            ':tipo'          => mb_substr(trim((string)($data['tipo']          ?? '')), 0, 80),
            ':ancho_cm'      => max(0, (float)($data['ancho_cm']      ?? 0)),
            ':largo_rollo_m' => max(0, (float)($data['largo_rollo_m'] ?? 50)),
            ':precio_rollo'  => max(0, (float)($data['precio_rollo']  ?? 0)),
            ':stock_rollos'  => max(0, (float)($data['stock_rollos']  ?? 0)),
            ':stock_minimo'  => max(0, (float)($data['stock_minimo']  ?? 1)),
            ':activo'        => (int)(bool)($data['activo'] ?? 1),
            ':notas'         => mb_substr(trim((string)($data['notas'] ?? '')), 0, 1000),
        ];
    }
}
