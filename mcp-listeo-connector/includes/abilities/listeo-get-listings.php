<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: listeo/get-listings
 */
MCP_Registry::register_ability( array(
	'id'          => 'listeo/get-listings',
	'title'       => 'Search and Get Listings',
	'description' => 'Use this to fetch directory listings. You can filter by category (slug or ID), region (slug or name like "Tunja"), or use keywords for searching names and addresses.',
	'example_prompt' => 'Busca hoteles en Villa de Leyva que acepten mascotas.',
	'scope'       => 'read',
	'callback'    => function( $args ) {
		return MCP_Listeo_API::get_listings( $args );
	},
	'schema'      => array(
		'properties' => array(
			'category' => array( 'type' => 'string', 'description' => 'Slug, ID o nombre de categoría' ),
			'region'   => array( 'type' => 'string', 'description' => 'Slug, ID o nombre de la ciudad o región (ej: Tunja)' ),
			'search'   => array( 'type' => 'string', 'description' => 'Búsqueda por palabras clave en el título o descripción' ),
			'limit'    => array( 'type' => 'number', 'default' => 10 )
		)
	)
) );

/**
 * Ability: listeo/get-listing-details
 */
MCP_Registry::register_ability( array(
	'id'          => 'listeo/get-listing-details',
	'title'       => 'Get Full Details of a Listing',
	'description' => 'Get all metadata, reviews, and gallery links for a specific listing.',
	'example_prompt' => 'Resalta las reseñas más recientes y menciona si hay advertencias importantes en los comentarios.',
	'scope'       => 'read',
	'callback'    => function( $args ) {
		if ( empty( $args['id'] ) ) {
			throw new Exception( "Missing 'id' parameter." );
		}
		return MCP_Listeo_API::get_listing_details( $args['id'] );
	},
	'schema'      => array(
		'properties' => array(
			'id' => array( 'type' => 'number', 'required' => true )
		)
	)
) );
