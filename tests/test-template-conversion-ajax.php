<?php
/**
 * AJAX tests for legacy template conversion.
 *
 * @package MyCalendar
 */

/**
 * Exercise the authenticated template conversion endpoint.
 */
class Tests_My_Calendar_Template_Conversion_Ajax extends WP_Ajax_UnitTestCase {
	/**
	 * Set up a user who can edit templates.
	 */
	public function set_up() {
		parent::set_up();
		$user = self::factory()->user->create_and_get( array( 'role' => 'subscriber' ) );
		$user->add_cap( 'mc_edit_templates' );
		wp_set_current_user( $user->ID );
	}

	/**
	 * Dispatch a conversion request and return its JSON response.
	 *
	 * @param mixed  $key Template key.
	 * @param string $nonce Optional nonce.
	 * @return array
	 */
	private function convert( $key, $nonce = '' ) {
		$_POST = array(
			'key'   => $key,
			'nonce' => $nonce ? $nonce : wp_create_nonce( 'mc-convert-template' ),
		);
		try {
			$this->_handleAjax( 'mc_convert_template' );
		} catch ( WPAjaxDieContinueException $exception ) {
			return json_decode( $this->_last_response, true );
		}
		$this->fail( 'The AJAX request did not terminate with a JSON response.' );
	}

	/**
	 * Core templates are loaded from settings and converted with their own type.
	 */
	public function test_converts_saved_core_template() {
		$options                      = mc_get_options();
		$options['templates']['grid'] = addslashes( '{title format="Y"}' );
		update_option( 'my_calendar_options', $options );

		$response = $this->convert( 'grid' );

		$this->assertTrue( $response['success'] );
		$this->assertSame( '{title format="Y"}', $response['data']['original'] );
		$this->assertSame( 'event/calendar.php', $response['data']['file'] );
		$this->assertStringContainsString( "mc_template_tag( \$data, 'title' )", $response['data']['converted'] );
		$this->assertSame( 'format', $response['data']['discarded'][0]['attribute'] );
	}

	/**
	 * A saved custom template uses the upcoming event template target.
	 */
	public function test_converts_saved_custom_template() {
		$key = md5( 'ajax custom template' );
		update_option( 'mc_ctemplate_' . $key, '<p>{title}</p>' );

		$response = $this->convert( $key );

		$this->assertTrue( $response['success'] );
		$this->assertSame( '<p>{title}</p>', $response['data']['original'] );
		$this->assertSame( 'event/upcoming.php', $response['data']['file'] );
		$this->assertSame( array(), $response['data']['discarded'] );
	}

	/**
	 * Missing template keys are rejected.
	 */
	public function test_rejects_missing_template() {
		$response = $this->convert( 'missing' );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Template not found.', $response['data']['message'] );
	}

	/**
	 * Non-string template keys are rejected.
	 */
	public function test_rejects_malformed_key() {
		$response = $this->convert( array( 'grid' ) );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Template not found.', $response['data']['message'] );
	}

	/**
	 * An empty saved template cannot be converted.
	 */
	public function test_rejects_empty_template() {
		$options                      = mc_get_options();
		$options['templates']['grid'] = '';
		update_option( 'my_calendar_options', $options );

		$response = $this->convert( 'grid' );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'This template is empty or invalid.', $response['data']['message'] );
	}

	/**
	 * A bad nonce cannot request a conversion.
	 */
	public function test_rejects_invalid_nonce() {
		$response = $this->convert( 'grid', 'invalid' );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'You are not authorized to convert templates.', $response['data']['message'] );
	}

	/**
	 * Conversion requires the template-editing capability.
	 */
	public function test_rejects_user_without_capability() {
		wp_get_current_user()->remove_cap( 'mc_edit_templates' );
		$response = $this->convert( 'grid' );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'You are not authorized to convert templates.', $response['data']['message'] );
	}
}
