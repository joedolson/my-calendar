<?php
/**
 * Location management tests.
 *
 * @package MyCalendar
 */

/**
 * Covers location creation, updating, and deletion functionality.
 */
class Tests_My_Calendar_Locations extends WP_UnitTestCase {
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
	 * Verify a location can be created and stored in the database.
	 */
	public function test_creates_location_in_database() {
		$post = $this->build_location_post(
			array(
				'location_label' => 'Downtown Venue',
				'location_city'  => 'Minneapolis',
				'location_state' => 'MN',
			)
		);

		$result = mc_insert_location( $post );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'location_id', $result );
		$this->assertArrayHasKey( 'location_post', $result );
		$this->assertIsInt( $result['location_id'] );
		$this->assertGreaterThan( 0, $result['location_id'] );

		// Verify data was stored in database.
		$location = mc_get_location( $result['location_id'], false );
		$this->assertIsObject( $location );
		$this->assertSame( 'Downtown Venue', $location->location_label );
		$this->assertSame( 'Minneapolis', $location->location_city );
		$this->assertSame( 'MN', $location->location_state );
	}

	/**
	 * Verify all location fields are properly stored.
	 */
	public function test_creates_location_with_all_fields() {
		$post = $this->build_location_post(
			array(
				'location_label'     => 'Full Location',
				'location_street'    => '456 Main Avenue',
				'location_street2'   => 'Suite 200',
				'location_city'      => 'St. Paul',
				'location_state'     => 'MN',
				'location_postcode'  => '55101',
				'location_region'    => 'Twin Cities',
				'location_country'   => 'US',
				'location_url'       => 'https://example.com/stpaul',
				'location_longitude' => '-93.0900',
				'location_latitude'  => '44.9537',
				'location_zoom'      => '16',
				'location_phone'     => '651-555-0100',
				'location_phone2'    => '651-555-0101',
			)
		);

		$result   = mc_insert_location( $post );
		$location = mc_get_location( $result['location_id'], false );

		$this->assertSame( 'Full Location', $location->location_label );
		$this->assertSame( '456 Main Avenue', $location->location_street );
		$this->assertSame( 'Suite 200', $location->location_street2 );
		$this->assertSame( 'St. Paul', $location->location_city );
		$this->assertSame( 'MN', $location->location_state );
		$this->assertSame( '55101', $location->location_postcode );
		$this->assertSame( 'Twin Cities', $location->location_region );
		$this->assertSame( 'US', $location->location_country );
		$this->assertSame( 'https://example.com/stpaul', $location->location_url );
		$this->assertEqualsWithDelta( -93.0900, (float) $location->location_longitude, 0.0001 );
		$this->assertEqualsWithDelta( 44.9537, (float) $location->location_latitude, 0.0001 );
		$this->assertSame( 16, (int) $location->location_zoom );
		$this->assertSame( '651-555-0100', $location->location_phone );
		$this->assertSame( '651-555-0101', $location->location_phone2 );
	}

	/**
	 * Verify location creation increments location count.
	 */
	public function test_location_creation_increments_count() {
		$before = mc_count_locations();

		$post   = $this->build_location_post();
		$result = mc_insert_location( $post );

		$after = mc_count_locations();

		$this->assertSame( $before + 1, $after );
		$this->assertGreaterThan( 0, $result['location_id'] );
	}

	/**
	 * Verify multiple locations can be created independently.
	 */
	public function test_creates_multiple_locations() {
		$before = mc_count_locations();

		$location1 = mc_insert_location(
			$this->build_location_post(
				array(
					'location_label' => 'Venue 1',
					'location_city'  => 'Minneapolis',
				)
			)
		);

		$location2 = mc_insert_location(
			$this->build_location_post(
				array(
					'location_label' => 'Venue 2',
					'location_city'  => 'Duluth',
				)
			)
		);

		$after = mc_count_locations();

		$this->assertSame( $before + 2, $after );
		$this->assertNotSame( $location1['location_id'], $location2['location_id'] );

		$loc1 = mc_get_location( $location1['location_id'], false );
		$loc2 = mc_get_location( $location2['location_id'], false );

		$this->assertSame( 'Venue 1', $loc1->location_label );
		$this->assertSame( 'Venue 2', $loc2->location_label );
	}

	/**
	 * Verify a location can be updated.
	 */
	public function test_updates_location_data() {
		$post      = $this->build_location_post(
			array(
				'location_label' => 'Original Name',
				'location_city'  => 'Minneapolis',
			)
		);
		$result    = mc_insert_location( $post );
		$location1 = mc_get_location( $result['location_id'], false );

		$this->assertSame( 'Original Name', $location1->location_label );
		$this->assertSame( 'Minneapolis', $location1->location_city );

		// Update the location.
		$update_post = $this->build_location_post(
			array(
				'mode'              => 'edit',
				'location_id'       => $result['location_id'],
				'location_label'    => 'Updated Name',
				'location_city'     => 'St. Paul',
				'location_postcode' => '55101',
			)
		);

		mc_update_location( $update_post );

		// Force reset to bypass static cache.
		$location2 = mc_get_location( $result['location_id'], true, true );

		$this->assertSame( 'Updated Name', $location2->location_label );
		$this->assertSame( 'St. Paul', $location2->location_city );
		$this->assertSame( '55101', $location2->location_postcode );
	}

	/**
	 * Verify location transient is cleared on update.
	 */
	public function test_location_transient_cleared_on_update() {
		$post   = $this->build_location_post();
		$result = mc_insert_location( $post );

		// Set a transient for this location.
		$transient_key = 'mc_location_' . $result['location_id'];
		set_transient( $transient_key, 'cached_value', WEEK_IN_SECONDS );

		$cached = get_transient( $transient_key );
		$this->assertSame( 'cached_value', $cached );

		// Update location.
		$update_post = $this->build_location_post(
			array(
				'mode'           => 'edit',
				'location_id'    => $result['location_id'],
				'location_label' => 'New Name',
			)
		);

		mc_update_location( $update_post );

		// Transient should be cleared.
		$cached = get_transient( $transient_key );
		$this->assertFalse( $cached );
	}

	/**
	 * Verify a location can be deleted from the database.
	 */
	public function test_deletes_location_from_database() {
		$post   = $this->build_location_post();
		$result = mc_insert_location( $post );

		$location_id = $result['location_id'];

		// Verify location exists.
		$location = mc_get_location( $location_id, false );
		$this->assertIsObject( $location );
		$this->assertSame( 'Test Location', $location->location_label );

		// Delete the location.
		$delete_result = mc_delete_location( $location_id, 'boolean' );

		$this->assertTrue( $delete_result );

		// Force reset to verify deletion.
		$deleted_location = mc_get_location( $location_id, true, true );
		$this->assertFalse( $deleted_location );
	}

	/**
	 * Verify location deletion decrements the location count.
	 */
	public function test_location_deletion_decrements_count() {
		$post   = $this->build_location_post();
		$result = mc_insert_location( $post );

		$before = mc_count_locations();

		mc_delete_location( $result['location_id'], 'boolean' );

		$after = mc_count_locations();

		$this->assertSame( $before - 1, $after );
	}

	/**
	 * Verify deletion of multiple locations works correctly.
	 */
	public function test_deletes_multiple_locations() {
		$location1 = mc_insert_location(
			$this->build_location_post(
				array( 'location_label' => 'Delete Test 1' )
			)
		);

		$location2 = mc_insert_location(
			$this->build_location_post(
				array( 'location_label' => 'Delete Test 2' )
			)
		);

		$before = mc_count_locations();

		$delete1 = mc_delete_location( $location1['location_id'], 'boolean' );
		$delete2 = mc_delete_location( $location2['location_id'], 'boolean' );

		$after = mc_count_locations();

		$this->assertTrue( $delete1 );
		$this->assertTrue( $delete2 );
		$this->assertSame( $before - 2, $after );

		// Verify both are gone - force reset to verify deletion.
		$this->assertFalse( mc_get_location( $location1['location_id'], true, true ) );
		$this->assertFalse( mc_get_location( $location2['location_id'], true, true ) );
	}

	/**
	 * Verify location deletion clears the location count transient.
	 */
	public function test_location_deletion_clears_count_transient() {
		$post   = $this->build_location_post();
		$result = mc_insert_location( $post );

		// Count should be cached.
		$count = mc_count_locations();

		// Delete location via the main function (which should clear transient).
		mc_delete_location( $result['location_id'], 'boolean' );

		// Manually verify count is recalculated.
		$new_count = mc_count_locations();

		$this->assertLessThan( $count, $new_count );
	}

	/**
	 * Verify deleting default location unsets the option.
	 */
	public function test_deleting_default_location_unsets_option() {
		$post   = $this->build_location_post();
		$result = mc_insert_location( $post );

		// Set as default location.
		mc_update_option( 'default_location', $result['location_id'] );
		$default = mc_get_option( 'default_location' );
		$this->assertSame( $result['location_id'], (int) $default );

		// Delete the location.
		mc_delete_location( $result['location_id'], 'boolean' );

		// Default location option should be cleared.
		$default = mc_get_option( 'default_location' );
		$this->assertEmpty( $default );
	}

	/**
	 * Verify location transient is cleared on deletion.
	 */
	public function test_location_transient_cleared_on_deletion() {
		$post   = $this->build_location_post();
		$result = mc_insert_location( $post );

		$transient_key = 'mc_location_' . $result['location_id'];

		// Verify transient can be set and retrieved.
		set_transient( $transient_key, 'test_value', WEEK_IN_SECONDS );
		$cached = get_transient( $transient_key );
		$this->assertSame( 'test_value', $cached );

		// Delete the location.
		mc_delete_location( $result['location_id'], 'boolean' );

		// Transient should be cleared.
		$cached = get_transient( $transient_key );
		$this->assertFalse( $cached );
	}

	/**
	 * Verify location can be retrieved after creation.
	 */
	public function test_retrieves_created_location() {
		$post = $this->build_location_post(
			array(
				'location_label'   => 'Retrievable Location',
				'location_phone'   => '612-555-0200',
				'location_country' => 'US',
			)
		);

		$result   = mc_insert_location( $post );
		$location = mc_get_location( $result['location_id'], false );

		$this->assertIsObject( $location );
		$this->assertSame( 'Retrievable Location', $location->location_label );
		$this->assertSame( '612-555-0200', $location->location_phone );
		$this->assertSame( 'US', $location->location_country );
	}

	/**
	 * Verify retrieving non-existent location returns false.
	 */
	public function test_returns_false_for_nonexistent_location() {
		// Use a very high ID that shouldn't exist.
		$location = mc_get_location( 99999, false );

		$this->assertFalse( $location );
	}

	/**
	 * Verify location coordinates are handled correctly on creation.
	 */
	public function test_creates_location_with_coordinates() {
		$post = $this->build_location_post(
			array(
				'location_latitude'  => '46.7833',
				'location_longitude' => '-92.1005',
				'location_zoom'      => '14',
			)
		);

		$result   = mc_insert_location( $post );
		$location = mc_get_location( $result['location_id'], false );

		$this->assertEqualsWithDelta( 46.7833, (float) $location->location_latitude, 0.0001 );
		$this->assertEqualsWithDelta( -92.1005, (float) $location->location_longitude, 0.0001 );
		$this->assertSame( 14, (int) $location->location_zoom );
	}

	/**
	 * Verify empty coordinates default to appropriate values.
	 */
	public function test_creates_location_with_empty_coordinates() {
		$post = $this->build_location_post(
			array(
				'location_latitude'  => '',
				'location_longitude' => '',
				'location_zoom'      => '',
			)
		);

		$result   = mc_insert_location( $post );
		$location = mc_get_location( $result['location_id'], false );

		$this->assertIsObject( $location );
		// Empty strings are acceptable for coordinates.
		$this->assertTrue( '' === $location->location_latitude || '0.000000' === $location->location_latitude );
	}

	/**
	 * Verify location street2 is optional.
	 */
	public function test_creates_location_without_street2() {
		$post = $this->build_location_post(
			array(
				'location_street'  => '100 Hennepin Avenue',
				'location_street2' => '',
			)
		);

		$result   = mc_insert_location( $post );
		$location = mc_get_location( $result['location_id'], false );

		$this->assertSame( '100 Hennepin Avenue', $location->location_street );
		$this->assertEmpty( $location->location_street2 );
	}

	/**
	 * Verify location phone2 is optional.
	 */
	public function test_creates_location_without_phone2() {
		$post = $this->build_location_post(
			array(
				'location_phone'  => '612-555-0100',
				'location_phone2' => '',
			)
		);

		$result   = mc_insert_location( $post );
		$location = mc_get_location( $result['location_id'], false );

		$this->assertSame( '612-555-0100', $location->location_phone );
		$this->assertEmpty( $location->location_phone2 );
	}

	/**
	 * Verify error handling for invalid location deletion.
	 */
	public function test_deletion_of_nonexistent_location_returns_false() {
		$result = mc_delete_location( 99999, 'boolean' );

		$this->assertFalse( $result );
	}

	/**
	 * Verify location creation triggers filters.
	 *
	 * Note: This test verifies the filter is applied, though actual execution depends on environment.
	 */
	public function test_location_creation_applies_filters() {
		$filter_called = false;

		// Add a test filter.
		$callback = function ( $insert_id ) use ( &$filter_called ) {
			$filter_called = true;
			return $insert_id;
		};

		add_filter( 'mc_save_location', $callback );

		$post = $this->build_location_post();
		mc_insert_location( $post );

		remove_filter( 'mc_save_location', $callback );

		$this->assertTrue( $filter_called );
	}

	/**
	 * Verify location deletion triggers actions.
	 *
	 * Note: This test verifies the action is triggered, though actual execution depends on environment.
	 */
	public function test_location_deletion_triggers_action() {
		$action_called = false;

		$callback = function () use ( &$action_called ) {
			$action_called = true;
		};

		add_action( 'mc_delete_location', $callback );

		$post   = $this->build_location_post();
		$result = mc_insert_location( $post );

		mc_delete_location( $result['location_id'], 'boolean' );

		remove_action( 'mc_delete_location', $callback );

		$this->assertTrue( $action_called );
	}
}
