<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Listeo Theme Helper
 */
class MCP_Listeo_API {

	public static function get_listings( $args ) {
		$query_args = array(
			'post_type'      => 'listing',
			'post_status'    => 'publish',
			'posts_per_page' => $args['limit'] ?? 10,
			'tax_query'      => array( 'relation' => 'AND' ),
			'meta_query'     => array( 'relation' => 'AND' ),
		);

		// Keyword Search (Support both 'keyword' and 'search' from MCP)
		$search_query = $args['search'] ?? ($args['keyword'] ?? '');
		if ( ! empty( $search_query ) ) {
			$query_args['s'] = $search_query;
		}

		if ( ! empty( $args['category'] ) ) {
			$cat_id = is_numeric($args['category']) ? $args['category'] : null;
			if (!$cat_id) {
				$term = get_term_by('name', $args['category'], 'listing_category');
				if ($term) $cat_id = $term->term_id;
			}
			if ($cat_id) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'listing_category',
					'field'    => 'id',
					'terms'    => $cat_id,
				);
			}
		}

		if ( ! empty( $args['region'] ) ) {
			$region_id = is_numeric($args['region']) ? $args['region'] : null;
			if (!$region_id) {
				$term = get_term_by('name', $args['region'], 'region');
				if ($term) $region_id = $term->term_id;
			}
			if ($region_id) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'region',
					'field'    => 'id',
					'terms'    => $region_id,
				);
			}
		}

		$query = new WP_Query( $query_args );

		$results = array();
		foreach ( $query->posts as $post ) {
			$results[] = self::format_listing( $post );
		}

		return $results;
	}

	public static function format_listing( $post ) {
		return array(
			'id'        => $post->ID,
			'title'     => $post->post_title,
			'address'   => get_post_meta( $post->ID, '_address', true ),
			'location'  => array(
				'lat' => get_post_meta( $post->ID, '_geolocation_lat', true ),
				'lng' => get_post_meta( $post->ID, '_geolocation_long', true ),
			),
			'price'     => get_post_meta( $post->ID, '_price', true ),
			'thumbnail' => get_the_post_thumbnail_url( $post->ID, 'medium' ),
			'link'      => get_permalink( $post->ID ),
		);
	}

	public static function get_listing_details( $id ) {
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== 'listing' ) {
			throw new Exception( "Listing not found." );
		}

		$details = self::format_listing( $post );
		$details['content'] = apply_filters( 'the_content', $post->post_content );
		$details['gallery'] = get_post_meta( $post->ID, '_gallery', true );
		$details['reviews'] = self::get_reviews( $id );
		$details['type']    = get_post_meta( $id, '_listing_type', true );

		return $details;
	}

	public static function get_reviews( $listing_id ) {
		$comments = get_comments( array(
			'post_id' => $listing_id,
			'status'  => 'approve',
			'type'    => 'review',
		) );

		$reviews = array();
		foreach ( $comments as $comment ) {
			$reviews[] = array(
				'author'  => $comment->comment_author,
				'rating'  => get_comment_meta( $comment->comment_ID, 'rating', true ),
				'content' => $comment->comment_content,
				'date'    => $comment->comment_date
			);
		}
		return $reviews;
	}

	public static function get_listing_meta( $post_id ) {
		return array(
			'id'   => $post_id,
			'meta' => get_post_meta( $post_id )
		);
	}

	public static function create_listing( $args ) {
		set_time_limit( 300 );

		// Force current user to admin if not authenticated to ensure upload permissions
		$original_user_id = get_current_user_id();
		if ( ! $original_user_id || ! user_can( $original_user_id, 'upload_files' ) ) {
			wp_set_current_user( 1 );
		}
		
		$author_id = get_current_user_id();

		$post_data = array(
			'post_title'   => $args['title'],
			'post_content' => $args['content'] ?? '',
			'post_status'  => $args['status'] ?? 'pending',
			'post_type'    => 'listing',
			'post_author'  => $author_id,
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			// Restore user if we changed it
			if ( $original_user_id ) wp_set_current_user( $original_user_id );
			throw new Exception( $post_id->get_error_message() );
		}

		// Save Listeo Specific Meta
		if ( ! empty( $args['address'] ) ) {
			update_post_meta( $post_id, '_address', $args['address'] );
		}
		if ( ! empty( $args['lat'] ) ) {
			update_post_meta( $post_id, '_geolocation_lat', $args['lat'] );
		}
		if ( ! empty( $args['lng'] ) ) {
			update_post_meta( $post_id, '_geolocation_long', $args['lng'] );
		}
		if ( ! empty( $args['price'] ) ) {
			update_post_meta( $post_id, '_price', $args['price'] );
		}
		if ( ! empty( $args['type'] ) ) {
			update_post_meta( $post_id, '_listing_type', $args['type'] );
		}

		// Handle Taxonomies
		if ( ! empty( $args['category'] ) ) {
			wp_set_object_terms( $post_id, $args['category'], 'listing_category' );
		}
		if ( ! empty( $args['region'] ) ) {
			wp_set_object_terms( $post_id, $args['region'], 'region' );
		}

		// Keywords support
		if ( ! empty( $args['keywords'] ) ) {
			update_post_meta( $post_id, 'keywords', $args['keywords'] );
		}

		// Handle Image Sideloading - Support up to 5 images
		$gallery_data = array();
		$image_results  = array();
		$total_images   = 0;
		$failed_images  = 0;

		// Determine which images to process
		$images_to_process = array();

		// If image_urls array provided, use it (max 5)
		if ( ! empty( $args['image_urls'] ) && is_array( $args['image_urls'] ) ) {
			$images_to_process = array_slice( $args['image_urls'], 0, 5 );
		}
		// If single image_url provided, use it
		elseif ( ! empty( $args['image_url'] ) ) {
			$images_to_process[] = $args['image_url'];
		}

		// Process each image
		foreach ( $images_to_process as $index => $image_url ) {
			if ( empty( $image_url ) ) continue;

			$image_id = self::set_listing_thumbnail_from_url( $post_id, $image_url );

			if ( is_wp_error( $image_id ) ) {
				$image_results[] = array(
					'url'    => $image_url,
					'status' => 'failed',
					'error'  => $image_id->get_error_message()
				);
				$failed_images++;
				continue;
			}

			if ( $image_id ) {
				$attached_file = get_attached_file( $image_id );
				$filename = $attached_file ? basename( $attached_file ) : '';

				$gallery_data[ (string) $image_id ] = $filename;

				$image_results[] = array(
					'url'       => $image_url,
					'status'    => 'success',
					'image_id'  => $image_id
				);
				$total_images++;

				// First image becomes the featured thumbnail
				if ( $index === 0 ) {
					set_post_thumbnail( $post_id, $image_id );
				}
			}
		}

		// Save gallery to Listeo meta
		if ( ! empty( $gallery_data ) ) {
			update_post_meta( $post_id, '_gallery', $gallery_data );
			update_post_meta( $post_id, '_gallery_images', $gallery_data );
		}

		// Build response message
		if ( $total_images > 1 ) {
			$message = sprintf(
				__( 'Listing created successfully with %d images (thumbnail + %d gallery images).', 'mcp-listeo-connector' ),
				$total_images,
				$total_images - 1
			);
		} elseif ( $total_images === 1 ) {
			$message = __( 'Listing created successfully WITH IMAGE.', 'mcp-listeo-connector' );
		} else {
			$last_error = $failed_images > 0 ? $image_results[0]['error'] : 'No images provided';
			$message    = sprintf( __( 'Listing created but IMAGE(S) FAILED: %s', 'mcp-listeo-connector' ), $last_error );
		}

		return array(
			'id'             => $post_id,
			'link'           => get_permalink( $post_id ),
			'message'        => $message,
			'total_images'   => $total_images,
			'failed_images'  => $failed_images,
			'image_results'  => $image_results,
			'image_status'   => $total_images > 0 ? 'success' : 'no_images',
		);
	}

	/**
	 * Sideload image from URL and set as thumbnail
	 private static function set_listing_thumbnail_from_url( $post_id, $image_url ) {
	 	if ( empty( $image_url ) ) return false;

	 	// 1. Check if we already have this image
	 	$existing_attachment = get_posts( array(
	 		'post_type'  => 'attachment',
	 		'meta_key'   => '_source_url',
	 		'meta_value' => $image_url,
	 		'posts_per_page' => 1,
	 		'fields'     => 'ids',
	 	) );

	 	if ( ! empty( $existing_attachment ) ) {
	 		return $existing_attachment[0];
	 	}

	 	if ( ! function_exists( 'media_sideload_image' ) ) {
	 		require_once( ABSPATH . 'wp-admin/includes/media.php' );
	 		require_once( ABSPATH . 'wp-admin/includes/file.php' );
	 		require_once( ABSPATH . 'wp-admin/includes/image.php' );
	 	}

	 	// --- NEW: Local File Support ---
	 	// Check if it's a local filename in our temp directory
	 	$filename = basename( parse_url( $image_url, PHP_URL_PATH ) );
	 	$temp_dir = WP_CONTENT_DIR . '/mcp-temp/';
	 	$local_path = $temp_dir . $filename;

	 	if ( file_exists( $local_path ) ) {
	 		error_log( "[MCP-Connector] Local file found: $local_path" );
	 		$file_array = array(
	 			'name'     => $filename,
	 			'tmp_name' => $local_path,
	 		);
	 		// For local files, we use media_handle_sideload but we MUST COPY the file first 
	 		// because sideload moves/deletes the temp file.
	 		$final_tmp = wp_tempnam( $filename );
	 		copy( $local_path, $final_tmp );
	 		$file_array['tmp_name'] = $final_tmp;

	 		$id = media_handle_sideload( $file_array, $post_id );
	 		if ( ! is_wp_error( $id ) ) {
	 			update_post_meta( $id, '_source_url', $image_url );
	 			return $id;
	 		}
	 	}
	 	// --- End Local File Support ---

	 	// Fallback to media_sideload_image (Original Download Logic)
	 	$bing_compat_filter = function( $args ) {
	 ...
		$bing_compat_filter = function( $args ) {
			$args['user-agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36';
			$args['sslverify'] = false;
			$args['timeout'] = 45;
			return $args;
		};
		add_filter( 'http_request_args', $bing_compat_filter );

		// Use media_sideload_image
		$id = media_sideload_image( $image_url, $post_id, null, 'id' );

		// Remove filter
		remove_filter( 'http_request_args', $bing_compat_filter );

		if ( is_wp_error( $id ) ) {
			error_log( "[MCP-Connector] Sideload Failed for URL $image_url: " . $id->get_error_message() );
			return $id;
		}

		if ( $id ) {
			update_post_meta( $id, '_source_url', $image_url );
			return $id;
		}

		return false;
	}

	/**
	 * Public wrapper for image sideload (used by update-listing and dokan abilities)
	 */
	public static function set_listing_thumbnail_from_url_public( $post_id, $image_url ) {
		return self::set_listing_thumbnail_from_url( $post_id, $image_url );
	}
}
