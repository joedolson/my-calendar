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
	function test_my_calendar_sanitized_output() {
		$output    = my_calendar( array() );
		$sanitized = wp_kses( $output . '-', mc_kses_elements() );

		$this->assertSame( $output, $sanitized );
	}
}
