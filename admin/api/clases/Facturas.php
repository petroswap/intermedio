<?php
/**
 * Clase Facturas - Queries para gestión de facturación
 * 
 * Proporciona métodos para consultar facturas, clientes, contactos
 * y estados de cuenta. Incluye lógica de filtrado por categorías
 * de cliente (prepago, crédito, contado) y series de factura.
 * 
 * Configuración requerida (desde .env):
 * - API_CAT_PREPAGO: ID categoría prepago (default: 3)
 * - API_CAT_CREDITO: ID categoría crédito (default: 5)
 * - API_CAT_CONTADO: ID categoría contado (default: 10)
 * - API_CAT_BONIFICACION: ID tipo pago bonificación (default: 51)
 * - API_FACTURA_TIPOPAGO_EXCLUIR: Tipos de pago a excluir (default: 1,4,21,22)
 * - API_FACTURA_FECHA_MIN: Fecha mínima de consulta (default: 2019-09-23)
 */
class Facturas {
    private PDO $db;
    private array $config;

    /**
     * @param PDO $db Conexión a la base de datos
     * @param array $config Configuración de categorías y filtros
     */
    public function __construct(PDO $db, array $config = []) {
        $this->db = $db;
        $this->config = array_merge([
            'cat_prepago' => 3,
            'cat_credito' => 5,
            'cat_contado' => 10,
            'cat_bonificacion' => 51,
            'tipopago_excluir' => [1, 4, 21, 22],
            'series_validas' => ['C', 'N', 'P', 'R', 'D', 'H', 'FRF'],
            'fecha_min' => '2019-09-23',
        ], $config);
    }

    /**
     * Obtiene listado de facturas de clientes con datos completos.
     * 
     * Incluye: cliente, contacto, tipo/forma de pago, importes, vencimiento,
     * status y país. Filtra por clientes activos y facturables.
     * 
     * Excluye tipos de pago configurados en tipopago_excluir.
     * Aplica reglas de serie según categorías del cliente:
     * - Serie 'D': solo si NO tiene categoría prepago
     * - Serie 'FRF'/'H': solo si NO tiene prepago NI contado
     * 
     * @param string|null $fechaLimite Fecha mínima de factura (YYYY-MM-DD). Si es null, usa fecha_min config.
     * @param int|null $idFactura ID de factura para paginación (retorna facturas con id > este valor)
     * @return array [{id, fcreacion, femision, fvencimiento, idcontacto, contacto, es_vendedor, es_conductor, es_empleado, es_comisionista, es_expendedor, contacto_activo, concepto, idejercicio, num, serie, plantilla, cliente, idcliente, cif, tpago, fpago, npagos, pago1, pago2, diapago1, diapago2, importe, importe_bonificaciones, status_id, status_concepto, status_descripcion, id_pais, pais_str, pais_descripcion}]
     */
    public function obtenerFacturasClientes(?string $fechaLimite = null, ?int $idFactura = null): array {
        $catPrepago = $this->config['cat_prepago'];
        $catContado = $this->config['cat_contado'];
        $tipopagoExcluir = implode(',', $this->config['tipopago_excluir']);
        $fechaMin = $this->config['fecha_min'];

        $sql = "SELECT fact.idfactura as id,
                       CAST(fact.fecha AS DATE) as fcreacion,
                       CAST(IIF(fact.periodofactfin IS NULL, fact.fecha, fact.periodofactfin) AS DATE) as femision,
                       CAST(dateadd({$catPrepago} day TO IIF(fact.periodofactfin IS NULL, fact.fecha, fact.periodofactfin)) AS DATE) as fvencimiento,
                       contacto.idpersona as idcontacto, contacto.nombre as contacto,
                       contacto.vendedor as es_vendedor, contacto.conductor as es_conductor,
                       contacto.empleado as es_empleado, contacto.comisionista as es_comisionista,
                       contacto.expendedor as es_expendedor, contacto.activo as contacto_activo,
                       sefact.concepto, sefact.idejercicio, fact.numfactura as num,
                       sefact.serie, sefact.plantilla,
                       cli.nombre as cliente, cli.idcliente as idcliente, cli.nif as cif,
                       tp.descripcion as tpago, fp.descripcion as fpago,
                       fp.npagos, fp.primerpago as pago1, fp.siguientespagos as pago2,
                       fp.diapago1, fp.diapago2,
                       rec.importe_total as importe,
                       recibos_bonificacion.importe_total as importe_bonificaciones,
                       stcli.idstatus as status_id, stcli.concepto as status_concepto,
                       stcli.descripcion as status_descripcion,
                       paises.idpais as id_pais, paises.idpaisstr as pais_str,
                       paises.descripcion as pais_descripcion
                FROM FACTURAS fact
                LEFT JOIN CLIENTES cli ON fact.idcliente = cli.idcliente
                LEFT JOIN (
                    SELECT cli2.idcliente, SUM(IIF(cat.idcategoria = {$catPrepago}, 1, 0)) as tiene_categoria_prepago,
                           SUM(IIF(cat.idcategoria = {$catContado}, 1, 0)) as tiene_categoria_contado,
                           SUM(IIF(cat.idcategoria = {$this->config['cat_credito']}, 1, 0)) as tiene_categoria_credito
                    FROM clientes cli2
                    LEFT JOIN categoriascli catcli ON catcli.idcliente = cli2.idcliente
                    LEFT JOIN categorias cat ON cat.idcategoria = catcli.idcategoria
                    GROUP BY cli2.idcliente
                ) cat_cli ON cat_cli.idcliente = cli.idcliente
                LEFT JOIN PERSONAS contacto ON contacto.idpersona = cli.idvendedor
                LEFT JOIN SERIESFACTURA sefact ON sefact.idcontador = fact.idcontador
                LEFT JOIN TIPOSPAGO tp ON tp.idtipopago = fact.idtipopago
                LEFT JOIN FORMASPAGO fp ON fp.idformapago = fact.idformapago
                LEFT JOIN (SELECT idfactura as id, SUM(importetotal) as importe_total FROM RECIBOS GROUP BY idfactura) rec ON rec.id = fact.idfactura
                LEFT JOIN (SELECT idfactura as id, SUM(importetotal) as importe_total FROM (SELECT DISTINCT idfactura, importetotal, fechavencimiento FROM RECIBOS WHERE idtipopago = {$this->config['cat_bonificacion']}) rb GROUP BY idfactura) recibos_bonificacion ON recibos_bonificacion.id = fact.idfactura
                LEFT JOIN STATUSCLIENTE stcli ON stcli.idstatus = cli.idstatus
                LEFT JOIN PAISES paises ON paises.idpais = cli.pais
                WHERE cli.activo = 1
                AND cli.facturable = 1
                AND fact.idtipopago NOT IN ({$tipopagoExcluir})
                AND ((rec.importe_total IS NOT NULL AND rec.importe_total > 0) OR (rec.importe_total IS NOT NULL AND rec.importe_total < 0 AND sefact.serie = 'FRF'))
                AND (sefact.serie IN ('C','N','P','R') OR (sefact.serie = 'D' AND cat_cli.tiene_categoria_prepago = 0) OR ((sefact.serie = 'FRF' OR sefact.serie = 'H') AND (cat_cli.tiene_categoria_prepago = 0 AND cat_cli.tiene_categoria_contado = 0)))";

        $params = [];

        if ($idFactura !== null && $idFactura > 0) {
            $sql .= " AND fact.idfactura > ?";
            $params[] = $idFactura;
        } elseif ($fechaLimite !== null && $fechaLimite !== '') {
            $sql .= " AND fact.fecha >= ?";
            $params[] = $fechaLimite;
        } else {
            $sql .= " AND fact.fecha >= ?";
            $params[] = $fechaMin;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una factura específica por su ID.
     * 
     * Misma estructura que obtenerFacturasClientes pero para una sola factura.
     * 
     * @param int $idFactura ID de la factura a consultar
     * @return array|null Array con datos de la factura o null si no existe
     */
    public function obtenerFactura(int $idFactura): ?array {
        $catPrepago = $this->config['cat_prepago'];

        $sql = "SELECT fact.idfactura as id,
                       CAST(fact.fecha AS DATE) as fcreacion,
                       CAST(IIF(fact.periodofactfin IS NULL, fact.fecha, fact.periodofactfin) AS DATE) as femision,
                       CAST(dateadd({$catPrepago} day TO IIF(fact.periodofactfin IS NULL, fact.fecha, fact.periodofactfin)) AS DATE) as fvencimiento,
                       contacto.idpersona as idcontacto, contacto.nombre as contacto,
                       contacto.vendedor as es_vendedor, contacto.conductor as es_conductor,
                       contacto.empleado as es_empleado, contacto.comisionista as es_comisionista,
                       contacto.expendedor as es_expendedor, contacto.activo as contacto_activo,
                       sefact.concepto, sefact.idejercicio, fact.numfactura as num,
                       sefact.serie, sefact.plantilla,
                       cli.nombre as cliente, cli.idcliente as idcliente, cli.nif as cif,
                       tp.descripcion as tpago, fp.descripcion as fpago,
                       fp.npagos, fp.primerpago as pago1, fp.siguientespagos as pago2,
                       fp.diapago1, fp.diapago2,
                       rec.importe_total as importe_total,
                       recibos_bonificacion.importe_total as importe_bonificaciones,
                       stcli.idstatus as status_id, stcli.concepto as status_concepto,
                       stcli.descripcion as status_descripcion,
                       paises.idpais as id_pais, paises.idpaisstr as pais_str,
                       paises.descripcion as pais_descripcion
                FROM FACTURAS fact
                LEFT JOIN CLIENTES cli ON fact.idcliente = cli.idcliente
                LEFT JOIN (
                    SELECT cli2.idcliente, SUM(IIF(cat.idcategoria = {$catPrepago}, 1, 0)) as tiene_categoria_prepago
                    FROM clientes cli2
                    LEFT JOIN categoriascli catcli ON catcli.idcliente = cli2.idcliente
                    LEFT JOIN categorias cat ON cat.idcategoria = catcli.idcategoria
                    GROUP BY cli2.idcliente
                ) cat_cli ON cat_cli.idcliente = cli.idcliente
                LEFT JOIN PERSONAS contacto ON contacto.idpersona = cli.idvendedor
                LEFT JOIN SERIESFACTURA sefact ON sefact.idcontador = fact.idcontador
                LEFT JOIN TIPOSPAGO tp ON tp.idtipopago = fact.idtipopago
                LEFT JOIN FORMASPAGO fp ON fp.idformapago = fact.idformapago
                LEFT JOIN (SELECT idfactura as id, SUM(importetotal) as importe_total FROM RECIBOS GROUP BY idfactura) rec ON rec.id = fact.idfactura
                LEFT JOIN (SELECT idfactura as id, SUM(importetotal) as importe_total FROM (SELECT DISTINCT idfactura, importetotal, fechavencimiento FROM RECIBOS WHERE idtipopago = {$this->config['cat_bonificacion']}) rb GROUP BY idfactura) recibos_bonificacion ON recibos_bonificacion.id = fact.idfactura
                LEFT JOIN STATUSCLIENTE stcli ON stcli.idstatus = cli.idstatus
                LEFT JOIN PAISES paises ON paises.idpais = cli.pais
                WHERE fact.idfactura = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idFactura]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene lista de contactos que son comisionistas.
     * 
     * @return array [{idcontacto, contacto, es_vendedor, es_conductor, es_empleado, es_comisionista, es_expendedor, activo}]
     */
    public function obtenerContactos(): array {
        $sql = "SELECT idpersona as idcontacto, nombre as contacto,
                       vendedor as es_vendedor, conductor as es_conductor,
                       empleado as es_empleado, comisionista as es_comisionista,
                       expendedor as es_expendedor, activo
                FROM PERSONAS
                WHERE comisionista = 1";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene clientes completos con sus categorías.
     * 
     * Retorna clientes activos con vendedor asignado que tengan al menos
     * una de las categorías configuradas (fueltruck, ruta, etc.).
     * 
     * Incluye flags de categorías: crédito, contado, prepago, riesgo, posicionamiento.
     * 
     * @return array [{id_cliente, nombre, emailfact, id_pais, idstatus, tiene_categoria_credito, tiene_categoria_contado, tiene_categoria_prepago, tiene_categoria_riesgo, tiene_categoria_posicionamiento, categorias_ids, codigocliente}]
     */
    public function obtenerClientesCompletos(): array {
        $cats = $this->config['cliente_categorias'] ?? [2, 7, 15];
        $catPlaceholder = implode(',', $cats);

        $sql = "SELECT TRIM(cli.idcliente) as id_cliente, cli.nombre, cli.emailfact,
                       TRIM(cli.pais) as id_pais, cli.idstatus,
                       SUM(IIF(todas_cat.idcategoria = 5, 1, 0)) as tiene_categoria_credito,
                       SUM(IIF(todas_cat.idcategoria = 10, 1, 0)) as tiene_categoria_contado,
                       SUM(IIF(todas_cat.idcategoria = 3, 1, 0)) as tiene_categoria_prepago,
                       SUM(IIF(todas_cat.idcategoria = 4, 1, 0)) as tiene_categoria_riesgo,
                       MAX(IIF(todas_cat.idcategoria IN (2, 12), 1, 0)) as tiene_categoria_posicionamiento,
                       LIST(todas_cat.idcategoria, ',') as categorias_ids,
                       cli.codigocliente as codigocliente
                FROM CLIENTES cli
                LEFT JOIN CATEGORIASCLI catcli_todas ON cli.idcliente = catcli_todas.idcliente
                LEFT JOIN CATEGORIAS todas_cat ON catcli_todas.idcategoria = todas_cat.idcategoria
                WHERE cli.activo = 1
                AND cli.idvendedor IS NOT NULL
                AND EXISTS (
                    SELECT 1 FROM CATEGORIASCLI cf
                    WHERE cf.idcliente = cli.idcliente
                    AND cf.idcategoria IN ({$catPlaceholder})
                )
                GROUP BY TRIM(cli.idcliente), cli.nombre, TRIM(cli.pais), cli.idstatus, cli.emailfact, cli.codigocliente
                ORDER BY cli.nombre";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
