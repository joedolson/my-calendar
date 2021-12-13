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
	<h1><?php esc_html_e( 'How to use My Calendar', 'my-calendar' ); ?></h1>
	<div class="mc-tabs">
		<div class="tabs" role="tablist" data-default="my-calendar-start">
			<button type="button" role="tab" aria-selected="false"  id="tab_start" aria-controls="my-calendar-start"><?php esc_html_e( 'Getting Started', 'my-calendar' ); ?></button>
			<button type="button" role="tab" aria-selected="false"  id="tab_files" aria-controls="my-calendar-files"><?php esc_html_e( 'Custom Files', 'my-calendar' ); ?></button>
			<button type="button" role="tab" aria-selected="false"  id="tab_privacy" aria-controls="my-calendar-privacy"><?php esc_html_e( 'Privacy', 'my-calendar' ); ?></button>
			<button type="button" role="tab" aria-selected="false"  id="tab_support" aria-controls="my-calendar-support"><?php esc_html_e( 'Get Support', 'my-calendar' ); ?></button>
		</div>
	<div class="postbox-container jcd-wide">
	<div class="metabox-holder">

	<div class="ui-sortable meta-box-sortables" id="get-started">
		<div class="wptab postbox" aria-labelledby="tab_start" role="tabpanel" id="my-calendar-start">
			<h2 id="help"><?php esc_html_e( 'Getting Started', 'my-calendar' ); ?></h2>

			<div class="inside">
				<ul class='list'>
					<?php
					if ( ! mc_get_uri( 'boolean' ) ) {
						echo '<li>' . __( 'Add the My Calendar shortcode (<code>[my_calendar]</code>) to a page.', 'my-calendar' ) . '</li>';
						echo '<li>' . __( 'Assign your Calendar Page Location at <code>My Calendar > Settings > General</code>', 'my-calendar' ) . '</li>';
					} else {
						$permalink = mc_get_uri();
						$edit_url  = get_edit_post_link( absint( get_option( 'mc_uri_id' ) ) );
						// Translators: Calendar link, calendar edit link.
						echo '<li>' . sprintf( __( '<a href="%1$s">View your calendar</a> or <a href="%2$s">Edit the calendar page</a>' ), esc_url( $permalink ), esc_url( $edit_url ) ) . '</li>';
					}
					$add_categories = admin_url( 'admin.php?page=my-calendar-categories' );
					$add_locations  = admin_url( 'admin.php?page=my-calendar-locations' );
					$edit_events    = admin_url( 'admin.php?page=my-calendar-manage' );
					$add_events     = admin_url( 'admin.php?page=my-calendar' );
					// Translators: Add events link, manage events link.
					echo '<li>' . sprintf( __( '<a href="%1$s">Add events</a> and <a href="%2$s">administer your events</a>.', 'my-calendar' ), esc_url( $add_events ), esc_url( $edit_events ) ) . '</li>';
					// Translators: Add categories link, add locations link.
					echo '<li>' . sprintf( __( '<a href="%1$s">Add categories</a> and <a href="%2$s">add locations</a>.', 'my-calendar' ), esc_url( $add_categories ), esc_url( $add_locations ) ) . '</li>';
					// Translators: Documentation URL.
					echo '<li>' . sprintf( __( 'When you\'re ready, <a href="%s">read the documentation</a>.', 'my-calendar' ), 'https://docs.joedolson.com/my-calendar/' ) . '</li>';
					?>
				</ul>
				<?php do_action( 'mc_before_help' ); ?>
			</div>
		</div>
	</div>

	<div class="ui-sortable meta-box-sortables" id="files">
		<div class="wptab postbox" aria-labelledby="tab_files" role="tabpanel" id="my-calendar-files">
			<h2><?php esc_html_e( 'Custom Files', 'my-calendar' ); ?></h2>

			<div class="inside">
				<h3><?php esc_html_e( 'Custom Icons Location', 'my-calendar' ); ?></h3>
				<ul>
					<li><code><?php echo str_replace( '/my-calendar', '', plugin_dir_path( __FILE__ ) ) . 'my-calendar-custom/'; ?></code></li>
				</ul>
				<h3><?php esc_html_e( 'Custom Styles Locations', 'my-calendar' ); ?></h3>
				<ul>
					<?php
					foreach ( mc_custom_dirs() as $dir ) {
						echo "<li><code>$dir</code></li>";
					}
					?>
				</ul>
				<p>
					<?php wp_kses_post( _e( 'Custom print, mobile, and tablet stylesheet file names: <code>mc-print.css</code>, <code>mc-mobile.css</code>, and <code>mc-tablet.css</code>.', 'my-calendar' ) ); ?>
				</p>
			</div>
		</div>
	</div>

	<div class="ui-sortable meta-box-sortables" id="privacy">
		<div class="wptab postbox" aria-labelledby="tab_privacy" role="tabpanel" id="my-calendar-privacy">
			<h2><?php esc_html_e( 'Privacy', 'my-calendar' ); ?></h2>

			<div class="inside">
				<h3><?php esc_html_e( 'Data Collection by My Calendar', 'my-calendar' ); ?></h3>
				<p>
					<?php _e( 'My Calendar collects no personally identifying data.', 'my-calendar' ); ?>
				</p>
				<p>
					<?php _e( 'My Calendar Pro, when installed, collects submitter names and email addresses when a public user submits an event from any public event submission form.', 'my-calendar' ); ?>
				</p>
				<h3><?php esc_html_e( 'Data Sharing by My Calendar', 'my-calendar' ); ?></h3>
				<p>
					<?php _e( 'The names and email addresses of people who author or host events are shared by My Calendar as part of the API output and iCal formatted event output. This data is sourced from user profiles, and will be destroyed or exported with that information.', 'my-calendar' ); ?>
				</p>
				<p>
					<?php _e( 'Events submitted by public users from any public event submission form using My Calendar Pro include names and emails as part of the event data. This data is destroyed when the event is deleted.', 'my-calendar' ); ?>
				</p>
				<h3><?php esc_html_e( 'Data Removal in My Calendar', 'my-calendar' ); ?></h3>
				<p>
					<?php _e( 'My Calendar supports the data export and removal features in WordPress 4.9.6 and later. When a data removal is requested, all events authored using the requested email address will be deleted. All events with that user assigned only as the host will remain, but the host will be changed.', 'my-calendar' ); ?>
				</p>
			</div>
		</div>
	</div>

	<?php do_action( 'mc_after_help' ); ?>

	<div class="ui-sortable meta-box-sortables" id="get-support">
		<div class="wptab postbox" aria-labelledby="tab_support" role="tabpanel" id="my-calendar-support">
			<h2 id="support"><?php esc_html_e( 'Get Support', 'my-calendar' ); ?></h2>

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
 * @param bool   $echo true to echo.
 *
 * @return string
 */
function mc_help_link( $link_text, $modal_title, $id, $echo = true ) {
	$url  = esc_url( admin_url( 'admin.php?help=' . (int) $id . '&query=' . urlencode( $modal_title ) . '&page=mc-contextual-help&TB_iframe=true&width=600&height=550&modal_window=true' ) );
	$link = sprintf(
		'<a href="%s" class="thickbox my-calendar-contextual-help" data-title="%s"><span class="dashicons dashicons-editor-help" aria-hidden="true"></span><span class="screen-reader-text">%s</span></a>',
		$url,
		$modal_title,
		$link_text
	);

	if ( $echo ) {
		echo wp_kses_post( $link );
	}

	return $link;
}

/**
 * Load modal for contextual help.
 */
function mc_enqueue_modal_assets() {
	$version = get_option( 'mc_version' );
	// Load only for My Calendar admin pages.
	if ( false !== stripos( get_current_screen()->id, 'my-calendar' ) || 'widgets' === get_current_screen()->id || isset( $_GET['post'] ) && get_option( 'mc_uri_id' ) === $_GET['post'] ) {
		// Enqueue assets from WordPress.
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'help-modal', plugins_url( 'js/help-modal.js', __FILE__ ), array( 'thickbox' ), $version, true );
	}
}
add_action( 'admin_enqueue_scripts', 'mc_enqueue_modal_assets' );

/**
 * Print the contents displayed within the modal window
 */
function mc_print_contextual_help() {
	$id = isset( $_REQUEST['help'] ) ? (int) $_REQUEST['help'] : false;
	$query = isset( $_REQUEST['query'] ) ? sanitize_text_field( $_REQUEST['query'] ) : '';
	?>
	<div class="modal-window-container">
	<?php
	echo wp_kses( mc_get_help_text( $id ), mc_kses_elements() );
	$return_url = add_query_arg( 's', $query, 'https://docs.joedolson.com/my-calendar/' );
	$return     = wp_kses_post( '<p class="docs-link"><a href="' . esc_url( $return_url ) . '">' . __( 'Documentation', 'my-calendar' ) . '</a></p>' );
	echo wp_kses_post( mc_get_help_footer( $return ) );
	?>
	</div>
	<?php
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
 *
 * @param string $return Return text if not using default.
 */
function mc_get_help_footer( $return = '' ) {
	if ( '' === $return ) {
		$return = '
		<ul class="help">
			<li>
				<a href="https://docs.joedolson.com/my-calendar/quick-start/">' . __( 'Documentation', 'my-calendar' ) . '</a>
			</li>
			<li>
				<a href="' . admin_url( 'admin.php?page=my-calendar-shortcodes' ) . '">' . __( 'Shortcode Generator', 'my-calendar' ) . '</a>
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
		</ul>';
	}

	return '<div class="mc-help-links">' . $return . '</div>';
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
		'3' => array(
			'title' => __( 'Navigation Keywords', 'my-calendar' ),
			// Translators: Settings URL.
			'text'  => sprintf( __( 'My Calendar shortcodes use keywords to represent the navigation interfaces that can be added to the calendar. The keywords can be added either above or below the calendar, and will appear in the order listed. These keywords are shown in the <a href="%s">My Calendar Display settings</a>.', 'my-calendar' ), admin_url( 'admin.php?page=my-calendar-config#my-calendar-output' ) ),
		),
		'4' => array(
			'title' => __( 'Pending', 'my-calendar' ),
			'text'  => '',
		),
		'5' => array(
			'title' => '',
			'text'  => mc_display_template_tags(),
		),
		'6' => array(
			'title' => __( 'Icon List', 'my-calendar' ),
			'text'  => mc_display_icons(),
		),
	);

	$title = ( '' !== $help[ $id ]['title'] ) ? '<h2>' . $help[ $id ]['title'] . '</h2>' : '';
	$text  = $help[ $id ]['text'];

	return sprintf( '%1$s<div class="mc-help-text">%2$s</div>', $title, wpautop( $text ) );
}

/**
 * Generate a list of icons.
 *
 * @return string
 */
function mc_display_icons() {
	$is_custom = mc_is_custom_icon();
	$output    = get_transient( 'my_calendar_svg_list' );
	if ( ! $output ) {
		if ( $is_custom ) {
			$dir       = plugin_dir_path( __FILE__ );
			$directory = str_replace( '/my-calendar', '', $dir ) . '/my-calendar-custom/';
		} else {
			$directory = dirname( __FILE__ ) . '/images/icons/';
		}
		$iconlist = mc_directory_list( $directory );
		if ( ! empty( $iconlist ) ) {
			$output = '<ul class="checkboxes icon-list">';
		}
		foreach ( $iconlist as $icon ) {
			$img     = mc_get_img( $icon, $is_custom );
			$output .= '<li class="category-icon"><code>' . $icon . '</code>' . $img . '</li>';
		}
		$output .= '</ul><p><a href="https://fontawesome.com/license">' . __( 'Icons by Font Awesome', 'my-calendar' ) . '</a></p>';
		set_transient( 'my_calendar_svg_list', $output, MONTH_IN_SECONDS );
	}

	return $output;
}
