<?php
/**
 * Endpoint: Obtener ventas del día anterior
 * 
 * Retorna la suma de litros vendidos por cada base y producto en la fecha indicada.
 * 
 * Método: POST
 * Parámetros:
 *   - productos (array, requerido): IDs de productos, ej: [1, 5, 6]
 *   - ayer (string, requerido): Fecha del día anterior (YYYY-MM-DD)
 * 
 * Respuesta:
 *   { success: true, data: [{fecha, idbase, suma_cantidad, idproducto, idempresa}] }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/litros/obtener_ventas_ayer.php" -d "productos[]=1&ayer=2026-09-06"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $litros = new Litros($db, envInt('API_ID_EMPRESA', 1));

    $productos = toArray($_POST['productos'] ?? []);
    $ayer = $_POST['ayer'] ?? '';

    if (empty($productos)) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro productos requerido']);
        exit;
    }

    if (empty($ayer)) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro ayer requerido']);
        exit;
    }

    $data = $litros->obtenerVentasAyer($productos, $ayer);

    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
