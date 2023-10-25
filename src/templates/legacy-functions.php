<?php
/**
 * My Calendar legacy template functions. Functions for templating replaced in version 3.5.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

/**
 * Function to draw a single event.
 *
 * @param object $event Event object.
 * @param string $type Type of view being drawn.
 * @param string $process_date Current date being displayed.
 * @param string $time Time view being drawn.
 * @param string $template Template to use to draw event.
 * @param string $id ID for the calendar calling this function.
 * @param array  $tags Event tags array.
 *
 * @return string
 */
function mc_legacy_template_draw_event( $event, $type, $process_date, $time, $template = '', $id = '', $tags = array() ) {
	// assign empty values to template sections.
	$header      = '';
	$address     = '';
	$more        = '';
	$author      = '';
	$host        = '';
	$list_title  = '';
	$title       = '';
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
		case 'card':
			$title_template = ( mc_get_template( 'title_card' ) === '' ) ? '{title}' : mc_get_template( 'title_card' );
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
				$classes = 'js-modal button button-link';
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
	if ( 'card' !== $type ) {
		$header .= ( false === stripos( $title, 'summary' ) ) ? '	<span class="summary screen-reader-text">' . strip_tags( $event_title ) . '</span>' : $title;
	}
	$close_button = mc_close_button( "$uid-$type-details-$id" );
	$close        = '';
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

			if ( 'card' === $type ) {
				$inner_title = $title;
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
			 * @param {object} $event My Calendar event object.
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
			 * @param {object} $event My Calendar event object.
			 *
			 * @return {string}
			 */
			$return_url = apply_filters( 'mc_return_uri', mc_get_uri( $event ), $event );
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
			$details      = '';
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
		/**
		 * Filter event content inside wrapper.
		 *
		 * @hook mc_inner_content
		 *
		 * @param {string} $detail HTML with event data.
		 * @param {object} $event My Calendar event object.
		 * @param {string} $type View type.
		 * @param {string} $time View timeframe.
		 *
		 * @return {string}
		 */
		$details = apply_filters( 'mc_inner_content', $details, $event, $type, $time );
		$details = $header . $container . $close . $details;

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

	return $details;
}