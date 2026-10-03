<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: listeo/audit-listings
 * Emulates the official Purethemes Listeo MCP Health Check functionality.
 * Scans listings to identify missing images, GPS coordinates, opening hours, or contact info.
 */
MCP_Registry::register_ability( array(
	'id'             => 'listeo/audit-listings',
	'title'          => 'Audit Listings Health',
	'description'    => 'Audits listings to find missing cover/gallery images, GPS map coordinates, opening hours, or contact info.',
	'example_prompt' => 'Encuentra todos los listados que no tengan coordenadas GPS o no tengan fotos asignadas.',
	'scope'          => 'read',
	'callback'       => function( $args ) {
		$posts_per_page = isset( $args['limit'] ) ? intval( $args['limit'] ) : 50;
		$category       = sanitize_text_field( $args['category'] ?? '' );
		$status         = sanitize_text_field( $args['status'] ?? 'publish' );
		$missing_filter = sanitize_text_field( $args['missing'] ?? 'any' ); // 'gps', 'images', 'hours', 'phone', 'any'

		$query_args = array(
			'post_type'      => 'listing',
			'post_status'    => $status,
			'posts_per_page' => $posts_per_page,
			'fields'         => 'ids',
		);

		if ( ! empty( $category ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'listing_category',
					'field'    => is_numeric( $category ) ? 'term_id' : 'slug',
					'terms'    => $category,
				),
			);
		}

		$query = new WP_Query( $query_args );
		$issues = array();

		foreach ( $query->posts as $post_id ) {
			$post_title = get_the_title( $post_id );
			$item_issues = array();

			// Check Thumbnail & Gallery
			$has_thumb = has_post_thumbnail( $post_id );
			$gallery   = get_post_meta( $post_id, '_gallery', true );
			$has_gallery = ! empty( $gallery ) && is_array( $gallery );

			if ( ! $has_thumb && ! $has_gallery ) {
				$item_issues[] = 'missing_images';
			}

			// Check GPS Coordinates
			$lat = get_post_meta( $post_id, '_geolocation_lat', true );
			$lng = get_post_meta( $post_id, '_geolocation_long', true );
			if ( empty( $lat ) || empty( $lng ) || floatval( $lat ) == 0 || floatval( $lng ) == 0 ) {
				$item_issues[] = 'missing_gps';
			}

			// Check Opening Hours
			$opening_hours = get_post_meta( $post_id, '_opening_hours', true );
			if ( empty( $opening_hours ) ) {
				$item_issues[] = 'missing_hours';
			}

			// Check Phone / Contact
			$phone = get_post_meta( $post_id, '_phone', true );
			if ( empty( $phone ) ) {
				$item_issues[] = 'missing_phone';
			}

			// Apply Filter
			$matches_filter = false;
			if ( ! empty( $item_issues ) ) {
				if ( 'any' === $missing_filter ) {
					$matches_filter = true;
				} elseif ( in_array( 'missing_' . $missing_filter, $item_issues ) ) {
					$matches_filter = true;
				}
			}

			if ( $matches_filter ) {
				$issues[] = array(
					'id'            => $post_id,
					'title'         => $post_title,
					'url'           => get_permalink( $post_id ),
					'missing_items' => $item_issues,
					'has_thumb'     => $has_thumb,
					'has_gallery'   => $has_gallery,
					'has_gps'       => ! in_array( 'missing_gps', $item_issues ),
					'has_hours'     => ! in_array( 'missing_hours', $item_issues ),
				);
			}
		}

		return array(
			'total_scanned' => count( $query->posts ),
			'total_flagged' => count( $issues ),
			'filter'        => $missing_filter,
			'items'         => $issues,
		);
	},
	'schema'         => array(
		'properties' => array(
			'limit'    => array(
				'type'        => 'number',
				'description' => 'Maximum number of listings to analyze (default: 50).',
			),
			'category' => array(
				'type'        => 'string',
				'description' => 'Category slug or term ID to filter the search.',
			),
			'status'   => array(
				'type'        => 'string',
				'description' => 'Listing post status (publish, pending, draft). Default: publish.',
			),
			'missing'  => array(
				'type'        => 'string',
				'enum'        => array( 'any', 'gps', 'images', 'hours', 'phone' ),
				'description' => 'Filter specifically by missing criteria (default: any).',
			),
		),
	),
) );
