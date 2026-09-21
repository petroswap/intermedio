<div class="dashboard-container">
    <!-- Stats Cards -->
    <div class="dashboard-stats">
        <div class="dashboard-stat-card" id="stat-connection">
            <div class="stat-icon stat-icon-success">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="stat-driver">-</span>
                <span class="stat-label">Conectado</span>
            </div>
        </div>

        <div class="dashboard-stat-card">
            <div class="stat-icon stat-icon-info">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="stat-tables">-</span>
                <span class="stat-label">Tablas</span>
            </div>
        </div>

        <div class="dashboard-stat-card">
            <div class="stat-icon stat-icon-primary">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="stat-records">-</span>
                <span class="stat-label">Registros totales</span>
            </div>
        </div>

        <div class="dashboard-stat-card">
            <div class="stat-icon stat-icon-warning">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="stat-latency">-</span>
                <span class="stat-label">Latencia</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dashboard-grid">
        <!-- Top Tables -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <h3 class="dashboard-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                    Tablas más grandes
                </h3>
            </div>
            <div class="dashboard-card-body">
                <div id="top-tables-container">
                    <div class="loading"><div class="spinner"></div><p>Cargando estadísticas...</p></div>
                </div>
            </div>
        </div>

        <!-- Query History -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <h3 class="dashboard-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Historial reciente
                </h3>
                <button id="btn-clear-history" class="btn btn-xs btn-ghost" title="Limpiar historial">Limpiar</button>
            </div>
            <div class="dashboard-card-body">
                <div id="history-container">
                    <div class="empty-state">
                        <div class="empty-state-icon">📜</div>
                        <p class="empty-state-description">Sin consultas en el historial</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access -->
    <div class="dashboard-quick-access">
        <h3 class="dashboard-section-title">Accesos rápidos</h3>
        <div class="dashboard-quick-buttons">
            <a href="?module=inspector" class="dashboard-quick-btn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                <span>Explorador</span>
            </a>
            <a href="?module=sql" class="dashboard-quick-btn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                <span>Consola SQL</span>
            </a>
        </div>
    </div>
</div>
