<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MCP Registry for Abilities
 */
class MCP_Registry {

	private static $abilities = array();

	/**
	 * Register an MCP ability
	 * 
	 * @param array $args {
	 *    ID: string (Required), 
	 *    title: string (Required),
	 *    description: string,
	 *    scope: string (WP Capability),
	 *    callback: callable (Required),
	 *    schema: array (Optional)
	 * }
	 */
	public static function register_ability( $args ) {
		if ( empty( $args['id'] ) || empty( $args['callback'] ) ) {
			return false;
		}

		self::$abilities[ $args['id'] ] = array(
			'id'              => $args['id'],
			'title'           => $args['title'] ?? $args['id'],
			'description'     => $args['description'] ?? '',
			'example_prompt'  => $args['example_prompt'] ?? '',
			'scope'           => $args['scope'] ?? 'read',
			'callback'        => $args['callback'],
			'schema'          => $args['schema'] ?? array()
		);

		return true;
	}

	public static function get_all() {
		return self::$abilities;
	}

	public static function get_enabled_abilities() {
		$enabled = get_option( 'mcp_enabled_abilities', array() );
		if ( empty( $enabled ) ) {
			return self::$abilities;
		}

		$result = array();
		foreach ( self::$abilities as $id => $ability ) {
			if ( in_array( $id, $enabled ) ) {
				$result[ $id ] = $ability;
			}
		}
		return $result;
	}

	public static function is_enabled( $id ) {
		$enabled = get_option( 'mcp_enabled_abilities', array() );
		if ( empty( $enabled ) ) {
			return true; // Default to all if not set
		}
		return in_array( $id, $enabled );
	}

	public static function get_by_id( $id ) {
		return self::$abilities[ $id ] ?? null;
	}

	/**
	 * Call an MCP ability
	 */
	public static function call( $id, $args ) {
		$ability = self::get_by_id( $id );

		if ( ! $ability ) {
			return MCP_Utils::send_json_error( "Ability not found: $id", 404 );
		}

		if ( ! self::is_enabled( $id ) ) {
			return MCP_Utils::send_json_error( "Ability '$id' is disabled in settings.", 403 );
		}

		// Security check
		if ( ! MCP_Utils::check_permission( $ability['scope'] ) ) {
			return MCP_Utils::send_json_error( "Permission denied for $id", 403 );
		}

		// Sanitization
		$clean_args = MCP_Utils::sanitize_input( $args );

		try {
			$result = call_user_func( $ability['callback'], $clean_args );
			return MCP_Utils::send_json_success( $result );
		} catch ( Exception $e ) {
			return MCP_Utils::send_json_error( $e->getMessage() );
		}
	}
}
