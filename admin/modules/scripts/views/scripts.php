<div class="scripts-container">
    <!-- Password Modal -->
    <div id="scripts-password-modal" class="modal" style="display: none;">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3 class="modal-title">🔒 Confirmar Ejecución</h3>
            </div>
            <div class="modal-body">
                <p class="text-muted">Introduce la contraseña para ejecutar este script.</p>
                <div class="form-group">
                    <label class="form-label" for="scripts-password">Contraseña</label>
                    <input type="password" id="scripts-password" class="form-input" placeholder="Contraseña..." autofocus>
                </div>
                <div id="scripts-password-error" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button id="scripts-password-cancel" class="btn btn-secondary">Cancelar</button>
                <button id="scripts-password-submit" class="btn btn-primary">Ejecutar</button>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="scripts-layout">
        <!-- Left: Script List -->
        <div class="scripts-panel-left">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Scripts</h3>
                    <div class="card-actions">
                        <button id="btn-scripts-refresh" class="btn btn-sm btn-ghost" title="Recargar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                        </button>
                    </div>
                </div>
                <div class="card-body scripts-list-body">
                    <div id="scripts-list">
                        <div class="loading"><div class="spinner"></div><p>Cargando...</p></div>
                    </div>
                </div>
            </div>
            
            <!-- History -->
            <div class="card" style="margin-top: 1rem;">
                <div class="card-header">
                    <h3 class="card-title">Historial</h3>
                    <div class="card-actions">
                        <button id="btn-history-clear" class="btn btn-sm btn-ghost" title="Limpiar">🗑️</button>
                    </div>
                </div>
                <div class="card-body scripts-history-body">
                    <div id="scripts-history">
                        <p class="text-muted" style="font-size: 0.8rem;">Sin ejecuciones</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Request/Response Panel -->
        <div class="scripts-panel-right">
            <div class="card">
                <div class="card-header">
                    <div class="scripts-request-header">
                        <span id="script-method-badge" class="badge badge-info">POST</span>
                        <span id="script-current-name" class="scripts-current-name">Selecciona un script</span>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="scripts-tabs">
                    <button class="scripts-tab active" data-tab="params">Params</button>
                    <button class="scripts-tab" data-tab="headers">Headers</button>
                    <button class="scripts-tab" data-tab="raw">Raw</button>
                </div>
                
                <div class="card-body scripts-request-body">
                    <!-- Placeholder -->
                    <div id="script-placeholder" class="empty-state">
                        <div class="empty-state-icon">▶️</div>
                        <p class="empty-state-title">Selecciona un script</p>
                        <p class="empty-state-description">Elige un script de la izquierda para comenzar</p>
                    </div>
                    
                    <!-- Params Tab -->
                    <div id="tab-params" class="scripts-tab-content" style="display: none;">
                        <div id="script-params-fields"></div>
                        <div id="script-no-params" class="text-muted" style="font-size: 0.85rem; padding: 0.5rem;">Este script no requiere parámetros</div>
                    </div>
                    
                    <!-- Headers Tab -->
                    <div id="tab-headers" class="scripts-tab-content" style="display: none;">
                        <div class="scripts-param-row">
                            <label>Content-Type</label>
                            <input type="text" class="form-input" value="application/json" readonly>
                        </div>
                        <div class="scripts-param-row">
                            <label>Authorization</label>
                            <input type="text" class="form-input" id="script-auth-header" value="Bearer ***" readonly>
                        </div>
                    </div>
                    
                    <!-- Raw Tab -->
                    <div id="tab-raw" class="scripts-tab-content" style="display: none;">
                        <textarea id="script-raw-body" class="form-input" rows="8" readonly style="font-family: monospace; font-size: 12px;"></textarea>
                    </div>
                </div>
                
                <!-- Execute Button -->
                <div class="card-footer" id="script-footer" style="display: none;">
                    <button id="btn-script-confirm" class="btn btn-danger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        Ejecutar con contraseña
                    </button>
                </div>
            </div>
            
            <!-- Response Panel -->
            <div class="card" style="margin-top: 1rem;" id="response-panel" style="display: none;">
                <div class="card-header">
                    <div class="scripts-response-header">
                        <h3 class="card-title">Respuesta</h3>
                        <div id="response-meta" class="scripts-response-meta" style="display: none;">
                            <span id="response-status" class="badge badge-success">200</span>
                            <span id="response-time" class="text-muted"></span>
                            <span id="response-size" class="text-muted"></span>
                        </div>
                    </div>
                </div>
                <div class="card-body scripts-response-body">
                    <div id="script-result"></div>
                </div>
                <div class="card-footer" id="response-footer" style="display: none;">
                    <button class="btn btn-sm btn-outline" id="btn-copy-output">📋 Copiar</button>
                    <button class="btn btn-sm btn-outline" id="btn-export-json">📥 JSON</button>
                    <button class="btn btn-sm btn-outline" id="btn-export-csv">📥 CSV</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.scripts-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1rem;
    min-height: 600px;
}
.scripts-panel-left .card,
.scripts-panel-right > .card:first-child {
    height: auto;
}
.scripts-panel-left {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.scripts-list-body {
    overflow-y: auto;
    max-height: 400px;
    padding: 0;
}
.scripts-history-body {
    overflow-y: auto;
    max-height: 200px;
    padding: 0.5rem;
}
.scripts-request-body {
    min-height: 200px;
}
.scripts-response-body {
    min-height: 150px;
    max-height: 400px;
    overflow-y: auto;
}
.scripts-request-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.scripts-current-name {
    font-weight: 500;
}
.scripts-response-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}
.scripts-response-meta {
    display: flex;
    gap: 0.75rem;
    font-size: 0.8rem;
}
.scripts-tabs {
    display: flex;
    border-bottom: 1px solid var(--border-color);
    background: var(--surface-secondary);
}
.scripts-tab {
    padding: 0.5rem 1rem;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 0.85rem;
    color: var(--text-muted);
    border-bottom: 2px solid transparent;
}
.scripts-tab:hover {
    color: var(--text-primary);
}
.scripts-tab.active {
    color: var(--brand-600);
    border-bottom-color: var(--brand-600);
}
.scripts-tab-content {
    padding: 0.75rem;
}
.scripts-param-row {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 0.5rem;
    align-items: center;
    margin-bottom: 0.5rem;
}
.scripts-param-row label {
    font-size: 0.85rem;
    font-weight: 500;
}
.scripts-param-row .form-input {
    font-size: 0.85rem;
    padding: 0.4rem 0.6rem;
}
.scripts-script-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.6rem 0.75rem;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: background 0.15s;
}
.scripts-script-item:hover {
    background: var(--surface-secondary);
}
.scripts-script-item.active {
    background: var(--brand-50);
    border-left: 3px solid var(--brand-500);
}
.scripts-script-info h4 {
    margin: 0;
    font-size: 0.85rem;
}
.scripts-script-info p {
    margin: 0;
    font-size: 0.75rem;
    color: var(--text-muted);
}
.scripts-history-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.4rem 0.5rem;
    border-bottom: 1px solid var(--border-color);
    font-size: 0.8rem;
    cursor: pointer;
}
.scripts-history-item:hover {
    background: var(--surface-secondary);
}
.scripts-history-item .history-name {
    font-weight: 500;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.scripts-history-item .history-time {
    color: var(--text-muted);
    font-size: 0.7rem;
    margin-left: 0.5rem;
}
.scripts-history-item .history-status {
    margin-left: 0.5rem;
}
.badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-info {
    background: var(--info-bg);
    color: var(--info);
}
.badge-success {
    background: var(--success-bg);
    color: var(--success);
}
.badge-danger {
    background: var(--error-bg);
    color: var(--error);
}
.scripts-result-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
}
.scripts-result-table th,
.scripts-result-table td {
    padding: 0.4rem 0.6rem;
    border: 1px solid var(--border-color);
    text-align: left;
}
.scripts-result-table th {
    background: var(--surface-secondary);
    font-weight: 600;
    position: sticky;
    top: 0;
}
.scripts-result-table tr:hover {
    background: var(--surface-secondary);
}
@media (max-width: 768px) {
    .scripts-layout {
        grid-template-columns: 1fr;
    }
}
</style>
