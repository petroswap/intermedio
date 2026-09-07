<div class="sql-module-container">
    <div class="sql-module-header">
        <h2 class="sql-module-title">
            <span class="icon">💻</span>
            SQL Custom
        </h2>
        <div class="sql-module-actions">
            <button id="btn-sql-clear" class="btn btn-secondary btn-sm">🗑️ Limpiar</button>
            <button id="btn-sql-execute" class="btn btn-primary btn-sm">▶ Ejecutar</button>
        </div>
    </div>

    <div class="sql-module-layout">
        <!-- Panel izquierdo: Editor -->
        <div class="sql-editor-panel">
            <!-- Ejemplos SQL -->
            <div class="sql-examples-section">
                <button class="sql-examples-toggle" id="btn-toggle-examples">
                    📖 Ejemplos SQL Firebird
                    <span class="sql-examples-arrow">▼</span>
                </button>
                <div class="sql-examples-panel" id="sql-examples-panel">

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 Básico</div>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA">SELECT * FROM MITABLA</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, COLUMNA2, COLUMNA3 FROM MITABLA">Columnas específicas</button>
                        <button class="sql-example-item" data-sql="SELECT FIRST 10 * FROM MITABLA ORDER BY COLUMNA1 DESC">Primeros 10 ordenados</button>
                        <button class="sql-example-item" data-sql="SELECT DISTINCT COLUMNA1 FROM MITABLA">Valores únicos (DISTINCT)</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 Filtros</div>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA WHERE COLUMNA1 = 'valor'">WHERE igual a valor</button>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA WHERE COLUMNA1 LIKE '%texto%'">LIKE búsqueda parcial</button>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA WHERE COLUMNA1 BETWEEN 100 AND 500">BETWEEN rango</button>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA WHERE COLUMNA1 IN ('A', 'B', 'C')">IN lista de valores</button>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA WHERE COLUMNA1 LIKE '%texto%' AND COLUMNA2 = 'otro'">Múltiples condiciones</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 Orden y Límite (Firebird)</div>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA ORDER BY COLUMNA1 ASC">ORDER BY ascendente</button>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA ORDER BY COLUMNA1 DESC">ORDER BY descendente</button>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA ORDER BY COLUMNA1, COLUMNA2 DESC">Múltiples columnas</button>
                        <button class="sql-example-item" data-sql="SELECT FIRST 5 * FROM MITABLA ORDER BY COLUMNA1 DESC">FIRST 5</button>
                        <button class="sql-example-item" data-sql="SELECT SKIP 10 FIRST 10 * FROM MITABLA">SKIP + FIRST (paginación)</button>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA ROWS 1 TO 20">ROWS 1 TO 20</button>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA ROWS 11 TO 20 ORDER BY COLUMNA1">Página 2</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 Funciones de Cadena</div>
                        <button class="sql-example-item" data-sql="SELECT UPPER(COLUMNA1) FROM MITABLA">UPPER (mayúsculas)</button>
                        <button class="sql-example-item" data-sql="SELECT LOWER(COLUMNA1) FROM MITABLA">LOWER (minúsculas)</button>
                        <button class="sql-example-item" data-sql="SELECT TRIM(COLUMNA1) FROM MITABLA">TRIM (espacios)</button>
                        <button class="sql-example-item" data-sql="SELECT SUBSTRING(COLUMNA1 FROM 1 FOR 10) FROM MITABLA">SUBSTRING</button>
                        <button class="sql-example-item" data-sql="SELECT CHAR_LENGTH(COLUMNA1) FROM MITABLA">Longitud (CHAR_LENGTH)</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1 || ' ' || COLUMNA2 FROM MITABLA">Concatenar (||)</button>
                        <button class="sql-example-item" data-sql="SELECT REPLACE(COLUMNA1, 'antiguo', 'nuevo') FROM MITABLA">REPLACE</button>
                        <button class="sql-example-item" data-sql="SELECT POSITION('texto' IN COLUMNA1) FROM MITABLA">POSITION</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 Funciones Numéricas</div>
                        <button class="sql-example-item" data-sql="SELECT ABS(COLUMNA1) FROM MITABLA">ABS (valor absoluto)</button>
                        <button class="sql-example-item" data-sql="SELECT ROUND(COLUMNA1, 2) FROM MITABLA">ROUND (redondear)</button>
                        <button class="sql-example-item" data-sql="SELECT CEIL(COLUMNA1) FROM MITABLA">CEIL (techo)</button>
                        <button class="sql-example-item" data-sql="SELECT FLOOR(COLUMNA1) FROM MITABLA">FLOOR (suelo)</button>
                        <button class="sql-example-item" data-sql="SELECT MOD(COLUMNA1, 3) FROM MITABLA">MOD (módulo)</button>
                        <button class="sql-example-item" data-sql="SELECT CAST(COLUMNA1 AS DECIMAL(10,2)) FROM MITABLA">CAST numérico</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 Funciones de Fecha</div>
                        <button class="sql-example-item" data-sql="SELECT CURRENT_DATE FROM RDB$DATABASE">Fecha actual</button>
                        <button class="sql-example-item" data-sql="SELECT CURRENT_TIMESTAMP FROM RDB$DATABASE">Fecha y hora actual</button>
                        <button class="sql-example-item" data-sql="SELECT EXTRACT(YEAR FROM COLUMNA1) FROM MITABLA">EXTRACT año</button>
                        <button class="sql-example-item" data-sql="SELECT EXTRACT(MONTH FROM COLUMNA1) FROM MITABLA">EXTRACT mes</button>
                        <button class="sql-example-item" data-sql="SELECT DATEADD(DAY, 30, COLUMNA1) FROM MITABLA">DATEADD +30 días</button>
                        <button class="sql-example-item" data-sql="SELECT DATEDIFF(DAY, COLUMNA1, CURRENT_DATE) FROM MITABLA">DATEDIFF días</button>
                        <button class="sql-example-item" data-sql="SELECT CAST(COLUMNA1 AS DATE) FROM MITABLA WHERE COLUMNA1 >= CURRENT_DATE - 30">Últimos 30 días</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 CASE / IIF</div>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, CASE WHEN COLUMNA1 > 100 THEN 'Alto' WHEN COLUMNA1 > 50 THEN 'Medio' ELSE 'Bajo' END AS CLASE FROM MITABLA">CASE WHEN</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, IIF(COLUMNA1 > 100, 'Sí', 'No') AS MAYOR_100 FROM MITABLA">IIF (Firebird 3+)</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, COALESCE(COLUMNA2, 0) AS VALOR FROM MITABLA">COALESCE</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, NULLIF(COLUMNA2, 0) AS VALOR FROM MITABLA">NULLIF</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 Agrupación (GROUP BY)</div>
                        <button class="sql-example-item" data-sql="SELECT COUNT(*) AS TOTAL FROM MITABLA">Contar registros</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, COUNT(*) AS TOTAL FROM MITABLA GROUP BY COLUMNA1">GROUP BY simple</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, SUM(COLUMNA2) AS SUMA, AVG(COLUMNA2) AS PROMEDIO FROM MITABLA GROUP BY COLUMNA1">SUM + AVG</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, MAX(COLUMNA2) AS MAXIMO, MIN(COLUMNA2) AS MINIMO FROM MITABLA GROUP BY COLUMNA1">MAX + MIN</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, COUNT(*) AS TOTAL FROM MITABLA GROUP BY COLUMNA1 HAVING COUNT(*) > 5">HAVING filtrar grupos</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 JOINs</div>
                        <button class="sql-example-item" data-sql="SELECT a.COLUMNA1, b.COLUMNA2 FROM TABLA_A a JOIN TABLA_B b ON a.ID = b.ID_A">INNER JOIN</button>
                        <button class="sql-example-item" data-sql="SELECT a.COLUMNA1, COUNT(b.ID) AS TOTAL FROM TABLA_A a LEFT JOIN TABLA_B b ON a.ID = b.ID_A GROUP BY a.COLUMNA1 ORDER BY TOTAL DESC">LEFT JOIN + GROUP BY</button>
                        <button class="sql-example-item" data-sql="SELECT a.COLUMNA1, b.COLUMNA2, c.COLUMNA3 FROM TABLA_A a JOIN TABLA_B b ON a.ID = b.ID_A JOIN TABLA_C c ON b.ID = c.ID_B">Triple JOIN</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 Subconsultas</div>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA WHERE ID IN (SELECT ID_A FROM TABLA_B WHERE COLUMNA1 > 100)">WHERE IN (subquery)</button>
                        <button class="sql-example-item" data-sql="SELECT * FROM MITABLA WHERE COLUMNA1 > (SELECT AVG(COLUMNA1) FROM MITABLA)">WHERE > (subquery)</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 UNION</div>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, 'A' AS TIPO FROM TABLA_A UNION ALL SELECT COLUMNA1, 'B' AS TIPO FROM TABLA_B">UNION ALL</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 Funciones de Ventana (Firebird 3+)</div>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, COLUMNA2, ROW_NUMBER() OVER (ORDER BY COLUMNA2 DESC) AS RANKING FROM MITABLA">ROW_NUMBER()</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, COLUMNA2, RANK() OVER (PARTITION BY COLUMNA1 ORDER BY COLUMNA2 DESC) AS RANK FROM MITABLA">RANK()</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, COLUMNA2, DENSE_RANK() OVER (ORDER BY COLUMNA2 DESC) AS DRANK FROM MITABLA">DENSE_RANK()</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, LAG(COLUMNA2, 1) OVER (ORDER BY COLUMNA1) AS ANTERIOR FROM MITABLA">LAG()</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, LEAD(COLUMNA2, 1) OVER (ORDER BY COLUMNA1) AS SIGUIENTE FROM MITABLA">LEAD()</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, SUM(COLUMNA2) OVER (PARTITION BY COLUMNA1 ORDER BY COLUMNA1) AS ACUMULADO FROM MITABLA">SUM OVER (acumulado)</button>
                        <button class="sql-example-item" data-sql="SELECT COLUMNA1, NTILE(4) OVER (ORDER BY COLUMNA1) AS CUARTIL FROM MITABLA">NTILE()</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 CTEs (WITH)</div>
                        <button class="sql-example-item" data-sql="WITH CTE AS (SELECT ID, COLUMNA1, COLUMNA2 FROM MITABLA WHERE COLUMNA1 > 100) SELECT * FROM CTE WHERE COLUMNA2 < 500">CTE simple</button>
                        <button class="sql-example-item" data-sql="WITH RECURSIVE CTE(ID, COLUMNA1, PADRE) AS (SELECT ID, COLUMNA1, PADRE FROM MITABLA WHERE PADRE IS NULL UNION ALL SELECT m.ID, m.COLUMNA1, m.PADRE FROM MITABLA m JOIN CTE c ON m.PADRE = c.ID) SELECT * FROM CTE">CTE recursivo</button>
                    </div>

                    <div class="sql-example-group">
                        <div class="sql-example-group-title">🔹 Metadatos Firebird</div>
                        <button class="sql-example-item" data-sql="SELECT RDB$RELATION_NAME AS TABLA FROM RDB$RELATIONS WHERE RDB$SYSTEM_FLAG = 0 ORDER BY RDB$RELATION_NAME">Listar tablas</button>
                        <button class="sql-example-item" data-sql="SELECT RDB$FIELD_NAME AS COLUMNA, RDB$FIELD_TYPE AS TIPO, RDB$FIELD_LENGTH AS LONGITUD FROM RDB$RELATION_FIELDS rf JOIN RDB$FIELDS f ON rf.RDB$FIELD_SOURCE = f.RDB$FIELD_NAME WHERE rf.RDB$RELATION_NAME = 'MITABLA'">Columnas de una tabla</button>
                        <button class="sql-example-item" data-sql="SELECT RDB$INDEX_NAME AS INDICE, RDB$RELATION_NAME AS TABLA FROM RDB$INDEXES WHERE RDB$SYSTEM_FLAG = 0">Listar índices</button>
                        <button class="sql-example-item" data-sql="SELECT RDB$PROCEDURE_NAME AS PROCEDIMIENTO FROM RDB$PROCEDURES WHERE RDB$SYSTEM_FLAG = 0">Listar procedimientos</button>
                        <button class="sql-example-item" data-sql="SELECT RDB$TRIGGER_NAME AS TRIGGER, RDB$RELATION_NAME AS TABLA FROM RDB$TRIGGERS WHERE RDB$SYSTEM_FLAG = 0">Listar triggers</button>
                    </div>

                    <!-- Referencia rápida -->
                    <div class="sql-quick-ref">
                        <div class="sql-quick-ref-title">📖 Referencia Rápida Firebird</div>
                        <div class="sql-quick-ref-grid">
                            <div><code>FIRST N</code> Primeros N</div>
                            <div><code>SKIP N</code> Saltar N</div>
                            <div><code>ROWS m TO n</code> Registros m a n</div>
                            <div><code>LIKE</code> Búsqueda parcial</div>
                            <div><code>IN</code> Lista de valores</div>
                            <div><code>BETWEEN</code> Rango</div>
                            <div><code>JOIN</code> Unir tablas</div>
                            <div><code>GROUP BY</code> Agrupar</div>
                            <div><code>HAVING</code> Filtrar grupos</div>
                            <div><code>UNION</code> Combinar queries</div>
                            <div><code>EXISTS</code> Verificar existencia</div>
                            <div><code>CASE</code> Condicionales</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Editor SQL -->
            <div class="sql-editor-wrapper">
                <textarea id="sql-editor" class="sql-editor-textarea"
                    placeholder="SELECT * FROM MITABLA WHERE COLUMNA1 LIKE '%texto%'&#10;&#10;Escribe tu consulta SQL aquí...&#10;Solo se permiten consultas SELECT de lectura.&#10;Reemplaza MITABLA y COLUMNA1 por los nombres reales de tu BD."
                    spellcheck="false"></textarea>
            </div>

            <!-- Atajos de teclado -->
            <div class="sql-editor-footer">
                <span class="sql-shortcut"><kbd>Ctrl+Enter</kbd> Ejecutar</span>
                <span class="sql-shortcut"><kbd>Ctrl+L</kbd> Limpiar</span>
            </div>

            <!-- Historial -->
            <div class="sql-history-section">
                <h4 class="section-title">📜 Historial</h4>
                <div id="sql-history" class="sql-history-list">
                    <div class="empty-state" style="padding: 1rem;">
                        <p class="empty-state-description">Sin consultas recientes</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel derecho: Resultados -->
        <div class="sql-results-panel">
            <div class="sql-results-header">
                <h3 class="section-title">
                    📊 Resultados
                    <span id="sql-results-count" class="badge badge-info">0</span>
                </h3>
                <div id="sql-results-info" class="sql-results-info" style="display: none;">
                    <span id="sql-execution-time"></span>
                </div>
            </div>
            <div id="sql-results-container" class="sql-results-container">
                <div class="empty-state">
                    <div class="empty-state-icon">💻</div>
                    <p class="empty-state-title">Ejecuta una consulta SQL</p>
                    <p class="empty-state-description">Escribe una consulta SELECT en el editor y haz clic en "Ejecutar"</p>
                </div>
            </div>
        </div>
    </div>
</div>
