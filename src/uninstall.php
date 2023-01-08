<?php
/**
 * Uninstall My Calendar.
 *
 * @category Core
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
} else {

	/**
	 * Delete all custom templates.
	 */
	function mc_delete_templates() {
		global $wpdb;
		$results = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . "options WHERE option_name LIKE '%mc_ctemplate_%'" );
		foreach ( $results as $result ) {
			$key = str_replace( 'mc_ctemplate_', '', $result->option_name );
			delete_option( "mc_template_desc_$key" );
			delete_option( "mc_ctemplate_$key" );
		}
		$results = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . "options WHERE option_name LIKE '%mc_category_icon_%'" );
		foreach ( $results as $result ) {
			delete_option( $result->option_name );
		}
	}
	$options = get_option( 'my_calendar_options' );
	if ( 'true' === $options['drop_settings'] ) {
		delete_option( 'my_calendar_options' );
		delete_option( 'ko_calendar_imported' );
		delete_option( 'mc_count_cache' );
		delete_option( 'mc_promotion_scheduled' );
		// Deletes custom template options.
		mc_delete_templates();
	}
	if ( 'true' === $options['drop_tables'] ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->posts . ' WHERE post_type = %s', 'mc-events' ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->posts . ' WHERE post_type = %s', 'mc-locations' ) );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'my_calendar' );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'my_calendar_events' );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'my_calendar_categories' );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'my_calendar_category_relationships' );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'my_calendar_locations' );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'my_calendar_location_relationships' );
	}

	delete_option( 'mc_promotion_scheduled' );
	add_option( 'mc_uninstalled', 'true' );
}
