<?php
/**
 * Tests for the my_calendar_now, my_calendar_next, and my_calendar_today shortcodes.
 *
 * @package MyCalendar
 */

/**
 * Verifies output of the happening now, next event, and today's events shortcodes.
 */
class Tests_My_Calendar_Now_Next_Today_Shortcodes extends WP_UnitTestCase {
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
	 * [my_calendar_now] shows an event that is currently happening, wrapped in the list container.
	 */
	public function test_now_shortcode_outputs_current_event() {
		$this->create_spanning_event( 'Happening Now Event' );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_now template="<b>{title}</b>"]' );

		$this->assertStringStartsWith( '<div class="mc-event-list">', $output );
		$this->assertStringContainsString( '<b>Happening Now Event</b>', $output );
	}

	/**
	 * [my_calendar_now] ignores events that are not in progress.
	 */
	public function test_now_shortcode_ignores_future_event() {
		$this->create_event_on( 'Future Event', gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +5 days' ) ) );
		wp_set_current_user( 0 );

		$this->assertSame( '', do_shortcode( '[my_calendar_now]' ) );
	}

	/**
	 * [my_calendar_now] does not show draft events to visitors.
	 */
	public function test_now_shortcode_excludes_draft_event() {
		$this->create_spanning_event( 'Draft Now Event', array( 'event_approved' => '0' ) );
		wp_set_current_user( 0 );

		$this->assertSame( '', do_shortcode( '[my_calendar_now]' ) );
	}

	/**
	 * [my_calendar_now] returns only the first event when several are in progress.
	 */
	public function test_now_shortcode_returns_single_event() {
		$this->create_spanning_event( 'Alpha Now Event' );
		$this->create_spanning_event( 'Beta Now Event' );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_now template="{title}"]' );

		$this->assertSame( 1, substr_count( $output, 'Now Event' ) );
	}

	/**
	 * [my_calendar_next] shows the nearest upcoming event and skips later ones.
	 */
	public function test_next_shortcode_outputs_nearest_upcoming_event() {
		$today = current_time( 'Y-m-d' );
		$this->create_event_on( 'Later Event', gmdate( 'Y-m-d', strtotime( $today . ' +10 days' ) ) );
		$this->create_event_on( 'Sooner Event', gmdate( 'Y-m-d', strtotime( $today . ' +3 days' ) ) );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_next template="<b>{title}</b>"]' );

		$this->assertStringStartsWith( '<div class="mc-event-list">', $output );
		$this->assertStringContainsString( '<b>Sooner Event</b>', $output );
		$this->assertStringNotContainsString( 'Later Event', $output );
	}

	/**
	 * [my_calendar_next skip] moves to the following event.
	 */
	public function test_next_shortcode_skip_attribute() {
		$today = current_time( 'Y-m-d' );
		$this->create_event_on( 'Later Event', gmdate( 'Y-m-d', strtotime( $today . ' +10 days' ) ) );
		$this->create_event_on( 'Sooner Event', gmdate( 'Y-m-d', strtotime( $today . ' +3 days' ) ) );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_next skip="1" template="{title}"]' );

		$this->assertStringContainsString( 'Later Event', $output );
		$this->assertStringNotContainsString( 'Sooner Event', $output );
	}

	/**
	 * [my_calendar_next] is empty with no upcoming events, and excludes past events.
	 */
	public function test_next_shortcode_empty_without_upcoming_events() {
		$this->create_event_on( 'Past Event', gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' -5 days' ) ) );
		wp_set_current_user( 0 );

		$this->assertSame( '', do_shortcode( '[my_calendar_next]' ) );
	}

	/**
	 * [my_calendar_next] does not show draft events to visitors.
	 */
	public function test_next_shortcode_excludes_draft_event() {
		$this->create_event_on( 'Draft Next Event', gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +3 days' ) ), array( 'event_approved' => '0' ) );
		wp_set_current_user( 0 );

		$this->assertSame( '', do_shortcode( '[my_calendar_next]' ) );
	}

	/**
	 * [my_calendar_today] lists today's events in a list container.
	 */
	public function test_today_shortcode_outputs_todays_events() {
		$today = current_time( 'Y-m-d' );
		$this->create_event_on( 'Today Event', $today );
		$this->create_event_on( 'Tomorrow Event', gmdate( 'Y-m-d', strtotime( $today . ' +1 day' ) ) );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_today template="<b>{title}</b>" author="all" host="all"]' );

		$this->assertStringContainsString( "class='mc-event-list todays-events list-events'", $output );
		$this->assertStringContainsString( '<b>Today Event</b>', $output );
		$this->assertStringNotContainsString( 'Tomorrow Event', $output );
	}

	/**
	 * [my_calendar_today] shows the fallback text when nothing is scheduled.
	 */
	public function test_today_shortcode_outputs_fallback_when_empty() {
		$this->create_event_on( 'Tomorrow Event', gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +1 day' ) ) );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_today fallback="Nothing scheduled" template="{title}" author="all" host="all"]' );

		$this->assertStringContainsString( 'Nothing scheduled', $output );
		$this->assertStringNotContainsString( 'Tomorrow Event', $output );
	}

	/**
	 * [my_calendar_today date] shows events for a specific date.
	 */
	public function test_today_shortcode_date_attribute() {
		$date = gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +2 days' ) );
		$this->create_event_on( 'Dated Event', $date );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_today date="' . $date . '" template="{title}" author="all" host="all"]' );

		$this->assertStringContainsString( 'Dated Event', $output );
	}

	/**
	 * [my_calendar_today] does not show draft events to visitors.
	 */
	public function test_today_shortcode_excludes_draft_event() {
		$this->create_event_on( 'Draft Today Event', current_time( 'Y-m-d' ), array( 'event_approved' => '0' ) );
		wp_set_current_user( 0 );

		$output = do_shortcode( '[my_calendar_today fallback="Nothing scheduled" template="{title}" author="all" host="all"]' );

		$this->assertStringNotContainsString( 'Draft Today Event', $output );
		$this->assertStringContainsString( 'Nothing scheduled', $output );
	}

	/**
	 * Create a single-day event.
	 *
	 * @param string $title Event title.
	 * @param string $date Date in Y-m-d format.
	 * @param array  $overrides Post overrides.
	 *
	 * @return array
	 */
	protected function create_event_on( $title, $date, $overrides = array() ) {
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
	 * Create an event spanning yesterday through tomorrow, so it is in progress at any time today.
	 *
	 * @param string $title Event title.
	 * @param array  $overrides Post overrides.
	 *
	 * @return array
	 */
	protected function create_spanning_event( $title, $overrides = array() ) {
		$today = current_time( 'Y-m-d' );

		return $this->create_event(
			$this->build_event_post(
				array_replace(
					array(
						'event_title'   => $title,
						'event_begin'   => array( gmdate( 'Y-m-d', strtotime( $today . ' -1 day' ) ) ),
						'event_end'     => array( gmdate( 'Y-m-d', strtotime( $today . ' +1 day' ) ) ),
						'event_time'    => array( '00:00' ),
						'event_endtime' => array( '23:59' ),
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
			'event_title'      => 'Shortcode Test Event',
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
