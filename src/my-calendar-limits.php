<?php
/**
 * Generate limits to event queries.
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
 * Prepare search query.
 *
 * @param string $query search term.
 *
 * @return string query params for SQL
 */
function mc_prepare_search_query( $query ) {
	$db_type = mc_get_db_type();
	$length  = strlen( $query );
	$search  = '';
	if ( '' !== trim( $query ) ) {
		$query = esc_sql( urldecode( urldecode( $query ) ) );
		if ( 'MyISAM' === $db_type && $length > 3 ) {
			/**
			 * Customize the MATCH fields for a MyISAM boolean search query.
			 *
			 * @hook mc_search_fields
			 *
			 * @param {string} $values Comma-separated list of columns.
			 *
			 * @return {string}
			 */
			$search = ' AND MATCH(' . apply_filters( 'mc_search_fields', 'event_title,event_desc,event_short,event_registration' ) . ") AGAINST ( '$query' IN BOOLEAN MODE ) ";
		} else {
			$search = " AND ( event_title LIKE '%$query%' OR event_desc LIKE '%$query%' OR event_short LIKE '%$query%' OR event_registration LIKE '%$query%' ) ";
		}
	}

	return $search;
}


/**
 * Generate WHERE pattern for a given category passed
 *
 * @param int|string $category Single or list of categories separated by commas using IDs or names.
 * @param string     $type context of query.
 * @param string     $group context of query.
 *
 * @return array<string> SQL clauses.
 */
function mc_select_category( $category, $type = 'event', $group = 'events' ) {
	if ( ! $category || 'all' === $category ) {
		return '';
	}
	$category      = urldecode( $category );
	$select_clause = '';
	$data          = ( 'category' === $group ) ? 'category_id' : 'r.category_id';
	if ( preg_match( '/^all$|^all,|,all$|,all,/i', $category ) > 0 ) {

		return '';
	} else {

		$categories = mc_category_select_ids( $category );
		if ( count( $categories ) > 0 ) {
			$cats          = implode( ',', $categories );
			$select_clause = "AND $data IN ($cats)";
		}

		$join = '';
		if ( '' !== $select_clause ) {
			$join = ' JOIN ' . my_calendar_category_relationships_table() . ' AS r ON r.event_id = e.event_id ';
		}

		return array( $join, $select_clause );
	}
}

/**
 * Get array of category IDs from passed comma-separated data
 *
 * @param string $category numeric or string-based category tokens.
 *
 * @return array category IDs
 */
function mc_category_select_ids( $category ) {
	$mcdb   = mc_is_remote_db();
	$select = array();

	if ( strpos( $category, '|' ) || strpos( $category, ',' ) ) {
		if ( strpos( $category, '|' ) ) {
			$categories = explode( '|', $category );
		} else {
			$categories = explode( ',', $category );
		}
		foreach ( $categories as $key ) {
			$add = false;
			$key = trim( $key );
			if ( is_numeric( $key ) ) {
				$add = (int) $key;
			} else {
				$key = esc_sql( $key );
				$cat = $mcdb->get_row( 'SELECT category_id FROM ' . my_calendar_categories_table() . " WHERE category_name = '$key'" );
				if ( is_object( $cat ) ) {
					$add = $cat->category_id;
				}
			}
			if ( $add ) {
				$select[] = $add;
			}
		}
	} else {
		$category = trim( $category );
		if ( is_numeric( $category ) ) {
			$select[] = absint( $category );
		} else {
			$cat = $mcdb->get_row( $mcdb->prepare( 'SELECT category_id FROM ' . my_calendar_categories_table() . ' WHERE category_name = %s', trim( $category ) ) );
			if ( is_object( $cat ) ) {
				$select[] = $cat->category_id;
			}
		}
	}

	return $select;
}

/**
 * Get select parameter values for authors & hosts
 *
 * @param string|int $author numeric or string tokens for authors or list of authors.
 * @param string     $type context of query.
 * @param string     $context context of data.
 *
 * @return string WHERE limits
 */
function mc_select_author( $author, $type = 'event', $context = 'author' ) {
	if ( '' === trim( (string) $author ) ) {
		return '';
	}
	$author = urldecode( $author );
	if ( '' === $author || 'all' === $author || 'default' === $author ) {
		return '';
	}
	$select_author = '';
	$data          = ( 'author' === $context ) ? 'event_author' : 'event_host';

	if ( preg_match( '/^all$|^all,|,all$|,all,/i', $author ) > 0 ) {
		return '';
	} else {
		$authors = mc_author_select_ids( $author );
		if ( count( $authors ) > 0 ) {
			$auths         = implode( ',', $authors );
			$select_author = "AND $data IN ($auths)";
		}

		return $select_author;
	}
}

/**
 * Get array of author IDs from passed comma-separated data
 *
 * @param string $author numeric or string-based author tokens.
 *
 * @return array author IDs
 */
function mc_author_select_ids( $author ) {
	$authors = array();
	if ( strpos( $author, '|' ) || strpos( $author, ',' ) ) {
		if ( strpos( $author, '|' ) ) {
			$authors = explode( '|', $author );
		} else {
			$authors = explode( ',', $author );
		}
		foreach ( $authors as $index => $key ) {
			$key = trim( $key );
			if ( is_numeric( $key ) ) {
				$add = absint( $key );
			} elseif ( 'current' === $key ) {
				$author = wp_get_current_user();
				$add    = $author->ID;
				unset( $authors[ $index ] );
			} else {
				$author = get_user_by( 'login', $key ); // Get author by username.
				$add    = $author->ID;
			}

			$authors[] = $add;
		}
	} else {
		if ( is_numeric( $author ) ) {
			$authors[] = absint( $author );
		} else {
			$author = trim( $author );
			$author = get_user_by( 'login', $author ); // Get author by username.

			if ( is_object( $author ) ) {
				$authors[] = $author->ID;
			}
		}
	}

	return $authors;
}

/**
 * Select host params.
 *
 * @uses mc_select_author()
 *
 * @param int|string $host Host ID or name..
 * @param string     $type context.
 *
 * @return string SQL
 */
function mc_select_host( $host, $type = 'event' ) {

	return mc_select_author( $host, $type, 'host' );
}


/**
 * Function to limit event query by location.
 *
 * @param string         $ltype {location type}.
 * @param string|integer $lvalue {location value}.
 *
 * @return string
 */
function mc_select_location( $ltype = '', $lvalue = '' ) {
	$limit_string  = '';
	$limit_strings = array();
	$location      = '';
	if ( ! $ltype || ! $lvalue ) {
		return '';
	} else {
		// If value passed is a string of comma separated values, turn into array.
		if ( is_string( $lvalue ) && false !== stripos( $lvalue, ',' ) ) {
			$lvalue = array_map( 'trim', explode( ',', $lvalue ) );
		}
	}
	if ( ! is_array( $lvalue ) ) {
		$lvalue = array( $lvalue );
	}
	foreach ( $lvalue as $lval ) {
		if ( '' !== $ltype && '' !== $lval ) {
			$location = $ltype;
			switch ( $location ) {
				case 'name':
					$location_type = 'location_label';
					break;
				case 'city':
					$location_type = 'location_city';
					break;
				case 'state':
					$location_type = 'location_state';
					break;
				case 'zip':
					$location_type = 'location_postcode';
					break;
				case 'country':
					$location_type = 'location_country';
					break;
				case 'region':
					$location_type = 'location_region';
					break;
				case 'id':
					$location_type = 'location_id';
					break;
				default:
					$location_type = str_replace( 'event_', 'location_', $location );
			}
			if ( in_array( $location_type, array( 'location_label', 'location_city', 'location_state', 'location_postcode', 'location_country', 'location_region', 'location_id', 'location_street', 'location_street2', 'location_url', 'location_longitude', 'location_latitude', 'location_zoom', 'location_phone', 'location_phone2' ), true ) ) {
				if ( 'all' !== $lval && '' !== $lval ) {
					$lval = trim( $lval );
					if ( is_numeric( $lval ) ) {
						$limit_strings[] = $location_type . ' = ' . absint( $lval );
					} else {
						$limit_strings[] = $location_type . " = '" . esc_sql( urldecode( urldecode( $lval ) ) ) . "'";
					}
				}
			}
		}
	}
	if ( ! empty( $limit_strings ) ) {
		$limit_string = implode( ' OR ', $limit_strings );
	}

	/**
	 * Customize location limit SQL.
	 *
	 * @hook mc_location_limit_sql
	 *
	 * @param {string} $limit_string SQL limit for location query.
	 * @param {string} $ltype Ltype value passed.
	 * @param {string} $lvalue Lvalue passed.
	 *
	 * @return {string}
	 */
	return apply_filters( 'mc_location_limit_sql', $limit_string, $ltype, $lvalue );
}

/**
 * Get events based on accessibility features available
 *
 * @param string $access type of accessibility feature.
 *
 * @return string limits to add to query
 */
function mc_access_limit( $access ) {
	global $wpdb;
	$options      = mc_event_access();
	$format       = ( isset( $options[ $access ] ) ) ? esc_sql( $options[ $access ] ) : false;
	$limit_string = ( $format ) ? "AND event_access LIKE '%$format%'" : '';

	return $limit_string;
}

/**
 * SQL modifiers for published vs. preview
 *
 * @return string
 */
function mc_select_published() {
	if ( mc_is_preview() ) {
		$published = 'event_flagged <> 1 AND ( event_approved = 1 OR event_approved = 0 )';
	} else {
		$published = 'event_flagged <> 1 AND event_approved = 1';
	}

	return $published;
}
