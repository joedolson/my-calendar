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

require __DIR__ . '/includes/tribe.php';

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
 * Display current progress in importing.
 *
 * @return string
 */
function mc_display_progress() {
	$message = '';
	if ( as_has_scheduled_action( 'mc_import_tribe' ) ) {
		$count = mc_count_tribe_remaining();
		// translators: Number of events remaining.
		$message = sprintf( __( 'Import from The Events Calendar is in progress. There are currently %d events remaining.', 'my-calendar' ), $count );
	}

	return ( $message ) ? '<div class="notice notice-info"><p>' . $message . '</p></div>' : '';
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
