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
    
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        Response::error('Nombre de tabla no válido');
        exit;
    }
    
    $db = Database::getInstance(DB_CONFIG);
    
    $tableUpper = strtoupper($table);
    
    $fieldSql = '*';
    if (!empty($fields)) {
        $fieldList = array_map('trim', explode(',', $fields));
        $safeFields = [];
        foreach ($fieldList as $f) {
            if (preg_match('/^[A-Za-z0-9_]+$/', $f)) {
                $safeFields[] = '"' . strtoupper($f) . '"';
            }
        }
        $fieldSql = !empty($safeFields) ? implode(', ', $safeFields) : '*';
    }
    
    $sql = "SELECT FIRST {$limit} {$fieldSql} FROM \"{$tableUpper}\" ORDER BY 1";
    $data = $db->query($sql);
    
    Response::success($data);
} catch (Exception $e) {
    Response::error('Error al obtener datos');
}
