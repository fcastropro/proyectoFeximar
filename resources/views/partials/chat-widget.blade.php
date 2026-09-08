<!-- Widget Flotante del Asesor Virtual Feximar (IA) -->
<div id="feximar-ai-chat-widget">
    <!-- Botón Flotante (Esquina Inferior Izquierda) -->
    <button type="button" id="feximar-chat-toggle-btn" class="feximar-chat-btn" title="Asesor Virtual de Rosas">
        <span class="chat-btn-pulse"></span>
        <span class="chat-btn-icon">
            <i class="fa fa-comments"></i>
        </span>
        <span class="chat-btn-badge">AI</span>
    </button>

    <!-- Ventana Flotante del Chat -->
    <div id="feximar-chat-window" class="feximar-chat-box">
        <!-- Cabecera del Chat -->
        <div class="feximar-chat-header">
            <div class="chat-header-info">
                <div class="chat-avatar">
                    <img src="{{ asset('home/images/flores/logo-recortado.png') }}" alt="Feximar IA" />
                    <span class="online-indicator"></span>
                </div>
                <div class="chat-header-text">
                    <h5>Asesor Virtual Feximar</h5>
                    <p><i class="fa fa-sparkles"></i> Especialista en Rosas Ecuatorianas</p>
                </div>
            </div>
            <button type="button" id="feximar-chat-close-btn" class="chat-close-btn" title="Cerrar chat">&times;</button>
        </div>

        <!-- Cuerpo de Mensajes -->
        <div id="feximar-chat-messages" class="feximar-chat-body">
            <!-- Mensaje Inicial de Bienvenida -->
            <div class="chat-msg bot-msg">
                <div class="msg-avatar">
                    <i class="fa fa-leaf"></i>
                </div>
                <div class="msg-bubble">
                    <p>¡Hola! Soy tu <strong>Asesor Virtual de Feximar</strong>. Estoy aquí para guiarte sobre nuestras variedades de rosas de exportación, especificaciones de tallos (40-100 cm), presentaciones de cajas y microclimas de cultivo en Ecuador.</p>
                    <p style="margin-top:6px; margin-bottom:0;">¿En qué puedo orientarte hoy?</p>
                </div>
            </div>

            <!-- Chips de Preguntas Frecuentes / Rápidas -->
            <div id="feximar-quick-chips" class="chat-quick-chips">
                <span class="chip-item" data-question="¿Qué variedades de rosas rojas tienen?">🌹 Rosas Rojas</span>
                <span class="chip-item" data-question="¿Qué variedades blancas recomiendan?">🤍 Blancas y Nupciales</span>
                <span class="chip-item" data-question="¿Cuáles son las presentaciones y tipos de cajas?">📦 Tipos de Cajas (QB/HB)</span>
                <span class="chip-item" data-question="¿De qué zonas de Ecuador provienen sus flores?">🏔️ Zonas de Cultivo</span>
            </div>
        </div>

        <!-- Indicador de "Escribiendo..." (Oculto por defecto) -->
        <div id="feximar-typing-indicator" class="chat-typing-row" style="display:none;">
            <div class="typing-bubble">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="typing-label">Asesor consultando catálogo...</span>
        </div>

        <!-- Formulario de Entrada -->
        <div class="feximar-chat-footer">
            <form id="feximar-chat-form" autocomplete="off">
                <div class="chat-input-wrapper">
                    <input
                        type="text"
                        id="feximar-chat-input"
                        placeholder="Pregunta sobre variedades, tallos, empaque..."
                        maxlength="600"
                        required
                    />
                    <button type="submit" id="feximar-chat-send-btn" title="Enviar mensaje">
                        <i class="fa fa-paper-plane"></i>
                    </button>
                </div>
            </form>
            <div class="chat-legal-hint">
                <i class="fa fa-lock"></i> Datos públicos de exportación • Cotizaciones formales en ventas
            </div>
        </div>
    </div>
</div>

<!-- Estilos Encapsulados del Widget (Vanilla CSS Luxury) -->
<style>
    /* Contenedor principal anclado a la izquierda */
    #feximar-ai-chat-widget {
        position: fixed;
        bottom: 25px;
        left: 25px;
        z-index: 99999;
        font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    /* Botón Flotante Circular */
    .feximar-chat-btn {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #d91438 0%, #850b20 100%);
        border: 2px solid rgba(255, 255, 255, 0.25);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(217, 20, 56, 0.45), 0 4px 12px rgba(0, 0, 0, 0.35);
        position: relative;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        outline: none;
        padding: 0;
    }
    .feximar-chat-btn:hover {
        transform: scale(1.08) translateY(-3px);
        box-shadow: 0 12px 28px rgba(217, 20, 56, 0.6), 0 6px 14px rgba(0, 0, 0, 0.4);
    }
    .feximar-chat-btn .chat-btn-icon {
        font-size: 26px;
        color: #ffffff;
        transition: transform 0.2s ease;
    }
    .feximar-chat-btn:hover .chat-btn-icon {
        transform: scale(1.1);
    }
    .feximar-chat-btn .chat-btn-badge {
        position: absolute;
        top: -3px;
        right: -3px;
        background: #10b981;
        color: #ffffff;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.5px;
        padding: 2px 6px;
        border-radius: 10px;
        border: 2px solid #141318;
    }
    .chat-btn-pulse {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: rgba(217, 20, 56, 0.5);
        animation: chatPulse 2.2s infinite cubic-bezier(0.4, 0, 0.6, 1);
        z-index: -1;
    }
    @keyframes chatPulse {
        0% { transform: scale(1); opacity: 0.8; }
        70% { transform: scale(1.4); opacity: 0; }
        100% { transform: scale(1.4); opacity: 0; }
    }

    /* Ventana del Chat */
    .feximar-chat-box {
        display: none;
        position: absolute;
        bottom: 75px;
        left: 0;
        width: 380px;
        max-width: calc(100vw - 40px);
        height: 520px;
        max-height: calc(100vh - 120px);
        background: #141318;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 1px rgba(255, 255, 255, 0.2);
        flex-direction: column;
        overflow: hidden;
        animation: chatSlideUp 0.3s ease-out;
    }
    @keyframes chatSlideUp {
        from { opacity: 0; transform: translateY(18px) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Cabecera */
    .feximar-chat-header {
        background: linear-gradient(135deg, #1f1d26 0%, #15141b 100%);
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        flex-shrink: 0;
    }
    .chat-header-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .chat-avatar {
        position: relative;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4px;
    }
    .chat-avatar img {
        max-width: 100%;
        max-height: 100%;
        display: block;
    }
    .chat-avatar .online-indicator {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 10px;
        height: 10px;
        background: #10b981;
        border-radius: 50%;
        border: 2px solid #141318;
    }
    .chat-header-text h5 {
        margin: 0;
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.3px;
        line-height: 1.2;
    }
    .chat-header-text p {
        margin: 2px 0 0 0;
        color: #a0a0ab;
        font-size: 11px;
        line-height: 1.2;
    }
    .chat-close-btn {
        background: transparent;
        border: none;
        color: #90909c;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
        padding: 0 4px;
        transition: color 0.2s;
    }
    .chat-close-btn:hover {
        color: #ffffff;
    }

    /* Cuerpo de Mensajes */
    .feximar-chat-body {
        flex: 1;
        padding: 16px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: #141318;
    }
    .feximar-chat-body::-webkit-scrollbar {
        width: 5px;
    }
    .feximar-chat-body::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 4px;
    }

    /* Mensajes */
    .chat-msg {
        display: flex;
        gap: 8px;
        max-width: 88%;
        animation: msgFadeIn 0.25s ease-out;
    }
    @keyframes msgFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .chat-msg.bot-msg {
        align-self: flex-start;
    }
    .chat-msg.bot-msg .msg-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: rgba(217, 20, 56, 0.15);
        border: 1px solid rgba(217, 20, 56, 0.3);
        color: var(--rose-crimson, #d91438);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .chat-msg.bot-msg .msg-bubble {
        background: #201e26;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px 12px 12px 3px;
        padding: 10px 14px;
        color: #e4e4e9;
        font-size: 12px;
        line-height: 1.5;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    }
    .chat-msg.bot-msg .msg-bubble p {
        margin: 0;
    }
    .chat-msg.bot-msg .msg-bubble strong {
        color: #ffffff;
    }
    .chat-msg.user-msg {
        align-self: flex-end;
        justify-content: flex-end;
    }
    .chat-msg.user-msg .msg-bubble {
        background: linear-gradient(135deg, #d91438 0%, #ab0f2c 100%);
        color: #ffffff;
        border-radius: 12px 12px 3px 12px;
        padding: 10px 14px;
        font-size: 12.5px;
        line-height: 1.4;
        box-shadow: 0 2px 8px rgba(217, 20, 56, 0.3);
    }

    /* Chips rápidos */
    .chat-quick-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 4px;
    }
    .chip-item {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #c4c4cc;
        font-size: 11px;
        padding: 5px 10px;
        border-radius: 14px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .chip-item:hover {
        background: rgba(217, 20, 56, 0.2);
        border-color: rgba(217, 20, 56, 0.5);
        color: #ffffff;
        transform: translateY(-1px);
    }

    /* Indicador Escribiendo */
    .chat-typing-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 16px 10px 16px;
        background: #141318;
    }
    .typing-bubble {
        display: inline-flex;
        gap: 4px;
        background: #201e26;
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 6px 10px;
        border-radius: 12px;
    }
    .typing-bubble span {
        width: 6px;
        height: 6px;
        background: #a0a0ab;
        border-radius: 50%;
        animation: typingDot 1.4s infinite ease-in-out both;
    }
    .typing-bubble span:nth-child(1) { animation-delay: -0.32s; }
    .typing-bubble span:nth-child(2) { animation-delay: -0.16s; }
    @keyframes typingDot {
        0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
        40% { transform: scale(1); opacity: 1; }
    }
    .typing-label {
        font-size: 10.5px;
        color: #8c8c98;
        font-style: italic;
    }

    /* Footer con Input */
    .feximar-chat-footer {
        padding: 10px 14px 12px 14px;
        background: #17161c;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        flex-shrink: 0;
    }
    .chat-input-wrapper {
        display: flex;
        align-items: center;
        background: #201e26;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 24px;
        padding: 4px 6px 4px 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .chat-input-wrapper:focus-within {
        border-color: rgba(217, 20, 56, 0.6);
        box-shadow: 0 0 0 3px rgba(217, 20, 56, 0.15);
    }
    .chat-input-wrapper input {
        flex: 1;
        background: transparent;
        border: none;
        outline: none;
        color: #ffffff;
        font-size: 12px;
        padding: 6px 0;
    }
    .chat-input-wrapper input::placeholder {
        color: #727280;
    }
    .chat-input-wrapper button {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--rose-crimson, #d91438);
        border: none;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 12px;
        transition: background 0.2s, transform 0.1s;
        padding: 0;
        margin-left: 6px;
    }
    .chat-input-wrapper button:hover {
        background: #f01740;
        transform: scale(1.05);
    }
    .chat-legal-hint {
        font-size: 9.5px;
        color: #70707c;
        text-align: center;
        margin-top: 6px;
    }
    .chat-legal-hint i {
        color: #a0a0ab;
    }
</style>

<!-- Script AJAX con jQuery del Widget -->
<script>
    jQuery(document).ready(function ($) {
        var $widget = $('#feximar-ai-chat-widget');
        var $toggleBtn = $('#feximar-chat-toggle-btn');
        var $chatBox = $('#feximar-chat-window');
        var $closeBtn = $('#feximar-chat-close-btn');
        var $messagesContainer = $('#feximar-chat-messages');
        var $form = $('#feximar-chat-form');
        var $input = $('#feximar-chat-input');
        var $sendBtn = $('#feximar-chat-send-btn');
        var $typingIndicator = $('#feximar-typing-indicator');

        // Historial local de la conversación (máximo 6 mensajes para contexto)
        var conversationHistory = [];

        // Alternar visibilidad de la ventana del chat
        $toggleBtn.on('click', function () {
            if ($chatBox.is(':visible')) {
                $chatBox.fadeOut(200);
            } else {
                $chatBox.css('display', 'flex').hide().fadeIn(250, function () {
                    $input.focus();
                    scrollToBottom();
                });
            }
        });

        $closeBtn.on('click', function () {
            $chatBox.fadeOut(200);
        });

        // Click en chips de preguntas rápidas
        $(document).on('click', '.chip-item', function () {
            var q = $(this).data('question');
            if (q) {
                $input.val(q);
                $form.trigger('submit');
            }
        });

        // Envío del formulario
        $form.on('submit', function (e) {
            e.preventDefault();
            var message = $.trim($input.val());
            if (!message) return;

            // 1. Renderizar mensaje del usuario
            appendUserMessage(message);
            $input.val('').focus();

            // 2. Mostrar indicador de "Escribiendo..."
            $typingIndicator.fadeIn(150);
            scrollToBottom();

            // 3. Deshabilitar botón temporalmente
            $sendBtn.prop('disabled', true);

            // 4. Enviar petición AJAX a /ai/advisor/query
            $.ajax({
                url: "{{ route('ai.advisor.query') }}",
                type: "POST",
                data: JSON.stringify({
                    message: message,
                    history: conversationHistory.slice(-12)
                }),
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                },
                success: function (response) {
                    $typingIndicator.hide();
                    $sendBtn.prop('disabled', false);

                    if (response && response.reply) {
                        appendBotMessage(response.reply);

                        // Guardar en historial
                        conversationHistory.push({ role: 'user', content: message });
                        conversationHistory.push({ role: 'assistant', content: response.reply });
                    } else {
                        appendBotMessage('Disculpa, no pude procesar la respuesta en este momento. Por favor formula tu pregunta nuevamente.');
                    }
                },
                error: function (xhr) {
                    $typingIndicator.hide();
                    $sendBtn.prop('disabled', false);

                    var errorMsg = 'En este momento nuestro asesor se encuentra ocupado. Por favor intenta de nuevo en unos segundos.';
                    if (xhr.responseJSON && xhr.responseJSON.reply) {
                        errorMsg = xhr.responseJSON.reply;
                    } else if (xhr.status === 429) {
                        errorMsg = 'Has alcanzado el límite temporal de consultas por minuto. Por favor espera unos momentos antes de preguntar de nuevo.';
                    }
                    appendBotMessage(errorMsg);
                }
            });
        });

        function appendUserMessage(text) {
            var safeText = $('<div>').text(text).html();
            var html = '<div class="chat-msg user-msg">' +
                '<div class="msg-bubble">' + safeText + '</div>' +
                '</div>';
            $messagesContainer.append(html);
            scrollToBottom();
        }

        function appendBotMessage(text) {
            // Formatear negritas básicas de markdown **texto**
            var formattedText = $('<div>').text(text).html();
            formattedText = formattedText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            formattedText = formattedText.replace(/\n/g, '<br/>');

            var html = '<div class="chat-msg bot-msg">' +
                '<div class="msg-avatar"><i class="fa fa-leaf"></i></div>' +
                '<div class="msg-bubble">' + formattedText + '</div>' +
                '</div>';
            $messagesContainer.append(html);
            scrollToBottom();
        }

        function scrollToBottom() {
            $messagesContainer.stop().animate({
                scrollTop: $messagesContainer[0].scrollHeight
            }, 250);
        }
    });
</script>
