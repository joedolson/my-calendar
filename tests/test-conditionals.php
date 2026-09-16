<?php
/**
 * Conditional helper tests.
 *
 * @package MyCalendar
 */

/**
 * Covers boolean helpers in includes/conditionals.php.
 */
class Tests_My_Calendar_Conditionals extends WP_UnitTestCase {
	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static $admin_id = 0;

	/**
	 * Set up plugin data and an authenticated test user.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		self::$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( self::$admin_id );
		mc_posttypes();
		mc_taxonomies();

		if ( ! get_option( 'my_calendar_options' ) || ! my_calendar_exists() ) {
			mc_initial_install();
		}
	}

	/**
	 * Reset request and user state before each test.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::$admin_id );
		$_GET  = array();
		$_POST = array();
	}

	/**
	 * Reset request state after each test.
	 */
	public function tear_down() {
		$_GET  = array();
		$_POST = array();
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Verify recurring events are identified from their recurrence code and ID.
	 */
	public function test_is_recurring_accepts_event_objects_and_ids() {
		$this->assertFalse( mc_is_recurring( (object) array( 'event_recur' => 'S' ) ) );
		$this->assertFalse( mc_is_recurring( (object) array( 'event_recur' => 'S1' ) ) );
		$this->assertTrue( mc_is_recurring( (object) array( 'event_recur' => 'W' ) ) );
		$this->assertFalse( mc_is_recurring( 999999 ) );
	}

	/**
	 * Verify all-day detection requires both boundary times.
	 */
	public function test_is_all_day_requires_full_day_times() {
		$all_day = (object) array(
			'event_time'    => '00:00:00',
			'event_endtime' => '23:59:59',
		);
		$timed   = (object) array(
			'event_time'    => '10:00:00',
			'event_endtime' => '23:59:59',
		);
		$this->assertTrue( mc_is_all_day( $all_day ) );
		$this->assertFalse( mc_is_all_day( $timed ) );
	}

	/**
	 * Verify custom icons are false when no custom icon directory is available.
	 */
	public function test_is_custom_icon_is_false_without_custom_icons() {
		delete_transient( 'mc_custom_icons' );
		$this->assertFalse( mc_is_custom_icon() );
	}

	/**
	 * Verify mobile detection uses the filterable default.
	 */
	public function test_is_mobile_can_be_filtered() {
		$this->assertFalse( mc_is_mobile() );
		add_filter( 'mc_is_mobile', '__return_true' );
		$this->assertTrue( mc_is_mobile() );
		remove_filter( 'mc_is_mobile', '__return_true' );
	}

	/**
	 * Verify tablet detection is false by default and filterable.
	 */
	public function test_is_tablet_can_be_filtered() {
		$this->assertFalse( mc_is_tablet() );
		add_filter( 'mc_is_tablet', '__return_true' );
		$this->assertTrue( mc_is_tablet() );
		remove_filter( 'mc_is_tablet', '__return_true' );
	}

	/**
	 * Verify preview mode requires the query values, capability, and nonce.
	 */
	public function test_is_preview_requires_valid_request() {
		$this->assertFalse( mc_is_preview() );
		$_GET = array(
			'mc_id'          => '1',
			'preview'        => 'true',
			'mcpreviewnonce' => wp_create_nonce( 'mcpreviewnonce' ),
		);
		$this->assertTrue( mc_is_preview() );
		$_GET['mcpreviewnonce'] = 'invalid';
		$this->assertFalse( mc_is_preview() );
	}

	/**
	 * Verify category matching works with a real saved event and both category forms.
	 */
	public function test_has_category_matches_category_id_and_name() {
		$event_id = $this->create_event();
		$event    = mc_get_event_core( $event_id, true );
		$category = mc_get_category( $event->event_category );

		$this->assertTrue( mc_has_category( $event_id, (int) $event->event_category ) );
		$this->assertTrue( mc_has_category( $event_id, $category->category_name ) );
		$this->assertFalse( mc_has_category( $event_id, 'Missing category' ) );
	}

	/**
	 * Verify iframe mode requires both query arguments.
	 */
	public function test_is_iframe_requires_event_context() {
		$this->assertFalse( mc_is_iframe() );
		$_GET = array( 'iframe' => 'true' );
		$this->assertFalse( mc_is_iframe() );
		$_GET['mc_id'] = '1';
		$this->assertTrue( mc_is_iframe() );
	}

	/**
	 * Verify tag view requires the query flag and add-event capability.
	 */
	public function test_is_tag_view_requires_capability() {
		$_GET = array( 'showtags' => 'true' );
		$this->assertTrue( mc_is_tag_view() );
		wp_set_current_user( 0 );
		$this->assertFalse( mc_is_tag_view() );
	}

	/**
	 * Verify custom styles use the documented filename prefix.
	 */
	public function test_is_custom_style_checks_filename_prefix() {
		$this->assertTrue( mc_is_custom_style( 'mc_custom_blue.css' ) );
		$this->assertFalse( mc_is_custom_style( 'my-calendar.css' ) );
	}

	/**
	 * Verify the supported core template keys.
	 *
	 * @dataProvider core_template_provider
	 *
	 * @param string $key Template key.
	 * @param bool   $expected Expected result.
	 */
	public function test_is_core_template( $key, $expected ) {
		$this->assertSame( $expected, mc_is_core_template( $key ) );
	}

	/**
	 * Verify singular event detection from a valid event query parameter.
	 */
	public function test_is_single_event_accepts_valid_event_id() {
		$this->assertFalse( mc_is_single_event() );
		$event_id    = $this->create_event();
		$occurrences = mc_get_occurrences( $event_id );
		$this->assertNotEmpty( $occurrences );
		$_GET['mc_id'] = (string) $occurrences[0]->occur_id;
		$this->assertTrue( mc_is_single_event() );
		$_GET['mc_id'] = 'invalid';
		$this->assertFalse( mc_is_single_event() );
	}

	/**
	 * Verify public and private event publication rules.
	 */
	public function test_event_published_respects_event_state_and_login() {
		wp_set_current_user( 0 );
		$this->assertTrue( mc_event_published( (object) array( 'event_approved' => 1 ) ) );
		$this->assertFalse( mc_event_published( (object) array( 'event_approved' => 0 ) ) );
		$this->assertFalse( mc_event_published( (object) array( 'event_approved' => 4 ) ) );
		wp_set_current_user( self::$admin_id );
		$this->assertTrue( mc_event_published( (object) array( 'event_approved' => 4 ) ) );
	}

	/**
	 * Verify hidden events are hidden for visitors and private events for visitors.
	 */
	public function test_event_is_hidden_for_unpublished_events_and_private_categories() {
		wp_set_current_user( 0 );
		$draft   = (object) array(
			'event_id'       => 1,
			'event_approved' => 0,
			'event_category' => 0,
		);
		$private = (object) array(
			'event_id'       => 1,
			'event_approved' => 4,
			'event_category' => 0,
		);
		$public  = (object) array(
			'event_id'       => 1,
			'event_approved' => 1,
			'event_category' => 0,
		);
		$this->assertTrue( mc_event_is_hidden( $draft ) );
		$this->assertTrue( mc_event_is_hidden( $private ) );
		$this->assertFalse( mc_event_is_hidden( $public ) );
	}

	/**
	 * Verify output visibility reflects configured fields and its filter.
	 */
	public function test_output_is_visible_uses_configuration_and_filter() {
		$this->assertIsBool( mc_output_is_visible( 'title', 'main' ) );
		add_filter( 'mc_output_is_visible', '__return_true' );
		$this->assertTrue( mc_output_is_visible( 'not-configured', 'invalid' ) );
		remove_filter( 'mc_output_is_visible', '__return_true' );
	}

	/**
	 * Provide core and non-core template keys.
	 *
	 * @return array
	 */
	public function core_template_provider() {
		return array(
			'grid'    => array( 'grid', true ),
			'details' => array( 'details', true ),
			'list'    => array( 'list', true ),
			'mini'    => array( 'mini', true ),
			'card'    => array( 'card', true ),
			'custom'  => array( 'custom', false ),
		);
	}

	/**
	 * Create a simple published event for conditional tests.
	 *
	 * @return int Event ID.
	 */
	protected function create_event() {
		$post    = array(
			'event_nonce_name' => wp_create_nonce( 'event_nonce' ),
			'event_title'      => 'Conditional Test Event',
			'content'          => 'Conditional test description',
			'event_short'      => 'Conditional test excerpt',
			'event_begin'      => array( '2026-08-01' ),
			'event_end'        => array( '2026-08-01' ),
			'event_time'       => array( '10:00' ),
			'event_endtime'    => array( '12:00' ),
			'event_every'      => '1',
			'event_recur'      => 'S1',
			'event_repeats'    => '0',
			'event_category'   => array( (int) mc_get_option( 'default_category', '', true ) ),
			'event_author'     => self::$admin_id,
			'event_host'       => self::$admin_id,
			'event_group_id'   => '0',
			'location_preset'  => 'none',
			'event_approved'   => '1',
		);
		$checked = mc_check_data( 'add', $post, 0 );
		$this->assertTrue( $checked[0], $checked[3] );
		$response = my_calendar_save( 'add', $checked );

		return $response['event_id'];
	}
}
