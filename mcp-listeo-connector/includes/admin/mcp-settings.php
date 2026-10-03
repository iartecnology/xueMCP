<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MCP Settings Admin Page
 */
class MCP_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	public function enqueue_styles( $hook ) {
		if ( strpos( $hook, 'mcp-connector' ) === false && strpos( $hook, 'mcp-agent-chat' ) === false ) {
			return;
		}
		$ver = time();
		// Go up two levels from includes/admin/ to reach the plugin root
		$plugin_root_url = plugin_dir_url( dirname( dirname( __FILE__ ) ) );
		
		wp_enqueue_style( 'mcp-admin-css', $plugin_root_url . 'assets/css/admin-style.css', array(), $ver );
		wp_enqueue_script( 'mcp-admin-js', $plugin_root_url . 'assets/js/mcp-settings.js', array( 'jquery' ), $ver, true );
        
        if ( strpos( $hook, 'mcp-agent-chat' ) !== false ) {
            wp_enqueue_script( 'mcp-agent-chat-js', $plugin_root_url . 'assets/js/mcp-agent-chat.js', array( 'jquery' ), $ver, true );
            wp_localize_script( 'mcp-agent-chat-js', 'mcpAdmin', array(
                'restUrl' => get_rest_url( null, '/' ),
                'nonce'   => wp_create_nonce( 'wp_rest' )
            ) );
        }
		
		wp_localize_script( 'mcp-admin-js', 'mcp_settings', array(
			'api_url'  => get_rest_url( null, 'mcp/v1/call' ),
			'rest_url' => get_rest_url( null, '/' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'token'    => get_option( 'mcp_security_token' ),
		) );
	}

	public function add_menu_page() {
		add_menu_page(
			__( 'MCP Connector', 'mcp-listeo-connector' ),
			__( 'MCP Connector', 'mcp-listeo-connector' ),
			'manage_options',
			'mcp-connector',
			array( $this, 'render_settings_page' ),
			'dashicons-share-alt',
			80
		);

        add_submenu_page(
			'mcp-connector',
			__( 'Agent IA', 'mcp-listeo-connector' ),
			__( 'Agent IA', 'mcp-listeo-connector' ),
			'manage_options',
			'mcp-agent-chat',
			array( $this, 'render_agent_chat_page' )
		);
	}

    public function render_agent_chat_page() {
        // This will call the specialized class for the chat UI
        if ( class_exists( 'MCP_Agent_Chat' ) ) {
            MCP_Agent_Chat::render();
        } else {
            echo '<div class="wrap"><h1>Agent IA</h1><p>Agent module not loaded.</p></div>';
        }
    }

	public function register_settings() {
		register_setting( 'mcp_settings_group', 'mcp_enabled_abilities', array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_abilities' ),
			'default'           => array(),
		) );

		register_setting( 'mcp_settings_group', 'mcp_security_token', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => wp_generate_password( 32, false ),
		) );

		register_setting( 'mcp_settings_group', 'mcp_disable_security', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'no',
		) );
        
        register_setting( 'mcp_settings_group', 'mcp_ai_provider', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'gemini',
		) );

        register_setting( 'mcp_settings_group', 'mcp_ai_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

        register_setting( 'mcp_settings_group', 'mcp_telegram_token', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

        register_setting( 'mcp_settings_group', 'mcp_telegram_chat_id', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

        register_setting( 'mcp_settings_group', 'mcp_ai_batch_delay', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 60,
		) );
        
        register_setting( 'mcp_settings_group', 'mcp_prompts', array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_prompts' ),
			'default'           => array(),
		) );

        register_setting( 'mcp_settings_group', 'mcp_ai_model', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );
	}

	public function sanitize_abilities( $input ) {
		return is_array( $input ) ? array_map( 'sanitize_text_field', $input ) : array();
	}

    public function sanitize_prompts( $input ) {
		return is_array( $input ) ? array_map( 'sanitize_textarea_field', $input ) : array();
	}

	public function render_settings_page() {
		$abilities  = MCP_Registry::get_all();
		$enabled    = get_option( 'mcp_enabled_abilities', array() );
        $token      = get_option( 'mcp_security_token' );
        $disable_security = get_option( 'mcp_disable_security', 'no' );
        $prompts    = get_option( 'mcp_prompts', array() );
        $logs       = get_option( 'mcp_audit_logs', array() );
        
        $ai_provider      = get_option( 'mcp_ai_provider', 'gemini' );
        $ai_api_key       = get_option( 'mcp_ai_api_key', '' );
        $telegram_token   = get_option( 'mcp_telegram_token', '' );
        $telegram_chat_id = get_option( 'mcp_telegram_chat_id', '' );
        $batch_delay      = get_option( 'mcp_ai_batch_delay', 60 );

        // Ensure token exists in DB
        if ( empty( $token ) ) {
            $token = wp_generate_password( 32, false );
            update_option( 'mcp_security_token', $token );
        }

        $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
		?>
		<div class="wrap">
			<h1>
				<?php _e( 'MCP Connector Settings', 'mcp-listeo-connector' ); ?> 
				<span style="font-size: 13px; font-weight: normal; background: #2271b1; color: #fff; padding: 3px 8px; border-radius: 12px; vertical-align: middle;">
					v<?php echo defined('MCP_LISTEO_CONNECTOR_VERSION') ? MCP_LISTEO_CONNECTOR_VERSION : '1.2.0'; ?>
				</span>
			</h1>
			
            <h2 class="nav-tab-wrapper">
                <a href="?page=mcp-connector&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'mcp-listeo-connector'); ?></a>
                <a href="?page=mcp-connector&tab=abilities" class="nav-tab <?php echo $active_tab == 'abilities' ? 'nav-tab-active' : ''; ?>"><?php _e('Habilidades', 'mcp-listeo-connector'); ?></a>
                <a href="?page=mcp-connector&tab=prompts" class="nav-tab <?php echo $active_tab == 'prompts' ? 'nav-tab-active' : ''; ?>">💡 <?php _e('Prompt para IAs', 'mcp-listeo-connector'); ?></a>
                <a href="?page=mcp-connector&tab=telegram" class="nav-tab <?php echo $active_tab == 'telegram' ? 'nav-tab-active' : ''; ?>"><?php _e('AI & Telegram', 'mcp-listeo-connector'); ?></a>
                <a href="?page=mcp-agent-chat" class="nav-tab"><?php _e('Chat Agent IA 🤖', 'mcp-listeo-connector'); ?></a>
                <a href="?page=mcp-connector&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>"><?php _e('Logs', 'mcp-listeo-connector'); ?></a>
            </h2>

            <div id="mcp-settings-tabs" style="margin-top: 20px;">
                <form method="post" action="options.php">
                    <?php settings_fields( 'mcp_settings_group' ); ?>
                    
                    <?php if ( 'general' === $active_tab ) : ?>
                        <!-- GENERAL TAB -->
                        <div class="mcp-settings-row">
                            <div class="card mcp-card-half">
                                <h2><?php _e( 'Security Configuration', 'mcp-listeo-connector' ); ?></h2>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php _e( 'X-MCP-Token', 'mcp-listeo-connector' ); ?></th>
                                        <td>
                                            <input type="text" name="mcp_security_token" value="<?php echo esc_attr( $token ); ?>" class="regular-text" />
                                            <p class="description"><?php _e( 'Usado para autenticar las llamadas REST.', 'mcp-listeo-connector' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php _e( 'Desactivar Seguridad', 'mcp-listeo-connector' ); ?></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="mcp_disable_security" value="yes" <?php checked( $disable_security, 'yes' ); ?> />
                                                <?php _e( 'Permitir conexiones sin token de seguridad (Útil si OpenClaw tiene problemas con la autenticación).', 'mcp-listeo-connector' ); ?>
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php _e( 'REST Endpoint', 'mcp-listeo-connector' ); ?></th>
                                        <td>
                                            <code><?php echo esc_url( get_rest_url( null, 'mcp/v1/call' ) ); ?></code>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <?php
                            $active_count = 0;
                            foreach ( $abilities as $id => $ability ) {
                                if ( in_array( $id, $enabled ) || empty( $enabled ) ) { $active_count++; }
                            }
                            $endpoint_healthy = true;
                            ?>

                            <div class="mcp-card-half">
                                <h2><?php _e( 'Resumen del Sistema', 'mcp-listeo-connector' ); ?></h2>
                                <div class="mcp-dashboard-grid" style="margin: 0 !important;">
                                    <div class="mcp-stat-card" style="min-width: 150px;">
                                        <div class="mcp-stat-info">
                                            <h4><?php _e( 'Habilidades', 'mcp-listeo-connector' ); ?></h4>
                                            <span class="mcp-stat-value"><?php echo count( $abilities ); ?></span>
                                        </div>
                                    </div>
                                    <div class="mcp-stat-card" style="min-width: 150px;">
                                        <div class="mcp-stat-info">
                                            <h4><?php _e( 'Estado', 'mcp-listeo-connector' ); ?></h4>
                                            <span class="mcp-stat-value">
                                                <span class="mcp-stat-status status-online">OK</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <p style="margin-top: 20px;">
                                    <a href="?page=mcp-agent-chat" class="button button-primary button-hero" style="width: 100%; text-align: center; height: auto; padding: 10px;">
                                        🚀 <?php _e( 'Abrir Chat de Agente IA', 'mcp-listeo-connector' ); ?>
                                    </a>
                                </p>
                            </div>
                        </div>

                    <?php elseif ( 'abilities' === $active_tab ) : ?>
                        <!-- ABILITIES TAB -->
                        <h2><?php _e( 'Habilidades Disponibles', 'mcp-listeo-connector' ); ?></h2>
                        <p><?php _e( 'Configura qué herramientas puede usar el Agente IA y define instrucciones personalizadas para cada una.', 'mcp-listeo-connector' ); ?></p>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 50px;"><?php _e( 'Activo', 'mcp-listeo-connector' ); ?></th>
                                    <th><?php _e( 'Capacidad', 'mcp-listeo-connector' ); ?></th>
                                    <th><?php _e( 'Instrucciones Personalizadas (Prompt)', 'mcp-listeo-connector' ); ?></th>
                                    <th style="width: 100px;"><?php _e( 'Acción', 'mcp-listeo-connector' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $abilities as $id => $ability ) : ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="mcp_enabled_abilities[]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, $enabled ) || empty($enabled) ); ?> />
                                        </td>
                                        <td>
                                            <strong><?php echo esc_html( $ability['title'] ); ?></strong><br/>
                                            <code><?php echo esc_html( $id ); ?></code><br/>
                                            <small><?php echo esc_html( $ability['description'] ); ?></small>
                                        </td>
                                        <td>
                                            <textarea name="mcp_prompts[<?php echo esc_attr($id); ?>]" class="large-text" rows="2" placeholder="<?php echo esc_attr( !empty($ability['example_prompt']) ? $ability['example_prompt'] : __( 'Ej: Instrucciones específicas...', 'mcp-listeo-connector' ) ); ?>"><?php echo esc_textarea( $prompts[$id] ?? '' ); ?></textarea>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-small mcp-test-ability" data-ability="<?php echo esc_attr($id); ?>">
                                                <?php _e( 'Probar', 'mcp-listeo-connector' ); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <?php elseif ( 'telegram' === $active_tab ) : ?>
                        <!-- TELEGRAM TAB -->
                        <div class="card" style="max-width: 800px;">
                            <h2><?php _e( 'Configuración de IA y Telegram', 'mcp-listeo-connector' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e( 'Proveedor de IA', 'mcp-listeo-connector' ); ?></th>
                                    <td>
                                        <select name="mcp_ai_provider" id="mcp_ai_provider" class="regular-text">
                                            <option value="gemini" <?php selected( $ai_provider, 'gemini' ); ?>>Google Gemini (Recomendado)</option>
                                            <option value="openai" <?php selected( $ai_provider, 'openai' ); ?>>OpenAI (GPT-4)</option>
                                            <option value="groq" <?php selected( $ai_provider, 'groq' ); ?>>Groq (Ultra Rápido)</option>
                                            <option value="openrouter" <?php selected( $ai_provider, 'openrouter' ); ?>>OpenRouter</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e( 'API Key', 'mcp-listeo-connector' ); ?></th>
                                    <td>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="password" name="mcp_ai_api_key" id="mcp_ai_api_key" value="<?php echo esc_attr( $ai_api_key ); ?>" class="regular-text" />
                                            <button type="button" id="mcp-connect-ai" class="button button-secondary">
                                                <span class="dashicons dashicons-admin-links" style="vertical-align: middle;"></span> <?php _e( 'Conectar', 'mcp-listeo-connector' ); ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="mcp-model-row" <?php echo empty($ai_api_key) ? 'style="display:none;"' : ''; ?>>
                                    <th scope="row"><?php _e( 'Modelo Seleccionado', 'mcp-listeo-connector' ); ?></th>
                                    <td>
                                        <select name="mcp_ai_model" id="mcp_ai_model" class="regular-text">
                                            <?php if ( ! empty( get_option( 'mcp_ai_model' ) ) ) : ?>
                                                <option value="<?php echo esc_attr( get_option( 'mcp_ai_model' ) ); ?>"><?php echo esc_html( get_option( 'mcp_ai_model' ) ); ?></option>
                                            <?php else : ?>
                                                <option value=""><?php _e( 'Haz clic en Conectar para listar modelos...', 'mcp-listeo-connector' ); ?></option>
                                            <?php endif; ?>
                                        </select>
                                        <p class="description"><?php _e( 'Selecciona el cerebro que usará el agente.', 'mcp-listeo-connector' ); ?></p>
                                    </td>
                                </tr>
                                <tr><td colspan="2"><hr/></td></tr>
                                <tr>
                                    <th scope="row"><?php _e( 'Telegram Token', 'mcp-listeo-connector' ); ?></th>
                                    <td>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="text" name="mcp_telegram_token" id="mcp_telegram_token" value="<?php echo esc_attr( $telegram_token ); ?>" class="regular-text" placeholder="123456:ABC..." />
                                            <button type="button" id="mcp-connect-telegram" class="button button-secondary">
                                                <span class="dashicons dashicons-megaphone" style="vertical-align: middle;"></span> <?php _e( 'Conectar', 'mcp-listeo-connector' ); ?>
                                            </button>
                                        </div>
                                        <p class="description"><?php _e( 'Token obtenido de @BotFather.', 'mcp-listeo-connector' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e( 'Telegram Chat ID', 'mcp-listeo-connector' ); ?></th>
                                    <td>
                                        <input type="text" name="mcp_telegram_chat_id" id="mcp_telegram_chat_id" value="<?php echo esc_attr( $telegram_chat_id ); ?>" class="regular-text" placeholder="-100..." />
                                        <p class="description"><?php _e( 'ID del grupo o chat para notificaciones.', 'mcp-listeo-connector' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e( 'Retraso de Lote (s)', 'mcp-listeo-connector' ); ?></th>
                                    <td>
                                        <input type="number" name="mcp_ai_batch_delay" value="<?php echo esc_attr( $batch_delay ); ?>" class="small-text" />
                                        <span class="description"><?php _e( 'Segundos entre publicaciones automáticas.', 'mcp-listeo-connector' ); ?></span>
                                    </td>
                                </tr>
                            </table>
                            
                            <div style="margin-top: 20px; padding: 15px; background: #f0f6fb; border-left: 4px solid #2271b1;">
                                <strong>💡 <?php _e( 'Prueba de Conexión', 'mcp-listeo-connector' ); ?></strong><br/>
                                <?php _e( 'Copia tu API Key o Token y haz clic en "Conectar" para verificar que el sistema puede comunicarse con los servicios externos.', 'mcp-listeo-connector' ); ?>
                            </div>
                        </div>

                    <?php elseif ( 'prompts' === $active_tab ) : ?>
                        <!-- PROMPTS TAB -->
                        <h2>💡 <?php _e( 'Instrucciones del Sistema para Agentes de IA (System Prompt)', 'mcp-listeo-connector' ); ?></h2>
                        <p><?php _e( 'Copia este System Prompt y pégalo en la configuración de tu agente en Claude Desktop, ChatGPT (Custom GPT), Cursor o Antigravity para que sepa exactamente cómo utilizar las herramientas de este WordPress.', 'mcp-listeo-connector' ); ?></p>

                        <?php
                        $rest_base = esc_url( get_rest_url( null, 'mcp-listeo/v1' ) );
                        $rpc_url   = esc_url( get_rest_url( null, 'mcp-listeo/v1/rpc' ) );
                        $schema_url= esc_url( get_rest_url( null, 'mcp-listeo/v1/schema' ) );
                        
                        $system_prompt_text = "Eres el Agente Oficial de Gestión para el directorio Listeo y Marketplace en " . get_bloginfo('name') . " (" . home_url() . ").

Tu misión es asistir en la administración de anuncios, reservas, auditoría de salud de contenidos y consultas del directorio.

### CREDENCIALES Y ENDPOINTS MCP:
- URL Base REST: {$rest_base}
- Endpoint RPC JSON-RPC 2.0: {$rpc_url}
- Esquema OpenAPI: {$schema_url}
- Cabecera de autenticación requerida: X-MCP-Token: {$token}

### HABILIDADES (ABILITIES) PRINCIPALES:
1. listeo/audit-listings:
   - Usa esta herramienta para detectar anuncios incompletos (sin coordenadas GPS, sin fotos de galería, sin horarios de apertura o sin teléfono).
   - Parámetros: 'missing' (any, gps, images, hours, phone), 'limit' (número), 'category' (slug), 'status' (publish, pending).

2. listeo/list-bookings:
   - Consulta el historial y estado de reservas de clientes.
   - Parámetros: 'listing_id' (ID del anuncio), 'status' (pending, approved, paid, cancelled).

3. listeo/update-booking-status:
   - Modifica el estado de una reserva (aprobar, cancelar, marcar pagada).
   - Parámetros: 'booking_id' (ID obligatorio), 'status' (pending, approved, paid, cancelled, deleted).

4. listeo/create-listing:
   - Crea un nuevo anuncio en el directorio con título, descripción HTML enriquecida, dirección, coordenadas, precio, fotos y categorías.

5. listeo/update-listing:
   - Actualiza un anuncio existente (título, contenido, teléfono, web, horarios '_opening_hours', redes sociales, fotos, amenities 'features' y 'region').
   - Parámetros: 'id' (ID del anuncio obligatorio).

6. listeo/get-listings:
   - Busca y filtra anuncios existentes con soporte de paginación.

### REGLAS DE CONDUCTA Y FORMATO:
- En la creación o edición de contenido turístico, utiliza descripciones atractivas con formato HTML y emojis moderados para estructurar secciones (🎯 Por qué visitar, 📜 Historia, 🏆 Imperdibles, 🕐 Horarios, 💡 Consejos).
- Siempre verifica las coordenadas GPS antes de guardar un anuncio para asegurar su visualización en el mapa de Listeo.
- Al reportar auditorías, resume claramente la cantidad de anuncios afectados y propón acciones de corrección.";
                        ?>

                        <div class="card" style="max-width: 100%; padding: 20px; background: #fff;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h3 style="margin: 0; font-size: 16px;">📋 <?php _e( 'Prompt Maestro (Listo para Copiar)', 'mcp-listeo-connector' ); ?></h3>
                                <button type="button" class="button button-primary" onclick="navigator.clipboard.writeText(document.getElementById('mcp-ai-prompt-box').value); alert('¡Prompt copiado al portapapeles!');">
                                    📋 <?php _e( 'Copiar al Portapapeles', 'mcp-listeo-connector' ); ?>
                                </button>
                            </div>
                            <textarea id="mcp-ai-prompt-box" rows="18" class="large-text code" readonly style="background: #fbfbfb; font-family: monospace; font-size: 13px; line-height: 1.5; padding: 12px;"><?php echo esc_textarea( $system_prompt_text ); ?></textarea>
                            
                            <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                                <a href="<?php echo esc_url($schema_url); ?>" target="_blank" class="button button-secondary">
                                    🌐 <?php _e( 'Ver Esquema OpenAPI (JSON)', 'mcp-listeo-connector' ); ?>
                                </a>
                                <a href="<?php echo esc_url(rest_url('mcp-listeo/v1/tools')); ?>" target="_blank" class="button button-secondary">
                                    🛠️ <?php _e( 'Ver Herramientas /tools (JSON)', 'mcp-listeo-connector' ); ?>
                                </a>
                            </div>
                        </div>

                    <?php elseif ( 'logs' === $active_tab ) : ?>
                        <!-- LOGS TAB -->
                        <h2><?php _e( 'Registros de Auditoría', 'mcp-listeo-connector' ); ?></h2>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 150px;"><?php _e( 'Fecha', 'mcp-listeo-connector' ); ?></th>
                                    <th style="width: 200px;"><?php _e( 'Acción', 'mcp-listeo-connector' ); ?></th>
                                    <th><?php _e( 'Detalles (Argumentos)', 'mcp-listeo-connector' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( empty( $logs ) ) : ?>
                                    <tr><td colspan="3"><?php _e( 'Sin actividad registrada.', 'mcp-listeo-connector' ); ?></td></tr>
                                <?php else : ?>
                                    <?php foreach ( array_reverse($logs) as $log ) : ?>
                                        <tr>
                                            <td><?php echo esc_html( $log['timestamp'] ); ?></td>
                                            <td><code><?php echo esc_html( $log['ability'] ); ?></code></td>
                                            <td><small><code><?php echo esc_html( $log['args'] ); ?></code></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <?php if ( 'logs' !== $active_tab && 'prompts' !== $active_tab ) submit_button(); ?>

                    <!-- Mantener los campos ocultos de otras pestañas para no perder datos al guardar -->
                    <?php if ( 'general' !== $active_tab ) : ?>
                        <input type="hidden" name="mcp_security_token" value="<?php echo esc_attr( $token ); ?>" />
                        <input type="hidden" name="mcp_disable_security" value="<?php echo esc_attr( $disable_security ); ?>" />
                    <?php endif; ?>
                    <?php if ( 'abilities' !== $active_tab ) : ?>
                        <?php foreach ( (array)$enabled as $val ) : ?>
                            <input type="hidden" name="mcp_enabled_abilities[]" value="<?php echo esc_attr( $val ); ?>" />
                        <?php endforeach; ?>
                        <?php foreach ( (array)$prompts as $key => $val ) : ?>
                            <input type="hidden" name="mcp_prompts[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr( $val ); ?>" />
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ( 'telegram' !== $active_tab ) : ?>
                        <input type="hidden" name="mcp_ai_provider" value="<?php echo esc_attr( $ai_provider ); ?>" />
                        <input type="hidden" name="mcp_ai_api_key" value="<?php echo esc_attr( $ai_api_key ); ?>" />
                        <input type="hidden" name="mcp_ai_model" value="<?php echo esc_attr( get_option('mcp_ai_model') ); ?>" />
                        <input type="hidden" name="mcp_telegram_token" value="<?php echo esc_attr( $telegram_token ); ?>" />
                        <input type="hidden" name="mcp_telegram_chat_id" value="<?php echo esc_attr( $telegram_chat_id ); ?>" />
                        <input type="hidden" name="mcp_ai_batch_delay" value="<?php echo esc_attr( $batch_delay ); ?>" />
                    <?php endif; ?>
                </form>
            </div>
		</div>
        </div>
		</div>

        <!-- Integration Test Modal -->
        <div id="mcp-test-modal" class="mcp-modal" style="display:none;">
            <div class="mcp-modal-content">
                <span class="mcp-close">&times;</span>
                <h3><?php _e( 'Test Ability:', 'mcp-listeo-connector' ); ?> <span id="mcp-test-id"></span></h3>
                <div class="mcp-test-split">
                    <div class="mcp-test-input">
                        <label><?php _e( 'Arguments (JSON object):', 'mcp-listeo-connector' ); ?></label>
                        <textarea id="mcp-test-args" rows="10" class="large-text">{&#10;  "query": "Hotel"&#10;}</textarea>
                        <button type="button" id="mcp-run-test" class="button button-primary"><?php _e( 'Execute REST Call', 'mcp-listeo-connector' ); ?></button>
                    </div>
                    <div class="mcp-test-output">
                        <label><?php _e( 'Raw API Response:', 'mcp-listeo-connector' ); ?></label>
                        <pre id="mcp-test-result">Waiting for input...</pre>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}
}
