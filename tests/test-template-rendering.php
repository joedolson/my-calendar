<?php
/**
 * Template rendering tests.
 *
 * @package MyCalendar
 */

/**
 * Covers mc_draw_template() and related functions.
 */
class Tests_My_Calendar_Template_Rendering extends WP_UnitTestCase {
	/**
	 * Verify simple tags are replaced with scalar values.
	 */
	public function test_draw_template_replaces_simple_tags() {
		$data = array(
			'title' => 'Spring Festival',
			'city'  => 'Seattle',
			'state' => 'WA',
		);

		$template = '<article><h2>{title}</h2><p>{city}, {state}</p></article>';

		$this->assertSame( '<article><h2>Spring Festival</h2><p>Seattle, WA</p></article>', mc_draw_template( $data, $template, 'list' ) );
	}

	/**
	 * Verify before/after and format modifiers are supported.
	 */
	public function test_draw_template_supports_modifiers_and_formatting() {
		$data = array(
			'event_date' => '2024-03-14 09:30:00',
			'city'       => 'Denver',
		);

		$template = '<div>{event_date format="Y-m-d" before="<strong>" after="</strong>"} - {city before="(" after=")"}</div>';

		$this->assertSame( '<div><strong>2024-03-14</strong> - (Denver)</div>', mc_draw_template( $data, $template, 'list' ) );
	}

	/**
	 * Verify non-list templates escape values before inserting them.
	 */
	public function test_draw_template_escapes_values_in_non_list_views() {
		$data     = array(
			'title' => 'Tom & Jerry <3',
			'guid'  => 'abc&123',
		);
		$template = '<h2>{title}</h2><span>{guid}</span>';

		$this->assertSame( '<h2>Tom &amp; Jerry &lt;3</h2><span>abc&123</span>', mc_draw_template( $data, $template, 'single' ) );
	}

	/**
	 * Verify empty data and templates without placeholders are handled safely.
	 */
	public function test_draw_template_handles_empty_input() {
		// Empty data with placeholders should result in empty strings for those placeholders.
		$this->assertSame( 'Plain text only', mc_draw_template( array( 'title' => '' ), 'Plain text only {title}', 'list' ) );
		// Templates without placeholders should be returned as-is, regardless of data.
		$this->assertSame( 'Plain text only', mc_draw_template( array(), 'Plain text only', 'list' ) );
		$this->assertSame( 'Plain text only', mc_draw_template( array( 'title' => 'Ignored' ), 'Plain text only', 'list' ) );
	}
}
