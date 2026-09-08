<?php
/**
 * Endpoint: Obtener facturas de clientes
 * 
 * Retorna listado de facturas con datos completos: cliente, contacto,
 * tipo/forma de pago, importes, vencimiento, status y país.
 * 
 * Filtra por clientes activos y facturables. Excluye tipos de pago
 * configurados en .env (API_FACTURA_TIPOPAGO_EXCLUIR).
 * 
 * Aplica reglas de serie según categorías del cliente:
 *   - Serie 'D': solo si NO tiene categoría prepago
 *   - Serie 'FRF'/'H': solo si NO tiene prepago NI contado
 * 
 * Método: POST
 * Parámetros (todos opcionales):
 *   - fecha_limite (string): Fecha mínima de factura (YYYY-MM-DD). Default: API_FACTURA_FECHA_MIN
 *   - id_factura (int): ID de factura para paginación (retorna facturas con id > este valor)
 * 
 * Respuesta:
 *   { success: true, data: [{id, fcreacion, femision, fvencimiento, cliente, importe, serie, num, tpago, fpago, ...}] }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/facturas/obtener_facturas_clientes.php" -d "fecha_limite=2026-01-01"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $config = [
        'cat_prepago' => envInt('API_CAT_PREPAGO', 3),
        'cat_credito' => envInt('API_CAT_CREDITO', 5),
        'cat_contado' => envInt('API_CAT_CONTADO', 10),
        'cat_bonificacion' => envInt('API_CAT_BONIFICACION', 51),
        'tipopago_excluir' => envArray('API_FACTURA_TIPOPAGO_EXCLUIR', [1, 4, 21, 22]),
        'fecha_min' => env('API_FACTURA_FECHA_MIN', '2019-09-23'),
    ];
    $facturas = new Facturas($db, $config);
    
    $fechaLimite = $_POST['fecha_limite'] ?? null;
    $idFactura = isset($_POST['id_factura']) ? (int) $_POST['id_factura'] : null;
    
    $data = $facturas->obtenerFacturasClientes($fechaLimite, $idFactura);
    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
