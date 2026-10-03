<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: listeo/create-listing
 */
MCP_Registry::register_ability( array(
	'id'          => 'listeo/create-listing',
	'title'       => 'Create Listeo Listing',
	'description' => 'ACTUALLY CREATE a new directory listing on xueturismo.com. Supports up to 5 images for gallery. First image becomes thumbnail. Use this when the user asks to "add", "insert", or "create" a new place or listing. Requires valid WP_AUTH credentials.',
	'scope'       => 'publish_posts',
	'callback'    => function( $args ) {
		if ( empty( $args['title'] ) ) {
			throw new Exception( "Missing 'title' parameter." );
		}

		// Normalize images: accept single image_url or array of image_urls
		$images = array();

		// If image_urls array provided, use it (max 5)
		if ( ! empty( $args['image_urls'] ) && is_array( $args['image_urls'] ) ) {
			$images = array_slice( $args['image_urls'], 0, 5 );
		}
		// If single image_url provided, use it as first image
		elseif ( ! empty( $args['image_url'] ) ) {
			$images[] = $args['image_url'];
		}

		// Set normalized values for API
		if ( ! empty( $images ) ) {
			$args['image_urls'] = $images;
			$args['image_url']  = $images[0]; // First image becomes thumbnail
		}

		return MCP_Listeo_API::create_listing( $args );
	},
	'schema'      => array(
		'properties' => array(
			'title'      => array( 'type' => 'string', 'required' => true ),
			'content'    => array( 'type' => 'string' ),
			'status'     => array( 'type' => 'string', 'default' => 'pending' ),
			'type'       => array( 'type' => 'string' ),
			'address'    => array( 'type' => 'string' ),
			'lat'        => array( 'type' => 'number' ),
			'lng'        => array( 'type' => 'number' ),
			'price'      => array( 'type' => 'string' ),
			'image_url'  => array( 'type' => 'string', 'description' => 'Single image URL (becomes thumbnail)' ),
			'image_urls' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'maxItems' => 5, 'description' => 'Array of up to 5 image URLs for gallery. First image becomes thumbnail.' ),
			'keywords'   => array( 'type' => 'string' ),
			'category'   => array( 'type' => 'array', 'items' => array( 'type' => 'number' ) ),
			'region'     => array( 'type' => 'array', 'items' => array( 'type' => 'number' ) ),
		)
	)
) );
