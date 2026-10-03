<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: listeo/list-listings
 * Desc: Consulta listados publicados y devuelve información detallada o resumida.
 */
MCP_Registry::register_ability( array(
	'id'          => 'listeo-list-listings',
	'title'       => 'List Listings with Details',
	'description' => 'Consulta listados de Listeo. Permite obtener un resumen (ID, título, categoría, URL, imágenes) o la información completa de cada listado.',
	'scope'       => 'read',
	'callback'    => function( $args ) {
		$limit    = ! empty( $args['limit'] ) ? (int) $args['limit'] : 100;
		$format   = ! empty( $args['format'] ) ? $args['format'] : 'summary'; // 'summary' or 'full'
		$category = ! empty( $args['category'] ) ? $args['category'] : null;
		$status   = ! empty( $args['status'] ) ? $args['status'] : 'publish';

		$query_args = array(
			'post_type'      => 'listing',
			'post_status'    => $status,
			'posts_per_page' => $limit,
		);

		if ( $category ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'listing_category',
					'field'    => 'slug',
					'terms'    => $category,
				),
			);
		}

		$query = new WP_Query( $query_args );
		$results = array();

		foreach ( $query->posts as $post ) {
			$post_id = $post->ID;
			
			// Obtener categorías
			$terms = get_the_terms( $post_id, 'listing_category' );
			$categories = array();
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$categories[] = $term->name;
				}
			}

			// Obtener imágenes de la galería de Listeo
			$gallery = get_post_meta( $post_id, '_gallery', true );
			$image_urls = array();
			if ( is_array( $gallery ) ) {
				foreach ( $gallery as $img_id => $filename ) {
					$url = wp_get_attachment_url( $img_id );
					if ( $url ) {
						$image_urls[] = $url;
					}
				}
			}

			// Si no hay galería, intentar con la imagen destacada
			if ( empty( $image_urls ) ) {
				$thumb_url = get_the_post_thumbnail_url( $post_id, 'full' );
				if ( $thumb_url ) {
					$image_urls[] = $thumb_url;
				}
			}

			if ( $format === 'summary' ) {
				$results[] = array(
					'id'            => $post_id,
					'title'         => $post->post_title,
					'categories'    => $categories,
					'url'           => get_permalink( $post_id ),
					'images_count'  => count( $image_urls ),
					'image_urls'    => $image_urls,
				);
			} else {
				// Formato FULL
				$results[] = array(
					'id'            => $post_id,
					'title'         => $post->post_title,
					'content'       => $post->post_content,
					'address'       => get_post_meta( $post_id, '_address', true ),
					'lat'           => get_post_meta( $post_id, '_geolocation_lat', true ),
					'lng'           => get_post_meta( $post_id, '_geolocation_long', true ),
					'categories'    => $categories,
					'url'           => get_permalink( $post_id ),
					'images_count'  => count( $image_urls ),
					'image_urls'    => $image_urls,
					'keywords'      => get_post_meta( $post_id, 'keywords', true ),
					'status'        => $post->post_status,
				);
			}
		}

		return array(
			'count'    => count( $results ),
			'listings' => $results,
		);
	},
	'schema'      => array(
		'properties' => array(
			'limit'    => array( 'type' => 'number', 'description' => 'Número máximo de listados a devolver (default 100).' ),
			'format'   => array( 'type' => 'string', 'enum' => array( 'summary', 'full' ), 'description' => 'Nivel de detalle de la respuesta.' ),
			'category' => array( 'type' => 'string', 'description' => 'Filtrar por slug de categoría.' ),
			'status'   => array( 'type' => 'string', 'description' => 'Estado del post (publish, pending, etc).' ),
		)
	)
) );
