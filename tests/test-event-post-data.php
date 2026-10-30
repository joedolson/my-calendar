<?php
/**
 * Tests for mc_event_post_data() sanitization.
 *
 * @package MyCalendar
 */

/**
 * Covers request data handling and sanitization in mc_event_post_data().
 */
class Tests_My_Calendar_Event_Post_Data extends WP_UnitTestCase {
	/**
	 * Reset request state before each test.
	 */
	public function set_up() {
		parent::set_up();

		$_POST    = array();
		$_REQUEST = array();
	}

	/**
	 * Clean request state and filters after each test.
	 */
	public function tear_down() {
		remove_filter( 'wp_doing_ajax', '__return_true' );
		$_POST    = array();
		$_REQUEST = array();

		parent::tear_down();
	}

	/**
	 * Without POST data, an empty array is returned.
	 */
	public function test_empty_post_returns_empty_array() {
		$this->assertSame( array(), mc_event_post_data() );
	}

	/**
	 * Plain values pass through unchanged and keys are preserved.
	 */
	public function test_plain_values_are_unchanged() {
		$_POST = array(
			'event_title' => 'Spring Festival',
			'event_short' => 'A short description.',
			'event_link'  => 'https://example.com/event?a=1&b=2',
		);

		$data = mc_event_post_data();

		$this->assertSame( 'Spring Festival', $data['event_title'] );
		$this->assertSame( 'A short description.', $data['event_short'] );
		$this->assertStringStartsWith( 'https://example.com/event?a=1', $data['event_link'] );
		// phpcs:ignore WordPress.Security.NonceVerification
		$this->assertSame( array_keys( $_POST ), array_keys( $data ) );
	}

	/**
	 * Slashes added by WordPress to request data are removed.
	 */
	public function test_slashes_are_removed() {
		$_POST = array(
			'event_title' => 'It\\\'s a \\"quoted\\" title',
		);

		$data = mc_event_post_data();

		$this->assertSame( 'It\'s a "quoted" title', $data['event_title'] );
	}

	/**
	 * Script tags are removed from values.
	 */
	public function test_script_tags_are_removed() {
		$_POST = array(
			'event_title' => 'Title<script>alert(1)</script>',
			'content'     => '<p>Text</p><script src="https://example.com/x.js"></script>',
		);

		$data = mc_event_post_data();

		$this->assertStringNotContainsString( '<script', $data['event_title'] );
		$this->assertStringNotContainsString( '</script', $data['event_title'] );
		$this->assertStringStartsWith( 'Title', $data['event_title'] );
		$this->assertStringNotContainsString( '<script', $data['content'] );
		$this->assertStringContainsString( '<p>Text</p>', $data['content'] );
	}

	/**
	 * Event handler attributes and unsafe protocols are removed.
	 */
	public function test_unsafe_attributes_are_removed() {
		$_POST = array(
			'content' => '<a href="javascript:alert(1)" onclick="alert(2)">Link</a><img src="x.jpg" onerror="alert(3)" alt="x" />',
		);

		$data = mc_event_post_data();

		$this->assertStringNotContainsString( 'javascript:', $data['content'] );
		$this->assertStringNotContainsString( 'onclick', $data['content'] );
		$this->assertStringNotContainsString( 'onerror', $data['content'] );
		$this->assertStringContainsString( 'Link', $data['content'] );
	}

	/**
	 * Disallowed elements such as iframes, forms and style blocks are removed.
	 */
	public function test_disallowed_elements_are_removed() {
		$_POST = array(
			'content' => '<iframe src="https://example.com"></iframe><form action="/x"><input name="a" /></form><style>body{display:none}</style><p>Kept</p>',
		);

		$data = mc_event_post_data();

		$this->assertStringNotContainsString( '<iframe', $data['content'] );
		$this->assertStringNotContainsString( '<form', $data['content'] );
		$this->assertStringNotContainsString( '<input', $data['content'] );
		$this->assertStringNotContainsString( '<style', $data['content'] );
		$this->assertStringContainsString( '<p>Kept</p>', $data['content'] );
	}

	/**
	 * Markup allowed in post content is preserved.
	 */
	public function test_allowed_markup_is_preserved() {
		$_POST = array(
			'content' => '<p>Intro <strong>bold</strong> <em>italic</em> <a href="https://example.com/">link</a></p><ul><li>One</li></ul>',
		);

		$data = mc_event_post_data();

		$this->assertStringContainsString( '<strong>bold</strong>', $data['content'] );
		$this->assertStringContainsString( '<em>italic</em>', $data['content'] );
		$this->assertStringContainsString( '<a href="https://example.com/">link</a>', $data['content'] );
		$this->assertStringContainsString( '<ul><li>One</li></ul>', $data['content'] );
	}

	/**
	 * Nested arrays are sanitized at every depth.
	 */
	public function test_nested_arrays_are_sanitized() {
		$_POST = array(
			'event_begin'    => array( '2026-08-01', '2026-08-02<script>x()</script>' ),
			'event_category' => array(
				'primary' => 'One',
				'others'  => array( 'Two<script>y()</script>', 'It\\\'s' ),
			),
		);

		$data = mc_event_post_data();

		$this->assertSame( '2026-08-01', $data['event_begin'][0] );
		$this->assertStringNotContainsString( '<script', $data['event_begin'][1] );
		$this->assertStringStartsWith( '2026-08-02', $data['event_begin'][1] );
		$this->assertSame( 'One', $data['event_category']['primary'] );
		$this->assertStringNotContainsString( '<script', $data['event_category']['others'][0] );
		$this->assertSame( "It's", $data['event_category']['others'][1] );
	}

	/**
	 * Without an AJAX request, a "post" key in the data is treated like any other key.
	 */
	public function test_post_key_ignored_outside_ajax() {
		$_POST = array(
			'event_title' => 'Top level',
			'post'        => array( 'event_title' => 'Nested' ),
		);

		$data = mc_event_post_data();

		$this->assertSame( 'Top level', $data['event_title'] );
		$this->assertArrayHasKey( 'post', $data );
	}

	/**
	 * During AJAX requests, data is read from the "post" array and sanitized.
	 */
	public function test_ajax_reads_nested_post_array() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_POST = array(
			'action' => 'mc_ajax_action',
			'post'   => array(
				'event_title' => 'Ajax Title<script>alert(1)</script>',
				'event_short' => 'It\\\'s short',
			),
		);

		$data = mc_event_post_data();

		$this->assertArrayNotHasKey( 'action', $data );
		$this->assertStringStartsWith( 'Ajax Title', $data['event_title'] );
		$this->assertStringNotContainsString( '<script', $data['event_title'] );
		$this->assertSame( "It's short", $data['event_short'] );
	}

	/**
	 * During AJAX requests, a query string in "post" is parsed and sanitized.
	 */
	public function test_ajax_parses_query_string() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_POST = array(
			'action' => 'mc_ajax_action',
			'post'   => 'event_title=Parsed+Title%3Cscript%3Ealert(1)%3C%2Fscript%3E&event_begin%5B%5D=2026-08-01&event_begin%5B%5D=2026-08-02',
		);

		$data = mc_event_post_data();

		$this->assertStringStartsWith( 'Parsed Title', $data['event_title'] );
		$this->assertStringNotContainsString( '<script', $data['event_title'] );
		$this->assertSame( array( '2026-08-01', '2026-08-02' ), $data['event_begin'] );
	}

	/**
	 * During AJAX requests without a "post" value, no data is returned.
	 */
	public function test_ajax_without_post_value_returns_empty_array() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_POST = array( 'action' => 'mc_ajax_action' );

		$this->assertSame( array(), mc_event_post_data() );
	}

	/**
	 * The function reads request data only, and leaves $_POST untouched.
	 */
	public function test_does_not_modify_global_post() {
		$_POST    = array( 'event_title' => 'Title<script>alert(1)</script>' );
		$original = $_POST;

		mc_event_post_data();

		$this->assertSame( $original, $_POST );
	}
}
