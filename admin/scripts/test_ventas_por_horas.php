<?php
/**
 * @name Test Ventas por Rango de Horas
 * @description Prueba de ventas por franja horaria con datos de ejemplo: productos 1,5,6 en dos fechas concretas dentro de la franja 08:00-12:00
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
    $fechas = ['2026-09-20', '2026-09-21'];
    $hInicio = '08:00:00';
    $hFin = '12:00:00';
    
    $productPlaceholders = implode(',', array_fill(0, count($productos), '?'));
    $datePlaceholders = implode(',', array_fill(0, count($fechas), '?'));
    
    $sql = "SELECT FIRST 20
                v.idbase,
                v.fecha,
                SUM(lv.cantidad) as litros
            FROM ventas v
            INNER JOIN LINEASVENTA lv ON v.idventa = lv.idventa
            INNER JOIN SERIESALBARAN s ON v.idseriealbaran = s.idcontador
            WHERE lv.idproducto IN ($productPlaceholders)
              AND s.idempresa = 1
              AND v.fecha IN ($datePlaceholders)
              AND CAST(v.fechahora AS TIME) >= CAST(? AS TIME)
              AND CAST(v.fechahora AS TIME) <= CAST(? AS TIME)
            GROUP BY v.idbase, v.fecha
            ORDER BY v.fecha, v.idbase";
    
    $params = array_merge($productos, $fechas, [$hInicio, $hFin]);
    
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
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data),
        'fechas' => $fechas,
        'hora_inicio' => $hInicio,
        'hora_fin' => $hFin,
        'test' => true
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}