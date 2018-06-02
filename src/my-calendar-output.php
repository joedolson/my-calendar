<?php
/**
 * Output the calendar.
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
 * Get template for a specific usage.
 *
 * @param string $template name of template.
 *
 * @return string Template HTML/tags
 */
function mc_get_template( $template ) {
	$templates = get_option( 'mc_templates' );
	$template  = ( isset( $templates[ $template ] ) ) ? $templates[ $template ] : $template;

	$keys = array( 'title', 'title_list', 'title_solo', 'link', 'mini', 'list', 'details', 'rss', 'grid' );

	if ( in_array( $template, $keys ) ) {
		$template = '';
	}

	return trim( $template );
}

/**
 * HTML output for event time
 *
 * @param object $event Current event.
 * @param string $type Type of view.
 * @param string $current Current date being processed.
 *
 * @return string HTML output.
 */
function mc_time_html( $event, $type, $current ) {
	$id_start    = date( 'Y-m-d', strtotime( $event->occur_begin ) );
	$id_end      = date( 'Y-m-d', strtotime( $event->occur_end ) );
	$cur_date    = ( 'list' == $type ) ? '' : "<span class='mc-event-date dtstart' itemprop='startDate' title='" . $id_start . 'T' . $event->event_time . "' content='" . $id_start . 'T' . $event->event_time . "'>$current</span>";
	$time_format = get_option( 'mc_time_format' );

	$time  = "<div class='time-block'>";
	$time .= "<p>$cur_date ";
	if ( '00:00:00' != $event->event_time && '' != $event->event_time ) {
		$time .= "\n
		<span class='event-time dtstart'>
			<time class='value-title' datetime='" . $id_start . 'T' . $event->event_time . "' title='" . $id_start . 'T' . $event->event_time . "'>" .
				date_i18n( $time_format, strtotime( $event->event_time ) ) . '
			</time>
		</span>';
		if ( 0 == $event->event_hide_end ) {
			if ( '' != $event->event_endtime && $event->event_endtime != $event->event_time ) {
				$time .= "
					<span class='time-separator'> &ndash; </span>
					<span class='end-time dtend'>
						<time class='value-title' datetime='" . $id_end . 'T' . $event->event_endtime . "' title='" . $id_end . 'T' . $event->event_endtime . "'>" . date_i18n( $time_format, strtotime( $event->event_endtime ) ) . '
						</time>
					</span>';
			}
		}
	} else {
		$notime = mc_notime_label( $event );
		$time  .= "<span class='event-time'>";
		$time  .= ( 'N/A' == $notime ) ? "<abbr title='" . __( 'Not Applicable', 'my-calendar' ) . "'>" . __( 'N/A', 'my-calendar' ) . "</abbr>\n" : esc_html( $notime );
		$time  .= '</span></p>';
	}
	$time .= apply_filters( 'mcs_end_time_block', '', $event );
	$time .= ( 0 == $event->event_hide_end ) ? "<meta itemprop='endDate' content='" . $id_start . 'T' . $event->event_endtime . "'/>" : '';
	$time .= '<meta itemprop="duration" content="' . mc_duration( $event ) . '"/>';
	$time .= '</div>';

	return apply_filters( 'mcs_time_block', $time, $event );
}

/**
 * Produce filepath & name or full img HTML for specific category's icon
 *
 * @param object $event Current event object.
 * @param string $type 'html' to generate HTML.
 *
 * @return string image path or HTML
 */
function mc_category_icon( $event, $type = 'html' ) {
	if ( is_object( $event ) && property_exists( $event, 'category_icon' ) ) {
		$url   = plugin_dir_url( __FILE__ );
		$image = '';
		if ( 'true' != get_option( 'mc_hide_icons' ) ) {
			if ( '' != $event->category_icon ) {
				$path = ( mc_is_custom_icon() ) ? str_replace( 'my-calendar', 'my-calendar-custom', $url ) : plugins_url( 'images/icons', __FILE__ ) . '/';
				$hex  = ( strpos( $event->category_color, '#' ) !== 0 ) ? '#' : '';
				if ( 'html' == $type ) {
					$image = '<img src="' . $path . $event->category_icon . '" alt="' . __( 'Category', 'my-calendar' ) . ': ' . esc_attr( $event->category_name ) . '" class="category-icon" style="background:' . $hex . $event->category_color . '" />';
				} else {
					$image = $path . $event->category_icon;
				}
			}
		}

		return apply_filters( 'mc_category_icon', $image, $event, $type );
	}
}

add_filter( 'the_title', 'mc_category_icon_title', 10, 2 );
/**
 * Add category icon into title on individual event pages.
 *
 * @param string $title Original title.
 * @param int    $post_id Post ID.
 *
 * @return string new title string
 */
function mc_category_icon_title( $title, $post_id = null ) {
	if ( is_singular( 'mc-events' ) && in_the_loop() ) {
		if ( $post_id ) {
			$event_id = ( isset( $_GET['mc_id'] ) && is_numeric( $_GET['mc_id'] ) ) ? $_GET['mc_id'] : get_post_meta( $post_id, '_mc_event_id', true );
			if ( is_numeric( $event_id ) ) {
				$event = mc_get_first_event( $event_id );
				if ( is_object( $event ) && property_exists( $event, 'category_icon' ) ) {
					$icon = mc_category_icon( $event );
				} else {
					$icon = '';
				}
				$title = $icon . ' ' . strip_tags( $title, mc_strip_tags() );
			}
		}
	}

	return $title;
}

/**
 * Generate the set of events for a given day
 *
 * @param array  $events Array of event objects.
 * @param array  $params calendar parameters.
 * @param string $process_date String formatted date being displayed.
 * @param string $template Template to use for drawing individual events.
 *
 * @return string Generated HTML.
 */
function my_calendar_draw_events( $events, $params, $process_date, $template = '' ) {
	$type = $params['format'];
	$time = $params['time'];

	$open_option = get_option( 'mc_open_day_uri' );
	if ( 'mini' == $type && ( 'true' == $open_option || 'listanchor' == $open_option || 'calendaranchor' == $open_option ) ) {
		return true;
	}
	// We need to sort arrays of objects by time.
	if ( is_array( $events ) ) {
		$output_array = array();
		$begin        = '';
		$event_output = '';
		$end          = '';
		if ( 'mini' == $type && count( $events ) > 0 ) {
			$begin .= "<div id='date-$process_date' class='calendar-events'>";
		}
		foreach ( array_keys( $events ) as $key ) {
			$event =& $events[ $key ];
			if ( 'S1' != $event->event_recur ) {
				$check = get_post_meta( $event->event_post, '_occurrence_overlap', true );
				if ( 'false' == $check ) {
					$check = mc_test_occurrence_overlap( $event, true );
				}
			} else {
				$check = '';
			}
			if ( '' == $check ) {
				$output_array[] = my_calendar_draw_event( $event, $type, $process_date, $time, $template );
			}
		}
		if ( is_array( $output_array ) ) {
			foreach ( array_keys( $output_array ) as $key ) {
				$value         =& $output_array[ $key ];
				$event_output .= $value;
			}
		}
		if ( '' == $event_output ) {
			return '';
		}
		if ( 'mini' == $type && count( $events ) > 0 ) {
			$end .= '</div>';
		}

		return $begin . $event_output . $end;
	}

	return '';
}


/**
 * Draw a single event
 *
 * @param object $event Event object.
 * @param string $type Type of view being drawn.
 * @param string $process_date Current date being displayed.
 * @param string $time Time view being drawn.
 * @param string $template Template to use to draw event.
 *
 * @return string Generated HTML.
 */
function my_calendar_draw_event( $event, $type = 'calendar', $process_date, $time, $template = '' ) {
	$exit_early = mc_exit_early( $event, $process_date );
	if ( $exit_early ) {
		return;
	}

	// assign empty values to template sections.
	$header      = '';
	$address     = '';
	$more        = '';
	$author      = '';
	$list_title  = '';
	$title       = '';
	$output      = '';
	$container   = '';
	$short       = '';
	$description = '';
	$link        = '';
	$vcal        = '';
	$gcal        = '';
	$image       = '';
	$tickets     = '';
	$date_format = get_option( 'mc_date_format' );
	$date_format = ( '' != $date_format ) ? $date_format : get_option( 'date_format' );
	$data        = mc_create_tags( $event );
	$details     = '';
	if ( mc_show_details( $time, $type ) ) {
		$details  = apply_filters( 'mc_custom_template', false, $data, $event, $type, $process_date, $time, $template );
		$template = apply_filters( 'mc_use_custom_template', $template, $data, $event, $type, $process_date, $time );
		if ( false === $details ) {
			$details = mc_get_details( $data, $template, $type );
		}
	}

	// Display options.
	$display_map     = get_option( 'mc_show_map' );
	$display_address = get_option( 'mc_show_address' );
	$display_gcal    = get_option( 'mc_show_gcal' );
	$display_vcal    = get_option( 'mc_show_event_vcal' );
	$open_uri        = get_option( 'mc_open_uri' );
	$display_author  = get_option( 'mc_display_author' );
	$display_more    = get_option( 'mc_display_more' );
	$display_desc    = get_option( 'mc_desc' );
	$display_short   = get_option( 'mc_short' );
	$display_gmap    = get_option( 'mc_gmap' );
	$display_link    = get_option( 'mc_event_link' );
	$display_image   = get_option( 'mc_image' );
	$display_reg     = get_option( 'mc_event_registration' );
	$uid             = 'mc_' . $event->occur_id;
	$day_id          = date( 'd', strtotime( $process_date ) );
	$image           = mc_category_icon( $event );
	$has_image       = ( '' != $image ) ? ' has-image' : '';
	$event_classes   = mc_event_classes( $event, $day_id, $type );
	$header         .= "<div id='$uid-$day_id-$type' class='$event_classes'>\n";

	switch ( $type ) {
		case 'calendar':
			$title_template = ( mc_get_template( 'title' ) == '' ) ? '{title}' : mc_get_template( 'title' );
			break;
		case 'list':
			$title_template = ( mc_get_template( 'title_list' ) == '' ) ? '{title}' : mc_get_template( 'title_list' );
			break;
		case 'single':
			$title_template = ( mc_get_template( 'title_solo' ) == '' ) ? '{title}' : mc_get_template( 'title_solo' );
			break;
		default:
			$title_template = ( mc_get_template( 'title' ) == '' ) ? '{title}' : mc_get_template( 'title' );
	}

	$event_title = mc_draw_template( $data, $title_template );
	$event_title = ( '' == $event_title ) ? $data['title'] : strip_tags( $event_title, mc_strip_tags() );

	if ( ( strpos( $event_title, 'href' ) === false ) && 'mini' != $type && 'list' != $type ) {
		if ( 'true' == $open_uri ) {
			$details_link = esc_url( mc_get_details_link( $event ) );
			$wrap         = ( _mc_is_url( $details_link ) ) ? "<a href='$details_link' class='url summary$has_image'>" : '<span class="no-link">';
			$balance      = ( _mc_is_url( $details_link ) ) ? '</a>' : '</span>';
		} else {
			$wrap    = "<a href='#$uid-$day_id-$type-details' class='et_smooth_scroll_disabled url summary$has_image'>";
			$balance = '</a>';
		}
	} else {
		$wrap    = '';
		$balance = '';
	}

	$current       = date_i18n( $date_format, strtotime( $process_date ) );
	$group_class   = ( 1 == $event->event_span ) ? ' multidate group' . $event->event_group_id : '';
	$hlevel        = apply_filters( 'mc_heading_level_table', 'h3', $type, $time, $template );
	$inner_heading = apply_filters( 'mc_heading_inner_title', $wrap . $image . trim( $event_title ) . $balance, $event_title, $event );
	$header       .= ( 'single' != $type && 'list' != $type ) ? "<$hlevel class='event-title summary$group_class' id='$uid-title'>$inner_heading</$hlevel>\n" : '';
	$event_title   = ( 'single' == $type ) ? apply_filters( 'mc_single_event_title', $event_title, $event ) : $event_title;
	$title         = ( 'single' == $type && ! is_singular( 'mc-events' ) ) ? "<h2 class='event-title summary'>$image $event_title</h2>\n" : '<span class="summary screen-reader-text">' . $event_title . '</span>';
	$title         = apply_filters( 'mc_event_title', $title, $event, $event_title, $image );
	$header       .= '<span class="summary">' . $title . '</span>';

	$close_image  = apply_filters( 'mc_close_button', "<span class='dashicons dashicons-dismiss' aria-hidden='true'></span><span class='screen-reader-text'>Close</span>" );
	$close_button = "<button type='button' aria-controls='$uid-$day_id-$type-details' class='mc-toggle close' data-action='shiftforward'>$close_image</button>";

	if ( mc_show_details( $time, $type ) ) {
		$close = ( 'calendar' == $type || 'mini' == $type ) ? $close_button : '';

		if ( false === $details ) {
			if ( ( 'true' == $display_address || 'true' == $display_map ) ) {
				$address = mc_hcard( $event, $display_address, $display_map );
			}
			$time_html = mc_time_html( $event, $type, $current );
			if ( 'list' == $type ) {
				$hlevel     = apply_filters( 'mc_heading_level_list', 'h3', $type, $time, $template );
				$list_title = "<$hlevel class='event-title summary' id='$uid-title'>$image" . $event_title . "</$hlevel>\n";
			}
			if ( 'true' == $display_author ) {
				if ( 0 != $event->event_author ) {
					$e      = get_userdata( $event->event_author );
					$author = '<p class="event-author">' . __( 'Posted by', 'my-calendar' ) . ' <span class="author-name">' . $e->display_name . "</span></p>\n";
				}
			}

			if ( 'false' != $display_more && ! isset( $_GET['mc_id'] ) ) {
				$details_label = mc_get_details_label( $event, $data );
				$details_link  = mc_get_details_link( $event );
				// Translators: Event title.
				$aria = " aria-label='" . sprintf( __( 'Details about %s', 'my-calendar' ), $event_title ) . "'";
				if ( _mc_is_url( $details_link ) ) {
					$more = "<p class='mc_details'><a$aria itemprop='url' href='" . esc_url( $details_link ) . "'>$details_label</a></p>\n";
				} else {
					$more = '';
				}
			}
			$more = apply_filters( 'mc_details_grid_link', $more, $event );

			if ( 'true' == $display_gcal ) {
				$gcal = "<p class='gcal'>" . mc_draw_template( $data, '{gcal_link}' ) . '</p>';
			}

			if ( 'true' == $display_vcal ) {
				$vcal = "<p class='ical'>" . mc_draw_template( $data, '{ical_html}' ) . '</p>';
			}

			if ( 'true' == $display_image ) {
				$image = mc_get_event_image( $event, $data );
			}

			if ( 'true' == $display_desc || 'single' == $type ) {
				$description = wpautop( stripcslashes( mc_kses_post( $event->event_desc ) ), 1 );
				$description = "<div class='longdesc description' itemprop='description'>$description</div>";
			}

			if ( 'true' == $display_reg ) {
				$info     = wpautop( $event->event_registration );
				$url      = esc_url( $event->event_tickets );
				$external = ( $url && mc_external_link( $url ) ) ? 'external' : '';
				$tickets  = ( $url ) ? "<a class='$external' href='" . $url . "'>" . __( 'Buy Tickets', 'my-calendar' ) . '</a>' : '';
				$tickets  = $info . $tickets;
			}

			if ( 'true' == $display_short && 'single' != $type ) {
				$short = wpautop( stripcslashes( mc_kses_post( $event->event_short ) ), 1 );
				$short = "<div class='shortdesc description'>$short</div>";
			}

			$status     = apply_filters( 'mc_registration_state', '', $event );
			$return_url = apply_filters( 'mc_return_uri', mc_get_uri( $event ) );
			$return     = ( 'single' == $type ) ? "<p class='view-full'><a href='$return_url'>" . __( 'View full calendar', 'my-calendar' ) . '</a></p>' : '';

			if ( ! mc_show_details( $time, $type ) ) {
				$description = '';
				$short       = '';
				$status      = '';
			}

			if ( 'true' == $display_gmap ) {
				$map = ( is_singular( 'mc-events' ) || 'single' == $type ) ? mc_generate_map( $event ) : '';
			} else {
				$map = '';
			}
			$event_link = mc_event_link( $event );

			if ( '' != $event_link && 'false' != $display_link ) {
				$external_class = ( mc_external_link( $event_link ) ) ? "$type-link external url" : "$type-link url";
				$link_template  = ( '' != mc_get_template( 'link' ) ) ? mc_get_template( 'link' ) : __( 'More information', 'my-calendar' );
				$link_text      = mc_draw_template( $data, $link_template );
				$link           = "
				<p>
					<a href='" . esc_url( $event_link ) . "' class='$external_class' aria-describedby='$uid-title'>" . $link_text . '</a>
				</p>';
			}

			$details = "\n"
						. $close
						. $time_html
						. $list_title
						. $image
						. "<div class='location'>"
						. $map . $address
						. '</div>'
						. $description
						. $short
						. $link
						. $status
						. $tickets
						. $author
						. "<div class='sharing'>"
						. $vcal . $gcal . $more
						. '</div>'
						. $return;
		} else {
			// If a custom template is in use.
			$toggle  = ( 'calendar' == $type || 'mini' == $type ) ? $close_button : '';
			$details = $toggle . $details . "\n";
		}

		$img_class  = ( '' != $image ) ? ' has-image' : ' no-image';
		$container  = "<div id='$uid-$day_id-$type-details' class='details$img_class' role='alert' aria-labelledby='$uid-title' itemscope itemtype='http://schema.org/Event'>\n";
		$container .= "<meta itemprop='name' content='" . strip_tags( $event->event_title ) . "' />";
		$container  = apply_filters( 'mc_before_event', $container, $event, $type, $time );
		$details    = $header . $container . apply_filters( 'mc_inner_content', $details, $event, $type, $time );
		$details   .= apply_filters( 'mc_after_event', '', $event, $type, $time );
		$details   .= '</div><!--end .details--></div>';
		$details    = apply_filters( 'mc_event_content', $details, $event, $type, $time );
	} else {
		$details = apply_filters( 'mc_before_event_no_details', $container, $event, $type, $time ) . $header . apply_filters( 'mc_after_event_no_details', '', $event, $type, $time ) . '</div>';
	}

	return $details;
}

/**
 * Generate the details when using a custom template
 *
 * @param array  $data event tags.
 * @param string $template File name, custom template, etc.
 * @param string $type Type of view.
 *
 * @return string HTML output
 */
function mc_get_details( $data, $template, $type ) {
	$details = false;
	if ( '' != $template && mc_file_exists( $template ) ) {
		$template = file_get_contents( mc_get_file( $template ) );
		$details  = mc_draw_template( $data, $template );
	} elseif ( '' != $template && mc_key_exists( $template ) ) {
		$template = mc_get_custom_template( $template );
		$details  = mc_draw_template( $data, $template );
	} else {
		switch ( $type ) {
			case 'mini':
				$template = mc_get_template( 'mini' );
				if ( 1 == ( 'mc_use_mini_template' ) ) {
					$details = mc_draw_template( $data, $template );
				}
				break;
			case 'list':
				$template = mc_get_template( 'list' );
				if ( 1 == get_option( 'mc_use_list_template' ) ) {
					$details = mc_draw_template( $data, $template );
				}
				break;
			case 'single':
				$template = mc_get_template( 'details' );
				if ( 1 == get_option( 'mc_use_details_template' ) ) {
					$details = mc_draw_template( $data, $template );
				}
				break;
			case 'calendar':
			default:
				$template = mc_get_template( 'grid' );
				if ( 1 == get_option( 'mc_use_grid_template' ) ) {
					$details = mc_draw_template( $data, $template );
				}
		}
	}

	return $details;
}

/**
 * Get image for an event
 *
 * @param object $event Event object.
 * @param array  $data event tags.
 *
 * @return string HTML output
 */
function mc_get_event_image( $event, $data ) {
	$image = '';
	$sizes = get_intermediate_image_sizes();
	if ( in_array( 'large', $sizes ) ) {
		$default_size = 'large';
	} else {
		$default_size = 'medium';
	}
	$default_size = apply_filters( 'mc_default_image_size', $default_size );

	if ( is_numeric( $event->event_post ) && 0 != $event->event_post && ( isset( $data[ $default_size ] ) && '' != $data[ $default_size ] ) ) {
		$atts      = apply_filters( 'mc_post_thumbnail_atts', array( 'class' => 'mc-image photo' ) );
		$image_url = get_the_post_thumbnail_url( $event->event_post, $default_size );
		$image     = get_the_post_thumbnail( $event->event_post, $default_size, $atts );
	} else {
		$alt       = esc_attr( apply_filters( 'mc_event_image_alt', '', $event ) );
		$image_url = $event->event_image;
		$image     = ( '' != $event->event_image ) ? "<img src='$event->event_image' alt='$alt' class='mc-image photo' />" : '';
	}

	$meta = ( $image ) ? "<meta itemprop='image' content='$image_url'/>" : '';

	return $meta . $image;
}

/**
 * Generate classes for a given event
 *
 * @param object $event Event Object.
 * @param string $uid Unique ID for event.
 * @param string $type Type of view being shown.
 *
 * @return string classes
 */
function mc_event_classes( $event, $uid, $type ) {
	$uid = 'mc_' . $event->occur_id;
	$ts  = strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $event->ts_occur_begin ) ) );
	$end = strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $event->ts_occur_end ) ) );
	$now = current_time( 'timestamp' );
	if ( $ts < $now && $end > $now ) {
		$date_relation = 'on-now';
	} elseif ( $now < $ts ) {
		$date_relation = 'future-event';
	} elseif ( $now > $ts ) {
		$date_relation = 'past-event';
	}

	$classes = array(
		'mc-' . $uid,
		$type . '-event',
		mc_category_class( $event, 'mc_' ),
		$date_relation,
	);

	if ( $event->event_begin != $event->event_end ) {
		$classes[] = 'multidate';
	}

	if ( 'upcoming' != $type && 'related' != $type ) {
		$classes[] = 'vevent';
	}

	// Adds a number of extra queries; if they aren't needed, leave disabled.
	if ( property_exists( $event, 'categories' ) ) {
		$categories = $event->categories;
	} else {
		$categories = mc_get_categories( $event, false );
	}
	foreach ( $categories as $category ) {
		$classes[] = 'mc_rel_' . sanitize_html_class( $category->category_name );
	}

	$classes    = apply_filters( 'mc_event_classes', $classes, $event, $uid, $type );
	$class_html = strtolower( implode( ' ', $classes ) );

	return esc_attr( $class_html );
}

/**
 * Generate category classes for a given date
 *
 * @param object $object Usually an event, can be category.
 * @param string $prefix Prefix to append to category; varies on context.
 *
 * @return string classes
 */
function mc_category_class( $object, $prefix ) {
	if ( is_array( $object ) ) {
		$term = $object['term'];
		$name = $object['category'];
	} else {
		$term = $object->category_term;
		$name = $object->category_name;
	}
	$fallback = get_term_by( 'id', $term, 'mc-event-category' );
	if ( is_object( $fallback ) ) {
		$fallback = $fallback->slug;
	} else {
		$fallback = 'category_slug_missing';
	}

	return $prefix . strtolower( sanitize_html_class( str_replace( ' ', '-', $name ), $prefix . $fallback ) );
}

/**
 * Whether to show details on this event.
 *
 * @param string $time Current time span.
 * @param string $type Current view.
 *
 * @return boolean
 */
function mc_show_details( $time, $type ) {
	return ( 'calendar' == $type && 'true' == get_option( 'mc_open_uri' ) && 'day' != $time ) ? false : true;
}

add_filter( 'mc_after_event', 'mc_edit_panel', 10, 4 );
/**
 * List of edit links; shown if user has permission to see them.
 *
 * @param string $html existing output.
 * @param object $event Current event.
 * @param string $type type of view.
 * @param string $time timespan shown.
 *
 * @return string HTML output
 */
function mc_edit_panel( $html, $event, $type, $time ) {
	// Create edit links.
	$edit = '';
	if ( mc_can_edit_event( $event ) && get_option( 'mc_remote' ) != 'true' ) {
		$mc_id     = $event->occur_id;
		$groupedit = ( 0 != $event->event_group_id ) ? " &bull; <a href='" . admin_url( "admin.php?page=my-calendar-groups&amp;mode=edit&amp;event_id=$event->event_id&amp;group_id=$event->event_group_id" ) . "' class='group'>" . __( 'Edit Group', 'my-calendar' ) . "</a>\n" : '';
		$recurs    = str_split( $event->event_recur, 1 );
		$recur     = $recurs[0];
		$referer   = urlencode( mc_get_current_url() );
		$edit      = "<div class='mc_edit_links'><p>";
		if ( 'S' == $recur ) {
			$edit .= "<a href='" . admin_url( "admin.php?page=my-calendar&amp;mode=edit&amp;event_id=$event->event_id&amp;ref=$referer" ) . "' class='edit'>" . __( 'Edit', 'my-calendar' ) . "</a> &bull; <a href='" . admin_url( "admin.php?page=my-calendar-manage&amp;mode=delete&amp;event_id=$event->event_id&amp;ref=$referer" ) . "' class='delete'>" . __( 'Delete', 'my-calendar' ) . "</a>$groupedit";
		} else {
			$edit .= "<a href='" . admin_url( "admin.php?page=my-calendar&amp;mode=edit&amp;event_id=$event->event_id&amp;date=$mc_id&amp;ref=$referer" ) . "' class='edit'>" . __( 'Edit This Date', 'my-calendar' ) . "</a> &bull; <a href='" . admin_url( "admin.php?page=my-calendar&amp;mode=edit&amp;event_id=$event->event_id&amp;ref=$referer" ) . "' class='edit'>" . __( 'Edit All', 'my-calendar' ) . "</a> &bull; <a href='" . admin_url( "admin.php?page=my-calendar-manage&amp;mode=delete&amp;event_id=$event->event_id&amp;date=$mc_id&amp;ref=$referer" ) . "' class='delete'>" . __( 'Delete This Date', 'my-calendar' ) . "</a> &bull; <a href='" . admin_url( "admin.php?page=my-calendar-manage&amp;mode=delete&amp;event_id=$event->event_id&amp;ref=$referer" ) . "' class='delete'>" . __( 'Delete All', 'my-calendar' ) . "</a>
			$groupedit";
		}
		$edit .= '</p></div>';
	}
	if ( ! mc_show_details( $time, $type ) ) {
		$edit = '';
	}

	return $html . $edit;
}

/**
 * Build date switcher
 *
 * @param string $type Current view being shown.
 * @param string $cid ID of current view.
 * @param string $time Current time view.
 * @param array  $date current date array (month, year, day).
 *
 * @return string HTML output.
 */
function mc_date_switcher( $type = 'calendar', $cid = 'all', $time = 'month', $date = array() ) {
	if ( 'week' == $time ) {
		return '';
	}
	global $wpdb;
	$mcdb    = $wpdb;
	$c_month = isset( $date['month'] ) ? $date['month'] : date( 'n', current_time( 'timestamp' ) );
	$c_year  = isset( $date['year'] ) ? $date['year'] : date( 'Y', current_time( 'timestamp' ) );
	$c_day   = isset( $date['day'] ) ? $date['day'] : date( 'j', current_time( 'timestamp' ) );
	if ( 'true' == get_option( 'mc_remote' ) && function_exists( 'mc_remote_db' ) ) {
		$mcdb = mc_remote_db();
	}
	$current_url    = mc_get_current_url();
	$date_switcher  = '';
	$date_switcher .= '<div class="my-calendar-date-switcher"><form action="' . $current_url . '" method="get"><div>';
	$qsa            = array();
	if ( isset( $_SERVER['QUERY_STRING'] ) ) {
		parse_str( $_SERVER['QUERY_STRING'], $qsa );
	}
	if ( ! isset( $_GET['cid'] ) ) {
		$date_switcher .= '<input type="hidden" name="cid" value="' . esc_attr( $cid ) . '" />';
	}
	foreach ( $qsa as $name => $argument ) {
		$name = esc_attr( strip_tags( $name ) );
		if ( is_array( $argument ) ) {
			$argument = '';
		} else {
			$argument = esc_attr( strip_tags( $argument ) );
		}
		if ( 'month' != $name && 'yr' != $name && 'dy' != $name ) {
			$date_switcher .= '<input type="hidden" name="' . $name . '" value="' . $argument . '" />';
		}
	}
	$day_switcher = '';
	if ( 'day' == $time ) {
		$day_switcher = ' <label class="maybe-hide" for="' . $cid . '-day">' . __( 'Day', 'my-calendar' ) . '</label> <select id="' . $cid . '-day" name="dy">' . "\n";
		for ( $i = 1; $i <= 31; $i++ ) {
			$day_switcher .= "<option value='$i'" . selected( $i, $c_day, false ) . '>' . $i . '</option>' . "\n";
		}
		$day_switcher .= '</select>';
	}
	// We build the months in the switcher.
	$date_switcher .= ' <label class="maybe-hide" for="' . $cid . '-month">' . __( 'Month', 'my-calendar' ) . '</label> <select id="' . $cid . '-month" name="month">' . "\n";
	for ( $i = 1; $i <= 12; $i ++ ) {
		$test           = str_pad( $i, 2, '0', STR_PAD_LEFT );
		$c_month        = str_pad( $c_month, 2, '0', STR_PAD_LEFT );
		$date_switcher .= "<option value='$i'" . selected( $test, $c_month, false ) . '>' . date_i18n( 'F', mktime( 0, 0, 0, $i, 1 ) ) . '</option>' . "\n";
	}
	$date_switcher .= '</select>' . "\n" . $day_switcher . ' <label class="maybe-hide" for="' . $cid . '-year">' . __( 'Year', 'my-calendar' ) . '</label> <select id="' . $cid . '-year" name="yr">' . "\n";
	// Query to identify oldest start date in the database.
	$year1  = date( 'Y', strtotime( $mcdb->get_var( 'SELECT event_begin FROM ' . my_calendar_table() . ' WHERE event_approved = 1 AND event_flagged <> 1 ORDER BY event_begin ASC LIMIT 0 , 1' ) ) );
	$diff1  = date( 'Y' ) - $year1;
	$past   = $diff1;
	$future = apply_filters( 'mc_jumpbox_future_years', 5, $cid );
	$fut    = 1;
	$f      = '';
	$p      = '';
	$time   = current_time( 'timestamp' );

	while ( $past > 0 ) {
		$p   .= '<option value="';
		$p   .= date( 'Y', $time ) - $past;
		$p   .= '"' . selected( date( 'Y', $time ) - $past, $c_year, false ) . '>';
		$p   .= date( 'Y', $time ) - $past . "</option>\n";
		$past = $past - 1;
	}

	while ( $fut < $future ) {
		$f  .= '<option value="';
		$f  .= date( 'Y', $time ) + $fut;
		$f  .= '"' . selected( date( 'Y', $time ) + $fut, $c_year, false ) . '>';
		$f  .= date( 'Y', $time ) + $fut . "</option>\n";
		$fut = $fut + 1;
	}

	$date_switcher .= $p;
	$date_switcher .= '<option value="' . date( 'Y', $time ) . '"' . selected( date( 'Y', $time ), $c_year, false ) . '>' . date( 'Y', $time ) . "</option>\n";
	$date_switcher .= $f;
	$date_switcher .= '</select> <input type="submit" class="button" value="' . __( 'Go', 'my-calendar' ) . '" /></div></form></div>';
	$date_switcher  = apply_filters( 'mc_jumpbox', $date_switcher );

	return $date_switcher;
}

/**
 * Generate toggle between list and grid views
 *
 * @param string $format currently shown.
 * @param string $toggle whether to show.
 * @param string $time Current time view.
 *
 * @return string HTML output
 */
function mc_format_toggle( $format, $toggle, $time ) {
	if ( 'mini' != $format && 'yes' == $toggle ) {
		$toggle = "<div class='mc-format'>";
		switch ( $format ) {
			case 'list':
				$url     = mc_build_url( array( 'format' => 'calendar' ), array() );
				$toggle .= "<a href='$url' class='grid mcajax'>" . __( '<span class="maybe-hide">View as </span>Grid', 'my-calendar' ) . '</a>';
				break;
			default:
				$url     = mc_build_url( array( 'format' => 'list' ), array() );
				$toggle .= "<a href='$url' class='list mcajax'>" . __( '<span class="maybe-hide">View as </span>List', 'my-calendar' ) . '</a>';
				break;
		}
		$toggle .= '</div>';
	} else {
		$toggle = '';
	}

	if ( 'day' == $time ) {
		$toggle = "<div class='mc-format'><span class='mc-active list'>" . __( '<span class="maybe-hide">View as </span>List', 'my-calendar' ) . '</span></div>';
	}

	if ( ( 'true' == get_option( 'mc_convert' ) || 'mini' == get_option( 'mc_convert' ) ) && mc_is_mobile() ) {
		$toggle = '';
	}

	return apply_filters( 'mc_format_toggle_html', $toggle, $format, $time );
}

/**
 * Generate toggle for time views between day month & week
 *
 * @param string $format of current view.
 * @param string $time timespan of current view.
 * @param string $month current month.
 * @param string $year current year.
 * @param string $current Current date.
 * @param int    $start_of_week Day week starts on.
 * @param string $from Date started from.
 *
 * @return string HTML output
 */
function mc_time_toggle( $format, $time, $month, $year, $current, $start_of_week, $from ) {
	// if dy parameter not set, use today's date instead of first day of month.
	if ( isset( $_GET['dy'] ) ) {
		$current_day = absint( $_GET['dy'] );
		$current_set = mktime( 0, 0, 0, $month, $current_day, $year );
		if ( date( 'N', $current_set ) == $start_of_week ) {
			$weeks_day = mc_first_day_of_week( $current_set );
		} else {
			$weeks_day = mc_first_day_of_week( $current );
		}
	} else {
		$weeks_day = mc_first_day_of_week( current_time( 'timestamp' ) );
	}
	$day = $weeks_day[0];
	if ( isset( $_GET['time'] ) && 'day' == $_GET['time'] ) {
		// don't adjust day if viewing day format.
	} else {
		if ( ! isset( $_GET['dy'] ) && $day > 20 ) {
			$day = date( 'j', strtotime( "$from + 1 week" ) );
		}
	}
	$adjust = ( isset( $weeks_day[1] ) ) ? $weeks_day[1] : 0;
	$toggle = '';

	if ( 'mini' != $format ) {
		$toggle      = "<div class='mc-time'>";
		$current_url = mc_get_current_url();
		if ( -1 == $adjust ) {
			$wmonth = ( 1 != $month ) ? $month - 1 : 12;
		} else {
			$wmonth = $month;
		}
		switch ( $time ) {
			case 'week':
				$url     = mc_build_url( array( 'time' => 'month' ), array( 'mc_id' ) );
				$toggle .= "<a href='$url' class='month mcajax'>" . __( 'Month', 'my-calendar' ) . '</a>';
				$toggle .= "<span class='mc-active week'>" . __( 'Week', 'my-calendar' ) . '</span>';
				$url     = mc_build_url( array(
					'time' => 'day',
					'dy'   => $day,
				), array( 'dy', 'mc_id' ) );
				$toggle .= "<a href='$url' class='day mcajax'>" . __( 'Day', 'my-calendar' ) . '</a>';
				break;
			case 'day':
				$url     = mc_build_url( array( 'time' => 'month' ), array() );
				$toggle .= "<a href='$url' class='month mcajax'>" . __( 'Month', 'my-calendar' ) . '</a>';
				$url     = mc_build_url( array(
					'time'  => 'week',
					'dy'    => $day,
					'month' => $wmonth,
					'yr'    => $year,
				), array( 'dy', 'month', 'mc_id' ) );
				$toggle .= "<a href='$url' class='week mcajax'>" . __( 'Week', 'my-calendar' ) . '</a>';
				$toggle .= "<span class='mc-active day'>" . __( 'Day', 'my-calendar' ) . '</span>';
				break;
			default:
				$toggle .= "<span class='mc-active month'>" . __( 'Month', 'my-calendar' ) . '</span>';
				$url     = mc_build_url( array(
					'time'  => 'week',
					'dy'    => $day,
					'month' => $wmonth,
				), array( 'dy', 'month', 'mc_id' ) );
				$toggle .= "<a href='$url' class='week mcajax'>" . __( 'Week', 'my-calendar' ) . '</a>';
				$url     = mc_build_url( array( 'time' => 'day' ), array() );
				$toggle .= "<a href='$url' class='day mcajax'>" . __( 'Day', 'my-calendar' ) . '</a>';
				break;
		}
		$toggle .= '</div>';
	} else {
		$toggle = '';
	}

	return apply_filters( 'mc_time_toggle_html', $toggle, $format, $time );
}

/**
 * Calculate dates that should be used to calculate start and end dates for current view.
 *
 * @param string $timestamp Time stamp for first date of current period.
 * @param string $period base type of span being displayed.
 *
 * @return array from and to dates
 */
function mc_date_array( $timestamp, $period ) {
	switch ( $period ) {
		case 'month':
		case 'month+1':
			if ( 'month+1' == $period ) {
				$timestamp = strtotime( '+1 month', $timestamp );
			}
			$start_of_week = get_option( 'start_of_week' );
			$first         = date( 'N', $timestamp ); // ISO-8601.
			$sub           = date( 'w', $timestamp ); // numeric (how WordPress option is stored).
			$n             = ( 1 == $start_of_week ) ? $first - 1 : $first;

			if ( $sub == $start_of_week ) {
				$from = date( 'Y-m-d', $timestamp );
			} else {
				$start = strtotime( "-$n days", $timestamp );
				$from  = date( 'Y-m-d', $start );
			}
			$endtime = mktime( 0, 0, 0, date( 'm', $timestamp ), date( 't', $timestamp ), date( 'Y', $timestamp ) );

			// This allows multiple months displayed. Will figure out splitting tables...
			// To handle: $endtime = strtotime( "+$months months",$endtime ); JCD TODO.
			$last = date( 'N', $endtime );
			$n    = ( 1 == get_option( 'start_of_week' ) ) ? 7 - $last : 6 - $last;
			if ( '-1' == $n && '7' == date( 'N', $endtime ) ) {
				$n = 6;
			}
			$to = date( 'Y-m-d', strtotime( "+$n days", $endtime ) );

			$return = array(
				'from' => $from,
				'to'   => $to,
			);
			break;
		case 'week':
			// First day of the week is calculated prior to this function. Argument received is the first day of the week.
			$from = date( 'Y-m-d', $timestamp );
			$to   = date( 'Y-m-d', strtotime( '+6 days', $timestamp ) );

			$return = array(
				'from' => $from,
				'to'   => $to,
			);
			break;
		default:
			$return = false;
	}

	return $return;
}

/**
 * Create list of classes for a given date.
 *
 * @param array                $events array of event objects.
 * @param mixed string/boolean $date current date if a date is being processed.
 *
 * @return string of classes
 */
function mc_events_class( $events, $date = false ) {
	$class        = '';
	$events_class = '';
	if ( ! is_array( $events ) || ! count( $events ) ) {
		$events_class = 'no-events';
	} else {
		foreach ( array_keys( $events ) as $key ) {
			$event =& $events[ $key ];
			if ( '00:00:00' == $event->event_endtime && date( 'Y-m-d', strtotime( $event->occur_end ) ) == $date && date( 'Y-m-d', strtotime( $event->occur_begin ) ) != $date ) {
				continue;
			}
			$author = ' author' . $event->event_author;
			if ( strpos( $class, $author ) === false ) {
				$class .= $author;
			}
			$cat = mc_category_class( $event, 'mcat_' );
			if ( strpos( $class, $cat ) === false ) {
				$class .= ' ' . sanitize_html_class( $cat );
			}
			if ( mc_private_event( $event ) ) {
				$class = ' private-event hidden';
			}
		}
		if ( $class ) {
			$events_class = "has-events$class";
		}
	}

	return esc_attr( $events_class );
}

/**
 * List first selected event + event count
 *
 * @param array $events Array of event objects.
 *
 * @return string
 */
function mc_list_title( $events ) {
	usort( $events, 'mc_time_cmp' );
	$now         = $events[0];
	$count       = count( $events ) - 1;
	$event_title = strip_tags( stripcslashes( $now->event_title ), mc_strip_tags() );
	if ( 0 == $count ) {
		$cstate = '';
	} elseif ( 1 == $count ) {
		// Translators: %s Title of event.
		$cstate = sprintf( __( '%s<span class="mc-list-extended"> and 1 other event</span>', 'my-calendar' ), $event_title );
	} else {
		// Translators: %s Title of event, %d number of other events.
		$cstate = sprintf( __( '%1$s<span class="mc-list-extended"> and %2$d other events</span>', 'my-calendar' ), $event_title, $count );
	}
	$title = apply_filters( 'mc_list_event_title_hint', $cstate, $now, $events );

	return $title;
}

/**
 * List all events viewable in this context
 *
 * @param array $events Array of event objects.
 *
 * @return string
 */
function mc_list_titles( $events ) {
	usort( $events, 'mc_time_cmp' );
	$titles = array();

	foreach ( $events as $event ) {
		$title    = apply_filters( 'mc_list_event_title_hint', strip_tags( stripcslashes( $event->event_title ), mc_strip_tags() ), $event, $events );
		$titles[] = $title;
	}

	$result = apply_filters( 'mc_titles_format', '', $titles );

	if ( '' == $result ) {
		$result = implode( ', ', $titles );
	}

	return "<span class='mc-list-event'>$result</span>";
}

/**
 * Output search results for a given query
 *
 * @param mixed string/array $query Search query.
 *
 * @return string HTML output
 */
function mc_search_results( $query ) {
	$before = apply_filters( 'mc_past_search_results', 0, 'basic' );
	$after  = apply_filters( 'mc_future_search_results', 10, 'basic' ); // Return only future events, nearest 10.
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
		$output = mc_produce_upcoming_events( $event_array, $template, 'list', 'ASC', 0, $before, $after, 'yes', 'nofilters' );
	} else {
		$output = apply_filters( 'mc_search_no_results', "<li class='no-results'>" . __( 'Sorry, your search produced no results.', 'my-calendar' ) . '</li>' );
	}

	$header = apply_filters( 'mc_search_before', '<ol class="mc-search-results">', $term );
	$footer = apply_filters( 'mc_search_after', '</ol>', $term );

	return $header . $output . $footer;
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
	if ( is_object( $post ) && in_the_loop() ) {
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

add_action( 'template_redirect', 'mc_hidden_event' );
/**
 * If an event is hidden from the current user, redirect to 404.
 */
function mc_hidden_event() {
	$do_redirect = false;
	$is_404      = false;
	if ( isset( $_GET['mc_id'] ) ) {
		$mc_id = absint( $_GET['mc_id'] );
		if ( ! mc_valid_id( $mc_id ) ) {
			$do_redirect = true;
			$is_404      = true;
		} else {
			$event = mc_get_event( $mc_id, 'object' );
			if ( mc_event_is_hidden( $event ) ) {
				$do_redirect = true;
			}
		}
	} else {
		global $wp_query;
		$slug = $wp_query->query_vars['name'];
		$post = get_page_by_path( $slug, OBJECT, 'mc-events' );
		if ( ! $post ) {
			return;
		}
		if ( is_object( $post ) && 'mc-events' == $post->post_type ) {
			$event_id = get_post_meta( $post->ID, '_mc_event_id', true );
			if ( ! $event_id ) {
				return;
			}
			$event = mc_get_first_event( $event_id );
			if ( mc_event_is_hidden( $event ) ) {
				$do_redirect = true;
			}
		}
	}
	if ( $do_redirect ) {
		$uri = mc_get_uri();
		if ( ! $is_404 ) {
			wp_safe_redirect( $uri );
		} else {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		}
	}
}

/**
 * Filter titles on event pages
 *
 * @param string $title Event title.
 * @param string $sep Defined separator.
 * @param string $seplocation Location of separator in relation to title.
 *
 * @return string New event title
 */
function mc_event_filter( $title, $sep = ' | ', $seplocation = 'right' ) {
	if ( isset( $_GET['mc_id'] ) && is_numeric( $_GET['mc_id'] ) ) {
		$id    = (int) $_GET['mc_id'];
		$event = mc_get_event( $id );
		if ( ! is_object( $event ) ) {
			return $title;
		}
		if ( mc_event_is_hidden( $event ) ) {
			return $title;
		}
		$array     = mc_create_tags( $event );
		$left_sep  = ( 'right' != $seplocation ? ' ' . $sep . ' ' : '' );
		$right_sep = ( 'right' != $seplocation ? '' : ' ' . $sep . ' ' );
		$template  = get_option( 'mc_event_title_template' );
		$template  = ( '' != $template ) ? stripslashes( $template ) : "$left_sep {title} $sep {date} $right_sep ";

		return strip_tags( mc_draw_template( $array, $template ) );
	} else {
		return $title;
	}
}

/**
 * Verify that a given occurrence ID is valid.
 *
 * @param int $mc_id Occurrence ID.
 *
 * @return boolean
 */
function mc_valid_id( $mc_id ) {
	global $wpdb;
	$mcdb = $wpdb;
	if ( 'true' == get_option( 'mc_remote' ) && function_exists( 'mc_remote_db' ) ) {
		$mcdb = mc_remote_db();
	}

	$result = $mcdb->get_row( $mcdb->prepare( 'SELECT * FROM ' . my_calendar_event_table() . ' WHERE occur_id = %d', $mc_id ) );

	if ( is_object( $result ) ) {
		return true;
	}

	return false;
}

add_filter( 'the_content', 'mc_show_event_template', 100, 1 );
/**
 * Filter post content to process event templates
 *
 * @param string $content Original post content.
 *
 * @return string New content using My Calendar event templates
 */
function mc_show_event_template( $content ) {
	global $post;
	if ( is_single() && in_the_loop() && is_main_query() ) {
		// Some early versions of this placed the shortcode into the post content. Strip that out.
		$new_content = $content;
		if ( 'mc-events' == $post->post_type ) {
			if ( isset( $_GET['mc_id'] ) && mc_valid_id( $_GET['mc_id'] ) ) {
				$mc_id    = intval( $_GET['mc_id'] );
				$event_id = get_post_meta( $post->ID, '_mc_event_id', true );
				$event    = mc_get_event( $mc_id, 'object' );
				$date     = date( 'Y-m-d', strtotime( $event->occur_begin ) );
				$time     = date( 'H:i:00', strtotime( $event->occur_begin ) );
			} else {
				$event_id = get_post_meta( $post->ID, '_mc_event_id', true );
				if ( is_numeric( $event_id ) ) {
					$event = mc_get_nearest_event( $event_id );
					$date  = date( 'Y-m-d', strtotime( $event->occur_begin ) );
					$time  = date( 'H:i:s', strtotime( $event->occur_begin ) );
				} else {

					return $content;
				}
			}
			if ( is_object( $event ) && mc_event_is_hidden( $event ) ) {

				return $content;
			}
			if ( 1 == get_option( 'mc_use_details_template' ) ) {
				$new_content = apply_filters( 'mc_before_event', '', $event, 'single', $time );
				if ( isset( $_GET['mc_id'] ) ) {
					$shortcode = str_replace( "event='$event_id'", "event='$mc_id' instance='1'", get_post_meta( $post->ID, '_mc_event_shortcode', true ) );
				} else {
					$shortcode = get_post_meta( $post->ID, '_mc_event_shortcode', true );
				}
				$new_content .= do_shortcode( apply_filters( 'mc_single_event_shortcode', $shortcode ) );
				$new_content .= apply_filters( 'mc_after_event', '', $event, 'single', $time );
			} else {
				$new_content = my_calendar_draw_event( $event, 'single', $date, $time, '' );
			}

			$content = do_shortcode( apply_filters( 'mc_event_post_content', $new_content, $content, $post ) );
		}
	}

	return $content;
}

/**
 * Get all events related to an event ID (group IDs)
 *
 * @param int    $id Event group ID.
 * @param int    $this_id Event ID.
 * @param string $template Display template.
 *
 * @return string list of related events
 */
function mc_list_related( $id, $this_id, $template = '{date}, {time}' ) {
	if ( ! $id ) {
		return;
	}
	$results = mc_get_related( $id );
	$count   = count( $results );
	$output  = '';
	$classes = '';
	// If a large number of events, skip this.
	if ( $count > apply_filters( 'mc_related_event_limit', 50 ) ) {
		// filter to return an subset of related events.
		return apply_filters( 'mc_related_events', '', $results );
	}

	if ( is_array( $results ) && ! empty( $results ) ) {
		foreach ( $results as $result ) {
			$event_id = $result->event_id;
			if ( $event_id == $this_id ) {
				continue;
			}

			$event = mc_get_first_event( $event_id );
			if ( is_object( $event ) ) {
				$array = mc_create_tags( $event, 'related' );
				if ( mc_key_exists( $template ) ) {
					$template = mc_get_custom_template( $template );
				}
				$html     = mc_draw_template( $array, $template );
				$classes  = mc_event_classes( $event, '', 'related' );
				$classes .= ( $event_id == $this_id ) ? ' current-event' : '';
				$output  .= "<li class='$classes'>$html</li>";
			}
		}
	} else {
		$output = '<li>' . __( 'No related events', 'my-calendar' ) . '</li>';
	}

	return $output;
}

/**
 * Determine whether event is published.
 *
 * @param object $event Event object.
 *
 * @return boolean
 */
function mc_event_published( $event ) {
	if ( 1 == $event->event_approved ) {
		return true;
	}

	return false;
}

/**
 * Check whether an event should be hidden (privacy)
 *
 * @param object $event Event object.
 *
 * @return boolean
 */
function mc_event_is_hidden( $event ) {
	if ( ! is_object( $event ) ) {
		return false;
	}

	$category = $event->event_category;
	$private  = mc_get_private_categories();
	$can_see  = apply_filters( 'mc_user_can_see_private_events', is_user_logged_in(), $event );
	if ( in_array( $category, $private ) && ! $can_see ) {

		return true;
	}

	return false;
}

/**
 * Translates the arguments passed to the calendar and process them to generate the actual view.
 *
 * @param array $args Parameters from shortcode or my_calendar() function call.
 *
 * @return array $params New parameters, modified by context
 */
function mc_calendar_params( $args ) {
	$name     = isset( $args['name'] ) ? $args['name'] : 'calendar';
	$format   = isset( $args['format'] ) ? $args['format'] : 'calendar';
	$category = isset( $args['category'] ) ? $args['category'] : '';
	$time     = isset( $args['time'] ) ? $args['time'] : 'month';
	$ltype    = isset( $args['ltype'] ) ? $args['ltype'] : '';
	$lvalue   = isset( $args['lvalue'] ) ? $args['lvalue'] : '';
	$id       = isset( $args['id'] ) ? $args['id'] : '';
	$template = isset( $args['template'] ) ? $args['template'] : '';
	$content  = isset( $args['content'] ) ? $args['content'] : '';
	$author   = isset( $args['author'] ) ? $args['author'] : null;
	$host     = isset( $args['host'] ) ? $args['host'] : null;
	$above    = isset( $args['above'] ) ? $args['above'] : '';
	$below    = isset( $args['below'] ) ? $args['below'] : '';
	$syear    = isset( $args['year'] ) ? $args['year'] : false;
	$smonth   = isset( $args['month'] ) ? $args['month'] : false;
	$sday     = isset( $args['day'] ) ? $args['day'] : false;
	$source   = isset( $args['source'] ) ? $args['source'] : 'shortcode';
	$site     = ( isset( $args['site'] ) && '' != trim( $args['site'] ) ) ? $args['site'] : false;
	$months   = isset( $args['months'] ) ? $args['months'] : false;

	if ( ! in_array( $format, array( 'list', 'calendar', 'mini' ) ) ) {
		$format = 'calendar';
	}

	if ( ! in_array( $time, array( 'day', 'week', 'month', 'month+1' ) ) ) {
		$time = 'month';
	}

	$category = ( isset( $_GET['mcat'] ) ) ? (int) $_GET['mcat'] : $category;
	// This relates to default value inconsistencies, I think.
	if ( '' == $category ) {
		$category = 'all';
	}

	if ( isset( $_GET['format'] ) && in_array( $_GET['format'], array( 'list', 'mini' ) ) && 'mini' != $format ) {
		$format = esc_attr( $_GET['format'] );
	} else {
		$format = esc_attr( $format );
	}

	if ( isset( $_GET['time'] ) && in_array( $_GET['time'], array( 'day', 'week', 'month', 'month+1' ) ) && 'mini' != $format ) {
		$time = esc_attr( $_GET['time'] );
	} else {
		$time = esc_attr( $time );
	}

	if ( 'day' == $time ) {
		$format = 'list';
	}

	$format = apply_filters( 'mc_display_format', $format, $args );
	$params = array(
		'name'     => $name, // Not used in my_calendar(); what is/was it for.
		'format'   => $format,
		'category' => $category,
		'above'    => $above,
		'below'    => $below,
		'time'     => $time,
		'ltype'    => $ltype,
		'lvalue'   => $lvalue,
		'author'   => $author,
		'id'       => $id, // Changed when hash is processed.
		'host'     => $host,
		'syear'    => $syear,
		'smonth'   => $smonth,
		'sday'     => $sday,
	);

	// Hash cannot include 'time', 'category', or 'format', since those can be changed by navigation.
	$hash_args = $params;
	unset( $hash_args['time'] );
	unset( $hash_args['category'] );
	unset( $hash_args['format'] );

	$hash         = md5( implode( ',', $hash_args ) );
	$id           = ( ! $id ) ? "mc-$hash" : $id;
	$params['id'] = $id;

	return $params;
}

/**
 * Create calendar output and return.
 *
 * @param array $args Lots of arguments; all shortcode parameters, etc.
 *
 * @return string HTML output of calendar
 */
function my_calendar( $args ) {
	$template = isset( $args['template'] ) ? $args['template'] : '';
	$content  = isset( $args['content'] ) ? $args['content'] : '';
	$source   = isset( $args['source'] ) ? $args['source'] : 'shortcode';
	$site     = ( isset( $args['site'] ) && '' != trim( $args['site'] ) ) ? $args['site'] : false;
	$months   = isset( $args['months'] ) ? $args['months'] : false;

	// Get options before switching sites in multisite environments.
	$list_js_class = ( 0 == get_option( 'mc_list_javascript' ) ) ? 'listjs' : '';
	$grid_js_class = ( 0 == get_option( 'mc_calendar_javascript' ) ) ? 'gridjs' : '';
	$mini_js_class = ( 0 == get_option( 'mc_mini_javascript' ) ) ? 'minijs' : '';
	$ajax_js_class = ( 0 == get_option( 'mc_ajax_javascript' ) ) ? 'ajaxjs' : '';
	$date_format   = ( '' != get_option( 'mc_date_format' ) ) ? get_option( 'mc_date_format' ) : get_option( 'date_format' );
	$start_of_week = ( get_option( 'start_of_week' ) == 1 ) ? 1 : 7; // convert start of week to ISO 8601 (Monday/Sunday).
	$show_weekends = ( get_option( 'mc_show_weekends' ) == 'true' ) ? true : false;
	$skip_holidays = get_option( 'mc_skip_holidays_category' );
	$month_format  = ( get_option( 'mc_month_format' ) == '' ) ? 'F Y' : get_option( 'mc_month_format' );
	$show_months   = apply_filters( 'mc_show_months', get_option( 'mc_show_months' ), $args );
	$caption_text  = ' ' . stripslashes( trim( get_option( 'mc_caption' ) ) );
	$week_format   = ( ! get_option( 'mc_week_format' ) ) ? 'M j, \'y' : get_option( 'mc_week_format' );
	$week_template = ( get_option( 'mc_week_caption' ) != '' ) ? get_option( 'mc_week_caption' ) : 'Week of {date format="M jS"}';
	$day_uri       = get_option( 'mc_open_day_uri' );
	$list_info     = get_option( 'mc_show_list_info' );
	$list_events   = get_option( 'mc_show_list_events' );

	if ( $site ) {
		$site = ( 'global' == $site ) ? BLOG_ID_CURRENT_SITE : $site;
		switch_to_blog( $site );
	}
	my_calendar_check();

	$params = mc_calendar_params( $args );
	$body   = apply_filters( 'mc_before_calendar', '', $params );

	$id         = $params['id'];
	$main_class = ( '' != $id ) ? sanitize_title( $id ) : 'all';
	$cid        = ( isset( $_GET['cid'] ) ) ? esc_attr( strip_tags( $_GET['cid'] ) ) : $main_class;
	$mc_wrapper = "
<div id=\"$id\" class=\"mc-main mcjs $list_js_class $grid_js_class $mini_js_class $ajax_js_class $params[format] $params[time] $main_class\" aria-live='assertive' aria-atomic='true' aria-relevant='additions'>";
	$mc_closer  = '
</div>';

	$date_format = apply_filters( 'mc_date_format', $date_format, $params['format'], $params['time'] );

	if ( isset( $_GET['mc_id'] ) && 'widget' != $source ) {
		// single event, main calendar only.
		$mc_id = ( is_numeric( $_GET['mc_id'] ) ) ? $_GET['mc_id'] : false;
		if ( $mc_id ) {
			$body .= mc_get_event( $mc_id, 'html' );
		}
	} else {
		$end_of_week   = ( 1 == $start_of_week ) ? 7 : 6;
		$start_of_week = ( $show_weekends ) ? $start_of_week : 1;
		$name_days     = mc_name_days( $params['format'] );
		$abbrevs       = array( 'sun', 'mon', 'tues', 'wed', 'thur', 'fri', 'sat' );

		if ( 1 == $start_of_week ) {
			$first       = array_shift( $name_days );
			$afirst      = array_shift( $abbrevs );
			$name_days[] = $first;
			$abbrevs[]   = $afirst;
		}

		$date    = mc_get_current_date( $main_class, $cid, $params );
		$current = $date['current_date'];

		if ( is_numeric( $months ) && $months < 12 && $months > 0 ) {
			$show_months = absint( $months );
		}

		$dates = mc_get_from_to( $show_months, $params, $date );
		$from  = apply_filters( 'mc_from_date', $dates['from'] );
		$to    = apply_filters( 'mc_to_date', $dates['to'] );

		$query       = array(
			'from'     => $from,
			'to'       => $to,
			'category' => $params['category'],
			'ltype'    => $params['ltype'],
			'lvalue'   => $params['lvalue'],
			'author'   => $params['author'],
			'host'     => $params['host'],
			'search'   => '',
			'source'   => 'calendar',
			'site'     => $site,
		);
		$query       = apply_filters( 'mc_calendar_attributes', $query, $params );
		$event_array = my_calendar_events( $query );
		$no_events   = ( empty( $event_array ) ) ? true : false;

		$nav    = mc_generate_calendar_nav( $params, $args['category'], $start_of_week, $show_months, $main_class, $site, $date, $from );
		$top    = $nav['top'];
		$bottom = $nav['bottom'];

		if ( 'day' == $params['time'] ) {
			$body .= "<div class='mcjs " . esc_attr( $params['format'] . ' ' . $params['time'] ) . "'>" . $top;
			$from  = date( 'Y-m-d', $current );
			$to    = date( 'Y-m-d', $current );

			$query  = array(
				'from'     => $from,
				'to'       => $to,
				'category' => $params['category'],
				'ltype'    => $params['ltype'],
				'lvalue'   => $params['lvalue'],
				'author'   => $params['author'],
				'host'     => $params['host'],
				'search'   => '',
				'source'   => 'calendar',
				'site'     => $site,
			);
			$query  = apply_filters( 'mc_grab_events_attributes', $query, $params );
			$events = my_calendar_get_events( $query );
			if ( ! $skip_holidays ) {
				$holidays = array();
			} else {
				$query['category'] = $skip_holidays;
				$query['holidays'] = 'holidays';
				$holidays          = my_calendar_get_events( $query );
			}

			$events_class = mc_events_class( $events, $from );
			$dateclass    = mc_dateclass( $current );
			$mc_events    = '';

			if ( is_array( $events ) && count( $events ) > 0 ) {
				if ( is_array( $holidays ) && count( $holidays ) > 0 ) {
					$mc_events .= my_calendar_draw_events( $holidays, $params, $from, $template );
				} else {
					$mc_events .= my_calendar_draw_events( $events, $params, $from, $template );
				}
			} else {
				$mc_events .= __( 'No events scheduled for today!', 'my-calendar' );
			}

			$hl       = apply_filters( 'mc_heading_level', 'h3', $params['format'], $params['time'], $template );
			$datetime = date_i18n( $date_format, $current );
			$body    .= "
				<$hl class='mc-single'>" . apply_filters( 'mc_heading', $datetime, $params['format'], $params['time'] ) . "</$hl>" . '
				<div id="mc-day" class="' . $dateclass . ' ' . $events_class . '">' . "$mc_events\n</div>
			</div>";
		} else {
			// If showing multiple months, figure out how far we're going.
			$months       = ( 'week' == $params['time'] ) ? 1 : $show_months;
			$through_date = mktime( 0, 0, 0, $date['month'] + ( $months - 1 ), $date['day'], $date['year'] );
			if ( 'month+1' == $params['time'] ) {
				$current_header = date_i18n( $month_format, strtotime( '+1 month', $current ) );
			} else {
				$current_header = date_i18n( $month_format, $current );
			}
			$current_month_header = ( date( 'Y', $current ) == date( 'Y', $through_date ) ) ? date_i18n( 'F', $current ) : date_i18n( 'F Y', $current );
			$through_month_header = date_i18n( $month_format, $through_date );
			$values               = array( 'date' => date( 'Y-m-d', $current ) );

			// Add the calendar table and heading.
			$body .= $top;
			if ( 'calendar' == $params['format'] || 'mini' == $params['format'] ) {
				$table           = apply_filters( 'mc_grid_wrapper', 'table', $params['format'] );
				$body           .= "\n<$table class=\"my-calendar-table\">\n";
				$week_caption    = mc_draw_template( $values, stripslashes( $week_template ) );
				$caption_heading = ( 'week' != $params['time'] ) ? $current_header . $caption_text : $week_caption . $caption_text;
				$caption         = apply_filters( 'mc_grid_caption', 'caption', $params['format'] );
				$body           .= "<$caption class=\"heading my-calendar-$params[time]\">" . $caption_heading . "</$caption>\n";
			} else {
				// Determine which header text to show depending on number of months displayed.
				if ( 'week' != $params['time'] && 'day' != $params['time'] ) {
					$list_heading = ( $months <= 1 ) ? $current_header . $caption_text . "\n" : $current_month_header . '&ndash;' . $through_month_header . $caption_text;
					// Translators: time period displayed.
					$list_heading = sprintf( __( 'Events in %s', 'my-calendar' ), $list_heading );
				} else {
					$list_heading = mc_draw_template( $values, stripslashes( $week_template ) );
				}
				$h2           = apply_filters( 'mc_list_header_level', 'h2' );
				$list_heading = apply_filters( 'mc_list_heading', $list_heading, $current_month_header, $through_month_header, $caption_text );
				$body        .= "<$h2 class=\"heading my-calendar-$params[time]\">$list_heading</$h2>\n";
			}

			$tr       = apply_filters( 'mc_grid_week_wrapper', 'tr', $params['format'] );
			$th       = apply_filters( 'mc_grid_header_wrapper', 'th', $params['format'] );
			$close_th = ( 'th' == $th ) ? 'th' : $th;
			$th      .= ( 'th' == $th ) ? ' scope="col"' : '';

			// If in a calendar format, print the headings of the days of the week.
			if ( 'list' == $params['format'] ) {
				$body .= "<ul id='list-$id' class='mc-list'>";
			} else {
				$body .= ( 'tr' == $tr ) ? "<thead>\n" : '<div class="mc-table-body">';
				$body .= "<$tr class='mc-row'>\n";
				if ( apply_filters( 'mc_show_week_number', false, $args ) ) {
					$body .= "<th class='mc-week-number'>" . __( 'Week', 'my-calendar' ) . '</th>';
				}
				for ( $i = 0; $i <= 6; $i ++ ) {
					if ( 0 == $start_of_week ) {
						$class = ( $i < 6 && $i > 0 ) ? 'day-heading' : 'weekend-heading';
					} else {
						$class = ( $i < 5 ) ? 'day-heading' : 'weekend-heading';
					}
					$dayclass = sanitize_html_class( $abbrevs[ $i ] );
					if ( ( 'weekend-heading' == $class && $show_weekends ) || 'weekend-heading' != $class ) {
						$body .= "<$th class='$class $dayclass'>" . $name_days[ $i ] . "</$close_th>\n";
					}
				}
				$body .= "\n</$tr>\n";
				$body .= ( 'tr' == $tr ) ? "</thead>\n<tbody>" : '';
			}
			$odd = 'odd';

			$show_all = apply_filters( 'mc_all_list_dates', false, $args );
			if ( $no_events && 'list' == $params['format'] && false == $show_all ) {
				// If there are no events in list format, just display that info.
				$no_events = ( '' == $content ) ? __( 'There are no events scheduled during this period.', 'my-calendar' ) : $content;
				$body     .= "<li class='mc-events no-events'>$no_events</li>";
			} else {
				$start = strtotime( $from );
				$end   = strtotime( $to );
				do {
					$date_is    = date( 'Y-m-d', $start );
					$is_weekend = ( date( 'N', $start ) < 6 ) ? false : true;
					if ( $show_weekends || ( ! $show_weekends && ! $is_weekend ) ) {
						if ( date( 'N', $start ) == $start_of_week && 'list' != $params['format'] ) {
							$body .= "<$tr class='mc-row'>";
						}
						$events          = ( isset( $event_array[ $date_is ] ) ) ? $event_array[ $date_is ] : array();
						$week_header     = date_i18n( $week_format, $start );
						$thisday_heading = ( 'week' == $params['time'] ) ? "<small>$week_header</small>" : date( 'j', $start );

						// Generate event classes & attributes.
						$events_class = mc_events_class( $events, $date_is );
						$monthclass   = ( date( 'n', $start ) == $date['month'] || 'month' != $params['time'] ) ? '' : 'nextmonth';
						$dateclass    = mc_dateclass( $start );
						$ariacurrent  = ( 'current-day' == $dateclass ) ? ' aria-current="date"' : '';

						$td    = apply_filters( 'mc_grid_day_wrapper', 'td', $params['format'] );
						$body .= mc_show_week_number( $events, $args, $params['format'], $td, $start );

						if ( ! empty( $events ) ) {
							$hide_nextmonth = apply_filters( 'mc_hide_nextmonth', false );
							if ( true == $hide_nextmonth && 'nextmonth' == $monthclass ) {
								$event_output = ' ';
							} else {
								if ( 'mini' == $params['format'] && 'false' != $day_uri ) {
									$event_output = ' ';
								} else {
									$event_output = my_calendar_draw_events( $events, $params, $date_is, $template );
								}
							}
							if ( true === $event_output ) {
								$event_output = ' ';
							}
							if ( 'mini' == $params['format'] && '' != $event_output ) {
								$link    = mc_build_mini_url( $start, $params['category'], $events, $args, $date );
								$element = "a href='$link'";
								$close   = 'a';
								$trigger = 'trigger';
							} else {
								$element = 'span';
								$close   = 'span';
								$trigger = '';
							}
							// set up events.
							if ( ( $is_weekend && $show_weekends ) || ! $is_weekend ) {
								$weekend_class = ( $is_weekend ) ? 'weekend' : '';
								if ( 'list' == $params['format'] ) {
									if ( 'true' == $list_info ) {
										$title = ' - ' . mc_wrap_title( "<span class='mc-list-details select-event'>" . mc_list_title( $events ) . '</span>' );
									} elseif ( 'true' == $list_events ) {
										$title = ' - ' . mc_wrap_title( "<span class='mc-list-details all-events'>" . mc_list_titles( $events ) . '</span>' );
									} else {
										$title = '';
									}
									if ( '' != $event_output ) {
										$body .= "<li id='$params[format]-$date_is' $ariacurrent class='mc-events $dateclass $events_class $odd'><strong class=\"event-date\">" . mc_wrap_title( date_i18n( $date_format, $start ) ) . "$title</strong>" . $event_output . '</li>';
										$odd   = ( 'odd' == $odd ) ? 'even' : 'odd';
									}
								} else {
									$body .= "<$td id='$params[format]-$date_is' $ariacurrent class='$dateclass $weekend_class $monthclass $events_class day-with-date'>" . "<$element class='mc-date $trigger'><span aria-hidden='true'>$thisday_heading</span><span class='screen-reader-text'>" . date_i18n( $date_format, strtotime( $date_is ) ) . "</span></$close>" . $event_output . "</$td>\n";
								}
							}
						} else {
							// If there are no events on this date within current params.
							if ( 'list' != $params['format'] ) {
								$weekend_class = ( $is_weekend ) ? 'weekend' : '';
								$body         .= "<$td $ariacurrent class='no-events $dateclass $weekend_class $monthclass $events_class day-with-date'><span class='mc-date no-events'><span aria-hidden='true'>$thisday_heading</span><span class='screen-reader-text'>" . date_i18n( $date_format, strtotime( $date_is ) ) . "</span></span></$td>\n";
							} else {
								if ( true == $show_all ) {
									$body .= "<li id='$params[format]-$date_is' $ariacurrent class='no-events $dateclass $events_class $odd'><strong class=\"event-date\">" . mc_wrap_title( date_i18n( $date_format, $start ) ) . '</strong></li>';
									$odd   = ( 'odd' == $odd ) ? 'even' : 'odd';
								}
							}
						}

						if ( date( 'N', $start ) == $end_of_week && 'list' != $params['format'] ) {
							$body .= "</$tr>\n"; // End of 'is beginning of week'.
						}
					}
					$start = strtotime( '+1 day', $start );

				} while ( $start <= $end );
			}

			$table = apply_filters( 'mc_grid_wrapper', 'table', $params['format'] );
			$end   = ( 'table' == $table ) ? "\n</tbody>\n</table>" : "</div></$table>";
			$body .= ( 'list' == $params['format'] ) ? "\n</ul>" : $end;
		}
		$body .= $bottom;
	}
	// The actual printing is done by the shortcode function.
	$body .= apply_filters( 'mc_after_calendar', '', $args );

	if ( $site ) {
		restore_current_blog();
	}

	return $mc_wrapper . apply_filters( 'my_calendar_body', $body ) . $mc_closer;
}


/**
 * Get from and to values for current view
 *
 * @param int   $show_months List view parameter.
 * @param array $params Calendar view parameters.
 * @param array $date Current date viewed.
 *
 * @return array from & to dates
 */
function mc_get_from_to( $show_months, $params, $date ) {
	$format = $params['format'];
	$time   = $params['time'];
	// The value is total months to show; need additional months to show.
	$num     = $show_months - 1;
	$c_month = $date['month'];
	$c_year  = $date['year'];

	// Grid calendar can't show multiple months.
	if ( 'list' == $format && 'week' != $time ) {
		if ( $num > 0 && 'day' != $time && 'week' != $time ) {
			if ( 'month+1' == $time ) {
				$from = date( 'Y-m-d', strtotime( '+1 month', mktime( 0, 0, 0, $c_month, 1, $c_year ) ) );
				$next = strtotime( "+$num months", strtotime( '+1 month', mktime( 0, 0, 0, $c_month, 1, $c_year ) ) );
			} else {
				$from = date( 'Y-m-d', mktime( 0, 0, 0, $c_month, 1, $c_year ) );
				$next = strtotime( "+$num months", mktime( 0, 0, 0, $c_month, 1, $c_year ) );
			}
			$last = date( 't', $next );
			$to   = date( 'Y-m', $next ) . '-' . $last;
		} else {
			$from = date( 'Y-m-d', mktime( 0, 0, 0, $c_month, 1, $c_year ) );
			$to   = date( 'Y-m-d', mktime( 0, 0, 0, $c_month, date( 't', mktime( 0, 0, 0, $c_month, 1, $c_year ) ), $c_year ) );
		}
		$dates = array(
			'from' => $from,
			'to'   => $to,
		);
	} else {
		$dates = mc_date_array( $date['current_date'], $time );
	}

	return $dates;
}

/**
 * Create navigation elements used in My Calendar main view
 *
 * @param array  $params Calendar parameters (modified).
 * @param int    $cat Original category from calendar args.
 * @param int    $start_of_week First day of week.
 * @param int    $show_months num months to show (modified).
 * @param string $main_class Class/ID.
 * @param int    $site Which site in multisite.
 * @param string $date current date.
 * @param string $from date view started from.
 *
 * @return array of calendar nav for top & bottom
 */
function mc_generate_calendar_nav( $params, $cat, $start_of_week, $show_months, $main_class, $site, $date, $from ) {
	$format   = $params['format'];
	$category = $params['category'];
	$above    = $params['above'];
	$below    = $params['below'];
	$time     = $params['time'];
	$ltype    = $params['ltype'];
	$lvalue   = $params['lvalue'];

	if ( 'none' == $above && 'none' == $below ) {
		return array(
			'bottom' => '',
			'top'    => '',
		);
	}

	// Fallback values.
	$mc_toporder    = array( 'nav', 'toggle', 'jump', 'print', 'timeframe' );
	$mc_bottomorder = array( 'key', 'feeds' );

	if ( 'none' == $above ) {
		$mc_toporder = array();
	} else {
		// Set up above-calendar order of fields.
		if ( '' != get_option( 'mc_topnav' ) ) {
			$mc_toporder = array_map( 'trim', explode( ',', get_option( 'mc_topnav' ) ) );
		}

		if ( '' != $above ) {
			$mc_toporder = array_map( 'trim', explode( ',', $above ) );
		}
	}

	if ( 'none' == $below ) {
		$mc_bottomorder = array();
	} else {
		if ( '' != get_option( 'mc_bottomnav' ) ) {
			$mc_bottomorder = array_map( 'trim', explode( ',', get_option( 'mc_bottomnav' ) ) );
		}

		if ( '' != $below ) {
			$mc_bottomorder = array_map( 'trim', explode( ',', $below ) );
		}
	}

	$aboves = $mc_toporder;
	$belows = $mc_bottomorder;
	$used   = array_merge( $aboves, $belows );

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
		'href'   => urlencode( mc_get_current_url() ),
	);

	$subtract = array();
	if ( '' == $ltype ) {
		$subtract[] = 'ltype';
		unset( $add['ltype'] );
	}

	if ( '' == $lvalue ) {
		$subtract[] = 'lvalue';
		unset( $add['lvalue'] );
	}

	if ( 'all' == $category ) {
		$subtract[] = 'mcat';
		unset( $add['mcat'] );
	}

	// Set up print link.
	if ( in_array( 'print', $used ) ) {
		$print_add    = array_merge( $add, array( 'cid' => 'mc-print-view' ) );
		$mc_print_url = mc_build_url( $print_add, $subtract, home_url() );
		$print        = "<div class='mc-print'><a href='$mc_print_url'>" . __( 'Print<span class="maybe-hide"> View</span>', 'my-calendar' ) . '</a></div>';
	}

	// Set up format toggle.
	$toggle = ( in_array( 'toggle', $used ) ) ? mc_format_toggle( $format, 'yes', $time ) : '';

	// Set up time toggle.
	if ( in_array( 'timeframe', $used ) ) {
		$timeframe = mc_time_toggle( $format, $time, $date['month'], $date['year'], $date['current_date'], $start_of_week, $from );
	}

	// Set up category key.
	$key = ( in_array( 'key', $used ) ) ? mc_category_key( $cat ) : '';

	// Set up navigation links.
	if ( in_array( 'nav', $used ) ) {
		$nav = mc_nav( $date, $format, $time, $show_months, $main_class );
	}

	// Set up rss feeds.
	if ( in_array( 'feeds', $used ) ) {
		$feeds = mc_sub_links( $subtract );
	}

	// Set up exports.
	if ( in_array( 'exports', $used ) ) {
		$ical_m    = ( isset( $_GET['month'] ) ) ? (int) $_GET['month'] : date( 'n' );
		$ical_y    = ( isset( $_GET['yr'] ) ) ? (int) $_GET['yr'] : date( 'Y' );
		$next_link = my_calendar_next_link( $date, $format, $time, $show_months );
		$exports   = mc_export_links( $ical_y, $ical_m, $next_link, $add, $subtract );
	}

	// Set up date switcher.
	if ( in_array( 'jump', $used ) ) {
		$jump = mc_date_switcher( $format, $main_class, $time, $date );
	}

	foreach ( $mc_toporder as $value ) {
		if ( 'none' != $value && in_array( $value, $used ) ) {
			$value      = trim( $value );
			$mc_topnav .= ${$value};
		}
	}

	foreach ( $mc_bottomorder as $value ) {
		if ( 'none' != $value && 'stop' != $value && in_array( $value, $used ) ) {
			$value         = trim( $value );
			$mc_bottomnav .= ${$value};
		}
	}

	if ( '' != $mc_topnav ) {
		$mc_topnav = '<div class="my-calendar-header">' . $mc_topnav . '</div>';
	}

	if ( '' != $mc_bottomnav ) {
		$mc_bottomnav = "<div class='mc_bottomnav my-calendar-footer'>$mc_bottomnav</div>";
	}

	return array(
		'bottom' => $mc_bottomnav,
		'top'    => $mc_topnav,
	);
}

/**
 * Arguments to show the week number in calendar views.
 *
 * @param array  $events array of event objects.
 * @param array  $args Calendar arguments.
 * @param string $format current view format.
 * @param string $td HTML element in use for cells.
 * @param string $start Current date.
 *
 * @return string
 */
function mc_show_week_number( $events, $args, $format, $td, $start ) {
	$body = '';
	static $weeknumber;
	if ( apply_filters( 'mc_show_week_number', false, $args ) ) {
		if ( ( date( 'N', $start ) == $start_of_week || strtotime( $from ) == $start || ! $week_number_shown ) && $weeknumber != date( 'W', $start) ) {
			$week_number_shown = false;
			if ( 'list' != $format ) {
				$weeknumber        = date( 'W', $start );
				$body              = "<$td class='week_number'>$weeknumber</$td>";
				$week_number_shown = true;
			}
			if ( 'list' == $format && ! empty( $events ) && ! $week_number_shown ) {
				$weeknumber        = date( 'W', $start );
				$body              = "<li class='mc-week-number'><span class='week-number-text'>" . __( 'Week', 'my-calendar' ) . "</span> <span class='week-number-number'>$weeknumber</span></li>";
				$week_number_shown = true;
			}
		}
	}

	return $body;
}

/**
 * Build the URL for use in the mini calendar
 *
 * @param string $start link date.
 * @param int    $category current category.
 * @param array  $events array of event objects.
 * @param array  $args calendar view parameters.
 * @param string $date view date.
 *
 * @return string URL
 */
function mc_build_mini_url( $start, $category, $events, $args, $date ) {
	$open_day_uri = get_option( 'mc_open_day_uri' );
	if ( 'true' == $open_day_uri || 'false' == $open_day_uri ) {
		// Yes, this is weird. it's from some old settings...
		$target = array(
			'yr'    => date( 'Y', $start ),
			'month' => date( 'm', $start ),
			'dy'    => date( 'j', $start ),
			'time'  => 'day',
		);
		if ( '' != $category ) {
			$target['mcat'] = $category;
		}
		$day_url = mc_build_url( $target, array( 'month', 'dy', 'yr', 'ltype', 'loc', 'mcat', 'cid', 'mc_id' ), apply_filters( 'mc_modify_day_uri', mc_get_uri( reset( $events ), $args ) ) );
		$link    = ( '' != $day_url ) ? $day_url : '#';
	} else {
		$mini_uri = get_option( 'mc_mini_uri' );
		$atype    = str_replace( 'anchor', '', $open_day_uri ); // List or grid.
		$ad       = str_pad( date( 'j', $start ), 2, '0', STR_PAD_LEFT ); // Need to match format in ID.
		$am       = str_pad( $date['month'], 2, '0', STR_PAD_LEFT );
		$date_url = mc_build_url( array(
			'yr'    => $date['year'],
			'month' => $date['month'],
			'dy'    => date( 'j', $start ),
		), array( 'month', 'dy', 'yr', 'ltype', 'loc', 'mcat', 'cid', 'mc_id' ), $mini_uri );
		$link     = esc_url( ( '' != $mini_uri ) ? $date_url . '#' . $atype . '-' . $date['year'] . '-' . $am . '-' . $ad : '#' );
	}

	return $link;
}

add_filter( 'mc_display_format', 'mc_convert_format', 10, 2 );
/**
 * Switch format for display depeding on environment.
 *
 * @param string $format current view.
 * @param array  $params Calendar view args.
 *
 * @return string new format.
 */
function mc_convert_format( $format, $params ) {
	if ( 'true' == get_option( 'mc_convert' ) ) {
		$format = ( mc_is_mobile() && 'calendar' == $format ) ? 'list' : $format;
	} elseif ( 'mini' == get_option( 'mc_convert' ) ) {
		$format = ( mc_is_mobile() ) ? 'mini' : $format;
	}

	return $format;
}

/**
 * Generate calendar navigation
 *
 * @param string $date Current date.
 * @param string $format Current format.
 * @param string $time Current time view.
 * @param int    $show_months Num months to show.
 * @param string $class view ID.
 *
 * @return string prev/next nav.
 */
function mc_nav( $date, $format, $time, $show_months, $class ) {
	$prev      = my_calendar_prev_link( $date, $format, $time, $show_months );
	$next      = my_calendar_next_link( $date, $format, $time, $show_months );
	$prev_link = mc_build_url( array(
		'yr'    => $prev['yr'],
		'month' => $prev['month'],
		'dy'    => $prev['day'],
		'cid'   => $class,
	), array() );
	$next_link = mc_build_url( array(
		'yr'    => $next['yr'],
		'month' => $next['month'],
		'dy'    => $next['day'],
		'cid'   => $class,
	), array() );

	$prev_link = apply_filters( 'mc_previous_link', '<li class="my-calendar-prev"><a href="' . $prev_link . '" rel="nofollow" class="mcajax">' . $prev['label'] . '</a></li>', $prev );
	$next_link = apply_filters( 'mc_next_link', '<li class="my-calendar-next"><a href="' . $next_link . '" rel="nofollow" class="mcajax">' . $next['label'] . '</a></li>', $next );

	$nav = '
		<div class="my-calendar-nav">
			<ul>
				' . $prev_link . $next_link . '
			</ul>
		</div>';

	return $nav;
}

/**
 * Get the current date for display of calendar
 *
 * @param string $main_class Main calendar ID/class.
 * @param string $cid Main calendar ID.
 * @param array  $params Array of calendar arguments.
 *
 * @return array
 */
function mc_get_current_date( $main_class, $cid, $params ) {
	$timestamp = current_time( 'timestamp' );
	$time      = $params['time'];
	$smonth    = $params['smonth'];
	$syear     = $params['syear'];
	$sday      = $params['sday'];
	$c_m       = 0;
	if ( isset( $_GET['dy'] ) && $main_class == $cid && ( 'day' == $time || 'week' == $time ) ) {
		$c_day = (int) $_GET['dy'];
	} else {
		if ( 'week' == $time ) {
			$dm    = mc_first_day_of_week();
			$c_day = $dm[0];
			$c_m   = $dm[1];
		} elseif ( 'day' == $time ) {
			$c_day = date( 'd', $timestamp );
		} else {
			$c_day = 1;
		}
	}
	if ( isset( $_GET['month'] ) && $main_class == $cid ) {
		$c_month = (int) $_GET['month'];
		if ( ! isset( $_GET['dy'] ) ) {
			$c_day = 1;
		}
	} else {
		$xnow    = date( 'Y-m-d', $timestamp );
		$c_month = ( 0 == $c_m ) ? date( 'm', $timestamp ) : date( 'm', strtotime( $xnow . ' -1 month' ) );
	}

	$is_start_of_week = ( get_option( 'start_of_week' ) == date( 'N', $timestamp ) ) ? true : false;
	if ( isset( $_GET['yr'] ) && $main_class == $cid ) {
		$c_year = (int) $_GET['yr'];
	} else {
		if ( 'week' == $time && ! isset( $_GET['dy'] ) ) {
			if ( $is_start_of_week ) {
				$c_year = ( date( 'Y', current_time( 'timestamp' ) ) );
			} else {
				$current_year = date( 'Y', current_time( 'timestamp' ) );
				$c_year       = ( 0 == $dm[1] ) ? $current_year : false;
				if ( ! $c_year ) {
					$c_year = ( date( 'Y', strtotime( '-1 month' ) ) == $current_year ) ? $current_year : $current_year - 1;
				}
			}
		} else {
			$c_year = ( date( 'Y', current_time( 'timestamp' ) ) );
		}
	}
	// Years get funny if we exceed 3000, so we use this check.
	if ( ! ( $c_year <= 3000 && $c_year >= 0 ) ) {
		// No valid year causes the calendar to default to today.
		$c_year  = date( 'Y', $timestamp );
		$c_month = date( 'm', $timestamp );
		$c_day   = date( 'd', $timestamp );
	}
	if ( ! ( isset( $_GET['yr'] ) || isset( $_GET['month'] ) || isset( $_GET['dy'] ) ) ) {
		// Month/year based on shortcode.
		$shortcode_month = ( false != $smonth ) ? $smonth : $c_month;
		$shortcode_year  = ( false != $syear ) ? $syear : $c_year;
		$shortcode_day   = ( false != $sday ) ? $sday : $c_day;
		// Override with filters.
		$c_year  = apply_filters( 'mc_filter_year', $shortcode_year, $params );
		$c_month = apply_filters( 'mc_filter_month', $shortcode_month, $params );
		$c_day   = apply_filters( 'mc_filter_day', $shortcode_day, $params );
	}
	$c_day   = ( 0 == $c_day ) ? 1 : $c_day; // c_day can't equal 0.
	$current = mktime( 0, 0, 0, $c_month, $c_day, $c_year );
	$c_month = str_pad( $c_month, 2, '0', STR_PAD_LEFT );

	return array(
		'day'          => $c_day,
		'month'        => $c_month,
		'year'         => $c_year,
		'current_date' => $current,
	);
}

add_filter( 'my_calendar_body', 'mc_run_shortcodes', 10, 1 );
/**
 * Process shortcodes on the final rendered calendar instead of each individual case.
 * Means this runs once instead of potentially hundreds of times.
 *
 * @param string $content Fully executed calendar body.
 *
 * @return string Calendar body with shortcodes processed
 */
function mc_run_shortcodes( $content ) {
	$content = ( 'true' == get_option( 'mc_process_shortcodes' ) ) ? do_shortcode( $content ) : $content;

	return $content;
}

/**
 * Set up button wrapping event title
 *
 * @param string $title Event title.
 *
 * @return string title with wrapper if appropriate
 */
function mc_wrap_title( $title ) {
	if ( 1 != get_option( 'mc_list_javascript' ) ) {
		$is_anchor       = '<button type="button" class="mc-text-button">';
		$is_close_anchor = '</button>';
	} else {
		$is_anchor       = '';
		$is_close_anchor = '';
	}

	return $is_anchor . $title . $is_close_anchor;
}

/**
 * Show the list of categories on the calendar
 *
 * @param int $category the currently selected category.
 *
 * @return string HTML for category key
 */
function mc_category_key( $category ) {
	global $wpdb;
	$url  = plugin_dir_url( __FILE__ );
	$mcdb = $wpdb;
	if ( 'true' == get_option( 'mc_remote' ) && function_exists( 'mc_remote_db' ) ) {
		$mcdb = mc_remote_db();
	}
	$key             = '';
	$cat_limit       = mc_select_category( $category, 'all', 'category' );
	$select_category = ( isset( $cat_limit[1] ) ) ? $cat_limit[1] : '';

	$sql        = 'SELECT * FROM ' . my_calendar_categories_table() . " $select_category ORDER BY category_name ASC";
	$categories = $mcdb->get_results( $sql );
	$key       .= '<div class="category-key"><h3>' . __( 'Categories', 'my-calendar' ) . "</h3>\n<ul>\n";
	$path       = ( mc_is_custom_icon() ) ? str_replace( 'my-calendar', 'my-calendar-custom', $url ) : plugins_url( 'images/icons', __FILE__ ) . '/';

	foreach ( $categories as $cat ) {
		$class = '';
		// Don't display private categories to public users.
		if ( mc_private_event( $cat ) ) {
			continue;
		}
		$hex   = ( 0 !== strpos( $cat->category_color, '#' ) ) ? '#' : '';
		$class = mc_category_class( $cat, '' );
		if ( isset( $_GET['mcat'] ) && $_GET['mcat'] == $cat->category_id || $category == $cat->category_id ) {
			$class .= ' current';
		}
		if ( 1 == $cat->category_private ) {
			$class .= ' private';
		}
		$cat_name = mc_kses_post( stripcslashes( $cat->category_name ) );
		$url      = mc_build_url( array( 'mcat' => $cat->category_id ), array( 'mcat' ) );
		if ( '' != $cat->category_icon && 'true' != get_option( 'mc_hide_icons' ) ) {
			$key .= '<li class="cat_' . $class . '"><a href="' . esc_url( $url ) . '" class="mcajax"><span class="category-color-sample"><img src="' . $path . $cat->category_icon . '" alt="" style="background:' . $hex . $cat->category_color . ';" /></span>' . $cat_name . '</a></li>';
		} else {
			$key .= '<li class="cat_' . $class . '"><a href="' . esc_url( $url ) . '" class="mcajax"><span class="category-color-sample no-icon" style="background:' . $hex . $cat->category_color . ';"> &nbsp; </span>' . $cat_name . '</a></li>';
		}
	}
	if ( isset( $_GET['mcat'] ) ) {
		$key .= "<li class='all-categories'><a href='" . esc_url( mc_build_url( array(), array( 'mcat' ), mc_get_current_url() ) ) . "' class='mcajax'>" . apply_filters( 'mc_text_all_categories', __( 'All Categories', 'my-calendar' ) ) . '</a></li>';
	}
	$key .= '</ul></div>';
	$key  = apply_filters( 'mc_category_key', $key, $categories );

	return $key;
}

/**
 * Set up RSS links for calendar
 *
 * @param array $subtract Array of data to remove.
 *
 * @return string HTML output for RSS links
 */
function mc_sub_links( $subtract ) {

	$feed    = get_feed_link( 'my-calendar-rss' );
	$google  = get_feed_link( 'my-calendar-google' );
	$outlook = get_feed_link( 'my-calendar-outlook' );

	$rss         = "\n	<li class='rss'><a href='" . esc_url( $feed ) . "'>" . __( 'RSS', 'my-calendar' ) . '</a></li>';
	$sub_google  = "\n	<li class='ics google'><a href='" . esc_url( $google ) . "'>" . __( 'Google', 'my-calendar' ) . '</a></li>';
	$sub_outlook = "\n	<li class='ics outlook'><a href='" . esc_url( $outlook ) . "'>" . __( 'Outlook', 'my-calendar' ) . '</a></li>';

	$output = "\n
<div class='mc-export'>
	<ul>$rss$sub_google$sub_outlook</ul>
</div>\n";

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

	$add['export'] = 'google';
	$ics           = mc_build_url( $add, $subtract, get_feed_link( 'my-calendar-ics' ) );
	$add['export'] = 'outlook';
	$ics2          = mc_build_url( $add, $subtract, get_feed_link( 'my-calendar-ics' ) );

	$google  = "\n <li class='ics google'><a href='" . $ics . "'>" . __( 'Google', 'my-calendar' ) . '</a></li>';
	$outlook = "\n <li class='ics outlook'><a href='" . $ics2 . "'>" . __( 'Outlook', 'my-calendar' ) . '</a></li>';

	$output = "\n
<div class='mc-export'>
	<ul>$google$outlook</ul>
</div>\n";

	return $output;
}

/**
 * Set up next link based on current view
 *
 * @param array  $date Current date of view.
 * @param string $format of calendar.
 * @param string $time current time view.
 * @param int    $months number of months shown in list views.
 *
 * @return string array of parameters for link
 */
function my_calendar_next_link( $date, $format, $time = 'month', $months = 1 ) {
	$cur_year  = $date['year'];
	$cur_month = $date['month'];
	$cur_day   = $date['day'];

	$next_year   = $cur_year + 1;
	$mc_next     = get_option( 'mc_next_events' );
	$next_events = ( '' == $mc_next ) ? '<span class="maybe-hide">' . __( 'Next', 'my-calendar' ) . '</span>' : stripslashes( $mc_next );
	if ( $months <= 1 || 'list' != $format ) {
		if ( 12 == $cur_month ) {
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
	if ( $yr != $cur_year ) {
		$format = 'F, Y';
	} else {
		$format = 'F';
	}
	$date = date_i18n( $format, mktime( 0, 0, 0, $month, 1, $yr ) );
	if ( 'week' == $time ) {
		$nextdate = strtotime( "$cur_year-$cur_month-$cur_day" . '+ 7 days' );
		$day      = date( 'd', $nextdate );
		$yr       = date( 'Y', $nextdate );
		$month    = date( 'm', $nextdate );
		if ( $yr != $cur_year ) {
			$format = 'F j, Y';
		} else {
			$format = 'F j';
		}
		// Translators: Current formatted date.
		$date = sprintf( __( 'Week of %s', 'my-calendar' ), date_i18n( $format, mktime( 0, 0, 0, $month, $day, $yr ) ) );
	}
	if ( 'day' == $time ) {
		$nextdate = strtotime( "$cur_year-$cur_month-$cur_day" . '+ 1 days' );
		$day      = date( 'd', $nextdate );
		$yr       = date( 'Y', $nextdate );
		$month    = date( 'm', $nextdate );
		if ( $yr != $cur_year ) {
			$format = 'F j, Y';
		} else {
			$format = 'F j';
		}
		$date = date_i18n( $format, mktime( 0, 0, 0, $month, $day, $yr ) );
	}
	$next_events = str_replace( '{date}', $date, $next_events );
	$output      = array(
		'month' => $month,
		'yr'    => $yr,
		'day'   => $day,
		'label' => $next_events,
	);

	return $output;
}

/**
 * Set up prev link based on current view
 *
 * @param array  $date Current date of view.
 * @param string $format of calendar.
 * @param string $time current time view.
 * @param int    $months number of months shown in list views.
 *
 * @return string array of parameters for link
 */
function my_calendar_prev_link( $date, $format, $time = 'month', $months = 1 ) {
	$cur_year  = $date['year'];
	$cur_month = $date['month'];
	$cur_day   = $date['day'];

	$last_year       = $cur_year - 1;
	$mc_previous     = get_option( 'mc_previous_events' );
	$previous_events = ( '' == $mc_previous ) ? '<span class="maybe-hide">' . __( 'Previous', 'my-calendar' ) . '</span>' : stripslashes( $mc_previous );
	if ( $months <= 1 || 'list' != $format ) {
		if ( 1 == $cur_month ) {
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
	if ( $yr != $cur_year ) {
		$format = 'F, Y';
	} else {
		$format = 'F';
	}
	$date = date_i18n( $format, mktime( 0, 0, 0, $month, 1, $yr ) );
	$day  = '';
	if ( 'week' == $time ) {
		$prevdate = strtotime( "$cur_year-$cur_month-$cur_day" . '- 7 days' );
		$day      = date( 'd', $prevdate );
		$yr       = date( 'Y', $prevdate );
		$month    = date( 'm', $prevdate );
		if ( $yr != $cur_year ) {
			$format = 'F j, Y';
		} else {
			$format = 'F j';
		}
		$date = __( 'Week of ', 'my-calendar' ) . date_i18n( $format, mktime( 0, 0, 0, $month, $day, $yr ) );
	}
	if ( 'day' == $time ) {
		$prevdate = strtotime( "$cur_year-$cur_month-$cur_day" . '- 1 days' );
		$day      = date( 'd', $prevdate );
		$yr       = date( 'Y', $prevdate );
		$month    = date( 'm', $prevdate );
		if ( $yr != $cur_year ) {
			$format = 'F j, Y';
		} else {
			$format = 'F j';
		}
		$date = date_i18n( $format, mktime( 0, 0, 0, $month, $day, $yr ) );
	}
	$previous_events = str_replace( '{date}', $date, $previous_events );
	$output          = array(
		'month' => $month,
		'yr'    => $yr,
		'day'   => $day,
		'label' => $previous_events,
	);

	return $output;
}

/**
 * Generate filters form to limit calendar events.
 *
 * @param array  $args can include 'categories', 'locations' and 'access' to define individual filters.
 * @param string $target_url Where to send queries.
 * @param string $ltype Which type of location data to show in form.
 *
 * @return string HTML output of form
 */
function mc_filters( $args, $target_url, $ltype ) {
	if ( ! is_array( $args ) ) {
		$fields = explode( ',', $args );
	} else {
		$fields = $args;
	}
	if ( empty( $fields ) ) {
		return;
	}
	$return = false;

	$current_url = mc_get_uri();
	$current_url = ( '' != $target_url && _mc_is_url( $target_url ) ) ? $target_url : $current_url;
	$form        = "
	<div id='mc_filters'>
		<form action='" . $current_url . "' method='get'>\n";
	$qsa         = array();
	if ( isset( $_SERVER['QUERY_STRING'] ) ) {
		parse_str( $_SERVER['QUERY_STRING'], $qsa );
	}
	if ( ! isset( $_GET['cid'] ) ) {
		$form .= '<input type="hidden" name="cid" value="all" />';
	}
	foreach ( $qsa as $name => $argument ) {
		$name     = esc_attr( strip_tags( $name ) );
		$argument = esc_attr( strip_tags( $argument ) );
		if ( ! ( 'access' == $name || 'mcat' == $name || 'loc' == $name || 'ltype' == $name || 'mc_id' == $name ) ) {
			$form .= '<input type="hidden" name="' . $name . '" value="' . $argument . '" />' . "\n";
		}
	}
	foreach ( $fields as $show ) {
		$show = trim( $show );
		switch ( $show ) {
			case 'categories':
				$form  .= my_calendar_categories_list( 'form', 'public', 'group' );
				$return = true;
				break;
			case 'locations':
				$form  .= my_calendar_locations_list( 'form', $ltype, 'group' );
				$return = true;
				break;
			case 'access':
				$form  .= mc_access_list( 'form', 'group' );
				$return = true;
				break;
		}
	}
	$form .= '<p><input type="submit" value="' . esc_attr( __( 'Filter Events', 'my-calendar' ) ) . '" /></p>
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
 * @param string $group single or multiple.
 * @param string $target_url Where to post form to.
 *
 * @return string HTML
 */
function my_calendar_categories_list( $show = 'list', $context = 'public', $group = 'single', $target_url = '' ) {
	global $wpdb;
	$mcdb = $wpdb;

	if ( 'true' == get_option( 'mc_remote' ) && function_exists( 'mc_remote_db' ) ) {
		$mcdb = mc_remote_db();
	}

	$output      = '';
	$current_url = mc_get_uri();
	$current_url = ( '' != $target_url && _mc_is_url( $target_url ) ) ? $target_url : $current_url;

	$name         = ( 'public' == $context ) ? 'mcat' : 'category';
	$admin_fields = ( 'public' == $context ) ? ' name="' . $name . '"' : ' multiple="multiple" size="5" name="' . $name . '[]"  ';
	$admin_label  = ( 'public' == $context ) ? '' : __( '(select to include)', 'my-calendar' );
	$form         = ( 'single' == $group ) ? '<form action="' . $current_url . '" method="get">
				<div>' : '';
	if ( 'single' == $group ) {
		$qsa = array();
		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			parse_str( $_SERVER['QUERY_STRING'], $qsa );
		}
		if ( ! isset( $_GET['cid'] ) ) {
			$form .= '<input type="hidden" name="cid" value="all" />';
		}
		foreach ( $qsa as $name => $argument ) {
			$name     = esc_attr( strip_tags( $name ) );
			$argument = esc_attr( strip_tags( $argument ) );
			if ( 'mcat' != $name || 'mc_id' != $name ) {
				$form .= '		<input type="hidden" name="' . $name . '" value="' . $argument . '" />' . "\n";
			}
		}
	}
	$form       .= ( 'list' == $show || 'group' == $group ) ? '' : '
		</div><p>';
	$public_form = ( 'public' == $context ) ? $form : '';
	if ( ! is_user_logged_in() ) {
		$categories = $mcdb->get_results( 'SELECT * FROM ' . my_calendar_categories_table() . ' WHERE category_private = 1 ORDER BY category_name ASC' );
	} else {
		$categories = $mcdb->get_results( 'SELECT * FROM ' . my_calendar_categories_table() . ' ORDER BY category_name ASC' );
	}
	if ( ! empty( $categories ) && count( $categories ) >= 1 ) {
		$output  = "<div id='mc_categories'>\n";
		$url     = mc_build_url( array( 'mcat' => 'all' ), array() );
		$output .= ( 'list' == $show ) ? "
		<ul>
			<li><a href='$url'>" . __( 'All Categories', 'my-calendar' ) . '</a></li>' : $public_form . '
			<label for="category">' . __( 'Categories', 'my-calendar' ) . ' ' . $admin_label . '</label>
			<select' . $admin_fields . ' id="category">
			<option value="all" selected="selected">' . __( 'All Categories', 'my-calendar' ) . '</option>' . "\n";

		foreach ( $categories as $category ) {
			$category_name = strip_tags( stripcslashes( $category->category_name ), mc_strip_tags() );
			$mcat          = ( empty( $_GET['mcat'] ) ) ? '' : (int) $_GET['mcat'];
			if ( 'list' == $show ) {
				$this_url = mc_build_url( array( 'mcat' => $category->category_id ), array() );
				$selected = ( $category->category_id == $mcat ) ? ' class="selected"' : '';
				$output  .= " <li$selected><a rel='nofollow' href='$this_url'>$category_name</a></li>";
			} else {
				$selected = ( $category->category_id == $mcat ) ? ' selected="selected"' : '';
				$output  .= " <option$selected value='$category->category_id'>$category_name</option>\n";
			}
		}
		$output .= ( 'list' == $show ) ? '</ul>' : '</select>';
		if ( 'admin' != $context && 'list' != $show ) {
			if ( 'single' == $group ) {
				$output .= '<input type="submit" value="' . __( 'Submit', 'my-calendar' ) . '" /></p></form>';
			}
		}
		$output .= '</div>';
	}
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
	$current_url = ( '' != $target_url && _mc_is_url( $target_url ) ) ? $target_url : $current_url;
	$form        = ( 'single' == $group ) ? "<form action='" . $current_url . "' method='get'><div>" : '';
	if ( 'single' == $group ) {
		$qsa = array();
		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			parse_str( $_SERVER['QUERY_STRING'], $qsa );
		}
		if ( ! isset( $_GET['cid'] ) ) {
			$form .= '<input type="hidden" name="cid" value="all" />';
		}
		foreach ( $qsa as $name => $argument ) {
			$name     = esc_attr( strip_tags( $name ) );
			$argument = esc_attr( strip_tags( $argument ) );
			if ( 'access' != $name || 'mc_id' != $name ) {
				$form .= '<input type="hidden" name="' . $name . '" value="' . $argument . '" />' . "\n";
			}
		}
	}
	$form .= ( 'list' == $show || 'group' == $group ) ? '' : '</div><p>';

	$access_options = mc_event_access();
	if ( ! empty( $access_options ) && count( $access_options ) >= 1 ) {
		$output       = "<div id='mc_access'>\n";
		$url          = mc_build_url( array( 'access' => 'all' ), array() );
		$not_selected = ( ! isset( $_GET['access'] ) ) ? 'selected="selected"' : '';
		$output      .= ( 'list' == $show ) ? "
		<ul>
			<li><a href='$url'>" . __( 'Accessibility Services', 'my-calendar' ) . '</a></li>' : $form . '
		<label for="access">' . __( 'Accessibility Services', 'my-calendar' ) . '</label>
			<select name="access" id="access">
			<option value="all"' . $not_selected . '>' . __( 'No Limit', 'my-calendar' ) . '</option>' . "\n";

		foreach ( $access_options as $key => $access ) {
			$access_name = $access;
			$this_access = ( empty( $_GET['access'] ) ) ? '' : (int) $_GET['access'];
			if ( 'list' == $show ) {
				$this_url = mc_build_url( array( 'access' => $key ), array() );
				$selected = ( $key == $this_access ) ? ' class="selected"' : '';
				$output  .= " <li$selected><a rel='nofollow' href='$this_url'>$access_name</a></li>";
			} else {
				$selected = ( $this_access == $key ) ? ' selected="selected"' : '';
				$output  .= " <option$selected value='" . esc_attr( $key ) . "'>" . esc_html( $access_name ) . "</option>\n";
			}
		}
		$output .= ( 'list' == $show ) ? '</ul>' : '</select>';
		$output .= ( 'list' != $show && 'single' == $group ) ? '<p><input type="submit" value="' . __( 'Limit by Access', 'my-calendar' ) . '" /></p></form>' : '';
		$output .= "\n</div>";
	}
	$output = apply_filters( 'mc_access_selector', $output, $access_options );

	return $output;
}

/**
 * Build a URL for My Calendar views.
 *
 * @param array  $add keys and values to add to URL.
 * @param array  $subtract keys to subtract from URL.
 * @param string $root Root URL, optional.
 *
 * @return string URL.
 */
function mc_build_url( $add, $subtract, $root = '' ) {
	$home = '';

	if ( '' != $root ) {
		$home = $root;
	}

	if ( is_numeric( $root ) ) {
		$home = get_permalink( $root );
	}

	if ( '' == $home ) {
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

	$variables = $_GET;
	$subtract  = array_merge( (array) $subtract, array( 'from', 'to', 'my-calendar-api', 's' ) );
	foreach ( $subtract as $value ) {
		unset( $variables[ $value ] );
	}

	foreach ( $add as $key => $value ) {
		$variables[ $key ] = $value;
	}

	unset( $variables['page_id'] );
	$home = add_query_arg( $variables, $home );

	return $home;
}

/**
 * Default My Calendar search form.
 *
 * @param string $type Type of search.
 * @param string $url URL to post query to.
 *
 * @return string HTML form.
 */
function my_calendar_searchform( $type, $url ) {
	$query = ( isset( $_GET['mcs'] ) ) ? $_GET['mcs'] : '';
	if ( 'simple' == $type ) {
		if ( ! $url || '' == $url ) {
			$url = mc_get_uri( false, array( 'type' => $type ) );
		}
		return '
		<div class="mc-search-container" role="search">
			<form method="get" action="' . apply_filters( 'mc_search_page', esc_url( $url ) ) . '" >
				<div class="mc-search">
					<label class="screen-reader-text" for="mcs">' . __( 'Search Events', 'my-calendar' ) . '</label>
					<input type="text" value="' . esc_attr( stripslashes( $query ) ) . '" name="mcs" id="mcs" />
					<input type="submit" id="searchsubmit" value="' . __( 'Search Events', 'my-calendar' ) . '" />
				</div>
			</form>
		</div>';
	}

	return '';
}

/**
 * Get list of locations.
 *
 * @param string   $datatype Type of data to sort by and return.
 * @param boolean  $full If need to return full location object.
 * @param constant $return_type valid query return type.
 *
 * @return array of location objects.
 */
function mc_get_list_locations( $datatype, $full = true, $return_type = OBJECT ) {
	global $wpdb;
	$mcdb = $wpdb;
	if ( 'true' == get_option( 'mc_remote' ) && function_exists( 'mc_remote_db' ) ) {
		$mcdb = mc_remote_db();
	}

	switch ( $datatype ) {
		case 'name':
		case 'location':
			$data = 'location_label';
			break;
		case 'city':
			$data = 'location_city';
			break;
		case 'state':
			$data = 'location_state';
			break;
		case 'zip':
			$data = 'location_postcode';
			break;
		case 'country':
			$data = 'location_country';
			break;
		case 'hcard':
			$data = 'location_label';
			break;
		case 'region':
			$data = 'location_region';
			break;
		default:
			$data = 'location_label';
	}

	$where = apply_filters( 'mc_filter_location_list', '', $datatype );
	if ( true !== $full ) {
		$select = $data;
	} else {
		$select = '*';
	}
	$locations = $mcdb->get_results( $mcdb->prepare( "SELECT DISTINCT $select FROM " . my_calendar_locations_table() . " $where ORDER BY %s ASC", $data ), $return_type );

	return $locations;
}

/**
 * Generate a list of locations for display.
 *
 * @param string $datatype Sort field.
 * @param string $template Display template.
 *
 * @return string HTML output of list
 */
function my_calendar_show_locations( $datatype = 'name', $template = '' ) {
	$locations = mc_get_list_locations( $datatype );
	if ( $locations ) {
		$output = '<ul class="mc-locations">';
		foreach ( $locations as $key => $value ) {
			if ( 'hcard' != $datatype && '' != $template ) {
				$label   = stripslashes( $value->{$data} );
				$url     = mc_maplink( $value, 'url', 'location' );
				$output .= ( $url ) ? "<li>$url</li>" : "<li>$label</li>";
			} elseif ( 'hcard' == $datatype ) {
				$label   = mc_hcard( $value, true, true, 'location' );
				$output .= "<li>$label</li>";
			} elseif ( '' != $template ) {
				if ( mc_key_exists( $template ) ) {
					$template = mc_get_custom_template( $template );
				}
				$values  = array(
					'id'        => $value->location_id,
					'label'     => $value->location_label,
					'street'    => $value->location_street,
					'street2'   => $value->location_street2,
					'city'      => $value->location_city,
					'state'     => $value->location_state,
					'postcode'  => $value->location_postcode,
					'region'    => $value->location_region,
					'url'       => $value->location_url,
					'country'   => $value->location_country,
					'longitude' => $value->location_longitude,
					'latitude'  => $value->location_latitude,
					'zoom'      => $value->location_zoom,
					'phone'     => $value->location_phone,
				);
				$label   = mc_draw_template( $values, $template );
				$output .= "<li>$label</li>";
			}
		}

		$output .= '</ul>';
		$output  = apply_filters( 'mc_location_list', $output, $locations );

		return $output;
	}

	return '';
}

/**
 * Output filters by location
 *
 * @param string $show either 'list' or 'form'.
 * @param string $datatype Type of data to sort by.
 * @param string $group whether this is being output as a single filter or as part of the group filters.
 * @param string $target_url URL to send requests to.
 *
 * @return string HTML to trigger location filters.
 */
function my_calendar_locations_list( $show = 'list', $datatype = 'name', $group = 'single', $target_url = '' ) {
	$output      = '';
	$locations   = mc_get_list_locations( $datatype, $datatype, ARRAY_A );
	$current_url = mc_get_uri();
	$current_url = ( '' != $target_url && _mc_is_url( $target_url ) ) ? $target_url : $current_url;

	if ( count( $locations ) > 1 ) {
		if ( 'list' == $show ) {
			$url     = mc_build_url( array(
				'loc'   => 'all',
				'ltype' => 'all',
			), array() );
			$output .= '<ul id="mc-locations-list">
			<li class="mc-show-all"><a href="' . $url . '">' . __( 'Show all', 'my-calendar' ) . '</a></li>';
		} else {
			$ltype   = ( ! isset( $_GET['ltype'] ) ) ? $datatype : $_GET['ltype'];
			$output .= '<div id="mc_locations">';
			$output .= ( 'single' == $group ) ? "<form action='" . $current_url . "' method='get'><div>" : '';
			$output .= "<input type='hidden' name='ltype' value='" . esc_attr( $ltype ) . "' />";
			if ( 'single' == $group ) {
				$qsa = array();
				if ( isset( $_SERVER['QUERY_STRING'] ) ) {
					parse_str( $_SERVER['QUERY_STRING'], $qsa );
				}
				if ( ! isset( $_GET['cid'] ) ) {
					$output .= '<input type="hidden" name="cid" value="all" />';
				}
				foreach ( $qsa as $name => $argument ) {
					$name     = esc_attr( strip_tags( $name ) );
					$argument = esc_attr( strip_tags( $argument ) );
					if ( 'loc' != $name && 'ltype' != $name ) {
						$output .= "\n" . '<input type="hidden" name="' . $name . '" value="' . $argument . '" />';
					}
				}
			}
			$output .= "
			<label for='mc-locations-list'>" . __( 'Location', 'my-calendar' ) . "</label>
			<select name='loc' id='mc-locations-list'>
			<option value='all'>" . __( 'Show all', 'my-calendar' ) . "</option>\n";
		}
		foreach ( $locations as $key => $location ) {
			foreach ( $location as $k => $value ) {
				$vt    = urlencode( trim( $value ) );
				$value = strip_tags( stripcslashes( $value ), mc_strip_tags() );
				if ( '' == $value ) {
					continue;
				}
				$loc = ( empty( $_GET['loc'] ) ) ? '' : $_GET['loc'];
				if ( 'list' == $show ) {
					$selected = ( $vt == $loc ) ? ' class="selected"' : '';
					$this_url = esc_url( mc_build_url( array(
						'loc'   => $vt,
						'ltype' => $datatype,
					), array() ) );
					$output  .= " <li$selected><a rel='nofollow' href='$this_url'>$value</a></li>\n";
				} else {
					$selected = ( $vt == $loc ) ? ' class="selected"' : '';
					if ( '' != $value ) {
						$output .= " <option value='" . esc_attr( $vt ) . "'$selected>$value</option>\n";
					}
				}
			}
		}
		if ( 'list' == $show ) {
			$output .= '</ul>';
		} else {
			$output .= '</select>';
			$output .= ( 'single' == $group ) ? '<input type="submit" value="' . __( 'Submit', 'my-calendar' ) . '" />
					</div>
				</form>' : '';
			$output .= '</div>';
		}
		$output = apply_filters( 'mc_location_selector', $output, $locations );

		return $output;
	} else {
		return '';
	}
}

add_action( 'mc_save_event', 'mc_refresh_cache', 10, 4 );
/**
 * Execute a refresh of the My Calendar primary URL cache if caching plug-in installed.
 *
 * @param string $action Type of action performed.
 * @param array  $data Data passed to filter.
 * @param int    $event_id Event ID being affected.
 * @param int    $result Result of calendar save query.
 */
function mc_refresh_cache( $action, $data, $event_id, $result ) {
	$mc_uri_id  = ( get_option( 'mc_uri_id' ) ) ? get_option( 'mc_uri_id' ) : false;
	$to_refresh = apply_filters( 'mc_cached_pages_to_refresh', array( $mc_uri_id ), $action, $data, $event_id, $result );

	foreach ( $to_refresh as $calendar ) {
		if ( ! $calendar || ! get_post( $calendar ) ) {
			continue;
		}
		// W3 Total Cache.
		if ( function_exists( 'w3tc_pgcache_flush_post' ) ) {
			w3tc_pgcache_flush_post( $calendar );
		}

		// WP Super Cache.
		if ( function_exists( 'wp_cache_post_change' ) ) {
			wp_cache_post_change( $calendar );
		}

		// WP Rocket.
		if ( function_exists( 'rocket_clean_post' ) ) {
			rocket_clean_post( $calendar );
		}

		// WP Fastest Cache.
		if ( isset( $GLOBALS['wp_fastest_cache'] ) && method_exists( $GLOBALS['wp_fastest_cache'], 'singleDeleteCache' ) ) {
			$GLOBALS['wp_fastest_cache']->singleDeleteCache( false, $calendar );
		}

		// Comet Cache.
		if ( class_exists( 'comet_cache' ) ) {
			comet_cache::clearPost( $calendar );
		}

		// Cache Enabler.
		if ( class_exists( 'Cache_Enabler' ) ) {
			Cache_Enabler::clear_page_cache_by_post_id( $calendar );
		}
	}
}
