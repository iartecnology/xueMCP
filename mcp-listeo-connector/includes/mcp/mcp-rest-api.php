<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MCP REST API Endpoint
 */
class MCP_REST_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_filter( 'determine_current_user', array( $this, 'authenticate_mcp_user' ), 15 );
	}

	/**
	 * Global Authentication via Token
	 */
	public function authenticate_mcp_user( $user_id ) {
		// Only run if it's an MCP REST request
		if ( false === strpos( $_SERVER['REQUEST_URI'], 'mcp-listeo/v1' ) ) {
			return $user_id;
		}

		$auth_header = $_SERVER['HTTP_X_MCP_TOKEN'] ?? '';
		$saved_token = get_option( 'mcp_security_token' );

		if ( ! empty( $auth_header ) && $auth_header === $saved_token ) {
			// Return Admin User ID (1)
			return 1;
		}

		return $user_id;
	}

	public function register_routes() {
		register_rest_route( 'mcp-listeo/v1', '/tools', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'handle_list_tools' ),
			'permission_callback' => array( $this, 'check_token_permission' ),
		) );

		register_rest_route( 'mcp-listeo/v1', '/call', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_call' ),
			'permission_callback' => array( $this, 'check_token_permission' ),
		) );

		register_rest_route( 'mcp-listeo/v1', '/chat', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_chat' ),
			'permission_callback' => function() { return current_user_can('manage_options'); },
		) );

		register_rest_route( 'mcp-listeo/v1', '/auto-create', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_auto_create' ),
			'permission_callback' => function() { return current_user_can('manage_options'); },
		) );

		register_rest_route( 'mcp-listeo/v1', '/models', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'handle_get_models' ),
			'permission_callback' => function() { return current_user_can('manage_options'); },
		) );

		register_rest_route( 'mcp-listeo/v1', '/test-telegram', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'handle_test_telegram' ),
			'permission_callback' => function() { return current_user_can('manage_options'); },
		) );

		register_rest_route( 'mcp-listeo/v1', '/schema', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'handle_get_openapi_schema' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'mcp-listeo/v1', '/rpc', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_json_rpc' ),
			'permission_callback' => array( $this, 'check_token_permission' ),
		) );
	}

	/**
	 * Security Check: Verify MCP Token
	 */
	public function check_token_permission( $request ) {
		$auth_header = $request->get_header( 'X-MCP-Token' );
		$saved_token = get_option( 'mcp_security_token' );
		$disable_security = get_option( 'mcp_disable_security', 'no' );

		if ( 'yes' === $disable_security ) {
			return true;
		}

		if ( empty( $saved_token ) ) {
			// If no token is set, we don't allow ANY requests for security
			return new WP_Error( 'mcp_security_no_token', 'No security token configured in settings.', array( 'status' => 403 ) );
		}

		if ( $auth_header !== $saved_token ) {
			return new WP_Error( 'mcp_invalid_token', 'Invalid MCP Token.', array( 'status' => 403 ) );
		}

		// Authenticate as Administrator (ID 1) for the duration of this request
		// This allows the agent to perform administrative actions via MCP
		if ( ! is_user_logged_in() || get_current_user_id() !== 1 ) {
			wp_set_current_user( 1 );
			error_log( "[MCP-Connector] Authenticated as Admin via token." );
		}

		return true;
	}

	/**
	 * List all dynamic abilities
	 */
	public function handle_list_tools( $request ) {
		$abilities = MCP_Registry::get_all();
        $tools = array();
        
        foreach ( $abilities as $id => $data ) {
            $tools[] = array(
                'name'        => $id,
                'description' => $data['description'] ?? '',
                'inputSchema' => $data['schema'] ?? array( 'type' => 'object', 'properties' => (object) array() )
            );
        }
        
		return rest_ensure_response( array( 'tools' => $tools ) );
	}

	/**
	 * Main Callback for MCP Calls
	 */
	public function handle_call( $request ) {
		if ( ! defined( 'MCP_REST_CALL' ) ) {
			define( 'MCP_REST_CALL', true );
		}
		
		// Force Administrator (ID 1) authentication for the call
		wp_set_current_user( 1 );

		$params = $request->get_json_params();
		$ability_id = $params['ability'] ?? '';
		$args = $params['args'] ?? array();

		if ( empty( $ability_id ) ) {
			return MCP_Utils::send_json_error( "Missing 'ability' parameter.", 400 );
		}

		// Log the attempt
		$this->log_mcp_action( $ability_id, $args );

		return MCP_Registry::call( $ability_id, $args );
	}

	/**
	 * Main Callback for Chat Capabilities
	 */
	public function handle_chat( $request ) {
		$params  = $request->get_json_params();
		$message = $params['message'] ?? '';
		$history = $params['history'] ?? array();

		if ( empty( $message ) ) {
			return new WP_Error( 'empty_message', 'Message cannot be empty.', array( 'status' => 400 ) );
		}

		if ( class_exists( 'MCP_LLM_Connector' ) ) {
			$llm = MCP_LLM_Connector::get_instance();
			$reply = $llm->chat( $message, $history );
			
			if ( is_wp_error( $reply ) ) {
			    return $reply;
			}
			
			return rest_ensure_response( array( 'reply' => $reply ) );
		}

		return new WP_Error( 'no_llm', 'LLM Service not available.', array( 'status' => 500 ) );
	}

	/**
	 * Callback for Auto-Creation Flow (Batch Items)
	 */
	public function handle_auto_create( $request ) {
		set_time_limit( 300 ); // Allow up to 5 minutes for AI generation + Image processing
		$params   = $request->get_json_params();
		$title    = sanitize_text_field( $params['title'] ?? '' );
		$category = $request->get_param( 'category' );
		$region   = $request->get_param( 'region' );

		if ( empty( $title ) ) {
			return new WP_Error( 'empty_title', 'Title is required.', array( 'status' => 400 ) );
		}

		if ( class_exists( 'MCP_LLM_Connector' ) && class_exists( 'MCP_Registry' ) ) {
			$llm = MCP_LLM_Connector::get_instance();
			
			// Generate full content from AI
			$generated_data = $llm->generate_full_content( $title, $category );
			
			if ( is_wp_error( $generated_data ) ) {
				return $generated_data;
			}

			// Parse just in case it is a JSON string
			if ( is_string( $generated_data ) ) {
				$generated_data = json_decode( $generated_data, true );
			}

            // Fallback object struct
            $content = $generated_data['content'] ?? "Descripción automática para $title.";
            $address = sanitize_text_field( $generated_data['address'] ?? 'Ubicación central' );
            $lat     = floatval( $generated_data['lat'] ?? 0 );
            $lng     = floatval( $generated_data['lng'] ?? 0 );
            $price   = sanitize_text_field( $generated_data['price'] ?? '' );
            $kwords  = sanitize_text_field( $generated_data['keywords'] ?? '' );
            $img_q   = urlencode( str_replace(' ', ',', $generated_data['image_search_query'] ?? $title ) );
            $seed    = rand(1, 9999);

            // Resolve category slug to term ID (listing_category taxonomy)
            $category_ids = array();
            $cat_term = get_term_by( 'slug', $category, 'listing_category' );
            if ( ! $cat_term ) {
                // Try name match as fallback
                $cat_term = get_term_by( 'name', $category, 'listing_category' );
            }
            if ( $cat_term && ! is_wp_error( $cat_term ) ) {
                $category_ids = array( $cat_term->term_id );
            }

            // Resolve region slug to term ID
            $region_ids = array();
            if ( ! empty( $region ) ) {
                $reg_term = get_term_by( 'slug', $region, 'region' );
                if ( ! $reg_term ) {
                    $reg_term = get_term_by( 'name', $region, 'region' );
                }
                if ( $reg_term && ! is_wp_error( $reg_term ) ) {
                    $region_ids = array( $reg_term->term_id );
                }
            }

			// Map AI output to create_listing API arguments
			// NOTE: 'content' maps to post_content in wp_insert_post
			$args = array(
				'title'     => $title,
				'content'   => wp_kses_post( $content ),
				'address'   => $address,
                'lat'       => $lat,
                'lng'       => $lng,
				'price'     => $price,
				'keywords'  => $kwords,
				'image_url' => "https://source.unsplash.com/featured/1200x800/?$img_q&sig=$seed",
				'status'    => 'publish',
			);
            if ( ! empty( $category_ids ) ) {
                $args['category'] = $category_ids;
            }
            if ( ! empty( $region_ids ) ) {
                $args['region'] = $region_ids;
            }

			// Execute via Registry (handles logging)
			$registry_result = MCP_Registry::call( 'listeo/create-listing', $args );
            
            // Unwrap data from Registry response
            $result = isset($registry_result['data']) ? $registry_result['data'] : $registry_result;

			// Send Telegram Notification on success
			if ( class_exists('MCP_Telegram_Service') && isset($result['id']) ) {
				$telegram = MCP_Telegram_Service::get_instance();
				$telegram->send_message( "✅ <b>Listado Publicado Automáticamente:</b> $title\n📍 {$address}\nID: {$result['id']}\n🔗 {$result['link']}" );
			}

			return rest_ensure_response( $result );
		}

		return new WP_Error( 'system_error', 'Missing internal components.', array( 'status' => 500 ) );
	}

	/**
	 * Fetch available models for an AI provider
	 */
	public function handle_get_models( $request ) {
		$provider = $request->get_param( 'provider' );
		$api_key  = $request->get_param( 'api_key' );

		if ( empty( $provider ) || empty( $api_key ) ) {
			return new WP_Error( 'missing_params', 'Provider and API Key are required.', array( 'status' => 400 ) );
		}

		if ( class_exists( 'MCP_LLM_Connector' ) ) {
			$llm = MCP_LLM_Connector::get_instance();
			$models = $llm->fetch_available_models( $provider, $api_key );
			
			if ( is_wp_error( $models ) ) {
				return $models;
			}

			return rest_ensure_response( array( 'models' => $models ) );
		}

		return new WP_Error( 'no_llm', 'LLM Service not available.', array( 'status' => 500 ) );
	}

	/**
	 * Test Telegram connection
	 */
	public function handle_test_telegram( $request ) {
		$token   = $request->get_param( 'token' );
		$chat_id = $request->get_param( 'chat_id' );

		if ( empty( $token ) ) {
			return new WP_Error( 'missing_token', 'Telegram token is required.', array( 'status' => 400 ) );
		}

		if ( class_exists( 'MCP_Telegram_Service' ) ) {
			$telegram = MCP_Telegram_Service::get_instance();
			$result = $telegram->test_connection( $token, $chat_id );
			
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response( array( 
                'success' => true, 
                'message' => __('¡Conexión Exitosa! Bot detectado.', 'mcp-listeo-connector') ,
                'bot_info' => $result
            ) );
		}

		return new WP_Error( 'no_telegram', 'Telegram Service not available.', array( 'status' => 500 ) );
	}

	/**
	 * Basic Audit Logging
	 */
	private function log_mcp_action( $id, $args ) {
		$logs = get_option( 'mcp_audit_logs', array() );
		$new_log = array(
			'timestamp' => current_time( 'mysql' ),
			'ability'   => $id,
			'args'      => json_encode( $args ),
			'user_id'   => get_current_user_id()
		);

		array_unshift( $logs, $new_log );
		$logs = array_slice( $logs, 0, 50 ); // Keep last 50
		update_option( 'mcp_audit_logs', $logs );
	}

	/**
	 * Serve OpenAPI / Tools schema for external connectors (ChatGPT Actions / Claude)
	 */
	public function handle_get_openapi_schema( $request ) {
		$abilities = MCP_Registry::get_all();
		$paths = array();

		foreach ( $abilities as $id => $data ) {
			$operation_id = str_replace( array( '/', '-' ), '_', $id );
			$paths['/' . $id] = array(
				'post' => array(
					'summary'     => $data['title'] ?? $id,
					'description' => $data['description'] ?? '',
					'operationId' => $operation_id,
					'requestBody' => array(
						'required' => true,
						'content'  => array(
							'application/json' => array(
								'schema' => $data['schema'] ?? array( 'type' => 'object', 'properties' => (object) array() ),
							),
						),
					),
					'responses'   => array(
						'200' => array(
							'description' => 'Successful execution response',
						),
					),
				),
			);
		}

		$schema = array(
			'openapi' => '3.0.0',
			'info'    => array(
				'title'       => 'Listeo MCP Connector API',
				'version'     => '1.1.0',
				'description' => 'Direct MCP tool abilities for Listeo WordPress directory and bookings.',
			),
			'servers' => array(
				array( 'url' => esc_url( rest_url( 'mcp-listeo/v1' ) ) ),
			),
			'paths'   => $paths,
		);

		return rest_ensure_response( $schema );
	}

	/**
	 * Standard JSON-RPC 2.0 endpoint for MCP protocol clients
	 */
	public function handle_json_rpc( $request ) {
		$params = $request->get_json_params();
		$rpc_id = $params['id'] ?? 1;
		$method = $params['method'] ?? '';
		$rpc_params = $params['params'] ?? array();

		if ( 'tools/list' === $method ) {
			$tools = array();
			foreach ( MCP_Registry::get_all() as $id => $data ) {
				$tools[] = array(
					'name'        => $id,
					'description' => $data['description'] ?? '',
					'inputSchema' => $data['schema'] ?? array( 'type' => 'object', 'properties' => (object) array() ),
				);
			}
			return rest_ensure_response( array(
				'jsonrpc' => '2.0',
				'id'      => $rpc_id,
				'result'  => array( 'tools' => $tools ),
			) );
		}

		if ( 'tools/call' === $method ) {
			$name = $rpc_params['name'] ?? '';
			$args = $rpc_params['arguments'] ?? array();

			if ( empty( $name ) ) {
				return rest_ensure_response( array(
					'jsonrpc' => '2.0',
					'id'      => $rpc_id,
					'error'   => array( 'code' => -32602, 'message' => 'Invalid params: tool name required' ),
				) );
			}

			try {
				wp_set_current_user( 1 );
				$this->log_mcp_action( $name, $args );
				$result = MCP_Registry::call( $name, $args );

				return rest_ensure_response( array(
					'jsonrpc' => '2.0',
					'id'      => $rpc_id,
					'result'  => array(
						'content' => array(
							array(
								'type' => 'text',
								'text' => is_string( $result ) ? $result : wp_json_encode( $result ),
							),
						),
					),
				) );
			} catch ( Exception $e ) {
				return rest_ensure_response( array(
					'jsonrpc' => '2.0',
					'id'      => $rpc_id,
					'error'   => array( 'code' => -32000, 'message' => $e->getMessage() ),
				) );
			}
		}

		return rest_ensure_response( array(
			'jsonrpc' => '2.0',
			'id'      => $rpc_id,
			'error'   => array( 'code' => -32601, 'message' => 'Method not found' ),
		) );
	}
}
