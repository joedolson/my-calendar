<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

function my_calendar_add_feed() {
	add_feed( 'my-calendar-rss', 'my_calendar_rss' );
	add_feed( 'my-calendar-ics', 'my_calendar_ical' );
	add_feed( 'my-calendar-subscribe', 'mc_ics_subscribe' );
}

/**
 * Add plug-in info page links to Plugins page
 *
 * @param array $links default set of plug-in links
 * @param string $file Current file (not used by custom function.)
 *
 * @return array updated set of links
 */
function mc_plugin_action( $links, $file ) {
	if ( $file == plugin_basename( dirname( __FILE__ ) . '/my-calendar.php' ) ) {
		$links[] = "<a href='admin.php?page=my-calendar-config'>" . __( 'Settings', 'my-calendar' ) . "</a>";
		$links[] = "<a href='admin.php?page=my-calendar-help'>" . __( 'Help', 'my-calendar' ) . "</a>";
		if ( !function_exists( 'mcs_submissions' ) ) {
			$links[] = "<a href='https://www.joedolson.com/my-calendar-pro/'>" . __( 'Go Pro', 'my-calendar' ) . "</a>";
		}
	}

	return $links;
}

/**
 * Check whether requested file exists either in plugin directory, theme directory, or calendar custom directory
 *
 * @param string $file file name relative to 'my-calendar', 'my-calendar-custom', or theme directory
 *
 * @return boolean
 */
function mc_file_exists( $file ) {
	$file   = sanitize_file_name( $file );
	$dir    = plugin_dir_path( __FILE__ );
	$base   = basename( $dir );
	$return = apply_filters( 'mc_file_exists', false, $file );
	if ( $return ) {
		return true;
	}
	if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
		return true;
	}
	if ( file_exists( str_replace( $base, 'my-calendar-custom', $dir ) . $file ) ) {
		return true;
	}

	return false;
}

/**
 * Fetch a file by path or URL. Checks multiple directories to see which to get.
 *
 * @param string $file name of file to get, relative to /my-calendar/, /my-calendar-custom/ or theme directory e.g. 'css/mc-print.css'
 * @param string $type either path or url
 * 
 * @return string full path or url
 */
function mc_get_file( $file, $type = 'path' ) {
	$file = sanitize_file_name( $file );
	$dir  = plugin_dir_path( __FILE__ );
	$url  = plugin_dir_url( __FILE__ );
	$base = basename( $dir );
	$path = ( $type == 'path' ) ? $dir . $file : $url . $file;
	
	if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
		$path = ( $type == 'path' ) ? get_stylesheet_directory() . '/' . $file : get_stylesheet_directory_uri() . '/' . $file;
	}
	
	if ( file_exists( str_replace( $base, 'my-calendar-custom', $dir ) . $file ) ) {
		$path = ( $type == 'path' ) ? str_replace( $base, 'my-calendar-custom', $dir ) . $file : str_replace( $base, 'my-calendar-custom', $url ) . $file;
	}
	$path = apply_filters( 'mc_get_file', $path, $file );

	return $path;
}

add_action( 'wp_enqueue_scripts', 'mc_register_styles' );
/**
 * Publically enqueued styles & scripts
 */
function mc_register_styles() {
	global $wp_query;
	$this_post = $wp_query->get_queried_object();
	
	$stylesheet = apply_filters( 'mc_registered_stylesheet', mc_get_style_path( get_option( 'mc_css_file' ), 'url' ) );
	wp_register_style( 'my-calendar-reset', plugins_url( 'css/reset.css', __FILE__ ) );
	wp_register_style( 'my-calendar-style', $stylesheet, array( 'dashicons', 'my-calendar-reset' ) );
	
	$admin_stylesheet = plugins_url( 'css/mc-admin.css', __FILE__ );
	wp_register_style( 'my-calendar-admin-style', $admin_stylesheet );
	
	if ( current_user_can( 'mc_manage_events' ) ) {
		wp_enqueue_style( 'my-calendar-admin-style' );
	}
	
	$id        = ( is_object( $this_post ) && isset( $this_post->ID ) ) ? $this_post->ID : false;
	$js_array  = ( get_option( 'mc_show_js' ) != '' ) ? explode( ",", get_option( 'mc_show_js' ) ) : array();
	$css_array = ( get_option( 'mc_show_css' ) != '' ) ? explode( ",", get_option( 'mc_show_css' ) ) : array();
	
	// check whether any scripts are actually enabled.
	if ( get_option( 'mc_calendar_javascript' ) != 1 || get_option( 'mc_list_javascript' ) != 1 || get_option( 'mc_mini_javascript' ) != 1 || get_option( 'mc_ajax_javascript' ) != 1 ) {
		if ( @in_array( $id, $js_array ) || get_option( 'mc_show_js' ) == '' || is_singular( 'mc-events' ) ) {
			wp_enqueue_script( 'jquery' );
			if ( get_option( 'mc_gmap' ) == 'true' ) {
				$api_key = get_option( 'mc_gmap_api_key' );
				if ( $api_key ) {
					wp_enqueue_script( 'gmaps', "https://maps.googleapis.com/maps/api/js?key=$api_key" );
					wp_enqueue_script( 'gmap3', plugins_url( 'js/gmap3.min.js', __FILE__ ), array( 'jquery' ) );
				}
			}		
		}
	}
	
	if ( get_option( 'mc_use_styles' ) != 'true' ) {
		if ( @in_array( $id, $css_array ) || get_option( 'mc_show_css' ) == '' ) {
			wp_enqueue_style( 'my-calendar-style' );
		}
	}
	
	if ( mc_is_tablet() && mc_file_exists( 'mc-tablet.css' ) ) {
		$tablet = mc_get_file( 'mc-tablet.css' );
		wp_register_style( 'my-calendar-tablet-style', $tablet );
		wp_enqueue_style( 'my-calendar-tablet-style' );
	}
	
	if ( mc_is_mobile() && mc_file_exists( 'mc-mobile.css' ) ) {
		$mobile = mc_get_file( 'mc-mobile.css' );
		wp_register_style( 'my-calendar-mobile-style', $mobile );
		wp_enqueue_style( 'my-calendar-mobile-style' );
	}
}

/**
 * Publically written head styles & scripts
 */
function my_calendar_head() {
	global $wpdb, $wp_query;
	$mcdb  = $wpdb;
	if ( get_option( 'mc_remote' ) == 'true' && function_exists( 'mc_remote_db' ) ) {
		$mcdb = mc_remote_db();
	}	
	$array = array();

	if ( get_option( 'mc_use_styles' ) != 'true' ) {
		$this_post = $wp_query->get_queried_object();
		$id        = ( is_object( $this_post ) && isset( $this_post->ID ) ) ? $this_post->ID : false;
		$array     = ( get_option( 'mc_show_css' ) != '' ) ? explode( ",", get_option( 'mc_show_css' ) ) : $array;
		if ( @in_array( $id, $array ) || get_option( 'mc_show_css' ) == '' ) {
			// generate category colors
			$category_styles = $inv = $type = $alt = '';
			$categories      = $mcdb->get_results( "SELECT * FROM " . my_calendar_categories_table( get_current_blog_id() ) . " ORDER BY category_id ASC" );
			foreach ( $categories as $category ) {
				$class = mc_category_class( $category, 'mc_' );
				$hex   = ( strpos( $category->category_color, '#' ) !== 0 ) ? '#' : '';
				$color = $hex . $category->category_color;
				if ( $color != '#' ) {
					$hcolor = mc_shift_color( $category->category_color );
					if ( get_option( 'mc_apply_color' ) == 'font' ) {
						$type = 'color';
						$alt  = 'background';
					} elseif ( get_option( 'mc_apply_color' ) == 'background' ) {
						$type = 'background';
						$alt  = 'color';
					}
					if ( get_option( 'mc_inverse_color' ) == 'true' ) {
						$inverse = mc_inverse_color( $color );
						$inv     = "$alt: $inverse;";
					}
					if ( get_option( 'mc_apply_color' ) == 'font' || get_option( 'mc_apply_color' ) == 'background' ) {
						// always an anchor as of 1.11.0, apply also to title
						$category_styles .= "\n.mc-main .$class .event-title, .mc-main .$class .event-title a { $type: $color; $inv }";
						$category_styles .= "\n.mc-main .$class .event-title a:hover, .mc-main .$class .event-title a:focus { $type: $hcolor;}";
					}
				}
			}
			
			$styles     = (array) get_option( 'mc_style_vars' );
			$style_vars = '';
			foreach( $styles as $key => $var ) {
				if ( $var ) {
					$style_vars .= sanitize_key( $key ) . ': ' . $var . "; ";
				}
			}
			if ( $style_vars != '' ) {
				$style_vars = '.mc-main {' . $style_vars . '}';
			}
			
			$all_styles = "
<style type=\"text/css\">
<!--
/* Styles by My Calendar - Joseph C Dolson http://www.joedolson.com/ */
$category_styles
.mc-event-visible {
	display: block!important;
}
$style_vars
-->
</style>";
			echo $all_styles;
		}
	}
}

/**
 * Deal with events posted by a user when that user is deleted
 *
 * @param user ID of deleted user
 */
function mc_deal_with_deleted_user( $id ) {
	global $wpdb;
	// Do the queries
	// This may not work quite right in multi-site. Need to explore further when I have time.
	$wpdb->get_results( 
		$wpdb->prepare( 
			"UPDATE " . my_calendar_table() . " SET event_author=" . apply_filters( 'mc_deleted_author', $wpdb->get_var( "SELECT MIN(ID) FROM " . $wpdb->users, 0, 0 ) ) . " WHERE event_author=%d", $id
		)
	);
	$wpdb->get_results( 
		$wpdb->prepare( 
			"UPDATE " . my_calendar_table() . " SET event_host=" . apply_filters( 'mc_deleted_host', $wpdb->get_var( "SELECT MIN(ID) FROM " . $wpdb->users, 0, 0 ) ) . " WHERE event_host=%d", $id 
		)
	);
}

/**
 * Move sidebars into the footer.
 *
 * @param string Existing admin body classes
 *
 * @return string New admin body classes
 */
function mc_admin_body_class( $classes ) {
	if ( get_option( 'mc_sidebar_footer' ) == 'true' ) {
		$classes .= ' mc-sidebar-footer';
	}
	
	return $classes;
}

/**
 * Write custom JS in admin head.
 */
function my_calendar_write_js() {
	if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'my-calendar' || $_GET['page'] == 'my-calendar-locations' ) ) {	
		?>
		<script>
			//<![CDATA[		
			jQuery(document).ready(function ($) {		
				$( '#mc-accordion' ).accordion( { collapsible: true, active: false, heightStyle: 'content' } );
				<?php
				if ( function_exists( 'jd_doTwitterAPIPost' ) && isset( $_GET['page'] ) && $_GET['page'] == 'my-calendar' ) { 
				?>
				$('#mc_twitter').charCount({
					allowed: 140,
					counterText: '<?php _e('Characters left: ','my-calendar') ?>'
				});
				<?php 
				} 
				?>
			});
			//]]>
		</script><?php
	}
}

add_action( 'in_plugin_update_message-my-calendar/my-calendar.php', 'mc_plugin_update_message' );
/**
 * Display notices from  WordPress.org about updated versions.
 */
function mc_plugin_update_message() {
	global $mc_version;
	define( 'MC_PLUGIN_README_URL', 'http://svn.wp-plugins.org/my-calendar/trunk/readme.txt' );
	$response = wp_remote_get( 
		MC_PLUGIN_README_URL, 
		array( 
			'user-agent' => 'WordPress/My Calendar' . $mc_version . '; ' . get_bloginfo( 'url' ) 
		)
	);
	if ( ! is_wp_error( $response ) || is_array( $response ) ) {
		$data = $response['body'];
		$bits = explode( '== Upgrade Notice ==', $data );
		echo '</div><div id="mc-upgrade" class="notice inline notice-warning"><ul><li><strong style="color:#c22;">Upgrade Notes:</strong> ' . str_replace( '* ', '', nl2br( trim( $bits[1] ) ) ) . '</li></ul>';
	}
}

/**
 * Scripts for My Calendar footer; ideally only on pages where My Calendar exists
 */
function mc_footer_js() {
	global $wp_query;
	$mcjs = "<script>(function ($) { 'use strict'; $(function () { $( '.mc-main' ).removeClass( 'mcjs' ); });}(jQuery));</script>";

	if ( mc_is_mobile() && apply_filters( 'mc_disable_mobile_js', false ) ) {
		return;
	} else {
		$pages = array();
		if ( get_option( 'mc_show_js' ) != '' ) {
			$pages = explode( ",", get_option( 'mc_show_js' ) );
		}
		if ( is_object( $wp_query ) && isset( $wp_query->post ) ) {
			$id = $wp_query->post->ID;
		} else {
			$id = false;
		}
		if ( get_option( 'mc_use_custom_js' ) == 1 ) {
			$top     = '';
			$bottom  = '';
			$inner   = '';
			$list_js = stripcslashes( get_option( 'mc_listjs' ) );
			$cal_js  = stripcslashes( get_option( 'mc_caljs' ) );
			if ( get_option( 'mc_open_uri' ) == 'true' ) { // remove sections of javascript if necessary.
				$replacements = array(
					'$(this).parent().children().not(".event-title").toggle();',
					'e.preventDefault();'
				);
				$cal_js = str_replace( $replacements, '', $cal_js );
			}
			$mini_js  = stripcslashes( get_option( 'mc_minijs' ) );
			$open_day = get_option( 'mc_open_day_uri' );
			if ( $open_day == 'true' || $open_day == 'listanchor' || $open_day == 'calendaranchor' ) {
				$mini_js = str_replace( 'e.preventDefault();', '', $mini_js );
			}
			$ajax_js = stripcslashes( get_option( 'mc_ajaxjs' ) );

			if ( @in_array( $id, $pages ) || get_option( 'mc_show_js' ) == '' ) {
				$inner = '';
				if ( get_option( 'mc_calendar_javascript' ) != 1 ) {
					$inner .= "\n" . $cal_js;
				}
				if ( get_option( 'mc_list_javascript' ) != 1 ) {
					$inner .= "\n" . $list_js;
				}
				if ( get_option( 'mc_mini_javascript' ) != 1 ) {
					$inner .= "\n" . $mini_js;
				}
				if ( get_option( 'mc_ajax_javascript' ) != 1 ) {
					$inner .= "\n" . $ajax_js;
				}
$script = '
<script type="text/javascript">
(function( $ ) { \'use strict\';'.
	$inner
.'}(jQuery));
</script>';
			}
			$inner = apply_filters( 'mc_filter_javascript_footer', $inner );
			echo ( $inner != '' ) ? $script . $mcjs : '';
		} else {
			$enqueue_mcjs = false;
			if ( @in_array( $id, $pages ) || get_option( 'mc_show_js' ) == '' ) {
				if ( get_option( 'mc_calendar_javascript' ) != 1 && get_option( 'mc_open_uri' ) != 'true' ) {
					$url          = apply_filters( 'mc_grid_js', plugins_url( 'js/mc-grid.js', __FILE__ ) );
					$enqueue_mcjs = true;
					wp_enqueue_script( 'mc.grid', $url, array( 'jquery' ) );
					wp_localize_script( 'mc.grid', 'mcgrid', 'true' );
				}
				if ( get_option( 'mc_list_javascript' ) != 1 ) {
					$url          = apply_filters( 'mc_list_js', plugins_url( 'js/mc-list.js', __FILE__ ) );
					$enqueue_mcjs = true;
					wp_enqueue_script( 'mc.list', $url, array( 'jquery' ) );
					wp_localize_script( 'mc.list', 'mclist', 'true' );					
				}
				if ( get_option( 'mc_mini_javascript' ) != 1 && get_option( 'mc_open_day_uri' ) != 'true' ) {
					$url          = apply_filters( 'mc_mini_js', plugins_url( 'js/mc-mini.js', __FILE__ ) );
					$enqueue_mcjs = true;
					wp_enqueue_script( 'mc.mini', $url, array( 'jquery' ) );
					wp_localize_script( 'mc.mini', 'mcmini', 'true' );
				}
				if ( get_option( 'mc_ajax_javascript' ) != 1 ) {
					$url          = apply_filters( 'mc_ajax_js', plugins_url( 'js/mc-ajax.js', __FILE__ ) );
					$enqueue_mcjs = true;
					wp_enqueue_script( 'mc.ajax', $url, array( 'jquery' ) );
					wp_localize_script( 'mc.ajax', 'mcAjax', 'true' );					
				}
				if ( $enqueue_mcjs ) {
					wp_enqueue_script( 'mc.mcjs', plugins_url( 'js/mcjs.js', __FILE__ ), array( 'jquery' ) );
				}
			}
		}
	}
}


/**
 * Add stylesheets to My Calendar admin screens
 */
function my_calendar_add_styles() {
	global $current_screen;
	$id = $current_screen->id;
	if ( strpos( $id, 'my-calendar' ) !== false ) {
		wp_enqueue_style( 'mc-styles', plugins_url( 'css/mc-styles.css', __FILE__ ) );
		
		if ( $id == 'toplevel_page_my-calendar' ) {
			wp_enqueue_style( 'mc-pickadate-default', plugins_url( 'js/pickadate/themes/default.css', __FILE__ ) );
			wp_enqueue_style( 'mc-pickadate-date', plugins_url( 'js/pickadate/themes/default.date.css', __FILE__ ) );
			wp_enqueue_style( 'mc-pickadate-time', plugins_url( 'js/pickadate/themes/default.time.css', __FILE__ ) );
		}
	}
}

/**
 * Attempts to correctly identify the current URL. 
 *
 */
function mc_get_current_url() {
	global $wp, $wp_rewrite;
	$args = array();
	if ( isset( $_GET['page_id'] ) ) {
		$args = array( 'page_id' => $_GET['page_id'] );
	}
	$current_url = home_url( add_query_arg( $args, $wp->request ) );
		
	if ( $wp_rewrite->using_index_permalinks() && strpos( $current_url, 'index.php' ) === false ) {
		$current_url = str_replace( home_url(), home_url( '/' ) . 'index.php', $current_url );
	}
	if ( $wp_rewrite->using_permalinks() ) {
		$current_url = trailingslashit( $current_url );
	}
	
	return esc_url( $current_url );
}

/**
 * Check whether the current user should have permissions and doesn't
 */
function mc_if_needs_permissions() {
	// prevent administrators from losing privileges to edit my calendar
	$role = get_role( 'administrator' );
	if ( is_object( $role ) ) {
		$caps = $role->capabilities;
		if ( isset( $caps['mc_add_events'] ) ) {
			return;
		} else {
			$role->add_cap( 'mc_add_events' );
			$role->add_cap( 'mc_approve_events' );
			$role->add_cap( 'mc_manage_events' );
			$role->add_cap( 'mc_edit_cats' );
			$role->add_cap( 'mc_edit_styles' );
			$role->add_cap( 'mc_edit_behaviors' );
			$role->add_cap( 'mc_edit_templates' );
			$role->add_cap( 'mc_edit_settings' );
			$role->add_cap( 'mc_edit_locations' );
			$role->add_cap( 'mc_view_help' );
		}
	} else {
		return;
	}
}

/**
 * Grant capabilities to standard site roles
 */
function mc_add_roles( $add = false, $manage = false, $approve = false ) {
	// grant administrator role all event permissions
	$role = get_role( 'administrator' );
	$role->add_cap( 'mc_add_events' );
	$role->add_cap( 'mc_approve_events' );
	$role->add_cap( 'mc_manage_events' );
	$role->add_cap( 'mc_edit_cats' );
	$role->add_cap( 'mc_edit_styles' );
	$role->add_cap( 'mc_edit_behaviors' );
	$role->add_cap( 'mc_edit_templates' );
	$role->add_cap( 'mc_edit_settings' );
	$role->add_cap( 'mc_edit_locations' );
	$role->add_cap( 'mc_view_help' );

	// depending on permissions settings, grant other permissions
	if ( $add && $manage && $approve ) {
		// this is an upgrade;
		// Get Roles
		$subscriber  = get_role( 'subscriber' );
		$contributor = get_role( 'contributor' );
		$author      = get_role( 'author' );
		$editor      = get_role( 'editor' );
		$subscriber->add_cap( 'mc_view_help' );
		$contributor->add_cap( 'mc_view_help' );
		$author->add_cap( 'mc_view_help' );
		$editor->add_cap( 'mc_view_help' );
		switch ( $add ) {
			case 'read':
				$subscriber->add_cap( 'mc_add_events' );
				$contributor->add_cap( 'mc_add_events' );
				$author->add_cap( 'mc_add_events' );
				$editor->add_cap( 'mc_add_events' );
				break;
			case 'edit_posts':
				$contributor->add_cap( 'mc_add_events' );
				$author->add_cap( 'mc_add_events' );
				$editor->add_cap( 'mc_add_events' );
				break;
			case 'publish_posts':
				$author->add_cap( 'mc_add_events' );
				$editor->add_cap( 'mc_add_events' );
				break;
			case 'moderate_comments':
				$editor->add_cap( 'mc_add_events' );
				break;
		}
		switch ( $approve ) {
			case 'read':
				$subscriber->add_cap( 'mc_approve_events' );
				$contributor->add_cap( 'mc_approve_events' );
				$author->add_cap( 'mc_approve_events' );
				$editor->add_cap( 'mc_approve_events' );
				break;
			case 'edit_posts':
				$contributor->add_cap( 'mc_approve_events' );
				$author->add_cap( 'mc_approve_events' );
				$editor->add_cap( 'mc_approve_events' );
				break;
			case 'publish_posts':
				$author->add_cap( 'mc_approve_events' );
				$editor->add_cap( 'mc_approve_events' );
				break;
			case 'moderate_comments':
				$editor->add_cap( 'mc_approve_events' );
				break;
		}
		switch ( $manage ) {
			case 'read':
				$subscriber->add_cap( 'mc_manage_events' );
				$contributor->add_cap( 'mc_manage_events' );
				$author->add_cap( 'mc_manage_events' );
				$editor->add_cap( 'mc_manage_events' );
				break;
			case 'edit_posts':
				$contributor->add_cap( 'mc_manage_events' );
				$author->add_cap( 'mc_manage_events' );
				$editor->add_cap( 'mc_manage_events' );
				break;
			case 'publish_posts':
				$author->add_cap( 'mc_manage_events' );
				$editor->add_cap( 'mc_manage_events' );
				break;
			case 'moderate_comments':
				$editor->add_cap( 'mc_manage_events' );
				break;
		}
	}
}

/**
 * Verify that My Calendar tables exist
 */
function my_calendar_exists() {
	global $wpdb;
	$tables = $wpdb->get_results( "show tables;" );
	foreach ( $tables as $table ) {
		foreach ( $table as $value ) {
			if ( $value == my_calendar_table() ) {
				// if the table exists, then My Calendar was already installed.
				return true;
			}
		}
	}
	
	return false;
}

/**
 * Check what version of My Calendar is installed; install or upgrade if needed
 */
function my_calendar_check() {
	// only execute this function for administrators
	if ( current_user_can( 'manage_options' ) ) {
		global $wpdb, $mc_version;
		mc_if_needs_permissions();
		$current_version = ( get_option( 'mc_version' ) == '' ) ? get_option( 'my_calendar_version' ) : get_option( 'mc_version' );
		if ( version_compare( $current_version, '2.3.12', '>=' ) ) {
			// if current is a version higher than 2.3.11, they've already seen this notice and handled it.
			update_option( 'mc_update_notice', 1 );
		}
		// If current version matches, don't bother running this.
		if ( $current_version == $mc_version ) {
			return true;
		}
		// Assume this is not a new install until we prove otherwise
		$new_install        = false;
		$upgrade_path       = array();
		$my_calendar_exists = my_calendar_exists();
		
		if ( $my_calendar_exists && $current_version == '' ) {
			// If the table exists, but I don't know what version it is, I have to run the full cycle of upgrades. 
			$current_version = '1.11.3';
		}

		if ( ! $my_calendar_exists ) {
			$new_install = true;
		} else {
			// for each release requiring an upgrade path, add a version compare. 
			// Loop will run every relevant upgrade cycle.
			$valid_upgrades = array(
				'2.0.0',
				'2.1.0',
				'2.2.10',
				'2.3.0',
				'2.3.11',
				'2.3.15',
				'2.4.4',
				'2.6.0',
			);
			foreach ( $valid_upgrades as $upgrade ) {
				if ( version_compare( $current_version, $upgrade, "<" ) ) {
					$upgrade_path[] = $upgrade;
				}
			}
		}
		// having determined upgrade path, assign new version number
		update_option( 'mc_version', $mc_version );
		// Now we've determined what the current install is or isn't 
		if ( $new_install == true ) {
			//add default settings
			mc_default_settings();
			mc_create_category( array( 'category_name' => 'General', 'category_color' => '#ffffcc', 'category_icon' => 'event.png' ) );
			
		}
		
		mc_do_upgrades( $upgrade_path );
		/*
		if the user has fully uninstalled the plugin but kept the database of events, this will restore default 
		settings and upgrade db if needed.
		*/
		if ( get_option( 'mc_uninstalled' ) == 'true' ) {
			mc_default_settings();
			update_option( 'mc_db_version', $mc_version );
			delete_option( 'mc_uninstalled' );
		}
	}
}

/**
 * Given a valid upgrade path, execute it.
 *
 * @param string $upgrade_path Specific path to execute
 */
function mc_do_upgrades( $upgrade_path ) {
	global $mc_version;
	
	foreach ( $upgrade_path as $upgrade ) {
		switch ( $upgrade ) {
			case '2.6.0':
				delete_option( 'mc_event_open' );
				delete_option( 'mc_widget_defaults' );
				delete_option( 'mc_event_closed' );	
				delete_option( 'mc_event_approve' );
				delete_option( 'mc_ical_utc' );
				delete_option( 'mc_user_settings_enabled' );
				delete_option( 'mc_user_location_type' );
				delete_option( 'mc_event_approve_perms' );
				delete_option( 'mc_location_type' );
				add_option( 'mc_style_vars', array(
					  '--primary-dark' => '#313233',
					  '--primary-light' => '#fff',
					  '--secondary-light' => '#fff',
					  '--secondary-dark' => '#000',
					  '--highlight-dark' => '#666',
					  '--highlight-light' => '#efefef',
					)
				);
				mc_upgrade_db();
				mc_transition_categories();
			// only upgrade db on most recent version
			case '2.4.4': // 8-11-2015 (2.4.0)
				add_option( 'mc_display_more', 'true' );
				$input_options = get_option( 'mc_input_options' );
				$input_options['event_host'] = 'on';
				update_option( 'mc_input_options', $input_options );
				add_option( 'mc_default_direction', 'DESC' );
				break;
			case '2.3.15': // 6/8/2015 (2.3.32)
				delete_option( 'mc_event_groups' );
				delete_option( 'mc_details' );
				break;
			case '2.3.11': 
				// delete notice when this goes away
				add_option( 'mc_use_custom_js', 0 );
				add_option( 'mc_update_notice', 0 );
				break;
			case '2.3.0': // 4/10/2014
				delete_option( 'mc_location_control' );
				$user_data              = get_option( 'mc_user_settings' );
				$loc_type               = ( get_option( 'mc_location_type' ) == '' ) ? 'event_state' : get_option( 'mc_location_type' );
				$locations[ $loc_type ] = $user_data['my_calendar_location_default']['values'];
				add_option( 'mc_use_permalinks', false );
				delete_option( 'mc_modified_feeds' );
				add_option( 'mc_location_controls', $locations );
				$mc_input_options                 = get_option( 'mc_input_options' );
				$mc_input_options['event_access'] = 'on';
				update_option( 'mc_input_options', $mc_input_options );
				mc_transition_db();
				break;
			case '2.2.10': // 10/29/13 (2.2.13)
				delete_option( 'mc_show_print' );
				delete_option( 'mc_show_ical' );
				delete_option( 'mc_show_rss' );
				delete_option( 'mc_draggable' );
				delete_option( 'mc_caching_enabled' ); // remove caching support via options. Filter only.
				add_option( 'mc_inverse_color', 'true' );
				break;
			case '2.1.0': // 4/17/2013 (2.1.5)
				$templates = get_option( 'mc_templates' );
				global $rss_template;
				$templates['rss'] = $rss_template;
				update_option( 'mc_templates', $templates );
				break;
			case '2.0.0': // 11/20/2012 (2.0.12)
				mc_migrate_db();
				$mc_input = get_option( 'mc_input_options' );
				if ( ! isset( $mc_input['event_specials'] ) ) {
					$mc_input['event_specials'] = 'on';
					update_option( 'mc_input_options', $mc_input );
				}
				break;
			default:
				break;
		}
	}
}

add_action( 'admin_bar_menu', 'my_calendar_admin_bar', 200 );
/**
 * Set up adminbar links
 */
function my_calendar_admin_bar() {
	global $wp_admin_bar;
	if ( current_user_can( 'mc_add_events' ) && get_option( 'mc_remote' ) != 'true' ) {
		$url  = apply_filters( 'mc_add_events_url', admin_url( 'admin.php?page=my-calendar' ) );
		$args = array( 'id' => 'mc-add-event', 'title' => __( 'Add Event', 'my-calendar' ), 'href' => $url );
		$wp_admin_bar->add_node( $args );
	}
	if ( mc_get_uri( 'boolean' ) ) {
		$url  = esc_url( apply_filters( 'mc_adminbar_uri', mc_get_uri() ) );
		$args = array( 'id' => 'mc-my-calendar', 'title' => __( 'View Calendar', 'my-calendar' ), 'href' => $url );
		$wp_admin_bar->add_node( $args );
	} else {
		$url  = admin_url( 'admin.php?page=my-calendar-config#my-calendar-manage' );
		$args = array( 'id' => 'mc-my-calendar', 'title' => __( 'Set Calendar URL', 'my-calendar' ), 'href' => $url );
		$wp_admin_bar->add_node( $args );		
	}
	if ( current_user_can( 'mc_manage_events' ) && current_user_can( 'mc_add_events' ) ) {
		$url  = admin_url( 'admin.php?page=my-calendar-manage' );
		$args = array(
			'id'     => 'mc-manage-events',
			'title'  => __( 'Events', 'my-calendar' ),
			'href'   => $url,
			'parent' => 'mc-add-event'
		);
		$wp_admin_bar->add_node( $args );
	}
	if ( current_user_can( 'mc_edit_cats' ) && current_user_can( 'mc_add_events' ) ) {
		$url  = admin_url( 'admin.php?page=my-calendar-categories' );
		$args = array(
			'id'     => 'mc-manage-categories',
			'title'  => __( 'Categories', 'my-calendar' ),
			'href'   => $url,
			'parent' => 'mc-add-event'
		);
		$wp_admin_bar->add_node( $args );
	}
	if ( current_user_can( 'mc_edit_locations' ) && current_user_can( 'mc_add_events' ) ) {
		$url  = admin_url( 'admin.php?page=my-calendar-locations' );
		$args = array(
			'id'     => 'mc-manage-locations',
			'title'  => __( 'Locations', 'my-calendar' ),
			'href'   => $url,
			'parent' => 'mc-add-event'
		);
		$wp_admin_bar->add_node( $args );
	}
	if ( function_exists( 'mcs_submissions' ) && is_numeric( get_option( 'mcs_submit_id' ) ) ) {
		$url  = get_permalink( get_option( 'mcs_submit_id' ) );
		$args = array(
			'id'     => 'mc-submit-events',
			'title'  => __( 'Public Submissions', 'my-calendar' ),
			'href'   => $url,
			'parent' => 'mc-add-event'
		);
		$wp_admin_bar->add_node( $args );		
	}
}

/**
 * Send email notification about an event.
 *
 * @param object $event 
 */
function my_calendar_send_email( $event ) {
	$details = mc_create_tags( $event );
	$headers = array();
	// shift to boolean
	$send_email_option = ( get_option( 'mc_event_mail' ) == 'true' ) ? true : false;
	$send_email        = apply_filters( 'mc_send_notification', $send_email_option, $details );
	if ( $send_email == true ) {
		add_filter( 'wp_mail_content_type', 'mc_html_type' );
	}
	if ( get_option( 'mc_event_mail' ) == 'true' ) {
		$to        = apply_filters( 'mc_event_mail_to', get_option( 'mc_event_mail_to' ), $details );
		$from      = ( get_option( 'mc_event_mail_from' ) == '' ) ? get_bloginfo( 'admin_email' ) : get_option( 'mc_event_mail_from' );
		$from      = apply_filters( 'mc_event_mail_from', $from, $details );
		$headers[] = "From: " . __( 'Event Notifications', 'my-calendar' ) . " <$from>";
		$bcc       = apply_filters( 'mc_event_mail_bcc', get_option( 'mc_event_mail_bcc' ), $details );
		if ( $bcc ) {
			$bcc = explode( PHP_EOL, $bcc );
			foreach ( $bcc as $b ) {
				$b = trim( $b );
				if ( is_email( $b ) ) {
					$headers[] = "Bcc: $b";
				}
			}
		}
		$headers = apply_filters( 'mc_customize_email_headers', $headers, $event );
		$subject = apply_filters( 'mc_event_mail_subject', get_option( 'mc_event_mail_subject' ), $details );
		$body    = apply_filters( 'mc_event_mail_body', get_option( 'mc_event_mail_message' ), $details );
		$subject = mc_draw_template( $details, $subject );
		$message = mc_draw_template( $details, $body );
		wp_mail( $to, $subject, $message, $headers );
	}
	if ( get_option( 'mc_html_email' ) == 'true' ) {
		remove_filter( 'wp_mail_content_type', 'mc_html_type' );
	}
}

/**
 * checks submitted events against akismet or botsmasher, if available
 *
 * @param string $event_url Provided URL
 * @param string $description Event description
 * @param array  $post Posted details
 *
 * @return boolean true if spam
 */
function mc_spam( $event_url = '', $description = '', $post = array() ) {
	global $akismet_api_host, $akismet_api_port, $current_user;
	$wpcom_api_key = defined( 'WPCOM_API_KEY' ) ? WPCOM_API_KEY : false;
	$current_user = wp_get_current_user();
	if ( current_user_can( 'mc_manage_events' ) ) { // is a privileged user
		return 0;
	}
	$bs = $akismet = false;
	$c  = array();
	// check for Akismet
	if ( ! function_exists( 'akismet_http_post' ) || ! ( get_option( 'wordpress_api_key' ) || $wpcom_api_key ) ) {
		// check for BotSmasher
		$bs = get_option( 'bs_options' );
		if ( is_array( $bs ) ) {
			$bskey = $bs['bs_api_key'];
		} else {
			$bskey = '';
		}
		if ( ! function_exists( 'bs_checker' ) || $bskey == '' ) {
			return 0; // if neither exist
		} else {
			$bs = true;
		}
	} else {
		$akismet = true;
	}
	if ( $akismet ) {
		$c['blog']         = get_option( 'home' );
		$c['user_ip']      = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
		$c['user_agent']   = $_SERVER['HTTP_USER_AGENT'];
		$c['referrer']     = $_SERVER['HTTP_REFERER'];
		$c['comment_type'] = 'my_calendar_event';
		if ( $permalink = get_permalink() ) {
			$c['permalink'] = $permalink;
		}
		if ( '' != $event_url ) {
			$c['comment_author_url'] = $event_url;
		}
		if ( '' != $description ) {
			$c['comment_content'] = $description;
		}
		$ignore = array( 'HTTP_COOKIE' );

		foreach ( $_SERVER as $key => $value ) {
			if ( ! in_array( $key, (array) $ignore ) ) {
				$c["$key"] = $value;
			}
		}
		$query_string = '';
		foreach ( $c as $key => $data ) {
			$query_string .= $key . '=' . urlencode( stripslashes( (string) $data ) ) . '&';
		}
		$response = akismet_http_post( $query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port );
		if ( 'true' == $response[1] ) {
			return 1;
		} else {
			return 0;
		}
	}
	if ( $bs ) {
		if ( is_user_logged_in() ) {
			$name  = $current_user->user_login;
			$email = $current_user->user_email;
		} else {
			$name  = $post['mcs_name'];
			$email = $post['mcs_email'];
		}
		$args       = array(
			'ip'     => preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] ),
			'email'  => $email,
			'name'   => $name,
			'action' => 'check'
		);
		$args['ip'] = "216.152.251.41";
		$response   = bs_checker( $args );
		if ( $response ) {
			return 1;
		} else {
			return 0;
		}
	}

	return 0;
}

add_action( 'admin_enqueue_scripts', 'mc_scripts' );
/**
 * Enqueue My Calendar admin scripts
 */
function mc_scripts() {
	global $current_screen;
	$id = $current_screen->id;
		
	if ( strpos( $id, 'my-calendar' ) !== false ) {
		wp_enqueue_script( 'mc.admin', plugins_url( 'js/jquery.admin.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable' ) );
		wp_localize_script( 'mc.admin', 'thumbHeight', get_option( 'thumbnail_size_h' ) );		
		wp_localize_script( 'mc.admin', 'draftText', __( 'Save Draft', 'my-calendar' ) );		
	}
	
	//my-calendar_page_my-calendar-categories
	if ( $id == 'toplevel_page_my-calendar' || $id == 'my-calendar_page_my-calendar-groups' || $id == 'my-calendar_page_my-calendar-locations' ) {
		wp_enqueue_script( 'jquery-ui-accordion' );		
	}
		
	if ( $id == 'toplevel_page_my-calendar' || $id == 'my-calendar_page_my-calendar-groups' ) {
		wp_enqueue_script( 'jquery-ui-autocomplete' ); // required for character counting
		wp_enqueue_script( 'pickadate', plugins_url( 'js/pickadate/picker.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'pickadate.date', plugins_url( 'js/pickadate/picker.date.js', __FILE__ ), array( 'pickadate' ) );
		wp_enqueue_script( 'pickadate.time', plugins_url( 'js/pickadate/picker.time.js', __FILE__ ), array( 'pickadate' ) );
		wp_localize_script( 'pickadate.date', 'mc_months', array(
			date_i18n( 'F', strtotime( 'January 1' ) ),
			date_i18n( 'F', strtotime( 'February 1' ) ),
			date_i18n( 'F', strtotime( 'March 1' ) ),
			date_i18n( 'F', strtotime( 'April 1' ) ),
			date_i18n( 'F', strtotime( 'May 1' ) ),
			date_i18n( 'F', strtotime( 'June 1' ) ),
			date_i18n( 'F', strtotime( 'July 1' ) ),
			date_i18n( 'F', strtotime( 'August 1' ) ),
			date_i18n( 'F', strtotime( 'September 1' ) ),
			date_i18n( 'F', strtotime( 'October 1' ) ),
			date_i18n( 'F', strtotime( 'November 1' ) ),
			date_i18n( 'F', strtotime( 'December 1' ) )
		) );
		wp_localize_script( 'pickadate.date', 'mc_days', array(
			date_i18n( 'D', strtotime( 'Sunday' ) ),
			date_i18n( 'D', strtotime( 'Monday' ) ),
			date_i18n( 'D', strtotime( 'Tuesday' ) ),
			date_i18n( 'D', strtotime( 'Wednesday' ) ),
			date_i18n( 'D', strtotime( 'Thursday' ) ),
			date_i18n( 'D', strtotime( 'Friday' ) ),
			date_i18n( 'D', strtotime( 'Saturday' ) )
		) );
		$sweek = get_option( 'start_of_week' );	
		wp_localize_script( 'pickadate.date', 'mc_text', array(
			'today' => addslashes( __( 'Today', 'my-calendar' ) ),
			'clear' => addslashes( __( 'Clear', 'my-calendar' ) ),
			'close' => addslashes( __( 'Close', 'my-calendar' ) ),
			'start' => ( $sweek == 1 || $sweek == 0 ) ? $sweek : 0
			)
		);
		wp_localize_script( 'pickadate.time', 'mc_time_format', apply_filters( 'mc_time_format', 'h:i A' ) );
		wp_localize_script( 'pickadate.time', 'mc_interval', apply_filters( 'mc_interval', '15' ) );
		
		wp_enqueue_script( 'mc.pickadate', plugins_url( 'js/mc-datepicker.js', __FILE__ ), array( 'jquery', 'pickadate.date', 'pickadate.time' ) );
		
		if ( function_exists( 'wp_enqueue_media' ) && ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}
	}
		
	if ( $id == 'my-calendar_page_my-calendar-locations' ) {
		if ( get_option( 'mc_gmap' ) == 'true' ) {
			$api_key = get_option( 'mc_gmap_api_key' );
			if ( $api_key ) {
				wp_enqueue_script( 'gmaps', "https://maps.googleapis.com/maps/api/js?key=$api_key" );
				wp_enqueue_script( 'gmap3', plugins_url( 'js/gmap3.min.js', __FILE__ ), array( 'jquery' ) );
			}
		}			
	}	
	
	if ( $id == 'toplevel_page_my-calendar' && function_exists( 'jd_doTwitterAPIPost' ) ) {
		wp_enqueue_script( 'charCount', plugins_url( 'wp-to-twitter/js/jquery.charcount.js' ), array( 'jquery' ) );
	}
	if ( $id == 'toplevel_page_my-calendar' ) {
		if ( current_user_can( 'mc_manage_events' ) ) {
			wp_enqueue_script( 'mc.ajax', plugins_url( 'js/ajax.js', __FILE__ ), array( 'jquery' ) );
			wp_localize_script( 'mc.ajax', 'mc_data', array(
				'action'   => 'delete_occurrence',
				'recur'    => 'add_date',
				'security' => wp_create_nonce( 'mc-delete-nonce' )
			) );
		}
	}
	
	if ( $id == 'my-calendar_page_my-calendar-config' ) {
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'mc.suggest', plugins_url( 'js/jquery.suggest.js', __FILE__ ), array(
				'jquery',
				'jquery-ui-autocomplete'
			) );	
		wp_localize_script( 'mc.suggest', 'mc_ajax_action', 'mc_post_lookup' );
			
	}
	
	if ( $id == 'my-calendar_page_my-calendar-categories' ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'mc-color-picker', plugins_url( 'js/color-picker.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
	}
}


add_action( 'wp_ajax_mc_post_lookup', 'mc_post_lookup' );
/**
 * Add post lookup for assigning My Calendar main page
 */
function mc_post_lookup() {
	if ( isset( $_REQUEST['term'] ) ) {
		$posts       = get_posts( array(
			's'         => $_REQUEST['term'],
			'post_type' => array( 'post', 'page' )
		) );
		$suggestions = array();
		global $post;
		foreach ( $posts as $post ) {
			setup_postdata( $post );
			$suggestion          = array();
			$suggestion['value'] = esc_html( $post->post_title );
			$suggestion['id']    = $post->ID;
			$suggestions[]       = $suggestion;
		}

		echo $_GET["callback"] . "(" . json_encode( $suggestions ) . ")";
		exit;
	}
}


add_action( 'wp_ajax_delete_occurrence', 'mc_ajax_delete_occurrence' );
/**
 * Delete a single occurrence of an event from the event manager.
 *
 * @return string Confirmation message indicating success or failure.
 */
function mc_ajax_delete_occurrence() {
	if ( ! check_ajax_referer( 'mc-delete-nonce', 'security', false ) ) {
		wp_send_json( array( 'success'=>0, 'response' => __( "Invalid Security Check", 'my-calendar' ) ) );
	}

	if ( current_user_can( 'mc_manage_events' ) ) {
		
		global $wpdb;
		$occur_id = (int) $_REQUEST['occur_id'];
				
		$delete = "DELETE FROM " . my_calendar_event_table() . " WHERE occur_id = $occur_id";
		$result = $wpdb->query( $delete );
				
		if ( $result ) {
			wp_send_json( array( 'success'=>1, 'response' => __( 'Event instance has been deleted.', 'my-calendar' ) ) );
		} else {
			wp_send_json( array( 'success'=>0, 'response' => __( 'Event instance was not deleted.', 'my-calendar' ) ) );
		}

	} else {
		wp_send_json( array( 'success'=>0, 'response' => __( 'You are not authorized to perform this action', 'my-calendar' ) ) );
	}
}


add_action( 'wp_ajax_add_date', 'mc_ajax_add_date' );
/**
 * Add a single additional date for an event from the event manager.
 *
 * @return string Confirmation message indicating success or failure.
 */
function mc_ajax_add_date() {
	if ( ! check_ajax_referer( 'mc-delete-nonce', 'security', false ) ) {
		wp_send_json( array( 'success'=>0, 'response' => __( "Invalid Security Check", 'my-calendar' ) ) );
	}

	if ( current_user_can( 'mc_manage_events' ) ) {
		
		global $wpdb;
		$event_id = (int) $_REQUEST['event_id'];
		
		if ( $event_id === 0 ) {
			wp_send_json( array( 'success' => 0, 'response' => __( 'No event ID in that request.', 'my-calendar' ) ) );
		}
		
		$event_date = $_REQUEST['event_date'];
		$event_end   = isset( $_REQUEST['event_end'] ) ? $_REQUEST['event_end'] : $event_date;
		$event_time  = $_REQUEST['event_time'];
		$event_endtime = isset( $_REQUEST['event_endtime'] ) ? $_REQUEST['event_endtime'] : '';
		$group_id = (int) $_REQUEST['group_id'];
		
		// event end can not be earlier than event start
		if ( ! $event_end || strtotime( $event_end ) < strtotime( $event_date ) ) {
			$event_end = $event_date;
		}
		
		$begin = strtotime( $event_date . ' ' . $event_time );
		$end   = ( $event_endtime != '' ) ? strtotime( $event_end . ' ' . $event_endtime ) : strtotime( $event_end . ' ' . $event_time ) + HOUR_IN_SECONDS;
		
		$format   = array( '%d', '%s', '%s', '%d' );
		$data = array(
				'occur_event_id' => $event_id,
				'occur_begin'    => date( 'Y-m-d  H:i:s', $begin ),
				'occur_end'      => date( 'Y-m-d  H:i:s', $end ),
				'occur_group_id' => $group_id
			);
		$result = $wpdb->insert( my_calendar_event_table(), $data, $format );
		
		if ( $result ) {
			wp_send_json( array( 'success'=>1, 'response' => __( 'Thanks! I added your new date.', 'my-calendar' ) ) );
		} else {
			wp_send_json( array( 'success'=>0, 'response' => __( 'Sorry! I failed to add that date.', 'my-calendar' ) ) );
		}

	} else {
		wp_send_json( array( 'success'=>0, 'response' => __( 'You are not authorized to perform this action', 'my-calendar' ) ) );
	}
}

/**
 * in multi-site, wp_is_mobile() won't be defined yet if plug-in is network activated. 
 */
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( ! function_exists( 'wp_is_mobile' ) ) {
	if ( ! is_plugin_active_for_network( 'my-calendar/my-calendar.php' ) ) {
		//function wp_is_mobile() {
		//	return false;
		//}
	}
}

/**
 * Test whether currently mobile using wp_is_mobile() with custom filter
 */
function mc_is_mobile() {
	return apply_filters( 'mc_is_mobile', wp_is_mobile() );
}

/* this function only provides a filter for custom dev. */
function mc_is_tablet() {
	return apply_filters( 'mc_is_tablet', false );
}

/**
 * As of version 2.6.0, this only checks for 'my-calendar', to see if this plug-in already exists.
 */
function mc_guess_calendar() {
	global $wpdb;
	$has_uri = mc_get_uri( 'boolean' );
	$current = mc_get_uri();
	// check whether calendar page is a valid URL.
	if ( $has_uri && esc_url( $current ) ) {
		$response = wp_remote_head( $current );
		if ( !is_wp_error( $response ) ) {
			$http = $response['response']['code'];
			// Only modify the value if it's explicitly missing. Redirects or secured pages are fine.
			if ( $http == 404 ) {
				$current = '';
			}
		}
	}
	
	if ( ! $has_uri ) {
		$post_ID = $wpdb->get_var( "SELECT id FROM $wpdb->posts WHERE post_name LIKE '%my-calendar%' AND post_status = 'publish'" );
		if ( $post_ID ) {
			$link    = get_permalink( $post_ID );
			$content = get_post( $post_ID )->post_content;
			// if my-calendar exists but does not contain shortcode, add it
			if ( ! has_shortcode( $content, 'my_calendar' ) ) {
				$content .= "\n\n[my_calendar]";
				wp_update_post( array( 
					'ID' => $post_ID, 
					'post_content' => $content ) 
				);
			}
			update_option( 'mc_uri', $link );
			update_option( 'mc_uri_id', $post_ID );
			$return = array( 
				'response' => true, 
				'message'=> __( 'Is this your calendar page?', 'my-calendar' ) . ' <code>' . $link . '</code>' 
			);

			return $return;
		} else {
			update_option( 'mc_uri', '' );
			update_option( 'mc_uri_id', '' );
			$return = array( 
				'response' => false, 
				'message' => __( 'No valid calendar detected. Please provide a URL!', 'my-calendar' ) 
			);
			
			return $return;
		}
	}
	
	return;
}

/**
 * Set up support form
 */
function mc_get_support_form() {
	global $current_user;
	$current_user = wp_get_current_user();
	// send fields for My Calendar
	$version       = get_option( 'mc_version' );
	$mc_db_version = get_option( 'mc_db_version' );
	$mc_uri        = mc_get_uri();
	$mc_css        = get_option( 'mc_css_file' );

	// Pro license status
	$license       = ( get_option( 'mcs_license_key' ) != '' ) ? get_option( 'mcs_license_key' ) : '';
	$license_valid = get_option( 'mcs_license_key_valid' );
	$checked       = ( get_option( 'mcs_license_key_valid' ) == 'valid' ) ? true : false;
	
	if ( $license ) {
		$license = "
		License: $license, $license_valid";
	}
	
	// send fields for all plugins
	$wp_version = get_bloginfo( 'version' );
	$home_url   = home_url();
	$wp_url     = site_url();
	$language   = get_bloginfo( 'language' );
	$charset    = get_bloginfo( 'charset' );
	// server
	$php_version = phpversion();

	$admin_email = get_option( 'admin_email' );
	// theme data
	$theme         = wp_get_theme();
	$theme_name    = $theme->Name;
	$theme_uri     = $theme->ThemeURI;
	$theme_parent  = $theme->Template;
	$theme_version = $theme->Version;

	// plugin data
	$plugins        = get_plugins();
	$plugins_string = '';

	foreach ( array_keys( $plugins ) as $key ) {
		if ( is_plugin_active( $key ) ) {
			$plugin         =& $plugins[ $key ];
			$plugin_name    = $plugin['Name'];
			$plugin_uri     = $plugin['PluginURI'];
			$plugin_version = $plugin['Version'];
			$plugins_string .= "$plugin_name: $plugin_version; $plugin_uri\n";
		}
	}
	$data    = "
================ Installation Data ====================
==My Calendar:==
Version: $version
DB Version: $mc_db_version
URI: $mc_uri
CSS: $mc_css$license
Requester Email: $current_user->user_email
Admin Email: $admin_email

==WordPress:==
Version: $wp_version
URL: $home_url
Install: $wp_url
Language: $language
Charset: $charset

==Extra info:==
PHP Version: $php_version
Server Software: $_SERVER[SERVER_SOFTWARE]
User Agent: $_SERVER[HTTP_USER_AGENT]

==Theme:==
Name: $theme_name
URI: $theme_uri
Parent: $theme_parent
Version: $theme_version

==Active Plugins:==
$plugins_string
";
	$request = '';
	if ( isset( $_POST['mc_support'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( "Security check failed" );
		}
		$request       = ( ! empty( $_POST['support_request'] ) ) ? stripslashes( $_POST['support_request'] ) : false;
		$has_donated   = ( $_POST['has_donated'] == 'on' ) ? "Donor" : "No donation";
		$has_purchased = ( $checked ) ? "Purchaser" : "No purchase";
		$has_read_faq  = ( $_POST['has_read_faq'] == 'on' ) ? "Read FAQ" : false;
		$subject       = "My Calendar support request. $has_donated; $has_purchased";
		$message       = $request . "\n\n" . $data;
		// Get the site domain and get rid of www. from pluggable.php
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		$from_email = 'wordpress@' . $sitename;
		$from       = "From: \"$current_user->username\" <$from_email>\r\nReply-to: \"$current_user->username\" <$current_user->user_email>\r\n";

		if ( ! $has_read_faq ) {
			echo "<div class='message error'><p>" . __( 'Please read the FAQ and other Help documents before making a support request.', 'my-calendar' ) . "</p></div>";
		} elseif ( ! $request ) {
			echo "<div class='message error'><p>" . __( 'Please describe your problem in detail. I\'m not psychic.', 'my-calendar' ) . "</p></div>";
		} else {
			$sent = wp_mail( "plugins@joedolson.com", $subject, $message, $from );
			if ( $sent ) {
				if ( $has_donated == 'Donor' || $has_purchased == 'Purchaser' ) {
					echo "<div class='message updated'><p>" . __( 'Thank you for supporting the continuing development of this plug-in! I\'ll get back to you as soon as I can.', 'my-calendar' ) . "</p></div>";
				} else {
					echo "<div class='message updated'><p>" . __( 'I\'ll get back to you as soon as I can, after dealing with any support requests from plug-in supporters.', 'my-calendar' ) . "</p></div>";
				}
			} else {
				echo "<div class='message error'><p>" . __( "Sorry! I couldn't send that message. Here's the text of your request:", 'my-calendar' ) . "</p><p>" . sprintf( __( '<a href="%s">Contact me here</a>, instead</p>', 'my-calendar' ), 'https://www.joedolson.com/contact/get-support/' ) . "<pre>$request</pre></div>";
			}
		}
	}

	echo "
	<form method='post' action='" . admin_url( 'admin.php?page=my-calendar-help' ) . "'>
		<div><input type='hidden' name='_wpnonce' value='" . wp_create_nonce( 'my-calendar-nonce' ) . "' /></div>
		<div>
		<code>" . __( 'From:', 'my-calendar' ) . " \"$current_user->display_name\" &lt;$current_user->user_email&gt;</code>
		</p>
		<p>
			<input type='checkbox' name='has_read_faq' id='has_read_faq' value='on' required='required' aria-required='true' /> <label for='has_read_faq'>" . __( 'I have read <a href="http://www.joedolson.com/my-calendar/faq/">the FAQ for this plug-in</a>.', 'my-calendar' ) . " <span>(required)</span></label>
		</p>
		<p>
			<input type='checkbox' name='has_donated' id='has_donated' value='on' $checked /> <label for='has_donated'>" . sprintf( __( 'I <a href="%s">made a donation to help support this plug-in</a>.', 'my-calendar' ), 'https://www.joedolson.com/donate/' ) . "</label>
		</p>
		<p>
			<label for='support_request'>Support Request:</label><br /><textarea name='support_request' id='support_request' required aria-required='true' cols='80' rows='10' class='widefat'>" . stripslashes( $request ) . "</textarea>
		</p>
		<p>
			<input type='submit' value='" . __( 'Send Support Request', 'my-calendar' ) . "' name='mc_support' class='button-primary' />
		</p>
		<p>" .
	     __( 'The following additional information will be sent with your support request:', 'my-calendar' )
	     . "</p>
		<div class='mc_support'>
		" . wpautop( $data ) . "
		</div>
		</div>
	</form>";
}

// Actions -- these are action hooks attached to My Calendar events, usable to add additional actions during those events.
add_action( 'init', 'mc_register_actions' );
function mc_register_actions() {
	add_filter( 'mc_event_registration', 'mc_standard_event_registration', 10, 4 );
	add_filter( 'mc_datetime_inputs', 'mc_standard_datetime_input', 10, 4 );
	add_action( 'mc_transition_event', 'mc_tweet_approval', 10, 2 );
	add_action( 'mc_save_event', 'mc_event_post', 10, 3 );
	add_action( 'mc_delete_event', 'mc_event_delete_post', 10, 2 );
	add_action( 'mc_mass_delete_events', 'mc_event_delete_posts', 10, 1 );
	add_action( 'parse_request', 'my_calendar_api' );
}

// Filters
add_filter( 'post_updated_messages', 'mc_posttypes_messages' );
add_filter( 'tmp_grunion_allow_editor_view', '__return_false' );

// Actions
add_action( 'init', 'mc_taxonomies', 0 );
add_action( 'init', 'mc_posttypes' );

add_action( 'load-options-permalink.php', 'mc_load_permalinks' );
function mc_load_permalinks() {
	if( isset( $_POST['mc_cpt_base'] ) )	{
		update_option( 'mc_cpt_base', sanitize_text_field( $_POST['mc_cpt_base'] ) );
	}
	
	// Add a settings field to the permalink page
	add_settings_field( 'mc_cpt_base', __( 'My Calendar Events base' ), 'mc_field_callback', 'permalink', 'optional', array( 'label_for'=>'mc_cpt_base' ) );
}

function mc_field_callback() {
	$value = ( get_option( 'mc_cpt_base' ) != '' ) ? get_option( 'mc_cpt_base' ) : 'mc-events';	
	echo '<input type="text" value="' . esc_attr( $value ) . '" name="mc_cpt_base" id="mc_cpt_base" class="regular-text" />';
}

function mc_posttypes() {
	$arguments = array(
		'public'              => apply_filters( 'mc_event_posts_public', true ),
		'publicly_queryable'  => true,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => apply_filters( 'mc_show_custom_posts_in_menu', false ),
		'menu_icon'           => null,
		'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields' )
	);

	$types   = array(
		'mc-events' => array(
			__( 'event', 'my-calendar' ),
			__( 'events', 'my-calendar' ),
			__( 'Event', 'my-calendar' ),
			__( 'Events', 'my-calendar' ),
			$arguments
		),
	);
	$enabled = array( 'mc-events' );
	$slug = ( get_option( 'mc_cpt_base' ) != '' ) ? get_option( 'mc_cpt_base' ) : 'mc-events';
	if ( is_array( $enabled ) ) {
		foreach ( $enabled as $key ) {
			$value  =& $types[ $key ];
			$labels = array(
				'name'               => _x( $value[3], 'post type general name' ),
				'singular_name'      => _x( $value[2], 'post type singular name' ),
				'add_new'            => _x( 'Add New', $key, 'my-calendar' ),
				'add_new_item'       => sprintf( __( 'Create New %s', 'my-calendar' ), $value[2] ),
				'edit_item'          => sprintf( __( 'Modify %s', 'my-calendar' ), $value[2] ),
				'new_item'           => sprintf( __( 'New %s', 'my-calendar' ), $value[2] ),
				'view_item'          => sprintf( __( 'View %s', 'my-calendar' ), $value[2] ),
				'search_items'       => sprintf( __( 'Search %s', 'my-calendar' ), $value[3] ),
				'not_found'          => sprintf( __( 'No %s found', 'my-calendar' ), $value[1] ),
				'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'my-calendar' ), $value[1] ),
				'parent_item_colon'  => ''
			);
			$raw    = $value[4];
			$args   = array(
				'labels'              => $labels,
				'public'              => $raw['public'],
				'publicly_queryable'  => $raw['publicly_queryable'],
				'exclude_from_search' => $raw['exclude_from_search'],
				'show_ui'             => $raw['show_ui'],
				'show_in_menu'        => $raw['show_in_menu'],
				'menu_icon'           => ( $raw['menu_icon'] == null ) ? plugins_url( 'images', __FILE__ ) . "/icon.png" : $raw['menu_icon'],
				'query_var'           => true,
				'rewrite'             => array(
					'with_front' => false,
					'slug'       => apply_filters( 'mc_event_slug', $slug )
				),
				'hierarchical'        => false,
				'menu_position'       => 20,
				'supports'            => $raw['supports']
			);
			register_post_type( $key, $args );
		}
	}
}

add_filter( 'the_posts', 'mc_close_comments' );
/**
 * Most people don't want comments open on events. This will automatically close them. 
 */
function mc_close_comments( $posts ) {
	if ( !is_single() || empty( $posts ) ) { return $posts; }
	
	if ( 'mc-events' == get_post_type($posts[0]->ID) ) {
		if ( apply_filters( 'mc_autoclose_comments', true ) && $posts[0]->comment_status != 'closed' ) {
			$posts[0]->comment_status = 'closed';
			$posts[0]->ping_status    = 'closed';
			wp_update_post( $posts[0] );
		}
	}
	
	return $posts;
}

/** 
 * By default, disable comments on event posts on save
 */
add_filter( 'default_content', 'mc_posttypes_defaults', 10, 2 );
function mc_posttypes_defaults( $post_content, $post ) {
	if ( $post->post_type ) {
		switch ( $post->post_type ) {
			case 'mc-events':
				$post->comment_status = 'closed';
				break;
		}
	}

	return $post_content;
}

/**
 * Create taxonomies on My Calendar custom post types
 */
function mc_taxonomies() {
	global $mc_types;
	$types   = $mc_types;
	$enabled = array( 'mc-events' );
	if ( is_array( $enabled ) ) {
		foreach ( $enabled as $key ) {
			$value = $types[ $key ];
			register_taxonomy(
				"mc-event-category",    // internal name = machine-readable taxonomy name
				array( $key ),    // object type = post, page, link, or custom post-type
				array(
					'hierarchical' => true,
					'label'        => sprintf( __( '%s Categories', 'my-calendar' ), $value[2] ),
					// the human-readable taxonomy name
					'query_var'    => true,
					// enable taxonomy-specific querying
					'rewrite'      => array( 'slug' => apply_filters( 'mc_event_category_slug', 'mc-event-category' ) ),
					// pretty permalinks for your taxonomy?
				)
			);
		}
	}
}

function mc_posttypes_messages( $messages ) {
	global $post, $post_ID, $mc_types;
	$types   = $mc_types;
	$enabled = array( 'mc-events' );
	if ( is_array( $enabled ) ) {
		foreach ( $enabled as $key ) {
			$value            = $types[ $key ];
			$messages[ $key ] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => sprintf( __( '%1$s updated. <a href="%2$s">View %1$s</a>' ), $value[2], esc_url( get_permalink( $post_ID ) ) ),
				2  => __( 'Custom field updated.' ),
				3  => __( 'Custom field deleted.' ),
				4  => sprintf( __( '%s updated.' ), $value[2] ),
				/* translators: %s: date and time of the revision */
				5  => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$ss' ), $value[2], wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6  => sprintf( __( '%1$s published. <a href="%2$s">View %3$s</a>' ), $value[2], esc_url( get_permalink( $post_ID ) ), $value[0] ),
				7  => sprintf( __( '%s saved.' ), $value[2] ),
				8  => sprintf( __( '%1$s submitted. <a target="_blank" href="%2$s">Preview %3$s</a>' ), $value[2], esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ), $value[0] ),
				9  => sprintf( __( '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %4$s</a>' ),
					$value[2], date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ), $value[0] ),
				10 => sprintf( __( '%1$s draft updated. <a target="_blank" href="%2$s">Preview %3$s</a>' ), $value[2], esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ), $value[0] ),
			);
		}
	}

	return $messages;
}

add_action( 'admin_init', 'mc_dismiss_notice' );
function mc_dismiss_notice() {
	if ( isset( $_GET['dismiss'] ) && $_GET['dismiss'] == 'update' ) {
		update_option( 'mc_update_notice', 1 );
	}
}

add_action( 'admin_notices', 'mc_update_notice' );
function mc_update_notice() {
	// Deprecate this notice when 2.3 no longer in upgrade cycles
	if ( current_user_can( 'activate_plugins' ) && get_option( 'mc_update_notice' ) == 0 || ! get_option( 'mc_update_notice' ) ) {
		$dismiss = admin_url( 'admin.php?page=my-calendar-behaviors&dismiss=update' );
		echo "<div class='updated fade'><p>" . sprintf( __( "<strong>Update notice:</strong> if you use custom JS with My Calendar, you need to activate your custom scripts following this update. <a href='%s'>Dismiss Notice</a>", 'wp-to-twitter' ), $dismiss ) . "</p></div>";
	}
	if ( current_user_can( 'manage_options' ) && isset( $_GET['page'] ) && stripos( $_GET['page'], 'my-calendar' ) !== false ) {
		if ( get_option( 'mc_remote' ) == 'true' ) {
			echo "<div class='updated'><p>" . sprintf( __( 'My Calendar is configured to retrieve events from a remote source. %s', 'my-calendar' ),  '<a href="' . admin_url( 'admin.php?page=my-calendar-config' ) . '">' . __( 'Update Settings', 'my-calendar' ) . '</a>' ) . "</p></div>";
		}
	}
}