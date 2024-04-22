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
	$skip = 0;
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
	$after   = apply_filters( 'mc_future_search_results', 20, 'basic' ); // Return only future events, nearest 20.
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
		 * Number of results to skip. Default `0`. Used for pagination.
		 *
		 * @hook mc_skip_search_results
		 *
		 * @param {int}    $after Number of results to skip.
		 * @param {string} $context 'advanced' for advanced search results.
		 *
		 * @return {string}
		 */
		$skip = apply_filters( 'mc_skip_search_results', 0, 'advanced' );
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
	$event_array = mc_remove_hidden_events( $event_array );
	$count       = mc_count_events( $event_array );

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
		if ( is_string( $query ) ) {
			$output = mc_produce_upcoming_events( $event_array, $template, 'list', 'ASC', $skip, $before, $after, 'yes', 'nofilters', $term );
		} else {
			// Use this function for advanced search queries.
			$output = mc_produce_search_results( $event_array, $template );
		}
		/**
		 * Filter that inserts search export links. Default empty string.
		 *
		 * @hook mc_search_exportlinks
		 *
		 * @param {string} $exports String.
		 * @param {string} $output Search results.
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
	$header = apply_filters( 'mc_search_before', '<h2>%s</h2><ol class="mc-search-results" role="list">', $term, $count );
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
		if ( isset( $_GET['mcs'] ) && ! isset( $_GET['mcp'] ) ) { // Simple search.
			$ret   = true;
			$query = sanitize_text_field( $_GET['mcs'] );
		} elseif ( isset( $_GET['mcp'] ) && isset( $_GET['mcs'] ) ) { // Advanced search.
			$ret   = true;
			$query = map_deep( $_GET, 'sanitize_text_field' );
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
	$print        = "<div class='mc-print'><a href='" . esc_url( $mc_print_url ) . "'>" . __( 'Print<span class="maybe-hide"> View</span>', 'my-calendar' ) . '</a></div>';

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

/**
 * Generates the list of search results.
 *
 * @param array  $events Array of events to analyze, organized by date.
 * @param string $template Custom template to use for display.
 *
 * @return string HTML output of search results.
 */
function mc_produce_search_results( $events, $template ) {
	$output      = array();
	$near_events = array();
	$temp_array  = array();

	$group = array();
	$spans = array();
	$occur = array();

	$recurring_events = array();
	$last_events      = array();
	$last_group       = array();
	if ( is_array( $events ) ) {
		foreach ( $events as $k => $event ) {
			if ( is_array( $event ) ) {
				foreach ( $event as $e ) {
					if ( ! mc_private_event( $e ) ) {
						// Store span time in an array to avoid repeating database query.
						if ( '1' === $e->event_span && ( ! isset( $spans[ $e->occur_group_id ] ) ) ) {
							// This is a multi-day event: treat each event as if it spanned the entire range of the group.
							$span_time                   = mc_span_time( $e->occur_group_id );
							$spans[ $e->occur_group_id ] = $span_time;
						} elseif ( '1' === $e->event_span && ( isset( $spans[ $e->occur_group_id ] ) ) ) {
							$span_time = $spans[ $e->occur_group_id ];

						}
						if ( $e ) {
							// If a multi-day event, show only once.
							if ( '0' !== $e->occur_group_id && '1' === $e->event_span && in_array( $e->occur_group_id, $group, true ) || in_array( $e->occur_id, $occur, true ) ) {
								$md = true;
							} else {
								$group[] = $e->occur_group_id;
								$occur[] = $e->occur_id;
								$md      = false;
							}
							// end multi-day reduction.
							if ( ! $md ) {
								$instances = mc_get_occurrences( $e->occur_event_id );
								if ( count( $instances ) > 1 ) {
									$recurring_events[] = $e->occur_event_id;
								}
								$near_events[] = $e; // Split off another future event.
								$last_events[] = $e->occur_id;
								$last_group[]  = $e->occur_group_id;
							}
						}
					}
				}
			}
		}
	}
	$events = $near_events;
	usort( $events, 'mc_datetime_cmp' ); // Sort split events by date.

	if ( is_array( $events ) ) {
		foreach ( array_keys( $events ) as $key ) {
			$event        =& $events[ $key ];
			$temp_array[] = $event;
		}
		$groups = array();

		foreach ( reverse_array( $temp_array, true, 'asc' ) as $event ) {
			$details = mc_create_tags( $event, 'nofilters' );
			if ( ! in_array( $details['group'], $groups, true ) ) {
				$output[] = array(
					'event' => $event,
					'tags'  => $details,
				);
				if ( '1' === $details['event_span'] ) {
					$groups[] = $details['group'];
				}
			}
		}
	}

	$html = '';
	foreach ( $output as $out ) {
		$data    = array(
			'event'    => $out['event'],
			'tags'     => $out['tags'],
			'template' => $template,
			'type'     => 'list',
		);
		$details = mc_load_template( 'event/search', $data );
		$html   .= $details;
		if ( ! $details ) {
			$html .= mc_format_upcoming_event( $out, $template, 'list' );
		}
	}

	return $html;
}
