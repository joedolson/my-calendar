<?php
/**
 * Tests for mc_str_replace_word_i() search term wrapping.
 *
 * @package MyCalendar
 */

/**
 * Covers how search terms are wrapped in text.
 */
class Tests_My_Calendar_Search_Term_Wrapping extends WP_UnitTestCase {
	/**
	 * Wrap text the same way the function does.
	 *
	 * @param string $text Text to wrap.
	 *
	 * @return string
	 */
	protected function wrap( $text ) {
		return '<strong class="mc_search_term">' . $text . '</strong>';
	}

	/**
	 * Verify wrapping for terms made of word characters, spaces, and inner punctuation.
	 *
	 * @dataProvider wrapped_terms_provider
	 *
	 * @param string $needle Search term.
	 * @param string $haystack Source text.
	 * @param string $expected Expected result.
	 */
	public function test_wraps_matching_terms( $needle, $haystack, $expected ) {
		$this->assertSame( $expected, mc_str_replace_word_i( $needle, $haystack ) );
	}

	/**
	 * Provide terms that should be wrapped.
	 *
	 * @return array
	 */
	public function wrapped_terms_provider() {
		$w = array( $this, 'wrap' );

		return array(
			'lowercase term, mixed case text'            => array(
				'festival',
				'Spring FESTIVAL and Festival',
				'Spring ' . $w( 'FESTIVAL' ) . ' and ' . $w( 'Festival' ),
			),
			'mixed case term, lowercase text'            => array(
				'FeStIvAl',
				'festival',
				$w( 'festival' ),
			),
			'original case of text is preserved'         => array(
				'concert',
				'CONCERT Concert concert',
				$w( 'CONCERT' ) . ' ' . $w( 'Concert' ) . ' ' . $w( 'concert' ),
			),
			'term with a space'                          => array(
				'spring festival',
				'Join the Spring Festival today.',
				'Join the ' . $w( 'Spring Festival' ) . ' today.',
			),
			'term with a space needs exact spacing'      => array(
				'spring festival',
				'spring  festival',
				'spring  festival',
			),
			'term is the entire text'                    => array(
				'festival',
				'festival',
				$w( 'festival' ),
			),
			'repeated term'                              => array(
				'rock',
				'rock and rock',
				$w( 'rock' ) . ' and ' . $w( 'rock' ),
			),
			'term at start and end of text'              => array(
				'jazz',
				'jazz night with jazz',
				$w( 'jazz' ) . ' night with ' . $w( 'jazz' ),
			),
			'adjacent punctuation is not wrapped'        => array(
				'rock',
				'Rock, rock. rock! (rock) "rock"',
				$w( 'Rock' ) . ', ' . $w( 'rock' ) . '. ' . $w( 'rock' ) . '! (' . $w( 'rock' ) . ') "' . $w( 'rock' ) . '"',
			),
			'hyphenated term'                            => array(
				'rock-n-roll',
				'A rock-n-roll night',
				'A ' . $w( 'rock-n-roll' ) . ' night',
			),
			'term followed by hyphenated text'           => array(
				'rock',
				'rock-n-roll',
				$w( 'rock' ) . '-n-roll',
			),
			'apostrophe in term'                         => array(
				"o'clock",
				"Meet at 5 o'clock sharp",
				'Meet at 5 ' . $w( "o'clock" ) . ' sharp',
			),
			'slash in term'                              => array(
				'a/b',
				'Use a/b today',
				'Use ' . $w( 'a/b' ) . ' today',
			),
			'dot is literal, not a wildcard'             => array(
				'a.b',
				'a.b and aXb',
				$w( 'a.b' ) . ' and aXb',
			),
			'decimal number'                             => array(
				'5.5',
				'Version 5.5 and 585',
				'Version ' . $w( '5.5' ) . ' and 585',
			),
			'number is not matched inside longer number' => array(
				'2026',
				'Year 2026, 20260',
				'Year ' . $w( '2026' ) . ', 20260',
			),
			'non-ASCII word ending in ASCII'             => array(
				'naïve',
				'A naïve plan',
				'A ' . $w( 'naïve' ) . ' plan',
			),
			'inner word of a shortcode is wrapped'       => array(
				'gallery',
				'See [gallery id="1"] here',
				'See [' . $w( 'gallery' ) . ' id="1"] here',
			),
			'shortcode attribute name is wrapped'        => array(
				'id',
				'See [gallery id="1"] here',
				'See [gallery ' . $w( 'id' ) . '="1"] here',
			),
		);
	}

	/**
	 * Verify partial-word matches are not wrapped.
	 *
	 * @dataProvider unmatched_terms_provider
	 *
	 * @param string $needle Search term.
	 * @param string $haystack Source text.
	 */
	public function test_leaves_text_unchanged_without_whole_word_match( $needle, $haystack ) {
		$this->assertSame( $haystack, mc_str_replace_word_i( $needle, $haystack ) );
	}

	/**
	 * Provide terms that should not be wrapped.
	 *
	 * @return array
	 */
	public function unmatched_terms_provider() {
		return array(
			'term not present'           => array( 'term', 'No match here' ),
			'empty text'                 => array( 'term', '' ),
			'term is prefix of word'     => array( 'rock', 'rockstar' ),
			'term is suffix of word'     => array( 'star', 'rockstar' ),
			'term is inside word'        => array( 'cks', 'rockstar' ),
			'plural is not matched'      => array( 'festival', 'festivals are fun' ),
			'dot does not match letters' => array( 'a.b', 'aXb' ),
			'only partial multi-word'    => array( 'spring festival', 'spring' ),
		);
	}

	/**
	 * Verify terms inside HTML tags and attributes are not wrapped, but text content is.
	 */
	public function test_does_not_wrap_inside_html_tags() {
		$this->assertSame(
			'<a href="/fun">' . $this->wrap( 'fun' ) . '</a> <span class="fun">' . $this->wrap( 'fun' ) . '</span>',
			mc_str_replace_word_i( 'fun', '<a href="/fun">fun</a> <span class="fun">fun</span>' )
		);
		$this->assertSame(
			'<a href="x" title="link">' . $this->wrap( 'link' ) . '</a>',
			mc_str_replace_word_i( 'link', '<a href="x" title="link">link</a>' )
		);
		$this->assertSame(
			'<img src="rock.jpg" alt="rock" />',
			mc_str_replace_word_i( 'rock', '<img src="rock.jpg" alt="rock" />' )
		);
	}

	/**
	 * Verify regex metacharacters inside a term are treated literally and do not error.
	 *
	 * @dataProvider metacharacter_provider
	 *
	 * @param string $needle Search term.
	 */
	public function test_regex_metacharacters_do_not_cause_errors( $needle ) {
		$haystack = 'Plain text with word and other words.';
		$result   = mc_str_replace_word_i( $needle, $haystack );

		$this->assertIsString( $result );
		$this->assertSame( $haystack, $result );
	}

	/**
	 * Provide terms containing regex metacharacters.
	 *
	 * @return array
	 */
	public function metacharacter_provider() {
		return array(
			'unclosed bracket' => array( '[abc' ),
			'unclosed paren'   => array( '(abc' ),
			'star'             => array( '*word' ),
			'plus'             => array( '+word' ),
			'question mark'    => array( '?word' ),
			'pipe'             => array( 'x|word' ),
			'backslash'        => array( 'a\\b' ),
			'caret and dollar' => array( '^word$' ),
			'curly braces'     => array( 'a{2,3}' ),
		);
	}

	/**
	 * Current behavior: terms that begin or end with a non-word character are never wrapped,
	 * because the pattern requires a word boundary at both ends. This includes bracketed shortcodes.
	 *
	 * @dataProvider non_word_edge_provider
	 *
	 * @param string $needle Search term.
	 * @param string $haystack Source text.
	 */
	public function test_terms_with_non_word_edges_are_not_wrapped( $needle, $haystack ) {
		$this->assertSame( $haystack, mc_str_replace_word_i( $needle, $haystack ) );
	}

	/**
	 * Provide terms with non-word characters at the start or end.
	 *
	 * @return array
	 */
	public function non_word_edge_provider() {
		return array(
			'full shortcode'            => array( '[gallery]', 'See [gallery] here' ),
			'shortcode with attributes' => array( '[gallery id="1"]', 'See [gallery id="1"] here' ),
			'opening bracket only'      => array( '[gallery', 'See [gallery] here' ),
			'closing bracket only'      => array( 'gallery]', 'See [gallery] here' ),
			'trailing plus signs'       => array( 'C++', 'Learn C++ now' ),
			'leading currency symbol'   => array( '$5', 'Cost $5 today' ),
			'wrapped in parentheses'    => array( '(test)', 'a (test) b' ),
		);
	}

	/**
	 * Verify matching is multibyte-aware: accented terms match, case-insensitively.
	 */
	public function test_non_ascii_terms_match_case_insensitively() {
		$this->assertSame( 'Visit the ' . $this->wrap( 'café' ) . ' today', mc_str_replace_word_i( 'Café', 'Visit the café today' ) );
		$this->assertSame( $this->wrap( 'CAFÉ' ), mc_str_replace_word_i( 'café', 'CAFÉ' ) );
		$this->assertSame( $this->wrap( 'Ünïcödé' ) . ' text', mc_str_replace_word_i( 'ünïcödé', 'Ünïcödé text' ) );
		$this->assertSame( $this->wrap( '東京' ) . ' event', mc_str_replace_word_i( '東京', '東京 event' ) );
	}

	/**
	 * Verify accented letters count as word characters for whole-word matching.
	 */
	public function test_non_ascii_letters_are_word_characters() {
		$this->assertSame( 'cafés', mc_str_replace_word_i( 'caf', 'cafés' ) );
		$this->assertSame( 'élan', mc_str_replace_word_i( 'lan', 'élan' ) );
	}

	/**
	 * Verify empty or whitespace-only terms leave the text unchanged.
	 *
	 * @dataProvider empty_term_provider
	 *
	 * @param string $needle Search term.
	 */
	public function test_empty_terms_leave_text_unchanged( $needle ) {
		$haystack = 'Some <b>bold</b> text';

		$this->assertSame( $haystack, mc_str_replace_word_i( $needle, $haystack ) );
	}

	/**
	 * Provide empty search terms.
	 *
	 * @return array
	 */
	public function empty_term_provider() {
		return array(
			'empty string'    => array( '' ),
			'single space'    => array( ' ' ),
			'tabs and spaces' => array( " \t " ),
		);
	}

	/**
	 * Verify invalid UTF-8 text is returned unchanged instead of being emptied.
	 */
	public function test_invalid_utf8_text_is_returned_unchanged() {
		$haystack = "Bad \xB1\x31 text with word";

		$this->assertSame( $haystack, mc_str_replace_word_i( 'word', $haystack ) );
	}
}
