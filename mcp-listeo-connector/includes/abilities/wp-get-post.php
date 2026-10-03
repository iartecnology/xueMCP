<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: wp/get-post
 */
MCP_Registry::register_ability( array(
	'id'          => 'wp/get-post',
	'title'       => 'Get WordPress Post',
	'description' => 'Retrieve a post/page or CPT by ID or Slug. Generic CPTs only.',
	'example_prompt' => 'Resume el contenido y menciona siempre quién es el autor y la fecha de publicación.',
	'scope'       => 'read',
	'callback'    => function( $args ) {
		return MCP_WP_Posts_API::get_post( $args );
	},
	'schema'      => array(
		'properties' => array(
			'id'        => array( 'type' => 'number' ),
			'slug'      => array( 'type' => 'string' ),
			'post_type' => array( 'type' => 'string', 'default' => 'any' )
		)
	)
) );

/**
 * Ability: wp/search-posts
 */
MCP_Registry::register_ability( array(
	'id'          => 'wp/search-posts',
	'title'       => 'Search WordPress Posts',
	'description' => 'Search posts by query, type, and limit.',
	'example_prompt' => 'Si buscas noticias, filtra solo por la categoría "Actualidad" o "Noticias".',
	'scope'       => 'read',
	'callback'    => function( $args ) {
		return MCP_WP_Posts_API::search_posts( $args );
	},
	'schema'      => array(
		'properties' => array(
			's'         => array( 'type' => 'string' ),
			'post_type' => array( 'type' => 'string', 'default' => 'post' ),
			'limit'     => array( 'type' => 'number', 'default' => 10 )
		)
	)
) );
