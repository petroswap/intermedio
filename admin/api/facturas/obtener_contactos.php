<?php
/**
 * Endpoint: Obtener contactos (comisionistas)
 * 
 * Retorna lista de personas que son comisionistas.
 * Útil para selectores de contacto en el front-end.
 * 
 * Método: POST (sin parámetros)
 * 
 * Respuesta:
 *   { success: true, data: [{idcontacto, contacto, es_vendedor, es_conductor, es_empleado, es_comisionista, es_expendedor, activo}] }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/facturas/obtener_contactos.php"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $facturas = new Facturas($db);
    $data = $facturas->obtenerContactos();
    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
