<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MCP Utilities & Security
 */
class MCP_Utils {

	public static function sanitize_input( $data, $key = '' ) {
		if ( is_array( $data ) ) {
			$result = array();
			foreach ( $data as $k => $v ) {
				$result[ $k ] = self::sanitize_input( $v, $k );
			}
			return $result;
		}
		// Preserve HTML in content fields
		if ( in_array( $key, array( 'content', 'post_content', 'description', 'message' ), true ) ) {
			return wp_kses_post( $data );
		}
		return sanitize_text_field( $data );
	}

	public static function check_permission( $capability ) {
		if ( defined( 'MCP_REST_CALL' ) && MCP_REST_CALL ) {
			return true;
		}
		return current_user_can( $capability );
	}

	public static function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "[MCP-Connector] " . print_r( $message, true ) );
		}
	}

	public static function send_json_error( $message, $code = 400 ) {
		return array(
			'isError' => true,
			'message' => $message,
			'code'    => $code
		);
	}

	public static function send_json_success( $data ) {
		return array(
			'isError' => false,
			'data'    => $data
		);
	}
}
