const SqlModule = {
    history: [],
    _resultsDt: null,

    init: function() {
        this.bindEvents();
        this.loadHistory();
    },

    bindEvents: function() {
        $(document).off('.sqlmod');

        $(document).on('click.sqlmod', '#btn-sql-execute', () => this.execute());
        $(document).on('click.sqlmod', '#btn-sql-clear', () => this.clearEditor());
        $(document).on('click.sqlmod', '.sql-history-item', (e) => {
            const sql = $(e.currentTarget).data('sql');
            if (sql) { $('#sql-editor').val(sql); }
        });

        // SQL Examples toggle
        $(document).on('click.sqlmod', '#btn-toggle-examples', () => {
            const $panel = $('#sql-examples-panel');
            const $arrow = $('#btn-toggle-examples .sql-examples-arrow');
            const isOpen = $panel.is(':visible');
            $arrow.text(isOpen ? '▶' : '▼');
            $panel.slideToggle(200);
        });

        // SQL Example click - insert into editor
        $(document).on('click.sqlmod', '.sql-example-item', (e) => {
            const sql = $(e.currentTarget).data('sql');
            if (sql) {
                $('#sql-editor').val(sql).focus();
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
        const sql = $('#sql-editor').val().trim();
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
                this.addToHistory(sql, response.success);

                if (response.success && response.data && response.data.length > 0) {
                    this.renderResults(response.data, elapsed, response.count);
                } else if (response.success) {
                    $container.html('<div class="empty-state"><div class="empty-state-icon">📭</div><p class="empty-state-description">La consulta no devolvió resultados</p></div>');
                    $('#sql-results-count').text('0');
                    $('#sql-results-info').show();
                    $('#sql-execution-time').text(`${elapsed}ms · 0 registros`);
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

        const html = Admin.createTable(data);
        $container.html(html);

        $('#sql-results-count').text(count || data.length);
        $('#sql-results-info').show();
        $('#sql-execution-time').text(`${elapsed}ms · ${(count || data.length).toLocaleString('es-ES')} registros`);

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

    addToHistory: function(sql, success) {
        this.history.unshift({
            sql: sql,
            success: success,
            time: new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
        });
        if (this.history.length > 20) this.history.pop();
        this.renderHistory();
        try { localStorage.setItem('sql_history', JSON.stringify(this.history)); } catch(e) {}
    },

    loadHistory: function() {
        try {
            const saved = localStorage.getItem('sql_history');
            if (saved) {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed)) {
                    this.history = parsed.filter(item => item && typeof item.sql === 'string');
                }
            }
        } catch(e) {
            this.history = [];
        }
        this.renderHistory();
    },

    renderHistory: function() {
        const $list = $('#sql-history');
        if (this.history.length === 0) {
            $list.html('<div class="empty-state" style="padding: 1rem;"><p class="empty-state-description">Sin consultas recientes</p></div>');
            return;
        }
        let html = '';
        this.history.forEach(item => {
            const icon = item.success ? '✅' : '❌';
            const truncated = item.sql.length > 60 ? item.sql.substring(0, 60) + '...' : item.sql;
            html += `<div class="sql-history-item" data-sql="${item.sql.replace(/"/g, '&quot;')}">
                <span class="sql-history-icon">${icon}</span>
                <span class="sql-history-sql">${truncated}</span>
                <span class="sql-history-time">${item.time}</span>
            </div>`;
        });
        $list.html(html);
    },

    clearEditor: function() {
        $('#sql-editor').val('').focus();
    }
};
