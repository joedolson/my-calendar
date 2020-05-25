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
if ( is_multisite() && get_site_option( 'mc_multisite_show' ) === '1' ) {
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
	$purchase_url = 'https://www.joedolson.com/awesome/my-calendar-pro/';
	$check_url    = 'https://www.joedolson.com/login/';
	$add          = array(
		'My Calendar Pro out of date!' => '<p>' . __( 'The version of My Calendar Pro (or My Calendar Submissions) you have installed is very out of date!', 'my-calendar' ) . '</p><p>' . __( 'The latest version of My Calendar Pro is the only version recommended for compatibility with My Calendar. Please <a href="$1%s">purchase an upgrade</a> or <a href="$2%s">login to check your license status</a>!', 'my-calendar' ) . '</p>',
	);
	mc_show_sidebar( '', $add, true );
}

/**
 * Get label for "forever" events (no longer exist.)
 *
 * @param string $recur Recurrence string (single character).
 * @param int    $repeats Number of occurrences to repeat.
 *
 * @deprecated 2.5.16. Last used 2.4.21.
 *
 * @return string label
 */
function mc_event_repeats_forever( $recur, $repeats ) {
	$repeats = absint( $repeats );
	if ( 'S' !== $recur && 0 === $repeats ) {
		return true;
	}
	switch ( $recur ) {
		case 'S': // single.
			return false;
			break;
		case 'D': // daily.
			return ( 500 === $repeats ) ? true : false;
			break;
		case 'W': // weekly.
			return ( 240 === $repeats ) ? true : false;
			break;
		case 'B': // biweekly.
			return ( 120 === $repeats ) ? true : false;
			break;
		case 'M': // monthly.
		case 'U':
			return ( 60 === $repeats ) ? true : false;
			break;
		case 'Y':
			return ( 5 === $repeats ) ? true : false;
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
			if ( 'on' === strtolower( $_SERVER['HTTPS'] ) ) {
				return true;
			}
			if ( '1' === $_SERVER['HTTPS'] ) {
				return true;
			}
		} elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' === (string) $_SERVER['SERVER_PORT'] ) ) {
			return true;
		}

		return false;
	}
}

/**
 * Old name of template drawing function. Deprecated 6/14/2018. Removed in Pro 3/31/2019.
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
 * Function to find the start date of a week in a year
 *
 * @param integer $week The week number of the year.
 * @param integer $year The year of the week we need to calculate on.
 *
 * @return integer The unix timestamp of the date is returned
 */
function get_week_date( $week, $year ) {
	// Get the target week of the year with reference to the starting day of the year.
	$start_of_week = ( get_option( 'start_of_week' ) === '1' || get_option( 'start_of_week' ) === '0' ) ? get_option( 'start_of_week' ) : 0;
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
	$time    = mktime( mc_date( 'h', $cd ), mc_date( 'i', $cd ), mc_date( 's', $cd ), mc_date( 'm', $cd ), mc_date( 'd', $cd ) + $day, mc_date( 'Y', $cd ) );
	$newdate = mc_date( 'Y-m-d h:i:s', $time );

	return $newdate;
}
