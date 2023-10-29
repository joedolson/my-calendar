<?php
/**
 * Import process. Import data from other active calendar plugins.
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
 * Handle import of events from other calendar sources.
 *
 * @param string $source Source to import from.
 */
function my_calendar_import( $source ) {
	// Kieran O'Shea's calendar.
	if ( 'true' !== get_option( 'ko_calendar_imported' ) && 'calendar' === $source ) {
		mc_import_source_calendar();
	}
	if ( 'true' !== get_option( 'mc_tribe_imported' ) && 'tribe' === $source ) {
		mc_import_source_tribe_events();
	}
}

/**
 * Import Tribe Events.
 *
 * @return int Number of events imported.
 */
function mc_import_source_tribe_events() {
	$count = wp_count_posts( 'tribe_events' );
	$total = 0;
	foreach ( $count as $c ) {
		$total = $total + (int) $c;
	}
	if ( $total < 50 ) {
		$num_posts = -1;
	} else {
		$num_posts = 25;
	}
	// Get all events not already imported.
	$args   = array(
		'post_type'   => 'tribe_events',
		'numberposts' => $num_posts,
		'fields'      => 'ids',
		'post_status' => 'any',
		'meta_query'  => array(
			'queries' => array(
				'key'     => '_mc_imported',
				'compare' => 'NOT EXISTS',
			),
		),
	);
	$events = get_posts( $args );
	$ids    = array();
	foreach ( $events as $post_id ) {
		$id = mc_import_source_tribe_event( $post_id );
		if ( $id ) {
			$ids[] = $id;
		}
	}
	$completed = count( $ids );
	// translators: 1) Number of events imported, 2) total number of events found.
	echo '<div class="notice notice-success"><p>' . sprintf( __( '%1$d events imported. %2$d remaining. Remaining events are being imported in the background. You can feel free to leave this page.', 'my-calendar' ), $completed, $total ) . '</p></div>';

	return $completed;
}

/**
 * Import an event from Tribe Events Calendar.
 *
 * @param int $post_id ID of a tribe event post.
 *
 * @return bool|int False of new post ID.
 */
function mc_import_source_tribe_event( $post_id ) {
	// If already imported, return the new event ID.
	$imported = get_post_meta( $post_id, '_mc_imported', true );
	if ( $imported ) {
		return $imported;
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

	$check    = mc_check_data( 'add', $event, 0, true );
	$event_id = false;
	if ( $check[0] ) {
		$response = my_calendar_save( 'add', $check );
		$event_id = $response['event_id'];
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
 * Convert a post status to an approval type.
 *
 * @param string $status Post status.
 *
 * @return int Approval value.
 */
function mc_convert_post_status_to_approval( $status ) {
	switch ( $status ) {
		case 'publish':
			$approval = 1;
			break;
		case 'draft':
			$approval = 0;
			break;
		case 'trash':
			$approval = 2;
			break;
		default:
			$approval = 0;
	}

	return $approval;
}

/**
 * Import events from Kieran O'Shea's "Calendar". Largely obsolete.
 */
function mc_import_source_calendar() {
	global $wpdb;
	$events         = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'calendar', 'ARRAY_A' );
	$event_ids      = array();
	$events_results = false;
	foreach ( $events as $key ) {
		$endtime        = ( '00:00:00' === $key['event_time'] ) ? '00:00:00' : date( 'H:i:s', strtotime( "$key[event_time] +1 hour" ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$data           = array(
			'event_title'    => $key['event_title'],
			'event_desc'     => $key['event_desc'],
			'event_begin'    => $key['event_begin'],
			'event_end'      => $key['event_end'],
			'event_time'     => $key['event_time'],
			'event_endtime'  => $endtime,
			'event_recur'    => $key['event_recur'],
			'event_repeats'  => $key['event_repeats'],
			'event_author'   => $key['event_author'],
			'event_category' => $key['event_category'],
			'event_hide_end' => 1,
			'event_link'     => ( isset( $key['event_link'] ) ) ? $key['event_link'] : '',
		);
		$format         = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s' );
		$update         = $wpdb->insert( my_calendar_table(), $data, $format );
		$events_results = ( $update ) ? true : false;
		$event_ids[]    = $wpdb->insert_id;
	}
	foreach ( $event_ids as $value ) { // propagate event instances.
		$sql   = 'SELECT event_begin, event_time, event_end, event_endtime FROM ' . my_calendar_table() . ' WHERE event_id = %d';
		$event = $wpdb->get_results( $wpdb->prepare( $sql, $value ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$event = $event[0];
		$dates = array(
			'event_begin'   => $event->event_begin,
			'event_end'     => $event->event_end,
			'event_time'    => $event->event_time,
			'event_endtime' => $event->event_endtime,
		);
		mc_increment_event( $value, $dates );
	}
	$cats         = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'calendar_categories', 'ARRAY_A' );
	$cats_results = false;
	foreach ( $cats as $key ) {
		$name         = esc_sql( $key['category_name'] );
		$color        = esc_sql( $key['category_colour'] );
		$id           = (int) $key['category_id'];
		$catsql       = 'INSERT INTO ' . my_calendar_categories_table() . ' SET category_id=%1$d, category_name=%2$s, category_color=%3$s ON DUPLICATE KEY UPDATE category_name=%2$s, category_color=%3$s;';
		$cats_results = $wpdb->query( $wpdb->prepare( $catsql, $id, $name, $color ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	$message   = ( false !== $cats_results ) ? __( 'Categories imported successfully.', 'my-calendar' ) : __( 'Categories not imported.', 'my-calendar' );
	$e_message = ( false !== $events_results ) ? __( 'Events imported successfully.', 'my-calendar' ) : __( 'Events not imported.', 'my-calendar' );
	$return    = "<div id='message' class='notice notice-success'><ul><li>$message</li><li>$e_message</li></ul></div>";
	echo wp_kses_post( $return );
	if ( false !== $cats_results && false !== $events_results ) {
		update_option( 'ko_calendar_imported', 'true' );
	}
}
