<?php
/**
 * Endpoint: Obtener tarifas CAA y clientes
 * 
 * Retorna listado de tarifas CAA activas y clientes activos.
 * Útil para poblar selectores en el front-end.
 * 
 * Método: POST (sin parámetros)
 * 
 * Respuesta:
 *   {
 *     success: true,
 *     data: {
 *       tarifascaa: [{id, descripcion}],
 *       clientes: [{id, nombre}]
 *     }
 *   }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/tarifas/obtener_tarifascaa_clientes.php"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $tarifas = new Tarifas($db);
    
    $data = [
        'tarifascaa' => $tarifas->obtenerTarifasCaa(),
        'clientes' => $tarifas->obtenerClientes(),
    ];
    
    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
