<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: listeo/create-listing
 */
MCP_Registry::register_ability( array(
	'id'          => 'listeo/create-listing',
	'title'       => 'Create Listeo Listing',
	'description' => 'ACTUALLY CREATE a new directory listing on xueturismo.com. Use this when the user asks to "add", "insert", or "create" a new place or listing. Requires valid WP_AUTH credentials.',
	'scope'       => 'publish_posts', // Or specific Listeo capability like 'edit_listings'
	'callback'    => function( $args ) {
		if ( empty( $args['title'] ) ) {
			throw new Exception( "Missing 'title' parameter." );
		}
		return MCP_Listeo_API::create_listing( $args );
	},
	'schema'      => array(
		'properties' => array(
			'title'    => array( 'type' => 'string', 'required' => true ),
			'content'  => array( 'type' => 'string' ),
			'status'   => array( 'type' => 'string', 'default' => 'pending' ),
			'type'     => array( 'type' => 'string' ),
			'address'  => array( 'type' => 'string' ),
			'lat'      => array( 'type' => 'number' ),
			'lng'      => array( 'type' => 'number' ),
			'price'    => array( 'type' => 'string' ),
			'image_url'=> array( 'type' => 'string' ),
			'category' => array( 'type' => 'array', 'items' => array( 'type' => 'number' ) ),
			'region'   => array( 'type' => 'array', 'items' => array( 'type' => 'number' ) ),
		)
	)
) );
