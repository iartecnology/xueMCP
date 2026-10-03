<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: utils/upload-chunk
 * Allows sending file content in small chunks to avoid WAF limits.
 */
MCP_Registry::register_ability( array(
	'id'          => 'utils/upload-chunk',
	'title'       => 'Upload File Chunk',
	'description' => 'Appends base64 content to a file in wp-content/mcp-temp/.',
	'callback'    => function( $args ) {
		if ( empty( $args['filename'] ) || !isset( $args['base64_chunk'] ) ) {
			throw new Exception( "Missing filename or chunk." );
		}

		$temp_dir = WP_CONTENT_DIR . '/mcp-temp/';
		if ( ! file_exists( $temp_dir ) ) {
			mkdir( $temp_dir, 0755, true );
		}

		$filename = sanitize_file_name( $args['filename'] );
		$file_path = $temp_dir . $filename;
		
		$data = base64_decode( $args['base64_chunk'] );
		if ( $data === false ) {
			throw new Exception( "Invalid base64 chunk." );
		}

		// Append mode
		if ( file_put_contents( $file_path, $data, FILE_APPEND ) !== false ) {
			return array(
				'status' => 'success',
				'current_size' => filesize( $file_path )
			);
		}

		throw new Exception( "Failed to write chunk." );
	}
) );
