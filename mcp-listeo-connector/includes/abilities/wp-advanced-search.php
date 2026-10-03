<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Advanced Search across WordPress, Listeo, and Dokan
 */
MCP_Registry::register_ability( array(
	'id'          => 'wp/search-advanced',
	'title'       => __( 'Advanced Intelligent Search', 'mcp-listeo-connector' ),
	'description' => __( 'Perform expert search across Listings, Products and Posts with geographic, price and feature filters.', 'mcp-listeo-connector' ),
	'example_prompt' => __( 'Prioriza listados con puntuación mayor a 4 y que estén en Guatavita.', 'mcp-listeo-connector' ),
	'scope'       => 'read',
	'callback'    => 'mcp_ability_wp_advanced_search',
    'schema'      => array(
        'properties' => array(
            'query'      => array( 'type' => 'string' ),
            'location'   => array( 'type' => 'string' ),
            'min_price'  => array( 'type' => 'number' ),
            'max_price'  => array( 'type' => 'number' ),
            'features'   => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
            'is_open'    => array( 'type' => 'boolean' ),
            'min_rating' => array( 'type' => 'number' )
        )
    )
) );

function mcp_ability_wp_advanced_search( $args ) {
	$query     = $args['query'] ?? '';
	$post_type = $args['post_types'] ?? array( 'listing', 'product', 'post', 'page' );
	$limit     = (int) ($args['limit'] ?? 10);
	
	// Advanced Filters
	$location    = $args['location'] ?? ''; // Taxonomy term
	$min_price   = isset($args['min_price']) ? (float) $args['min_price'] : null;
	$max_price   = isset($args['max_price']) ? (float) $args['max_price'] : null;
	$features    = $args['features'] ?? array(); // Taxonomy terms (WiFi, etc.)
	$min_rating  = isset($args['min_rating']) ? (float) $args['min_rating'] : null;

	$query_args = array(
		'post_type'      => $post_type,
		's'              => $query,
		'posts_per_page' => $limit,
		'post_status'    => 'publish',
		'meta_query'     => array( 'relation' => 'AND' ),
		'tax_query'      => array( 'relation' => 'AND' ),
	);

	// Filter by Location Taxonomy
	if ( ! empty( $location ) ) {
        $field = is_numeric($location) ? 'id' : 'name';
		$query_args['tax_query'][] = array(
			'taxonomy' => 'region',
			'field'    => $field,
			'terms'    => $location,
		);
	}

	// Filter by Features
	if ( ! empty( $features ) ) {
		$query_args['tax_query'][] = array(
			'taxonomy' => 'listing_feature',
			'field'    => 'slug',
			'terms'    => (array) $features,
			'operator' => 'IN',
		);
	}

	// Filter by Price
	if ( $min_price !== null ) {
		$query_args['meta_query'][] = array(
			'key'     => '_price',
			'value'   => $min_price,
			'type'    => 'NUMERIC',
			'compare' => '>=',
		);
	}
	if ( $max_price !== null ) {
		$query_args['meta_query'][] = array(
			'key'     => '_price',
			'value'   => $max_price,
			'type'    => 'NUMERIC',
			'compare' => '<=',
		);
	}

	// Filter by Rating
	if ( $min_rating !== null ) {
		$query_args['meta_query'][] = array(
			'key'     => '_overall_rating',
			'value'   => $min_rating,
			'type'    => 'DECIMAL',
			'compare' => '>=',
		);
	}

	$wp_query = new WP_Query( $query_args );
	$results  = array();

	if ( $wp_query->have_posts() ) {
		while ( $wp_query->have_posts() ) {
			$wp_query->the_post();
			$id = get_the_ID();
			$type = get_post_type();
			
			$item = array(
				'id'        => $id,
				'title'     => get_the_title(),
				'type'      => $type,
				'link'      => get_permalink(),
				'excerpt'   => get_the_excerpt(),
				'price'     => get_post_meta( $id, '_price', true ),
				'rating'    => get_post_meta( $id, '_overall_rating', true ),
			);

			// Specific for Listeo
			if ( $type === 'listing' ) {
				$item['address'] = get_post_meta( $id, '_address', true );
				$item['phone']   = get_post_meta( $id, '_phone', true );
			}

			$results[] = $item;
		}
		wp_reset_postdata();
	}

	return $results;
}
