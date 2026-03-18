-- Crear tabla maestra para agrupar reportes
CREATE TABLE IF NOT EXISTS reportes_maestro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacion_general TEXT
);

-- Agregar columna de relación a la tabla de reportes si no existe
ALTER TABLE reportes ADD COLUMN IF NOT EXISTS maestro_id INT AFTER id;
ALTER TABLE reportes ADD CONSTRAINT fk_reporte_maestro FOREIGN KEY (maestro_id) REFERENCES reportes_maestro(id) ON DELETE CASCADE;
