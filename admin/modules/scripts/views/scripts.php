<div class="scripts-container">
    <!-- Password Modal -->
    <div id="scripts-password-modal" class="modal" style="display: none;">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3 class="modal-title">🔒 Confirmar Ejecución</h3>
            </div>
            <div class="modal-body">
                <p class="text-muted">Introduce la contraseña de administrador para ejecutar este script.</p>
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
        <!-- Left Column: Scripts List (1/3) -->
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
        </div>

        <!-- Right Column: Execution Panel (2/3) -->
        <div class="scripts-panel-right">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Resultado: <span id="script-current-name">Selecciona un script</span></h3>
                </div>
                <div class="card-body scripts-result-body">
                    <div id="script-placeholder" class="empty-state">
                        <div class="empty-state-icon">▶️</div>
                        <p class="empty-state-title">Sin ejecutar</p>
                        <p class="empty-state-description">Selecciona un script de la izquierda</p>
                    </div>
                    
                    <!-- Parameters Form -->
                    <div id="script-params" style="display: none; margin-bottom: 1rem;">
                        <h4 style="margin: 0 0 0.5rem; font-size: 0.9rem;">Parámetros</h4>
                        <div id="script-params-fields"></div>
                    </div>
                    
                    <div id="script-result" style="display: none;"></div>
                </div>
                <div class="card-footer" id="script-footer" style="display: none;">
                    <button id="btn-script-confirm" class="btn btn-danger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        Ejecutar Script
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.scripts-layout {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1rem;
    min-height: 500px;
}
.scripts-panel-left .card,
.scripts-panel-right .card {
    height: 100%;
    display: flex;
    flex-direction: column;
}
.scripts-list-body {
    overflow-y: auto;
    max-height: 600px;
}
.scripts-result-body {
    flex: 1;
    overflow-y: auto;
    min-height: 300px;
}
.scripts-result-body .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 250px;
}
.scripts-script-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
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
    margin: 0 0 0.25rem;
    font-size: 0.9rem;
}
.scripts-script-info p {
    margin: 0;
    font-size: 0.8rem;
    color: var(--text-muted);
}
.scripts-param-row {
    display: grid;
    grid-template-columns: 120px 1fr;
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
}
@media (max-width: 768px) {
    .scripts-layout {
        grid-template-columns: 1fr;
    }
}
</style>
