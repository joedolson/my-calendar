<?php
/**
 * Search results page tests.
 *
 * @package MyCalendar
 */

/**
 * Verify when search results replace page content.
 */
class Tests_My_Calendar_Search_Results_Page extends WP_UnitTestCase {
	/**
	 * Prepare event tables and an administrator.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		mc_posttypes();
		mc_taxonomies();
		if ( ! get_option( 'my_calendar_options' ) || ! my_calendar_exists() ) {
			mc_initial_install();
		}
	}

	/**
	 * Clear request state and the cached settings.
	 */
	public function tear_down() {
		$_GET = array();
		parent::tear_down();
		mc_get_option( 'uri_id', '', true );
	}

	/**
	 * Search results should render without a primary calendar page.
	 */
	public function test_search_results_render_without_a_calendar_page() {
		$this->set_calendar_page( '' );
		$this->visit_page( 'Search form' );

		$this->assertStringContainsString( 'mc-event-list-container', mc_show_search_results( 'Search form' ) );
	}

	/**
	 * The primary calendar page remains responsible for its own output.
	 */
	public function test_primary_calendar_page_is_not_replaced() {
		$page_id = $this->visit_page( 'Calendar content' );
		$this->set_calendar_page( $page_id );

		$this->assertSame( 'Calendar content', mc_show_search_results( 'Calendar content' ) );
	}

	/**
	 * Search results also render when a different primary calendar page is set.
	 */
	public function test_search_results_render_on_another_page() {
		$calendar_page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->set_calendar_page( $calendar_page );
		$this->visit_page( 'Search form' );

		$this->assertStringContainsString( 'mc-event-list-container', mc_show_search_results( 'Search form' ) );
	}

	/**
	 * Pages with a calendar shortcode remain responsible for their own output.
	 */
	public function test_calendar_shortcode_page_is_not_replaced() {
		$this->set_calendar_page( '' );
		$this->visit_page( '[my_calendar]' );

		$this->assertSame( 'Calendar content', mc_show_search_results( 'Calendar content' ) );
	}

	/**
	 * Set the primary calendar page and refresh the settings cache.
	 *
	 * @param int|string $page_id Page ID or an empty string.
	 */
	private function set_calendar_page( $page_id ) {
		$options           = mc_get_options();
		$options['uri_id'] = $page_id;
		update_option( 'my_calendar_options', $options );
		mc_get_option( 'uri_id', '', true );
	}

	/**
	 * Create a page and enter its main query loop with a search request.
	 *
	 * @param string $content Page content.
	 * @return int Page ID.
	 */
	private function visit_page( $content ) {
		$page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => $content,
			)
		);
		$this->go_to( get_permalink( $page_id ) );
		$_GET['mcs'] = 'nonexistent-event';
		the_post();

		return $page_id;
	}
}
