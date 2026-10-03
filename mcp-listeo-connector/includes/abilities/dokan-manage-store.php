<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: dokan/get-vendor-store
 */
MCP_Registry::register_ability( array(
	'id'          => 'dokan/get-vendor-store',
	'title'       => 'Get Vendor Store Data',
	'description' => 'Retrieve vendor store details like name, URL, banner, and location.',
	'example_prompt' => 'Verifica si la tienda tiene promociones activas en la descripción y resáltalas.',
	'scope'       => 'read',
	'callback'    => function( $args ) {
		if ( empty( $args['vendor_id'] ) ) {
			throw new Exception( "Missing 'vendor_id' parameter." );
		}
		return MCP_Dokan_API::get_vendor_store( $args['vendor_id'] );
	},
	'schema'      => array(
		'properties' => array(
			'vendor_id' => array( 'type' => 'number', 'required' => true )
		)
	)
) );
