<?php
/**
 * Endpoint: Obtener suministros desde fecha
 * 
 * Retorna suministros (cargas a tanques) desde una fecha específica.
 * Los suministros representan entradas de combustible a los tanques.
 * 
 * Método: POST
 * Parámetros:
 *   - productos (array, requerido): IDs de productos, ej: [1, 5, 6]
 *   - desde (string, requerido): Fecha inicio (YYYY-MM-DD HH:MM:SS)
 *   - hasta (string, opcional): Fecha fin (YYYY-MM-DD HH:MM:SS)
 * 
 * Respuesta:
 *   { success: true, data: [{idsuministro, idproducto, idtanque, idbase, cantidad, fechahoraini, fechahorafin, idempresa}] }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/litros/obtener_suministros_desde.php" -d "productos[]=1&desde=2026-09-01 00:00:00"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $litros = new Litros($db, envInt('API_ID_EMPRESA', 1));

    $productos = toArray($_POST['productos'] ?? []);
    $desde = $_POST['desde'] ?? '';
    $hasta = $_POST['hasta'] ?? null;

    if (empty($productos)) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro productos requerido']);
        exit;
    }

    if (empty($desde)) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro desde requerido']);
        exit;
    }

    $data = $litros->obtenerSuministrosDesde($productos, $desde, $hasta ?: null);

    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
