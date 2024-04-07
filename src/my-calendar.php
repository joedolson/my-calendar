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
 * Plugin URI:  http://www.joedolson.com/my-calendar/
 * Description: Accessible WordPress event calendar plugin. Show events from multiple calendars on pages, in posts, or in widgets.
 * Author:      Joseph C Dolson
 * Author URI:  http://www.joedolson.com
 * Text Domain: my-calendar
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/license/gpl-2.0.txt
 * Domain Path: lang
 * Version:     3.5.0-rc2
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
	return '3.5.0-rc2';
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

add_action( 'plugins_loaded', 'mc_load_textdomain' );
/**
 * Load internationalization.
 */
function mc_load_textdomain() {
	// Shipped translations removed @v3.3.0.
	load_plugin_textdomain( 'my-calendar' );
}

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
