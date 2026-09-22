var ScriptsModule = (function() {
    var scripts = [];
    var currentScript = null;
    var history = [];
    var _eventsBound = false;

    function init() {
        if (_eventsBound) return;
        _eventsBound = true;
        bindEvents();
        loadScripts();
        loadHistory();
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

        $(document).on('click.scriptsmod', '.scripts-tab', function() {
            var tab = $(this).data('tab');
            $('.scripts-tab').removeClass('active');
            $(this).addClass('active');
            $('.scripts-tab-content').hide();
            $('#tab-' + tab).show();
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

        $(document).on('click.scriptsmod', '.scripts-history-item', function() {
            var idx = $(this).data('index');
            var item = history[idx];
            if (item) {
                $('#script-current-name').text(item.script);
                $('#response-status').text(item.success ? '200' : '500');
                $('#response-status').attr('class', 'badge ' + (item.success ? 'badge-success' : 'badge-danger'));
                $('#response-time').text(item.elapsed + 'ms');
                $('#response-meta').show();
                $('#response-footer').show();
                $('#response-panel').show();
                
                if (item.is_json) {
                    renderJsonTable(item.json_data);
                } else {
                    renderRawOutput(item.output);
                }
            }
        });

        $(document).on('click.scriptsmod', '#btn-history-clear', function() {
            history = [];
            localStorage.removeItem('scripts_history');
            renderHistory();
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
            html += '  <div class="scripts-script-info">';
            html += '    <h4>' + Admin.escapeHtml(script.name) + '</h4>';
            html += '    <p>' + Admin.escapeHtml(script.description || 'Sin descripción') + '</p>';
            html += '  </div>';
            html += '  <span class="badge badge-info">' + script.params.length + ' params</span>';
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
        $('#script-current-name').text(script.name);
        $('#script-method-badge').text(script.method || 'POST');
        $('#script-footer').show();
        $('#response-panel').hide();
        
        renderParamsForm(script.params);
        updateRawBody();
    }

    function renderParamsForm(params) {
        var html = '';
        var $fields = $('#script-params-fields');
        var $noParams = $('#script-no-params');
        
        if (!params || params.length === 0) {
            $fields.html('');
            $noParams.show();
            return;
        }
        
        $noParams.hide();
        
        params.forEach(function(param) {
            html += '<div class="scripts-param-row">';
            html += '  <label>' + Admin.escapeHtml(param.label || param.name);
            if (param.required) html += ' <span style="color: var(--error);">*</span>';
            html += '  </label>';
            
            var value = param.default || '';
            
            if (param.type === 'textarea') {
                html += '  <textarea class="form-input" data-param="' + param.name + '" placeholder="' + Admin.escapeHtml(param.placeholder || '') + '" rows="3" style="font-family: monospace; font-size: 0.85rem;">' + Admin.escapeHtml(value) + '</textarea>';
            } else if (param.type === 'select' && param.options) {
                html += '  <select class="form-input" data-param="' + param.name + '">';
                param.options.forEach(function(opt) {
                    var optVal = typeof opt === 'object' ? opt.value : opt;
                    var optLabel = typeof opt === 'object' ? opt.label : opt;
                    var selected = optVal == value ? ' selected' : '';
                    html += '    <option value="' + Admin.escapeHtml(optVal) + '"' + selected + '>' + Admin.escapeHtml(optLabel) + '</option>';
                });
                html += '  </select>';
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

    function updateRawBody() {
        var params = getParams();
        var raw = JSON.stringify(params, null, 2);
        $('#script-raw-body').val(raw);
    }

    $(document).on('input.scriptsmod', '#script-params-fields input, #script-params-fields textarea, #script-params-fields select', function() {
        updateRawBody();
    });

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
        
        var postData = {
            script_id: currentScript.id,
            params: params
        };
        
        Admin.post('modules/scripts/ajax/run_script.php', postData)
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
                
                addToHistory(currentScript.name, true, data.elapsed, data.output, data.is_json, data.json_data);
            })
            .catch(function(error) {
                $('#response-status').text('500');
                $('#response-status').attr('class', 'badge badge-danger');
                $('#response-time').text('');
                $('#response-size').text('');
                $('#response-meta').show();
                
                var msg = error.message || 'Error desconocido';
                $('#script-result').html('<div class="alert alert-danger">' + Admin.escapeHtml(msg) + '</div>');
                
                addToHistory(currentScript.name, false, 0, msg, false, null);
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
            html += '<p class="text-muted" style="font-size: 0.8rem; margin-top: 0.5rem;">Mostrando 500 de ' + data.length + ' registros. Exporta para ver todos.</p>';
        }
        
        $('#script-result').html(html);
    }

    function renderRawOutput(output) {
        var html = '<pre style="white-space: pre-wrap; font-size: 0.8rem; margin: 0; padding: 0.5rem; background: var(--surface-secondary); border-radius: 4px; overflow-x: auto;">' + Admin.escapeHtml(output) + '</pre>';
        $('#script-result').html(html);
    }

    function addToHistory(name, success, elapsed, output, isJson, jsonData) {
        var entry = {
            script: name,
            success: success,
            elapsed: elapsed,
            output: output.substring(0, 1000),
            is_json: isJson,
            json_data: jsonData,
            time: new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
        };
        
        history.unshift(entry);
        if (history.length > 20) history.pop();
        
        localStorage.setItem('scripts_history', JSON.stringify(history));
        renderHistory();
    }

    function loadHistory() {
        try {
            var stored = localStorage.getItem('scripts_history');
            history = stored ? JSON.parse(stored) : [];
        } catch(e) {
            history = [];
        }
        renderHistory();
    }

    function renderHistory() {
        if (!history.length) {
            $('#scripts-history').html('<p class="text-muted" style="font-size: 0.8rem; padding: 0.5rem;">Sin ejecuciones</p>');
            return;
        }
        
        var html = '';
        history.forEach(function(item, idx) {
            var icon = item.success ? '✅' : '❌';
            html += '<div class="scripts-history-item" data-index="' + idx + '">';
            html += '  <span class="history-status">' + icon + '</span>';
            html += '  <span class="history-name">' + Admin.escapeHtml(item.script) + '</span>';
            html += '  <span class="history-time">' + item.elapsed + 'ms</span>';
            html += '  <span class="history-time">' + item.time + '</span>';
            html += '</div>';
        });
        
        $('#scripts-history').html(html);
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
