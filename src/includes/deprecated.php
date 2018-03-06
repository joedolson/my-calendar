<?php
/**
 * This file holds functions that have been removed or deprecated,
 * but are kept in case 3rd party code is using the function independently.
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
	// Define the tables used in My Calendar
	define( 'MY_CALENDAR_GLOBAL_TABLE', $wpdb->base_prefix . 'my_calendar' );
	define( 'MY_CALENDAR_GLOBAL_EVENT_TABLE', $wpdb->base_prefix . 'my_calendar_events' );
	define( 'MY_CALENDAR_GLOBAL_CATEGORIES_TABLE', $wpdb->base_prefix . 'my_calendar_categories' );
	define( 'MY_CALENDAR_GLOBAL_LOCATIONS_TABLE', $wpdb->base_prefix . 'my_calendar_locations' );
}

 
/**
 * Caching has been disabled by default with no option to enable for some time. 
 * Leaving functions, but they will only return false.
 */
function mc_check_cache( $category, $ltype, $lvalue, $author, $host, $hash ) {
	return false;
}

function mc_clean_cache( $cache, $category, $ltype, $lvalue, $auth, $host ) {
	return false;
}

function mc_create_cache( $arr_events, $hash, $category, $ltype, $lvalue, $author, $host ) {
	return false;
}

function mc_delete_cache() {
	// doesn't do anything anymore
}

function mc_get_cache( $cache ) {
	return false;
}

function mc_set_cache( $cache, $time ) {
	// doesn't do anything
}

/**
 * Old function to get users
 */
function my_calendar_getUsers( $group = 'authors' ) {
	return mc_get_users( $group );
}

function mc_remove_cache( $cache ) {
	// doesn't do anything
}

// back compat
function jd_show_support_box() {
	mc_show_sidebar();
}

// don't know when this was last in use
function my_calendar_is_odd( $int ) {
	return ( $int & 1 );
}

// Eliminated when I stopped labeling events as "forever" 
// Exists only <= 2.5.16
function mc_event_repeats_forever( $recur, $repeats ) {
	if ( $recur != 'S' && $repeats == 0 ) {
		return true;
	}
	switch ( $recur ) {
		case "S": // single
			return false;
			break;
		case "D": // daily
			return ( $repeats == 500 ) ? true : false;
			break;
		case "W": // weekly
			return ( $repeats == 240 ) ? true : false;
			break;
		case "B": // biweekly
			return ( $repeats == 120 ) ? true : false;
			break;
		case "M": // monthly
		case "U":
			return ( $repeats == 60 ) ? true : false;
			break;
		case "Y":
			return ( $repeats == 5 ) ? true : false;
			break;
		default:
			return false;
	}
}

/**
 * Try to check whether site is running in an HTTPS environment.
 *
 * Currently used only in My Calendar PRO; exists in both for back compat 
 */
if ( ! function_exists( 'is_ssl' ) ) {
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
 */
function jd_draw_template( $array, $template, $type = 'list' ) {
	
	return mc_draw_template( $array, $template, $type );
}

// test whether two dates are day-consecutive
// not used per audit 3/1/2018
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
 */
function my_calendar_reverse_datetime_cmp( $b, $a ) {
	$event_dt_a = strtotime( $a->occur_begin );
	$event_dt_b = strtotime( $b->occur_begin );
	if ( $event_dt_a == $event_dt_b ) {
		return 0;
	}

	return ( $event_dt_a < $event_dt_b ) ? - 1 : 1;
}

function jd_date_diff( $start, $end = "NOW" ) {
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
 * @param integer $week The week number of the year
 * @param integer $year The year of the week we need to calculate on
 *
 * @return integer The unix timestamp of the date is returned
 */
function get_week_date( $week, $year ) {
	// Get the target week of the year with reference to the starting day of
	// the year
	$start_of_week = ( get_option( 'start_of_week' ) == 1 || get_option( 'start_of_week' ) == 0 ) ? get_option( 'start_of_week' ) : 0;
	$target_week   = strtotime( "$week week", strtotime( "1 January $year" ) );
	$date_info     = getdate( $target_week );
	$day_of_week   = $date_info['wday'];
	// normal start day of the week is Monday
	$adjusted_date = $day_of_week - $start_of_week;
	// Get the timestamp of that day
	$first_day = strtotime( "-$adjusted_date day", $target_week );

	return $first_day;
}


function add_days_to_date( $givendate, $day = 0 ) {
	$cd      = strtotime( $givendate );
	$newdate = date( 'Y-m-d h:i:s',
		mktime(
			date( 'h', $cd ),
			date( 'i', $cd ),
			date( 's', $cd ),
			date( 'm', $cd ),
			date( 'd', $cd ) + $day,
			date( 'Y', $cd )
		) );

	return $newdate;
}