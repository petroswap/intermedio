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
    _initialSql: null,

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
            var tableName = self.currentTable || 'MITABLA';
            var col = (self.columns.length > 0) ? (self.columns[0].COLUMNA || 'COLUMNA1') : 'COLUMNA1';
            var finalSql = sql.replace(/\{TABLE\}/g, tableName).replace(/\{COL\}/g, col);
            if (self._cmEditor) {
                self._cmEditor.setValue(finalSql);
            } else {
                $('#sql-input').val(finalSql);
            }
        });
        $(document).on('input', '#sql-input', function() {
            // Solo actualiza el editor, sql-generated se actualiza al ejecutar
        });
        $(document).on('click', '.sql-example', function(e) {
            e.preventDefault();
            const sql = $(e.target).closest('.sql-example').data('sql');
            if (sql) { $('#sql-input').val(sql); self.executeSql(); }
        });

        // Export
        $(document).on('click', '#btn-export-csv', function() { self.exportCSV(); });
        $(document).on('click', '#btn-export-json', function() { self.exportJSON(); });

        // Reset SQL
        $(document).on('click', '#btn-reset-sql', function() {
            if (self._initialSql) {
                $('#sql-generated').text(self._initialSql);
                if (self._cmEditor) {
                    self._cmEditor.setValue(self._initialSql);
                } else {
                    $('#sql-input').val(self._initialSql);
                }
            }
            $('#btn-reset-sql').hide();
            self.filters = [];
            self.showingAll = false;
            self.currentPage = 1;
            $('#filter-field').val('');
            $('#filter-value').val('');
            $('#active-filters').empty();
            self.loadLastRecords(10);
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
            '<tr><td colspan="2"><div class="skeleton-cell name" style="width:150px"></div></td></tr>' +
            '<tr><td colspan="2"><div class="skeleton-cell name" style="width:120px"></div></td></tr>' +
            '<tr><td colspan="2"><div class="skeleton-cell name" style="width:180px"></div></td></tr>'
        );

        Admin.post('modules/inspector/ajax/listar_tablas.php', {})
        .then(function(response) {
            if (response.data && response.data.length > 0) {
                self.renderTablesList(response.data);
            } else {
                tbody.html('<tr><td colspan="2" class="text-center" style="padding:2rem;color:var(--text-tertiary)">No se encontraron tablas</td></tr>');
            }
        })
        .catch(function(error) {
            Admin.logError('loadTables', error);
            tbody.html('<tr><td colspan="2" class="text-center" style="padding:2rem;color:var(--error)">Error al conectar con la BD</td></tr>');
            Admin.showAlert('No se pudo conectar a la base de datos Firebird.', 'danger', 'Error de conexión');
        });
    },

    renderTablesList: function(tables) {
        const self = this;
        const tbody = $('#tables-tbody').empty();

        tables.forEach(function(table) {
            const name = (table.TABLA || table.tabla || '').trim();
            var cachedCount = self.getTableCount(name);

            tbody.append(
                '<tr data-table="' + name + '" class="clickable-row">' +
                    '<td>' +
                        '<div class="table-name-cell">' +
                            '<span class="table-name">' + name + '</span>' +
                            '<span class="table-count-badge">' + (cachedCount !== null ? Admin.formatNumber(cachedCount) : '...') + '</span>' +
                        '</div>' +
                    '</td>' +
                    '<td>' +
                        '<button class="table-action-btn" data-table="' + name + '">Ver datos</button>' +
                    '</td>' +
                '</tr>'
            );
        });

        this.initTablesDataTable();
        this.loadTableCounts(tables);
    },

    getTableCount: function(tableName) {
        try {
            var cache = JSON.parse(localStorage.getItem('table_counts') || '{}');
            var entry = cache[tableName];
            if (entry && Date.now() - entry.time < 300000) {
                return entry.count;
            }
        } catch(e) {}
        return null;
    },

    setTableCount: function(tableName, count) {
        try {
            var cache = JSON.parse(localStorage.getItem('table_counts') || '{}');
            cache[tableName] = { count: count, time: Date.now() };
            localStorage.setItem('table_counts', JSON.stringify(cache));
        } catch(e) {}
    },

    loadTableCounts: function(tables) {
        var self = this;
        var toCount = [];
        tables.forEach(function(t) {
            var name = (t.TABLA || t.tabla || '').trim();
            if (self.getTableCount(name) === null) {
                toCount.push(name);
            }
        });
        if (toCount.length === 0) return;

        Admin.post('modules/inspector/ajax/contar_tablas.php', { tables: toCount })
        .then(function(response) {
            var counts = response.data || {};
            Object.keys(counts).forEach(function(name) {
                self.setTableCount(name, counts[name]);
                var badge = $('#tables-datatable tbody tr[data-table="' + name + '"] .table-count-badge');
                if (badge.length) {
                    badge.text(Admin.formatNumber(counts[name]));
                }
            });
        });
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
            order: [[0, 'asc']],
            columns: [
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
        this._initialSql = null;

        $('#sql-generated').text('SELECT * FROM ' + tableName);
        $('#btn-reset-sql').hide();

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
            Admin.logError('loadTableInfo', error);
            Admin.showError($('#results-container'), 'Error al cargar columnas: ' + error.message);
        });
    },

    renderColumns: function() {
        var self = this;
        var container = $('#columns-container').empty();

        this.columns.forEach(function(col) {
            var name = (col.COLUMNA || '').trim();
            if (!name) return;

            var typeNum = parseInt(col.TIPO) || 0;
            var typeNames = {45: 'VARCHAR', 46: 'CHAR', 261: 'BLOB', 271: 'INTEGER', 279: 'SMALLINT', 327: 'DOUBLE', 32754: 'FLOAT', 520: 'DATE', 560: 'TIME', 580: 'TIMESTAMP', 26154: 'BIGINT'};
            var typeName = typeNames[typeNum] || 'TYPE_' + typeNum;
            var isPk = col.IS_PK == 1;

            var chip = $('<div class="column-chip selected">')
                .attr('data-column', name)
                .attr('title', typeName + (isPk ? ' (PK)' : ''));
            
            var label = '<span class="col-name">' + name + '</span>';
            var meta = '<span class="col-meta">' + typeName + (isPk ? ' 🔑' : '') + '</span>';
            
            chip.html(label + meta);
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
            Admin.logError('loadLastRecords', error);
            Admin.showError($('#results-container'), 'Error al cargar datos: ' + error.message);
        });
    },

    /**
     * Cargar todos los registros de la tabla
     */
    loadAllRecords: function() {
        if (!this.currentTable) return;

        var self = this;
        var tableName = this.currentTable;
        var cachedCount = this.getTableCount(tableName);

        if (cachedCount !== null && cachedCount > 1000) {
            Admin.confirm(
                'La tabla "' + tableName + '" tiene ' + Admin.formatNumber(cachedCount) + ' registros. Cargar todos puede ser lento. ¿Continuar?',
                'Muchos registros'
            ).then(function(ok) {
                if (ok) self._doLoadAllRecords();
            });
        } else {
            this._doLoadAllRecords();
        }
    },

    _doLoadAllRecords: function() {
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
            Admin.logError('loadAllRecords', error);
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
            Admin.logError('applyFilters', error);
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
            params['filters[' + index + '][field]'] = filter.field;
            params['filters[' + index + '][operator]'] = filter.operator;
            params['filters[' + index + '][value]'] = filter.value;
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

        if (operator === 'BETWEEN') {
            var parts = value.split(',');
            if (parts.length !== 2 || !parts[0].trim() || !parts[1].trim()) {
                Admin.showAlert('BETWEEN requiere dos valores separados por coma. Ejemplo: 100, 500', 'warning');
                return;
            }
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
            if (filter.operator === 'BETWEEN') {
                var parts = filter.value.split(',');
                opLabel = 'entre ' + parts[0].trim() + ' y ' + parts[1].trim();
            }
            container.append(
                '<div class="filter-tag">' +
                    '<span>' + filter.field + ' ' + opLabel + (filter.operator !== 'BETWEEN' ? ' &quot;' + filter.value + '&quot;' : '') + '</span>' +
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
        $('#results-count').text(Admin.formatNumber(count));
        $('#current-table-count').text(Admin.formatNumber(count) + ' registros');
    },

    updateRecordsShownInfo: function(shown, total) {
        if (total !== undefined && shown !== undefined && shown < total) {
            $('#records-shown').show();
            $('#shown-count').text(shown);
            $('#total-count').text(Admin.formatNumber(total));
        } else {
            $('#records-shown').hide();
        }
        if (shown > 0) {
            $('#export-buttons').show();
        } else {
            $('#export-buttons').hide();
        }
    },

    // ============================================
    // SQL GENERADO
    // ============================================

    updateSql: function() {
        var selectedColumns = this.getSelectedColumns();
        var fields = selectedColumns.length > 0 ? selectedColumns.join(', ') : '*';
        var tableName = this.currentTable || '';

        if (!tableName) {
            $('#sql-generated').text('-- Selecciona una tabla para generar SQL');
            return;
        }

        var sql = 'SELECT ' + fields + '\nFROM ' + tableName;

        if (this.filters.length > 0) {
            var conditions = this.filters.map(function(f) {
                if (f.operator === 'LIKE') {
                    return f.field + " LIKE '%" + f.value + "%'";
                }
                if (f.operator === 'BETWEEN') {
                    var parts = f.value.split(',');
                    return f.field + " BETWEEN '" + parts[0].trim() + "' AND '" + parts[1].trim() + "'";
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

        if (this._initialSql === null) {
            this._initialSql = sql;
        }

        if (sql !== this._initialSql) {
            $('#btn-reset-sql').show();
        } else {
            $('#btn-reset-sql').hide();
        }
    },

    // ============================================
    // SQL LIBRE
    // ============================================

    openSqlTab: function() {
        var tableName = this.currentTable || 'CLIENTES';
        var currentSql = $('#sql-generated').text().trim();
        var sqlVal = currentSql || 'SELECT * FROM ' + tableName;
        
        $('#sql-modal').show();
        
        var textarea = document.getElementById('sql-input');
        if (typeof CodeMirror !== 'undefined') {
            if (this._cmEditor) {
                this._cmEditor.setValue(sqlVal);
                this._cmEditor.refresh();
                setTimeout(function() { this._cmEditor.focus(); }.bind(this), 100);
            } else {
                this._cmEditor = CodeMirror.fromTextArea(textarea, {
                    mode: 'text/x-sql',
                    theme: 'monokai',
                    lineNumbers: true,
                    indentWithTabs: true,
                    smartIndent: true,
                    autofocus: true,
                    lineWrapping: true
                });
                this._cmEditor.setValue(sqlVal);
                var self = this;
                setTimeout(function() { self._cmEditor.focus(); }, 100);
            }
        } else {
            $('#sql-input').val(sqlVal);
            setTimeout(function() { $('#sql-input').focus(); }, 100);
        }
    },

    closeModals: function() {
        $('.modal').hide();
    },

    executeSql: function() {
        var sql = this._cmEditor ? this._cmEditor.getValue().trim() : $('#sql-input').val().trim();

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
            if (self._initialSql === null) self._initialSql = sql;
            $('#btn-reset-sql').toggle(sql !== self._initialSql);
        })
        .catch(function(error) {
            self.hideOverlay();
            Admin.showError($('#results-container'), 'Error SQL: ' + error.message);
        });
    },

    // ============================================
    // EXPORTAR DATOS
    // ============================================

    exportCSV: function() {
        if (!this.data || this.data.length === 0) {
            Admin.showAlert('No hay datos para exportar', 'warning');
            return;
        }
        var headers = Object.keys(this.data[0]);
        var csvRows = [];
        csvRows.push(headers.join(','));
        this.data.forEach(function(row) {
            var values = headers.map(function(h) {
                var val = row[h] ?? '';
                val = String(val).replace(/"/g, '""');
                return '"' + val + '"';
            });
            csvRows.push(values.join(','));
        });
        var blob = new Blob(['\uFEFF' + csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = (this.currentTable || 'datos') + '.csv';
        a.click();
        URL.revokeObjectURL(url);
        Admin.toastSuccess('CSV exportado');
    },

    exportJSON: function() {
        if (!this.data || this.data.length === 0) {
            Admin.showAlert('No hay datos para exportar', 'warning');
            return;
        }
        var blob = new Blob([JSON.stringify(this.data, null, 2)], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = (this.currentTable || 'datos') + '.json';
        a.click();
        URL.revokeObjectURL(url);
        Admin.toastSuccess('JSON exportado');
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
