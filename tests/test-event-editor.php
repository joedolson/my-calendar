<?php
/**
 * Event editor save pipeline tests.
 *
 * @package MyCalendar
 */

/**
 * Covers event creation via the editor save helpers.
 */
class Tests_My_Calendar_Event_Editor extends WP_UnitTestCase {
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
	 * Reset user context before each test.
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$admin_id );
		$_GET  = array();
		$_POST = array();
	}

	/**
	 * Clean global request state.
	 */
	public function tear_down() {
		$_GET  = array();
		$_POST = array();

		parent::tear_down();
	}

	/**
	 * Verify a standard event save creates an event, event post, and category relationship.
	 */
	public function test_creates_single_event_from_editor_submission() {
		$post     = $this->build_event_post();
		$response = $this->create_event( $post );

		$this->assertIsInt( $response['event_id'] );
		$this->assertGreaterThan( 0, $response['event_id'] );
		$this->assertIsInt( $response['event_post'] );
		$this->assertGreaterThan( 0, $response['event_post'] );

		$event = mc_get_event_core( $response['event_id'], true );
		$this->assertSame( 'Test Event', $event->event_title );
		$this->assertSame( 'S1', $event->event_recur );
		$this->assertSame( 'https://example.com/event', $event->event_link );

		$occurrences = mc_get_occurrences( $response['event_id'] );
		$this->assertCount( 1, $occurrences );

		$this->assertSame( $response['event_id'], (int) get_post_meta( $response['event_post'], '_mc_event_id', true ) );
		$this->assertSame( 'mc-events', get_post_type( $response['event_post'] ) );

		$categories = mc_get_categories( $response['event_id'] );
		$this->assertNotEmpty( $categories );
	}

	/**
	 * Verify location data submitted during event creation creates a location and links it to the event.
	 */
	public function test_creates_location_while_creating_event() {
		$post = $this->build_event_post(
			array(
				'location_preset' => 'none',
				'event_label'     => 'Test Venue',
				'event_street'    => '123 Main Street',
				'event_city'      => 'Seattle',
				'event_state'     => 'WA',
				'event_postcode'  => '98101',
				'event_country'   => 'US',
				'event_phone'     => '206-555-0100',
			)
		);

		$before   = mc_count_locations();
		$response = $this->create_event( $post );
		$after    = mc_count_locations();

		$this->assertSame( $before + 1, $after );

		$event = mc_get_event_core( $response['event_id'], true );
		$this->assertGreaterThan( 0, (int) $event->event_location );

		$location = mc_get_location( $event->event_location );
		$this->assertSame( 'Test Venue', $location->location_label );
		$this->assertSame( 'Seattle', $location->location_city );
		$this->assertSame( 'WA', $location->location_state );
	}

	/**
	 * Verify empty location fields do not create a location record.
	 */
	public function test_does_not_create_location_when_location_fields_empty() {
		$post = $this->build_event_post(
			array(
				'location_preset' => 'none',
				'event_label'     => '',
				'event_street'    => '',
				'event_city'      => '',
				'event_state'     => '',
			)
		);

		$before   = mc_count_locations();
		$response = $this->create_event( $post );
		$after    = mc_count_locations();

		$this->assertSame( $before, $after );

		$event = mc_get_event_core( $response['event_id'], true );
		$this->assertSame( 0, (int) $event->event_location );
	}

	/**
	 * Verify creating multiple copied events with one new location only inserts one location.
	 */
	public function test_multi_copy_creation_creates_single_location() {
		$post = $this->build_event_post(
			array(
				'event_begin'     => array( '2026-08-01', '2026-08-02' ),
				'event_end'       => array( '2026-08-01', '2026-08-02' ),
				'event_time'      => array( '10:00', '11:00' ),
				'event_endtime'   => array( '12:00', '13:00' ),
				'event_group_id'  => '101',
				'location_preset' => 'none',
				'event_label'     => 'Shared Venue',
				'event_street'    => '456 Broad Street',
				'event_city'      => 'Portland',
				'event_state'     => 'OR',
			)
		);

		$before        = mc_count_locations();
		$first_response = $this->create_event( $post, 0 );
		$second_response = $this->create_event( $post, 1 );
		$after         = mc_count_locations();

		$this->assertSame( $before + 1, $after );

		$first_event  = mc_get_event_core( $first_response['event_id'], true );
		$second_event = mc_get_event_core( $second_response['event_id'], true );

		$this->assertGreaterThan( 0, (int) $first_event->event_location );
		$this->assertSame( (int) $first_event->event_location, (int) $second_event->event_location );
	}

	/**
	 * Verify category creation stores both a category row and a taxonomy term.
	 */
	public function test_creates_category_record_and_term() {
		$name        = 'Created Category ' . wp_generate_password( 8, false );
		$category_id = mc_create_category(
			array(
				'category_name'  => $name,
				'category_color' => '#123456',
				'category_icon'  => 'event.svg',
			)
		);

		$this->assertIsInt( $category_id );
		$this->assertGreaterThan( 0, $category_id );

		$category = mc_get_category( $category_id );
		$this->assertSame( $name, $category->category_name );
		$this->assertSame( '#123456', $category->category_color );

		$term = get_term( $category->category_term, 'mc-event-category' );
		$this->assertNotWPError( $term );
		$this->assertInstanceOf( 'WP_Term', $term );
		$this->assertSame( $name, $term->name );
	}

	/**
	 * Verify an event can be created using a newly created category.
	 */
	public function test_creates_event_using_new_category() {
		$name        = 'Attached Category ' . wp_generate_password( 8, false );
		$category_id = mc_create_category(
			array(
				'category_name'  => $name,
				'category_color' => '#654321',
				'category_icon'  => 'event.svg',
			)
		);

		$post = $this->build_event_post(
			array(
				'event_category'   => array( $category_id ),
				'primary_category' => $category_id,
			)
		);

		$response   = $this->create_event( $post );
		$categories = mc_get_categories( $response['event_id'] );

		$this->assertSame( $category_id, mc_get_data( 'event_category', $response['event_id'] ) );
		$this->assertContains( $category_id, array_map( 'intval', $categories ) );
	}

	/**
	 * Verify recurring creation supports multiple recurrence profiles.
	 *
	 * @dataProvider recurring_event_provider
	 *
	 * @param string $recur Expected recurrence code.
	 * @param string $every Frequency value sent by the editor.
	 * @param string $repeats Repeat-until value.
	 * @param int    $expected_occurrences Expected created occurrence count.
	 */
	public function test_creates_recurring_events_for_supported_options( $recur, $every, $repeats, $expected_occurrences ) {
		$post = $this->build_event_post(
			array(
				'event_recur'   => $recur,
				'event_every'   => $every,
				'event_repeats' => $repeats,
			)
		);

		$response = $this->create_event( $post );
		$event    = mc_get_event_core( $response['event_id'], true );

		$this->assertSame( $recur . $every, $event->event_recur );
		$this->assertSame( $repeats, $event->event_repeats );
		$this->assertCount( $expected_occurrences, mc_get_occurrences( $response['event_id'] ) );
	}

	/**
	 * Provide recurrence cases for event creation coverage.
	 *
	 * @return array<string,array<string|int>>
	 */
	public function recurring_event_provider() {
		return array(
			'daily'           => array( 'D', '1', '2026-08-03', 3 ),
			'weekdays-only'   => array( 'E', '1', '2026-08-05', 3 ),
			'weekly'          => array( 'W', '1', '2026-08-15', 3 ),
			'monthly-by-date' => array( 'M', '1', '2026-10-01', 3 ),
			'yearly'          => array( 'Y', '1', '2028-08-01', 3 ),
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
		$default_category = mc_get_option( 'default_category' );
		if ( ! $default_category ) {
			$default_category = mc_no_category_default( true );
		}

		$post = array(
			'event_nonce_name' => wp_create_nonce( 'event_nonce' ),
			'event_title'      => 'Test Event',
			'content'          => 'Event description',
			'event_short'      => 'Short description',
			'event_begin'      => array( '2026-08-01' ),
			'event_end'        => array( '2026-08-01' ),
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
