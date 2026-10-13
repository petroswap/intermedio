<?php
/**
 * ============================================
 * SQLERROR - Clasificación específica de errores SQL
 * ============================================
 *
 * Convierte una excepción de PDO/Firebird en un error tipado:
 *   type    -> slug del error (syntax, table_not_found, ...)
 *   label   -> nombre humano del error (español)
 *   hint    -> sugerencia concreta de cómo resolverlo
 *   code    -> SQLCODE de Firebird (-104, -204, ...)
 *   sqlstate-> SQLSTATE del driver (HY000, 42000, ...)
 *   detail  -> mensaje original del driver (limpio)
 *   line    -> línea aproximada del error (si el driver la indica)
 *   column  -> columna aproximada del error
 */

class SqlError
{
    /**
     * Tipo => [etiqueta, sugerencia]
     */
    private const TYPES = [
        'validation' => [
            'Consulta no permitida',
            'Solo se permiten: SELECT, WITH, SET TRANSACTION READ ONLY y ROLLBACK. Quita la sentencia que modifica datos (INSERT, UPDATE, DELETE, DDL, etc.).',
        ],
        'invalid_input' => [
            'Parámetro no válido',
            'Uno de los parámetros enviados (tabla, columna, filtro o límite) no es válido. Revisa la petición.',
        ],
        'connection' => [
            'Error de conexión a la base de datos',
            'No se pudo conectar con Firebird: comprueba que el servidor esté activo y que DB_SERVER, DB_PATH y DB_PORT en .env sean correctos.',
        ],
        'auth' => [
            'Error de autenticación',
            'Usuario o contraseña de la base de datos incorrectos. Revisa DB_USER y DB_PASSWORD en .env.',
        ],
        'permission' => [
            'Sin permisos en la base de datos',
            'El usuario conectado no tiene privilegios sobre ese objeto. Pide al administrador que conceda el permiso SELECT.',
        ],
        'syntax' => [
            'Error de sintaxis SQL',
            'La consulta no está bien formada: revisa la línea y columna indicadas (coma, paréntesis sin cerrar, palabra reservada mal escrita o fin de sentencia incompleto).',
        ],
        'table_not_found' => [
            'Tabla o vista no encontrada',
            'El objeto indicado no existe en la base de datos. Verifica el nombre (Firebird distingue mayúsculas si va entre comillas dobles).',
        ],
        'column_not_found' => [
            'Columna no encontrada',
            'La columna no existe en la tabla o alias indicado. Comprueba los nombres en el panel de columnas del Inspector.',
        ],
        'ambiguous_column' => [
            'Columna ambigua',
            'La columna aparece en varias tablas de la consulta. Califícala con su alias (ejemplo: T.COLUMNA).',
        ],
        'routine_not_found' => [
            'Función o procedimiento no encontrado',
            'No existe la función/procedimiento invocado o no coincide el número o tipo de parámetros.',
        ],
        'aggregate_group' => [
            'Error de agrupación (GROUP BY)',
            'Toda columna del SELECT que no esté dentro de una función de agregado debe estar en GROUP BY; para filtrar agregados usa HAVING.',
        ],
        'order_by' => [
            'Error en ORDER BY',
            'La cláusula ORDER BY no es válida: revisa columnas/posiciones y evita opciones de orden contradictorias para la misma columna.',
        ],
        'join' => [
            'Error en la unión de tablas (JOIN)',
            'Revisa los alias: una tabla aparece repetida sin alias, o una tabla referenciada no está en el FROM.',
        ],
        'arithmetic' => [
            'Error aritmético',
            'División por cero o desbordamiento numérico. Revisa las operaciones matemáticas de la consulta.',
        ],
        'datatype' => [
            'Error de tipo de dato',
            'Conversión o comparación de tipos no permitida (CAST, fechas, cadenas). Ajusta los tipos o usa un CAST explícito.',
        ],
        'unique_violation' => [
            'Clave duplicada',
            'Ya existe un valor igual en un índice único o clave primaria.',
        ],
        'not_null' => [
            'Violación de NOT NULL',
            'La columna no admite valores nulos y se intentó guardar NULL.',
        ],
        'foreign_key' => [
            'Violación de clave externa (FK)',
            'El registro referenciado no existe o está siendo referenciado por otro registro.',
        ],
        'check_constraint' => [
            'Violación de una restricción CHECK',
            'El valor no cumple la condición CHECK definida en la columna o tabla.',
        ],
        'validation_rule' => [
            'Violación de regla de validación',
            'El valor no pasa la regla de validación del dominio o de la columna.',
        ],
        'singleton' => [
            'Selección singleton con varios resultados',
            'Se esperaba una única fila y la consulta devolvió varias. Añade una condición o límite a la consulta.',
        ],
        'lock' => [
            'Conflicto de bloqueo (lock)',
            'Otra transacción está bloqueando los datos (deadlock o tiempo de espera agotado). Reintenta la operación.',
        ],
        'readonly' => [
            'Operación de solo lectura',
            'El objeto o la base de datos están en modo de solo lectura, por lo que la operación no puede realizarse.',
        ],
        'transaction' => [
            'Error de transacción',
            'Estado de transacción inválido en el driver (pdo_firebird). La consulta se reintenta con conexión nueva automáticamente; si persiste, reinicia el servicio de Firebird.',
        ],
        'resource_limit' => [
            'Límite de recursos excedido',
            'Demasiadas peticiones, memoria o contextos. Simplifica la consulta o contacta con el administrador de la BD.',
        ],
        'parameter' => [
            'Parámetros de la consulta no válidos',
            'La consulta envía parámetros que no están vinculados o no coinciden en número o tipo con las columnas. Revisa los marcadores ? o los valores pasados.',
        ],
        'cursor' => [
            'Error de cursor',
            'El cursor de la consulta no está en un estado válido (no está abierto o ya fue cerrado).',
        ],
        'db_exception' => [
            'Excepción de la base de datos',
            'Firebird lanzó una excepción definida en el propio procedimiento o trigger de la base de datos.',
        ],
        'server_error' => [
            'Error interno del servidor de base de datos',
            'Fallo interno de Firebird (archivo, caché, memoria o petición). Revisa el registro del servidor Firebird.',
        ],
        'metadata' => [
            'Error de metadatos',
            'La operación sobre el diccionario de datos (tablas, columnas, índices) no pudo completarse.',
        ],
        'unknown' => [
            'Error de base de datos',
            'No se pudo determinar el tipo exacto del error. Revisa el detalle del driver y el SQLCODE.',
        ],
        'http' => [
            'Error del servidor web',
            'El servidor devolvió una respuesta no válida (error HTTP). Revisa el servidor Apache/IIS y los registros de PHP.',
        ],
        'network' => [
            'Sin respuesta del servidor',
            'No se recibió respuesta del servidor. Comprueba tu conexión de red y que el servicio web esté activo.',
        ],
        'app' => [
            'Error de la aplicación',
            'Error interno de PHP (no proviene de la base de datos). Revisa los registros de error de PHP.',
        ],
    ];

    /**
     * Patrones sobre el mensaje del driver (el más específico primero).
     * El mensaje es quien decide, porque varios SQLCODE comparten código
     * con mensajes muy distintos (-104, -204, -904...).
     */
    private const PATTERNS = [
        // Conexión / apertura de la base de datos
        ['/(i\/o error during|createfile \(open\)|unable to complete network request|failed to establish a connection|connection lost|connection shutdown|connection rejected|cannot attach|unable to open database|unavailable database|not a valid database|can.t open database file|error reading data from the connection|error writing data to the connection|failed to locate host|invalid database handle)/i', 'connection'],
        // Estado de transacción (pdo_firebird: -901)
        ['/(invalid transaction handle|expecting explicit transaction start)/i', 'transaction'],
        // Autenticación
        ['/(your user name and password are not defined|password .{0,30}not defined|login name|user name .{0,30}(required|not found)|password required)/i', 'auth'],
        // Permisos
        ['/(no permission for|does not have grant|grant privileges|access .{0,40}denied by server|not permitted)/i', 'permission'],
        // Entidades no encontradas
        ['/(table unknown|table .{0,60}does not exist|table .{0,60}is not defined|no relation found|relation .{0,60}does not exist|view .{0,60}does not exist|no alias or table named|cannot find table|is not a valid base table)/i', 'table_not_found'],
        ['/(column unknown|column .{0,60}is not defined|column .{0,60}not found|field .{0,60}does not exist|field .{0,60}doesn.t exist|could not find column|no column name specified)/i', 'column_not_found'],
        ['/(ambiguous|conflicts with an alias|conflicts with a table|conflicts with a procedure)/i', 'ambiguous_column'],
        ['/(SQLDA|SQLVAR|empty pointer to NULL indicator|empty pointer to data|wrong number of (input|output) parameters|parameter mismatch|values within SQLDA)/i', 'parameter'],
        ['/(function .{0,60}(is not defined|could not be matched|unknown)|procedure .{0,60}(is not defined|unknown)|generator .{0,60}is not defined|sequence .{0,60}not found)/i', 'routine_not_found'],
        // Agregados / GROUP BY
        ['/(aggregate|group by|having clause|not contained in either an aggregate)/i', 'aggregate_group'],
        ['/(invalid order by|order by clause|conflicting sorting options)/i', 'order_by'],
        ['/(referenced more than once|is not referenced in plan|referenced twice|use alias|is ambiguous in cursor)/i', 'join'],
        // Datos / cálculo
        ['/(divide by zero|arithmetic exception|numeric overflow|integer overflow|floating-point overflow|concatenation overflow)/i', 'arithmetic'],
        ['/(conversion error|cannot transliterate|invalid character value for cast|not comparable in expression|data type .{0,40}not supported for arithmetic|value exceeds the range for valid dates|invalid escape|extract part|data type for constant unknown)/i', 'datatype'],
        ['/(unique key|duplicate value|duplicate key|null segment of unique)/i', 'unique_violation'],
        ['/(must be not null|not null column|violation of .{0,40}not null)/i', 'not_null'],
        ['/(foreign key|referential)/i', 'foreign_key'],
        ['/(check constraint)/i', 'check_constraint'],
        ['/(validation error for)/i', 'validation_rule'],
        ['/(multiple rows in singleton)/i', 'singleton'],
        ['/(deadlock|lock .{0,30}conflict|lock time-out|lock manager|no lock manager)/i', 'lock'],
        ['/(read-only|not updatable|cannot update read)/i', 'readonly'],
        ['/(too many requests|too many concurrent|implementation limit|buffer exhausted|memory exhausted|stack overflow|too many open|maximum .{0,40}exceeded|too many contexts|too many recursion)/i', 'resource_limit'],
        ['/(^exception |exception .{0,60}not defined)/i', 'db_exception'],
        // Sintaxis (al final: los anteriores son más específicos)
        ['/(token unknown|unexpected end of command|invalid token|blr syntax error|invalid request blr|malformed string|string constant is delimited|invalid command|invalid clause|token size exceeds|option specified more than once|using clause)/i', 'syntax'],
    ];

    /**
     * Fallback por SQLCODE cuando ningún patrón coincide.
     * Solo códigos cuyo significado es homogéneo.
     */
    private const CODE_MAP = [
        -103 => 'datatype',
        -104 => 'syntax',
        -105 => 'datatype',
        -155 => 'table_not_found',
        -171 => 'routine_not_found',
        -172 => 'routine_not_found',
        -170 => 'parameter',
        -203 => 'ambiguous_column',
        -205 => 'column_not_found',
        -206 => 'column_not_found',
        -208 => 'order_by',
        -219 => 'table_not_found',
        -281 => 'join',
        -282 => 'join',
        -283 => 'join',
        -297 => 'check_constraint',
        -407 => 'unique_violation',
        -413 => 'datatype',
        -501 => 'cursor',
        -502 => 'cursor',
        -504 => 'cursor',
        -508 => 'cursor',
        -510 => 'cursor',
        -530 => 'foreign_key',
        -551 => 'permission',
        -552 => 'permission',
        -615 => 'lock',
        -625 => 'validation_rule',
        -802 => 'arithmetic',
        -803 => 'unique_violation',
        -804 => 'parameter',
        -811 => 'singleton',
        -816 => 'readonly',
        -817 => 'readonly',
        -830 => 'aggregate_group',
        -833 => 'datatype',
        -836 => 'db_exception',
        -901 => 'transaction',
        -902 => 'server_error',
        -904 => 'server_error',
        -913 => 'lock',
        -922 => 'connection',
        -923 => 'connection',
        -924 => 'connection',
        -999 => 'server_error',
    ];

    /**
     * Construir un error tipado manualmente
     */
    public static function make(string $type, string $detail = '', array $extra = []): array
    {
        if (!isset(self::TYPES[$type])) {
            $type = 'unknown';
        }

        $err = [
            'type'     => $type,
            'label'    => self::TYPES[$type][0],
            'hint'     => self::TYPES[$type][1],
            'code'     => null,
            'gds'      => null,
            'sqlstate' => null,
            'detail'   => trim($detail),
            'line'     => null,
            'column'   => null,
        ];

        $pos = self::extractPosition($err['detail']);
        $err['line'] = $pos['line'];
        $err['column'] = $pos['column'];

        return array_merge($err, $extra);
    }

    /**
     * Clasificar una excepción (PDOException, RuntimeException de conexión, etc.)
     */
    public static function fromException(Throwable $e): array
    {
        $sqlstate = null;
        $code = null;
        $detail = '';

        if ($e instanceof PDOException) {
            if (is_array($e->errorInfo ?? null)) {
                $sqlstate = $e->errorInfo[0] !== null ? (string) $e->errorInfo[0] : null;
                if (isset($e->errorInfo[1]) && is_numeric($e->errorInfo[1])) {
                    $code = (int) $e->errorInfo[1];
                }
                $detail = trim((string) ($e->errorInfo[2] ?? ''));
            }
            if ($detail === '' && is_string($e->getCode()) && strlen($e->getCode()) === 5) {
                $sqlstate = $e->getCode();
            }
        }

        if ($detail === '') {
            $detail = trim($e->getMessage());
        }

        $detail = self::cleanDetail($detail);

        // En errores de conexión/adjunte el driver devuelve un GDSCODE (3355443xx),
        // no un SQLCODE: se separa para no confundirlos.
        $gds = null;
        if ($code !== null && abs($code) > 100000) {
            $gds = $code;
            $code = null;
        }

        // Database::connect() envuelve el fallo en RuntimeException
        if (preg_match('/^Error de conexi[oó]n a BD\s*:?\s*(.*)$/isu', $detail, $m)) {
            $inner = trim($m[1]);
            $err = self::make('connection', $inner !== '' ? $inner : $detail);
            $err['sqlstate'] = $sqlstate;
            $err['gds'] = $gds;
            return $err;
        }

        // Excepción que no proviene del driver de BD (error de PHP)
        if (!($e instanceof PDOException) && $gds === null) {
            $err = self::make('app', $detail);
            $err['sqlstate'] = $sqlstate;
            return $err;
        }

        $type = self::classify($code, $detail);

        $err = self::make($type, $detail);
        $err['code'] = $code;
        $err['gds'] = $gds;
        $err['sqlstate'] = $sqlstate;

        return $err;
    }

    /**
     * Mensaje compacto para el campo msg de la respuesta
     */
    public static function message(array $err): string
    {
        $msg = $err['label'] ?? 'Error de base de datos';

        if (!empty($err['detail'])) {
            $msg .= ': ' . $err['detail'];
        }

        if (!empty($err['code'])) {
            $msg .= ' (SQLCODE ' . $err['code'] . ')';
        } elseif (!empty($err['gds']) && strpos((string) $err['detail'], (string) $err['gds']) === false) {
            $msg .= ' (GDSCODE ' . $err['gds'] . ')';
        }

        return $msg;
    }

    /**
     * Clasificar por mensaje y luego por SQLCODE
     */
    private static function classify(?int $code, string $detail): string
    {
        if ($detail !== '') {
            foreach (self::PATTERNS as $pattern) {
                if (preg_match($pattern[0], $detail)) {
                    return $pattern[1];
                }
            }
        }

        if ($code !== null && isset(self::CODE_MAP[$code])) {
            return self::CODE_MAP[$code];
        }

        return 'unknown';
    }

    /**
     * Quitar el prefijo genérico de Firebird y normalizar espacios
     * "Dynamic SQL Error SQL error code = -104 Token unknown..." -> "Token unknown..."
     */
    private static function cleanDetail(string $detail): string
    {
        $detail = preg_replace('/^\s*Dynamic SQL Error\s+SQL error code = -?\d+\s*/i', '', $detail);
        $detail = preg_replace('/\s*SQLSTATE\[[A-Z0-9]{5}\]\s*/i', ' ', $detail);
        $detail = preg_replace('/\s+/', ' ', $detail);
        return trim($detail);
    }

    /**
     * Extraer línea/columna del mensaje del driver
     * "Token unknown - line 1, column 10 FRM" / "At line 1, column 15"
     */
    private static function extractPosition(string $detail): array
    {
        $result = ['line' => null, 'column' => null];
        if ($detail === '') {
            return $result;
        }

        $patterns = [
            '/line\s+(\d+)\s*,\s*column\s+(\d+)/i',
            '/línea\s+(\d+)\s*,\s*columna\s+(\d+)/iu',
            '/at\s+line\s+(\d+)\s+column\s+(\d+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $detail, $m)) {
                $result['line'] = (int) $m[1];
                $result['column'] = (int) $m[2];
                break;
            }
        }

        return $result;
    }
}
