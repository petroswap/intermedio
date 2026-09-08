/**
 * ============================================
 * INSPECTOR.JS - LÃ³gica del Inspector de BD (v3.0)
 * ============================================
 * Flujo: Tablas â†’ 10 registros â†’ Mostrar todos + filtros + SQL con ayuda
 *
 * IMPORTANT: All events use delegation (bound to document) because the
 * inspector HTML is loaded dynamically via Admin.loadModule().
 * The $(document).ready() init is removed â€” Admin.loadModule() calls Inspector.init().
 *
 * Endpoints:
 *   listar_tablas.php    â†’ lista de tablas con conteo
 *   listar_columnas.php  â†’ columnas de una tabla
 *   obtener_ultimos.php  â†’ Ãºltimos N registros (sin filtros, para carga rÃ¡pida)
 *   obtener_datos.php    â†’ datos paginados con filtros y campos seleccionados
 *   ejecutar_sql.php     â†’ SQL libre (SELECT only)
 */

const Inspector = {
    currentTable: null,
    columns: [],
    filters: [],
    data: [],
    totalRecords: 0,
    showingAll: false,
    currentPage: 1,
    perPage: 50,
    _eventsBound: false,
    _tablesDt: null,
    _resultsDt: null,
    _loadingTimeout: null,

    /**
     * Show full-screen loading overlay (with delay for fast operations)
     */
    showOverlay: function(msg) {
        var self = this;
        $('#loading-message').text(msg || 'Cargando datos...');
        // Delay showing overlay for fast operations (< 300ms)
        this._loadingTimeout = setTimeout(function() {
            $('#loading-overlay').fadeIn(150);
        }, 300);
    },

    /**
     * Hide full-screen loading overlay
     */
    hideOverlay: function() {
        clearTimeout(this._loadingTimeout);
        $('#loading-overlay').fadeOut(100);
    },

    /**
     * Initialize inspector. Called by Admin.loadModule() after HTML is in DOM.
     */
    init: function() {
        if (!this._eventsBound) {
            this.bindEvents();
            this._eventsBound = true;
        }
        this.loadTables();
        this.checkUrlParams();
    },

    // ============================================
    // EVENT BINDING (delegated â€” survives DOM replacement)
    // ============================================

    bindEvents: function() {
        const self = this;

        // Tablas
        $(document).on('input', '#tables-search', function(e) { self.filterTablesList(e.target.value); });
        $(document).on('click', '#btn-refresh-tables', function() { self.loadTables(); });
        $(document).on('click', '.table-action-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            self.selectTable($(e.currentTarget).data('table'));
        });
        $(document).on('click', '#tables-datatable tbody tr', function(e) {
            if (!$(e.target).closest('.table-action-btn').length) {
                var table = $(this).data('table');
                if (table) self.selectTable(table);
            }
        });

        // NavegaciÃ³n
        $(document).on('click', '#btn-back-tables', function(e) { e.preventDefault(); self.showTablesView(); });
        $(document).on('click', '#btn-show-last-10', function() { self.loadLastRecords(10); });
        $(document).on('click', '#btn-show-all', function() { self.loadAllRecords(); });
        $(document).on('click', '#btn-sql', function() { self.openSqlTab(); });

        // Columnas - select all/none
        $(document).on('click', '#btn-select-all-cols', function() {
            $('#columns-container .column-chip').addClass('selected');
            self.updateSql();
        });
        $(document).on('click', '#btn-select-none-cols', function() {
            $('#columns-container .column-chip').removeClass('selected');
            self.updateSql();
        });

        // Filtros
        $(document).on('click', '#btn-add-filter', function() { self.addFilter(); });
        $(document).on('click', '#btn-search', function() { self.applyFilters(); });
        $(document).on('click', '#btn-clear-filters', function() { self.clearFilters(); });
        $(document).on('keydown', '#filter-value', function(e) { if (e.key === 'Enter') self.addFilter(); });

        // SQL
        $(document).on('click', '#btn-copy-sql', function() {
            Admin.copyToClipboard($('#sql-generated').text());
        });
        $(document).on('click', '#btn-execute-sql', function() { self.executeSql(); });
        $(document).on('click', '#btn-close-sql-modal', function() { self.closeModals(); });
        $(document).on('click', '#btn-cancel-sql', function() { self.closeModals(); });
        $(document).on('click', '.modal-overlay', function() { self.closeModals(); });
        $(document).on('click', '.sql-suggestion', function(e) {
            var sql = $(e.currentTarget).data('sql');
            var tableName = self.currentTable || 'CLIENTES';
            $('#sql-input').val(sql.replace(/\{TABLE\}/g, tableName));
            $('#sql-generated').text($('#sql-input').val());
        });
        $(document).on('input', '#sql-input', function() {
            $('#sql-generated').text($(this).val());
        });
        $(document).on('click', '.sql-example', function(e) {
            e.preventDefault();
            const sql = $(e.target).closest('.sql-example').data('sql');
            if (sql) { $('#sql-input').val(sql); self.executeSql(); }
        });

        // Test conexión
        $(document).on('click', '#btn-test-connection', function() { self.testConnection(); });

        // Modales
        $(document).on('click', '.modal-close', function() { self.closeModals(); });
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') self.closeModals();
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && $('#sql-modal').is(':visible')) {
                self.executeSql();
            }
        });
    },

    // ============================================
    // VISTA DE TABLAS
    // ============================================

    loadTables: function() {
        const self = this;
        const tbody = $('#tables-tbody');
        tbody.html(
            '<tr><td colspan="4"><div class="skeleton-cell name" style="width:150px"></div></td></tr>' +
            '<tr><td colspan="4"><div class="skeleton-cell name" style="width:120px"></div></td></tr>' +
            '<tr><td colspan="4"><div class="skeleton-cell name" style="width:180px"></div></td></tr>'
        );

        Admin.post('modules/inspector/ajax/listar_tablas.php', {})
        .then(function(response) {
            if (response.data && response.data.length > 0) {
                self.renderTablesList(response.data);
            } else {
                tbody.html('<tr><td colspan="4" class="text-center" style="padding:2rem;color:var(--text-tertiary)">No se encontraron tablas</td></tr>');
            }
        })
        .catch(function(error) {
            console.error('Error cargando tablas:', error);
            tbody.html('<tr><td colspan="4" class="text-center" style="padding:2rem;color:var(--error)">Error al conectar con la BD</td></tr>');
            Admin.showAlert('No se pudo conectar a la base de datos Firebird.', 'danger', 'Error de conexión');
        });
    },

    renderTablesList: function(tables) {
        const self = this;
        const tbody = $('#tables-tbody').empty();

        tables.forEach(function(table) {
            const name = (table.TABLA || table.tabla || '').trim();
            const records = parseInt(table.REGISTROS || table.registros || 0);
            const columns = parseInt(table.COLUMNAS || table.columnas || 0);

            tbody.append(
                '<tr data-table="' + name + '" class="clickable-row">' +
                    '<td>' +
                        '<div class="table-name-cell">' +
                            '<span class="table-name">' + name + '</span>' +
                        '</div>' +
                    '</td>' +
                    '<td class="record-count-cell">' +
                        '<span class="count">' + records.toLocaleString('es-ES') + '</span>' +
                    '</td>' +
                    '<td class="column-count-cell">' + columns + '</td>' +
                    '<td>' +
                        '<button class="table-action-btn" data-table="' + name + '">Ver datos</button>' +
                    '</td>' +
                '</tr>'
            );
        });

        this.initTablesDataTable();
    },

    initTablesDataTable: function() {
        if (this._tablesDt) {
            this._tablesDt.destroy();
        }

        this._tablesDt = $('#tables-datatable').DataTable({
            language: {
                lengthMenu: 'Mostrar _MENU_ tablas',
                zeroRecords: 'No se encontraron tablas',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ tablas',
                infoEmpty: 'Sin resultados',
                infoFiltered: '(filtrado de _MAX_)',
                search: 'Buscar:',
                paginate: { first: 'Primero', last: 'Último', next: '→', previous: '←' }
            },
            pageLength: 25,
            order: [[1, 'desc']],
            columns: [
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: false }
            ],
            dom: '<"top"fl>rt<"bottom"ip>'
        });
    },

    filterTablesList: function(query) {
        if (this._tablesDt) {
            this._tablesDt.search(query).draw();
        }
    },

    // ============================================
    // NAVEGACIÃ“N ENTRE VISTAS
    // ============================================

    checkUrlParams: function() {
        const params = new URLSearchParams(window.location.search);
        const table = params.get('table');
        if (table) this.selectTable(table);
    },

    selectTable: function(tableName) {
        if (!tableName) return;
        this.currentTable = tableName;
        this.filters = [];
        this.showingAll = false;
        this.currentPage = 1;

        const url = new URL(window.location);
        url.searchParams.set('table', tableName);
        window.history.pushState({}, '', url);

        this.showTableView();
        this.loadTableInfo();
    },

    showTablesView: function() {
        $('#view-tables').show();
        $('#view-table-data').hide();

        const url = new URL(window.location);
        url.searchParams.delete('table');
        window.history.pushState({}, '', url);

        this.currentTable = null;
    },

    showTableView: function() {
        $('#view-tables').hide();
        $('#view-table-data').show();
    },

    // ============================================
    // CARGA DE INFORMACIÃ“N DE TABLA
    // ============================================

    loadTableInfo: function() {
        var self = this;
        $('#current-table-name').text(this.currentTable);

        Admin.post('modules/inspector/ajax/listar_columnas.php', {
            table: this.currentTable
        })
        .then(function(response) {
            self.columns = response.data || [];
            self.renderColumns();
            self.populateFilterFields();
            self.loadLastRecords(10);
        })
        .catch(function(error) {
            console.error('Error cargando columnas:', error);
            Admin.showError($('#results-container'), 'Error al cargar columnas: ' + error.message);
        });
    },

    renderColumns: function() {
        var self = this;
        var container = $('#columns-container').empty();

        this.columns.forEach(function(col) {
            var name = (col.COLUMNA || '').trim();
            if (!name) return;

            var chip = $('<div class="column-chip selected">')
                .text(name)
                .attr('data-column', name);
            container.append(chip);
        });

        // Delegate click for column chips
        container.off('click', '.column-chip').on('click', '.column-chip', function(e) {
            $(e.currentTarget).toggleClass('selected');
            self.updateSql();
        });
    },

    populateFilterFields: function() {
        var select = $('#filter-field').empty().append('<option value="">Campo...</option>');

        this.columns.forEach(function(col) {
            var name = (col.COLUMNA || '').trim();
            if (name) select.append('<option value="' + name + '">' + name + '</option>');
        });
    },

    // ============================================
    // CARGA DE DATOS
    // ============================================

    /**
     * Cargar Ãºltimos N registros (carga rÃ¡pida, sin filtros)
     */
    loadLastRecords: function(limit) {
        if (!this.currentTable) return;

        this.showingAll = false;
        this.currentPage = 1;

        var selectedColumns = this.getSelectedColumns();
        var data = {
            table: this.currentTable,
            limit: limit
        };
        if (selectedColumns.length > 0) {
            data.fields = selectedColumns.join(',');
        }

        Admin.showLoading($('#results-container'));
        this.showOverlay('Cargando Ãºltimos ' + limit + ' registros...');

        var self = this;
        Admin.post('modules/inspector/ajax/obtener_ultimos.php', data)
        .then(function(response) {
            self.hideOverlay();
            self.data = response.data || [];
            self.totalRecords = self.data.length;
            self.renderResults(response, false);
            self.updateCount(self.totalRecords);
            self.updateRecordsShownInfo(self.data.length, self.totalRecords);
            self.updateSql();
        })
        .catch(function(error) {
            self.hideOverlay();
            console.error('Error cargando registros:', error);
            Admin.showError($('#results-container'), 'Error al cargar datos: ' + error.message);
        });
    },

    /**
     * Cargar todos los registros de la tabla
     */
    loadAllRecords: function() {
        if (!this.currentTable) return;

        this.showingAll = true;
        this.currentPage = 1;

        var data = this.buildDataParams();
        data.page = 1;
        data.per_page = 10000;

        Admin.showLoading($('#results-container'));
        this.showOverlay('Cargando todos los registros...');

        var self = this;
        Admin.post('modules/inspector/ajax/obtener_datos.php', data)
        .then(function(response) {
            self.hideOverlay();
            var inner = response.data || {};
            self.data = inner.data || [];
            self.totalRecords = inner.total || self.data.length;
            self.renderResults({ data: self.data }, true);
            self.updateCount(self.totalRecords);
            self.updateRecordsShownInfo(self.data.length, self.totalRecords);
            self.updateSql();
        })
        .catch(function(error) {
            self.hideOverlay();
            console.error('Error cargando todos:', error);
            Admin.showError($('#results-container'), 'Error al cargar datos: ' + error.message);
        });
    },

    /**
     * Aplicar filtros activos y cargar datos
     */
    applyFilters: function() {
        if (!this.currentTable) return;

        if (this.filters.length === 0) {
            this.loadLastRecords(10);
            return;
        }

        this.showingAll = true;
        this.currentPage = 1;

        var data = this.buildDataParams();
        data.page = 1;
        data.per_page = 10000;

        Admin.showLoading($('#results-container'));
        this.showOverlay('Buscando registros...');

        var self = this;
        Admin.post('modules/inspector/ajax/obtener_datos.php', data)
        .then(function(response) {
            self.hideOverlay();
            var inner = response.data || {};
            self.data = inner.data || [];
            self.totalRecords = inner.total || self.data.length;
            self.renderResults({ data: self.data }, true);
            self.updateCount(self.totalRecords);
            self.updateRecordsShownInfo(self.data.length, self.totalRecords);
            self.updateSql();
        })
        .catch(function(error) {
            self.hideOverlay();
            console.error('Error aplicando filtros:', error);
            Admin.showError($('#results-container'), 'Error al buscar: ' + error.message);
        });
    },

    /**
     * Construir parÃ¡metros de envÃ­o para obtener_datos.php
     */
    buildDataParams: function() {
        var params = {
            table: this.currentTable
        };

        var selectedColumns = this.getSelectedColumns();
        if (selectedColumns.length > 0) {
            params.fields = selectedColumns.join(',');
        }

        this.filters.forEach(function(filter, index) {
            params['filter_field_' + index] = filter.field;
            params['filter_operator_' + index] = filter.operator;
            params['filter_value_' + index] = filter.value;
        });

        return params;
    },

    /**
     * Obtener columnas seleccionadas en el grid
     */
    getSelectedColumns: function() {
        var cols = [];
        $('.column-chip.selected').each(function() {
            cols.push($(this).data('column'));
        });
        return cols;
    },

    // ============================================
    // FILTROS
    // ============================================

    addFilter: function() {
        var field = $('#filter-field').val();
        var operator = $('#filter-operator').val();
        var value = $('#filter-value').val();

        if (!field || !value) {
            Admin.showAlert('Selecciona un campo y escribe un valor', 'warning');
            return;
        }

        this.filters.push({ field: field, operator: operator, value: value });
        this.renderFilters();
        this.applyFilters();

        $('#filter-field').val('');
        $('#filter-value').val('').focus();
    },

    renderFilters: function() {
        var self = this;
        var container = $('#active-filters').empty();

        this.filters.forEach(function(filter, index) {
            var opLabel = filter.operator === 'LIKE' ? 'contiene' : filter.operator;
            container.append(
                '<div class="filter-tag">' +
                    '<span>' + filter.field + ' ' + opLabel + ' &quot;' + filter.value + '&quot;</span>' +
                    '<span class="filter-tag-remove" data-index="' + index + '">&times;</span>' +
                '</div>'
            );
        });

        container.off('click', '.filter-tag-remove').on('click', '.filter-tag-remove', function(e) {
            var idx = $(e.currentTarget).data('index');
            self.removeFilter(idx);
        });
    },

    removeFilter: function(index) {
        this.filters.splice(index, 1);
        this.renderFilters();
        this.applyFilters();
    },

    clearFilters: function() {
        this.filters = [];
        this.renderFilters();
        this.loadLastRecords(10);
    },

    // ============================================
    // RENDERIZADO DE RESULTADOS
    // ============================================

    renderResults: function(response, useDataTable) {
        var container = $('#results-container');

        if (!response.data || response.data.length === 0) {
            container.html(
                '<div style="text-align:center;padding:3rem;color:var(--text-tertiary)">' +
                    '<div style="font-size:2rem;margin-bottom:1rem">ðŸ“­</div>' +
                    '<p>No se encontraron registros</p>' +
                '</div>'
            );
            return;
        }

        // Destruir DataTable previa si existe
        if (this._resultsDt) {
            this._resultsDt.destroy();
            this._resultsDt = null;
        }

        var html = Admin.createTable(response.data);
        container.html(html);

        if (useDataTable && response.data.length > 10) {
            this._resultsDt = container.find('.data-table').DataTable({
                language: {
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Sin resultados",
                    infoFiltered: "(filtrado de _MAX_)",
                    zeroRecords: "No se encontraron resultados",
                    paginate: { first: "Primero", last: "Ãšltimo", next: "â†’", previous: "â†" }
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                order: [[0, 'asc']],
                dom: '<"top"fl>rt<"bottom"ip>'
            });
        }
    },

    // ============================================
    // CONTADORES E INFORMACIÃ“N
    // ============================================

    updateCount: function(count) {
        $('#results-count').text(count.toLocaleString('es-ES'));
        $('#current-table-count').text(count.toLocaleString('es-ES') + ' registros');
        $('#show-all-count').text(count.toLocaleString('es-ES'));
    },

    updateRecordsShownInfo: function(shown, total) {
        if (total !== undefined && shown !== undefined && shown < total) {
            $('#records-shown').show();
            $('#shown-count').text(shown);
            $('#total-count').text(total.toLocaleString('es-ES'));
        } else {
            $('#records-shown').hide();
        }
    },

    // ============================================
    // SQL GENERADO
    // ============================================

    updateSql: function() {
        var selectedColumns = this.getSelectedColumns();
        var fields = selectedColumns.length > 0 ? selectedColumns.join(', ') : '*';
        var sql = 'SELECT ' + fields + '\nFROM ' + this.currentTable;

        if (this.filters.length > 0) {
            var conditions = this.filters.map(function(f) {
                if (f.operator === 'LIKE') {
                    return f.field + " LIKE '%" + f.value + "%'";
                }
                return f.field + ' ' + f.operator + " '" + f.value + "'";
            });
            sql += '\nWHERE ' + conditions.join('\n  AND ');
        }

        if (selectedColumns.length > 0) {
            sql += '\nORDER BY ' + selectedColumns[0];
        }

        if (!this.showingAll) {
            sql += '\nROWS 1 TO 10';
        }

        $('#sql-generated').text(sql);
    },

    // ============================================
    // SQL LIBRE
    // ============================================

    openSqlTab: function() {
        var tableName = this.currentTable || 'CLIENTES';
        var currentSql = $('#sql-generated').text().trim();
        $('#sql-input').val(currentSql || 'SELECT * FROM ' + tableName);
        $('#sql-modal').show();
        setTimeout(function() { $('#sql-input').focus(); }, 100);
    },

    closeModals: function() {
        $('.modal').hide();
    },

    executeSql: function() {
        var sql = $('#sql-input').val().trim();

        if (!sql) {
            Admin.showAlert('Escribe una consulta SQL', 'warning');
            return;
        }

        Admin.showLoading($('#results-container'));
        this.closeModals();
        this.showOverlay('Ejecutando consulta SQL...');

        var self = this;
        Admin.post('modules/inspector/ajax/ejecutar_sql.php', { sql: sql })
        .then(function(response) {
            self.hideOverlay();
            var inner = response.data || {};
            self.data = inner.data || [];
            self.totalRecords = inner.rows || self.data.length;
            self.renderResults({ data: self.data }, true);
            self.updateCount(self.totalRecords);
            $('#sql-generated').text(sql);
        })
        .catch(function(error) {
            self.hideOverlay();
            Admin.showError($('#results-container'), 'Error SQL: ' + error.message);
        });
    },

    // ============================================
    // TEST DE CONEXIÓN
    // ============================================

    testConnection: function() {
        var $btn = $('#btn-test-connection');
        var $result = $('#connection-test-result');

        $btn.prop('disabled', true).addClass('loading');
        $result.hide().removeClass('connection-ok connection-error');

        var self = this;
        Admin.post('modules/inspector/ajax/test_connection.php', {})
        .then(function(response) {
            var d = response.data || {};
            var html = '<div class="connection-test-card">' +
                '<div class="connection-test-header">' +
                    '<span class="connection-icon ok">&#10003;</span>' +
                    '<strong>Conexión exitosa</strong>' +
                '</div>' +
                '<div class="connection-test-details">' +
                    '<div><span>Driver:</span> <code>' + (d.driver || '-') + '</code></div>' +
                    '<div><span>Versión:</span> <code>' + (d.version || '-') + '</code></div>' +
                    '<div><span>Tablas:</span> <code>' + (d.tables_count || 0) + '</code></div>' +
                    '<div><span>Latencia:</span> <code>' + (d.latency_ms || 0) + ' ms</code></div>' +
                '</div>' +
            '</div>';

            $result.html(html).addClass('connection-ok').fadeIn(200);
        })
        .catch(function(error) {
            var html = '<div class="connection-test-card">' +
                '<div class="connection-test-header">' +
                    '<span class="connection-icon error">&#10007;</span>' +
                    '<strong>Error de conexión</strong>' +
                '</div>' +
                '<div class="connection-test-details">' +
                    '<div class="connection-error-msg">' + (error.message || 'Error desconocido') + '</div>' +
                '</div>' +
            '</div>';

            $result.html(html).addClass('connection-error').fadeIn(200);
        })
        .always(function() {
            $btn.prop('disabled', false).removeClass('loading');
        });
    }
};

// DO NOT use $(document).ready() here â€” Admin.loadModule() calls Inspector.init()
// after the inspector HTML is loaded into the DOM.
