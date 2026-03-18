<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Crear tabla maestra para agrupar reportes
    $conn->exec("CREATE TABLE IF NOT EXISTS reportes_maestro (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        observacion_general TEXT
    )");
    
    // Agregar columna de relación a la tabla de reportes si no existe
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS 
                           WHERE TABLE_SCHEMA = DATABASE() 
                           AND TABLE_NAME = 'reportes' 
                           AND COLUMN_NAME = 'maestro_id'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $conn->exec("ALTER TABLE reportes ADD COLUMN maestro_id INT AFTER id");
        $conn->exec("ALTER TABLE reportes ADD CONSTRAINT fk_reporte_maestro 
                    FOREIGN KEY (maestro_id) REFERENCES reportes_maestro(id) ON DELETE CASCADE");
    }
    
    echo "Base de datos actualizada con la tabla maestra de reportes.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
