<div class="sql-module-container">
    <div class="sql-module-header">
        <h2 class="sql-module-title">
            <span class="icon">💻</span>
            SQL Custom
        </h2>
        <div id="sql-results-info" class="sql-results-info-header" style="display: none;">
            <span id="sql-results-count" class="badge badge-info">0</span>
            <span id="sql-execution-time"></span>
            <div id="sql-export-buttons" class="export-buttons" style="display:none;">
                <button id="btn-sql-export-csv" class="btn btn-xs btn-ghost" title="CSV">CSV</button>
                <button id="btn-sql-export-json" class="btn btn-xs btn-ghost" title="JSON">JSON</button>
            </div>
        </div>
    </div>

    <div class="sql-module-layout">
        <!-- Panel izquierdo: Editor -->
        <div class="sql-editor-panel">
            <!-- Editor SQL -->
            <div class="sql-editor-wrapper">
                <textarea id="sql-editor" class="sql-editor-textarea"
                    placeholder="SELECT * FROM MITABLA WHERE COLUMNA1 LIKE '%texto%'"
                    spellcheck="false"></textarea>
                <!-- Acciones del editor (flotantes) -->
                <div class="sql-editor-footer">
                    <button id="btn-sql-clear" class="btn btn-secondary btn-sm">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Limpiar
                    </button>
                    <button id="btn-save-bookmark" class="btn btn-secondary btn-sm" title="Guardar como favorito">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        Guardar
                    </button>
                    <button id="btn-sql-execute" class="btn btn-primary btn-sm">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        Ejecutar
                    </button>
                </div>
            </div>

            <!-- Pestañas dentro de card -->
            <div class="sql-tabs-card">
                <div class="sql-tabs">
                    <button class="sql-tab active" data-tab="favorites">⭐ Favoritos</button>
                    <button class="sql-tab" data-tab="examples">📖 Ejemplos</button>
                </div>

                <div class="sql-tab-content">
                    <div class="sql-tab-pane active" id="tab-favorites">
                        <div class="sql-tab-pane-header">
                            <input type="file" id="sql-import-favorites-input" accept=".json" style="display:none;">
                            <button id="btn-sql-import-favorites" class="btn btn-xs btn-ghost" title="Importar">📥</button>
                            <button id="btn-sql-export-favorites" class="btn btn-xs btn-ghost" title="Exportar">📤</button>
                        </div>
                        <div id="sql-bookmarks" class="sql-favorites-list">
                            <div class="empty-state" style="padding: 0.5rem;">
                                <p class="empty-state-description">Sin consultas guardadas</p>
                            </div>
                        </div>
                    </div>

                    <div class="sql-tab-pane" id="tab-examples">
                        <div class="sql-examples-compact">
                            <div class="sql-example-chip-group">
                                <span class="sql-example-chip-label">Básico</span>
                                <button class="sql-example-chip" data-sql="SELECT * FROM MITABLA">SELECT *</button>
                                <button class="sql-example-chip" data-sql="SELECT FIRST 10 * FROM MITABLA ORDER BY 1">FIRST 10</button>
                                <button class="sql-example-chip" data-sql="SELECT COUNT(*) AS TOTAL FROM MITABLA">COUNT</button>
                                <button class="sql-example-chip" data-sql="SELECT DISTINCT COLUMNA1 FROM MITABLA">DISTINCT</button>
                            </div>
                            <div class="sql-example-chip-group">
                                <span class="sql-example-chip-label">Filtros</span>
                                <button class="sql-example-chip" data-sql="SELECT * FROM MITABLA WHERE COLUMNA1 = 'valor'">WHERE =</button>
                                <button class="sql-example-chip" data-sql="SELECT * FROM MITABLA WHERE COLUMNA1 LIKE '%texto%'">LIKE</button>
                                <button class="sql-example-chip" data-sql="SELECT * FROM MITABLA WHERE COLUMNA1 BETWEEN 100 AND 500">BETWEEN</button>
                                <button class="sql-example-chip" data-sql="SELECT * FROM MITABLA WHERE COLUMNA1 IN ('A', 'B')">IN</button>
                            </div>
                            <div class="sql-example-chip-group">
                                <span class="sql-example-chip-label">Orden</span>
                                <button class="sql-example-chip" data-sql="SELECT * FROM MITABLA ORDER BY COLUMNA1 ASC">ASC</button>
                                <button class="sql-example-chip" data-sql="SELECT * FROM MITABLA ORDER BY COLUMNA1 DESC">DESC</button>
                                <button class="sql-example-chip" data-sql="SELECT * FROM MITABLA ROWS 1 TO 20">ROWS 1-20</button>
                                <button class="sql-example-chip" data-sql="SELECT SKIP 10 FIRST 10 * FROM MITABLA">SKIP+FIRST</button>
                            </div>
                            <div class="sql-example-chip-group">
                                <span class="sql-example-chip-label">Fechas</span>
                                <button class="sql-example-chip" data-sql="SELECT CURRENT_DATE FROM RDB\$DATABASE">DATE</button>
                                <button class="sql-example-chip" data-sql="SELECT CURRENT_TIMESTAMP FROM RDB\$DATABASE">TIMESTAMP</button>
                                <button class="sql-example-chip" data-sql="SELECT EXTRACT(YEAR FROM COLUMNA1) FROM MITABLA">EXTRACT</button>
                                <button class="sql-example-chip" data-sql="SELECT DATEADD(DAY, 30, COLUMNA1) FROM MITABLA">DATEADD</button>
                            </div>
                            <div class="sql-example-chip-group">
                                <span class="sql-example-chip-label">Agrupar</span>
                                <button class="sql-example-chip" data-sql="SELECT COLUMNA1, COUNT(*) AS TOTAL FROM MITABLA GROUP BY COLUMNA1">GROUP BY</button>
                                <button class="sql-example-chip" data-sql="SELECT COLUMNA1, SUM(COLUMNA2) AS SUMA FROM MITABLA GROUP BY COLUMNA1">SUM</button>
                                <button class="sql-example-chip" data-sql="SELECT COLUMNA1, AVG(COLUMNA2) AS PROM FROM MITABLA GROUP BY COLUMNA1">AVG</button>
                                <button class="sql-example-chip" data-sql="SELECT COLUMNA1, COUNT(*) AS T FROM MITABLA GROUP BY COLUMNA1 HAVING COUNT(*) > 5">HAVING</button>
                            </div>
                            <div class="sql-example-chip-group">
                                <span class="sql-example-chip-label">JOINs</span>
                                <button class="sql-example-chip" data-sql="SELECT a.ID, b.NOMBRE FROM TABLA_A a JOIN TABLA_B b ON a.ID = b.ID_A">INNER</button>
                                <button class="sql-example-chip" data-sql="SELECT a.ID, b.NOMBRE FROM TABLA_A a LEFT JOIN TABLA_B b ON a.ID = b.ID_A">LEFT</button>
                                <button class="sql-example-chip" data-sql="SELECT a.ID, b.NOMBRE, c.DETALLE FROM TABLA_A a JOIN TABLA_B b ON a.ID = b.ID_A JOIN TABLA_C c ON b.ID = c.ID_B">3x</button>
                            </div>
                            <div class="sql-example-chip-group">
                                <span class="sql-example-chip-label">Avanzado</span>
                                <button class="sql-example-chip" data-sql="SELECT * FROM MITABLA WHERE ID IN (SELECT ID_A FROM TABLA_B WHERE COLUMNA1 > 100)">SUB IN</button>
                                <button class="sql-example-chip" data-sql="WITH CTE AS (SELECT ID, COLUMNA1 FROM MITABLA WHERE COLUMNA1 > 100) SELECT * FROM CTE">CTE</button>
                                <button class="sql-example-chip" data-sql="SELECT COLUMNA1, COLUMNA2, ROW_NUMBER() OVER (ORDER BY COLUMNA2 DESC) AS RANK FROM MITABLA">WINDOW</button>
                                <button class="sql-example-chip" data-sql="SELECT COLUMNA1, CASE WHEN COLUMNA1 > 100 THEN 'Alto' ELSE 'Bajo' END AS CLASE FROM MITABLA">CASE</button>
                            </div>
                            <div class="sql-example-chip-group">
                                <span class="sql-example-chip-label">Metadatos</span>
                                <button class="sql-example-chip" data-sql="SELECT RDB\$RELATION_NAME AS TABLA FROM RDB\$RELATIONS WHERE RDB\$SYSTEM_FLAG = 0 ORDER BY RDB\$RELATION_NAME">Tablas</button>
                                <button class="sql-example-chip" data-sql="SELECT RDB\$FIELD_NAME AS COLUMNA, RDB\$FIELD_TYPE AS TIPO FROM RDB\$RELATION_FIELDS rf JOIN RDB\$FIELDS f ON rf.RDB\$FIELD_SOURCE = f.RDB\$FIELD_NAME WHERE rf.RDB\$RELATION_NAME = 'MITABLA'">Columnas</button>
                                <button class="sql-example-chip" data-sql="SELECT RDB\$INDEX_NAME AS INDICE, RDB\$RELATION_NAME AS TABLA FROM RDB\$INDEXES WHERE RDB\$SYSTEM_FLAG = 0">Índices</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel derecho: Resultados -->
        <div class="sql-results-panel">
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
