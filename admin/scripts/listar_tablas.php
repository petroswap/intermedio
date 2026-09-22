<?php
/**
 * Script: Listar tablas de la base de datos
 * Muestra todas las tablas del sistema Firebird
 */

require_once __DIR__ . '/script_guard.php';

require_once dirname(__DIR__, 1) . '/config.php';
require_once dirname(__DIR__, 1) . '/core/Database.php';

try {
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();
    
    $sql = "SELECT RDB$RELATION_NAME as tabla
            FROM RDB$RELATIONS
            WHERE RDB$SYSTEM_FLAG = 0
            ORDER BY RDB$RELATION_NAME";
    
    $res = $pdo->query($sql);
    $tablas = [];
    
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $tablas[] = trim($row['TABLA']);
    }
    
    echo "=== TABLAS DE LA BASE DE DATOS ===\n";
    echo "Total: " . count($tablas) . " tablas\n\n";
    
    foreach ($tablas as $i => $tabla) {
        echo ($i + 1) . ". " . $tabla . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
