<?php
/**
 * Clase Litros - Queries para gestión de combustible y tanques
 * 
 * Proporciona métodos para consultar compras, ventas, suministros y stock
 * de productos petrolíferos agrupados por empresa, base y producto.
 * 
 * Todas las queries filtran por idempresa para aislar datos por compañía.
 */
class Litros {
    private PDO $db;
    private int $idEmpresa;

    /**
     * @param PDO $db Conexión a la base de datos
     * @param int $idEmpresa ID de la empresa (por defecto 1)
     */
    public function __construct(PDO $db, int $idEmpresa = 1) {
        $this->db = $db;
        $this->idEmpresa = $idEmpresa;
    }

    /**
     * Obtiene las últimas compras por base y producto.
     * 
     * Usa subconsulta para encontrar la fecha máxima de compra por cada
     * combinación base+producto, y retorna el detalle completo de esa compra.
     * 
     * @param array $productos IDs de productos, ej: [1, 5, 6]
     * @return array [{idcompra, idbase, fechahora, numalbaran, nombre_operador, serie, idempresa, idproducto, precio, cantidad}]
     */
    public function obtenerUltimasCompras(array $productos): array {
        $placeholders = implode(',', array_fill(0, count($productos), '?'));

        $sql = "SELECT comp.idcompra, comp.idbase, comp.fechahora, comp.numalbaran,
                       prov.nombre as nombre_operador, serie.serie, serie.idempresa,
                       lic.idproducto, lic.precio, lic.cantidad
                FROM COMPRAS comp
                LEFT JOIN LINEASCOMPRA lic ON comp.idcompra = lic.idcompra
                LEFT JOIN PROVEEDORES prov ON comp.idproveedor = prov.idproveedor
                LEFT JOIN SERIESCOMPRA serie ON comp.idcontador = serie.idcontador
                INNER JOIN (
                    SELECT lc.idproducto, c.idbase, MAX(c.fechahora) as ultima_fecha
                    FROM COMPRAS c
                    INNER JOIN LINEASCOMPRA lc ON c.idcompra = lc.idcompra
                    INNER JOIN SERIESCOMPRA sc ON c.idcontador = sc.idcontador
                    WHERE lc.idproducto IN ({$placeholders})
                    AND sc.idempresa = ?
                    GROUP BY lc.idproducto, c.idbase
                ) ultimas ON comp.idbase = ultimas.idbase
                           AND lic.idproducto = ultimas.idproducto
                           AND comp.fechahora = ultimas.ultima_fecha
                ORDER BY comp.fechahora DESC";

        $stmt = $this->db->prepare($sql);
        $params = array_merge($productos, [$this->idEmpresa]);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene compras desde una fecha específica.
     * 
     * @param array $productos IDs de productos, ej: [1, 5, 6]
     * @param string $desde Fecha inicio (YYYY-MM-DD HH:MM:SS)
     * @return array [{idcompra, idbase, fechahora, numalbaran, nombre_operador, serie, idempresa, idproducto, precio, cantidad}]
     */
    public function obtenerComprasDesde(array $productos, string $desde): array {
        $placeholders = implode(',', array_fill(0, count($productos), '?'));

        $sql = "SELECT comp.idcompra, comp.idbase, comp.fechahora, comp.numalbaran,
                       prov.nombre as nombre_operador, serie.serie, serie.idempresa,
                       lic.idproducto, lic.precio, lic.cantidad
                FROM COMPRAS comp
                LEFT JOIN LINEASCOMPRA lic ON comp.idcompra = lic.idcompra
                LEFT JOIN PROVEEDORES prov ON comp.idproveedor = prov.idproveedor
                LEFT JOIN SERIESCOMPRA serie ON comp.idcontador = serie.idcontador
                WHERE comp.fechahora >= ?
                AND lic.idproducto IN ({$placeholders})
                AND serie.idempresa = ?
                ORDER BY comp.fechahora ASC";

        $stmt = $this->db->prepare($sql);
        $params = array_merge([$desde], $productos, [$this->idEmpresa]);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas del día anterior agrupadas por base y producto.
     * 
     * Retorna la suma de litros vendidos por cada base/producto en la fecha indicada.
     * 
     * @param array $productos IDs de productos, ej: [1, 5, 6]
     * @param string $ayer Fecha del día anterior (YYYY-MM-DD)
     * @return array [{fecha, idbase, suma_cantidad, idproducto, idempresa}]
     */
    public function obtenerVentasAyer(array $productos, string $ayer): array {
        $placeholders = implode(',', array_fill(0, count($productos), '?'));

        $sql = "SELECT vent.fecha, vent.idbase, SUM(liv.cantidad) as suma_cantidad,
                       liv.idproducto, serie.idempresa
                FROM VENTAS vent
                LEFT JOIN LINEASVENTA liv ON vent.idventa = liv.idventa
                LEFT JOIN SERIESALBARAN serie ON vent.idseriealbaran = serie.idcontador
                WHERE liv.idproducto IN ({$placeholders})
                AND serie.idempresa = ?
                AND vent.fecha = ?
                GROUP BY vent.fecha, vent.idbase, liv.idproducto, serie.idempresa";

        $stmt = $this->db->prepare($sql);
        $params = array_merge($productos, [$this->idEmpresa, $ayer]);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas desde una fecha con filtros opcionales.
     * 
     * @param array $productos IDs de productos, ej: [1, 5, 6]
     * @param string $desde Fecha inicio (YYYY-MM-DD HH:MM:SS)
     * @param string|null $hasta Fecha fin opcional (YYYY-MM-DD HH:MM:SS)
     * @param int|null $idbase ID de base opcional para filtrar
     * @return array [{fechahora, idbase, idventa, litros, idproducto, idempresa, nsuministro}]
     */
    public function obtenerVentasDesde(array $productos, string $desde, ?string $hasta = null, ?int $idbase = null): array {
        $placeholders = implode(',', array_fill(0, count($productos), '?'));

        $sql = "SELECT vent.fechahora, vent.idbase, vent.idventa, liv.cantidad as litros,
                       liv.idproducto, serie.idempresa, liv.nsuministro
                FROM VENTAS vent
                LEFT JOIN LINEASVENTA liv ON vent.idventa = liv.idventa
                LEFT JOIN SERIESALBARAN serie ON vent.idseriealbaran = serie.idcontador
                WHERE liv.idproducto IN ({$placeholders})
                AND serie.idempresa = ?
                AND vent.fechahora > ?";

        $params = array_merge($productos, [$this->idEmpresa, $desde]);

        if ($hasta !== null) {
            $sql .= " AND vent.fechahora <= ?";
            $params[] = $hasta;
        }

        if ($idbase !== null) {
            $sql .= " AND vent.idbase = ?";
            $params[] = $idbase;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas con información de suministros (desde tanque).
     * 
     * Combina ventas con suministros para mostrar qué tanque alimentó cada venta.
     * Útil para rastrear el origen del combustible vendido.
     * 
     * @param array $productos IDs de productos, ej: [1, 5, 6]
     * @param string $desde Fecha inicio (YYYY-MM-DD HH:MM:SS)
     * @param string|null $hasta Fecha fin opcional
     * @param int|null $idbase ID de base opcional
     * @param int|null $idtanque ID de tanque opcional
     * @return array [{fechahora, idbase, idventa, idlineaventa, idproducto, litros, nsuministro, idsuministro, idtanque, cantidad_suministro, fechahoraini, fechahorafin, base_id}]
     */
    public function obtenerVentasConSuministros(array $productos, string $desde, ?string $hasta = null, ?int $idbase = null, ?int $idtanque = null): array {
        $placeholders = implode(',', array_fill(0, count($productos), '?'));

        $sql = "SELECT vent.fechahora, vent.idbase, vent.idventa, liv.idlineaventa,
                       liv.idproducto, liv.cantidad as litros, liv.nsuministro,
                       sumin.idsuministro, sumin.idtanque,
                       sumin.cantidad as cantidad_suministro,
                       sumin.fechahoraini, sumin.fechahorafin,
                       vent.idbase as base_id
                FROM VENTAS vent
                INNER JOIN LINEASVENTA liv ON vent.idventa = liv.idventa
                LEFT JOIN SUMINISTROS sumin ON liv.nsuministro = sumin.idsuministro
                WHERE liv.idproducto IN ({$placeholders})
                AND vent.fechahora > ?";

        $params = array_merge($productos, [$desde]);

        if ($hasta !== null) {
            $sql .= " AND vent.fechahora <= ?";
            $params[] = $hasta;
        }

        if ($idbase !== null) {
            $sql .= " AND vent.idbase = ?";
            $params[] = $idbase;
        }

        if ($idtanque !== null) {
            $sql .= " AND sumin.idtanque = ?";
            $params[] = $idtanque;
        }

        $sql .= " ORDER BY vent.fechahora ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene suministros (cargas a tanques) desde una fecha.
     * 
     * Los suministros representan las entradas de combustible a los tanques
     * (cargas desde camión cisterna u otras fuentes).
     * 
     * @param array $productos IDs de productos, ej: [1, 5, 6]
     * @param string $desde Fecha inicio (YYYY-MM-DD HH:MM:SS)
     * @param string|null $hasta Fecha fin opcional
     * @return array [{idsuministro, idproducto, idtanque, idbase, cantidad, fechahoraini, fechahorafin, idempresa}]
     */
    public function obtenerSuministrosDesde(array $productos, string $desde, ?string $hasta = null): array {
        $placeholders = implode(',', array_fill(0, count($productos), '?'));

        $sql = "SELECT sumin.idsuministro, sumin.idproducto, sumin.idtanque,
                       BASES.idbase, sumin.cantidad, sumin.fechahoraini,
                       sumin.fechahorafin, sumin.idempresa
                FROM SUMINISTROS sumin
                LEFT JOIN TANQUES ON sumin.idtanque = TANQUES.idtanque
                LEFT JOIN BASES ON TANQUES.idbase = BASES.idbase
                WHERE sumin.idproducto IN ({$placeholders})
                AND sumin.fechahorafin > ?
                AND sumin.idempresa = ?";

        $params = array_merge($productos, [$desde, $this->idEmpresa]);

        if ($hasta !== null) {
            $sql .= " AND sumin.fechahorafin <= ?";
            $params[] = $hasta;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene stock actual de tanques (tanques individuales).
     * 
     * Retorna cada tanque con sus existencias actuales, capacidad y ubicación.
     * No incluye alarmas (usar obtenerLitrosConsumidosCapacidad para vista con alarmas).
     * 
     * @param array $productos IDs de productos, ej: [1, 5, 6]
     * @return array [{idtanque, fechahoraultimalectura, existencias, numerotanque, descripcion, idproducto, idbase, activo, idempresa, nombre}]
     */
    public function obtenerStockTanques(array $productos): array {
        $placeholders = implode(',', array_fill(0, count($productos), '?'));

        $sql = "SELECT nivtanq.idtanque, nivtanq.fechahoraultimalectura, nivtanq.existencias,
                       tanq.numerotanque, tanq.descripcion, tanq.idproducto, tanq.idbase, tanq.activo,
                       bases.idempresa, bases.nombre
                FROM NIVELTANQUES nivtanq
                LEFT JOIN TANQUES tanq ON nivtanq.idtanque = tanq.idtanque
                LEFT JOIN BASES bases ON bases.idbase = tanq.idbase
                WHERE tanq.idproducto IN ({$placeholders})
                AND bases.idempresa = ?";

        $stmt = $this->db->prepare($sql);
        $params = array_merge($productos, [$this->idEmpresa]);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene litros consumidos, capacidad y alarmas agrupados por producto.
     * 
     * Retorna datos agregados por producto (sumas, promedios, contadores de alarmas)
     * más el desglose individual de cada tanque con su estado de alarma.
     * 
     * Estados de alarma por tanque:
     * - ROJO: existencias <= alarma2 (crítico)
     * - AMARILLO: alarma2 < existencias <= alarma1 (precaución)
     * - OK: existencias > alarma1 (normal)
     * 
     * @param array $productos IDs de productos, ej: [1, 5, 6]
     * @param int|null $idbase ID de base para filtrar (opcional)
     * @param int|null $activo Filtrar por tanques activos 1/0 (opcional)
     * @return array Array de productos con datos agregados y array 'tanques' anidado
     * @return array[].number idproducto
     * @return array[].string nombre_producto
     * @return array[].string ultima_actualizacion Fecha última lectura del sensor
     * @return array[].number total_existencias Suma de litros en todos los tanques
     * @return array[].number total_capacidad Suma de capacidad máxima
     * @return array[].number porcentaje_capacidad (total_existencias / total_capacidad) * 100
     * @return array[].number total_tanques Cantidad de tanques con ese producto
     * @return array[].number horas_desde_lectura Horas desde última lectura
     * @return array[].number min_existencias Tanque con menos stock
     * @return array[].number max_existencias Tanque con más stock
     * @return array[].number limite_alarma1_global Suma de alarma1 de todos los tanques
     * @return array[].number limite_alarma2_global Suma de alarma2 de todos los tanques
     * @return array[].number tanques_rojo Cantidad de tanques en estado ROJO
     * @return array[].number tanques_amarillo Cantidad de tanques en estado AMARILLO
     * @return array[].number tanques_ok Cantidad de tanques en estado OK
     * @return array[].array tanques Array con detalles de cada tanque individual
     */
    public function obtenerLitrosConsumidosCapacidad(array $productos, ?int $idbase = null, ?int $activo = null): array {
        $placeholders = implode(',', array_fill(0, count($productos), '?'));

        $sql = "SELECT tanq.idproducto,
                       prod.descripcion as nombre_producto,
                       MAX(nivtanq.fechahoraultimalectura) as ultima_actualizacion,
                       CAST(SUM(CAST(nivtanq.existencias AS DOUBLE PRECISION)) AS DOUBLE PRECISION) as total_existencias,
                       CAST(SUM(CAST(tanq.capacidad AS DOUBLE PRECISION)) AS DOUBLE PRECISION) as total_capacidad,
                       CASE WHEN SUM(CAST(tanq.capacidad AS DOUBLE PRECISION)) > 0 
                            THEN CAST(SUM(CAST(nivtanq.existencias AS DOUBLE PRECISION)) * 100.0 / SUM(CAST(tanq.capacidad AS DOUBLE PRECISION)) AS DOUBLE PRECISION)
                            ELSE 0 
                       END as porcentaje_capacidad,
                       COUNT(DISTINCT tanq.idtanque) as total_tanques,
                       bases.idempresa,
                       MIN(nivtanq.existencias) as min_existencias,
                       MAX(nivtanq.existencias) as max_existencias,
                       CAST(SUM(CAST(tanq.alarma1 AS DOUBLE PRECISION)) AS DOUBLE PRECISION) as limite_alarma1_global,
                       CAST(SUM(CAST(tanq.alarma2 AS DOUBLE PRECISION)) AS DOUBLE PRECISION) as limite_alarma2_global,
                       SUM(CASE WHEN nivtanq.existencias <= tanq.alarma2 THEN 1 ELSE 0 END) as tanques_rojo,
                       SUM(CASE WHEN nivtanq.existencias > tanq.alarma2 AND nivtanq.existencias <= tanq.alarma1 THEN 1 ELSE 0 END) as tanques_amarillo,
                       SUM(CASE WHEN nivtanq.existencias > tanq.alarma1 THEN 1 ELSE 0 END) as tanques_ok
                FROM NIVELTANQUES nivtanq
                LEFT JOIN TANQUES tanq ON nivtanq.idtanque = tanq.idtanque
                LEFT JOIN BASES bases ON bases.idbase = tanq.idbase
                LEFT JOIN PRODUCTOS prod ON prod.idproducto = tanq.idproducto
                WHERE bases.idempresa = ?
                AND tanq.idproducto IN ({$placeholders})";

        $params = array_merge([$this->idEmpresa], $productos);

        if ($idbase !== null) {
            $sql .= " AND tanq.idbase = ?";
            $params[] = $idbase;
        }

        if ($activo !== null) {
            $sql .= " AND tanq.activo = ?";
            $params[] = $activo;
        }

        $sql .= " GROUP BY tanq.idproducto, prod.descripcion, bases.idempresa";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlTanks = "SELECT nivtanq.idtanque, nivtanq.fechahoraultimalectura, nivtanq.existencias,
                            tanq.numerotanque, tanq.descripcion, tanq.idproducto, tanq.idbase,
                            tanq.capacidad, tanq.alarma1, tanq.alarma2,
                            bases.nombre as nombre_base,
                            CASE 
                              WHEN nivtanq.existencias <= tanq.alarma2 THEN 'ROJO'
                              WHEN nivtanq.existencias <= tanq.alarma1 THEN 'AMARILLO'
                              ELSE 'OK'
                            END as estado_alarma
                     FROM NIVELTANQUES nivtanq
                     LEFT JOIN TANQUES tanq ON nivtanq.idtanque = tanq.idtanque
                     LEFT JOIN BASES bases ON bases.idbase = tanq.idbase
                     WHERE bases.idempresa = ?
                     AND tanq.idproducto IN ({$placeholders})";

        $paramsTanks = array_merge([$this->idEmpresa], $productos);

        if ($idbase !== null) {
            $sqlTanks .= " AND tanq.idbase = ?";
            $paramsTanks[] = $idbase;
        }

        if ($activo !== null) {
            $sqlTanks .= " AND tanq.activo = ?";
            $paramsTanks[] = $activo;
        }

        $sqlTanks .= " ORDER BY tanq.idproducto, tanq.numerotanque";

        $stmtTanks = $this->db->prepare($sqlTanks);
        $stmtTanks->execute($paramsTanks);
        $allTanks = $stmtTanks->fetchAll(PDO::FETCH_ASSOC);

        $tanksByProduct = [];
        foreach ($allTanks as $tank) {
            $pid = $tank['IDPRODUCTO'];
            $tanksByProduct[$pid][] = [
                'idtanque' => $tank['IDTANQUE'],
                'numerotanque' => $tank['NUMEROTANQUE'],
                'descripcion' => $tank['DESCRIPCION'],
                'existencias' => $tank['EXISTENCIAS'],
                'capacidad' => $tank['CAPACIDAD'],
                'alarma1' => $tank['ALARMA1'],
                'alarma2' => $tank['ALARMA2'],
                'nombre_base' => $tank['NOMBRE_BASE'],
                'estado_alarma' => trim($tank['ESTADO_ALARMA']),
                'fechahoraultimalectura' => $tank['FECHAHORAULTIMALECTURA']
            ];
        }

        foreach ($results as &$row) {
            $pid = $row['IDPRODUCTO'];
            $row['horas_desde_lectura'] = $row['ULTIMA_ACTUALIZACION']
                ? round((time() - strtotime($row['ULTIMA_ACTUALIZACION'])) / 3600, 1)
                : null;
            $row['tanques'] = $tanksByProduct[$pid] ?? [];
        }

        return $results;
    }

    /**
     * Obtiene lista de tanques con filtros opcionales.
     * 
     * Retorna información básica de los tanques (sin stock ni alarmas).
     * 
     * @param int|null $idbase Filtrar por ID de base (opcional)
     * @param int|null $idproducto Filtrar por ID de producto (opcional)
     * @return array [{idtanque, descripcion, idbase, idproducto}]
     */
    public function obtenerTanques(?int $idbase = null, ?int $idproducto = null): array {
        $sql = "SELECT idtanque, descripcion, idbase, idproducto FROM TANQUES";
        $conditions = [];
        $params = [];

        if ($idbase !== null) {
            $conditions[] = "idbase = ?";
            $params[] = $idbase;
        }

        if ($idproducto !== null) {
            $conditions[] = "idproducto = ?";
            $params[] = $idproducto;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
