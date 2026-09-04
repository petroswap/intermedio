<?php
/**
 * ============================================
 * LISTAR COLUMNAS - AJAX
 * ============================================
 * Obtiene las columnas de una tabla específica
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/Request.php';

try {
    $request = new Request();
    $request->required('table');
    
    $table = $request->sanitize($request->get('table'));
    
    $db = Database::getInstance(DB_CONFIG);
    $columns = $db->getColumns($table);
    
    Response::success($columns, "Columnas obtenidas correctamente");
    
} catch (InvalidArgumentException $e) {
    Response::error($e->getMessage(), 400);
} catch (Exception $e) {
    Response::error("Error al obtener columnas: " . $e->getMessage(), 500);
}
