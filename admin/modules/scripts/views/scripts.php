<div class="scripts-container">
    <!-- Password Modal (shown when executing a script) -->
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

    <!-- Scripts List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Scripts Disponibles</h3>
            <div class="card-actions">
                <button id="btn-scripts-refresh" class="btn btn-sm btn-ghost" title="Recargar lista">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="scripts-list">
                <div class="loading"><div class="spinner"></div><p>Cargando scripts...</p></div>
            </div>
        </div>
    </div>

    <!-- Script Execution Panel (shown after clicking Ejecutar) -->
    <div id="script-execution-panel" class="card" style="margin-top: 1rem; display: none;">
        <div class="card-header">
            <h3 class="card-title">Ejecutar: <span id="script-current-name"></span></h3>
            <button id="btn-script-close" class="btn btn-sm btn-ghost">✕</button>
        </div>
        <div class="card-body">
            <p id="script-description" class="text-muted"></p>
            <div id="script-result" style="margin-top: 1rem;"></div>
            <div style="margin-top: 1rem;">
                <button id="btn-script-confirm" class="btn btn-danger">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Ejecutar Script
                </button>
            </div>
        </div>
    </div>
</div>
