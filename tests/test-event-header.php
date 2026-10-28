<?php
/**
 * Tests for mc_draw_event_header() link output.
 *
 * @package MyCalendar
 */

/**
 * Covers anchor rendering in event headers.
 */
class Tests_My_Calendar_Event_Header extends WP_UnitTestCase {
	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static $admin_id = 0;

	/**
	 * Original plugin options.
	 *
	 * @var array
	 */
	protected $original_options = array();

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
		mc_get_option( 'templates', '', true );
		remove_all_filters( 'mc_disable_link' );
		$_POST = array();

		parent::tear_down();
	}

	/**
	 * Create an event and build the data array expected by mc_draw_event_header().
	 *
	 * @param string $type View type.
	 *
	 * @return array
	 */
	protected function build_header_data( $type ) {
		$default_category = (int) mc_get_option( 'default_category', '', true );
		if ( ! $default_category || ! is_object( mc_get_category( $default_category ) ) ) {
			$default_category = (int) mc_no_category_default( true );
			mc_update_option( 'default_category', $default_category );
			mc_get_option( 'default_category', '', true );
		}

		$post  = array(
			'event_nonce_name' => wp_create_nonce( 'event_nonce' ),
			'event_title'      => 'Header Test Event',
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
		$event       = mc_get_event( $occurrence->occur_id );
		$date        = '2026-08-01';

		return array(
			'event'        => $event,
			'tags'         => mc_create_tags( $event, 'filters', false, $type, $date ),
			'id'           => $event->occur_id,
			'process_date' => $date,
			'time'         => 'month',
		);
	}

	/**
	 * Set options and the grid title template.
	 *
	 * @param array $options Option overrides.
	 * @param array $templates Template overrides.
	 */
	protected function set_options( $options = array(), $templates = array() ) {
		foreach ( $options as $key => $value ) {
			mc_update_option( $key, $value );
		}
		if ( $templates ) {
			mc_update_option( 'templates', array_merge( (array) mc_get_option( 'templates' ), $templates ) );
		}
		mc_get_option( 'templates', '', true );
	}

	/**
	 * Cards always link to the event.
	 */
	public function test_card_renders_link() {
		$data   = $this->build_header_data( 'card' );
		$header = mc_draw_event_header( $data, 'card', 'card' );

		$this->assertStringContainsString( "<a href='", $header );
		$this->assertStringContainsString( "class='url summary", $header );
		$this->assertStringContainsString( 'Header Test Event', $header );
	}

	/**
	 * Cards do not link when mc_disable_link returns true.
	 */
	public function test_card_omits_link_when_disabled() {
		$data = $this->build_header_data( 'card' );
		add_filter( 'mc_disable_link', '__return_true' );
		$header = mc_draw_event_header( $data, 'card', 'card' );

		$this->assertStringNotContainsString( '<a ', $header );
		$this->assertStringContainsString( 'Header Test Event', $header );
	}

	/**
	 * Lists link when list_link_titles is true.
	 */
	public function test_list_renders_link_when_list_link_titles_true() {
		$this->set_options(
			array(
				'list_link_titles' => 'true',
				'open_uri'         => 'true',
			),
			array( 'title_list' => '{title}' )
		);
		$data   = $this->build_header_data( 'list' );
		$header = mc_draw_event_header( $data, 'list', 'list' );

		$this->assertStringContainsString( "<a href='", $header );
		$this->assertStringContainsString( "class='url summary", $header );
	}

	/**
	 * Lists do not link when list_link_titles is not true.
	 */
	public function test_list_omits_link_when_list_link_titles_false() {
		$this->set_options(
			array(
				'list_link_titles' => 'false',
				'open_uri'         => 'true',
			),
			array( 'title_list' => '{title}' )
		);
		$data   = $this->build_header_data( 'list' );
		$header = mc_draw_event_header( $data, 'list', 'list' );

		$this->assertStringNotContainsString( '<a ', $header );
	}

	/**
	 * Lists do not link when mc_disable_link returns true.
	 */
	public function test_list_omits_link_when_disabled() {
		$this->set_options(
			array(
				'list_link_titles' => 'true',
				'open_uri'         => 'true',
			),
			array( 'title_list' => '{title}' )
		);
		$data = $this->build_header_data( 'list' );
		add_filter( 'mc_disable_link', '__return_true' );
		$header = mc_draw_event_header( $data, 'list', 'list' );

		$this->assertStringNotContainsString( '<a ', $header );
	}

	/**
	 * Grid view renders the link from a title template that contains one.
	 */
	public function test_grid_renders_template_link() {
		$this->set_options(
			array( 'open_uri' => 'true' ),
			array( 'title' => '<a href="https://example.com/custom">{title}</a>' )
		);
		$data   = $this->build_header_data( 'calendar' );
		$header = mc_draw_event_header( $data, 'calendar', 'calendar' );

		$this->assertStringContainsString( 'href="https://example.com/custom"', $header );
		// The template link is not wrapped in a second anchor.
		$this->assertSame( 1, substr_count( $header, '<a ' ) );
		$this->assertStringNotContainsString( "class='url summary", $header );
	}

	/**
	 * Grid view links titles by default when the template has no link.
	 */
	public function test_grid_wraps_title_when_template_has_no_link() {
		$this->set_options(
			array( 'open_uri' => 'true' ),
			array( 'title' => '{title}' )
		);
		$data   = $this->build_header_data( 'calendar' );
		$header = mc_draw_event_header( $data, 'calendar', 'calendar' );

		$this->assertStringContainsString( "class='url summary", $header );
	}

	/**
	 * Grid view wrapper link is removed by mc_disable_link.
	 */
	public function test_grid_omits_wrapper_link_when_disabled() {
		$this->set_options(
			array( 'open_uri' => 'true' ),
			array( 'title' => '{title}' )
		);
		$data = $this->build_header_data( 'calendar' );
		add_filter( 'mc_disable_link', '__return_true' );
		$header = mc_draw_event_header( $data, 'calendar', 'calendar' );

		$this->assertStringNotContainsString( '<a ', $header );
	}
}
