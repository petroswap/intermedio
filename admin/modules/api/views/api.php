<div class="api-container">
    <div class="api-header">
        <h2 class="section-title">API Endpoints</h2>
        <p class="api-base-url">Base URL: <code id="api-base-url"></code></p>
        <div class="api-tools">
            <span class="api-info">14 endpoints · 3 categorias · Solo lectura (SELECT)</span>
        </div>
    </div>

    <div class="api-categories">
        <!-- LITROS -->
        <div class="api-category">
            <div class="api-category-header" data-toggle="cat-litros">
                <span class="api-category-badge badge-litros">LITROS</span>
                <span class="api-category-title">Operaciones de combustible y tanques</span>
                <span class="api-category-count">8 endpoints</span>
                <span class="api-toggle-icon">v</span>
            </div>
            <div class="api-category-body" id="cat-litros">

                <!-- obtener_stock_tanques -->
                <div class="api-endpoint" data-method="POST" data-url="api/litros/obtener_stock_tanques.php">
                    <div class="api-endpoint-header">
                        <span class="api-method post">POST</span>
                        <span class="api-url">api/litros/obtener_stock_tanques.php</span>
                        <span class="api-desc">Stock actual de tanques por productos</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Devuelve el nivel actual de combustible (existencias) de los tanques asociados a los productos solicitados. Incluye nombre del tanque, base, producto y fecha de ultima actualizacion.</p>
                                <h5>Tabla de respuesta</h5>
                                <div class="ep-response-fields">
                                    <code>idtanque</code> <code>existencias</code> <code>tanque</code> <code>base</code> <code>producto</code> <code>fechahora</code>
                                </div>
                            </div>
                            <div class="ep-params">
                                <h5>Parametros (POST body)</h5>
                                <table class="api-params-table">
                                    <tr><th>Parametro</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr>
                                    <tr><td><code>productos</code></td><td>array</td><td>Si</td><td>IDs de productos, ej: [1, 5, 6]</td></tr>
                                </table>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl -X POST "BASE_URL/api/litros/obtener_stock_tanques.php" \
  -d "productos[]=1&productos[]=5"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form">
                                <div class="ep-field">
                                    <label>productos <span class="required">*</span></label>
                                    <input type="text" name="productos" placeholder="1,5,6 (IDs separados por coma)">
                                </div>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- obtener_ultimas_compras -->
                <div class="api-endpoint" data-method="POST" data-url="api/litros/obtener_ultimas_compras.php">
                    <div class="api-endpoint-header">
                        <span class="api-method post">POST</span>
                        <span class="api-url">api/litros/obtener_ultimas_compras.php</span>
                        <span class="api-desc">Ultima compra por base y producto</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Devuelve la ultima compra registrada por cada producto en cada base. Incluye datos del proveedor, numero de albaran, serie, precio y cantidad.</p>
                            </div>
                            <div class="ep-params">
                                <h5>Parametros (POST body)</h5>
                                <table class="api-params-table">
                                    <tr><th>Parametro</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr>
                                    <tr><td><code>productos</code></td><td>array</td><td>Si</td><td>IDs de productos</td></tr>
                                </table>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl -X POST "BASE_URL/api/litros/obtener_ultimas_compras.php" \
  -d "productos[]=1&productos[]=5"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form">
                                <div class="ep-field">
                                    <label>productos <span class="required">*</span></label>
                                    <input type="text" name="productos" placeholder="1,5,6">
                                </div>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- obtener_compras_desde -->
                <div class="api-endpoint" data-method="POST" data-url="api/litros/obtener_compras_desde.php">
                    <div class="api-endpoint-header">
                        <span class="api-method post">POST</span>
                        <span class="api-url">api/litros/obtener_compras_desde.php</span>
                        <span class="api-desc">Compras desde una fecha</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Lista todas las compras de los productos indicados desde una fecha de inicio. Ordenadas por fecha ascendente.</p>
                            </div>
                            <div class="ep-params">
                                <h5>Parametros (POST body)</h5>
                                <table class="api-params-table">
                                    <tr><th>Parametro</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr>
                                    <tr><td><code>productos</code></td><td>array</td><td>Si</td><td>IDs de productos</td></tr>
                                    <tr><td><code>desde</code></td><td>string</td><td>Si</td><td>Fecha inicio (Y-m-d H:i:s)</td></tr>
                                </table>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl -X POST "BASE_URL/api/litros/obtener_compras_desde.php" \
  -d "productos[]=1&desde=2026-09-01 00:00:00"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form">
                                <div class="ep-field">
                                    <label>productos <span class="required">*</span></label>
                                    <input type="text" name="productos" placeholder="1,5,6">
                                </div>
                                <div class="ep-field">
                                    <label>desde <span class="required">*</span></label>
                                    <input type="text" name="desde" placeholder="2026-09-01 00:00:00">
                                </div>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- obtener_ventas_ayer -->
                <div class="api-endpoint" data-method="POST" data-url="api/litros/obtener_ventas_ayer.php">
                    <div class="api-endpoint-header">
                        <span class="api-method post">POST</span>
                        <span class="api-url">api/litros/obtener_ventas_ayer.php</span>
                        <span class="api-desc">Ventas del dia anterior (agrupadas)</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Devuelve las ventas de la fecha indicada, agrupadas por producto y base. Incluye total de litros y cantidad de ventas.</p>
                            </div>
                            <div class="ep-params">
                                <h5>Parametros (POST body)</h5>
                                <table class="api-params-table">
                                    <tr><th>Parametro</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr>
                                    <tr><td><code>productos</code></td><td>array</td><td>Si</td><td>IDs de productos</td></tr>
                                    <tr><td><code>ayer</code></td><td>string</td><td>Si</td><td>Fecha (Y-m-d)</td></tr>
                                </table>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl -X POST "BASE_URL/api/litros/obtener_ventas_ayer.php" \
  -d "productos[]=1&ayer=2026-09-06"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form">
                                <div class="ep-field">
                                    <label>productos <span class="required">*</span></label>
                                    <input type="text" name="productos" placeholder="1,5,6">
                                </div>
                                <div class="ep-field">
                                    <label>ayer <span class="required">*</span></label>
                                    <input type="text" name="ayer" placeholder="2026-09-06">
                                </div>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- obtener_ventas_desde -->
                <div class="api-endpoint" data-method="POST" data-url="api/litros/obtener_ventas_desde.php">
                    <div class="api-endpoint-header">
                        <span class="api-method post">POST</span>
                        <span class="api-url">api/litros/obtener_ventas_desde.php</span>
                        <span class="api-desc">Ventas desde fecha (lineas individuales)</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Devuelve lineas individuales de venta desde una fecha. Permite filtrar por base, rango de fechas y productos. Cada linea incluye: fecha, cliente, producto, litros, precio y total.</p>
                            </div>
                            <div class="ep-params">
                                <h5>Parametros (POST body)</h5>
                                <table class="api-params-table">
                                    <tr><th>Parametro</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr>
                                    <tr><td><code>productos</code></td><td>array</td><td>Si</td><td>IDs de productos</td></tr>
                                    <tr><td><code>desde</code></td><td>string</td><td>Si</td><td>Fecha inicio (Y-m-d H:i:s)</td></tr>
                                    <tr><td><code>hasta</code></td><td>string</td><td>No</td><td>Fecha fin (Y-m-d H:i:s)</td></tr>
                                    <tr><td><code>idbase</code></td><td>int</td><td>No</td><td>Filtrar por base</td></tr>
                                </table>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl -X POST "BASE_URL/api/litros/obtener_ventas_desde.php" \
  -d "productos[]=1&desde=2026-09-01 00:00:00&idbase=5"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form">
                                <div class="ep-field">
                                    <label>productos <span class="required">*</span></label>
                                    <input type="text" name="productos" placeholder="1,5,6">
                                </div>
                                <div class="ep-field">
                                    <label>desde <span class="required">*</span></label>
                                    <input type="text" name="desde" placeholder="2026-09-01 00:00:00">
                                </div>
                                <div class="ep-field">
                                    <label>hasta</label>
                                    <input type="text" name="hasta" placeholder="2026-09-30 23:59:59">
                                </div>
                                <div class="ep-field">
                                    <label>idbase</label>
                                    <input type="text" name="idbase" placeholder="5">
                                </div>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- obtener_ventas_con_suministros -->
                <div class="api-endpoint" data-method="POST" data-url="api/litros/obtener_ventas_con_suministros.php">
                    <div class="api-endpoint-header">
                        <span class="api-method post">POST</span>
                        <span class="api-url">api/litros/obtener_ventas_con_suministros.php</span>
                        <span class="api-desc">Ventas vinculadas a suministros de tanques</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Devuelve ventas enlazadas con el suministro de tanque que las origino. Util para trazabilidad: que tanque surtio cada venta.</p>
                            </div>
                            <div class="ep-params">
                                <h5>Parametros (POST body)</h5>
                                <table class="api-params-table">
                                    <tr><th>Parametro</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr>
                                    <tr><td><code>productos</code></td><td>array</td><td>Si</td><td>IDs de productos</td></tr>
                                    <tr><td><code>desde</code></td><td>string</td><td>Si</td><td>Fecha inicio (Y-m-d H:i:s)</td></tr>
                                    <tr><td><code>hasta</code></td><td>string</td><td>No</td><td>Fecha fin (Y-m-d H:i:s)</td></tr>
                                    <tr><td><code>idbase</code></td><td>int</td><td>No</td><td>Filtrar por base</td></tr>
                                    <tr><td><code>idtanque</code></td><td>int</td><td>No</td><td>Filtrar por tanque</td></tr>
                                </table>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl -X POST "BASE_URL/api/litros/obtener_ventas_con_suministros.php" \
  -d "productos[]=1&desde=2026-09-01 00:00:00"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form">
                                <div class="ep-field">
                                    <label>productos <span class="required">*</span></label>
                                    <input type="text" name="productos" placeholder="1,5,6">
                                </div>
                                <div class="ep-field">
                                    <label>desde <span class="required">*</span></label>
                                    <input type="text" name="desde" placeholder="2026-09-01 00:00:00">
                                </div>
                                <div class="ep-field">
                                    <label>hasta</label>
                                    <input type="text" name="hasta" placeholder="2026-09-30 23:59:59">
                                </div>
                                <div class="ep-field">
                                    <label>idbase</label>
                                    <input type="text" name="idbase" placeholder="5">
                                </div>
                                <div class="ep-field">
                                    <label>idtanque</label>
                                    <input type="text" name="idtanque" placeholder="12">
                                </div>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- obtener_suministros_desde -->
                <div class="api-endpoint" data-method="POST" data-url="api/litros/obtener_suministros_desde.php">
                    <div class="api-endpoint-header">
                        <span class="api-method post">POST</span>
                        <span class="api-url">api/litros/obtener_suministros_desde.php</span>
                        <span class="api-desc">Suministros a tanques desde fecha</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Lista los suministros (cargas) realizados a tanques desde una fecha. Muestra tanque, producto, litros cargados y fecha del suministro.</p>
                            </div>
                            <div class="ep-params">
                                <h5>Parametros (POST body)</h5>
                                <table class="api-params-table">
                                    <tr><th>Parametro</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr>
                                    <tr><td><code>productos</code></td><td>array</td><td>Si</td><td>IDs de productos</td></tr>
                                    <tr><td><code>desde</code></td><td>string</td><td>Si</td><td>Fecha inicio (Y-m-d H:i:s)</td></tr>
                                    <tr><td><code>hasta</code></td><td>string</td><td>No</td><td>Fecha fin (Y-m-d H:i:s)</td></tr>
                                </table>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl -X POST "BASE_URL/api/litros/obtener_suministros_desde.php" \
  -d "productos[]=1&desde=2026-09-01 00:00:00"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form">
                                <div class="ep-field">
                                    <label>productos <span class="required">*</span></label>
                                    <input type="text" name="productos" placeholder="1,5,6">
                                </div>
                                <div class="ep-field">
                                    <label>desde <span class="required">*</span></label>
                                    <input type="text" name="desde" placeholder="2026-09-01 00:00:00">
                                </div>
                                <div class="ep-field">
                                    <label>hasta</label>
                                    <input type="text" name="hasta" placeholder="2026-09-30 23:59:59">
                                </div>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- obtener_tanques -->
                <div class="api-endpoint" data-method="POST" data-url="api/litros/obtener_tanques.php">
                    <div class="api-endpoint-header">
                        <span class="api-method post">POST</span>
                        <span class="api-url">api/litros/obtener_tanques.php</span>
                        <span class="api-desc">Listado de tanques</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Listado de tanques registrados. Permite filtrar por base y/o producto. Devuelve id, nombre, capacidad, base y producto asociado.</p>
                            </div>
                            <div class="ep-params">
                                <h5>Parametros (POST body)</h5>
                                <table class="api-params-table">
                                    <tr><th>Parametro</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr>
                                    <tr><td><code>idbase</code></td><td>int</td><td>No</td><td>Filtrar por base</td></tr>
                                    <tr><td><code>idproducto</code></td><td>int</td><td>No</td><td>Filtrar por producto</td></tr>
                                </table>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl -X POST "BASE_URL/api/litros/obtener_tanques.php" \
  -d "idbase=5&idproducto=1"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form">
                                <div class="ep-field">
                                    <label>idbase</label>
                                    <input type="text" name="idbase" placeholder="5">
                                </div>
                                <div class="ep-field">
                                    <label>idproducto</label>
                                    <input type="text" name="idproducto" placeholder="1">
                                </div>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- FACTURAS -->
        <div class="api-category">
            <div class="api-category-header" data-toggle="cat-facturas">
                <span class="api-category-badge badge-facturas">FACTURAS</span>
                <span class="api-category-title">Gestion de facturas y clientes</span>
                <span class="api-category-count">4 endpoints</span>
                <span class="api-toggle-icon">v</span>
            </div>
            <div class="api-category-body" id="cat-facturas">

                <!-- obtener_facturas_clientes -->
                <div class="api-endpoint" data-method="POST" data-url="api/facturas/obtener_facturas_clientes.php">
                    <div class="api-endpoint-header">
                        <span class="api-method post">POST</span>
                        <span class="api-url">api/facturas/obtener_facturas_clientes.php</span>
                        <span class="api-desc">Listado de facturas de clientes</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Devuelve facturas de clientes con filtros por fecha y/o ID. Incluye datos del cliente, serie, tipo de pago, estado y totales. Excluye tipos de pago configurados en .env.</p>
                            </div>
                            <div class="ep-params">
                                <h5>Parametros (POST body)</h5>
                                <table class="api-params-table">
                                    <tr><th>Parametro</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr>
                                    <tr><td><code>fecha_limite</code></td><td>string</td><td>No</td><td>Fecha minima (Y-m-d)</td></tr>
                                    <tr><td><code>id_factura</code></td><td>int</td><td>No</td><td>ID de factura (trae desde este ID)</td></tr>
                                </table>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl -X POST "BASE_URL/api/facturas/obtener_facturas_clientes.php" \
  -d "fecha_limite=2026-01-01"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form">
                                <div class="ep-field">
                                    <label>fecha_limite</label>
                                    <input type="text" name="fecha_limite" placeholder="2026-01-01">
                                </div>
                                <div class="ep-field">
                                    <label>id_factura</label>
                                    <input type="text" name="id_factura" placeholder="12345">
                                </div>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- obtener_factura -->
                <div class="api-endpoint" data-method="POST" data-url="api/facturas/obtener_factura.php">
                    <div class="api-endpoint-header">
                        <span class="api-method post">POST</span>
                        <span class="api-url">api/facturas/obtener_factura.php</span>
                        <span class="api-desc">Detalle de una factura</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Devuelve el detalle completo de una factura por su ID. Incluye cliente, lineas de factura, totales, pagos y estado.</p>
                            </div>
                            <div class="ep-params">
                                <h5>Parametros (POST body)</h5>
                                <table class="api-params-table">
                                    <tr><th>Parametro</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr>
                                    <tr><td><code>id</code></td><td>int</td><td>Si</td><td>ID de la factura</td></tr>
                                </table>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl -X POST "BASE_URL/api/facturas/obtener_factura.php" \
  -d "id=12345"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form">
                                <div class="ep-field">
                                    <label>id <span class="required">*</span></label>
                                    <input type="text" name="id" placeholder="12345">
                                </div>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- obtener_contactos -->
                <div class="api-endpoint" data-method="GET" data-url="api/facturas/obtener_contactos.php">
                    <div class="api-endpoint-header">
                        <span class="api-method get">GET</span>
                        <span class="api-url">api/facturas/obtener_contactos.php</span>
                        <span class="api-desc">Contactos comisionistas</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Devuelve listado de contactos marcados como comisionistas (comisionista=1). Incluye nombre, telefono, email y datos de contacto.</p>
                            </div>
                            <div class="ep-params">
                                <p class="api-no-params">Sin parametros requeridos</p>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl "BASE_URL/api/facturas/obtener_contactos.php"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form ep-no-params">
                                <p>Sin parametros. Haz clic en Ejecutar para consultar.</p>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- obtener_clientes_completos -->
                <div class="api-endpoint" data-method="GET" data-url="api/facturas/obtener_clientes_completos.php">
                    <div class="api-endpoint-header">
                        <span class="api-method get">GET</span>
                        <span class="api-url">api/facturas/obtener_clientes_completos.php</span>
                        <span class="api-desc">Clientes con categorias</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Devuelve clientes activos que pertenecen a categorias configuradas (fueltruck/ruta). Incluye datos del cliente, categoria, contacto y direccion.</p>
                            </div>
                            <div class="ep-params">
                                <p class="api-no-params">Sin parametros requeridos</p>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl "BASE_URL/api/facturas/obtener_clientes_completos.php"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form ep-no-params">
                                <p>Sin parametros. Haz clic en Ejecutar para consultar.</p>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- TARIFAS -->
        <div class="api-category">
            <div class="api-category-header" data-toggle="cat-tarifas">
                <span class="api-category-badge badge-tarifas">TARIFAS</span>
                <span class="api-category-title">Tarifas y precios por cliente</span>
                <span class="api-category-count">2 endpoints</span>
                <span class="api-toggle-icon">v</span>
            </div>
            <div class="api-category-body" id="cat-tarifas">

                <!-- obtener_tarifascaa_clientes -->
                <div class="api-endpoint" data-method="GET" data-url="api/tarifas/obtener_tarifascaa_clientes.php">
                    <div class="api-endpoint-header">
                        <span class="api-method get">GET</span>
                        <span class="api-url">api/tarifas/obtener_tarifascaa_clientes.php</span>
                        <span class="api-desc">Listado de tarifas y clientes activos</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Devuelve tarifas CAA activas y sus clientes asociados. Incluye nombre de tarifa, grupo, cliente y estado.</p>
                            </div>
                            <div class="ep-params">
                                <p class="api-no-params">Sin parametros requeridos</p>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl "BASE_URL/api/tarifas/obtener_tarifascaa_clientes.php"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form ep-no-params">
                                <p>Sin parametros. Haz clic en Ejecutar para consultar.</p>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- tarifas_bases_clientes_precios -->
                <div class="api-endpoint" data-method="GET" data-url="api/tarifas/tarifas_bases_clientes_precios.php">
                    <div class="api-endpoint-header">
                        <span class="api-method get">GET</span>
                        <span class="api-url">api/tarifas/tarifas_bases_clientes_precios.php</span>
                        <span class="api-desc">Precios por cliente, tarifa y base</span>
                    </div>
                    <div class="api-endpoint-body">
                        <div class="ep-tabs">
                            <button class="ep-tab active" data-tab="docs">Docs</button>
                            <button class="ep-tab" data-tab="test">Test</button>
                        </div>
                        <div class="ep-tab-content active" data-content="docs">
                            <div class="ep-info">
                                <p>Devuelve precios de productos por cliente, tarifa y base. Permite filtrar por tarifa especifica, lista de clientes, fecha de vigencia y tipo (semanal/diario).</p>
                            </div>
                            <div class="ep-params">
                                <h5>Parametros (query string)</h5>
                                <table class="api-params-table">
                                    <tr><th>Parametro</th><th>Tipo</th><th>Requerido</th><th>Descripcion</th></tr>
                                    <tr><td><code>id_tarifa</code></td><td>int</td><td>No</td><td>Filtrar por ID de tarifa</td></tr>
                                    <tr><td><code>todos</code></td><td>int</td><td>No</td><td>1 = todos los clientes, 0 = filtrar por lista</td></tr>
                                    <tr><td><code>clientes</code></td><td>string</td><td>No</td><td>IDs separados por coma (si todos=0)</td></tr>
                                    <tr><td><code>id_cliente</code></td><td>int</td><td>No</td><td>Filtrar por un cliente</td></tr>
                                    <tr><td><code>fecha_limite</code></td><td>string</td><td>No</td><td>Fecha para filtrar tarifas vigentes</td></tr>
                                    <tr><td><code>tipo_tarifa</code></td><td>string</td><td>No</td><td>"semanal" o "diario"</td></tr>
                                </table>
                            </div>
                            <div class="ep-example">
                                <h5>Ejemplo curl</h5>
                                <pre class="api-code">curl "BASE_URL/api/tarifas/tarifas_bases_clientes_precios.php?id_tarifa=5&todos=1"</pre>
                            </div>
                        </div>
                        <div class="ep-tab-content" data-content="test">
                            <div class="ep-test-form">
                                <div class="ep-field">
                                    <label>id_tarifa</label>
                                    <input type="text" name="id_tarifa" placeholder="5">
                                </div>
                                <div class="ep-field">
                                    <label>todos</label>
                                    <input type="text" name="todos" placeholder="1 = todos, 0 = filtrar">
                                </div>
                                <div class="ep-field">
                                    <label>clientes</label>
                                    <input type="text" name="clientes" placeholder="1,5,10">
                                </div>
                                <div class="ep-field">
                                    <label>id_cliente</label>
                                    <input type="text" name="id_cliente" placeholder="123">
                                </div>
                                <div class="ep-field">
                                    <label>fecha_limite</label>
                                    <input type="text" name="fecha_limite" placeholder="2026-09-01">
                                </div>
                                <div class="ep-field">
                                    <label>tipo_tarifa</label>
                                    <input type="text" name="tipo_tarifa" placeholder="semanal o diario">
                                </div>
                                <button class="ep-execute">Ejecutar</button>
                            </div>
                            <div class="ep-response" style="display:none">
                                <div class="ep-response-header">
                                    <span class="ep-status"></span>
                                    <span class="ep-time"></span>
                                    <span class="ep-size"></span>
                                    <button class="ep-copy">Copiar</button>
                                </div>
                                <pre class="ep-response-body"></pre>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.api-container { padding: 1rem; }
.api-header { margin-bottom: 1.5rem; }
.api-tools { display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem; }
.api-info { color: var(--text-secondary); font-size: 0.85rem; }
.api-base-url { color: var(--text-secondary); font-size: 0.9rem; }
.api-base-url code { background: var(--bg-tertiary); padding: 0.2rem 0.5rem; border-radius: 4px; }

.api-category { margin-bottom: 1rem; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; }
.api-category-header {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.75rem 1rem; cursor: pointer;
    background: var(--bg-secondary); transition: background 0.2s;
}
.api-category-header:hover { background: var(--bg-tertiary); }
.api-category-badge {
    padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem;
    font-weight: 700; color: #fff; letter-spacing: 0.5px;
}
.badge-litros { background: #3b82f6; }
.badge-facturas { background: #10b981; }
.badge-tarifas { background: #f59e0b; }
.api-category-title { flex: 1; font-weight: 500; }
.api-category-count { color: var(--text-secondary); font-size: 0.85rem; }
.api-toggle-icon { transition: transform 0.2s; font-size: 0.8rem; }
.api-category.collapsed .api-toggle-icon { transform: rotate(-90deg); }
.api-category.collapsed .api-category-body { display: none; }

.api-endpoint { border-top: 1px solid var(--border-color); }
.api-endpoint-header {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.6rem 1rem; cursor: pointer; transition: background 0.15s;
}
.api-endpoint-header:hover { background: var(--bg-tertiary); }
.api-method {
    padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.7rem;
    font-weight: 700; color: #fff; min-width: 48px; text-align: center;
}
.api-method.get { background: #22c55e; }
.api-method.post { background: #3b82f6; }
.api-url { font-family: monospace; font-size: 0.85rem; color: var(--text-primary); }
.api-desc { color: var(--text-secondary); font-size: 0.85rem; margin-left: auto; }

.api-endpoint-body { display: none; background: var(--bg-primary); }
.api-endpoint.open .api-endpoint-body { display: block; }
.api-no-params { color: var(--text-secondary); font-style: italic; font-size: 0.85rem; margin: 0; }
.api-params-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.api-params-table th { text-align: left; padding: 0.4rem; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); font-weight: 600; }
.api-params-table td { padding: 0.4rem; border-bottom: 1px solid var(--border-color); }
.api-params-table code { background: var(--bg-tertiary); padding: 0.1rem 0.3rem; border-radius: 3px; font-size: 0.8rem; }
.api-code {
    background: var(--bg-tertiary); padding: 0.75rem; border-radius: 6px;
    font-size: 0.8rem; overflow-x: auto; white-space: pre; margin: 0;
    border: 1px solid var(--border-color);
}

/* Endpoint Tabs */
.ep-tabs {
    display: flex; border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
}
.ep-tab {
    padding: 0.5rem 1rem; border: none; background: transparent;
    color: var(--text-secondary); font-size: 0.85rem; cursor: pointer;
    border-bottom: 2px solid transparent; transition: all 0.15s;
    font-weight: 500;
}
.ep-tab:hover { color: var(--text-primary); }
.ep-tab.active { color: var(--accent, #3b82f6); border-bottom-color: var(--accent, #3b82f6); }

.ep-tab-content { display: none; padding: 0.75rem 1rem; }
.ep-tab-content.active { display: block; }

/* Docs tab */
.ep-info p { margin: 0 0 0.75rem; color: var(--text-primary); font-size: 0.9rem; line-height: 1.5; }
.ep-info h5, .ep-params h5, .ep-example h5 {
    font-size: 0.75rem; color: var(--text-secondary); margin: 0.75rem 0 0.4rem;
    text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;
}
.ep-response-fields { display: flex; flex-wrap: wrap; gap: 0.4rem; margin: 0.5rem 0; }
.ep-response-fields code {
    background: var(--bg-tertiary); padding: 0.2rem 0.5rem; border-radius: 4px;
    font-size: 0.8rem; color: var(--accent, #3b82f6);
}

/* Test tab */
.ep-test-form { display: flex; flex-direction: column; gap: 0.6rem; }
.ep-test-form.ep-no-params { align-items: flex-start; }
.ep-test-form.ep-no-params p { margin: 0 0 0.5rem; color: var(--text-secondary); font-size: 0.85rem; }
.ep-field { display: flex; flex-direction: column; gap: 0.25rem; }
.ep-field label {
    font-size: 0.8rem; font-weight: 600; color: var(--text-secondary);
    font-family: monospace;
}
.ep-field label .required { color: #ef4444; }
.ep-field input {
    padding: 0.45rem 0.6rem; border: 1px solid var(--border-color); border-radius: 4px;
    background: var(--bg-primary); color: var(--text-primary); font-size: 0.85rem;
    font-family: monospace;
}
.ep-field input:focus { border-color: var(--accent, #3b82f6); outline: none; }
.ep-execute {
    align-self: flex-start; padding: 0.5rem 1.25rem; border: none; border-radius: 4px;
    background: var(--accent, #3b82f6); color: #fff; font-weight: 600;
    font-size: 0.85rem; cursor: pointer; transition: opacity 0.15s; margin-top: 0.25rem;
}
.ep-execute:hover { opacity: 0.9; }
.ep-execute:disabled { opacity: 0.5; cursor: not-allowed; }

/* Response */
.ep-response { margin-top: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden; }
.ep-response-header {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.4rem 0.75rem; background: var(--bg-tertiary);
    border-bottom: 1px solid var(--border-color);
}
.ep-status {
    padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;
}
.ep-status.ok { background: #dcfce7; color: #16a34a; }
.ep-status.err { background: #fee2e2; color: #dc2626; }
.ep-time, .ep-size { font-size: 0.8rem; color: var(--text-secondary); }
.ep-copy {
    margin-left: auto; padding: 0.2rem 0.5rem; border: 1px solid var(--border-color);
    border-radius: 4px; background: var(--bg-primary); color: var(--text-secondary);
    font-size: 0.75rem; cursor: pointer;
}
.ep-copy:hover { border-color: var(--accent, #3b82f6); color: var(--accent, #3b82f6); }
.ep-response-body {
    padding: 0.75rem; margin: 0; font-size: 0.8rem; max-height: 400px;
    overflow: auto; background: var(--bg-primary); white-space: pre-wrap;
    word-break: break-word; font-family: monospace;
}
</style>

<script>
$(document).ready(function() {
    var baseUrl = window.location.origin + '<?= APP_BASE_URL ?>';
    $('#api-base-url').text(baseUrl);

    // Toggle categories
    $('.api-category-header').on('click', function() {
        $(this).closest('.api-category').toggleClass('collapsed');
    });

    // Toggle endpoints
    $('.api-endpoint-header').on('click', function() {
        $(this).closest('.api-endpoint').toggleClass('open');
    });

    // Endpoint tab switching
    $(document).on('click', '.ep-tab', function() {
        var tab = $(this).data('tab');
        var $body = $(this).closest('.api-endpoint-body');
        $(this).addClass('active').siblings().removeClass('active');
        $body.find('.ep-tab-content').removeClass('active');
        $body.find('.ep-tab-content[data-content="' + tab + '"]').addClass('active');
    });

    // Execute endpoint
    $(document).on('click', '.ep-execute', function() {
        var $endpoint = $(this).closest('.api-endpoint');
        var method = $endpoint.data('method');
        var url = $endpoint.data('url');
        var fullUrl = baseUrl + url;

        var $btn = $(this).prop('disabled', true).text('Enviando...');
        var $response = $endpoint.find('.ep-response');
        var startTime = Date.now();

        // Collect params
        var params = {};
        $endpoint.find('.ep-field input').each(function() {
            var name = $(this).attr('name');
            var val = $(this).val().trim();
            if (val) params[name] = val;
        });

        $.ajax({
            url: fullUrl,
            method: method,
            data: params,
            success: function(rawData, status, xhr) {
                var elapsed = Date.now() - startTime;
                var data = rawData;
                if (typeof rawData === 'string') {
                    try { data = JSON.parse(rawData); } catch(e) {
                        data = { success: false, msg: 'Respuesta no es JSON valido', raw: rawData.substring(0, 500) };
                    }
                }
                var size = JSON.stringify(data).length;
                showEndpointResponse($endpoint, data, xhr.status, elapsed, size);
            },
            error: function(xhr) {
                var elapsed = Date.now() - startTime;
                var data = null;
                try { data = JSON.parse(xhr.responseText); } catch(e) {
                    data = { success: false, msg: 'HTTP ' + xhr.status + ' - ' + (xhr.statusText || 'Error') };
                }
                if (xhr.status === 0) {
                    data = { success: false, msg: 'Conexion rechazada. Verifica que el servidor este activo.' };
                }
                showEndpointResponse($endpoint, data, xhr.status || 0, elapsed, 0);
            },
            complete: function() {
                $btn.prop('disabled', false).text('Ejecutar');
            }
        });
    });

    function showEndpointResponse($endpoint, data, httpStatus, elapsed, size) {
        var $response = $endpoint.find('.ep-response');
        var $status = $endpoint.find('.ep-status');
        var $time = $endpoint.find('.ep-time');
        var $size = $endpoint.find('.ep-size');
        var $body = $endpoint.find('.ep-response-body');

        var ok = httpStatus >= 200 && httpStatus < 300;
        $status.removeClass('ok err')
               .addClass(ok ? 'ok' : 'err')
               .text(httpStatus + (ok ? ' OK' : ' Error'));
        $time.text(elapsed + 'ms');
        $size.text(size + ' B');
        $body.text(JSON.stringify(data, null, 2));
        $response.show();

        // Switch to test tab to show response
        var $body2 = $endpoint.find('.api-endpoint-body');
        $body2.find('.ep-tab[data-tab="test"]').click();

        $response[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Copy response
    $(document).on('click', '.ep-copy', function() {
        var $endpoint = $(this).closest('.api-endpoint');
        var text = $endpoint.find('.ep-response-body').text();
        var $btn = $(this);
        navigator.clipboard.writeText(text).then(function() {
            $btn.text('Copiado!');
            setTimeout(function() { $btn.text('Copiar'); }, 1500);
        });
    });
});
</script>
