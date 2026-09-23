<?php
/**
 * @name Obtener Ventas por Rango de Horas
 * @description Consulta las ventas de productos combustibles dentro de una franja horaria concreta (hora de inicio y hora de fin) para unas fechas determinadas, agrupadas por día y estación (idbase). Devuelve la suma de litros vendidos en cada fecha dentro del tramo de horas indicado, limitado a 1000 resultados. Útil para comparar el comportamiento de un mismo rango horario entre fechas distintas (ej: hoy vs ayer en las mismas horas).
 *
 * Parámetros:
 * - Productos: IDs de producto separados por coma (ej: 1,2,5). Corresponden al campo IDPRODUCTO de la tabla LINEASVENTA. Definen qué combustibles se incluyen.
 * - Fechas: Fechas seleccionadas separadas por coma (formato: YYYY-MM-DD). Cada fecha se filtra por la franja horaria indicada. Ej: 2026-09-20,2026-09-21
 * - Hora inicio: Hora de inicio de la franja (formato: HH:MM:SS). Incluye registros con fechahora >= a esta hora en cada fecha.
 * - Hora fin: Hora de fin de la franja (formato: HH:MM:SS, inclusive). Incluye registros con fechahora <= a esta hora en cada fecha.
 * @method POST
 * @output JSON
 */

$SCRIPT_CONFIG = [
    'params' => [
        ['name' => 'productos', 'label' => 'Productos', 'type' => 'text', 'required' => true, 'placeholder' => '1,2,5', 'default' => '1',
         'help' => 'IDs de producto separados por coma (ej: 1,2,5). Corresponde al campo IDPRODUCTO de la tabla LINEASVENTA.'],
        ['name' => 'fechas', 'label' => 'Fechas', 'type' => 'text', 'required' => true, 'placeholder' => '2026-09-20,2026-09-21', 'default' => '2026-09-20',
         'help' => 'Fechas seleccionadas separadas por coma (formato: YYYY-MM-DD). Se consulta el histórico de cada fecha dentro de la franja horaria indicada.'],
        ['name' => 'hora_inicio', 'label' => 'Hora inicio', 'type' => 'text', 'required' => true, 'placeholder' => '08:00:00', 'default' => '08:00:00',
         'help' => 'Hora de inicio de la franja (formato: HH:MM:SS). Incluye ventas desde esta hora en cada fecha.'],
        ['name' => 'hora_fin', 'label' => 'Hora fin', 'type' => 'text', 'required' => true, 'placeholder' => '12:00:00', 'default' => '12:00:00',
         'help' => 'Hora de fin de la franja (formato: HH:MM:SS, inclusive). Incluye ventas hasta esta hora en cada fecha.']
    ]
];

require_once __DIR__ . '/script_guard.php';

require_once dirname(__DIR__, 1) . '/config.php';
require_once dirname(__DIR__, 1) . '/core/Database.php';

header("Content-Type: application/json; charset=UTF-8");

function _normalizarHora($h)
{
    $h = trim($h);
    if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $h, $m)) {
        return null;
    }
    $hh = (int)$m[1];
    $mm = (int)$m[2];
    $ss = isset($m[3]) ? (int)$m[3] : 0;
    if ($hh > 23 || $mm > 59 || $ss > 59) {
        return null;
    }
    return sprintf('%02d:%02d:%02d', $hh, $mm, $ss);
}

function _validarFecha($f)
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $f, $m)) {
        return false;
    }
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}

try {
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();
    
    $productos  = $_POST['productos'] ?? '';
    $fechas     = $_POST['fechas'] ?? '';
    $horaInicio = $_POST['hora_inicio'] ?? '';
    $horaFin    = $_POST['hora_fin'] ?? '';
    
    if (empty($productos) || empty($fechas) || empty($horaInicio) || empty($horaFin)) {
        echo json_encode(['success' => false, 'msg' => 'Faltan parámetros: productos, fechas, hora_inicio, hora_fin']);
        exit;
    }
    
    $arr_productos = array_values(array_unique(array_filter(array_map('intval', explode(',', $productos)))));
    
    if (empty($arr_productos)) {
        echo json_encode(['success' => false, 'msg' => 'No hay productos válidos']);
        exit;
    }
    
    $arr_fechas = array_values(array_unique(array_filter(array_map('trim', explode(',', $fechas)))));
    
    if (empty($arr_fechas)) {
        echo json_encode(['success' => false, 'msg' => 'No hay fechas válidas']);
        exit;
    }
    
    if (count($arr_fechas) > 100) {
        echo json_encode(['success' => false, 'msg' => 'Máximo 100 fechas por consulta']);
        exit;
    }
    
    foreach ($arr_fechas as $f) {
        if (!_validarFecha($f)) {
            echo json_encode(['success' => false, 'msg' => 'Fecha no válida: ' . $f . ' (formato esperado YYYY-MM-DD)']);
            exit;
        }
    }
    
    $hInicio = _normalizarHora($horaInicio);
    $hFin    = _normalizarHora($horaFin);
    
    if ($hInicio === null || $hFin === null) {
        echo json_encode(['success' => false, 'msg' => 'Formato de hora no válido (esperado HH:MM:SS)']);
        exit;
    }
    
    if ($hFin < $hInicio) {
        echo json_encode(['success' => false, 'msg' => 'hora_fin debe ser mayor o igual a hora_inicio']);
        exit;
    }
    
    $productPlaceholders = implode(',', array_fill(0, count($arr_productos), '?'));
    $datePlaceholders = implode(',', array_fill(0, count($arr_fechas), '?'));
    
    // Las fechas se filtran sobre la columna FECHA (DATE) y la franja horaria
    // se aplica sobre la parte TIME de FECHAHORA.
    $sql = "SELECT FIRST 1000
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
    
    $params = array_merge($arr_productos, $arr_fechas, [$hInicio, $hFin]);
    
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
        'fechas' => $arr_fechas,
        'hora_inicio' => $hInicio,
        'hora_fin' => $hFin
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}