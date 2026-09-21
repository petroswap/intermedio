<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $startTime = microtime(true);
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();

    // Test latency
    $pdo->query("SELECT 1 FROM RDB\$DATABASE");
    $latency = round((microtime(true) - $startTime) * 1000, 2);

    $driver = defined('DB_CONFIG') ? DB_CONFIG['driver'] ?? 'firebird' : 'firebird';
    $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

    // Get all tables
    $tables = $db->getTables();
    $tablesCount = count($tables);

    // Count records for each table (limit to 200 tables for performance)
    $totalRecords = 0;
    $topTables = [];
    $tablesToCount = array_slice($tables, 0, 200);

    foreach ($tablesToCount as $tableRow) {
        $tableName = $tableRow['TABLA'] ?? $tableRow['tabla'] ?? '';
        if (empty($tableName)) continue;

        try {
            $sql = "SELECT COUNT(*) AS total FROM \"$tableName\"";
            $result = $pdo->query($sql);
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $count = (int)($row['TOTAL'] ?? 0);
            $totalRecords += $count;
            $topTables[] = ['name' => $tableName, 'records' => $count];
        } catch (Exception $e) {
            // Skip tables we can't count (views, system tables, etc.)
        }
    }

    // Sort by records descending and take top 10
    usort($topTables, function($a, $b) {
        return $b['records'] - $a['records'];
    });
    $topTables = array_slice($topTables, 0, 10);

    Response::success([
        'driver' => $driver,
        'version' => $serverVersion,
        'latency_ms' => $latency,
        'tables_count' => $tablesCount,
        'total_records' => $totalRecords,
        'top_tables' => $topTables,
    ]);
} catch (Exception $e) {
    Response::error('Error al obtener estadísticas: ' . $e->getMessage());
}
