<?php
/**
 * @name Test Conexión
 * @description Verifica conexión a Firebird y muestra información del servidor
 * @method POST
 */

$SCRIPT_CONFIG = [
    'params' => []
];

require_once __DIR__ . '/script_guard.php';

require_once dirname(__DIR__, 1) . '/config.php';
require_once dirname(__DIR__, 1) . '/core/Database.php';

try {
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();
    
    echo "=== TEST DE CONEXIÓN ===\n\n";
    
    echo "Driver: " . DB_CONFIG['driver'] . "\n";
    echo "Servidor: " . DB_CONFIG['server'] . ":" . DB_CONFIG['port'] . "\n";
    echo "Base de datos: " . DB_CONFIG['path'] . "\n";
    echo "Charset: " . DB_CONFIG['charset'] . "\n";
    
    $res = $pdo->query('SELECT RDB$GET_CONTEXT(\'SYSTEM\', \'ENGINE_VERSION\') as version FROM RDB$DATABASE');
    $row = $res->fetch(PDO::FETCH_ASSOC);
    echo "Versión Firebird: " . trim($row['VERSION']) . "\n";
    
    $start = microtime(true);
    $pdo->query('SELECT 1 FROM RDB$DATABASE');
    $latency = round((microtime(true) - $start) * 1000, 2);
    echo "Latencia: " . $latency . "ms\n";
    
    $res = $pdo->query('SELECT COUNT(*) as total FROM RDB$RELATIONS WHERE RDB$SYSTEM_FLAG = 0');
    $row = $res->fetch(PDO::FETCH_ASSOC);
    echo "Total tablas: " . $row['TOTAL'] . "\n";
    
    echo "\n✅ Conexión exitosa\n";
    
} catch (Exception $e) {
    echo "❌ ERROR DE CONEXIÓN: " . $e->getMessage() . "\n";
}
