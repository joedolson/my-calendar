<?php
/**
 * Shortcodes.
 *
 * @category Calendar
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Primary My Calendar shortcode.
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string Calendar.
 */
function my_calendar_insert( $atts, $content = null ) {
	$args = shortcode_atts(
		array(
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
			'months'   => false,
			'search'   => '',
			'self'     => '',
		),
		$atts,
		'my_calendar'
	);

	if ( (int) get_the_ID() === (int) get_option( 'mc_uri_id' ) ) {
		$params = get_post_meta( get_the_ID(), '_mc_calendar', true );
		$params = ( is_array( $params ) ) ? $params : array();
		$args   = array_merge( $args, $params );
	}

	if ( 'mini' !== $args['format'] ) {
		if ( isset( $_GET['format'] ) ) {
			$args['format'] = $_GET['format'];
		}
	}

	if ( isset( $_GET['search'] ) ) {
		$args['search'] = $_GET['search'];
	}

	global $user_ID;
	if ( 'current' === $args['author'] ) {
		$args['author'] = apply_filters( 'mc_display_author', $user_ID, 'main' );
	}
	if ( 'current' === $args['host'] ) {
		$args['host'] = apply_filters( 'mc_display_host', $user_ID, 'main' );
	}

	return my_calendar( $args );
}

/**
 * Upcoming Events My Calendar shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string Calendar.
 */
function my_calendar_insert_upcoming( $atts ) {
	$args = shortcode_atts(
		array(
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
			'site'       => false,
		),
		$atts,
		'my_calendar_upcoming'
	);

	global $user_ID;
	if ( 'current' === $args['author'] ) {
		$args['author'] = apply_filters( 'mc_display_author', $user_ID, 'upcoming' );
	}
	if ( 'current' === $args['host'] ) {
		$args['host'] = apply_filters( 'mc_display_host', $user_ID, 'upcoming' );
	}

	return my_calendar_upcoming_events( $args );
}

/**
 * Today's Events My Calendar shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string Calendar.
 */
function my_calendar_insert_today( $atts ) {
	$args = shortcode_atts(
		array(
			'category' => 'default',
			'author'   => 'default',
			'host'     => 'default',
			'template' => 'default',
			'fallback' => '',
			'date'     => false,
			'site'     => false,
		),
		$atts,
		'my_calendar_today'
	);

	global $user_ID;
	if ( 'current' === $args['author'] ) {
		$args['author'] = apply_filters( 'mc_display_author', $user_ID, 'today' );
	}
	if ( 'current' === $args['host'] ) {
		$args['host'] = apply_filters( 'mc_display_host', $user_ID, 'today' );
	}

	return my_calendar_todays_events( $args );
}

/**
 * Locations List My Calendar shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string locations.
 */
function my_calendar_show_locations_list( $atts ) {
	$args = shortcode_atts(
		array(
			'datatype' => 'name',
			'template' => '',
		),
		$atts,
		'my_calendar_locations_list'
	);

	return my_calendar_show_locations( $args['datatype'], $args['template'] );
}

/**
 * Location Filter My Calendar shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string location filter.
 */
function my_calendar_locations( $atts ) {
	$args = shortcode_atts(
		array(
			'show'       => 'list',
			'datatype'   => 'name',
			'target_url' => '',
		),
		$atts,
		'my_calendar_locations'
	);

	return my_calendar_locations_list( $args['show'], $args['datatype'], 'single', $args['target_url'] );
}

/**
 * Category filter My Calendar shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string category filter.
 */
function my_calendar_categories( $atts ) {
	$args = shortcode_atts(
		array(
			'show'       => 'list',
			'target_url' => '',
		),
		$atts,
		'my_calendar_categories'
	);

	return my_calendar_categories_list( $args['show'], 'public', 'single', $args['target_url'] );
}

/**
 * Accessibility Filter My Calendar shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string accessibility filters.
 */
function my_calendar_access( $atts ) {
	$args = shortcode_atts(
		array(
			'show'       => 'list',
			'target_url' => '',
		),
		$atts,
		'my_calendar_access'
	);

	return mc_access_list( $args['show'], 'single', $args['target_url'] );
}

/**
 * All Filters My Calendar shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string filters.
 */
function my_calendar_filters( $atts ) {
	$args = shortcode_atts(
		array(
			'show'       => 'categories,locations',
			'target_url' => '',
			'ltype'      => 'name',
		),
		$atts,
		'my_calendar_filters'
	);

	return mc_filters( $args['show'], $args['target_url'], $args['ltype'] );
}

/**
 * Single Event My Calendar shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string event.
 */
function my_calendar_show_event( $atts ) {
	$args = shortcode_atts(
		array(
			'event'    => '',
			'template' => '<h3>{title}</h3>{description}',
			'list'     => '<li>{date}, {time}</li>',
			'before'   => '<ul>',
			'after'    => '</ul>',
			'instance' => false,
		),
		$atts,
		'my_calendar_event'
	);

	return mc_instance_list( $args );
}

/**
 * Search Form My Calendar shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string search form.
 */
function my_calendar_search( $atts ) {
	$args = shortcode_atts(
		array(
			'type' => 'simple',
			'url'  => '',
		),
		$atts,
		'my_calendar_search'
	);

	return my_calendar_searchform( $args['type'], $args['url'] );
}

/**
 * Current Event My Calendar shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string event.
 */
function my_calendar_now( $atts ) {
	$args = shortcode_atts(
		array(
			'category' => '',
			'template' => '<strong>{link_title}</strong> {timerange}',
			'site'     => false,
		),
		$atts,
		'my_calendar_now'
	);

	return my_calendar_events_now( $args['category'], $args['template'], $args['site'] );
}

/**
 * Next Event My Calendar shortcode.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string event.
 */
function my_calendar_next( $atts ) {
	$args = shortcode_atts(
		array(
			'category' => '',
			'template' => '<strong>{link_title}</strong> {timerange}',
			'skip'     => 0,
			'site'     => false,
		),
		$atts,
		'my_calendar_next'
	);

	return my_calendar_events_next( $args['category'], $args['template'], $args['skip'], $args['site'] );
}


/**
 * Configure calendar view for primary calendar.
 */
function mc_calendar_view() {
	$calendar_id = get_option( 'mc_uri_id' );
	if ( isset( $_GET['post'] ) && $calendar_id === $_GET['post'] ) {
		add_meta_box( 'mc-calendar-view', __( 'My Calendar Display Options', 'my-calendar' ), 'mc_calendar_generator_fields', 'page', 'advanced', 'high', 'main' );
	}
}
add_action( 'add_meta_boxes', 'mc_calendar_view' );

/**
 * Settings to configure My Calendar view, overriding shortcode.
 *
 * @param object       $post Post object.
 * @param array|string $callback_args Post callback args or selected type.
 */
function mc_calendar_generator_fields( $post, $callback_args ) {
	$params = array();
	$script = '';
	if ( $post ) {
		$params = get_post_meta( $post->ID, '_mc_calendar', true );
	}
	$type       = ( is_array( $callback_args ) ) ? $callback_args['args'] : $callback_args;
	$category   = ( isset( $params['category'] ) ) ? $params['category'] : null;
	$ltype      = ( isset( $params['ltype'] ) ) ? $params['ltype'] : '';
	$lvalue     = ( isset( $params['lvalue'] ) ) ? $params['lvalue'] : '';
	$disabled   = ( '' === $lvalue ) ? '' : 'disabled';
	$search     = ( isset( $params['search'] ) ) ? $params['search'] : '';
	$show_hosts = ( isset( $params['host'] ) ) ? explode( ',', $params['host'] ) : array();
	$show_users = ( isset( $params['author'] ) ) ? explode( ',', $params['author'] ) : array();
	$format     = ( isset( $params['format'] ) ) ? $params['format'] : '';
	$time       = ( isset( $params['time'] ) ) ? $params['time'] : '';
	$year       = ( isset( $params['year'] ) ) ? $params['year'] : '';
	$month      = ( isset( $params['month'] ) ) ? $params['month'] : '';
	$day        = ( isset( $params['day'] ) ) ? $params['day'] : '';
	$months     = ( isset( $params['months'] ) ) ? $params['months'] : '';
	$above      = ( isset( $params['above'] ) ) ? $params['above'] : '';
	$below      = ( isset( $params['below'] ) ) ? $params['below'] : '';
	$shortcode  = ( isset( $params['shortcode'] ) ) ? $params['shortcode'] : '';

	?>
	<div id="mc-generator" class="generator">
		<div><input type="hidden" name="_mc_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-generator' ); ?>"/></div>
		<input type='hidden' name='shortcode' value='<?php echo esc_attr( $type ); ?>'/>
		<?php
		// Common Elements to all Shortcodes.
		if ( $shortcode ) {
			echo '<p><label for="mc_shortcode">Shortcode</label><textarea readonly class="large-text readonly" id="mc_shortcode">' . $shortcode . '</textarea></p>';
		}
		?>
		<div class="mc-generator-inputs">
		<fieldset>
			<legend><?php _e( 'Content Filters', 'my-calendar' ); ?></legend>
			<?php
			$all_checked = '';
			if ( empty( $category ) ) {
				$all_checked = ' checked="checked"';
			}
			?>
			<fieldset>
				<legend><?php _e( 'Categories to display:', 'my-calendar' ); ?></legend>
				<ul style="padding:0;margin:0;list-style-type:none;columns:3;">
					<li>
						<input type="checkbox" value="all" <?php echo $all_checked; ?> name="category[]" id="category"> <label for="category"><?php _e( 'All', 'my-calendar' ); ?></label>
					</li>
					<?php
					$select = mc_category_select( $category, true, true, 'category[]', 'category' );
					echo $select;
					?>
				</ul>
			</fieldset>
			<p>
				<label for="ltype"><?php _e( 'Location filter type:', 'my-calendar' ); ?></label>
				<select name="ltype" id="ltype">
					<option value=''><?php _e( 'All locations', 'my-calendar' ); ?></option>
					<option value='event_label'<?php selected( $ltype, 'event_label' ); ?>><?php _e( 'Location Name', 'my-calendar' ); ?></option>
					<option value='event_city'<?php selected( $ltype, 'event_city' ); ?>><?php _e( 'City', 'my-calendar' ); ?></option>
					<option value='event_state'<?php selected( $ltype, 'event_state' ); ?>><?php _e( 'State', 'my-calendar' ); ?></option>
					<option value='event_postcode'<?php selected( $ltype, 'event_postcode' ); ?>><?php _e( 'Postal Code', 'my-calendar' ); ?></option>
					<option value='event_country'<?php selected( $ltype, 'event_country' ); ?>><?php _e( 'Country', 'my-calendar' ); ?></option>
					<option value='event_region'<?php selected( $ltype, 'event_region' ); ?>><?php _e( 'Region', 'my-calendar' ); ?></option>
				</select>
			</p>
			<p>
				<label for="lvalue" id='lval'><?php _e( 'Location filter values (comma-separated)', 'my-calendar' ); ?></label>
				<input type="text" name="lvalue" id="lvalue" value="<?php echo esc_attr( $lvalue ); ?>" aria-labelledby='lval location-info' <?php echo $disabled; ?> />
			</p>

			<p id='location-info'>
				<?php _e( '<strong>Note:</strong> If you provide a location filter value, it must be an exact match for that information as saved with your events. (e.g. "Saint Paul" is not equivalent to "saint paul" or "St. Paul")', 'my-calendar' ); ?>
			</p>
			<p>
				<label for="search" id='sterm'><?php _e( 'Search keyword', 'my-calendar' ); ?></label>
				<input type="text" name="search" id="sterm" value="<?php echo esc_attr( $search ); ?>" /><br/>
			</p>
		</fieldset>
		<fieldset>
				<legend><?php _e( 'Navigation', 'my-calendar' ); ?></legend>
				<p id='navigation-info'>
					<?php
					// Translators: Settings page URL.
					printf( __( "Navigation above and below the calendar: your <a href='%s'>settings</a> if this is left blank. Use <code>none</code> to hide all navigation.", 'my-calendar' ), admin_url( 'admin.php?page=my-calendar-config#mc-output' ) );
					echo mc_help_link( 'Help', __( 'My Calendar: Navigation Keywords', 'my-calendar' ), 3 );
					?>
				</p>
				<p>
					<label for="above" id='labove'><?php _e( 'Navigation above calendar', 'my-calendar' ); ?></label>
					<input type="text" name="above" id="above" placeholder="nav,toggle,jump,print,timeframe" aria-labelledby='labove navigation-info' value="<?php echo esc_attr( $above ); ?>" /><br/>
				</p>
				<p>
					<label for="below" id='lbelow'><?php _e( 'Navigation below calendar', 'my-calendar' ); ?></label>
					<input type="text" name="below" id="below" placeholder="key,feeds" aria-labelledby='lbelow navigation-info' value="<?php echo esc_attr( $below ); ?>" /><br/>
				</p>
		</fieldset>
		<fieldset>
			<legend><?php _e( 'Formatting & Timeframe', 'my-calendar' ); ?></legend>
			<?php
			// Main shortcode only.
			if ( 'main' === $type ) {
				?>
				<p>
					<label for="format"><?php _e( 'Format', 'my-calendar' ); ?></label>
					<select name="format" id="format">
						<option value=""><?php _e( 'Default', 'my-calendar' ); ?></option>
						<option value="calendar"<?php selected( 'calendar', $format ); ?>><?php _e( 'Grid', 'my-calendar' ); ?></option>
						<option value='list'<?php selected( 'list', $format ); ?>><?php _e( 'List', 'my-calendar' ); ?></option>
						<option value="mini"<?php selected( 'mini', $format ); ?>><?php _e( 'Mini', 'my-calendar' ); ?></option>
					</select>
				</p>
				<p>
					<label for="time"><?php _e( 'Time Segment', 'my-calendar' ); ?></label>
					<select name="time" id="time">
						<option value=""><?php _e( 'Default', 'my-calendar' ); ?></option>
						<option value="month"<?php selected( 'month', $time ); ?>><?php _e( 'Month', 'my-calendar' ); ?></option>
						<option value="month+1"<?php selected( 'month+1', $time ); ?>><?php _e( 'Next Month', 'my-calendar' ); ?></option>
						<option value="week"<?php selected( 'week', $time ); ?>><?php _e( 'Week', 'my-calendar' ); ?></option>
						<option value="day"<?php selected( 'day', $time ); ?>><?php _e( 'Day', 'my-calendar' ); ?></option>
					</select>
				</p>
				<p>
					<label for="months" id='lmonths'><?php _e( 'Months to show in list view', 'my-calendar' ); ?></label>
					<input type="number" min="1" max="12" step="1" name="months" id="lmonths" value="<?php echo esc_attr( $months ); ?>" /><br/>
				</p>
			</fieldset>
			<fieldset>
				<legend><?php _e( 'Author & Host Filters', 'my-calendar' ); ?></legend>
				<?php
				// Grab authors and list them.
				$users   = mc_get_users( 'authors' );
				$options = '';
				foreach ( $users as $u ) {
					$selected = '';
					if ( in_array( $u->ID, $show_users, true ) ) {
						$selected = ' selected="selected"';
					}
					$options .= '<option value="' . $u->ID . '" ' . $selected . '>' . esc_html( $u->display_name ) . "</option>\n";
				}
				?>
				<p>
					<label for="author"><?php _e( 'Limit by Author', 'my-calendar' ); ?></label>
					<select name="author[]" id="author" multiple="multiple">
						<option value="all"><?php _e( 'All authors', 'my-calendar' ); ?></option>
						<option value="current"><?php _e( 'Currently logged-in user', 'my-calendar' ); ?></option>
						<?php echo $options; ?>
					</select>
				</p>
				<?php
				// Grab authors and list them.
				$users   = mc_get_users( 'hosts' );
				$options = '';
				foreach ( $users as $u ) {
					$selected = '';
					if ( in_array( $u->ID, $show_hosts, true ) ) {
						$selected = ' selected="selected"';
					}
					$options .= '<option value="' . $u->ID . '" ' . $selected . '>' . esc_html( $u->display_name ) . "</option>\n";
				}
				?>
				<p>
					<label for="host"><?php _e( 'Limit by Host', 'my-calendar' ); ?></label>
					<select name="host[]" id="host" multiple="multiple">
						<option value="all"><?php _e( 'All hosts', 'my-calendar' ); ?></option>
						<option value="current"><?php _e( 'Currently logged-in user', 'my-calendar' ); ?></option>
						<?php echo $options; ?>
					</select>
				</p>
			</fieldset>
			<fieldset>
				<legend><?php _e( 'Initial Date Shown', 'my-calendar' ); ?></legend>
				<p>
					<label for="year"><?php _e( 'Year', 'my-calendar' ); ?></label>
					<select name="year" id="year">
						<option value=''><?php _e( 'Default', 'my-calendar' ); ?></option>
						<?php
						global $wpdb;
						$mcdb = $wpdb;
						if ( 'true' === get_option( 'mc_remote' ) && function_exists( 'mc_remote_db' ) ) {
							$mcdb = mc_remote_db();
						}
						$query  = 'SELECT event_begin FROM ' . my_calendar_table() . ' WHERE event_approved = 1 AND event_flagged <> 1 ORDER BY event_begin ASC LIMIT 0 , 1';
						$year1  = mc_date( 'Y', strtotime( $mcdb->get_var( $query ) ) );
						$diff1  = mc_date( 'Y' ) - $year1;
						$past   = $diff1;
						$future = apply_filters( 'mc_jumpbox_future_years', 5, false );
						$fut    = 1;
						$f      = '';
						$p      = '';
						while ( $past > 0 ) {
							$p   .= '<option value="';
							$p   .= current_time( 'Y' ) - $past;
							$p   .= '">';
							$p   .= current_time( 'Y' ) - $past . "</option>\n";
							$past = $past - 1;
						}
						while ( $fut < $future ) {
							$f  .= '<option value="';
							$f  .= current_time( 'Y' ) + $fut;
							$f  .= '">';
							$f  .= current_time( 'Y' ) + $fut . "</option>\n";
							$fut = $fut + 1;
						}
						echo $p . '<option value="' . current_time( 'Y' ) . '"' . selected( current_time( 'Y' ), $year ) . '>' . current_time( 'Y' ) . "</option>\n" . $f;
						?>
					</select>
				</p>
				<p>
					<label for="month"><?php _e( 'Month', 'my-calendar' ); ?></label>
					<select name="month" id="month">
						<option value=''><?php _e( 'Default', 'my-calendar' ); ?></option>
						<?php
						$list_months = '';
						for ( $i = 1; $i <= 12; $i ++ ) {
							$list_months .= "<option value='$i'" . selected( $i, $month ) . '>' . date_i18n( 'F', mktime( 0, 0, 0, $i, 1 ) ) . '</option>' . "\n";
						}
						echo $list_months;
						?>
					</select>
				</p>
				<p>
					<label for="day"><?php _e( 'Day', 'my-calendar' ); ?></label>
					<select name="day" id="day">
						<option value=''><?php _e( 'Default', 'my-calendar' ); ?></option>
						<?php
						$days = '';
						for ( $i = 1; $i <= 31; $i++ ) {
							$days .= "<option value='$i'" . selected( $i, $day ) . '>' . $i . '</option>' . "\n";
						}
						echo $days;
						?>
					</select>
				</p>
			</fieldset>
				<?php
			}
			if ( 'upcoming' === $type || 'today' === $type ) {
				// Upcoming Events & Today's Events shortcodes.
				?>
				<p>
					<label for="fallback"><?php _e( 'Fallback Text', 'my-calendar' ); ?></label>
					<input type="text" name="fallback" id="fallback" value="" />
				</p>
				<p>
					<label for="template"><?php _e( 'Template', 'my-calendar' ); ?></label>
					<textarea cols="40" rows="4" name="template" id="template"><?php echo htmlentities( '<strong>{date}</strong>, {time}: {link_title}' ); ?></textarea>
				</p>
				<?php
			}
			if ( 'upcoming' === $type ) {
				// Upcoming events only.
				?>
				<p>
					<label for="before"><?php _e( 'Events/Days Before Current Day', 'my-calendar' ); ?></label>
					<input type="number" name="before" id="before" value="" />
				</p>
				<p>
					<label for="after"><?php _e( 'Events/Days After Current Day', 'my-calendar' ); ?></label>
					<input type="number" name="after" id="after" value="" />
				</p>
				<p>
					<label for="skip"><?php _e( 'Events/Days to Skip', 'my-calendar' ); ?></label>
					<input type="number" name="skip" id="skip" value="" />
				</p>
				<p>
					<label for="show_today"><?php _e( "Show Today's Events", 'my-calendar' ); ?></label>
					<input type="checkbox" name="show_today" id="show_today" value="yes"/>
				</p>
				<p>
					<label for="type"><?php _e( 'Type of Upcoming Events List', 'my-calendar' ); ?></label>
					<select name="type" id="type">
						<option value="event" selected="selected"><?php _e( 'Events', 'my-calendar' ); ?></option>
						<option value="year"><?php _e( 'Current Year', 'my-calendar' ); ?></option>
						<option value="days"><?php _e( 'Days', 'my-calendar' ); ?></option>
						<option value="custom"><?php _e( 'Custom Dates', 'my-calendar' ); ?></option>
						<option value="month"><?php _e( 'Current Month', 'my-calendar' ); ?></option>
						<option value="month+1"><?php _e( 'Next Month', 'my-calendar' ); ?></option>
						<option value="month+2"><?php _e( '2nd Month Out', 'my-calendar' ); ?></option>
						<option value="month+3"><?php _e( '3rd Month Out', 'my-calendar' ); ?></option>
						<option value="month+4"><?php _e( '4th Month Out', 'my-calendar' ); ?></option>
						<option value="month+5"><?php _e( '5th Month Out', 'my-calendar' ); ?></option>
						<option value="month+6"><?php _e( '6th Month Out', 'my-calendar' ); ?></option>
						<option value="month+7"><?php _e( '7th Month Out', 'my-calendar' ); ?></option>
						<option value="month+8"><?php _e( '8th Month Out', 'my-calendar' ); ?></option>
						<option value="month+9"><?php _e( '9th Month Out', 'my-calendar' ); ?></option>
						<option value="month+10"><?php _e( '10th Month Out', 'my-calendar' ); ?></option>
						<option value="month+11"><?php _e( '11th Month Out', 'my-calendar' ); ?></option>
						<option value="month+12"><?php _e( '12th Month Out', 'my-calendar' ); ?></option>
					</select>
				</p>
				<div class='custom'>
					<p>
						<label for='from'><?php _e( 'Starting Date', 'my-calendar' ); ?></label> <input type='text' name='from' id='from' />
					</p>
					<p>
						<label for='to'><?php _e( 'End Date', 'my-calendar' ); ?></label> <input type='text' name='to' id='to' />
					</p>
				</div>
				<p>
					<label for="order"><?php _e( 'Event Order', 'my-calendar' ); ?></label>
					<select name="order" id="order">
						<option value="asc" selected="selected"><?php _e( 'Ascending', 'my-calendar' ); ?></option>
						<option value="desc"><?php _e( 'Descending', 'my-calendar' ); ?></option>
					</select>
				</p>
				<?php
			}
			?>
		</div>
	</div>
		<?php
}

/**
 * Save My Calendar custom configuration data.
 *
 * @param int $post_id Post ID.
 */
function mc_update_calendar( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $post_id ) || ! ( (int) get_option( 'mc_uri_id' ) === (int) $post_id ) ) {
		return $post_id;
	}
	$options = mc_generate( 'array' );
	update_post_meta( $post_id, '_mc_calendar', $options );
}
add_action( 'save_post', 'mc_update_calendar', 10, 1 );
