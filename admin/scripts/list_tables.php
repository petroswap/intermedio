<?php
/**
 * ============================================
 * LISTAR TABLAS - Script CLI
 * ============================================
 * Lista todas las tablas de la BD
 * 
 * Uso: php scripts/list_tables.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance(DB_CONFIG);
    $tables = $db->getTables();
    
    echo "============================================\n";
    echo "TABLAS DE LA BASE DE DATOS\n";
    echo "============================================\n\n";
    
    echo "Total: " . count($tables) . " tablas\n\n";
    
    foreach ($tables as $i => $table) {
        $name = $table['TABLA'] ?? $table['tabla'] ?? 'Desconocido';
        echo ($i + 1) . ". {$name}\n";
    }
    
    echo "\n============================================\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
