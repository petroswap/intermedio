<?php
/**
 * @name Test Ventas Históricas
 * @description Prueba de ventas históricas con datos de ejemplo
 * @method POST
 * @output JSON
 */

$SCRIPT_CONFIG = [
    'params' => []
];

require_once __DIR__ . '/script_guard.php';

require_once dirname(__DIR__, 1) . '/config.php';
require_once dirname(__DIR__, 1) . '/core/Database.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();
    
    $productos = [1, 5, 6];
    $desde = '2026-01-01 00:00:00';
    $hasta = '2026-12-31 23:59:59';
    
    $placeholders = implode(',', array_fill(0, count($productos), '?'));
    
    $sql = "SELECT FIRST 20
                v.idbase,
                CAST(v.fechahora AS DATE) as fecha,
                SUM(lv.cantidad) as litros
            FROM ventas v
            INNER JOIN LINEASVENTA lv ON v.idventa = lv.idventa
            INNER JOIN SERIESALBARAN s ON v.idseriealbaran = s.idcontador
            WHERE lv.idproducto IN ($placeholders)
              AND s.idempresa = 1
              AND v.fechahora >= ?
              AND v.fechahora <= ?
            GROUP BY v.idbase, CAST(v.fechahora AS DATE)
            ORDER BY v.idbase, fecha";
    
    $params = array_merge($productos, [$desde, $hasta]);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = [
            'idbase' => intval($row['IDBASE']),
            'fecha'  => $row['FECHA'],
            'litros' => floatval($row['LITROS'])
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $data, 'count' => count($data), 'test' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
