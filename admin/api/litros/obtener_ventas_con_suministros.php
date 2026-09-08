<?php
/**
 * Endpoint: Obtener ventas con suministros
 * 
 * Retorna ventas combinadas con información de suministros (desde tanque).
 * Permite rastrear qué tanque alimentó cada venta.
 * 
 * Método: POST
 * Parámetros:
 *   - productos (array, requerido): IDs de productos, ej: [1, 5, 6]
 *   - desde (string, requerido): Fecha inicio (YYYY-MM-DD HH:MM:SS)
 *   - hasta (string, opcional): Fecha fin (YYYY-MM-DD HH:MM:SS)
 *   - idbase (int, opcional): ID de base para filtrar
 *   - idtanque (int, opcional): ID de tanque para filtrar
 * 
 * Respuesta:
 *   { success: true, data: [{fechahora, idbase, idventa, idlineaventa, idproducto, litros, nsuministro, idsuministro, idtanque, cantidad_suministro, fechahoraini, fechahorafin, base_id}] }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/litros/obtener_ventas_con_suministros.php" -d "productos[]=1&desde=2026-09-01 00:00:00"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $litros = new Litros($db, envInt('API_ID_EMPRESA', 1));

    $productos = toArray($_POST['productos'] ?? []);
    $desde = $_POST['desde'] ?? '';
    $hasta = $_POST['hasta'] ?? null;
    $idbase = isset($_POST['idbase']) ? (int) $_POST['idbase'] : null;
    $idtanque = isset($_POST['idtanque']) ? (int) $_POST['idtanque'] : null;

    if (empty($productos)) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro productos requerido']);
        exit;
    }

    if (empty($desde)) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro desde requerido']);
        exit;
    }

    $data = $litros->obtenerVentasConSuministros($productos, $desde, $hasta ?: null, $idbase, $idtanque);

    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
