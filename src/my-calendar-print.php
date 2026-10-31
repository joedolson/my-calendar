<?php
/**
 * Output the print view.
 *
 * @category Calendar
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'my_calendar_load_print_view' );
/**
 * Redirect to print view if query set.
 */
function my_calendar_load_print_view() {
	if ( isset( $_GET['cid'] ) && 'mc-print-view' === $_GET['cid'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$template = locate_template( 'my-calendar-print.php' );
		if ( $template ) {
			load_template( $template );
		} else {
			load_template( __DIR__ . '/templates/my-calendar-print.php' );
		}
		exit;
	}
}

/**
 * Produce print view return URL output.
 */
function my_calendar_print_return_url() {
	// The time string can contain a plus literal, which needs to be re-encoded.
	$time       = ( isset( $_GET['time'] ) ) ? sanitize_text_field( rawurlencode( wp_unslash( $_GET['time'] ) ) ) : 'month'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$category   = ( isset( $_GET['mcat'] ) ) ? sanitize_text_field( wp_unslash( $_GET['mcat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ltype      = ( isset( $_GET['ltype'] ) ) ? sanitize_text_field( wp_unslash( $_GET['ltype'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$lvalue     = ( isset( $_GET['lvalue'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lvalue'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$args       = array(
		'type'     => 'print',
		'category' => $category,
		'time'     => $time,
		'ltype'    => $ltype,
		'lvalue'   => $lvalue,
	);
	$return_url = mc_get_uri( false, $args );
	/**
	 * Filter the root URL used to generate the return URL.
	 *
	 * @hook mc_print_return_url
	 *
	 * @param string $return_url Referer URL for calendar print view arrived from.
	 * @param string $category Category argument.
	 * @param string $time Time argument.
	 * @param string $ltype Location type argument.
	 * @param string $lvalue Location value argument.
	 *
	 * @return string
	 */
	$return_url = apply_filters( 'mc_print_return_url', $return_url, $category, $time, $ltype, $lvalue );

	if ( isset( $_GET['href'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// Only support URLs on the same home_url().
		$ref_url  = sanitize_text_field( urldecode( wp_unslash( $_GET['href'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$parsed   = wp_parse_url( $ref_url );
		$ref_root = ( $parsed && isset( $parsed['host'] ) ) ? $parsed['host'] : false;
		$root     = wp_parse_url( home_url() )['host'];
		$local    = false;
		if ( $ref_root ) {
			$local = ( false !== stripos( $ref_url, home_url() ) && false !== stripos( $root, $ref_root ) ) ? true : false;
		}
		if ( $ref_url && $local ) {
			$return_url = $ref_url;
		} else {
			wp_die( 'My Calendar: invalid print URL provided.' );
		}
	}
	$add = map_deep( wp_unslash( $_GET ), 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	unset( $add['cid'] );
	unset( $add['feed'] );
	unset( $add['href'] );
	/**
	 * Return to calendar URL from print view.
	 *
	 * @hook mc_return_to_calendar
	 *
	 * @param string $return_url URL to return to previous page.
	 * @param array  $add Array of parameters added to this URL.
	 *
	 * @return string
	 */
	$return_url = apply_filters( 'mc_return_to_calendar', mc_build_url( $add, array( 'feed', 'cid', 'href', 'searched' ), $return_url ), $add );
	if ( $return_url ) {
		echo "<p class='return'><a href='" . esc_url( $return_url ) . "'><span class='dashicons dashicons-arrow-left-alt' aria-hidden='true'></span> " . esc_html__( 'Return to calendar', 'my-calendar' ) . '</a> <a href="javascript:window.print()"><span class="dashicons dashicons-printer" aria-hidden="true"></span> ' . esc_html( __( 'Print', 'my-calendar' ) ) . '</a></p>';
	}
}

/**
 * Display the print view of the My Calendar plugin.
 *
 * @return void
 */
function my_calendar_print_view() {
	// The time string can contain a plus literal, which needs to be re-encoded.
	$time     = ( isset( $_GET['time'] ) ) ? sanitize_text_field( rawurlencode( wp_unslash( $_GET['time'] ) ) ) : 'month'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$category = ( isset( $_GET['mcat'] ) ) ? sanitize_text_field( wp_unslash( $_GET['mcat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ltype    = ( isset( $_GET['ltype'] ) ) ? sanitize_text_field( wp_unslash( $_GET['ltype'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$lvalue   = ( isset( $_GET['lvalue'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lvalue'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$calendar = array(
		'name'     => 'print',
		'format'   => 'calendar',
		'category' => $category,
		'time'     => $time,
		'ltype'    => $ltype,
		'lvalue'   => $lvalue,
		'id'       => 'mc-print-view',
		'below'    => 'key',
		'above'    => 'none',
		'json'     => 'false',
	);

	echo wp_kses( my_calendar( $calendar ), mc_kses_elements() );
}

/**
 * Dequeue styles other than the My Calendar print styles on print view.
 */
function mc_dequeue_other_styles() {
	$is_print_view = isset( $_GET['cid'] ) && 'mc-print-view' === $_GET['cid']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $is_print_view ) {
		global $wp_styles;

		// Loop through and dequeue all styles.
		foreach ( $wp_styles->queue as $handle ) {
			if ( 'my-calendar-print' === $handle ) {
				continue;
			}
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
		global $wp_scripts;

		// Loop through and dequeue all scripts.
		foreach ( $wp_scripts->queue as $handle ) {
			if ( 'my-calendar-print' === $handle ) {
				continue;
			}
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'mc_dequeue_other_styles', 999 );

/**
 * Enqueue styles for the print view.
 */
function mc_enqueue_print_styles() {
	if ( isset( $_GET['cid'] ) && 'mc-print-view' === $_GET['cid'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$mc_version  = mc_get_version();
		$mc_version .= ( SCRIPT_DEBUG ) ? '-' . wp_rand( 10000, 99999 ) : '';
		// Copy mc-print.css to your theme directory if you wish to replace the default print styles.
		if ( mc_file_exists( 'mc-print.css' ) ) {
			$stylesheet = mc_get_file( 'mc-print.css', 'url' );
		} else {
			$stylesheet = plugins_url( 'css/mc-print.css', __FILE__ );
		}
		wp_enqueue_style( 'my-calendar-print', $stylesheet, array( 'dashicons' ), $mc_version );
	}
}
add_action( 'wp_enqueue_scripts', 'mc_enqueue_print_styles' );
