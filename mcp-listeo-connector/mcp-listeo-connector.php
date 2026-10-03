<?php
/**
 * Plugin Name: MCP Listeo Connector
 * Plugin URI: https://xueturismo.com
 * Description: Puente entre el protocolo MCP (Model Context Protocol) y el ecosistema Listeo + Dokan + WordPress.
 * Version: 1.2.0
 * Author: Antigravity
 * Author URI: https://deepmind.google
 * License: GPL2
 * Text Domain: mcp-listeo-connector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'MCP_LISTEO_CONNECTOR_VERSION' ) ) {
	define( 'MCP_LISTEO_CONNECTOR_VERSION', '1.2.0' );
}

/**
 * Main Plugin Class
 */
class MCP_Listeo_Connector {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'admin_notices', array( $this, 'dependency_notices' ) );
	}

	public function init() {
        // Ensure security token is initialized
        if ( ! get_option( 'mcp_security_token' ) ) {
            update_option( 'mcp_security_token', wp_generate_password( 32, false ) );
        }

		// Include helpers
		include_once plugin_dir_path( __FILE__ ) . 'includes/helpers/utils.php';
		include_once plugin_dir_path( __FILE__ ) . 'includes/helpers/wp-posts-api.php';
		
		if ( $this->is_listeo_active() ) {
			include_once plugin_dir_path( __FILE__ ) . 'includes/helpers/listeo-api.php';
		}

		if ( $this->is_dokan_active() ) {
			include_once plugin_dir_path( __FILE__ ) . 'includes/helpers/dokan-api.php';
		}

		include_once plugin_dir_path( __FILE__ ) . 'includes/mcp/mcp-registry.php';
		include_once plugin_dir_path( __FILE__ ) . 'includes/mcp/mcp-rest-api.php';
		include_once plugin_dir_path( __FILE__ ) . 'includes/admin/mcp-settings.php';
		
		// Include Services
		include_once plugin_dir_path( __FILE__ ) . 'includes/services/llm-connector.php';
		include_once plugin_dir_path( __FILE__ ) . 'includes/services/telegram-service.php';
		include_once plugin_dir_path( __FILE__ ) . 'includes/admin/mcp-agent-chat.php';

		new MCP_REST_API();

		if ( is_admin() ) {
			new MCP_Settings();
		}

		// Load Abilities
		$this->load_abilities();
	}

	public function is_listeo_active() {
		return class_exists( 'Listeo_Core' );
	}

	public function is_dokan_active() {
		return function_exists( 'dokan' ) || class_exists( 'WeDevs_Dokan' );
	}

	public function dependency_notices() {
		if ( ! $this->is_listeo_active() ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php _e( 'MCP Listeo Connector: Listeo Core no está activo. Las funcionalidades de Listeo estarán deshabilitadas.', 'mcp-listeo-connector' ); ?></p>
			</div>
			<?php
		}

		if ( ! $this->is_dokan_active() ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php _e( 'MCP Listeo Connector: Dokan no está activo. Las funcionalidades de Marketplace estarán deshabilitadas.', 'mcp-listeo-connector' ); ?></p>
			</div>
			<?php
		}
	}

	private function load_abilities() {
		$abilities_path = plugin_dir_path( __FILE__ ) . 'includes/abilities/';
		
		// WP Core Abilities (Always loaded)
		include_once $abilities_path . 'wp-get-post.php';
		include_once $abilities_path . 'wp-create-post.php';
		include_once $abilities_path . 'wp-update-post.php';
include_once $abilities_path . 'wp-delete-post.php';		include_once $abilities_path . 'wp-advanced-search.php';

		// Listeo Abilities
		if ( $this->is_listeo_active() ) {
			include_once $abilities_path . 'listeo-get-listings.php';
			include_once $abilities_path . 'listeo-update-listing.php';
			include_once $abilities_path . 'listeo-create-booking.php';
			include_once $abilities_path . 'listeo-manage-bookings.php';
			include_once $abilities_path . 'listeo-audit-listings.php';
			include_once $abilities_path . 'listeo-create-listing.php';
			include_once $abilities_path . 'listeo-list-listings.php';
			include_once $abilities_path . 'listeo-get-user-dashboard.php';
			include_once $abilities_path . 'listeo-get-listing-meta.php';
		}

		// Dokan Abilities
		if ( $this->is_dokan_active() ) {
			include_once $abilities_path . 'dokan-manage-store.php';
			include_once $abilities_path . 'dokan-manage-products.php';
		}
	}
}

// Start the plugin
MCP_Listeo_Connector::get_instance();
