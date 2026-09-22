var ScriptsModule = {
    currentScript: null,
    
    init: function() {
        this.bindEvents();
        this.loadScripts();
    },
    
    bindEvents: function() {
        var self = this;
        
        $(document).off('click.scriptsmod');
        
        $(document).on('click.scriptsmod', '#btn-scripts-refresh', function() {
            self.loadScripts();
        });
        
        $(document).on('click.scriptsmod', '.scripts-script-item', function() {
            var $item = $(this);
            var scriptId = $item.data('script');
            var scriptName = $item.data('name');
            $('.scripts-script-item').removeClass('active');
            $item.addClass('active');
            self.selectScript(scriptId, scriptName);
        });
        
        $(document).on('click.scriptsmod', '#btn-script-confirm', function() {
            self.showPasswordModal();
        });
        
        $(document).on('click.scriptsmod', '#scripts-password-cancel', function() {
            self.hidePasswordModal();
        });
        
        $(document).on('click.scriptsmod', '#scripts-password-submit', function() {
            self.verifyAndExecute();
        });
        
        $(document).on('keypress.scriptsmod', '#scripts-password', function(e) {
            if (e.which === 13) self.verifyAndExecute();
        });
        
        $(document).on('click.scriptsmod', '#btn-copy-output', function() {
            var text = $('#script-output-text').val();
            Admin.copyToClipboard(text);
            Admin.toastSuccess('Copiado al portapapeles');
        });
    },
    
    loadScripts: function() {
        var self = this;
        var $list = $('#scripts-list');
        $list.html('<div class="loading"><div class="spinner"></div><p>Cargando...</p></div>');
        
        Admin.post('modules/scripts/ajax/list_scripts.php', {})
            .then(function(response) {
                if (response.success && response.data) {
                    self.renderScripts(response.data);
                } else {
                    $list.html('<p class="text-muted">Error al cargar scripts</p>');
                }
            })
            .catch(function(error) {
                Admin.logError('scripts', error);
                $list.html('<p class="text-muted">Error de conexión</p>');
            });
    },
    
    renderScripts: function(scripts) {
        var $list = $('#scripts-list');
        $list.empty();
        
        if (!scripts || scripts.length === 0) {
            $list.html('<div class="empty-state"><p class="empty-state-title">No hay scripts</p><p class="empty-state-description">Coloca .php en admin/scripts/</p></div>');
            return;
        }
        
        var html = '';
        scripts.forEach(function(script) {
            html += '<div class="scripts-script-item" data-script="' + Admin.escapeHtml(script.id) + '" data-name="' + Admin.escapeHtml(script.name) + '">';
            html += '  <div class="scripts-script-info">';
            html += '    <h4>' + Admin.escapeHtml(script.name) + '</h4>';
            html += '    <p>' + Admin.escapeHtml(script.description || '') + '</p>';
            html += '  </div>';
            html += '  <button class="btn btn-sm btn-outline">▶ Ejecutar</button>';
            html += '</div>';
        });
        
        $list.html(html);
    },
    
    selectScript: function(scriptId, scriptName) {
        this.currentScript = scriptId;
        $('#script-current-name').text(scriptName);
        $('#script-placeholder').hide();
        $('#script-result').empty().show();
        $('#script-footer').show();
    },
    
    showPasswordModal: function() {
        $('#scripts-password-modal').show();
        $('#scripts-password').val('').focus();
        $('#scripts-password-error').hide();
        $('#scripts-password-submit').prop('disabled', false).text('Ejecutar');
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
        $('#scripts-password-error').hide();
        
        Admin.post('modules/scripts/ajax/verify_password.php', { password: password })
            .then(function(response) {
                if (response.success) {
                    self.hidePasswordModal();
                    self.executeScript();
                } else {
                    $('#scripts-password-error').text(response.msg || 'Contraseña incorrecta').show();
                    $('#scripts-password').val('').focus();
                    $('#scripts-password-submit').prop('disabled', false).text('Ejecutar');
                }
            })
            .catch(function(error) {
                $('#scripts-password-error').text('Error de verificación').show();
                $('#scripts-password-submit').prop('disabled', false).text('Ejecutar');
            });
    },
    
    executeScript: function() {
        var self = this;
        var scriptId = this.currentScript;
        
        if (!scriptId) {
            Admin.toastError('Selecciona un script primero');
            return;
        }
        
        $('#script-result').html('<div class="loading"><div class="spinner"></div><p>Ejecutando...</p></div>');
        $('#btn-script-confirm').prop('disabled', true).text('Ejecutando...');
        
        Admin.post('modules/scripts/ajax/run_script.php', { script_id: scriptId })
            .then(function(response) {
                if (response.success) {
                    var output = response.data.output || 'Ejecutado correctamente';
                    var html = '<div class="alert alert-success" style="margin-bottom: 0.5rem;"><strong>✅ Completado</strong></div>';
                    html += '<textarea id="script-output-text" class="form-input" rows="14" readonly style="font-family: monospace; font-size: 12px; background: var(--surface-secondary); resize: vertical;">' + Admin.escapeHtml(output) + '</textarea>';
                    html += '<div style="margin-top: 0.5rem;">';
                    html += '<button class="btn btn-sm btn-outline" id="btn-copy-output">📋 Copiar</button>';
                    html += '</div>';
                    $('#script-result').html(html);
                } else {
                    var html = '<div class="alert alert-danger" style="margin-bottom: 0.5rem;"><strong>❌ Error</strong></div>';
                    html += '<textarea class="form-input" rows="8" readonly style="font-family: monospace; font-size: 12px; background: var(--surface-secondary);">' + Admin.escapeHtml(response.msg || 'Error desconocido') + '</textarea>';
                    $('#script-result').html(html);
                }
            })
            .catch(function(error) {
                Admin.logError('scripts', error);
                $('#script-result').html('<div class="alert alert-danger"><strong>❌ Error de conexión</strong></div>');
            })
            .always(function() {
                $('#btn-script-confirm').prop('disabled', false);
            });
    }
};
