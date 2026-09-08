<?php
/**
 * Endpoint: Obtener precios de tarifas por base/cliente/producto
 * 
 * Consulta compleja que cruza: clientesbase, clientes, subtarifascategoriacaa,
 * productos, bases, comunidades, tarifascaa, grupostarifacaa y países.
 * 
 * Método: GET o POST
 * Parámetros (todos opcionales):
 *   - id_tarifa (int): Filtrar por ID de tarifa. Usar -1 para todas.
 *   - todos (int): 1 = traer todos los clientes, 0 = usar filtro 'clientes'
 *   - clientes (array|string): IDs de clientes (cuando todos=0). Acepta array o string separado por comas.
 *   - id_cliente (int): Filtrar por un cliente específico
 *   - fecha_limite (string): Fecha para filtrar vigencia de subtarifas (YYYY-MM-DD HH:MM:SS)
 *   - tipo_tarifa (string): 'semanal', 'diario' o null para todas
 * 
 * Respuesta:
 *   { success: true, data: [{id_cliente, nombre_cliente, pais_cliente, id_producto, nombre_base, zona, comunidad, id_base, id_tarifa, nombre_tarifa, grupo_tarifa, producto, precio, precio_adblue, inicio, final}] }
 * 
 * Ejemplo curl:
 *   curl "BASE_URL/api/tarifas/tarifas_bases_clientes_precios.php?id_tarifa=5&todos=1"
 *   curl -X POST "BASE_URL/api/tarifas/tarifas_bases_clientes_precios.php" -d "id_cliente=123&fecha_limite=2026-09-01"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $tarifas = new Tarifas($db);
    
    $params = [];
    if (!empty($_GET['id_tarifa']) || !empty($_POST['id_tarifa'])) {
        $params['id_tarifa'] = (int) ($_GET['id_tarifa'] ?? $_POST['id_tarifa']);
    }
    if (isset($_GET['todos']) || isset($_POST['todos'])) {
        $params['todos'] = (int) ($_GET['todos'] ?? $_POST['todos']);
    }
    if (!empty($_GET['clientes']) || !empty($_POST['clientes'])) {
        $clientes = $_GET['clientes'] ?? $_POST['clientes'];
        $params['clientes'] = is_array($clientes) ? array_map('intval', $clientes) : array_map('intval', explode(',', $clientes));
    }
    if (!empty($_GET['id_cliente']) || !empty($_POST['id_cliente'])) {
        $params['id_cliente'] = (int) ($_GET['id_cliente'] ?? $_POST['id_cliente']);
    }
    if (!empty($_GET['fecha_limite']) || !empty($_POST['fecha_limite'])) {
        $params['fecha_limite'] = $_GET['fecha_limite'] ?? $_POST['fecha_limite'];
    }
    if (!empty($_GET['tipo_tarifa']) || !empty($_POST['tipo_tarifa'])) {
        $params['tipo_tarifa'] = $_GET['tipo_tarifa'] ?? $_POST['tipo_tarifa'];
    }
    
    $data = $tarifas->tarifasBasesClientesPrecios($params);
    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
