<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: listeo/get-user-dashboard-data
 */
MCP_Registry::register_ability( array(
	'id'          => 'listeo/get-user-dashboard-data',
	'title'       => 'Get User Dashboard Stats',
	'description' => 'Retrieves summary of active listings, bookings count, and reviews for the logged-in user.',
	'example_prompt' => 'Compara mis estadísticas actuales con las del mes pasado y resalta si han subido las reservas.',
	'scope'       => 'read',
	'callback'    => function( $args ) {
		$user_id = get_current_user_id();
        if ( ! $user_id ) throw new Exception( "User session required." );

        $listings_count = count_user_posts( $user_id, 'listing' );
        
        // Listeo Core often uses custom tables for bookings or custom post types
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'listeo_bookings'; // Try to find if table exists
        $has_table = $wpdb->get_var("SHOW TABLES LIKE '$bookings_table'");
        
        $bookings_count = 0;
        if ( $has_table ) {
            $bookings_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $bookings_table WHERE owner_id = %d OR client_id = %d", $user_id, $user_id ) );
        } else {
            // Fallback to CPT if they use it (older versions or specific config)
            $bookings_count = count_user_posts( $user_id, 'booking' );
        }

		return array(
			'active_listings' => $listings_count,
			'total_bookings'  => intval($bookings_count),
			'role'            => get_user_meta( $user_id, 'listeo_core_user_role', true )
		);
	},
    'schema' => array( 'properties' => array() )
) );

/**
 * Ability: listeo/get-user-messages
 */
MCP_Registry::register_ability( array(
	'id'          => 'listeo/get-user-messages',
	'title'       => 'Get My Private Messages',
	'description' => 'Retrieve private messages threads from the Listeo messaging system.',
	'example_prompt' => 'Busca mensajes que no hayan sido respondidos y resalta si alguno menciona una urgencia o cancelación.',
	'scope'       => 'read',
	'callback'    => function( $args ) {
		global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'listeo_messages';
        
        $has_table = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if ( ! $has_table ) throw new Exception( "Messaging table not found." );

        $messages = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE sender_id = %d OR recipient_id = %d ORDER BY created_at DESC LIMIT 20",
            $user_id, $user_id
        ) );

        return $messages;
	},
    'schema' => array( 'properties' => array() )
) );
