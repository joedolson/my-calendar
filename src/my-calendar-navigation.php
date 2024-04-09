<?php
/**
 * Navigation Output. Functions that generate elements of the My Calendar navigation.
 *
 * @category Output
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create navigation elements used in My Calendar main view
 *
 * @param array      $params Calendar parameters (modified).
 * @param int        $cat Original category from calendar args.
 * @param int        $start_of_week First day of week.
 * @param int        $show_months num months to show (modified).
 * @param string     $main_class Class/ID.
 * @param int|string $site Which site in multisite.
 * @param array      $date current date.
 * @param string     $from date view started from.
 *
 * @return array of calendar nav for top & bottom
 */
function mc_generate_calendar_nav( $params, $cat, $start_of_week, $show_months, $main_class, $site, $date, $from ) {
	if ( $site ) {
		$site    = ( 'global' === $site ) ? BLOG_ID_CURRENT_SITE : $site;
		$restore = $site;
		restore_current_blog();
	}
	$format   = $params['format'];
	$category = $params['category'];
	$above    = $params['above'];
	$below    = $params['below'];
	$time     = $params['time'];
	$ltype    = $params['ltype'];
	$lvalue   = $params['lvalue'];

	if ( 'none' === $above && 'none' === $below ) {
		return array(
			'bottom' => '',
			'top'    => '',
		);
	}

	// Fallback values.
	$mc_toporder    = array( 'nav', 'toggle', 'jump', 'print', 'timeframe' );
	$mc_bottomorder = array( 'key', 'feeds' );
	$available      = array( 'nav', 'toggle', 'jump', 'print', 'timeframe', 'key', 'feeds', 'exports', 'categories', 'locations', 'access', 'search' );

	if ( 'none' === $above ) {
		$mc_toporder = array();
	} else {
		// Set up above-calendar order of fields.
		if ( '' !== mc_get_option( 'topnav', '' ) ) {
			$mc_toporder = array_map( 'trim', explode( ',', mc_get_option( 'topnav' ) ) );
		}

		if ( '' !== $above ) {
			$mc_toporder = array_map( 'trim', explode( ',', $above ) );
		}
	}

	if ( 'none' === $below ) {
		$mc_bottomorder = array();
	} else {
		if ( '' !== mc_get_option( 'bottomnav', '' ) ) {
			$mc_bottomorder = array_map( 'trim', explode( ',', mc_get_option( 'bottomnav' ) ) );
		}

		if ( '' !== $below ) {
			$mc_bottomorder = array_map( 'trim', explode( ',', $below ) );
		}
	}
	// Navigation elements passed from shortcode or settings.
	$used = array_merge( $mc_toporder, $mc_bottomorder );
	/**
	 * Filter the order in which navigation elements are shown on the top of the calendar.
	 * Insert custom navigation elements by adding a value into the array with a callable function as a value.
	 * E.g. `my_custom_nav`, that expects the $params array as an argument.
	 *
	 * @hook mc_header_navigation
	 * @since 3.4.0
	 *
	 * @param {array} $mc_toporder Array of navigation elements.
	 * @param {array} $used Array of all navigation elements in use for this view.
	 * @param {array} $params Current calendar view parameters.
	 *
	 * @return {array}
	 */
	$mc_toporder = apply_filters( 'mc_header_navigation', $mc_toporder, $used, $params );
	$aboves      = $mc_toporder;

	/**
	 * Filter the order in which navigation elements are shown at the bottom of the calendar.
	 * Insert custom navigation elements by adding a value into the array with a callable function as a value.
	 * E.g. `my_custom_nav`, that expects the $params array as an argument.
	 *
	 * @hook mc_footer_navigation
	 * @since 3.4.0
	 *
	 * @param {array} $mc_bottomorder Array of navigation elements.
	 * @param {array} $used Array of all navigation elements in use for this view.
	 * @param {array} $params Current calendar view parameters.
	 *
	 * @return {array}
	 */
	$mc_bottomorder = apply_filters( 'mc_footer_navigation', $mc_bottomorder, $used, $params );
	$belows         = $mc_bottomorder;

	// Generate array of navigation elements in use, to avoid executing unneeded code.
	$used = array_merge( $aboves, $belows );

	// Define navigation element strings.
	$timeframe    = '';
	$print        = '';
	$toggle       = '';
	$nav          = '';
	$feeds        = '';
	$exports      = '';
	$jump         = '';
	$mc_topnav    = '';
	$mc_bottomnav = '';

	// Setup link data.
	$add = array(
		'time'   => $time,
		'ltype'  => $ltype,
		'lvalue' => $lvalue,
		'mcat'   => $category,
		'yr'     => $date['year'],
		'month'  => $date['month'],
		'dy'     => $date['day'],
		'href'   => ( isset( $params['self'] ) && esc_url( $params['self'] ) ) ? urlencode( $params['self'] ) : urlencode( mc_get_current_url() ),
	);

	if ( 'list' === $format ) {
		$add['format'] = 'list';
	}

	$subtract = array();
	if ( '' === $ltype ) {
		$subtract[] = 'ltype';
		unset( $add['ltype'] );
	}

	if ( '' === $lvalue ) {
		$subtract[] = 'lvalue';
		unset( $add['lvalue'] );
	}

	if ( 'all' === $category ) {
		$subtract[] = 'mcat';
		unset( $add['mcat'] );
	}

	// Set up print link.
	if ( in_array( 'print', $used, true ) ) {
		$print_add    = array_merge( $add, array( 'cid' => 'mc-print-view' ) );
		$mc_print_url = mc_build_url( $print_add, $subtract, home_url() );
		$print        = "<div class='mc-print'><a id='mc_print-$main_class' href='$mc_print_url'>" . __( 'Print<span class="maybe-hide"> View</span>', 'my-calendar' ) . '</a></div>';
	}

	// Set up format toggle.
	$toggle = ( in_array( 'toggle', $used, true ) ) ? mc_format_toggle( $format, 'yes', $time, $main_class ) : '';

	// Set up time toggle.
	if ( in_array( 'timeframe', $used, true ) ) {
		$timeframe = mc_time_toggle( $format, $time, $date['month'], $date['year'], $date['current_date'], $start_of_week, $from, $main_class );
	}

	// Set up category key.
	$key = ( in_array( 'key', $used, true ) ) ? mc_category_key( $cat, $main_class ) : '';

	// Set up category filter.
	$cat_args   = array(
		'categories',
		'id' => $main_class . '-categories',
	);
	$categories = ( in_array( 'categories', $used, true ) ) ? mc_filters( $cat_args, mc_get_current_url() ) : '';

	// Set up location filter.
	$loc_args  = array(
		'locations',
		'id' => $main_class . '-locations',
	);
	$locations = ( in_array( 'locations', $used, true ) ) ? mc_filters( $loc_args, mc_get_current_url(), 'id' ) : '';

	// Set up access filter.
	$acc_args = array(
		'access',
		'id' => $main_class . '-access',
	);
	$access   = ( in_array( 'access', $used, true ) ) ? mc_filters( $acc_args, mc_get_current_url() ) : '';

	// Set up search.
	$search = ( in_array( 'search', $used, true ) ) ? my_calendar_searchform( 'simple', mc_get_current_url(), $main_class ) : '';
	// Set up navigation links.
	if ( in_array( 'nav', $used, true ) ) {
		$nav = mc_nav( $date, $format, $time, $show_months, $main_class, $site );
	}

	// Set up subscription feeds.
	if ( in_array( 'feeds', $used, true ) ) {
		$feeds = mc_sub_links( $subtract );
	}

	// Set up exports.
	if ( in_array( 'exports', $used, true ) ) {
		$ical_m    = ( isset( $_GET['month'] ) ) ? (int) $_GET['month'] : mc_date( 'n' );
		$ical_y    = ( isset( $_GET['yr'] ) ) ? (int) $_GET['yr'] : mc_date( 'Y' );
		$next_link = my_calendar_next_link( $date, $format, $time, $show_months );
		$exports   = mc_export_links( $ical_y, $ical_m, $next_link, $add, $subtract );
	}

	// Set up date switcher.
	if ( in_array( 'jump', $used, true ) ) {
		$jump = mc_date_switcher( $format, $main_class, $time, $date, $site );
	}

	foreach ( $mc_toporder as $value ) {
		if ( 'none' !== $value && in_array( $value, $used, true ) && in_array( $value, $available, true ) ) {
			$value      = trim( $value );
			$mc_topnav .= ${$value};
		}
		if ( ! in_array( $value, $available, true ) && $value && 'none' !== strtolower( $value ) ) {
			if ( function_exists( $value ) ) {
				$mc_topnav .= call_user_func( $value, $params );
			}
		}
	}

	foreach ( $mc_bottomorder as $value ) {
		if ( 'none' !== strtolower( $value ) && 'stop' !== strtolower( $value ) && in_array( $value, $used, true ) && in_array( $value, $available, true ) ) {
			$value         = trim( $value );
			$mc_bottomnav .= ${$value};
		}
		if ( ! in_array( $value, $available, true ) && $value && 'none' !== strtolower( $value ) ) {
			if ( function_exists( $value ) ) {
				$mc_bottomnav .= call_user_func( $value, $params );
			}
		}
	}

	if ( '' !== $mc_topnav ) {
		$mc_topnav = PHP_EOL . '<nav class="my-calendar-navigation" aria-label="' . __( 'Calendar (top)', 'my-calendar' ) . '">' . PHP_EOL . '<div class="my-calendar-header">' . $mc_topnav . '</div>' . PHP_EOL . '</nav>' . PHP_EOL;
	}

	if ( '' !== $mc_bottomnav ) {
		$mc_bottomnav = PHP_EOL . '<nav class="my-calendar-navigation" aria-label="' . __( 'Calendar (bottom)', 'my-calendar' ) . '">' . PHP_EOL . '<div class="mc_bottomnav my-calendar-footer">' . $mc_bottomnav . '</div>' . PHP_EOL . '</nav>' . PHP_EOL;
	}

	if ( $site ) {
		switch_to_blog( $restore );
	}

	return array(
		'bottom' => $mc_bottomnav,
		'top'    => $mc_topnav,
	);
}

/**
 * Generate calendar navigation
 *
 * @param string $date Current date.
 * @param string $format Current format.
 * @param string $time Current time view.
 * @param int    $show_months Num months to show.
 * @param string $id view ID.
 * @param int    $site Optional. Site ID if not main site.
 *
 * @return string prev/next nav.
 */
function mc_nav( $date, $format, $time, $show_months, $id, $site = false ) {
	$prev      = my_calendar_prev_link( $date, $format, $time, $show_months );
	$next      = my_calendar_next_link( $date, $format, $time, $show_months );
	$prev_link = '';
	$next_link = '';
	if ( $prev ) {
		$prev_link = mc_build_url(
			array(
				'yr'    => $prev['yr'],
				'month' => $prev['month'],
				'dy'    => $prev['day'],
				'cid'   => $id,
			),
			array()
		);
		$prev_link = mc_url_in_loop( $prev_link );
		/**
		 * Filter HTML output for navigation 'prev' link.
		 *
		 * @hook mc_prev_link
		 *
		 * @param {string} $prev_link HTML output for link.
		 * @param {array} $prev Previous link parameters.
		 *
		 * @return {string}
		 */
		$prev_link = apply_filters( 'mc_previous_link', '<li class="my-calendar-prev"><a id="mc_previous_' . $id . '" href="' . $prev_link . '" rel="nofollow">' . wp_kses_post( $prev['label'] ) . '</a></li>', $prev );
	}
	if ( $next ) {
		$next_link = mc_build_url(
			array(
				'yr'    => $next['yr'],
				'month' => $next['month'],
				'dy'    => $next['day'],
				'cid'   => $id,
			),
			array()
		);
		$next_link = mc_url_in_loop( $next_link );
		/**
		 * Filter HTML output for navigation 'next' link.
		 *
		 * @hook mc_next_link
		 *
		 * @param {string} $next_link HTML output for link.
		 * @param {array} $next Next link parameters.
		 *
		 * @return {string}
		 */
		$next_link = apply_filters( 'mc_next_link', '<li class="my-calendar-next"><a id="mc_next_' . $id . '" href="' . $next_link . '" rel="nofollow">' . wp_kses_post( $next['label'] ) . '</a></li>', $next );
	}
	$today_text = ( '' === mc_get_option( 'today_events' ) ) ? __( 'Today', 'my-calendar' ) : mc_get_option( 'today_events' );

	$active  = '';
	$current = '';
	if ( ! ( isset( $_GET['month'] ) || isset( $_GET['yr'] ) || isset( $_GET['dy'] ) ) ) {
		$active  = ' mc-active';
		$current = ' aria-current="true"';
	}
	$today      = mc_build_url(
		array(
			'cid' => $id,
		),
		array( 'yr', 'month', 'dy' )
	);
	$today_link = mc_url_in_loop( $today );
	/**
	 * Filter HTML output for navigation 'today' link.
	 *
	 * @hook mc_today_link
	 *
	 * @param {string} $today_link HTML output for link.
	 *
	 * @return {string}
	 */
	$today_link = apply_filters( 'mc_today_link', '<li class="my-calendar-today"><a id="mc_today_' . $id . '" href="' . $today . '" rel="nofollow" class="today' . $active . '"' . $current . '>' . wp_kses_post( $today_text ) . '</a></li>' );

	$nav = '
		<div class="my-calendar-nav">
			<ul>
				' . $prev_link . $today_link . $next_link . '
			</ul>
		</div>';

	return $nav;
}

/**
 * Show the list of categories on the calendar
 *
 * @param int    $category the currently selected category.
 * @param string $id Calendar view ID.
 *
 * @return string HTML for category key
 */
function mc_category_key( $category, $id = '' ) {
	$mcdb            = mc_is_remote_db();
	$url             = plugin_dir_url( __FILE__ );
	$has_icons       = ( 'true' === mc_get_option( 'hide_icons' ) ) ? false : true;
	$class           = ( $has_icons ) ? 'has-icons' : 'no-icons';
	$key             = '';
	$cat_limit       = mc_select_category( $category, 'all', 'category' );
	$select_category = str_replace( 'AND', 'WHERE', ( isset( $cat_limit[1] ) ) ? $cat_limit[1] : '' );

	$sql        = 'SELECT * FROM ' . my_calendar_categories_table() . " $select_category ORDER BY category_name ASC";
	$categories = $mcdb->get_results( $sql );
	$key       .= '<div class="category-key ' . $class . '"><h3 class="maybe-hide">' . __( 'Categories', 'my-calendar' ) . "</h3>\n<ul>\n";

	foreach ( $categories as $cat ) {
		$class = '';
		// Don't display private categories to public users.
		if ( mc_private_event( $cat ) ) {
			continue;
		}
		$hex   = ( 0 !== strpos( $cat->category_color, '#' ) ) ? '#' : '';
		$class = mc_category_class( $cat, '' );

		$selected_categories = ( empty( $_GET['mcat'] ) ) ? array() : map_deep( explode( ',', $_GET['mcat'] ), 'absint' );
		$category_id         = (int) $cat->category_id;

		if ( in_array( $category_id, $selected_categories, true ) || $category === $category_id ) {
			$selected_categories = array_diff( $selected_categories, array( $category_id ) );
			$class              .= ' current';
			$aria_current        = 'aria-current="true"';
		} else {
			$selected_categories[] = $category_id;
			$aria_current          = '';
		}
		$selectable_categories = implode( ',', $selected_categories );
		if ( '' === $selectable_categories ) {
			$url = remove_query_arg( 'mcat', mc_get_current_url() );
		} else {
			$url = mc_build_url( array( 'mcat' => $selectable_categories ), array( 'mcat' ) );
		}
		$url = mc_url_in_loop( $url );
		if ( 1 === (int) $cat->category_private ) {
			$class .= ' private';
		}
		$cat_name = mc_kses_post( stripcslashes( $cat->category_name ) );
		$cat_name = ( '' === $cat_name ) ? '<span class="screen-reader-text">' . __( 'Untitled Category', 'my-calendar' ) . '</span>' : $cat_name;
		$cat_key  = '';
		if ( '' !== $cat->category_icon && $has_icons ) {
			$image    = mc_category_icon( $cat );
			$type     = ( stripos( $image, 'svg' ) ) ? 'svg' : 'img';
			$back     = ( 'background' === mc_get_option( 'apply_color' ) ) ? ' style="background:' . $hex . $cat->category_color . ';"' : '';
			$cat_key .= '<span class="category-color-sample ' . $type . '"' . $back . '>' . $image . '</span>' . $cat_name;
		} elseif ( 'default' !== mc_get_option( 'apply_color' ) ) {
			$cat_key .= ( ( '' !== $cat->category_color ) ? '<span class="category-color-sample no-icon" style="background:' . $hex . $cat->category_color . ';"> &nbsp; </span>' : '' ) . '<span class="mc-category-title">' . $cat_name . '</span>';
		} else {
			// If category colors are ignored, don't render HTML for them.
			$cat_key .= $cat_name;
		}
		$key .= '<li class="cat_' . $class . '"><a id="mc_cat_' . $category_id . '-' . $id . '" href="' . esc_url( $url ) . '" ' . $aria_current . '>' . $cat_key . '</a></li>';
	}
	/**
	 * Filter text label for 'All Categories'.
	 *
	 * @hook mc_text_all_categories
	 *
	 * @param {string} $all Text for link to show all categories.
	 *
	 * @return {string}
	 */
	$all = apply_filters( 'mc_text_all_categories', __( 'All Categories', 'my-calendar' ) );
	if ( isset( $_GET['mcat'] ) ) {
		$key .= "<li class='all-categories'><a id='mc_cat_all-$id' href='" . esc_url( mc_url_in_loop( mc_build_url( array(), array( 'mcat' ), mc_get_current_url() ) ) ) . "'><span>" . $all . '</span></a></li>';
	} else {
		$key .= "<li class='all-categories'><span class='mc-active' id='mc_cat_all-$id' tabindex='-1'>" . $all . '</span></li>';
	}
	$key .= '</ul></div>';

	/**
	 * Filter the category key output in navigation.
	 *
	 * @hook mc_category_key
	 *
	 * @param {string} $key Key HTML output.
	 * @param {array} $categories Categories in key.
	 *
	 * @return {string}
	 */
	$key = apply_filters( 'mc_category_key', $key, $categories );

	return $key;
}

/**
 * Set up subscription links for calendar
 *
 * @return string HTML output for subscription links
 */
function mc_sub_links() {
	$replace = 'webcal:';
	$search  = array( 'http:', 'https:' );

	$google = str_replace( $search, $replace, get_feed_link( 'my-calendar-google' ) );
	$google = add_query_arg( 'cid', $google, 'https://www.google.com/calendar/render' );
	$ical   = str_replace( $search, $replace, get_feed_link( 'my-calendar-ical' ) );

	$sub_google = "<li class='ics google'><a href='" . esc_url( $google ) . "'>" . __( '<span class="maybe-hide">Subscribe in </span>Google', 'my-calendar' ) . '</a></li>';
	$sub_ical   = "<li class='ics ical'><a href='" . esc_url( $ical ) . "'>" . __( '<span class="maybe-hide">Subscribe in </span>iCal', 'my-calendar' ) . '</a></li>';

	$output = "<div class='mc-export mc-subscribe'>
	<ul>$sub_google$sub_ical</ul>
</div>";

	return $output;
}

/**
 * Generate links to export current view's dates.
 *
 * @param string $y year.
 * @param string $m month.
 * @param array  $next array of next view's dates.
 * @param array  $add params to add to link.
 * @param array  $subtract params to subtract from links.
 *
 * @return string HTML output for export links.
 */
function mc_export_links( $y, $m, $next, $add, $subtract ) {
	$add['yr']     = $y;
	$add['month']  = $m;
	$add['nyr']    = $next['yr'];
	$add['nmonth'] = $next['month'];
	unset( $add['href'] );

	$ics  = mc_build_url( $add, $subtract, get_feed_link( 'my-calendar-ics' ) );
	$ics  = add_query_arg( 'cid', $ics, 'https://www.google.com/calendar/render' );
	$ics2 = mc_build_url( $add, $subtract, get_feed_link( 'my-calendar-ics' ) );

	$google = "<li class='ics google'><a href='" . $ics . "'>" . __( '<span class="maybe-hide">Export to </span>Google', 'my-calendar' ) . '</a></li>';
	$ical   = "<li class='ics ical'><a href='" . $ics2 . "'>" . __( '<span class="maybe-hide">Export to </span>iCal', 'my-calendar' ) . '</a></li>';

	$output = "<div class='mc-export mc-download'>
	<ul>$google$ical</ul>
</div>";

	return $output;
}

/**
 * Set up next link based on current view
 *
 * @param array  $date Current date of view.
 * @param string $format of calendar.
 * @param string $time current time view.
 * @param int    $months number of months shown in list views.
 * @param int    $site Optional. Site ID if not main site.
 *
 * @return array of parameters for link
 */
function my_calendar_next_link( $date, $format, $time = 'month', $months = 1, $site = false ) {
	$bounds    = mc_get_date_bounds( $site );
	$cur_year  = (int) $date['year'];
	$cur_month = (int) $date['month'];
	$cur_day   = (int) $date['day'];

	$next_year   = $cur_year + 1;
	$mc_next     = mc_get_option( 'next_events' );
	$next_events = ( '' === $mc_next ) ? '<span class="maybe-hide">' . __( 'Next', 'my-calendar' ) . ' </span>' : stripslashes( $mc_next );
	if ( $months <= 1 || 'list' !== $format ) {
		if ( 12 === (int) $cur_month ) {
			$month = 1;
			$yr    = $next_year;
		} else {
			$next_month = $cur_month + 1;
			$month      = $next_month;
			$yr         = $cur_year;
		}
	} else {
		$next_month = ( ( $cur_month + $months ) > 12 ) ? ( ( $cur_month + $months ) - 12 ) : ( $cur_month + $months );
		if ( $cur_month >= ( 13 - $months ) ) {
			$month = $next_month;
			$yr    = $next_year;
		} else {
			$month = $next_month;
			$yr    = $cur_year;
		}
	}
	$day = '';
	if ( (int) $yr !== (int) $cur_year ) {
		/**
		 * Filter the date format used for next link if the next link is in a different year.
		 *
		 * @hook mc_month_format
		 *
		 * @param {string} $format PHP Date format string.
		 * @param {array} $date Current date array.
		 * @param {string} $format View format.
		 * @param {string} $time View time frame.
		 * @param {string} $month month used in navigation reference (next month.)
		 *
		 * @return {string}
		 */
		$format = apply_filters( 'mc_month_year_format', 'F, Y', $date, $format, $time, $month );
	} else {
		/**
		 * Filter the date format used for next link if the next link is in the same year.
		 *
		 * @hook mc_month_format
		 *
		 * @param {string} $format PHP Date format string.
		 * @param {array} $date Current date array.
		 * @param {string} $format View format.
		 * @param {string} $time View time frame.
		 * @param {string} $month month used in navigation reference (next month.)
		 *
		 * @return {string}
		 */
		$format = apply_filters( 'mc_month_format', 'F', $date, $format, $time, $month );
	}
	$date = date_i18n( $format, mktime( 0, 0, 0, $month, 1, $yr ) );
	if ( 'week' === $time ) {
		$nextdate = strtotime( "$cur_year-$cur_month-$cur_day" . '+ 7 days' );
		$day      = mc_date( 'd', $nextdate, false );
		$yr       = mc_date( 'Y', $nextdate, false );
		$month    = mc_date( 'm', $nextdate, false );
		if ( (int) $yr !== (int) $cur_year ) {
			$format = 'F j, Y';
		} else {
			$format = 'F j';
		}
		// Translators: Current formatted date.
		$date = sprintf( __( 'Week of %s', 'my-calendar' ), date_i18n( $format, mktime( 0, 0, 0, $month, $day, $yr ) ) );
	}
	if ( 'day' === $time ) {
		$nextdate = strtotime( "$cur_year-$cur_month-$cur_day" . '+ 1 days' );
		$day      = mc_date( 'd', $nextdate, false );
		$yr       = mc_date( 'Y', $nextdate, false );
		$month    = mc_date( 'm', $nextdate, false );
		if ( (int) $yr !== (int) $cur_year ) {
			$format = 'F j, Y';
		} else {
			$format = 'F j';
		}
		$date = date_i18n( $format, mktime( 0, 0, 0, $month, $day, $yr ) );
	}
	$next_events = str_replace( '{date}', $date, $next_events );
	$test_date   = ( $day ) ? "$yr-$month-$day" : "$yr-" . str_pad( $month, 2, 0, STR_PAD_LEFT ) . '-01';
	if ( strtotime( $bounds['last'] ) < strtotime( $test_date ) ) {
		$output = false;
	} else {
		$output = array(
			'month' => $month,
			'yr'    => $yr,
			'day'   => $day,
			'label' => $next_events,
		);
	}

	return $output;
}

/**
 * Set up prev link based on current view
 *
 * @param array  $date Current date of view.
 * @param string $format of calendar.
 * @param string $time current time view.
 * @param int    $months number of months shown in list views.
 * @param int    $site Optional. Site ID if not main site.
 *
 * @return array of parameters for link
 */
function my_calendar_prev_link( $date, $format, $time = 'month', $months = 1, $site = false ) {
	$bounds    = mc_get_date_bounds( $site );
	$cur_year  = (int) $date['year'];
	$cur_month = (int) $date['month'];
	$cur_day   = (int) $date['day'];

	$last_year       = $cur_year - 1;
	$mc_previous     = mc_get_option( 'previous_events' );
	$previous_events = ( '' === $mc_previous ) ? '<span class="maybe-hide">' . __( 'Previous', 'my-calendar' ) . ' </span>' : stripslashes( $mc_previous );
	if ( $months <= 1 || 'list' !== $format ) {
		if ( 1 === (int) $cur_month ) {
			$month = 12;
			$yr    = $last_year;
		} else {
			$next_month = $cur_month - 1;
			$month      = $next_month;
			$yr         = $cur_year;
		}
	} else {
		$next_month = ( $cur_month > $months ) ? ( $cur_month - $months ) : ( ( $cur_month - $months ) + 12 );
		if ( $cur_month <= $months ) {
			$month = $next_month;
			$yr    = $last_year;
		} else {
			$month = $next_month;
			$yr    = $cur_year;
		}
	}
	if ( (int) $yr !== (int) $cur_year ) {
		/**
		 * Filter the date format used for previous link if the prev link is in a different year.
		 *
		 * @hook mc_month_year_format
		 *
		 * @param {string} $format PHP Date format string.
		 * @param {array} $date Current date array.
		 * @param {string} $format View format.
		 * @param {string} $time View time frame.
		 * @param {string} $month month used in navigation reference (previous month.)
		 *
		 * @return {string}
		 */
		$format = apply_filters( 'mc_month_year_format', 'F, Y', $date, $format, $time, $month );
	} else {
		/**
		 * Filter the date format used for previous link if the previous link is in the same year.
		 *
		 * @hook mc_month_format
		 *
		 * @param {string} $format PHP Date format string.
		 * @param {array} $date Current date array.
		 * @param {string} $format View format.
		 * @param {string} $time View time frame.
		 * @param {string} $month month used in navigation reference (previous month, generally.)
		 *
		 * @return {string}
		 */
		$format = apply_filters( 'mc_month_format', 'F', $date, $format, $time, $month );
	}
	$date = date_i18n( $format, mktime( 0, 0, 0, $month, 1, $yr ) );
	$day  = '';
	if ( 'week' === $time ) {
		$prevdate = strtotime( "$cur_year-$cur_month-$cur_day" . '- 7 days' );
		$day      = mc_date( 'd', $prevdate, false );
		$yr       = mc_date( 'Y', $prevdate, false );
		$month    = mc_date( 'm', $prevdate, false );
		if ( (int) $yr !== (int) $cur_year ) {
			$format = 'F j, Y';
		} else {
			$format = 'F j';
		}
		$date = __( 'Week of ', 'my-calendar' ) . date_i18n( $format, mktime( 0, 0, 0, $month, $day, $yr ) );
	}
	if ( 'day' === $time ) {
		$prevdate = strtotime( "$cur_year-$cur_month-$cur_day" . '- 1 days' );
		$day      = mc_date( 'd', $prevdate, false );
		$yr       = mc_date( 'Y', $prevdate, false );
		$month    = mc_date( 'm', $prevdate, false );
		if ( (int) $yr !== (int) $cur_year ) {
			$format = 'F j, Y';
		} else {
			$format = 'F j';
		}
		$date = date_i18n( $format, mktime( 0, 0, 0, $month, $day, $yr ) );
	}
	$previous_events = str_replace( '{date}', $date, $previous_events );
	$test_date       = ( $day ) ? "$yr-$month-$day" : "$yr-" . str_pad( $month, 2, 0, STR_PAD_LEFT ) . '-' . mc_date( 't', strtotime( "$yr-$month" ) );
	if ( strtotime( $bounds['first'] ) > strtotime( $test_date ) ) {
		$output = false;
	} else {
		$output = array(
			'month' => $month,
			'yr'    => $yr,
			'day'   => $day,
			'label' => $previous_events,
		);
	}

	return $output;
}

/**
 * Generate filters form to limit calendar events.
 *
 * @param array  $args can include 'categories', 'locations' and 'access' to define individual filters.
 * @param string $target_url Where to send queries.
 * @param string $ltype Which type of location data to show in form. Default ID.
 *
 * @return string HTML output of form
 */
function mc_filters( $args, $target_url, $ltype = 'id' ) {
	$id = ( isset( $args['id'] ) ) ? esc_attr( $args['id'] ) : 'mc_filters';
	if ( isset( $args['id'] ) ) {
		unset( $args['id'] );
	}
	if ( ! is_array( $args ) ) {
		$fields = explode( ',', $args );
	} else {
		$fields = $args;
	}
	if ( empty( $fields ) ) {
		return '';
	}
	$has_multiple = ( count( $fields ) > 1 ) ? true : false;
	$return       = false;

	$current_url = mc_get_uri();
	$current_url = ( '' !== $target_url && esc_url( $target_url ) ) ? $target_url : $current_url;
	$class       = ( $has_multiple ) ? 'mc-filters-form' : 'mc-' . esc_attr( $fields[0] ) . '-switcher';
	$form        = "
	<div id='$id' class='mc_filters'>
		<form action='" . esc_url( $current_url ) . "' method='get' class='$class'>\n";
	$qsa         = array();
	if ( isset( $_SERVER['QUERY_STRING'] ) ) {
		parse_str( $_SERVER['QUERY_STRING'], $qsa );
	}
	if ( ! isset( $_GET['cid'] ) ) {
		$form .= '<input type="hidden" name="cid" value="all" />';
	}
	foreach ( $qsa as $name => $argument ) {
		$name = esc_attr( strip_tags( $name ) );
		if ( ! ( 'access' === $name || 'mcat' === $name || 'loc' === $name || 'ltype' === $name || 'mc_id' === $name || 'legacy-widget-preview' === $name ) ) {
			$argument = ( ! is_string( $argument ) ) ? (string) $argument : $argument;
			$argument = esc_attr( strip_tags( $argument ) );
			$form    .= '<input type="hidden" name="' . $name . '" value="' . $argument . '" />' . "\n";
		}
	}
	$multiple = __( 'Events', 'my-calendar' );
	$key      = '';
	foreach ( $fields as $show ) {
		$show = trim( $show );
		switch ( $show ) {
			case 'categories':
				$cats   = my_calendar_categories_list( 'form', 'public', 'group' );
				$form  .= '<div class="mc-category-filter">' . $cats . '</div>';
				$return = ( $cats || $return ) ? true : false;
				$key    = __( 'Categories', 'my-calendar' );
				break;
			case 'locations':
				$locs   = my_calendar_locations_list( 'form', $ltype, 'group' );
				$form  .= '<div class="mc-location-filter">' . $locs . '</div>';
				$return = ( $locs || $return ) ? true : false;
				$key    = __( 'Locations', 'my-calendar' );
				break;
			case 'access':
				$access = mc_access_list( 'form', 'group' );
				$form  .= '<div class="mc-access-filter">' . $access . '</div>';
				$return = ( $access || $return ) ? true : false;
				$key    = __( 'Accessibility Services', 'my-calendar' );
				break;
		}
	}
	$key = ( $has_multiple ) ? $multiple : $key;
	// Translators: Type of filter shown. Events, Categories, Locations, or Accessibility Services.
	$label = sprintf( __( 'Filter %s', 'my-calendar' ), '<span class="screen-reader-text"> ' . $key . '</a>' );
	$form .= '<p><button id="mc_filter_' . $show . '-' . $id . '" class="button" data-href="' . esc_url( $current_url ) . '">' . $label . '"</button></p>
	</form></div>';
	if ( $return ) {
		return $form;
	}

	return '';
}

/**
 * Generate select form of categories for filters.
 *
 * @param string $show type of view.
 * @param string $context Public or admin.
 * @param string $group single form or part of a field group.
 * @param string $target_url Where to post form to.
 *
 * @return string HTML
 */
function my_calendar_categories_list( $show = 'list', $context = 'public', $group = 'single', $target_url = '' ) {
	$mcdb        = mc_is_remote_db();
	$output      = '';
	$current_url = mc_get_uri();
	$current_url = ( '' !== $target_url && esc_url( $target_url ) ) ? $target_url : $current_url;

	$name         = ( 'public' === $context ) ? 'mcat' : 'category';
	$admin_fields = ( 'public' === $context ) ? ' name="' . $name . '"' : ' multiple="multiple" size="5" name="' . $name . '[]"  ';
	$admin_label  = ( 'public' === $context ) ? '' : __( '(select to include)', 'my-calendar' );
	$form         = ( 'single' === $group ) ? '<form action="' . esc_url( $current_url ) . '" method="get">
				<div>' : '';
	if ( 'single' === $group ) {
		$qsa = array();
		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			parse_str( $_SERVER['QUERY_STRING'], $qsa );
		}
		if ( ! isset( $_GET['cid'] ) ) {
			$form .= '<input type="hidden" name="cid" value="all" />';
		}
		foreach ( $qsa as $name => $argument ) {
			if ( ! ( 'mcat' === $name || 'mc_id' === $name ) ) {
				$form .= '<input type="hidden" name="' . esc_attr( strip_tags( $name ) ) . '" value="' . esc_attr( strip_tags( $argument ) ) . '" />' . "\n";
			}
		}
	}
	$form       .= ( 'list' === $show || 'group' === $group ) ? '' : '
		</div><p>';
	$public_form = ( 'public' === $context ) ? $form : '';
	if ( ! is_user_logged_in() ) {
		$categories = $mcdb->get_results( 'SELECT * FROM ' . my_calendar_categories_table() . ' WHERE category_private = 0 ORDER BY category_name ASC' );
	} else {
		$categories = $mcdb->get_results( 'SELECT * FROM ' . my_calendar_categories_table() . ' ORDER BY category_name ASC' );
	}
	if ( ! empty( $categories ) && count( $categories ) >= 1 ) {
		$output  = ( 'single' === $group ) ? "<div id='mc_categories'>\n" : '';
		$url     = mc_build_url( array( 'mcat' => 'all' ), array() );
		$output .= ( 'list' === $show ) ? "
		<ul>
			<li><a href='$url'>" . __( 'All Categories', 'my-calendar' ) . '</a></li>' : $public_form . '
			<label for="category">' . __( 'Categories', 'my-calendar' ) . ' ' . $admin_label . '</label>
			<select' . $admin_fields . ' id="category">
			<option value="all">' . __( 'All Categories', 'my-calendar' ) . '</option>' . "\n";

		foreach ( $categories as $category ) {
			$category_name = strip_tags( stripcslashes( $category->category_name ), mc_strip_tags() );
			$mcat          = ( empty( $_GET['mcat'] ) ) ? '' : (int) $_GET['mcat'];
			$category_id   = (int) $category->category_id;
			if ( 'list' === $show ) {
				$this_url = mc_build_url( array( 'mcat' => $category->category_id ), array() );
				$selected = ( $category_id === $mcat ) ? ' class="selected"' : '';
				$output  .= " <li$selected><a rel='nofollow' href='$this_url'>$category_name</a></li>";
			} else {
				$selected = ( $category_id === $mcat ) ? ' selected="selected"' : '';
				$output  .= " <option$selected value='$category_id'>$category_name</option>\n";
			}
		}
		$output .= ( 'list' === $show ) ? '</ul>' : '</select>';
		if ( 'admin' !== $context && 'list' !== $show ) {
			if ( 'single' === $group ) {
				$output .= '<input type="submit" class="button" value="' . __( 'Submit', 'my-calendar' ) . '" /></p></form>';
			}
		}
		$output .= ( 'single' === $group ) ? '</div>' : '';
	}

	/**
	 * Filter the HTML for the category filter dropdown in navigation elements.
	 *
	 * @hook mc_category_selector
	 *
	 * @param {string} $toggle HTML output for control.
	 * @param {array} $categories Available categories.
	 *
	 * @return {string}
	 */
	$output = apply_filters( 'mc_category_selector', $output, $categories );

	return $output;
}

/**
 * Show set of filters to limit by accessibility features.
 *
 * @param string $show type of view.
 * @param string $group single or multiple.
 * @param string $target_url Where to post form to.
 *
 * @return string HTML
 */
function mc_access_list( $show = 'list', $group = 'single', $target_url = '' ) {
	$output      = '';
	$current_url = mc_get_uri();
	$current_url = ( '' !== $target_url && esc_url( $target_url ) ) ? $target_url : $current_url;
	$form        = ( 'single' === $group ) ? "<form action='" . esc_url( $current_url ) . "' method='get'><div>" : '';
	if ( 'single' === $group ) {
		$qsa = array();
		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			parse_str( $_SERVER['QUERY_STRING'], $qsa );
		}
		if ( ! isset( $_GET['cid'] ) ) {
			$form .= '<input type="hidden" name="cid" value="all" />';
		}
		foreach ( $qsa as $name => $argument ) {
			if ( ! ( 'access' === $name || 'mc_id' === $name ) ) {
				$form .= '<input type="hidden" name="' . esc_attr( strip_tags( $name ) ) . '" value="' . esc_attr( strip_tags( $argument ) ) . '" />' . "\n";
			}
		}
	}
	$form .= ( 'list' === $show || 'group' === $group ) ? '' : '</div><p>';

	$access_options = mc_event_access();
	if ( ! empty( $access_options ) && count( $access_options ) >= 1 ) {
		$output       = ( 'single' === $group ) ? "<div id='mc_access'>\n" : '';
		$url          = mc_build_url( array( 'access' => 'all' ), array() );
		$not_selected = ( ! isset( $_GET['access'] ) ) ? 'selected="selected"' : '';
		$output      .= ( 'list' === $show ) ? "
		<ul>
			<li><a href='$url'>" . __( 'Accessibility Services', 'my-calendar' ) . '</a></li>' : $form . '
		<label for="access">' . __( 'Accessibility Services', 'my-calendar' ) . '</label>
			<select name="access" id="access">
			<option value="all"' . $not_selected . '>' . __( 'All Services', 'my-calendar' ) . '</option>' . "\n";

		foreach ( $access_options as $key => $access ) {
			$access_name = $access;
			$this_access = ( empty( $_GET['access'] ) ) ? '' : (int) $_GET['access'];
			if ( 'list' === $show ) {
				$this_url = mc_build_url( array( 'access' => $key ), array() );
				$selected = ( $key === $this_access ) ? ' class="selected"' : '';
				$output  .= " <li$selected><a rel='nofollow' href='$this_url'>$access_name</a></li>";
			} else {
				$selected = ( $this_access === $key ) ? ' selected="selected"' : '';
				$output  .= " <option$selected value='" . esc_attr( $key ) . "'>" . esc_html( $access_name ) . "</option>\n";
			}
		}
		$output .= ( 'list' === $show ) ? '</ul>' : '</select>';
		$output .= ( 'list' !== $show && 'single' === $group ) ? '<p><input type="submit" class="button" value="' . __( 'Limit by Access', 'my-calendar' ) . '" /></p></form>' : '';
		$output .= ( 'single' === $group ) ? "\n</div>" : '';
	}
	/**
	 * Filter the HTML for the accessibility feature filter in navigation elements.
	 *
	 * @hook mc_access_selector
	 *
	 * @param {string} $output HTML output for control.
	 * @param {array}  $access_options Available accessibility options.
	 *
	 * @return {string}
	 */
	$output = apply_filters( 'mc_access_selector', $output, $access_options );

	return $output;
}

/**
 * Build date switcher
 *
 * @param string $type Current view being shown.
 * @param string $cid ID of current view.
 * @param string $time Current time view.
 * @param array  $date current date array (month, year, day).
 * @param int    $site Optional. Site ID if not current site.
 *
 * @return string HTML output.
 */
function mc_date_switcher( $type = 'calendar', $cid = 'all', $time = 'month', $date = array(), $site = false ) {
	if ( 'week' === $time ) {
		return '';
	}
	$c_month = isset( $date['month'] ) ? $date['month'] : current_time( 'n' );
	$c_year  = isset( $date['year'] ) ? $date['year'] : current_time( 'Y' );
	$c_day   = isset( $date['day'] ) ? $date['day'] : current_time( 'j' );

	$current_url    = mc_get_current_url();
	$date_switcher  = '';
	$date_switcher .= '<div class="my-calendar-date-switcher"><form class="mc-date-switcher" action="' . esc_url( $current_url ) . '" method="get"><div>';
	$qsa            = array();
	if ( isset( $_SERVER['QUERY_STRING'] ) ) {
		parse_str( $_SERVER['QUERY_STRING'], $qsa );
	}
	if ( ! isset( $_GET['cid'] ) ) {
		$date_switcher .= '<input type="hidden" name="cid" value="' . esc_attr( $cid ) . '" />';
	}
	$data_href = $current_url;
	foreach ( $qsa as $name => $argument ) {
		$name = esc_attr( strip_tags( $name ) );
		if ( is_array( $argument ) ) {
			$argument = '';
		} else {
			$argument = esc_attr( strip_tags( $argument ) );
		}
		if ( 'month' !== $name && 'yr' !== $name && 'dy' !== $name ) {
			$date_switcher .= '<input type="hidden" name="' . $name . '" value="' . $argument . '" />';
			$data_href      = add_query_arg( $name, $argument, $data_href );
		}
	}
	$day_switcher = '';
	if ( 'day' === $time ) {
		$day_switcher = ' <label class="maybe-hide" for="' . $cid . '-day">' . __( 'Day', 'my-calendar' ) . '</label> <select id="' . $cid . '-day" name="dy">' . "\n";
		for ( $i = 1; $i <= 31; $i++ ) {
			$day_switcher .= "<option value='$i'" . selected( $i, $c_day, false ) . '>' . $i . '</option>' . "\n";
		}
		$day_switcher .= '</select>';
	}
	// We build the months in the switcher.
	$date_switcher .= ' <label class="maybe-hide" for="' . $cid . '-month">' . __( 'Month', 'my-calendar' ) . '</label> <select id="' . $cid . '-month" name="month">' . "\n";
	for ( $i = 1; $i <= 12; $i++ ) {
		$test           = str_pad( $i, 2, '0', STR_PAD_LEFT );
		$c_month        = str_pad( $c_month, 2, '0', STR_PAD_LEFT );
		$date_switcher .= "<option value='$i'" . selected( $test, $c_month, false ) . '>' . date_i18n( 'F', mktime( 0, 0, 0, $i, 1 ) ) . '</option>' . "\n";
	}
	$date_switcher .= '</select>' . "\n" . $day_switcher . ' <label class="maybe-hide" for="' . $cid . '-year">' . __( 'Year', 'my-calendar' ) . '</label> <select id="' . $cid . '-year" name="yr">' . "\n";
	// Check first start date in the database.
	$bounds = mc_get_date_bounds( $site );
	$first  = $bounds['first'];
	$first  = ( '1970-01-01 00:00:00' === $first ) ? '2000-01-01' : $first;
	$year1  = (int) mc_date( 'Y', strtotime( $first, false ) );
	$diff1  = (int) mc_date( 'Y' ) - $year1;
	$past   = $diff1;
	// Check last end date.
	$last   = $bounds['last'];
	$year2  = (int) mc_date( 'Y', strtotime( $last, false ) );
	$diff2  = $year2 - (int) mc_date( 'Y' );
	$future = $diff2;
	/**
	 * How many years into the future should be shown in the navigation jumpbox. Default '5'.
	 *
	 * @hook mc_jumpbox_future_years
	 *
	 * @param {int}   $future Number of years ahead.
	 * @param {string} $cid Current calendar ID. '' when running in the shortcode generator.
	 *
	 * @return {int}
	 */
	$future = apply_filters( 'mc_jumpbox_future_years', $future, $cid );
	$fut    = 1;
	$f      = '';
	$p      = '';
	$time   = (int) current_time( 'Y' );

	while ( $past > 0 ) {
		$p   .= '<option value="';
		$p   .= $time - $past;
		$p   .= '"' . selected( $time - $past, $c_year, false ) . '>';
		$p   .= $time - $past . "</option>\n";
		$past = $past - 1;
	}

	while ( $fut <= $future ) {
		$f  .= '<option value="';
		$f  .= $time + $fut;
		$f  .= '"' . selected( $time + $fut, $c_year, false ) . '>';
		$f  .= $time + $fut . "</option>\n";
		$fut = $fut + 1;
	}

	$date_switcher .= $p;
	$date_switcher .= '<option value="' . $time . '"' . selected( $time, $c_year, false ) . '>' . $time . "</option>\n";
	$date_switcher .= $f;
	$date_switcher .= '</select> <input type="submit" class="button" data-href="' . esc_attr( $data_href ) . '" value="' . __( 'Go', 'my-calendar' ) . '" /></div></form></div>';

	/**
	 * Filter the HTML for the date jumpbox controls.
	 *
	 * @hook mc_jumpbox
	 *
	 * @param {string} $date_switcher HTML output for control.
	 * @param {string} $type Current view format.
	 * @param {string} $time Current time frame.
	 *
	 * @return {string}
	 */
	$date_switcher = apply_filters( 'mc_jumpbox', $date_switcher, $type, $time );

	return $date_switcher;
}

/**
 * Generate toggle between list and grid views
 *
 * @param string $format currently shown.
 * @param string $toggle whether to show.
 * @param string $time Current time view.
 * @param string $id Calendar ID.
 *
 * @return string HTML output
 */
function mc_format_toggle( $format, $toggle, $time, $id ) {
	$enabled = mc_get_option( 'views' );
	foreach ( $enabled as $key => $type ) {
		if ( 'mini' === $type ) {
			unset( $enabled[ $key ] );
		}
	}
	// If there is only one format enabled, don't show format toggle.
	if ( count( $enabled ) < 2 ) {
		return '';
	}
	if ( 'mini' !== $format && 'yes' === $toggle ) {
		if ( '1' !== mc_get_option( 'ajax_javascript' ) ) {
			$is_grid = ( 'calendar' === $format ) ? ' aria-pressed="true"' : '';
			$is_list = ( 'list' === $format ) ? ' aria-pressed="true"' : '';
			$is_card = ( 'card' === $format ) ? ' aria-pressed="true"' : '';
		} else {
			$is_grid = ( 'calendar' === $format ) ? ' aria-current="true"' : '';
			$is_list = ( 'list' === $format ) ? ' aria-current="true"' : '';
			$is_card = ( 'card' === $format ) ? ' aria-current="true"' : '';
		}
		$grid_active = ( 'calendar' === $format ) ? ' mc-active' : '';
		$list_active = ( 'list' === $format ) ? ' mc-active' : '';
		$card_active = ( 'card' === $format ) ? ' mc-active' : '';

		$toggle = "<div class='mc-format'>
		<ul>";

		if ( in_array( 'calendar', $enabled, true ) ) {
			$url     = mc_build_url( array( 'format' => 'calendar' ), array() );
			$url     = mc_url_in_loop( $url );
			$toggle .= "<li><a id='mc_grid-$id' href='$url'" . $is_grid . " class='mc-grid-option$grid_active'>" . __( '<span class="maybe-hide">View as </span>Grid', 'my-calendar' ) . '</a></li>';
		}
		if ( in_array( 'card', $enabled, true ) ) {
			$url     = mc_build_url( array( 'format' => 'card' ), array() );
			$url     = mc_url_in_loop( $url );
			$toggle .= "<li><a id='mc_card-$id' href='$url'" . $is_card . " class='mc-card-option$card_active'>" . __( '<span class="maybe-hide">View as </span>Cards', 'my-calendar' ) . '</a></li>';
		}
		if ( in_array( 'list', $enabled, true ) ) {
			$url     = mc_build_url( array( 'format' => 'list' ), array() );
			$url     = mc_url_in_loop( $url );
			$toggle .= "<li><a id='mc_list-$id' href='$url'" . $is_list . "  class='mc-list-option$list_active'>" . __( '<span class="maybe-hide">View as </span>List', 'my-calendar' ) . '</a></li>';
		}
		$toggle .= '</ul>
		</div>';
	} else {
		$toggle = '';
	}

	if ( 'day' === $time ) {
		$toggle = "<div class='mc-format'><span class='mc-active list'>" . __( '<span class="maybe-hide">View as </span>List', 'my-calendar' ) . '</span></div>';
	}

	if ( ( 'true' === mc_get_option( 'convert' ) || 'mini' === mc_get_option( 'convert' ) ) && mc_is_mobile() ) {
		$toggle = '';
	}

	/**
	 * Filter the HTML for the list/grid/card format switcher in navigation elements.
	 *
	 * @hook mc_format_toggle_html
	 *
	 * @param {string} $toggle HTML output for control.
	 * @param {string} $format Current view format.
	 * @param {string} $time Current time frame.
	 *
	 * @return {string}
	 */
	return apply_filters( 'mc_format_toggle_html', $toggle, $format, $time );
}

/**
 * Generate toggle for time views between day month & week
 *
 * @param string     $format of current view.
 * @param string     $time timespan of current view.
 * @param string|int $month Numeric value ofcurrent month.
 * @param string|int $year current year.
 * @param string     $current Current date.
 * @param int        $start_of_week Day week starts on.
 * @param string     $from Date started from.
 * @param string     $id Current view ID.
 *
 * @return string HTML output
 */
function mc_time_toggle( $format, $time, $month, $year, $current, $start_of_week, $from, $id ) {
	// if dy parameter not set, use today's date instead of first day of month.
	$aria      = ( '1' !== mc_get_option( 'ajax_javascript' ) ) ? 'pressed' : 'current';
	$month     = (int) $month;
	$year      = (int) $year;
	$weeks_day = mc_first_day_of_week( $current );
	$adjusted  = false;
	if ( isset( $_GET['dy'] ) ) {
		if ( '' === $_GET['dy'] ) {
			$current_day = $weeks_day[0];
			if ( -1 === (int) $weeks_day[1] ) {
				$adjusted = true;
				$month    = $month - 1;
			}
		} else {
			$current_day = absint( $_GET['dy'] );
		}
		$current_set = mktime( 0, 0, 0, $month, $current_day, $year );
		if ( (int) mc_date( 'N', $current_set, false ) === $start_of_week ) {
			$weeks_day = mc_first_day_of_week( $current_set );
		}
	} else {
		$weeks_day = mc_first_day_of_week();
	}
	$day = $weeks_day[0];
	if ( isset( $_GET['time'] ) && 'day' === $_GET['time'] ) {
		// don't adjust day if viewing day format.
	} else {
		// if the current date is displayed and the week beginning day is greater than 20 in the month.
		if ( ! isset( $_GET['dy'] ) && $day > 20 ) {
			$day = mc_date( 'j', strtotime( "$from + 1 week" ), false );
		}
	}
	$adjust = ( isset( $weeks_day[1] ) ) ? $weeks_day[1] : 0;
	$toggle = '';

	if ( 'mini' !== $format ) {
		$toggle = "<div class='mc-time'><ul>";
		if ( -1 === (int) $adjust && ! $adjusted ) {
			$wmonth = ( 1 !== (int) $month ) ? $month - 1 : 12;
		} else {
			$wmonth = $month;
		}
		$month_url = mc_build_url( array( 'time' => 'month' ), array( 'mc_id' ) );
		$week_url  = mc_build_url(
			array(
				'time'  => 'week',
				'dy'    => $day,
				'month' => $wmonth,
				'yr'    => $year,
			),
			array( 'dy', 'month', 'mc_id' )
		);
		$day_url   = mc_build_url(
			array(
				'time' => 'day',
				'dy'   => $day,
			),
			array( 'dy', 'mc_id' )
		);

		$month_active = ( 'month' === $time ) ? ' mc-active' : '';
		$week_active  = ( 'week' === $time ) ? ' mc-active' : '';
		$day_active   = ( 'day' === $time ) ? ' mc-active' : '';
		$aria_month   = ( 'month' === $time ) ? " aria-$aria='true'" : '';
		$aria_week    = ( 'week' === $time ) ? " aria-$aria='true'" : '';
		$aria_day     = ( 'day' === $time ) ? " aria-$aria='true'" : '';

		$toggle .= "<li><a id='mc_month-$id'  href='" . mc_url_in_loop( $month_url ) . "' class='month$month_active'$aria_month>" . __( 'Month', 'my-calendar' ) . '</a></li>';
		$toggle .= "<li><a id='mc_week-$id'  href='" . mc_url_in_loop( $week_url ) . "' class='week$week_active'$aria_week>" . __( 'Week', 'my-calendar' ) . '</a></li>';
		$toggle .= "<li><a id='mc_day-$id'  href='" . mc_url_in_loop( $day_url ) . "' class='day$day_active'$aria_day>" . __( 'Day', 'my-calendar' ) . '</a><li>';
		$toggle .= '</ul></div>';
	} else {
		$toggle = '';
	}

	/**
	 * Filter the HTML for the time format switcher in navigation elements.
	 *
	 * @hook mc_time_toggle_html
	 *
	 * @param {string} $toggle HTML output for control.
	 * @param {string} $format Current view format.
	 * @param {string} $time Current time frame.
	 *
	 * @return {string}
	 */
	return apply_filters( 'mc_time_toggle_html', $toggle, $format, $time );
}
