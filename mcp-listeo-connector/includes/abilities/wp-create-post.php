<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: wp/create-post
 */
MCP_Registry::register_ability( array(
	'id'          => 'wp/create-post',
	'title'       => 'Create WordPress Post',
	'description' => 'Create a new post, page, or generic CPT.',
	'scope'       => 'edit_posts',
	'callback'    => function( $args ) {
		return MCP_WP_Posts_API::create_post( $args );
	},
	'schema'      => array(
		'properties' => array(
			'title'     => array( 'type' => 'string', 'required' => true ),
			'content'   => array( 'type' => 'string' ),
			'status'    => array( 'type' => 'string', 'default' => 'draft' ),
			'post_type' => array( 'type' => 'string', 'default' => 'post' ),
			'author'    => array( 'type' => 'number' )
		)
	)
) );
