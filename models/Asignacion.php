<?php
declare(strict_types=1);

class Asignacion {
    private PDO $db;
    private bool $schemaChecked = false;
    private const CAPACIDAD_PLOTTER_DEFAULT = 100;

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
            estado VARCHAR(20) DEFAULT 'Pendiente',
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
            $this->db->exec("ALTER TABLE asignaciones_plotter MODIFY COLUMN estado VARCHAR(20) DEFAULT 'Pendiente'");
        } catch (Exception $e) {
            // Silenciar si hay error menor
        }
    }

    private function getPendienteAsignableDeTrabajo(int $trabajoId, ?int $excluirAsignacionId = null): int {
        $joinCondExclusion = '';
        if ($excluirAsignacionId !== null && $excluirAsignacionId > 0) {
            $joinCondExclusion = " AND a.id <> :asignacion_id_excluir ";
        }

        $sql = "
            SELECT
                t.tirajes,
                t.tirajes_impresos,
                COALESCE(SUM(GREATEST(a.tirajes_asignados - a.tirajes_producidos, 0)), 0) AS pendiente_ya_asignado
            FROM trabajos t
            LEFT JOIN asignaciones_plotter a ON a.trabajo_id = t.id
                AND a.estado <> 'Completado'
                {$joinCondExclusion}
            WHERE t.id = :trabajo_id
        ";

        $sql .= " GROUP BY t.id ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':trabajo_id', $trabajoId, PDO::PARAM_INT);
        if ($excluirAsignacionId !== null && $excluirAsignacionId > 0) {
            $stmt->bindValue(':asignacion_id_excluir', $excluirAsignacionId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $trabajo = $stmt->fetch();

        if (!$trabajo) {
            throw new Exception('Trabajo no encontrado.');
        }

        $restanteReal = max(0, (int)$trabajo['tirajes'] - (int)$trabajo['tirajes_impresos']);
        return max(0, $restanteReal - (int)$trabajo['pendiente_ya_asignado']);
    }

    public function crear(array $data): int {
        $this->ensureSchema();

        $pendiente = $this->getPendienteAsignableDeTrabajo((int)$data['trabajo_id']);
        if ($pendiente <= 0) {
            throw new Exception('El trabajo ya no tiene tirajes pendientes por asignar.');
        }

        $tirajesAsignados = (int)$data['tirajes_asignados'];
        if ($tirajesAsignados <= 0) {
            throw new Exception('La cantidad a asignar debe ser mayor a 0.');
        }

        if ($tirajesAsignados > $pendiente) {
            throw new Exception("No puedes asignar más tirajes de los pendientes ($pendiente). ");
        }

        $stmt = $this->db->prepare("
            INSERT INTO asignaciones_plotter (trabajo_id, plotter_id, tirajes_asignados, estado)
            VALUES (?, ?, ?, 'Pendiente')
        ");
        $stmt->execute([
            (int)$data['trabajo_id'],
            (int)$data['plotter_id'],
            $tirajesAsignados
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function autoAsignarPendientes(int $capacidadPorPlotter = self::CAPACIDAD_PLOTTER_DEFAULT, ?int $campanaId = null): array {
        $this->ensureSchema();
        $capacidadPorPlotter = max(1, $capacidadPorPlotter);

        $this->db->beginTransaction();
        try {
            $carga = $this->getCargaPendientePorPlotter();

            $sql = "
                SELECT t.id, t.prioridad, t.tirajes, t.tirajes_impresos, t.ancho_panel,
                       COALESCE(m.tipo, '') AS material_tipo,
                       COALESCE(m.nombre, '') AS material_nombre
                FROM trabajos t
                LEFT JOIN materiales m ON m.id = t.material_id
                WHERE t.tirajes > t.tirajes_impresos
            ";
            if ($campanaId !== null && $campanaId > 0) {
                $sql .= " AND t.campana_id = :campana_id ";
            }
            $sql .= " ORDER BY t.prioridad DESC, t.id ASC ";
            $stmt = $this->db->prepare($sql);
            if ($campanaId !== null && $campanaId > 0) {
                $stmt->bindValue(':campana_id', $campanaId, PDO::PARAM_INT);
            }
            $stmt->execute();
            $trabajos = $stmt->fetchAll();

            $resultado = [
                'creadas' => 0,
                'tirajes_asignados' => 0,
                'saltadas' => [],
            ];

            foreach ($trabajos as $trabajo) {
                $trabajoId = (int)$trabajo['id'];
                $pendiente = $this->getPendienteAsignableDeTrabajo($trabajoId);
                if ($pendiente <= 0) {
                    continue;
                }

                $plottersCompatibles = $this->getPlottersCompatibles(
                    (float)$trabajo['ancho_panel'],
                    (string)$trabajo['material_tipo'],
                    (string)$trabajo['material_nombre']
                );
                if (empty($plottersCompatibles)) {
                    $resultado['saltadas'][] = "Trabajo #{$trabajoId}: sin plotter compatible.";
                    continue;
                }

                while ($pendiente > 0) {
                    $plotter = $this->pickPlotterDisponiblePorPrioridad($plottersCompatibles, $carga, $capacidadPorPlotter);
                    if ($plotter === null) {
                        $resultado['saltadas'][] = "Trabajo #{$trabajoId}: capacidad diaria agotada en plotters compatibles.";
                        break;
                    }

                    $capDisponible = max(0, $capacidadPorPlotter - $carga[$plotter]);
                    if ($capDisponible <= 0) {
                        break;
                    }

                    $lote = min($pendiente, $capDisponible);
                    $this->crear([
                        'trabajo_id' => $trabajoId,
                        'plotter_id' => $plotter,
                        'tirajes_asignados' => $lote,
                    ]);

                    $carga[$plotter] += $lote;
                    $pendiente -= $lote;
                    $resultado['creadas']++;
                    $resultado['tirajes_asignados'] += $lote;

                    if ($pendiente > 0 && $lote < $capDisponible) {
                        break;
                    }
                }
            }

            $this->db->commit();
            return $resultado;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function getPlottersCompatibles(float $ancho, string $materialTipo = '', string $materialNombre = ''): array {
        $esAdhesivo = $this->esMaterialAdhesivo($materialTipo, $materialNombre);

        // En adhesivos se prioriza 1-4 (quedan 5-6 como respaldo).
        if ($esAdhesivo) {
            return [1, 2, 3, 4, 5, 6];
        }

        // 150cm = cualquier plotter. Otros anchos = plotters 5-6
        if (abs($ancho - 150.0) < 0.01) {
            return [1, 2, 3, 4, 5, 6];
        }
        return [5, 6];
    }

    private function esMaterialAdhesivo(string $tipo, string $nombre): bool {
        $texto = mb_strtolower(trim($tipo . ' ' . $nombre), 'UTF-8');
        return str_contains($texto, 'adhesiv');
    }

    private function getCargaPendientePorPlotter(): array {
        $carga = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
        $stmt = $this->db->query("
            SELECT plotter_id, COALESCE(SUM(GREATEST(tirajes_asignados - tirajes_producidos, 0)), 0) AS carga
            FROM asignaciones_plotter
            WHERE estado <> 'Completado'
            GROUP BY plotter_id
        ");
        foreach ($stmt->fetchAll() as $row) {
            $pid = (int)$row['plotter_id'];
            if (isset($carga[$pid])) {
                $carga[$pid] = (int)$row['carga'];
            }
        }
        return $carga;
    }

    private function pickPlotterDisponiblePorPrioridad(array $plottersCompatibles, array $carga, int $capacidadPorPlotter): ?int {
        foreach ($plottersCompatibles as $plotterId) {
            if (($carga[$plotterId] ?? 0) < $capacidadPorPlotter) {
                return $plotterId;
            }
        }
        return null;
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
            WHERE a.plotter_id = ? AND a.estado <> 'Completado'
            ORDER BY a.fecha_asignacion ASC
        ");
        $stmt->execute([$plotterId]);
        return $stmt->fetchAll();
    }

    public function getResumenJornada(): array {
        $this->ensureSchema();
        $stmt = $this->db->query("
            SELECT a.id,
                   a.plotter_id,
                   a.tirajes_asignados,
                   a.tirajes_producidos,
                   a.estado,
                   t.descripcion AS trabajo_nombre,
                   c.nombre AS campana_nombre,
                   COALESCE(m.nombre, '') AS material_nombre
            FROM asignaciones_plotter a
            INNER JOIN trabajos t ON t.id = a.trabajo_id
            INNER JOIN campanas c ON c.id = t.campana_id
            LEFT JOIN materiales m ON m.id = t.material_id
            WHERE a.tirajes_asignados > 0
              AND (a.estado <> 'Completado' OR a.tirajes_producidos > 0)
            ORDER BY a.plotter_id ASC, a.fecha_asignacion ASC, a.id ASC
        ");

        return $stmt->fetchAll();
    }

    public function getAsignacionesDeTrabajo(int $trabajoId): array {
        $this->ensureSchema();
        $stmt = $this->db->prepare("SELECT * FROM asignaciones_plotter WHERE trabajo_id = ? ORDER BY fecha_asignacion ASC, id ASC");
        $stmt->execute([$trabajoId]);
        return $stmt->fetchAll();
    }

    public function getAsignacionesPorCampana(int $campanaId): array {
        $this->ensureSchema();
        $stmt = $this->db->prepare("
            SELECT a.*
            FROM asignaciones_plotter a
            INNER JOIN trabajos t ON t.id = a.trabajo_id
            WHERE t.campana_id = ?
            ORDER BY a.fecha_asignacion ASC, a.id ASC
        ");
        $stmt->execute([$campanaId]);
        return $stmt->fetchAll();
    }

    public function actualizar(int $asignacionId, int $plotterId, int $tirajesAsignados): bool {
        $this->ensureSchema();
        $stmt = $this->db->prepare("SELECT * FROM asignaciones_plotter WHERE id = ?");
        $stmt->execute([$asignacionId]);
        $actual = $stmt->fetch();
        if (!$actual) {
            throw new Exception('Asignación no encontrada.');
        }

        $tirajesAsignados = max(1, $tirajesAsignados);
        $tirajesProducidos = (int)$actual['tirajes_producidos'];
        if ($tirajesAsignados < $tirajesProducidos) {
            throw new Exception("No puedes asignar menos de los tirajes ya producidos ({$tirajesProducidos}).");
        }

        $pendienteDisponible = $this->getPendienteAsignableDeTrabajo((int)$actual['trabajo_id'], $asignacionId);
        $maximoPermitido = $pendienteDisponible + $tirajesProducidos;
        if ($tirajesAsignados > $maximoPermitido) {
            throw new Exception("No puedes asignar más de lo disponible para este trabajo ({$maximoPermitido}).");
        }

        $nuevoEstado = 'Pendiente';
        if ($tirajesProducidos > 0 && $tirajesProducidos < $tirajesAsignados) {
            $nuevoEstado = 'En proceso';
        } elseif ($tirajesProducidos >= $tirajesAsignados) {
            $nuevoEstado = 'Completado';
        }

        $stmt = $this->db->prepare("
            UPDATE asignaciones_plotter
            SET plotter_id = ?, tirajes_asignados = ?, estado = ?
            WHERE id = ?
        ");
        return $stmt->execute([$plotterId, $tirajesAsignados, $nuevoEstado, $asignacionId]);
    }

    public function eliminar(int $asignacionId): bool {
        $this->ensureSchema();
        $stmt = $this->db->prepare("SELECT tirajes_producidos FROM asignaciones_plotter WHERE id = ?");
        $stmt->execute([$asignacionId]);
        $actual = $stmt->fetch();
        if (!$actual) {
            throw new Exception('Asignación no encontrada.');
        }
        if ((int)$actual['tirajes_producidos'] > 0) {
            throw new Exception('No se puede eliminar una asignación con producción registrada.');
        }

        $stmt = $this->db->prepare("DELETE FROM asignaciones_plotter WHERE id = ?");
        return $stmt->execute([$asignacionId]);
    }

    public function registrarProduccion(int $asignacionId, int $cantidad): bool {
        $this->ensureSchema();
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM asignaciones_plotter WHERE id = ? FOR UPDATE");
            $stmt->execute([$asignacionId]);
            $a = $stmt->fetch();
            if (!$a) throw new Exception('Asignación no encontrada.');

            $cantidad = max(1, $cantidad);
            $pendienteAsignacion = max(0, (int)$a['tirajes_asignados'] - (int)$a['tirajes_producidos']);
            $cantidadAplicada = min($cantidad, $pendienteAsignacion);
            if ($cantidadAplicada <= 0) {
                throw new Exception('La asignación ya está completada.');
            }

            $nuevoProducido = (int)$a['tirajes_producidos'] + $cantidadAplicada;
            $nuevoEstado = 'En proceso';
            if ($nuevoProducido <= 0) {
                $nuevoEstado = 'Pendiente';
            } elseif ($nuevoProducido >= (int)$a['tirajes_asignados']) {
                $nuevoEstado = 'Completado';
            }

            $stmt = $this->db->prepare('UPDATE asignaciones_plotter SET tirajes_producidos = ?, estado = ? WHERE id = ?');
            $stmt->execute([$nuevoProducido, $nuevoEstado, $asignacionId]);

            $stmt = $this->db->prepare('UPDATE trabajos SET tirajes_impresos = LEAST(tirajes, tirajes_impresos + ?) WHERE id = ?');
            $stmt->execute([$cantidadAplicada, $a['trabajo_id']]);

            $stmt = $this->db->prepare('INSERT INTO produccion_log (asignacion_id, trabajo_id, plotter_id, tirajes) VALUES (?, ?, ?, ?)');
            $stmt->execute([$asignacionId, $a['trabajo_id'], $a['plotter_id'], $cantidadAplicada]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
