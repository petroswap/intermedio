<?php
/**
 * ============================================
 * INSPECTOR - Vista Principal (v2)
 * ============================================
 * Nuevo flujo: DataTable tablas → 10 registros → Mostrar todos
 */
?>

<div class="inspector-container">
    <!-- VISTA 1: Listado de Tablas -->
    <div id="view-tables" class="tables-list-container">
        <div class="tables-list-header">
            <h2 class="tables-list-title">
                <span class="icon">📊</span>
                Tablas de Base de Datos
            </h2>
            <div class="tables-list-search">
                <input type="text" id="tables-search" class="form-input" placeholder="Buscar tabla...">
                <button id="btn-refresh-tables" class="btn btn-secondary" title="Actualizar">
                    🔄
                </button>
            </div>
        </div>
        <div class="tables-list-body">
            <table id="tables-datatable" class="data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Tabla</th>
                        <th>Registros</th>
                        <th>Columnas</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="tables-tbody">
                    <!-- Skeleton loading -->
                    <tr class="skeleton-row"><td colspan="4"><div class="skeleton-cell name"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="4"><div class="skeleton-cell name"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="4"><div class="skeleton-cell name"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- VISTA 2: Datos de una tabla -->
    <div id="view-table-data" class="table-view-container" style="display: none;">
        <!-- Header con volver y título -->
        <div class="table-view-header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button id="btn-back-tables" class="table-view-back">
                    ← Volver a Tablas
                </button>
                <h2 class="table-view-title">
                    📋 <span id="current-table-name">-</span>
                    <span class="badge" id="current-table-count">0 registros</span>
                </h2>
            </div>
            <div class="table-view-actions">
                <button id="btn-show-last-10" class="btn btn-secondary btn-sm">
                    📄 Últimos 10
                </button>
                <button id="btn-show-all" class="btn-show-all">
                    📥 Mostrar todos (<span id="show-all-count">0</span>)
                </button>
                <button id="btn-sql" class="btn btn-secondary btn-sm">
                    📝 SQL Libre
                </button>
            </div>
        </div>

        <!-- Columnas -->
        <div class="inspector-section">
            <h3 class="section-title">📋 Columnas</h3>
            <div id="columns-container" class="columns-grid"></div>
        </div>

        <!-- Filtros -->
        <div class="inspector-section">
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
        <div class="inspector-section">
            <h3 class="section-title">📝 SQL Generado</h3>
            <div class="sql-box">
                <pre id="sql-generated" class="sql-code"></pre>
                <button id="btn-copy-sql" class="btn btn-sm btn-secondary">📋 Copiar</button>
            </div>
        </div>

        <!-- Resultados -->
        <div class="inspector-section">
            <h3 class="section-title">
                📊 Resultados
                <span id="results-count" class="badge badge-info">0</span>
                <span id="records-shown" class="records-shown-info" style="display: none;">
                    <span class="icon">ℹ️</span>
                    Mostrando <span id="shown-count">0</span> de <span id="total-count">0</span> registros
                </span>
            </h3>
            <div id="results-container" class="data-table-wrapper">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Cargando datos...</p>
                </div>
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

<!-- CSS adicional para inspector (movido a admin.css en FASE 2) -->
