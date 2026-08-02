<?php
/**
 * Frontend visibility tests for event statuses.
 *
 * @package MyCalendar
 */

/**
 * Confirms non-public event statuses are excluded from frontend output.
 */
class Tests_My_Calendar_Frontend_Event_Visibility extends WP_UnitTestCase {
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
	 * Reset user and request globals before each test.
	 */
	public function set_up() {
		parent::set_up();

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
	 * Draft and trash events should not render in singular event output.
	 *
	 * @dataProvider hidden_status_provider
	 *
	 * @param int $status Event approved status.
	 */
	public function test_hidden_status_never_renders_in_single_event_output( $status ) {
		$date            = current_time( 'Y-m-d' );
		$hidden_title    = 'Hidden Single Status ' . $status;
		$published_title = 'Published Single Control ' . $status;

		$hidden    = $this->create_event_with_status( $hidden_title, $status, $date );
		$published = $this->create_event_with_status( $published_title, 1, $date );

		wp_set_current_user( 0 );

		$hidden_html    = mc_get_event( $hidden['occur_id'], 'html' );
		$published_html = mc_get_event( $published['occur_id'], 'html' );

		$this->assertStringContainsString( $published_title, $published_html );
		$this->assertStringNotContainsString( $hidden_title, $hidden_html );
	}

	/**
	 * Draft and trash events should not render in [my_calendar] output.
	 *
	 * @dataProvider hidden_status_provider
	 *
	 * @param int $status Event approved status.
	 */
	public function test_hidden_status_never_renders_in_my_calendar_shortcode( $status ) {
		$date            = current_time( 'Y-m-d' );
		$year            = (int) gmdate( 'Y', strtotime( $date ) );
		$month           = (int) gmdate( 'n', strtotime( $date ) );
		$day             = (int) gmdate( 'j', strtotime( $date ) );
		$hidden_title    = 'Hidden Shortcode Status ' . $status;
		$published_title = 'Published Shortcode Control ' . $status;

		$this->create_event_with_status( $hidden_title, $status, $date );
		$this->create_event_with_status( $published_title, 1, $date );

		wp_set_current_user( 0 );

		$output = my_calendar_insert(
			array(
				'format' => 'list',
				'time'   => 'day',
				'year'   => $year,
				'month'  => $month,
				'day'    => $day,
				'id'     => 'status-visibility-test',
			)
		);

		$this->assertStringContainsString( $published_title, $output );
		$this->assertStringNotContainsString( $hidden_title, $output );
	}

	/**
	 * Draft and trash events should not render in events widget outputs.
	 *
	 * @dataProvider hidden_status_provider
	 *
	 * @param int $status Event approved status.
	 */
	public function test_hidden_status_never_renders_in_events_widgets( $status ) {
		$date            = current_time( 'Y-m-d' );
		$hidden_title    = 'Hidden Widget Status ' . $status;
		$published_title = 'Published Widget Control ' . $status;

		$this->create_event_with_status( $hidden_title, $status, $date );
		$this->create_event_with_status( $published_title, 1, $date );

		wp_set_current_user( 0 );

		$todays_output = my_calendar_todays_events(
			array(
				'category' => '',
				'template' => '<strong>{title}</strong>',
				'fallback' => 'No events',
				'author'   => 'all',
				'host'     => 'all',
				'date'     => $date,
			)
		);

		$upcoming_output = my_calendar_upcoming_events(
			array(
				'before'         => 0,
				'after'          => 0,
				'type'           => 'days',
				'category'       => '',
				'template'       => '<strong>{title}</strong>',
				'fallback'       => 'No events',
				'order'          => 'asc',
				'skip'           => 0,
				'show_recurring' => 'yes',
				'author'         => 'all',
				'host'           => 'all',
				'from'           => $date,
				'to'             => $date,
				'ltype'          => '',
				'lvalue'         => '',
			),
			array()
		);

		$this->assertStringContainsString( $published_title, $todays_output );
		$this->assertStringNotContainsString( $hidden_title, $todays_output );
		$this->assertStringContainsString( $published_title, $upcoming_output );
		$this->assertStringNotContainsString( $hidden_title, $upcoming_output );
	}

	/**
	 * Private events should only be visible to logged-in users.
	 */
	public function test_private_events_visible_only_to_logged_in_users() {
		$date         = current_time( 'Y-m-d' );
		$private_title = 'Private Event Auth Visibility';
		$private      = $this->create_event_with_status( $private_title, 4, $date );

		wp_set_current_user( 0 );
		$anonymous_views = $this->get_frontend_views_for_date( $date, $private['occur_id'] );
		$this->assertStringNotContainsString( $private_title, $anonymous_views['single'] );
		$this->assertStringNotContainsString( $private_title, $anonymous_views['shortcode'] );
		$this->assertStringNotContainsString( $private_title, $anonymous_views['today_widget'] );
		$this->assertStringNotContainsString( $private_title, $anonymous_views['upcoming_widget'] );

		$subscriber_id = self::factory()->user->create(
			array(
				'role' => 'subscriber',
			)
		);
		wp_set_current_user( $subscriber_id );

		$logged_in_views = $this->get_frontend_views_for_date( $date, $private['occur_id'] );
		$this->assertStringContainsString( $private_title, $logged_in_views['single'] );
		$this->assertStringContainsString( $private_title, $logged_in_views['shortcode'] );
		$this->assertStringContainsString( $private_title, $logged_in_views['today_widget'] );
		$this->assertStringContainsString( $private_title, $logged_in_views['upcoming_widget'] );
	}

	/**
	 * Personal events should only be visible to their event author.
	 */
	public function test_personal_events_visible_only_to_event_author() {
		$date            = current_time( 'Y-m-d' );
		$personal_title  = 'Personal Event Author Visibility';
		$personal_author = self::factory()->user->create(
			array(
				'role' => 'subscriber',
			)
		);

		$personal = $this->create_event_with_status(
			$personal_title,
			5,
			$date,
			array(
				'event_author' => $personal_author,
				'event_host'   => $personal_author,
			)
		);

		wp_set_current_user( 0 );
		$anonymous_views = $this->get_frontend_views_for_date( $date, $personal['occur_id'] );
		$this->assertStringNotContainsString( $personal_title, $anonymous_views['single'] );
		$this->assertStringNotContainsString( $personal_title, $anonymous_views['shortcode'] );
		$this->assertStringNotContainsString( $personal_title, $anonymous_views['today_widget'] );
		$this->assertStringNotContainsString( $personal_title, $anonymous_views['upcoming_widget'] );

		$other_user = self::factory()->user->create(
			array(
				'role' => 'subscriber',
			)
		);
		wp_set_current_user( $other_user );

		$other_user_views = $this->get_frontend_views_for_date( $date, $personal['occur_id'] );
		$this->assertStringNotContainsString( $personal_title, $other_user_views['single'] );
		$this->assertStringNotContainsString( $personal_title, $other_user_views['shortcode'] );
		$this->assertStringNotContainsString( $personal_title, $other_user_views['today_widget'] );
		$this->assertStringNotContainsString( $personal_title, $other_user_views['upcoming_widget'] );

		wp_set_current_user( $personal_author );
		$author_views = $this->get_frontend_views_for_date( $date, $personal['occur_id'] );
		$this->assertStringContainsString( $personal_title, $author_views['single'] );
		$this->assertStringContainsString( $personal_title, $author_views['shortcode'] );
		$this->assertStringContainsString( $personal_title, $author_views['today_widget'] );
		$this->assertStringContainsString( $personal_title, $author_views['upcoming_widget'] );
	}

	/**
	 * Provider for statuses that should never be displayed publicly.
	 *
	 * @return array<string,array<int>>
	 */
	public function hidden_status_provider() {
		return array(
			'draft' => array( 0 ),
			'trash' => array( 2 ),
		);
	}

	/**
	 * Create an event with a specific status and return its IDs.
	 *
	 * @param string $title Event title.
	 * @param int    $status Event approved status.
	 * @param string $date Event date in Y-m-d format.
	 *
	 * @return array<string,int>
	 */
	protected function create_event_with_status( $title, $status, $date, $overrides = array() ) {
		$post     = $this->build_event_post(
			array(
				'event_title'    => $title,
				'event_begin'    => array( $date ),
				'event_end'      => array( $date ),
				'event_approved' => (string) $status,
			)
		);
		$post     = array_replace( $post, $overrides );
		$response = $this->create_event( $post );

		$occurrences = mc_get_occurrences( (int) $response['event_id'] );
		$this->assertNotEmpty( $occurrences );

		return array(
			'event_id'  => (int) $response['event_id'],
			'event_post'=> (int) $response['event_post'],
			'occur_id'  => (int) $occurrences[0]->occur_id,
		);
	}

	/**
	 * Build frontend output strings for a date and instance.
	 *
	 * @param string $date Event date in Y-m-d format.
	 * @param int    $occur_id Event occurrence ID.
	 *
	 * @return array<string,string>
	 */
	protected function get_frontend_views_for_date( $date, $occur_id ) {
		$year  = (int) gmdate( 'Y', strtotime( $date ) );
		$month = (int) gmdate( 'n', strtotime( $date ) );
		$day   = (int) gmdate( 'j', strtotime( $date ) );

		$single = mc_get_event( $occur_id, 'html' );

		$shortcode = my_calendar_insert(
			array(
				'format' => 'list',
				'time'   => 'day',
				'year'   => $year,
				'month'  => $month,
				'day'    => $day,
				'id'     => 'status-visibility-test',
			)
		);

		$today_widget = my_calendar_todays_events(
			array(
				'category' => '',
				'template' => '<strong>{title}</strong>',
				'fallback' => 'No events',
				'author'   => 'all',
				'host'     => 'all',
				'date'     => $date,
			)
		);

		$upcoming_widget = my_calendar_upcoming_events(
			array(
				'before'         => 0,
				'after'          => 0,
				'type'           => 'days',
				'category'       => '',
				'template'       => '<strong>{title}</strong>',
				'fallback'       => 'No events',
				'order'          => 'asc',
				'skip'           => 0,
				'show_recurring' => 'yes',
				'author'         => 'all',
				'host'           => 'all',
				'from'           => $date,
				'to'             => $date,
				'ltype'          => '',
				'lvalue'         => '',
			),
			array()
		);

		return array(
			'single'          => $single,
			'shortcode'       => $shortcode,
			'today_widget'    => $today_widget,
			'upcoming_widget' => $upcoming_widget,
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
			'event_title'      => 'Status Visibility Test Event',
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
