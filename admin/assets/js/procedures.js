/**
 * ============================================
 * PROCEDURES MODULE
 * ============================================
 * Listado de procedures Firebird, detalle (params, fuente,
 * dependencias) y ejecución (procedure existente o EXECUTE BLOCK).
 * Namespace: ProcsModule
 */
var ProcsModule = {
    _dt: null,
    _allProcs: [],
    _current: null,
    _lastData: [],
    _blockEditor: null,
    _pendingAction: null, // 'procedure' | 'block' — qué ejecutar tras verificar contraseña

    init: function() {
        this.bindEvents();
        this.loadProcedures();
    },

    bindEvents: function() {
        $(document).off('.procsmod');

        $(document).on('input.procsmod', '#procs-search', (e) => {
            this.filterList($(e.target).val());
        });

        $(document).on('click.procsmod', '#btn-refresh-procs', () => {
            this.loadProcedures();
        });

        $(document).on('click.procsmod', '#procs-datatable tbody tr[data-proc]', (e) => {
            const name = $(e.currentTarget).data('proc');
            if (name) this.selectProcedure(name);
        });

        $(document).on('click.procsmod', '.proc-tab', (e) => {
            this.switchTab($(e.currentTarget).data('tab'));
        });

        $(document).on('change.procsmod', 'input[name="proc-exec-mode"]', (e) => {
            const mode = $(e.target).val();
            $('#exec-mode-procedure').toggle(mode === 'procedure');
            $('#exec-mode-block').toggle(mode === 'block');
            $('#proc-exec-result').hide();
            $('#proc-exec-container').empty();
            this._lastData = [];
            if (mode === 'block' && !this._blockEditor) {
                this.initBlockEditor();
            }
        });

        $(document).on('click.procsmod', '#btn-run-proc', () => {
            this.switchTab('execute');
        });

        // Ejecutar: primero pedir contraseña (misma lógica que Scripts)
        $(document).on('click.procsmod', '#btn-exec-proc', () => {
            this.requestExecution('procedure');
        });

        $(document).on('click.procsmod', '#btn-exec-block', () => {
            this.requestExecution('block');
        });

        // Password modal (idéntico a scripts.js)
        $(document).on('click.procsmod', '#proc-password-cancel, #proc-password-close, #proc-password-modal .modal-overlay', () => {
            this.hidePasswordModal();
        });

        $(document).on('click.procsmod', '#proc-password-submit', () => {
            this.verifyAndRun();
        });

        $(document).on('keydown.procsmod', '#proc-password', (e) => {
            if (e.which === 13) this.verifyAndRun();
        });

        $(document).on('keydown.procsmod', (e) => {
            if (e.key === 'Escape') this.hidePasswordModal();
        });

        $(document).on('click.procsmod', '#btn-copy-source', () => {
            const src = $('#proc-source').text();
            if (src && src !== '—') {
                Admin.copyToClipboard(src);
                Admin.toastSuccess('Código copiado al portapapeles');
            }
        });

        $(document).on('click.procsmod', '#btn-proc-export-csv', () => {
            this.exportCSV();
        });

        $(document).on('click.procsmod', '#btn-proc-export-json', () => {
            this.exportJSON();
        });
    },

    /* --------------------------------------------
     * Autenticación (misma lógica que Scripts)
     * ------------------------------------------ */
    requestExecution: function(action) {
        this._pendingAction = action;
        $('#proc-password-modal').show();
        $('#proc-password').val('').focus();
        $('#proc-password-error').hide();
    },

    hidePasswordModal: function() {
        $('#proc-password-modal').hide();
        $('#proc-password').val('');
        $('#proc-password-error').hide();
        this._pendingAction = null;
    },

    verifyAndRun: function() {
        const password = $('#proc-password').val();
        if (!password) {
            $('#proc-password-error').text('Introduce la contraseña').show();
            return;
        }

        $('#proc-password-submit').prop('disabled', true).text('Verificando...');

        // Mismo endpoint y misma sesión (scripts_auth) que el módulo Scripts
        Admin.post('modules/scripts/ajax/verify_password.php', { password: password })
            .then(() => {
                const action = this._pendingAction;
                $('#proc-password-modal').hide();
                $('#proc-password').val('');
                $('#proc-password-error').hide();
                this._pendingAction = null;

                if (action === 'block') {
                    this.executeBlock();
                } else {
                    this.executeProcedure();
                }
            })
            .catch(() => {
                $('#proc-password-error').text('Contraseña incorrecta').show();
            })
            .finally(() => {
                $('#proc-password-submit').prop('disabled', false).text('Ejecutar');
            });
    },

    /* --------------------------------------------
     * Listado
     * ------------------------------------------ */
    loadProcedures: function() {
        const tbody = $('#procs-tbody');
        tbody.html('<tr><td colspan="1"><div class="loading"><div class="spinner"></div><p>Cargando procedures...</p></div></td></tr>');

        Admin.post('modules/procedures/ajax/listar_procedures.php', {})
            .then((response) => {
                this._allProcs = response.data || [];
                this.renderList(this._allProcs);
            })
            .catch((error) => {
                Admin.logError('ProcsModule.loadProcedures', error, error.details || null);
                tbody.html('<tr><td colspan="1"><div class="sql-error-box">' + Admin.escapeHtml(error.message || 'Error al cargar') + '</div></td></tr>');
            });
    },

    renderList: function(procs) {
        const tbody = $('#procs-tbody');
        if (!procs || procs.length === 0) {
            tbody.html('<tr><td colspan="1"><div class="empty-state" style="padding:1rem;"><p class="empty-state-description">No hay procedures de usuario</p></div></td></tr>');
            return;
        }

        const rows = procs.map((p) => {
            const badge = p.tipo === 2
                ? '<span class="proc-badge proc-badge-exec" title="Ejecutable (sin SUSPEND)">Ejec</span>'
                : '<span class="proc-badge proc-badge-sel" title="Seleccionable (con SUSPEND)">Sel</span>';
            const inOut = `<span class="proc-params-hint">(${p.entradas} in · ${p.salidas} out)</span>`;
            return `<tr data-proc="${Admin.escapeHtml(p.nombre)}" class="clickable-row">
                <td>
                    <div class="proc-name-cell">
                        <span class="proc-name">${Admin.escapeHtml(p.nombre)}</span>
                        ${badge}
                        ${inOut}
                    </div>
                </td>
            </tr>`;
        }).join('');

        tbody.html(rows);
    },

    filterList: function(term) {
        term = (term || '').toLowerCase();
        const filtered = this._allProcs.filter((p) => p.nombre.toLowerCase().indexOf(term) !== -1);
        this.renderList(filtered);
    },

    /* --------------------------------------------
     * Selección + detalle
     * ------------------------------------------ */
    selectProcedure: function(name) {
        this._current = name;
        $('#proc-placeholder').hide();
        $('#proc-detail').show();
        $('#proc-name').text(name);
        $('#proc-type-badge').text('...');
        $('#proc-description').hide().text('');
        $('#proc-source').text('—');
        $('#proc-params-tbody').html('<tr><td colspan="4"><div class="loading"><div class="spinner"></div><p>Cargando...</p></div></td></tr>');
        $('#proc-deps').html('<span class="proc-tag-empty">—</span>');
        $('#proc-callers').html('<span class="proc-tag-empty">—</span>');
        $('#proc-exec-result').hide();
        $('#proc-params-form').html('');
        this._lastData = [];

        // reset tab a info
        this.switchTab('info');

        // marcar fila activa
        $('#procs-datatable tbody tr').removeClass('active-row');
        $(`#procs-datatable tbody tr[data-proc="${name}"]`).addClass('active-row');

        Admin.post('modules/procedures/ajax/obtener_procedure.php', { nombre: name })
            .then((response) => {
                this.renderDetail(response.data);
            })
            .catch((error) => {
                Admin.logError('ProcsModule.selectProcedure', error, error.details || null);
                if (typeof Inspector !== 'undefined' && Inspector.showErrorBox) {
                    Inspector.showErrorBox(error, 'detalle del procedure');
                } else {
                    $('#tab-info').prepend('<div class="sql-error-box">' + Admin.escapeHtml(error.message || 'Error') + '</div>');
                }
            });
    },

    renderDetail: function(d) {
        this._current = d.nombre;

        const badge = d.tipo === 2
            ? '<span class="proc-badge proc-badge-exec">Ejecutable</span>'
            : '<span class="proc-badge proc-badge-sel">Seleccionable</span>';
        $('#proc-type-badge').replaceWith(badge.replace('<span class="proc-badge', '<span id="proc-type-badge" class="proc-badge'));

        if (d.descripcion) {
            $('#proc-description').text(d.descripcion).show();
        } else {
            $('#proc-description').hide();
        }

        $('#proc-source').text(d.fuente || '—');

        // params table
        if (d.params && d.params.length) {
            const rows = d.params.map((p) => {
                const dir = p.direccion === 1
                    ? '<span class="proc-dir proc-dir-out">OUT</span>'
                    : '<span class="proc-dir proc-dir-in">IN</span>';
                const tname = Admin.fbTypeName ? Admin.fbTypeName(p.tipo_code) : (p.tipo_code != null ? 'TYPE_' + p.tipo_code : '—');
                const tmeta = [];
                if (p.char_len) tmeta.push(p.char_len);
                if (p.escala != null && p.escala < 0) tmeta.push('scale ' + Math.abs(p.escala));
                const tdisplay = tname + (tmeta.length ? ' (' + tmeta.join(', ') + ')' : '');
                return `<tr>
                    <td>${p.posicion + 1}</td>
                    <td><code>${Admin.escapeHtml(p.nombre)}</code></td>
                    <td>${dir}</td>
                    <td><span class="proc-type-chip">${Admin.escapeHtml(tdisplay)}</span></td>
                </tr>`;
            }).join('');
            $('#proc-params-tbody').html(rows);
        } else {
            $('#proc-params-tbody').html('<tr><td colspan="4"><div class="empty-state" style="padding:0.75rem;"><p class="empty-state-description">Sin parámetros</p></div></td></tr>');
        }

        // deps
        if (d.dependencias && d.dependencias.length) {
            const tags = d.dependencias.map((dep) => {
                const label = dep.tipo === 1 ? 'vista' : 'tabla';
                return `<span class="proc-tag proc-tag-${label}"><span class="proc-tag-kind">${label}</span> ${Admin.escapeHtml(dep.objeto)}</span>`;
            }).join('');
            $('#proc-deps').html(tags);
        } else {
            $('#proc-deps').html('<span class="proc-tag-empty">Sin dependencias registradas</span>');
        }

        // callers
        if (d.callers && d.callers.length) {
            const tags = d.callers.map((c) => {
                const label = c.tipo === 2 ? 'trigger' : 'procedure';
                return `<span class="proc-tag proc-tag-${label}"><span class="proc-tag-kind">${label}</span> ${Admin.escapeHtml(c.objeto)}</span>`;
            }).join('');
            $('#proc-callers').html(tags);
        } else {
            $('#proc-callers').html('<span class="proc-tag-empty">Nadie lo referencia (según RDB$DEPENDENCIES)</span>');
        }

        // params form for execute
        const ins = (d.params || []).filter((p) => p.direccion === 0);
        if (ins.length) {
            const form = ins.map((p) => {
                const tname = Admin.fbTypeName ? Admin.fbTypeName(p.tipo_code) : '';
                return `<div class="proc-param-field">
                    <label class="form-label">${Admin.escapeHtml(p.nombre)} <span class="proc-param-type">${Admin.escapeHtml(tname)}</span></label>
                    <input type="text" class="form-input form-input-sm proc-param-input" data-pos="${p.posicion}" placeholder="Valor para ${Admin.escapeHtml(p.nombre)}...">
                </div>`;
            }).join('');
            $('#proc-params-form').html(form);
        } else {
            $('#proc-params-form').html('<p class="proc-params-none">Este procedure no tiene parámetros de entrada.</p>');
        }
    },

    /* --------------------------------------------
     * Tabs
     * ------------------------------------------ */
    switchTab: function(tab) {
        $('.proc-tab').removeClass('active');
        $(`.proc-tab[data-tab="${tab}"]`).addClass('active');
        $('.proc-tab-panel').removeClass('active');
        $('#tab-' + tab).addClass('active');
    },

    /* --------------------------------------------
     * Ejecución
     * ------------------------------------------ */
    executeProcedure: function() {
        if (!this._current) return;

        const inputs = $('#proc-params-form .proc-param-input').toArray();
        const params = inputs.map((el) => $(el).val());

        const container = $('#proc-exec-container');
        const resultBox = $('#proc-exec-result');
        resultBox.show();
        Admin.showLoading(container);

        Admin.post('modules/procedures/ajax/ejecutar_procedure.php', {
            modo: 'procedure',
            nombre: this._current,
            params: params
        })
            .then((response) => {
                this.renderExecResult(response.data);
            })
            .catch((error) => {
                Admin.logError('ProcsModule.executeProcedure', error, error.details || null);
                container.html(Admin.errorBoxHtml ? Admin.errorBoxHtml(error) : '<div class="sql-error-box">' + Admin.escapeHtml(error.message || 'Error') + '</div>');
                $('#proc-exec-count').text('0');
                $('#proc-exec-time').text('0');
            });
    },

    initBlockEditor: function() {
        const ta = document.getElementById('proc-block-input');
        if (!ta || typeof CodeMirror === 'undefined') return;
        this._blockEditor = CodeMirror.fromTextArea(ta, {
            mode: 'text/x-sql',
            theme: 'monokai',
            lineNumbers: true,
            smartIndent: true,
            lineWrapping: true
        });
    },

    executeBlock: function() {
        if (!this._blockEditor) this.initBlockEditor();
        const code = this._blockEditor ? this._blockEditor.getValue() : ($('#proc-block-input').val() || '');

        const container = $('#proc-exec-container');
        const resultBox = $('#proc-exec-result');
        resultBox.show();
        Admin.showLoading(container);

        Admin.post('modules/procedures/ajax/ejecutar_procedure.php', {
            modo: 'block',
            code: code
        })
            .then((response) => {
                this.renderExecResult(response.data);
            })
            .catch((error) => {
                Admin.logError('ProcsModule.executeBlock', error, error.details || null);
                container.html(Admin.errorBoxHtml ? Admin.errorBoxHtml(error) : '<div class="sql-error-box">' + Admin.escapeHtml(error.message || 'Error') + '</div>');
                $('#proc-exec-count').text('0');
                $('#proc-exec-time').text('0');
            });
    },

    renderExecResult: function(data) {
        const rows = data.data || [];
        this._lastData = rows;
        $('#proc-exec-count').text(rows.length);
        $('#proc-exec-time').text(data.time != null ? data.time : '—');

        const container = $('#proc-exec-container');
        if (!rows.length) {
            container.html('<div class="empty-state" style="padding:1rem;"><p class="empty-state-description">El procedure no devolvió filas (puede haber hecho DML sin RETURN).</p></div>');
            return;
        }

        const table = Admin.createTable(rows, {});
        container.html(table);
    },

    /* --------------------------------------------
     * Export
     * ------------------------------------------ */
    exportCSV: function() {
        if (!this._lastData || !this._lastData.length) {
            Admin.toastWarning('No hay datos para exportar');
            return;
        }
        const headers = Object.keys(this._lastData[0]);
        const csvRows = [headers.join(';')];
        this._lastData.forEach((row) => {
            const vals = headers.map((h) => {
                let v = row[h] == null ? '' : String(row[h]);
                if (v.indexOf(';') !== -1 || v.indexOf('"') !== -1 || v.indexOf('\n') !== -1) {
                    v = '"' + v.replace(/"/g, '""') + '"';
                }
                return v;
            });
            csvRows.push(vals.join(';'));
        });
        const blob = new Blob(['\uFEFF' + csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = (this._current || 'procedure') + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    },

    exportJSON: function() {
        if (!this._lastData || !this._lastData.length) {
            Admin.toastWarning('No hay datos para exportar');
            return;
        }
        const blob = new Blob([JSON.stringify(this._lastData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = (this._current || 'procedure') + '.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
};

// Admin.loadModule() calls ProcsModule.init() — do NOT bind $(document).ready here.
