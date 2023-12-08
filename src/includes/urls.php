<?php
/**
 * URL Management. Create and manipulate calendar URLs.
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
 * Build a URL for My Calendar views.
 *
 * @param array<string> $add keys and values to add to URL.
 * @param array<string> $subtract keys to subtract from URL.
 * @param string        $root Root URL, optional.
 *
 * @return string URL.
 */
function mc_build_url( $add, $subtract, $root = '' ) {
	$home = '';
	$root = apply_filters( 'mc_build_url_root', $root );

	if ( '' !== $root ) {
		$home = $root;
	}

	if ( is_numeric( $root ) ) {
		$home = get_permalink( $root );
	}

	if ( '' === $home ) {
		if ( is_front_page() ) {
			$home = home_url( '/' );
		} elseif ( is_home() ) {
			$page = get_option( 'page_for_posts' );
			$home = get_permalink( $page );
		} elseif ( is_archive() ) {
			$home = '';
			// An empty string seems to work best; leaving it open.
		} else {
			wp_reset_query();

			// Break out of alternate loop. If theme uses query_posts to fetch, this causes problems. But themes should *never* use query_posts to replace the loop, so screw that.
			$home = get_permalink();
		}
	}

	$variables = map_deep( $_GET, 'sanitize_text_field' );
	$subtract  = array_merge( (array) $subtract, array( 'from', 'to', 'my-calendar-api', 's', 'embed' ) );
	foreach ( $subtract as $value ) {
		unset( $variables[ $value ] );
	}

	foreach ( $add as $key => $value ) {
		$variables[ $key ] = $value;
	}

	unset( $variables['page_id'] );
	$home = add_query_arg( $variables, $home );
	$home = apply_filters( 'mc_build_url', $home, $add, $subtract, $root );

	return esc_url( $home );
}

/**
 * Is this URL being queried while in the primary content.
 *
 * @param string $url URL to attach query to.
 *
 * @return string
 */
function mc_url_in_loop( $url ) {
	// Only if AJAX is enabled.
	if ( ( '1' !== mc_get_option( 'ajax_javascript' ) ) && true === apply_filters( 'mc_use_embed_targets', false, $url ) ) {
		if ( is_singular() && in_the_loop() && is_main_query() ) {
			$url = esc_url( add_query_arg( 'embed', 'true', html_entity_decode( $url ) ) );
		}
	}

	return $url;
}

/**
 * Build the URL for use in the mini calendar
 *
 * @param int   $start date timestamp.
 * @param int   $category current category.
 * @param array $events array of event objects.
 * @param array $args calendar view parameters.
 * @param array $date view date.
 *
 * @return string URL
 */
function mc_build_mini_url( $start, $category, $events, $args, $date ) {
	$open_day_uri = mc_get_option( 'open_day_uri' );
	$mini_uri     = ( _mc_is_url( mc_get_option( 'mini_uri' ) ) ) ? mc_get_option( 'mini_uri' ) : mc_get_uri( reset( $events ) );
	if ( is_singular() && 'current' === $open_day_uri ) {
		global $post;
		$mini_uri = get_permalink( $post->ID );
	}
	/**
	 * Filter the URI used to link days in the mini calendar.
	 *
	 * @hook mc_modify_day_uri
	 *
	 * @param string $mini_uri The URL generated from settings.
	 * @param array  $args The arguments passed to the current calendar view.
	 *
	 * @return string URL.
	 */
	$mini_uri = apply_filters( 'mc_modify_day_uri', $mini_uri, $args );

	if ( 'true' === $open_day_uri || 'false' === $open_day_uri ) {
		// Yes, this is weird. it's from some old settings...
		$target = array(
			'yr'    => mc_date( 'Y', $start, false ),
			'month' => mc_date( 'm', $start, false ),
			'dy'    => mc_date( 'j', $start, false ),
			'time'  => 'day',
		);
		if ( $category ) {
			$target['mcat'] = $category;
		}
		$day_url = mc_build_url( $target, array( 'month', 'dy', 'yr', 'ltype', 'loc', 'mcat', 'cid', 'mc_id' ), $mini_uri );
		$link    = ( '' !== $day_url ) ? $day_url : '#';
	} else {
		$atype    = str_replace( 'anchor', '', $open_day_uri ); // List or grid.
		$ad       = str_pad( mc_date( 'j', $start, false ), 2, '0', STR_PAD_LEFT ); // Need to match format in ID.
		$am       = str_pad( $date['month'], 2, '0', STR_PAD_LEFT );
		$date_url = mc_build_url(
			array(
				'yr'    => $date['year'],
				'month' => $date['month'],
				'dy'    => mc_date( 'j', $start, false ),
			),
			array( 'month', 'dy', 'yr', 'ltype', 'loc', 'mcat', 'cid', 'mc_id' ),
			$mini_uri
		);
		$link     = esc_url( ( '' !== $mini_uri ) ? $date_url . '#' . $atype . '-' . $date['year'] . '-' . $am . '-' . $ad : '#' );
	}

	return $link;
}

/**
 * Re-parse URL for translation plug-ins.
 *
 * @param string $url Original URL.
 *
 * @return string
 */
function mc_translate_url( $url ) {
	$is_default = true;
	$home_url   = home_url();
	// Polylang support.
	if ( function_exists( 'pll_home_url' ) ) {
		$home_url   = pll_home_url();
		$is_default = ( pll_current_language() === pll_default_language() ) ? true : false;
	}
	// WPML support.
	if ( function_exists( 'wpml_current_language' ) ) {
		$home_url   = apply_filters( 'wpml_home_url', home_url() );
		$is_default = ( apply_filters( 'wpml_current_language', null ) === apply_filters( 'wpml_default_language', null ) ) ? true : false;
	}
	if ( ! $is_default ) {
		$url = str_replace( home_url(), $home_url, $url );
	}

	return $url;
}
add_filter( 'mc_build_url', 'mc_translate_url' );


/**
 * Filter Polylang translation URL in translation menu.
 *
 * @param string $url Translation URL.
 * @param string $lang Language of translation.
 *
 * @return string
 */
function mc_pll_translation_url( $url, $lang ) {
	if ( is_singular( 'mc-events' ) ) {
		$mc_id = '';
		if ( isset( $_GET['mc_id'] ) ) {
			$mc_id = (int) $_GET['mc_id'];
		}
		$url = add_query_arg( 'mc_id', $mc_id, $url );
	}

	return $url;
}
add_filter( 'pll_translation_url', 'mc_pll_translation_url', 10, 2 );
