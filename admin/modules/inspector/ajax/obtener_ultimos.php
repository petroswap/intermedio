<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $table = $_POST['table'] ?? '';
    $limit = intval($_POST['limit'] ?? 10);
    $fields = $_POST['fields'] ?? '';
    
    if (empty($table)) {
        Response::error('Parámetro table requerido');
        exit;
    }
    
    $db = Database::getInstance(DB_CONFIG);
    
    $fieldSql = '*';
    if (!empty($fields)) {
        $fieldList = array_map('trim', explode(',', $fields));
        $fieldSql = implode(', ', $fieldList);
    }
    
    $sql = "SELECT FIRST {$limit} {$fieldSql} FROM {$table} ORDER BY 1";
    $data = $db->query($sql);
    
    Response::success($data);
} catch (Exception $e) {
    Response::error($e->getMessage());
}