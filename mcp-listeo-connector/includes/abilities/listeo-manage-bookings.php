<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: listeo/manage-bookings
 * Covers booking lookup, availability verification, and status updates (confirm, cancel, paid).
 */
MCP_Registry::register_ability( array(
	'id'             => 'listeo/list-bookings',
	'title'          => 'List and Filter Bookings',
	'description'    => 'Retrieve and inspect bookings with filters for status, listing ID, and date ranges.',
	'example_prompt' => 'Muéstrame las reservas pendientes para el listado 4746.',
	'scope'          => 'read',
	'callback'       => function( $args ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'listeo_core_bookings';
		
		// Fallback to custom post type 'booking' if custom table does not exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
			$where = array('1=1');
			$params = array();

			if ( ! empty( $args['listing_id'] ) ) {
				$where[] = 'listing_id = %d';
				$params[] = intval( $args['listing_id'] );
			}
			if ( ! empty( $args['status'] ) ) {
				$where[] = 'status = %s';
				$params[] = sanitize_text_field( $args['status'] );
			}

			$sql = "SELECT * FROM $table_name WHERE " . implode( ' AND ', $where ) . " ORDER BY id DESC LIMIT 50";
			if ( ! empty( $params ) ) {
				$sql = $wpdb->prepare( $sql, $params );
			}
			$results = $wpdb->get_results( $sql, ARRAY_A );
			return array(
				'count'    => count( $results ),
				'bookings' => $results,
			);
		} else {
			// Query via standard WP posts (CPT 'booking')
			$query_args = array(
				'post_type'      => 'booking',
				'post_status'    => 'any',
				'posts_per_page' => 50,
			);
			if ( ! empty( $args['listing_id'] ) ) {
				$query_args['meta_query'] = array(
					array(
						'key'     => '_listing_id',
						'value'   => intval( $args['listing_id'] ),
						'compare' => '=',
					),
				);
			}
			$bookings_posts = get_posts( $query_args );
			$items = array();
			foreach ( $bookings_posts as $b ) {
				$items[] = array(
					'id'         => $b->ID,
					'title'      => $b->post_title,
					'status'     => $b->post_status,
					'date'       => $b->post_date,
					'listing_id' => get_post_meta( $b->ID, '_listing_id', true ),
					'comment'    => $b->post_content,
				);
			}
			return array(
				'count'    => count( $items ),
				'bookings' => $items,
			);
		}
	},
	'schema'         => array(
		'properties' => array(
			'listing_id' => array(
				'type'        => 'number',
				'description' => 'Filter bookings by listing ID.',
			),
			'status'     => array(
				'type'        => 'string',
				'description' => 'Filter bookings by status: pending, approved, paid, cancelled.',
			),
		),
	),
) );

MCP_Registry::register_ability( array(
	'id'             => 'listeo/update-booking-status',
	'title'          => 'Update Booking Status',
	'description'    => 'Change status of a booking (e.g. approve, mark paid, cancel).',
	'example_prompt' => 'Aprueba la reserva 1024 y márcala como pagada.',
	'scope'          => 'publish_posts',
	'callback'       => function( $args ) {
		global $wpdb;

		if ( empty( $args['booking_id'] ) || empty( $args['status'] ) ) {
			throw new Exception( "Missing 'booking_id' or 'status' parameter." );
		}

		$booking_id = intval( $args['booking_id'] );
		$status     = sanitize_text_field( $args['status'] );
		$allowed_statuses = array( 'pending', 'approved', 'paid', 'cancelled', 'deleted' );

		if ( ! in_array( $status, $allowed_statuses ) ) {
			throw new Exception( "Invalid status. Allowed values: " . implode( ', ', $allowed_statuses ) );
		}

		$table_name = $wpdb->prefix . 'listeo_core_bookings';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
			$updated = $wpdb->update(
				$table_name,
				array( 'status' => $status ),
				array( 'id' => $booking_id ),
				array( '%s' ),
				array( '%d' )
			);

			return array(
				'success'    => false !== $updated,
				'booking_id' => $booking_id,
				'new_status' => $status,
			);
		} else {
			// Update CPT
			$updated = wp_update_post( array(
				'ID'          => $booking_id,
				'post_status' => $status,
			) );

			if ( is_wp_error( $updated ) ) {
				throw new Exception( $updated->get_error_message() );
			}

			update_post_meta( $booking_id, '_booking_status', $status );

			return array(
				'success'    => true,
				'booking_id' => $booking_id,
				'new_status' => $status,
			);
		}
	},
	'schema'         => array(
		'properties' => array(
			'booking_id' => array(
				'type'        => 'number',
				'required'    => true,
				'description' => 'The ID of the booking to update.',
			),
			'status'     => array(
				'type'        => 'string',
				'enum'        => array( 'pending', 'approved', 'paid', 'cancelled', 'deleted' ),
				'required'    => true,
				'description' => 'The target booking status.',
			),
		),
	),
) );
