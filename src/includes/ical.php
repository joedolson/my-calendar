<?php
/**
 * Export iCal generation
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

use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Components\CustomEvent;

/**
 * Create an iCal calendar of events.
 *
 * @param array  $events One-dimensional array of events.
 * @param string $context Optional. iCal or Google export format.
 */
function mc_generate_ical( $events, $context = '' ) {
	$processed   = array();
	$calendar    = Calendar::create();
	$ical_events = array();
	// Translators: Blogname.
	$events_from = sprintf( __( 'Events from %s', 'my-calendar' ), get_bloginfo( 'blogname' ) );
	/**
	 * Filter the suggested frequency for iCal subscription sources to be rechecked. Default 1440 (One day in minutes).
	 *
	 * @hook mc_ical_x_published_ttl
	 *
	 * @param {int} $ttl Refresh interval in minutes.
	 *
	 * @return {int}
	 */
	$ttl = apply_filters( 'mc_ical_x_published_ttl', 1440 );
	foreach ( array_keys( $events ) as $key ) {
		$event =& $events[ $key ];
		if ( is_object( $event ) ) {
			if ( ! mc_private_event( $event ) ) {
				// Only include one recurring instance in collection.
				if ( mc_is_recurring( $event ) && in_array( $event->event_id, $processed, true ) ) {
					continue;
				} else {
					$processed[] = $event->event_id;
				}
				$tags  = mc_create_tags( $event, $context );
				$tz_id = get_option( 'timezone_string' );
				$off   = ( get_option( 'gmt_offset' ) * -1 );
				$etc   = 'Etc/GMT' . ( ( 0 > $off ) ? $off : '+' . $off );
				$tz_id = ( $tz_id ) ? $tz_id : $etc;
				/**
				 * Filter TimeZone passed to `DateTimeZone` to set event timezone in iCal.
				 *
				 * @hook mc_ical_timezone
				 *
				 * @param {string} $tz_id Existing timezone identifier.
				 * @param {object} $event Event object.
				 *
				 * @return {string}
				 */
				$tz_id = apply_filters( 'mc_ical_timezone', $tz_id, $event );
				$rrule = mc_generate_rrule( $event );

				$ical = CustomEvent::create( $tags['title'] )
					->startsAt( new DateTime( $tags['ical_date_start'], new DateTimeZone( $tz_id ) ) )
					->endsAt( new DateTime( $tags['ical_date_end'], new DateTimeZone( $tz_id ) ) )
					->address( $tags['ical_location'] )
					->description_html( $tags['excerpt'] )
					->organizer( $tags['host_email'], $tags['host'] )
					->categories( explode( ',', $tags['ical_categories'] ) )
					->url( $tags['details_link'] )
					->uniqueIdentifier( $tags['dateid'] . '-' . $tags['id'] );
				if ( $rrule ) {
					$ical->rruleAsString( $rrule );
				}
				if ( mc_is_all_day( $event ) ) {
					$ical->fullDay();
				}
				/**
				 * Filter information used to set an alarm on an event in .ics files.
				 *
				 * @hook mc_event_has_alarm
				 *
				 * @param {array} Alarm information passable to `mc_generate_alert_ical()`
				 * @param {int}   $event_id Event ID.
				 * @param {int}   $post Post ID.
				 *
				 * @return {array}
				 */
				$alarm = apply_filters( 'mc_event_has_alarm', array(), $event->event_id, $tags['post'] );
				if ( ! empty( $alarm ) ) {
					$abs  = str_contains( $alarm['trigger'], '-' ) ? 'before' : 'after';
					$time = preg_replace( '/[^0-9]/', '', $alarm['trigger'] );
					$desc = ( isset( $alarm['description'] ) ) ? $alarm['description'] : '';
					if ( 'before' === $abs ) {
						$ical->alertMinutesBefore( $time, $desc );
					} else {
						$ical->alertMinutesAfter( $time, $desc );
					}
				}
				$ical_events[] = $ical;
			}
		}
	} // End foreach.
	$calendar->event( $ical_events )
		->description( $events_from )
		->refreshInterval( $ttl );
	return $calendar->get();
}
