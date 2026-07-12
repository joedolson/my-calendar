<?php
/**
 * Category lifecycle tests.
 *
 * @package MyCalendar
 */

/**
 * Covers category creation and deletion behavior.
 */
class Tests_My_Calendar_Categories extends WP_UnitTestCase {
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
	 * Verify category creation stores both a category row and a taxonomy term.
	 */
	public function test_creates_category_record_and_term() {
		$name        = 'Lifecycle Category ' . wp_generate_password( 8, false );
		$category_id = mc_create_category(
			array(
				'category_name'  => $name,
				'category_color' => '#112233',
				'category_icon'  => 'event.svg',
			)
		);

		$this->assertIsInt( $category_id );
		$this->assertGreaterThan( 0, $category_id );

		$category = mc_get_category( $category_id );
		$this->assertIsObject( $category );
		$this->assertSame( $name, $category->category_name );
		$this->assertSame( '#112233', $category->category_color );

		$term = get_term( $category->category_term, 'mc-event-category' );
		$this->assertNotWPError( $term );
		$this->assertInstanceOf( 'WP_Term', $term );
		$this->assertSame( $name, $term->name );
	}

	/**
	 * Verify category deletion through the manage screen removes the DB row.
	 */
	public function test_deletes_category_via_manage_categories_screen() {
		global $wpdb;

		$category_id = mc_create_category(
			array(
				'category_name'  => 'Delete Category ' . wp_generate_password( 8, false ),
				'category_color' => '#334455',
				'category_icon'  => 'event.svg',
			)
		);

		$this->assertGreaterThan( 0, $category_id );

		$before = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . my_calendar_categories_table() . ' WHERE category_id = %d', $category_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->assertSame( '1', (string) $before );

		$_GET = array(
			'mode'        => 'delete',
			'category_id' => (string) $category_id,
			'_mcnonce'    => wp_create_nonce( 'mcnonce' ),
		);

		ob_start();
		my_calendar_manage_categories();
		$output = ob_get_clean();

		$after = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . my_calendar_categories_table() . ' WHERE category_id = %d', $category_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->assertSame( '0', (string) $after );
		$this->assertStringContainsString( 'Category deleted successfully', (string) $output );
	}

	/**
	 * Verify category deletion also removes the corresponding taxonomy term.
	 */
	public function test_deletes_category_taxonomy_term_when_category_is_removed() {
		$category_name = 'Delete Term Category ' . wp_generate_password( 8, false );
		$category_id   = mc_create_category(
			array(
				'category_name'  => $category_name,
				'category_color' => '#556677',
				'category_icon'  => 'event.svg',
			)
		);

		$this->assertGreaterThan( 0, $category_id );

		$category = mc_get_category( $category_id );
		$this->assertIsObject( $category );

		$term_id = (int) $category->category_term;
		$this->assertGreaterThan( 0, $term_id );
		$this->assertNotFalse( term_exists( $term_id, 'mc-event-category' ) );

		$_GET = array(
			'mode'        => 'delete',
			'category_id' => (string) $category_id,
			'_mcnonce'    => wp_create_nonce( 'mcnonce' ),
		);

		ob_start();
		my_calendar_manage_categories();
		ob_end_clean();

		$this->assertNull( term_exists( $term_id, 'mc-event-category' ) );
		$this->assertNull( term_exists( $category_name, 'mc-event-category' ) );
	}

	/**
	 * Verify deleting a category reassigns linked event primary category and relationships.
	 */
	public function test_deleting_category_reassigns_event_relationships_to_default() {
		global $wpdb;

		$fallback_category_id = mc_create_category(
			array(
				'category_name'  => 'Fallback Category ' . wp_generate_password( 8, false ),
				'category_color' => '#445566',
				'category_icon'  => 'event.svg',
			)
		);
		$deleted_category_id  = mc_create_category(
			array(
				'category_name'  => 'Linked Category ' . wp_generate_password( 8, false ),
				'category_color' => '#667788',
				'category_icon'  => 'event.svg',
			)
		);

		$this->assertGreaterThan( 0, $fallback_category_id );
		$this->assertGreaterThan( 0, $deleted_category_id );

		mc_update_option( 'default_category', $fallback_category_id );
		$current_default = (int) mc_get_option( 'default_category', '', true );
		$this->assertSame( $fallback_category_id, $current_default );
		$expected_set_category = ( $deleted_category_id !== $current_default && $current_default > 0 ) ? $current_default : 1;

		$response = $this->create_event(
			$this->build_event_post(
				array(
					'event_category'   => array( $deleted_category_id ),
					'primary_category' => $deleted_category_id,
				)
			)
		);

		$event_id = (int) $response['event_id'];
		$this->assertGreaterThan( 0, $event_id );
		$this->assertSame( $deleted_category_id, mc_get_data( 'event_category', $event_id ) );

		// Ensure category delete runs as a GET request without stale editor POST data.
		$_POST    = array();
		$_REQUEST = array();
		$_GET     = array(
			'mode'        => 'delete',
			'category_id' => (string) $deleted_category_id,
			'_mcnonce'    => wp_create_nonce( 'mcnonce' ),
		);

		ob_start();
		my_calendar_manage_categories();
		$output = ob_get_clean();

		$primary_after = mc_get_data( 'event_category', $event_id );
		$this->assertSame( $expected_set_category, $primary_after );

		$old_relationships = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . my_calendar_category_relationships_table() . ' WHERE event_id = %d AND category_id = %d', $event_id, $deleted_category_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$new_relationships = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . my_calendar_category_relationships_table() . ' WHERE event_id = %d AND category_id = %d', $event_id, $expected_set_category ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$this->assertSame( '0', (string) $old_relationships );
		$this->assertGreaterThan( 0, (int) $new_relationships );
		$this->assertStringContainsString( 'Categories in calendar updated', (string) $output );
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
			'event_title'      => 'Category Relationship Test Event',
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
