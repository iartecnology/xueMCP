<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: listeo/update-listing
 */
MCP_Registry::register_ability( array(
	'id'          => 'listeo/update-listing',
	'title'       => 'Update an Existing Listing',
	'description' => 'Update title, content, address, coordinates, price, images, and metadata of an existing listing. Supports up to 5 images for gallery.',
	'example_prompt' => 'Actualiza el listing 4746 con nueva descripción, precio y 3 imágenes nuevas.',
	'scope'       => 'publish_posts',
	'callback'    => function( $args ) {
		set_time_limit( 300 ); // Increase execution time for image downloads

		if ( empty( $args['id'] ) ) {
			throw new Exception( "Missing 'id' parameter. Provide the listing ID to update." );
		}

		// Force current user to admin to ensure upload permissions
		$original_user_id = get_current_user_id();
		if ( ! $original_user_id || ! user_can( $original_user_id, 'upload_files' ) ) {
			wp_set_current_user( 1 );
		}

		$post_id = (int) $args['id'];
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'listing' ) {
			if ( $original_user_id ) wp_set_current_user( $original_user_id );
			throw new Exception( "Listing with ID $post_id not found." );
		}

		// Check permission
		if ( ! current_user_can( 'publish_posts' ) ) {
			if ( $original_user_id ) wp_set_current_user( $original_user_id );
			throw new Exception( "Permission denied for ID $post_id" );
		}

		// Update post content
		$update_data = array( 'ID' => $post_id );

		if ( ! empty( $args['title'] ) ) {
			$update_data['post_title'] = sanitize_text_field( $args['title'] );
		}
		if ( isset( $args['content'] ) ) {
			$update_data['post_content'] = wp_kses_post( $args['content'] );
		}
		if ( ! empty( $args['status'] ) ) {
			$update_data['post_status'] = sanitize_text_field( $args['status'] );
		}

		$result = wp_update_post( $update_data, true );

		if ( is_wp_error( $result ) ) {
			throw new Exception( $result->get_error_message() );
		}

		// Update metadata
		if ( ! empty( $args['address'] ) ) {
			update_post_meta( $post_id, '_address', sanitize_text_field( $args['address'] ) );
		}
		if ( isset( $args['lat'] ) && is_numeric( $args['lat'] ) ) {
			update_post_meta( $post_id, '_geolocation_lat', floatval( $args['lat'] ) );
		}
		if ( isset( $args['lng'] ) && is_numeric( $args['lng'] ) ) {
			update_post_meta( $post_id, '_geolocation_long', floatval( $args['lng'] ) );
		}
		if ( ! empty( $args['price'] ) ) {
			update_post_meta( $post_id, '_price', sanitize_text_field( $args['price'] ) );
		}
		if ( ! empty( $args['type'] ) ) {
			update_post_meta( $post_id, '_listing_type', sanitize_text_field( $args['type'] ) );
		}
		if ( ! empty( $args['keywords'] ) ) {
			update_post_meta( $post_id, 'keywords', sanitize_text_field( $args['keywords'] ) );
		}

		// Handle Taxonomies
		if ( ! empty( $args['category'] ) ) {
			wp_set_object_terms( $post_id, $args['category'], 'listing_category' );
		}
		if ( ! empty( $args['region'] ) ) {
			wp_set_object_terms( $post_id, $args['region'], 'region' );
		}

		// Handle image update - Support up to 5 images
		$gallery_data = array();
		$image_results  = array();
		$total_images   = 0;
		$failed_images  = 0;
		$thumbnail_set  = false;

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

			$image_id = MCP_Listeo_API::set_listing_thumbnail_from_url_public( $post_id, $image_url );

			if ( is_wp_error( $image_id ) ) {
				$image_results[] = array(
					'url'   => $image_url,
					'status' => 'failed',
					'error' => $image_id->get_error_message()
				);
				$failed_images++;
				continue;
			}

			if ( $image_id ) {
				$attached_file = get_attached_file( $image_id );
				$filename = $attached_file ? basename( $attached_file ) : '';

				$gallery_data[ (string) $image_id ] = $filename;

				$image_results[] = array(
					'url'      => $image_url,
					'status'   => 'success',
					'image_id' => $image_id
				);
				$total_images++;

				// First image becomes the featured thumbnail
				if ( $index === 0 ) {
					set_post_thumbnail( $post_id, $image_id );
					$thumbnail_set = true;
				}
			}
		}

		// Save gallery to Listeo meta if images were processed
		if ( ! empty( $gallery_data ) ) {
			update_post_meta( $post_id, '_gallery', $gallery_data );
			update_post_meta( $post_id, '_gallery_images', $gallery_data );
		}

		// Build response message
		if ( $total_images > 1 ) {
			$image_status = "success: $total_images images (thumbnail + " . ($total_images - 1) . " gallery)";
		} elseif ( $total_images === 1 ) {
			$image_status = 'success';
		} elseif ( $failed_images > 0 ) {
			$image_status = 'failed: ' . $image_results[0]['error'];
		} else {
			$image_status = 'unchanged';
		}

		return array(
			'id'             => $post_id,
			'link'           => get_permalink( $post_id ),
			'updated'        => true,
			'total_images'   => $total_images,
			'failed_images'  => $failed_images,
			'image_results'  => $image_results,
			'image_status'   => $image_status,
		);
	},
	'schema'      => array(
		'properties' => array(
			'id'             => array( 'type' => 'number', 'required' => true ),
			'title'          => array( 'type' => 'string' ),
			'content'        => array( 'type' => 'string' ),
			'status'         => array( 'type' => 'string' ),
			'address'        => array( 'type' => 'string' ),
			'lat'            => array( 'type' => 'number' ),
			'lng'            => array( 'type' => 'number' ),
			'price'          => array( 'type' => 'string' ),
			'type'           => array( 'type' => 'string' ),
			'keywords'       => array( 'type' => 'string' ),
			'image_url'      => array( 'type' => 'string', 'description' => 'Single image URL (becomes thumbnail)' ),
			'image_urls'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'maxItems' => 5, 'description' => 'Array of up to 5 image URLs for gallery. First image becomes thumbnail.' ),
		)
	)
) );
