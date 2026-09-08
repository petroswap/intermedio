<?php
/**
 * Endpoint: Obtener stock de tanques
 * 
 * Retorna stock actual de tanques individuales (sin alarmas).
 * Para vista con alarmas, usar obtener_litros_consumidos_capacidad.
 * 
 * Método: POST
 * Parámetros:
 *   - productos (array, requerido): IDs de productos, ej: [1, 5, 6]
 * 
 * Respuesta:
 *   { success: true, data: [{idtanque, fechahoraultimalectura, existencias, numerotanque, descripcion, idproducto, idbase, activo, idempresa, nombre}] }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/litros/obtener_stock_tanques.php" -d "productos[]=1&productos[]=5"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $litros = new Litros($db, envInt('API_ID_EMPRESA', 1));

    $productos = toArray($_POST['productos'] ?? []);

    if (empty($productos)) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro productos requerido']);
        exit;
    }

    $data = $litros->obtenerStockTanques($productos);

    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
