<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();
    $tables = $db->getTables();
    
    $result = [];
    foreach ($tables as $t) {
        $name = trim($t['TABLA'] ?? $t['tabla'] ?? '');
        if (empty($name)) continue;
        
        try {
            $countResult = $pdo->query("SELECT COUNT(*) AS total FROM \"{$name}\"")->fetch(PDO::FETCH_ASSOC);
            $count = intval($countResult['TOTAL'] ?? 0);
        } catch (Exception $e) {
            $count = 0;
        }
        
        try {
            $colResult = $db->getColumns($name);
            $columns = count($colResult);
        } catch (Exception $e) {
            $columns = 0;
        }
        
        $result[] = [
            'TABLA' => $name,
            'REGISTROS' => $count,
            'COLUMNAS' => $columns,
        ];
    }
    
    Response::success($result);
} catch (Exception $e) {
    Response::error($e->getMessage());
}
