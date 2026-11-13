<?php
/**
 * Context-specific settings permission tests.
 *
 * @package MyCalendar
 */

/**
 * Verify delegated setting groups and their handlers.
 */
class Tests_My_Calendar_Context_Settings extends WP_UnitTestCase {
	/**
	 * Administrator ID.
	 *
	 * @var int
	 */
	protected static $admin_id;

	/** Install calendar tables. */
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

	/** Reset the current user and request. */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::$admin_id );
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
	}

	/** Clear request state. */
	public function tear_down() {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		parent::tear_down();
	}

	/**
	 * Only authorized groups and keys can be updated.
	 *
	 * @dataProvider context_provider
	 *
	 * @param string $capability Required capability.
	 * @param string $handler Update function.
	 * @param string $allowed_key Allowed setting.
	 * @param mixed  $value New value.
	 * @param string $other_key Disallowed setting.
	 */
	public function test_context_permissions_and_atomic_key_allowlist( $capability, $handler, $allowed_key, $value, $other_key ) {
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user );
		$before = get_option( 'my_calendar_options' );
		$this->assertFalse( $handler( array( $allowed_key => $value ) ) );
		$this->assertSame( $before, get_option( 'my_calendar_options' ) );
		get_user_by( 'id', $user )->add_cap( $capability );
		wp_set_current_user( 0 );
		wp_set_current_user( $user );
		$this->assertFalse(
			$handler(
				array(
					$allowed_key => $value,
					$other_key   => 'forbidden',
				)
			)
		);
		$this->assertSame( $before, get_option( 'my_calendar_options' ) );
		$this->assertTrue( $handler( array( $allowed_key => $value ) ) );
		$this->assertSame( $value, get_option( 'my_calendar_options' )[ $allowed_key ] );
		$this->assertTrue( $handler( array( $allowed_key => $value ) ) );
		$this->assertFalse( mc_update_option( 'api_enabled', 'true' ) );
	}

	/**
	 * Provide setting group cases.
	 *
	 * @return array
	 */
	public function context_provider() {
		return array(
			'category'   => array( 'mc_edit_cats', 'mc_update_category_settings', 'default_category', 321, 'default_location' ),
			'location'   => array( 'mc_edit_locations', 'mc_update_location_settings', 'default_location', 321, 'default_category' ),
			'styles'     => array( 'mc_edit_styles', 'mc_update_design_settings', 'css_file', 'test.css', 'show_js' ),
			'behaviors'  => array( 'mc_edit_behaviors', 'mc_update_design_settings', 'show_js', 'test.js', 'css_file' ),
			'templates'  => array( 'mc_edit_templates', 'mc_update_design_settings', 'list_template', 'test-template', 'css_file' ),
			'generator'  => array( 'mc_view_help', 'mc_update_generator_settings', 'last_shortcode_main', 'my_calendar', 'api_key' ),
			'event view' => array( 'mc_add_events', 'mc_update_event_view_settings', 'default_admin_view', 'grid', 'api_key' ),
		);
	}

	/** Template patches must not modify unrelated entries. */
	public function test_core_template_patch_preserves_other_templates() {
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		get_user_by( 'id', $user )->add_cap( 'mc_edit_templates' );
		wp_set_current_user( $user );
		$before = get_option( 'my_calendar_options' );
		$this->assertFalse( mc_update_design_settings( array( 'templates' => array( 'title' => 'not allowed' ) ) ) );
		$this->assertFalse( mc_update_context_settings( array( 'templates' => array( 'title' => 'not allowed' ) ), 'design' ) );
		$this->assertSame( $before, get_option( 'my_calendar_options' ) );
		$this->assertTrue(
			mc_update_design_settings(
				array(
					'templates'         => array( 'grid' => 'updated grid' ),
					'use_grid_template' => 1,
				)
			)
		);
		$after = get_option( 'my_calendar_options' );
		$this->assertSame( 'updated grid', $after['templates']['grid'] );
		foreach ( $before['templates'] as $key => $value ) {
			if ( 'grid' !== $key ) {
				$this->assertSame( $value, $after['templates'][ $key ] );
			}
		}
	}

	/** Delegated category managers can update default access terms. */
	public function test_category_and_location_default_access_from_handlers() {
		$user        = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$user_object = get_user_by( 'id', $user );
		$user_object->add_cap( 'mc_edit_cats' );
		$user_object->add_cap( 'mc_edit_locations' );
		wp_set_current_user( $user );
		$this->assertTrue( mc_update_default_access_terms( 321 ) );
		$_GET['terms'] = 'locations';
		$this->assertTrue( mc_update_default_access_terms( 322 ) );
		$options = get_option( 'my_calendar_options' );
		$this->assertContains( 321, $options['default_access_terms'] );
		$this->assertContains( 322, $options['default_location_terms'] );
		$this->assertTrue( mc_update_location_settings( array( 'default_location' => 123 ) ) );
		$this->assertSame( 123, get_option( 'my_calendar_options' )['default_location'] );
	}

	/** Delegated category managers can set defaults using the editor. */
	public function test_category_manager_can_set_default_through_edit_form() {
		$category = mc_create_category(
			array(
				'category_name'  => 'Delegated category',
				'category_color' => '#123456',
				'category_icon'  => 'event.svg',
			)
		);
		$user     = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		get_user_by( 'id', $user )->add_cap( 'mc_edit_cats' );
		wp_set_current_user( $user );
		$_POST    = array(
			'mode'                => 'edit',
			'category_id'         => $category,
			'category_name'       => 'Delegated category',
			'category_color'      => '#123456',
			'category_icon'       => 'event.svg',
			'mc_default_category' => '1',
		);
		$_REQUEST = array( '_wpnonce' => wp_create_nonce( 'my-calendar-nonce' ) );
		ob_start();
		my_calendar_manage_categories();
		ob_end_clean();
		$this->assertSame( $category, get_option( 'my_calendar_options' )['default_category'] );
	}

	/** Delegated style editors can save using the style editor. */
	public function test_style_editor_saves_with_style_capability_only() {
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		get_user_by( 'id', $user )->add_cap( 'mc_edit_styles' );
		wp_set_current_user( $user );
		$_POST    = array(
			'mc_edit_style' => '1',
			'mc_show_css'   => 'delegated.css',
			'use_styles'    => '1',
		);
		$_REQUEST = array( '_wpnonce' => wp_create_nonce( 'my-calendar-nonce' ) );
		my_calendar_update_styles();
		$options = get_option( 'my_calendar_options' );
		$this->assertSame( 'delegated.css', $options['show_css'] );
		$this->assertSame( 'true', $options['use_styles'] );
	}

	/** Location managers can set defaults from the location manager. */
	public function test_location_manager_can_set_default() {
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		get_user_by( 'id', $user )->add_cap( 'mc_edit_locations' );
		wp_set_current_user( $user );
		$_GET = array(
			'default'  => 123,
			'_mcnonce' => wp_create_nonce( 'mcnonce' ),
		);
		ob_start();
		my_calendar_manage_locations();
		$output = ob_get_clean();
		$this->assertSame( 123, get_option( 'my_calendar_options' )['default_location'] );
		$this->assertStringContainsString( 'Default Location Changed', $output );
	}

	/** Template editors can save core templates without editing general settings. */
	public function test_core_template_editor_saves() {
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		get_user_by( 'id', $user )->add_cap( 'mc_edit_templates' );
		wp_set_current_user( $user );
		$_GET     = array( 'page' => 'my-calendar-design' );
		$_POST    = array(
			'mc_template_key' => 'grid',
			'mc_template'     => '<p>Updated template</p>',
			'mc_use_template' => '1',
		);
		$_REQUEST = array( '_wpnonce' => wp_create_nonce( 'my-calendar-nonce' ) );
		add_filter( 'wp_redirect', '__return_false' );
		try {
			mc_templates_do_edit();
		} finally {
			remove_filter( 'wp_redirect', '__return_false' );
		}
		$options = get_option( 'my_calendar_options' );
		$this->assertSame( '<p>Updated template</p>', $options['templates']['grid'] );
		$this->assertSame( 1, $options['use_grid_template'] );
	}

	/** Script editors can save without general settings permission. */
	public function test_behavior_editor_saves() {
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		get_user_by( 'id', $user )->add_cap( 'mc_edit_behaviors' );
		wp_set_current_user( $user );
		$_POST    = array(
			'mc-js-save'  => '1',
			'calendar_js' => '1',
			'list_js'     => '1',
			'mini_js'     => '1',
			'ajax_js'     => '1',
			'mc_show_js'  => 'delegated.js',
		);
		$_REQUEST = array( '_wpnonce' => wp_create_nonce( 'my-calendar-nonce' ) );
		add_filter( 'wp_redirect', '__return_false' );
		try {
			my_calendar_behaviors_save();
		} finally {
			remove_filter( 'wp_redirect', '__return_false' );
		}
		$this->assertSame( 'delegated.js', get_option( 'my_calendar_options' )['show_js'] );
	}

	/**
	 * Style saves require both permission and a valid nonce.
	 *
	 * @dataProvider denied_style_provider
	 * @param bool $has_capability Whether the user can edit styles.
	 * @param bool $valid_nonce Whether the request has a valid nonce.
	 */
	public function test_style_editor_rejects_unauthorized_requests( $has_capability, $valid_nonce ) {
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		if ( $has_capability ) {
			get_user_by( 'id', $user )->add_cap( 'mc_edit_styles' );
		}
		wp_set_current_user( $user );
		$before   = get_option( 'my_calendar_options' );
		$_POST    = array(
			'mc_edit_style' => '1',
			'mc_show_css'   => 'forbidden.css',
		);
		$_REQUEST = array( '_wpnonce' => $valid_nonce ? wp_create_nonce( 'my-calendar-nonce' ) : 'invalid' );
		try {
			my_calendar_update_styles();
			$this->fail( 'The request should have been rejected.' );
		} catch ( WPDieException $exception ) {
			$this->assertNotEmpty( $exception->getMessage() );
		}
		$this->assertSame( $before, get_option( 'my_calendar_options' ) );
	}

	/**
	 * Provide rejected style-save cases.
	 *
	 * @return array
	 */
	public function denied_style_provider() {
		return array(
			'no capability' => array( false, true ),
			'invalid nonce' => array( true, false ),
		);
	}

	/** View settings cannot be used to store arbitrary values. */
	public function test_event_view_requires_supported_value() {
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		get_user_by( 'id', $user )->add_cap( 'mc_add_events' );
		wp_set_current_user( $user );
		$before = get_option( 'my_calendar_options' );
		$this->assertFalse( mc_update_event_view_settings( array( 'default_admin_view' => 'invalid' ) ) );
		$this->assertFalse( mc_update_context_settings( array( 'default_admin_view' => 'invalid' ), 'event_view' ) );
		$this->assertSame( $before, get_option( 'my_calendar_options' ) );
	}

	/** Shortcode generator users can save their most recent shortcode. */
	public function test_shortcode_generator_saves_with_help_capability() {
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		get_user_by( 'id', $user )->add_cap( 'mc_view_help' );
		wp_set_current_user( $user );
		$_POST  = array(
			'shortcode'   => 'main',
			'_mc_wpnonce' => wp_create_nonce( 'my-calendar-generator' ),
		);
		$output = mc_generate( 'array' );
		$this->assertStringContainsString( 'my_calendar', $output['shortcode'] );
		$this->assertStringContainsString( 'my_calendar', get_option( 'my_calendar_options' )['last_shortcode_main'] );
	}
}
