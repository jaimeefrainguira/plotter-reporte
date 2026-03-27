CREATE DATABASE IF NOT EXISTS plotter_reportes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE plotter_reportes;

CREATE TABLE IF NOT EXISTS reportes_maestro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacion_general TEXT
);

CREATE TABLE IF NOT EXISTS reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maestro_id INT NULL,
    plotter VARCHAR(50) NOT NULL,
    observacion TEXT NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    material VARCHAR(120) NOT NULL,
    cantidad INT NOT NULL,
    cantidad_impreso INT NOT NULL DEFAULT 0,
    porcentaje_impresion INT NOT NULL,
    material_sobrante INT NOT NULL DEFAULT 0,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_plotter (plotter),
    INDEX idx_fecha (fecha),
    CONSTRAINT fk_reporte_maestro FOREIGN KEY (maestro_id) REFERENCES reportes_maestro(id) ON DELETE CASCADE
);
