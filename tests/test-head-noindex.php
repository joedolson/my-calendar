<?php
/**
 * Tests for the noindex robots meta output by mc_head().
 *
 * @package MyCalendar
 */

/**
 * Covers noindex,follow on the primary calendar page.
 */
class Tests_My_Calendar_Head_Noindex extends WP_UnitTestCase {
	const META = '<meta name="robots" content="noindex,follow" />';

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static $admin_id = 0;

	/**
	 * Original plugin options.
	 *
	 * @var mixed
	 */
	protected $original_options;

	/**
	 * Ensure plugin data structures exist for tests.
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
	 * Reset state before each test.
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$admin_id );
		$this->original_options = get_option( 'my_calendar_options' );
		$_GET                   = array();
		$_POST                  = array();
		$_REQUEST               = array();
	}

	/**
	 * Restore state after each test.
	 */
	public function tear_down() {
		update_option( 'my_calendar_options', $this->original_options );
		mc_get_option( 'uri_id', '', true );
		$_GET  = array();
		$_POST = array();

		parent::tear_down();
	}

	/**
	 * Create a page and set it as the primary calendar page.
	 *
	 * @return int Page ID.
	 */
	protected function create_calendar_page() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '[my_calendar]',
			)
		);
		mc_update_option( 'uri_id', $page_id );
		mc_get_option( 'uri_id', '', true );

		return $page_id;
	}

	/**
	 * Create an event and return one of its occurrence IDs.
	 *
	 * @return int
	 */
	protected function create_occurrence_id() {
		$default_category = (int) mc_get_option( 'default_category', '', true );
		if ( ! $default_category || ! is_object( mc_get_category( $default_category ) ) ) {
			$default_category = (int) mc_no_category_default( true );
			mc_update_option( 'default_category', $default_category );
			mc_get_option( 'default_category', '', true );
		}

		$post  = array(
			'event_nonce_name' => wp_create_nonce( 'event_nonce' ),
			'event_title'      => 'Noindex Test Event',
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
		$_POST = $post;

		$mc_output = mc_check_data( 'add', $post, 0 );
		$this->assertTrue( $mc_output[0], $mc_output[3] );
		$response    = my_calendar_save( 'add', $mc_output );
		$occurrences = mc_get_occurrences( $response['event_id'] );
		$occurrence  = reset( $occurrences );

		return (int) $occurrence->occur_id;
	}

	/**
	 * Capture mc_head() output.
	 *
	 * @return string
	 */
	protected function get_head_output() {
		ob_start();
		mc_head();

		return ob_get_clean();
	}

	/**
	 * The primary calendar page gets noindex,follow.
	 */
	public function test_noindex_on_primary_calendar_page() {
		$page_id = $this->create_calendar_page();
		$this->go_to( get_permalink( $page_id ) );

		$this->assertTrue( is_page( $page_id ) );
		$this->assertStringContainsString( self::META, $this->get_head_output() );
	}

	/**
	 * The primary calendar page does not get noindex when it is the front page.
	 */
	public function test_no_noindex_when_calendar_page_is_front_page() {
		$page_id = $this->create_calendar_page();
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );
		$this->go_to( home_url( '/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertStringNotContainsString( self::META, $this->get_head_output() );
	}

	/**
	 * The primary calendar page does not get noindex when rendering a single event.
	 */
	public function test_no_noindex_when_single_event_in_primary_shortcode() {
		$occur_id = $this->create_occurrence_id();
		$page_id  = $this->create_calendar_page();
		$this->go_to( add_query_arg( 'mc_id', $occur_id, get_permalink( $page_id ) ) );
		$_GET['mc_id'] = (string) $occur_id;

		$this->assertTrue( mc_is_single_event() );
		$this->assertStringNotContainsString( self::META, $this->get_head_output() );
	}

	/**
	 * Pages other than the primary calendar page do not get noindex.
	 */
	public function test_no_noindex_on_other_pages() {
		$this->create_calendar_page();
		$other_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		$this->go_to( get_permalink( $other_id ) );

		$this->assertStringNotContainsString( self::META, $this->get_head_output() );
	}
}
