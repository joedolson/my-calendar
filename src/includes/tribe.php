<?php
/**
 * Migration tool: The Events Calendar.
 *
 * @category Import
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Count remaining events from Tribe Events Calendar.
 *
 * @return int
 */
function mc_count_tribe_remaining() {
	$args   = array(
		'post_type'   => 'tribe_events',
		'numberposts' => -1,
		'fields'      => 'ids',
		'post_status' => 'any',
		'meta_query'  => array(
			array(
				'key'     => '_mc_imported',
				'compare' => 'NOT EXISTS',
			),
		),
	);
	$events = get_posts( $args );
	if ( 0 === count( $events ) ) {
		as_unschedule_all_actions( 'mc_import_tribe' );
	}

	return count( $events );
}

/**
 * Import Tribe Events.
 *
 * @param int $limit Number of events to import.
 *
 * @return string Message about imported events.
 */
function mc_import_source_tribe_events( $limit = 25 ) {
	global $wpdb;
	$count   = wp_count_posts( 'tribe_events' );
	$message = '';
	$total   = 0;
	foreach ( $count as $c ) {
		$total = $total + (int) $c;
	}

	// Get selection of events not already imported.
	$events = $wpdb->get_results( $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS $wpdb->posts.ID FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '_mc_imported' ) WHERE 1=1 AND ( $wpdb->postmeta.post_id IS NULL ) AND $wpdb->posts.post_type = 'tribe_events' AND (($wpdb->posts.post_status <> 'trash' AND $wpdb->posts.post_status <> 'auto-draft')) GROUP BY $wpdb->posts.ID ORDER BY $wpdb->posts.post_parent ASC LIMIT 0, %d", $limit ) );
	$ids    = array();
	$count  = count( $events );
	if ( 0 === $count ) {
		update_option( 'mc_import_tribe_completed', 'true' );
	} else {
		foreach ( $events as $post ) {
			$id = mc_import_source_tribe_event( $post->ID );
			if ( $id ) {
				$ids[] = $id;
			}
		}

		$completed = count( $ids );
		if ( false === as_has_scheduled_action( 'mc_import_tribe' ) ) {
			as_schedule_recurring_action( strtotime( '+1 minutes' ), 60, 'mc_import_tribe', array(), 'my-calendar' );
		}
		// translators: 1) Number of events imported, 2) total number of events found.
		$message = '<div class="notice notice-info"><p>' . sprintf( __( '%1$d events imported. %2$d remaining. Remaining events are being imported in the background. You can feel free to leave this page.', 'my-calendar' ), $completed, $total ) . '</p></div>';
	}

	return $message;
}
add_action( 'mc_import_tribe', 'mc_import_source_tribe_events' );

/**
 * Suspend import process if completed.
 */
function mc_check_tribe_imports() {
	if ( 'true' === get_option( 'mc_import_tribe_completed' ) ) {
		as_unschedule_all_actions( 'mc_import_tribe' );
	}
}
add_action( 'init', 'mc_check_tribe_imports' );

/**
 * Import an event from Tribe Events Calendar.
 *
 * @param int $tribe_id ID of a tribe event post.
 *
 * @return bool|int False or new post ID.
 */
function mc_import_source_tribe_event( $tribe_id ) {
	// If already imported, return false.
	$imported = get_post_meta( $tribe_id, '_mc_imported', true );
	if ( $imported ) {
		return false;
	}
	$tribe_event = get_post( $tribe_id );
	/**
	 * Filter imported event to customize what gets added to database. Return false to skip event.
	 *
	 * @hook mc_imported_event
	 *
	 * @param {array} $event Array of event data passed to `mc_check_data`.
	 *
	 * @return {array|false}
	 */
	$event  = apply_filters( 'mc_imported_event_tribe', $tribe_event );
	$parent = $tribe_event->post_parent;
	if ( ! $parent ) {
		// This is a full event, not a sub event in a recurring series.
		$event = mc_format_tribe_event_for_import( $event );
		if ( false === $event ) {
			return;
		}
		$count = count( $event['event_begin'] );
		for ( $i = 0; $i < $count; $i++ ) {
			$check    = mc_check_data( 'add', $event, 0, true );
			$event_id = false;
			if ( $check[0] ) {
				$response = my_calendar_save( 'add', $check );
				$event_id = $response['event_id'];
				/**
				 * Perform an action after an event has been imported from Tribe Events Calendar to My Calendar.
				 *
				 * @hook my_calendar_event_imported_from_tribe
				 *
				 * @param {int} $tribe_id Post ID from Tribe Events.
				 * @param {int} $event_id Event ID from My Calendar.
				 * @param {int} $event_post Post ID for My Calendar event.
				 */
				do_action( 'my_calendar_event_imported_from_tribe', $tribe_id, $event_id, $response['event_post'] );
				if ( ! empty( $event['event_image_id'] ) ) {
					set_post_thumbnail( $response['event_post'], $event['event_image_id'] );
				}
				// Import tickets from Tribe Tickets to My Tickets.
				mc_import_tribe_tickets( $tribe_id, $response['event_post'] );
				update_post_meta( $tribe_id, '_mc_imported', $event_id );
			}
		}
	} else {
		// This is part of a recurring series; add to parent event.
		$event       = mc_format_tribe_event_for_import( $event, 'instance' );
		$mc_event    = get_post_meta( $parent, '_mc_imported', true );
		$event['id'] = $mc_event;
		// This isn't the same event ID; it's an instance ID. Only used for counting the imports, however.
		$event_id = mc_insert_instance( $event );
		// Mark post as imported.
		update_post_meta( $tribe_id, '_mc_imported', $mc_event );
		/**
		 * Perform an action after an occurrence has been imported from Tribe Events Calendar to My Calendar.
		 *
		 * @hook my_calendar_instance_imported_from_tribe
		 *
		 * @param {int} $tribe_id Post ID from Tribe Events.
		 * @param {int} $mc_event Event ID from My Calendar.
		 * @param {int} $parent Parent ID from Tribe.
		 */
		do_action( 'my_calendar_instance_imported_from_tribe', $tribe_id, $mc_event, $parent );
	}

	return $event_id;
}

/**
 * Adapt a Tribe event to the My Calendar import structure.
 *
 * @param object $event WordPress Post from Tribe Events.
 * @param string $type  Type of return. 'event' or 'instance'.
 *
 * @return array Importable data for My Calendar depending on type.
 */
function mc_format_tribe_event_for_import( $event, $type = 'event' ) {
	$terms = get_the_terms( $event, 'tribe_events_cat' );
	if ( is_array( $terms ) ) {
		foreach ( $terms as $term ) {
			$cat_id = mc_category_by_name( $term->name );
			if ( ! $cat_id ) {
				$cat    = array(
					'category_name' => $term->name,
				);
				$cat_id = mc_create_category( $cat );
			}
			// if category does not exist, create.
			$category_ids[] = $cat_id;
		}
	} else {
		$category_ids[] = 1;
	}

	// If _EventRecurrence, create instance for each event. TODO
	// Spawn collection of dates in array to be used in event_begin and event_end.
	if ( 'instance' === $type ) {
		$my_calendar_event = array(
			'id'            => '',
			'event_date'    => gmdate( 'Y-m-d', strtotime( get_post_meta( $event->ID, '_EventStartDate', true ) ) ),
			'event_time'    => gmdate( 'H:i:00', strtotime( get_post_meta( $event->ID, '_EventStartDate', true ) ) ),
			'event_end'     => gmdate( 'Y-m-d', strtotime( get_post_meta( $event->ID, '_EventEndDate', true ) ) ),
			'event_endtime' => gmdate( 'H:i:00', strtotime( get_post_meta( $event->ID, '_EventEndDate', true ) ) ),
			'group'         => '', // No correlation in Tribe.
		);
	} else {
		$my_calendar_event = array(
			// Event data.
			'event_title'      => $event->post_title,
			'event_begin'      => array( gmdate( 'Y-m-d', strtotime( get_post_meta( $event->ID, '_EventStartDate', true ) ) ) ),
			'event_end'        => array( gmdate( 'Y-m-d', strtotime( get_post_meta( $event->ID, '_EventEndDate', true ) ) ) ),
			'event_time'       => array( gmdate( 'H:i:00', strtotime( get_post_meta( $event->ID, '_EventStartDate', true ) ) ) ),
			'event_endtime'    => array( gmdate( 'H:i:00', strtotime( get_post_meta( $event->ID, '_EventEndDate', true ) ) ) ),
			'content'          => $event->post_content,
			'event_short'      => $event->post_excerpt,
			'event_link'       => get_post_meta( $event->ID, '_EventURL', true ),
			'event_image'      => get_the_post_thumbnail_url( $event->ID ),
			'event_image_id'   => get_post_thumbnail_id( $event->ID ),
			'event_allday'     => ( 'yes' === get_post_meta( $event->ID, '_EventAllDay', true ) ) ? '1' : '0',
			'event_author'     => $event->post_author,
			'event_approved'   => mc_convert_post_status_to_approval( $event->post_status ),
			'event_category'   => $category_ids,
			// Event organizers are only supported in My Calendar Pro.
			'event_host'       => $event->post_author,
			// meta data.
			'event_added'      => $event->post_date,
			'event_nonce_name' => wp_create_nonce( 'event_nonce' ),
			'event_group_id'   => mc_group_id(),
		);

		$venue_id = get_post_meta( $event->ID, '_EventVenueID', true );
		if ( $venue_id && 'tribe_venue' === get_post_type( $venue_id ) ) {
			$location_id = get_post_meta( $venue_id, '_mc_tribe_location_id', true );
			if ( ! $location_id ) {
				$location_id = mc_import_tribe_location( $venue_id );
			}
			$my_calendar_event['preset_location'] = $location_id;
		}
	}
	/**
	 * Filter event to be inserted from Tribe.
	 *
	 * @hook mc_format_tribe_event_for_import
	 *
	 * @param {array}  $my_calendar_event Array of data to be passed to mc_check_data.
	 * @param {object} $event Post object from tribe_events post type.
	 * @param {string} $type Type of data being returned; instance or event.
	 *
	 * @return {array}
	 */
	$my_calendar_event = apply_filters( 'mc_format_tribe_event_for_import', $my_calendar_event, $event, $type );

	return $my_calendar_event;
}

/**
 * Import a venue from Tribe to My Calendar.
 *
 * @param int $venue_id Post ID for a Tribe venue.
 *
 * @return int $location_id Location ID for a My Calendar location.
 */
function mc_import_tribe_location( $venue_id ) {
	// This is a double check before inserting.
	$check = get_post_meta( $venue_id, '_mc_tribe_location_id', true );
	if ( is_numeric( $check ) ) {
		return $check;
	}
	$state = ( get_post_meta( $venue_id, '_VenueState', true ) ) ? get_post_meta( $venue_id, '_VenueState', true ) : get_post_meta( $venue_id, '_VenueProvince', true );
	$venue = get_post( $venue_id );
	$add   = array(
		'location_label'     => $venue->post_title,
		'location_street'    => get_post_meta( $venue_id, '_VenueAddress', true ),
		'location_street2'   => '',
		'location_city'      => get_post_meta( $venue_id, '_VenueCity', true ),
		'location_state'     => $state,
		'location_postcode'  => get_post_meta( $venue_id, '_VenueZip', true ),
		'location_region'    => '',
		'location_country'   => get_post_meta( $venue_id, '_VenueCountry', true ),
		'location_url'       => get_post_meta( $venue_id, '_VenueURL', true ),
		'location_latitude'  => '',
		'location_longitude' => '',
		'location_zoom'      => 16,
		'location_phone'     => get_post_meta( $venue_id, '_VenuePhone', true ),
		'location_phone2'    => '',
		'location_access'    => '',
	);

	$location_id = mc_insert_location( $add );
	$location_id = apply_filters( 'mc_save_location', $location_id, $add, array() );
	if ( is_numeric( $location_id ) ) {
		// Only set the venue relationship if location ID is set.
		update_post_meta( $venue_id, '_mc_tribe_location_id', $location_id );

		// Set featured image for location.
		$featured_image_id = get_post_thumbnail_id( $venue_id );
		if ( $featured_image_id ) {
			$location_post = mc_get_location_post( $location_id );
			set_post_thumbnail( $location_post, $featured_image_id );
		}
		// Set location post content to venue content.
		$update_post = array(
			'ID'           => $location_post,
			'post_content' => $venue->post_content,
		);
		wp_update_post( $update_post );
	}

	return $location_id;
}

/**
 * Add tickets from Tribe when an event is added.
 *
 * @param int $tribe_id    Post ID for a Tribe event.
 * @param int $event_post  Event post for a calendar event.
 */
function mc_import_tribe_tickets( $tribe_id, $event_post ) {
	// If Event Tickets && My Tickets installed, migrate tickets data.
	if ( function_exists( 'tribe_events_has_tickets' ) && function_exists( 'mt_create_payment' ) ) {
		global $wpdb;
		$tribe_tickets = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_tribe_wooticket_for_event' AND meta_value = %d", $tribe_id ) );
		// Handle creation of tickets on event post.
		if ( is_array( $tribe_tickets ) && count( $tribe_tickets ) > 0 ) {
			// Get post meta for this data.
			$prices = array();
			foreach ( $tribe_tickets as $ticket ) {
				$ticket_id = $ticket->post_id;
				$title     = get_the_title( $ticket_id ); // Title is the label for ticket.
				$title_key = sanitize_key( $title );
				$sales     = get_post_meta( $ticket_id, 'total_sales', true ); // Sales on this ticket.
				$total     = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true ); // Number available.
				$price     = get_post_meta( $ticket_id, '_price', true ); // ticket price as int.
				$end       = get_post_meta( $ticket_id, '_ticket_end_date', true ); // ticket off sale date.
				// Structure prices array.
				$prices[ $title_key ] = array(
					'label'   => $title,
					'price'   => $price,
					'tickets' => $total,
					'sold'    => $sales,
					'close'   => strtotime( $end ),
				);
			}
			// Global My Calendar event data.
			$mc_event_data                      = get_post_meta( $event_post, '_mc_event_data', true );
			$mc_event_data['general_admission'] = ''; // Tribe events don't support general admission.
			$mc_event_data['event_valid']       = ''; // Event validity only applies to general admissions.
			$mc_event_data['expire_date']       = ''; // Expiration date for general admission.
			update_post_meta( $event_post, '_mc_event_data', $mc_event_data );
			// Set sales to expired if date in past.
			$begin = strtotime( $mc_event_data['event_begin'] . ' ' . $mc_event_data['event_time'] );
			if ( mt_date_comp( mt_date( 'Y-m-d H:i:s', $begin ), mt_date( 'Y-m-d H:i:s', mt_current_time() ) ) ) {
				update_post_meta( $event_post, '_mt_event_expired', 'true' );
			}
			update_post_meta( $event_post, '_mc_event_date', $begin );
			$registration_options = array(
				'reg_expires'     => 1,
				'sales_type'      => 'tickets',
				'counting_method' => 'discrete',
				'prices'          => $prices,
				'total'           => 'inherit',
				'multiple'        => 'true',
			);
			/**
			 * Filter My Tickets profile data before saving.
			 *
			 * @hook mc_import_tribe_tickets_options
			 *
			 * @param {array} $registration_options Options data to save for new event.
			 * @param {int}   $tribe_id Tribe event ID.
			 * @param {int}   $event_post My Calendar event post ID.
			 *
			 * @return {array}
			 */
			$registration_options = apply_filters( 'mc_import_tribe_tickets_options', $registration_options, $tribe_id, $event_post );
			update_post_meta( $event_post, '_mt_registration_options', $registration_options );
			update_post_meta( $event_post, '_mt_sell_tickets', 'true' );
		}
	}
}
