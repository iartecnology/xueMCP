<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: utils/upload-file
 * Allows sending raw file content (base64) to the server's temp directory.
 */
MCP_Registry::register_ability( array(
	'id'          => 'utils/upload-file',
	'title'       => 'Upload File Content',
	'description' => 'Uploads a file to wp-content/mcp-temp/ using base64 encoded content.',
	'callback'    => function( $args ) {
		if ( empty( $args['filename'] ) || empty( $args['base64_content'] ) ) {
			throw new Exception( "Missing filename or content." );
		}

		$temp_dir = WP_CONTENT_DIR . '/mcp-temp/';
		if ( ! file_exists( $temp_dir ) ) {
			mkdir( $temp_dir, 0755, true );
		}

		$filename = sanitize_file_name( $args['filename'] );
		$file_path = $temp_dir . $filename;
		
		$data = base64_decode( $args['base64_content'] );
		if ( $data === false ) {
			throw new Exception( "Invalid base64 content." );
		}

		if ( file_put_contents( $file_path, $data ) ) {
			return array(
				'status' => 'success',
				'path'   => $file_path,
				'url'    => content_url( 'mcp-temp/' . $filename )
			);
		}

		throw new Exception( "Failed to write file to disk." );
	},
	'schema'      => array(
		'properties' => array(
			'filename'       => array( 'type' => 'string', 'required' => true ),
			'base64_content' => array( 'type' => 'string', 'required' => true, 'description' => 'Raw file content in base64 format.' ),
		)
	)
) );
