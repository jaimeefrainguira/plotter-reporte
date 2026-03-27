-- Tablas para el módulo de Gestión de Campañas e Industrial
CREATE TABLE IF NOT EXISTS materiales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('ROLLO', 'PLANCHA') NOT NULL,
    medida_ancho DECIMAL(10,2) NOT NULL COMMENT 'En mm para planchas, en cm para rollos',
    medida_largo DECIMAL(10,2) NOT NULL COMMENT 'En mm para planchas, en cm para rollos',
    unidades_por_paquete INT DEFAULT 1 COMMENT '1 para rollos, N para paquetes de planchas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campanas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    requerimiento_nro VARCHAR(50),
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('PENDIENTE', 'EN_PROCESO', 'COMPLETADO') DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trabajos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campana_id INT NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    cantidad INT NOT NULL,
    ancho_panel DECIMAL(10,2) DEFAULT 0 COMMENT 'En mm',
    alto_panel DECIMAL(10,2) DEFAULT 0 COMMENT 'En mm',
    material_id INT,
    separacion_h DECIMAL(10,2) DEFAULT 0 COMMENT 'En mm',
    separacion_v DECIMAL(10,2) DEFAULT 0 COMMENT 'En mm',
    FOREIGN KEY (campana_id) REFERENCES campanas(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materiales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consumos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trabajo_id INT NOT NULL,
    total_metros DECIMAL(10,2) DEFAULT 0,
    total_planchas DECIMAL(10,2) DEFAULT 0,
    distribucion_texto VARCHAR(255) COMMENT 'Ej: 1 rollo + 40.20m',
    unidades_por_unidad_venta INT DEFAULT 0 COMMENT 'Ej: 83 unidades por rollo',
    FOREIGN KEY (trabajo_id) REFERENCES trabajos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar materiales base
INSERT INTO materiales (nombre, tipo, medida_ancho, medida_largo) VALUES 
('ADHESIVO 122', 'ROLLO', 122.00, 5000.00),
('LONA 150', 'ROLLO', 150.00, 5000.00),
('SINTRA 3mm', 'PLANCHA', 1220.00, 2440.00),
('SINTRA 4mm', 'PLANCHA', 1220.00, 2440.00);
