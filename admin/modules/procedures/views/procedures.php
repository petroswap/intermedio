<div class="inspector-container unified-layout procedures-layout">

    <!-- Password Modal (misma lógica que Scripts: verify_password.php + sesión scripts_auth) -->
    <div id="proc-password-modal" class="modal" style="display:none;">
        <div class="modal-overlay"></div>
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3 class="modal-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Confirmar Ejecución
                </h3>
                <button class="modal-close" id="proc-password-close">&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Introduce la contraseña para ejecutar (misma contraseña que los scripts).</p>
                <div class="form-group">
                    <label class="form-label" for="proc-password">Contraseña</label>
                    <input type="password" id="proc-password" class="form-input" placeholder="Contraseña..." autofocus>
                </div>
                <div id="proc-password-error" class="alert alert-danger" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button id="proc-password-cancel" class="btn btn-secondary">Cancelar</button>
                <button id="proc-password-submit" class="btn btn-primary">Ejecutar</button>
            </div>
        </div>
    </div>

    <!-- Panel izquierdo: listado de procedures -->
    <div id="view-procs" class="unified-left">
        <div class="inspector-section tables-card">
            <div class="tables-card-header">
                <div class="tables-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    <h3>Procedures</h3>
                </div>
                <div class="tables-card-actions">
                    <button id="btn-refresh-procs" class="btn btn-icon btn-secondary" title="Actualizar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    </button>
                </div>
            </div>
            <div class="unified-search">
                <div class="search-input-wrapper">
                    <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="procs-search" class="search-input" placeholder="Buscar procedures...">
                </div>
            </div>
            <div class="data-table-wrapper unified-tables-wrapper">
                <table id="procs-datatable" class="data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Procedure</th>
                        </tr>
                    </thead>
                    <tbody id="procs-tbody">
                        <tr><td colspan="1"><div class="loading"><div class="spinner"></div><p>Cargando procedures...</p></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Panel derecho: detalle del procedure -->
    <div class="unified-right">
        <!-- Placeholder -->
        <div id="proc-placeholder" class="unified-placeholder">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="16 18 22 12 16 6"/>
                        <polyline points="8 6 2 12 8 18"/>
                    </svg>
                </div>
                <h3 class="empty-state-title">Selecciona un procedure</h3>
                <p class="empty-state-description">Haz clic en un procedure del listado para ver sus parámetros, código fuente, dependencias y ejecutarlo.</p>
            </div>
        </div>

        <!-- Detalle -->
        <div id="proc-detail" style="display:none;">
            <div class="table-data-header">
                <div class="table-data-header-left">
                    <div class="table-data-title-group">
                        <h2 id="proc-name" class="table-data-title">Procedure</h2>
                        <span id="proc-type-badge" class="badge badge-primary">—</span>
                    </div>
                </div>
            </div>

            <div class="proc-tabs">
                <button class="proc-tab active" data-tab="info">Info</button>
                <button class="proc-tab" data-tab="params">Parámetros</button>
                <button class="proc-tab" data-tab="source">Código</button>
                <button class="proc-tab" data-tab="execute">Ejecutar</button>
            </div>

            <!-- Tab: Info -->
            <div id="tab-info" class="proc-tab-panel active">
                <div class="proc-info-grid">
                    <div class="proc-info-card">
                        <h4 class="proc-info-label">Tablas / vistas referenciadas</h4>
                        <div id="proc-deps" class="proc-tag-list"></div>
                    </div>
                    <div class="proc-info-card">
                        <h4 class="proc-info-label">Usado por (triggers / procedures)</h4>
                        <div id="proc-callers" class="proc-tag-list"></div>
                    </div>
                    <div class="proc-info-card">
                        <h4 class="proc-info-label">Comentarios</h4>
                        <p id="proc-comments" class="proc-comments-text proc-comments-empty">Sin comentarios registrados</p>
                    </div>
                </div>
            </div>

            <!-- Tab: Parámetros -->
            <div id="tab-params" class="proc-tab-panel">
                <div class="data-table-wrapper">
                    <table class="data-table proc-params-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Dirección</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody id="proc-params-tbody">
                            <tr><td colspan="4"><div class="loading"><div class="spinner"></div><p>Cargando parámetros...</p></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Código fuente -->
            <div id="tab-source" class="proc-tab-panel">
                <div class="sql-generated-strip">
                    <div class="sql-generated-strip-header">
                        <h4 class="sidebar-card-title">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                            Fuente (ALTER PROCEDURE)
                        </h4>
                        <button id="btn-copy-source" class="btn btn-xs btn-ghost" title="Copiar al portapapeles">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            Copiar
                        </button>
                    </div>
                    <pre class="sql-preview proc-source-preview"><code id="proc-source">—</code></pre>
                </div>
            </div>

            <!-- Tab: Ejecutar -->
            <div id="tab-execute" class="proc-tab-panel">
                <div class="proc-exec-modes">
                    <label class="proc-exec-mode">
                        <input type="radio" name="proc-exec-mode" value="procedure" checked>
                        <span>Procedure existente</span>
                    </label>
                    <label class="proc-exec-mode">
                        <input type="radio" name="proc-exec-mode" value="block">
                        <span>Bloque anónimo (EXECUTE BLOCK)</span>
                    </label>
                </div>

                <!-- Modo: procedure existente -->
                <div id="exec-mode-procedure" class="proc-exec-section">
                    <div id="proc-params-form" class="proc-params-form"></div>
                    <div class="proc-exec-actions">
                        <button id="btn-exec-proc" class="btn btn-primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            Ejecutar procedure
                        </button>
                    </div>
                    <p class="proc-exec-warning">
                        <strong>Aviso:</strong> procedures tipo 2 (ejecutables) pueden modificar datos de forma <strong>permanente</strong>. Los procedures tipo 1 (seleccionables / con SUSPEND) solo leen.
                    </p>
                </div>

                <!-- Modo: bloque anónimo -->
                <div id="exec-mode-block" class="proc-exec-section" style="display:none;">
                    <p class="proc-block-hint">
                        Escribe un bloque <code>EXECUTE BLOCK</code> completo. Firebird lo ejecuta en la sesión actual y <strong>no lo crea</strong> en la base de datos. Usa <code>RETURNS (...)</code> + <code>SUSPEND</code> si quieres devolver filas.
                    </p>
                    <textarea id="proc-block-input" class="sql-editor" rows="8" placeholder="EXECUTE BLOCK RETURNS (RESULTADO VARCHAR(50)) AS&#10;BEGIN&#10;  RESULTADO = 'Hola';&#10;  SUSPEND;&#10;END"></textarea>
                    <div class="proc-exec-actions">
                        <button id="btn-exec-block" class="btn btn-primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            Ejecutar bloque
                        </button>
                    </div>
                    <p class="proc-exec-warning">
                        <strong>Aviso:</strong> el bloque puede contener DML (INSERT/UPDATE/DELETE) que se confirma en la transacción. No puede contener DDL (CREATE/ALTER/DROP).
                    </p>
                </div>

                <!-- Resultado -->
                <div id="proc-exec-result" class="proc-exec-result" style="display:none;">
                    <div class="results-info">
                        <div class="results-info-left">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <span><strong id="proc-exec-count">0</strong> filas · <span id="proc-exec-time">0</span> ms</span>
                        </div>
                        <div class="export-buttons">
                            <button id="btn-proc-export-csv" class="btn btn-xs btn-ghost" title="Exportar a CSV">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                CSV
                            </button>
                            <button id="btn-proc-export-json" class="btn btn-xs btn-ghost" title="Exportar a JSON">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                JSON
                            </button>
                        </div>
                    </div>
                    <div id="proc-exec-container" class="data-table-wrapper results-table-wrapper"></div>
                </div>
            </div>
        </div>
    </div>
</div>
