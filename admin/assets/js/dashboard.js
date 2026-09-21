/**
 * ============================================
 * DASHBOARD.JS - Lógica del Dashboard
 * ============================================
 * Resumen de la base de datos: stats, top tablas, historial
 */

const Dashboard = {
    _eventsBound: false,

    init: function() {
        if (!this._eventsBound) {
            this.bindEvents();
            this._eventsBound = true;
        }
        this.loadStats();
        this.loadHistory();
    },

    bindEvents: function() {
        var self = this;
        $(document).off('.dashboard');

        $(document).on('click.dashboard', '#btn-clear-history', function() {
            self.clearHistory();
        });
    },

    loadStats: function() {
        var $container = $('#top-tables-container');
        Admin.showLoading($container);

        Admin.post('modules/dashboard/ajax/stats.php', {})
        .then(function(response) {
            var d = response.data || {};

            // Update stat cards
            $('#stat-driver').text((d.driver || 'firebird').toUpperCase());
            $('#stat-tables').text(Admin.formatNumber(d.tables_count || 0));
            $('#stat-records').text(Admin.formatNumber(d.total_records || 0));
            $('#stat-latency').text((d.latency_ms || 0) + 'ms');

            // Render top tables
            Dashboard.renderTopTables(d.top_tables || []);
        })
        .catch(function(error) {
            Admin.logError('Dashboard.loadStats', error);
            $container.html('<div class="empty-state"><p class="empty-state-description">Error al cargar estadísticas</p></div>');
        });
    },

    renderTopTables: function(tables) {
        var $container = $('#top-tables-container');

        if (tables.length === 0) {
            $container.html('<div class="empty-state"><p class="empty-state-description">No hay tablas disponibles</p></div>');
            return;
        }

        var maxRecords = tables[0].records || 1;
        var html = '<div class="top-tables-list">';

        tables.forEach(function(table, index) {
            var percentage = Math.round((table.records / maxRecords) * 100);
            html += '<div class="top-table-item">' +
                '<div class="top-table-info">' +
                    '<span class="top-table-rank">#' + (index + 1) + '</span>' +
                    '<a href="?module=inspector&table=' + table.name + '" class="top-table-name">' + table.name + '</a>' +
                    '<span class="top-table-records">' + Admin.formatNumber(table.records) + ' registros</span>' +
                '</div>' +
                '<div class="top-table-bar">' +
                    '<div class="top-table-bar-fill" style="width: ' + percentage + '%"></div>' +
                '</div>' +
            '</div>';
        });

        html += '</div>';
        $container.html(html);
    },

    loadHistory: function() {
        var history = JSON.parse(localStorage.getItem('dashboard_query_history') || '[]');
        this.renderHistory(history);
    },

    renderHistory: function(history) {
        var $container = $('#history-container');

        if (!history || history.length === 0) {
            $container.html(
                '<div class="empty-state">' +
                    '<div class="empty-state-icon">📜</div>' +
                    '<p class="empty-state-description">Sin consultas en el historial</p>' +
                '</div>'
            );
            return;
        }

        var html = '<div class="history-list">';

        history.forEach(function(entry) {
            var icon = entry.success ? '✅' : '❌';
            var time = Dashboard.formatRelativeTime(entry.timestamp);
            var execTime = entry.execution_time ? entry.execution_time + 'ms' : '';
            var sqlPreview = entry.sql.length > 80 ? entry.sql.substring(0, 80) + '...' : entry.sql;

            html += '<div class="history-item" data-sql="' + entry.sql.replace(/"/g, '&quot;') + '">' +
                '<div class="history-item-header">' +
                    '<span class="history-item-icon">' + icon + '</span>' +
                    '<span class="history-item-time">' + time + '</span>' +
                    '<span class="history-item-exec-time">' + execTime + '</span>' +
                '</div>' +
                '<div class="history-item-sql">' + Dashboard.escapeHtml(sqlPreview) + '</div>' +
            '</div>';
        });

        html += '</div>';
        $container.html(html);

        // Click to load SQL in console
        $(document).off('click.dashboard', '.history-item');
        $(document).on('click.dashboard', '.history-item', function() {
            var sql = $(this).data('sql');
            if (sql) {
                window.location.href = '?module=sql';
                // Store SQL to load after redirect
                sessionStorage.setItem('dashboard_pending_sql', sql);
            }
        });
    },

    clearHistory: function() {
        localStorage.removeItem('dashboard_query_history');
        this.renderHistory([]);
        Admin.toastSuccess('Historial limpiado');
    },

    formatRelativeTime: function(timestamp) {
        var now = new Date();
        var then = new Date(timestamp);
        var diffMs = now - then;
        var diffMins = Math.floor(diffMs / 60000);
        var diffHours = Math.floor(diffMs / 3600000);
        var diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Ahora';
        if (diffMins < 60) return 'Hace ' + diffMins + ' min';
        if (diffHours < 24) return 'Hace ' + diffHours + 'h';
        if (diffDays < 7) return 'Hace ' + diffDays + 'd';
        return then.toLocaleDateString('es-ES');
    },

    escapeHtml: function(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
};
