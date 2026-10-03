<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: listeo/delete-listing
 */
MCP_Registry::register_ability( array(
	'id'          => 'listeo/delete-listing',
	'title'       => 'Delete a Listing',
	'description' => 'Move a listing to trash or permanently delete it. Requires edit permissions on the listing.',
	'example_prompt' => 'Borra el listing con ID 4744.',
	'scope'       => 'delete_posts',
	'callback'    => function( $args ) {
		if ( empty( $args['id'] ) ) {
			throw new Exception( "Missing 'id' parameter." );
		}

		$post_id = (int) $args['id'];
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'listing' ) {
			throw new Exception( "Listing with ID $post_id not found." );
		}

		// Check permission
		if ( ! current_user_can( 'delete_post', $post_id ) ) {
			throw new Exception( "Permission denied: cannot delete listing $post_id." );
		}

		$force = ! empty( $args['force'] );
		$title = $post->post_title;

		$result = wp_delete_post( $post_id, $force );

		if ( ! $result ) {
			throw new Exception( "Failed to delete listing $post_id." );
		}

		return array(
			'id'      => $post_id,
			'title'   => $title,
			'deleted' => true,
			'force'   => $force,
		);
	},
	'schema'      => array(
		'properties' => array(
			'id'    => array( 'type' => 'number', 'required' => true ),
			'force' => array( 'type' => 'boolean', 'default' => false ),
		)
	)
) );
