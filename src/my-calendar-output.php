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
 * HTML output for event time
 *
 * @param object $e Current event.
 * @param string $type Type of view.
 *
 * @return string HTML output.
 */
function mc_time_html( $e, $type ) {
	$date_format = mc_date_format();
	$time_format = mc_time_format();
	$start       = mc_date( 'Y-m-d', strtotime( $e->occur_begin ), false );
	$end         = mc_date( 'Y-m-d', strtotime( $e->occur_end ), false );
	$has_time    = ( '00:00:00' !== $e->event_time && '' !== $e->event_time ) ? true : false;

	$offset  = get_option( 'gmt_offset' );
	$hours   = (int) $offset;
	$minutes = abs( ( $offset - (int) $offset ) * 60 );
	$offset  = sprintf( '%+03d:%02d', $hours, $minutes );
	$dtstart = $start . 'T' . $e->event_time . $offset;
	$dtend   = $end . 'T' . $e->event_endtime . $offset;
	$notime  = '';
	if ( ! $has_time ) {
		$label = mc_notime_label( $e );
		if ( $label ) {
			$notime .= " <span class='event-time'>";
			$notime .= ( 'N/A' === $label ) ? "<abbr title='" . esc_html__( 'Not Applicable', 'my-calendar' ) . "'>" . esc_html__( 'N/A', 'my-calendar' ) . '</abbr>' : esc_html( $label );
			$notime .= '</span>';
		}
	}
	$date_start  = "<span class='mc-start-date dtstart' title='" . esc_attr( $dtstart ) . "' content='" . esc_attr( $dtstart ) . "'>" . date_i18n( $date_format, strtotime( $e->occur_begin ) ) . '</span>';
	$time_start  = ( $has_time ) ? "<span class='event-time dtstart'><time class='value-title' datetime='" . esc_attr( $dtstart ) . "' title='" . esc_attr( $dtstart ) . "'>" . date_i18n( $time_format, strtotime( $e->occur_begin ) ) . '</time></span>' : $notime;
	$date_end    = ( 0 === (int) $e->event_hide_end && ( $e->event_begin !== $e->event_end ) ) ? '<span class="event-time dtend">' . date_i18n( $date_format, strtotime( $e->occur_end ) ) . '</span>' : '';
	$time_end    = ( $has_time && 0 === (int) $e->event_hide_end ) ? "<span class='end-time dtend'> <time class='value-title' datetime='" . esc_attr( $dtend ) . "' title='" . esc_attr( $dtend ) . "'>" . date_i18n( $time_format, strtotime( $e->occur_end ) ) . '</time></span>' : '';
	$t_separator = ( $time_end ) ? "<span class='time-separator'> &ndash; </span>" : '';
	$d_separator = ( $date_end ) ? "<span class='date-separator'> &ndash; </span>" : '';
	$br          = ( $time_end || $time_start ) ? '<br />' : '';

	$time_content = '<span class="time-wrapper">' . $time_start . ' ' . $t_separator . ' ' . $time_end . '</span>' . $br . '<span class="date-wrapper">' . $date_start . ' ' . $d_separator . ' ' . $date_end . '</span>';

	$time = "
	<div class='time-block'>
		<p>$time_content</p>
	</div>";

	/**
	 * Filter time block output.
	 *
	 * @hook mcs_time_block
	 *
	 * @param {string} $time HTML time block output.
	 * @param {object} $e Event object.
	 *
	 * @return string
	 */
	return apply_filters( 'mcs_time_block', $time, $e );
}

/**
 * Generate the set of events for a given day
 *
 * @param array  $events Array of event objects.
 * @param array  $params calendar parameters.
 * @param string $process_date String formatted date being displayed.
 * @param string $template Template to use for drawing individual events.
 * @param string $id ID for the calendar calling this function.
 *
 * @return array|true [html] Generated HTML & [json] array of schema.org data. True if event details not included.
 */
function my_calendar_draw_events( $events, $params, $process_date, $template = '', $id = '' ) {
	$type = $params['format'];
	$time = $params['time'];

	$open_option = mc_get_option( 'open_day_uri' );
	if ( 'mini' === $type && ( 'true' === $open_option || 'listanchor' === $open_option || 'calendaranchor' === $open_option ) ) {
		return true;
	}
	// We need to sort arrays of objects by time.
	if ( ! empty( $events ) ) {
		$output_array = array();
		$json         = array();
		$begin        = '';
		$event_output = '';
		$end          = '';
		if ( 'mini' === $type && count( $events ) > 0 ) {
			$minitype = mc_get_option( 'mini_javascript' );
			if ( 'modal' === $minitype ) {
				$begin .= "<div id='date-$process_date' class='calendar-events uses-modal'>";
			} else {
				$begin .= "<div id='date-$process_date' class='calendar-events'>";
			}
			$begin .= mc_close_button( "date-$process_date" );
		}
		foreach ( array_keys( $events ) as $key ) {
			$event =& $events[ $key ];
			if ( 'S1' !== $event->event_recur ) {
				$check = get_post_meta( $event->event_post, '_occurrence_overlap', true );
				if ( 'false' === $check ) {
					$check = mc_test_occurrence_overlap( $event, true );
				}
			} else {
				$check = '';
			}
			if ( '' === $check ) {
				$tags           = mc_create_tags( $event, $id );
				$output_array[] = my_calendar_draw_event( $event, $type, $process_date, $time, $template, $id, $tags );
				$json           = mc_event_schema( $event, $tags );
			}
		}
		if ( is_array( $output_array ) ) {
			foreach ( array_keys( $output_array ) as $key ) {
				$value         =& $output_array[ $key ];
				$event_output .= $value;
			}
		}
		if ( '' === $event_output ) {
			return array();
		}
		if ( 'mini' === $type && count( $events ) > 0 ) {
			$end .= '</div>';
		}

		return array(
			'html' => $begin . $event_output . $end,
			'json' => $json,
		);
	}

	return array();
}

/**
 * Check whether legacy templates are enabled.
 *
 * @return bool
 */
function mc_legacy_templates_enabled() {
	/**
	 * Filter legacy templates status. New templates are intended for release with version 3.5.0 and will be in alpha at least through then.
	 *
	 * @hook mc_legacy_templates_enabled
	 *
	 * @param {bool} $enabled Return 'true' to use legacy templates.
	 *
	 * @return {bool}
	 */
	$enabled = apply_filters( 'mc_legacy_templates_enabled', true );
	// New templates require at least WP 5.5.
	if ( version_compare( $GLOBALS['wp_version'], '5.5', '<' ) ) {
		$enabled = true;
	}

	return $enabled;
}

/**
 * Load a PHP template for an event.
 *
 * @param string $type Template type.
 * @param array  $data Event and display data.
 *
 * @return string
 */
function mc_load_template( $type, $data ) {

	$legacy_templates = mc_legacy_templates_enabled();
	$details          = '';
	if ( ! $legacy_templates ) {
		$source = 'event';
		if ( false === strpos( $type, '/' ) ) {
			$parts  = explode( $type, '/' );
			$source = $parts[0];
			$type   = $parts[1];
		}
		$templates = new Mc_Template_Loader();
		ob_start();
		$templates->set_template_data( $data );
		$templates->get_template_part( $source, $type );
		$details = ob_get_clean();
	}

	return $details;
}
/**
 * Draw a single event
 *
 * @param object $event Event object.
 * @param string $type Type of view being drawn.
 * @param string $process_date Current date being displayed.
 * @param string $time Time view being drawn.
 * @param string $template Template to use to draw event.
 * @param string $id ID for the calendar calling this function.
 * @param array  $tags Event tags array.
 *
 * @return string Generated HTML.
 */
function my_calendar_draw_event( $event, $type, $process_date, $time, $template = '', $id = '', $tags = array() ) {
	$exit_early = mc_exit_early( $event, $process_date );
	if ( $exit_early ) {
		return '';
	}
	/**
	 * Runs right before a calendar event template is handled.
	 *
	 * @since 3.3.15.
	 * @hook my_calendar_drawing_event
	 *
	 * @param {object} $event My Calendar Event Object.
	 */
	do_action( 'my_calendar_drawing_event', $event );
	$data    = array(
		'event'        => $event,
		'process_date' => $process_date,
		'time'         => $time,
		'id'           => $id,
		'tags'         => $tags,
	);
	$details = mc_load_template( $type, $data );

	if ( ! $details ) {
		// assign empty values to template sections.
		$header      = '';
		$address     = '';
		$more        = '';
		$author      = '';
		$host        = '';
		$list_title  = '';
		$title       = '';
		$output      = '';
		$container   = '';
		$short       = '';
		$description = '';
		$link        = '';
		$vcal        = '';
		$inner_title = '';
		$gcal        = '';
		$access      = '';
		$image       = '';
		$tickets     = '';
		$details     = '';
		$data        = ( empty( $tags ) ) ? mc_create_tags( $event, $id ) : $tags;
		$otype       = ( 'calendar' === $type ) ? 'grid' : $type;

		if ( mc_show_details( $time, $type ) ) {
			/**
			 * Filter My Calendar view output. Returning any content will shortcircuit drawing event output.
			 *
			 * @hook mc_custom_template
			 *
			 * @param {string|bool} $details Output HTML for event. Default boolean false.
			 * @param {array}       $data Event data array passed to template function.
			 * @param {object}      $event My Calendar event object.
			 * @param {string}      $type View type.
			 * @param {string}      $process_date Current date being processed.
			 * @param {string}      $time View timeframe.
			 * @param {string}      $template Existing template.
			 *
			 * @return {string}
			 */
			$details = apply_filters( 'mc_custom_template', false, $data, $event, $type, $process_date, $time, $template );
			/**
			 * Filter My Calendar view template.
			 *
			 * @hook mc_use_custom_template
			 *
			 * @param {string} $template HTML with template tags.
			 * @param {array} $data Event data array passed to template function.
			 * @param {object} $event My Calendar event object.
			 * @param {string} $type View type.
			 * @param {string} $process_date Current date being processed.
			 * @param {string} $time View timeframe.
			 *
			 * @return {string}
			 */
			$template = apply_filters( 'mc_use_custom_template', $template, $data, $event, $type, $process_date, $time );
			if ( false === $details ) {
				$details = mc_get_details( $data, $template, $type );
			}
		}

		// Fallback display options. Changed in 3.3.0; fallback to old settings if new don't exist.
		$no_old_options  = ( '' === mc_get_option( 'display_' . $otype ) ) ? true : false;
		$display_map     = ( $no_old_options ) ? get_option( 'mc_show_map' ) : '';
		$display_address = ( $no_old_options ) ? get_option( 'mc_show_address' ) : '';
		$display_gcal    = ( $no_old_options ) ? get_option( 'mc_show_gcal' ) : '';
		$display_vcal    = ( $no_old_options ) ? get_option( 'mc_show_event_vcal' ) : '';
		$display_author  = ( $no_old_options ) ? get_option( 'mc_display_author' ) : '';
		$display_host    = ( $no_old_options ) ? get_option( 'mc_display_host' ) : '';
		$display_more    = ( $no_old_options ) ? get_option( 'mc_display_more' ) : '';
		$display_desc    = ( $no_old_options ) ? get_option( 'mc_desc' ) : '';
		$display_short   = ( $no_old_options ) ? get_option( 'mc_short' ) : '';
		$display_gmap    = ( $no_old_options ) ? get_option( 'mc_gmap' ) : '';
		$display_link    = ( $no_old_options ) ? get_option( 'mc_event_link' ) : '';
		$display_image   = ( $no_old_options ) ? get_option( 'mc_image' ) : '';
		$display_reg     = ( $no_old_options ) ? get_option( 'mc_event_registration' ) : '';
		$open_uri        = mc_get_option( 'open_uri' );
		$day_id          = mc_date( 'd', strtotime( $process_date ), false );
		$uid             = 'mc_' . $type . '_' . $day_id . '_' . $event->occur_id;
		$image           = mc_category_icon( $event );
		$image           = ( $image ) ? $image . ' ' : '';
		$img             = '';
		$has_image       = ( '' !== $image ) ? ' has-image' : '';
		$event_classes   = mc_event_classes( $event, $type );
		$nofollow        = ( stripos( $event_classes, 'past-event' ) !== false ) ? 'rel="nofollow"' : '';
		$header         .= "\n\n	<div id='$uid-$type-$id' class='$event_classes'>\n";

		switch ( $type ) {
			case 'calendar':
				$title_template = ( mc_get_template( 'title' ) === '' ) ? '{title}' : mc_get_template( 'title' );
				break;
			case 'list':
				$title_template = ( mc_get_template( 'title_list' ) === '' ) ? '{title}' : mc_get_template( 'title_list' );
				break;
			case 'single':
				$title_template = ( mc_get_template( 'title_solo' ) === '' ) ? '{title}' : mc_get_template( 'title_solo' );
				break;
			default:
				$title_template = ( mc_get_template( 'title' ) === '' ) ? '{title}' : mc_get_template( 'title' );
		}

		$event_title = mc_draw_template( $data, $title_template );
		if ( 0 === strpos( $event_title, ': ' ) ) {
			// If the first two characters of the title are ": ", this is the default templates but no time.
			$event_title = str_replace( ': ', '', $event_title );
		}
		$event_title = ( '' === $event_title ) ? $data['title'] : strip_tags( $event_title, mc_strip_tags() );
		if ( 'single' === $type ) {
			/**
			 * Customize event title in single view.
			 *
			 * @hook mc_single_event_title
			 *
			 * @param {string} $event_title Event title.
			 * @param {object} $event My Calendar event object.
			 *
			 * @return {string}
			 */
			$event_title = apply_filters( 'mc_single_event_title', $event_title, $event );
		} else {
			/**
			 * Customize event title in group views.
			 *
			 * @hook mc_event_title
			 *
			 * @param {string} $event_title Event title.
			 * @param {object} $event My Calendar event object.
			 * @param {string} $title Title in event template array.
			 * @param {string} $image Category icon.
			 *
			 * @return {string}
			 */
			$event_title = apply_filters( 'mc_event_title', $event_title, $event, $data['title'], $image );
		}
		/**
		 * Disable links on grid view.
		 *
		 * @hook mc_disable_link
		 *
		 * @param {bool} $no_link True to disable link.
		 * @param {array} $data Event data array.
		 *
		 * @return {bool}
		 */
		$no_link = apply_filters( 'mc_disable_link', false, $data );

		if ( ( ( strpos( $event_title, 'href' ) === false ) && 'mini' !== $type && 'list' !== $type || ( 'list' === $type && 'true' === mc_get_option( 'list_link_titles' ) ) ) && ! $no_link ) {
			if ( 'true' === $open_uri ) {
				$details_link = esc_url( mc_get_details_link( $event ) );
				$wrap         = ( _mc_is_url( $details_link ) ) ? "<a href='$details_link' class='url summary$has_image' $nofollow>" : '<span class="no-link">';
				$balance      = ( _mc_is_url( $details_link ) ) ? '</a>' : '</span>';
			} else {
				$gridtype           = mc_get_option( 'calendar_javascript' );
				$listtype           = mc_get_option( 'list_javascript' );
				$single_template    = ( mc_get_template( 'title_solo' ) === '' ) ? '{title}' : mc_get_template( 'title_solo' );
				$event_title_single = mc_draw_template( $data, $single_template );
				if ( ( 'modal' === $gridtype && 'calendar' === $type ) || ( 'modal' === $listtype && 'list' === $type ) ) {
					$params  = "id='modal-button-$uid-$type-details-$id' data-modal-content-id='$uid-$type-details-$id' data-modal-prefix-class='my-calendar' data-modal-close-text='" . esc_attr( __( 'Close', 'my-calendar' ) ) . "' data-modal-title='" . esc_attr( $event_title_single ) . "'";
					$classes = 'js-modal button';
				} else {
					$params  = " aria-expanded='false'";
					$classes = 'open';
				}
				$wrap    = "<a href='#$uid-$type-details-$id' $params aria-controls='$uid-$type-details-$id' class='$type $classes et_smooth_scroll_disabled opl-link url summary$has_image'><span>";
				$balance = '</span></a>';
			}
		} else {
			$wrap    = '';
			$balance = '';
		}

		$group_class = ( 1 === (int) $event->event_span ) ? ' multidate group' . $event->event_group_id : '';
		$hlevel      = ( mc_get_option( 'show_months' ) > 1 ) ? 'h4' : 'h3';
		/**
		 * Filter default event heading when in a table.
		 *
		 * @hook mc_heading_level_table
		 *
		 * @param {string} $hlevel HTML element. Default 'h3'.
		 * @param {string} $type View type.
		 * @param {string} $time View timeframe.
		 * @param {string} $template Current template.
		 *
		 * @return {string}
		 */
		$hlevel = apply_filters( 'mc_heading_level_table', $hlevel, $type, $time, $template );
		// Set up .summary - required once per page for structured data. Should only be added in cases where heading & anchor are removed.
		if ( 'single' === $type ) {
			$title = ( ! is_singular( 'mc-events' ) ) ? "	<h2 class='event-title summary'>$image$event_title</h2>\n" : '	<span class="summary screen-reader-text">' . strip_tags( $event_title ) . '</span>';
		} elseif ( 'list' !== $type || ( 'list' === $type && 'true' === mc_get_option( 'list_link_titles' ) ) ) {
			/**
			 * Filter event title inside event heading.
			 *
			 * @hook mc_heading_inner_title
			 *
			 * @param {string} $inner_heading Heading HTML and text.
			 * @param {string} $event_title Title as passed.
			 * @param {object} $event My Calendar event object.
			 *
			 * @return {string}
			 */
			$inner_heading = apply_filters( 'mc_heading_inner_title', $wrap . $image . trim( $event_title ) . $balance, $event_title, $event );
			$title         = "	<$hlevel class='event-title summary$group_class' id='mc_$event->occur_id-title-$id'>$inner_heading</$hlevel>\n";
		} else {
			$title = '';
		}
		$header      .= ( false === stripos( $title, 'summary' ) ) ? '	<span class="summary screen-reader-text">' . strip_tags( $event_title ) . '</span>' : $title;
		$close_button = mc_close_button( "$uid-$type-details-$id" );

		if ( mc_show_details( $time, $type ) ) {
			// Since 3.2.0, close button is added to event container in mini calendar.
			$close = ( 'calendar' === $type ) ? $close_button : '';

			if ( false === $details ) {
				if ( ( 'true' === $display_address || 'true' === $display_map ) || ( mc_output_is_visible( 'address', $type, $event ) || mc_output_is_visible( 'gmap_link', $type, $event ) ) ) {
					$show_add = ( 'true' === $display_address || mc_output_is_visible( 'address', $type, $event ) ) ? 'true' : 'false';
					$show_map = ( 'true' === $display_map || mc_output_is_visible( 'gmap_link', $type, $event ) ) ? 'true' : 'false';
					$address  = mc_hcard( $event, $show_add, $show_map );
				}
				$time_html = mc_time_html( $event, $type );
				if ( 'list' === $type ) {
					/**
					 * Filter list event heading level. Default 'h3'.
					 *
					 * @hook mc_heading_level_list
					 *
					 * @param {string} $hlevel Default heading level element.
					 * @param {string} $type View type.
					 * @param {string} $time View timeframe.
					 * @param {string} $template Current template.
					 *
					 * @return {string}
					 */
					$hlevel = apply_filters( 'mc_heading_level_list', 'h3', $type, $time, $template );
					if ( 'false' === mc_get_option( 'list_link_titles' ) ) {
						$list_title = "	<$hlevel class='event-title summary' id='mc_$event->occur_id-title-$id'>$image" . $event_title . "</$hlevel>\n";
					}
				}
				if ( 'true' === $display_author || mc_output_is_visible( 'author', $type, $event ) ) {
					$author = mc_template_user_card( $event, 'author' );
				}
				if ( 'true' === $display_host || mc_output_is_visible( 'host', $type, $event ) ) {
					$host = mc_template_user_card( $event, 'host' );
				}
				if ( ( 'true' === $display_more && ! isset( $_GET['mc_id'] ) ) || mc_output_is_visible( 'more', $type, $event ) ) {
					$details_label = mc_get_details_label( $event, $data );
					$details_link  = mc_get_details_link( $event );
					$aria          = '';
					// If the event title is already in the details label, omit ARIA.
					if ( false === stripos( strip_tags( $details_label ), strip_tags( $event_title ) ) ) {
						$aria = " aria-label='" . esc_attr( "$details_label: " . strip_tags( $event_title ) ) . "'";
					}
					if ( _mc_is_url( $details_link ) ) {
						$more = "	<p class='mc-details'><a$aria href='" . esc_url( $details_link ) . "'>$details_label</a></p>\n";
					} else {
						$more = '';
					}
				}
				/**
				 * Filter link to event details in grid.
				 *
				 * @hook mc_details_grid_link
				 *
				 * @param {string} $more More link.
				 * @param {object} $event My Calendar event object.
				 *
				 * @return {string}
				 */
				$more = apply_filters( 'mc_details_grid_link', $more, $event );

				if ( mc_output_is_visible( 'access', $type, $event ) ) {
					$access_heading = ( '' !== mc_get_option( 'event_accessibility', '' ) ) ? mc_get_option( 'event_accessibility' ) : __( 'Event Accessibility', 'my-calendar' );
					$access_content = mc_expand( get_post_meta( $event->event_post, '_mc_event_access', true ) );
					$sublevel       = 'h4';
					if ( 'single' === $type ) {
						$sublevel = 'h2';
					}
					/**
					 * Filter subheading levels inside event content.
					 *
					 * @hook mc_subheading_level
					 *
					 * @param {string} $el Element name. Default 'h4' in grouped templates, h2 on single templates.
					 * @param {string} $type View type.
					 * @param {string} $time View timeframe.
					 * @param {string} $template Current template.
					 *
					 * @return {string}
					 */
					$sublevel = apply_filters( 'mc_subheading_level', $sublevel, $type, $time, $template );
					if ( $access_content ) {
						$access = '<div class="mc-accessibility"><' . $sublevel . '>' . $access_heading . '</' . $sublevel . '>' . $access_content . '</div>';
					}
				}

				if ( 'true' === $display_gcal || mc_output_is_visible( 'gcal', $type, $event ) ) {
					$gcal = "	<p class='gcal'>" . mc_draw_template( $data, '{gcal_link}' ) . '</p>';
				}

				if ( 'true' === $display_vcal || mc_output_is_visible( 'ical', $type, $event ) ) {
					$vcal = "	<p class='ical'>" . mc_draw_template( $data, '{ical_html}' ) . '</p>';
				}

				if ( 'true' === $display_image || mc_output_is_visible( 'image', $type, $event ) ) {
					$img = mc_get_event_image( $event, $data );
				}

				if ( 'calendar' === $type ) {
					// This is semantically a duplicate of the title, but can be beneficial for sighted users.
					$uitype = mc_get_option( 'calendar_javascript' );
					if ( 'modal' === $uitype ) {
						$inner_title = '';
					} else {
						$headingtype = ( 'h3' === $hlevel ) ? 'h4' : 'h' . ( ( (int) str_replace( 'h', '', $hlevel ) ) - 1 );
						$inner_title = '	<' . $headingtype . ' class="mc-title">' . $event_title . '</' . $headingtype . '>';
					}
				}

				if ( 'true' === $display_desc || mc_output_is_visible( 'description', $type, $event ) ) {
					if ( '' !== trim( $event->event_desc ) ) {
						$description = wpautop( stripcslashes( mc_kses_post( $event->event_desc ) ), 1 );
						$description = "	<div class='longdesc description'>$description</div>";
					}
				}

				if ( 'true' === $display_reg || mc_output_is_visible( 'tickets', $type, $event ) ) {
					$info     = wpautop( stripcslashes( mc_kses_post( $event->event_registration ) ) );
					$url      = esc_url( $event->event_tickets );
					$external = ( $url && mc_external_link( $url ) ) ? 'external' : '';
					$text     = ( '' !== mc_get_option( 'buy_tickets', '' ) ) ? mc_get_option( 'buy_tickets' ) : __( 'Buy Tickets', 'my-calendar' );
					$tickets  = ( $url ) ? "<a class='$external' href='" . $url . "'>" . $text . '</a>' : '';
					if ( '' !== trim( $info . $tickets ) ) {
						$tickets = '<div class="mc-registration">' . $info . $tickets . '</div>';
					} else {
						$tickets = '';
					}
				}

				if ( 'true' === $display_short || mc_output_is_visible( 'excerpt', $type, $event ) ) {
					if ( '' !== trim( $event->event_short ) ) {
						$short = wpautop( stripcslashes( mc_kses_post( $event->event_short ) ), 1 );
						$short = "	<div class='shortdesc description'>$short</div>";
					}
				}

				/**
				 * Filter registration status of event. Default empty.
				 *
				 * @hook mc_registration_state
				 *
				 * @param {string} $status String.
				 * @param {object} $event My Calendar object.
				 *
				 * @return {string}
				 */
				$status = apply_filters( 'mc_registration_state', '', $event );
				/**
				 * Filter URL appended on single event view to return to calendar.
				 *
				 * @hook mc_return_uri
				 *
				 * @param {string} $url Calendar URL.
				 *
				 * @return {string}
				 */
				$return_url = apply_filters( 'mc_return_uri', mc_get_uri( $event ) );
				$text       = ( '' !== mc_get_option( 'view_full', '' ) ) ? mc_get_option( 'view_full' ) : __( 'View full calendar', 'my-calendar' );
				$return     = ( 'single' === $type ) ? "	<p class='view-full'><a href='$return_url'>" . $text . '</a></p>' : '';

				if ( ! mc_show_details( $time, $type ) ) {
					$description = '';
					$short       = '';
					$status      = '';
				}

				if ( 'true' === $display_gmap || mc_output_is_visible( 'gmap', $type, $event ) ) {
					$map = ( is_singular( 'mc-events' ) || 'single' === $type ) ? mc_generate_map( $event ) : '';
				} else {
					$map = '';
				}
				$event_link = mc_event_link( $event );

				if ( '' !== $event_link && ( 'true' === $display_link || mc_output_is_visible( 'link', $type, $event ) ) ) {
					$external_class = ( mc_external_link( $event_link ) ) ? "$type-link external url" : "$type-link url";
					$link_template  = ( '' !== mc_get_template( 'link' ) ) ? mc_get_template( 'link' ) : __( 'More information', 'my-calendar' );
					$link_text      = mc_draw_template( $data, $link_template );
					$link           = "
		<p>
			<a href='" . esc_url( $event_link ) . "' class='$external_class' aria-describedby='mc_{$event->occur_id}-title-$id'>" . $link_text . '</a>
		</p>';
				}
				$access   = ( '' !== $access ) ? '<div class="mc-access-information">' . $access . '</div>' : '';
				$location = ( '' === trim( $map . $address ) ) ? '' : '	<div class="mc-location">' . $map . $address . '</div>';
				$sharing  = ( '' === trim( $vcal . $gcal . $more ) ) ? '' : '	<div class="sharing">' . $vcal . $gcal . $more . '</div>';

				$close       = ( '' !== $close ) ? PHP_EOL . '	' . $close : '';
				$inner_title = ( $inner_title ) ? PHP_EOL . '	' . $inner_title : '';
				$time_html   = ( $time_html ) ? PHP_EOL . '	' . $time_html : '';
				$list_title  = ( $list_title ) ? PHP_EOL . '	' . $list_title : '';
				$img         = ( $img ) ? PHP_EOL . '	' . $img : '';
				$location    = ( $location ) ? PHP_EOL . '	' . $location : '';
				$description = ( $description ) ? PHP_EOL . '	' . $description : '';
				$short       = ( $short ) ? PHP_EOL . '	' . $short : '';
				$link        = ( $link ) ? PHP_EOL . '	' . $link : '';
				$status      = ( $status ) ? PHP_EOL . '	' . $status : '';
				$tickets     = ( $tickets ) ? PHP_EOL . '	' . $tickets : '';
				$author      = ( $author ) ? PHP_EOL . '	' . $author : '';
				$host        = ( $host ) ? PHP_EOL . '	' . $host : '';
				$sharing     = ( $sharing ) ? PHP_EOL . '	' . $sharing : '';
				$access      = ( $access ) ? PHP_EOL . '	' . $access : '';
				$return      = ( $return ) ? PHP_EOL . '	' . $return : '';
				$order       = array( 'close', 'inner_title', 'list_title', 'time_html', 'img', 'description', 'short', 'location', 'access', 'link', 'status', 'tickets', 'author', 'host', 'sharing', 'return' );
				/**
				 * Filter the order in which event template display elements are appended.
				 *
				 * @hook mc_default_output_order
				 *
				 * @param {array}  $order Array of ordered keywords representing items in template.
				 * @param {object} $event Event object.
				 *
				 * @return {array}
				 */
				$output_order = apply_filters( 'mc_default_output_order', $order, $event );
				$details      = $close;
				if ( ! empty( $output_order ) ) {
					foreach ( $output_order as $value ) {
						/**
						 * Filter individual display output items. Variable filter name based on `array( 'close', 'inner_title', 'list_title', 'time_html', 'img', 'description', 'short', 'location', 'access', 'link', 'status', 'tickets', 'author', 'host', 'sharing', 'return' );`
						 *
						 * @hook mc_event_detail_{name}
						 *
						 * @param {string} $details HTML content for section.
						 * @param {object} $event My Calendar event object.
						 *
						 * @return {string}
						 */
						$details .= apply_filters( 'mc_event_detail_' . sanitize_title( $value ), ${$value}, $event );
					}
				}
			} else {
				// If a custom template is in use.
				$toggle  = ( 'calendar' === $type ) ? $close_button : '';
				$details = $toggle . $details . "\n";
			}

			$img_class = ( $img ) ? ' has-image' : ' no-image';
			$gridtype  = mc_get_option( 'calendar_javascript' );
			$listtype  = mc_get_option( 'list_javascript' );
			if ( ( 'modal' === $gridtype && 'calendar' === $type ) || ( 'modal' === $listtype && 'list' === $type ) ) {
				$img_class .= ' uses-modal';
			}
			if ( 'list' === $type || 'calendar' === $type ) {
				$img_class .= ' single-details';
			}
			$container = "\n	<div id='$uid-$type-details-$id' class='details$img_class' aria-labelledby='mc_$event->occur_id-title" . '-' . $id . "'>\n";
			/**
			 * Filter details before the event content..
			 *
			 * @hook mc_before_event
			 *
			 * @param {string} $details HTML content.
			 * @param {object} $event My Calendar event object.
			 * @param {string} $type View type.
			 * @param {string} $time View timeframe.
			 *
			 * @return {string}
			 */
			$container = apply_filters( 'mc_before_event', $container, $event, $type, $time );
			$details   = $header . $container . apply_filters( 'mc_inner_content', $details, $event, $type, $time );
			/**
			 * Filter details appended after the event content.
			 *
			 * @hook mc_after_event
			 *
			 * @param {string} $details HTML content. Default empty.
			 * @param {object} $event My Calendar event object.
			 * @param {string} $type View type.
			 * @param {string} $time View timeframe.
			 *
			 * @return {string}
			 */
			$details .= apply_filters( 'mc_after_event', '', $event, $type, $time );
			$details .= "\n" . '	</div><!--end .details-->' . "\n" . '	</div>' . "\n";
			/**
			 * Filter details output.
			 *
			 * @hook mc_event_content
			 *
			 * @param {string} $details HTML content.
			 * @param {object} $event My Calendar event object.
			 * @param {string} $type View type.
			 * @param {string} $time View timeframe.
			 *
			 * @return {string}
			 */
			$details = apply_filters( 'mc_event_content', $details, $event, $type, $time );
		} else {
			/**
			 * Filter container before event details when view details panel is disabled.
			 *
			 * @hook mc_before_event_no_details
			 *
			 * @param {string} $container HTML string.
			 * @param {object} $event My Calendar event.
			 * @param {string} $type View type.
			 * @param {string} $time View timeframe.
			 *
			 * @return {string}
			 */
			$before = apply_filters( 'mc_before_event_no_details', $container, $event, $type, $time );
			/**
			 * Filter container after event details when view details panel is disabled.
			 *
			 * @hook mc_after_event_no_details
			 *
			 * @param {string} $container HTML string. Default empty.
			 * @param {object} $event My Calendar event.
			 * @param {string} $type View type.
			 * @param {string} $time View timeframe.
			 *
			 * @return {string}
			 */
			$after   = apply_filters( 'mc_after_event_no_details', '', $event, $type, $time ) . '</div>';
			$details = $before . $header . $after;
		}
	}
	$details = apply_filters( 'mc_event_details_output', $details, $event );
	/**
	 * Runs right after a calendar event template is run.
	 *
	 * @since 3.3.15.
	 * @hook my_calendar_event_drawn
	 *
	 * @param {object} My Calendar event object.
	 */
	do_action( 'my_calendar_event_drawn', $event );

	return $details;
}

/**
 * Generate close button.
 *
 * @param string $controls ID for object this controls.
 *
 * @return string
 */
function mc_close_button( $controls ) {
	/**
	 * Filter event modal close button label.
	 *
	 * @hook mc_close_button
	 *
	 * @param {string} $close HTML or text string to use as label of close button.
	 *
	 * @return {string}
	 */
	$close_image  = apply_filters( 'mc_close_button', "<span class='dashicons dashicons-dismiss' aria-hidden='true'></span><span class='screen-reader-text'>Close</span>" );
	$close_button = "	<button type='button' aria-controls='$controls' class='mc-toggle close' data-action='shiftforward'>$close_image</button>";

	return $close_button;
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
	if ( '' !== $template && mc_file_exists( $template ) ) {
		$template = file_get_contents( mc_get_file( $template ) );
		$details  = mc_draw_template( $data, $template );
	} elseif ( '' !== $template && mc_key_exists( $template ) ) {
		$template = mc_get_custom_template( $template );
		$details  = mc_draw_template( $data, $template );
	} else {
		switch ( $type ) {
			case 'mini':
				$template = mc_get_template( 'mini' );
				if ( '1' === mc_get_option( 'use_mini_template' ) && '' !== $template ) {
					$details = mc_draw_template( $data, $template );
				}
				break;
			case 'list':
				$template = mc_get_template( 'list' );
				if ( '1' === mc_get_option( 'use_list_template' ) && '' !== $template ) {
					$details = mc_draw_template( $data, $template );
				}
				break;
			case 'single':
				$template = mc_get_template( 'details' );
				if ( '1' === mc_get_option( 'use_details_template' ) && '' !== $template ) {
					$details = mc_draw_template( $data, $template );
				}
				break;
			case 'calendar':
			default:
				$template = mc_get_template( 'grid' );
				if ( '1' === mc_get_option( 'use_grid_template' ) && '' !== $template ) {
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
	if ( in_array( 'large', $sizes, true ) ) {
		$default_size = 'large';
	} else {
		$default_size = 'medium';
	}
	/**
	 * Customize default image size for event output. Default is 'large' if it exists, 'medium' if not.
	 *
	 * @hook mc_default_image_size
	 *
	 * @param {string} $default_size Image size designator.
	 *
	 * @return {string}
	 */
	$default_size = apply_filters( 'mc_default_image_size', $default_size );

	if ( is_numeric( $event->event_post ) && 0 !== (int) $event->event_post && ( isset( $data[ $default_size ] ) && '' !== $data[ $default_size ] ) ) {
		/**
		 * Customize featured image attributes.
		 *
		 * @hook mc_post_thumbnail_atts
		 *
		 * @param {array}  $atts Array of attributes passed to `get_the_post_thumbnail`
		 * @param {object} $event Event object.
		 *
		 * @return {array}
		 */
		$atts  = apply_filters( 'mc_post_thumbnail_atts', array( 'class' => 'mc-image photo' ), $event );
		$image = get_the_post_thumbnail( $event->event_post, $default_size, $atts );
	} else {
		// Get alt attribute from a publicly submitted image.
		if ( property_exists( $event, 'event_post' ) ) {
			$alt = get_post_meta( $event->event_post, '_mcs_submitted_alt', true );
		}
		/**
		 * Customize alt attribute for an event featured image that is not attached to a post.
		 *
		 * @hook mc_event_image_alt
		 *
		 * @param {string} $alt Empty string or user submitted alt attribute.
		 * @param {object} $event Event object.
		 *
		 * @return {string}
		 */
		$alt   = apply_filters( 'mc_event_image_alt', $alt, $event );
		$image = ( '' !== $event->event_image ) ? "<img src='" . esc_url( $event->event_image ) . "' alt='" . esc_attr( $alt ) . "' class='mc-image photo' />" : '';
	}
	$return = true;

	global $template;
	$template_file_name = ( ! is_string( $template ) ) ? '' : basename( $template );
	/**
	 * Fires when displaying an event image in the default template. Return false to show the template image rather than the theme's featured image.
	 *
	 * @hook mc_override_featured_image
	 * @since 3.3.0
	 *
	 * @param {bool}   $return True to return thumbnail in templates.
	 * @param {object} $event Event object.
	 * @param {array}  $data Event template tags.
	 *
	 * @return {bool}
	 */
	$override = apply_filters( 'mc_override_featured_image', $return, $event, $data );
	if ( $override && is_singular( 'mc-events' ) && has_post_thumbnail( $event->event_post ) && current_theme_supports( 'post-thumbnails' ) && ( 'single-mc-events.php' !== $template_file_name ) ) {
		return '';
	}

	return $image;
}

/**
 * If option to disable link is toggled, disable the link.
 *
 * @param boolean $status Default value.
 * @param array   $event Event details.
 *
 * @return boolean
 */
function mc_disable_link( $status, $event ) {
	$option     = mc_get_option( 'no_link' );
	$new_option = mc_get_option( 'open_uri' );
	if ( 'true' === $option || 'none' === $new_option ) {
		$status = true;
	}

	return $status;
}
add_filter( 'mc_disable_link', 'mc_disable_link', 10, 2 );

/**
 * Generate classes for a given event
 *
 * @param object $event Event Object.
 * @param string $type Type of view being shown.
 *
 * @return string classes
 */
function mc_event_classes( $event, $type ) {
	$uid      = 'mc_' . $type . '_' . $event->occur_id;
	$relation = mc_date_relation( $event );
	switch ( $relation ) {
		case 0:
			$date_relation = 'past-event';
			break;
		case 1:
			$date_relation = 'on-now';
			break;
		case 2:
			$date_relation = 'future-event';
			break;
	}
	$primary      = 'mc_primary_' . sanitize_title( mc_get_category_detail( $event->event_category, 'category_name' ) );
	$length       = sanitize_title( 'mc-' . mc_runtime( $event->ts_occur_begin, $event->ts_occur_end, $event ) );
	$start        = sanitize_title( 'mc-start-' . mc_date( 'H-i', $event->ts_occur_begin ) );
	$is_recurring = ( mc_is_recurring( $event ) ) ? 'recurring' : 'nonrecurring';

	$classes = array(
		'mc-' . $uid,
		$type . '-event',
		mc_category_class( $event, 'mc_' ),
		$date_relation,
		$primary,
		$is_recurring,
		$length,
		$start,
	);

	if ( 'single' !== $type ) {
		$classes[] = 'mc-events';
	}

	if ( $event->event_begin !== $event->event_end ) {
		$classes[] = 'multidate';
	}

	if ( 'upcoming' !== $type && 'related' !== $type ) {
		$classes[] = 'mc-event';
	}

	// Adds a number of extra queries; if they aren't needed, leave disabled.
	if ( property_exists( $event, 'categories' ) ) {
		$categories = $event->categories;
	} else {
		$categories = mc_get_categories( $event, false );
	}
	foreach ( $categories as $category ) {
		if ( ! is_object( $category ) ) {
			$category = (object) $category;
		}
		$classes[] = 'mc_rel_' . sanitize_html_class( $category->category_name, 'mcat' . $category->category_id );
	}

	/**
	 * Filter event classes.
	 *
	 * @hook mc_event_classes
	 *
	 * @param {array}  $classes Array of classes for event.
	 * @param {object} $event Event object.
	 * @param {string} $uid Unique ID for this event.
	 * @param {string} $type View type.
	 *
	 * @return {array}
	 */
	$classes    = apply_filters( 'mc_event_classes', array_unique( $classes ), $event, $uid, $type );
	$class_html = strtolower( implode( ' ', $classes ) );

	return esc_attr( $class_html );
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
	/**
	 * Display details links globally from main calendar view.
	 *
	 * @hook mc_disable_link
	 *
	 * @param {bool}  $no_link true to disable link.
	 * @param {array} $array Empty array. (deprecated).
	 *
	 * @return {bool}
	 */
	$no_link = apply_filters( 'mc_disable_link', false, array() );

	return ( ( 'calendar' === $type && 'true' === mc_get_option( 'open_uri' ) && 'day' !== $time ) || $no_link ) ? false : true;
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
	if ( mc_can_edit_event( $event ) && mc_get_option( 'remote' ) !== 'true' ) {
		$mc_id     = $event->occur_id;
		$groupedit = ( 0 !== (int) $event->event_group_id ) ? " &bull; <a href='" . admin_url( "admin.php?page=my-calendar-manage&groups=true&amp;mode=edit&amp;event_id=$event->event_id&amp;group_id=$event->event_group_id" ) . "' class='group'>" . __( 'Edit Group', 'my-calendar' ) . "</a>\n" : '';
		$recurs    = str_split( $event->event_recur, 1 );
		$recur     = $recurs[0];
		$referer   = urlencode( mc_get_current_url() );
		$edit      = "	<div class='mc_edit_links'><p>";
		if ( 'S' === $recur ) {
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
 * Create list of classes for a given date.
 *
 * @param array        $events array of event objects.
 * @param string|false $date current date if a date is being processed.
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
			if ( '00:00:00' === $event->event_endtime && mc_date( 'Y-m-d', strtotime( $event->occur_end ), false ) === $date && mc_date( 'Y-m-d', strtotime( $event->occur_begin ), false ) !== $date ) {
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
	$now   = $events[0];
	$count = count( $events ) - 1;
	/**
	 * Format a single title for display in single titles on list view.
	 *
	 * @hook mc_list_event_title_hint
	 *
	 * @param {string} $title Event title.
	 * @param {object} $now Event object.
	 *
	 * @return {string}
	 */
	$event_title = apply_filters( 'mc_list_title_title', strip_tags( stripcslashes( $now->event_title ), mc_strip_tags() ), $now );
	if ( 0 === $count ) {
		$cstate = $event_title;
	} elseif ( 1 === $count ) {
		// Translators: %s Title of event.
		$cstate = sprintf( __( '%s<span class="mc-list-extended"> and 1 other event</span>', 'my-calendar' ), $event_title );
	} else {
		// Translators: %s Title of event, %d number of other events.
		$cstate = sprintf( __( '%1$s<span class="mc-list-extended"> and %2$d other events</span>', 'my-calendar' ), $event_title, $count );
	}
	/**
	 * Format display output on a single titles on list view.
	 *
	 * @hook mc_list_event_title_hint
	 *
	 * @param {string} $title Event title.
	 * @param {object} $now Event object.
	 * @param {array}  $events Array of event objects.
	 *
	 * @return {string}
	 */
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

	foreach ( $events as $now ) {
		/**
		 * Format a single title for display in multiple titles on list view.
		 *
		 * @hook mc_list_event_title_hint
		 *
		 * @param {string} $title Event title.
		 * @param {object} $now Event object.
		 * @param {array}  $events Array of event objects.
		 *
		 * @return {string}
		 */
		$title    = apply_filters( 'mc_list_event_title_hint', strip_tags( stripcslashes( $now->event_title ), mc_strip_tags() ), $now, $events );
		$titles[] = $title;
	}
	/**
	 * Format titles displayed in multiple titles on list view. Default ''. Returning any value will shortcircuit standard formatting.
	 *
	 * @hook mc_titles_format
	 *
	 * @param {string} $result Custom format of data in results.
	 * @param {array}  $titles Array of titles of events on this date.
	 *
	 * @return {string}
	 */
	$result = apply_filters( 'mc_titles_format', '', $titles );

	if ( '' === $result ) {
		/**
		 * Filter separator used to separate a collection of event titles on the list view day while collapsed.
		 *
		 * @hook mc_list_titles_separator
		 *
		 * @param {string} $separator Separator between titles in list view titles.
		 *
		 * @return {string}
		 */
		$result = implode( apply_filters( 'mc_list_titles_separator', ', ' ), $titles );
	}

	return "<span class='mc-list-event'>$result</span>";
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
		if ( is_object( $post ) && 'mc-events' === $post->post_type ) {
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
			exit;
		} else {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		}
	}
}

/**
 * Check whether a given output field should be displayed.
 *
 * @param string         $feature Feature key.
 * @param string         $type Display type.
 * @param object|boolean $event Event if in event context.
 *
 * @return bool
 */
function mc_output_is_visible( $feature, $type, $event = false ) {
	// Map either calendar popup or list to main settings.
	$type   = ( 'calendar' === $type || 'list' === $type ) ? 'main' : $type;
	$option = mc_get_option( 'display_' . $type );
	if ( ! is_array( $option ) ) {
		$display_type = 'display_' . $type;
		$option       = mc_default_options()[ $display_type ];
	}
	$return = false;
	if ( in_array( $feature, $option, true ) ) {
		$return = true;
	}
	/**
	 * Filter whether any given piece of information should be output.
	 *
	 * @param {bool}           $return Should this piece of data be output.
	 * @param {string}         $feature Feature key.
	 * @param {string}         $type Type of view.
	 * @param {object|boolean} $event Event object if in event context.
	 *
	 * @return bool
	 */
	return apply_filters( 'mc_output_is_visible', $return, $feature, $type, $event );
}

/**
 * Filter titles on event pages
 *
 * @param string $title Event title.
 *
 * @return string New event title
 */
function mc_event_filter( $title ) {
	if ( isset( $_GET['mc_id'] ) && is_numeric( $_GET['mc_id'] ) ) {
		$id    = (int) $_GET['mc_id'];
		$event = mc_get_event( $id );
		if ( ! is_object( $event ) ) {
			return $title;
		}
		if ( mc_event_is_hidden( $event ) ) {
			return $title;
		}
		$array    = mc_create_tags( $event );
		$template = mc_get_option( 'event_title_template', '' );
		$template = ( '' !== $template ) ? stripslashes( $template ) : '{title} / {date}';

		return esc_html( strip_tags( stripslashes( mc_draw_template( $array, $template ) ) ) );
	} else {
		return $title;
	}
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
	if ( ( is_single() || is_page() ) && get_the_ID() === (int) mc_get_option( 'uri_id' ) ) {
		global $post;
		if ( ! has_shortcode( $post->post_content, 'my_calendar' ) ) {
			return $content . do_shortcode( '[my_calendar id="my_calendar_' . get_the_ID() . '"]' );
		}
	}
	if ( is_single() && in_the_loop() && is_main_query() ) {
		// Some early versions of this placed the shortcode into the post content. Strip that out.
		$new_content = $content;
		if ( 'mc-events' === $post->post_type ) {
			$event_id = get_post_meta( $post->ID, '_mc_event_id', true );
			if ( isset( $_GET['mc_id'] ) && mc_valid_id( $_GET['mc_id'] ) ) {
				$mc_id = intval( $_GET['mc_id'] );
				$event = mc_get_event( $mc_id, 'object' );
				$date  = mc_date( 'Y-m-d', strtotime( $event->occur_begin ), false );
				$time  = mc_date( 'H:i:00', strtotime( $event->occur_begin ), false );
			} else {
				if ( is_numeric( $event_id ) ) {
					$event = mc_get_nearest_event( $event_id, true );
					$date  = mc_date( 'Y-m-d', strtotime( $event->occur_begin ), false );
					$time  = mc_date( 'H:i:s', strtotime( $event->occur_begin ), false );
				} else {

					return $content;
				}
			}
			if ( is_object( $event ) && mc_event_is_hidden( $event ) ) {

				return $content;
			}
			if ( '1' === mc_get_option( 'use_details_template' ) ) {
				/**
				 * Prepend content before single event. Default empty string.
				 *
				 * @hook mc_before_event
				 *
				 * @param {string} $new_content Content to prepend before the event.
				 * @param {object} $event Event object.
				 * @param {string} $view View type.
				 * @param {string} $time Time view.
				 *
				 * @return {string}
				 */
				$new_content = apply_filters( 'mc_before_event', '', $event, 'single', $time );
				if ( isset( $_GET['mc_id'] ) ) {
					$shortcode = str_replace( "event='$event_id'", "event='$mc_id' instance='1'", get_post_meta( $post->ID, '_mc_event_shortcode', true ) );
				} else {
					$shortcode = get_post_meta( $post->ID, '_mc_event_shortcode', true );
				}
				/**
				 * Filter shortcode before appending to single event content.
				 *
				 * @hook mc_single_event_shortcode
				 *
				 * @param {string} $shortcode Shortcode for single event.
				 *
				 * @return {string}
				 */
				$new_content .= do_shortcode( apply_filters( 'mc_single_event_shortcode', $shortcode ) );
				/**
				 * Append content after single event. Default empty string.
				 *
				 * @hook mc_after_event
				 *
				 * @param {string} $new_content Content to append after the event.
				 * @param {object} $event Event object.
				 * @param {string} $view View type.
				 * @param {string} $time Time view.
				 *
				 * @return {string}
				 */
				$new_content .= apply_filters( 'mc_after_event', '', $event, 'single', $time );
			} else {
				$new_content = my_calendar_draw_event( $event, 'single', $date, $time, '' );
			}
			/**
			 * Filter single event content prior to running shortcodes.
			 *
			 * @hook mc_event_post_content
			 *
			 * @param {string} $new_content Event content with event shortcode appended.
			 * @param {string} $content Original event content.
			 * @param {WP_Post} $post Post object.
			 *
			 * @return {string}
			 */
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
function mc_list_group( $id, $this_id, $template = '{date}, {time}' ) {
	if ( ! $id ) {
		return;
	}
	$results = mc_get_grouped_events( $id );
	$count   = count( $results );
	$output  = '';
	$classes = '';
	// If a large number of events, skip this.
	/**
	 * How many related events should there be before they are not shown? Default `50`.
	 *
	 * @hook mc_related_event_limit
	 *
	 * @param {int} Number of events where the large limit triggers.
	 *
	 * @return {int}.
	 */
	if ( $count > apply_filters( 'mc_related_event_limit', 50 ) ) {
		/**
		 * For large lists of related events, related events are not shown by default. Only runs if number of related events higher than `mc_related_event_limit`.
		 *
		 * @hook mc_grouped_events
		 *
		 * @param {string} $output HTML output of events to shown. Default empty string.
		 * @param {array}  $results Array of related event objects.
		 *
		 * @return {bool}
		 */
		return apply_filters( 'mc_grouped_events', '', $results );
	}

	if ( is_array( $results ) && ! empty( $results ) ) {
		foreach ( $results as $result ) {
			$event_id = $result->event_id;
			if ( (int) $event_id === (int) $this_id ) {
				continue;
			}

			$event = mc_get_first_event( $event_id );
			if ( is_object( $event ) ) {
				$array = mc_create_tags( $event, 'related' );
				if ( mc_key_exists( $template ) ) {
					$template = mc_get_custom_template( $template );
				}
				$html     = mc_draw_template( $array, $template );
				$classes  = mc_event_classes( $event, 'related' );
				$classes .= ( (int) $event_id === (int) $this_id ) ? ' current-event' : '';
				$output  .= "<li class='$classes'>$html</li>";
			}
		}
	} else {
		$output = '<li>' . __( 'No grouped events', 'my-calendar' ) . '</li>';
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
	if ( 1 === (int) $event->event_approved ) {
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
	// Also hide events that are unpublished if the current user does not have permission to edit.
	if ( ! mc_event_published( $event ) && ! mc_can_edit_event( $event->event_id ) ) {
		return true;
	}
	$category = $event->event_category;
	$private  = mc_get_private_categories();
	/**
	 * Filter whether an event is visible to the current user.
	 *
	 * @hook mc_user_can_see_private_events
	 *
	 * @param {bool}   $can_see 'true' if the event should be shown.
	 * @param {object} $event Event object.
	 *
	 * @return {bool}
	 */
	$can_see = apply_filters( 'mc_user_can_see_private_events', is_user_logged_in(), $event );
	if ( in_array( $category, $private, true ) && ! $can_see ) {

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
	$search   = isset( $args['search'] ) ? $args['search'] : '';
	$site     = ( isset( $args['site'] ) && '' !== trim( $args['site'] ) ) ? $args['site'] : false;
	$months   = isset( $args['months'] ) ? $args['months'] : false;
	$weekends = isset( $args['weekends'] ) ? $args['weekends'] : mc_get_option( 'show_weekends' );

	if ( ! in_array( $format, array( 'list', 'calendar', 'mini' ), true ) ) {
		$format = 'calendar';
	}

	if ( ! in_array( $time, array( 'day', 'week', 'month', 'month+1' ), true ) ) {
		$time = 'month';
	}

	$category = ( isset( $_GET['mcat'] ) ) ? (int) $_GET['mcat'] : $category;
	// This relates to default value inconsistencies, I think.
	if ( '' === $category ) {
		$category = 'all';
	}

	if ( isset( $_GET['format'] ) && in_array( $_GET['format'], array( 'list', 'mini' ), true ) && 'mini' !== $format ) {
		$format = esc_attr( $_GET['format'] );
	} else {
		$format = esc_attr( $format );
	}

	if ( isset( $_GET['time'] ) && in_array( $_GET['time'], array( 'day', 'week', 'month', 'month+1' ), true ) && 'mini' !== $format ) {
		$time = esc_attr( $_GET['time'] );
	} else {
		$time = esc_attr( $time );
	}

	if ( 'day' === $time ) {
		$format = 'list';
	}

	if ( isset( $_GET['mcs'] ) ) {
		$search = $_GET['mcs'];
	}

	/**
	 * Filter the display format.
	 *
	 * @hook mc_display_format
	 *
	 * @param {string} $format Current view format. E.g. 'grid', 'list'.
	 * @param {array}  $args Calendar view arguments.
	 *
	 * @return {string}
	 */
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
		'search'   => $search,
		'weekends' => $weekends,
	);

	// Hash cannot include 'time', 'category', 'search', or 'format', since those can be changed by navigation.
	$hash_args = $params;
	unset( $hash_args['time'] );
	unset( $hash_args['category'] );
	unset( $hash_args['format'] );
	unset( $hash_args['search'] );

	$hash         = md5( implode( ',', $hash_args ) );
	$id           = ( ! $id ) ? "mc-$hash" : $id;
	$params['id'] = $id;

	return $params;
}

/**
 * Generate calendar header if required.
 *
 * @param array  $params Calendar parameters.
 * @param string $id Calendar ID.
 * @param string $tr Table row element.
 * @param int    $start_of_week Starting day of the week.
 *
 * @return string
 */
function mc_get_calendar_header( $params, $id, $tr, $start_of_week ) {
	$days      = mc_get_week_days( $params, $start_of_week );
	$name_days = $days['name_days'];
	$abbrevs   = $days['abbrevs'];
	$weekends  = ( 'true' === $params['weekends'] ) ? true : false;
	/**
	 * Alter the HTML date header element for the grid view. Default `th`
	 *
	 * @hook mc_grid_header_wrapper
	 *
	 * @param {string} $th HTML element tag without `<>`.
	 * @param {string} $format Viewed format.
	 *
	 * @return {string}
	 */
	$th       = apply_filters( 'mc_grid_header_wrapper', 'th', $params['format'] );
	$close_th = ( 'th' === $th ) ? 'th' : $th;
	$th      .= ( 'th' === $th ) ? ' scope="col"' : '';
	$body     = '';
	if ( 'calendar' === $params['format'] || 'mini' === $params['format'] ) {
		/**
		 * Alter the outer wrapper HTML element for the grid view. Default `table`.
		 *
		 * @hook mc_grid_wrapper
		 *
		 * @param {string} $table HTML element tag without `<>`.
		 * @param {string} $format Viewed format.
		 *
		 * @return {string}
		 */
		$table = apply_filters( 'mc_grid_wrapper', 'table', $params['format'] );
		$body .= "\n<$table class='my-calendar-table' aria-labelledby='mc_head_$id'>\n";
	}
	// If in a calendar format, print the headings of the days of the week.
	if ( 'list' === $params['format'] ) {
		$body .= "<ul id='list-$id' class='mc-list'>";
	} else {
		$body .= ( 'tr' === $tr ) ? '<thead>' : '<div class="mc-table-body">';
		$body .= "\n	<$tr class='mc-row'>\n";
		/**
		 * Should the number of the week (1-52) appear in the calendar grid.
		 *
		 * @hook mc_show_week_number
		 *
		 * @param {bool}  $show `true` to add a column with the week number.
		 * @param {array} $params Calendar view arguments.
		 *
		 * @return {bool}
		 */
		if ( apply_filters( 'mc_show_week_number', false, $params ) ) {
			$body .= "		<$th class='mc-week-number'>" . __( 'Week', 'my-calendar' ) . "</$close_th>\n";
		}
		for ( $i = 0; $i <= 6; $i ++ ) {
			if ( 0 === (int) $start_of_week ) {
				$class = ( $i < 6 && $i > 0 ) ? 'day-heading' : 'weekend-heading';
			} else {
				$class = ( $i < 5 ) ? 'day-heading' : 'weekend-heading';
			}
			$dayclass = sanitize_html_class( $abbrevs[ $i ] );
			if ( ( 'weekend-heading' === $class && $weekends ) || 'weekend-heading' !== $class ) {
				$body .= "		<$th class='$class $dayclass'>" . $name_days[ $i ] . "</$close_th>\n";
			}
		}
		$body .= "	</$tr>\n";
		$body .= ( 'tr' === $tr ) ? "</thead>\n<tbody>\n" : '';
	}

	return '<div class="mc-content">' . $body;
}

/**
 * Handle switching languages in shortcodes.
 *
 * @param string $current Current language set.
 * @param string $switch  Language to switch to.
 *
 * @return string HTML attribute.
 */
function mc_switch_language( $current, $switch ) {
	$available = get_available_languages();
	$lang      = '';
	if ( in_array( $switch, $available, true ) ) {
		if ( $switch && ( $current !== $switch ) ) {
			switch_to_locale( $switch );
		}
		$lang = explode( '_', $switch )[0];
	}

	return $lang;
}

/**
 * Create calendar output and return.
 *
 * @param array $args Lots of arguments; all shortcode parameters, etc.
 *
 * @return string HTML output of calendar
 */
function my_calendar( $args ) {
	$language = isset( $args['language'] ) ? $args['language'] : '';
	$switched = '';
	if ( $language ) {
		$locale   = get_locale();
		$switched = mc_switch_language( $locale, $language );
	}
	$template = isset( $args['template'] ) ? $args['template'] : '';
	$content  = isset( $args['content'] ) ? $args['content'] : '';
	$source   = isset( $args['source'] ) ? $args['source'] : 'shortcode';
	$site     = ( isset( $args['site'] ) && '' !== trim( $args['site'] ) ) ? $args['site'] : false;
	$months   = isset( $args['months'] ) ? $args['months'] : false;

	// Get options before switching sites in multisite environments.
	$list_js_class = ( '1' !== mc_get_option( 'list_javascript' ) ) ? 'listjs' : '';
	$grid_js_class = ( '1' !== mc_get_option( 'calendar_javascript' ) ) ? 'gridjs' : '';
	$mini_js_class = ( '1' !== mc_get_option( 'mini_javascript' ) ) ? 'minijs' : '';
	$ajax_js_class = ( '1' !== mc_get_option( 'ajax_javascript' ) ) ? 'ajaxjs' : '';
	$style_class   = sanitize_html_class( str_replace( '.css', '', mc_get_option( 'css_file' ) ) );
	$date_format   = mc_date_format();
	$start_of_week = ( get_option( 'start_of_week' ) === '1' ) ? 1 : 7; // convert start of week to ISO 8601 (Monday/Sunday).
	$month_format  = ( mc_get_option( 'month_format', '' ) === '' ) ? 'F Y' : mc_get_option( 'month_format' );
	/**
	 * Filter how many months to show in list views.
	 *
	 * @hook mc_show_months
	 *
	 * @param {int}   $show_months Number of months to show at once.
	 * @param {array} $args Current view arguments.
	 *
	 * @return {int}
	 */
	$show_months  = absint( apply_filters( 'mc_show_months', mc_get_option( 'show_months' ), $args ) );
	$show_months  = ( 0 === $show_months ) ? 1 : $show_months;
	$caption_text = ( '' !== mc_get_option( 'caption' ) ) ? ' <span class="mc-extended-caption">' . stripslashes( trim( mc_get_option( 'caption' ) ) ) . '</span>' : '';
	$week_format  = ( mc_get_option( 'week_format' ) ) ? mc_get_option( 'week_format' ) : 'M j, \'y';
	// Translators: Template tag with date format.
	$week_template = ( mc_get_option( 'week_caption', '' ) !== '' ) ? mc_get_option( 'week_caption' ) : sprintf( __( 'Week of %s', 'my-calendar' ), '{date format="M jS"}' );
	$open_day_uri  = ( ! mc_get_option( 'open_day_uri' ) ) ? 'false' : mc_get_option( 'open_day_uri' ); // This is not a URL. It's a behavior reference.
	$list_info     = mc_get_option( 'show_list_info' );
	$list_events   = mc_get_option( 'show_list_events' );

	if ( $site ) {
		$site = ( 'global' === $site ) ? BLOG_ID_CURRENT_SITE : $site;
		switch_to_blog( $site );
	}
	my_calendar_check();

	$params = mc_calendar_params( $args );
	/**
	 * Prepend content before the calendar.
	 *
	 * @hook mc_before_calendar
	 *
	 * @param {string} $before HTML content.
	 * @param {array}  $params Current view arguments.
	 *
	 * @return {string}
	 */
	$body = apply_filters( 'mc_before_calendar', '', $params );

	$show_weekends = ( 'true' === $params['weekends'] ) ? true : false;
	$id            = $params['id'];
	$main_class    = ( '' !== $id ) ? sanitize_title( $id ) : 'all';
	$cid           = ( isset( $_GET['cid'] ) ) ? esc_attr( strip_tags( $_GET['cid'] ) ) : $main_class;
	$lang          = ( $switched ) ? ' lang="' . esc_attr( $switched ) . '"' : '';
	$mc_wrapper    = "
<div id=\"$id\" class=\"mc-main mcjs $list_js_class $grid_js_class $mini_js_class $ajax_js_class $style_class $params[format] $params[time] $main_class\" $lang>";
	$mc_closer     = '
</div>';

	/**
	 * Filter date format in main calendar view.
	 *
	 * @hook mc_date_format
	 *
	 * @param {string} $date_format Date format in PHP date format structure.
	 * @param {string} $format Current view format.
	 * @param {string} $time Current view time frame.
	 *
	 * @return {string}
	 */
	$date_format = apply_filters( 'mc_date_format', $date_format, $params['format'], $params['time'] );
	/**
	 * Main heading level. Default `h2`.
	 *
	 * @hook mc_heading_level
	 *
	 * @param {string} $h1 Main heading level.
	 * @param {string} $format Current view format.
	 * @param {string} $time Current view time frame.
	 * @param {string} $template Current view template.
	 *
	 * @return {string}
	 */
	$hl = apply_filters( 'mc_heading_level', 'h2', $params['format'], $params['time'], $template );

	if ( isset( $_GET['mc_id'] ) && 'widget' !== $source ) {
		// single event, main calendar only.
		$mc_id = ( is_numeric( $_GET['mc_id'] ) ) ? $_GET['mc_id'] : false;
		if ( $mc_id ) {
			$body .= mc_get_event( $mc_id, 'html' );
		}
	} else {
		$end_of_week   = ( 1 === (int) $start_of_week ) ? 7 : 6;
		$start_of_week = ( $show_weekends ) ? $start_of_week : 1;
		$date          = mc_get_current_date( $main_class, $cid, $params );
		$current       = $date['current_date'];

		if ( is_numeric( $months ) && $months < 12 && $months > 0 ) {
			$show_months = absint( $months );
		}

		$dates = mc_get_from_to( $show_months, $params, $date );
		/**
		 * Filter the calendar start date.
		 *
		 * @hook mc_from_date
		 *
		 * @param {string} $from Start date of events shown in main calendar shortcode in format `yyyy-mm-dd`.
		 *
		 * @return {string}
		 */
		$from = apply_filters( 'mc_from_date', $dates['from'] );
		/**
		 * Filter the calendar end date.
		 *
		 * @hook mc_to_date
		 *
		 * @param {string} $to End date of events shown in main calendar shortcode in format `yyyy-mm-dd`.
		 *
		 * @return {string}
		 */
		$to    = apply_filters( 'mc_to_date', $dates['to'] );
		$from  = ( 'day' === $params['time'] ) ? mc_date( 'Y-m-d', $current, false ) : $from;
		$to    = ( 'day' === $params['time'] ) ? mc_date( 'Y-m-d', $current, false ) : $to;
		$query = array(
			'from'     => $from,
			'to'       => $to,
			'category' => $params['category'],
			'ltype'    => $params['ltype'],
			'lvalue'   => $params['lvalue'],
			'author'   => $params['author'],
			'host'     => $params['host'],
			'search'   => $params['search'],
			'source'   => 'calendar',
			'site'     => $site,
		);
		/**
		 * Filter calendar view attributes.
		 *
		 * @hook mc_calendar_attributes
		 *
		 * @param {array} $query Current view arguments.
		 * @param {array} $params Shortcode parameters passed to view.
		 *
		 * @return {array}
		 */
		$query = apply_filters( 'mc_calendar_attributes', $query, $params );
		if ( 'mc-print-view' === $id && isset( $_GET['searched'] ) && $_GET['searched'] ) {
			$event_array = mc_get_searched_events();
			if ( ! empty( $event_array ) ) {
				reset( $event_array );
				$from = key( $event_array );
				end( $event_array );
				$to = key( $event_array );
			}
		} else {
			$event_array = my_calendar_events( $query );
		}
		$no_events = ( empty( $event_array ) ) ? true : false;

		$nav    = mc_generate_calendar_nav( $params, $args['category'], $start_of_week, $show_months, $main_class, $site, $date, $from );
		$top    = $nav['top'];
		$bottom = $nav['bottom'];

		if ( 'day' === $params['time'] ) {
			/**
			 * Filter the main calendar heading content in single day view.
			 *
			 * @hook mc_heading
			 *
			 * @param {string} $heading HTML heading for calendar.
			 * @param {string} $format Viewed format.
			 * @param {string} $time Time frame currently viewed.
			 *
			 * @return {string}
			 */
			$heading      = "<$hl id='mc_head_$id' class='mc-single heading my-calendar-$params[time]'><span>" . apply_filters( 'mc_heading', date_i18n( $date_format, $current ), $params['format'], $params['time'] ) . "</span></$hl>";
			$dateclass    = mc_dateclass( $current );
			$mc_events    = '';
			$events       = my_calendar_events( $query );
			$events_class = '';
			$json         = '';
			foreach ( $events as $day ) {
				$events_class = mc_events_class( $day, $from );
				$event_output = my_calendar_draw_events( $day, $params, $from, $template, $id );
				if ( ! empty( $event_output ) ) {
					$mc_events .= $event_output['html'];
					$json       = array( $event_output['json'] );
				}
			}
			$body .= $heading . $top . '
			<div class="mc-content">
				<div id="mc-day-' . $id . '" class="mc-day ' . $dateclass . ' ' . $events_class . '">
					' . "$mc_events
				</div>
			</div><!-- .mc-content -->";
		} else {
			// If showing multiple months, figure out how far we're going.
			$months       = ( 'week' === $params['time'] ) ? 1 : $show_months;
			$through_date = mktime( 0, 0, 0, $date['month'] + ( $months - 1 ), $date['day'], $date['year'] );
			if ( 'month+1' === $params['time'] ) {
				$current_header = date_i18n( $month_format, strtotime( '+1 month', $current ) );
			} else {
				$current_header = date_i18n( $month_format, $current );
			}
			$current_month_header = ( mc_date( 'Y', $current, false ) === mc_date( 'Y', $through_date, false ) ) ? date_i18n( 'F', $current ) : date_i18n( 'F Y', $current );
			$through_month_header = date_i18n( $month_format, $through_date );
			$values               = array( 'date' => mc_date( 'Y-m-d', $current, false ) );

			// Determine which header text to show depending on format & time period displayed.
			if ( 'week' !== $params['time'] && 'day' !== $params['time'] ) {
				$heading = ( $months <= 1 ) ? $current_header . "\n" : $current_month_header . '&ndash;' . $through_month_header;
				// Translators: time period displayed.
				$header  = ( '' === mc_get_option( 'heading_text', '' ) ) ? __( 'Events in %s', 'my-calendar' ) : str_replace( '{date}', '%s', mc_get_option( 'heading_text' ) );
				$heading = sprintf( $header, $heading ) . $caption_text;
				if ( isset( $_GET['searched'] ) && 1 === (int) $_GET['searched'] ) {
					$heading = __( 'Search Results', 'my-calendar' );
				}
			} else {
				$heading = mc_draw_template( $values, stripslashes( $week_template ) );
			}
			/**
			 * Filter the main calendar heading level. Default `h2`.
			 *
			 * @hook mc_heading_level
			 *
			 * @param {string} $heading HTML heading element.
			 * @param {string} $format Viewed format.
			 * @param {string} $time Time frame currently viewed.
			 * @param {string} $template Heading template.
			 *
			 * @return {string}
			 */
			$h2 = apply_filters( 'mc_heading_level', 'h2', $params['format'], $params['time'], $template );
			/**
			 * Filter the main calendar heading in multiday views.
			 *
			 * @hook mc_heading
			 *
			 * @param {string} $heading HTML heading for calendar.
			 * @param {string} $format Viewed format.
			 * @param {string} $time Time frame currently viewed.
			 * @param {string} $template Heading template.
			 *
			 * @return {string}
			 */
			$heading = apply_filters( 'mc_heading', $heading, $params['format'], $params['time'], $template );
			$body   .= "<$h2 id=\"mc_head_$id\" class=\"heading my-calendar-$params[time]\"><span>$heading</span></$h2>\n";
			$body   .= $top;

			/**
			 * Alter the outer wrapper HTML element for the grid view. Default `table`.
			 *
			 * @hook mc_grid_wrapper
			 *
			 * @param {string} $table HTML element tag without `<>`.
			 * @param {string} $format Viewed format.
			 *
			 * @return {string}
			 */
			$table = apply_filters( 'mc_grid_wrapper', 'table', $params['format'] );
			/**
			 * Change the element wrapping a week of events in grid view. Default `tr`.
			 *
			 * @hook mc_grid_week_wrapper
			 *
			 * @param {string} $format Date format per PHP date formatting.
			 * @param {string} $format Current view format.
			 *
			 * @return {string}
			 */
			$tr    = apply_filters( 'mc_grid_week_wrapper', 'tr', $params['format'] );
			$body .= mc_get_calendar_header( $params, $id, $tr, $start_of_week );
			$odd   = 'odd';

			/**
			 * Change whether list format removes dates with no events.
			 *
			 * @hook mc_all_list_dates
			 *
			 * @param {bool}  $show_all `true` to show all dates in list format.
			 * @param {array} $args Array of view arguments.
			 *
			 * @return {bool}
			 */
			$show_all = apply_filters( 'mc_all_list_dates', false, $args );
			if ( $no_events && 'list' === $params['format'] && false === $show_all ) {
				// If there are no events in list format, just display that info.
				$no_events = ( '' === $content ) ? __( 'There are no events scheduled during these dates.', 'my-calendar' ) : $content;
				$body     .= "<li class='mc-events no-events'>$no_events</li>";
			} else {
				$start             = strtotime( $from );
				$end               = strtotime( $to );
				$week_number_shown = false;
				$json              = array();
				do {
					$date_is    = mc_date( 'Y-m-d', $start, false );
					$is_weekend = ( (int) mc_date( 'N', $start, false ) < 6 ) ? false : true;
					if ( $show_weekends || ( ! $show_weekends && ! $is_weekend ) ) {
						if ( mc_date( 'N', $start, false ) === (string) $start_of_week && 'list' !== $params['format'] ) {
							$body .= "<$tr class='mc-row'>";
						}
						$events      = ( isset( $event_array[ $date_is ] ) ) ? $event_array[ $date_is ] : array();
						$week_header = date_i18n( $week_format, $start );
						/**
						 * Alter the date format used to show the current day in grid view. Default 'j'.
						 *
						 * @hook mc_grid_date
						 *
						 * @param {string} $format Date format per PHP date formatting.
						 * @param {array}  $params Array of view arguments.
						 *
						 * @return {string}
						 */
						$thisday_heading = ( 'week' === $params['time'] ) ? "<small>$week_header</small>" : mc_date( apply_filters( 'mc_grid_date', 'j', $params ), $start, false );
						$month_heading   = '';
						$has_month       = '';
						// Generate event classes & attributes.
						$events_class = mc_events_class( $events, $date_is );
						if ( $months > 1 ) {
							$monthclass = ( mc_date( 'n', $start, false ) === (string) (int) $date['month'] || 'month' !== $params['time'] ) ? '' : 'month-' . mc_date( 'm', $start, false );
							if ( mc_date( 'j', $start, false ) === '1' ) {
								$month_heading = '<h3 class="mc-change-months">' . date_i18n( 'F', $start ) . '</h3>';
								$has_month     = ' has-month';
							}
						} else {
							$monthclass = ( mc_date( 'n', $start, false ) === (string) (int) $date['month'] || 'month' !== $params['time'] ) ? '' : 'nextmonth';
						}
						$dateclass   = mc_dateclass( $start );
						$ariacurrent = ( false !== strpos( $dateclass, 'current-day' ) ) ? ' aria-current="date"' : '';

						$is_past_date = false;
						if ( false !== stripos( $dateclass, 'past-day' ) ) {
							$is_past_date = true;
						}
						$hide_past_dates = ( 'true' === mc_get_option( 'hide_past_dates' ) ) ? true : false;
						/**
						 * Filter whether past dates are hidden in the initial list view. Dates are only hidden when no date parameters are set in the URL.
						 *
						 * @hook mc_hide_past_dates
						 *
						 * @param {bool}  $hide_past_dates Whether to hide past dates.
						 * @param {array} $params Current view parameters.
						 */
						$hide_past_dates = apply_filters( 'mc_hide_past_dates', $hide_past_dates, $params );

						/**
						 * Alter the date wrapper HTML element for the grid view. Default `td`.
						 *
						 * @hook mc_grid_day_wrapper
						 *
						 * @param {string} $td HTML element tag without `<>`.
						 * @param {string} $format Viewed format.
						 *
						 * @return {string}
						 */
						$td = apply_filters( 'mc_grid_day_wrapper', 'td', $params['format'] );
						if ( ! $week_number_shown ) {
							$weeknumber = mc_show_week_number( $events, $args, $params['format'], $td, $start );
							if ( ! ( '' === $weeknumber ) ) {
								$body             .= $weeknumber;
								$week_number_shown = true;
							}
						}
						$label       = date_i18n( mc_date_format(), $start );
						$modal_attrs = "id='{format}-modal-button-$date_is' data-modal-content-id='{target_id}' data-modal-prefix-class='my-calendar' data-modal-close-text='" . esc_attr( __( 'Close', 'my-calendar' ) ) . "' data-modal-title='" . esc_attr( $label ) . "'";

						if ( ! empty( $events ) ) {
							/**
							 * Hide events in next or previous month in grid view.
							 *
							 * @hook mc_hide_nextmonth
							 *
							 * @param {bool} $hide true to hide events not in the currently viewed month.
							 *
							 * @return {bool}
							 */
							$hide_nextmonth = apply_filters( 'mc_hide_nextmonth', false );
							if ( true === $hide_nextmonth && 'nextmonth' === $monthclass ) {
								$event_output = ' ';
							} else {
								if ( 'mini' === $params['format'] && 'false' !== $open_day_uri ) {
									$event_output = ' ';
								} else {
									$events_array = my_calendar_draw_events( $events, $params, $date_is, $template, $id );
									$event_output = ' ';
									if ( ! empty( $events_array ) ) {
										$event_output = $events_array['html'];
										$json[]       = $events_array['json'];
									}
								}
							}
							if ( true === $event_output ) {
								$event_output = ' ';
							}
							if ( 'mini' === $params['format'] && '' !== $event_output ) {
								$minitype = mc_get_option( 'mini_javascript' );
								if ( 'modal' === $minitype ) {
									$attrs   = str_replace( array( '{format}', '{target_id}' ), array( 'mini-', 'date-' . $date_is ), $modal_attrs );
									$trigger = ' js-modal button';
								} else {
									$attrs   = " aria-expanded='false'";
									$trigger = ' trigger';
								}
								$link    = mc_build_mini_url( $start, $params['category'], $events, $args, $date );
								$element = "a $attrs href='$link'";
								$close   = 'a';
							} else {
								$element = 'span';
								$close   = 'span';
								$trigger = '';
							}
							// set up events.
							if ( ( $is_weekend && $show_weekends ) || ! $is_weekend ) {
								$weekend_class = ( $is_weekend ) ? 'weekend' : '';
								if ( 'list' === $params['format'] ) {
									if ( 'true' === $list_info ) {
										$inner = '';
										$title = '<span class="mc-list-details-separator"> - </span>' . "<span class='mc-list-details select-event'>" . mc_list_title( $events ) . '</span>';
									} elseif ( 'true' === $list_events ) {
										$inner = '';
										$title = '<span class="mc-list-details-separator"> - </span>' . "<span class='mc-list-details all-events'>" . mc_list_titles( $events ) . '</span>';
									} else {
										$title = '';
										// Translators: Number of events on this date.
										$inner = ' <span class="mc-list-details event-count">(' . sprintf( _n( '%d event', '%d events', count( $events ), 'my-calendar' ), count( $events ) ) . ')</span>';
									}
									if ( '' !== $event_output ) {
										$date_params_set = ( isset( $_GET['month'] ) || isset( $_GET['dy'] ) || isset( $_GET['yr'] ) ) ? true : false;
										if ( $is_past_date && $hide_past_dates && ! $date_params_set ) {
											$body .= '<!-- Date Hidden -->';
										} else {
											$attrs    = '';
											$listtype = mc_get_option( 'list_javascript' );
											if ( 'modal' === $listtype ) {
												$attrs = str_replace( array( '{format}', '{target_id}' ), array( 'list-', 'list-date-' . $date_is ), $modal_attrs );
											}
											if ( 'false' === mc_get_option( 'list_link_titles' ) ) {
												$body .= "<li id='$params[format]-$date_is'$ariacurrent class='mc-events $dateclass $events_class $odd'>
													<strong class=\"event-date\">" . mc_wrap_title( '<span>' . date_i18n( $date_format, $start ) . $inner . '</span>', $attrs ) . "$title</strong>
													<div id='list-date-" . $date_is . "' class='mc-list-date-wrapper'>
													" . $event_output . '
													</div>
												</li>';
											} else {
												$body .= "<li id='$params[format]-$date_is'$ariacurrent class='mc-events $dateclass $events_class $odd'><h2 class=\"event-date\">" . '<span>' . date_i18n( $date_format, $start ) . $inner . '</span>' . "$title</h2>" . $event_output . '</li>';
											}
											$odd = ( 'odd' === $odd ) ? 'even' : 'odd';
										}
									}
								} else {
									$marker = ( count( $events ) > 1 ) ? '&#9679;&#9679;' : '&#9679;';
									// Translators: Number of events on this date.
									$inner = ( count( $events ) > 0 ) ? '<span class="event-icon" aria-hidden="true">' . $marker . '</span><span class="screen-reader-text"><span class="mc-list-details event-count">(' . sprintf( _n( '%d event', '%d events', count( $events ), 'my-calendar' ), count( $events ) ) . ')</span></span>' : '';
									$body .= "<$td id='$params[format]-$date_is'$ariacurrent class='$dateclass $weekend_class $monthclass $events_class day-with-date'><div class='mc-date-container$has_month'>$month_heading" . "\n	<$element class='mc-date$trigger'><span aria-hidden='true'>$thisday_heading</span><span class='screen-reader-text'>" . date_i18n( $date_format, strtotime( $date_is ) ) . "</span>$inner</$close></div>" . $event_output . "\n</$td>\n";
								}
							}
						} else {
							// If there are no events on this date within current params.
							if ( 'list' !== $params['format'] ) {
								$weekend_class = ( $is_weekend ) ? 'weekend' : '';
								$body         .= "<$td$ariacurrent class='no-events $dateclass $weekend_class $monthclass $events_class day-with-date'><div class='mc-date-container$has_month'>$month_heading<span class='mc-date no-events'><span aria-hidden='true'>$thisday_heading</span><span class='screen-reader-text'>" . date_i18n( $date_format, strtotime( $date_is ) ) . "</span></span></div>\n</$td>\n";
							} else {
								if ( true === $show_all ) {
									$body .= "<li id='$params[format]-$date_is' $ariacurrent class='no-events $dateclass $events_class $odd'><strong class=\"event-date\">" . mc_wrap_title( '<span>' . date_i18n( $date_format, $start ) . '</span>' ) . '</strong></li>';
									$odd   = ( 'odd' === $odd ) ? 'even' : 'odd';
								}
							}
						}

						if ( mc_date( 'N', $start, false ) === (string) $end_of_week || ( mc_date( 'N', $start, false ) === '5' && ! $show_weekends ) ) {
							if ( 'list' !== $params['format'] ) {
								$body .= "</$tr>\n<!-- End Event Row -->\n"; // End of 'is beginning of week'.
							}
							$week_number_shown = false;
						}
					}
					$start = strtotime( '+1 day', $start );

				} while ( $start <= $end );
			}

			$end   = ( 'table' === $table ) ? "\n</tbody>\n</table>" : "</div></$table>";
			$body .= ( 'list' === $params['format'] ) ? "\n</ul>" : $end;
		}
		// For clarity, day closer is appended above.
		$body .= ( 'day' === $params['time'] ) ? '' : '</div><!-- .mc-content -->';
		$body .= $bottom;
	}
	/**
	 * Append content after the calendar.
	 *
	 * @hook mc_after_calendar
	 *
	 * @param {string} $after HTML content.
	 * @param {array}  $args Current view arguments.
	 *
	 * @return {string}
	 */
	$body .= apply_filters( 'mc_after_calendar', '', $args );

	if ( $site ) {
		restore_current_blog();
	}
	$json_ld = '';
	if ( ! is_admin() ) {
		if ( ! empty( $json ) && is_array( $json ) ) {
			$json_ld = json_encode( map_deep( $json, 'esc_html' ), JSON_UNESCAPED_SLASHES );
			$json_ld = PHP_EOL . '<script type="application/ld+json">' . PHP_EOL . $json_ld . PHP_EOL . '</script>' . PHP_EOL;
		}
	}

	/**
	 * Filter the calendar shortcode main body output.
	 *
	 * @hook my_calendar_body
	 *
	 * @param {string} $body HTML output.
	 *
	 * @return {string}
	 */
	$output = $mc_wrapper . $json_ld . apply_filters( 'my_calendar_body', $body ) . $mc_closer;
	if ( $language ) {
		mc_switch_language( $language, $locale );
	}
	return $output;
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
	/**
	 * Should the number of the week (1-52) appear in the calendar grid.
	 *
	 * @hook mc_show_week_number
	 *
	 * @param {bool} $show `true` to add a column with the week number.
	 * @param {array} $args Calendar view arguments.
	 *
	 * @return {bool}
	 */
	if ( apply_filters( 'mc_show_week_number', false, $args ) ) {
		$weeknumber = mc_date( 'W', $start, false );
		if ( 'list' !== $format ) {
			$body = "<$td class='week_number'>$weeknumber</$td>";
		}
		if ( 'list' === $format && ! empty( $events ) ) {
			$body = "<li class='mc-week-number'><span class='week-number-text'>" . __( 'Week', 'my-calendar' ) . "</span> <span class='week-number-number'>$weeknumber</span></li>";
		}
	}

	return $body;
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
	if ( 'true' === mc_get_option( 'convert' ) ) {
		$format = ( mc_is_mobile() && 'calendar' === $format ) ? 'list' : $format;
	} elseif ( 'mini' === mc_get_option( 'convert' ) ) {
		$format = ( mc_is_mobile() ) ? 'mini' : $format;
	}

	return $format;
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
	$time   = $params['time'];
	$smonth = $params['smonth'];
	$syear  = $params['syear'];
	$sday   = $params['sday'];
	$c_m    = 0;
	$dm     = array();
	if ( isset( $_GET['dy'] ) && $main_class === $cid && ( 'day' === $time || 'week' === $time ) ) {
		if ( '' === $_GET['dy'] ) {
			$today = current_time( 'j' );
			$month = ( isset( $_GET['month'] ) ) ? $_GET['month'] : current_time( 'n' );
			$year  = ( isset( $_GET['yr'] ) ) ? $_GET['yr'] : current_time( 'Y' );
			$time  = strtotime( "$year-$month-$today" );
			$dm    = mc_first_day_of_week( $time );
			$c_day = $dm[0];
			$c_m   = $dm[1];
		} else {
			$c_day = (int) $_GET['dy'];
		}
	} else {
		if ( 'week' === $time ) {
			$dm    = mc_first_day_of_week();
			$c_day = $dm[0];
			$c_m   = $dm[1];
		} elseif ( 'day' === $time ) {
			$c_day = current_time( 'd' );
		} else {
			$c_day = 1;
		}
	}
	if ( isset( $_GET['month'] ) && $main_class === $cid ) {
		$c_month = (int) $_GET['month'];
		if ( ! isset( $_GET['dy'] ) ) {
			$c_day = 1;
		}
	} else {
		$xnow    = current_time( 'Y-m-d' );
		$c_month = ( 0 === (int) $c_m ) ? current_time( 'm' ) : mc_date( 'm', strtotime( $xnow . ' -1 month' ), false );
	}

	$is_start_of_week = ( get_option( 'start_of_week' ) === current_time( 'N' ) ) ? true : false;
	if ( isset( $_GET['yr'] ) && $main_class === $cid ) {
		$c_year = (int) $_GET['yr'];
	} else {
		if ( 'week' === $time && ! isset( $_GET['dy'] ) ) {
			if ( $is_start_of_week ) {
				$c_year = ( current_time( 'Y' ) );
			} else {
				$current_year = (int) current_time( 'Y' );
				$c_year       = ( 0 === (int) $dm[1] ) ? $current_year : false;
				if ( ! $c_year ) {
					$c_year = ( (int) mc_date( 'Y', strtotime( '-1 month' ), false ) === $current_year ) ? $current_year : $current_year - 1;
				}
			}
		} else {
			$c_year = ( current_time( 'Y' ) );
		}
	}
	// Years get funny if we exceed 3000, so we use this check.
	if ( ! ( $c_year <= 3000 && $c_year >= 0 ) ) {
		// No valid year causes the calendar to default to today.
		$c_year  = current_time( 'Y' );
		$c_month = current_time( 'm' );
		$c_day   = current_time( 'd' );
	}
	if ( ! ( isset( $_GET['yr'] ) || isset( $_GET['month'] ) || isset( $_GET['dy'] ) ) ) {
		// Month/year based on shortcode.
		$shortcode_month = ( false !== $smonth ) ? $smonth : $c_month;
		$shortcode_year  = ( false !== $syear ) ? $syear : $c_year;
		$shortcode_day   = ( false !== $sday ) ? $sday : $c_day;
		/**
		 * Filter the starting year for the [my_calendar] shortcode.
		 *
		 * @hook mc_filter_year
		 *
		 * @param {int} $year An integer between 0 and 3000.
		 * @param {array} Shortcode parameters.
		 *
		 * @return {int}
		 */
		$c_year = apply_filters( 'mc_filter_year', $shortcode_year, $params );
		/**
		 * Filter the starting month for the [my_calendar] shortcode.
		 *
		 * @hook mc_filter_month
		 *
		 * @param {int} $month An integer between 1 and 12.
		 * @param {array} Shortcode parameters.
		 *
		 * @return {int}
		 */
		$c_month = apply_filters( 'mc_filter_month', $shortcode_month, $params );
		/**
		 * Filter the starting day for the [my_calendar] shortcode.
		 *
		 * @hook mc_filter_day
		 *
		 * @param {int} $day An integer between 1 and 31.
		 * @param {array} Shortcode parameters.
		 *
		 * @return {int}
		 */
		$c_day = apply_filters( 'mc_filter_day', $shortcode_day, $params );
	}
	$c_day   = ( 0 === (int) $c_day ) ? 1 : $c_day; // c_day can't equal 0.
	$current = mktime( 0, 0, 0, (int) $c_month, (int) $c_day, (int) $c_year );
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
	/**
	 * Disable shortcode parsing in content. Default 'true'.
	 *
	 * @hook mc_process_shortcodes
	 *
	 * @param {string} $return String 'true' to run do_shortcode() on calendar content.
	 *
	 * @return {string}
	 */
	$content = ( 'true' === apply_filters( 'mc_process_shortcodes', 'true' ) ) ? do_shortcode( $content ) : $content;

	return $content;
}

/**
 * Set up button wrapping event title
 *
 * @param string $title Event title.
 * @param string $params Additional attributes if modal enabled.
 *
 * @return string title with wrapper if appropriate
 */
function mc_wrap_title( $title, $params = '' ) {
	if ( '1' !== mc_get_option( 'list_javascript' ) ) {
		$uses_modal = '';
		if ( 'modal' === mc_get_option( 'list_javascript' ) ) {
			$uses_modal = ' js-modal';
		}
		$is_anchor       = '<button type="button" ' . $params . ' class="mc-text-button' . $uses_modal . '">';
		$is_close_anchor = '</button>';
	} else {
		$is_anchor       = '';
		$is_close_anchor = '';
	}

	return $is_anchor . $title . $is_close_anchor;
}

/**
 * Default My Calendar search form.
 *
 * @param string $type Type of search.
 * @param string $url URL to post query to.
 * @param string $id Calendar view ID.
 *
 * @return string HTML form.
 */
function my_calendar_searchform( $type, $url = '', $id = 'events' ) {
	$query = ( isset( $_GET['mcs'] ) ) ? $_GET['mcs'] : '';
	if ( 'simple' === $type ) {
		if ( ! $url ) {
			$url = mc_get_uri( false, array( 'type' => $type ) );
		}
		/**
		 * Filter the target action URL for search query form. Should target a page with a [my_calendar} shortcode.
		 *
		 * @hook mc_search_page
		 *
		 * @param {string} $url Target URL.
		 *
		 * @return {string}
		 */
		$url = apply_filters( 'mc_search_page', $url );
		return '
		<div class="mc-search-container" role="search">
			<form class="mc-search-form" method="get" action="' . esc_url( $url ) . '" >
				<div class="mc-search">
					<label class="screen-reader-text" for="mc_query_search-' . $id . '">' . __( 'Search Events', 'my-calendar' ) . '</label><input id="mc_query_search-' . $id . '" type="text" value="' . esc_attr( stripslashes( urldecode( $query ) ) ) . '" name="mcs" /><input type="submit" data-href="' . esc_url( add_query_arg( 'source', 'widget', $url ) ) . '" class="button" id="mc_submit_search-' . $id . '" value="' . __( 'Search Events', 'my-calendar' ) . '" />
				</div>
			</form>
		</div>';
	}

	return '';
}

/**
 * Get list of locations.
 *
 * @param string  $datatype Type of data to sort by and return.
 * @param boolean $full If need to return full location object.
 * @param string  $return_type valid query return type.
 *
 * @return array of location objects.
 */
function mc_get_list_locations( $datatype, $full = true, $return_type = 'OBJECT' ) {
	$mcdb        = mc_is_remote_db();
	$return_type = ( 'OBJECT' === $return_type ) ? OBJECT : ARRAY_A;

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
	/**
	 * Filter the `where` clause of the location list SQL query. Default empty string.
	 *
	 * @hook mc_filter_location_list
	 *
	 * @param {string} $where WHERE portion of a SQL query.
	 * @param {string} $datatype Type of data this list is ordered by.
	 *
	 * @return {string}
	 */
	$where = esc_sql( apply_filters( 'mc_filter_location_list', '', $datatype ) );
	if ( true !== $full ) {
		$select = esc_sql( $data );
	} else {
		$select = '*';
	}
	// Value of $data is set in switch above. $select is same as data unless *.
	$locations = $mcdb->get_results( "SELECT DISTINCT $select FROM " . my_calendar_locations_table() . " $where ORDER BY $data ASC", $return_type ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

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
	$datatype  = ( 'name' === $datatype ) ? 'location_label' : $datatype;
	$output    = '';
	if ( $locations ) {
		if ( 'map' === $template ) {
			$output = mc_generate_map( $locations, 'location', true );
		} else {
			// If no template provided, return hcard.
			$datatype = ( '' === trim( $template ) ) ? 'hcard' : $datatype;
			foreach ( $locations as $key => $value ) {
				if ( 'hcard' !== $datatype && '' === $template ) {
					$label = stripslashes( $value->{$datatype} );
					if ( $label ) {
						$url     = mc_maplink( $value, 'url', 'location' );
						$output .= ( $url ) ? "<li><a href='" . esc_url( $url ) . "'>$label</a></li>" : "<li>$label</li>";
					}
				} elseif ( 'hcard' === $datatype || 'hcard' === $template ) {
					$label   = mc_hcard( $value, 'true', 'true', 'location' );
					$output .= ( $label ) ? "<li>$label</li>" : '';
				} elseif ( '' !== $template ) {
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
						'phone2'    => $value->location_phone2,
					);
					$label   = mc_draw_template( $values, $template );
					$output .= ( '' !== trim( $label ) ) ? "<li>$label</li>" : '';
				}
			}
			$output = '<ul class="mc-locations">' . $output . '</ul>';
		}
		/**
		 * Filter the output HTML of the location list.
		 *
		 * @hook mc_location_list
		 *
		 * @param {string} $output Output HTML for the form.
		 * @param {array}  $add Array of locations represented in this list.
		 *
		 * @return {string}
		 */
		$output = apply_filters( 'mc_location_list', $output, $locations );

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
	$current_url = ( '' !== $target_url && esc_url( $target_url ) ) ? $target_url : $current_url;

	if ( count( $locations ) > 1 ) {
		if ( 'list' === $show ) {
			$url     = mc_build_url(
				array(
					'loc'   => 'all',
					'ltype' => 'all',
				),
				array()
			);
			$output .= '<ul id="mc-locations-list">
			<li class="mc-show-all"><a href="' . $url . '">' . __( 'Show all', 'my-calendar' ) . '</a></li>';
		} else {
			$ltype   = ( ! isset( $_GET['ltype'] ) ) ? $datatype : $_GET['ltype'];
			$output .= ( 'single' === $group ) ? '<div id="mc_locations">' : '';
			$output .= ( 'single' === $group ) ? "<form action='" . esc_url( $current_url ) . "' method='get'><div>" : '';
			$output .= "<input type='hidden' name='ltype' value='" . esc_attr( $ltype ) . "' />";
			if ( 'single' === $group ) {
				$qsa = array();
				if ( isset( $_SERVER['QUERY_STRING'] ) ) {
					parse_str( $_SERVER['QUERY_STRING'], $qsa );
				}
				if ( ! isset( $_GET['cid'] ) ) {
					$output .= '<input type="hidden" name="cid" value="all" />';
				}
				foreach ( $qsa as $name => $argument ) {
					if ( 'loc' !== $name && 'ltype' !== $name ) {
						$output .= "\n" . '<input type="hidden" name="' . esc_attr( strip_tags( $name ) ) . '" value="' . esc_attr( strip_tags( $argument ) ) . '" />';
					}
				}
			}
			$output .= "
			<label for='mc-locations-list'>" . __( 'Location', 'my-calendar' ) . "</label>
			<select name='loc' id='mc-locations-list'>
			<option value='all'>" . __( 'All Locations', 'my-calendar' ) . "</option>\n";
		}
		foreach ( $locations as $key => $location ) {
			foreach ( $location as $k => $value ) {
				$vt    = urlencode( trim( $value ) );
				$value = strip_tags( stripcslashes( $value ), mc_strip_tags() );
				if ( '' === trim( $value ) ) {
					continue;
				}
				$loc = ( empty( $_GET['loc'] ) ) ? '' : $_GET['loc'];
				if ( 'list' === $show ) {
					$selected = ( $vt === $loc ) ? ' class="selected"' : '';
					$this_url = esc_url(
						mc_build_url(
							array(
								'loc'   => $vt,
								'ltype' => $datatype,
							),
							array()
						)
					);
					$output  .= " <li$selected><a rel='nofollow' href='$this_url'>$value</a></li>\n";
				} else {
					$selected = ( $vt === $loc || urlencode( $loc ) === $vt ) ? ' selected="selected"' : '';
					$output  .= " <option value='" . esc_attr( $vt ) . "'$selected>$value</option>\n";
				}
			}
		}
		if ( 'list' === $show ) {
			$output .= '</ul>';
		} else {
			$output .= '</select>';
			$output .= ( 'single' === $group ) ? '<input type="submit" class="button" value="' . __( 'Submit', 'my-calendar' ) . '" />
					</div>
				</form>' : '';
			$output .= ( 'single' === $group ) ? '</div>' : '';
		}
		/**
		 * Filter the output HTML of the location selector.
		 *
		 * @hook mc_location_selector
		 *
		 * @param {string} $output Output HTML for the form.
		 * @param {array}  $add Array of locations represented in this list.
		 *
		 * @return {string}
		 */
		$output = apply_filters( 'mc_location_selector', $output, $locations );

		return $output;
	} else {
		return '';
	}
}
