<div class="scripts-container">
    <!-- Password Modal -->
    <div id="scripts-password-modal" class="modal" style="display: none;">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3 class="modal-title">🔒 Acceso Restringido</h3>
            </div>
            <div class="modal-body">
                <p class="text-muted">Introduce la contraseña de administrador para acceder a los scripts.</p>
                <div class="form-group">
                    <label class="form-label" for="scripts-password">Contraseña</label>
                    <input type="password" id="scripts-password" class="form-input" placeholder="Contraseña..." autofocus>
                </div>
                <div id="scripts-password-error" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button id="scripts-password-cancel" class="btn btn-secondary">Cancelar</button>
                <button id="scripts-password-submit" class="btn btn-primary">Acceder</button>
            </div>
        </div>
    </div>

    <!-- Scripts Panel (hidden until authenticated) -->
    <div id="scripts-panel" style="display: none;">
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
                    <div class="empty-state">
                        <div class="empty-state-icon">📜</div>
                        <p class="empty-state-title">No hay scripts disponibles</p>
                        <p class="empty-state-description">Los scripts se listarán aquí cuando estén configurados.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Script Execution Panel -->
        <div id="script-execution-panel" class="card" style="margin-top: 1rem; display: none;">
            <div class="card-header">
                <h3 class="card-title">Ejecutar: <span id="script-name"></span></h3>
                <button id="btn-script-close" class="btn btn-sm btn-ghost">✕</button>
            </div>
            <div class="card-body">
                <p id="script-description" class="text-muted"></p>
                <div id="script-params-form"></div>
                <div id="script-result" style="margin-top: 1rem;"></div>
                <div style="margin-top: 1rem;">
                    <button id="btn-script-execute" class="btn btn-danger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        Ejecutar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Login prompt (shown when not authenticated) -->
    <div id="scripts-login-prompt">
        <div class="empty-state">
            <div class="empty-state-icon">🔐</div>
            <p class="empty-state-title">Acceso Restringido</p>
            <p class="empty-state-description">Esta sección requiere autenticación de administrador.</p>
            <button id="btn-scripts-login" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Iniciar Sesión
            </button>
        </div>
    </div>
</div>

<script>
var ScriptsModule = {
    authenticated: false,
    
    init: function() {
        this.bindEvents();
        this.checkSession();
    },
    
    bindEvents: function() {
        var self = this;
        
        $(document).off('click.scripts');
        
        $(document).on('click.scripts', '#btn-scripts-login', function() {
            self.showPasswordModal();
        });
        
        $(document).on('click.scripts', '#scripts-password-cancel', function() {
            self.hidePasswordModal();
        });
        
        $(document).on('click.scripts', '#scripts-password-submit', function() {
            self.verifyPassword();
        });
        
        $(document).on('keypress.scripts', '#scripts-password', function(e) {
            if (e.which === 13) self.verifyPassword();
        });
        
        $(document).on('click.scripts', '#btn-scripts-refresh', function() {
            self.loadScripts();
        });
    },
    
    checkSession: function() {
        var saved = sessionStorage.getItem('scripts_auth');
        if (saved) {
            this.authenticated = true;
            this.showPanel();
        }
    },
    
    showPasswordModal: function() {
        $('#scripts-password-modal').show();
        $('#scripts-password').focus();
        $('#scripts-password-error').hide();
    },
    
    hidePasswordModal: function() {
        $('#scripts-password-modal').hide();
        $('#scripts-password').val('');
        $('#scripts-password-error').hide();
    },
    
    verifyPassword: function() {
        var password = $('#scripts-password').val();
        var self = this;
        
        if (!password) {
            $('#scripts-password-error').text('Introduce la contraseña').show();
            return;
        }
        
        Admin.post('modules/scripts/ajax/verify_password.php', { password: password })
            .then(function(response) {
                if (response.success) {
                    sessionStorage.setItem('scripts_auth', '1');
                    self.authenticated = true;
                    self.hidePasswordModal();
                    self.showPanel();
                    self.loadScripts();
                } else {
                    $('#scripts-password-error').text(response.msg || 'Contraseña incorrecta').show();
                }
            })
            .catch(function(error) {
                $('#scripts-password-error').text('Error de verificación').show();
            });
    },
    
    showPanel: function() {
        $('#scripts-login-prompt').hide();
        $('#scripts-panel').show();
    },
    
    loadScripts: function() {
        var self = this;
        Admin.post('modules/scripts/ajax/list_scripts.php', {})
            .then(function(response) {
                if (response.success && response.data) {
                    self.renderScripts(response.data);
                }
            })
            .catch(function(error) {
                Admin.logError('scripts', error);
            });
    },
    
    renderScripts: function(scripts) {
        var $list = $('#scripts-list');
        $list.empty();
        
        if (!scripts || scripts.length === 0) {
            $list.html('<div class="empty-state"><div class="empty-state-icon">📜</div><p class="empty-state-title">No hay scripts disponibles</p><p class="empty-state-description">Los scripts se listarán aquí cuando estén configurados.</p></div>');
            return;
        }
        
        var html = '<table class="data-table"><thead><tr><th>Script</th><th>Descripción</th><th>Acciones</th></tr></thead><tbody>';
        
        scripts.forEach(function(script) {
            html += '<tr>';
            html += '<td><strong>' + Admin.escapeHtml(script.name) + '</strong></td>';
            html += '<td>' + Admin.escapeHtml(script.description || '') + '</td>';
            html += '<td><button class="btn btn-sm btn-outline btn-run-script" data-script="' + Admin.escapeHtml(script.id) + '">Ejecutar</button></td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        $list.html(html);
    }
};

// Initialize when view is loaded
$(document).ready(function() {
    ScriptsModule.init();
});
</script>
