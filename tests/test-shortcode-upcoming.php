<?php
/**
 * Tests for the my_calendar_upcoming shortcode.
 *
 * @package MyCalendar
 */

/**
 * Verifies date-based (type="days"/"custom") and event-based (type="event") upcoming output.
 */
class Tests_My_Calendar_Upcoming_Shortcode extends WP_UnitTestCase {
	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static $admin_id = 0;

	/**
	 * Ensure plugin data structures exist for tests.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		self::$admin_id = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);

		wp_set_current_user( self::$admin_id );
		mc_posttypes();
		mc_taxonomies();

		if ( ! get_option( 'my_calendar_options' ) || ! my_calendar_exists() ) {
			mc_initial_install();
		}
	}

	/**
	 * Start each test with no events and a logged-out visitor context.
	 */
	public function set_up() {
		parent::set_up();

		global $wpdb;
		$wpdb->query( 'DELETE FROM ' . my_calendar_event_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( 'DELETE FROM ' . my_calendar_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		wp_set_current_user( self::$admin_id );
		$_GET  = array();
		$_POST = array();
	}

	/**
	 * Clean request globals after each test.
	 */
	public function tear_down() {
		$_GET  = array();
		$_POST = array();

		parent::tear_down();
	}

	/**
	 * Date-based lists include events inside the day range and exclude those outside it.
	 */
	public function test_days_type_includes_only_events_in_range() {
		$this->create_event_in_days( 'Inside Range Event', 3 );
		$this->create_event_in_days( 'Outside Range Event', 30 );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_upcoming type="days" before="0" after="7" template="<b>{title}</b>"]' );

		$this->assertStringContainsString( "class='mc-event-list upcoming-events list-events'", $output );
		$this->assertStringContainsString( 'Inside Range Event', $output );
		$this->assertStringNotContainsString( 'Outside Range Event', $output );
	}

	/**
	 * Date-based lists with a custom from/to range respect those boundaries.
	 */
	public function test_custom_type_uses_from_and_to() {
		$this->create_event_in_days( 'Custom Inside Event', 5 );
		$this->create_event_in_days( 'Custom Outside Event', 20 );
		wp_set_current_user( 0 );

		$from   = gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +4 days' ) );
		$to     = gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +6 days' ) );
		$output = do_shortcode( '[my_calendar_upcoming type="custom" from="' . $from . '" to="' . $to . '" template="{title}"]' );

		$this->assertStringContainsString( 'Custom Inside Event', $output );
		$this->assertStringNotContainsString( 'Custom Outside Event', $output );
	}

	/**
	 * Date-based lists honor the order attribute.
	 */
	public function test_days_type_order_attribute() {
		$this->create_event_in_days( 'Sooner Event', 2 );
		$this->create_event_in_days( 'Later Event', 5 );
		wp_set_current_user( 0 );

		$asc  = do_shortcode( '[my_calendar_upcoming type="days" before="0" after="10" order="asc" template="{title}"]' );
		$desc = do_shortcode( '[my_calendar_upcoming type="days" before="0" after="10" order="desc" template="{title}"]' );

		$this->assertLessThan( strpos( $asc, 'Later Event' ), strpos( $asc, 'Sooner Event' ) );
		$this->assertLessThan( strpos( $desc, 'Sooner Event' ), strpos( $desc, 'Later Event' ) );
	}

	/**
	 * Date-based lists show fallback text with the no-events class when empty.
	 */
	public function test_days_type_outputs_fallback_when_empty() {
		$this->create_event_in_days( 'Far Away Event', 60 );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_upcoming type="days" before="0" after="5" fallback="Nothing upcoming" template="{title}"]' );

		$this->assertStringContainsString( 'no-events-fallback', $output );
		$this->assertStringContainsString( 'Nothing upcoming', $output );
		$this->assertStringNotContainsString( 'Far Away Event', $output );
	}

	/**
	 * Date-based lists never show draft events to visitors.
	 */
	public function test_days_type_excludes_draft_event() {
		$this->create_event_in_days( 'Draft Days Event', 2, array( 'event_approved' => '0' ) );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_upcoming type="days" before="0" after="7" fallback="Nothing upcoming" template="{title}"]' );

		$this->assertStringNotContainsString( 'Draft Days Event', $output );
		$this->assertStringContainsString( 'Nothing upcoming', $output );
	}

	/**
	 * Event-based lists show the nearest events first and omit those beyond the count.
	 */
	public function test_event_type_limits_number_of_events() {
		$this->create_event_in_days( 'Event One', 1 );
		$this->create_event_in_days( 'Event Two', 2 );
		$this->create_event_in_days( 'Event Three', 3 );
		$this->create_event_in_days( 'Event Four', 4 );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_upcoming type="event" before="0" after="1" template="{title}"]' );

		$this->assertStringContainsString( "class='mc-event-list upcoming-events list-events'", $output );
		$this->assertStringContainsString( 'Event One', $output );
		$this->assertStringNotContainsString( 'Event Four', $output );
	}

	/**
	 * Event-based lists are not bounded by a date range.
	 */
	public function test_event_type_ignores_distance_in_days() {
		$this->create_event_in_days( 'Distant Event', 90 );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_upcoming type="event" before="0" after="3" template="{title}"]' );

		$this->assertStringContainsString( 'Distant Event', $output );
	}

	/**
	 * Event-based lists skip the requested number of events.
	 */
	public function test_event_type_skip_attribute() {
		$this->create_event_in_days( 'Skipped Event', 1 );
		$this->create_event_in_days( 'Shown Event', 2 );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_upcoming type="event" before="0" after="3" skip="1" template="{title}"]' );

		$this->assertStringContainsString( 'Shown Event', $output );
		$this->assertStringNotContainsString( 'Skipped Event', $output );
	}

	/**
	 * Event-based lists exclude past events.
	 */
	public function test_event_type_excludes_past_events() {
		$this->create_event_in_days( 'Past Event', -5 );
		$this->create_event_in_days( 'Coming Event', 5 );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_upcoming type="event" before="0" after="3" template="{title}"]' );

		$this->assertStringContainsString( 'Coming Event', $output );
		$this->assertStringNotContainsString( 'Past Event', $output );
	}

	/**
	 * Event-based lists show fallback text with the no-events class when empty.
	 */
	public function test_event_type_outputs_fallback_when_empty() {
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_upcoming type="event" fallback="No events soon" template="{title}"]' );

		$this->assertStringContainsString( 'no-events-fallback', $output );
		$this->assertStringContainsString( 'No events soon', $output );
	}

	/**
	 * Event-based lists never show draft events to visitors.
	 */
	public function test_event_type_excludes_draft_event() {
		$this->create_event_in_days( 'Draft Event Type Event', 2, array( 'event_approved' => '0' ) );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_upcoming type="event" before="0" after="3" fallback="No events soon" template="{title}"]' );

		$this->assertStringNotContainsString( 'Draft Event Type Event', $output );
		$this->assertStringContainsString( 'No events soon', $output );
	}

	/**
	 * Create a single-day event offset from today.
	 *
	 * @param string $title Event title.
	 * @param int    $days Days from today; negative for past.
	 * @param array  $overrides Post overrides.
	 *
	 * @return array
	 */
	protected function create_event_in_days( $title, $days, $overrides = array() ) {
		$date = gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' ' . ( $days >= 0 ? '+' : '-' ) . abs( $days ) . ' days' ) );

		return $this->create_event(
			$this->build_event_post(
				array_replace(
					array(
						'event_title' => $title,
						'event_begin' => array( $date ),
						'event_end'   => array( $date ),
					),
					$overrides
				)
			)
		);
	}

	/**
	 * Build a valid event editor post array.
	 *
	 * @param array $overrides Values to override in the default payload.
	 *
	 * @return array
	 */
	protected function build_event_post( $overrides = array() ) {
		$default_category = (int) mc_get_option( 'default_category', '', true );
		$default_exists   = ( $default_category > 0 ) ? mc_get_category( $default_category ) : false;
		if ( ! $default_category || ! is_object( $default_exists ) ) {
			$default_category = (int) mc_no_category_default( true );
			mc_update_option( 'default_category', $default_category );
			mc_get_option( 'default_category', '', true );
		}

		$post = array(
			'event_nonce_name' => wp_create_nonce( 'event_nonce' ),
			'event_title'      => 'Upcoming Shortcode Test Event',
			'content'          => 'Event description',
			'event_short'      => 'Short description',
			'event_begin'      => array( current_time( 'Y-m-d' ) ),
			'event_end'        => array( current_time( 'Y-m-d' ) ),
			'event_time'       => array( '10:00' ),
			'event_endtime'    => array( '12:00' ),
			'event_every'      => '1',
			'event_recur'      => 'S',
			'event_repeats'    => '0',
			'event_category'   => array( $default_category ),
			'primary_category' => $default_category,
			'event_link'       => 'https://example.com/event',
			'event_author'     => self::$admin_id,
			'event_host'       => self::$admin_id,
			'event_group_id'   => '0',
			'location_preset'  => 'none',
			'event_approved'   => '1',
		);

		return array_replace( $post, $overrides );
	}

	/**
	 * Drive event creation through the checked save pipeline.
	 *
	 * @param array $post Editor submission payload.
	 * @param int   $index Event index from repeatable arrays.
	 *
	 * @return array
	 */
	protected function create_event( $post, $index = 0 ) {
		$_POST     = $post;
		$mc_output = mc_check_data( 'add', $post, $index );

		$this->assertIsArray( $mc_output );
		$this->assertNotEmpty( $mc_output );
		$this->assertTrue( $mc_output[0], $mc_output[3] );

		return my_calendar_save( 'add', $mc_output );
	}
}
