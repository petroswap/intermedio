<div class="endpoints-container">
    <div class="endpoints-header">
        <h2 class="endpoints-title">
            <span class="icon">⚡</span>
            Endpoints API
        </h2>
        <div class="endpoints-header-actions">
            <button id="btn-refresh-endpoints" class="btn btn-secondary btn-sm">🔄 Actualizar</button>
        </div>
    </div>

    <div class="endpoints-layout">
        <!-- Panel izquierdo: Lista de endpoints -->
        <div class="endpoints-sidebar">
            <div class="endpoints-sidebar-header">
                <input type="text" id="endpoints-search" class="form-input" placeholder="Buscar endpoint...">
            </div>
            <div id="endpoints-list" class="endpoints-list">
                <div class="empty-state" style="padding: 2rem 1rem;">
                    <div class="empty-state-icon">⚡</div>
                    <p class="empty-state-description">Cargando endpoints...</p>
                </div>
            </div>
        </div>

        <!-- Panel derecho: Detalle + Tester -->
        <div class="endpoints-main">
            <!-- Estado vacío -->
            <div id="endpoint-empty" class="endpoint-empty-state">
                <div class="empty-state">
                    <div class="empty-state-icon">⚡</div>
                    <p class="empty-state-title">Endpoints API</p>
                    <p class="empty-state-description">Selecciona un endpoint de la lista para ver sus detalles y probarlo</p>
                </div>
            </div>

            <!-- Detalle del endpoint -->
            <div id="endpoint-detail" style="display: none;">
                <div class="endpoint-detail-header">
                    <div>
                        <h3 id="endpoint-name" class="endpoint-detail-name">-</h3>
                        <p id="endpoint-description" class="endpoint-detail-desc">-</p>
                    </div>
                    <span id="endpoint-method-badge" class="endpoint-method-badge">POST</span>
                </div>

                <!-- Info del endpoint -->
                <div class="endpoint-info-grid">
                    <div class="endpoint-info-item">
                        <span class="endpoint-info-label">URL</span>
                        <code id="endpoint-url" class="endpoint-info-value">-</code>
                    </div>
                    <div class="endpoint-info-item">
                        <span class="endpoint-info-label">Tabla</span>
                        <code id="endpoint-table" class="endpoint-info-value">-</code>
                    </div>
                    <div class="endpoint-info-item">
                        <span class="endpoint-info-label">Tipo</span>
                        <span id="endpoint-type" class="endpoint-info-value">-</span>
                    </div>
                </div>

                <!-- Tester -->
                <div class="endpoint-tester">
                    <h4 class="section-title">🧪 Tester</h4>

                    <!-- Parámetros -->
                    <div class="endpoint-params">
                        <label class="form-label">Parámetros</label>
                        <div id="endpoint-params-container" class="endpoint-params-list">
                            <p class="text-secondary" style="font-size: 12px;">Sin parámetros requeridos</p>
                        </div>
                    </div>

                    <!-- Body (para POST) -->
                    <div class="endpoint-body-section">
                        <label class="form-label">Body (JSON)</label>
                        <textarea id="endpoint-body" class="form-textarea" rows="3"
                            style="font-family: var(--font-mono); font-size: 12px;"
                            placeholder='{"key": "value"}'></textarea>
                    </div>

                    <!-- Botón ejecutar -->
                    <div class="endpoint-test-actions">
                        <button id="btn-endpoint-test" class="btn btn-primary">▶ Ejecutar Petición</button>
                        <span id="endpoint-test-time" class="endpoint-test-time"></span>
                    </div>
                </div>

                <!-- Respuesta -->
                <div class="endpoint-response">
                    <h4 class="section-title">📤 Respuesta</h4>
                    <div class="endpoint-response-header">
                        <span id="endpoint-status-code" class="endpoint-status-code"></span>
                        <button id="btn-copy-response" class="btn btn-sm btn-secondary">📋 Copiar JSON</button>
                    </div>
                    <pre id="endpoint-response-body" class="endpoint-response-body">Selecciona un endpoint y ejecuta una petición para ver la respuesta</pre>
                </div>
            </div>
        </div>
    </div>
</div>
