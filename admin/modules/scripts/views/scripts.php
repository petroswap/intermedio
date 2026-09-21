<div class="scripts-container">
    <!-- Password Modal (shown when executing a script) -->
    <div id="scripts-password-modal" class="modal" style="display: none;">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3 class="modal-title">🔒 Confirmar Ejecución</h3>
            </div>
            <div class="modal-body">
                <p class="text-muted">Introduce la contraseña de administrador para ejecutar este script.</p>
                <div class="form-group">
                    <label class="form-label" for="scripts-password">Contraseña</label>
                    <input type="password" id="scripts-password" class="form-input" placeholder="Contraseña..." autofocus>
                </div>
                <div id="scripts-password-error" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button id="scripts-password-cancel" class="btn btn-secondary">Cancelar</button>
                <button id="scripts-password-submit" class="btn btn-primary">Ejecutar</button>
            </div>
        </div>
    </div>

    <!-- Scripts List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Scripts Disponibles</h3>
            <div class="card-actions">
                <button id="btn-scripts-refresh" class="btn btn-sm btn-ghost" title="Recargar lista">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="scripts-list">
                <div class="loading"><div class="spinner"></div><p>Cargando scripts...</p></div>
            </div>
        </div>
    </div>

    <!-- Script Execution Panel (shown after clicking Ejecutar) -->
    <div id="script-execution-panel" class="card" style="margin-top: 1rem; display: none;">
        <div class="card-header">
            <h3 class="card-title">Ejecutar: <span id="script-current-name"></span></h3>
            <button id="btn-script-close" class="btn btn-sm btn-ghost">✕</button>
        </div>
        <div class="card-body">
            <p id="script-description" class="text-muted"></p>
            <div id="script-result" style="margin-top: 1rem;"></div>
            <div style="margin-top: 1rem;">
                <button id="btn-script-confirm" class="btn btn-danger">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Ejecutar Script
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var ScriptsModule = {
    currentScript: null,
    
    init: function() {
        this.bindEvents();
        this.loadScripts();
    },
    
    bindEvents: function() {
        var self = this;
        
        $(document).off('click.scripts');
        
        $(document).on('click.scripts', '#btn-scripts-refresh', function() {
            self.loadScripts();
        });
        
        $(document).on('click.scripts', '.btn-run-script', function() {
            var scriptId = $(this).data('script');
            var scriptName = $(this).data('name');
            var scriptDesc = $(this).data('description');
            self.showExecutionPanel(scriptId, scriptName, scriptDesc);
        });
        
        $(document).on('click.scripts', '#btn-script-close', function() {
            self.hideExecutionPanel();
        });
        
        $(document).on('click.scripts', '#btn-script-confirm', function() {
            self.showPasswordModal();
        });
        
        $(document).on('click.scripts', '#scripts-password-cancel', function() {
            self.hidePasswordModal();
        });
        
        $(document).on('click.scripts', '#scripts-password-submit', function() {
            self.verifyAndExecute();
        });
        
        $(document).on('keypress.scripts', '#scripts-password', function(e) {
            if (e.which === 13) self.verifyAndExecute();
        });
    },
    
    loadScripts: function() {
        var self = this;
        var $list = $('#scripts-list');
        $list.html('<div class="loading"><div class="spinner"></div><p>Cargando scripts...</p></div>');
        
        Admin.post('modules/scripts/ajax/list_scripts.php', {})
            .then(function(response) {
                if (response.success && response.data) {
                    self.renderScripts(response.data);
                } else {
                    $list.html('<div class="empty-state"><div class="empty-state-icon">❌</div><p class="empty-state-title">Error al cargar scripts</p></div>');
                }
            })
            .catch(function(error) {
                Admin.logError('scripts', error);
                $list.html('<div class="empty-state"><div class="empty-state-icon">❌</div><p class="empty-state-title">Error de conexión</p></div>');
            });
    },
    
    renderScripts: function(scripts) {
        var $list = $('#scripts-list');
        $list.empty();
        
        if (!scripts || scripts.length === 0) {
            $list.html('<div class="empty-state"><div class="empty-state-icon">📜</div><p class="empty-state-title">No hay scripts disponibles</p><p class="empty-state-description">Coloca archivos .php en la carpeta admin/scripts/</p></div>');
            return;
        }
        
        var html = '<table class="data-table"><thead><tr><th>Script</th><th>Descripción</th><th>Acciones</th></tr></thead><tbody>';
        
        scripts.forEach(function(script) {
            html += '<tr>';
            html += '<td><strong>' + Admin.escapeHtml(script.name) + '</strong></td>';
            html += '<td>' + Admin.escapeHtml(script.description || 'Script personalizado') + '</td>';
            html += '<td><button class="btn btn-sm btn-outline btn-run-script" data-script="' + Admin.escapeHtml(script.id) + '" data-name="' + Admin.escapeHtml(script.name) + '" data-description="' + Admin.escapeHtml(script.description || '') + '">Ejecutar</button></td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        $list.html(html);
    },
    
    showExecutionPanel: function(scriptId, scriptName, scriptDescription) {
        this.currentScript = scriptId;
        $('#script-current-name').text(scriptName);
        $('#script-description').text(scriptDescription || '');
        $('#script-result').empty();
        $('#script-execution-panel').show();
        $('html, body').animate({ scrollTop: $('#script-execution-panel').offset().top - 100 }, 300);
    },
    
    hideExecutionPanel: function() {
        $('#script-execution-panel').hide();
        this.currentScript = null;
    },
    
    showPasswordModal: function() {
        $('#scripts-password-modal').show();
        $('#scripts-password').val('').focus();
        $('#scripts-password-error').hide();
    },
    
    hidePasswordModal: function() {
        $('#scripts-password-modal').hide();
        $('#scripts-password').val('');
        $('#scripts-password-error').hide();
    },
    
    verifyAndExecute: function() {
        var password = $('#scripts-password').val();
        var self = this;
        
        if (!password) {
            $('#scripts-password-error').text('Introduce la contraseña').show();
            return;
        }
        
        $('#scripts-password-submit').prop('disabled', true).text('Verificando...');
        
        Admin.post('modules/scripts/ajax/verify_password.php', { password: password })
            .then(function(response) {
                if (response.success) {
                    self.hidePasswordModal();
                    self.executeScript();
                } else {
                    $('#scripts-password-error').text(response.msg || 'Contraseña incorrecta').show();
                }
            })
            .catch(function(error) {
                $('#scripts-password-error').text('Error de verificación').show();
            })
            .finally(function() {
                $('#scripts-password-submit').prop('disabled', false).text('Ejecutar');
            });
    },
    
    executeScript: function() {
        var self = this;
        var scriptId = this.currentScript;
        
        if (!scriptId) {
            Admin.toastError('No hay script seleccionado');
            return;
        }
        
        $('#script-result').html('<div class="loading"><div class="spinner"></div><p>Ejecutando script...</p></div>');
        $('#btn-script-confirm').prop('disabled', true);
        
        Admin.post('modules/scripts/ajax/run_script.php', { script_id: scriptId })
            .then(function(response) {
                if (response.success) {
                    var output = response.data.output || 'Script ejecutado correctamente';
                    $('#script-result').html('<div class="alert alert-success"><strong>✅ Ejecutado</strong><pre style="margin-top: 0.5rem; white-space: pre-wrap;">' + Admin.escapeHtml(output) + '</pre></div>');
                } else {
                    $('#script-result').html('<div class="alert alert-danger"><strong>❌ Error</strong><pre style="margin-top: 0.5rem; white-space: pre-wrap;">' + Admin.escapeHtml(response.msg || 'Error desconocido') + '</pre></div>');
                }
            })
            .catch(function(error) {
                Admin.logError('scripts', error);
                $('#script-result').html('<div class="alert alert-danger"><strong>❌ Error de conexión</strong></div>');
            })
            .finally(function() {
                $('#btn-script-confirm').prop('disabled', false);
            });
    }
};

// Initialize when view is loaded
$(document).ready(function() {
    ScriptsModule.init();
});
</script>
