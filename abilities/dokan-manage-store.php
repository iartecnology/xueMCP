<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: dokan/manage-store
 */
MCP_Registry::register_ability( array(
	'id'          => 'dokan/manage-store',
	'title'       => 'Update Vendor Store Settings',
	'description' => 'Update store name, description, address, phone, social media, and other vendor store settings.',
	'example_prompt' => 'Actualiza el nombre de la tienda del vendedor 15 a "Mi Tienda Gourmet" y agrega la dirección.',
	'scope'       => 'edit_posts',
	'callback'    => function( $args ) {
		if ( empty( $args['vendor_id'] ) ) {
			throw new Exception( "Missing 'vendor_id' parameter." );
		}

		$vendor_id = (int) $args['vendor_id'];
		$user      = get_user_by( 'id', $vendor_id );

		if ( ! $user ) {
			throw new Exception( "Vendor with ID $vendor_id not found." );
		}

		// Get existing store info
		if ( function_exists( 'dokan_get_store_info' ) ) {
			$store_info = dokan_get_store_info( $vendor_id );
		} else {
			$store_info = get_user_meta( $vendor_id, 'dokan_profile_settings', true );
			if ( empty( $store_info ) ) {
				$store_info = array();
			}
		}

		// Update fields
		$updates = array();

		if ( ! empty( $args['store_name'] ) ) {
			$updates['store_name'] = sanitize_text_field( $args['store_name'] );
		}
		if ( isset( $args['description'] ) ) {
			$updates['description'] = wp_kses_post( $args['description'] );
		}
		if ( ! empty( $args['phone'] ) ) {
			$updates['phone'] = sanitize_text_field( $args['phone'] );
		}
		if ( ! empty( $args['address'] ) ) {
			$updates['address'] = sanitize_text_field( $args['address'] );
		}
		if ( ! empty( $args['location'] ) && is_array( $args['location'] ) ) {
			$updates['location'] = array_map( 'floatval', $args['location'] );
		}
		if ( ! empty( $args['banner'] ) ) {
			$updates['banner'] = absint( $args['banner'] );
		}
		if ( ! empty( $args['social'] ) && is_array( $args['social'] ) ) {
			$updates['social'] = array_map( 'esc_url_raw', $args['social'] );
		}
		if ( ! empty( $args['gravatar'] ) ) {
			$updates['gravatar'] = absint( $args['gravatar'] );
		}

		// Merge with existing data
		$updated_settings = array_merge( $store_info, $updates );

		// Save
		update_user_meta( $vendor_id, 'dokan_profile_settings', $updated_settings );

		// Update store name in user meta if provided
		if ( ! empty( $args['store_name'] ) ) {
			update_user_meta( $vendor_id, 'dokan_store_name', $args['store_name'] );
		}

		return array(
			'vendor_id'   => $vendor_id,
			'updated'     => true,
			'store_url'   => function_exists( 'dokan_get_store_url' ) ? dokan_get_store_url( $vendor_id ) : '',
			'fields'      => array_keys( $updates ),
		);
	},
	'schema'      => array(
		'properties' => array(
			'vendor_id'   => array( 'type' => 'number', 'required' => true ),
			'store_name'  => array( 'type' => 'string' ),
			'description' => array( 'type' => 'string' ),
			'phone'       => array( 'type' => 'string' ),
			'address'     => array( 'type' => 'string' ),
			'location'    => array( 'type' => 'object', 'properties' => array( 'lat' => array( 'type' => 'number' ), 'lng' => array( 'type' => 'number' ) ) ),
			'banner'      => array( 'type' => 'number' ),
			'gravatar'    => array( 'type' => 'number' ),
			'social'      => array( 'type' => 'object' ),
		)
	)
) );
