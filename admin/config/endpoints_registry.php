<?php
/**
 * Registro de Endpoints API
 * 
 * Cada endpoint define:
 *   - name: Nombre descriptivo
 *   - description: Descripción corta
 *   - url: URL relativa al admin/
 *   - method: GET o POST
 *   - table: Tabla de BD (para operaciones genéricas)
 *   - type: list|get|create|update|delete|custom
 *   - category: Agrupación visual
 *   - params: Array de parámetros [{name, type, required, example, description}]
 *   - sql: Solo para type=custom. Consulta SQL con :placeholder
 *   - returns: Descripción de lo que devuelve
 */

return [
    // ============================================
    // INSPECTOR - Operaciones genéricas de BD
    // ============================================
    [
        'id' => 'listar_tablas',
        'name' => 'Listar Tablas',
        'description' => 'Obtiene todas las tablas con conteo de registros y columnas',
        'url' => 'modules/inspector/ajax/listar_tablas.php',
        'method' => 'POST',
        'table' => null,
        'type' => 'list',
        'category' => 'Inspector',
        'params' => [],
        'returns' => 'Array de objetos {TABLA, TOTAL, COLUMNAS}'
    ],
    [
        'id' => 'listar_columnas',
        'name' => 'Listar Columnas',
        'description' => 'Obtiene la estructura de columnas de una tabla',
        'url' => 'modules/inspector/ajax/listar_columnas.php',
        'method' => 'POST',
        'table' => null,
        'type' => 'list',
        'category' => 'Inspector',
        'params' => [
            ['name' => 'table', 'type' => 'string', 'required' => true, 'example' => 'CLIENTES', 'description' => 'Nombre de la tabla']
        ],
        'returns' => 'Array de columnas {COLUMNA, TIPO, LONGITUD, NOT_NULL}'
    ],
    [
        'id' => 'obtener_ultimos',
        'name' => 'Obtener Últimos',
        'description' => 'Últimos N registros de una tabla (ordenados por PK DESC)',
        'url' => 'modules/inspector/ajax/obtener_ultimos.php',
        'method' => 'POST',
        'table' => null,
        'type' => 'list',
        'category' => 'Inspector',
        'params' => [
            ['name' => 'table', 'type' => 'string', 'required' => true, 'example' => 'CLIENTES', 'description' => 'Nombre de la tabla'],
            ['name' => 'limit', 'type' => 'int', 'required' => false, 'example' => '10', 'description' => 'Número de registros (máx 50)'],
            ['name' => 'fields', 'type' => 'string', 'required' => false, 'example' => 'NOMBRE,EMAIL', 'description' => 'Campos separados por coma o *']
        ],
        'returns' => 'Array de registros'
    ],
    [
        'id' => 'obtener_datos',
        'name' => 'Obtener Datos',
        'description' => 'Datos con filtros, paginación y orden',
        'url' => 'modules/inspector/ajax/obtener_datos.php',
        'method' => 'POST',
        'table' => null,
        'type' => 'paginated',
        'category' => 'Inspector',
        'params' => [
            ['name' => 'table', 'type' => 'string', 'required' => true, 'example' => 'CLIENTES', 'description' => 'Nombre de la tabla'],
            ['name' => 'page', 'type' => 'int', 'required' => false, 'example' => '1', 'description' => 'Página'],
            ['name' => 'per_page', 'type' => 'int', 'required' => false, 'example' => '50', 'description' => 'Registros por página'],
            ['name' => 'fields', 'type' => 'string', 'required' => false, 'example' => '*', 'description' => 'Campos o *'],
            ['name' => 'filter_field_0', 'type' => 'string', 'required' => false, 'example' => 'PAIS', 'description' => 'Campo del filtro (índice 0)'],
            ['name' => 'filter_operator_0', 'type' => 'string', 'required' => false, 'example' => '=', 'description' => 'Operador: =, !=, LIKE, >, <, >=, <='],
            ['name' => 'filter_value_0', 'type' => 'string', 'required' => false, 'example' => 'ES', 'description' => 'Valor del filtro']
        ],
        'returns' => 'Datos paginados con {data, total, page, per_page}'
    ],
    [
        'id' => 'ejecutar_sql',
        'name' => 'Ejecutar SQL',
        'description' => 'Ejecuta una consulta SQL personalizada (solo SELECT)',
        'url' => 'modules/inspector/ajax/ejecutar_sql.php',
        'method' => 'POST',
        'table' => null,
        'type' => 'custom',
        'category' => 'Inspector',
        'params' => [
            ['name' => 'sql', 'type' => 'string', 'required' => true, 'example' => 'SELECT * FROM CLIENTES', 'description' => 'Consulta SQL (solo SELECT)']
        ],
        'returns' => 'Resultados de la consulta con paginación'
    ],

    // ============================================
    // GENERIC - Endpoints genéricos de tabla
    // ============================================
    [
        'id' => 'generic_list',
        'name' => 'Listar Registros',
        'description' => 'Lista registros de cualquier tabla con filtros y paginación',
        'url' => 'modules/api/ajax/endpoint.php?action=list',
        'method' => 'POST',
        'table' => '*',
        'type' => 'list',
        'category' => 'Genéricos',
        'params' => [
            ['name' => 'table', 'type' => 'string', 'required' => true, 'example' => 'CLIENTES', 'description' => 'Tabla a consultar'],
            ['name' => 'page', 'type' => 'int', 'required' => false, 'example' => '1', 'description' => 'Página'],
            ['name' => 'per_page', 'type' => 'int', 'required' => false, 'example' => '25', 'description' => 'Registros por página'],
            ['name' => 'order', 'type' => 'string', 'required' => false, 'example' => 'NOMBRE', 'description' => 'Campo de orden'],
            ['name' => 'order_dir', 'type' => 'string', 'required' => false, 'example' => 'ASC', 'description' => 'Dirección: ASC o DESC']
        ],
        'returns' => 'Registros paginados'
    ],
    [
        'id' => 'generic_get',
        'name' => 'Obtener por ID',
        'description' => 'Obtiene un registro por su ID',
        'url' => 'modules/api/ajax/endpoint.php?action=get',
        'method' => 'POST',
        'table' => '*',
        'type' => 'get',
        'category' => 'Genéricos',
        'params' => [
            ['name' => 'table', 'type' => 'string', 'required' => true, 'example' => 'CLIENTES', 'description' => 'Tabla'],
            ['name' => 'id', 'type' => 'int', 'required' => true, 'example' => '1', 'description' => 'ID del registro']
        ],
        'returns' => 'Registro encontrado o error 404'
    ],
    [
        'id' => 'generic_create',
        'name' => 'Crear Registro',
        'description' => 'Crea un nuevo registro en cualquier tabla',
        'url' => 'modules/api/ajax/endpoint.php?action=create',
        'method' => 'POST',
        'table' => '*',
        'type' => 'create',
        'category' => 'Genéricos',
        'params' => [
            ['name' => 'table', 'type' => 'string', 'required' => true, 'example' => 'CLIENTES', 'description' => 'Tabla'],
            ['name' => 'data', 'type' => 'JSON', 'required' => true, 'example' => '{"NOMBRE":"Juan","EMAIL":"juan@test.com"}', 'description' => 'Datos del registro en JSON']
        ],
        'returns' => 'Registro creado con ID'
    ],
    [
        'id' => 'generic_update',
        'name' => 'Actualizar Registro',
        'description' => 'Actualiza un registro existente',
        'url' => 'modules/api/ajax/endpoint.php?action=update',
        'method' => 'POST',
        'table' => '*',
        'type' => 'update',
        'category' => 'Genéricos',
        'params' => [
            ['name' => 'table', 'type' => 'string', 'required' => true, 'example' => 'CLIENTES', 'description' => 'Tabla'],
            ['name' => 'id', 'type' => 'int', 'required' => true, 'example' => '1', 'description' => 'ID del registro'],
            ['name' => 'data', 'type' => 'JSON', 'required' => true, 'example' => '{"NOMBRE":"Juan Updated"}', 'description' => 'Datos a actualizar en JSON']
        ],
        'returns' => 'Registro actualizado'
    ],
    [
        'id' => 'generic_delete',
        'name' => 'Eliminar Registro',
        'description' => 'Elimina un registro por ID',
        'url' => 'modules/api/ajax/endpoint.php?action=delete',
        'method' => 'POST',
        'table' => '*',
        'type' => 'delete',
        'category' => 'Genéricos',
        'params' => [
            ['name' => 'table', 'type' => 'string', 'required' => true, 'example' => 'CLIENTES', 'description' => 'Tabla'],
            ['name' => 'id', 'type' => 'int', 'required' => true, 'example' => '1', 'description' => 'ID del registro']
        ],
        'returns' => 'Confirmación de eliminación'
    ],
    [
        'id' => 'generic_custom_sql',
        'name' => 'SQL Personalizado',
        'description' => 'Ejecuta SQL personalizado en cualquier tabla',
        'url' => 'modules/api/ajax/endpoint.php?action=sql',
        'method' => 'POST',
        'table' => '*',
        'type' => 'custom',
        'category' => 'Genéricos',
        'params' => [
            ['name' => 'sql', 'type' => 'string', 'required' => true, 'example' => 'SELECT c.NOMBRE, COUNT(f.IDFACTURA) FROM CLIENTES c JOIN FACTURAS f ON c.IDCLIENTE = f.IDCLIENTE GROUP BY c.NOMBRE', 'description' => 'Consulta SQL (solo SELECT)']
        ],
        'returns' => 'Resultados de la consulta'
    ],

    // ============================================
    // BUSINESS - Endpoints de negocio (legado)
    // ============================================
    [
        'id' => 'clientes_list',
        'name' => 'Listar Clientes',
        'description' => 'Lista todos los clientes activos con datos básicos',
        'url' => 'modules/api/ajax/endpoint.php?action=list',
        'method' => 'POST',
        'table' => 'CLIENTES',
        'type' => 'list',
        'category' => 'Clientes',
        'params' => [
            ['name' => 'page', 'type' => 'int', 'required' => false, 'example' => '1', 'description' => 'Página'],
            ['name' => 'per_page', 'type' => 'int', 'required' => false, 'example' => '25', 'description' => 'Registros por página']
        ],
        'returns' => 'Lista de clientes con ID, NOMBRE, EMAIL, PAIS, ACTIVO'
    ],
    [
        'id' => 'clientes_get',
        'name' => 'Obtener Cliente',
        'description' => 'Obtiene un cliente por ID con todos sus datos',
        'url' => 'modules/api/ajax/endpoint.php?action=get',
        'method' => 'POST',
        'table' => 'CLIENTES',
        'type' => 'get',
        'category' => 'Clientes',
        'params' => [
            ['name' => 'id', 'type' => 'int', 'required' => true, 'example' => '1', 'description' => 'ID del cliente (IDCLIENTE)']
        ],
        'returns' => 'Datos completos del cliente'
    ],
    [
        'id' => 'facturas_list',
        'name' => 'Listar Facturas',
        'description' => 'Lista facturas con datos del cliente',
        'url' => 'modules/api/ajax/endpoint.php?action=list',
        'method' => 'POST',
        'table' => 'FACTURAS',
        'type' => 'list',
        'category' => 'Facturas',
        'params' => [
            ['name' => 'page', 'type' => 'int', 'required' => false, 'example' => '1', 'description' => 'Página'],
            ['name' => 'per_page', 'type' => 'int', 'required' => false, 'example' => '25', 'description' => 'Registros por página']
        ],
        'returns' => 'Lista de facturas con ID, FECHA, IMPORTE, ESTADO, NUMEROFACTURA'
    ],
    [
        'id' => 'facturas_get',
        'name' => 'Obtener Factura',
        'description' => 'Obtiene una factura por ID',
        'url' => 'modules/api/ajax/endpoint.php?action=get',
        'method' => 'POST',
        'table' => 'FACTURAS',
        'type' => 'get',
        'category' => 'Facturas',
        'params' => [
            ['name' => 'id', 'type' => 'int', 'required' => true, 'example' => '1', 'description' => 'ID de la factura (IDFACTURA)']
        ],
        'returns' => 'Datos de la factura'
    ],
    [
        'id' => 'productos_list',
        'name' => 'Listar Productos',
        'description' => 'Lista todos los productos',
        'url' => 'modules/api/ajax/endpoint.php?action=list',
        'method' => 'POST',
        'table' => 'PRODUCTOS',
        'type' => 'list',
        'category' => 'Productos',
        'params' => [
            ['name' => 'page', 'type' => 'int', 'required' => false, 'example' => '1', 'description' => 'Página']
        ],
        'returns' => 'Lista de productos'
    ],
    [
        'id' => 'compras_list',
        'name' => 'Listar Compras',
        'description' => 'Lista todas las compras con datos de cliente y producto',
        'url' => 'modules/api/ajax/endpoint.php?action=list',
        'method' => 'POST',
        'table' => 'COMPRAS',
        'type' => 'list',
        'category' => 'Compras',
        'params' => [
            ['name' => 'page', 'type' => 'int', 'required' => false, 'example' => '1', 'description' => 'Página']
        ],
        'returns' => 'Lista de compras'
    ],
    [
        'id' => 'clientes_con_facturas',
        'name' => 'Clientes con Facturas (JOIN)',
        'description' => 'Consulta personalizada: clientes con conteo de facturas y total',
        'url' => 'modules/api/ajax/endpoint.php?action=sql',
        'method' => 'POST',
        'table' => null,
        'type' => 'custom',
        'category' => 'Consultas',
        'params' => [],
        'sql' => 'SELECT c.NOMBRE, c.EMAIL, c.PAIS, COUNT(f.IDFACTURA) AS FACTURAS, COALESCE(SUM(f.IMPORTE), 0) AS TOTAL_IMPORTE FROM CLIENTES c LEFT JOIN FACTURAS f ON c.IDCLIENTE = f.IDCLIENTE GROUP BY c.NOMBRE, c.EMAIL, c.PAIS ORDER BY TOTAL_IMPORTE DESC',
        'returns' => 'Clientes con conteo de facturas y total importe'
    ],
    [
        'id' => 'facturas_por_estado',
        'name' => 'Facturas por Estado',
        'description' => 'Distribución de facturas por estado con totales',
        'url' => 'modules/api/ajax/endpoint.php?action=sql',
        'method' => 'POST',
        'table' => null,
        'type' => 'custom',
        'category' => 'Consultas',
        'params' => [],
        'sql' => 'SELECT ESTADO, COUNT(*) AS CANTIDAD, SUM(IMPORTE) AS TOTAL, AVG(IMPORTE) AS PROMEDIO FROM FACTURAS GROUP BY ESTADO ORDER BY TOTAL DESC',
        'returns' => 'Estados con cantidad, total y promedio'
    ],
    [
        'id' => 'resumen_general',
        'name' => 'Resumen General',
        'description' => 'Estadísticas generales: tablas, registros, últimas facturas',
        'url' => 'modules/api/ajax/endpoint.php?action=sql',
        'method' => 'POST',
        'table' => null,
        'type' => 'custom',
        'category' => 'Consultas',
        'params' => [],
        'sql' => 'SELECT (SELECT COUNT(*) FROM CLIENTES) AS TOTAL_CLIENTES, (SELECT COUNT(*) FROM FACTURAS) AS TOTAL_FACTURAS, (SELECT COUNT(*) FROM PRODUCTOS) AS TOTAL_PRODUCTOS, (SELECT SUM(IMPORTE) FROM FACTURAS) AS IMPORTE_TOTAL',
        'returns' => 'Resumen con conteos y totales generales'
    ]
];
