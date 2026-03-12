CREATE DATABASE IF NOT EXISTS plotter_reportes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE plotter_reportes;

CREATE TABLE IF NOT EXISTS reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plotter VARCHAR(50) NOT NULL,
    observacion TEXT NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    cantidad INT NOT NULL,
    porcentaje_impresion INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_plotter (plotter),
    INDEX idx_fecha (fecha)
);
