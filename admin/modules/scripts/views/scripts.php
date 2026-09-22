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
            <div class="card" style="height: 100%;">
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
        </div>

        <!-- Right: Script Detail + Results -->
        <div class="scripts-panel-right">
            <!-- Placeholder -->
            <div id="script-placeholder" class="card" style="height: 100%; display: flex; align-items: center; justify-content: center;">
                <div class="empty-state">
                    <div class="empty-state-icon">▶️</div>
                    <p class="empty-state-title">Selecciona un script</p>
                    <p class="empty-state-description">Elige un script de la izquierda para comenzar</p>
                </div>
            </div>

            <!-- Script Content (hidden initially) -->
            <div id="script-content" style="display: none;">
                <!-- Info Card -->
                <div class="card">
                    <div class="card-body">
                        <h3 id="script-name" style="margin: 0 0 0.25rem 0;"></h3>
                        <p id="script-description" class="text-muted" style="margin: 0; font-size: 0.9rem;"></p>
                    </div>
                </div>

                <!-- Params Card -->
                <div class="card" id="script-params-card" style="display: none; margin-top: 1rem;">
                    <div class="card-header">
                        <h3 class="card-title">Parámetros</h3>
                    </div>
                    <div class="card-body">
                        <div id="script-params-fields"></div>
                    </div>
                </div>

                <!-- Execute Card -->
                <div class="card" style="margin-top: 1rem;">
                    <div class="card-body" style="display: flex; align-items: center; justify-content: space-between;">
                        <span id="script-method-badge" class="badge badge-info">POST</span>
                        <button id="btn-script-confirm" class="btn btn-danger">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            Ejecutar con contraseña
                        </button>
                    </div>
                </div>

                <!-- Results Card -->
                <div class="card" style="margin-top: 1rem;" id="response-panel">
                    <div class="card-header">
                        <h3 class="card-title">Resultado</h3>
                        <div id="response-meta" class="scripts-response-meta" style="display: none;">
                            <span id="response-status" class="badge badge-success">200</span>
                            <span id="response-time" class="text-muted"></span>
                            <span id="response-size" class="text-muted"></span>
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
</div>

<style>
.scripts-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    min-height: 600px;
}
.scripts-panel-left .card,
.scripts-panel-right > .card {
    height: 100%;
}
.scripts-list-body {
    overflow-y: auto;
    max-height: 550px;
    padding: 0;
}
.scripts-response-body {
    min-height: 100px;
    max-height: 400px;
    overflow-y: auto;
}
.scripts-response-meta {
    display: flex;
    gap: 0.75rem;
    font-size: 0.8rem;
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
.scripts-script-item h4 {
    margin: 0;
    font-size: 0.85rem;
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
@media (max-width: 768px) {
    .scripts-layout {
        grid-template-columns: 1fr;
    }
}
</style>
