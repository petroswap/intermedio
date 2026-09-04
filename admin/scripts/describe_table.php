<?php
/**
 * ============================================
 * DESCRIBIR TABLA - Script CLI
 * ============================================
 * Muestra la estructura de una tabla específica
 * 
 * Uso: php scripts/describe_table.php --table=NOMBRE_TABLA
 */

// Obtener parámetro de línea de comandos
$table = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--table=') === 0) {
        $table = substr($arg, 8);
    }
}

if (!$table) {
    echo "Uso: php describe_table.php --table=NOMBRE_TABLA\n";
    exit(1);
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance(DB_CONFIG);
    $columns = $db->getColumns($table);
    $pk = $db->getPrimaryKey($table);
    $count = $db->count($table);
    
    echo "============================================\n";
    echo "ESTRUCTURA DE LA TABLA: {$table}\n";
    echo "============================================\n\n";
    
    echo "Clave Primaria: " . ($pk ?? 'No definida') . "\n";
    echo "Total Registros: {$count}\n";
    echo "Total Columnas: " . count($columns) . "\n\n";
    
    echo "--------------------------------------------\n";
    echo "COLUMNAS:\n";
    echo "--------------------------------------------\n\n";
    
    echo sprintf("%-30s %-15s %-10s %-10s\n", "COLUMNA", "LONGITUD", "TIPO", "NOT NULL");
    echo str_repeat("-", 70) . "\n";
    
    foreach ($columns as $col) {
        $name = $col['COLUMNA'] ?? '';
        $length = $col['LONGITUD'] ?? '-';
        $type = $col['TIPO'] ?? '-';
        $notNull = ($col['NOT_NULL'] ?? 0) ? 'SÍ' : 'NO';
        
        echo sprintf("%-30s %-15s %-10s %-10s\n", $name, $length, $type, $notNull);
    }
    
    echo "\n============================================\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
