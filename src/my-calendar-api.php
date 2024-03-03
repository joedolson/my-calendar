<?php
/**
 * My Calendar API - get events outside of My Calendar UI
 *
 * @category Events
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main API function
 */
function my_calendar_api() {
	if ( isset( $_REQUEST['my-calendar-api'] ) || isset( $_REQUEST['mc-api'] ) ) {
		if ( 'true' === mc_get_option( 'api_enabled' ) ) {
			/**
			 * Filter to test access to the event API. Default 'true'.
			 *
			 * @hook mc_api_key
			 *
			 * @param {bool} $api_key Return true to allow access. Use filter to control or limit access.
			 *
			 * @return {bool}
			 */
			$api_key = apply_filters( 'mc_api_key', true );
			if ( $api_key ) {
				$format = ( isset( $_REQUEST['my-calendar-api'] ) ) ? $_REQUEST['my-calendar-api'] : 'json';
				$format = ( isset( $_REQUEST['mc-api'] ) ) ? $_REQUEST['mc-api'] : $format;
				$from   = ( isset( $_REQUEST['from'] ) ) ? $_REQUEST['from'] : current_time( 'Y-m-d' );
				$range  = '+ 7 days';
				/**
				 * Default date for API 'to' parameter. Default '+ 7 days'.
				 *
				 * @hook mc_api_auto_date
				 *
				 * @param {string} $time A time string convertable using strtotime.
				 *
				 * @return {string}
				 */
				$adjust = apply_filters( 'mc_api_auto_date', $range );
				$to     = ( isset( $_REQUEST['to'] ) ) ? $_REQUEST['to'] : mc_date( 'Y-m-d', strtotime( $adjust ) );
				// sanitization is handled elsewhere.
				$category = ( isset( $_REQUEST['mcat'] ) ) ? $_REQUEST['mcat'] : '';
				$ltype    = ( isset( $_REQUEST['ltype'] ) ) ? $_REQUEST['ltype'] : '';
				$lvalue   = ( isset( $_REQUEST['lvalue'] ) ) ? $_REQUEST['lvalue'] : '';
				$author   = ( isset( $_REQUEST['author'] ) ) ? $_REQUEST['author'] : '';
				$host     = ( isset( $_REQUEST['host'] ) ) ? $_REQUEST['host'] : '';
				$search   = ( isset( $_REQUEST['search'] ) ) ? $_REQUEST['search'] : '';
				$args     = array(
					'from'     => $from,
					'to'       => $to,
					'category' => $category,
					'ltype'    => $ltype,
					'lvalue'   => $lvalue,
					'author'   => $author,
					'host'     => $host,
					'search'   => $search,
					'source'   => 'api',
				);
				/**
				 * Filter arguments submitted to the API.
				 *
				 * @hook mc_filter_api_args
				 *
				 * @param {array} $args Keys: ['from', 'to', 'category', 'ltype', 'lvalue', 'author', 'host', 'search'].
				 * @param {array} $request $_REQUEST parameters, sanitized.
				 *
				 * @return {array}
				 */
				$args   = apply_filters( 'mc_filter_api_args', $args, map_deep( $_REQUEST, 'sanitize_text_field' ) );
				$data   = my_calendar_events( $args );
				$output = mc_format_api( $data, $format );
				echo wp_kses_post( $output );
			}
			die;
		} else {
			esc_html_e( 'The My Calendar API is not enabled.', 'my-calendar' );
		}
	}
}

/**
 * Check which format the API should return
 *
 * @param array  $data Array of event objects.
 * @param string $format Format to return.
 */
function mc_format_api( $data, $format ) {
	switch ( $format ) {
		case 'json':
			mc_api_format_json( $data );
			break;
		case 'csv':
			mc_api_format_csv( $data );
			break;
		case 'ical':
			mc_api_format_ical( $data );
			break;
	}
}

/**
 * JSON formatted events
 *
 * @param array $data array of event objects.
 */
function mc_api_format_json( $data ) {
	wp_send_json( $data );
}

/**
 * CSV formatted events
 *
 * @param array $data array of event objects.
 */
function mc_api_format_csv( $data ) {
	if ( ob_get_contents() ) {
		ob_clean();
	}
	ob_start();
	$keyed = false;
	// Create a stream opening it with read / write mode.
	$stream = fopen( 'php://output', 'w' );
	// Iterate over the data, writing each line to the text stream.
	foreach ( $data as $key => $val ) {
		foreach ( $val as $v ) {
			$values = get_object_vars( $v );
			unset( $values['categories'] );
			unset( $values['location'] );
			$values['UID'] = $values['uid'];
			// If this is an import from Pro, insert locations into DB.
			if ( ! ( isset( $_GET['file'] ) && 'false' === $_GET['file'] ) ) {
				$values['event_category'] = $values['category_name'];
				unset( $values['uid'] );
				unset( $values['site_id'] );
				unset( $values['ts_occur_end'] );
				unset( $values['ts_occur_begin'] );
				unset( $values['category_term'] );
				unset( $values['category_id'] );
				unset( $values['category_name'] );
				unset( $values['event_group'] );
				unset( $values['event_location'] );
				unset( $values['event_post'] );
				unset( $values['occur_event_id'] );
				unset( $values['occur_group_id'] );
				unset( $values['event_id'] );
			}

			foreach ( $values as $key => $text ) {
				$values[ $key ] = str_replace( array( "\r\n", "\r", "\n" ), '<br class="mc-export" />', trim( $text ) );
			}
			if ( ! $keyed ) {
				$keys = array_keys( $values );
				fputcsv( $stream, $keys );
				$keyed = true;
			}
			fputcsv( $stream, $values );
		}
	}
	// Rewind the stream.
	rewind( $stream );
	// You can now echo its content.
	if ( ! ( isset( $_GET['file'] ) && 'false' === $_GET['file'] ) ) {
		// If accessing remotely as content.
		header( 'Content-type: text/csv' );
		header( 'Content-Disposition: attachment; filename=my-calendar.csv' );
	}
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	echo stream_get_contents( $stream );
	// Close the stream.
	fclose( $stream );
	ob_end_flush();
	die;
}

/**
 * Export single event as iCal file
 */
function mc_export_vcal() {
	if ( isset( $_GET['vcal'] ) ) {
		$vcal = absint( $_GET['vcal'] );
		print wp_kses_post( my_calendar_send_vcal( $vcal ) );
		die;
	}
}

/**
 * Send iCal event to browser
 *
 * @param integer $event_id Event ID.
 *
 * @return string headers & text for iCal event.
 */
function my_calendar_send_vcal( $event_id ) {
	$sitename = sanitize_title( get_bloginfo( 'name' ) );
	header( 'Content-Type: text/calendar' );
	header( 'Cache-control: private' );
	header( 'Pragma: private' );
	header( 'Expires: Thu, 11 Nov 1977 05:40:00 GMT' ); // That's my birthday. :).
	header( "Content-Disposition: inline; filename=my-calendar-$event_id-$sitename.ics" );
	$output = mc_generate_vcal( $event_id );

	return $output;
}

/**
 * Generate iCal formatted event for one event
 *
 * @param int $event_id Event ID or false to get active event.
 *
 * @return string text for iCal
 */
function mc_generate_vcal( $event_id ) {
	$output = '';
	$mc_id  = ( isset( $_GET['vcal'] ) ) ? (int) str_replace( 'mc_', '', $_GET['vcal'] ) : $event_id;
	if ( $mc_id ) {
		$event  = mc_get_event( $mc_id );
		$output = mc_generate_ical( array( $event ) );
	}

	return $output;
}

/**
 * Generate an iCal subscription export with most recently added events by category.
 */
function mc_ics_subscribe() {
	// get event category.
	if ( isset( $_GET['mcat'] ) ) {
		$cat_id = (int) $_GET['mcat'];
	} else {
		$cat_id = false;
	}
	$events = mc_get_new_events( $cat_id );

	mc_api_format_ical( $events );
}

/**
 * Generate Google subscribe feed data.
 */
function mc_ics_subscribe_google() {
	mc_ics_subscribe( 'google' );
}

/**
 * Generate Outlook subscribe feed data.
 */
function mc_ics_subscribe_outlook() {
	mc_ics_subscribe( 'outlook' );
}

/**
 * Generate ICS export of current period of events
 */
function my_calendar_ical() {
	$p   = ( isset( $_GET['span'] ) ) ? 'year' : false;
	$y   = ( isset( $_GET['yr'] ) ) ? absint( $_GET['yr'] ) : mc_date( 'Y' );
	$m   = ( isset( $_GET['month'] ) ) ? absint( $_GET['month'] ) : mc_date( 'n' );
	$ny  = ( isset( $_GET['nyr'] ) ) ? absint( $_GET['nyr'] ) : $y;
	$nm  = ( isset( $_GET['nmonth'] ) ) ? absint( $_GET['nmonth'] ) : $m;
	$cat = ( isset( $_GET['mcat'] ) ) ? intval( $_GET['mcat'] ) : '';

	if ( $p ) {
		$from = "$y-1-1";
		$to   = "$y-12-31";
	} else {
		$d    = mc_date( 't', mktime( 0, 0, 0, $m, 1, $y ), false );
		$from = "$y-$m-1";
		$to   = "$ny-$nm-$d";
	}

	/**
	 * Filter iCal download 'from' date.
	 *
	 * @hook mc_ical_download_from
	 *
	 * @param {string} $from Date string.
	 * @param {string} $p Date span.
	 *
	 * @return {string}
	 */
	$from = apply_filters( 'mc_ical_download_from', $from, $p );
	/**
	 * Filter iCal download 'to' date.
	 *
	 * @hook mc_ical_download_to
	 *
	 * @param {string} $from Date string.
	 * @param {string} $p Date span.
	 *
	 * @return {string}
	 */
	$to   = apply_filters( 'mc_ical_download_to', $to, $p );
	$site = ( ! isset( $_GET['site'] ) ) ? get_current_blog_id() : intval( $_GET['site'] );
	$args = array(
		'from'     => $from,
		'to'       => $to,
		'category' => $cat,
		'ltype'    => '',
		'lvalue'   => '',
		'author'   => null,
		'host'     => null,
		'search'   => '',
		'source'   => 'calendar',
		'site'     => $site,
	);

	/**
	 * Filter calendar arguments for iCal output.
	 *
	 * @hook mc_ical_attributes
	 *
	 * @param {array} $args Array of calendar query args.
	 * @param {array} $get GET parameters, sanitized.
	 *
	 * @return {array}
	 */
	$args = apply_filters( 'mc_ical_attributes', $args, map_deep( $_GET, 'sanitize_text_field' ) );
	// Load search result from $_SESSION array.
	if ( isset( $_GET['searched'] ) && $_GET['searched'] && isset( $_SESSION['MC_SEARCH_RESULT'] ) ) {
		$data = mc_get_searched_events();
	} else {
		$data = my_calendar_events( $args );
	}

	mc_api_format_ical( $data );
}

/**
 * Output iCal formatted events
 *
 * @param array $data array of event objects.
 */
function mc_api_format_ical( $data ) {
	$events = mc_flatten_array( $data );
	$output = mc_generate_ical( $events );

	if ( ! ( isset( $_GET['sync'] ) && 'true' === $_GET['sync'] ) ) {
		$sitename = sanitize_title( get_bloginfo( 'name' ) );
		header( 'Content-Type: text/calendar; charset=UTF-8' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		header( "Content-Disposition: inline; filename=my-calendar-$sitename.ics" );
	}

	echo wp_kses_post( $output );
}

/**
 * Translate a My Calendar recurrence pattern into an ical RRULE.
 *
 * @param object $event Event object.
 *
 * @return string
 */
function mc_generate_rrule( $event ) {
	$rrule  = '';
	$by     = '';
	$repeat = $event->event_repeats;
	$month  = mc_date( 'm', strtotime( $event->event_begin ), false );
	$day    = mc_date( 'd', strtotime( $event->event_begin ), false );
	$numday = mc_recur_date( mc_date( 'Y-m-d', strtotime( $event->event_begin ), false ) );
	$recurs = str_split( $event->event_recur, 1 );
	$recur  = $recurs[0];
	$every  = ( isset( $recurs[1] ) ) ? str_replace( $recurs[0], '', $event->event_recur ) : 1;

	switch ( $recur ) {
		case 'S':
			$rrule = '';
			break;
		case 'D':
			$rrule = 'FREQ=DAILY';
			break;
		case 'E':
			$rrule = 'FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR';
			break;
		case 'W':
			$rrule = 'FREQ=WEEKLY';
			break;
		case 'B':
			$rrule = 'FREQ=WEEKLY'; // interval = 2.
			break;
		case 'M':
			$by    = 'BYMONTHDAY=' . $day;
			$rrule = 'FREQ=MONTHLY';
			break;
		case 'U':
			$fifth = $event->event_fifth_week;
			if ( '1' === $fifth && 5 === $numday['num'] ) {
				$by = 'BYDAY=-1' . strtoupper( substr( $numday['day'], 0, 2 ) );
			} else {
				$by = 'BYDAY=' . $numday['num'] . strtoupper( substr( $numday['day'], 0, 2 ) );
			}
			$rrule = 'FREQ=MONTHLY'; // Calculate which day/week the first date is for BYDAY= pattern.
			break;
		case 'Y':
			$by    = 'BYMONTH=' . $month . ';BYDAY=' . $day;
			$rrule = 'FREQ=YEARLY';
			break;
	}

	$interval = ( '1' !== $every ) ? 'INTERVAL=' . $every : '';

	if ( is_numeric( $repeat ) ) {
		$until = 'COUNT=' . $repeat;
	} else {
		$until = 'UNTIL=' . mc_date( 'Ymd\THis', strtotime( $repeat ), false ) . 'Z';
	}

	$rrule = ( '' !== $rrule ) ? "$rrule;$by;$interval;$until" : '';
	$rrule = str_replace( array( ';;;', ';;' ), ';', $rrule );

	return $rrule;
}

/**
 * Generate alert parameters for an iCal event.
 *
 * @param array $alarm Parameters for describing an alarm.
 *
 * @return string iCal alert block.
 */
function mc_generate_alert_ical( $alarm ) {
	$defaults = array(
		'TRIGGER'     => '-PT30M',
		'REPEAT'      => '0',
		'DURATION'    => '',
		'ACTION'      => 'DISPLAY',
		'DESCRIPTION' => '{title}',
	);

	$values = array_merge( $defaults, $alarm );
	$alert  = PHP_EOL . 'BEGIN:VALARM' . PHP_EOL;
	$alert .= "TRIGGER:$values[TRIGGER]\n";
	$alert .= ( '0' !== $values['REPEAT'] ) ? "REPEAT:$values[REPEAT]\n" : '';
	$alert .= ( '' !== $values['DURATION'] ) ? "REPEAT:$values[DURATION]\n" : '';
	$alert .= "ACTION:$values[ACTION]\n";
	$alert .= "DESCRIPTION:$values[DESCRIPTION]\n";
	$alert .= 'END:VALARM';

	return $alert;
}

/**
 * Get events via the REST API.
 */
function my_calendar_rest_events() {
	register_rest_route(
		'my-calendar/v1',
		'/events/',
		array(
			'methods'             => 'GET',
			'callback'            => 'my_calendar_rest_route',
			'args'                => array(
				'from'     => array(
					'default' => current_time( 'Y-m-d' ),
				),
				'to'       => array(
					'default' => mc_date( 'Y-m-d', strtotime( '+ 7 days' ) ),
				),
				'category' => array(),
				'author'   => array(),
				'host'     => array(),
				'search'   => array(),
				'ltype'    => array(),
				'lvalue'   => array(),
			),
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'my_calendar_rest_events' );

/**
 * Convert REST query into My Calendar event query.
 *
 * @param WP_REST_Request $request Request object.
 */
function my_calendar_rest_route( WP_REST_Request $request ) {
	$parameters = $request->get_params();
	$from       = sanitize_text_field( $parameters['from'] );
	$to         = sanitize_text_field( $parameters['to'] );
	$category   = isset( $parameters['category'] ) ? absint( $parameters['category'] ) : '';
	$ltype      = isset( $parameters['ltype'] ) ? sanitize_text_field( $parameters['ltype'] ) : '';
	$lvalue     = isset( $parameters['lvalue'] ) ? sanitize_text_field( $parameters['lvalue'] ) : '';
	$author     = isset( $parameters['author'] ) ? sanitize_text_field( $parameters['author'] ) : '';
	$host       = isset( $parameters['host'] ) ? sanitize_text_field( $parameters['host'] ) : '';
	$search     = isset( $parameters['search'] ) ? sanitize_text_field( $parameters['search'] ) : '';
	$args       = array(
		'from'     => $from,
		'to'       => $to,
		'category' => $category,
		'ltype'    => $ltype,
		'lvalue'   => $lvalue,
		'author'   => $author,
		'host'     => $host,
		'search'   => $search,
		'source'   => 'api',
	);
	$events     = my_calendar_events( $args );

	return $events;
}
