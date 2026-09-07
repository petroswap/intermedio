<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $sql = $_POST['sql'] ?? '';
    if (empty($sql)) {
        Response::error('Parámetro sql requerido');
        exit;
    }
    
    $sqlLimpio = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $sqlLimpio = preg_replace('/--.*$/m', '', $sqlLimpio);
    $sqlLimpio = trim($sqlLimpio);
    $sqlLimpio = mb_strtoupper(mb_substr($sqlLimpio, 0, 10));
    
    $permitidas = ['SELECT', 'WITH'];
    $inicioValido = false;
    foreach ($permitidas as $kw) {
        if (strpos($sqlLimpio, $kw) === 0) {
            $inicioValido = true;
            break;
        }
    }
    
    if (!$inicioValido) {
        Response::error('Solo se permiten consultas SELECT de lectura');
        exit;
    }
    
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();
    
    $startTime = microtime(true);
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $elapsed = round((microtime(true) - $startTime) * 1000, 2);
    
    $data = array_map(function($row) {
        return array_map(function($val) {
            return is_string($val) ? trim($val) : $val;
        }, $row);
    }, $data);
    
    Response::success([
        'data' => $data,
        'rows' => count($data),
        'time' => $elapsed,
    ]);
} catch (PDOException $e) {
    Response::error($e->getMessage());
} catch (Exception $e) {
    Response::error($e->getMessage());
}