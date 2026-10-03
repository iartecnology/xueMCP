<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: dokan/get-vendor-products
 */
MCP_Registry::register_ability( array(
	'id'          => 'dokan/get-vendor-products',
	'title'       => 'Get Vendor Products',
	'description' => 'List all products belonging to a specific vendor with details like price, stock, status, and link.',
	'example_prompt' => 'Muéstrame todos los productos del vendedor 15.',
	'scope'       => 'read',
	'callback'    => function( $args ) {
		if ( empty( $args['vendor_id'] ) ) {
			throw new Exception( "Missing 'vendor_id' parameter." );
		}

		$vendor_id = (int) $args['vendor_id'];
		$user      = get_user_by( 'id', $vendor_id );

		if ( ! $user ) {
			throw new Exception( "Vendor with ID $vendor_id not found." );
		}

		// Use Dokan function if available
		if ( function_exists( 'dokan_get_store_info' ) ) {
			$store_info = dokan_get_store_info( $vendor_id );
			$store_name = $store_info['store_name'] ?? 'Unknown Store';
			$store_url  = dokan_get_store_url( $vendor_id );
		} else {
			$store_name = $user->display_name;
			$store_url  = '';
		}

		// Query products
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => (int) ( $args['limit'] ?? 20 ),
			'author'         => $vendor_id,
			'paged'          => (int) ( $args['page'] ?? 1 ),
		);

		// Filter by status if provided
		if ( ! empty( $args['status'] ) ) {
			$query_args['post_status'] = $args['status'];
		}

		// Search if provided
		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = sanitize_text_field( $args['search'] );
		}

		$query  = new WP_Query( $query_args );
		$products = array();

		foreach ( $query->posts as $post ) {
			$product = wc_get_product( $post->ID );

			$products[] = array(
				'id'            => $post->ID,
				'name'          => $post->post_title,
				'status'        => $post->post_status,
				'price'         => $product ? $product->get_price() : '0',
				'regular_price' => $product ? $product->get_regular_price() : '',
				'sale_price'    => $product ? $product->get_sale_price() : '',
				'stock_status'  => $product ? $product->get_stock_status() : 'unknown',
				'stock_qty'     => $product ? $product->get_stock_quantity() : null,
				'sku'           => $product ? $product->get_sku() : '',
				'featured'      => $product ? $product->is_featured() : false,
				'image'         => $product && $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'medium' ) : '',
				'categories'    => $product ? wp_get_post_terms( $post->ID, 'product_cat', array( 'fields' => 'names' ) ) : array(),
				'link'          => get_permalink( $post->ID ),
				'date'          => $post->post_date,
			);
		}

		return array(
			'vendor_id'    => $vendor_id,
			'store_name'   => $store_name,
			'store_url'    => $store_url,
			'total'        => $query->found_posts,
			'page'         => $query->query_vars['paged'] ?? 1,
			'pages'        => $query->max_num_pages,
			'products'     => $products,
		);
	},
	'schema'      => array(
		'properties' => array(
			'vendor_id' => array( 'type' => 'number', 'required' => true ),
			'limit'     => array( 'type' => 'number', 'default' => 20 ),
			'page'      => array( 'type' => 'number', 'default' => 1 ),
			'status'    => array( 'type' => 'string' ),
			'search'    => array( 'type' => 'string' ),
		)
	)
) );
