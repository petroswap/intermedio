<?php
/**
 * @name Obtener Ventas Históricas
 * @description Suma de litros vendidos por día y estación
 * @method POST
 * @output JSON
 */

$SCRIPT_CONFIG = [
    'params' => [
        ['name' => 'productos', 'label' => 'Productos', 'type' => 'text', 'required' => true, 'placeholder' => '1,2,5', 'default' => '1'],
        ['name' => 'desde', 'label' => 'Desde', 'type' => 'text', 'required' => true, 'placeholder' => '2026-01-01 00:00:00', 'default' => '2026-01-01 00:00:00'],
        ['name' => 'hasta', 'label' => 'Hasta', 'type' => 'text', 'required' => true, 'placeholder' => '2026-12-31 23:59:59', 'default' => date('Y-m-d') . ' 23:59:59']
    ]
];

require_once __DIR__ . '/script_guard.php';

require_once dirname(__DIR__, 1) . '/config.php';
require_once dirname(__DIR__, 1) . '/core/Database.php';

header("Content-Type: application/json; charset=UTF-8");

try {
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();
    
    $productos = $_POST['productos'] ?? '';
    $desde = $_POST['desde'] ?? '';
    $hasta = $_POST['hasta'] ?? '';
    
    if (empty($productos) || empty($desde) || empty($hasta)) {
        echo json_encode(['success' => false, 'msg' => 'Faltan parámetros: productos, desde, hasta']);
        exit;
    }
    
    $arr_productos = array_map('intval', explode(',', $productos));
    $arr_productos = array_filter($arr_productos);
    
    if (empty($arr_productos)) {
        echo json_encode(['success' => false, 'msg' => 'No hay productos válidos']);
        exit;
    }
    
    $placeholders = implode(',', array_fill(0, count($arr_productos), '?'));
    
    $sql = "SELECT FIRST 1000
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
    
    $params = array_merge($arr_productos, [$desde, $hasta]);
    
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
    
    echo json_encode(['success' => true, 'data' => $data, 'count' => count($data)]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
