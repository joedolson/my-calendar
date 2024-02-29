<?php
/**
 * Class Tests_My_Calendar_Date_Utilities
 *
 * @package Sample_Plugin
 */

/**
 * Sample test case.
 */
class Tests_My_Calendar_Date_Utilities extends WP_UnitTestCase {
	/**
	 * Test that date classes return the correct matching class for current, past, and future days.
	 */
	public function test_mc_dateclass() {
		$past   = mc_dateclass( strtotime( '-1 day' ) );
		$now    = mc_dateclass( strtotime( 'now' ) );
		$future = mc_dateclass( strtotime( '+1 day' ) );

		$this->assertStringContainsString( 'past-day past-date', $past, 'Past date does not generate past date classes.' );
		$this->assertStringContainsString( 'current-day', $now, 'Current date does not generate current date class.' );
		$this->assertStringContainsString( 'future-day', $future, 'Future date does not generate future date class.' );
	}

	/**
	 * Test that adding a value to a date returns the expected new date.
	 */
	public function test_my_calendar_add_date() {
		$tomorrow   = my_calendar_add_date( '2024-02-28', 1, 0, 0 );
		$next_month = my_calendar_add_date( '2024-02-28', 0, 1, 0 );
		$next_year  = my_calendar_add_date( '2024-02-28', 0, 0, 1 );

		$this->assertSame( $tomorrow, strtotime( '2024-02-29' ), 'Adding one day does not return the correct date.' );
		$this->assertSame( $next_month, strtotime( '2024-03-28' ), 'Adding one month does not return the correct date.' );
		$this->assertSame( $next_year, strtotime( '2025-02-28' ), 'Adding one year does not return the correct date.' );
	}

	/**
	 * Test that date comparisons checking before or equal with time precision are correct.
	 */
	public function test_my_calendar_date_comp() {
		$first_is_earlier   = my_calendar_date_comp( '2024-02-28 01:00:00', '2024-02-28 01:01:00' );
		$first_is_later     = my_calendar_date_comp( '2024-02-28 01:01:00', '2024-02-28 01:00:00' );
		$dates_are_the_same = my_calendar_date_comp( '2024-02-28 01:00:00', '2024-02-28 01:00:00' );

		$this->assertSame( $first_is_earlier, true, 'First is earlier should be true.' );
		$this->assertSame( $first_is_later, false, 'First is later should be false.' );
		$this->assertSame( $dates_are_the_same, true, 'Identical dates should be true.' );
	}

	/**
	 * Test that date comparisons checking before only with time precision are correct.
	 */
	public function test_my_calendar_date_xcomp() {
		$first_is_earlier   = my_calendar_date_xcomp( '2024-02-28 01:00:00', '2024-02-28 01:01:00' );
		$first_is_later     = my_calendar_date_xcomp( '2024-02-28 01:01:00', '2024-02-28 01:00:00' );
		$dates_are_the_same = my_calendar_date_xcomp( '2024-02-28 01:00:00', '2024-02-28 01:00:00' );

		$this->assertSame( $first_is_earlier, true, 'First is earlier should be true.' );
		$this->assertSame( $first_is_later, false, 'First is later should be false.' );
		$this->assertSame( $dates_are_the_same, false, 'Identical dates should be false.' );
	}

	/**
	 * Test that date comparisons checking equality only with day precision are correct.
	 */
	public function test_my_calendar_date_equal() {
		$first_is_earlier   = my_calendar_date_equal( '2024-02-27 01:00:00', '2024-02-28 01:01:00' );
		$first_is_later     = my_calendar_date_equal( '2024-02-29 01:01:00', '2024-02-28 01:00:00' );
		$dates_are_the_same = my_calendar_date_equal( '2024-02-28 12:00:00', '2024-02-28 01:00:00' );

		$this->assertSame( $first_is_earlier, false, 'First is earlier should be false.' );
		$this->assertSame( $first_is_later, false, 'First is later should be false.' );
		$this->assertSame( $dates_are_the_same, true, 'Matched dates should be true.' );
	}

	/**
	 * Test that the week of the month an event occurs in is correct.
	 */
	public function test_mc_week_of_month() {
		$first_week  = mc_week_of_month( 1 );
		$second_week = mc_week_of_month( 8 );
		$third_week  = mc_week_of_month( 15 );
		$fourth_week = mc_week_of_month( 22 );
		$fifth_week  = mc_week_of_month( 29 );

		$this->assertSame( $first_week, 0, 'This date should be the 1st week of the month.' );
		$this->assertSame( $second_week, 1, 'This date should be the 2nd week of the month.' );
		$this->assertSame( $third_week, 2, 'This date should be the 3rd week of the month.' );
		$this->assertSame( $fourth_week, 3, 'This date should be the 4th week of the month.' );
		$this->assertSame( $fifth_week, 4, 'This date should be the 5th week of the month.' );
	}

	/**
	 * Test that the date validation returns a valid date.
	 */
	public function test_mc_checkdate() {
		$valid   = mc_checkdate( '2024-02-28' ); // Real date.
		$invalid = mc_checkdate( '2023-02-32' ); // Properly formatted, but doesn't exist.
		$notdate = mc_checkdate( 'not a date' ); // Not a valid date format.
		$string  = mc_checkdate( 'Wednesday, February 28, 2024' );

		$this->assertSame( $valid, '2024-02-28', 'Valid Y-m-d dates should return the same information passed.' );
		$this->assertSame( $string, '2024-02-28', 'Valid string dates should return a Y-m-d equivalent.' );
		$this->assertSame( $invalid, gmdate( 'Y-m-d' ), 'Invalid dates should return today.' );
		$this->assertSame( $notdate, gmdate( 'Y-m-d' ), 'Dates that are not valid in any way should return current day.' );
	}

	/**
	 * Test where a specific date lies within a month as an position and day name.
	 */
	public function test_mc_recur_date() {
		$first_week  = mc_recur_date( strtotime( '2024-02-01' ) );
		$second_week = mc_recur_date( strtotime( '2024-02-08' ) );
		$third_week  = mc_recur_date( strtotime( '2024-02-15' ) );
		$fourth_week = mc_recur_date( strtotime( '2024-02-22' ) );
		$fifth_week  = mc_recur_date( strtotime( '2024-02-29' ) );

		$first_week_expected  = array(
			'num' => 1,
			'day' => 'Thursday',
		);
		$second_week_expected = array(
			'num' => 2,
			'day' => 'Thursday',
		);
		$third_week_expected  = array(
			'num' => 3,
			'day' => 'Thursday',
		);
		$fourth_week_expected = array(
			'num' => 4,
			'day' => 'Thursday',
		);
		$fifth_week_expected  = array(
			'num' => 5,
			'day' => 'Thursday',
		);
		$this->assertSame( $first_week, $first_week_expected, 'This date should be the 1st Thursday of the month.' );
		$this->assertSame( $second_week, $second_week_expected, 'This date should be the 2nd Thursday of the month.' );
		$this->assertSame( $third_week, $third_week_expected, 'This date should be the 3rd Thursday of the month.' );
		$this->assertSame( $fourth_week, $fourth_week_expected, 'This date should be the 4th Thursday of the month.' );
		$this->assertSame( $fifth_week, $fifth_week_expected, 'This date should be the 5th Thursday of the month.' );
	}

	/**
	 * Test that the day & month of the first day of the current week is correctly identified.
	 */
	public function test_mc_first_day_of_week_is_sunday() {
		update_option( 'start_of_week', '0' );
		$start_of_week_is_sunday = mc_first_day_of_week( strtotime( '2024-04-04' ) );
		$sunday_expected         = array( '31', -1 );

		$this->assertSame( $start_of_week_is_sunday, $sunday_expected, 'Expecting Sunday, March 31st.' );
	}

	/**
	 * Test that the day & month of the first day of the current week is correctly identified.
	 */
	public function test_mc_first_day_of_week_is_monday() {
		update_option( 'start_of_week', '1' );
		$start_of_week_is_monday = mc_first_day_of_week( strtotime( '2024-04-04' ) );
		$monday_expected         = array( '1', 0 );

		$this->assertSame( $start_of_week_is_monday, $monday_expected, 'Expecting Monday, April 1st.' );
	}

	/**
	 * Test that the day & month of the first day of the current week is correctly identified.
	 */
	public function test_mc_first_day_of_week_is_other() {
		update_option( 'start_of_week', '2' );
		$start_of_week_is_other = mc_first_day_of_week( strtotime( '2024-04-04' ) );
		$sunday_expected        = array( '31', -1 );

		$this->assertSame( $start_of_week_is_other, $sunday_expected, 'Expecting Sunday, March 31st.' );
	}

	/**
	 * Test that date formatting acts as expected.
	 */
	public function test_mc_date() {
		$offset = get_option( 'gmt_offset' );
		update_option( 'gmt_offset', '6' );
		$timestamp       = mc_date( '', false, false );
		$test_time       = time();
		$includes_offset = mc_date( 'Y-m-d H:i:s', strtotime( '2024-02-28 00:00:00' ) );
		$without_offset  = mc_date( 'Y-m-d H:i:s', strtotime( '2024-02-28 00:00:00' ), false );
		update_option( 'gmt_offset', $offset ); // Reset to original setting.

		$this->assertSame( $timestamp, $test_time, 'Expecting current timestamp.' );
		$this->assertSame( $includes_offset, '2024-02-28 06:00:00', 'Expecting a six hour timezone offset.' );
		$this->assertSame( $without_offset, '2024-02-28 00:00:00', 'Expecting same value as passed.' );
	}

	/**
	 * Test that the start and end dates for the displayed period are correct.
	 */
	public function test_mc_date_array() {
		update_option( 'start_of_week', 0 );
		$month_view = mc_date_array( strtotime( '2024-02-01' ), 'month' );
		$week_view  = mc_date_array( strtotime( '2024-02-26' ), 'week' );
		$next_month = mc_date_array( strtotime( '2024-02-01' ), 'month+1' );
		$two_month  = mc_date_array( strtotime( '2024-02-01' ), 'month', 1 );

		$month_view_expected = array(
			'from' => '2024-01-28',
			'to'   => '2024-03-02',
		);
		$week_view_expected  = array(
			'from' => '2024-02-25',
			'to'   => '2024-03-02',
		);
		$next_month_expected = array(
			'from' => '2024-02-25',
			'to'   => '2024-04-06',
		);
		$two_month_expected  = array(
			'from' => '2024-01-28',
			'to'   => '2024-04-06',
		);
		$this->assertSame( $month_view, $month_view_expected, 'Expected Jan 28 to March 2.' );
		$this->assertSame( $week_view, $week_view_expected, 'Expected Feb 25 to March 2.' );
		$this->assertSame( $next_month, $next_month_expected, 'Expected Feb 25 to Apr 6.' );
		$this->assertSame( $two_month, $two_month_expected, 'Expected Jan 28 to Apr 6.' );
	}

	/**
	 * Get the month from/to dates in list view.
	 */
	public function test_mc_get_from_to() {
		$params       = array(
			'format' => 'list',
			'time'   => 'month',
		);
		$date         = array(
			'day'          => 28,
			'month'        => 2,
			'year'         => 2024,
			'current_date' => '2024-02-28',
		);
		$single_month = mc_get_from_to( 1, $params, $date );
		$two_month    = mc_get_from_to( 2, $params, $date );

		$expected_single = array(
			'from' => '2024-02-01',
			'to'   => '2024-02-29',
		);
		$expected_multi  = array(
			'from' => '2024-02-01',
			'to'   => '2024-03-31',
		);
		$this->assertSame( $single_month, $expected_single, 'Expected Feb 1 to Feb 29.' );
		$this->assertSame( $two_month, $expected_multi, 'Expected Feb 1 to March 31.' );
	}
}
