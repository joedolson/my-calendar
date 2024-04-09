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
			'name'           => 'all',
			'format'         => 'calendar',
			'category'       => 'all',
			'time'           => 'month',
			'ltype'          => '',
			'lvalue'         => '',
			'author'         => 'all',
			'host'           => 'all',
			'id'             => '',
			'template'       => '',
			'above'          => '',
			'below'          => '',
			'year'           => '',
			'month'          => '',
			'day'            => '',
			'site'           => '',
			'months'         => '',
			'search'         => '',
			'self'           => '',
			'language'       => '',
			'weekends'       => mc_get_option( 'show_weekends' ),
			'hide_groups'    => '', // Hide grouped events after first.
			'hide_recurring' => 'card', // Hide recurring events after first. Comma-separated list of formats.
		),
		$atts,
		'my_calendar'
	);
	$args = map_deep( $args, 'sanitize_text_field' );

	if ( (int) get_the_ID() === (int) mc_get_option( 'uri_id' ) ) {
		$params = get_post_meta( get_the_ID(), '_mc_calendar', true );
		$params = ( is_array( $params ) ) ? $params : array();
		$args   = array_merge( $args, $params );
	}

	if ( 'mini' !== $args['format'] ) {
		if ( isset( $_GET['format'] ) ) {
			$args['format'] = sanitize_text_field( $_GET['format'] );
		}
	}

	if ( isset( $_GET['search'] ) ) {
		$args['search'] = sanitize_text_field( $_GET['search'] );
	}

	global $user_ID;
	if ( 'current' === $args['author'] ) {
		/**
		 * Filter the author parameter for a My Calendar view if set as 'current'. Default current user ID.
		 *
		 * @hook mc_display_author
		 *
		 * @param {int} $user_ID Logged-in user ID.
		 * @param {string} $context 'main' to indicate the `my_calendar` shortcode is running.
		 *
		 * @return {int} Valid author ID.
		 */
		$args['author'] = apply_filters( 'mc_display_author', $user_ID, 'main' );
	}
	if ( 'current' === $args['host'] ) {
		/**
		 * Filter the host parameter for a My Calendar view if set as 'current'. Default current user ID.
		 *
		 * @hook mc_display_host
		 *
		 * @param {int} $user_ID Logged-in user ID.
		 * @param {string} $context 'main' to indicate the `my_calendar` shortcode is running.
		 *
		 * @return {int} Valid author ID.
		 */
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
			'before'         => 'default',
			'after'          => 'default',
			'type'           => 'default',
			'category'       => 'default',
			'template'       => 'default',
			'fallback'       => '',
			'order'          => 'asc',
			'skip'           => '0',
			'show_recurring' => 'yes',
			'author'         => 'default',
			'host'           => 'default',
			'ltype'          => '',
			'lvalue'         => '',
			'from'           => false,
			'to'             => false,
			'site'           => false,
			'language'       => '',
		),
		$atts,
		'my_calendar_upcoming'
	);

	global $user_ID;
	if ( 'current' === $args['author'] ) {
		/**
		 * Filter the author parameter for a My Calendar view if set as 'current'. Default current user ID.
		 *
		 * @hook mc_display_author
		 *
		 * @param {int} $user_ID Logged-in user ID.
		 * @param {string} $context 'upcoming' to indicate the `my_calendar_upcoming` shortcode is running.
		 *
		 * @return {int} Valid author ID.
		 */
		$args['author'] = apply_filters( 'mc_display_author', $user_ID, 'upcoming' );
	}
	if ( 'current' === $args['host'] ) {
		/**
		 * Filter the host parameter for a My Calendar view if set as 'current'. Default current user ID.
		 *
		 * @hook mc_display_host
		 *
		 * @param {int} $user_ID Logged-in user ID.
		 * @param {string} $context 'upcoming' to indicate the `my_calendar_upcoming` shortcode is running.
		 *
		 * @return {int} Valid author ID.
		 */
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
			'language' => '',
		),
		$atts,
		'my_calendar_today'
	);

	global $user_ID;
	if ( 'current' === $args['author'] ) {
		/**
		 * Filter the author parameter for a My Calendar view if set as 'current'. Default current user ID.
		 *
		 * @hook mc_display_author
		 *
		 * @param {int} $user_ID Logged-in user ID.
		 * @param {string} $context 'today' to indicate the `my_calendar_today` shortcode is running.
		 *
		 * @return {int} Valid author ID.
		 */
		$args['author'] = apply_filters( 'mc_display_author', $user_ID, 'today' );
	}
	if ( 'current' === $args['host'] ) {
		/**
		 * Filter the host parameter for a My Calendar view if set as 'current'. Default current user ID.
		 *
		 * @hook mc_display_host
		 *
		 * @param {int} $user_ID Logged-in user ID.
		 * @param {string} $context 'today' to indicate the `my_calendar_today` shortcode is running.
		 *
		 * @return {int} Valid author ID.
		 */
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
			'sort'     => '',
			'template' => '',
		),
		$atts,
		'my_calendar_locations_list'
	);
	// Sort replaces 'datatype'.
	$sort = ( '' !== $args['sort'] ) ? $args['sort'] : $args['datatype'];

	return my_calendar_show_locations( $sort, $args['template'] );
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

	return my_calendar_searchform( $args['type'], $args['url'], 'shortcode' );
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
	$calendar_id = mc_get_option( 'uri_id' );
	if ( isset( $_GET['post'] ) && (int) $calendar_id === (int) $_GET['post'] ) {
		add_meta_box( 'mc-calendar-view', __( 'My Calendar Display Options', 'my-calendar' ), 'mc_calendar_generator_fields', 'page', 'advanced', 'high', 'main' );
	}
}
add_action( 'add_meta_boxes', 'mc_calendar_view' );

/**
 * Settings to configure My Calendar view or build shortcode.
 *
 * @param object|false $post WP_Post object or false if no data.
 * @param array|string $callback_args Post callback args or selected type.
 */
function mc_calendar_generator_fields( $post, $callback_args ) {
	$params = array();
	if ( $post && is_object( $post ) ) {
		$params = get_post_meta( $post->ID, '_mc_calendar', true );
	}
	if ( $post && is_array( $post ) ) {
		$params = $post;
	}
	$type    = ( is_array( $callback_args ) ) ? $callback_args['args'] : $callback_args;
	$message = '';
	$base    = '';
	switch ( $type ) {
		case 'main':
			$base     = 'my_calendar';
			$post     = mc_get_option( 'mc_uri_id' );
			$edit_url = add_query_arg(
				array(
					'post'   => $post,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			);
			// Translators: URL to edit your primary calendar settings.
			$message = sprintf( __( 'Generate the <code>[my_calendar]</code> shortcode. Generates the main grid, list, and mini calendar views. <a href="%s">Configure your primary view.</a>', 'my-calendar' ), $edit_url );
			break;
		case 'upcoming':
			$base    = 'my_calendar_upcoming';
			$message = __( 'Generate the <code>[my_calendar_upcoming]</code> shortcode. Generates lists of upcoming events.', 'my-calendar' );
			break;
		case 'today':
			$base    = 'my_calendar_today';
			$message = __( 'Generate the <code>[my_calendar_today]</code> shortcode. Generates lists of events happening today.', 'my-calendar' );
			break;
	}
	$category   = ( isset( $params['category'] ) ) ? $params['category'] : null;
	$weekends   = ( isset( $params['weekends'] ) ) ? $params['weekends'] : mc_get_option( 'show_weekends' );
	$ltype      = ( isset( $params['ltype'] ) ) ? $params['ltype'] : '';
	$lvalue     = ( isset( $params['lvalue'] ) ) ? $params['lvalue'] : '';
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
	$shortcode  = ( isset( $params['shortcode'] ) ) ? $params['shortcode'] : "[$base]";
	$append     = ( isset( $params['append'] ) ) ? $params['append'] : '';

	$last_shortcode = mc_get_option( 'last_shortcode_' . $type );
	$shortcode      = ( ! isset( $params['shortcode'] ) && $last_shortcode ) ? "[$last_shortcode]" : $shortcode;
	?>
	<div id="mc-generator" class="generator">
		<div class="mc-generator-data">
			<?php echo wp_kses_post( wpautop( $message ) ); ?>
			<div><input type="hidden" name="_mc_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-generator' ); ?>"/></div>
			<input type='hidden' name='shortcode' value='<?php echo esc_attr( $type ); ?>'/>
			<?php
			// Common Elements to all Shortcodes.
			if ( $shortcode ) {
				echo wp_kses_post(
					'<div class="shortcode-preview"><p><label for="mc_shortcode_' . $type . '">Shortcode</label><textarea readonly class="large-text readonly mc-shortcode-container" id="mc_shortcode_' . $type . '">' . $shortcode . '</textarea>' . $append . '</p>
					<div class="mc-copy-button"><button type="button" class="button-primary mc-copy-to-clipboard" data-clipboard-target="#mc_shortcode_' . $type . '">' . __( 'Copy to clipboard', 'my-calendar' ) . '</button><span class="mc-notice-copied">' . __( 'Shortcode Copied', 'my-calendar' ) . '</span></div>
					<p><button data-type="' . $base . '" type="button" class="button button-secondary reset-my-calendar">' . __( 'Reset Shortcode', 'my-calendar' ) . '</button></p></div>'
				);
			}
			?>
		</div>
		<?php
		if ( isset( $_GET['post'] ) ) {
			echo '<div class="editor-save-notice"><p>' . __( 'Save this post to update your My Calendar settings.', 'my-calendar' ) . '</p></div>';
		}
		?>
		<div class="mc-generator-inputs">
			<fieldset>
				<legend><?php esc_html_e( 'Content Filters', 'my-calendar' ); ?></legend>
				<fieldset class="categories">
					<legend><?php esc_html_e( 'Categories', 'my-calendar' ); ?></legend>
					<ul style="padding:0;margin:0;list-style-type:none;columns:3;">
						<li>
							<input type="checkbox" value="all" <?php checked( empty( $category ), true ); ?> name="category[]" id="category_<?php echo esc_attr( $type ); ?>"> <label for="category_<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'All', 'my-calendar' ); ?></label>
						</li>
						<?php
						$categories = ( $category && ! is_array( $category ) ) ? explode( ',', $category ) : $category;
						$select     = mc_category_select( $categories, true, true, 'category[]', 'category_' . $type );
						echo wp_kses( $select, mc_kses_elements() );
						?>
					</ul>
				</fieldset>
				<p>
					<label for="ltype<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Location filter type:', 'my-calendar' ); ?></label>
					<select name="ltype" id="ltype<?php echo esc_attr( $type ); ?>">
						<option value=''><?php esc_html_e( 'All locations', 'my-calendar' ); ?></option>
						<option value='event_label'<?php selected( $ltype, 'event_label' ); ?>><?php esc_html_e( 'Location Name', 'my-calendar' ); ?></option>
						<option value='event_city'<?php selected( $ltype, 'event_city' ); ?>><?php esc_html_e( 'City', 'my-calendar' ); ?></option>
						<option value='event_state'<?php selected( $ltype, 'event_state' ); ?>><?php esc_html_e( 'State', 'my-calendar' ); ?></option>
						<option value='event_postcode'<?php selected( $ltype, 'event_postcode' ); ?>><?php esc_html_e( 'Postal Code', 'my-calendar' ); ?></option>
						<option value='event_country'<?php selected( $ltype, 'event_country' ); ?>><?php esc_html_e( 'Country', 'my-calendar' ); ?></option>
						<option value='event_region'<?php selected( $ltype, 'event_region' ); ?>><?php esc_html_e( 'Region', 'my-calendar' ); ?></option>
					</select>
				</p>
				<p>
					<label id="lval" for="lvalue<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Location values (comma-separated)', 'my-calendar' ); ?></label>
					<input type="text" name="lvalue" id="lvalue<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( $lvalue ); ?>" aria-labelledby='lval location-info' />
				</p>

				<p id='location-info'>
					<?php _e( 'If you filter events by location, it must be an exact match for that information as saved with your events. (e.g. "Saint Paul" is not equivalent to "saint paul" or "St. Paul")', 'my-calendar' ); ?>
				</p>
				<p>
					<label for="search<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Search keyword', 'my-calendar' ); ?></label>
					<input type="text" name="search" id="search<?php echo esc_attr( $type ); ?>" aria-describedby="search-info" value="<?php echo esc_attr( $search ); ?>" /><br/>
				</p>
				<span id='search-info'>
					<?php _e( 'Show events containing a specific search keyword.', 'my-calendar' ); ?>
				</span>
			</fieldset>
			<?php
			// Main shortcode only.
			if ( 'main' === $type ) {
				?>
			<fieldset>
				<legend><?php esc_html_e( 'Navigation', 'my-calendar' ); ?></legend>
				<p id='navigation-info'>
					<?php
					// Translators: Settings page URL.
					printf( __( "Navigation above and below the calendar: your <a href='%s'>settings</a> if this is left blank. Use <code>none</code> to hide all navigation.", 'my-calendar' ), admin_url( 'admin.php?page=my-calendar-config#mc-output' ) );
					mc_help_link( __( 'Help', 'my-calendar' ), __( 'Navigation Keywords', 'my-calendar' ), 'navigation keywords', 3 );
					?>
				</p>
				<p>
					<label for="above<?php echo esc_attr( $type ); ?>" id='labove'><?php esc_html_e( 'Navigation above calendar', 'my-calendar' ); ?></label>
					<input type="text" name="above" id="above<?php echo esc_attr( $type ); ?>" placeholder="nav,toggle,jump,print,timeframe" aria-labelledby='labove navigation-info' value="<?php echo esc_attr( $above ); ?>" /><br/>
				</p>
				<p>
					<label for="below<?php echo esc_attr( $type ); ?>" id='lbelow'><?php esc_html_e( 'Navigation below calendar', 'my-calendar' ); ?></label>
					<input type="text" name="below" id="below<?php echo esc_attr( $type ); ?>" placeholder="key,feeds" aria-labelledby='lbelow navigation-info' value="<?php echo esc_attr( $below ); ?>" /><br/>
				</p>
			</fieldset>
			<fieldset>
				<legend><?php esc_html_e( 'Formatting & Timeframe', 'my-calendar' ); ?></legend>
				<p>
					<label for="format<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Format', 'my-calendar' ); ?></label>
					<select name="format" id="format<?php echo esc_attr( $type ); ?>">
						<option value=""><?php esc_html_e( 'Default', 'my-calendar' ); ?></option>
						<option value="calendar"<?php selected( 'calendar', $format ); ?>><?php esc_html_e( 'Grid', 'my-calendar' ); ?></option>
						<option value='list'<?php selected( 'list', $format ); ?>><?php esc_html_e( 'List', 'my-calendar' ); ?></option>
						<option value='card'<?php selected( 'card', $format ); ?>><?php esc_html_e( 'Card', 'my-calendar' ); ?></option>
						<option value="mini"<?php selected( 'mini', $format ); ?>><?php esc_html_e( 'Mini', 'my-calendar' ); ?></option>
					</select>
				</p>
				<p>
					<label for="time<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Time Segment', 'my-calendar' ); ?></label>
					<select name="time" id="time<?php echo esc_attr( $type ); ?>">
						<option value=""><?php esc_html_e( 'Default', 'my-calendar' ); ?></option>
						<option value="month"<?php selected( 'month', $time ); ?>><?php esc_html_e( 'Month', 'my-calendar' ); ?></option>
						<option value="month+1"<?php selected( 'month+1', $time ); ?>><?php esc_html_e( 'Next Month', 'my-calendar' ); ?></option>
						<option value="week"<?php selected( 'week', $time ); ?>><?php esc_html_e( 'Week', 'my-calendar' ); ?></option>
						<option value="day"<?php selected( 'day', $time ); ?>><?php esc_html_e( 'Day', 'my-calendar' ); ?></option>
					</select>
				</p>
				<p class="checkbox">
					<label for="weekends<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Include weekends', 'my-calendar' ); ?></label>
					<input type="checkbox" value="true" name="weekends" id="weekends<?php echo esc_attr( $type ); ?>" <?php checked( 'true', $weekends ); ?> /><br/>
				</p>
				<p>
					<label for="months<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Months to show', 'my-calendar' ); ?></label>
					<input type="number" min="1" max="12" step="1" name="months" id="months<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( $months ); ?>" /><br/>
				</p>
			</fieldset>
				<?php
			}
			?>
			<fieldset>
				<legend><?php esc_html_e( 'Author & Host Filters', 'my-calendar' ); ?></legend>
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
					<label for="author<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Limit by Author', 'my-calendar' ); ?></label>
					<select name="author[]" id="author<?php echo esc_attr( $type ); ?>" multiple="multiple">
						<option value="all"><?php esc_html_e( 'All authors', 'my-calendar' ); ?></option>
						<option value="current"><?php esc_html_e( 'Currently logged-in user', 'my-calendar' ); ?></option>
						<?php echo wp_kses( $options, mc_kses_elements() ); ?>
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
					<label for="host<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Limit by Host', 'my-calendar' ); ?></label>
					<select name="host[]" id="host<?php echo esc_attr( $type ); ?>" multiple="multiple">
						<option value="all"><?php esc_html_e( 'All hosts', 'my-calendar' ); ?></option>
						<option value="current"><?php esc_html_e( 'Currently logged-in user', 'my-calendar' ); ?></option>
						<?php echo wp_kses( $options, mc_kses_elements() ); ?>
					</select>
				</p>
			</fieldset>
			<?php
			if ( 'main' === $type ) {
				?>
			<fieldset>
				<legend><?php esc_html_e( 'Start Date', 'my-calendar' ); ?></legend>
				<p>
					<label for="year<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Year', 'my-calendar' ); ?></label>
					<select name="year" id="year<?php echo esc_attr( $type ); ?>">
						<option value=''><?php esc_html_e( 'Default', 'my-calendar' ); ?></option>
						<?php
						$mcdb  = mc_is_remote_db();
						$query = 'SELECT event_begin FROM ' . my_calendar_table() . ' WHERE event_approved = 1 AND event_flagged <> 1 ORDER BY event_begin ASC LIMIT 0 , 1';
						$year1 = mc_date( 'Y', strtotime( $mcdb->get_var( $query ) ) );
						$diff1 = mc_date( 'Y' ) - $year1;
						$past  = $diff1;
						/**
						 * How many years into the future should be shown in the navigation jumpbox. Default '5'.
						 *
						 * @hook mc_jumpbox_future_years
						 *
						 * @param {int}    $future Number of years ahead.
						 * @param {string} $cid Current calendar ID. '' when running in the shortcode generator.
						 *
						 * @return {int}
						 */
						$future = apply_filters( 'mc_jumpbox_future_years', 5, '' );
						$fut    = 1;
						$f      = '';
						$p      = '';
						while ( $past > 0 ) {
							$p   .= '<option value="';
							$p   .= (int) current_time( 'Y' ) - $past;
							$p   .= '">';
							$p   .= (int) current_time( 'Y' ) - $past . "</option>\n";
							$past = $past - 1;
						}
						while ( $fut < $future ) {
							$f  .= '<option value="';
							$f  .= (int) current_time( 'Y' ) + $fut;
							$f  .= '">';
							$f  .= (int) current_time( 'Y' ) + $fut . "</option>\n";
							$fut = $fut + 1;
						}
						echo wp_kses( $p . '<option value="' . current_time( 'Y' ) . '"' . selected( current_time( 'Y' ), $year ) . '>' . current_time( 'Y' ) . "</option>\n" . $f, mc_kses_elements() );
						?>
					</select>
				</p>
				<p>
					<label for="month<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Month', 'my-calendar' ); ?></label>
					<select name="month" id="month<?php echo esc_attr( $type ); ?>">
						<option value=''><?php esc_html_e( 'Default', 'my-calendar' ); ?></option>
						<?php
						$list_months = '';
						for ( $i = 1; $i <= 12; $i++ ) {
							$list_months .= "<option value='$i'" . selected( $i, $month ) . '>' . date_i18n( 'F', mktime( 0, 0, 0, $i, 1 ) ) . '</option>' . "\n";
						}
						echo wp_kses( $list_months, mc_kses_elements() );
						?>
					</select>
				</p>
				<p>
					<label for="day<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Day', 'my-calendar' ); ?></label>
					<select name="day" id="day<?php echo esc_attr( $type ); ?>">
						<option value=''><?php esc_html_e( 'Default', 'my-calendar' ); ?></option>
						<?php
						$days = '';
						for ( $i = 1; $i <= 31; $i++ ) {
							$days .= "<option value='$i'" . selected( $i, $day ) . '>' . $i . '</option>' . "\n";
						}
						echo wp_kses( $days, mc_kses_elements() );
						?>
					</select>
				</p>
			</fieldset>
				<?php
			}
			if ( 'upcoming' === $type ) {
				// Upcoming events only.
				?>
			<fieldset>
				<legend><?php esc_html_e( 'Event Range', 'my-calendar' ); ?></legend>
				<p>
					<label for="before<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Events/Days Before Current Day', 'my-calendar' ); ?></label>
					<input type="number" name="before" id="before<?php echo esc_attr( $type ); ?>" value="" />
				</p>
				<p>
					<label for="after<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Events/Days After Current Day', 'my-calendar' ); ?></label>
					<input type="number" name="after" id="after<?php echo esc_attr( $type ); ?>" value="" />
				</p>
				<p>
					<label for="skip<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Events/Days to Skip', 'my-calendar' ); ?></label>
					<input type="number" name="skip" id="skip<?php echo esc_attr( $type ); ?>" value="" />
				</p>
				<p class="checkbox">
					<input type="checkbox" name="show_recurring" id="show_recurring<?php echo esc_attr( $type ); ?>" value="no" />
					<label for="show_recurring<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Show only the first recurring event in a series', 'my-calendar' ); ?></label>
				</p>
				<p>
					<label for="type<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Type of Upcoming Events List', 'my-calendar' ); ?></label>
					<select name="type" id="type<?php echo esc_attr( $type ); ?>">
						<option value="event" selected="selected"><?php esc_html_e( 'Events', 'my-calendar' ); ?></option>
						<option value="year"><?php esc_html_e( 'Current Year', 'my-calendar' ); ?></option>
						<option value="days"><?php esc_html_e( 'Days', 'my-calendar' ); ?></option>
						<option value="custom"><?php esc_html_e( 'Custom Dates', 'my-calendar' ); ?></option>
						<option value="month"><?php esc_html_e( 'Current Month', 'my-calendar' ); ?></option>
						<option value="month+1"><?php esc_html_e( 'Next Month', 'my-calendar' ); ?></option>
						<option value="month+2"><?php esc_html_e( '2nd Month Out', 'my-calendar' ); ?></option>
						<option value="month+3"><?php esc_html_e( '3rd Month Out', 'my-calendar' ); ?></option>
						<option value="month+4"><?php esc_html_e( '4th Month Out', 'my-calendar' ); ?></option>
						<option value="month+5"><?php esc_html_e( '5th Month Out', 'my-calendar' ); ?></option>
						<option value="month+6"><?php esc_html_e( '6th Month Out', 'my-calendar' ); ?></option>
						<option value="month+7"><?php esc_html_e( '7th Month Out', 'my-calendar' ); ?></option>
						<option value="month+8"><?php esc_html_e( '8th Month Out', 'my-calendar' ); ?></option>
						<option value="month+9"><?php esc_html_e( '9th Month Out', 'my-calendar' ); ?></option>
						<option value="month+10"><?php esc_html_e( '10th Month Out', 'my-calendar' ); ?></option>
						<option value="month+11"><?php esc_html_e( '11th Month Out', 'my-calendar' ); ?></option>
						<option value="month+12"><?php esc_html_e( '12th Month Out', 'my-calendar' ); ?></option>
					</select>
				</p>
				<div class='custom'>
					<p>
						<label for='from<?php echo esc_attr( $type ); ?>'><?php esc_html_e( 'Starting Date (YYYY-MM-DD)', 'my-calendar' ); ?></label> <input type='text' name='from' id='from<?php echo esc_attr( $type ); ?>' placeholder="YYYY-MM-DD" />
					</p>
					<p>
						<label for='to<?php echo esc_attr( $type ); ?>'><?php esc_html_e( 'End Date (YYYY-MM-DD)', 'my-calendar' ); ?></label> <input type='text' name='to' id='to<?php echo esc_attr( $type ); ?>' placeholder="YYYY-MM-DD" />
					</p>
				</div>
				<p>
					<label for="order<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Event Order', 'my-calendar' ); ?></label>
					<select name="order" id="order<?php echo esc_attr( $type ); ?>">
						<option value="asc" selected="selected"><?php esc_html_e( 'Ascending', 'my-calendar' ); ?></option>
						<option value="desc"><?php esc_html_e( 'Descending', 'my-calendar' ); ?></option>
					</select>
				</p>
			</fieldset>
				<?php
			}
			if ( 'upcoming' === $type || 'today' === $type ) {
				// Upcoming Events & Today's Events shortcodes.
				?>
			<fieldset>
				<legend><?php esc_html_e( 'Templating', 'my-calendar' ); ?></legend>
				<p>
					<label for="fallback<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Fallback Text', 'my-calendar' ); ?></label>
					<input type="text" name="fallback" id="fallback<?php echo esc_attr( $type ); ?>" value="" />
				</p>
				<p>
					<label for="template<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Template', 'my-calendar' ); ?></label>
					<textarea cols="40" rows="4" name="template" id="template<?php echo esc_attr( $type ); ?>" aria-describedby="mc_template-note"><?php echo esc_textarea( '<strong>{date}</strong>, {time}: {link_title}' ); ?></textarea><span id="mc_template-note"><i class="dashicons dashicons-editor-help" aria-hidden="true"></i>
					<?php
					// Translators: Link to custom template UI.
					printf( __( 'Creates a new <a href="%s">custom template</a>.', 'my-calendar' ), admin_url( 'admin.php?page=my-calendar-design#my-calendar-templates' ) );
					?>
					</span>
				</p>
			</fieldset>
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
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $post_id ) || ! ( (int) mc_get_option( 'uri_id' ) === (int) $post_id ) ) {
		return $post_id;
	}
	$options = mc_generate( 'array' );
	update_post_meta( $post_id, '_mc_calendar', $options );
}
add_action( 'save_post', 'mc_update_calendar', 10, 1 );
