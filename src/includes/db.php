<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Handlers for backwards compatibility
 */
function my_calendar_table( $site = false ) {
	return my_calendar_select_table( 'my_calendar', $site );
}

function my_calendar_event_table( $site = false ) {
	return my_calendar_select_table( 'my_calendar_events', $site );
}

function my_calendar_categories_table( $site = false ) {
	return my_calendar_select_table( 'my_calendar_categories', $site );
}

function my_calendar_category_relationships_table( $site = false ) {
	return my_calendar_select_table( 'my_calendar_category_relationships', $site );
}

function my_calendar_locations_table( $site = false ) {
	return my_calendar_select_table( 'my_calendar_locations', $site );
}

/**
 * Get table to query based on table data required & required site.
 *
 * @since 2.5.0
 * 
 * @param string table name
 * @param mixed 'global' to get global database; site ID to get that site's database; false for defaults according to settings.
 *
 * @return prefixed string table name
 */
function my_calendar_select_table( $table = 'my_calendar_events', $site = false ) {
	global $wpdb;
	$local = $wpdb->prefix . $table;
		
	if ( is_multisite() ) {	
		$option = (int) get_site_option( 'mc_multisite' );
		$choice = (int) get_option( 'mc_current_table' );
		$show   = (int) get_site_option( 'mc_multisite_show' ); // 1 == use global instead of local
		
		if ( $site == 'global' ) {
			return $wpdb->base_prefix . $table;
		}
		
		if ( $site != false && $site ) {
			$site = absint( $site );
			$wpdb->set_blog_id( $site );
		} 

		$local  = ( $show == 1 ) ? $wpdb->base_prefix . $table : $wpdb->prefix . $table;
		$global = $wpdb->base_prefix . $table;
		
		switch ( $option ) {
			case 0:
				return $local;
				break;
			case 1:
				return $global;
				break;
			case 2:
				return ( $choice == 1 ) ? $global : $local;
				break;
			default:
				return $local;
		}
	} else {
		return $local;
	}
}