<?php
/**
 * Clase Tarifas - Queries para gestión de tarifas CAA (Convenio de Abastecimiento)
 * 
 * Proporciona métodos para consultar tarifas, clientes y precios
 * asociados a bases de suministro.
 */
class Tarifas {
    private PDO $db;

    /**
     * @param PDO $db Conexión a la base de datos
     */
    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Obtiene tarifas CAA activas con grupo asignado.
     * 
     * @return array [{id, descripcion}]
     */
    public function obtenerTarifasCaa(): array {
        $sql = "SELECT idtarifacaa as id, descripcion
                FROM tarifascaa
                WHERE activa = 1
                AND idgrupotarifacaa IS NOT NULL";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene lista de clientes activos.
     * 
     * @return array [{id, nombre}]
     */
    public function obtenerClientes(): array {
        $sql = "SELECT idcliente as id, nombre
                FROM clientes
                WHERE activo = 1";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene precios de tarifas por base, cliente y producto.
     * 
     * Consulta compleja que cruza: clientesbase, clientes, subtarifascategoriacaa,
     * productos, bases, comunidades, tarifascaa, grupostarifacaa y países.
     * 
     * Filtros disponibles:
     * - id_tarifa (int): Filtrar por ID de tarifa específica. Usar -1 para todas.
     * - todos (int): 1 = traer todos los clientes, 0 = usar filtro 'clientes'
     * - clientes (array): IDs de clientes a filtrar (cuando todos=0)
     * - id_cliente (int): Filtrar por un cliente específico
     * - fecha_limite (string): Fecha para filtrar vigencia de subtarifas (YYYY-MM-DD HH:MM:SS)
     * - tipo_tarifa (string): 'semanal', 'diario' o null para todas
     * 
     * @param array $params Parámetros de filtro
     * @return array [{id_cliente, nombre_cliente, email_cliente, cliente_activo, pais_cliente, id_producto, nombre_base, zona, comunidad, id_base, id_tarifa, tarifa_activa, nombre_tarifa, grupo_tarifa, producto, precio, precio_adblue, inicio, final}]
     */
    public function tarifasBasesClientesPrecios(array $params = []): array {
        $sql = "SELECT cli.idcliente as id_cliente, cli.nombre as nombre_cliente,
                       cli.email as email_cliente, cli.activo as cliente_activo,
                       TRIM(p.idpaisstr) as pais_cliente, pro.idproducto as id_producto,
                       b.nombre as nombre_base, b.provincia as zona,
                       com.descripcion as comunidad, cli_base.idbase as id_base,
                       cli_base.idtarifacaa as id_tarifa, tcaa.activa as tarifa_activa,
                       tcaa.descripcion as nombre_tarifa, gtcaa.descripcion as grupo_tarifa,
                       pro.concepto as producto,
                       stcaa.preciofijo as precio, cli_base.precioadblue as precio_adblue,
                       stcaa.fechahorainicial as inicio, stcaa.fechahorafinal as final
                FROM clientesbase cli_base
                JOIN (SELECT * FROM clientes WHERE activo = 1) cli ON cli.idcliente = cli_base.idcliente";

        if (!empty($params['fecha_limite'])) {
            $fechaLimite = date("Y-m-d H:i:s", strtotime($params['fecha_limite']));
            // NOTA: $fechaLimite se interpone directamente en SQL (riesgo teórico de SQL injection).
            // date() + strtotime() sanitizan el valor, pero lo ideal sería parameterizar con ?.
            // Si se quiere hardcodear: usar prepare() con placeholders en el JOIN subquery.
            $sql .= " JOIN (SELECT * FROM subtarifascategoriacaa WHERE fechahorainicial <= '{$fechaLimite}' AND fechahorafinal >= '{$fechaLimite}' AND idcategoria = 1) stcaa ON cli_base.idtarifacaa = stcaa.idtarifacaa";
        } else {
            $sql .= " JOIN (SELECT * FROM subtarifascategoriacaa WHERE idcategoria = 1) stcaa ON cli_base.idtarifacaa = stcaa.idtarifacaa";
        }

        $sql .= " JOIN productos pro ON pro.idproducto = stcaa.idcategoria
                JOIN bases b ON b.idbase = cli_base.idbase
                JOIN comunidades com ON com.idcomunidad = b.idcomunidad";

        if (!empty($params['tipo_tarifa'])) {
            switch ($params['tipo_tarifa']) {
                case 'semanal':
                    $sql .= " JOIN (SELECT * FROM tarifascaa WHERE activa = 1 AND (descripcion NOT LIKE '%diario%' AND descripcion NOT LIKE '%Diario%')) tcaa ON cli_base.idtarifacaa = tcaa.idtarifacaa";
                    break;
                case 'diario':
                    $sql .= " JOIN (SELECT * FROM tarifascaa WHERE activa = 1 AND (descripcion LIKE '%diario%' OR descripcion LIKE '%Diario%')) tcaa ON cli_base.idtarifacaa = tcaa.idtarifacaa";
                    break;
                default:
                    $sql .= " JOIN (SELECT * FROM tarifascaa WHERE activa = 1) tcaa ON cli_base.idtarifacaa = tcaa.idtarifacaa";
            }
        } else {
            $sql .= " JOIN (SELECT * FROM tarifascaa WHERE activa = 1) tcaa ON cli_base.idtarifacaa = tcaa.idtarifacaa";
        }

        $sql .= " LEFT JOIN grupostarifacaa gtcaa ON tcaa.idgrupotarifacaa = gtcaa.idgrupotarifacaa
                LEFT JOIN paises p ON p.idpais = cli.pais";

        $conditions = [];
        $queryParams = [];

        if (!empty($params['id_tarifa']) && $params['id_tarifa'] != -1) {
            $conditions[] = "cli_base.idtarifacaa = ?";
            $queryParams[] = $params['id_tarifa'];
        }

        if (isset($params['todos']) && $params['todos'] == 0 && !empty($params['clientes'])) {
            $clientesPlaceholders = implode(',', array_fill(0, count($params['clientes']), '?'));
            $conditions[] = "cli_base.idcliente IN ({$clientesPlaceholders})";
            $queryParams = array_merge($queryParams, $params['clientes']);
        }

        if (!empty($params['id_cliente']) && $params['id_cliente'] != 0) {
            $conditions[] = "cli_base.idcliente = ?";
            $queryParams[] = $params['id_cliente'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY cli.idcliente ASC, bases.idbase ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($queryParams);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
