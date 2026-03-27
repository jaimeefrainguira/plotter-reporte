<?php
// Override env for local test
putenv('DB_HOST=127.0.0.1');
putenv('DB_NAME=plotter_reportes');
putenv('DB_USER=root');
putenv('DB_PASS=');

require_once 'config/database.php';
$dbClass = new Database();
try {
    $db = $dbClass->getConnection();
    $res = $db->query("DESCRIBE trabajos");
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "--- ASIGNACIONES ---\n";
    try {
        $res = $db->query("DESCRIBE asignaciones_plotter");
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } catch (Exception $e) { echo "No asignaciones_plotter table yet.\n"; }
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
