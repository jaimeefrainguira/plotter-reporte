<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $sql = file_get_contents(__DIR__ . '/database/update_campaigns.sql');
    
    // Split by semicolon but careful with triggers/stored procs if any (none here)
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        $conn->exec($statement);
    }
    
    echo "Base de datos actualizada con éxito.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
