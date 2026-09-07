<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Response.php';

try {
    $db = Database::getInstance(DB_CONFIG);
    
    // Test listar_tablas
    $tables = $db->getTables();
    echo "Tables found: " . count($tables) . "\n";
    
    // Test obtener_datos
    $pdo = $db->getConnection();
    $stmt = $pdo->query("SELECT FIRST 3 * FROM CLIENTES");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "CLIENTES rows: " . count($data) . "\n";
    if (!empty($data)) {
        echo "First row keys: " . implode(', ', array_keys($data[0])) . "\n";
    }
    
    // Test ejecutar_sql  
    $stmt2 = $pdo->query("SELECT COUNT(*) AS TOTAL FROM FACTURAS");
    $count = $stmt2->fetch(PDO::FETCH_ASSOC);
    echo "FACTURAS count: " . $count['TOTAL'] . "\n";
    
    // Test JOIN performance
    $start = microtime(true);
    $stmt3 = $pdo->query("SELECT FIRST 20 F.IDFACTURA, C.NOMBRE, F.FECHA, F.IMPORTE_TOTAL, F.ESTADO FROM FACTURAS F JOIN CLIENTES C ON F.IDCLIENTE=C.IDCLIENTE ORDER BY F.FECHA DESC");
    $joinData = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    $elapsed = round((microtime(true) - $start) * 1000, 2);
    echo "JOIN query: " . count($joinData) . " rows in {$elapsed}ms\n";
    
    echo "\n✅ All tests passed!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
