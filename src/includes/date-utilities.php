<?php
/**
 * Date Utilities file
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
 * Generate classes for a given date
 *
 * @param int $current timestamp.
 *
 * @return string classes
 */
function mc_dateclass( $current ) {
	$dayclass = sanitize_html_class( strtolower( date_i18n( 'l', $current ) ) ) . ' ' . sanitize_html_class( strtolower( date_i18n( 'D', $current ) ) );
	if ( current_time( 'Ymd' ) === mc_date( 'Ymd', $current, false ) ) {
		$dateclass = 'current-day';
	} elseif ( my_calendar_date_comp( current_time( 'Y-m-d' ), mc_date( 'Y-m-d', $current, false ) ) ) {
		$dateclass = 'future-day';
	} else {
		$dateclass = 'past-day past-date'; // stupid legacy classes.
	}

	return esc_attr( $dayclass . ' ' . $dateclass );
}

/**
 * Given a date and a quantity of time to add, produce new date
 *
 * @param string $givendate A time string.
 * @param int    $day Number of days to add.
 * @param int    $mth Number of months to add.
 * @param int    $yr number of years to add.
 *
 * @return int
 */
function my_calendar_add_date( $givendate, $day = 0, $mth = 0, $yr = 0 ) {
	$cd      = strtotime( $givendate );
	$newdate = mktime( mc_date( 'H', $cd, false ), mc_date( 'i', $cd, false ), mc_date( 's', $cd, false ), (int) mc_date( 'm', $cd, false ) + $mth, (int) mc_date( 'd', $cd, false ) + $day, (int) mc_date( 'Y', $cd, false ) + $yr );

	return $newdate;
}

/**
 * Test if the date is before or equal to second date with time precision
 *
 * @param string $early date string.
 * @param string $late date string.
 *
 * @return boolean true if first date earlier or equal
 */
function my_calendar_date_comp( $early, $late ) {
	$firstdate = strtotime( $early );
	$lastdate  = strtotime( $late );
	if ( $firstdate <= $lastdate ) {

		return true;
	} else {

		return false;
	}
}

/**
 * Test if first date before second date with time precision
 *
 * @param string $early date string.
 * @param string $late date string.
 *
 * @return boolean true if first date earlier
 */
function my_calendar_date_xcomp( $early, $late ) {
	$firstdate = strtotime( $early );
	$lastdate  = strtotime( $late );
	if ( $firstdate < $lastdate ) {

		return true;
	} else {

		return false;
	}
}

/**
 *  Test if dates are the same with day precision
 *
 * @param string $early date string in current time zone.
 * @param string $late date string in current time zone.
 *
 * @return boolean true if first date equal to second
 */
function my_calendar_date_equal( $early, $late ) {
	// convert full datetime to date only.
	$firstdate = strtotime( mc_date( 'Y-m-d', strtotime( $early ), false ) );
	$lastdate  = strtotime( mc_date( 'Y-m-d', strtotime( $late ), false ) );
	if ( $firstdate === $lastdate ) {

		return true;
	} else {

		return false;
	}
}

/**
 * Function to compare time in event objects for sorting
 *
 * @param object $a event object.
 * @param object $b event object.
 *
 * @return int (ternary value)
 */
function mc_time_cmp( $a, $b ) {
	if ( $a->occur_begin === $b->occur_begin ) {

		return 0;
	}

	return ( $a->occur_begin < $b->occur_begin ) ? - 1 : 1;
}

/**
 * Function to compare datetime in event objects & sort by string
 *
 * @param object $a event object.
 * @param object $b event object.
 *
 * @return integer (ternary value)
 */
function mc_datetime_cmp( $a, $b ) {
	$event_dt_a = strtotime( $a->occur_begin );
	$event_dt_b = strtotime( $b->occur_begin );
	if ( $event_dt_a === $event_dt_b ) {
		// this should sub-sort by title if date is the same. But it doesn't seem to.
		$ta = $a->event_title;
		$tb = $b->event_title;

		return strcmp( $ta, $tb );
	}

	return ( $event_dt_a < $event_dt_b ) ? - 1 : 1;
}

/**
 * Compare two event dates with time precision
 *
 * @param object $a event object.
 * @param object $b event object.
 *
 * @return integer (ternary value)
 */
function mc_timediff_cmp( $a, $b ) {
	$a          = $a . current_time( ' H:i:s' );
	$b          = $b . current_time( ' H:i:s' );
	$event_dt_a = strtotime( $a );
	$event_dt_b = strtotime( $b );
	$diff_a     = mc_date_diff_precise( $event_dt_a );
	$diff_b     = mc_date_diff_precise( $event_dt_b );

	if ( $diff_a === $diff_b ) {
		return 0;
	}

	return ( $diff_a < $diff_b ) ? - 1 : 1;
}

/**
 * Compare two dates for diff with high precision
 *
 * @param int        $start timestamp.
 * @param int|string $end timestamp or 'now'.
 *
 * @return int absolute value of time diff
 */
function mc_date_diff_precise( $start, $end = 'NOW' ) {
	if ( 'NOW' === $end ) {
		$end = strtotime( 'NOW' );
	}
	$sdate = $start;
	$edate = $end;

	$time = $edate - $sdate;

	return abs( $time );
}

/**
 * Get the week of the month a given date falls on.
 *
 * @param integer $date_of_event  A numbered day of the month. E.g., 9 or 24.
 *
 * @return integer $week_of_event The week of the month this date falls in, 0 - 4.
 */
function mc_week_of_month( $date_of_event ) {
	$week_of_event = 0;
	switch ( $date_of_event ) {
		case ( $date_of_event >= 1 && $date_of_event < 8 ):
			$week_of_event = 0;
			break;
		case ( $date_of_event >= 8 && $date_of_event < 15 ):
			$week_of_event = 1;
			break;
		case ( $date_of_event >= 15 && $date_of_event < 22 ):
			$week_of_event = 2;
			break;
		case ( $date_of_event >= 22 && $date_of_event < 29 ):
			$week_of_event = 3;
			break;
		case ( $date_of_event >= 29 ):
			$week_of_event = 4;
			break;
	}

	return $week_of_event;
}

/**
 * Validate that a string is a valid date. Returns a Y-m-d date string with no timezone offset applied.
 *
 * @param string $date date string.
 *
 * @return boolean|string date string if verified date, false if not.
 */
function mc_checkdate( $date ) {
	$time = strtotime( $date );
	$m    = mc_date( 'n', $time );
	$d    = mc_date( 'j', $time );
	$y    = mc_date( 'Y', $time );

	$check = checkdate( $m, $d, $y );
	if ( $check ) {
		return mc_date( 'Y-m-d', $time, false );
	}

	return false;
}

/**
 * Determine where a date lies in a month in terms of week/day.
 *
 * @param string $ts Timestamp.
 *
 * @return array
 */
function mc_recur_date( $ts ) {
	$ts      = is_numeric( $ts ) ? $ts : strtotime( $ts );
	$weekday = mc_date( 'l', $ts, false );
	$month   = mc_date( 'M', $ts, false );
	$ord     = 1;

	while ( mc_date( 'M', ( $ts = strtotime( '-1 week', $ts ) ), false ) === $month ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		++$ord;
	}

	return array(
		'num' => $ord,
		'day' => $weekday,
	);
}

/**
 * Get the first day value of the current week.
 *
 * @param int|boolean $timestamp timestamp + offset or false if now.
 *
 * @return array day and integer representing the month offset. -1 for previous month, 0 for current month.
 */
function mc_first_day_of_week( $timestamp = false ) {
	$start_of_week = ( get_option( 'start_of_week' ) === '1' || get_option( 'start_of_week' ) === '0' ) ? absint( get_option( 'start_of_week' ) ) : 0;
	if ( $timestamp ) {
		$today = mc_date( 'w', $timestamp, false );
		$now   = mc_date( 'Y-m-d', $timestamp, false );
	} else {
		$today = current_time( 'w' );
		$now   = current_time( 'Y-m-d' );
	}
	$month = 0;
	$sub   = 0; // don't change month.
	switch ( $today ) {
		case 1:
			$sub = ( 1 === $start_of_week ) ? 0 : 1;
			break; // mon.
		case 2:
			$sub = ( 1 === $start_of_week ) ? 1 : 2;
			break; // tues.
		case 3:
			$sub = ( 1 === $start_of_week ) ? 2 : 3;
			break; // wed.
		case 4:
			$sub = ( 1 === $start_of_week ) ? 3 : 4;
			break; // thu.
		case 5:
			$sub = ( 1 === $start_of_week ) ? 4 : 5;
			break; // fri.
		case 6:
			$sub = ( 1 === $start_of_week ) ? 5 : 6;
			break; // sat.
		case 0:
			$sub = ( 1 === $start_of_week ) ? 6 : 0;
			break; // sun.
	}
	$day = mc_date( 'j', strtotime( $now . ' -' . $sub . ' day' ), false );
	if ( 0 !== $sub ) {
		if ( mc_date( 'n', strtotime( $now . ' -' . $sub . ' day' ), false ) !== mc_date( 'n', strtotime( $now ), false ) ) {
			$month = - 1;
		} else {
			$month = 0;
		}
	}

	return array( $day, $month );
}

/**
 * Generate an ordinal string in English for numeric values
 *
 * @param int $number Any integer value.
 *
 * @return string number plus ordinal value
 */
function mc_ordinal( $number ) {
	// when fed a number, adds the English ordinal suffix. Works for any number.
	if ( $number % 100 > 10 && $number % 100 < 14 ) {
		$suffix = 'th';
	} else {
		switch ( $number % 10 ) {
			case 0:
				$suffix = 'th';
				break;
			case 1:
				$suffix = 'st';
				break;
			case 2:
				$suffix = 'nd';
				break;
			case 3:
				$suffix = 'rd';
				break;
			default:
				$suffix = 'th';
				break;
		}
	}

	return apply_filters( 'mc_ordinal', "${number}$suffix", $number, $suffix );
}

/**
 * Handles all cases for exiting processing early: private events, drafts, etc.
 *
 * @param object $event Event object.
 * @param string $process_date Current date being articulated.
 *
 * @return boolean true if early exit is qualified.
 */
function mc_exit_early( $event, $process_date ) {
	// if event is not approved, return without processing.
	if ( 1 !== (int) $event->event_approved && ! mc_is_preview() ) {
		return true;
	}

	$hide_days_default = false;
	if ( 'true' === get_post_meta( $event->event_post, '_event_same_day', true ) ) {
		$hide_days_default = true;
	}
	/**
	 * Hide subsequent days of events crossing multiple days.
	 *
	 * @hook mc_hide_additional_days
	 *
	 * @param {bool}   $hide_days_default False if 'same day event' not checked.
	 * @param {object} $event Event object.
	 *
	 * @return {bool}
	 */
	$hide_days = apply_filters( 'mc_hide_additional_days', $hide_days_default, $event );
	$today     = mc_date( 'Y-m-d', strtotime( $event->occur_begin ) );
	$current   = mc_date( 'Y-m-d', strtotime( $process_date ) );
	$end       = mc_date( 'Y-m-d', strtotime( $event->occur_end ) );
	// if event ends at midnight today (e.g., very first thing of the day), exit without re-drawing.
	// or if event started yesterday & has event_hide_end checked.
	$ends_at_midnight = ( '00:00:00' === $event->event_endtime && $end === $process_date && $current !== $today ) ? true : false;

	// hides events if hiding end time & not first day.
	$hide_day_two = ( $hide_days && ( $today !== $current ) ) ? true : false;

	if ( $ends_at_midnight || $hide_day_two ) {
		return true;
	}

	if ( mc_private_event( $event ) ) {
		return true;
	}

	return false;
}

/**
 * Check whether an object with a category_private property is private &or hidden.
 *
 * @param object $event [can be a category object].
 * @param bool   $type true to check whether an object is hidden; false to check the object configuration.
 *
 * @return boolean
 */
function mc_private_event( $event, $type = true ) {
	// If this is an invalid event, consider it private.
	if ( ! is_object( $event ) || ! property_exists( $event, 'category_private' ) ) {
		return true;
	}
	if ( $type ) {
		// Checking whether this should currently be hidden.
		$status = ( 1 === absint( $event->category_private ) && ! is_user_logged_in() ) ? true : false;
	} else {
		// Checking whether this is supposed to be private.
		$status = ( 1 === absint( $event->category_private ) ) ? true : false;
	}

	/**
	 * Filter the privacy status of an event or category.
	 *
	 * @hook mc_private_event
	 *
	 * @param {bool}   $status true if an event is private, false if it is public.
	 * @param {object} $event A category or event object to test.
	 *
	 * @return {bool}
	 */
	$status = apply_filters( 'mc_private_event', $status, $event );

	return $status;
}

/**
 * Parse a string and replace internationalized months with English so strtotime() will parse correctly
 *
 * @param string $date Date information.
 *
 * @return int de-internationalized change
 */
function mc_strtotime( $date ) {
	$months  = array(
		date_i18n( 'F', strtotime( 'January 1' ) ),
		date_i18n( 'F', strtotime( 'February 1' ) ),
		date_i18n( 'F', strtotime( 'March 1' ) ),
		date_i18n( 'F', strtotime( 'April 1' ) ),
		date_i18n( 'F', strtotime( 'May 1' ) ),
		date_i18n( 'F', strtotime( 'June 1' ) ),
		date_i18n( 'F', strtotime( 'July 1' ) ),
		date_i18n( 'F', strtotime( 'August 1' ) ),
		date_i18n( 'F', strtotime( 'September 1' ) ),
		date_i18n( 'F', strtotime( 'October 1' ) ),
		date_i18n( 'F', strtotime( 'November 1' ) ),
		date_i18n( 'F', strtotime( 'December 1' ) ),
		date_i18n( 'M', strtotime( 'January 1' ) ),
		date_i18n( 'M', strtotime( 'February 1' ) ),
		date_i18n( 'M', strtotime( 'March 1' ) ),
		date_i18n( 'M', strtotime( 'April 1' ) ),
		date_i18n( 'M', strtotime( 'May 1' ) ),
		date_i18n( 'M', strtotime( 'June 1' ) ),
		date_i18n( 'M', strtotime( 'July 1' ) ),
		date_i18n( 'M', strtotime( 'August 1' ) ),
		date_i18n( 'M', strtotime( 'September 1' ) ),
		date_i18n( 'M', strtotime( 'October 1' ) ),
		date_i18n( 'M', strtotime( 'November 1' ) ),
		date_i18n( 'M', strtotime( 'December 1' ) ),
	);
	$english = array(
		'January',
		'February',
		'March',
		'April',
		'May',
		'June',
		'July',
		'August',
		'September',
		'October',
		'November',
		'December',
		'Jan',
		'Feb',
		'Mar',
		'Apr',
		'May',
		'Jun',
		'Jul',
		'Aug',
		'Sep',
		'Oct',
		'Nov',
		'Dec',
	);

	return strtotime( str_replace( $months, $english, $date ) );
}

/**
 * Wrapper for date(). Used for date comparisons and non-translatable dates.
 *
 * @param string    $format Default ''. Format to use. Empty string for timestamp.
 * @param int|false $timestamp Default false. Timestamp or false if current time..
 * @param bool      $offset Default true. False to not add offset; if already a timestamp.
 *
 * @return string|int Formatted date or timestamp if no format provided.
 */
function mc_date( $format = '', $timestamp = false, $offset = true ) {
	if ( ! $timestamp ) {
		// Timestamp is UTC.
		$timestamp = time();
	}
	if ( $offset ) {
		$offset = intval( get_option( 'gmt_offset', 0 ) ) * 60 * 60;
	} else {
		$offset = 0;
	}
	$timestamp = $timestamp + $offset;

	return ( '' === $format ) ? $timestamp : gmdate( $format, $timestamp );
}

/**
 * Get the days of the week for calendar layout.
 *
 * @param array $params Calendar parameters.
 * @param int   $start_of_week First day of this week.
 *
 * @return array
 */
function mc_get_week_days( $params, $start_of_week ) {
	$name_days = mc_name_days( $params['format'] );
	$abbrevs   = array( 'sun', 'mon', 'tues', 'wed', 'thur', 'fri', 'sat' );
	if ( 1 === (int) $start_of_week ) {
		$first       = array_shift( $name_days );
		$afirst      = array_shift( $abbrevs );
		$name_days[] = $first;
		$abbrevs[]   = $afirst;
	}
	$return = array(
		'name_days' => $name_days,
		'abbrevs'   => $abbrevs,
	);

	/**
	 * Filter the days of the week as used for column headings in grid format.
	 *
	 * @hook mc_get_week_days
	 * @since 3.4.0
	 *
	 * @param {array} $return Array of full names and abbreviations.
	 * @param {array} $params Array of parameters for this calendar view.
	 * @param {int}   $start_of_week Which day is the start of the week.
	 *
	 * @return {array}
	 */
	return apply_filters( 'mc_get_week_days', $return, $params, $start_of_week );
}

/**
 * Generate abbreviations & code used for HTML output of calendar headings.
 *
 * @param string $format 'mini', 'list', 'grid'.
 *
 * @return array HTML for each day in an array.
 */
function mc_name_days( $format ) {
	$name_days = array(
		'<abbr title="' . date_i18n( 'l', strtotime( 'Sunday' ) ) . '" aria-hidden="true">' . date_i18n( 'D', strtotime( 'Sunday' ) ) . '</abbr><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Sunday' ) ) . '</span>',
		'<abbr title="' . date_i18n( 'l', strtotime( 'Monday' ) ) . '" aria-hidden="true">' . date_i18n( 'D', strtotime( 'Monday' ) ) . '</abbr><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Monday' ) ) . '</span>',
		'<abbr title="' . date_i18n( 'l', strtotime( 'Tuesday' ) ) . '" aria-hidden="true">' . date_i18n( 'D', strtotime( 'Tuesday' ) ) . '</abbr><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Tuesday' ) ) . '</span>',
		'<abbr title="' . date_i18n( 'l', strtotime( 'Wednesday' ) ) . '" aria-hidden="true">' . date_i18n( 'D', strtotime( 'Wednesday' ) ) . '</abbr><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Wednesday' ) ) . '</span>',
		'<abbr title="' . date_i18n( 'l', strtotime( 'Thursday' ) ) . '" aria-hidden="true">' . date_i18n( 'D', strtotime( 'Thursday' ) ) . '</abbr><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Thursday' ) ) . '</span>',
		'<abbr title="' . date_i18n( 'l', strtotime( 'Friday' ) ) . '" aria-hidden="true">' . date_i18n( 'D', strtotime( 'Friday' ) ) . '</abbr><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Friday' ) ) . '</span>',
		'<abbr title="' . date_i18n( 'l', strtotime( 'Saturday' ) ) . '" aria-hidden="true">' . date_i18n( 'D', strtotime( 'Saturday' ) ) . '</abbr><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Saturday' ) ) . '</span>',
	);
	if ( 'mini' === $format ) {
		// PHP doesn't have a single letter abbreviation, so this has to be a translatable.
		$name_days = array(
			'<span aria-hidden="true">' . __( '<abbr title="Sunday">S</abbr>', 'my-calendar' ) . '</span><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Sunday' ) ) . '</span>', // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
			'<span aria-hidden="true">' . __( '<abbr title="Monday">M</abbr>', 'my-calendar' ) . '</span><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Monday' ) ) . '</span>', // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
			'<span aria-hidden="true">' . __( '<abbr title="Tuesday">T</abbr>', 'my-calendar' ) . '</span><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Tuesday' ) ) . '</span>', // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
			'<span aria-hidden="true">' . __( '<abbr title="Wednesday">W</abbr>', 'my-calendar' ) . '</span><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Wednesday' ) ) . '</span>', // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
			'<span aria-hidden="true">' . __( '<abbr title="Thursday">T</abbr>', 'my-calendar' ) . '</span><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Thursday' ) ) . '</span>', // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
			'<span aria-hidden="true">' . __( '<abbr title="Friday">F</abbr>', 'my-calendar' ) . '</span><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Friday' ) ) . '</span>', // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
			'<span aria-hidden="true">' . __( '<abbr title="Saturday">S</abbr>', 'my-calendar' ) . '</span><span class="screen-reader-text">' . date_i18n( 'l', strtotime( 'Saturday' ) ) . '</span>', // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
		);
	}

	return $name_days;
}

/**
 * Calculate dates that should be used to set start and end dates for current view.
 *
 * @param string $timestamp Time stamp for first date of current period.
 * @param string $period base type of date span displayed.
 * @param int    $months Number of months to add to display.
 *
 * @return array from and to dates
 */
function mc_date_array( $timestamp, $period, $months = 0 ) {
	switch ( $period ) {
		case 'month':
		case 'month+1':
			if ( 'month+1' === $period ) {
				$timestamp = strtotime( '+1 month', $timestamp );
			}
			$start_of_week = get_option( 'start_of_week' );
			$first         = mc_date( 'N', $timestamp, false ); // ISO-8601.
			$sub           = mc_date( 'w', $timestamp, false ); // numeric (how WordPress option is stored).
			$n             = ( 1 === (int) $start_of_week ) ? $first - 1 : $first;

			if ( $sub === $start_of_week ) {
				$from = mc_date( 'Y-m-d', $timestamp, false );
			} else {
				$start = strtotime( "-$n days", $timestamp );
				$from  = mc_date( 'Y-m-d', $start, false );
			}
			$endtime = mktime( 0, 0, 0, mc_date( 'm', $timestamp, false ), mc_date( 't', $timestamp, false ), mc_date( 'Y', $timestamp, false ) );
			$endtime = strtotime( "+$months months", $endtime );
			$last    = (int) mc_date( 'N', $endtime, false );
			$n       = ( '1' === get_option( 'start_of_week' ) ) ? 7 - $last : 6 - $last;
			if ( -1 === $n && '7' === mc_date( 'N', $endtime, false ) ) {
				$n = 6;
			}
			$to = mc_date( 'Y-m-d', strtotime( "+$n days", $endtime ), false );

			$calculated_date = mc_date( 'j', strtotime( $to ), false );
			$last_of_month   = mc_date( 't', strtotime( $to ), false );
			$boundary        = (int) $last_of_month - 7;
			/**
			 * This looks magic, but it's logical. The problem here is that
			 * strtotime( "+ 1 month" ) doesn't always yield the next month.
			 * But it does always yield something close to it. If the date is
			 * more than length - 7, this is late in the month, and can be increased.
			 */
			if ( $calculated_date < $last_of_month && $calculated_date > $boundary ) {
				$n  = $n + 7;
				$to = mc_date( 'Y-m-d', strtotime( "+$n days", $endtime ), false );
			}

			$return = array(
				'from' => $from,
				'to'   => $to,
			);
			break;
		case 'week':
			$day_of_week   = (int) mc_date( 'N', $timestamp, false );
			$start_of_week = ( get_option( 'start_of_week' ) === '1' ) ? 1 : 7; // convert start of week to ISO 8601 (Monday/Sunday).
			if ( $day_of_week !== $start_of_week ) {
				if ( $start_of_week > $day_of_week ) {
					$diff = 7 - ( ( $start_of_week - $day_of_week ) );
				} else {
					$diff = ( $day_of_week - $start_of_week );
				}
				$timestamp = ( 7 !== $diff ) ? strtotime( "-$diff days", $timestamp ) : $timestamp;
			}
			$from = mc_date( 'Y-m-d', $timestamp, false );
			$to   = mc_date( 'Y-m-d', strtotime( '+6 days', $timestamp ), false );

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
	$c_month = (int) $date['month'];
	$c_year  = (int) $date['year'];
	// The first day of the current month.
	$month_first = mktime( 0, 0, 0, $c_month, 1, $c_year );

	if ( 'list' === $format && 'week' !== $time ) {
		if ( $num > 0 && 'day' !== $time && 'week' !== $time ) {
			if ( 'month+1' === $time ) {
				$from = mc_date( 'Y-m-d', strtotime( '+1 month', $month_first ), false );
				$next = strtotime( "+$num months", strtotime( '+1 month', $month_first ) );
			} else {
				$from = mc_date( 'Y-m-d', $month_first, false );
				$next = strtotime( "+$num months", $month_first );
			}
			$last = mc_date( 't', $next, false );
			$to   = mc_date( 'Y-m', $next, false ) . '-' . $last;
		} else {
			$from = mc_date( 'Y-m-d', $month_first, false );
			$to   = mc_date( 'Y-m-d', mktime( 0, 0, 0, $c_month, mc_date( 't', $month_first, false ), $c_year ), false );
		}
		$dates = array(
			'from' => $from,
			'to'   => $to,
		);
	} else {
		// Get a view based on current date.
		$dates = mc_date_array( $date['current_date'], $time, $num );
	}

	return $dates;
}

/**
 * Test whether an event is in the past, currently happening, or in the future.
 *
 * @param object $event Event object.
 *
 * @return int
 */
function mc_date_relation( $event ) {
	$ts            = $event->ts_occur_begin;
	$end           = $event->ts_occur_end;
	$date_relation = 2;
	$now           = time();
	if ( $ts < $now && $end > $now ) {
		/**
		 * Execute action while an event is happening.
		 *
		 * @hook mc_event_happening
		 *
		 * @param {object} $object Event object.
		 */
		do_action( 'mc_event_happening', $event );
		$date_relation = 1;
	} elseif ( $now < $ts ) {
		/**
		 * Execute action before an event will occur.
		 *
		 * @hook mc_event_future
		 *
		 * @param {object} $object Event object.
		 */
		do_action( 'mc_event_future', $event );
		$date_relation = 2;
	} elseif ( $now > $ts ) {
		/**
		 * Execute action after an event has occurred.
		 *
		 * @hook mc_event_over
		 *
		 * @param {object} $object Event object.
		 */
		do_action( 'mc_event_over', $event );
		$date_relation = 0;
	}

	return $date_relation;
}

/**
 * Get the date format for My Calendar primary views.
 *
 * @return string
 */
function mc_date_format() {
	$date_format = ( '' === mc_get_option( 'date_format' ) ) ? get_option( 'date_format' ) : mc_get_option( 'date_format' );

	return $date_format;
}

/**
 * Produce the human-readable string for recurrence.
 *
 * @param object $event Event object.
 * @param array  $args Recurrence settings.
 *
 * @return string Type of recurrence
 */
function mc_recur_string( $event, $args = array() ) {
	if ( ! $event ) {
		$recur = $args['recur'];
		$every = (int) $args['every'];
		$until = $args['until'];
	} else {
		$recurs = str_split( $event->event_recur, 1 );
		$recur  = $recurs[0];
		$every  = ( isset( $recurs[1] ) ) ? str_replace( $recurs[0], '', $event->event_recur ) : 1;
		$until  = $event->event_repeats;
	}
	$string = '';
	// Interpret the DB values into something human readable.
	switch ( $recur ) {
		case 'D':
			// Translators: number of days between repetitions.
			$string = ( 1 === (int) $every ) ? __( 'Daily', 'my-calendar' ) : sprintf( __( 'Every %s day', 'my-calendar' ), mc_ordinal( $every ) );
			break;
		case 'E':
			// Translators: number of days between repetitions.
			$string = ( 1 === (int) $every ) ? __( 'Weekdays', 'my-calendar' ) : sprintf( __( 'Every %s weekday', 'my-calendar' ), mc_ordinal( $every ) );
			break;
		case 'W':
			// Translators: number of weeks between repetitions.
			$string = ( 1 === (int) $every ) ? __( 'Weekly', 'my-calendar' ) : sprintf( __( 'Every %d weeks', 'my-calendar' ), $every );
			break;
		case 'B':
			$string = __( 'Bi-Weekly', 'my-calendar' );
			break;
		case 'M':
			// Translators: number of months between repetitions.
			$string = ( 1 === (int) $every ) ? __( 'Monthly (by date)', 'my-calendar' ) : sprintf( __( 'Every %d months (by date)', 'my-calendar' ), $every );
			break;
		case 'U':
			$string = __( 'Monthly (by day)', 'my-calendar' );
			break;
		case 'Y':
			// Translators: number of years between repetitions.
			$string = ( 1 === (int) $every ) ? __( 'Yearly', 'my-calendar' ) : sprintf( __( 'Every %d years', 'my-calendar' ), $every );
			break;
	}
	$eternity = _mc_increment_values( $recur );
	if ( $until && 'S' !== $recur ) {
		if ( is_numeric( $until ) ) {
			// Translators: number of repeats.
			$string .= ' ' . sprintf( __( '- %d Times', 'my-calendar' ), $until );
		} else {
			// Translators: date repeating until.
			$string .= ' ' . sprintf( __( ' until %s', 'my-calendar' ), $until );
		}
	} elseif ( $eternity ) {
		// Translators: number of repeats.
		$string .= ' ' . sprintf( __( '- %d Times', 'my-calendar' ), $eternity );
	}

	return $string;
}
