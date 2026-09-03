<?php
/**
 * Integrity tests for individual template helper functions in my-calendar-templates.php.
 *
 * @package MyCalendar
 */

/**
 * Covers the smaller template helper functions used to build event markup.
 */
class Tests_My_Calendar_Template_Functions extends WP_UnitTestCase {
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
	 * Reset request state before each test.
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$admin_id );
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
	}

	/**
	 * Clean global request state.
	 */
	public function tear_down() {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();

		parent::tear_down();
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
	 * Build a location POST array with default values.
	 *
	 * @param array $overrides Location field overrides.
	 *
	 * @return array
	 */
	protected function build_location_post( $overrides = array() ) {
		$post = array(
			'location_nonce_name' => wp_create_nonce( 'location_nonce' ),
			'mode'                => 'add',
			'location_label'      => 'Test Location',
			'location_street'     => '123 Test Street',
			'location_street2'    => '',
			'location_city'       => 'Minneapolis',
			'location_state'      => 'MN',
			'location_postcode'   => '55401',
			'location_region'     => 'Twin Cities',
			'location_country'    => 'US',
			'location_url'        => 'https://example.com/location',
			'location_longitude'  => '-93.2650',
			'location_latitude'   => '44.9778',
			'location_zoom'       => '15',
			'location_phone'      => '612-555-0100',
			'location_phone2'     => '',
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

	/**
	 * Create an event with an attached, fully populated location.
	 *
	 * @return object Event occurrence object as returned by mc_get_event().
	 */
	protected function create_event_with_location() {
		$location = mc_insert_location( $this->build_location_post() );
		$post     = $this->build_event_post(
			array(
				'location_preset' => 'id',
				'preset_location'  => (string) $location['location_id'],
			)
		);
		$response = $this->create_event( $post );

		$occurrences = mc_get_occurrences( $response['event_id'] );
		$occurrence  = reset( $occurrences );

		return mc_get_event( $occurrence->occur_id );
	}

	/**
	 * mc_str_replace_word_i() should wrap whole-word matches, case-insensitively.
	 */
	public function test_str_replace_word_i_wraps_whole_word_matches() {
		$result = mc_str_replace_word_i( 'festival', 'Spring Festival is fun, festivals are not matched.' );

		$this->assertSame( 'Spring <strong class="mc_search_term">Festival</strong> is fun, festivals are not matched.', $result );
	}

	/**
	 * mc_search_highlight() should return the first string unmodified when no search term is present.
	 */
	public function test_search_highlight_returns_original_text_without_search_term() {
		unset( $_REQUEST['mcs'] );

		$result = mc_search_highlight( 'Full description text.', 'Short text.' );

		$this->assertSame( 'Full description text.', $result );
	}

	/**
	 * mc_search_highlight() should wrap the requested search term when `mcs` is present.
	 */
	public function test_search_highlight_wraps_search_term_when_present() {
		$_REQUEST['mcs'] = 'festival';

		$result = mc_search_highlight( 'Join us for the annual Spring Festival downtown.', '' );

		$this->assertStringContainsString( '<strong class="mc_search_term">Festival</strong>', $result );
	}

	/**
	 * mc_format_timestamp() should produce an iCal-formatted UTC timestamp string.
	 */
	public function test_format_timestamp_formats_expected_ical_string() {
		$timestamp = mktime( 9, 30, 0, 3, 14, 2026 );

		$this->assertSame( '20260314T093000', mc_format_timestamp( $timestamp ) );
	}

	/**
	 * mc_runtime() should return a human-readable time difference when the event has distinct start/end.
	 */
	public function test_runtime_returns_human_readable_difference() {
		$event = (object) array(
			'event_hide_end' => '0',
			'event_end'      => '2026-03-14',
		);
		$start = strtotime( '2026-03-14 09:00:00' );
		$end   = strtotime( '2026-03-14 11:00:00' );

		$this->assertSame( human_time_diff( $start, $end ), mc_runtime( $start, $end, $event ) );
	}

	/**
	 * mc_runtime() should return an empty string when the end time is hidden.
	 */
	public function test_runtime_returns_empty_when_end_hidden() {
		$event = (object) array(
			'event_hide_end' => '1',
			'event_end'      => '2026-03-14',
		);
		$start = strtotime( '2026-03-14 09:00:00' );
		$end   = strtotime( '2026-03-14 11:00:00' );

		$this->assertSame( '', mc_runtime( $start, $end, $event ) );
	}

	/**
	 * mc_duration() should produce an ISO8601 duration string for a timed event.
	 */
	public function test_duration_returns_iso8601_duration_for_timed_event() {
		$event = (object) array(
			'occur_begin' => '2026-03-14 09:00:00',
			'occur_end'   => '2026-03-14 11:30:00',
		);

		$this->assertSame( 'PT2H30M', mc_duration( $event ) );
	}

	/**
	 * mc_duration() should treat events ending at 23:59 as running through the following day.
	 */
	public function test_duration_treats_end_of_day_as_all_day() {
		$event = (object) array(
			'occur_begin' => '2026-03-14 00:00:00',
			'occur_end'   => '2026-03-14 23:59:00',
		);

		$this->assertSame( 'PD1TH0M0', mc_duration( $event ) );
	}

	/**
	 * mc_date_badge() should output a `<time>` element containing month and day markup.
	 */
	public function test_date_badge_contains_month_and_day_markup() {
		$badge = mc_date_badge( '2026-03-14' );

		$this->assertStringContainsString( '<time class="mc-date-badge"', $badge );
		$this->assertStringContainsString( '<span class="month">', $badge );
		$this->assertStringContainsString( '<span class="day">14</span>', $badge );
	}

	/**
	 * mc_format_date_span() should return the default output when no dates are provided.
	 */
	public function test_format_date_span_returns_default_when_empty() {
		$this->assertSame( 'no dates', mc_format_date_span( array(), 'simple', 'no dates' ) );
	}

	/**
	 * mc_format_date_span() should format a simple begin/end span using the first and last dates.
	 */
	public function test_format_date_span_formats_simple_range() {
		$dates = array(
			(object) array(
				'occur_begin' => '2026-03-14 09:00:00',
				'occur_end'   => '2026-03-14 11:00:00',
			),
			(object) array(
				'occur_begin' => '2026-03-16 09:00:00',
				'occur_end'   => '2026-03-16 11:00:00',
			),
		);

		$result = mc_format_date_span( $dates, 'simple' );

		$this->assertStringContainsString( date_i18n( mc_date_format(), strtotime( '2026-03-14 09:00:00' ) ), $result );
		$this->assertStringContainsString( date_i18n( mc_date_format(), strtotime( '2026-03-16 11:00:00' ) ), $result );
		$this->assertStringContainsString( '<span>&ndash;</span>', $result );
	}

	/**
	 * mc_get_preset_template() should return distinct markup per preset key and fall back to a default.
	 */
	public function test_get_preset_template_returns_expected_variants() {
		$this->assertStringContainsString( 'mc-group-1', mc_get_preset_template( 'list_preset_1' ) );
		$this->assertStringContainsString( 'mc-group-1', mc_get_preset_template( 'list_preset_2' ) );
		$this->assertStringContainsString( 'mc-group-1', mc_get_preset_template( 'list_preset_3' ) );
		$this->assertStringContainsString( 'mc-group-1', mc_get_preset_template( 'list_preset_4' ) );
		// Unknown types fall through to the default preset.
		$this->assertSame( mc_get_preset_template( 'unknown' ), mc_get_preset_template( 'not_a_real_preset' ) );
	}

	/**
	 * mc_setup_template() should fall back to the default template when given an empty/placeholder value.
	 */
	public function test_setup_template_falls_back_to_default() {
		$this->assertSame( 'fallback', mc_setup_template( 'default', 'fallback' ) );
		$this->assertSame( 'fallback', mc_setup_template( '', 'fallback' ) );
	}

	/**
	 * mc_setup_template() should return a literal template string as-is when it isn't a file reference or saved key.
	 */
	public function test_setup_template_returns_literal_template_unmodified() {
		$this->assertSame( '{title}', mc_setup_template( '{title}', 'fallback' ) );
	}

	/**
	 * mc_map_string() should concatenate location fields into a single address string.
	 */
	public function test_map_string_builds_address_from_location_fields() {
		$location = (object) array(
			'location_street'    => '123 Test Street',
			'location_street2'   => '',
			'location_city'      => 'Minneapolis',
			'location_state'     => 'MN',
			'location_postcode'  => '55401',
			'location_country'   => 'US',
		);

		$result = mc_map_string( $location, 'location' );

		$this->assertSame( '123 Test Street  Minneapolis MN 55401 US', $result );
	}

	/**
	 * mc_map_string() should return an empty string when passed a non-object.
	 */
	public function test_map_string_returns_empty_for_non_object() {
		$this->assertSame( '', mc_map_string( 'not-an-object', 'location' ) );
	}

	/**
	 * mc_maplink() should build a Google Maps link using coordinates when available.
	 */
	public function test_maplink_returns_google_maps_link_with_coordinates() {
		mc_update_option( 'map_service', 'google' );
		$location = (object) array(
			'location_street'    => '123 Test Street',
			'location_street2'   => '',
			'location_city'      => 'Minneapolis',
			'location_state'     => 'MN',
			'location_postcode'  => '55401',
			'location_country'   => 'US',
			'location_url'       => '',
			'location_label'     => 'Test Location',
			'location_zoom'      => '15',
			'location_longitude' => '-93.265000',
			'location_latitude'  => '44.977800',
		);

		$map = mc_maplink( $location, 'map', 'location' );

		$this->assertStringContainsString( 'maps.google.com/maps', $map );
		$this->assertStringContainsString( 'map-link external', $map );
	}

	/**
	 * mc_maplink() should return an empty string when the location has no address data.
	 */
	public function test_maplink_returns_empty_string_without_address() {
		$location = (object) array(
			'location_street'    => '',
			'location_street2'   => '',
			'location_city'      => '',
			'location_state'     => '',
			'location_postcode'  => '',
			'location_country'   => '',
			'location_url'       => '',
			'location_label'     => '',
			'location_zoom'      => '0',
			'location_longitude' => '0.000000',
			'location_latitude'  => '0.000000',
		);

		$this->assertSame( '', mc_maplink( $location, 'map', 'location' ) );
	}

	/**
	 * mc_google_cal() should build a Google Calendar template link with encoded parameters.
	 */
	public function test_google_cal_builds_expected_link() {
		$link = mc_google_cal( '20260314T090000', '20260314T110000', 'https://example.com', 'Spring Festival', 'Minneapolis, MN', 'Come join us' );

		$this->assertStringStartsWith( 'https://www.google.com/calendar/render?action=TEMPLATE', $link );
		$this->assertStringContainsString( '&dates=20260314T090000/20260314T110000', $link );
		$this->assertStringContainsString( '&text=' . urlencode( 'Spring Festival' ), $link );
		$this->assertStringContainsString( '&location=' . urlencode( 'Minneapolis, MN' ), $link );
	}

	/**
	 * mc_outlook_cal() should build an Outlook link with the expected query args.
	 */
	public function test_outlook_cal_builds_expected_link() {
		$dtstart = strtotime( '2026-03-14 09:00:00' );
		$dtend   = strtotime( '2026-03-14 11:00:00' );
		$link    = mc_outlook_cal( $dtstart, $dtend, 'https://example.com', 'Spring Festival', 'Minneapolis, MN', 'Come join us', 'false' );

		$this->assertStringStartsWith( 'https://outlook.live.com/calendar/0/action/compose', $link );
		$this->assertStringContainsString( 'subject=' . urlencode( 'Spring Festival' ), $link );
		$this->assertStringContainsString( 'allday=false', $link );
	}

	/**
	 * mc_office_cal() should return the Outlook link rewritten to the Office 365 host.
	 */
	public function test_office_cal_uses_office_host() {
		$dtstart = strtotime( '2026-03-14 09:00:00' );
		$dtend   = strtotime( '2026-03-14 11:00:00' );
		$link    = mc_office_cal( $dtstart, $dtend, 'https://example.com', 'Spring Festival', 'Minneapolis, MN', 'Come join us', 'false' );

		$this->assertStringContainsString( 'outlook.office.com', $link );
		$this->assertStringNotContainsString( 'outlook.live.com', $link );
	}

	/**
	 * mc_get_event_location() with source 'location' should return the object as-is.
	 */
	public function test_get_event_location_returns_location_object_directly() {
		$location = (object) array( 'location_label' => 'Test' );

		$this->assertSame( $location, mc_get_event_location( $location, 'location' ) );
	}

	/**
	 * mc_get_event_location() should return false when an event has no location data.
	 */
	public function test_get_event_location_returns_false_without_location_data() {
		$event = (object) array( 'event_location' => 0 );

		$this->assertFalse( mc_get_event_location( $event, 'event' ) );
	}

	/**
	 * mc_get_event_location() should resolve a location object from an event's location ID.
	 */
	public function test_get_event_location_resolves_location_from_event_id() {
		$event = $this->create_event_with_location();

		$location = mc_get_event_location( $event, 'event' );

		$this->assertIsObject( $location );
		$this->assertSame( 'Test Location', $location->location_label );
	}

	/**
	 * mc_event_expired() should return true for events with an end date in the past.
	 */
	public function test_event_expired_returns_true_for_past_event() {
		$event = (object) array( 'occur_end' => '2000-01-01 12:00:00' );

		$this->assertTrue( mc_event_expired( $event ) );
	}

	/**
	 * mc_event_expired() should return false for events with an end date in the future.
	 */
	public function test_event_expired_returns_false_for_future_event() {
		$event = (object) array( 'occur_end' => '2099-01-01 12:00:00' );

		$this->assertFalse( mc_event_expired( $event ) );
	}

	/**
	 * mc_event_link() should return the event's link when the link is not set to expire.
	 */
	public function test_event_link_returns_link_when_not_set_to_expire() {
		$event = (object) array(
			'event_link'         => 'https://example.com/event',
			'event_link_expires' => '0',
			'occur_end'          => '2000-01-01 12:00:00',
		);

		$this->assertSame( 'https://example.com/event', mc_event_link( $event ) );
	}

	/**
	 * mc_event_link() should return an empty string once an expiring link's event has passed.
	 */
	public function test_event_link_returns_empty_when_expired_and_set_to_expire() {
		$event = (object) array(
			'event_link'         => 'https://example.com/event',
			'event_link_expires' => '1',
			'occur_end'          => '2000-01-01 12:00:00',
		);

		$this->assertSame( '', mc_event_link( $event ) );
	}

	/**
	 * mc_event_link() should return an empty string for non-object input.
	 */
	public function test_event_link_returns_empty_for_non_object() {
		$this->assertSame( '', mc_event_link( 'not-an-event' ) );
	}

	/**
	 * mc_notime_label() should return the configured default "no time" label.
	 */
	public function test_notime_label_returns_configured_default() {
		mc_update_option( 'notime_text', 'All Day' );
		$event = (object) array();

		$this->assertSame( 'All Day', mc_notime_label( $event ) );
	}

	/**
	 * mc_create_tags() should return an empty array for non-object input.
	 */
	public function test_create_tags_returns_empty_array_for_non_object() {
		$this->assertSame( array(), mc_create_tags( 'not-an-event' ) );
	}

	/**
	 * mc_create_tags() should populate the expected core tag keys for a real event.
	 */
	public function test_create_tags_populates_expected_keys() {
		$event = $this->create_event_with_location();

		$tags = mc_create_tags( $event );

		$this->assertArrayHasKey( 'runtime', $tags );
		$this->assertArrayHasKey( 'duration', $tags );
		$this->assertArrayHasKey( 'dtstart', $tags );
		$this->assertArrayHasKey( 'dtend', $tags );
		$this->assertArrayHasKey( 'datebadge', $tags );
		$this->assertArrayHasKey( 'author', $tags );
		$this->assertSame( $event->event_post, $tags['post'] );
	}

	/**
	 * mc_hcard() should render the event location's address details.
	 */
	public function test_hcard_renders_location_address() {
		$event = $this->create_event_with_location();

		$hcard = mc_hcard( $event, 'true', 'false' );

		$this->assertStringContainsString( 'Test Location', $hcard );
		$this->assertStringContainsString( 'Minneapolis', $hcard );
	}

	/**
	 * mc_get_template_tag()/mc_template_tag() should read a value from an event's tag array.
	 */
	public function test_get_template_tag_reads_value_from_tags() {
		$data = (object) array(
			'tags' => array( 'title' => 'Spring Festival' ),
		);

		$this->assertSame( 'Spring Festival', mc_get_template_tag( $data, 'title' ) );
		$this->assertSame( '', mc_get_template_tag( $data, 'missing_key' ) );
	}

	/**
	 * mc_get_template() should only return values for recognized template keys.
	 */
	public function test_get_template_returns_empty_for_unrecognized_key() {
		$this->assertSame( '', mc_get_template( 'not_a_real_template_key' ) );
	}

	/**
	 * mc_get_template() should return the saved template value for a recognized key.
	 */
	public function test_get_template_returns_saved_value_for_recognized_key() {
		$templates          = mc_get_option( 'templates' );
		$templates['title'] = '{title}';
		mc_update_option( 'templates', $templates );

		$this->assertSame( '{title}', mc_get_template( 'title' ) );
	}
}
