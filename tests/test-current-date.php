<?php
/**
 * Test mc_get_current_date(), which determines the date a calendar view is based on.
 *
 * @package My_Calendar
 */

/**
 * Tests for mc_get_current_date().
 */
class Tests_My_Calendar_Current_Date extends WP_UnitTestCase {

	/**
	 * Main calendar ID/class used when GET parameters should apply.
	 *
	 * @var string
	 */
	private $main = 'mc-main';

	/**
	 * Reset request state.
	 */
	public function set_up() {
		parent::set_up();
		$_GET = array();
		update_option( 'start_of_week', '1' );
	}

	/**
	 * Clean up request state and filters.
	 */
	public function tear_down() {
		$_GET = array();
		remove_all_filters( 'mc_filter_year' );
		remove_all_filters( 'mc_filter_month' );
		remove_all_filters( 'mc_filter_day' );
		parent::tear_down();
	}

	/**
	 * Build shortcode parameters.
	 *
	 * @param string $time  Time frame: month, week, or day.
	 * @param array  $extra Overrides for smonth, syear, sday.
	 *
	 * @return array
	 */
	private function params( $time = 'month', $extra = array() ) {
		return array_merge(
			array(
				'time'   => $time,
				'smonth' => '',
				'syear'  => '',
				'sday'   => '',
			),
			$extra
		);
	}

	/**
	 * Get the date for the main calendar.
	 *
	 * @param array  $params  Shortcode parameters.
	 * @param string $main_id Calendar ID to treat as the main calendar.
	 *
	 * @return array
	 */
	private function date( $params, $main_id = '' ) {
		$main_id = ( '' === $main_id ) ? $this->main : $main_id;

		return mc_get_current_date( $this->main, $main_id, $params );
	}

	/**
	 * Assert the returned day, month, and year, and that the timestamp matches.
	 *
	 * @param int    $year   Expected year.
	 * @param int    $month  Expected month.
	 * @param int    $day    Expected day.
	 * @param array  $result Result from mc_get_current_date().
	 * @param string $msg   Failure message.
	 */
	private function assert_date( $year, $month, $day, $result, $msg = '' ) {
		$this->assertSame( $year, $result['year'], "$msg Year." );
		$this->assertSame( $month, $result['month'], "$msg Month." );
		$this->assertSame( $day, $result['day'], "$msg Day." );
		$this->assertSame( mktime( 0, 0, 0, $month, $day, $year ), $result['current_date'], "$msg Timestamp." );
	}

	/**
	 * A month never equal to the current month, to prove GET values are used.
	 *
	 * @return int
	 */
	private function other_month() {
		return ( (int) current_time( 'n' ) % 12 ) + 1;
	}

	/**
	 * Return value has the expected keys and types.
	 */
	public function test_returns_expected_structure() {
		$result = $this->date( $this->params() );

		$this->assertSame( array( 'day', 'month', 'year', 'current_date' ), array_keys( $result ) );
		$this->assertIsInt( $result['day'] );
		$this->assertIsInt( $result['month'] );
		$this->assertIsInt( $result['year'] );
		$this->assertIsInt( $result['current_date'] );
	}

	/**
	 * Month view with no values is the first of the current month.
	 */
	public function test_month_defaults_to_first_of_current_month() {
		$result = $this->date( $this->params( 'month' ) );

		$this->assert_date( (int) current_time( 'Y' ), (int) current_time( 'n' ), 1, $result );
	}

	/**
	 * Day view with no values is today.
	 */
	public function test_day_defaults_to_today() {
		$result = $this->date( $this->params( 'day' ) );

		$this->assert_date( (int) current_time( 'Y' ), (int) current_time( 'n' ), (int) current_time( 'j' ), $result );
	}

	/**
	 * Week view with no values starts on the first day of the current week.
	 *
	 * @dataProvider data_start_of_week
	 *
	 * @param string $start_of_week Day the week starts on: 0 for Sunday, 1 for Monday.
	 */
	public function test_week_defaults_to_start_of_current_week( $start_of_week ) {
		update_option( 'start_of_week', $start_of_week );

		$result = $this->date( $this->params( 'week' ) );
		$today  = strtotime( current_time( 'Y-m-d' ) );

		$this->assertLessThanOrEqual( $today, $result['current_date'], 'Week should not start after today.' );
		$this->assertGreaterThan( $today - ( 7 * DAY_IN_SECONDS ), $result['current_date'], 'Week should start within the last 7 days.' );
		$this->assertSame( (int) $start_of_week, (int) gmdate( 'w', $result['current_date'] ), 'Week should begin on the configured start of week.' );
	}

	/**
	 * Start of week values.
	 *
	 * @return array
	 */
	public static function data_start_of_week() {
		return array(
			'sunday' => array( '0' ),
			'monday' => array( '1' ),
		);
	}

	/**
	 * Shortcode month and year set a month view.
	 */
	public function test_shortcode_month_and_year_in_month_view() {
		$result = $this->date(
			$this->params(
				'month',
				array(
					'smonth' => 7,
					'syear'  => 2027,
				)
			)
		);

		$this->assert_date( 2027, 7, 1, $result );
	}

	/**
	 * Shortcode month alone keeps the current year.
	 */
	public function test_shortcode_month_only_uses_current_year() {
		$result = $this->date( $this->params( 'month', array( 'smonth' => 3 ) ) );

		$this->assert_date( (int) current_time( 'Y' ), 3, 1, $result );
	}

	/**
	 * Shortcode year alone keeps the current month.
	 */
	public function test_shortcode_year_only_uses_current_month() {
		$result = $this->date( $this->params( 'month', array( 'syear' => 2031 ) ) );

		$this->assert_date( 2031, (int) current_time( 'n' ), 1, $result );
	}

	/**
	 * Shortcode day, month, and year set a specific day in day view.
	 */
	public function test_shortcode_full_date_in_day_view() {
		$result = $this->date(
			$this->params(
				'day',
				array(
					'sday'   => 14,
					'smonth' => 2,
					'syear'  => 2026,
				)
			)
		);

		$this->assert_date( 2026, 2, 14, $result );
	}

	/**
	 * Shortcode date overrides the computed start of week in week view.
	 */
	public function test_shortcode_full_date_in_week_view() {
		$result = $this->date(
			$this->params(
				'week',
				array(
					'sday'   => 10,
					'smonth' => 5,
					'syear'  => 2025,
				)
			)
		);

		$this->assert_date( 2025, 5, 10, $result );
	}

	/**
	 * Shortcode day in month view is honored, not forced to the first.
	 */
	public function test_shortcode_day_in_month_view() {
		$result = $this->date(
			$this->params(
				'month',
				array(
					'sday'   => 9,
					'smonth' => 6,
					'syear'  => 2025,
				)
			)
		);

		$this->assert_date( 2025, 6, 9, $result );
	}

	/**
	 * Shortcode values can be passed as strings.
	 */
	public function test_shortcode_values_as_strings() {
		$result = $this->date(
			$this->params(
				'day',
				array(
					'sday'   => '5',
					'smonth' => '11',
					'syear'  => '2024',
				)
			)
		);

		$this->assert_date( 2024, 11, 5, $result );
	}

	/**
	 * Filters can alter shortcode-derived values when there are no GET parameters.
	 */
	public function test_filters_alter_shortcode_values() {
		add_filter(
			'mc_filter_year',
			function () {
				return 2040;
			}
		);
		add_filter(
			'mc_filter_month',
			function () {
				return 8;
			}
		);
		add_filter(
			'mc_filter_day',
			function () {
				return 21;
			}
		);

		$result = $this->date( $this->params( 'day' ) );

		$this->assert_date( 2040, 8, 21, $result );
	}

	/**
	 * Filters do not run when GET parameters supply the date.
	 */
	public function test_filters_do_not_override_get_parameters() {
		add_filter(
			'mc_filter_year',
			function () {
				return 2040;
			}
		);
		$_GET['yr']    = '2030';
		$_GET['month'] = '4';

		$result = $this->date( $this->params( 'month' ) );

		$this->assert_date( 2030, 4, 1, $result );
	}

	/**
	 * GET month shifts the month, using the current year and the first day.
	 */
	public function test_get_month_overrides_default() {
		$month         = $this->other_month();
		$_GET['month'] = (string) $month;

		$result = $this->date( $this->params( 'month' ) );

		$this->assert_date( (int) current_time( 'Y' ), $month, 1, $result );
	}

	/**
	 * GET year shifts the year, using the current month and the first day.
	 */
	public function test_get_year_overrides_default() {
		$_GET['yr'] = '2029';

		$result = $this->date( $this->params( 'month' ) );

		$this->assert_date( 2029, (int) current_time( 'n' ), 1, $result );
	}

	/**
	 * GET month and year together.
	 */
	public function test_get_month_and_year() {
		$_GET['month'] = '12';
		$_GET['yr']    = '2023';

		$result = $this->date( $this->params( 'month' ) );

		$this->assert_date( 2023, 12, 1, $result );
	}

	/**
	 * GET values replace shortcode values rather than combining with them.
	 */
	public function test_get_overrides_shortcode_values() {
		$_GET['month'] = '9';
		$_GET['yr']    = '2022';

		$result = $this->date(
			$this->params(
				'month',
				array(
					'smonth' => 2,
					'syear'  => 2035,
				)
			)
		);

		$this->assert_date( 2022, 9, 1, $result );
	}

	/**
	 * GET month in a day view resets the day to the first of the month.
	 */
	public function test_get_month_resets_day_in_day_view() {
		$_GET['month'] = '3';
		$_GET['yr']    = '2025';

		$result = $this->date( $this->params( 'day' ) );

		$this->assert_date( 2025, 3, 1, $result );
	}

	/**
	 * GET day, month, and year in day view.
	 */
	public function test_get_day_month_year_in_day_view() {
		$_GET['dy']    = '20';
		$_GET['month'] = '2';
		$_GET['yr']    = '2025';

		$result = $this->date( $this->params( 'day' ) );

		$this->assert_date( 2025, 2, 20, $result );
	}

	/**
	 * GET day without a month keeps the current month and year in day view.
	 */
	public function test_get_day_only_in_day_view() {
		$_GET['dy'] = '3';

		$result = $this->date( $this->params( 'day' ) );

		$this->assert_date( (int) current_time( 'Y' ), (int) current_time( 'n' ), 3, $result );
	}

	/**
	 * GET day, month, and year in week view.
	 */
	public function test_get_day_month_year_in_week_view() {
		$_GET['dy']    = '9';
		$_GET['month'] = '4';
		$_GET['yr']    = '2025';

		$result = $this->date( $this->params( 'week' ) );

		$this->assert_date( 2025, 4, 9, $result );
	}

	/**
	 * An empty GET day in week view resolves to the start of the week containing today's day number in the requested month.
	 */
	public function test_empty_get_day_in_week_view_uses_week_start() {
		$_GET['dy']    = '';
		$_GET['month'] = '6';
		$_GET['yr']    = '2024';

		$result   = $this->date( $this->params( 'week' ) );
		$expected = mc_first_day_of_week( strtotime( '2024-6-' . current_time( 'j' ) ) );

		$this->assert_date( 2024, 6, (int) $expected[0], $result );
	}

	/**
	 * GET day does not apply in month view; the day stays at the first.
	 */
	public function test_get_day_ignored_in_month_view() {
		$_GET['dy']    = '20';
		$_GET['month'] = '2';
		$_GET['yr']    = '2025';

		$result = $this->date( $this->params( 'month' ) );

		$this->assert_date( 2025, 2, 1, $result );
	}

	/**
	 * GET month and year are ignored for calendars other than the main calendar.
	 */
	public function test_get_ignored_for_other_calendars() {
		$_GET['month'] = (string) $this->other_month();
		$_GET['yr']    = '2019';

		$result = $this->date( $this->params( 'month' ), 'some-widget' );

		$this->assertSame( (int) current_time( 'Y' ), $result['year'], 'Year from GET should not apply.' );
		$this->assertSame( (int) current_time( 'n' ), $result['month'], 'Month from GET should not apply.' );
		$this->assertSame( 1, $result['day'] );
	}

	/**
	 * GET day is ignored for calendars other than the main calendar.
	 */
	public function test_get_day_ignored_for_other_calendars() {
		$_GET['dy'] = '17';

		$result = $this->date( $this->params( 'day' ), 'some-widget' );

		$this->assertSame( (int) current_time( 'j' ), $result['day'] );
	}

	/**
	 * A GET year beyond 3000 falls back to today.
	 */
	public function test_year_above_range_falls_back_to_today() {
		$_GET['yr']    = '3001';
		$_GET['month'] = '2';

		$result = $this->date( $this->params( 'month' ) );

		$this->assert_date( (int) current_time( 'Y' ), (int) current_time( 'n' ), (int) current_time( 'j' ), $result );
	}

	/**
	 * A negative GET year falls back to today.
	 */
	public function test_negative_year_falls_back_to_today() {
		$_GET['yr'] = '-5';

		$result = $this->date( $this->params( 'month' ) );

		$this->assert_date( (int) current_time( 'Y' ), (int) current_time( 'n' ), (int) current_time( 'j' ), $result );
	}

	/**
	 * The boundary year 3000 is accepted.
	 */
	public function test_year_3000_is_accepted() {
		$_GET['yr']    = '3000';
		$_GET['month'] = '1';

		$result = $this->date( $this->params( 'month' ) );

		$this->assert_date( 3000, 1, 1, $result );
	}

	/**
	 * Single-digit and zero-padded GET months produce the same date.
	 */
	public function test_zero_padded_month_matches_unpadded() {
		$_GET['yr']    = '2025';
		$_GET['month'] = '03';
		$padded        = $this->date( $this->params( 'month' ) );

		$_GET['month'] = '3';
		$plain         = $this->date( $this->params( 'month' ) );

		$this->assertSame( $plain, $padded );
		$this->assert_date( 2025, 3, 1, $padded );
	}

	/**
	 * A day of zero is invalid and ignored, so the first of the requested month is used.
	 */
	public function test_zero_day_becomes_first() {
		$_GET['dy']    = '0';
		$_GET['month'] = '5';
		$_GET['yr']    = '2025';

		$result = $this->date( $this->params( 'day' ) );

		$this->assert_date( 2025, 5, 1, $result );
	}

	/**
	 * Invalid GET months are ignored rather than rolling into another year.
	 *
	 * @dataProvider data_invalid_months
	 *
	 * @param string $month Invalid month value.
	 */
	public function test_invalid_get_month_is_ignored( $month ) {
		$_GET['month'] = $month;
		$_GET['yr']    = '2025';

		$result = $this->date( $this->params( 'month' ) );

		$this->assert_date( 2025, (int) current_time( 'n' ), 1, $result, "Month '$month'." );
	}

	/**
	 * Invalid month values.
	 *
	 * @return array
	 */
	public static function data_invalid_months() {
		return array(
			'zero'     => array( '0' ),
			'thirteen' => array( '13' ),
			'negative' => array( '-1' ),
			'text'     => array( 'march' ),
			'empty'    => array( '' ),
			'decimal'  => array( '2.5x' ),
		);
	}

	/**
	 * A non-numeric GET year is ignored.
	 */
	public function test_non_numeric_year_is_ignored() {
		$_GET['yr']    = 'abc';
		$_GET['month'] = '4';

		$result = $this->date( $this->params( 'month' ) );

		$this->assert_date( (int) current_time( 'Y' ), 4, 1, $result );
	}

	/**
	 * Invalid GET days are ignored in day view.
	 *
	 * @dataProvider data_invalid_days
	 *
	 * @param string $day Invalid day value.
	 */
	public function test_invalid_get_day_is_ignored( $day ) {
		$_GET['dy']    = $day;
		$_GET['month'] = '8';
		$_GET['yr']    = '2025';

		$result = $this->date( $this->params( 'day' ) );

		$this->assert_date( 2025, 8, 1, $result, "Day '$day'." );
	}

	/**
	 * Invalid day values.
	 *
	 * @return array
	 */
	public static function data_invalid_days() {
		return array(
			'zero'       => array( '0' ),
			'thirty-two' => array( '32' ),
			'negative'   => array( '-3' ),
			'text'       => array( 'tuesday' ),
		);
	}

	/**
	 * A day beyond the end of the requested month is limited to the last day.
	 */
	public function test_day_is_limited_to_month_length() {
		$_GET['dy']    = '31';
		$_GET['month'] = '2';
		$_GET['yr']    = '2025';

		$result = $this->date( $this->params( 'day' ) );

		$this->assert_date( 2025, 2, 28, $result );
	}

	/**
	 * February 29 is valid in leap years.
	 */
	public function test_leap_day_is_accepted() {
		$_GET['dy']    = '29';
		$_GET['month'] = '2';
		$_GET['yr']    = '2028';

		$result = $this->date( $this->params( 'day' ) );

		$this->assert_date( 2028, 2, 29, $result );
	}

	/**
	 * Invalid GET values leave shortcode values in effect.
	 */
	public function test_invalid_get_values_keep_shortcode_values() {
		$_GET['month'] = '13';
		$_GET['yr']    = 'abc';

		$result = $this->date(
			$this->params(
				'month',
				array(
					'smonth' => 7,
					'syear'  => 2027,
				)
			)
		);

		$this->assert_date( 2027, 7, 1, $result );
	}

	/**
	 * GET values for the main calendar don't remove shortcode values from other calendars.
	 */
	public function test_other_calendar_keeps_shortcode_values_when_get_present() {
		$_GET['month'] = '3';
		$_GET['yr']    = '2019';
		$_GET['dy']    = '9';

		$result = $this->date(
			$this->params(
				'day',
				array(
					'sday'   => 14,
					'smonth' => 2,
					'syear'  => 2026,
				)
			),
			'some-widget'
		);

		$this->assert_date( 2026, 2, 14, $result );
	}

	/**
	 * A GET day that doesn't apply to the time frame doesn't remove shortcode values.
	 */
	public function test_get_day_in_month_view_keeps_shortcode_values() {
		$_GET['dy'] = '20';

		$result = $this->date(
			$this->params(
				'month',
				array(
					'smonth' => 6,
					'syear'  => 2025,
				)
			)
		);

		$this->assert_date( 2025, 6, 1, $result );
	}
}
