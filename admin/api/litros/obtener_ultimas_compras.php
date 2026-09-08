<?php
/**
 * Endpoint: Obtener últimas compras
 * 
 * Retorna la última compra registrada por cada base y producto.
 * Usa subconsulta para encontrar la fecha máxima de compra por combinación.
 * 
 * Método: POST
 * Parámetros:
 *   - productos (array, requerido): IDs de productos, ej: [1, 5, 6]
 * 
 * Respuesta:
 *   { success: true, data: [{idcompra, idbase, fechahora, numalbaran, nombre_operador, serie, idempresa, idproducto, precio, cantidad}] }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/litros/obtener_ultimas_compras.php" -d "productos[]=1&productos[]=5"
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

    $data = $litros->obtenerUltimasCompras($productos);

    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
