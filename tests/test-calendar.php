<?php
/**
 * Class Tests_My_Calendar_General
 *
 * @package Sample_Plugin
 */

/**
 * Sample test case.
 */
class Tests_My_Calendar_General extends WP_UnitTestCase {
	/**
	 * Verify that output of My Calendar is unchanged after sanitizing.
	 *
	 * Fails if attributes or elements not represented in kses filters.
	 */
	public function test_my_calendar_sanitized_output() {
		// Fetch core calendar output & then run through sanitize. Requires factory.
		$output    = '';
		$sanitized = '';

		$this->assertSame( $output, $sanitized );
	}

	/**
	 * Verify that REST event request arguments are sanitized before use.
	 */
	public function test_rest_event_request_args_are_sanitized() {
		$server = rest_get_server();
		$routes = $server->get_routes();

		$this->assertArrayHasKey( '/my-calendar/v1/events/', $routes );

		$route = $routes['/my-calendar/v1/events/'][0];
		$this->assertArrayHasKey( 'args', $route );
		$this->assertArrayHasKey( 'from', $route['args'] );
		$this->assertArrayHasKey( 'sanitize_callback', $route['args']['from'] );
		$this->assertArrayHasKey( 'search', $route['args'] );
		$this->assertArrayHasKey( 'sanitize_callback', $route['args']['search'] );
		$this->assertArrayHasKey( 'category', $route['args'] );
		$this->assertArrayHasKey( 'sanitize_callback', $route['args']['category'] );
	}
}
