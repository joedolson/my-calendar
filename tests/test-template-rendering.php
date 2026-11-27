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

		$this->assertSame( '<h2>Tom &amp; Jerry &lt;3</h2><span>abc&amp;123</span>', mc_draw_template( $data, $template, 'single' ) );
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

	/**
	 * Core and custom template lists retain their conversion buttons after sanitization.
	 */
	public function test_template_lists_include_conversion_buttons() {
		$key = md5( 'conversion button test' );
		update_option( 'mc_ctemplate_' . $key, '{title}' );
		update_option( 'mc_template_desc_' . $key, 'Conversion button test' );

		$core   = wp_kses( mc_list_core_templates(), mc_kses_elements() );
		$custom = wp_kses( mc_list_custom_templates(), mc_kses_elements() );

		foreach ( array( 'grid', 'list', 'mini', 'card', 'details' ) as $type ) {
			$this->assertStringContainsString( '<button type="button" class="button button-compact mc-convert-template" data-action="' . $type . '">' . __( 'Convert to PHP', 'my-calendar' ) . '</button>', $core );
		}
		$this->assertStringContainsString( '<button type="button" class="button button-compact mc-convert-template" data-action="' . $key . '">' . __( 'Convert to PHP', 'my-calendar' ) . '</button>', $custom );
		$this->assertStringNotContainsString( 'mc_convert=', $core . $custom );
	}

	/**
	 * Render generated PHP with the same data object supplied to event templates.
	 *
	 * @param string      $template Generated PHP source.
	 * @param array       $tags Event tags.
	 * @param object|null $event Optional event data.
	 * @return string
	 */
	private function render_converted_template( $template, $tags, $event = null ) {
		$data = (object) array(
			'tags'  => $tags,
			'event' => $event,
		);
		ob_start();
		try {
			eval( '?>' . $template ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- Exercise the generated PHP template.
			return ob_get_contents();
		} finally {
			ob_end_clean();
		}
	}

	/**
	 * Each legacy type identifies the corresponding event template file.
	 */
	public function test_convert_legacy_template_maps_types() {
		$files = array(
			'grid'    => 'event/calendar.php',
			'list'    => 'event/list.php',
			'mini'    => 'event/mini.php',
			'card'    => 'event/card.php',
			'details' => 'event/single.php',
			'custom'  => 'event/upcoming.php',
		);
		foreach ( $files as $type => $file ) {
			$result = mc_convert_legacy_template( '<p>{title}</p>', $type );
			$this->assertSame( '<p>{title}</p>', $result['original'] );
			$this->assertSame( $file, $result['file'] );
			$this->assertSame( array(), $result['discarded'] );
			$this->assertSame( '<p>Festival</p>', $this->render_converted_template( $result['converted'], array( 'title' => 'Festival' ) ) );
		}
		$this->assertWPError( mc_convert_legacy_template( '', 'unknown' ) );
	}

	/**
	 * Wrappers only appear for nonblank values; zero is meaningful content.
	 */
	public function test_convert_legacy_template_conditionally_renders_wrappers() {
		$template = '{title}|{city before="<b>" after="</b>"}|{custom before="(" after=")"}';
		$result   = mc_convert_legacy_template( $template, 'list' );

		$this->assertSame(
			' Festival |<b>Denver</b>|(0)',
			$this->render_converted_template(
				$result['converted'],
				array(
					'title'  => ' Festival ',
					'city'   => 'Denver',
					'custom' => '0',
				)
			)
		);
		$this->assertSame(
			' ||',
			$this->render_converted_template(
				$result['converted'],
				array(
					'title'  => ' ',
					'city'   => ' ',
					'custom' => '',
				)
			)
		);
	}

	/**
	 * Generated markup uses double-quoted attributes without multiline PHP literals.
	 */
	public function test_convert_legacy_template_normalizes_markup_and_line_breaks() {
		$template = "\n<span class=\"event-time value-title\">{time}</span>\n{image before=\"<div class='mc-event-image'>\" after=\"</div>\"}\n";
		$result   = mc_convert_legacy_template( $template, 'grid' );

		$this->assertSame( $template, $result['original'] );
		$this->assertStringContainsString( 'echo \'<span class="event-time value-title">\';', $result['converted'] );
		$this->assertStringContainsString( 'echo \'<div class="mc-event-image">\';', $result['converted'] );
		$this->assertStringNotContainsString( "echo '\n", $result['converted'] );
		$this->assertSame(
			"<span class=\"event-time value-title\">10:00</span>\n<div class=\"mc-event-image\">Image</div>",
			$this->render_converted_template(
				$result['converted'],
				array(
					'time'  => '10:00',
					'image' => 'Image',
				)
			)
		);
	}

	/**
	 * Quotes inside attribute values and meaningful line breaks remain intact.
	 */
	public function test_convert_legacy_template_preserves_attribute_values_and_text() {
		$template = "<p title='She said \"yes\"' data-name='{title}'>Joe's {title}</p>\n<pre>line 1\nline 2</pre>";
		$result   = mc_convert_legacy_template( $template, 'list' );

		$this->assertStringContainsString( 'title="She said &quot;yes&quot;" data-name="', $result['converted'] );
		$this->assertStringContainsString( '"\n"', $result['converted'] );
		$this->assertSame(
			"<p title=\"She said &quot;yes&quot;\" data-name=\"Event\">Joe's Event</p>\n<pre>line 1\nline 2</pre>",
			$this->render_converted_template( $result['converted'], array( 'title' => 'Event' ) )
		);
	}

	/**
	 * Placeholder modifiers in attributes and original line endings remain valid.
	 */
	public function test_convert_legacy_template_preserves_complex_attributes_and_line_endings() {
		$template = "<p title='{title before=\"[\" after=\"]\"}'>Joe's event</p>\r\n<pre>first\rsecond\nthird</pre><!-- <span class='example'> -->";
		$result   = mc_convert_legacy_template( $template, 'list' );

		$this->assertStringContainsString( 'mc_get_template_tag( $data, \'title\' )', $result['converted'] );
		$this->assertStringContainsString( '<!-- <span class=\\\'example\\\'> -->', $result['converted'] );
		$this->assertStringContainsString( '"\r\n"', $result['converted'] );
		$this->assertStringContainsString( '"\r"', $result['converted'] );
		$this->assertStringContainsString( '"\n"', $result['converted'] );
		$this->assertStringContainsString(
			"Joe's event</p>\r\n<pre>first\rsecond\nthird</pre>",
			$this->render_converted_template( $result['converted'], array( 'title' => 'Event' ) )
		);
	}

	/**
	 * Date formats are applied and discarded modifiers are reported.
	 */
	public function test_convert_legacy_template_formats_dates_and_reports_discarded_modifiers() {
		$template = '{date format="Y-m-d" before="[" after="]"} {time format="H:i"} {title format="Y" extra="yes"}';
		$result   = mc_convert_legacy_template( $template, 'grid' );

		$this->assertSame(
			array(
				array(
					'tag'       => 'title',
					'attribute' => 'extra',
					'value'     => 'yes',
				),
				array(
					'tag'       => 'title',
					'attribute' => 'format',
					'value'     => 'Y',
				),
			),
			$result['discarded']
		);
		$this->assertSame(
			'[2024-03-14] 09:30 Festival',
			$this->render_converted_template(
				$result['converted'],
				array(
					'date'  => '2024-03-14',
					'time'  => '09:30:00',
					'title' => 'Festival',
				)
			)
		);
	}

	/**
	 * Localized date tags use the event's occurrence date rather than guessing its format.
	 */
	public function test_convert_legacy_template_formats_localized_dates() {
		$result = mc_convert_legacy_template( '{date format="Y-m-d"}', 'list' );
		$event  = (object) array( 'occur_begin' => '2024-03-14 09:30:00' );

		$this->assertSame( '2024-03-14', $this->render_converted_template( $result['converted'], array( 'date' => '14/03/2024' ), $event ) );
		$event->occur_begin = '2024-04-03 09:30:00';
		$this->assertSame( '2024-04-03', $this->render_converted_template( $result['converted'], array( 'date' => '03/04/2024' ), $event ) );
	}

	/**
	 * Literal markup and attribute values cannot become executable PHP.
	 */
	public function test_convert_legacy_template_keeps_literal_php_as_text() {
		$template = '<?php $GLOBALS["mc_converter_executed"] = true; ?> {title before="it\'s [" after="]"}';
		$result   = mc_convert_legacy_template( $template, 'custom' );

		$GLOBALS['mc_converter_executed'] = false;
		$output                           = $this->render_converted_template( $result['converted'], array( 'title' => 'Event' ) );
		$this->assertFalse( $GLOBALS['mc_converter_executed'] );
		$this->assertStringContainsString( 'it\'s [Event]', $output );
		unset( $GLOBALS['mc_converter_executed'] );
	}

	/**
	 * Converted templates retain the legacy renderer's HTML sanitization.
	 */
	public function test_convert_legacy_template_sanitizes_output() {
		$result = mc_convert_legacy_template( '<script>alert(1)</script><p>{title}</p>', 'list' );
		$output = $this->render_converted_template( $result['converted'], array( 'title' => 'Event' ) );

		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( '<p>Event</p>', $output );
	}
}
