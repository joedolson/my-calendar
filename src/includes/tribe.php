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
 * @return string Message about imported events.
 */
function mc_import_source_tribe_events() {
	global $wpdb;
	$count   = wp_count_posts( 'tribe_events' );
	$message = '';
	$total   = 0;
	foreach ( $count as $c ) {
		$total = $total + (int) $c;
	}

	// Get selection of events not already imported.
	$events = $wpdb->get_results( "SELECT SQL_CALC_FOUND_ROWS $wpdb->posts.ID FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '_mc_imported' ) WHERE 1=1 AND ( $wpdb->postmeta.post_id IS NULL ) AND $wpdb->posts.post_type = 'tribe_events' AND (($wpdb->posts.post_status <> 'trash' AND $wpdb->posts.post_status <> 'auto-draft')) GROUP BY wp_posts.ID ORDER BY $wpdb->posts.post_date DESC LIMIT 0, 25" );
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
 * @param int $post_id ID of a tribe event post.
 *
 * @return bool|int False of new post ID.
 */
function mc_import_source_tribe_event( $post_id ) {
	// If already imported, return false.
	$imported = get_post_meta( $post_id, '_mc_imported', true );
	if ( $imported ) {
		return false;
	}
	$tribe_event = get_post( $post_id );
	/**
	 * Filter imported event to customize what gets added to database. Return false to skip event.
	 *
	 * @hook mc_imported_event
	 *
	 * @param {array} $event Array of event data passed to `mc_check_data`.
	 *
	 * @return {array|false}
	 */
	$event = apply_filters( 'mc_imported_event_tribe', $tribe_event );
	$event = mc_format_tribe_event_for_import( $event );
	if ( false === $event ) {
		return;
	}
	$count = count( $event['event_begin'] );
	for ( $i = 0; $i < $count; $i ++ ) {
		$check    = mc_check_data( 'add', $event, 0, true );
		$event_id = false;
		if ( $check[0] ) {
			$response = my_calendar_save( 'add', $check );
			$event_id = $response['event_id'];
			/**
			 * Perform an action after an event has been imported from Tribe Events Calendar to My Calendar.
			 *
			 * @hook my_calendar_imported_from_tribe
			 *
			 * @param {int} $post_id Post ID from Tribe Events.
			 * @param {int} $event_id Event ID from My Calendar.
			 */
			do_action( 'my_calendar_imported_from_tribe', $post_id, $event_id );
			update_post_meta( $post_id, '_mc_imported', $event_id );
		}
	}

	return $event_id;
}

/**
 * Adapt a Tribe event to the My Calendar import structure.
 *
 * @param object $event WordPress Post from Tribe Events.
 *
 * @return array Importable data for My Calendar.
 */
function mc_format_tribe_event_for_import( $event ) {
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

	$my_calendar_event = array(
		// Event data.
		'event_title'      => $event->post_title,
		'event_begin'      => array( gmdate( 'Y-m-d', strtotime( get_post_meta( $event->ID, '_EventStartDate', true ) ) ) ), // Pretty sure UTC is what I want.
		'event_end'        => array( gmdate( 'Y-m-d', strtotime( get_post_meta( $event->ID, '_EventEndDate', true ) ) ) ),
		'event_time'       => array( gmdate( 'H:i:00', strtotime( get_post_meta( $event->ID, '_EventStartDate', true ) ) ) ),
		'event_endtime'    => array( gmdate( 'H:i:00', strtotime( get_post_meta( $event->ID, '_EventEndDate', true ) ) ) ),
		'content'          => $event->post_content,
		'event_short'      => $event->post_excerpt,
		'event_link'       => get_post_meta( $event->ID, '_EventURL', true ),
		// Tribe recurring events work radically differently. Treat as event group?
		'event_image'      => get_the_post_thumbnail_url( $event ),
		'event_image_id'   => get_post_thumbnail_id( $event ),
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
	if ( $venue_id ) {
		$location_id = get_post_meta( $venue_id, '_mc_location_id', true );
		if ( ! $location_id ) {
			$location_id = mc_import_tribe_location( $venue_id );
		}
		$my_calendar_event['event_location'] = $location_id;
		$my_calendar_event['event_label']    = get_post( $venue_id )->post_title;
		$my_calendar_event['event_street']   = get_post_meta( $venue_id, '_VenueAddress', true );
		$my_calendar_event['event_city']     = get_post_meta( $venue_id, '_VenueCity', true );
		$my_calendar_event['event_state']    = get_post_meta( $venue_id, '_VenueState', true ); // province and state are separate fields in Tribe.
		$my_calendar_event['event_postcode'] = get_post_meta( $venue_id, '_VenueZip', true );
		$my_calendar_event['event_country']  = get_post_meta( $venue_id, '_VenueCountry', true );
		$my_calendar_event['event_url']      = get_post_meta( $venue_id, '_VenueURL', true );
		$my_calendar_event['event_phone']    = get_post_meta( $venue_id, '_VenuePhone', true );
	}
	/**
	 * Filter event to be inserted from Tribe.
	 *
	 * @hook mc_format_tribe_event_for_import
	 *
	 * @param {array}  $my_calendar_event Array of data to be passed to mc_check_data.
	 * @param {object} $event Post object from tribe_events post type.
	 *
	 * @return {array}
	 */
	$my_calendar_event = apply_filters( 'mc_format_tribe_event_for_import', $my_calendar_event, $event );

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
	$state = ( get_post_meta( $venue_id, '_VenueState', true ) ) ? get_post_meta( $venue_id, '_VenueState', true ) : get_post_meta( $venue_id, '_VenueProvince', true );
	$add   = array(
		'location_label'     => get_post( $venue_id )->post_title,
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
	update_post_meta( $venue_id, '_mc_location_id', $location_id );

	return $location_id;
}


/**
 * Import tickets from Tribe to My Calendar/My Tickets.
 *
 * @param int $tribe_id    Post ID for a Tribe event.
 * @param int $calendar_id Calendar ID for a My Calendar event.
 *
 * @return int Post ID for a My Calendar event.
 */
function mc_import_tribe_tickets( $tribe_id, $calendar_id ) {
	// If Event Tickets installed, migrate tickets data.
	if ( function_exists( 'tribe_events_has_tickets' ) ) {
		$post_id     = mc_get_event_post( $calendar_id );
		$has_tickets = tribe_events_has_tickets( $tribe_id );
		if ( $has_tickets ) {
			$ticket_capacity = get_post_meta( $tribe_id, '_tribe_ticket_capacity', true );
		}

		return $post_id;
	}
}
add_filter( 'my_calendar_imported_from_tribe', 'mc_import_tribe_tickets', 10, 2 );
