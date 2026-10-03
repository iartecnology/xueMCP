<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: dokan/manage-products
 */
MCP_Registry::register_ability( array(
	'id'          => 'dokan/manage-products',
	'title'       => 'Update or Delete Vendor Products',
	'description' => 'Edit existing products: price, stock, description, images, status. Supports update and delete actions.',
	'example_prompt' => 'Actualiza el precio del producto 456 a 29.99 y pon el stock como agotado.',
	'scope'       => 'edit_posts',
	'callback'    => function( $args ) {
		$action = $args['action'] ?? 'update';

		if ( $action === 'delete' ) {
			if ( empty( $args['product_id'] ) ) {
				throw new Exception( "Missing 'product_id' parameter for delete action." );
			}

			$product_id = (int) $args['product_id'];
			$product    = wc_get_product( $product_id );

			if ( ! $product ) {
				throw new Exception( "Product with ID $product_id not found." );
			}

			// Check ownership
			if ( ! empty( $args['vendor_id'] ) ) {
				if ( $product->get_post_data()->post_author != $args['vendor_id'] ) {
					throw new Exception( "This product does not belong to the specified vendor." );
				}
			}

			$force = ! empty( $args['force'] );
			$result = wp_delete_post( $product_id, $force );

			if ( ! $result ) {
				throw new Exception( "Failed to delete product $product_id." );
			}

			return array(
				'product_id' => $product_id,
				'deleted'    => true,
				'force'      => $force,
			);
		}

		// UPDATE action
		if ( empty( $args['product_id'] ) ) {
			throw new Exception( "Missing 'product_id' parameter. Provide the product ID to update." );
		}

		$product_id = (int) $args['product_id'];
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			throw new Exception( "Product with ID $product_id not found." );
		}

		// Check ownership
		if ( ! empty( $args['vendor_id'] ) ) {
			if ( $product->get_post_data()->post_author != $args['vendor_id'] ) {
				throw new Exception( "This product does not belong to the specified vendor." );
			}
		}

		$updates = array();

		// Basic post updates
		if ( ! empty( $args['name'] ) ) {
			$updates['post_title'] = sanitize_text_field( $args['name'] );
		}
		if ( isset( $args['description'] ) ) {
			$updates['post_content'] = wp_kses_post( $args['description'] );
		}
		if ( ! empty( $args['short_description'] ) ) {
			$updates['post_excerpt'] = sanitize_textarea_field( $args['short_description'] );
		}
		if ( ! empty( $args['status'] ) ) {
			$updates['post_status'] = in_array( $args['status'], array( 'publish', 'draft', 'pending', 'private' ) ) ? $args['status'] : 'draft';
		}

		if ( ! empty( $updates ) ) {
			$update_data = array_merge( $updates, array( 'ID' => $product_id ) );
			wp_update_post( $update_data );
		}

		// WooCommerce product meta updates
		if ( isset( $args['price'] ) ) {
			$product->set_regular_price( sanitize_text_field( $args['price'] ) );
			$product->set_price( sanitize_text_field( $args['price'] ) );
		}
		if ( isset( $args['sale_price'] ) ) {
			$product->set_sale_price( sanitize_text_field( $args['sale_price'] ) );
		}
		if ( isset( $args['stock_status'] ) ) {
			$valid_statuses = array( 'instock', 'outofstock', 'onbackorder' );
			if ( in_array( $args['stock_status'], $valid_statuses ) ) {
				$product->set_stock_status( $args['stock_status'] );
			}
		}
		if ( isset( $args['stock_quantity'] ) ) {
			$product->set_stock_quantity( (int) $args['stock_quantity'] );
		}
		if ( isset( $args['manage_stock'] ) ) {
			$product->set_manage_stock( (bool) $args['manage_stock'] );
		}
		if ( ! empty( $args['sku'] ) ) {
			$product->set_sku( sanitize_text_field( $args['sku'] ) );
		}
		if ( ! empty( $args['categories'] ) && is_array( $args['categories'] ) ) {
			$product->set_category_ids( array_map( 'intval', $args['categories'] ) );
		}
		if ( ! empty( $args['tags'] ) && is_array( $args['tags'] ) ) {
			$product->set_tag_ids( array_map( 'intval', $args['tags'] ) );
		}
		if ( isset( $args['featured'] ) ) {
			$product->set_featured( (bool) $args['featured'] );
		}
		if ( ! empty( $args['image_url'] ) ) {
			// Sideload image for product gallery
			$attachment_id = MCP_Listeo_API::set_listing_thumbnail_from_url_public( $product_id, $args['image_url'] );
			if ( ! is_wp_error( $attachment_id ) && $attachment_id ) {
				$product->set_image_id( $attachment_id );
			}
		}

		$product->save();

		return array(
			'product_id' => $product_id,
			'updated'    => true,
			'link'       => get_permalink( $product_id ),
			'fields'     => array_keys( $args ),
		);
	},
	'schema'      => array(
		'properties' => array(
			'action'           => array( 'type' => 'string', 'default' => 'update', 'enum' => array( 'update', 'delete' ) ),
			'vendor_id'        => array( 'type' => 'number' ),
			'product_id'       => array( 'type' => 'number', 'required' => true ),
			'name'             => array( 'type' => 'string' ),
			'description'      => array( 'type' => 'string' ),
			'short_description' => array( 'type' => 'string' ),
			'price'            => array( 'type' => 'number' ),
			'sale_price'       => array( 'type' => 'number' ),
			'stock_status'     => array( 'type' => 'string', 'enum' => array( 'instock', 'outofstock', 'onbackorder' ) ),
			'stock_quantity'   => array( 'type' => 'number' ),
			'manage_stock'     => array( 'type' => 'boolean' ),
			'sku'              => array( 'type' => 'string' ),
			'status'           => array( 'type' => 'string' ),
			'categories'       => array( 'type' => 'array', 'items' => array( 'type' => 'number' ) ),
			'tags'             => array( 'type' => 'array', 'items' => array( 'type' => 'number' ) ),
			'featured'         => array( 'type' => 'boolean' ),
			'image_url'        => array( 'type' => 'string' ),
			'force'            => array( 'type' => 'boolean', 'default' => false ),
		)
	)
) );
