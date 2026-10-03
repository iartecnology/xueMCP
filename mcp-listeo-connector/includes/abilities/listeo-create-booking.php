<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ability: listeo/create-booking
 */
MCP_Registry::register_ability( array(
	'id'          => 'listeo/create-booking',
	'title'       => 'Create a Booking (Draft)',
	'description' => 'Submit a booking request for a listing. Permissions of "guest" or higher.',
	'example_prompt' => 'Asegúrate de preguntar siempre por alergias o peticiones especiales antes de confirmar el borrador.',
	'scope'       => 'read', // Base permission to submit
	'callback'    => function( $args ) {
		global $wpdb;

		if ( empty( $args['listing_id'] ) ) {
			throw new Exception( "Missing 'listing_id' parameter." );
		}

		$listing_id = intval( $args['listing_id'] );
		$date_start = sanitize_text_field( $args['date'] ?? current_time( 'Y-m-d' ) );
		$date_end   = sanitize_text_field( $args['date_end'] ?? $date_start );
		$comment    = sanitize_textarea_field( $args['comment'] ?? 'Reserva asistida por IA vía MCP' );
		$user_id    = get_current_user_id() ? get_current_user_id() : 1;
		$user       = get_userdata( $user_id );
		$email      = sanitize_email( $args['email'] ?? ( $user ? $user->user_email : 'guest@example.com' ) );
		$first_name = sanitize_text_field( $args['first_name'] ?? ( $user ? $user->display_name : 'Huésped' ) );

		$table_name = $wpdb->prefix . 'listeo_core_bookings';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
			$inserted = $wpdb->insert(
				$table_name,
				array(
					'listing_id' => $listing_id,
					'date_start' => $date_start,
					'date_end'   => $date_end,
					'comment'    => $comment,
					'status'     => 'pending',
					'user_id'    => $user_id,
					'email'      => $email,
					'first_name' => $first_name,
					'created'    => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
			);

			if ( false === $inserted ) {
				throw new Exception( "Error creating booking in database: " . $wpdb->last_error );
			}

			return array(
				'booking_id' => $wpdb->insert_id,
				'listing_id' => $listing_id,
				'status'     => 'pending',
				'message'    => 'Reserva creada exitosamente en estado pendiente.',
			);
		} else {
			// Fallback: create CPT 'booking'
			$post_id = wp_insert_post( array(
				'post_title'   => "Reserva # para Listing $listing_id - $first_name",
				'post_type'    => 'booking',
				'post_status'  => 'pending',
				'post_content' => $comment,
				'post_author'  => $user_id,
			) );

			if ( is_wp_error( $post_id ) ) {
				throw new Exception( $post_id->get_error_message() );
			}

			update_post_meta( $post_id, '_listing_id', $listing_id );
			update_post_meta( $post_id, '_date_start', $date_start );
			update_post_meta( $post_id, '_date_end', $date_end );
			update_post_meta( $post_id, '_email', $email );
			update_post_meta( $post_id, '_first_name', $first_name );

			return array(
				'booking_id' => $post_id,
				'listing_id' => $listing_id,
				'status'     => 'pending',
				'message'    => 'Reserva creada exitosamente como post de reserva.',
			);
		}
	},
	'schema'      => array(
		'properties' => array(
			'listing_id' => array( 'type' => 'number', 'required' => true, 'description' => 'Target listing ID' ),
			'date'       => array( 'type' => 'string', 'description' => 'Start date (YYYY-MM-DD)' ),
			'date_end'   => array( 'type' => 'string', 'description' => 'End date (YYYY-MM-DD)' ),
			'first_name' => array( 'type' => 'string', 'description' => 'Guest or customer name' ),
			'email'      => array( 'type' => 'string', 'description' => 'Customer email' ),
			'comment'    => array( 'type' => 'string', 'description' => 'Special requests or notes' )
		)
	)
) );
