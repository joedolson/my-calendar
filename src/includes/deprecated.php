<?php
/**
 * This file holds functions that have been removed or deprecated,
 * but are kept in case 3rd party code is using the function independently.
 *
 * @category Utilities
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

// Define the table constants used in My Calendar in case anybody is still using them.
// These were eliminated some time ago.
if ( is_multisite() && get_site_option( 'mc_multisite_show' ) == 1 ) {
	define( 'MY_CALENDAR_TABLE', $wpdb->base_prefix . 'my_calendar' );
	define( 'MY_CALENDAR_EVENTS_TABLE', $wpdb->base_prefix . 'my_calendar_events' );
	define( 'MY_CALENDAR_CATEGORIES_TABLE', $wpdb->base_prefix . 'my_calendar_categories' );
	define( 'MY_CALENDAR_LOCATIONS_TABLE', $wpdb->base_prefix . 'my_calendar_locations' );
} else {
	define( 'MY_CALENDAR_TABLE', $wpdb->prefix . 'my_calendar' );
	define( 'MY_CALENDAR_EVENTS_TABLE', $wpdb->prefix . 'my_calendar_events' );
	define( 'MY_CALENDAR_CATEGORIES_TABLE', $wpdb->prefix . 'my_calendar_categories' );
	define( 'MY_CALENDAR_LOCATIONS_TABLE', $wpdb->prefix . 'my_calendar_locations' );
}

if ( is_multisite() ) {
	// Define the tables used in My Calendar.
	define( 'MY_CALENDAR_GLOBAL_TABLE', $wpdb->base_prefix . 'my_calendar' );
	define( 'MY_CALENDAR_GLOBAL_EVENT_TABLE', $wpdb->base_prefix . 'my_calendar_events' );
	define( 'MY_CALENDAR_GLOBAL_CATEGORIES_TABLE', $wpdb->base_prefix . 'my_calendar_categories' );
	define( 'MY_CALENDAR_GLOBAL_LOCATIONS_TABLE', $wpdb->base_prefix . 'my_calendar_locations' );
}

/**
 * Caching has been disabled by default with no option to enable for some time.
 * Leaving functions, but they will only return false.
 *
 * @param int    $category Deprecated.
 * @param string $ltype Deprecated.
 * @param string $lvalue Deprecated.
 * @param string $author Deprecated.
 * @param int    $host Deprecated.
 * @param int    $hash Deprecated.
 *
 * @deprecated
 */
function mc_check_cache( $category, $ltype, $lvalue, $author, $host, $hash ) {
	return false;
}

/**
 * Caching has been disabled by default with no option to enable for some time.
 * Leaving functions, but they will only return false.
 *
 * @param string $cache Deprecated.
 * @param int    $category Deprecated.
 * @param string $ltype Deprecated.
 * @param string $lvalue Deprecated.
 * @param string $auth Deprecated.
 * @param int    $host Deprecated.
 *
 * @deprecated
 */
function mc_clean_cache( $cache, $category, $ltype, $lvalue, $auth, $host ) {
	return false;
}

/**
 * Caching has been disabled by default with no option to enable for some time.
 * Leaving functions, but they will only return false.
 *
 * @param array  $arr_events Deprecated.
 * @param string $hash Deprecated.
 * @param int    $category Deprecated.
 * @param string $ltype Deprecated.
 * @param string $lvalue Deprecated.
 * @param int    $author Deprecated.
 * @param int    $host Deprecated.
 *
 * @deprecated
 */
function mc_create_cache( $arr_events, $hash, $category, $ltype, $lvalue, $author, $host ) {
	return false;
}

/**
 * Caching has been disabled by default with no option to enable for some time.
 * Leaving functions, but they will only return false.
 *
 * @deprecated
 */
function mc_delete_cache() {
	// doesn't do anything anymore.
}

/**
 * Caching has been disabled by default with no option to enable for some time.
 * Leaving functions, but they will only return false.
 *
 * @param string $cache Deprecated.
 *
 * @deprecated
 */
function mc_get_cache( $cache ) {
	return false;
}

/**
 * Caching has been disabled by default with no option to enable for some time.
 * Leaving functions, but they will only return false.
 *
 * @param string $cache Deprecated.
 * @param string $time Deprecated.
 *
 * @deprecated
 */
function mc_set_cache( $cache, $time ) {
	// doesn't do anything.
}

/**
 * Caching has been disabled by default with no option to enable for some time.
 * Leaving functions, but they will only return false.
 *
 * @param string $cache Deprecated.
 *
 * @deprecated
 */
function mc_remove_cache( $cache ) {
	// doesn't do anything.
}

/**
 * Old support box function
 *
 * @see mc_show_sidebar()
 * @deprecated
 */
function jd_show_support_box() {
	mc_show_sidebar();
}

/**
 * Odd toggle. Unknown when last used.
 *
 * @param int $int Any integer.
 *
 * @deprecated
 *
 * @return boolean
 */
function my_calendar_is_odd( $int ) {
	return ( $int & 1 );
}

/**
 * Get label for "forever" events (no longer exist.)
 *
 * @param string $recur Recurrence string (single character).
 * @param int    $repeats Number of occurrences to repeat.
 *
 * @deprecated 2.5.16
 *
 * @return string label
 */
function mc_event_repeats_forever( $recur, $repeats ) {
	if ( 'S' != $recur && 0 == $repeats ) {
		return true;
	}
	switch ( $recur ) {
		case 'S': // single.
			return false;
			break;
		case 'D': // daily.
			return ( 500 == $repeats ) ? true : false;
			break;
		case 'W': // weekly.
			return ( 240 == $repeats ) ? true : false;
			break;
		case 'B': // biweekly.
			return ( 120 == $repeats ) ? true : false;
			break;
		case 'M': // monthly.
		case 'U':
			return ( 60 == $repeats ) ? true : false;
			break;
		case 'Y':
			return ( 5 == $repeats ) ? true : false;
			break;
		default:
			return false;
	}
}

if ( ! function_exists( 'is_ssl' ) ) {
	/**
	 * Try to check whether site is running in an HTTPS environment.
	 *
	 * Currently used only in My Calendar PRO; exists in both for back compat
	 */
	function is_ssl() {
		if ( isset( $_SERVER['HTTPS'] ) ) {
			if ( 'on' == strtolower( $_SERVER['HTTPS'] ) ) {
				return true;
			}
			if ( '1' == $_SERVER['HTTPS'] ) {
				return true;
			}
		} elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
			return true;
		}

		return false;
	}
}

/**
 * Old name of template drawing function
 *
 * @see mc_draw_template()
 *
 * @param array  $array Associative Array of information.
 * @param string $template String containing tags.
 * @param string $type Type of display.
 *
 * @return string
 */
function jd_draw_template( $array, $template, $type = 'list' ) {

	return mc_draw_template( $array, $template, $type );
}

/**
 * Test whether two dates are day-consecutive
 * not used per audit 3/1/2018
 *
 * @param string $current date string.
 * @param string $last_date previous date.
 *
 * @return boolean
 */
function mc_dates_consecutive( $current, $last_date ) {
	if ( strtotime( $last_date . '+ 1 day' ) == strtotime( $current ) ) {

		return true;
	} else {

		return false;
	}
}
/**
 * Reverse Function to compare datetime in event objects
 *
 * @param object $b Event object.
 * @param object $a Event object.
 *
 * @return int (ternary value)
 */
function my_calendar_reverse_datetime_cmp( $b, $a ) {
	$event_dt_a = strtotime( $a->occur_begin );
	$event_dt_b = strtotime( $b->occur_begin );
	if ( $event_dt_a == $event_dt_b ) {
		return 0;
	}

	return ( $event_dt_a < $event_dt_b ) ? - 1 : 1;
}

/**
 * Compare two dates for diff
 *
 * @param string $start date string.
 * @param string $end datee string.
 *
 * @deprecated
 *
 * @return diff
 */
function jd_date_diff( $start, $end = 'NOW' ) {
	$sdate = strtotime( $start );
	$edate = strtotime( $end );

	$time = $edate - $sdate;
	if ( $time < 86400 && $time > - 86400 ) {
		return false;
	} else {
		$pday   = ( $edate - $sdate ) / 86400;
		$preday = explode( '.', $pday );

		return $preday[0];
	}
}

/**
 * Function to find the start date of a week in a year
 *
 * @param integer $week The week number of the year.
 * @param integer $year The year of the week we need to calculate on.
 *
 * @return integer The unix timestamp of the date is returned
 */
function get_week_date( $week, $year ) {
	// Get the target week of the year with reference to the starting day of the year.
	$start_of_week = ( get_option( 'start_of_week' ) == 1 || get_option( 'start_of_week' ) == 0 ) ? get_option( 'start_of_week' ) : 0;
	$target_week   = strtotime( "$week week", strtotime( "1 January $year" ) );
	$date_info     = getdate( $target_week );
	$day_of_week   = $date_info['wday'];
	// normal start day of the week is Monday.
	$adjusted_date = $day_of_week - $start_of_week;
	// Get the timestamp of that day.
	$first_day = strtotime( "-$adjusted_date day", $target_week );

	return $first_day;
}

/**
 * Add days to a given date
 *
 * @param string $givendate original date.
 * @param int    $day days to add.
 *
 * @return new date
 */
function add_days_to_date( $givendate, $day = 0 ) {
	$cd      = strtotime( $givendate );
	$time    = mktime( date( 'h', $cd ), date( 'i', $cd ), date( 's', $cd ), date( 'm', $cd ), date( 'd', $cd ) + $day, date( 'Y', $cd ) );
	$newdate = date( 'Y-m-d h:i:s', $time );

	return $newdate;
}
