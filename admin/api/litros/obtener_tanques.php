<?php
/**
 * Endpoint: Obtener lista de tanques
 * 
 * Retorna información básica de los tanques (sin stock ni alarmas).
 * 
 * Método: POST
 * Parámetros (todos opcionales):
 *   - idbase (int): Filtrar por ID de base
 *   - idproducto (int): Filtrar por ID de producto
 * 
 * Si no se envían parámetros, retorna todos los tanques.
 * 
 * Respuesta:
 *   { success: true, data: [{idtanque, descripcion, idbase, idproducto}] }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/litros/obtener_tanques.php" -d "idbase=1"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $litros = new Litros($db, envInt('API_ID_EMPRESA', 1));

    $idbase = isset($_POST['idbase']) ? (int) $_POST['idbase'] : null;
    $idproducto = isset($_POST['idproducto']) ? (int) $_POST['idproducto'] : null;

    $data = $litros->obtenerTanques($idbase, $idproducto);

    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
