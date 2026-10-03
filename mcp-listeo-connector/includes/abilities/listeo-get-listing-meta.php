<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: listeo/get_listing_meta
 * Retrieves all metadata for a specific listing for debugging gallery formats.
 */
MCP_Registry::register_ability( array(
    'id'          => 'listeo/get-listing-meta',
    'title'       => 'Get Listing Metadata',
    'description' => 'Retrieves all raw metadata for a specific listing to debug gallery and image formats.',
    'scope'       => 'manage_options',
    'callback'    => array( 'MCP_Listeo_API', 'get_listing_meta' ),
    'schema'      => array(
        'type'       => 'object',
        'properties' => array(
            'id' => array(
                'type'        => 'integer',
                'description' => 'Target listing ID'
            )
        ),
        'required' => array( 'id' )
    )
) );
