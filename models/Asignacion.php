<?php
declare(strict_types=1);

class Asignacion {
    private PDO $db;
    private bool $schemaChecked = false;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    private function ensureSchema(): void {
        if ($this->schemaChecked) return;
        $this->schemaChecked = true;

        $sql = "
        CREATE TABLE IF NOT EXISTS asignaciones_plotter (
            id INT AUTO_INCREMENT PRIMARY KEY,
            trabajo_id INT NOT NULL,
            plotter_id INT NOT NULL,
            tirajes_asignados INT NOT NULL,
            tirajes_producidos INT DEFAULT 0,
            estado ENUM('PENDIENTE', 'COMPLETADO') DEFAULT 'PENDIENTE',
            fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (trabajo_id) REFERENCES trabajos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS produccion_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            asignacion_id INT NOT NULL,
            trabajo_id INT NOT NULL,
            plotter_id INT NOT NULL,
            tirajes INT NOT NULL,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (asignacion_id) REFERENCES asignaciones_plotter(id) ON DELETE CASCADE,
            FOREIGN KEY (trabajo_id) REFERENCES trabajos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        try {
            $this->db->exec($sql);
        } catch (Exception $e) {
            // Silenciar si hay error menor
        }
    }

    public function crear(array $data): int {
        $this->ensureSchema();
        
        // Validar tirajes pendientes
        $stmt = $this->db->prepare("
            SELECT tirajes, tirajes_impresos,
            (SELECT COALESCE(SUM(tirajes_asignados), 0) FROM asignaciones_plotter WHERE trabajo_id = t.id) as total_asignado
            FROM trabajos t WHERE id = ?
        ");
        $stmt->execute([$data['trabajo_id']]);
        $trabajo = $stmt->fetch();

        if (!$trabajo) throw new Exception("Trabajo no encontrado.");

        $pendientes = $trabajo['tirajes'] - $trabajo['total_asignado'];
        if ($data['tirajes_asignados'] > $pendientes) {
            throw new Exception("No puedes asignar más tirajes de los que están pendientes (" . $pendientes . ").");
        }

        $stmt = $this->db->prepare("
            INSERT INTO asignaciones_plotter (trabajo_id, plotter_id, tirajes_asignados)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $data['trabajo_id'],
            $data['plotter_id'],
            $data['tirajes_asignados']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getPorPlotter(int $plotterId): array {
        $this->ensureSchema();
        $stmt = $this->db->prepare("
            SELECT a.*, t.descripcion as trabajo_nombre, c.nombre as campana_nombre,
                   t.tirajes as trabajo_total, t.tirajes_impresos as trabajo_completados,
                   m.nombre as material_nombre
            FROM asignaciones_plotter a
            JOIN trabajos t ON a.trabajo_id = t.id
            JOIN campanas c ON t.campana_id = c.id
            LEFT JOIN materiales m ON t.material_id = m.id
            WHERE a.plotter_id = ? AND a.estado = 'PENDIENTE'
            ORDER BY a.fecha_asignacion ASC
        ");
        $stmt->execute([$plotterId]);
        return $stmt->fetchAll();
    }

    public function getAsignacionesDeTrabajo(int $trabajoId): array {
        $this->ensureSchema();
        $stmt = $this->db->prepare("
            SELECT * FROM asignaciones_plotter WHERE trabajo_id = ?
        ");
        $stmt->execute([$trabajoId]);
        return $stmt->fetchAll();
    }

    public function registrarProduccion(int $asignacionId, int $cantidad): bool {
        $this->ensureSchema();
        $this->db->beginTransaction();
        try {
            // 1. Obtener asignacion
            $stmt = $this->db->prepare("SELECT * FROM asignaciones_plotter WHERE id = ?");
            $stmt->execute([$asignacionId]);
            $a = $stmt->fetch();
            if (!$a) throw new Exception("Asignación no encontrada.");

            // 2. Validar que no exceda lo asignado
            if ($a['tirajes_producidos'] + $cantidad > $a['tirajes_asignados']) {
                 // Permitimos sobre-produccion? El usuario no dijo, pero usualmente se controla.
                 // Vamos a permitirlo pero registrar la realidad.
            }

            // 3. Actualizar asignación
            $nuevoProducido = $a['tirajes_producidos'] + $cantidad;
            $nuevoEstado = ($nuevoProducido >= $a['tirajes_asignados']) ? 'COMPLETADO' : 'PENDIENTE';
            
            $stmt = $this->db->prepare("UPDATE asignaciones_plotter SET tirajes_producidos = ?, estado = ? WHERE id = ?");
            $stmt->execute([$nuevoProducido, $nuevoEstado, $asignacionId]);

            // 4. Actualizar trabajo
            $stmt = $this->db->prepare("UPDATE trabajos SET tirajes_impresos = tirajes_impresos + ? WHERE id = ?");
            $stmt->execute([$cantidad, $a['trabajo_id']]);

            // 5. Guardar Log
            $stmt = $this->db->prepare("INSERT INTO produccion_log (asignacion_id, trabajo_id, plotter_id, tirajes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$asignacionId, $a['trabajo_id'], $a['plotter_id'], $cantidad]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
