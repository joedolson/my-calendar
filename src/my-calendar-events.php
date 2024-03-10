<?php
/**
 * Get event data. Queries to fetch events and create or modify objects.
 *
 * @category Events
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes objects to add needed properties.
 *
 * @param object $event Event object.
 *
 * @return object $event Modifed event.
 */
function mc_event_object( $event ) {
	if ( is_object( $event ) ) {
		if ( ! property_exists( $event, 'categories' ) ) {
			$event->categories = mc_get_categories( $event, 'objects' );
		}
		if ( ! property_exists( $event, 'location' ) && is_numeric( $event->event_location ) && 0 !== (int) $event->event_location ) {
			$event->location = mc_get_location( $event->event_location );
		}
		if ( ! property_exists( $event, 'uid' ) ) {
			$guid = (string) get_post_meta( $event->event_post, '_mc_guid', true );
			if ( '' === $guid ) {
				$guid = mc_create_guid( $event );
			}
			$event->uid = $guid;
		}
		/**
		 * Customize the My Calendar event object.
		 *
		 * @hook mc_event_object
		 *
		 * @param {object} $object A My Calendar event.
		 *
		 * @return {object}
		 */
		$event = apply_filters( 'mc_event_object', $event );
	}

	return $event;
}

/**
 * Get the first and last event stored in the database.
 *
 * @param int|false $site Optional. Site to check.
 *
 * @return array Array containing the date of the first and last event.
 */
function mc_get_date_bounds( $site = false ) {
	$return = get_transient( 'mc_get_date_bounds' );
	if ( ! $return ) {
		$mcdb  = mc_is_remote_db();
		$first = $mcdb->get_var( 'SELECT occur_begin FROM ' . my_calendar_event_table( $site ) . ' ORDER BY occur_begin ASC LIMIT 0, 1' );
		$last  = $mcdb->get_var( 'SELECT occur_end FROM ' . my_calendar_event_table( $site ) . ' ORDER BY occur_end DESC LIMIT 0, 1' );

		$return = array(
			'first' => $first,
			'last'  => $last,
		);
		set_transient( 'mc_get_date_bounds', $return, DAY_IN_SECONDS );
	}

	return $return;
}


/**
 * Create a GUID for an event.
 *
 * @param object $event Event object.
 *
 * @return string GUID
 */
function mc_create_guid( $event ) {
	$guid = md5( home_url() . $event->event_post . $event->event_id . $event->event_title );
	update_post_meta( $event->event_post, '_mc_guid', $guid );

	return $guid;
}

/**
 * Function for extracting event timestamps from MySQL.
 *
 * @param bool $test Test offset time.
 *
 * @return string|array
 */
function mc_ts( $test = false ) {
	$ts_sql = get_transient( 'mc_ts_string' );
	$ts_db  = get_transient( 'mc_ts_db' );
	if ( $ts_db && $test ) {
		return $ts_db;
	}
	if ( $test || ! $ts_sql || ! $ts_db ) {
		global $wpdb;
		$offset = $wpdb->get_var( 'SELECT TIMEDIFF(NOW(), UTC_TIMESTAMP);' );
		/**
		 * Filter timezone offset applied when displaying events. Can fix issues with an incorrect server time.
		 *
		 * @hook mc_filter_offset
		 *
		 * @param {string} $offset Timezone offset format -HH:MM:SS.
		 *
		 * @return {string}
		 */
		$offset = apply_filters( 'mc_filter_offset', $offset );
		$offset = substr( $offset, 0, -3 );
		if ( strpos( $offset, '-' ) !== 0 ) {
			$offset = '+' . $offset;
		}

		$wp_time  = get_option( 'gmt_offset', '0' );
		$wp_time  = ( $wp_time < 0 ) ? '-' . str_pad( absint( $wp_time ), 2, 0, STR_PAD_LEFT ) : '+' . str_pad( $wp_time, 2, 0, STR_PAD_LEFT );
		$wp_time .= ':00';

		if ( $test ) {
			$return = array(
				'db' => $offset,
				'wp' => $wp_time,
			);
			set_transient( 'mc_ts_db', $return, WEEK_IN_SECONDS );

			return $return;
		}
		// Converts occur_begin value from the WordPress timezone to the db timezone.
		// Has weakness that if an event was entered during DST, it's wrong during ST and vice versa.
		$ts_sql = "UNIX_TIMESTAMP( CONVERT_TZ( `occur_begin`, '$wp_time', '$offset' ) ) AS ts_occur_begin, UNIX_TIMESTAMP( CONVERT_TZ( `occur_end`, '$wp_time', '$offset' ) ) AS ts_occur_end ";
		set_transient( 'mc_ts_string', $ts_sql, WEEK_IN_SECONDS );
	}

	return $ts_sql;
}

/**
 * Grab all events for the requested dates from calendar
 *
 * This function needs to be able to react to URL parameters for most factors, with the arguments being the default shown.
 *
 * @param array $args parameters to use for selecting events.
 *
 * @return array qualified events
 */
function my_calendar_get_events( $args ) {
	$get      = map_deep( $_GET, 'sanitize_text_field' );
	$from     = isset( $args['from'] ) ? $args['from'] : '';
	$to       = isset( $args['to'] ) ? $args['to'] : '';
	$category = isset( $args['category'] ) ? $args['category'] : 'all';
	$ltype    = isset( $args['ltype'] ) ? $args['ltype'] : 'all';
	$lvalue   = isset( $args['lvalue'] ) ? $args['lvalue'] : 'all';
	$author   = isset( $args['author'] ) ? $args['author'] : 'all';
	$host     = isset( $args['host'] ) ? $args['host'] : 'all';
	$search   = isset( $args['search'] ) ? $args['search'] : '';
	$holidays = isset( $args['holidays'] ) ? $args['holidays'] : null;
	$site     = isset( $args['site'] ) ? $args['site'] : false;
	$site     = ! is_array( $site ) ? array( $site ) : $site;
	$mcdb     = mc_is_remote_db();

	if ( 'holidays' === $holidays && '' === $category ) {
		return array();
	}

	if ( null === $holidays ) {
		$ccategory = ( isset( $get['mcat'] ) && '' !== trim( $get['mcat'] ) ) ? $get['mcat'] : $category;
	} else {
		$ccategory = $category;
	}
	$cltype  = ( isset( $get['ltype'] ) ) ? $get['ltype'] : $ltype;
	$clvalue = ( isset( $get['loc'] ) ) ? $get['loc'] : $lvalue;
	$clauth  = ( isset( $get['mc_auth'] ) ) ? $get['mc_auth'] : $author;
	$clhost  = ( isset( $get['mc_host'] ) ) ? $get['mc_host'] : $host;

	// If location value is not set, then location type shouldn't be set.
	if ( 'all' === $clvalue ) {
		$cltype = 'all';
	}

	$from = mc_checkdate( $from );
	$to   = mc_checkdate( $to );
	if ( ! $from || ! $to ) {
		return array();
	} // Not valid dates.

	$cat_limit          = ( 'all' !== $ccategory ) ? mc_select_category( $ccategory ) : array();
	$join               = ( isset( $cat_limit[0] ) ) ? $cat_limit[0] : '';
	$select_category    = ( isset( $cat_limit[1] ) ) ? $cat_limit[1] : '';
	$select_author      = ( 'all' !== $clauth ) ? mc_select_author( $clauth ) : '';
	$select_host        = ( 'all' !== $clhost ) ? mc_select_host( $clhost ) : '';
	$select_location    = mc_select_location( $cltype, $clvalue );
	$select_access      = ( isset( $get['access'] ) ) ? mc_access_limit( $get['access'] ) : '';
	$select_published   = mc_select_published();
	$search             = mc_prepare_search_query( $search );
	$exclude_categories = mc_private_categories();
	$arr_events         = array();
	$ts_string          = mc_ts();

	/**
	 * Set primary sort for getting events. Default 'occur_begin'.
	 *
	 * @hook mc_primary_sort
	 *
	 * @param {string} $primary_sort SQL sort column.
	 * @param {string} $context Current function.
	 *
	 * @return {string}
	 */
	$primary_sort = apply_filters( 'mc_primary_sort', 'occur_begin', 'my_calendar_get_events' );
	/**
	 * Set secondary sort for getting events. Default 'event_title ASC'.
	 *
	 * @hook mc_secondary_sort
	 *
	 * @param {string} $secondary_sort SQL sort column.
	 * @param {string} $context Current function.
	 *
	 * @return {string}
	 */
	$secondary_sort = apply_filters( 'mc_secondary_sort', 'event_title ASC', 'my_calendar_get_events' );

	$location_join = ( $select_location ) ? 'JOIN (SELECT location_id FROM ' . my_calendar_locations_table() . " WHERE $select_location) l on e.event_location = l.location_id" : '';
	/**
	 * Filter site parameter in queries on a multisite network. Allows a query to show events merged from multiple sites using a single shortcode.
	 *
	 * @hook mc_get_events_sites
	 *
	 * @param {array} $site Array of sites or a single site if displaying events from a different site on the network.
	 * @param {array} $args Shortcode arguments.
	 *
	 * @return {array}
	 */
	$site = apply_filters( 'mc_get_events_sites', $site, $args );
	$site = ! is_array( $site ) ? array( $site ) : $site;

	foreach ( $site as $s ) {
		$event_query = '
	SELECT *, ' . $ts_string . '
	FROM ' . my_calendar_event_table( $s ) . ' AS o
	JOIN ' . my_calendar_table( $s ) . ' AS e
	ON (event_id=occur_event_id)
	JOIN ' . my_calendar_categories_table( $s ) . " AS c 
	ON (event_category=category_id)
	$join
	$location_join
	WHERE $select_published $select_category $select_author $select_host $select_access $search
	AND ( DATE(occur_begin) BETWEEN '$from 00:00:00' AND '$to 23:59:59'
		OR DATE(occur_end) BETWEEN '$from 00:00:00' AND '$to 23:59:59'
		OR ( DATE('$from') BETWEEN DATE(occur_begin) AND DATE(occur_end) )
		OR ( DATE('$to') BETWEEN DATE(occur_begin) AND DATE(occur_end) ) )
	$exclude_categories
	GROUP BY o.occur_id ORDER BY $primary_sort, $secondary_sort";

		$events = $mcdb->get_results( $event_query );

		if ( ! empty( $events ) ) {
			$cats = array();
			$locs = array();
			foreach ( array_keys( $events ) as $key ) {
				$event          =& $events[ $key ];
				$event->site_id = $s;
				$object_id      = $event->event_id;
				$location_id    = $event->event_location;
				if ( ! isset( $cats[ $object_id ] ) ) {
					$categories         = mc_get_categories( $event, 'objects' );
					$event->categories  = $categories;
					$cats[ $object_id ] = $categories;
				} else {
					$event->categories = $cats[ $object_id ];
				}
				if ( 0 !== (int) $location_id ) {
					if ( ! isset( $locs[ $object_id ] ) ) {
						$location           = mc_get_location( $location_id );
						$event->location    = $location;
						$locs[ $object_id ] = $location;
					} else {
						$event->location = $locs[ $object_id ];
					}
				}
				$object = mc_event_object( $event );
				if ( false !== $object ) {
					$arr_events[] = $object;
				}
			}
		}
	}

	/**
	 * Filter events returned by my_calendar_get_events queries. Function returns a range of events between a start and end date.
	 *
	 * @hook mc_filter_events
	 *
	 * @param {array} $arr_events Array of event objects.
	 * @param {array} $args Event query arguments.
	 * @param {string} $context Current function context.
	 *
	 * @return {array}
	 */
	return apply_filters( 'mc_filter_events', $arr_events, $args, 'my_calendar_get_events' );
}

/**
 * Fetch events for upcoming events list. Not date based; fetches the nearest events regardless of date.
 *
 * @param array $args array of event limit parameters.
 *
 * @return array Set of matched events.
 */
function mc_get_all_events( $args ) {
	$category = isset( $args['category'] ) ? $args['category'] : 'default';
	$before   = isset( $args['before'] ) ? $args['before'] : 0;
	$after    = isset( $args['after'] ) ? $args['after'] : 6;
	$author   = isset( $args['author'] ) ? $args['author'] : 'default';
	$host     = isset( $args['host'] ) ? $args['host'] : 'default';
	$ltype    = isset( $args['ltype'] ) ? $args['ltype'] : '';
	$lvalue   = isset( $args['lvalue'] ) ? $args['lvalue'] : '';
	$site     = isset( $args['site'] ) ? $args['site'] : false;
	$search   = isset( $args['search'] ) ? $args['search'] : '';
	$mcdb     = mc_is_remote_db();

	$exclude_categories = mc_private_categories();
	$cat_limit          = ( 'default' !== $category ) ? mc_select_category( $category ) : array();
	$join               = ( isset( $cat_limit[0] ) ) ? $cat_limit[0] : '';
	$select_category    = ( isset( $cat_limit[1] ) ) ? $cat_limit[1] : '';
	$select_location    = mc_select_location( $ltype, $lvalue );
	$location_join      = ( $select_location ) ? 'JOIN (SELECT location_id FROM ' . my_calendar_locations_table() . " WHERE $select_location) l on e.event_location = l.location_id" : '';

	$select_access    = ( isset( $_GET['access'] ) ) ? mc_access_limit( $_GET['access'] ) : '';
	$select_published = mc_select_published();
	$select_author    = ( 'default' !== $author ) ? mc_select_author( $author ) : '';
	$select_host      = ( 'default' !== $host ) ? mc_select_host( $host ) : '';
	$ts_string        = mc_ts();
	$limit            = "$select_published $select_category $select_author $select_host $select_access $search";

	// New Query style.
	$total  = absint( $before ) + absint( $after ) + 30;
	$events = $mcdb->get_results(
		'SELECT *, ' . $ts_string . '
		FROM ' . my_calendar_event_table( $site ) . '
		JOIN ' . my_calendar_table( $site ) . " AS e
		ON (event_id=occur_event_id)
		$join
		$location_join
		JOIN " . my_calendar_categories_table( $site ) . " as c
		ON (e.event_category=c.category_id)
		WHERE $limit
		$exclude_categories
		ORDER BY ABS(TIMESTAMPDIFF(SECOND, NOW(), occur_begin)) ASC LIMIT 0,$total"
	);

	$cats = array();
	foreach ( array_keys( $events ) as $key ) {
		$event          =& $events[ $key ];
		$event->site_id = $site;
		$object_id      = $event->event_id;
		if ( ! isset( $fetched[ $object_id ] ) ) {
			$cats                  = mc_get_categories( $event, 'objects' );
			$event->categories     = $cats;
			$fetched[ $object_id ] = $cats;
		} else {
			$event->categories = $fetched[ $object_id ];
		}
		$object = mc_event_object( $event );
		if ( false !== $object ) {
			$events[ $key ] = $object;
		}
	}

	/**
	 * Filter events returned by mc_get_all_events queries. Function returns a range of events based on proximity to the current date using parameters for number of days/events before or after today.
	 *
	 * @hook mc_filter_events
	 *
	 * @param {array} $events Array of event objects.
	 * @param {array} $args Event query arguments.
	 * @param {string} $context Current function context.
	 *
	 * @return {array}
	 */
	return apply_filters( 'mc_filter_events', $events, $args, 'mc_get_all_events' );
}

/**
 * Fetch only the defined holiday category
 *
 * @param int $before Number of events before.
 * @param int $after Number of events after.
 *
 * @return array events
 */
function mc_get_all_holidays( $before, $after ) {
	if ( ! mc_get_option( 'skip_holidays_category' ) ) {
		return array();
	} else {
		$category = absint( mc_get_option( 'skip_holidays_category' ) );
		$args     = array(
			'category' => $category,
			'before'   => $before,
			'after'    => $after,
		);

		return mc_get_all_events( $args );
	}
}

/**
 * Get most recently added events.
 *
 * @param integer|false $cat_id Category ID.
 *
 * @return array Event objects, limited by category if category ID passed.
 */
function mc_get_new_events( $cat_id = false ) {
	$mcdb      = mc_is_remote_db();
	$ts_string = mc_ts();
	if ( $cat_id ) {
		$cat = "WHERE event_category = $cat_id AND event_approved = 1 AND event_flagged <> 1";
	} else {
		$cat = 'WHERE event_approved = 1 AND event_flagged <> 1';
	}
	$exclude_categories = mc_private_categories();
	/**
	 * Filter how many days of newly added events will be included in ICS subscription links.
	 *
	 * @hook mc_rss_feed_date_range
	 *
	 * @param {int} $limit Number of days. Default 7.
	 *
	 * @return {int}
	 */
	$limit  = apply_filters( 'mc_rss_feed_date_range', 7 );
	$events = $mcdb->get_results(
		'SELECT *, ' . $ts_string . '
		FROM ' . my_calendar_event_table() . '
		JOIN ' . my_calendar_table() . ' ON (event_id=occur_event_id)
		JOIN ' . my_calendar_categories_table() . " AS c ON (event_category=category_id) $cat
		AND event_added > NOW() - INTERVAL $limit DAY 
		$exclude_categories
		ORDER BY event_added DESC"
	);

	if ( empty( $events ) ) {
		$events = $mcdb->get_results(
			'SELECT *, ' . $ts_string . '
			FROM ' . my_calendar_event_table() . '
			JOIN ' . my_calendar_table() . ' ON (event_id=occur_event_id)
			JOIN ' . my_calendar_categories_table() . " AS c ON (event_category=category_id) $cat
			$exclude_categories
			ORDER BY event_added DESC LIMIT 0,30"
		);
	}
	$groups = array();
	$output = array();
	foreach ( array_keys( $events ) as $key ) {
		$event =& $events[ $key ];
		if ( ! in_array( $event->occur_group_id, $groups, true ) ) {
			$output[ $event->event_begin ][] = $event;
		}
		if ( 1 === (int) $event->event_span ) {
			$groups[] = $event->occur_group_id;
		}
	}

	return $output;
}

/**
 * Get all existing instances of an ID. Assemble into array with dates as keys
 *
 * @param int $id Event ID.
 *
 * @return array of event dates & instance IDs
 */
function mc_get_instances( $id ) {
	global $wpdb;
	$id      = (int) $id;
	$results = $wpdb->get_results( $wpdb->prepare( 'SELECT occur_id, occur_begin FROM ' . my_calendar_event_table() . ' WHERE occur_event_id = %d', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$return  = array();

	foreach ( $results as $result ) {
		$key            = sanitize_key( mc_date( 'Y-m-d', strtotime( $result->occur_begin ), false ) );
		$return[ $key ] = $result->occur_id;
	}

	return $return;
}

/**
 * Fetch results of an event search.
 *
 * @param array|string $search array (PRO) or string (Simple).
 *
 * @return array of event objects
 */
function mc_get_search_results( $search ) {
	/**
	 * Filter number of past search results to return. Default 0.
	 *
	 * @hook mc_past_search_results
	 *
	 * @param {int} $before Number of results.
	 *
	 * @return {int}
	 */
	$before = apply_filters( 'mc_past_search_results', 0 );
	/**
	 * Filter number of future search results to return. Default 15.
	 *
	 * @hook mc_future_search_results
	 *
	 * @param {int} $after Number of results.
	 *
	 * @return {int}
	 */
	$after = apply_filters( 'mc_future_search_results', 15 ); // return only future events, nearest 10.
	if ( is_array( $search ) ) {
		// If from & to are set, we need to use a date-based event query.
		$from     = mc_checkdate( $search['from'] );
		$to       = mc_checkdate( $search['to'] );
		$category = ( isset( $search['category'] ) ) ? $search['category'] : null;
		$ltype    = ( isset( $search['ltype'] ) ) ? $search['ltype'] : null;
		$lvalue   = ( isset( $search['lvalue'] ) ) ? $search['lvalue'] : null;
		$author   = ( isset( $search['author'] ) ) ? $search['author'] : null;
		$host     = ( isset( $search['host'] ) ) ? $search['host'] : null;
		$search   = ( isset( $search['search'] ) ) ? $search['search'] : '';
		$args     = array(
			'from'     => $from,
			'to'       => $to,
			'category' => $category,
			'ltype'    => $ltype,
			'lvalue'   => $lvalue,
			'author'   => $author,
			'host'     => $host,
			'search'   => $search,
			'source'   => 'search',
		);

		/**
		 * Filter advanced search query arguments.
		 *
		 * @hook mc_search_attributes
		 *
		 * @param {array} $args Search query arguments.
		 * @param {string} $search Search term.
		 *
		 * @return {array}
		 */
		$args        = apply_filters( 'mc_search_attributes', $args, $search );
		$event_array = my_calendar_events( $args );
	} else {
		// If not, we use relational event queries.
		$args = array(
			'before' => $before,
			'after'  => $after,
			'search' => $search,
		);

		$arr_events    = mc_get_all_events( $args );
		$holidays      = mc_get_all_holidays( $before, $after );
		$holiday_array = mc_set_date_array( $holidays );

		if ( is_array( $arr_events ) && ! empty( $arr_events ) ) {
			$event_array = mc_set_date_array( $arr_events );
			if ( is_array( $holidays ) && count( $holidays ) > 0 ) {
				$event_array = mc_holiday_limit( $event_array, $holiday_array ); // if there are holidays, rejigger.
			}
		}
	}

	/**
	 * Filter search events.
	 *
	 * @hook mc_searched_events
	 *
	 * @param {array} $event_array Array of found event objects.
	 * @param {array} $args Search query arguments.
	 *
	 * @return {array}
	 */
	return (array) apply_filters( 'mc_searched_events', $event_array, $args );
}

/**
 * Get event basic info
 *
 * @param int     $id Event ID in my_calendar db.
 * @param boolean $rebuild Get core data only if doing an event rebuild.
 *
 * @return object|false My Calendar Event
 */
function mc_get_event_core( $id, $rebuild = false ) {
	if ( ! is_numeric( $id ) ) {
		return false;
	}
	$mcdb      = mc_is_remote_db();
	$ts_string = mc_ts();

	if ( $rebuild ) {
		$event = $mcdb->get_row( $mcdb->prepare( 'SELECT * FROM ' . my_calendar_table() . ' JOIN ' . my_calendar_categories_table() . ' ON (event_category=category_id) WHERE event_id=%d', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	} else {
		$event = $mcdb->get_row( $mcdb->prepare( 'SELECT *, ' . $ts_string . ' FROM ' . my_calendar_event_table() . ' JOIN ' . my_calendar_table() . ' ON (event_id=occur_event_id) JOIN ' . my_calendar_categories_table() . ' ON (event_category=category_id) WHERE event_id = %d ORDER BY occur_id ASC LIMIT 1', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$event = mc_event_object( $event );
	}

	return $event;
}

/**
 * Fetches the first fully-realized event object with all parameters even if the specific instance ID isn't available.
 *
 * @param int $id Event core ID.
 *
 * @return object|boolean Event or false if no event found.
 */
function mc_get_first_event( $id ) {
	$mcdb      = mc_is_remote_db();
	$ts_string = mc_ts();
	$event     = ( ! is_admin() ) ? get_transient( 'mc_first_event_cache_' . $id ) : false;
	if ( $event ) {
		return $event;
	} else {
		$event = $mcdb->get_row( $mcdb->prepare( 'SELECT *, ' . $ts_string . 'FROM ' . my_calendar_event_table() . ' JOIN ' . my_calendar_table() . ' ON (event_id=occur_event_id) JOIN ' . my_calendar_categories_table() . ' ON (event_category=category_id) WHERE occur_event_id=%d', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $event ) {
			$event = mc_event_object( $event );
			set_transient( 'mc_first_event_cache_' . $id, $event, WEEK_IN_SECONDS );
		} else {
			$event = false;
		}
	}

	return $event;
}

/**
 * Get the instance-specific information about a single event instance.
 *
 * @param int $instance_id Event instance ID.
 *
 * @return object|null query result
 */
function mc_get_instance_data( $instance_id ) {
	$mcdb   = mc_is_remote_db();
	$result = $mcdb->get_row( $mcdb->prepare( 'SELECT * FROM ' . my_calendar_event_table() . ' WHERE occur_id = %d', $instance_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return $result;
}

/**
 * Fetch the instance of an event closest to today.
 *
 * @param int  $id Event core ID.
 * @param bool $next If true, return closest event that's in the future.
 *
 * @return object Event
 */
function mc_get_nearest_event( $id, $next = false ) {
	$next_event = false;
	$mcdb       = mc_is_remote_db();
	$ts_string  = mc_ts();
	$event      = $mcdb->get_row( $mcdb->prepare( 'SELECT *, ' . $ts_string . ' FROM ' . my_calendar_event_table() . ' JOIN ' . my_calendar_table() . ' ON (event_id=occur_event_id) JOIN ' . my_calendar_categories_table() . ' ON (event_category=category_id) WHERE occur_event_id=%d ORDER BY ABS( DATEDIFF( occur_begin, NOW() ) )', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( true === $next ) {
		$next_event = $mcdb->get_row( $mcdb->prepare( 'SELECT *, ' . $ts_string . ' FROM ' . my_calendar_event_table() . ' JOIN ' . my_calendar_table() . ' ON (event_id=occur_event_id) JOIN ' . my_calendar_categories_table() . ' ON (event_category=category_id) WHERE occur_event_id=%d AND occur_begin > NOW() ORDER BY ABS( DATEDIFF( occur_begin, NOW() ) )', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	$event = ( $next_event ) ? mc_event_object( $next_event ) : mc_event_object( $event );

	return $event;
}

/**
 * Returns the event object for a specific instance of an event.
 *
 * @param int    $id  Event instance ID.
 * @param string $type  'object' or 'html'.
 *
 * @return object|string
 */
function mc_get_event( $id, $type = 'object' ) {
	if ( ! is_numeric( $id ) ) {
		return false;
	}
	$ts_string = mc_ts();
	$mcdb      = mc_is_remote_db();
	$event     = $mcdb->get_row( $mcdb->prepare( 'SELECT *, ' . $ts_string . ' FROM ' . my_calendar_event_table() . ' JOIN ' . my_calendar_table() . ' ON (event_id=occur_event_id) JOIN ' . my_calendar_categories_table() . ' ON (event_category=category_id) WHERE occur_id=%d', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( 'object' === $type ) {
		$event = mc_event_object( $event );
		return $event;
	} else {
		$date         = mc_date( 'Y-m-d', strtotime( $event->occur_begin ), false );
		$time         = mc_date( 'H:i:s', strtotime( $event->occur_begin ), false );
		$event_output = my_calendar_draw_event( $event, 'single', $date, $time, 'single' );
		$value        = '<div id="mc_event">' . $event_output['html'] . '</div>';

		return $value;
	}
}

/**
 * Get a single data field from an event.
 *
 * @param string $field database column.
 * @param int    $id Event core ID.
 *
 * @return mixed string/integer value
 */
function mc_get_data( $field, $id ) {
	$mcdb   = mc_is_remote_db();
	$result = $mcdb->get_var( $mcdb->prepare( "SELECT $field FROM " . my_calendar_table() . ' WHERE event_id = %d', $id ) );

	return $result;
}

/**
 * Fetch all events according to date parameters and supported limits.
 *
 * @since 2.3.0
 *
 * @param array $args array of My Calendar display & limit parameters.
 *
 * @return array Array of event objects with dates as keys.
 */
function my_calendar_events( $args ) {
	/**
	 * Filter calendar event query arguments.
	 *
	 * @hook my_calendar_events_args
	 *
	 * @param {array} $args Array of arguments for display and limiting of events.
	 *
	 * @return {array}
	 */
	$args          = apply_filters( 'my_calendar_events_args', $args );
	$events        = my_calendar_get_events( $args );
	$event_array   = array();
	$holiday_array = array();
	$holidays      = array();
	// Get holidays to filter out.
	if ( mc_get_option( 'skip_holidays_category' ) ) {
		$args['category'] = mc_get_option( 'skip_holidays_category' );
		$args['holidays'] = 'holidays';
		$holidays         = my_calendar_get_events( $args );
		$holiday_array    = mc_set_date_array( $holidays );
	}
	// Get events into an easily parseable set, keyed by date.
	if ( is_array( $events ) && ! empty( $events ) ) {
		$event_array = mc_set_date_array( $events );
		if ( is_array( $holidays ) && count( $holidays ) > 0 ) {
			$event_array = mc_holiday_limit( $event_array, $holiday_array ); // if there are holidays, rejigger.
		}
	}

	return $event_array;
}

/**
 * Get one event currently happening.
 *
 * @param string|integer      $category category ID or 'default'.
 * @param string              $template display Template.
 * @param integer|string|bool $site Site ID if fetching events from a different multisite instance.
 *
 * @return string output HTML
 */
function my_calendar_events_now( $category = 'default', $template = '<strong>{link_title}</strong> {timerange}', $site = false ) {
	if ( $site ) {
		$site = ( 'global' === $site ) ? BLOG_ID_CURRENT_SITE : $site;
		switch_to_blog( $site );
	}
	$mcdb               = mc_is_remote_db();
	$arr_events         = array();
	$select_published   = mc_select_published();
	$cat_limit          = ( 'default' !== $category ) ? mc_select_category( $category ) : array();
	$join               = ( isset( $cat_limit[0] ) ) ? $cat_limit[0] : '';
	$select_category    = ( isset( $cat_limit[1] ) ) ? $cat_limit[1] : '';
	$exclude_categories = mc_private_categories();
	$ts_string          = mc_ts();

	// May add support for location/author/host later.
	$select_location = '';
	$select_author   = '';
	$select_host     = '';
	/**
	 * Set primary sort for getting today's events. Default 'occur_begin'.
	 *
	 * @hook mc_primary_sort
	 *
	 * @param {string} $primary_sort SQL sort column.
	 * @param {string} $context Current function.
	 *
	 * @return {string}
	 */
	$primary_sort = apply_filters( 'mc_primary_sort', 'occur_begin', 'my_calendar_events_now' );
	/**
	 * Set secondary sort for getting today's events. Default 'event_title ASC'.
	 *
	 * @hook mc_secondary_sort
	 *
	 * @param {string} $secondary_sort SQL sort column.
	 * @param {string} $context Current function.
	 *
	 * @return {string}
	 */
	$secondary_sort = apply_filters( 'mc_secondary_sort', 'event_title ASC', 'my_calendar_events_now' );
	$now            = current_time( 'Y-m-d H:i:s' );
	$event_query    = 'SELECT *, ' . $ts_string . '
					FROM ' . my_calendar_event_table( $site ) . ' AS o
					JOIN ' . my_calendar_table( $site ) . " AS e
					ON (event_id=occur_event_id)
					$join
					JOIN " . my_calendar_categories_table( $site ) . " AS c
					ON (event_category=category_id)
					WHERE $select_published $select_category $select_location $select_author $select_host
					$exclude_categories
					AND ( CAST('$now' AS DATETIME) BETWEEN occur_begin AND occur_end )
					ORDER BY $primary_sort, $secondary_sort";
	$events         = $mcdb->get_results( $event_query );
	if ( ! empty( $events ) ) {
		foreach ( array_keys( $events ) as $key ) {
			$event        =& $events[ $key ];
			$arr_events[] = $event;
		}
	}
	if ( ! empty( $arr_events ) ) {
		$event = mc_create_tags( $arr_events[0] );

		if ( mc_key_exists( $template ) ) {
			$template = mc_get_custom_template( $template );
		}

		$args    = array(
			'event'    => $arr_events[0],
			'tags'     => $event,
			'template' => $template,
		);
		$details = mc_load_template( 'event/now', $args );
		if ( $details ) {
			$return = $details;
		} else {
			/**
			 * Customize the template used to draw the "happening now" shortcode output.
			 *
			 * @hook mc_happening_next_template
			 *
			 * @param {string} $template HTML and template tags.
			 * @param {object} $event Event object to draw.
			 *
			 * @return {string}
			 */
			$output = mc_draw_template( $event, apply_filters( 'mc_happening_now_template', $template, $event ) );
			$return = mc_run_shortcodes( $output );
		}
	} else {
		$return = '';
	}

	if ( $site ) {
		restore_current_blog();
	}

	return $return;
}

/**
 * Get the next scheduled event, not currently happening.
 *
 * @param string|integer      $category category ID or 'default'.
 * @param string              $template display Template.
 * @param integer             $skip Number of events to skip.
 * @param integer|string|bool $site Site ID if fetching events from a different multisite instance.
 *
 * @return string output HTML
 */
function my_calendar_events_next( $category = 'default', $template = '<strong>{link_title}</strong> {timerange}', $skip = 0, $site = false ) {
	if ( $site ) {
		$site = ( 'global' === $site ) ? BLOG_ID_CURRENT_SITE : $site;
		switch_to_blog( $site );
	}
	$mcdb               = mc_is_remote_db();
	$arr_events         = array();
	$select_published   = mc_select_published();
	$cat_limit          = ( 'default' !== $category ) ? mc_select_category( $category ) : array();
	$join               = ( isset( $cat_limit[0] ) ) ? $cat_limit[0] : '';
	$select_category    = ( isset( $cat_limit[1] ) ) ? $cat_limit[1] : '';
	$exclude_categories = mc_private_categories();
	$ts_string          = mc_ts();

	// May add support for location/author/host later.
	$select_location = '';
	$select_author   = '';
	$select_host     = '';
	$now             = current_time( 'Y-m-d H:i:s' );
	$event_query     = 'SELECT *, ' . $ts_string . '
			FROM ' . my_calendar_event_table( $site ) . '
			JOIN ' . my_calendar_table( $site ) . " AS e
			ON (event_id=occur_event_id)
			$join
			JOIN " . my_calendar_categories_table( $site ) . " as c
			ON (e.event_category=c.category_id)
			WHERE $select_published $select_category $select_location $select_author $select_host
					$exclude_categories
			AND DATE(occur_begin) > CAST('$now' as DATETIME) ORDER BY occur_begin LIMIT $skip,1";

	$events = $mcdb->get_results( $event_query );
	if ( ! empty( $events ) ) {
		foreach ( array_keys( $events ) as $key ) {
			$event        =& $events[ $key ];
			$arr_events[] = $event;
		}
	}
	if ( ! empty( $arr_events ) ) {
		$event = mc_create_tags( $arr_events[0] );

		if ( mc_key_exists( $template ) ) {
			$template = mc_get_custom_template( $template );
		}

		$args    = array(
			'event'    => $arr_events[0],
			'tags'     => $event,
			'template' => $template,
		);
		$details = mc_load_template( 'event/next', $args );
		if ( $details ) {
			$return = $details;
		} else {
			/**
			 * Customize the template used to draw the next event shortcode output.
			 *
			 * @hook mc_happening_next_template
			 *
			 * @param {string} $template HTML and template tags.
			 * @param {object} $event Event object to draw.
			 *
			 * @return {string}
			 */
			$output = mc_draw_template( $event, apply_filters( 'mc_happening_next_template', $template, $event ) );
			$return = mc_run_shortcodes( $output );
		}
	} else {
		$return = '';
	}

	if ( $site ) {
		restore_current_blog();
	}

	return $return;
}


/**
 *  Get all occurrences associated with an event.
 *
 * @param int $id Event ID.
 *
 * @return array of objects with instance and event IDs.
 */
function mc_get_occurrences( $id ) {
	global $wpdb;
	$id = absint( $id );
	if ( 0 === $id ) {
		return array();
	}
	$results = $wpdb->get_results( $wpdb->prepare( 'SELECT occur_id, occur_event_id FROM ' . my_calendar_event_table() . ' WHERE occur_event_id=%d ORDER BY occur_begin ASC', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return $results;
}

/**
 * Return all instances of a given event.
 *
 * @param array $args Arguments describing the output type.
 *
 * @return string HTML list of instance data & single event view
 */
function mc_instance_list( $args ) {
	$id = isset( $args['event'] ) ? $args['event'] : false;
	if ( ! $id ) {
		return '';
	}
	$template = isset( $args['template'] ) ? $args['template'] : '<h3>{title}</h3>{description}';
	$list     = isset( $args['list'] ) ? $args['list'] : '<li>{date}, {time}</li>';
	$before   = isset( $args['before'] ) ? $args['before'] : '<ul>';
	$after    = isset( $args['after'] ) ? $args['after'] : '</ul>';
	$instance = isset( $args['instance'] ) ? $args['instance'] : false;

	global $wpdb;
	$output = '';
	if ( true === $instance || '1' === $instance ) {
		$sql = 'SELECT * FROM ' . my_calendar_event_table() . ' WHERE occur_id=%d ORDER BY occur_begin ASC';
	} else {
		$sql = 'SELECT * FROM ' . my_calendar_event_table() . ' WHERE occur_event_id=%d ORDER BY occur_begin ASC';
	}
	$results = $wpdb->get_results( $wpdb->prepare( $sql, $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( is_array( $results ) ) {
		$details = '';
		foreach ( $results as $result ) {
			$event_id = $result->occur_id;
			$event    = mc_get_event( $event_id );
			$array    = mc_create_tags( $event );
			if ( in_array( $template, array( 'details', 'grid', 'list', 'mini', 'card' ), true ) || mc_key_exists( $template ) ) {
				if ( 1 === (int) mc_get_option( 'use_' . $template . '_template' ) ) {
					$template = mc_get_template( $template );
				} elseif ( mc_key_exists( $template ) ) {
					$template = mc_get_custom_template( $template );
				} else {
					$details = my_calendar_draw_event( $event, 'single', $event->event_begin, $event->event_time, '', '', $array );
				}
			}
			$item = ( '' !== $list ) ? mc_draw_template( $array, $list ) : '';
			if ( '' === $details ) {
				$details = ( '' !== $template ) ? mc_draw_template( $array, $template ) : '';
			}
			$output .= $item;
			if ( '' === $list ) {
				break;
			}
		}
		$output = $details . $before . $output . $after;

	}

	return mc_run_shortcodes( $output );
}

/**
 * Generate a list of instances for the currently edited event
 *
 * @param int $id Event ID.
 * @param int $occur Specific occurrence ID.
 *
 * @return bool|string
 */
function mc_admin_instances( $id, $occur = 0 ) {
	global $wpdb;
	$output    = '';
	$ts_string = mc_ts();
	$results   = $wpdb->get_results( $wpdb->prepare( 'SELECT *, ' . $ts_string . ' FROM ' . my_calendar_event_table() . ' WHERE occur_event_id=%d ORDER BY occur_begin ASC', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( empty( $results ) ) {
		return false;
	}
	$count = count( $results );
	if ( is_array( $results ) && is_admin() ) {
		foreach ( $results as $result ) {
			$start = $result->ts_occur_begin;
			$end   = $result->ts_occur_end;
			if ( ( ( $end + 1 ) - $start ) === DAY_IN_SECONDS || ( $end - $start ) === DAY_IN_SECONDS ) {
				$time = '';
			} elseif ( ( $end - $start ) <= HOUR_IN_SECONDS ) {
				$time = mc_date( mc_time_format(), $start );
			} else {
				$time = mc_date( mc_time_format(), $start ) . ' - ' . mc_date( mc_time_format(), $end );
			}
			// Omitting format from mc_date() returns timestamp.
			$date  = date_i18n( mc_date_format(), mc_date( '', $start ) );
			$date  = "<span id='occur_date_$result->occur_id'><strong>" . $date . '</strong><br />' . $time . '</span>';
			$class = ( my_calendar_date_xcomp( mc_date( 'Y-m-d H:i:00', $start ), mc_date( 'Y-m-d H:i:00', time() ) ) ) ? 'past-event' : 'future-event';
			if ( (int) $result->occur_id === (int) $occur || 1 === $count ) {
				$control = '';
				$edit    = "<p>$date</p><p><em>" . __( 'Editing Now', 'my-calendar' ) . '</em></p>';
				$class   = 'current-event';
			} else {
				$control = "<p>$date</p><p class='instance-buttons'><button class='button delete_occurrence' type='button' data-event='$result->occur_event_id' data-begin='$result->occur_begin' data-end='$result->occur_end' data-value='$result->occur_id' aria-describedby='occur_date_$result->occur_id' />" . __( 'Delete', 'my-calendar' ) . '</button> ';
				$edit    = "<a href='" . admin_url( 'admin.php?page=my-calendar' ) . "&amp;mode=edit&amp;event_id=$id&amp;date=$result->occur_id' class='button' aria-describedby='occur_date_$result->occur_id'>" . __( 'Edit', 'my-calendar' ) . '</a></p>';
			}
			$output .= "<li class='$class'>$control$edit</li>";
		}
	}

	return $output;
}

/**
 * Get all events with a grouped relationship with the current event.
 *
 * @param int $id Group ID.
 *
 * @return array Array event IDs of grouped events
 */
function mc_get_grouped_events( $id ) {
	global $wpdb;
	$id = (int) $id;
	if ( 0 === $id ) {
		return array();
	}
	$results = $wpdb->get_results( $wpdb->prepare( 'SELECT event_id FROM ' . my_calendar_table() . ' WHERE event_group_id=%d', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return $results;
}


/**
 * Get the events adjacent to the currently displayed event.
 *
 * @param integer $mc_id ID of current event.
 * @param string  $adjacent Next/Previous.
 *
 * @return array Event template array.
 */
function mc_adjacent_event( $mc_id, $adjacent = 'previous' ) {
	$mcdb               = mc_is_remote_db();
	$adjacence          = ( 'next' === $adjacent ) ? '>' : '<';
	$order              = ( 'next' === $adjacent ) ? 'ASC' : 'DESC';
	$site               = false;
	$arr_events         = array();
	$select_published   = mc_select_published();
	$exclude_categories = mc_private_categories();
	$ts_string          = mc_ts();
	$source             = mc_get_event( $mc_id );
	$return             = array();
	if ( is_object( $source ) ) {
		$date        = mc_date( 'Y-m-d H:i:s', strtotime( $source->occur_begin ), false );
		$event_query = 'SELECT *, ' . $ts_string . '
				FROM ' . my_calendar_event_table( $site ) . '
				JOIN ' . my_calendar_table( $site ) . ' AS e
				ON (event_id=occur_event_id)
				JOIN ' . my_calendar_categories_table( $site ) . " as c
				ON (e.event_category=c.category_id)
				WHERE $select_published $exclude_categories
				AND occur_begin $adjacence CAST('$date' as DATETIME) ORDER BY occur_begin $order LIMIT 0,1";

		$events = $mcdb->get_results( $event_query );
		if ( ! empty( $events ) ) {
			foreach ( array_keys( $events ) as $key ) {
				$event        =& $events[ $key ];
				$arr_events[] = $event;
			}
		}
		if ( ! empty( $arr_events ) ) {
			$return = mc_create_tags( $arr_events[0] );
		} else {
			$return = array();
		}
	}

	return $return;
}

/**
 * Remove non-holiday events from data if a holiday is present.
 *
 * @param array $events Array of event objects.
 * @param array $holidays Array of event objects.
 *
 * @return array Array of event objects with conflicts removed.
 */
function mc_holiday_limit( $events, $holidays ) {
	foreach ( array_keys( $events ) as $key ) {
		if ( ! empty( $holidays[ $key ] ) ) {
			foreach ( $events[ $key ] as $k => $event ) {
				if ( (int) mc_get_option( 'skip_holidays_category' ) !== (int) $event->event_category && 1 === (int) $event->event_holiday ) {
					unset( $events[ $key ][ $k ] );
				}
			}
		}
	}

	return $events;
}

/**
 * For date-based views, manipulate array to be organized by dates
 *
 * @param array $events Array of event objects returned by query.
 *
 * @return array $events indexed by date
 */
function mc_set_date_array( $events ) {
	$event_array = array();
	if ( is_array( $events ) && ! empty( $events ) ) {
		foreach ( $events as $event ) {
			$date = mc_date( 'Y-m-d', strtotime( $event->occur_begin ), false );
			$end  = mc_date( 'Y-m-d', strtotime( $event->occur_end ), false );
			if ( $date !== $end ) {
				$start = strtotime( $date );
				$end   = strtotime( $end );
				do {
					$date                   = mc_date( 'Y-m-d', $start, false );
					$event_array[ $date ][] = $event;
					$start                  = strtotime( '+1 day', $start );
				} while ( $start <= $end );
			} else {
				$event_array[ $date ][] = $event;
			}
		}
	}

	return $event_array;
}

/**
 * Verify that a given occurrence ID is valid.
 *
 * @param int $mc_id Occurrence ID.
 *
 * @return boolean|int Returns event ID on valid value.
 */
function mc_valid_id( $mc_id ) {
	$mcdb   = mc_is_remote_db();
	$result = $mcdb->get_var( $mcdb->prepare( 'SELECT occur_event_id FROM ' . my_calendar_event_table() . ' WHERE occur_id = %d', $mc_id ) );

	if ( null !== $result ) {
		return $result;
	}

	return false;
}

/**
 * Get post associated with a given My Calendar event
 *
 * @param int $event_id Event ID.
 *
 * @return mixed int/boolean post ID if found; else false
 */
function mc_get_event_post( $event_id ) {
	$event = mc_get_first_event( $event_id );
	if ( is_object( $event ) ) {
		if ( property_exists( $event, 'event_post' ) && get_post_status( $event->event_post ) ) {
			return $event->event_post;
		}
	}

	return false;
}

/**
 * Check the type of database, so handling of search queries is correct.
 *
 * @return string type of database engine in use;
 */
function mc_get_db_type() {
	// This is unlikely to change, but it's not impossible.
	$db_type = get_transient( 'mc_db_type' );
	if ( ! $db_type ) {
		$db_type     = 'MyISAM';
		$mcdb        = mc_is_remote_db();
		$my_calendar = my_calendar_table();
		$dbs         = $mcdb->get_results( $mcdb->prepare( 'SHOW TABLE STATUS WHERE name=%s', $my_calendar ) );
		foreach ( $dbs as $db ) {
			$db = (array) $db;
			if ( my_calendar_table() === $db['Name'] ) {
				$db_type = $db['Engine'];
			}
		}
		set_transient( 'mc_db_type', $db_type, MONTH_IN_SECONDS );
	}

	return $db_type;
}

/**
 * Produce list of statuses & counts for events manager.
 *
 * @param bool $allow_filters Whether current user can see spam filters.
 *
 * @return string
 */
function mc_status_links( $allow_filters ) {
	$counts = get_option( 'mc_count_cache' );
	if ( empty( $counts ) ) {
		$counts = mc_update_count_cache();
	}
	$all            = isset( $counts['all'] ) ? $counts['all'] : $counts['published'] + $counts['draft'] + $counts['trash'];
	$all_attributes = ( ( isset( $_GET['limit'] ) && 'all' === $_GET['limit'] ) || ! isset( $_GET['limit'] ) ) ? ' aria-current="true"' : '';
	// Translators: Number of total events.
	$all_text = sprintf( __( 'All (%d)', 'my-calendar' ), $all );

	$pub_attributes = ( isset( $_GET['limit'] ) && 'published' === $_GET['limit'] ) ? ' aria-current="true"' : '';
	// Translators: Number of total events.
	$pub_text = sprintf( __( 'Published (%d)', 'my-calendar' ), $counts['published'] );

	$dra_attributes = ( isset( $_GET['limit'] ) && 'draft' === $_GET['limit'] ) ? ' aria-current="true"' : '';
	// Translators: Number of total events.
	$dra_text = sprintf( __( 'Drafts (%d)', 'my-calendar' ), $counts['draft'] );

	$tra_attributes = ( isset( $_GET['limit'] ) && 'trashed' === $_GET['limit'] ) ? ' aria-current="true"' : '';
	// Translators: Number of total events.
	$tra_text = sprintf( __( 'Trashed (%d)', 'my-calendar' ), $counts['trash'] );

	$arc_attributes = ( isset( $_GET['restrict'] ) && 'archived' === $_GET['restrict'] ) ? ' aria-current="true"' : '';
	// Translators: Number of total events.
	$arc_text = sprintf( __( 'Archived (%d)', 'my-calendar' ), $counts['archive'] );

	$spa_attributes = ( isset( $_GET['restrict'] ) && 'flagged' === $_GET['restrict'] ) ? ' aria-current="true"' : '';
	// Translators: Number of total events.
	$spa_text = sprintf( __( 'Spam (%d)', 'my-calendar' ), $counts['spam'] );

	$output = '
	<ul class="links">
		<li>
			<a ' . $all_attributes . ' href="' . mc_admin_url( 'admin.php?page=my-calendar-manage&amp;limit=all' ) . '">' . $all_text . '</a>
		</li>
		<li>
			<a ' . $pub_attributes . ' href="' . mc_admin_url( 'admin.php?page=my-calendar-manage&amp;limit=published' ) . '">' . $pub_text . '</a>
		</li>
		<li>
			<a ' . $dra_attributes . ' href="' . mc_admin_url( 'admin.php?page=my-calendar-manage&amp;limit=draft' ) . '">' . $dra_text . '</a>
		</li>
		<li>
			<a ' . $tra_attributes . ' href="' . admin_url( 'admin.php?page=my-calendar-manage&amp;limit=trashed' ) . '">' . $tra_text . '</a>
		</li>
		<li>
			<a ' . $arc_attributes . ' href="' . mc_admin_url( 'admin.php?page=my-calendar-manage&amp;restrict=archived' ) . '">' . $arc_text . '</a>
		</li>';
	if ( function_exists( 'akismet_http_post' ) && $allow_filters ) {
		$output .= '
		<li>
			<a ' . $spa_attributes . ' href="' . mc_admin_url( 'admin.php?page=my-calendar-manage&amp;restrict=flagged&amp;filter=1' ) . '">' . $spa_text . '</a>
		</li>';
	}
	$output .= '</ul>';

	return $output;
}
