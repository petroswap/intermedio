<?php
/**
 * ============================================
 * TEST DE CONEXIÓN - Script CLI
 * ============================================
 * Verifica la conexión a la base de datos
 * 
 * Uso: php tests/test_connection.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/Database.php';

echo "============================================\n";
echo "TEST DE CONEXIÓN A BASE DE DATOS\n";
echo "============================================\n\n";

echo "Entorno: " . APP_ENV . "\n";
echo "Driver: " . DB_CONFIG['driver'] . "\n";
echo "Servidor: " . DB_CONFIG['server'] . ":" . DB_CONFIG['port'] . "\n";
echo "Usuario: " . DB_CONFIG['user'] . "\n\n";

try {
    echo "Intentando conectar...";
    $db = Database::getInstance(DB_CONFIG);
    
    if ($db->isConnected()) {
        echo " ✓\n\n";
        echo "✅ CONEXIÓN EXITOSA\n\n";
        
        // Probar obtener tablas
        echo "Obteniendo tablas...";
        $tables = $db->getTables();
        echo " ✓\n";
        echo "   Total tablas: " . count($tables) . "\n\n";
        
        // Mostrar primeras 5 tablas
        echo "Primeras 5 tablas:\n";
        foreach (array_slice($tables, 0, 5) as $table) {
            $name = $table['TABLA'] ?? $table['tabla'] ?? '?';
            echo "   - {$name}\n";
        }
        
        if (count($tables) > 5) {
            echo "   ... y " . (count($tables) - 5) . " más\n";
        }
        
        echo "\n✅ TODOS LOS TESTS PASARON\n";
        
    } else {
        echo " ✗\n\n";
        echo "❌ ERROR: No se pudo establecer la conexión\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo " ✗\n\n";
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    
    echo "Posibles soluciones:\n";
    echo "1. Verificar que el servidor Firebird está ejecutándose\n";
    echo "2. Verificar que la ruta de la BD es correcta\n";
    echo "3. Verificar credenciales de acceso\n";
    echo "4. Verificar que el puerto {$config['port']} está abierto\n";
    
    exit(1);
}
