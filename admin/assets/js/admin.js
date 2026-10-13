/**
 * ============================================
 * ADMIN.JS - Funciones Globales
 * ============================================
 * Funciones utilitarias y gestión de módulos
 * Versión: 2.0 - Con Toast Notifications y Keyboard Shortcuts
 */

const Admin = {
    base_url: '',
    currentModule: null,
    toastContainer: null,
    shortcuts: {},

    /**
     * Inicializar admin
     */
    init: function() {
        this.base_url = this.detectBaseUrl();
        this.initToastContainer();
        this.initKeyboardShortcuts();
        this.initAccessibility();
    },

    /**
     * Detectar URL base automáticamente
     */
    detectBaseUrl: function() {
        const path = window.location.pathname;
        const adminIndex = path.indexOf('/admin/');
        if (adminIndex !== -1) {
            return path.substring(0, adminIndex + 7);
        }
        return '/admin/';
    },

    /**
     * Inicializar contenedor de toast
     */
    initToastContainer: function() {
        if (!document.querySelector('.toast-container')) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.className = 'toast-container';
            this.toastContainer.setAttribute('aria-live', 'polite');
            this.toastContainer.setAttribute('aria-atomic', 'true');
            document.body.appendChild(this.toastContainer);
        } else {
            this.toastContainer = document.querySelector('.toast-container');
        }
    },

    /**
     * Inicializar accesibilidad
     */
    initAccessibility: function() {
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.className = 'skip-link';
        skipLink.textContent = 'Saltar al contenido principal';
        document.body.insertBefore(skipLink, document.body.firstChild);

        $(document).on('click', '#nav-toggle', function() {
            const $tabs = $('.nav-tabs');
            const expanded = $(this).attr('aria-expanded') === 'true';
            $tabs.toggleClass('open');
            $(this).attr('aria-expanded', !expanded);
        });
        $(document).on('click', '.nav-tab a', function() {
            $('.nav-tabs').removeClass('open');
            $('#nav-toggle').attr('aria-expanded', 'false');
        });
    },

    /**
     * Inicializar keyboard shortcuts
     */
    initKeyboardShortcuts: function() {
        document.addEventListener('keydown', (e) => {
            // Ignorar si está en input/textarea
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                return;
            }

            const key = this.getKeyString(e);
            
            if (this.shortcuts[key]) {
                e.preventDefault();
                this.shortcuts[key]();
            }
        });
    },

    /**
     * Obtener string de tecla
     */
    getKeyString: function(e) {
        const parts = [];
        if (e.ctrlKey || e.metaKey) parts.push('ctrl');
        if (e.altKey) parts.push('alt');
        if (e.shiftKey) parts.push('shift');
        parts.push(e.key.toLowerCase());
        return parts.join('+');
    },

    /**
     * Registrar shortcut
     */
    registerShortcut: function(key, callback, description) {
        this.shortcuts[key] = callback;
        // Agregar tooltip a botones con data-shortcut
        $(`[data-shortcut="${key}"]`).attr('data-tooltip', `${description} (${key.toUpperCase()})`);
    },

    /**
     * Realizar petición AJAX
     */
    ajax: function(endpoint, data = {}, method = 'POST') {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.base_url + endpoint,
                method: method,
                dataType: 'json',
                data: data,
                success: function(response) {
                    if (response.success) {
                        resolve(response);
                    } else {
                        reject(Admin.buildError(response.msg || 'Error desconocido', response.error));
                    }
                },
                error: function(xhr, status, error) {
                    var body = xhr && xhr.responseJSON ? xhr.responseJSON : null;

                    if (body && body.success === false) {
                        reject(Admin.buildError(body.msg || error || 'Error desconocido', body.error));
                        return;
                    }

                    var details;
                    if (!xhr || xhr.status === 0) {
                        details = {
                            type: 'network',
                            label: 'Sin respuesta del servidor',
                            hint: 'No se recibió respuesta del servidor. Comprueba tu conexión de red y que el servicio web esté activo.',
                            detail: error || status || 'Sin conexión con el servidor'
                        };
                    } else {
                        details = {
                            type: 'http',
                            label: 'Error del servidor web',
                            hint: 'El servidor devolvió una respuesta no válida. Revisa el servidor Apache/IIS y los registros de PHP.',
                            detail: 'HTTP ' + xhr.status + (error ? ' ' + error : '')
                        };
                    }

                    reject(Admin.buildError(details.label + ': ' + details.detail, details));
                }
            });
        });
    },

    /**
     * Crear un Error con el detalle tipado del backend adjunto
     */
    buildError: function(message, details) {
        var err = new Error(message || 'Error desconocido');
        err.details = details || null;
        return err;
    },

    /**
     * Normalizar cualquier error (Error, string o detalle del servidor)
     * a {type, label, hint, detail, code, gds, sqlstate, line, column, message}
     */
    describeError: function(err) {
        var source = null;

        if (err && typeof err === 'object' && ('type' in err || 'label' in err || 'detail' in err)) {
            source = err;
        } else if (err && err.details) {
            source = err.details;
        } else if (typeof err === 'string') {
            source = { type: 'unknown', detail: err };
        } else if (err && err.message) {
            source = { type: 'unknown', detail: err.message };
        }

        source = source || {};

        var message = '';
        if (err && typeof err === 'object' && err.message) {
            message = err.message;
        } else if (source.message) {
            message = source.message;
        } else {
            message = source.detail || 'Error desconocido';
        }

        return {
            type: source.type || 'unknown',
            label: source.label || 'Error de base de datos',
            hint: source.hint || '',
            detail: source.detail || '',
            code: (source.code === 0 || source.code) ? source.code : null,
            gds: (source.gds === 0 || source.gds) ? source.gds : null,
            sqlstate: source.sqlstate || null,
            line: (source.line === 0 || source.line) ? source.line : null,
            column: (source.column === 0 || source.column) ? source.column : null,
            message: message
        };
    },

    /**
     * HTML de caja de error tipado (título + detalle + consejo + metadatos)
     */
    errorBoxHtml: function(err) {
        var d = this.describeError(err);
        var esc = this.escapeHtml;

        var html = '<div class="sql-error-box" role="alert">';
        html += '<div class="sql-error-title"><span class="sql-error-badge" data-type="' + esc(d.type) + '">' + esc(d.label) + '</span></div>';

        var body = d.detail || d.message || d.label;
        html += '<pre class="sql-error-message">' + esc(body) + '</pre>';

        if (d.hint) {
            html += '<div class="sql-error-hint">' + esc(d.hint) + '</div>';
        }

        var meta = [];
        if (d.code !== null && d.code !== undefined) meta.push(['SQLCODE', d.code]);
        if (d.gds !== null && d.gds !== undefined) meta.push(['GDSCODE', d.gds]);
        if (d.sqlstate) meta.push(['SQLSTATE', d.sqlstate]);
        if (d.line !== null) {
            meta.push(['Ubicación', 'línea ' + d.line + (d.column !== null ? ', columna ' + d.column : '')]);
        }

        if (meta.length) {
            html += '<div class="sql-error-meta">';
            meta.forEach(function(item) {
                html += '<span class="sql-error-meta-item"><b>' + esc(item[0]) + '</b> ' + esc(String(item[1])) + '</span>';
            });
            html += '</div>';
        }

        html += '</div>';
        return html;
    },

    /**
     * Divide un script SQL en sentencias individuales.
     * Respeta cadenas '...' / "..." y comentarios -- / /* *\/
     */
    splitSqlStatements: function(sql) {
        var stmts = [];
        var current = '';
        var len = (sql || '').length;
        var i = 0;

        function isCommentOnly(s) {
            var noBlock = s.replace(/\/\*[\s\S]*?\*\//g, '');
            var noLine = noBlock.replace(/--.*$/gm, '');
            return noLine.trim() === '';
        }

        while (i < len) {
            var ch = sql[i];

            // Comentario de línea
            if (ch === '-' && i + 1 < len && sql[i + 1] === '-') {
                var j = sql.indexOf('\n', i);
                if (j === -1) {
                    current += sql.substring(i);
                    i = len;
                } else {
                    current += sql.substring(i, j + 1);
                    i = j + 1;
                }
                continue;
            }

            // Comentario de bloque
            if (ch === '/' && i + 1 < len && sql[i + 1] === '*') {
                var j2 = sql.indexOf('*/', i + 2);
                if (j2 === -1) {
                    current += sql.substring(i);
                    i = len;
                } else {
                    current += sql.substring(i, j2 + 2);
                    i = j2 + 2;
                }
                continue;
            }

            // Cadena '...'
            if (ch === "'") {
                current += ch;
                i++;
                while (i < len) {
                    current += sql[i];
                    if (sql[i] === "'") {
                        if (i + 1 < len && sql[i + 1] === "'") {
                            current += sql[i + 1];
                            i += 2;
                            continue;
                        }
                        i++;
                        break;
                    }
                    i++;
                }
                continue;
            }

            // Identificador "..."
            if (ch === '"') {
                current += ch;
                i++;
                while (i < len) {
                    current += sql[i];
                    if (sql[i] === '"') {
                        if (i + 1 < len && sql[i + 1] === '"') {
                            current += sql[i + 1];
                            i += 2;
                            continue;
                        }
                        i++;
                        break;
                    }
                    i++;
                }
                continue;
            }

            // Fin de sentencia
            if (ch === ';') {
                var trimmed = current.trim();
                if (trimmed && !isCommentOnly(trimmed)) {
                    stmts.push(trimmed);
                }
                current = '';
                i++;
                continue;
            }

            current += ch;
            i++;
        }

        var last = current.trim();
        if (last && !isCommentOnly(last)) {
            stmts.push(last);
        }

        return stmts;
    },

    /**
     * Validación client-side de consulta de solo lectura.
     * Whitelist: SELECT, WITH, SET TRANSACTION READ ONLY, ROLLBACK.
     * Devuelve null si es válida, o un detalle tipado si no lo es.
     */
    validateReadOnlySql: function(sql) {
        var stmts = this.splitSqlStatements(sql || '');

        if (!stmts.length) {
            return {
                type: 'validation',
                label: 'Consulta vacía',
                detail: 'No se envió ninguna consulta SQL.',
                hint: 'Escribe una consulta SELECT (o WITH) en el editor.'
            };
        }

        for (var s = 0; s < stmts.length; s++) {
            var compact = stmts[s].replace(/\s+/g, ' ').trim();
            var upper = compact.toUpperCase();

            var ok =
                /^SELECT\b/.test(upper) ||
                /^WITH\b/.test(upper) ||
                (/^SET\s+TRANSACTION\b/.test(upper) && /READ\s+ONLY\b/.test(upper) && !/READ\s+WRITE\b/.test(upper)) ||
                /^ROLLBACK(\s+WORK)?\b/.test(upper);

            if (!ok) {
                var match = compact.match(/^([A-Za-z_]+)/);
                var keyword = match ? match[1].toUpperCase() : '';
                var num = s + 1;
                var total = stmts.length;
                return {
                    type: 'validation',
                    label: 'Consulta no permitida',
                    detail: keyword
                        ? 'Sentencia #' + num + '/' + total + ' comienza por «' + keyword + '». Solo se permiten: SELECT, WITH, SET TRANSACTION READ ONLY y ROLLBACK.'
                        : 'Sentencia #' + num + '/' + total + ' no permitida. Solo se permiten: SELECT, WITH, SET TRANSACTION READ ONLY y ROLLBACK.',
                    hint: 'Esta consola es de solo lectura: retira la sentencia que modifica datos (INSERT, UPDATE, DELETE, DDL, etc.) y vuelve a intentarlo.'
                };
            }
        }

        return null;
    },

    /**
     * Petición GET
     */
    get: function(endpoint, data = {}) {
        return this.ajax(endpoint, data, 'GET');
    },

    /**
     * Petición POST
     */
    post: function(endpoint, data = {}) {
        return this.ajax(endpoint, data, 'POST');
    },

    /**
     * Cargar módulo dinámicamente
     */
    loadModule: function(module) {
        this.currentModule = module;
        const content = $('#main-content');
        
        this.showLoading(content);
        
        // Cargar vista del módulo
        $.ajax({
            url: this.base_url + 'modules/' + module + '/index.php',
            method: 'GET',
            success: function(html) {
                content.html(html);
                $(document).trigger('moduleLoaded', module);
                if (typeof Dashboard !== 'undefined' && module === 'dashboard') {
                    Dashboard.init();
                }
                if (typeof Inspector !== 'undefined' && module === 'inspector') {
                    Inspector.init();
                }
                if (typeof SqlModule !== 'undefined' && module === 'sql') {
                    SqlModule.init();
                }
                if (typeof Builder !== 'undefined' && module === 'builder') {
                    Builder.init();
                }
                if (typeof ScriptsModule !== 'undefined' && module === 'scripts') {
                    ScriptsModule.init();
                }
                if (typeof Api !== 'undefined' && module === 'api') {
                    Api.init();
                }
            },
            error: function(xhr, status, error) {
                Admin.showError(content, 'Error al cargar el módulo');
            }
        });
    },

    /**
     * Crear tabla HTML desde datos JSON
     */
    createTable: function(data, options = {}) {
        if (!data || data.length === 0) {
            return this.renderEmptyState(
                options.emptyIcon || '📭',
                options.emptyTitle || 'No hay datos',
                options.emptyDescription || 'No se encontraron registros'
            );
        }

        const columns = Object.keys(data[0]);
        let html = '<div class="data-table-wrapper"><table class="data-table">';

        // Header
        html += '<thead><tr>';
        columns.forEach(col => {
            const label = options.labels?.[col] || col;
            html += '<th>' + label + '</th>';
        });
        html += '</tr></thead>';

        // Body
        html += '<tbody>';
        data.forEach(row => {
            html += '<tr>';
            columns.forEach(col => {
                const value = row[col] ?? '';
                html += '<td>' + value + '</td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table></div>';

        return html;
    },

    /**
     * Renderizar empty state
     */
    renderEmptyState: function(icon, title, description, actionHtml) {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">${icon}</div>
                <h3 class="empty-state-title">${title}</h3>
                <p class="empty-state-description">${description}</p>
                ${actionHtml ? `<div class="empty-state-action">${actionHtml}</div>` : ''}
            </div>
        `;
    },

    /**
     * Renderizar skeleton loading
     */
    renderSkeleton: function(rows = 5, cols = 4) {
        let html = '<div class="skeleton-table">';
        for (let i = 0; i < rows; i++) {
            html += '<div class="skeleton-table-row">';
            for (let j = 0; j < cols; j++) {
                html += '<div class="skeleton skeleton-text"></div>';
            }
            html += '</div>';
        }
        html += '</div>';
        return html;
    },

    /**
     * Inicializar DataTable
     */
    initDataTable: function(selector, options = {}) {
        const defaults = {
            language: {
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                zeroRecords: "No se encontraron resultados",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                }
            },
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            order: [[0, 'asc']],
            responsive: true
        };

        const settings = $.extend(true, {}, defaults, options);
        return $(selector).DataTable(settings);
    },

    /**
     * Mostrar loading
     */
    showLoading: function(container) {
        container.html(
            '<div class="loading">' +
            '<div class="spinner"></div>' +
            '<p>Cargando...</p>' +
            '</div>'
        );
    },

    /**
     * Mostrar skeleton loading
     */
    showSkeleton: function(container, rows, cols) {
        container.html(this.renderSkeleton(rows, cols));
    },

    /**
     * Mostrar error
     */
    showError: function(container, message) {
        container.html(
            '<div class="alert alert-danger">' +
            '<span class="alert-icon">❌</span>' +
            '<div class="alert-content">' +
            '<strong>Error:</strong> ' + message +
            '</div>' +
            '</div>'
        );
    },

    /**
     * Mostrar éxito
     */
    showSuccess: function(container, message) {
        container.html(
            '<div class="alert alert-success">' +
            '<span class="alert-icon">✅</span>' +
            '<div class="alert-content">' +
            '<strong>Éxito:</strong> ' + message +
            '</div>' +
            '</div>'
        );
    },

    /**
     * Mostrar alerta inline
     */
    showAlert: function(message, type = 'info', title) {
        const icons = {
            success: '✅',
            danger: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        const alertHtml = `
            <div class="alert alert-${type}">
                <span class="alert-icon">${icons[type] || icons.info}</span>
                <div class="alert-content">
                    ${title ? `<div class="alert-title">${title}</div>` : ''}
                    ${message}
                </div>
                <button class="alert-close" onclick="this.parentElement.remove()" aria-label="Cerrar">&times;</button>
            </div>
        `;
        
        // Insertar al inicio del contenido
        const mainContent = document.getElementById('main-content');
        if (mainContent) {
            mainContent.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto-ocultar después de 5 segundos (the last inserted alert)
            const inserted = mainContent.querySelector('.alert');
            if (inserted) {
                setTimeout(() => {
                    if (inserted.parentNode) inserted.remove();
                }, 5000);
            }
        }
    },

    /**
     * Toast notification
     */
    toast: function(message, type = 'info', duration = 4000) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" aria-label="Cerrar">&times;</button>
        `;

        // Evento para cerrar
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.removeToast(toast);
        });

        // Cerrar al hacer click
        toast.addEventListener('click', () => {
            this.removeToast(toast);
        });

        this.toastContainer.appendChild(toast);

        // Auto-remover
        if (duration > 0) {
            setTimeout(() => {
                this.removeToast(toast);
            }, duration);
        }

        return toast;
    },

    /**
     * Remover toast
     */
    removeToast: function(toast) {
        if (toast && toast.parentElement) {
            toast.classList.add('removing');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
    },

    /**
     * Atajos de teclado para toast
     */
    toastSuccess: function(message, duration) {
        return this.toast(message, 'success', duration);
    },

    toastError: function(message, duration) {
        return this.toast(message, 'error', duration);
    },

    toastWarning: function(message, duration) {
        return this.toast(message, 'warning', duration);
    },

    toastInfo: function(message, duration) {
        return this.toast(message, 'info', duration);
    },

    /**
     * Confirmar acción con modal personalizado
     */
    confirm: function(message, title = 'Confirmar') {
        return new Promise((resolve) => {
            const modalHtml = `
                <div class="modal" id="confirm-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>${title}</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" id="confirm-cancel">Cancelar</button>
                            <button class="btn btn-primary" id="confirm-ok">Aceptar</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            const modal = document.getElementById('confirm-modal');
            const closeBtn = modal.querySelector('.modal-close');
            const cancelBtn = document.getElementById('confirm-cancel');
            const okBtn = document.getElementById('confirm-ok');

            const closeModal = (result) => {
                modal.remove();
                resolve(result);
            };

            closeBtn.addEventListener('click', () => closeModal(false));
            cancelBtn.addEventListener('click', () => closeModal(false));
            okBtn.addEventListener('click', () => closeModal(true));
            
            // Cerrar con Escape
            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Escape') {
                    closeModal(false);
                    document.removeEventListener('keydown', handler);
                }
            });
        });
    },

    /**
     * Formatear número
     */
    formatNumber: function(num, decimals = 0) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(num);
    },

    /**
     * Formatear fecha
     */
    formatDate: function(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES');
    },

    /**
     * Formatear fecha y hora
     */
    formatDateTime: function(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
    },

    /**
     * Copiar al portapapeles
     */
    copyToClipboard: async function(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.toastSuccess('Copiado al portapapeles');
        } catch (e) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            this.toastSuccess('Copiado al portapapeles');
        }
    },

    /**
     * Log de errores (consola + opcionalmente envío a servidor)
     */
    logError: function(context, error, extra) {
        var entry = {
            time: new Date().toISOString(),
            context: context,
            error: error && error.message ? error.message : String(error),
            stack: error && error.stack ? error.stack : null,
            extra: extra || null
        };
        console.error('[FuelOps]', entry.context, entry.error, entry.extra || '');
        try {
            var logs = JSON.parse(localStorage.getItem('fuelops_error_logs') || '[]');
            logs.push(entry);
            if (logs.length > 50) logs = logs.slice(-50);
            localStorage.setItem('fuelops_error_logs', JSON.stringify(logs));
        } catch (e) {}
    },

    /**
     * Debounce - Limitar frecuencia de ejecución
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Obtener parámetros de URL
     */
    getUrlParams: function() {
        return new URLSearchParams(window.location.search);
    },

    /**
     * Actualizar parámetro de URL
     */
    setUrlParam: function(key, value) {
        const url = new URL(window.location);
        url.searchParams.set(key, value);
        window.history.pushState({}, '', url);
    },

    /**
     * Remover parámetro de URL
     */
    removeUrlParam: function(key) {
        const url = new URL(window.location);
        url.searchParams.delete(key);
        window.history.pushState({}, '', url);
    },

    /**
     * Agregar entrada al historial de consultas (localStorage)
     * Usado por Dashboard para mostrar historial reciente
     */
    addQueryHistory: function(sql, success, executionTime) {
        try {
            var history = JSON.parse(localStorage.getItem('dashboard_query_history') || '[]');
            history.unshift({
                sql: sql.substring(0, 500),
                timestamp: new Date().toISOString(),
                success: success,
                execution_time: executionTime || 0
            });
            if (history.length > 15) history = history.slice(0, 15);
            localStorage.setItem('dashboard_query_history', JSON.stringify(history));
        } catch (e) {}
    },

    /**
     * Escapar HTML para prevenir XSS
     */
    escapeHtml: function(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
};

// Inicializar cuando el DOM esté listo
$(document).ready(function() {
    Admin.init();
});
