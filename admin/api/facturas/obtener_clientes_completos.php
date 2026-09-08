<?php
/**
 * Endpoint: Obtener clientes completos
 * 
 * Retorna clientes activos con vendedor asignado que tengan al menos
 * una de las categorías configuradas en API_CLIENTE_CATEGORIAS (default: 2,7,15).
 * 
 * Incluye flags de categorías: crédito, contado, prepago, riesgo, posicionamiento.
 * 
 * Método: POST (sin parámetros)
 * 
 * Respuesta:
 *   { success: true, data: [{id_cliente, nombre, emailfact, id_pais, idstatus, tiene_categoria_credito, tiene_categoria_contado, tiene_categoria_prepago, tiene_categoria_riesgo, tiene_categoria_posicionamiento, categorias_ids, codigocliente}] }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/facturas/obtener_clientes_completos.php"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $config = [
        'cliente_categorias' => envArray('API_CLIENTE_CATEGORIAS', [2, 7, 15]),
    ];
    $facturas = new Facturas($db, $config);
    $data = $facturas->obtenerClientesCompletos();
    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
