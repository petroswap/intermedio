<?php
/**
 * Endpoint: Obtener factura específica
 * 
 * Retorna datos completos de una factura por su ID.
 * Misma estructura que obtener_facturas_clientes pero para una sola factura.
 * 
 * Método: POST
 * Parámetros:
 *   - id (int, requerido): ID de la factura a consultar
 * 
 * Respuesta:
 *   { success: true, data: {id, fcreacion, femision, fvencimiento, cliente, importe, serie, num, ...} }
 *   { success: true, data: null, msg: "Factura no encontrada" } si no existe
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/facturas/obtener_factura.php" -d "id=12345"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $config = [
        'cat_prepago' => envInt('API_CAT_PREPAGO', 3),
        'cat_bonificacion' => envInt('API_CAT_BONIFICACION', 51),
    ];
    $facturas = new Facturas($db, $config);
    
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro id requerido']);
        exit;
    }
    
    $data = $facturas->obtenerFactura($id);
    if ($data) {
        echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
    } else {
        echo json_encode(['success' => true, 'data' => null, 'msg' => 'Factura no encontrada']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
