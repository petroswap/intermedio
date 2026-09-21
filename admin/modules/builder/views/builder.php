<div class="builder-container">
    <div class="builder-layout">
        <!-- Panel izquierdo: Configuracion -->
        <div class="builder-config-panel">
            <!-- Tabla principal -->
            <div class="builder-section">
                <h3 class="builder-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                    Tabla principal
                </h3>
                <div class="builder-table-row">
                    <select id="builder-table" class="form-select">
                        <option value="">Seleccionar tabla...</option>
                    </select>
                    <button id="builder-reset" class="btn btn-xs btn-ghost" title="Limpiar todo (Ctrl+L)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                    </button>
                </div>
            </div>

            <!-- Columnas -->
            <div class="builder-section" id="builder-columns-section" style="display:none;">
                <h3 class="builder-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Columnas
                </h3>
                <div class="builder-columns-actions">
                    <button id="builder-select-all" class="btn btn-xs btn-ghost">Todas</button>
                    <button id="builder-select-none" class="btn btn-xs btn-ghost">Ninguna</button>
                    <label class="builder-distinct-toggle">
                        <input type="checkbox" id="builder-distinct"> DISTINCT
                    </label>
                </div>
                <div id="builder-columns-list" class="builder-checkbox-list"></div>
            </div>

            <!-- JOINs -->
            <div class="builder-section" id="builder-joins-section" style="display:none;">
                <h3 class="builder-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    JOINs
                    <button id="builder-add-join" class="btn btn-xs btn-primary" title="Agregar JOIN">+</button>
                </h3>
                <div id="builder-joins-list" class="builder-joins-list"></div>
            </div>

            <!-- Filtros -->
            <div class="builder-section" id="builder-filters-section">
                <h3 class="builder-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    Filtros
                    <button id="builder-add-filter" class="btn btn-xs btn-primary" title="Agregar filtro">+</button>
                </h3>
                <div id="builder-filters-list" class="builder-filters-list"></div>
            </div>

            <!-- GROUP BY -->
            <div class="builder-section" id="builder-group-section" style="display:none;">
                <h3 class="builder-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    GROUP BY
                </h3>
                <select id="builder-group-by" class="form-select form-select-sm">
                    <option value="">Sin agrupacion</option>
                </select>
            </div>

            <!-- Orden -->
            <div class="builder-section">
                <h3 class="builder-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                    Ordenar por
                </h3>
                <div class="builder-order-row">
                    <select id="builder-order-column" class="form-select form-select-sm">
                        <option value="">Sin orden</option>
                    </select>
                    <select id="builder-order-direction" class="form-select form-select-sm">
                        <option value="ASC">ASC</option>
                        <option value="DESC">DESC</option>
                    </select>
                </div>
            </div>

            <!-- Limite -->
            <div class="builder-section">
                <h3 class="builder-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg>
                    Limite (ROWS)
                </h3>
                <input type="number" id="builder-limit" class="form-input form-input-sm" value="100" min="1" max="10000">
            </div>
        </div>

        <!-- Panel derecho: SQL + Resultados -->
        <div class="builder-sql-panel">
            <div class="builder-sql-header">
                <h3 class="builder-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    SQL Generado
                </h3>
                <div class="builder-sql-actions">
                    <button id="builder-copy-sql" class="btn btn-xs btn-ghost" title="Copiar SQL (Ctrl+C)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        Copiar
                    </button>
                    <button id="builder-open-sql" class="btn btn-xs btn-ghost" title="Abrir en Consola SQL">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                        Consola SQL
                    </button>
                </div>
            </div>
            <div class="builder-sql-content">
                <pre id="builder-sql-output" class="builder-sql-code">SELECT
    *
FROM TABLA</pre>
            </div>
            <div class="builder-sql-footer">
                <button id="builder-execute" class="btn btn-primary btn-sm" disabled>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Ejecutar
                </button>
                <span class="builder-shortcut-hint">Ctrl+Enter</span>
            </div>

            <!-- Resultados -->
            <div id="builder-results-container" class="builder-results-container" style="display:none;">
                <div class="builder-results-header">
                    <span id="builder-results-count" class="badge badge-info">0</span>
                    <span id="builder-results-time" class="builder-results-time"></span>
                    <div class="builder-results-export">
                        <button id="builder-export-csv" class="btn btn-xs btn-ghost">CSV</button>
                        <button id="builder-export-json" class="btn btn-xs btn-ghost">JSON</button>
                    </div>
                </div>
                <div id="builder-results-table" class="builder-results-table"></div>
            </div>
        </div>
    </div>
</div>
