<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

function my_calendar_insert( $atts, $content = null ) {
	$args = shortcode_atts( array(
		'name'     => 'all',
		'format'   => 'calendar',
		'category' => 'all',
		'time'     => 'month',
		'ltype'    => '',
		'lvalue'   => '',
		'author'   => 'all',
		'host'     => 'all',
		'id'       => '',
		'template' => '',
		'above'    => '',
		'below'    => '',
		'year'     => false,
		'month'    => false,
		'day'      => false,
		'site'     => false,
		'months'   => false
	), $atts, 'my_calendar' );
	
	if ( $args['format'] != 'mini' ) {
		if ( isset( $_GET['format'] ) ) {
			$args['format'] = $_GET['format'];
		}
	}
	global $user_ID;
	if ( $args['author'] == 'current' ) {
		$args['author'] = apply_filters( 'mc_display_author', $user_ID, 'main' );
	}
	if ( $args['host'] == 'current' ) {
		$args['host'] = apply_filters( 'mc_display_host', $user_ID, 'main' );
	}
	
	/*
	 $args['name'], $args['format'], $args['category'], $args['time'], $args['ltype'], $args['lvalue'], $args['id'], $args['template'], $args['content'], $args['author'], $args['host'], $args['above'], $args['below'], $args['year'], $args['month'], $args['day'], 'shortcode', $args['site'], $args['months']
	*/
	
	return my_calendar( $args );
}

function my_calendar_insert_upcoming( $atts ) {
	$args = shortcode_atts( array(
		'before'     => 'default',
		'after'      => 'default',
		'type'       => 'default',
		'category'   => 'default',
		'template'   => 'default',
		'fallback'   => '',
		'order'      => 'asc',
		'skip'       => '0',
		'show_today' => 'yes',
		'author'     => 'default',
		'host'       => 'default',
		'ltype'      => '',
		'lvalue'     => '',
		'from'       => false,
		'to'         => false,
		'site'       => false
	), $atts, 'my_calendar_upcoming' );
	
	global $user_ID;
	if ( $args['author'] == 'current' ) {
		$args['author'] = apply_filters( 'mc_display_author', $user_ID, 'upcoming' );
	}
	if ( $args['host'] == 'current' ) {
		$args['host'] = apply_filters( 'mc_display_host', $user_ID, 'upcoming' );
	}
	
	return my_calendar_upcoming_events( $args );
}

function my_calendar_insert_today( $atts ) {
	$args = shortcode_atts( array(
		'category' => 'default',
		'author'   => 'default',
		'host'     => 'default',
		'template' => 'default',
		'fallback' => '', 
		'date'     => false,
		'site'     => false
	), $atts, 'my_calendar_today' );
	
	global $user_ID;
	if ( $args['author'] == 'current' ) {
		$args['author'] = apply_filters( 'mc_display_author', $user_ID, 'today' );
	}
	if ( $args['host'] == 'current' ) {
		$args['host'] = apply_filters( 'mc_display_host', $user_ID, 'today' );
	}

	return my_calendar_todays_events( $args );
}

/**
 * Shortcode to show list of locations
 */
function my_calendar_show_locations_list( $atts ) {
	$args = shortcode_atts( array(
		'datatype' => 'name',
		'template' => ''
	), $atts, 'my_calendar_locations_list' );

	return my_calendar_show_locations( $args['datatype'], $args['template'] );
}

/**
 * Shortcode to show location filters
 */
function my_calendar_locations( $atts ) {
	$args = shortcode_atts( array(
		'show'     => 'list',
		'datatype' => 'name',
		'target_url' => ''
	), $atts, 'my_calendar_locations' );

	return my_calendar_locations_list( $args['show'], $args['datatype'], $args['target_url'] );
}

/**
 * Shortcode to show category filters
 */
function my_calendar_categories( $atts ) {
	$args = shortcode_atts( array(
		'show' => 'list',
		'target_url' => ''
	), $atts, 'my_calendar_categories' );

	return my_calendar_categories_list( $args['show'], 'public', 'single', $args['target_url'] );
}

/**
 * Shortcode to show accessibility filters
 */
function my_calendar_access( $atts ) {
	$args = shortcode_atts( array(
		'show'       => 'list',
		'target_url' => ''
	), $atts, 'my_calendar_access' );

	return mc_access_list( $args['show'], 'single', $args['target_url'] );
}

/**
 * Shortcode to show filters panels
 */
function my_calendar_filters( $atts ) {
	$args = shortcode_atts( array(
		'show' => 'categories,locations', 
		'target_url' => '',
		'ltype' => 'name'
	), $atts, 'my_calendar_filters' );

	return mc_filters( $args['show'], $args['target_url'], $args['ltype'] );
}

/**
 * Show a single event
 */
function my_calendar_show_event( $atts ) {
	$args = shortcode_atts( array(
		'event'    => '',
		'template' => '<h3>{title}</h3>{description}',
		'list'     => '<li>{date}, {time}</li>',
		'before'   => '<ul>',
		'after'    => '</ul>',
		'instance' => false
	), $atts, 'my_calendar_event' );

	return mc_instance_list( $args );
}

/**
 * Shortcode for simple search form.
 */
function my_calendar_search( $atts ) {
	$args = shortcode_atts( array(
		'type' => 'simple',
		'url' => ''
	), $atts, 'my_calendar_search' );

	return my_calendar_searchform( $args['type'], $args['url'] );
}

/**
 * Currently happening event.
 */
function my_calendar_now( $atts ) {
	$args = shortcode_atts( array(
		'category' => '',
		'template' => '<strong>{link_title}</strong> {timerange}',
		'site'     => false
	), $atts, 'my_calendar_now' );
	
	return my_calendar_events_now( $args['category'], $args['template'], $args['site'] );
}