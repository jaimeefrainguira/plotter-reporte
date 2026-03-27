<?php

declare(strict_types=1);

class Reporte
{
    private bool $schemaChecked = false;
    private bool $masterTableChecked = false;
    private bool $masterTableExists = false;

    private array $requiredColumns = [
        'cantidad_impreso' => 'ALTER TABLE reportes ADD COLUMN cantidad_impreso INT NOT NULL DEFAULT 0 AFTER cantidad',
        'porcentaje_impresion' => 'ALTER TABLE reportes ADD COLUMN porcentaje_impresion INT NOT NULL DEFAULT 0 AFTER cantidad_impreso',
        'material_sobrante' => 'ALTER TABLE reportes ADD COLUMN material_sobrante INT NOT NULL DEFAULT 0 AFTER porcentaje_impresion',
        'maestro_id' => 'ALTER TABLE reportes ADD COLUMN maestro_id INT NULL AFTER id',
        'material' => 'ALTER TABLE reportes ADD COLUMN material VARCHAR(120) NOT NULL DEFAULT "" AFTER descripcion',
    ];

    public function __construct(private PDO $db)
    {
    }

    public function getLatestMasterId(): ?int
    {
        if (!$this->hasMasterTable()) {
            return null;
        }

        return (int) $this->db->query('SELECT MAX(id) FROM reportes_maestro')->fetchColumn() ?: null;
    }

    public function getMasterById(int $masterId): ?array
    {
        if (!$this->hasMasterTable()) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM reportes_maestro WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $masterId]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function getByMasterId(int $masterId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM reportes WHERE maestro_id = :masterId ORDER BY plotter ASC, id ASC');
        $stmt->execute([':masterId' => $masterId]);
        return $stmt->fetchAll();
    }

    public function createMaster(?string $observacion = null): int
    {
        if (!$this->hasMasterTable()) {
            throw new RuntimeException(
                "No existe la tabla reportes_maestro. Ejecuta el script actualizado de database/script.sql."
            );
        }

        $sql = 'INSERT INTO reportes_maestro (fecha_creacion, observacion_general)
                VALUES (NOW(), :observacion)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':observacion' => $observacion]);
        return (int) $this->db->lastInsertId();
    }

    public function create(array $data): bool
    {
        $this->ensureRequiredColumns();

        $sql = 'INSERT INTO reportes (maestro_id, plotter, observacion, descripcion, material, cantidad, cantidad_impreso, porcentaje_impresion, material_sobrante, fecha)
                VALUES (:maestro_id, :plotter, :observacion, :descripcion, :material, :cantidad, :cantidad_impreso, :porcentaje_impresion, :material_sobrante, NOW())';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':maestro_id' => $data['maestro_id'] ?? null,
            ':plotter' => $data['plotter'],
            ':observacion' => $data['observacion'],
            ':descripcion' => $data['descripcion'],
            ':material' => $data['material'] ?? '',
            ':cantidad' => (int) $data['cantidad'],
            ':cantidad_impreso' => (int) $data['cantidad_impreso'],
            ':porcentaje_impresion' => (int) $data['porcentaje_impresion'],
            ':material_sobrante' => (int) $data['material_sobrante'],
        ]);
    }

    public function getById(int $id): ?array
    {
        $this->ensureRequiredColumns();

        $stmt = $this->db->prepare('SELECT * FROM reportes WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureRequiredColumns();

        $sql = 'UPDATE reportes
                SET plotter = :plotter,
                    observacion = :observacion,
                    descripcion = :descripcion,
                    material = :material,
                    cantidad = :cantidad,
                    cantidad_impreso = :cantidad_impreso,
                    porcentaje_impresion = :porcentaje_impresion,
                    material_sobrante = :material_sobrante
                WHERE id = :id';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':plotter' => $data['plotter'],
            ':observacion' => $data['observacion'],
            ':descripcion' => $data['descripcion'],
            ':material' => $data['material'] ?? '',
            ':cantidad' => (int) $data['cantidad'],
            ':cantidad_impreso' => (int) $data['cantidad_impreso'],
            ':porcentaje_impresion' => (int) $data['porcentaje_impresion'],
            ':material_sobrante' => (int) $data['material_sobrante'],
        ]);

        return $stmt->rowCount() > 0;
    }

    public function updatePercentage(int $id, int $percentage): bool
    {
        $sql = 'UPDATE reportes SET porcentaje_impresion = :percentage WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':percentage' => $percentage]);
    }

    public function delete(int $id): bool
    {
        $this->ensureRequiredColumns();

        $stmt = $this->db->prepare('DELETE FROM reportes WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function getDashboardStats(): array
    {
        $this->ensureRequiredColumns();

        $total = (int) $this->db->query('SELECT COUNT(*) FROM reportes')->fetchColumn();
        $latest = $this->db->query('SELECT * FROM reportes ORDER BY fecha DESC, id DESC LIMIT 1')->fetch();
        $perPlotter = $this->db->query('SELECT plotter, COUNT(*) AS total FROM reportes GROUP BY plotter ORDER BY plotter')->fetchAll();

        return [
            'total' => $total,
            'latest' => $latest ?: null,
            'perPlotter' => $perPlotter,
        ];
    }

    public function getPaginated(array $filters, int $page, int $perPage): array
    {
        $this->ensureRequiredColumns();

        $where = [];
        $params = [];

        if (!empty($filters['plotter'])) {
            $where[] = 'plotter = :plotter';
            $params[':plotter'] = $filters['plotter'];
        }

        if (!empty($filters['fecha'])) {
            $where[] = 'DATE(fecha) = :fecha';
            $params[':fecha'] = $filters['fecha'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM reportes {$whereSql}");
        $countStmt->execute($params);
        $totalRows = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT * FROM reportes {$whereSql} ORDER BY fecha DESC, id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'totalRows' => $totalRows,
        ];
    }

    public function getAllForPdf(?int $id = null, ?string $plotter = null, ?string $date = null): array
    {
        $this->ensureRequiredColumns();

        if ($id !== null && $id > 0) {
            $stmt = $this->db->prepare('SELECT * FROM reportes WHERE id = :id ORDER BY fecha DESC, id DESC');
            $stmt->execute([':id' => $id]);
            return $stmt->fetchAll();
        }

        $where = [];
        $params = [];

        if ($plotter !== null && $plotter !== '') {
            $where[] = 'plotter = :plotter';
            $params[':plotter'] = $plotter;
        }

        if ($date !== null && $date !== '') {
            $where[] = 'DATE(fecha) = :fecha';
            $params[':fecha'] = $date;
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $sql = 'SELECT * FROM reportes' . $whereSql . ' ORDER BY fecha DESC, id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getAll(): array
    {
        $this->ensureRequiredColumns();

        return $this->db->query('SELECT * FROM reportes ORDER BY fecha DESC, id DESC')->fetchAll();
    }

    public function getByDateAndPlotter(?string $date = null, ?string $plotter = null): array
    {
        $this->ensureRequiredColumns();

        $where = [];
        $params = [];

        if ($date !== null && $date !== '') {
            $where[] = 'DATE(fecha) = :fecha';
            $params[':fecha'] = $date;
        }

        if ($plotter !== null && $plotter !== '') {
            $where[] = 'plotter = :plotter';
            $params[':plotter'] = $plotter;
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $sql = 'SELECT * FROM reportes' . $whereSql . ' ORDER BY fecha DESC, id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function ensureRequiredColumns(): void
    {
        if ($this->schemaChecked) {
            return;
        }

        $this->schemaChecked = true;

        foreach ($this->requiredColumns as $columnName => $alterSql) {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table
                   AND COLUMN_NAME = :column'
            );
            $stmt->execute([
                ':table' => 'reportes',
                ':column' => $columnName,
            ]);
            $columnExists = (int) $stmt->fetchColumn() > 0;

            if ($columnExists) {
                continue;
            }

            try {
                $this->db->exec($alterSql);
            } catch (PDOException $exception) {
                throw new RuntimeException(
                    'No se pudo preparar la tabla reportes. Ejecuta el script actualizado de database/script.sql.',
                    0,
                    $exception
                );
            }
        }
    }

    private function hasMasterTable(): bool
    {
        if ($this->masterTableChecked) {
            return $this->masterTableExists;
        }

        $this->masterTableChecked = true;

        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table'
            );
            $stmt->execute([':table' => 'reportes_maestro']);
            $this->masterTableExists = (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'No se pudo verificar la tabla reportes_maestro. Ejecuta el script actualizado de database/script.sql.',
                0,
                $exception
            );
        }

        return $this->masterTableExists;
    }
}
