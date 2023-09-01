<?php
/**
 * Database reference file
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
 * My Calendar main table
 *
 * @param int|boolean $site Site ID in multisite.
 *
 * @return string table name
 */
function my_calendar_table( $site = false ) {
	return my_calendar_select_table( 'my_calendar', $site );
}

/**
 * My Calendar event table
 *
 * @param int|boolean $site Site ID in multisite.
 *
 * @return string table name
 */
function my_calendar_event_table( $site = false ) {
	return my_calendar_select_table( 'my_calendar_events', $site );
}

/**
 * My Calendar category table
 *
 * @param int|boolean $site Site ID in multisite.
 *
 * @return string table name
 */
function my_calendar_categories_table( $site = false ) {
	return my_calendar_select_table( 'my_calendar_categories', $site );
}

/**
 * My Calendar category relationships table
 *
 * @param int|boolean $site Site ID in multisite.
 *
 * @return string table name
 */
function my_calendar_category_relationships_table( $site = false ) {
	return my_calendar_select_table( 'my_calendar_category_relationships', $site );
}

/**
 * My Calendar location relationships table
 *
 * @param int|boolean $site Site ID in multisite.
 *
 * @return string table name
 */
function my_calendar_location_relationships_table( $site = false ) {
	return my_calendar_select_table( 'my_calendar_location_relationships', $site );
}

/**
 * My Calendar locations table
 *
 * @param int|boolean $site Site ID in multisite.
 *
 * @return string table name
 */
function my_calendar_locations_table( $site = false ) {
	return my_calendar_select_table( 'my_calendar_locations', $site );
}

/**
 * Get table to query based on table data required & required site.
 *
 * @since 2.5.0
 *
 * @param string           $table table name.
 * @param int|string|false $site 'global' to get global database; site ID to get that site's database; false for defaults according to settings.
 *
 * @return string properly prefixed table name
 */
function my_calendar_select_table( $table = 'my_calendar_events', $site = false ) {
	global $wpdb;
	$local = $wpdb->prefix . $table;

	if ( is_multisite() ) {
		$option = (int) get_site_option( 'mc_multisite' );
		$choice = (int) mc_get_option( 'current_table' );
		$show   = (int) get_site_option( 'mc_multisite_show' ); // 1 == use global instead of local.
		if ( 'global' === $site ) {
			return $wpdb->base_prefix . $table;
		}
		if ( false !== $site && $site ) {
			$site = absint( $site );
			$wpdb->set_blog_id( $site );
		}
		$local  = ( 1 === $show ) ? $wpdb->base_prefix . $table : $wpdb->prefix . $table;
		$global = $wpdb->base_prefix . $table;

		switch ( $option ) {
			case 0:
				$return = $local;
				break;
			case 1:
				$return = $global;
				break;
			case 2:
				$return = ( 1 === $choice ) ? $global : $local;
				break;
			default:
				$return = $local;
		}
	} else {
		$return = $local;
	}

	return $return;
}

/**
 * Determine whether we're accessing a remote database.
 *
 * @return object WP DB object
 */
function mc_is_remote_db() {
	global $wpdb;
	global $remotedb;
	$mcdb = $wpdb;
	if ( 'true' === mc_get_option( 'remote' ) && function_exists( 'mc_remote_db' ) ) {
		if ( ! isset( $remotedb ) ) {
			$remotedb = mc_remote_db();
		}
		$mcdb = $remotedb;
	}

	return $mcdb;
}
