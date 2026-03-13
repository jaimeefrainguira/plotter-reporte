<?php

declare(strict_types=1);

class Reporte
{
    private bool $schemaChecked = false;

    public function __construct(private PDO $db)
    {
    }

    public function create(array $data): bool
    {
        $this->ensureCantidadImpresoColumn();

        $sql = 'INSERT INTO reportes (plotter, observacion, descripcion, cantidad, cantidad_impreso, porcentaje_impresion, fecha)
                VALUES (:plotter, :observacion, :descripcion, :cantidad, :cantidad_impreso, :porcentaje_impresion, NOW())';
        $sql = 'INSERT INTO reportes (plotter, observacion, descripcion, cantidad, cantidad_impreso, porcentaje_impresion, fecha)
                VALUES (:plotter, :observacion, :descripcion, :cantidad, :cantidad_impreso, :porcentaje_impresion, NOW())';
        $sql = 'INSERT INTO reportes (plotter, observacion, descripcion, cantidad, porcentaje_impresion, fecha)
                VALUES (:plotter, :observacion, :descripcion, :cantidad, :porcentaje_impresion, NOW())';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':plotter' => $data['plotter'],
            ':observacion' => $data['observacion'],
            ':descripcion' => $data['descripcion'],
            ':cantidad' => (int) $data['cantidad'],
            ':cantidad_impreso' => (int) $data['cantidad_impreso'],
            ':porcentaje_impresion' => (int) $data['porcentaje_impresion'],
        ]);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM reportes WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureCantidadImpresoColumn();

        $sql = 'UPDATE reportes
                SET plotter = :plotter,
                    observacion = :observacion,
                    descripcion = :descripcion,
                    cantidad = :cantidad,
                    cantidad_impreso = :cantidad_impreso,
                    porcentaje_impresion = :porcentaje_impresion
                WHERE id = :id';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':plotter' => $data['plotter'],
            ':observacion' => $data['observacion'],
            ':descripcion' => $data['descripcion'],
            ':cantidad' => (int) $data['cantidad'],
            ':cantidad_impreso' => (int) $data['cantidad_impreso'],
            ':porcentaje_impresion' => (int) $data['porcentaje_impresion'],
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM reportes WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function getDashboardStats(): array
    {
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


    public function getReportsByDateGroupedByPlotter(string $date, array $plotters): array
    {
        $grouped = [];

        foreach ($plotters as $plotter) {
            $grouped[(string) $plotter] = [];
        }

        $stmt = $this->db->prepare('SELECT * FROM reportes WHERE DATE(fecha) = :fecha ORDER BY plotter ASC, fecha DESC, id DESC');
        $stmt->execute([':fecha' => $date]);

        foreach ($stmt->fetchAll() as $row) {
            $key = (string) ($row['plotter'] ?? '');
            if (!array_key_exists($key, $grouped)) {
                $grouped[$key] = [];
            }

            $grouped[$key][] = $row;
        }

        return $grouped;
    }
    public function getAllForPdf(?int $id = null): array
    {
        if ($id !== null && $id > 0) {
            $stmt = $this->db->prepare('SELECT * FROM reportes WHERE id = :id ORDER BY fecha DESC, id DESC');
            $stmt->execute([':id' => $id]);
            return $stmt->fetchAll();
        }

        return $this->db->query('SELECT * FROM reportes ORDER BY fecha DESC, id DESC')->fetchAll();
    }
    private function ensureCantidadImpresoColumn(): void
    {
        if ($this->schemaChecked) {
            return;
        }

        $this->schemaChecked = true;

        $stmt = $this->db->query("SHOW COLUMNS FROM reportes LIKE 'cantidad_impreso'");
        $column = $stmt->fetch();

        if ($column) {
            return;
        }

        try {
            $this->db->exec('ALTER TABLE reportes ADD COLUMN cantidad_impreso INT NOT NULL DEFAULT 0 AFTER cantidad');
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'No se pudo preparar la tabla reportes. Ejecuta: ALTER TABLE reportes ADD COLUMN cantidad_impreso INT NOT NULL DEFAULT 0 AFTER cantidad;',
                0,
                $exception
            );
        }
    }

}
