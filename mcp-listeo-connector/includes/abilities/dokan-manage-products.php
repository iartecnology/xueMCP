<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: dokan/create-product
 */
MCP_Registry::register_ability( array(
	'id'          => 'dokan/create-product',
	'title'       => 'Create Product for a Vendor',
	'description' => 'Create a WooCommerce product assigned to a specific vendor.',
	'example_prompt' => 'Asegúrate de que la descripción mencione que el producto es artesanal y verifica el stock mínimo.',
	'scope'       => 'publish_posts', // Requires ability to publish or similar
	'callback'    => function( $args ) {
		if ( empty( $args['vendor_id'] ) ) {
			throw new Exception( "Missing 'vendor_id' parameter." );
		}
		return MCP_Dokan_API::create_vendor_product( $args['vendor_id'], $args );
	},
	'schema'      => array(
		'properties' => array(
			'vendor_id'   => array( 'type' => 'number', 'required' => true ),
			'name'        => array( 'type' => 'string', 'required' => true ),
			'description' => array( 'type' => 'string' ),
			'price'       => array( 'type' => 'number' )
		)
	)
) );
