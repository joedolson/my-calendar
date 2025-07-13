<?php
/**
 * Construct widgets. Incorporate widget classes & supporting widget functions.
 *
 * @category Calendar
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv3
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
	$args['after']     = ( isset( $args['after'] ) ) ? $args['after'] : 'default';
	$args['type']       = ( isset( $args['type'] ) ) ? $args['type'] : 'default';
	$args['category']   = ( isset( $args['category'] ) ) ? $args['category'] : 'default';
	$args['substitute'] = ( isset( $args['fallback'] ) ) ? $args['fallback'] : '';
	$args['order']      = ( isset( $args['order'] ) ) ? $args['order'] : 'asc';
	$args['skip']       = ( isset( $args['skip'] ) ) ? $args['skip'] : 0;
	$args['author']     = ( isset( $args['author'] ) ) ? $args['author'] : 'default';
	$args['host']       = ( isset( $args['host'] ) ) ? $args['host'] : 'default';
	$args['ltype']      = ( isset( $args['ltype'] ) ) ? $args['ltype'] : '';
	$args['lvalue']     = ( isset( $args['lvalue'] ) ) ? $args['lvalue'] : '';
	$args['from']       = ( isset( $args['from'] ) ) ? $args['from'] : '';
	$args['to']         = ( isset( $args['to'] ) ) ? $args['to'] : '';
	$args['site']       = ( isset( $args['site'] ) ) ? $args['site'] : false;
	$args['time']       = ( isset( $args['time'] ) ) ? $args['time'] : '';

	if ( $args['site'] ) {
		$args['site'] = ( 'global' === $args['site'] ) ? BLOG_ID_CURRENT_SITE : $args['site'];
		switch_to_blog( $args['site'] );
	}

	$hash         = md5( implode( ',', $args ) );
	$output       = '';
	$defaults     = mc_widget_defaults();
	$display_type = ( 'default' === $args['type'] ) ? $defaults['upcoming']['type'] : $args['type'];
	$display_type = ( '' === $display_type ) ? 'events' : $display_type;

	// Get number of units we should go into the future.
	$args['after'] = ( 'default' === $args['after'] ) ? $defaults['upcoming']['after'] : $args['after'];
	$args['after'] = ( '' === $args['after'] ) ? 10 : $args['after'];

	// Get number of units we should go into the past.
	$args['before']    = ( 'default' === $args['before'] ) ? $defaults['upcoming']['before'] : $args['before'];
	$args['before']    = ( '' === $args['before'] ) ? 0 : $args['before'];
	$args['category']  = ( 'default' === $args['category'] ) ? '' : $args['category'];
	$args['template '] = ( isset( $args['template'] ) ) ? $args['template'] : '';

	/**
	 * Pass a custom template to the upcoming events list. Template can either be a template key referencing a stored template or a template pattern using {} template tags.
	 *
	 * @hook mc_upcoming_events_template
	 *
	 * @param {string} $template Un-parsed template.
	 *
	 * @return {string} Template string.
	 */
	$args['template'] = apply_filters( 'mc_upcoming_events_template', $args['template'] );
	$default          = ( ! $args['template'] || 'default' === $args['template'] ) ? $defaults['upcoming']['template'] : $args['template'];
	$args['template'] = mc_setup_template( $args['template'], $default );

	$args['substitute'] = ( ! $args['substitute'] ) ? $defaults['upcoming']['text'] : $args['substitute'];
	$lang               = ( $switched ) ? ' lang="' . esc_attr( $switched ) . '"' : '';
	$class              = ( 'card' === $args['template'] ) ? 'my-calendar-cards' : 'list-events';
	$header             = "<div class='mc-event-list-container'><ul id='upcoming-events-$hash' class='mc-event-list upcoming-events $class'$lang>";
	$footer             = '</ul></div>';
	$display_events = ( 'events' === $display_type || 'event' === $display_type ) ? true : false;
	if ( ! $display_events ) {
		$temp_array = array();
		if ( ! empty( $args['from'] ) && ! empty( $args['to'] ) ) {
		} else {
			$args = mc_set_from_and_to( $args, $display_type );
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
		$args['from'] = apply_filters( 'mc_upcoming_date_from', $args['from'], $args );
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
		$args['to'] = apply_filters( 'mc_upcoming_date_to', $args['to'], $args );

		$query = array(
			'from'     => $args['from'],
			'to'       => $args['to'],
			'category' => $args['category'],
			'ltype'    => $args['ltype'],
			'lvalue'   => $args['lvalue'],
			'author'   => $args['author'],
			'host'     => $args['host'],
			'search'   => '',
			'source'   => 'upcoming',
			'site'     => $args['site'],
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
		foreach ( reverse_array( $temp_array, true, $args['order'] ) as $event ) {
			$details = mc_create_tags( $event );
			$data    = array(
				'event'    => $event,
				'tags'     => $details,
				'template' => $args['template'],
				'args'     => $args,
				'class'    => ( str_contains( $args['template'], 'list_preset_' ) ) ? "list-preset $args[template]" : '',
			);
			if ( 'card' === $args['template'] ) {
				$item = '<li class="card-event"><h3>' . mc_load_template( 'event/card-title', $data ) . '</h3>' . mc_load_template( 'event/card', $data ) . '</li>';
			} else {
				$item = mc_load_template( 'event/upcoming', $data );
			}
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
			$item = apply_filters( 'mc_draw_upcoming_event', $item, $details, $args['template'], $args );
			// if an event is a multidate group, only display first found.
			if ( in_array( $event->event_group_id, $omit, true ) ) {
				continue;
			}
			if ( '1' === $event->event_span ) {
				$omit[] = $event->event_group_id;
			}
			if ( '' === $item ) {
				$item = mc_format_upcoming_event( $data, $args['template'], 'list' );
			}
			if ( $i < $args['skip'] && 0 !== $args['skip'] ) {
				++$i;
			} else {
				// Recurring events should only appear once.
				if ( ! in_array( $details['dateid'], $skips, true ) ) {
					$output .= ( $item === $last_item ) ? '' : $item;
				}
			}
			$skips[]   = $details['dateid']; // Prevent the same event from showing more than once.
			$last_item = $item;
		}
	} else {
		$query  = array(
			'category' => $args['category'],
			'before'   => $args['before'],
			'after'    => $args['after'],
			'author'   => $args['author'],
			'host'     => $args['host'],
			'ltype'    => $args['ltype'],
			'lvalue'   => $args['lvalue'],
			'site'     => $args['site'],
			'time'     => $args['time'],
		);
		$events = mc_get_all_events( $query );

		$holidays      = mc_get_all_holidays( $args['before'], $args['after'], $args['time'] );
		$holiday_array = mc_set_date_array( $holidays );

		if ( is_array( $events ) && ! empty( $events ) ) {
			$event_array = mc_set_date_array( $events );
			if ( is_array( $holidays ) && count( $holidays ) > 0 ) {
				$event_array = mc_holiday_limit( $event_array, $holiday_array ); // if there are holidays, filter results.
			}
		}
		if ( ! empty( $event_array ) ) {
			$output .= mc_produce_upcoming_events( $event_array, $args, 'list' );
		} else {
			$output = '';
		}
	}
	/**
	 * Replace the list header for upcoming events lists. Default value `<ul id='upcoming-events-$hash' class='mc-event-list upcoming-events'$lang>`.
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
	$footer     = apply_filters( 'mc_upcoming_events_footer', $footer );
	$navigation = ( 'days' === $args['type'] ) ? mc_upcoming_dates_navigation( $args ) : '';
	if ( '' !== $output ) {
		$output = $header . $navigation . $output . $footer;
		$return = mc_run_shortcodes( $output );
	} else {
		$header = str_replace( 'mc-event-list ', 'mc-event-list no-events-fallback ', $header );
		$class  = ( str_contains( $args['template'], 'list_preset_' ) ) ? "list-preset $args[template]" : '';
		$return = $header . $navigation . '<li class="' . $class . '">' . wp_unslash( $args['substitute'] ) . '</li>' . $footer;
	}

	if ( $args['site'] ) {
		restore_current_blog();
	}

	if ( $language ) {
		mc_switch_language( $language, $locale );
	}

	return $return;
}

/**
 * Generate from and to values from arguments.
 *
 * @param array  $args Upcoming Event arguments.
 * @param string $display_type Type of display.
 *
 * @return array
 */
function mc_set_from_and_to( $args, $display_type ) {
	if ( 'days' === $display_type ) {
		$args['from'] = mc_date( 'Y-m-d', strtotime( "-$args[before] days" ), false );
		$args['to']   = mc_date( 'Y-m-d', strtotime( "+$args[after] days" ), false );
	}

	if ( 'month' === $display_type ) {
		$args['from'] = mc_date( 'Y-m-1' );
		$args['to']   = mc_date( 'Y-m-t' );
	}

	if ( 'custom' === $display_type && '' !== $args['from'] && '' !== $args['to'] ) {
		$args['from'] = mc_date( 'Y-m-d', strtotime( $args['from'] ), false );
		$args['to']   = ( 'today' === $args['to'] ) ? current_time( 'Y-m-d' ) : mc_date( 'Y-m-d', strtotime( $args['to'] ), false );
	}

	for ( $i = 1; $i <= 12; ++$i ) {
		if ( 'month+' . $i === $display_type ) {
			$args['from'] = mc_date( 'Y-m-1', strtotime( '+' . $i . ' month' ), false );
			$args['to']   = mc_date( 'Y-m-t', strtotime( '+' . $i . ' month' ), false );
		}
	}

	if ( 'year' === $display_type ) {
		$args['from'] = mc_date( 'Y-1-1' );
		$args['to']   = mc_date( 'Y-12-31' );
	}

	return $args;
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
 * @param array  $args Array of list arguments from calling function.
 * @param string $type Usually 'list', but also RSS or export.
 * @param string $context Display context.
 *
 * @return string; HTML output of list
 */
function mc_produce_upcoming_events( $events, $args, $type = 'list', $context = 'filters' ) {
	$template       = $args['template'];
	$order          = $args['template'];
	$skip           = $args['skip'];
	$before         = $args['before'];
	$after          = $args['after'];
	$show_recurring = $args['show_recurring'];
	// $events has +5 before and +5 after if those values are non-zero.
	// $events equals array of events based on before/after queries. Nothing skipped, order is not set, holiday conflicts removed.
	$output      = array();
	$near_events = array();
	$temp_array  = array();
	$past        = 0; // Number of events selected in the past.
	$future      = 0; // Number of events selected in the future.
	if ( '' === $args['time'] ) {
		uksort( $events, 'mc_timediff_cmp' ); // Sort all events by proximity to current date.
	} else {
		$compare_time = $args['time'];
		uksort(
			$events,
			function ( $a, $b ) use ( $compare_time ) {

				return mc_timediff_cmp( $a, $b, $compare_time );
			}
		);
	}
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
									if ( '' !== $args['time'] ) {
										$current = $args['time'];
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

	$html       = '';
	$first_date = false;
	$last_date  = false;
	$i          = 1;
	foreach ( $output as $out ) {
		$event = $out['event'];
		$tags  = $out['tags'];
		$data  = array(
			'event'    => $event,
			'tags'     => $tags,
			'template' => $template,
			'type'     => $type,
			'time'     => 'list',
			'class'    => ( str_contains( $template, 'list_preset_' ) ) ? "list-preset $template" : '',
		);
		// Get first ID in set.
		if ( ! $first_date ) {
			$first_date = $event->occur_id;
		}
		if ( count( $output ) === $i ) {
			$last_date = $event->occur_id;
		}
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
		++$i;
	}
	if ( ( $last_date || $first_date ) && 'events' === $args['type'] || 'default' === $args['type'] ) {
		$args['offset']     = count( $output ) - 1;
		$args['navigation'] = ( ! isset( $args['navigation'] ) ) ? mc_get_option( 'upcoming_events_navigation' ) : $args['navigation'];
		$buttons            = mc_upcoming_events_navigation( $args, $first_date, $last_date );
		$html               = $buttons . $html;
	}

	return $html;
}

/**
 * Generate upcoming events navigation for dates.
 *
 * @param array $args Array of Upcoming events arguments.
 *
 * @return string
 */
function mc_upcoming_dates_navigation( $args ) {
	if ( 'true' !== $args['navigation'] ) {
		return '';
	}
	if ( ! isset( $args['from'] ) || ! $args['from'] ) {
		$args['from'] = gmdate( 'Y-m-d', time() - DAY_IN_SECONDS * $args['before'] );
		$args['to']   = gmdate( 'Y-m-d', time() + DAY_IN_SECONDS * $args['after'] );
	}

	$diff     = strtotime( $args['to'] ) - strtotime( $args['from'] );
	$new_from = gmdate( 'Y-m-d', strtotime( $args['from'] ) - $diff );
	$new_to   = gmdate( 'Y-m-d', strtotime( $args['to'] ) + $diff );
	$to       = $args['to'];
	$from     = $args['from'];

	$args['to']     = $from;
	$args['from']   = $new_from;
	$json_args_prev = str_replace( '&', '|', http_build_query( $args ) );

	$args['to']     = $new_to;
	$args['from']   = $to;
	$json_args_next = str_replace( '&', '|', http_build_query( $args ) );

	return '<li class="mc-load-events-controls">
				<button class="mc-loader mc-load-prev-upcoming-dates mc-previous" type="button" data-value="' . esc_attr( $json_args_prev ) . '" value="dates"><span class="mc-icon" aria-hidden="true"></span><span class="mc-text">' . esc_html__( 'Previous Events', 'my-calendar' ) . '</span></button>
				<button class="mc-loader mc-load-next-upcoming-dates mc-next" type="button" data-value="' . esc_attr( $json_args_next ) . '" value="dates"><span class="mc-text">' . esc_html__( 'Future Events', 'my-calendar' ) . '</span><span class="mc-icon" aria-hidden="true"></span></button>
			</li>';
}

/**
 * Generate upcoming events navigation buttons.
 *
 * @param array    $args Upcoming Events arguments.
 * @param int|bool $first_date Occurrence ID of previous event.
 * @param int|bool $last_date  Occurrence ID of next event.
 *
 * @return string
 */
function mc_upcoming_events_navigation( $args, $first_date, $last_date ) {
	if ( 'true' !== $args['navigation'] ) {
		return '';
	}
	unset( $args['time'] );
	$json_args   = str_replace( '&', '|', http_build_query( $args ) );
	$prev_button = '';
	$next_button = '';
	if ( $first_date ) {
		$args['return'] = 'object';
		$prev           = mc_adjacent_event( $first_date, 'previous', $args );
		if ( is_object( $prev ) ) {
			$prev_date = $prev->occur_begin;
			$label     = __( 'Previous Events', 'my-calendar' );
			$class     = 'mc-previous';
		} else {
			$prev_date = '';
			$label     = __( 'Today', 'my-calendar' );
			$class     = 'mc-today';
		}
		$prev_button .= '<button class="mc-loader mc-load-prev-upcoming-events ' . esc_attr( $class ) . '" type="button" data-value="' . esc_attr( $json_args ) . '" value="' . esc_attr( $prev_date ) . '"><span class="mc-icon" aria-hidden="true"></span><span class="mc-text">' . esc_html( $label ) . '</span></button>';
	}
	if ( $last_date ) {
		unset( $args['offset'] );
		$args['return'] = 'object';
		$next           = mc_adjacent_event( $last_date, 'next', $args );
		if ( is_object( $next ) ) {
			$next_date = $next->occur_begin;
			$label     = __( 'Future Events', 'my-calendar' );
			$class     = 'mc-next';
		} else {
			$next_date = '';
			$label     = __( 'Today', 'my-calendar' );
			$class     = 'mc-today';
		}
		$next_button .= '<button class="mc-loader mc-load-next-upcoming-events ' . esc_attr( $class ) . '" type="button" data-value="' . esc_attr( $json_args ) . '" value="' . esc_attr( $next_date ) . '"><span class="mc-text">' . esc_html( $label ) . '</span><span class="mc-icon" aria-hidden="true"></span></button>';
	}
	$buttons = ( $prev_button || $next_button ) ? '<li class="mc-load-events-controls">' . $prev_button . $next_button . '</li>' : '';

	return $buttons;
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

	$args['category']   = ( isset( $args['category'] ) ) ? $args['category'] : 'default';
	$args['template']   = ( isset( $args['template'] ) ) ? $args['template'] : 'default';
	$args['substitute'] = ( isset( $args['fallback'] ) ) ? $args['fallback'] : '';
	$args['author']     = ( isset( $args['author'] ) ) ? $args['author'] : 'all';
	$args['host']       = ( isset( $args['host'] ) ) ? $args['host'] : 'all';
	$args['date']       = ( isset( $args['date'] ) ) ? $args['date'] : false;
	$args['site']       = ( isset( $args['site'] ) ) ? $args['site'] : false;

	if ( $args['site'] ) {
		$args['site'] = ( 'global' === $args['site'] ) ? BLOG_ID_CURRENT_SITE : $args['site'];
		switch_to_blog( $args['site'] );
	}

	$params = array(
		'category'   => $args['category'],
		'template'   => $args['template'],
		'substitute' => $args['substitute'],
		'author'     => $args['author'],
		'host'       => $args['host'],
		'date'       => $args['date'],
	);
	$hash   = md5( implode( ',', $params ) );
	$output = '';

	$defaults         = mc_widget_defaults();
	$default          = ( ! $args['template'] || 'default' === $args['template'] ) ? $defaults['today']['template'] : $args['template'];
	$args['template'] = mc_setup_template( $args['template'], $default );

	$args['category']   = ( 'default' === $args['category'] ) ? $defaults['today']['category'] : $args['category'];
	$args['substitute'] = ( '' === $args['substitute'] ) ? $defaults['today']['text'] : $args['substitute'];
	if ( $args['date'] ) {
		$args['from'] = mc_date( 'Y-m-d', strtotime( $args['date'] ), false );
		$args['to']   = mc_date( 'Y-m-d', strtotime( $args['date'] ), false );
	} else {
		$args['from'] = current_time( 'Y-m-d' );
		$args['to']   = current_time( 'Y-m-d' );
	}

	$args = array(
		'from'     => $args['from'],
		'to'       => $args['to'],
		'category' => $args['category'],
		'ltype'    => '',
		'lvalue'   => '',
		'author'   => $args['author'],
		'host'     => $args['host'],
		'search'   => '',
		'source'   => 'upcoming',
		'site'     => $args['site'],
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

	$today         = ( isset( $events[ $args['from'] ] ) ) ? $events[ $args['from'] ] : false;
	$lang          = ( $switched ) ? ' lang="' . esc_attr( $switched ) . '"' : '';
	$class         = ( 'card' === $args['template'] ) ? 'my-calendar-cards' : 'list-events';
	$header        = "<ul id='todays-events-$hash' class='mc-event-list todays-events $class'$lang>";
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
					'template' => $args['template'],
					'args'     => $args,
					'class'    => ( str_contains( $args['template'], 'list_preset_' ) ) ? "list-preset $args[template]" : '',
				);
				if ( 'card' === $args['template'] ) {
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
					$prepend = apply_filters( 'mc_todays_events_before', "<li class='$classes'>", $classes, $args['category'] );
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
					$item = apply_filters( 'mc_draw_todays_event', '', $event_details, $args['template'], $args );
					if ( '' === $item ) {
						$item = mc_draw_template( $event_details, $args['template'], 'list', $e );
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
			 * Replace the list header for today's events lists. Default value `<ul id='todays-events-$hash' class='mc-event-list todays-events'$lang>`.
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
			$return = '<div class="no-events-fallback todays-events">' . stripcslashes( $args['substitute'] ) . '</div>';
		}
	} else {
		$return = '<div class="no-events-fallback todays-events">' . stripcslashes( $args['substitute'] ) . '</div>';
	}

	if ( $args['site'] ) {
		restore_current_blog();
	}

	$output = mc_run_shortcodes( $return );

	if ( $language ) {
		mc_switch_language( $language, $locale );
	}

	return '<div class="mc-event-list-container">' . $output . '</div>';
}
