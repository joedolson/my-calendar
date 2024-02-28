<?php
/**
 * Event utilities. Event functions not directly related to display or management.
 *
 * @category Utilities
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check an event for any occurrence overlap problems. Used in admin only.
 *
 * @param integer $event_id Event ID.
 *
 * @return string with edit link to go to event.
 */
function mc_error_check( $event_id ) {
	$data      = mc_form_data( $event_id );
	$test      = ( $data ) ? mc_test_occurrence_overlap( $data, true ) : '';
	$args      = array(
		'mode'     => 'edit',
		'event_id' => $event_id,
	);
	$edit_link = ' <a href="' . esc_url( add_query_arg( $args, admin_url( 'admin.php?page=my-calendar' ) ) ) . '">' . __( 'Edit Event', 'my-calendar' ) . '</a>';
	$test      = ( '' !== $test ) ? str_replace( '</p></div>', "$edit_link</p></div>", $test ) : $test;

	return $test;
}

/**
 * Test whether an event has an invalid overlap.
 *
 * @param object  $data Event object.
 * @param boolean $should_return Return or echo.
 *
 * @return string Warning text about problem with event.
 */
function mc_test_occurrence_overlap( $data, $should_return = false ) {
	$warning = '';
	// If this event is single, skip query.
	$single_recur = ( 'S' === $data->event_recur || 'S1' === $data->event_recur ) ? true : false;
	// If event starts and ends on same day, skip query.
	$start_end = ( $data->event_begin === $data->event_end ) ? true : false;
	// Only run test when an event is set up to recur & starts/ends on different days.
	if ( ! $single_recur && ! $start_end ) {
		$check = mc_increment_event( $data->event_id, array(), true );
		if ( my_calendar_date_xcomp( $check['occur_begin'], $data->event_end . ' ' . $data->event_endtime ) ) {
			$warning = "<div class='error'><span class='problem-icon dashicons dashicons-performance' aria-hidden='true'></span> <p><strong>" . __( 'Event hidden from public view.', 'my-calendar' ) . '</strong> ' . __( 'This event ends after the next occurrence begins. Events must end <strong>before</strong> the next occurrence begins.', 'my-calendar' ) . '</p><p>';
			$enddate = date_i18n( mc_date_format(), strtotime( $data->event_end ) );
			$endtime = mc_date( mc_time_format(), strtotime( $data->event_endtime ), false );
			$begin   = date_i18n( mc_date_format(), strtotime( $check['occur_begin'] ) ) . ' ' . mc_date( mc_time_format(), strtotime( $check['occur_begin'] ), false );
			// Translators: End date, end time, beginning of next event.
			$warning .= sprintf( __( 'Event end date: <strong>%1$s %2$s</strong>. Next occurrence starts: <strong>%3$s</strong>', 'my-calendar' ), $enddate, $endtime, $begin ) . '</p></div>';
			update_post_meta( $data->event_post, '_occurrence_overlap', 'false' );
		} else {
			delete_post_meta( $data->event_post, '_occurrence_overlap' );
		}
	} else {
		// If event has been changed to same date, still delete meta.
		delete_post_meta( $data->event_post, '_occurrence_overlap' );
	}
	if ( $should_return ) {
		return $warning;
	} else {
		echo wp_kses_post( $warning );
	}

	return $warning;
}

/**
 * Find event that conflicts with newly scheduled events based on time and location.
 *
 * @param string $begin date of event.
 * @param string $time time of event.
 * @param string $end date of event.
 * @param string $endtime time of event.
 * @param int    $loc_id location of event.
 *
 * @return mixed results array or false
 */
function mcs_check_conflicts( $begin, $time, $end, $endtime, $loc_id ) {
	global $wpdb;
	$select_location = ( $loc_id ) ? "event_location = '" . absint( $loc_id ) . "' AND" : '';
	$begin_time      = $begin . ' ' . $time;
	$end_time        = $end . ' ' . $endtime;
	// Need two queries; one to find outer events, one to find inner events.
	$event_query = 'SELECT occur_id
					FROM ' . my_calendar_event_table() . '
					JOIN ' . my_calendar_table() . "
					ON (event_id=occur_event_id)
					WHERE $select_location " . '
					( occur_begin BETWEEN cast( \'%1$s\' AS DATETIME ) AND cast( \'%2$s\' AS DATETIME )
					OR occur_end BETWEEN cast( \'%3$s\' AS DATETIME ) AND cast( \'%4$s\' AS DATETIME ) )';

	$results = $wpdb->get_results( $wpdb->prepare( $event_query, $begin_time, $end_time, $begin_time, $end_time ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if ( empty( $results ) ) {
		// Finds events that conflict if they either start or end during another event.
		$event_query2 = 'SELECT occur_id
						FROM ' . my_calendar_event_table() . '
						JOIN ' . my_calendar_table() . "
						ON (event_id=occur_event_id)
						WHERE $select_location " . '
						( cast( \'%1$s\' AS DATETIME ) BETWEEN occur_begin AND occur_end
						OR cast( \'%2$s\' AS DATETIME ) BETWEEN occur_begin AND occur_end )';

		$results = $wpdb->get_results( $wpdb->prepare( $event_query2, $begin_time, $end_time ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	return ( ! empty( $results ) ) ? $results : false;
}
