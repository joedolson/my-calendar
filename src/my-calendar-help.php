<?php
/**
 * Help page.
 *
 * @category Core
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display help.
 */
function my_calendar_help() {
	?>

	<div class="wrap my-calendar-admin">
	<h1><?php _e( 'How to use My Calendar', 'my-calendar' ); ?></h1>

	<div class="postbox-container jcd-wide">
	<div class="metabox-holder">

	<div class="ui-sortable meta-box-sortables" id="get-started">
		<div class="postbox">
			<h2 id="help"><?php _e( 'Getting Started', 'my-calendar' ); ?></h2>

			<div class="inside">
				<div class='mc-support-me'>
					<p>
						<?php
							// Translators: Donate URL, Upgrade URL.
							printf( __( 'Please, consider a <a href="%1$s">donation</a> or a <a href="%2$s">purchase</a> to support My Calendar!', 'my-calendar' ), 'https://www.joedolson.com/donate/', 'https://www.joedolson.com/my-calendar/pro/' );
						?>
					</p>
				</div>
				<ul class='list'>
					<?php
					if ( ! mc_get_uri( 'boolean' ) ) {
						echo '<li>' . __( 'Add the My Calendar shortcode (<code>[my_calendar]</code>) to a page.', 'my-calendar' ) . '</li>';
					}
					echo '<li>' . __( 'Add events by clicking on the Add/Edit Events link in the admin or on "Add Events" in the toolbar.', 'my-calendar' ) . '</li>';
					echo '<li>' . __( 'Select your preferred stylesheet in the Styles Editor', 'my-calendar' ) . '</li>';
					if ( mc_get_uri( 'boolean' ) ) {
						// Translators: Calendar URL.
						echo '<li>' . sprintf( __( 'View <a href="%s">your calendar</a>', 'my-calendar' ), mc_get_uri() ) . '</li>';
					}
					?>
				</ul>
				<?php do_action( 'mc_before_help' ); ?>
				<ul class="mc-settings checkboxes">
					<li><a href="<?php echo admin_url( 'admin.php?page=my-calendar-shortcodes' ); ?>#mc-generator"><?php _e( 'Shortcode Generator', 'my-calendar' ); ?></a></li>
					<li><a href="#files"><?php _e( 'Custom Files', 'my-calendar' ); ?></a></li>
					<li><a href="#privacy"><?php _e( 'Privacy', 'my-calendar' ); ?></a></li>
					<li><a href="#get-support"><?php _e( 'Get Support', 'my-calendar' ); ?></a></li>
				</ul>
			</div>
		</div>
	</div>

	<div class="ui-sortable meta-box-sortables" id="files">
		<div class="postbox">
			<h2><?php _e( 'Custom Files', 'my-calendar' ); ?></h2>

			<div class="inside">
				<h3><?php _e( 'Custom Icons Location', 'my-calendar' ); ?></h3>
				<ul>
					<li><code><?php echo str_replace( '/my-calendar', '', plugin_dir_path( __FILE__ ) ) . 'my-calendar-custom/'; ?></code></li>
				</ul>
				<h3><?php _e( 'Custom Styles Locations', 'my-calendar' ); ?></h3>
				<ul>
					<?php
					foreach ( mc_custom_dirs() as $dir ) {
						echo "<li><code>$dir</code></li>";
					}
					?>
				</ul>
				<p>
					<?php _e( 'Custom print, mobile, and tablet stylesheet file names: <code>mc-print.css</code>, <code>mc-mobile.css</code>, and <code>mc-tablet.css</code>.', 'my-calendar' ); ?>
				</p>
			</div>
		</div>
	</div>

	<div class="ui-sortable meta-box-sortables" id="privacy">
		<div class="postbox">
			<h2><?php _e( 'Privacy', 'my-calendar' ); ?></h2>

			<div class="inside">
				<h3><?php _e( 'Data Collection by My Calendar', 'my-calendar' ); ?></h3>
				<p>
					<?php _e( 'My Calendar collects no personally identifying data.', 'my-calendar' ); ?>
				</p>
				<p>
					<?php _e( 'My Calendar Pro, when installed, collects submitter names and email addresses when a public user submits an event from any public event submission form.', 'my-calendar' ); ?>
				</p>
				<h3><?php _e( 'Data Sharing by My Calendar', 'my-calendar' ); ?></h3>
				<p>
					<?php _e( 'The names and email addresses of people who author or host events are shared by My Calendar as part of the API output, RSS feeds, and iCal formatted event output. This data is sourced from user profiles, and will be destroyed or exported with that information.', 'my-calendar' ); ?>
				</p>
				<p>
					<?php _e( 'Events submitted by public users from any public event submission form using My Calendar Pro include names and emails as part of the event data. This data is destroyed when the event is deleted.', 'my-calendar' ); ?>
				</p>
				<h3><?php _e( 'Data Removal in My Calendar', 'my-calendar' ); ?></h3>
				<p>
					<?php _e( 'My Calendar supports the data export and removal features in WordPress 4.9.6 and later. When a data removal is requested, all events authored using the requested email address will be deleted. All events with that user assigned only as the host will remain, but the host will be changed.', 'my-calendar' ); ?>
				</p>
			</div>
		</div>
	</div>

	<?php do_action( 'mc_after_help' ); ?>

	<div class="ui-sortable meta-box-sortables" id="get-support">
		<div class="postbox">
			<h2 id="support"><?php _e( 'Get Support', 'my-calendar' ); ?></h2>

			<div class="inside">
				<div class='mc-support-me'>
					<p>
					<?php
					// Translators: Donate URL, Purchase URL.
					printf( __( 'Please, consider a <a href="%1$s">donation</a> or a <a href="%2$s">purchase</a> to support My Calendar!', 'my-calendar' ), 'https://www.joedolson.com/donate/', 'https://www.joedolson.com/my-calendar/pro/' );
					?>
					</p>
				</div>
				<?php
				if ( current_user_can( 'administrator' ) ) {
					mc_get_support_form();
				} else {
					_e( 'My Calendar support requests can only be sent by administrators.', 'my-calendar' );
				}
				?>
			</div>
		</div>

	</div>
	</div>
	</div>
	<?php mc_show_sidebar(); ?>

	</div>
	<?php
}

/**
 * Generate link for contextual help.
 *
 * @param string $link_text Link text.
 * @param string $modal_title Modal iframe title.
 * @param int    $id Help text ID.
 *
 * @return string
 */
function mc_help_link( $link_text, $modal_title, $id ) {
	$url  = admin_url( 'admin.php?help=' . (int) $id . '&page=mc-contextual-help&TB_iframe=true&width=600&height=550&modal_window=true' );
	$link = sprintf(
		'<a href="%s" class="thickbox my-calendar-contextual-help" data-title="%s"><span class="dashicons dashicons-editor-help" aria-hidden="true"></span><span class="screen-reader-text">%s</span></a>',
		$url,
		$modal_title,
		$link_text
	);

	return $link;
}

/**
 * Load modal for contextual help.
 */
function mc_enqueue_modal_assets() {
	// Load only for My Calendar admin pages.
	if ( false !== stripos( get_current_screen()->id, 'my-calendar' ) ) {
		// Enqueue assets from WordPress.
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'help-modal', plugins_url( 'js/help-modal.js', __FILE__ ), array( 'thickbox' ), '1.0.0', true );
	}
}
add_action( 'admin_enqueue_scripts', 'mc_enqueue_modal_assets' );

/**
 * Print the contents displayed within the modal window
 */
function mc_print_contextual_help() {
	$id = isset( $_REQUEST['help'] ) ? (int) $_REQUEST['help'] : false;
	echo '<div class="modal-window-container">';
	echo mc_get_help_text( $id );
	echo mc_get_help_footer();
	echo '</div>';
}

/**
 * Add custom CSS for contextual help modal.
 */
function mc_contextual_help_css() {
	// Check that we are on the right screen.
	if ( 'admin_page_mc-contextual-help' === get_current_screen()->id ) {
		wp_enqueue_style( 'mc-contextual-help', plugins_url( 'css/help.css', __FILE__ ) );
	}
}
add_action( 'admin_enqueue_scripts', 'mc_contextual_help_css' );

/**
 * Footer for contextual help modal.
 */
function mc_get_help_footer() {
	$return = '<div class="mc-help-links">
		<ul class="help">
			<li>
				<a href="https://docs.joedolson.com/my-calendar/quick-start/">' . __( 'Documentation', 'my-calendar' ) . '</a>
			</li>
			<li>
				<a href="' . admin_url( 'admin.php?page=my-calendar-shortcodes' ) . '#mc-generator">' . __( 'Shortcode Generator', 'my-calendar' ) . '</a>
			</li>
			<li>
				<a href="' . admin_url( 'admin.php?page=my-calendar-help' ) . '#get-support">' . __( 'Get Support', 'my-calendar' ) . '</a>
			</li>
		</ul>
		<ul class="help">
			<li>
				<div class="dashicons dashicons-editor-help" aria-hidden="true"></div>
				<a href="' . admin_url( 'admin.php?page=my-calendar-help' ) . '">' . __( 'My Calendar Help', 'my-calendar' ) . '</a>
			</li>
			<li>
				<div class="dashicons dashicons-yes" aria-hidden="true"></div>
				<a href="http://profiles.wordpress.org/joedolson/">' . __( 'Check out my other plugins', 'my-calendar' ) . '</a>
			</li>
			<li>
				<div class="dashicons dashicons-star-filled" aria-hidden="true"></div>
				<a href="http://wordpress.org/support/plugin/my-calendar/reviews/?filter=5">' . __( 'Rate this plugin 5 stars!', 'my-calendar' ) . '</a>
			</li>
			<li>
				<div class="dashicons dashicons-translation" aria-hidden="true"></div>
				<a href="http://translate.joedolson.com/projects/my-calendar">' . __( 'Help translate this plugin!', 'my-calendar' ) . '</a>
			</li>
		</ul>
	</div>';

	return $return;
}

/**
 * Get contextual help by ID.
 *
 * @param int $id Help ID.
 *
 * @return string
 */
function mc_get_help_text( $id ) {
	$help = array(
		'1' => array(
			'title' => __( 'Add Another Occurrence', 'my-calendar' ),
			'text'  => __( 'Create a duplicate copy of this event for another date. After creation, you can manage each event separately in the event manager.', 'my-calendar' ),
		),
		'2' => array(
			'title' => __( 'Repetition Pattern', 'my-calendar' ),
			'text'  => __( 'Recurring events repeat on a specific pattern. The individual dates of recurring events are references to the main event, and only the main event will show in the event manager.', 'my-calendar' ),
		),
	);

	$title = $help[ $id ]['title'];
	$text  = $help[ $id ]['text'];

	return sprintf( '<h2>%1$s</h2><div class="mc-help-text">%2$s</div>', $title, wpautop( $text ) );
}
