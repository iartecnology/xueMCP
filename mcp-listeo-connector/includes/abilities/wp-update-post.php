<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: wp/update-post
 */
MCP_Registry::register_ability( array(
	'id'          => 'wp/update-post',
	'title'       => 'Update WordPress Post',
	'description' => 'Update an existing post or page.',
	'scope'       => 'edit_posts',
	'callback'    => function( $args ) {
		return MCP_WP_Posts_API::update_post( $args );
	},
	'schema'      => array(
		'properties' => array(
			'id'      => array( 'type' => 'number', 'required' => true ),
			'title'   => array( 'type' => 'string' ),
			'content' => array( 'type' => 'string' ),
			'status'  => array( 'type' => 'string' )
		)
	)
) );
