<?php
/**
 * Construct widgets. Incorporate widget classes & supporting widget functions.
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

require __DIR__ . '/includes/widgets/class-my-calendar-simple-search.php';
require __DIR__ . '/includes/widgets/class-my-calendar-filters.php';
require __DIR__ . '/includes/widgets/class-my-calendar-today-widget.php';
require __DIR__ . '/includes/widgets/class-my-calendar-upcoming-widget.php';
require __DIR__ . '/includes/widgets/class-my-calendar-mini-widget.php';

/**
 * Generate the widget output for upcoming events.
 *
 * @param array $args Event selection arguments.
 *
 * @return String HTML output list.
 */
function my_calendar_upcoming_events( $args ) {
	$language = isset( $args['language'] ) ? $args['language'] : '';
	$switched = '';
	if ( $language ) {
		$locale   = get_locale();
		$switched = mc_switch_language( $locale, $language );
	}
	$before         = ( isset( $args['before'] ) ) ? $args['before'] : 'default';
	$after          = ( isset( $args['after'] ) ) ? $args['after'] : 'default';
	$type           = ( isset( $args['type'] ) ) ? $args['type'] : 'default';
	$category       = ( isset( $args['category'] ) ) ? $args['category'] : 'default';
	$template       = ( isset( $args['template'] ) ) ? $args['template'] : 'default';
	$substitute     = ( isset( $args['fallback'] ) ) ? $args['fallback'] : '';
	$order          = ( isset( $args['order'] ) ) ? $args['order'] : 'asc';
	$skip           = ( isset( $args['skip'] ) ) ? $args['skip'] : 0;
	$show_recurring = ( isset( $args['show_recurring'] ) ) ? $args['show_recurring'] : 'yes';
	$author         = ( isset( $args['author'] ) ) ? $args['author'] : 'default';
	$host           = ( isset( $args['host'] ) ) ? $args['host'] : 'default';
	$ltype          = ( isset( $args['ltype'] ) ) ? $args['ltype'] : '';
	$lvalue         = ( isset( $args['lvalue'] ) ) ? $args['lvalue'] : '';
	$from           = ( isset( $args['from'] ) ) ? $args['from'] : '';
	$to             = ( isset( $args['to'] ) ) ? $args['to'] : '';
	$site           = ( isset( $args['site'] ) ) ? $args['site'] : false;

	if ( $site ) {
		$site = ( 'global' === $site ) ? BLOG_ID_CURRENT_SITE : $site;
		switch_to_blog( $site );
	}

	$hash         = md5( implode( ',', $args ) );
	$output       = '';
	$defaults     = mc_widget_defaults();
	$display_type = ( 'default' === $type ) ? $defaults['upcoming']['type'] : $type;
	$display_type = ( '' === $display_type ) ? 'events' : $display_type;

	// Get number of units we should go into the future.
	$after = ( 'default' === $after ) ? $defaults['upcoming']['after'] : $after;
	$after = ( '' === $after ) ? 10 : $after;

	// Get number of units we should go into the past.
	$before   = ( 'default' === $before ) ? $defaults['upcoming']['before'] : $before;
	$before   = ( '' === $before ) ? 0 : $before;
	$category = ( 'default' === $category ) ? '' : $category;

	/**
	 * Pass a custom template to the upcoming events list. Template can either be a template key referencing a stored template or a template pattern using {} template tags.
	 *
	 * @hook mc_upcoming_events_template
	 *
	 * @param {string} $template Un-parsed template.
	 *
	 * @return {string} Template string.
	 */
	$template = apply_filters( 'mc_upcoming_events_template', $template );
	$default  = ( ! $template || 'default' === $template ) ? $defaults['upcoming']['template'] : $template;
	$template = mc_setup_template( $template, $default );

	$no_event_text  = ( '' === $substitute ) ? $defaults['upcoming']['text'] : $substitute;
	$lang           = ( $switched ) ? ' lang="' . esc_attr( $switched ) . '"' : '';
	$class          = ( 'card' === $template ) ? 'my-calendar-cards' : 'list-events';
	$header         = "<ul id='upcoming-events-$hash' class='upcoming-events $class'$lang>";
	$footer         = '</ul>';
	$display_events = ( 'events' === $display_type || 'event' === $display_type ) ? true : false;
	if ( ! $display_events ) {
		$temp_array = array();
		if ( 'days' === $display_type ) {
			$from = mc_date( 'Y-m-d', strtotime( "-$before days" ), false );
			$to   = mc_date( 'Y-m-d', strtotime( "+$after days" ), false );
		}
		if ( 'month' === $display_type ) {
			$from = mc_date( 'Y-m-1' );
			$to   = mc_date( 'Y-m-t' );
		}
		if ( 'custom' === $display_type && '' !== $from && '' !== $to ) {
			$from = mc_date( 'Y-m-d', strtotime( $from ), false );
			$to   = ( 'today' === $to ) ? current_time( 'Y-m-d' ) : mc_date( 'Y-m-d', strtotime( $to ), false );
		}
		/* Yes, this is crude. But there are only 12 possibilities, after all. */
		if ( 'month+1' === $display_type ) {
			$from = mc_date( 'Y-m-1', strtotime( '+1 month' ), false );
			$to   = mc_date( 'Y-m-t', strtotime( '+1 month' ), false );
		}
		if ( 'month+2' === $display_type ) {
			$from = mc_date( 'Y-m-1', strtotime( '+2 month' ), false );
			$to   = mc_date( 'Y-m-t', strtotime( '+2 month' ), false );
		}
		if ( 'month+3' === $display_type ) {
			$from = mc_date( 'Y-m-1', strtotime( '+3 month' ), false );
			$to   = mc_date( 'Y-m-t', strtotime( '+3 month' ), false );
		}
		if ( 'month+4' === $display_type ) {
			$from = mc_date( 'Y-m-1', strtotime( '+4 month' ), false );
			$to   = mc_date( 'Y-m-t', strtotime( '+4 month' ), false );
		}
		if ( 'month+5' === $display_type ) {
			$from = mc_date( 'Y-m-1', strtotime( '+5 month' ), false );
			$to   = mc_date( 'Y-m-t', strtotime( '+5 month' ), false );
		}
		if ( 'month+6' === $display_type ) {
			$from = mc_date( 'Y-m-1', strtotime( '+6 month' ), false );
			$to   = mc_date( 'Y-m-t', strtotime( '+6 month' ), false );
		}
		if ( 'month+7' === $display_type ) {
			$from = mc_date( 'Y-m-1', strtotime( '+7 month' ), false );
			$to   = mc_date( 'Y-m-t', strtotime( '+7 month' ), false );
		}
		if ( 'month+8' === $display_type ) {
			$from = mc_date( 'Y-m-1', strtotime( '+8 month' ), false );
			$to   = mc_date( 'Y-m-t', strtotime( '+8 month' ), false );
		}
		if ( 'month+9' === $display_type ) {
			$from = mc_date( 'Y-m-1', strtotime( '+9 month' ), false );
			$to   = mc_date( 'Y-m-t', strtotime( '+9 month' ), false );
		}
		if ( 'month+10' === $display_type ) {
			$from = mc_date( 'Y-m-1', strtotime( '+10 month' ), false );
			$to   = mc_date( 'Y-m-t', strtotime( '+10 month' ), false );
		}
		if ( 'month+11' === $display_type ) {
			$from = mc_date( 'Y-m-1', strtotime( '+11 month' ), false );
			$to   = mc_date( 'Y-m-t', strtotime( '+11 month' ), false );
		}
		if ( 'month+12' === $display_type ) {
			$from = mc_date( 'Y-m-1', strtotime( '+12 month' ), false );
			$to   = mc_date( 'Y-m-t', strtotime( '+12 month' ), false );
		}
		if ( 'year' === $display_type ) {
			$from = mc_date( 'Y-1-1' );
			$to   = mc_date( 'Y-12-31' );
		}
		/**
		 * Custom upcoming events date start value for upcoming events lists using date parameters.
		 *
		 * @hook mc_upcoming_date_from
		 *
		 * @param {string} $from Starting date for this list of upcoming events in Y-m-d format.
		 * @param {array}  $args Associative array holding the arguments used to generate this list of upcoming events.
		 *
		 * @return {string} List starting date.
		 */
		$from = apply_filters( 'mc_upcoming_date_from', $from, $args );
		/**
		 * Custom upcoming events date end value for upcoming events lists using date parameters.
		 *
		 * @hook mc_upcoming_date_to
		 *
		 * @param {string} $to Ending date for this list of upcoming events in Y-m-d format.
		 * @param {array}  $args Associative array holding the arguments used to generate this list of upcoming events.
		 *
		 * @return {string} List ending date.
		 */
		$to = apply_filters( 'mc_upcoming_date_to', $to, $args );

		$query = array(
			'from'     => $from,
			'to'       => $to,
			'category' => $category,
			'ltype'    => $ltype,
			'lvalue'   => $lvalue,
			'author'   => $author,
			'host'     => $host,
			'search'   => '',
			'source'   => 'upcoming',
			'site'     => $site,
		);
		/**
		 * Modify the arguments used to generate upcoming events.
		 *
		 * @hook mc_upcoming_attributes
		 *
		 * @param {array} $query All arguments used to generate this list.
		 * @param {array} $args Subset of parameters used to generate this list's ID hash.
		 *
		 * @return {array} Array of event listing arguments.
		 */
		$query       = apply_filters( 'mc_upcoming_attributes', $query, $args );
		$event_array = my_calendar_events( $query );

		if ( 0 !== count( $event_array ) ) {
			foreach ( $event_array as $key => $value ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $k => $v ) {
						if ( mc_private_event( $v ) ) {
							// this event is private.
						} else {
							$temp_array[] = $v;
						}
					}
				}
			}
		}
		$i         = 0;
		$last_item = '';
		$skips     = array();
		$omit      = array();
		foreach ( reverse_array( $temp_array, true, $order ) as $event ) {
			$details = mc_create_tags( $event );
			/**
			 * Draw a custom template for upcoming events. Returning any non-empty string short circuits other template settings.
			 *
			 * @hook mc_draw_upcoming_event
			 *
			 * @param {string} $item Empty string before event template is drawn.
			 * @param {array}  $details Associative array of event template tags.
			 * @param {string} $template Template string passed from widget or shortcode.
			 * @param {array}  $args Associative array holding the arguments used to generate this list of upcoming events.
			 *
			 * @return {string} Event template details.
			 */
			$item = apply_filters( 'mc_draw_upcoming_event', '', $details, $template, $args );
			// if an event is a multidate group, only display first found.
			if ( in_array( $event->event_group_id, $omit, true ) ) {
				continue;
			}
			if ( '1' === $event->event_span ) {
				$omit[] = $event->event_group_id;
			}
			if ( '' === $item ) {
				$item = mc_draw_template( $details, $template, 'list', $event );
			}
			if ( $i < $skip && 0 !== $skip ) {
				++$i;
			} else {
				$date    = mc_date( 'Y-m-d H:i', strtotime( $details['dtstart'], false ) );
				$classes = mc_get_event_classes( $event, 'upcoming' );
				$prepend = "<li class='mc-events $classes'>";
				$append  = '</li>';
				/**
				 * Opening elements for upcoming events list items. Default `<li class="$classes">`.
				 *
				 * @hook mc_event_upcoming_before
				 *
				 * @param {string} $append Template HTML closing tag.
				 * @param {string} $classes Space-separated list of classes for the event.
				 * @param {string} $date Event date in Y-m-d H:i:s format.
				 *
				 * @return {string} HTML following each event in upcoming events lists.
				 */
				$prepend = apply_filters( 'mc_event_upcoming_before', $prepend, $classes, $date );
				/**
				 * Closing elements for upcoming events list items. Default `</li>`.
				 *
				 * @hook mc_event_upcoming_after
				 *
				 * @param {string} $append Template HTML closing tag.
				 * @param {string} $classes Space-separated list of classes for the event.
				 * @param {string} $date Event date in Y-m-d H:i:s format.
				 *
				 * @return {string} HTML following each event in upcoming events lists.
				 */
				$append = apply_filters( 'mc_event_upcoming_after', $append, $classes, $date );
				// Recurring events should only appear once.
				if ( ! in_array( $details['dateid'], $skips, true ) ) {
					$output .= ( $item === $last_item ) ? '' : $prepend . $item . $append;
				}
			}
			$skips[]   = $details['dateid']; // Prevent the same event from showing more than once.
			$last_item = $item;
		}
	} else {
		$query  = array(
			'category' => $category,
			'before'   => $before,
			'after'    => $after,
			'author'   => $author,
			'host'     => $host,
			'ltype'    => $ltype,
			'lvalue'   => $lvalue,
			'site'     => $site,
		);
		$events = mc_get_all_events( $query );

		$holidays      = mc_get_all_holidays( $before, $after );
		$holiday_array = mc_set_date_array( $holidays );

		if ( is_array( $events ) && ! empty( $events ) ) {
			$event_array = mc_set_date_array( $events );
			if ( is_array( $holidays ) && count( $holidays ) > 0 ) {
				$event_array = mc_holiday_limit( $event_array, $holiday_array ); // if there are holidays, filter results.
			}
		}
		if ( ! empty( $event_array ) ) {
			$output .= mc_produce_upcoming_events( $event_array, $template, 'list', $order, $skip, $before, $after, $show_recurring );
		} else {
			$output = '';
		}
	}
	if ( '' !== $output ) {
		/**
		 * Replace the list header for upcoming events lists. Default value `<ul id='upcoming-events-$hash' class='upcoming-events'$lang>`.
		 *
		 * @hook mc_upcoming_events_header
		 *
		 * @param {string} $header Existing upcoming events header HTML.
		 *
		 * @return {string} List header HTML.
		 */
		$header = apply_filters( 'mc_upcoming_events_header', $header );
		/**
		 * Replace the list footer for upcoming events lists. Default value `</ul>`.
		 *
		 * @hook mc_upcoming_events_footer
		 *
		 * @param {string} $header Existing upcoming events footer HTML.
		 *
		 * @return {string} List header HTML.
		 */

		$footer = apply_filters( 'mc_upcoming_events_footer', $footer );
		$output = $header . $output . $footer;
		$return = mc_run_shortcodes( $output );
	} else {
		$return = '<div class="no-events-fallback upcoming-events ' . $class . '">' . stripcslashes( $no_event_text ) . '</div>';
	}

	if ( $site ) {
		restore_current_blog();
	}

	if ( $language ) {
		mc_switch_language( $language, $locale );
	}

	return $return;
}

/**
 * For a set of grouped events, get the total time spanned by the group of events.
 *
 * @param int $group_id Event Group ID.
 *
 * @return array beginning and ending dates
 */
function mc_span_time( $group_id ) {
	$mcdb     = mc_is_remote_db();
	$group_id = (int) $group_id;
	$dates    = $mcdb->get_results( $mcdb->prepare( 'SELECT event_begin, event_time, event_end, event_endtime FROM ' . my_calendar_table() . ' WHERE event_group_id = %d ORDER BY event_begin ASC', $group_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$count    = count( $dates );
	$last     = $count - 1;
	$begin    = $dates[0]->event_begin . ' ' . $dates[0]->event_time;
	$end      = $dates[ $last ]->event_end . ' ' . $dates[ $last ]->event_endtime;

	return array( $begin, $end );
}

/**
 * Generates the list of upcoming events when counting by events rather than a date pattern
 *
 * @param array  $events (Array of events to analyze).
 * @param string $template Custom template to use for display.
 * @param string $type Usually 'list', but also RSS or export.
 * @param string $order 'asc' or 'desc'.
 * @param int    $skip Number of events to skip over.
 * @param int    $before How many past events to show.
 * @param int    $after How many future events to show.
 * @param string $show_recurring 'yes', show all recurring events. Else, first only.
 * @param string $context Display context.
 *
 * @return string; HTML output of list
 */
function mc_produce_upcoming_events( $events, $template, $type = 'list', $order = 'asc', $skip = 0, $before = 0, $after = 3, $show_recurring = 'yes', $context = 'filters' ) {
	// $events has +5 before and +5 after if those values are non-zero.
	// $events equals array of events based on before/after queries. Nothing skipped, order is not set, holiday conflicts removed.
	$output      = array();
	$near_events = array();
	$temp_array  = array();
	$past        = 0; // Number of events selected in the past.
	$future      = 0; // Number of events selected in the future.
	uksort( $events, 'mc_timediff_cmp' ); // Sort all events by proximity to current date.
	$count = count( $events );
	$group = array();
	$spans = array();
	$occur = array();
	$extra = 0;
	$i     = 0;
	// Create near_events array.
	$recurring_events = array();
	$last_events      = array();
	$last_group       = array();
	if ( is_array( $events ) ) {
		foreach ( $events as $k => $event ) {
			if ( $i < $count ) {
				if ( is_array( $event ) ) {
					foreach ( $event as $e ) {
						if ( ! mc_private_event( $e ) ) {
							$beginning = $e->occur_begin;
							$end       = $e->occur_end;
							// Store span time in an array to avoid repeating database query.
							if ( '1' === $e->event_span && ( ! isset( $spans[ $e->occur_group_id ] ) ) ) {
								// This is a multi-day event: treat each event as if it spanned the entire range of the group.
								$span_time                   = mc_span_time( $e->occur_group_id );
								$beginning                   = $span_time[0];
								$end                         = $span_time[1];
								$spans[ $e->occur_group_id ] = $span_time;
							} elseif ( '1' === $e->event_span && ( isset( $spans[ $e->occur_group_id ] ) ) ) {
								$span_time = $spans[ $e->occur_group_id ];
								$beginning = $span_time[0];
								$end       = $span_time[1];
							}
							$current = current_time( 'Y-m-d H:i:00' );
							if ( $e ) {
								// If a multi-day event, show only once.
								if ( '0' !== $e->occur_group_id && '1' === $e->event_span && in_array( $e->occur_group_id, $group, true ) || in_array( $e->occur_id, $occur, true ) ) {
									$md = true;
								} else {
									$group[] = $e->occur_group_id;
									$occur[] = $e->occur_id;
									$md      = false;
								}
								// end multi-day reduction.
								if ( ! $md ) {
									if ( in_array( $e->occur_event_id, $recurring_events, true ) ) {
										$is_recurring = true;
									} else {
										$is_recurring = false;
										$instances    = mc_get_occurrences( $e->occur_event_id );
										if ( count( $instances ) > 1 ) {
											$recurring_events[] = $e->occur_event_id;
										}
									}
									if ( ( 'yes' !== $show_recurring ) && $is_recurring ) {
										continue;
									}
									$event_used = false;
									if ( my_calendar_date_equal( $beginning, $current ) ) {

										/**
										 * Should today's events be counted towards total number of upcoming events. Default `yes`. Any value other than 'no' will be interpreted as 'yes'.
										 *
										 * @hook mc_include_today_in_total
										 *
										 * @param {string} $in_total Return 'no' to exclude today's events from event count. Default 'yes'.
										 *
										 * @return {string} 'yes' or 'no'.
										 */
										$in_total = apply_filters( 'mc_include_today_in_total', 'yes' ); // count todays events in total.
										if ( 'yes' === $in_total ) {
											$near_events[] = $e;
											$event_used    = true;
											// Should today's events be counted as future or past? If more past events chosen, count as past.
											if ( $before > $after ) {
												++$past;
											} else {
												++$future;
											}
										} else {
											$near_events[] = $e;
											$event_used    = true;
										}
									}
									if ( $past <= $before && ( my_calendar_date_comp( $beginning, $current ) ) && ! $event_used ) {
										$near_events[] = $e; // Split off another past event.
										$event_used    = true;
									}
									if ( $future <= $after && ( ! my_calendar_date_comp( $end, $current ) ) && ! $event_used ) {
										$near_events[] = $e; // Split off another future event.
										$event_used    = true;
									}

									$event_added = false;
									// If this event happened before the current date.
									if ( my_calendar_date_comp( $beginning, $current ) ) {
										++$past;
										$event_added = true;
									}
									// If this happened on the current date.
									if ( my_calendar_date_equal( $beginning, $current ) && ! $event_added ) {
										++$extra;
										$event_added = true;
									}
									// If this did not end before the current date.
									if ( ! my_calendar_date_comp( $end, $current ) && ! $event_added ) {
										++$future;
										$event_added = true;
									}

									$last_events[] = $e->occur_id;
									$last_group[]  = $e->occur_group_id;
								}
								if ( $past > $before && $future > $after ) {
									break;
								}
							}
						}
					}
				}
			}
		}
	}
	$events = $near_events;
	usort( $events, 'mc_datetime_cmp' ); // Sort split events by date.

	if ( is_array( $events ) ) {
		foreach ( array_keys( $events ) as $key ) {
			$event        =& $events[ $key ];
			$temp_array[] = $event;
		}
		$i      = 0;
		$groups = array();
		$skips  = array();

		foreach ( reverse_array( $temp_array, true, $order ) as $event ) {
			$details = mc_create_tags( $event, $context );
			if ( ! in_array( $details['group'], $groups, true ) ) {
				// dtstart is already in current time zone.
				if ( $i < $skip && 0 !== $skip ) {
					++$i;
				} else {
					if ( ! in_array( $details['dateid'], $skips, true ) ) {
						$output[] = array(
							'event' => $event,
							'tags'  => $details,
						);
						$skips[]  = $details['dateid'];
					}
				}
				if ( '1' === $details['event_span'] ) {
					$groups[] = $details['group'];
				}
			}
		}
	}
	// If more items than there should be (due to handling of current-day's events), pop off.
	$intended = $before + $after + $extra;
	$actual   = count( $output );
	if ( $actual > $intended ) {
		for ( $i = 0; $i < ( $actual - $intended ); $i++ ) {
			array_pop( $output );
		}
	}

	$html = '';
	foreach ( $output as $out ) {
		$event = $out['event'];
		$tags  = $out['tags'];
		$data  = array(
			'event'    => $event,
			'tags'     => $tags,
			'template' => $template,
			'type'     => $type,
			'time'     => 'list',
		);
		if ( 'card' === $template ) {
			$details = '<li class="card-event"><h3>' . mc_load_template( 'event/card-title', $data ) . '</h3>' . mc_load_template( 'event/card', $data ) . '</li>';
		} else {
			$details = mc_load_template( 'event/upcoming', $data );
		}
		if ( ! $details ) {
			$html .= mc_format_upcoming_event( $out, $template, $type );
		} else {
			$html .= $details;
		}
	}

	return $html;
}

/**
 * Process the Today's Events widget.
 *
 * @param array $args Event & output construction parameters.
 *
 * @return string HTML.
 */
function my_calendar_todays_events( $args ) {
	$language = isset( $args['language'] ) ? $args['language'] : '';
	$switched = '';
	if ( $language ) {
		$locale   = get_locale();
		$switched = mc_switch_language( $locale, $language );
	}

	$category   = ( isset( $args['category'] ) ) ? $args['category'] : 'default';
	$template   = ( isset( $args['template'] ) ) ? $args['template'] : 'default';
	$substitute = ( isset( $args['fallback'] ) ) ? $args['fallback'] : '';
	$author     = ( isset( $args['author'] ) ) ? $args['author'] : 'all';
	$host       = ( isset( $args['host'] ) ) ? $args['host'] : 'all';
	$date       = ( isset( $args['date'] ) ) ? $args['date'] : false;
	$site       = ( isset( $args['site'] ) ) ? $args['site'] : false;

	if ( $site ) {
		$site = ( 'global' === $site ) ? BLOG_ID_CURRENT_SITE : $site;
		switch_to_blog( $site );
	}

	$params = array(
		'category'   => $category,
		'template'   => $template,
		'substitute' => $substitute,
		'author'     => $author,
		'host'       => $host,
		'date'       => $date,
	);
	$hash   = md5( implode( ',', $params ) );
	$output = '';

	$defaults = mc_widget_defaults();
	$default  = ( ! $template || 'default' === $template ) ? $defaults['today']['template'] : $template;
	$template = mc_setup_template( $template, $default );

	$category      = ( 'default' === $category ) ? $defaults['today']['category'] : $category;
	$no_event_text = ( '' === $substitute ) ? $defaults['today']['text'] : $substitute;
	if ( $date ) {
		$from = mc_date( 'Y-m-d', strtotime( $date ), false );
		$to   = mc_date( 'Y-m-d', strtotime( $date ), false );
	} else {
		$from = current_time( 'Y-m-d' );
		$to   = current_time( 'Y-m-d' );
	}

	$args = array(
		'from'     => $from,
		'to'       => $to,
		'category' => $category,
		'ltype'    => '',
		'lvalue'   => '',
		'author'   => $author,
		'host'     => $host,
		'search'   => '',
		'source'   => 'upcoming',
		'site'     => $site,
	);
	/**
	 * Modify the arguments used to generate today's events.
	 *
	 * @hook mc_today_attributes
	 *
	 * @param {array} $args All arguments used to generate this list.
	 * @param {array} $params Subset of parameters used to generate this list's ID hash.
	 *
	 * @return {array} Array of event listing arguments.
	 */
	$args   = apply_filters( 'mc_today_attributes', $args, $params );
	$events = my_calendar_events( $args );

	$today         = ( isset( $events[ $from ] ) ) ? $events[ $from ] : false;
	$lang          = ( $switched ) ? ' lang="' . esc_attr( $switched ) . '"' : '';
	$class         = ( 'card' === $template ) ? 'my-calendar-cards' : 'list-events';
	$header        = "<ul id='todays-events-$hash' class='todays-events $class'$lang>";
	$footer        = '</ul>';
	$groups        = array();
	$todays_events = array();
	// quick loop through all events today to check for holidays.
	if ( is_array( $today ) ) {
		foreach ( $today as $e ) {
			if ( ! mc_private_event( $e ) && ! in_array( $e->event_group_id, $groups, true ) ) {
				$event_details = mc_create_tags( $e );
				$ts            = $e->ts_occur_begin;
				$classes       = mc_get_event_classes( $e, 'today' );

				$data = array(
					'event'    => $e,
					'tags'     => $event_details,
					'template' => $template,
					'args'     => $args,
				);
				if ( 'card' === $template ) {
					$details = '<li class="card-event"><h3>' . mc_load_template( 'event/card-title', $data ) . '</h3>' . mc_load_template( 'event/card', $data ) . '</li>';
				} else {
					$details = mc_load_template( 'event/today', $data );
				}
				if ( $details ) {
					$todays_events[ $ts ][] = $details;
				} else {
					/**
					 * Modify the HTML preceding each list item in a list of today's events.
					 *
					 * @hook mc_todays_events_before
					 *
					 * @param {string} $item HTML string before each event.
					 * @param {string} $classes Space separated list of classes for this event.
					 * @param {string} $category Category argument passed to this list.
					 *
					 * @return {string} HTML preceding each event in today's events lists.
					 */
					$prepend = apply_filters( 'mc_todays_events_before', "<li class='$classes'>", $classes, $category );
					/**
					 * Closing elements for today's events list items. Default `</li>`.
					 *
					 * @hook mc_todays_events_after
					 *
					 * @param {string} $item Template HTML closing tag.
					 *
					 * @return {string} HTML following each event in today's events lists.
					 */
					$append = apply_filters( 'mc_todays_events_after', '</li>' );

					/**
					 * Draw a custom template for today's events. Returning any non-empty string short circuits other template settings.
					 *
					 * @hook mc_draw_todays_event
					 *
					 * @param {string} $item Empty string before event template is drawn.
					 * @param {array}  $event_details Associative array of event template tags.
					 * @param {string} $template Template string passed from widget or shortcode.
					 * @param {array}  $args Associative array holding the arguments used to generate this list of events.
					 *
					 * @return {string} Event output details.
					 */
					$item = apply_filters( 'mc_draw_todays_event', '', $event_details, $template, $args );
					if ( '' === $item ) {
						$item = mc_draw_template( $event_details, $template, 'list', $e );
					}
					$todays_events[ $ts ][] = $prepend . $item . $append;
				}
			}
		}
		/**
		 * Filter the array of events listed in today's events lists.
		 *
		 * @hook mc_event_today
		 *
		 * @param {array} $todays_events  A multidimensional array of event items with today's date as a key with an array of formatted HTML on event templates on the current date.
		 * @param {array} $events Array of events without private events removed. Values are event objects.
		 *
		 * @return {array} A multidimensional array of event items with today's date as a key with an array of formatted HTML on event templates on the current date.
		 */
		$todays_events = apply_filters( 'mc_event_today', $todays_events, $events );
		foreach ( $todays_events as $k => $t ) {
			foreach ( $t as $now ) {
				$output .= $now;
			}
		}
		if ( 0 !== count( $events ) ) {
			/**
			 * Replace the list header for today's events lists. Default value `<ul id='todays-events-$hash' class='todays-events'$lang>`.
			 *
			 * @hook mc_todays_events_header
			 *
			 * @param {string} $header Existing today's events header HTML.
			 *
			 * @return {string} List header HTML.
			 */
			$return  = apply_filters( 'mc_todays_events_header', $header );
			$return .= $output;
			/**
			 * Replace the list footer for today's events lists. Default value `</ul>`.
			 *
			 * @hook mc_todays_events_header
			 *
			 * @param {string} $header Existing today's events header HTML.
			 *
			 * @return {string} List header HTML.
			 */
			$return .= apply_filters( 'mc_todays_events_footer', $footer );
		} else {
			$return = '<div class="no-events-fallback todays-events">' . stripcslashes( $no_event_text ) . '</div>';
		}
	} else {
		$return = '<div class="no-events-fallback todays-events">' . stripcslashes( $no_event_text ) . '</div>';
	}

	if ( $site ) {
		restore_current_blog();
	}

	$output = mc_run_shortcodes( $return );

	if ( $language ) {
		mc_switch_language( $language, $locale );
	}

	return $output;
}
