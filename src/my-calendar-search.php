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
 * @param string|array $query Search query.
 *
 * @return string HTML output
 */
function mc_search_results( $query ) {
	/**
	 * Number of past results to show. Default `0`.
	 *
	 * @hook mc_past_search_results
	 *
	 * @param {int}    $before Number of past results to display.
	 * @param {string} $context 'basic' for basic search results.
	 *
	 * @return {string}
	 */
	$before = apply_filters( 'mc_past_search_results', 0, 'basic' );
	/**
	 * Number of future results to show. Default `20`.
	 *
	 * @hook mc_future_search_results
	 *
	 * @param {int}    $after Number of future results to display.
	 * @param {string} $context 'basic' for basic search results.
	 *
	 * @return {string}
	 */
	$after   = apply_filters( 'mc_future_search_results', 10, 'basic' ); // Return only future events, nearest 10.
	$exports = '';
	if ( is_string( $query ) ) {
		$search = mc_prepare_search_query( $query );
		$term   = $query;
	} else {
		/**
		 * Build the advanced search query. Default ''
		 *
		 * @hook mc_advanced_search
		 *
		 * @param {string}       $search Placeholder to create search results.
		 * @param {array|string} $query User search query parameters.
		 *
		 * @return {array}
		 */
		$search = apply_filters( 'mc_advanced_search', '', $query );
		$term   = $query['mcs'];
		/**
		 * Number of past results to show. Default `0`.
		 *
		 * @hook mc_past_search_results
		 *
		 * @param {int}    $before Number of past results to display.
		 * @param {string} $context 'advanced' for advanced search results.
		 *
		 * @return {string}
		 */
		$before = apply_filters( 'mc_past_search_results', 0, 'advanced' );
		/**
		 * Number of future results to show. Default `20`.
		 *
		 * @hook mc_future_search_results
		 *
		 * @param {int}    $after Number of future results to display.
		 * @param {string} $context 'advanced' for advanced search results.
		 *
		 * @return {string}
		 */
		$after = apply_filters( 'mc_future_search_results', 20, 'advanced' );
	}

	$event_array = mc_get_search_results( $search );

	if ( ! empty( $event_array ) ) {
		$template = '<h3><strong>{timerange after=", "}{daterange}</strong> &#8211; {linking_title}</h3><div class="mcs-search-excerpt">{search_excerpt}</div>';
		/**
		 * Template for outputting search results. Default `<strong>{date}</strong> {title} {details}`.
		 *
		 * @hook mc_search_template
		 *
		 * @param {string}       $template String with HTML and template tags.
		 * @param {string|array} $term The search query arguments. Can be a string or an array of search parameters.
		 *
		 * @return {string}
		 */
		$template = apply_filters( 'mc_search_template', $template, $term );
		// No filters parameter prevents infinite looping on the_content filters.
		$output = mc_produce_upcoming_events( $event_array, $template, 'list', 'ASC', 0, $before, $after, 'yes', 'yes', 'nofilters', $term );
		/**
		 * Filter that inserts search export links. Default empty string.
		 *
		 * @hook mc_search_exportlinks
		 *
		 * @param {string} $exports String.
		 * @param {array}  $output Search results.
		 *
		 * @return {string}
		 */
		$exports = apply_filters( 'mc_search_exportlinks', '', $output );
	} else {
		/**
		 * HTML template if no search results. Default `<li class='no-results'>" . __( 'Sorry, your search produced no results.', 'my-calendar' ) . '</li>`.
		 *
		 * @hook mc_search_no_results
		 *
		 * @param {string}       $output HTML output.
		 * @param {string|array} $term The search query arguments. Can be a string or an array of search parameters.
		 *
		 * @return {string}
		 */
		$output = apply_filters( 'mc_search_no_results', "<li class='no-results'>" . __( 'Sorry, your search produced no results.', 'my-calendar' ) . '</li>', $term );
	}

	/**
	 * HTML template before the search results. Default `<ol class="mc-search-results">`.
	 *
	 * @hook mc_search_before
	 *
	 * @param {string}       $header HTML output.
	 * @param {string|array} $term The search query arguments. Can be a string or an array of search parameters.
	 *
	 * @return {string}
	 */
	$header = apply_filters( 'mc_search_before', '<h2>%s</h2><ol class="mc-search-results" role="list">', $term );
	// Translators: search term.
	$header = sprintf( $header, sprintf( __( 'Search Results for "%s"', 'my-calendar' ), esc_html( $term ) ) );
	/**
	 * HTML template after the search results. Default `</ol>`.
	 *
	 * @hook mc_search_after
	 *
	 * @param {string}       $footer HTML output.
	 * @param {string|array} $term The search query arguments. Can be a string or an array of search parameters.
	 *
	 * @return {string}
	 */
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
function mc_search_results_title( $title, $id = null ) {
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
	if ( is_object( $post ) && in_the_loop() && ! is_page( mc_get_option( 'uri_id' ) ) ) {
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

	$above = array();
	$below = array();

	// Set up exports.
	if ( '' !== mc_get_option( 'topnav', '' ) ) {
		$above = array_map( 'trim', explode( ',', mc_get_option( 'topnav' ) ) );
	}

	if ( '' !== mc_get_option( 'bottomnav', '' ) ) {
		$below = array_map( 'trim', explode( ',', mc_get_option( 'bottomnav' ) ) );
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
		return array();
	}
	$event_array    = array();
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
