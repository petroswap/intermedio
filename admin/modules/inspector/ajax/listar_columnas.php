<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $table = $_POST['table'] ?? '';
    if (empty($table)) {
        Response::error('Parámetro table requerido');
        exit;
    }
    
    $db = Database::getInstance(DB_CONFIG);
    $columns = $db->getColumns($table);
    
    Response::success($columns);
} catch (Exception $e) {
    Response::error($e->getMessage());
}