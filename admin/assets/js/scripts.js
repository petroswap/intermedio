var ScriptsModule = (function() {
    var scripts = [];
    var currentScript = null;
    var _eventsBound = false;

    function init() {
        if (_eventsBound) return;
        _eventsBound = true;
        bindEvents();
        loadScripts();
    }

    function bindEvents() {
        $(document).off('.scriptsmod');

        $(document).on('click.scriptsmod', '.scripts-script-item', function() {
            var id = $(this).data('id');
            var script = scripts.find(function(s) { return s.id === id; });
            if (script) selectScript(script);
        });

        $(document).on('click.scriptsmod', '#btn-scripts-refresh', function() {
            loadScripts();
        });

        $(document).on('click.scriptsmod', '#btn-script-confirm', function() {
            showPasswordModal();
        });

        $(document).on('click.scriptsmod', '#scripts-password-cancel', function() {
            $('#scripts-password-modal').hide();
            $('#scripts-password').val('');
            $('#scripts-password-error').hide();
        });

        $(document).on('click.scriptsmod', '#scripts-password-submit', function() {
            verifyAndExecute();
        });

        $(document).on('keydown.scriptsmod', '#scripts-password', function(e) {
            if (e.which === 13) verifyAndExecute();
        });

        $(document).on('keydown.scriptsmod', function(e) {
            if (e.key === 'Escape') {
                $('#scripts-password-modal').hide();
            }
        });

        $(document).on('click.scriptsmod', '#btn-copy-output', function() {
            var output = $('#script-result').text();
            Admin.copyToClipboard(output);
            Admin.toastSuccess('Copiado al portapapeles');
        });

        $(document).on('click.scriptsmod', '#btn-export-json', function() {
            var data = window._lastJsonData || [];
            var json = JSON.stringify(data, null, 2);
            var blob = new Blob([json], { type: 'application/json' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = (currentScript ? currentScript.id : 'respuesta') + '.json';
            a.click();
            URL.revokeObjectURL(url);
        });

        $(document).on('click.scriptsmod', '#btn-export-csv', function() {
            var data = window._lastJsonData || [];
            if (!data.length) return;
            var headers = Object.keys(data[0]);
            var csv = headers.join(';') + '\n';
            data.forEach(function(row) {
                csv += headers.map(function(h) { return String(row[h] || '').replace('.', ','); }).join(';') + '\n';
            });
            var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = (currentScript ? currentScript.id : 'respuesta') + '.csv';
            a.click();
            URL.revokeObjectURL(url);
        });
    }

    function loadScripts() {
        $('#scripts-list').html('<div class="loading"><div class="spinner"></div><p>Cargando...</p></div>');

        Admin.post('modules/scripts/ajax/list_scripts.php', {})
            .then(function(response) {
                scripts = response.data || [];
                renderScripts();
            })
            .catch(function(error) {
                $('#scripts-list').html('<div class="alert alert-danger">Error al cargar scripts</div>');
                Admin.logError('loadScripts', error);
            });
    }

    function renderScripts() {
        var html = '';
        scripts.forEach(function(script) {
            html += '<div class="scripts-script-item" data-id="' + script.id + '">';
            html += '  <h4>' + Admin.escapeHtml(script.name) + '</h4>';
            html += '</div>';
        });
        
        if (!html) {
            html = '<div class="empty-state" style="padding: 1rem;"><p class="text-muted">No hay scripts disponibles</p></div>';
        }
        
        $('#scripts-list').html(html);
    }

    function selectScript(script) {
        currentScript = script;
        
        $('.scripts-script-item').removeClass('active');
        $('.scripts-script-item[data-id="' + script.id + '"]').addClass('active');
        
        $('#script-placeholder').hide();
        $('#script-content').show();
        
        $('#script-name').text(script.name);
        $('#script-description').text(script.description || 'Sin descripción');
        $('#script-method-badge').text(script.method || 'POST');
        
        renderParamsForm(script.params);
        $('#response-panel').hide();
        $('#response-footer').hide();
        $('#response-meta').hide();
    }

    function renderParamsForm(params) {
        var html = '';
        var $fields = $('#script-params-fields');
        var $card = $('#script-params-card');
        
        if (!params || params.length === 0) {
            $card.hide();
            return;
        }
        
        $card.show();
        
        params.forEach(function(param) {
            html += '<div class="scripts-param-row">';
            html += '  <label>' + Admin.escapeHtml(param.label || param.name);
            if (param.required) html += ' <span style="color: var(--error);">*</span>';
            html += '  </label>';
            
            var value = param.default || '';
            
            if (param.type === 'textarea') {
                html += '  <textarea class="form-input" data-param="' + param.name + '" placeholder="' + Admin.escapeHtml(param.placeholder || '') + '" rows="3">' + Admin.escapeHtml(value) + '</textarea>';
            } else {
                html += '  <input type="' + (param.type || 'text') + '" class="form-input" data-param="' + param.name + '" placeholder="' + Admin.escapeHtml(param.placeholder || '') + '" value="' + Admin.escapeHtml(value) + '">';
            }
            
            html += '</div>';
        });
        
        $fields.html(html);
    }

    function getParams() {
        var params = {};
        $('#script-params-fields [data-param]').each(function() {
            var name = $(this).data('param');
            var value = $(this).val();
            if (value !== '') params[name] = value;
        });
        return params;
    }

    function showPasswordModal() {
        if (!currentScript) return;
        
        if (currentScript.params && currentScript.params.length > 0) {
            var required = currentScript.params.filter(function(p) { return p.required; });
            var params = getParams();
            for (var i = 0; i < required.length; i++) {
                if (!params[required[i].name]) {
                    Admin.toastError('Falta el parámetro requerido: ' + required[i].label);
                    return;
                }
            }
        }
        
        $('#scripts-password-modal').show();
        $('#scripts-password').focus();
    }

    function verifyAndExecute() {
        var password = $('#scripts-password').val();
        
        if (!password) {
            $('#scripts-password-error').text('Introduce la contraseña').show();
            return;
        }
        
        $('#scripts-password-submit').prop('disabled', true).text('Verificando...');
        
        Admin.post('modules/scripts/ajax/verify_password.php', { password: password })
            .then(function(response) {
                if (response.success) {
                    $('#scripts-password-modal').hide();
                    $('#scripts-password').val('');
                    $('#scripts-password-error').hide();
                    executeScript();
                } else {
                    $('#scripts-password-error').text('Contraseña incorrecta').show();
                }
            })
            .catch(function(error) {
                $('#scripts-password-error').text('Error de verificación').show();
            })
            .finally(function() {
                $('#scripts-password-submit').prop('disabled', false).text('Ejecutar');
            });
    }

    function executeScript() {
        if (!currentScript) return;
        
        var params = getParams();
        
        $('#btn-script-confirm').prop('disabled', true).html('<div class="spinner" style="width:16px;height:16px;border-width:2px;"></div> Ejecutando...');
        $('#response-panel').show();
        $('#response-meta').hide();
        $('#response-footer').hide();
        $('#script-result').html('<div class="loading"><div class="spinner"></div><p>Ejecutando...</p></div>');
        
        Admin.post('modules/scripts/ajax/run_script.php', {
            script_id: currentScript.id,
            params: params
        })
        .then(function(response) {
            var data = response.data;
            
            $('#response-status').text('200');
            $('#response-status').attr('class', 'badge badge-success');
            $('#response-time').text(data.elapsed + 'ms');
            $('#response-size').text(formatBytes(data.size));
            $('#response-meta').show();
            $('#response-footer').show();
            
            if (data.is_json && data.json_data) {
                window._lastJsonData = data.json_data;
                renderJsonTable(data.json_data);
            } else {
                window._lastJsonData = null;
                renderRawOutput(data.output);
            }
        })
        .catch(function(error) {
            $('#response-status').text('500');
            $('#response-status').attr('class', 'badge badge-danger');
            $('#response-time').text('');
            $('#response-size').text('');
            $('#response-meta').show();
            $('#response-footer').show();
            
            var msg = error.message || 'Error desconocido';
            $('#script-result').html('<div class="alert alert-danger">' + Admin.escapeHtml(msg) + '</div>');
            Admin.logError('executeScript', error);
        })
        .finally(function() {
            $('#btn-script-confirm').prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg> Ejecutar con contraseña');
        });
    }

    function renderJsonTable(data) {
        if (!data || !data.length) {
            $('#script-result').html('<p class="text-muted">Sin datos</p>');
            return;
        }
        
        var headers = Object.keys(data[0]);
        var html = '<div style="overflow-x: auto;"><table class="scripts-result-table">';
        html += '<thead><tr>';
        headers.forEach(function(h) {
            html += '<th>' + Admin.escapeHtml(h) + '</th>';
        });
        html += '</tr></thead><tbody>';
        
        var maxRows = Math.min(data.length, 500);
        for (var i = 0; i < maxRows; i++) {
            html += '<tr>';
            headers.forEach(function(h) {
                var val = data[i][h];
                if (val === null || val === undefined) val = '';
                html += '<td>' + Admin.escapeHtml(String(val)) + '</td>';
            });
            html += '</tr>';
        }
        
        html += '</tbody></table></div>';
        
        if (data.length > 500) {
            html += '<p class="text-muted" style="font-size: 0.8rem; margin-top: 0.5rem;">Mostrando 500 de ' + data.length + ' registros.</p>';
        }
        
        $('#script-result').html(html);
    }

    function renderRawOutput(output) {
        var html = '<pre style="white-space: pre-wrap; font-size: 0.8rem; margin: 0; padding: 0.5rem; background: var(--surface-secondary); border-radius: 4px; overflow-x: auto;">' + Admin.escapeHtml(output) + '</pre>';
        $('#script-result').html(html);
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    return {
        init: init
    };
})();
