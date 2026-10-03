jQuery(document).ready(function($) {
    const modal = $('#mcp-test-modal');
    const closeBtn = $('.mcp-close');
    const runBtn = $('#mcp-run-test');
    const resultPre = $('#mcp-test-result');
    const argsArea = $('#mcp-test-args');
    const titleSpan = $('#mcp-test-id');

    let currentAbility = '';

    // Connect AI Button
    $('#mcp-connect-ai').on('click', function() {
        const provider = $('#mcp_ai_provider').val();
        const apiKey   = $('#mcp_ai_api_key').val();
        const btn      = $(this);
        const modelRow = $('#mcp-model-row');
        const modelSelect = $('#mcp_ai_model');

        if (!apiKey) {
            alert('Por favor ingresa una API Key.');
            return;
        }

        btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Conectando...');

        $.ajax({
            url: mcp_settings.rest_url + 'mcp-listeo/v1/models',
            method: 'GET',
            data: {
                provider: provider,
                api_key: apiKey
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', mcp_settings.nonce);
            },
            success: function(response) {
                if (response.models && response.models.length > 0) {
                    modelSelect.empty();
                    response.models.forEach(function(model) {
                        modelSelect.append($('<option>', {
                            value: model,
                            text: model
                        }));
                    });
                    modelRow.show();
                    btn.removeClass('button-secondary').addClass('button-primary').html('<span class="dashicons dashicons-yes"></span> Conectado');
                    alert('¡Conexión Exitosa! Modelos cargados.');
                }
            },
            error: function(xhr) {
                alert('Error de conexión: ' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText));
                btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-links"></span> Reintentar');
            }
        });
    });

    // Connect Telegram Button
    $('#mcp-connect-telegram').on('click', function() {
        const token  = $('#mcp_telegram_token').val();
        const chatId = $('#mcp_telegram_chat_id').val();
        const btn    = $(this);

        if (!token) {
            alert('Por favor ingresa el Token de Telegram.');
            return;
        }

        btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Probando...');

        $.ajax({
            url: mcp_settings.rest_url + 'mcp-listeo/v1/test-telegram',
            method: 'GET',
            data: {
                token: token,
                chat_id: chatId
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', mcp_settings.nonce);
            },
            success: function(response) {
                btn.removeClass('button-secondary').addClass('button-primary').html('<span class="dashicons dashicons-yes"></span> Conectado');
                alert(response.message + '\nBot: @' + response.bot_info.username);
            },
            error: function(xhr) {
                alert('Error de Telegram: ' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText));
                btn.prop('disabled', false).html('<span class="dashicons dashicons-megaphone"></span> Reintentar');
            }
        });
    });

    // Open Modal (Event Delegation)
    $(document).on('click', '.mcp-test-ability', function() {
        currentAbility = $(this).data('ability');
        titleSpan.text(currentAbility);
        modal.show();
        
        // Default args per ability type (helpful UX in Spanish)
        if (currentAbility.includes('search')) {
            argsArea.val('{\n  "query": "Hotel en Guatavita",\n  "limit": 3\n}');
        } else if (currentAbility.includes('booking')) {
            argsArea.val('{\n  "listing_id": 1,\n  "date": "2024-12-31",\n  "time": "18:00",\n  "comment": "Reserva de prueba"\n}');
        } else if (currentAbility.includes('create-listing')) {
            argsArea.val('{\n  "title": "La Estatua de la Libertad",\n  "content": "Monumento icónico en Nueva York...",\n  "address": "Liberty Island, New York, NY 10004",\n  "price": "25.00",\n  "status": "publish"\n}');
        } else if (currentAbility.includes('get-post')) {
            argsArea.val('{\n  "id": 1,\n  "post_type": "listing"\n}');
        } else {
            argsArea.val('{\n  \n}');
        }
        resultPre.text('Esperando ejecución...');
        resultPre.css('color', '#e6e6e6');
    });

    // Close Modal
    $(document).on('click', '.mcp-close', function() {
        modal.hide();
    });

    $(window).on('click', function(event) {
        if (event.target == modal[0]) {
            modal.hide();
        }
    });

    // Run Test
    runBtn.on('click', function() {
        try {
            const args = JSON.parse(argsArea.val());
            runBtn.prop('disabled', true).text('Calling REST API...');
            resultPre.text('Processing...');

            $.ajax({
                url: mcp_settings.api_url,
                method: 'POST',
                headers: {
                    'X-MCP-Token': mcp_settings.token,
                    'Content-Type': 'application/json'
                },
                data: JSON.stringify({
                    ability: currentAbility,
                    args: args
                }),
                success: function(response) {
                    resultPre.html(syntaxHighlight(response));
                },
                error: function(xhr) {
                    let errorMsg = 'Error ' + xhr.status + ': ' + xhr.statusText;
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMsg += '\n\nDetails: ' + xhr.responseJSON.data;
                    }
                    resultPre.text(errorMsg).css('color', '#ff4d4d');
                },
                complete: function() {
                    runBtn.prop('disabled', false).text('Execute REST Call');
                }
            });

        } catch (e) {
            alert('Invalid JSON format in arguments.');
        }
    });

    function syntaxHighlight(json) {
        if (typeof json != 'string') {
            json = JSON.stringify(json, undefined, 2);
        }
        json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+-]?\d+)?)/g, function (match) {
            var cls = 'number';
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    cls = 'key';
                } else {
                    cls = 'string';
                }
            } else if (/true|false/.test(match)) {
                cls = 'boolean';
            } else if (/null/.test(match)) {
                cls = 'null';
            }
            return '<span class="' + cls + '">' + match + '</span>';
        });
    }

    // Add CSS for spin animation
    $('<style>.spin { animation: mcp-spin 2s linear infinite; } @keyframes mcp-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>').appendTo('head');
});
