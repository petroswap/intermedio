<?php
/**
 * Endpoint: Obtener litros consumidos, capacidad y alarmas
 * 
 * Retorna datos agregados por producto (sumas, promedios, contadores de alarmas)
 * más el desglose individual de cada tanque con su estado de alarma.
 * 
 * Esta es la principal endpoint para el panel de monitoreo de stock.
 * Combina la información de NIVELTANQUES + TANQUES + BASES + PRODUCTOS.
 * 
 * Estados de alarma por tanque:
 *   - ROJO: existencias <= alarma2 (crítico)
 *   - AMARILLO: alarma2 < existencias <= alarma1 (precaución)
 *   - OK: existencias > alarma1 (normal)
 * 
 * Método: POST
 * Parámetros:
 *   - productos (array, requerido): IDs de productos, ej: [1, 5, 6]
 *   - idbase (int, opcional): Filtrar por ID de base
 *   - activo (int, opcional): Filtrar por tanques activos (1) o inactivos (0)
 * 
 * Respuesta:
 *   {
 *     success: true,
 *     data: [{
 *       IDPRODUCTO: 1,
 *       NOMBRE_PRODUCTO: "Gasoleo A",
 *       ULTIMA_ACTUALIZACION: "2026-09-07 12:13:09",
 *       TOTAL_EXISTENCIAS: 32500,
 *       TOTAL_CAPACIDAD: 110000,
 *       PORCENTAJE_CAPACIDAD: 29.55,
 *       TOTAL_TANQUES: 4,
 *       MIN_EXISTENCIAS: 1500,
 *       MAX_EXISTENCIAS: 18000,
 *       LIMITE_ALARMA1_GLOBAL: 20000,
 *       LIMITE_ALARMA2_GLOBAL: 8000,
 *       TANQUES_ROJO: 1,
 *       TANQUES_AMARILLO: 1,
 *       TANQUES_OK: 2,
 *       horas_desde_lectura: 1.8,
 *       tanques: [{
 *         idtanque: 601,
 *         numerotanque: "T1-M",
 *         descripcion: "Tanque 1 Gasoleo A Madrid",
 *         existencias: 1500,
 *         capacidad: 30000,
 *         alarma1: 5000,
 *         alarma2: 2000,
 *         nombre_base: "Base Central Madrid",
 *         estado_alarma: "ROJO",
 *         fechahoraultimalectura: "2026-09-07 12:13:09"
 *       }]
 *     }]
 *   }
 * 
 * Ejemplo curl:
 *   curl -X POST "BASE_URL/api/litros/obtener_litros_consumidos_capacidad.php" -d "productos[]=1&productos[]=5"
 */
require_once __DIR__ . '/../init.php';

try {
    $db = getDb();
    $litros = new Litros($db, envInt('API_ID_EMPRESA', 1));

    $productos = toArray($_POST['productos'] ?? []);
    $idbase = isset($_POST['idbase']) ? intval($_POST['idbase']) : null;
    $activo = isset($_POST['activo']) ? intval($_POST['activo']) : null;

    if (empty($productos)) {
        echo json_encode(['success' => false, 'msg' => 'Parámetro productos requerido']);
        exit;
    }

    $data = $litros->obtenerLitrosConsumidosCapacidad($productos, $idbase, $activo);

    echo json_encode(['success' => true, 'data' => $data, 'msg' => 'OK']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
