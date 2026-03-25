-- ==========================================================
-- ACTUALIZACIÓN PARA EL MÓDULO MES (PRODUCCIÓN PLOTTER)
-- Ejecutar en phpMyAdmin para habilitar Asignaciones y Control de Producción
-- ==========================================================

-- 1. Asegurar que la tabla 'trabajos' tenga los campos necesarios para tirajes
ALTER TABLE trabajos 
    ADD COLUMN IF NOT EXISTS caras INT DEFAULT 1 AFTER cantidad,
    ADD COLUMN IF NOT EXISTS orientacion VARCHAR(20) DEFAULT 'auto' AFTER separacion_v,
    ADD COLUMN IF NOT EXISTS usar_panelado TINYINT(1) DEFAULT 0 AFTER orientacion,
    ADD COLUMN IF NOT EXISTS panel_ancho DECIMAL(10,2) DEFAULT 0 AFTER usar_panelado,
    ADD COLUMN IF NOT EXISTS panel_gap DECIMAL(10,2) DEFAULT 0 AFTER panel_ancho,
    ADD COLUMN IF NOT EXISTS usar_sintra TINYINT(1) DEFAULT 0 AFTER panel_gap,
    ADD COLUMN IF NOT EXISTS prioridad INT DEFAULT 1 AFTER usar_sintra,
    ADD COLUMN IF NOT EXISTS tirajes INT DEFAULT 0 AFTER prioridad,
    ADD COLUMN IF NOT EXISTS tiraje_dimension VARCHAR(50) AFTER tirajes,
    ADD COLUMN IF NOT EXISTS tirajes_impresos INT DEFAULT 0 AFTER tiraje_dimension;

-- 2. Crear tabla de Asignaciones a Plotters
CREATE TABLE IF NOT EXISTS asignaciones_plotter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trabajo_id INT NOT NULL,
    plotter_id INT NOT NULL COMMENT 'ID del plotter (1 a 6)',
    tirajes_asignados INT NOT NULL,
    tirajes_producidos INT DEFAULT 0,
    estado ENUM('PENDIENTE', 'COMPLETADO') DEFAULT 'PENDIENTE',
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trabajo_id) REFERENCES trabajos(id) ON DELETE CASCADE,
    INDEX idx_trabajo (trabajo_id),
    INDEX idx_plotter (plotter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Crear tabla de Registro de Historial de Producción (Logs)
CREATE TABLE IF NOT EXISTS produccion_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asignacion_id INT NOT NULL,
    trabajo_id INT NOT NULL,
    plotter_id INT NOT NULL,
    tirajes INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asignacion_id) REFERENCES asignaciones_plotter(id) ON DELETE CASCADE,
    FOREIGN KEY (trabajo_id) REFERENCES trabajos(id) ON DELETE CASCADE,
    INDEX idx_asignacion (asignacion_id),
    INDEX idx_fecha_prod (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
