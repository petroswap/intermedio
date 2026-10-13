<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/SqlError.php';

try {
    $tables = $_POST['tables'] ?? [];
    
    if (empty($tables) || !is_array($tables)) {
        $err = SqlError::make('invalid_input', 'Falta el parámetro tables (array de nombres de tabla).');
        Response::error(SqlError::message($err), 400, ['error' => $err]);
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
} catch (PDOException $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 400, ['error' => $err]);
} catch (Throwable $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 500, ['error' => $err]);
}
