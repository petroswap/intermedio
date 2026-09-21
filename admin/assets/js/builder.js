/**
 * ============================================
 * BUILDER.JS - Query Builder Module
 * ============================================
 * Visual SELECT query construction with JOINs
 */

const Builder = {
    _eventsBound: false,
    _resultsDt: null,
    _lastData: null,

    // State
    tables: [],
    columns: [],
    selectedTable: '',
    selectedColumns: [],
    joins: [],
    filters: [],
    orderColumn: '',
    orderDirection: 'ASC',
    limit: 100,
    isDistinct: false,
    groupByColumn: '',

    // Firebird type map
    _typeMap: {
        7: 'SMALLINT', 8: 'INTEGER', 9: 'QUAD', 10: 'FLOAT',
        11: 'D_FLOAT', 12: 'DATE', 13: 'TIME', 14: 'CHAR',
        16: 'BIGINT', 27: 'DOUBLE', 35: 'TIMESTAMP', 37: 'VARCHAR',
        38: 'NCHAR', 40: 'CSTRING', 45: 'BLOB_ID', 261: 'BLOB',
        271: 'ARRAY', 32764: 'BOOLEAN'
    },

    _fkJoinSuggestions: null,

    init: function() {
        if (!this._eventsBound) {
            this.bindEvents();
            this._eventsBound = true;
        }
        this.loadTables();
        this.resetState();
    },

    resetState: function() {
        this.columns = [];
        this.selectedTable = '';
        this.selectedColumns = [];
        this.joins = [];
        this.filters = [];
        this.orderColumn = '';
        this.orderDirection = 'ASC';
        this.limit = 100;
        this.isDistinct = false;
        this.groupByColumn = '';
        this._lastData = null;
        this._fkJoinSuggestions = null;
        if (this._resultsDt) { this._resultsDt.destroy(); this._resultsDt = null; }
        $('#builder-columns-section, #builder-joins-section, #builder-group-section').hide();
        $('#builder-results-container').hide();
        $('#builder-distinct').prop('checked', false);
        $('#builder-group-by').val('');
        this.updateSql();
    },

    getTypeName: function(code) {
        return this._typeMap[code] || 'TYPE_' + code;
    },

    bindEvents: function() {
        var self = this;
        $(document).off('.builder');

        // Table selection
        $(document).on('change.builder', '#builder-table', function() {
            var table = $(this).val();
            if (table) { self.selectTable(table); }
            else { self.resetState(); }
        });

        // Column selection
        $(document).on('click.builder', '#builder-select-all', function() { self.selectAllColumns(); });
        $(document).on('click.builder', '#builder-select-none', function() { self.selectNoColumns(); });
        $(document).on('change.builder', '.builder-column-check', function() {
            self.updateSelectedColumns();
            self.updateSql();
        });

        // DISTINCT
        $(document).on('change.builder', '#builder-distinct', function() {
            self.isDistinct = $(this).prop('checked');
            self.updateSql();
        });

        // JOINs
        $(document).on('click.builder', '#builder-add-join', function() { self.addJoin(); });
        $(document).on('click.builder', '.builder-join-remove', function() {
            self.removeJoin($(this).data('index'));
        });
        $(document).on('change.builder', '.builder-join-type', function() {
            self.joins[$(this).data('index')].type = $(this).val();
            self.updateSql();
        });
        $(document).on('change.builder', '.builder-join-table', function() {
            self.detectJoin($(this).data('index'));
        });
        $(document).on('input.builder', '.builder-join-on-input', function() {
            var idx = $(this).data('index');
            self.joins[idx].on = $(this).val();
            self.validateJoinOn(idx);
            self.updateSql();
        });
        $(document).on('click.builder', '.builder-join-col-chip', function() {
            var $chip = $(this);
            var idx = $chip.data('index');
            var alias = $chip.data('table');
            var col = $chip.data('col');
            var $input = $('.builder-join-on-input[data-index="' + idx + '"]');
            var current = $input.val();
            var ins = alias + '.' + col;
            if (current && !/\s$/.test(current) && current.slice(-1) !== ' ') ins = ' ' + ins;
            $input.val(current + ins).trigger('input');
        });

        // Filters
        $(document).on('click.builder', '#builder-add-filter', function() { self.addFilter(); });
        $(document).on('click.builder', '.builder-filter-remove', function() {
            self.removeFilter($(this).data('index'));
        });
        $(document).on('change.builder', '.builder-filter-column, .builder-filter-operator', function() {
            var $row = $(this).closest('.builder-filter-row');
            self.updateFilter($row.data('index'));
            self.toggleFilterValue($row);
        });
        $(document).on('input.builder', '.builder-filter-value', function() {
            self.updateFilter($(this).closest('.builder-filter-row').data('index'));
        });

        // Order
        $(document).on('change.builder', '#builder-order-column', function() {
            self.orderColumn = $(this).val();
            self.updateSql();
        });
        $(document).on('change.builder', '#builder-order-direction', function() {
            self.orderDirection = $(this).val();
            self.updateSql();
        });

        // GROUP BY
        $(document).on('change.builder', '#builder-group-by', function() {
            self.groupByColumn = $(this).val();
            self.updateSql();
        });

        // Limit
        $(document).on('input.builder', '#builder-limit', function() {
            self.limit = parseInt($(this).val()) || 100;
            self.updateSql();
        });

        // SQL actions
        $(document).on('click.builder', '#builder-copy-sql', function() { self.copySql(); });
        $(document).on('click.builder', '#builder-open-sql', function() { self.openInSqlConsole(); });
        $(document).on('click.builder', '#builder-execute', function() { self.executeSql(); });
        $(document).on('click.builder', '#builder-reset', function() { self.resetState(); self.loadTables(); });

        // Export
        $(document).on('click.builder', '#builder-export-csv', function() { self.exportCSV(); });
        $(document).on('click.builder', '#builder-export-json', function() { self.exportJSON(); });

        // Keyboard shortcuts
        $(document).on('keydown.builder', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                if (!$('#builder-execute').prop('disabled')) self.executeSql();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
                e.preventDefault();
                self.resetState();
                self.loadTables();
            }
        });
    },

    loadTables: function() {
        var self = this;
        Admin.post('modules/inspector/ajax/listar_tablas.php', {})
        .then(function(response) {
            self.tables = response.data || [];
            self.renderTablesList();
        })
        .catch(function(error) {
            Admin.logError('Builder.loadTables', error);
        });
    },

    renderTablesList: function() {
        var $select = $('#builder-table');
        var current = $select.val();
        $select.find('option:gt(0)').remove();
        this.tables.forEach(function(t) {
            var name = t.TABLA || t.tabla || '';
            if (name) $select.append('<option value="' + name + '">' + name + '</option>');
        });
        if (current) $select.val(current);
    },

    selectTable: function(tableName) {
        this.selectedTable = tableName;
        this.selectedColumns = [];
        this.joins = [];
        this.filters = [];
        this.orderColumn = '';
        this.groupByColumn = '';
        this._fkJoinSuggestions = null;
        this.loadColumns(tableName);
        this.updateSql();
    },

    loadColumns: function(tableName) {
        var self = this;
        Admin.post('modules/inspector/ajax/listar_columnas.php', { table: tableName })
        .then(function(response) {
            self.columns = response.data || [];
            self.renderColumns();
            $('#builder-columns-section, #builder-joins-section').show();
            self.renderJoinsList();
            self.renderFiltersList();
            self.updateOrderColumns();
            self.updateGroupByColumns();
            self.loadFKSuggestions();
        })
        .catch(function(error) {
            Admin.logError('Builder.loadColumns', error);
        });
    },

    loadFKSuggestions: function() {
        var self = this;
        this._fkJoinSuggestions = null;
        Admin.get('modules/inspector/ajax/relaciones.php', { table: this.selectedTable })
        .then(function(response) {
            var data = response.data || {};
            self._fkJoinSuggestions = {
                hijos: data.hijos || [],
                padres: data.padres || []
            };
        })
        .catch(function() {});
    },

    renderColumns: function() {
        var $list = $('#builder-columns-list').empty();
        var self = this;

        // Main table columns
        this._renderColumnGroup($list, 'a', this.selectedTable, this.columns, true);

        // JOIN table columns
        this.joins.forEach(function(join, index) {
            if (join.table && join.joinColumns && join.joinColumns.length > 0) {
                var joinAlias = String.fromCharCode(98 + index);
                self._renderColumnGroup($list, joinAlias, join.table, join.joinColumns, false);
            }
        });

        this.updateSelectedColumns();
    },

    _renderColumnGroup: function($list, alias, tableName, columns, checked) {
        var self = this;
        var $group = $('<div class="builder-col-group"></div>');
        $group.append('<div class="builder-col-group-header"><span class="builder-col-group-alias">' + alias + '</span> ' + tableName + '</div>');
        columns.forEach(function(col) {
            var colName = (col.COLUMNA || '').trim();
            var pk = col.IS_PK ? ' <span class="builder-pk-badge">PK</span>' : '';
            var type = self.getTypeName(col.TIPO);
            var val = alias + '.' + colName;
            var isChecked = checked ? ' checked' : '';
            var html = '<label class="builder-checkbox-item">' +
                '<input type="checkbox" class="builder-column-check" value="' + val + '"' + isChecked + '>' +
                '<span class="builder-checkbox-label">' + colName + pk + '</span>' +
                '<span class="builder-checkbox-type">' + type + '</span>' +
            '</label>';
            $group.append(html);
        });
        $list.append($group);
    },

    selectAllColumns: function() {
        $('.builder-column-check').prop('checked', true);
        this.updateSelectedColumns();
        this.updateSql();
    },

    selectNoColumns: function() {
        $('.builder-column-check').prop('checked', false);
        this.updateSelectedColumns();
        this.updateSql();
    },

    updateSelectedColumns: function() {
        this.selectedColumns = [];
        $('.builder-column-check:checked').each(function() {
            Builder.selectedColumns.push($(this).val());
        });
    },

    // ==================== JOINs ====================
    addJoin: function() {
        this.joins.push({
            type: 'LEFT', table: '', on: '',
            from_column: '', to_column: '', joinColumns: []
        });
        this.renderJoinsList();
    },

    removeJoin: function(index) {
        this.joins.splice(index, 1);
        this.renderJoinsList();
        this.updateSql();
    },

    renderJoinsList: function() {
        var $list = $('#builder-joins-list').empty();
        var self = this;

        if (this.joins.length === 0) {
            $list.html('<p class="builder-empty-text">Sin JOINs configurados</p>');
            return;
        }

        this.joins.forEach(function(join, index) {
            var colsHtml = '';
            if (join.joinColumns && join.joinColumns.length > 0) {
                colsHtml = '<div class="builder-join-columns-ref">' +
                    '<div class="builder-join-cols-header">Columnas disponibles <span class="builder-join-cols-hint">(click para insertar en ON)</span></div>' +
                    '<div class="builder-join-cols-list">';
                if (self.columns && self.columns.length > 0) {
                    colsHtml += '<div class="builder-join-cols-group">' +
                        '<span class="builder-join-cols-table">a: ' + self.selectedTable + '</span>';
                    self.columns.forEach(function(c) {
                        var n = (c.COLUMNA || '').trim();
                        colsHtml += '<span class="builder-join-col-chip" data-table="a" data-col="' + n + '" data-index="' + index + '">a.' + n + '</span>';
                    });
                    colsHtml += '</div>';
                }
                colsHtml += '<div class="builder-join-cols-group">' +
                    '<span class="builder-join-cols-table">b: ' + (join.table || '') + '</span>';
                join.joinColumns.forEach(function(c) {
                    var n = (c.COLUMNA || '').trim();
                    colsHtml += '<span class="builder-join-col-chip" data-table="b" data-col="' + n + '" data-index="' + index + '">b.' + n + '</span>';
                });
                colsHtml += '</div></div></div>';
            }

            var onClass = 'builder-join-on-input';
            if (join.table && (!join.on || join.on.indexOf('=') === -1)) onClass += ' builder-input-error';

            var html = '<div class="builder-join-row" data-index="' + index + '">' +
                '<div class="builder-join-header">' +
                    '<select class="form-select form-select-sm builder-join-type" data-index="' + index + '">' +
                        '<option value="INNER"' + (join.type === 'INNER' ? ' selected' : '') + '>INNER</option>' +
                        '<option value="LEFT"' + (join.type === 'LEFT' ? ' selected' : '') + '>LEFT</option>' +
                        '<option value="RIGHT"' + (join.type === 'RIGHT' ? ' selected' : '') + '>RIGHT</option>' +
                        '<option value="CROSS"' + (join.type === 'CROSS' ? ' selected' : '') + '>CROSS</option>' +
                    '</select>' +
                    '<select class="form-select form-select-sm builder-join-table" data-index="' + index + '">' +
                        '<option value="">Seleccionar tabla...</option>' +
                    '</select>' +
                    '<button class="btn btn-xs btn-ghost btn-danger builder-join-remove" data-index="' + index + '" title="Eliminar JOIN">&times;</button>' +
                '</div>' +
                '<div class="builder-join-on">' +
                    '<span class="builder-join-on-label">ON</span>' +
                    '<input type="text" class="form-input form-input-sm ' + onClass + '" value="' + self.escapeHtml(join.on || '') + '" placeholder="a.COL = b.COL" data-index="' + index + '">' +
                '</div>' +
                colsHtml +
            '</div>';
            $list.append(html);
        });

        this.populateJoinTables();
    },

    validateJoinOn: function(index) {
        var join = this.joins[index];
        var $input = $('.builder-join-on-input[data-index="' + index + '"]');
        if (join.table && (!join.on || join.on.indexOf('=') === -1)) {
            $input.addClass('builder-input-error');
        } else {
            $input.removeClass('builder-input-error');
        }
    },

    populateJoinTables: function() {
        var self = this;
        $('.builder-join-table').each(function() {
            var $select = $(this);
            var index = parseInt($select.data('index'));
            var join = self.joins[index];
            var currentValue = join ? join.table : '';
            $select.find('option:gt(0)').remove();
            self.tables.forEach(function(t) {
                var name = t.TABLA || t.tabla || '';
                if (name && name !== self.selectedTable) {
                    $select.append('<option value="' + name + '"' + (name === currentValue ? ' selected' : '') + '>' + name + '</option>');
                }
            });
        });
    },

    detectJoin: function(index) {
        var join = this.joins[index];
        var targetTable = $('.builder-join-table[data-index="' + index + '"]').val();

        if (!targetTable) {
            join.table = ''; join.on = ''; join.from_column = ''; join.to_column = ''; join.joinColumns = [];
            this.renderJoinsList();
            this.updateSql();
            return;
        }

        join.table = targetTable;
        var self = this;

        Admin.post('modules/inspector/ajax/listar_columnas.php', { table: targetTable })
        .then(function(response) {
            join.joinColumns = response.data || [];
            return self.tryAutoJoin(join, targetTable);
        })
        .then(function() {
            self.renderJoinsList();
            self.updateSql();
        })
        .catch(function(error) {
            Admin.logError('Builder.detectJoin', error);
            join.joinColumns = []; join.on = '';
            self.renderJoinsList();
            self.updateSql();
        });
    },

    tryAutoJoin: function(join, targetTable) {
        var self = this;
        var found = false;

        // 1. Try FK detection
        if (this._fkJoinSuggestions) {
            (this._fkJoinSuggestions.hijos || []).forEach(function(r) {
                if (trim(r.tabla_hija) === targetTable) {
                    join.on = 'a.' + trim(r.columna_pk) + ' = b.' + trim(r.columna_fk);
                    join.from_column = trim(r.columna_pk);
                    join.to_column = trim(r.columna_fk);
                    found = true;
                }
            });
            if (!found) {
                (this._fkJoinSuggestions.padres || []).forEach(function(r) {
                    if (trim(r.tabla_padre) === targetTable) {
                        join.on = 'a.' + trim(r.columna_fk) + ' = b.' + trim(r.columna_pk);
                        join.from_column = trim(r.columna_fk);
                        join.to_column = trim(r.columna_pk);
                        found = true;
                    }
                });
            }
        }

        // 2. If no FK, try exact column name match
        if (!found && self.columns.length > 0 && join.joinColumns.length > 0) {
            var mainCols = self.columns.map(function(c) { return (c.COLUMNA || '').trim().toUpperCase(); });
            var joinCols = join.joinColumns.map(function(c) { return (c.COLUMNA || '').trim().toUpperCase(); });
            for (var i = 0; i < mainCols.length; i++) {
                for (var j = 0; j < joinCols.length; j++) {
                    if (mainCols[i] && mainCols[i] === joinCols[j]) {
                        join.on = 'a.' + mainCols[i] + ' = b.' + joinCols[j];
                        join.from_column = mainCols[i];
                        join.to_column = joinCols[j];
                        found = true;
                        break;
                    }
                }
                if (found) break;
            }
        }

        // 3. If no match, clear
        if (!found) { join.on = ''; join.from_column = ''; join.to_column = ''; }
        return Promise.resolve();
    },

    // ==================== Filters ====================
    addFilter: function() {
        this.filters.push({ column: '', operator: '=', value: '' });
        this.renderFiltersList();
    },

    removeFilter: function(index) {
        this.filters.splice(index, 1);
        this.renderFiltersList();
        this.updateSql();
    },

    renderFiltersList: function() {
        var $list = $('#builder-filters-list').empty();
        var self = this;

        if (this.filters.length === 0) {
            $list.html('<p class="builder-empty-text">Sin filtros configurados</p>');
            return;
        }

        var operators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'BETWEEN', 'IN', 'IS NULL', 'IS NOT NULL'];
        this.filters.forEach(function(filter, index) {
            var ops = operators.map(function(op) {
                return '<option value="' + op + '"' + (filter.operator === op ? ' selected' : '') + '>' + op + '</option>';
            }).join('');

            var hideValue = (filter.operator === 'IS NULL' || filter.operator === 'IS NOT NULL');
            var html = '<div class="builder-filter-row" data-index="' + index + '">' +
                '<select class="form-select form-select-sm builder-filter-column" data-index="' + index + '">' +
                    '<option value="">Columna...</option>' +
                '</select>' +
                '<select class="form-select form-select-sm builder-filter-operator" data-index="' + index + '">' + ops + '</select>' +
                '<input type="text" class="form-input form-input-sm builder-filter-value' + (hideValue ? ' builder-input-hidden' : '') + '" value="' + (filter.value || '') + '" placeholder="' + self.getFilterPlaceholder(filter.operator) + '" data-index="' + index + '">' +
                '<button class="btn btn-xs btn-ghost btn-danger builder-filter-remove" data-index="' + index + '" title="Eliminar filtro">&times;</button>' +
            '</div>';
            $list.append(html);
        });

        this.populateFilterColumns();
    },

    getFilterPlaceholder: function(operator) {
        if (operator === 'IS NULL' || operator === 'IS NOT NULL') return '';
        if (operator === 'BETWEEN') return 'valor1, valor2';
        if (operator === 'IN') return 'val1, val2, val3';
        if (operator === 'LIKE') return '%texto%';
        return 'Valor';
    },

    toggleFilterValue: function($row) {
        var op = $row.find('.builder-filter-operator').val();
        var $val = $row.find('.builder-filter-value');
        if (op === 'IS NULL' || op === 'IS NOT NULL') {
            $val.addClass('builder-input-hidden').val('');
        } else {
            $val.removeClass('builder-input-hidden');
        }
    },

    populateFilterColumns: function() {
        var self = this;
        $('.builder-filter-column').each(function() {
            var $select = $(this);
            var current = $select.val();
            $select.find('option:gt(0)').remove();
            self.columns.forEach(function(col) {
                $select.append('<option value="' + col.COLUMNA + '">' + col.COLUMNA + '</option>');
            });
            if (current) $select.val(current);
        });
    },

    updateFilter: function(index) {
        var $row = $('.builder-filter-row[data-index="' + index + '"]');
        this.filters[index].column = $row.find('.builder-filter-column').val();
        this.filters[index].operator = $row.find('.builder-filter-operator').val();
        this.filters[index].value = $row.find('.builder-filter-value').val();
        this.updateSql();
    },

    // ==================== Order ====================
    updateOrderColumns: function() {
        var $select = $('#builder-order-column');
        var current = $select.val();
        $select.find('option:gt(0)').remove();
        var self = this;
        // Main table
        this.columns.forEach(function(col) {
            $select.append('<option value="a.' + col.COLUMNA + '">a.' + col.COLUMNA + '</option>');
        });
        // JOIN tables
        this.joins.forEach(function(join, index) {
            if (join.table && join.joinColumns) {
                var alias = String.fromCharCode(98 + index);
                join.joinColumns.forEach(function(col) {
                    $select.append('<option value="' + alias + '.' + col.COLUMNA + '">' + alias + '.' + col.COLUMNA + '</option>');
                });
            }
        });
        if (current) $select.val(current);
    },

    // ==================== GROUP BY ====================
    updateGroupByColumns: function() {
        var $select = $('#builder-group-by');
        var current = $select.val();
        $select.find('option:gt(0)').remove();
        $('#builder-group-section').show();
        var self = this;
        // Main table
        this.columns.forEach(function(col) {
            $select.append('<option value="a.' + col.COLUMNA + '">a.' + col.COLUMNA + '</option>');
        });
        // JOIN tables
        this.joins.forEach(function(join, index) {
            if (join.table && join.joinColumns) {
                var alias = String.fromCharCode(98 + index);
                join.joinColumns.forEach(function(col) {
                    $select.append('<option value="' + alias + '.' + col.COLUMNA + '">' + alias + '.' + col.COLUMNA + '</option>');
                });
            }
        });
        if (current) $select.val(current);
    },

    // ==================== SQL Generation ====================
    updateSql: function() {
        var sql = this.generateSql();
        $('#builder-sql-output').text(sql);
        $('#builder-execute').prop('disabled', !this.selectedTable);
    },

    generateSql: function() {
        if (!this.selectedTable) return 'SELECT\n    *\nFROM TABLA';

        var alias = 'a';
        var distinct = this.isDistinct ? 'DISTINCT ' : '';

        // Columns - handle aliased columns (a.COL, b.COL)
        var cols = this.selectedColumns.length > 0 ? this.selectedColumns : ['*'];
        var self = this;
        var colList = cols.map(function(c) {
            if (c.indexOf('.') !== -1) {
                var parts = c.split('.');
                return parts[0] + '."' + parts[1] + '"';
            }
            return alias + '."' + c + '"';
        }).join(',\n    ');

        var sql = 'SELECT ' + distinct + '\n    ' + colList + '\nFROM "' + this.selectedTable + '" ' + alias;

        // JOINs
        var joinAliases = [];
        this.joins.forEach(function(join, index) {
            if (join.table && join.on && join.on.indexOf('=') !== -1) {
                var joinAlias = String.fromCharCode(98 + index);
                joinAliases.push({ alias: joinAlias, table: join.table, type: join.type, on: join.on });
            }
        });

        joinAliases.forEach(function(j) {
            sql += '\n' + j.type + ' JOIN "' + j.table + '" ' + j.alias;
            // Replace a. with main alias, b. with this join's alias
            var fixedOn = j.on.replace(/\ba\./g, alias + '.').replace(/\bb\./g, j.alias + '.');
            sql += '\n    ON ' + fixedOn;
        });

        // WHERE
        var whereConditions = [];
        this.filters.forEach(function(filter) {
            if (filter.column && filter.operator) {
                var condition = alias + '."' + filter.column + '" ' + filter.operator;
                if (filter.value && filter.operator !== 'IS NULL' && filter.operator !== 'IS NOT NULL') {
                    if (filter.operator === 'LIKE') {
                        condition += " '" + filter.value + "'";
                    } else if (filter.operator === 'BETWEEN') {
                        var parts = filter.value.split(',');
                        if (parts.length === 2) {
                            condition += " '" + parts[0].trim() + "' AND '" + parts[1].trim() + "'";
                        }
                    } else if (filter.operator === 'IN') {
                        var vals = filter.value.split(',').map(function(v) { return "'" + v.trim() + "'"; });
                        condition += ' (' + vals.join(', ') + ')';
                    } else {
                        condition += " '" + filter.value + "'";
                    }
                }
                whereConditions.push(condition);
            }
        });

        if (whereConditions.length > 0) {
            sql += '\nWHERE ' + whereConditions.join('\n  AND ');
        }

        // GROUP BY
        if (this.groupByColumn) {
            if (this.groupByColumn.indexOf('.') !== -1) {
                var gParts = this.groupByColumn.split('.');
                sql += '\nGROUP BY ' + gParts[0] + '."' + gParts[1] + '"';
            } else {
                sql += '\nGROUP BY ' + alias + '."' + this.groupByColumn + '"';
            }
        }

        // ORDER BY
        if (this.orderColumn) {
            if (this.orderColumn.indexOf('.') !== -1) {
                var oParts = this.orderColumn.split('.');
                sql += '\nORDER BY ' + oParts[0] + '."' + oParts[1] + '" ' + this.orderDirection;
            } else {
                sql += '\nORDER BY ' + alias + '."' + this.orderColumn + '" ' + this.orderDirection;
            }
        }

        // ROWS (Firebird LIMIT)
        if (this.limit && this.limit > 0) {
            sql += '\nROWS 1 TO ' + this.limit;
        }

        return sql;
    },

    // ==================== Actions ====================
    copySql: function() {
        Admin.copyToClipboard(this.generateSql());
        Admin.toastSuccess('SQL copiado al portapapeles');
    },

    openInSqlConsole: function() {
        sessionStorage.setItem('builder_pending_sql', this.generateSql());
        window.location.href = '?module=sql';
    },

    executeSql: function() {
        var self = this;

        // Validate JOINs
        for (var i = 0; i < this.joins.length; i++) {
            var j = this.joins[i];
            if (j.table && (!j.on || j.on.indexOf('=') === -1)) {
                Admin.toastError('JOIN con ' + j.table + ': cláusula ON incompleta (formato: a.COL = b.COL)');
                return;
            }
        }

        var sql = this.generateSql();
        var $btn = $('#builder-execute');
        $btn.prop('disabled', true).html('<span class="spinner-sm"></span> Ejecutando...');

        var startTime = performance.now();

        Admin.post('modules/inspector/ajax/ejecutar_sql.php', { sql: sql })
        .then(function(response) {
            var data = response.data || {};
            var rows = data.data || [];
            var elapsed = ((performance.now() - startTime) / 1000).toFixed(2);
            self._lastData = rows;
            self.renderResults(rows, data.rows || rows.length, elapsed);
            Admin.addQueryHistory(sql, true);
        })
        .catch(function(error) {
            Admin.logError('Builder.executeSql', error);
            Admin.toastError('Error: ' + error.message);
            Admin.addQueryHistory(sql, false);
        })
        .finally(function() {
            $btn.prop('disabled', false).html('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg> Ejecutar');
        });
    },

    renderResults: function(rows, count, elapsed) {
        var $container = $('#builder-results-container');
        var $table = $('#builder-results-table');

        if (this._resultsDt) { this._resultsDt.destroy(); this._resultsDt = null; }

        if (!rows || rows.length === 0) {
            $table.html('<div class="builder-empty-results"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg><p>Sin resultados</p></div>');
            $('#builder-results-count').text('0');
            $('#builder-results-time').text(elapsed ? elapsed + 's' : '');
            $container.show();
            return;
        }

        var html = Admin.createTable(rows);
        $table.html(html);
        $('#builder-results-count').text(count || rows.length);
        $('#builder-results-time').text(elapsed ? elapsed + 's' : '');
        $container.show();

        this._resultsDt = $table.find('.data-table').DataTable({
            language: {
                search: "Buscar:", lengthMenu: "Mostrar _MENU_",
                info: "_START_ a _END_ de _TOTAL_", infoEmpty: "Sin resultados",
                infoFiltered: "(filtrado de _MAX_)", zeroRecords: "Sin resultados",
                paginate: { first: "Primero", last: "Ultimo", next: "\u2192", previous: "\u2190" }
            },
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            order: [[0, 'asc']],
            dom: '<"top"fl>rt<"bottom"ip>'
        });
    },

    // ==================== Export ====================
    exportCSV: function() {
        if (!this._lastData || this._lastData.length === 0) {
            Admin.toastWarning('No hay datos para exportar');
            return;
        }
        var headers = Object.keys(this._lastData[0]);
        var csvRows = [headers.join(',')];
        this._lastData.forEach(function(row) {
            var values = headers.map(function(h) {
                var val = row[h] ?? '';
                return '"' + String(val).replace(/"/g, '""') + '"';
            });
            csvRows.push(values.join(','));
        });
        var blob = new Blob(['\uFEFF' + csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a'); a.href = url; a.download = this.selectedTable + '.csv'; a.click();
        URL.revokeObjectURL(url);
        Admin.toastSuccess('CSV exportado');
    },

    exportJSON: function() {
        if (!this._lastData || this._lastData.length === 0) {
            Admin.toastWarning('No hay datos para exportar');
            return;
        }
        var blob = new Blob([JSON.stringify(this._lastData, null, 2)], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a'); a.href = url; a.download = this.selectedTable + '.json'; a.click();
        URL.revokeObjectURL(url);
        Admin.toastSuccess('JSON exportado');
    },

    escapeHtml: function(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
};

function trim(s) { return (s || '').trim(); }
