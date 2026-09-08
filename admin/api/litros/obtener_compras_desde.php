<?php
/**
 * Endpoint: Obtener compras desde fecha
 * 
 * Retorna todas las compras desde una fecha específica, ordenadas ascendente.
 * 
 * Método: POST
 * Parámetros:
 *   - productos (array, requerido): IDs de productos, ej: [1, 5, 6]
 *   - desde (string, requerido): Fecha inicio (YYYY-MM-DD HH:MM:SS)
 * 
 * Respuesta:
 *   { success: true, data: [{idcompra, idbase, fechahora, numalbaran, nombre_operador, serie, idempresa, idproducto, precio, cantidad}] }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/litros/obtener_compras_desde.php" -d "productos[]=1&desde=2026-01-01 00:00:00"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $litros = new Litros($db, envInt('API_ID_EMPRESA', 1));

    $productos = toArray($_POST['productos'] ?? []);
    $desde = $_POST['desde'] ?? '';

    if (empty($productos)) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro productos requerido']);
        exit;
    }

    if (empty($desde)) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro desde requerido']);
        exit;
    }

    $data = $litros->obtenerComprasDesde($productos, $desde);

    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
