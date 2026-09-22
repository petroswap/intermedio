<?php
/**
 * Script: Test Ventas Históricas
 * Muestra prueba de ventas históricas con datos de ejemplo
 */

require_once __DIR__ . '/script_guard.php';

require_once dirname(__DIR__, 1) . '/config.php';
require_once dirname(__DIR__, 1) . '/core/Database.php';

try {
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();
    
    echo "=== TEST VENTAS HISTÓRICAS ===\n\n";
    
    // Parámetros de prueba
    $productos = [1, 5, 6];
    $desde = '2026-01-01 00:00:00';
    $hasta = '2026-12-31 23:59:59';
    
    echo "Parámetros de prueba:\n";
    echo "  Productos: " . implode(', ', $productos) . "\n";
    echo "  Desde: $desde\n";
    echo "  Hasta: $hasta\n\n";
    
    $placeholders = implode(',', array_fill(0, count($productos), '?'));
    
    $sql = "SELECT 
                v.idbase,
                CAST(v.fechahora AS DATE) as fecha,
                SUM(lv.cantidad) as litros
            FROM ventas v
            LEFT JOIN LINEASVENTA lv ON v.idventa = lv.idventa
            LEFT JOIN SERIESALBARAN s ON v.idseriealbaran = s.idcontador
            WHERE lv.idproducto IN ($placeholders)
              AND s.idempresa = 1
              AND v.fechahora >= ?
              AND v.fechahora <= ?
            GROUP BY v.idbase, CAST(v.fechahora AS DATE)
            ORDER BY v.idbase, fecha
            ROWS 1 TO 20";
    
    $params = array_merge($productos, [$desde, $hasta]);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        echo "No se encontraron datos de ventas.\n";
    } else {
        echo "Resultados (primeros 20 registros):\n\n";
        echo str_pad("IDBASE", 8) . str_pad("FECHA", 14) . "LITROS\n";
        echo str_repeat("-", 30) . "\n";
        
        foreach ($data as $row) {
            echo str_pad($row['IDBASE'], 8) . 
                 str_pad($row['FECHA'], 14) . 
                 number_format($row['LITROS'], 2) . "\n";
        }
        
        echo "\nTotal registros: " . count($data) . "\n";
    }
    
    echo "\n✅ Test completado\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
