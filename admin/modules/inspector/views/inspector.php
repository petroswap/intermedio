<div class="inspector-container">
    <!-- Vista: Lista de tablas -->
    <div id="view-tables">
        <div class="inspector-section tables-card">
            <div class="tables-card-header">
                <div class="tables-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
                    <h3>Tablas de la Base de Datos</h3>
                </div>
                <div class="tables-card-actions">
                    <div class="search-input-wrapper">
                        <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="tables-search" class="search-input" placeholder="Buscar tablas...">
                    </div>
                    <button id="btn-refresh-tables" class="btn btn-icon btn-secondary" title="Actualizar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    </button>
                </div>
            </div>
            <div class="data-table-wrapper">
                <table id="tables-datatable" class="data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Tabla</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tables-tbody">
                        <tr><td colspan="2"><div class="loading"><div class="spinner"></div><p>Cargando tablas...</p></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Vista: Datos de tabla -->
    <div id="view-table-data" style="display:none;">
        <!-- Header -->
        <div class="table-data-header">
            <div class="table-data-header-left">
                <button id="btn-back-tables" class="btn btn-back" title="Volver a tablas">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Tablas
                </button>
                <div class="table-data-title-group">
                    <h2 id="current-table-name" class="table-data-title">Tabla</h2>
                    <span id="current-table-count" class="badge badge-primary">0 registros</span>
                </div>
            </div>
            <div class="table-data-header-right">
                <button id="btn-show-last-10" class="btn btn-secondary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Últimos 10
                </button>
                <button id="btn-show-all" class="btn btn-secondary btn-sm">
                    Ver todos
                </button>
                <button id="btn-sql" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    SQL
                </button>
                <button id="btn-relaciones" class="btn btn-secondary btn-sm" style="display:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                    Relaciones
                </button>
            </div>
        </div>

        <div class="inspector-grid">
            <!-- Sidebar -->
            <aside class="inspector-sidebar">
                <!-- Columnas -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <h4 class="sidebar-card-title">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                            Columnas
                        </h4>
                        <div class="sidebar-card-actions">
                            <button id="btn-select-all-cols" class="btn btn-xs btn-ghost">Todas</button>
                            <button id="btn-select-none-cols" class="btn btn-xs btn-ghost">Ninguna</button>
                        </div>
                    </div>
                    <div id="columns-container" class="columns-chips"></div>
                </div>

                <!-- Filtros -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <h4 class="sidebar-card-title">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            Filtros
                        </h4>
                        <button id="btn-clear-filters" class="btn btn-xs btn-ghost">Limpiar</button>
                    </div>
                    <div id="active-filters" class="active-filters"></div>
                    <div class="filter-form">
                        <select id="filter-field" class="form-select form-select-sm">
                            <option value="">Seleccionar campo...</option>
                        </select>
                        <select id="filter-operator" class="form-select form-select-sm">
                            <option value="LIKE">Contiene</option>
                            <option value="=">Igual a</option>
                            <option value="!=">No igual</option>
                            <option value=">">Mayor que</option>
                            <option value="<">Menor que</option>
                            <option value=">=">Mayor o igual</option>
                            <option value="<=">Menor o igual</option>
                            <option value="BETWEEN">Entre (BETWEEN)</option>
                        </select>
                        <input type="text" id="filter-value" class="form-input form-input-sm" placeholder="Valor...">
                        <button id="btn-add-filter" class="btn btn-sm btn-outline">+ Agregar filtro</button>
                    </div>
                    <button id="btn-search" class="btn btn-primary btn-block btn-apply">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Aplicar Filtros
                    </button>
                </div>
            </aside>

            <!-- Main: SQL + Resultados -->
            <main class="inspector-main">
                <!-- SQL Generado (inline) -->
                <div class="sql-generated-strip">
                    <div class="sql-generated-strip-header">
                        <h4 class="sidebar-card-title">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                            SQL Generado
                        </h4>
                        <div class="sql-generated-actions">
                            <button id="btn-reset-sql" class="btn btn-xs btn-ghost" title="Restablecer query inicial" style="display:none;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                Limpiar
                            </button>
                            <button id="btn-copy-sql" class="btn btn-xs btn-ghost" title="Copiar al portapapeles">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                Copiar
                            </button>
                        </div>
                    </div>
                    <pre class="sql-preview"><code id="sql-generated">SELECT * FROM tabla</code></pre>
                </div>

                <!-- Resultados -->
                <div class="results-card inspector-section">
                    <div id="results-container" class="data-table-wrapper results-table-wrapper">
                        <div class="loading"><div class="spinner"></div><p>Cargando datos...</p></div>
                    </div>
                    <div id="results-info" class="results-info" style="display:none;">
                        <div class="results-info-left">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <span><strong id="results-count">0</strong> registros encontrados</span>
                        </div>
                        <span id="results-time" class="results-info-time"></span>
                    </div>
                    <span id="records-shown" style="display:none;">
                        Mostrando <span id="shown-count">0</span> de <span id="total-count">0</span> registros
                    </span>
                    <div id="export-buttons" class="export-buttons" style="display:none;">
                        <button id="btn-export-csv" class="btn btn-xs btn-ghost" title="Exportar a CSV">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            CSV
                        </button>
                        <button id="btn-export-json" class="btn btn-xs btn-ghost" title="Exportar a JSON">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            JSON
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Vista: Relaciones -->
    <div id="view-relationships" style="display:none;">
        <div class="table-data-header">
            <div class="table-data-header-left">
                <button id="btn-back-from-relations" class="btn btn-back" title="Volver a datos">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Volver
                </button>
                <div class="table-data-title-group">
                    <h2 id="relations-table-name" class="table-data-title">Relaciones</h2>
                    <span id="relations-info" class="badge badge-primary">0 relaciones</span>
                </div>
            </div>
            <div class="table-data-header-right">
                <button id="btn-rel-back-data" class="btn btn-secondary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Ver datos
                </button>
            </div>
        </div>
        <div class="relations-container">
            <div id="relations-graph" class="relations-graph"></div>
            <div id="relations-list" class="relations-list">
                <div class="relations-list-section">
                    <h4 class="relations-list-title">Referencia a (padres)</h4>
                    <div id="relations-parents" class="relations-list-items"></div>
                </div>
                <div class="relations-list-section">
                    <h4 class="relations-list-title">Referenciada por (hijos)</h4>
                    <div id="relations-children" class="relations-list-items"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Connection Test Modal -->
<div id="connection-modal" class="modal" style="display:none;">
    <div class="modal-overlay"></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Test de Conexión
            </h3>
            <button class="modal-close" id="btn-close-connection-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div id="connection-modal-content">
                <div class="loading"><div class="spinner"></div><p>Verificando conexión...</p></div>
            </div>
        </div>
        <div class="modal-footer">
            <button id="btn-close-connection" class="btn btn-secondary">Cerrar</button>
        </div>
    </div>
</div>

<!-- SQL Modal -->
<div id="sql-modal" class="modal" style="display:none;">
    <div class="modal-overlay"></div>
    <div class="modal-content modal-md">
        <div class="modal-header">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                Ejecutar SQL
            </h3>
            <button class="modal-close" id="btn-close-sql-modal">&times;</button>
        </div>
        <div class="modal-body">
            <textarea id="sql-input" class="sql-editor" rows="6" placeholder="Escribe tu consulta SQL aquí..."></textarea>
            <p class="sql-editor-hint">Solo se permiten consultas SELECT</p>
            <div class="sql-suggestions">
                <p class="sql-suggestions-title">Sugerencias:</p>
                <div class="sql-suggestions-list">
                    <div class="sql-suggestion-group">
                        <span class="sql-suggestion-group-label">Básico</span>
                        <button class="sql-suggestion" data-sql="SELECT * FROM {TABLE}">Todos los campos</button>
                        <button class="sql-suggestion" data-sql="SELECT FIRST 10 * FROM {TABLE}">Primeros 10</button>
                        <button class="sql-suggestion" data-sql="SELECT * FROM {TABLE} ORDER BY 1">Ordenar por 1ra columna</button>
                        <button class="sql-suggestion" data-sql="SELECT COUNT(*) AS total FROM {TABLE}">Contar registros</button>
                    </div>
                    <div class="sql-suggestion-group">
                        <span class="sql-suggestion-group-label">Filtros</span>
                        <button class="sql-suggestion" data-sql="SELECT * FROM {TABLE} WHERE {COL} = ''">Igual a valor</button>
                        <button class="sql-suggestion" data-sql="SELECT * FROM {TABLE} WHERE {COL} LIKE '%texto%'">Búsqueda parcial</button>
                        <button class="sql-suggestion" data-sql="SELECT * FROM {TABLE} WHERE {COL} BETWEEN '' AND ''">Rango BETWEEN</button>
                        <button class="sql-suggestion" data-sql="SELECT * FROM {TABLE} WHERE {COL} IN ('', '')">Lista IN</button>
                    </div>
                    <div class="sql-suggestion-group">
                        <span class="sql-suggestion-group-label">Avanzado</span>
                        <button class="sql-suggestion" data-sql="SELECT DISTINCT {COL} FROM {TABLE}">Valores únicos</button>
                        <button class="sql-suggestion" data-sql="SELECT {COL}, COUNT(*) AS total FROM {TABLE} GROUP BY {COL}">Agrupar por columna</button>
                        <button class="sql-suggestion" data-sql="SELECT * FROM {TABLE} WHERE {COL} IS NOT NULL">No nulos</button>
                        <button class="sql-suggestion" data-sql="SELECT FIRST 100 * FROM {TABLE} ROWS 1 TO 100">ROWS 1 a 100</button>
                    </div>
                </div>
            </div>

            <!-- Favoritos en modal (oculto - en desarrollo) -->
            <div class="modal-favorites" style="display:none;">
                <div class="modal-favorites-header">
                    <span class="modal-favorites-title">⭐ Favoritos</span>
                    <div class="modal-favorites-actions">
                        <input type="file" id="import-favorites-input" accept=".json" style="display:none;">
                        <button id="btn-import-favorites" class="btn btn-xs btn-ghost" title="Importar favoritos desde JSON">Importar</button>
                        <button id="btn-export-favorites" class="btn btn-xs btn-ghost" title="Exportar favoritos a JSON">Exportar</button>
                    </div>
                </div>
                <div id="modal-favorites-list" class="modal-favorites-list">
                    <div class="empty-state" style="padding: 0.5rem;"><p class="empty-state-description">Sin consultas guardadas</p></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button id="btn-cancel-sql" class="btn btn-secondary">Cancelar</button>
            <button id="btn-execute-sql" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                Ejecutar
            </button>
        </div>
    </div>
</div>
