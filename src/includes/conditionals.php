<?php
/**
 * Conditional functions. Boolean functions testing calendar and event conditions.
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
 * Determine whether an event is recurring.
 *
 * @param object $event Event object.
 *
 * @return bool
 */
function mc_is_recurring( $event ) {
	$is_recurring = ( ! ( 'S' === $event->event_recur || 'S1' === $event->event_recur ) ) ? true : false;

	return $is_recurring;
}

/**
 * Test an event and see if it's an all day event.
 *
 * @param object $event Event object.
 *
 * @return boolean
 */
function mc_is_all_day( $event ) {

	return ( '00:00:00' === $event->event_time && '23:59:59' === $event->event_endtime ) ? true : false;
}

/**
 * Check whether using custom or stock icons.
 *
 * @return boolean
 */
function mc_is_custom_icon() {
	$on     = ( WP_DEBUG ) ? false : get_transient( 'mc_custom_icons' );
	$dir    = trailingslashit( dirname( __DIR__, 1 ) );
	$base   = trailingslashit( basename( $dir ) );
	$custom = ( file_exists( str_replace( $base, '', $dir ) . 'my-calendar-custom/icons' ) );
	if ( ! $custom ) {
		// backcompat for old icon directories.
		$custom = ( file_exists( str_replace( $base, '', $dir ) . 'my-calendar-custom/icons' ) );
	}
	$return = false;
	if ( $on && $custom ) {
		$return = true;
	} else {
		$dir  = trailingslashit( dirname( __DIR__, 1 ) );
		$base = trailingslashit( basename( $dir ) );
		if ( $custom ) {
			$results = mc_directory_list( str_replace( $base, '', $dir ) . 'my-calendar-custom/icons' );
			if ( empty( $results ) ) {
				$results = mc_directory_list( str_replace( $base, '', $dir ) . 'my-calendar-custom' );
				if ( empty( $results ) ) {
					$return = false;
				}
			} else {
				$return = true;
			}
			set_transient( 'mc_custom_icons', true, HOUR_IN_SECONDS );
		}
	}

	return $return;
}

/**
 * Test whether currently mobile using wp_is_mobile() with custom filter
 *
 * @return boolean
 */
function mc_is_mobile() {
	$mobile = false;
	if ( function_exists( 'wp_is_mobile' ) ) {
		$mobile = wp_is_mobile();
	}

	return apply_filters( 'mc_is_mobile', $mobile );
}

/**
 * Provides a filter for custom dev. Not used in core.
 *
 * @return boolean
 */
function mc_is_tablet() {

	return apply_filters( 'mc_is_tablet', false );
}


/**
 * Check whether this is a valid preview scenario.
 *
 * @return boolean
 */
function mc_is_preview() {
	if ( isset( $_GET['preview'] ) && 'true' === $_GET['preview'] && current_user_can( 'mc_manage_events' ) ) {
		return true;
	}

	return false;
}

/**
 * Check whether this event is targeted for an iFrame.
 *
 * @return boolean
 */
function mc_is_iframe() {
	if ( isset( $_GET['iframe'] ) && 'true' === $_GET['iframe'] && isset( $_GET['mc_id'] ) ) {
		return true;
	}

	return false;
}

/**
 * Check whether this event is supposed to show template output.
 *
 * @return boolean
 */
function mc_is_tag_view() {
	if ( isset( $_GET['showtags'] ) && 'true' === $_GET['showtags'] && current_user_can( 'mc_add_events' ) ) {
		return true;
	}

	return false;
}

/**
 * Identify whether a given file is a custom style or a core style
 *
 * @param string $filename File name..
 *
 * @return boolean
 */
function mc_is_custom_style( $filename ) {
	if ( 0 === strpos( $filename, 'mc_custom_' ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Check whether the current key refers to a core template
 *
 * @param string $key Template unique key.
 *
 * @return boolean
 */
function mc_is_core_template( $key ) {
	switch ( $key ) {
		case 'grid':
		case 'details':
		case 'list':
		case 'mini':
		case 'card':
			$return = true;
			break;
		default:
			$return = false;
	}

	return $return;
}

/**
 * Check whether a view is a singular event view.
 *
 * @return bool
 */
function mc_is_single_event() {
	if ( is_singular( 'mc-events' ) ) {
		return true;
	}
	if ( isset( $_GET['mc_id'] ) && mc_valid_id( $_GET['mc_id'] ) ) {
		return true;
	}

	return false;
}

/**
 * Check whether a given output field should be displayed.
 *
 * @param string         $feature Feature key.
 * @param string         $type Display type.
 * @param object|boolean $event Event if in event context.
 *
 * @return bool
 */
function mc_output_is_visible( $feature, $type, $event = false ) {
	// Map either calendar popup or list to main settings.
	$type   = ( 'calendar' === $type || 'list' === $type ) ? 'main' : $type;
	$option = mc_get_option( 'display_' . $type );
	if ( ! is_array( $option ) ) {
		$display_type = 'display_' . $type;
		$option       = ( isset( mc_default_options()[ $display_type ] ) ) ? mc_default_options()[ $display_type ] : array();
	}
	$return = false;
	if ( in_array( $feature, $option, true ) ) {
		$return = true;
	}
	/**
	 * Filter whether any given piece of information should be output.
	 *
	 * @param {bool}           $return Should this piece of data be output.
	 * @param {string}         $feature Feature key.
	 * @param {string}         $type Type of view.
	 * @param {object|boolean} $event Event object if in event context.
	 *
	 * @return bool
	 */
	return apply_filters( 'mc_output_is_visible', $return, $feature, $type, $event );
}
