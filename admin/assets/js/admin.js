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
                        reject(new Error(response.msg || 'Error desconocido'));
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error(error || 'Error de conexión'));
                }
            });
        });
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
                if (typeof Inspector !== 'undefined' && module === 'inspector') {
                    Inspector.init();
                }
                if (typeof SqlModule !== 'undefined' && module === 'sql') {
                    SqlModule.init();
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
    }
};

// Inicializar cuando el DOM esté listo
$(document).ready(function() {
    Admin.init();
});
