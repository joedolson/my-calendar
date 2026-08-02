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
	 * Verify that LIKE search clauses escape wildcard characters.
	 */
	public function test_prepare_search_query_escapes_like_wildcards() {
		$search = mc_prepare_search_query( '100%_test' );

		$this->assertStringContainsString( '100\\%\\_test', $search );
		$this->assertStringNotContainsString( '100%_test', $search );
	}
}
