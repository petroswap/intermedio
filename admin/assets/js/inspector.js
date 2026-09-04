/**
 * ============================================
 * INSPECTOR.JS - Lógica del Inspector de BD (v2)
 * ============================================
 * Nuevo flujo: DataTable tablas → 10 registros → Mostrar todos
 */

const Inspector = {
    currentTable: null,
    columns: [],
    filters: [],
    data: [],
    totalRecords: 0,
    showingAll: false,
    _initialized: false,

    /**
     * Inicializar inspector
     */
    init: function() {
        if (this._initialized) return;
        this._initialized = true;

        this.bindEvents();
        this.loadTables();
        this.checkUrlParams();
    },

    /**
     * Vincular eventos
     */
    bindEvents: function() {
        // === VISTA DE TABLAS ===
        
        // Búsqueda de tablas
        $('#tables-search').on('input', (e) => {
            this.filterTablesList(e.target.value);
        });

        // Refrescar tablas
        $('#btn-refresh-tables').on('click', () => {
            this.loadTables();
        });

        // === VISTA DE DATOS ===
        
        // Volver a tablas
        $('#btn-back-tables').on('click', () => {
            this.showTablesView();
        });

        // Últimos 10
        $('#btn-show-last-10').on('click', () => {
            this.loadLastRecords(10);
        });

        // Mostrar todos
        $('#btn-show-all').on('click', () => {
            this.loadAllRecords();
        });

        // SQL libre
        $('#btn-sql').on('click', () => {
            this.openSqlModal();
        });

        // Agregar filtro
        $('#btn-add-filter').on('click', () => {
            this.addFilter();
        });

        // Buscar
        $('#btn-search').on('click', () => {
            this.loadDataWithFilters();
        });

        // Limpiar filtros
        $('#btn-clear-filters').on('click', () => {
            this.clearFilters();
        });

        // Copiar SQL
        $('#btn-copy-sql').on('click', () => {
            Admin.copyToClipboard($('#sql-generated').text());
        });

        // Ejecutar SQL
        $('#btn-execute-sql').on('click', () => {
            this.executeSql();
        });

        // Cerrar modales
        $('.modal-close').on('click', () => {
            this.closeModals();
        });

        // Cerrar con Escape
        $(document).on('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModals();
            }
        });
    },

    /**
     * Cargar tablas via AJAX
     */
    loadTables: function() {
        const tbody = $('#tables-tbody');
        
        // Mostrar skeleton
        tbody.html(`
            <tr><td colspan="4"><div class="skeleton-cell name" style="width:150px;height:16px;background:linear-gradient(90deg,#e2e8f0 25%,#f1f5f9 50%,#e2e8f0 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:4px;"></div></td></tr>
            <tr><td colspan="4"><div class="skeleton-cell name" style="width:120px;height:16px;background:linear-gradient(90deg,#e2e8f0 25%,#f1f5f9 50%,#e2e8f0 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:4px;"></div></td></tr>
            <tr><td colspan="4"><div class="skeleton-cell name" style="width:180px;height:16px;background:linear-gradient(90deg,#e2e8f0 25%,#f1f5f9 50%,#e2e8f0 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:4px;"></div></td></tr>
        `);

        Admin.post('modules/inspector/ajax/listar_tablas.php', {})
        .then(response => {
            if (response.data && response.data.length > 0) {
                this.renderTablesList(response.data);
            } else {
                tbody.html('<tr><td colspan="4" class="text-center text-gray-500">No se encontraron tablas</td></tr>');
            }
        })
        .catch(error => {
            tbody.html('<tr><td colspan="4" class="text-center text-danger">Error al conectar con la BD</td></tr>');
            Admin.showAlert('No se pudo conectar a la base de datos Firebird.', 'danger', 'Error de conexión');
        });
    },

    /**
     * Renderizar lista de tablas en DataTable
     */
    renderTablesList: function(tables) {
        const tbody = $('#tables-tbody');
        tbody.empty();

        tables.forEach(table => {
            const name = table.TABLA || table.tabla || '';
            const records = parseInt(table.REGISTROS || table.registros || 0);
            const columns = parseInt(table.COLUMNAS || table.columnas || 0);
            
            const row = $(`
                <tr data-table="${name}">
                    <td>
                        <div class="table-name-cell">
                            <div class="table-icon">📋</div>
                            <span class="table-name">${name}</span>
                        </div>
                    </td>
                    <td class="record-count-cell">
                        <span class="count">${records.toLocaleString('es-ES')}</span>
                    </td>
                    <td class="column-count-cell">
                        ${columns}
                    </td>
                    <td>
                        <button class="table-action-btn" data-table="${name}">
                            Ver datos →
                        </button>
                    </td>
                </tr>
            `);
            tbody.append(row);
        });

        // Vincular eventos de botones
        $('.table-action-btn').on('click', (e) => {
            const tableName = $(e.target).data('table');
            this.selectTable(tableName);
        });

        // Inicializar DataTable
        this.initTablesDataTable();
    },

    /**
     * Inicializar DataTable para lista de tablas
     */
    initTablesDataTable: function() {
        // Destruir DataTable existente si hay
        if ($.fn.DataTable.isDataTable('#tables-datatable')) {
            $('#tables-datatable').DataTable().destroy();
        }

        $('#tables-datatable').DataTable({
            language: {
                lengthMenu: 'Mostrar _MENU_ tablas',
                zeroRecords: 'No se encontraron tablas',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ tablas',
                infoEmpty: 'Mostrando 0 a 0 de 0 tablas',
                infoFiltered: '(filtrado de _MAX_ tablas totales)',
                search: 'Buscar:',
                paginate: {
                    first: 'Primero',
                    last: 'Último',
                    next: '→',
                    previous: '←'
                }
            },
            pageLength: 25,
            order: [[1, 'desc']], // Ordenar por registros (desc)
            columns: [
                { orderable: true },  // Tabla
                { orderable: true },  // Registros
                { orderable: true },  // Columnas
                { orderable: false }  // Acción
            ],
            dom: '<"top"fl>rt<"bottom"ip>'
        });
    },

    /**
     * Filtrar lista de tablas
     */
    filterTablesList: function(query) {
        if ($.fn.DataTable.isDataTable('#tables-datatable')) {
            $('#tables-datatable').DataTable().search(query).draw();
        }
    },

    /**
     * Verificar parámetros URL
     */
    checkUrlParams: function() {
        const params = new URLSearchParams(window.location.search);
        const table = params.get('table');
        if (table) {
            this.selectTable(table);
        }
    },

    /**
     * Seleccionar tabla (cambiar a vista de datos)
     */
    selectTable: function(tableName) {
        if (!tableName) return;

        this.currentTable = tableName;
        this.filters = [];
        this.showingAll = false;
        
        // Actualizar URL
        const url = new URL(window.location);
        url.searchParams.set('table', tableName);
        window.history.pushState({}, '', url);

        // Cambiar a vista de datos
        this.showTableView();
        
        // Cargar información de la tabla
        this.loadTableInfo();
    },

    /**
     * Mostrar vista de tablas
     */
    showTablesView: function() {
        $('#view-tables').show();
        $('#view-table-data').hide();
        
        // Limpiar URL
        const url = new URL(window.location);
        url.searchParams.delete('table');
        window.history.pushState({}, '', url);
        
        this.currentTable = null;
    },

    /**
     * Mostrar vista de datos de tabla
     */
    showTableView: function() {
        $('#view-tables').hide();
        $('#view-table-data').show();
    },

    /**
     * Cargar información de la tabla
     */
    loadTableInfo: function() {
        $('#current-table-name').text(this.currentTable);
        
        Admin.post('modules/inspector/ajax/listar_columnas.php', {
            table: this.currentTable
        })
        .then(response => {
            this.columns = response.data;
            this.renderColumns();
            this.populateFilterFields();
            this.loadLastRecords(10);
        })
        .catch(error => {
            Admin.showError($('#results-container'), error.message);
        });
    },

    /**
     * Renderizar columnas
     */
    renderColumns: function() {
        const container = $('#columns-container');
        container.empty();

        this.columns.forEach(col => {
            const chip = $('<div class="column-chip selected">')
                .text(col.COLUMNA)
                .attr('data-column', col.COLUMNA)
                .on('click', (e) => {
                    $(e.target).toggleClass('selected');
                    this.updateSql();
                });
            container.append(chip);
        });
    },

    /**
     * Poblar campos de filtro
     */
    populateFilterFields: function() {
        const select = $('#filter-field');
        select.empty().append('<option value="">Campo...</option>');

        this.columns.forEach(col => {
            select.append(`<option value="${col.COLUMNA}">${col.COLUMNA}</option>`);
        });
    },

    /**
     * Cargar últimos N registros
     */
    loadLastRecords: function(limit) {
        if (!this.currentTable) return;

        this.showingAll = false;
        this.updateRecordsShownInfo(limit);

        const selectedColumns = [];
        $('.column-chip.selected').each(function() {
            selectedColumns.push($(this).data('column'));
        });

        const data = {
            table: this.currentTable,
            limit: limit,
            fields: selectedColumns.join(',')
        };

        Admin.showLoading($('#results-container'));

        Admin.post('modules/inspector/ajax/obtener_ultimos.php', data)
        .then(response => {
            this.data = response.data;
            this.totalRecords = response.count || 0;
            this.renderResults(response);
            this.updateSql();
            this.updateCount(this.totalRecords);
            this.updateRecordsShownInfo(limit, this.totalRecords);
        })
        .catch(error => {
            Admin.showError($('#results-container'), error.message);
        });
    },

    /**
     * Cargar todos los registros
     */
    loadAllRecords: function() {
        if (!this.currentTable) return;

        this.showingAll = true;
        $('#records-shown').hide();

        const selectedColumns = [];
        $('.column-chip.selected').each(function() {
            selectedColumns.push($(this).data('column'));
        });

        const data = {
            table: this.currentTable,
            fields: selectedColumns.join(','),
            page: 1,
            per_page: 50
        };

        // Agregar filtros
        this.filters.forEach((filter, index) => {
            data[`filter_field_${index}`] = filter.field;
            data[`filter_operator_${index}`] = filter.operator;
            data[`filter_value_${index}`] = filter.value;
        });

        Admin.showLoading($('#results-container'));

        Admin.post('modules/inspector/ajax/obtener_datos.php', data)
        .then(response => {
            this.data = response.data;
            this.totalRecords = response.count || 0;
            this.renderResults(response, true);
            this.updateSql();
            this.updateCount(this.totalRecords);
        })
        .catch(error => {
            Admin.showError($('#results-container'), error.message);
        });
    },

    /**
     * Cargar datos con filtros
     */
    loadDataWithFilters: function() {
        if (this.showingAll) {
            this.loadAllRecords();
        } else {
            this.loadLastRecords(10);
        }
    },

    /**
     * Renderizar resultados
     */
    renderResults: function(response, paginate = false) {
        const container = $('#results-container');
        
        if (!response.data || response.data.length === 0) {
            container.html('<p class="text-center text-gray-500" style="padding: 2rem;">No se encontraron registros</p>');
            return;
        }

        const html = Admin.createTable(response.data);
        container.html(html);

        // Inicializar DataTable con paginación si se muestran todos
        if (paginate) {
            Admin.initDataTable('.data-table', {
                pageLength: 25,
                order: [[0, 'asc']]
            });
        }
    },

    /**
     * Actualizar contador
     */
    updateCount: function(count) {
        $('#results-count').text(count.toLocaleString('es-ES'));
        $('#current-table-count').text(count.toLocaleString('es-ES') + ' registros');
        $('#show-all-count').text(count.toLocaleString('es-ES'));
    },

    /**
     * Actualizar información de registros mostrados
     */
    updateRecordsShownInfo: function(shown, total) {
        if (total !== undefined) {
            $('#records-shown').show();
            $('#shown-count').text(shown);
            $('#total-count').text(total.toLocaleString('es-ES'));
        } else {
            $('#records-shown').hide();
        }
    },

    /**
     * Agregar filtro
     */
    addFilter: function() {
        const field = $('#filter-field').val();
        const operator = $('#filter-operator').val();
        const value = $('#filter-value').val();

        if (!field || !value) {
            Admin.showAlert('Selecciona un campo y valor', 'warning');
            return;
        }

        this.filters.push({ field, operator, value });
        this.renderFilters();
        this.loadDataWithFilters();

        // Limpiar campos
        $('#filter-field').val('');
        $('#filter-value').val('');
    },

    /**
     * Renderizar filtros activos
     */
    renderFilters: function() {
        const container = $('#active-filters');
        container.empty();

        this.filters.forEach((filter, index) => {
            const tag = $(`
                <div class="filter-tag">
                    <span>${filter.field} ${filter.operator} "${filter.value}"</span>
                    <span class="filter-tag-remove" data-index="${index}">&times;</span>
                </div>
            `);
            container.append(tag);
        });

        // Evento para eliminar filtro
        $('.filter-tag-remove').on('click', (e) => {
            const index = $(e.target).data('index');
            this.removeFilter(index);
        });
    },

    /**
     * Eliminar filtro
     */
    removeFilter: function(index) {
        this.filters.splice(index, 1);
        this.renderFilters();
        this.loadDataWithFilters();
    },

    /**
     * Limpiar filtros
     */
    clearFilters: function() {
        this.filters = [];
        this.renderFilters();
        this.loadDataWithFilters();
    },

    /**
     * Actualizar SQL generado
     */
    updateSql: function() {
        const selectedColumns = [];
        $('.column-chip.selected').each(function() {
            selectedColumns.push($(this).data('column'));
        });

        const fields = selectedColumns.length > 0 ? selectedColumns.join(', ') : '*';
        let sql = `SELECT ${fields}\nFROM ${this.currentTable}`;

        if (this.filters.length > 0) {
            const conditions = this.filters.map(f => {
                if (f.operator === 'LIKE') {
                    return `${f.field} LIKE '%${f.value}%'`;
                }
                return `${f.field} ${f.operator} '${f.value}'`;
            });
            sql += `\nWHERE ${conditions.join('\n  AND ')}`;
        }

        sql += '\nORDER BY ' + (selectedColumns[0] || '1');
        
        if (!this.showingAll) {
            sql += '\nROWS 1 TO 10';
        }

        $('#sql-generated').text(sql);
    },

    /**
     * Abrir modal SQL
     */
    openSqlModal: function() {
        $('#sql-modal').show();
        $('#sql-input').focus();
    },

    /**
     * Cerrar modales
     */
    closeModals: function() {
        $('.modal').hide();
    },

    /**
     * Ejecutar SQL libre
     */
    executeSql: function() {
        const sql = $('#sql-input').val().trim();
        
        if (!sql) {
            Admin.showAlert('Escribe una consulta SQL', 'warning');
            return;
        }

        Admin.showLoading($('#results-container'));
        this.closeModals();

        Admin.post('modules/inspector/ajax/ejecutar_sql.php', {
            sql: sql
        })
        .then(response => {
            this.data = response.data;
            this.renderResults(response, true);
            this.updateCount(response.count || 0);
        })
        .catch(error => {
            Admin.showError($('#results-container'), error.message);
        });
    }
};

// Inicializar cuando el DOM esté listo
$(document).ready(function() {
    Inspector.init();
});
