<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: wp/delete-post
 */
MCP_Registry::register_ability( array(
	'id'          => 'wp/delete-post',
	'title'       => 'Delete a Post',
	'description' => 'Move a post/page/CPT to trash or permanently delete it.',
	'example_prompt' => 'Borra la entrada con ID 123.',
	'scope'       => 'delete_posts',
	'callback'    => function( $args ) {
		if ( empty( $args['id'] ) ) {
			throw new Exception( "Missing 'id' parameter." );
		}

		$post_id = (int) $args['id'];
		$force   = ! empty( $args['force'] );

		$post = get_post( $post_id );
		if ( ! $post ) {
			throw new Exception( "Post with ID $post_id not found." );
		}

		// Check permission
		if ( ! current_user_can( 'delete_post', $post_id ) ) {
			throw new Exception( "Permission denied: cannot delete post $post_id." );
		}

		$result = wp_delete_post( $post_id, $force );

		if ( ! $result ) {
			throw new Exception( "Failed to delete post $post_id." );
		}

		return array(
			'id'      => $post_id,
			'title'   => $post->post_title,
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
