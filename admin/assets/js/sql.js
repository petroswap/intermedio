const SqlModule = {
    _resultsDt: null,
    _cmEditor: null,

    init: function() {
        this.bindEvents();
        this.loadBookmarks();
        this.initCodeMirror();
        this.syncPanelHeights();
        var self = this;
        $(window).off('resize.sqlmod').on('resize.sqlmod', function() {
            self.syncPanelHeights();
        });
    },

    syncPanelHeights: function() {
        var $left = $('.sql-editor-panel');
        var $right = $('.sql-results-panel');
        if ($left.length && $right.length) {
            var leftH = $left.outerHeight();
            if (leftH > 0) {
                $right.css('height', leftH + 'px');
            }
        }
    },

    initCodeMirror: function() {
        var self = this;
        var textarea = document.getElementById('sql-editor');
        if (typeof CodeMirror !== 'undefined' && textarea && !this._cmEditor) {
            this._cmEditor = CodeMirror.fromTextArea(textarea, {
                mode: 'text/x-sql',
                theme: 'monokai',
                lineNumbers: true,
                indentWithTabs: true,
                smartIndent: true,
                autofocus: true,
                lineWrapping: true
            });
            // Sync heights after CodeMirror finishes layout
            setTimeout(function() { self.syncPanelHeights(); }, 100);
        }
    },

    bindEvents: function() {
        $(document).off('.sqlmod');

        $(document).on('click.sqlmod', '#btn-sql-execute', () => this.execute());
        $(document).on('click.sqlmod', '#btn-sql-clear', () => this.clearEditor());
        $(document).on('click.sqlmod', '#btn-sql-export-csv', () => this.exportCSV());
        $(document).on('click.sqlmod', '#btn-sql-export-json', () => this.exportJSON());
        $(document).on('click.sqlmod', '#btn-save-bookmark', () => this.saveBookmark());
        $(document).on('click.sqlmod', '#btn-sql-export-favorites', () => this.exportFavorites());
        $(document).on('click.sqlmod', '#btn-sql-import-favorites', () => $('#sql-import-favorites-input').click());
        $(document).on('change.sqlmod', '#sql-import-favorites-input', (e) => {
            var file = e.target.files[0];
            if (file) this.importFavorites(file);
            $(e.target).val('');
        });
        $(document).on('click.sqlmod', '.sql-bookmark-item', (e) => {
            if ($(e.target).hasClass('bookmark-delete-btn')) return;
            const sql = $(e.currentTarget).data('sql');
            if (sql) {
                if (this._cmEditor) {
                    this._cmEditor.setValue(sql);
                } else {
                    $('#sql-editor').val(sql);
                }
            }
        });
        $(document).on('click.sqlmod', '.bookmark-delete-btn', (e) => {
            e.stopPropagation();
            var id = $(e.currentTarget).data('id');
            if (id) this.deleteBookmark(id);
        });

        // Tabs switching
        $(document).on('click.sqlmod', '.sql-tab', (e) => {
            var tab = $(e.currentTarget).data('tab');
            $('.sql-tab').removeClass('active');
            $(e.currentTarget).addClass('active');
            $('.sql-tab-pane').removeClass('active');
            $('#tab-' + tab).addClass('active');
        });

        // SQL Example chip click - insert into editor
        $(document).on('click.sqlmod', '.sql-example-chip', (e) => {
            const sql = $(e.currentTarget).data('sql');
            if (sql) {
                if (this._cmEditor) {
                    this._cmEditor.setValue(sql);
                    this._cmEditor.focus();
                } else {
                    $('#sql-editor').val(sql).focus();
                }
            }
        });

        $(document).on('keydown.sqlmod', '#sql-editor', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                this.execute();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
                e.preventDefault();
                this.clearEditor();
            }
        });
    },

    execute: function() {
        const sql = this._cmEditor ? this._cmEditor.getValue().trim() : $('#sql-editor').val().trim();
        if (!sql) {
            Admin.showAlert('Escribe una consulta SQL', 'warning');
            return;
        }

        const limpia = sql.replace(/\/\*[\s\S]*?\*\//g, '').replace(/--.*$/gm, '').trim().substring(0, 10).toUpperCase();
        if (!limpia.startsWith('SELECT') && !limpia.startsWith('WITH')) {
            Admin.showAlert('Solo se permiten consultas SELECT de lectura', 'warning');
            return;
        }

        const $container = $('#sql-results-container');
        const startTime = Date.now();

        Admin.showLoading($container);
        $('#sql-results-info').hide();

        $.ajax({
            url: Admin.base_url + 'modules/inspector/ajax/ejecutar_sql.php',
            method: 'POST',
            data: { sql: sql },
            success: (response) => {
                const elapsed = Date.now() - startTime;
                const rows = response.data && response.data.data ? response.data.data : [];

                if (response.success && rows.length > 0) {
                    this.renderResults(rows, elapsed, rows.length);
                } else if (response.success) {
                    $container.html('<div class="empty-state"><div class="empty-state-icon">📭</div><p class="empty-state-description">La consulta no devolvió resultados</p></div>');
                    $('#sql-results-count').text('0');
                    $('#sql-results-info').show();
                    $('#sql-execution-time').text(elapsed + 'ms · 0 registros');
                } else {
                    $container.html(`<div class="sql-error-box"><div class="sql-error-title">❌ Error</div><pre class="sql-error-message">${response.msg || 'Error desconocido'}</pre></div>`);
                    $('#sql-results-count').text('0');
                    $('#sql-results-info').hide();
                }
            },
            error: function(xhr, status, error) {
                $container.html(`<div class="sql-error-box"><div class="sql-error-title">❌ Error de conexión</div><pre class="sql-error-message">${error || status}</pre></div>`);
                $('#sql-results-count').text('0');
            }
        });
    },

    renderResults: function(data, elapsed, count) {
        const $container = $('#sql-results-container');

        if (this._resultsDt) {
            this._resultsDt.destroy();
            this._resultsDt = null;
        }

        this._lastData = data;
        const html = Admin.createTable(data);
        $container.html(html);

        $('#sql-results-count').text(count || data.length);
        $('#sql-results-info').show();
        var totalReg = Admin.formatNumber(count || data.length);
        $('#sql-execution-time').text(elapsed + 'ms · ' + totalReg + ' registros');
        $('#sql-export-buttons').show();

        if (data.length > 10) {
            this._resultsDt = $container.find('.data-table').DataTable({
                language: {
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_",
                    info: "_START_ a _END_ de _TOTAL_",
                    infoEmpty: "Sin resultados",
                    infoFiltered: "(filtrado de _MAX_)",
                    zeroRecords: "No se encontraron resultados",
                    paginate: { first: "Primero", last: "Último", next: "→", previous: "←" }
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                order: [[0, 'asc']],
                dom: '<"top"fl>rt<"bottom"ip>'
            });
        }
    },

    clearEditor: function() {
        if (this._cmEditor) {
            this._cmEditor.setValue('');
            this._cmEditor.focus();
        } else {
            $('#sql-editor').val('').focus();
        }
    },

    // ============================================
    // BOOKMARKS (server-side favorites)
    // ============================================

    favorites: [],

    loadBookmarks: function() {
        var self = this;
        Admin.get('modules/inspector/ajax/favorites.php', {})
        .then(function(response) {
            self.favorites = response.data || [];
            self.renderBookmarks();
        })
        .catch(function(error) {
            Admin.logError('SqlModule.loadBookmarks', error);
            self.favorites = [];
            self.renderBookmarks();
        });
    },

    saveBookmark: function() {
        var self = this;
        var sql = this._cmEditor ? this._cmEditor.getValue().trim() : $('#sql-editor').val().trim();
        if (!sql) {
            Admin.showAlert('No hay consulta para guardar', 'warning');
            return;
        }

        var name = prompt('Nombre para esta consulta:', 'Mi consulta');
        if (!name) return;

        Admin.post('modules/inspector/ajax/favorites.php', {
            action: 'add',
            name: name,
            sql: sql,
            table: ''
        })
        .then(function() {
            self.loadBookmarks();
            Admin.toastSuccess('Consulta guardada como favorita');
        })
        .catch(function(error) {
            Admin.logError('SqlModule.saveBookmark', error);
        });
    },

    deleteBookmark: function(id) {
        var self = this;
        Admin.post('modules/inspector/ajax/favorites.php', {
            action: 'delete',
            id: id
        })
        .then(function() {
            self.loadBookmarks();
            Admin.toastSuccess('Favorito eliminado');
        })
        .catch(function(error) {
            Admin.logError('SqlModule.deleteBookmark', error);
        });
    },

    exportFavorites: function() {
        window.location.href = 'modules/inspector/ajax/favorites.php?action=export';
    },

    importFavorites: function(file) {
        var self = this;
        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var imported = JSON.parse(e.target.result);
                var favs = imported.favorites || (Array.isArray(imported) ? imported : []);
                Admin.post('modules/inspector/ajax/favorites.php', {
                    action: 'import',
                    favorites: JSON.stringify(favs)
                })
                .then(function(response) {
                    var d = response.data || {};
                    self.loadBookmarks();
                    Admin.toastSuccess('Importados: ' + (d.added || 0) + ' nuevos, ' + (d.updated || 0) + ' actualizados');
                })
                .catch(function(error) {
                    Admin.logError('SqlModule.importFavorites', error);
                });
            } catch (err) {
                Admin.showAlert('Archivo JSON no válido', 'danger');
            }
        };
        reader.readAsText(file);
    },

    renderBookmarks: function() {
        var $list = $('#sql-bookmarks');
        if (!this.favorites || this.favorites.length === 0) {
            $list.html('<div class="empty-state" style="padding: 1rem;"><p class="empty-state-description">Sin consultas guardadas</p></div>');
            return;
        }
        var html = '';
        this.favorites.forEach(function(fav) {
            html += '<div class="sql-history-item sql-bookmark-item" data-sql="' + fav.sql.replace(/"/g, '&quot;') + '">' +
                '<span class="sql-history-icon">⭐</span>' +
                '<span class="sql-history-sql" title="' + fav.sql.replace(/"/g, '&quot;') + '">' + fav.name + '</span>' +
                '<button class="bookmark-delete-btn" data-id="' + fav.id + '" title="Eliminar favorito">&times;</button>' +
            '</div>';
        });
        $list.html(html);
    },

    exportCSV: function() {
        if (!this._lastData || this._lastData.length === 0) {
            Admin.showAlert('No hay datos para exportar', 'warning');
            return;
        }
        var headers = Object.keys(this._lastData[0]);
        var csvRows = [];
        csvRows.push(headers.join(','));
        this._lastData.forEach(function(row) {
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
        a.download = 'consulta_sql.csv';
        a.click();
        URL.revokeObjectURL(url);
        Admin.toastSuccess('CSV exportado');
    },

    exportJSON: function() {
        if (!this._lastData || this._lastData.length === 0) {
            Admin.showAlert('No hay datos para exportar', 'warning');
            return;
        }
        var blob = new Blob([JSON.stringify(this._lastData, null, 2)], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'consulta_sql.json';
        a.click();
        URL.revokeObjectURL(url);
        Admin.toastSuccess('JSON exportado');
    }
};
