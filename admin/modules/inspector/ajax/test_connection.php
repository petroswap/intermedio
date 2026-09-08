<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $startTime = microtime(true);
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();

    $pdo->query("SELECT 1 FROM RDB\$DATABASE");
    $elapsed = round((microtime(true) - $startTime) * 1000, 2);

    $driver = defined('DB_CONFIG') ? DB_CONFIG['driver'] ?? 'firebird' : 'firebird';
    $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

    $tables = $db->getTables();

    Response::success([
        'connected' => true,
        'driver' => $driver,
        'version' => $serverVersion,
        'tables_count' => count($tables),
        'latency_ms' => $elapsed,
    ]);
} catch (Exception $e) {
    Response::error('Connection failed: ' . $e->getMessage());
}
