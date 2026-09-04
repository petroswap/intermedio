<?php
/**
 * ============================================
 * LISTAR TABLAS - AJAX
 * ============================================
 * Obtiene la lista de todas las tablas de la BD
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $db = Database::getInstance(DB_CONFIG);
    $tables = $db->getTables();
    
    Response::success($tables, "Tablas obtenidas correctamente");
    
} catch (Exception $e) {
    Response::error("Error al obtener tablas: " . $e->getMessage(), 500);
}
