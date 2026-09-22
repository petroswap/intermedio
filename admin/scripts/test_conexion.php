<?php
/**
 * @name Test Conexión
 * @description Verifica la conexión a la base de datos Firebird y muestra información técnica detallada del servidor. Devuelve: el driver PDO utilizado, el servidor y puerto configurado, la ruta al archivo .FDB, el charset empleado para la conexión, la versión del motor Firebird consultada desde el sistema, la latencia en milisegundos de una consulta de prueba (SELECT 1), y el número total de tablas de usuario (no del sistema) presentes en la base. Esta prueba no modifica datos: solo ejecuta lecturas de metadatos y una consulta trivial para medir el tiempo de respuesta. Útil para diagnosticar problemas de conectividad antes de ejecutar otros scripts, validar credenciales, o verificar que la configuración de red/puerto sea correcta en entornos de producción.
 * @method POST
 * @output TEXT
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
