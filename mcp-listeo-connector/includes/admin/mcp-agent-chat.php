<?php
/**
 * MCP Agent Chat UI Class
 */
class MCP_Agent_Chat {

    public static function render() {
        ?>
        <div class="wrap mcp-agent-wrap">
            <h1><?php _e( 'MCP Intelligent Agent', 'mcp-listeo-connector' ); ?></h1>
            <p class="description"><?php _e( 'Habla con tu experto en turismo para buscar, crear y gestionar listados en lotes.', 'mcp-listeo-connector' ); ?></p>

            <div class="mcp-agent-container">
                <!-- Chat Window -->
                <div class="mcp-chat-section">
                    <div id="mcp-chat-history" class="mcp-chat-history">
                        <div class="mcp-message agent-message">
                            <div class="mcp-avatar">🤖</div>
                            <div class="mcp-text">
                                <?php _e( '¡Hola! Soy tu asistente MCP. ¿Qué destino o lote de sitios quieres preparar hoy?', 'mcp-listeo-connector' ); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mcp-chat-input-area">
                        <textarea id="mcp-agent-input" placeholder="<?php _e( 'Ej: Crea 5 sitios turísticos en París con hoteles y restaurantes...', 'mcp-listeo-connector' ); ?>"></textarea>
                        <button id="mcp-send-to-agent" class="button button-primary">
                            <span class="dashicons dashicons-paper-plane"></span>
                        </button>
                    </div>
                </div>

                <!-- Preview & Tasks Sidebar -->
                <div class="mcp-agent-sidebar">
                    <div class="mcp-sidebar-card" id="mcp-batch-tasks-card" style="display:none;">
                        <h3><?php _e( 'Checklist de Tareas', 'mcp-listeo-connector' ); ?></h3>
                        <div id="mcp-task-list" class="mcp-task-list">
                            <!-- Tasks will be injected here -->
                        </div>
                        <div class="mcp-batch-controls">
                            <label><?php _e( 'Intervalo:', 'mcp-listeo-connector' ); ?></label>
                             <select id="mcp-batch-interval">
                                <option value="10">10s</option>
                                <option value="15" selected>15s</option>
                                <option value="30">30s</option>
                                <option value="60">1 min</option>
                            </select>
                            <button id="mcp-run-batch" class="button button-secondary"><?php _e( 'Publicar Seleccionados', 'mcp-listeo-connector' ); ?></button>
                        </div>
                    </div>

                    <div class="mcp-sidebar-card" id="mcp-preview-card" style="display:none;">
                        <h3><?php _e( 'Previsualización', 'mcp-listeo-connector' ); ?></h3>
                        <div id="mcp-preview-content">
                            <!-- Preview data -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .mcp-agent-wrap { margin-top: 20px; }
            .mcp-agent-container {
                display: flex;
                gap: 20px;
                height: calc(100vh - 200px);
                min-height: 600px;
            }
            .mcp-chat-section {
                flex: 2;
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(10px);
                border: 1px solid #ddd;
                border-radius: 12px;
                display: flex;
                flex-direction: column;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            }
            .mcp-chat-history {
                flex: 1;
                padding: 20px;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
                gap: 15px;
            }
            .mcp-message {
                display: flex;
                gap: 12px;
                max-width: 85%;
            }
            .agent-message { align-self: flex-start; }
            .user-message { align-self: flex-end; flex-direction: row-reverse; }
            
            .mcp-avatar { font-size: 24px; }
            .mcp-text {
                background: #f0f0f1;
                padding: 12px 16px;
                border-radius: 15px;
                line-height: 1.5;
                font-size: 14px;
            }
            .user-message .mcp-text { background: #2271b1; color: white; }

            .mcp-chat-input-area {
                padding: 20px;
                border-top: 1px solid #ddd;
                display: flex;
                gap: 10px;
            }
            #mcp-agent-input {
                flex: 1;
                border-radius: 8px;
                border: 1px solid #ccd0d4;
                padding: 10px;
                resize: none;
                height: 60px;
            }
            #mcp-send-to-agent { height: 60px; width: 60px; }

            .mcp-agent-sidebar { flex: 1; display: flex; flex-direction: column; gap: 20px; }
            .mcp-sidebar-card {
                background: white;
                border: 1px solid #ddd;
                border-radius: 12px;
                padding: 15px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            }
            .mcp-task-header {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 6px;
                margin-bottom: 10px;
                border: 1px solid #eee;
            }
            .mcp-task-list { margin: 15px 0; max-height: 400px; overflow-y: auto; }
            .mcp-task-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px;
                border-bottom: 1px solid #f0f0f0;
                transition: background 0.2s;
            }
            .mcp-task-item:hover { background: #fafafa; }
            .mcp-task-item label { 
                flex: 1; 
                cursor: pointer; 
                display: flex; 
                flex-direction: column;
            }
            .mcp-task-name { font-weight: 500; color: #1d2327; }
            .mcp-task-cat { font-size: 11px; color: #646970; }
            .mcp-task-remove {
                background: none;
                border: none;
                color: #d63638;
                cursor: pointer;
                opacity: 0.3;
                transition: opacity 0.2s;
            }
            .mcp-task-remove:hover { opacity: 1; }
            
            .mcp-batch-controls {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid #eee;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }
            #mcp-run-batch { flex: 1; padding: 8px; height: 40px; }
            
            /* Status Indicators */
            .mcp-status-processing { animation: spin 1s linear infinite; display: inline-block; }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        </style>
        <?php
    }
}
