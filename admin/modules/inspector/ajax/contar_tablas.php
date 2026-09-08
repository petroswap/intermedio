<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $tables = $_POST['tables'] ?? [];
    
    if (empty($tables) || !is_array($tables)) {
        Response::error('Parámetro tables requerido (array)');
        exit;
    }

    $tables = array_slice($tables, 0, 100);
    
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();
    
    $result = [];
    foreach ($tables as $t) {
        $name = trim($t);
        if (empty($name) || !preg_match('/^[A-Za-z0-9_]+$/', $name)) continue;
        
        try {
            $nameUpper = strtoupper($name);
            $countResult = $pdo->query("SELECT COUNT(*) AS total FROM \"{$nameUpper}\"")->fetch(PDO::FETCH_ASSOC);
            $result[$name] = intval($countResult['TOTAL'] ?? 0);
        } catch (Exception $e) {
            $result[$name] = -1;
        }
    }
    
    Response::success($result);
} catch (Exception $e) {
    Response::error('Error al contar registros');
}
