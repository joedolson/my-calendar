<?php
/**
 * Calendar search.
 *
 * @category Search
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output search results for a given query
 *
 * @param mixed string/array $query Search query.
 *
 * @return string HTML output
 */
function mc_search_results( $query ) {
	$before  = apply_filters( 'mc_past_search_results', 0, 'basic' );
	$after   = apply_filters( 'mc_future_search_results', 10, 'basic' ); // Return only future events, nearest 10.
	$exports = '';
	if ( is_string( $query ) ) {
		$search = mc_prepare_search_query( $query );
		$term   = $query;
	} else {
		$search = apply_filters( 'mc_advanced_search', '', $query );
		$term   = $query['mcs'];
		$before = apply_filters( 'mc_past_search_results', 0, 'advanced' );
		$after  = apply_filters( 'mc_future_search_results', 20, 'advanced' );
	}

	$event_array = mc_get_search_results( $search );

	if ( ! empty( $event_array ) ) {
		$template = '<strong>{date}</strong> {title} {details}';
		$template = apply_filters( 'mc_search_template', $template );
		// No filters parameter prevents infinite looping on the_content filters.
		$output  = mc_produce_upcoming_events( $event_array, $template, 'list', 'ASC', 0, $before, $after, 'yes', 'yes', 'nofilters' );
		$exports = apply_filters( 'mc_search_exportlinks', '', $output );
	} else {
		$output = apply_filters( 'mc_search_no_results', "<li class='no-results'>" . __( 'Sorry, your search produced no results.', 'my-calendar' ) . '</li>' );
	}

	$header = apply_filters( 'mc_search_before', '<ol class="mc-search-results">', $term );
	$footer = apply_filters( 'mc_search_after', '</ol>', $term );

	return $header . $output . $footer . $exports;
}

add_filter( 'the_title', 'mc_search_results_title', 10, 2 );
/**
 * Custom title for search results screen.
 *
 * @param string $title Current title.
 * @param int    $id post ID.
 *
 * @return string New title
 */
function mc_search_results_title( $title, $id = false ) {
	if ( ( isset( $_GET['mcs'] ) || isset( $_POST['mcs'] ) ) && ( is_page( $id ) || is_single( $id ) ) && in_the_loop() ) {
		$query = ( isset( $_GET['mcs'] ) ) ? $_GET['mcs'] : $_POST['mcs'];
		// Translators: entered search query.
		$title = sprintf( __( 'Events Search for &ldquo;%s&rdquo;', 'my-calendar' ), esc_html( $query ) );
	}

	return $title;
}

add_filter( 'the_content', 'mc_show_search_results' );
/**
 * Show search results on predefined search page.
 *
 * @param string $content Post Content.
 *
 * @return string $content
 */
function mc_show_search_results( $content ) {
	global $post;
	if ( is_object( $post ) && in_the_loop() && ! is_page( get_option( 'mc_uri_id' ) ) ) {
		// if this is the result of a search, show search output.
		$ret   = false;
		$query = false;
		if ( isset( $_GET['mcs'] ) ) { // Simple search.
			$ret   = true;
			$query = $_GET['mcs'];
		} elseif ( isset( $_POST['mcs'] ) ) { // Advanced search.
			$ret   = true;
			$query = $_POST;
		}
		if ( $ret && $query ) {
			return mc_search_results( $query );
		} else {
			return $content;
		}
	} else {
		return $content;
	}
}

add_filter( 'mc_search_exportlinks', 'mc_search_exportlinks', 10, 0 );
/**
 * Creates the export links for search result
 */
function mc_search_exportlinks() {
	if ( ! session_id() ) {
		return;
	}

	// Setup print link.
	$print_add    = array(
		'format'   => 'list',
		'searched' => true,
		'href'     => urlencode( mc_get_current_url() ),
		'cid'      => 'mc-print-view',
	);
	$subtract     = array( 'time', 'ltype', 'lvalue', 'mcat', 'yr', 'month', 'dy' );
	$mc_print_url = mc_build_url( $print_add, '', home_url() );
	$print        = "<div class='mc-print'><a href='$mc_print_url'>" . __( 'Print<span class="maybe-hide"> View</span>', 'my-calendar' ) . '</a></div>';

	// Set up exports.
	if ( '' !== get_option( 'mc_topnav', '' ) ) {
		$above = array_map( 'trim', explode( ',', get_option( 'mc_topnav' ) ) );
	}

	if ( '' !== get_option( 'mc_bottomnav', '' ) ) {
		$below = array_map( 'trim', explode( ',', get_option( 'mc_bottomnav' ) ) );
	}
	$used = array_merge( $above, $below );

	if ( in_array( 'exports', $used, true ) ) {
		$ics_add = array( 'searched' => true );
		$exports = mc_export_links( 1, 1, 1, $ics_add, $subtract );
	} else {
		$exports = '';
	}

	$before = "<div class='mc_bottomnav my-calendar-footer'>";
	$after  = '</div>';

	return $before . $exports . $print . $after;
}

add_filter( 'mc_searched_events', 'mc_searched_events', 10, 1 );
/**
 * Saves all searched events in $_SESSION
 *
 * @param array $event_array The events to be saved.
 *
 * @return array Events.
 */
function mc_searched_events( $event_array ) {
	if ( session_id() ) {
		$_SESSION['MC_SEARCH_RESULT'] = json_encode( $event_array );
	}
	return $event_array;
}

/**
 * Get searched events from $_SESSION array
 *
 * @return array event_array
 */
function mc_get_searched_events() {
	if ( ! session_id() || ! isset( $_SESSION['MC_SEARCH_RESULT'] ) ) {
		return;
	}
	$event_searched = json_decode( $_SESSION['MC_SEARCH_RESULT'], true );
	foreach ( $event_searched as $key => $value ) {
		$daily_events = array();
		foreach ( $value as $k => $v ) {
			$daily_events[] = (object) $v;
		}
		$event_array[ $key ] = $daily_events;
	}
	return $event_array;
}