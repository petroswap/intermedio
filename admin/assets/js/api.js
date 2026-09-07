const Api = {
    endpoints: [],
    currentEndpoint: null,

    init: function() {
        this.bindEvents();
        this.loadEndpoints();
    },

    bindEvents: function() {
        $(document).off('.api');

        $(document).on('click.api', '#btn-refresh-endpoints', () => this.loadEndpoints());
        $(document).on('input.api', '#endpoints-search', (e) => this.filterEndpoints(e.target.value));
        $(document).on('click.api', '.endpoint-item', (e) => {
            const id = $(e.currentTarget).data('id');
            this.selectEndpoint(id);
        });
        $(document).on('click.api', '#btn-endpoint-test', () => this.testEndpoint());
        $(document).on('click.api', '#btn-copy-response', () => this.copyResponse());
    },

    loadEndpoints: function() {
        const $list = $('#endpoints-list');
        $list.html('<div class="empty-state" style="padding: 2rem 1rem;"><p class="empty-state-description">Cargando...</p></div>');

        $.ajax({
            url: Admin.base_url + 'modules/api/ajax/listar_endpoints.php',
            method: 'POST',
            success: (response) => {
                if (response.success && response.data) {
                    this.endpoints = response.data;
                    this.renderEndpointsList(response.data);
                } else {
                    $list.html('<div class="empty-state" style="padding: 2rem 1rem;"><p class="empty-state-description">Error al cargar endpoints</p></div>');
                }
            },
            error: () => {
                $list.html('<div class="empty-state" style="padding: 2rem 1rem;"><p class="empty-state-description">Error de conexión</p></div>');
            }
        });
    },

    renderEndpointsList: function(endpoints) {
        const $list = $('#endpoints-list');
        if (endpoints.length === 0) {
            $list.html('<div class="empty-state" style="padding: 2rem 1rem;"><p class="empty-state-description">No se encontraron endpoints</p></div>');
            return;
        }

        let html = '';
        let currentCategory = '';
        endpoints.forEach(ep => {
            const cat = ep.category || 'Otros';
            if (cat !== currentCategory) {
                currentCategory = cat;
                html += `<div class="endpoints-category-title">${currentCategory}</div>`;
            }
            const isActive = this.currentEndpoint && this.currentEndpoint.id === ep.id;
            const methodClass = (ep.method || 'POST').toLowerCase() === 'get' ? 'method-get' : 'method-post';
            html += `
                <div class="endpoint-item ${isActive ? 'active' : ''}" data-id="${ep.id}">
                    <div class="endpoint-item-main">
                        <span class="endpoint-item-method ${methodClass}">${ep.method || 'POST'}</span>
                        <div class="endpoint-item-info">
                            <div class="endpoint-item-name">${ep.name}</div>
                            <div class="endpoint-item-desc">${ep.description || ''}</div>
                        </div>
                    </div>
                </div>
            `;
        });
        $list.html(html);
    },

    filterEndpoints: function(query) {
        if (!query) {
            this.renderEndpointsList(this.endpoints);
            return;
        }
        const q = query.toLowerCase();
        const filtered = this.endpoints.filter(ep =>
            (ep.name || '').toLowerCase().includes(q) ||
            (ep.description || '').toLowerCase().includes(q) ||
            (ep.id || '').toLowerCase().includes(q) ||
            (ep.category || '').toLowerCase().includes(q)
        );
        this.renderEndpointsList(filtered);
    },

    selectEndpoint: function(id) {
        const ep = this.endpoints.find(e => e.id === id);
        if (!ep) return;

        this.currentEndpoint = ep;

        $('.endpoint-item').removeClass('active');
        $(`.endpoint-item[data-id="${id}"]`).addClass('active');

        $('#endpoint-empty').hide();
        $('#endpoint-detail').show();

        const baseUrl = Admin.base_url;
        const fullUrl = baseUrl + ep.url;

        $('#endpoint-name').text(ep.name);
        $('#endpoint-description').text(ep.description || '');
        $('#endpoint-url').text(fullUrl);
        $('#endpoint-table').text(ep.table || '*');
        $('#endpoint-type').text(ep.type || 'list');

        const method = (ep.method || 'POST').toUpperCase();
        $('#endpoint-method-badge').text(method).attr('class', 'endpoint-method-badge method-' + method.toLowerCase());

        this.renderParams(ep.params || []);
        this.prefillBody(ep);

        $('#endpoint-response-body').text('Haz clic en "Ejecutar Petición" para ver la respuesta');
        $('#endpoint-status-code').text('').attr('class', 'endpoint-status-code');
        $('#endpoint-test-time').text('');
    },

    renderParams: function(params) {
        const $container = $('#endpoint-params-container');
        if (!params || params.length === 0) {
            $container.html('<p class="text-secondary" style="font-size: 12px;">Sin parámetros requeridos</p>');
            return;
        }

        let html = '';
        params.forEach(p => {
            const required = p.required ? '<span class="text-danger">*</span>' : '';
            html += `
                <div class="endpoint-param-row">
                    <label class="endpoint-param-label">
                        <code>${p.name}</code> ${required}
                    </label>
                    <input type="text" class="form-input endpoint-param-input"
                        data-param="${p.name}"
                        value="${p.example || ''}"
                        placeholder="${p.description || ''}">
                </div>
            `;
        });
        $container.html(html);
    },

    prefillBody: function(ep) {
        const body = {};
        if (ep.table && ep.type !== 'sql') {
            body.table = ep.table;
        }
        if (ep.params) {
            ep.params.forEach(p => {
                if (p.example) body[p.name] = p.example;
            });
        }
        if (ep.sql) {
            body.sql = ep.sql;
        }
        if (Object.keys(body).length > 0) {
            $('#endpoint-body').val(JSON.stringify(body, null, 2));
        } else {
            $('#endpoint-body').val('{}');
        }
    },

    testEndpoint: function() {
        if (!this.currentEndpoint) return;

        const ep = this.currentEndpoint;
        const startTime = Date.now();

        // Parse body JSON
        const bodyText = $('#endpoint-body').val().trim();
        let data = {};
        if (bodyText) {
            try {
                data = JSON.parse(bodyText);
            } catch(e) {
                $('#endpoint-response-body').text('Error: Body JSON no válido - ' + e.message);
                $('#endpoint-status-code').text('400').attr('class', 'endpoint-status-code status-error');
                return;
            }
        }

        // Override with param inputs
        $('.endpoint-param-input').each(function() {
            const name = $(this).data('param');
            const value = $(this).val().trim();
            if (value) data[name] = value;
        });

        // Build URL
        let url = ep.url;
        if (url && !url.startsWith('http')) {
            url = Admin.base_url + url;
        }

        $('#btn-endpoint-test').prop('disabled', true).text('⏳ Ejecutando...');
        $('#endpoint-response-body').text('...');
        $('#endpoint-status-code').text('').attr('class', 'endpoint-status-code');

        $.ajax({
            url: url,
            method: ep.method || 'POST',
            data: data,
            success: (response) => {
                const elapsed = Date.now() - startTime;
                $('#btn-endpoint-test').prop('disabled', false).text('▶ Ejecutar Petición');
                $('#endpoint-test-time').text(`${elapsed}ms`);
                $('#endpoint-status-code').text('200 OK').attr('class', 'endpoint-status-code status-success');
                $('#endpoint-response-body').text(JSON.stringify(response, null, 2));
            },
            error: (xhr) => {
                const elapsed = Date.now() - startTime;
                $('#btn-endpoint-test').prop('disabled', false).text('▶ Ejecutar Petición');
                $('#endpoint-test-time').text(`${elapsed}ms`);
                const status = xhr.status || 'Error';
                const statusText = xhr.statusText || '';
                $('#endpoint-status-code').text(`${status} ${statusText}`).attr('class', 'endpoint-status-code status-error');
                try {
                    const body = JSON.parse(xhr.responseText);
                    $('#endpoint-response-body').text(JSON.stringify(body, null, 2));
                } catch(e) {
                    $('#endpoint-response-body').text(xhr.responseText || 'Sin respuesta');
                }
            }
        });
    },

    copyResponse: function() {
        const text = $('#endpoint-response-body').text();
        if (!text || text.startsWith('Haz clic') || text === '...') return;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => Admin.showSuccess($('#btn-copy-response'), 'Copiado!'));
        }
    }
};
