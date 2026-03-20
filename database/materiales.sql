-- Tabla de Materia Prima (materiales en rollo de 50m)
CREATE TABLE IF NOT EXISTS materiales (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(120)    NOT NULL COMMENT 'Nombre del material (p.ej. Lona 150)',
    tipo          VARCHAR(80)     NOT NULL COMMENT 'Categoría (lona, adhesivo, papel, etc.)',
    ancho_cm      DECIMAL(8,2)    NOT NULL COMMENT 'Ancho del rollo en cm',
    largo_rollo_m DECIMAL(8,2)    NOT NULL DEFAULT 50.00 COMMENT 'Largo estándar del rollo en metros',
    precio_rollo  DECIMAL(10,2)   NOT NULL DEFAULT 0.00 COMMENT 'Precio por rollo',
    stock_rollos  DECIMAL(10,2)   NOT NULL DEFAULT 0.00 COMMENT 'Stock actual en rollos',
    stock_minimo  DECIMAL(10,2)   NOT NULL DEFAULT 1.00 COMMENT 'Alerta cuando el stock baje de este valor',
    activo        TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
    notas         TEXT            NULL     COMMENT 'Observaciones adicionales',
    creado_en     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_activo (activo)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Datos de ejemplo
INSERT INTO materiales (nombre, tipo, ancho_cm, largo_rollo_m, precio_rollo, stock_rollos, stock_minimo, notas)
VALUES
  ('Lona 150',        'lona',     150, 50, 0.00, 2.00, 1.00, 'Lona estándar 150 cm de ancho'),
  ('Lona 160',        'lona',     160, 50, 0.00, 1.00, 1.00, 'Lona 160 cm de ancho'),
  ('Adhesivo Blanco', 'adhesivo', 122, 50, 0.00, 3.00, 1.00, 'Vinilo adhesivo blanco brillante'),
  ('Adhesivo Negro',  'adhesivo', 122, 50, 0.00, 1.50, 1.00, 'Vinilo adhesivo negro mate'),
  ('Papel Bond',      'papel',    100, 50, 0.00, 0.50, 1.00, 'Papel bond para pruebas');
