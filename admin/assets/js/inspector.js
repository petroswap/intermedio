/**
 * ============================================
 * INSPECTOR.JS - Lógica del Inspector de BD
 * ============================================
 */

const Inspector = {
    currentTable: null,
    columns: [],
    filters: [],
    data: [],
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
        // Selector de tabla
        $('#table-select').on('change', (e) => {
            this.selectTable(e.target.value);
        });

        // Botón refrescar
        $('#btn-refresh').on('click', () => {
            this.refreshData();
        });

        // Botón SQL libre
        $('#btn-sql').on('click', () => {
            this.openSqlModal();
        });

        // Agregar filtro
        $('#btn-add-filter').on('click', () => {
            this.addFilter();
        });

        // Buscar
        $('#btn-search').on('click', () => {
            this.loadData();
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
        const select = $('#table-select');
        
        Admin.post('modules/inspector/ajax/listar_tablas.php', {})
        .then(response => {
            select.empty().append('<option value="">-- Seleccionar tabla --</option>');
            
            if (response.data && response.data.length > 0) {
                response.data.forEach(table => {
                    const name = table.TABLA || table.tabla || '';
                    select.append(`<option value="${name}">${name}</option>`);
                });
            } else {
                select.empty().append('<option value="">No se encontraron tablas</option>');
            }
        })
        .catch(error => {
            select.empty().append('<option value="">Error al conectar con la BD</option>');
            Admin.showAlert(
                'No se pudo conectar a la base de datos Firebird. Verifica la configuración en <code>admin/config.php</code>.',
                'danger',
                'Error de conexión'
            );
        });
    },

    /**
     * Verificar parámetros URL
     */
    checkUrlParams: function() {
        const params = new URLSearchParams(window.location.search);
        const table = params.get('table');
        if (table) {
            $('#table-select').val(table);
            this.selectTable(table);
        }
    },

    /**
     * Seleccionar tabla
     */
    selectTable: function(tableName) {
        if (!tableName) {
            this.resetView();
            return;
        }

        this.currentTable = tableName;
        this.filters = [];
        
        // Actualizar URL
        const url = new URL(window.location);
        url.searchParams.set('table', tableName);
        window.history.pushState({}, '', url);

        // Cargar información de la tabla
        this.loadTableInfo();
    },

    /**
     * Cargar información de la tabla
     */
    loadTableInfo: function() {
        Admin.post('modules/inspector/ajax/listar_columnas.php', {
            table: this.currentTable
        })
        .then(response => {
            this.columns = response.data;
            this.renderTableInfo();
            this.renderColumns();
            this.populateFilterFields();
            this.loadData();
        })
        .catch(error => {
            Admin.showError($('#results-container'), error.message);
        });
    },

    /**
     * Renderizar información de la tabla
     */
    renderTableInfo: function() {
        $('#info-table-name').text(this.currentTable);
        $('#info-table-columns').text(this.columns.length);
        $('#table-info').show();
        $('#columns-section').show();
        $('#filters-section').show();
        $('#sql-section').show();
        $('#results-section').show();
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
     * Cargar datos
     */
    loadData: function() {
        if (!this.currentTable) return;

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
            this.renderResults(response);
            this.updateSql();
            this.updateCount(response.count || 0);
        })
        .catch(error => {
            Admin.showError($('#results-container'), error.message);
        });
    },

    /**
     * Renderizar resultados
     */
    renderResults: function(response) {
        const container = $('#results-container');
        
        if (!response.data || response.data.length === 0) {
            container.html('<p class="no-data">No se encontraron registros</p>');
            return;
        }

        const html = Admin.createTable(response.data);
        container.html(html);

        // Inicializar DataTable
        Admin.initDataTable('.data-table', {
            pageLength: 25,
            order: [[0, 'asc']]
        });
    },

    /**
     * Actualizar contador
     */
    updateCount: function(count) {
        $('#results-count').text(count);
        $('#info-table-count').text(count);
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
        this.loadData();

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
        this.loadData();
    },

    /**
     * Limpiar filtros
     */
    clearFilters: function() {
        this.filters = [];
        this.renderFilters();
        this.loadData();
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
        sql += '\nROWS 1 TO 50';

        $('#sql-generated').text(sql);
    },

    /**
     * Refrescar datos
     */
    refreshData: function() {
        if (this.currentTable) {
            this.loadData();
        }
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
            this.renderResults(response);
            this.updateCount(response.count || 0);
        })
        .catch(error => {
            Admin.showError($('#results-container'), error.message);
        });
    },

    /**
     * Resetear vista
     */
    resetView: function() {
        this.currentTable = null;
        this.columns = [];
        this.filters = [];
        this.data = [];

        $('#table-info').hide();
        $('#columns-section').hide();
        $('#filters-section').hide();
        $('#sql-section').hide();
        $('#results-section').hide();
    }
};

// Inicializar cuando el DOM esté listo
$(document).ready(function() {
    Inspector.init();
});
