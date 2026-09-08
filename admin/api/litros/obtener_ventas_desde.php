<?php
/**
 * Endpoint: Obtener ventas desde fecha
 * 
 * Retorna ventas desde una fecha específica con filtros opcionales.
 * 
 * Método: POST
 * Parámetros:
 *   - productos (array, requerido): IDs de productos, ej: [1, 5, 6]
 *   - desde (string, requerido): Fecha inicio (YYYY-MM-DD HH:MM:SS)
 *   - hasta (string, opcional): Fecha fin (YYYY-MM-DD HH:MM:SS)
 *   - idbase (int, opcional): ID de base para filtrar
 * 
 * Respuesta:
 *   { success: true, data: [{fechahora, idbase, idventa, litros, idproducto, idempresa, nsuministro}] }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/litros/obtener_ventas_desde.php" -d "productos[]=1&desde=2026-09-01 00:00:00&idbase=1"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $litros = new Litros($db, envInt('API_ID_EMPRESA', 1));

    $productos = toArray($_POST['productos'] ?? []);
    $desde = $_POST['desde'] ?? '';
    $hasta = $_POST['hasta'] ?? null;
    $idbase = isset($_POST['idbase']) ? (int) $_POST['idbase'] : null;

    if (empty($productos)) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro productos requerido']);
        exit;
    }

    if (empty($desde)) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro desde requerido']);
        exit;
    }

    $data = $litros->obtenerVentasDesde($productos, $desde, $hasta ?: null, $idbase);

    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
