<?php
/**
 * ============================================
 * INSPECTOR - Vista Principal
 * ============================================
 * Las tablas se cargan via AJAX (inspector.js)
 */
?>

<div class="inspector-container">
    <!-- Selector de Tabla -->
    <div class="inspector-header">
        <div class="form-group">
            <label class="form-label" for="table-select">Seleccionar Tabla</label>
            <select id="table-select" class="form-select" style="max-width: 400px;">
                <option value="">Cargando tablas...</option>
            </select>
        </div>
        
        <div class="inspector-actions">
            <button id="btn-refresh" class="btn btn-secondary" title="Actualizar">
                🔄 Actualizar
            </button>
            <button id="btn-sql" class="btn btn-secondary" title="SQL Libre">
                📝 SQL Libre
            </button>
        </div>
    </div>

    <!-- Información de la tabla -->
    <div id="table-info" class="inspector-info" style="display: none;">
        <div class="info-card">
            <div class="info-label">Tabla</div>
            <div id="info-table-name" class="info-value">-</div>
        </div>
        <div class="info-card">
            <div class="info-label">Registros</div>
            <div id="info-table-count" class="info-value">-</div>
        </div>
        <div class="info-card">
            <div class="info-label">Columnas</div>
            <div id="info-table-columns" class="info-value">-</div>
        </div>
    </div>

    <!-- Columnas -->
    <div id="columns-section" class="inspector-section" style="display: none;">
        <h3 class="section-title">📋 Columnas</h3>
        <div id="columns-container" class="columns-grid"></div>
    </div>

    <!-- Filtros -->
    <div id="filters-section" class="inspector-section" style="display: none;">
        <h3 class="section-title">🔍 Filtros</h3>
        <div id="filters-container">
            <div class="filter-row">
                <select id="filter-field" class="form-select filter-field">
                    <option value="">Campo...</option>
                </select>
                <select id="filter-operator" class="form-select filter-operator">
                    <option value="=">=</option>
                    <option value="!=">≠</option>
                    <option value="LIKE">Contiene</option>
                    <option value=">">></option>
                    <option value="<"><</option>
                    <option value=">=">≥</option>
                    <option value="<=">≤</option>
                </select>
                <input type="text" id="filter-value" class="form-input filter-value" placeholder="Valor">
                <button id="btn-add-filter" class="btn btn-secondary btn-sm">+ Agregar</button>
            </div>
            <div id="active-filters" class="active-filters"></div>
        </div>
        <div class="filter-actions">
            <button id="btn-search" class="btn btn-primary">🔍 Buscar</button>
            <button id="btn-clear-filters" class="btn btn-secondary">Limpiar Filtros</button>
        </div>
    </div>

    <!-- SQL Generado -->
    <div id="sql-section" class="inspector-section" style="display: none;">
        <h3 class="section-title">📝 SQL Generado</h3>
        <div class="sql-box">
            <pre id="sql-generated" class="sql-code"></pre>
            <button id="btn-copy-sql" class="btn btn-sm btn-secondary">📋 Copiar</button>
        </div>
    </div>

    <!-- Resultados -->
    <div id="results-section" class="inspector-section" style="display: none;">
        <h3 class="section-title">📊 Resultados <span id="results-count" class="badge badge-info">0</span></h3>
        <div id="results-container" class="data-table-wrapper">
            <div class="loading">
                <div class="spinner"></div>
                <p>Cargando datos...</p>
            </div>
        </div>
    </div>

    <!-- Modal SQL Libre -->
    <div id="sql-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📝 Ejecutar SQL</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning" style="margin-bottom: 1rem;">
                    <span class="alert-icon">⚠️</span>
                    <div class="alert-content">
                        <strong>Solo consultas SELECT de lectura</strong><br>
                        Las operaciones INSERT, UPDATE, DELETE, DROP, ALTER, CREATE están bloqueadas por seguridad.
                    </div>
                </div>
                <textarea id="sql-input" class="form-textarea" rows="6" placeholder="SELECT * FROM CLIENTES WHERE ACTIVO = 1"></textarea>
            </div>
            <div class="modal-footer">
                <button id="btn-execute-sql" class="btn btn-primary">▶ Ejecutar</button>
                <button class="btn btn-secondary modal-close">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- CSS adicional para inspector -->
<style>
.inspector-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.inspector-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.inspector-actions {
    display: flex;
    gap: 0.5rem;
}

.inspector-info {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.info-card {
    background: var(--gray-50);
    padding: 1rem 1.5rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--gray-200);
    min-width: 120px;
}

.info-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--gray-800);
}

.inspector-section {
    background: var(--gray-50);
    padding: 1.25rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--gray-200);
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.columns-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.column-chip {
    background: var(--white);
    padding: 0.375rem 0.75rem;
    border-radius: var(--radius);
    border: 1px solid var(--gray-300);
    font-size: 0.875rem;
    font-family: var(--font-mono);
    cursor: pointer;
    transition: var(--transition);
}

.column-chip:hover {
    border-color: var(--primary);
    background: var(--primary-bg);
}

.column-chip.selected {
    background: var(--primary);
    color: var(--white);
    border-color: var(--primary);
}

.filter-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}

.filter-field {
    min-width: 150px;
}

.filter-operator {
    min-width: 80px;
}

.filter-value {
    flex: 1;
    min-width: 150px;
}

.active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--primary-bg);
    color: var(--primary-dark);
    padding: 0.375rem 0.75rem;
    border-radius: var(--radius);
    font-size: 0.875rem;
}

.filter-tag-remove {
    cursor: pointer;
    opacity: 0.7;
}

.filter-tag-remove:hover {
    opacity: 1;
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.sql-box {
    position: relative;
    background: var(--gray-800);
    border-radius: var(--radius);
    padding: 1rem;
}

.sql-code {
    color: var(--gray-100);
    font-family: var(--font-mono);
    font-size: 0.875rem;
    white-space: pre-wrap;
    word-break: break-all;
    margin: 0;
}

.sql-box .btn {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: var(--white);
    border-radius: var(--radius-md);
    width: 90%;
    max-width: 600px;
    box-shadow: var(--shadow-lg);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--gray-200);
}

.modal-header h3 {
    margin: 0;
    font-size: 1.125rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray-500);
    padding: 0;
    line-height: 1;
}

.modal-body {
    padding: 1.25rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--gray-200);
}

@media (max-width: 768px) {
    .inspector-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-field,
    .filter-operator,
    .filter-value {
        width: 100%;
    }
}
</style>
</div>
