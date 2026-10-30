<?php
/**
 * Tests for mc_build_url() when no root URL is passed.
 *
 * @package MyCalendar
 */

/**
 * Covers base URL selection and query argument handling in mc_build_url().
 */
class Tests_My_Calendar_Build_Url extends WP_UnitTestCase {
	/**
	 * Set up plugin data and pretty permalinks.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		mc_posttypes();
		mc_taxonomies();

		if ( ! get_option( 'my_calendar_options' ) || ! my_calendar_exists() ) {
			mc_initial_install();
		}
	}

	/**
	 * Reset request state before each test.
	 */
	public function set_up() {
		parent::set_up();

		$this->set_permalink_structure( '/%postname%/' );
		mc_posttypes();
		mc_taxonomies();
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
	}

	/**
	 * Clean request state and filters after each test.
	 */
	public function tear_down() {
		remove_all_filters( 'mc_build_url_root' );
		remove_all_filters( 'mc_build_url' );
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		wp_reset_postdata();

		parent::tear_down();
	}

	/**
	 * Build the escaped URL mc_build_url() is expected to return.
	 *
	 * @param string $base Base URL.
	 * @param array  $args Query arguments.
	 *
	 * @return string
	 */
	protected function expected( $base, $args ) {
		ksort( $args );

		return esc_url( add_query_arg( $args, $base ) );
	}

	/**
	 * Navigate to a URL and then apply request parameters.
	 *
	 * @param string $url URL to visit.
	 * @param array  $get Query parameters to expose in $_GET.
	 */
	protected function visit( $url, $get = array() ) {
		$this->go_to( $url );
		$_GET = $get;
	}

	/**
	 * The default front page (latest posts) uses the home URL.
	 */
	public function test_front_page_showing_posts() {
		update_option( 'show_on_front', 'posts' );
		$this->visit( home_url( '/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertSame(
			$this->expected( home_url( '/' ), array( 'month' => '03' ) ),
			mc_build_url( array( 'month' => '03' ), array() )
		);
	}

	/**
	 * A static front page uses the home URL, not the page permalink.
	 */
	public function test_front_page_static_page() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'static-front',
			)
		);
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );
		$this->visit( home_url( '/' ) );

		$this->assertTrue( is_front_page() );
		$this->assertSame(
			$this->expected( home_url( '/' ), array( 'time' => 'week' ) ),
			mc_build_url( array( 'time' => 'week' ), array() )
		);
	}

	/**
	 * The posts page uses the permalink of the page_for_posts page.
	 */
	public function test_posts_page() {
		$front_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		$blog_id  = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'blog',
			)
		);
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_id );
		update_option( 'page_for_posts', $blog_id );
		$this->visit( get_permalink( $blog_id ) );

		$this->assertTrue( is_home() );
		$this->assertFalse( is_front_page() );
		$this->assertSame(
			$this->expected( get_permalink( $blog_id ), array( 'mcat' => '2' ) ),
			mc_build_url( array( 'mcat' => '2' ), array() )
		);
	}

	/**
	 * Archives leave the base URL empty so links remain relative to the archive.
	 *
	 * @dataProvider archive_provider
	 *
	 * @param string $type Archive type.
	 */
	public function test_archives_use_relative_query_string( $type ) {
		$post_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_date'   => '2026-05-10 10:00:00',
			)
		);
		switch ( $type ) {
			case 'category':
				$term = self::factory()->category->create( array( 'slug' => 'news' ) );
				wp_set_post_categories( $post_id, array( $term ) );
				$url = get_category_link( $term );
				break;
			case 'tag':
				wp_set_post_tags( $post_id, array( 'featured' ) );
				$tag = get_term_by( 'slug', 'featured', 'post_tag' );
				$url = get_tag_link( $tag->term_id );
				break;
			case 'date':
				$url = get_month_link( 2026, 5 );
				break;
			case 'author':
				$author_id = self::factory()->user->create( array( 'role' => 'author' ) );
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_author' => $author_id,
					)
				);
				$url = get_author_posts_url( $author_id );
				break;
			default:
				$url = '';
		}
		$this->visit( $url );

		$test_url = mc_build_url(
			array(
				'month' => '03',
				'yr'    => '2026',
			),
			array()
		);

		$this->assertTrue( is_archive(), "$type should be an archive." );
		$this->assertSame( '?month=03&#038;yr=2026', $test_url, "$type should return a relative query string." );
	}

	/**
	 * Archive types to test.
	 *
	 * @return array
	 */
	public function archive_provider() {
		return array(
			'category' => array( 'category' ),
			'tag'      => array( 'tag' ),
			'date'     => array( 'date' ),
			'author'   => array( 'author' ),
		);
	}

	/**
	 * Singular views use the permalink of the current post.
	 *
	 * @dataProvider singular_provider
	 *
	 * @param string $post_type Post type.
	 */
	public function test_singular_views_use_current_permalink( $post_type ) {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'post_name'   => 'current-' . $post_type,
			)
		);
		$this->visit( get_permalink( $post_id ) );

		$this->assertTrue( is_singular() );
		$this->assertSame(
			$this->expected( get_permalink( $post_id ), array( 'dy' => '5' ) ),
			mc_build_url( array( 'dy' => '5' ), array() )
		);
	}

	/**
	 * Singular post types to test.
	 *
	 * @return array
	 */
	public function singular_provider() {
		return array(
			'page'     => array( 'page' ),
			'post'     => array( 'post' ),
			'mc-event' => array( 'mc-events' ),
		);
	}

	/**
	 * A page nested under a parent keeps its full permalink.
	 */
	public function test_child_page() {
		$parent_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'parent',
			)
		);
		$child_id  = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'child',
				'post_parent' => $parent_id,
			)
		);
		$this->visit( get_permalink( $child_id ) );

		$this->assertStringEndsWith( '/parent/child/', get_permalink( $child_id ) );
		$this->assertSame(
			$this->expected( get_permalink( $child_id ), array( 'time' => 'day' ) ),
			mc_build_url( array( 'time' => 'day' ), array() )
		);
	}

	/**
	 * A paginated single post resets to the post permalink.
	 */
	public function test_paginated_singular_uses_base_permalink() {
		$post_id = self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_name'    => 'paged-post',
				'post_content' => 'One<!--nextpage-->Two',
			)
		);
		$this->visit( get_permalink( $post_id ) . '2/' );

		$this->assertSame( 2, (int) get_query_var( 'page' ) );
		$this->assertSame(
			$this->expected( get_permalink( $post_id ), array( 'mcat' => '3' ) ),
			mc_build_url( array( 'mcat' => '3' ), array() )
		);
	}

	/**
	 * When another post is the global post (alternate loop), the queried object is still used,
	 * and the loop's global post is left untouched.
	 */
	public function test_alternate_loop_is_reset_to_main_query() {
		$main_id  = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'main-page',
			)
		);
		$other_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_name'   => 'other-post',
			)
		);
		$this->visit( get_permalink( $main_id ) );

		$GLOBALS['post'] = get_post( $other_id );
		setup_postdata( $GLOBALS['post'] );

		$this->assertSame(
			$this->expected( get_permalink( $main_id ), array( 'dy' => '1' ) ),
			mc_build_url( array( 'dy' => '1' ), array() )
		);
		$this->assertSame( $other_id, get_the_ID(), 'Global post is not reset.' );
	}

	/**
	 * Existing, non-removed query parameters are preserved.
	 */
	public function test_existing_query_parameters_are_preserved() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'calendar',
			)
		);
		$args    = array(
			'mcat' => '4',
			'yr'   => '2026',
		);

		$this->visit( get_permalink( $page_id ), $args );

		$this->assertSame(
			$this->expected(
				get_permalink( $page_id ),
				array(
					'mcat'  => '4',
					'month' => '06',
					'yr'    => '2026',
				)
			),
			mc_build_url( array( 'month' => '06' ), array() )
		);
	}

	/**
	 * Added values override existing values.
	 */
	public function test_added_values_override_existing() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'calendar',
			)
		);
		$this->visit( get_permalink( $page_id ), array( 'yr' => '2025' ) );

		$this->assertSame(
			$this->expected( get_permalink( $page_id ), array( 'yr' => '2027' ) ),
			mc_build_url( array( 'yr' => '2027' ), array() )
		);
	}

	/**
	 * Subtracted keys are removed, including when they are also added.
	 */
	public function test_subtracted_keys_are_removed() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'calendar',
			)
		);
		$args    = array(
			'mcat' => '4',
			'loc'  => 'x',
			'yr'   => '2026',
		);
		$this->visit( get_permalink( $page_id ), $args );

		$this->assertSame(
			$this->expected( get_permalink( $page_id ), array( 'yr' => '2026' ) ),
			mc_build_url( array(), array( 'mcat', 'loc' ) )
		);

		$args = array(
			'mcat' => '9',
			'loc'  => 'x',
			'yr'   => '2026',
		);
		$this->assertSame(
			$this->expected( get_permalink( $page_id ), $args ),
			mc_build_url( array( 'mcat' => '9' ), array( 'mcat' ) ),
			'Added values are applied after subtraction.'
		);
	}

	/**
	 * Parameters that are always removed.
	 */
	public function test_always_removed_parameters() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'calendar',
			)
		);
		$this->visit(
			get_permalink( $page_id ),
			array(
				'from'            => '1',
				'to'              => '2',
				'my-calendar-api' => 'x',
				's'               => 'search',
				'embed'           => 'true',
				'page_id'         => (string) $page_id,
				'yr'              => '2026',
			)
		);

		$this->assertSame(
			$this->expected( get_permalink( $page_id ), array( 'yr' => '2026' ) ),
			mc_build_url( array(), array() )
		);
	}

	/**
	 * Query parameters are sorted by key and ampersands are escaped.
	 */
	public function test_parameters_are_sorted_and_escaped() {
		$this->visit( home_url( '/' ) );

		$args = array(
			'yr'    => '2026',
			'month' => '02',
			'cid'   => '1',
		);
		$this->assertSame(
			home_url( '/' ) . '?cid=1&#038;month=02&#038;yr=2026',
			mc_build_url( $args, array() )
		);
	}

	/**
	 * Request values are sanitized before being added to the URL.
	 */
	public function test_request_values_are_sanitized() {
		$this->visit( home_url( '/' ), array( 'mcat' => '<script>alert(1)</script>' ) );

		$url = mc_build_url( array(), array() );

		$this->assertStringNotContainsString( '<', $url );
		$this->assertStringNotContainsString( '>', $url );
	}

	/**
	 * Without extra arguments, the URL is just the base URL.
	 */
	public function test_no_arguments_returns_base_url() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'calendar',
			)
		);
		$this->visit( get_permalink( $page_id ) );

		$this->assertSame( esc_url( get_permalink( $page_id ) ), mc_build_url( array(), array() ) );
	}

	/**
	 * The mc_build_url_root filter can supply a root when none is passed.
	 */
	public function test_root_filter_supplies_base_url() {
		$this->visit( home_url( '/' ) );
		add_filter(
			'mc_build_url_root',
			function () {
				return 'https://example.com/custom/';
			}
		);

		$this->assertSame(
			'https://example.com/custom/?yr=2026',
			mc_build_url( array( 'yr' => '2026' ), array() )
		);
	}

	/**
	 * The mc_build_url filter receives the unescaped URL and can change it.
	 */
	public function test_build_url_filter_can_modify_result() {
		$this->visit( home_url( '/' ) );
		add_filter(
			'mc_build_url',
			function ( $url ) {
				return $url . '&extra=1';
			}
		);

		$this->assertSame(
			home_url( '/' ) . '?yr=2026&#038;extra=1',
			mc_build_url( array( 'yr' => '2026' ), array() )
		);
	}
}
