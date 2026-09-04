<?php
/**
 * ============================================
 * OBTENER DATOS - AJAX
 * ============================================
 * Obtiene datos de una tabla con filtros y paginación
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/Request.php';
require_once __DIR__ . '/../../../core/CRUD.php';

try {
    $request = new Request();
    $request->required('table');
    
    $table = $request->sanitize($request->get('table'));
    $page = $request->int('page', 1);
    $perPage = $request->int('per_page', 50);
    $offset = ($page - 1) * $perPage;
    
    // Obtener campos seleccionados
    $fields = $request->get('fields', '*');
    if (is_string($fields) && $fields !== '*') {
        $fields = array_map('trim', explode(',', $fields));
    }
    
    // Construir filtros
    $filters = [];
    $i = 0;
    while ($request->has("filter_field_{$i}")) {
        $field = $request->sanitize($request->get("filter_field_{$i}"));
        $operator = $request->sanitize($request->get("filter_operator_{$i}", '='));
        $value = $request->get("filter_value_{$i}");
        
        if ($field && $value !== '') {
            $filters[$field] = [
                'operator' => $operator,
                'value' => $value
            ];
        }
        $i++;
    }
    
    // Conectar y obtener datos
    $db = Database::getInstance(DB_CONFIG);
    $crud = new CRUD($db, $table);
    
    $data = $crud->getAll($filters, ['1' => 'ASC'], $perPage, $offset);
    $total = $crud->count($filters);
    
    // Formatear datos si se especificaron campos
    if (is_array($fields) && !empty($fields)) {
        $data = array_map(function($row) use ($fields) {
            return array_intersect_key($row, array_flip($fields));
        }, $data);
    }
    
    Response::paginated($data, $total, $page, $perPage);
    
} catch (InvalidArgumentException $e) {
    Response::error($e->getMessage(), 400);
} catch (Exception $e) {
    Response::error("Error al obtener datos: " . $e->getMessage(), 500);
}
