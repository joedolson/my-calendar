<?php
/**
 * My Calendar, Accessible Events Manager for WordPress
 *
 * @package     MyCalendar
 * @author      Joe Dolson
 * @copyright   2009-2024 Joe Dolson
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: My Calendar - Accessible Event Manager
 * Plugin URI:  https://www.joedolson.com/my-calendar/
 * Description: Accessible WordPress event calendar plugin. Show events from multiple calendars on pages, in posts, or in widgets.
 * Author:      Joe Dolson
 * Author URI:  https://www.joedolson.com
 * Text Domain: my-calendar
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/license/gpl-2.0.txt
 * Domain Path: lang
 * Version:     3.5.21
 */

/*
	Copyright 2009-2024  Joe Dolson (email : joe@joedolson.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

/**
 * Current My Calendar version.
 *
 * @param bool $version Pass false to return previous installed version.
 *
 * @return string
 */
function mc_get_version( $version = true ) {
	if ( ! $version ) {
		return get_option( 'mc_version', '' );
	}
	return '3.5.21';
}

define( 'MC_DEBUG', false );
define( 'MC_DIRECTORY', plugin_dir_path( __FILE__ ) );
if ( ! class_exists( 'Gamajo_Template_Loader' ) ) {
	require MC_DIRECTORY . 'includes/class-gamajo-template-loader.php';
}
require MC_DIRECTORY . 'includes/class-mc-template-loader.php';

register_activation_hook( __FILE__, 'mc_plugin_activated' );
register_deactivation_hook( __FILE__, 'mc_plugin_deactivated' );
/**
 * Actions to execute on activation.
 */
function mc_plugin_activated() {
	$required_php_version = '7.4.0';

	if ( version_compare( PHP_VERSION, $required_php_version, '<' ) ) {
		$plugin_data = get_plugin_data( __FILE__, false );
		// Translators: Name of plug-in, required PHP version, current PHP version.
		$message = sprintf( __( '%1$s requires PHP version %2$s or higher. Your current PHP version is %3$s', 'my-calendar' ), $plugin_data['Name'], $required_php_version, phpversion() );
		echo "<div class='error'><p>$message</p></div>";
		deactivate_plugins( plugin_basename( __FILE__ ) );
		exit;
	}
	mc_posttypes();
	mc_taxonomies();
	flush_rewrite_rules();
	mc_upgrade_db();
	my_calendar_check();
	mc_create_demo_content();
	mc_schedule_promotions();
}

register_uninstall_hook( __FILE__, 'mc_uninstall' );

/**
 * Actions to execute on plugin deactivation.
 */
function mc_plugin_deactivated() {
	flush_rewrite_rules();
}

/**
 * Bulk delete posts.
 *
 * @param string $type Post type.
 */
function mc_delete_posts( $type ) {
	$posts = get_posts(
		array(
			'post_type'   => $type,
			'numberposts' => -1,
		)
	);
	foreach ( $posts as $post ) {
		wp_delete_post( $post->ID, true );
	}
}

/**
 * Uninstall function for removing terms and posts.
 */
function mc_uninstall() {
	$options = get_option( 'my_calendar_options' );
	if ( 'true' === $options['drop_tables'] ) {
		mc_delete_posts( 'mc-events' );
		mc_delete_posts( 'mc-locations' );
		$terms = get_terms(
			'mc-event-category',
			array(
				'fields'     => 'ids',
				'hide_empty' => false,
			)
		);
		foreach ( $terms as $term ) {
			wp_delete_term( $term, 'mc-event-category' );
		}
	}
}

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/action-scheduler/action-scheduler.php';
require __DIR__ . '/includes/date-utilities.php';
require __DIR__ . '/includes/general-utilities.php';
require __DIR__ . '/includes/event-utilities.php';
require __DIR__ . '/includes/kses.php';
require __DIR__ . '/includes/post-types.php';
require __DIR__ . '/includes/privacy.php';
require __DIR__ . '/includes/conditionals.php';
require __DIR__ . '/includes/urls.php';
require __DIR__ . '/includes/screen-options.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/deprecated.php';
require __DIR__ . '/includes/class-customevent.php';
require __DIR__ . '/includes/ical.php';
require __DIR__ . '/templates/legacy-functions.php';
require __DIR__ . '/my-calendar-core.php';
require __DIR__ . '/my-calendar-install.php';
require __DIR__ . '/my-calendar-settings.php';
require __DIR__ . '/my-calendar-migrate.php';
require __DIR__ . '/my-calendar-categories.php';
require __DIR__ . '/my-calendar-locations.php';
require __DIR__ . '/my-calendar-location-manager.php';
require __DIR__ . '/my-calendar-event-editor.php';
require __DIR__ . '/my-calendar-event-manager.php';
require __DIR__ . '/my-calendar-styles.php';
require __DIR__ . '/my-calendar-behaviors.php';
require __DIR__ . '/my-calendar-events.php';
require __DIR__ . '/my-calendar-widgets.php';
require __DIR__ . '/my-calendar-upgrade-db.php';
require __DIR__ . '/my-calendar-output.php';
require __DIR__ . '/my-calendar-navigation.php';
require __DIR__ . '/my-calendar-search.php';
require __DIR__ . '/my-calendar-print.php';
require __DIR__ . '/my-calendar-iframe.php';
require __DIR__ . '/my-calendar-templates.php';
require __DIR__ . '/my-calendar-design.php';
require __DIR__ . '/my-calendar-limits.php';
require __DIR__ . '/my-calendar-shortcodes.php';
require __DIR__ . '/my-calendar-templating.php';
require __DIR__ . '/my-calendar-group-manager.php';
require __DIR__ . '/my-calendar-api.php';
require __DIR__ . '/my-calendar-generator.php';
require __DIR__ . '/my-calendar-call-template.php';
require __DIR__ . '/my-calendar-help.php';
require __DIR__ . '/my-calendar-ajax.php';
require __DIR__ . '/my-calendar-import.php';

// Add actions.
add_action( 'admin_menu', 'my_calendar_menu' );
add_action( 'wp_head', 'mc_head' );
add_filter( 'wpseo_schema_graph', 'mc_add_yoast_schema', 10, 2 );
add_action( 'delete_user', 'mc_deal_with_deleted_user', 10, 2 );
add_action( 'widgets_init', 'mc_register_widgets' );
add_action( 'init', 'mc_add_feed' );
add_action( 'wp_footer', 'mc_footer_js' );
add_action( 'init', 'mc_export_vcal', 200 );
// Add filters.
add_filter( 'widget_text', 'do_shortcode', 9 );
add_filter( 'plugin_action_links', 'mc_plugin_action', 10, 2 );
add_filter( 'pre_get_document_title', 'mc_event_filter', 10, 1 );

/**
 * Register all My Calendar widgets
 */
function mc_register_widgets() {
	register_widget( 'My_Calendar_Today_Widget' );
	register_widget( 'My_Calendar_Upcoming_Widget' );
	register_widget( 'My_Calendar_Mini_Widget' );
	register_widget( 'My_Calendar_Simple_Search' );
	register_widget( 'My_Calendar_Filters' );
}

add_action( 'template_redirect', 'mc_custom_canonical' );
/**
 * Customize canonical URL for My Calendar custom links
 */
function mc_custom_canonical() {
	if ( mc_is_single_event() ) {
		add_action( 'wp_head', 'mc_canonical' );
		remove_action( 'wp_head', 'rel_canonical' );
		add_filter( 'wpseo_canonical', 'mc_disable_yoast_canonical' );
	}
}

/**
 * When Yoast is enabled with canonical URLs, it returns an invalid URL for single events. Disable on single events.
 *
 * @return boolean
 */
function mc_disable_yoast_canonical() {
	return false;
}

if ( isset( $_REQUEST['mcs'] ) ) {
	// Only call a session if a search has been performed.
	add_action( 'init', 'mc_start_session', 1 );
}
/**
 * Makes sure session is started to be able to save search results.
 */
function mc_start_session() {
	// Starting a session breaks the white screen check.
	if ( isset( $_GET['wp_scrape_key'] ) ) {
		return;
	}

	$status = session_status();
	if ( PHP_SESSION_DISABLED === $status ) {
		return;
	}

	if ( PHP_SESSION_NONE === $status ) {
		session_start();
	}
}

/**
 * Generate canonical link
 */
function mc_canonical() {
	// Original code.
	if ( ! is_singular() ) {
		return;
	}

	$id = get_queried_object_id();

	if ( 0 === $id ) {
		return;
	}

	$link = wp_get_canonical_url( $id );

	// End original code.
	if ( isset( $_GET['mc_id'] ) ) {
		$mc_id = ( absint( $_GET['mc_id'] ) ) ? absint( $_GET['mc_id'] ) : false;
	} else {
		$event_id = get_post_meta( $id, '_mc_event_id', true );
		$event    = mc_get_first_event( $event_id );
		$mc_id    = $event->occur_id;
	}
	$link = add_query_arg( 'mc_id', $mc_id, $link );

	echo "<link rel='canonical' href='" . esc_url( $link ) . "' />\n";
}

/**
 * Produce My Calendar admin sidebar
 *
 * @param string        $show deprecated.
 * @param array|boolean $add boolean or array.
 * @param boolean       $remove Hide commercial blocks.
 */
function mc_show_sidebar( $show = '', $add = false, $remove = false ) {
	/**
	 * Inject a sidebar panel in the My Calendar admin. Does not replace existing panels.
	 *
	 * @hook mc_custom_sidebar_panels
	 *
	 * @param {array} $add Associative array with headings as keys and content as values.
	 *
	 * @return {array} Associative array with all extra sidebars.
	 */
	$add = apply_filters( 'mc_custom_sidebar_panels', $add );

	if ( current_user_can( 'mc_view_help' ) ) {
		?>
		<div class="postbox-container jcd-narrow">
		<div class="metabox-holder">
		<?php
		if ( is_array( $add ) ) {
			foreach ( $add as $key => $value ) {
				?>
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h2><?php echo $key; ?></h2>

						<div class='<?php echo sanitize_title( $key ); ?> inside'>
							<?php echo $value; ?>
						</div>
					</div>
				</div>
				<?php
			}
		}
		if ( ! $remove ) {
			if ( ! function_exists( 'mcs_submissions' ) ) {
				?>
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox mc-support-me promotion">
						<h2><strong><?php esc_html_e( 'My Calendar Pro', 'my-calendar' ); ?></strong></h2>

						<div class="inside resources mc-flex">
							<img src="<?php echo plugins_url( 'images/awd-logo-disc.png', __FILE__ ); ?>" alt="Joe Dolson Accessible Web Design" />
							<p>
							<?php
							// Translators: URL for My Calendar Pro.
							printf( __( "Buy <a href='%s' rel='external'>My Calendar Pro</a> &mdash; a more powerful calendar for your site.", 'my-calendar' ), 'https://www.joedolson.com/my-calendar/pro/' );
							?>
							</p>
						</div>
					</div>
				</div>
				<?php
			}
			if ( ! function_exists( 'mt_update_check' ) ) {
				?>
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox sell my-tickets">
						<h2 class='sales'><strong><?php esc_html_e( 'My Tickets', 'my-calendar' ); ?></strong></h2>

						<div class="inside resources">
							<p class="mcbuy">
							<?php
							// Translators: URL to view details about My Tickets.
							printf( __( 'Do you sell tickets to your events? <a href="%s" class="thickbox open-plugin-details-modal" rel="external">Use My Tickets</a> and sell directly from My Calendar.', 'my-calendar' ), admin_url( 'plugin-install.php?tab=plugin-information&plugin=my-tickets&TB_iframe=true&width=600&height=550' ) );
							?>
							</p>

						</div>
					</div>
				</div>
				<?php
			}
		}
		?>
		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h2><?php esc_html_e( 'Get Help', 'my-calendar' ); ?></h2>

				<div class="inside">
					<?php echo mc_get_help_footer(); ?>
					<ul class="mc-flex mc-social">
						<li><a href="https://toot.io/@joedolson">
							<svg aria-hidden="true" width="24" height="24" viewBox="0 0 61 65" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M60.7539 14.3904C59.8143 7.40642 53.7273 1.90257 46.5117 0.836066C45.2943 0.655854 40.6819 0 29.9973 0H29.9175C19.2299 0 16.937 0.655854 15.7196 0.836066C8.70488 1.87302 2.29885 6.81852 0.744617 13.8852C-0.00294988 17.3654 -0.0827298 21.2237 0.0561464 24.7629C0.254119 29.8384 0.292531 34.905 0.753482 39.9598C1.07215 43.3175 1.62806 46.6484 2.41704 49.9276C3.89445 55.9839 9.87499 61.0239 15.7344 63.0801C22.0077 65.2244 28.7542 65.5804 35.2184 64.1082C35.9295 63.9428 36.6318 63.7508 37.3252 63.5321C38.8971 63.0329 40.738 62.4745 42.0913 61.4937C42.1099 61.4799 42.1251 61.4621 42.1358 61.4417C42.1466 61.4212 42.1526 61.3986 42.1534 61.3755V56.4773C42.153 56.4557 42.1479 56.4345 42.1383 56.4151C42.1287 56.3958 42.1149 56.3788 42.0979 56.3655C42.0809 56.3522 42.0611 56.3429 42.04 56.3382C42.019 56.3335 41.9971 56.3336 41.9761 56.3384C37.8345 57.3276 33.5905 57.8234 29.3324 57.8156C22.0045 57.8156 20.0336 54.3384 19.4693 52.8908C19.0156 51.6397 18.7275 50.3346 18.6124 49.0088C18.6112 48.9866 18.6153 48.9643 18.6243 48.9439C18.6333 48.9236 18.647 48.9056 18.6643 48.8915C18.6816 48.8774 18.7019 48.8675 18.7237 48.8628C18.7455 48.858 18.7681 48.8585 18.7897 48.8641C22.8622 49.8465 27.037 50.3423 31.2265 50.3412C32.234 50.3412 33.2387 50.3412 34.2463 50.3146C38.4598 50.1964 42.9009 49.9808 47.0465 49.1713C47.1499 49.1506 47.2534 49.1329 47.342 49.1063C53.881 47.8507 60.1038 43.9097 60.7362 33.9301C60.7598 33.5372 60.8189 29.8148 60.8189 29.4071C60.8218 28.0215 61.2651 19.5781 60.7539 14.3904Z" fill="url(#paint0_linear_89_8)"/><path d="M50.3943 22.237V39.5876H43.5185V22.7481C43.5185 19.2029 42.0411 17.3949 39.036 17.3949C35.7325 17.3949 34.0778 19.5338 34.0778 23.7585V32.9759H27.2434V23.7585C27.2434 19.5338 25.5857 17.3949 22.2822 17.3949C19.2949 17.3949 17.8027 19.2029 17.8027 22.7481V39.5876H10.9298V22.237C10.9298 18.6918 11.835 15.8754 13.6453 13.7877C15.5128 11.7049 17.9623 10.6355 21.0028 10.6355C24.522 10.6355 27.1813 11.9885 28.9542 14.6917L30.665 17.5633L32.3788 14.6917C34.1517 11.9885 36.811 10.6355 40.3243 10.6355C43.3619 10.6355 45.8114 11.7049 47.6847 13.7877C49.4931 15.8734 50.3963 18.6899 50.3943 22.237Z" fill="white"/><defs><linearGradient id="paint0_linear_89_8" x1="30.5" y1="0" x2="30.5" y2="65" gradientUnits="userSpaceOnUse"><stop stop-color="#6364FF"/><stop offset="1" stop-color="#563ACC"/></linearGradient></defs></svg>
							<span class="screen-reader-text">Mastodon</span></a>
						</li>
						<li><a href="https://bsky.app/profile/joedolson.bsky.social">
							<svg width="24" height="24" viewBox="0 0 568 501" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M123.121 33.6637C188.241 82.5526 258.281 181.681 284 234.873C309.719 181.681 379.759 82.5526 444.879 33.6637C491.866 -1.61183 568 -28.9064 568 57.9464C568 75.2916 558.055 203.659 552.222 224.501C531.947 296.954 458.067 315.434 392.347 304.249C507.222 323.8 536.444 388.56 473.333 453.32C353.473 576.312 301.061 422.461 287.631 383.039C285.169 375.812 284.017 372.431 284 375.306C283.983 372.431 282.831 375.812 280.369 383.039C266.939 422.461 214.527 576.312 94.6667 453.32C31.5556 388.56 60.7778 323.8 175.653 304.249C109.933 315.434 36.0535 296.954 15.7778 224.501C9.94525 203.659 0 75.2916 0 57.9464C0 -28.9064 76.1345 -1.61183 123.121 33.6637Z" fill="#1185fe"/></svg>
							<span class="screen-reader-text">Bluesky</span></a>
						</li>
						<li><a href="https://linkedin.com/in/joedolson">
							<svg aria-hidden="true" height="24" viewBox="0 0 72 72" width="24" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><path d="M8,72 L64,72 C68.418278,72 72,68.418278 72,64 L72,8 C72,3.581722 68.418278,-8.11624501e-16 64,0 L8,0 C3.581722,8.11624501e-16 -5.41083001e-16,3.581722 0,8 L0,64 C5.41083001e-16,68.418278 3.581722,72 8,72 Z" fill="#007EBB"/><path d="M62,62 L51.315625,62 L51.315625,43.8021149 C51.315625,38.8127542 49.4197917,36.0245323 45.4707031,36.0245323 C41.1746094,36.0245323 38.9300781,38.9261103 38.9300781,43.8021149 L38.9300781,62 L28.6333333,62 L28.6333333,27.3333333 L38.9300781,27.3333333 L38.9300781,32.0029283 C38.9300781,32.0029283 42.0260417,26.2742151 49.3825521,26.2742151 C56.7356771,26.2742151 62,30.7644705 62,40.051212 L62,62 Z M16.349349,22.7940133 C12.8420573,22.7940133 10,19.9296567 10,16.3970067 C10,12.8643566 12.8420573,10 16.349349,10 C19.8566406,10 22.6970052,12.8643566 22.6970052,16.3970067 C22.6970052,19.9296567 19.8566406,22.7940133 16.349349,22.7940133 Z M11.0325521,62 L21.769401,62 L21.769401,27.3333333 L11.0325521,27.3333333 L11.0325521,62 Z" fill="#FFF"/></g></svg>
							<span class="screen-reader-text">LinkedIn</span></a>
						</li>
						<li><a href="https://github.com/joedolson">
							<svg aria-hidden="true" width="24" height="24" viewBox="0 0 1024 1024" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8C0 11.54 2.29 14.53 5.47 15.59C5.87 15.66 6.02 15.42 6.02 15.21C6.02 15.02 6.01 14.39 6.01 13.72C4 14.09 3.48 13.23 3.32 12.78C3.23 12.55 2.84 11.84 2.5 11.65C2.22 11.5 1.82 11.13 2.49 11.12C3.12 11.11 3.57 11.7 3.72 11.94C4.44 13.15 5.59 12.81 6.05 12.6C6.12 12.08 6.33 11.73 6.56 11.53C4.78 11.33 2.92 10.64 2.92 7.58C2.92 6.71 3.23 5.99 3.74 5.43C3.66 5.23 3.38 4.41 3.82 3.31C3.82 3.31 4.49 3.1 6.02 4.13C6.66 3.95 7.34 3.86 8.02 3.86C8.7 3.86 9.38 3.95 10.02 4.13C11.55 3.09 12.22 3.31 12.22 3.31C12.66 4.41 12.38 5.23 12.3 5.43C12.81 5.99 13.12 6.7 13.12 7.58C13.12 10.65 11.25 11.33 9.47 11.53C9.76 11.78 10.01 12.26 10.01 13.01C10.01 14.08 10 14.94 10 15.21C10 15.42 10.15 15.67 10.55 15.59C13.71 14.53 16 11.53 16 8C16 3.58 12.42 0 8 0Z" transform="scale(64)" fill="#1B1F23"/></svg>
							<span class="screen-reader-text">GitHub</span></a>
						</li>
					</ul>
				</div>
			</div>
		</div>
		</div>
		</div>
		<?php
	}
}

/**
 * Test whether a system is present that My Calendar supports migration from.
 *
 * @return bool
 */
function mc_has_migration_path() {
	if ( function_exists( 'check_calendar' ) && 'true' !== get_option( 'ko_calendar_imported' ) ) {
		return true;
	}
	if ( function_exists( 'tribe_get_event' ) && 'true' !== get_option( 'mc_tribe_imported' ) ) {
		return true;
	}
	return false;
}

/**
 * Add My Calendar menu items to main admin menu
 */
function my_calendar_menu() {
	if ( function_exists( 'add_menu_page' ) ) {
		if ( 'true' !== mc_get_option( 'remote' ) ) {
			add_menu_page( __( 'My Calendar', 'my-calendar' ), __( 'My Calendar', 'my-calendar' ), 'mc_add_events', apply_filters( 'mc_modify_default', 'my-calendar' ), apply_filters( 'mc_modify_default_cb', 'my_calendar_edit' ), 'dashicons-calendar' );
		} else {
			add_menu_page( __( 'My Calendar', 'my-calendar' ), __( 'My Calendar', 'my-calendar' ), 'mc_edit_settings', 'my-calendar', 'my_calendar_settings', 'dashicons-calendar' );
		}
	}
	if ( function_exists( 'add_submenu_page' ) ) {
		add_action( 'admin_head', 'mc_write_js' );
		add_action( 'admin_enqueue_scripts', 'mc_admin_styles' );
		if ( 'true' === mc_get_option( 'remote' ) && function_exists( 'mc_remote_db' ) ) {
			// If we're accessing a remote site, remove these pages.
		} else {
			if ( isset( $_GET['event_id'] ) ) {
				$event_id = absint( $_GET['event_id'] );
				// Translators: Title of event.
				$page_title = sprintf( __( 'Editing Event: %s', 'my-calendar' ), esc_html( strip_tags( stripslashes( mc_get_data( 'event_title', $event_id ) ) ) ) );
			} else {
				$page_title = __( 'Add New Event', 'my-calendar' );
			}
			add_submenu_page( 'my-calendar', $page_title, __( 'Add Event', 'my-calendar' ), 'mc_add_events', 'my-calendar', 'my_calendar_edit' );
			$manage = add_submenu_page( 'my-calendar', __( 'Events', 'my-calendar' ), __( 'Events', 'my-calendar' ), 'mc_add_events', 'my-calendar-manage', 'my_calendar_manage_screen' );
			add_action( "load-$manage", 'mc_add_screen_option' );
			add_action( "load-$manage", 'mc_add_help_tab' );
			if ( isset( $_GET['location_id'] ) ) {
				$loc_id = absint( $_GET['location_id'] );
				// Translators: Title of event.
				$page_title = sprintf( __( 'Editing Location: %s', 'my-calendar' ), mc_location_data( 'location_label', $loc_id ) );
			} else {
				$page_title = __( 'Add New Location', 'my-calendar' );
			}
			add_submenu_page( 'my-calendar', $page_title, __( 'Add New Location', 'my-calendar' ), 'mc_edit_locations', 'my-calendar-locations', 'my_calendar_add_locations' );
			$locations = add_submenu_page( 'my-calendar', __( 'Locations', 'my-calendar' ), __( 'Locations', 'my-calendar' ), 'mc_edit_locations', 'my-calendar-location-manager', 'my_calendar_manage_locations' );
			add_action( "load-$locations", 'mc_location_help_tab' );
			add_submenu_page( 'my-calendar', __( 'Categories', 'my-calendar' ), __( 'Categories', 'my-calendar' ), 'mc_edit_cats', 'my-calendar-categories', 'my_calendar_manage_categories' );
		}
		// The Design screen is available with any of these permissions.
		$permission = 'manage_options';
		if ( current_user_can( 'mc_edit_styles' ) ) {
			$permission = 'mc_edit_styles';
		}
		if ( current_user_can( 'mc_edit_templates' ) ) {
			$permission = 'mc_edit_templates';
		}
		if ( current_user_can( 'mc_edit_scripts' ) ) {
			$permission = 'mc_edit_scripts';
		}
		add_submenu_page( 'my-calendar', __( 'Design', 'my-calendar' ), __( 'Design', 'my-calendar' ), $permission, 'my-calendar-design', 'my_calendar_design' );
		add_submenu_page( 'my-calendar', __( 'Settings', 'my-calendar' ), __( 'Settings', 'my-calendar' ), 'mc_edit_settings', 'my-calendar-config', 'my_calendar_settings' );
		if ( mc_has_migration_path() ) {
			add_submenu_page( 'my-calendar', __( 'Migration', 'my-calendar' ), __( 'Migration', 'my-calendar' ), 'mc_edit_settings', 'my-calendar-migrate', 'my_calendar_migration' );
		}
		add_submenu_page( 'my-calendar', __( 'My Calendar Shortcode Generator', 'my-calendar' ), __( 'Shortcodes', 'my-calendar' ), 'mc_view_help', 'my-calendar-shortcodes', 'my_calendar_shortcodes' );
		add_submenu_page( 'my-calendar', __( 'My Calendar Help', 'my-calendar' ), __( 'Help', 'my-calendar' ), 'mc_view_help', 'my-calendar-help', 'my_calendar_help' );
		// Null submenu parent prevents this from appearing in the admin menu.
		add_submenu_page( '', __( 'My Calendar Contextual Help', 'my-calendar' ), __( 'My Calendar Contextual Help', 'my-calendar' ), 'mc_view_help', 'mc-contextual-help', 'mc_print_contextual_help' );
	}
	if ( function_exists( 'mcs_submissions' ) ) {
		$capability = 'manage_options';
		/**
		 * Filter user capability required to use the My Calendar Pro front-end submissions.
		 *
		 * @hook mcs_submission_permissions
		 *
		 * @param {string} $capability A string representing a WordPress capability.
		 *
		 * @return {string} A string representing a WordPress capability.
		 */
		$permission = apply_filters( 'mcs_submission_permissions', $capability );
		add_action( 'admin_head', 'my_calendar_sub_js' );
		add_action( 'admin_head', 'my_calendar_sub_styles' );
		add_submenu_page( 'my-calendar', __( 'My Calendar Pro Settings', 'my-calendar' ), __( 'My Calendar Pro', 'my-calendar' ), $permission, 'my-calendar-submissions', 'mcs_settings' );
		// Only show payments screen if enabled.
		if ( 'true' === get_option( 'mcs_payments' ) ) {
			add_submenu_page( 'my-calendar', __( 'Payments Received', 'my-calendar' ), __( 'Payments', 'my-calendar' ), $permission, 'my-calendar-payments', 'mcs_sales_page' );
		}
	}
}

add_shortcode( 'my_calendar', 'my_calendar_insert' );
add_shortcode( 'my_calendar_upcoming', 'my_calendar_insert_upcoming' );
add_shortcode( 'my_calendar_today', 'my_calendar_insert_today' );
add_shortcode( 'my_calendar_locations', 'my_calendar_locations' );
add_shortcode( 'my_calendar_categories', 'my_calendar_categories' );
add_shortcode( 'my_calendar_access', 'my_calendar_access' );
add_shortcode( 'mc_filters', 'my_calendar_filters' );
add_shortcode( 'my_calendar_show_locations', 'my_calendar_show_locations_list' );
add_shortcode( 'my_calendar_event', 'my_calendar_show_event' );
add_shortcode( 'my_calendar_search', 'my_calendar_search' );
add_shortcode( 'my_calendar_now', 'my_calendar_now' );
add_shortcode( 'my_calendar_next', 'my_calendar_next' );
