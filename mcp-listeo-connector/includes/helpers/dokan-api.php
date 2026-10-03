<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Dokan Marketplace Helper
 */
class MCP_Dokan_API {

	public static function get_vendor_store( $vendor_id ) {
		if ( ! function_exists( 'dokan_get_store_info' ) ) {
			throw new Exception( "Dokan functions not available." );
		}

		$store_info = dokan_get_store_info( $vendor_id );
		return array(
			'store_name' => $store_info['store_name'] ?? '',
			'store_url'  => dokan_get_store_url( $vendor_id ),
			'location'   => $store_info['location'] ?? array(),
			'banner'     => $store_info['banner'] ?? '',
		);
	}

	public static function get_vendor_products( $vendor_id, $args ) {
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $args['limit'] ?? 10,
			'author'         => $vendor_id,
		);

		$query = new WP_Query( $query_args );
		
		$products = array();
		foreach ( $query->posts as $post ) {
			$product = wc_get_product( $post->ID );
			$products[] = array(
				'id'    => $post->ID,
				'name'  => $post->post_title,
				'price' => $product ? $product->get_price() : 0,
				'link'  => get_permalink( $post->ID ),
			);
		}

		return $products;
	}

    public static function create_vendor_product( $vendor_id, $args ) {
        // Basic check
        if ( ! user_can( $vendor_id, 'dokan_add_product' ) ) {
            throw new Exception( "Vendor does not have permission to add products." );
        }

        $post_id = wp_insert_post( array(
            'post_title'   => $args['name'],
            'post_content' => $args['description'] ?? '',
            'post_status'  => 'pending', // Usually pending for vendor products
            'post_type'    => 'product',
            'post_author'  => $vendor_id,
        ), true );

        if ( is_wp_error( $post_id ) ) {
            throw new Exception( $post_id->get_error_message() );
        }

        if ( ! empty( $args['price'] ) ) {
            update_post_meta( $post_id, '_regular_price', $args['price'] );
            update_post_meta( $post_id, '_price', $args['price'] );
        }

        return array( 'id' => $post_id, 'link' => get_permalink( $post_id ) );
    }

	public static function get_vendor_orders( $vendor_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'dokan_orders';
		$orders = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE seller_id = %d ORDER BY order_id DESC LIMIT 10",
			$vendor_id
		) );
		return $orders;
	}
}
