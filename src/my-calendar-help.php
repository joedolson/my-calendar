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
						$edit_url  = get_edit_post_link( absint( mc_get_option( 'uri_id' ) ) );
						// Translators: Calendar link, calendar edit link.
						echo '<li>' . sprintf( __( '<a href="%1$s">View your calendar</a> or <a href="%2$s">Edit the calendar page</a>', 'my-calendar' ), esc_url( $permalink ), esc_url( $edit_url ) ) . '</li>';
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
				<?php
				/**
				 * Execute action after My Calendar Help data.
				 *
				 * @hook mc_after_help
				 */
				do_action( 'mc_before_help' );
				?>
			</div>
		</div>
	</div>

	<div class="ui-sortable meta-box-sortables" id="files">
		<div class="wptab postbox" aria-labelledby="tab_files" role="tabpanel" id="my-calendar-files">
			<h2><?php esc_html_e( 'Custom Files', 'my-calendar' ); ?></h2>

			<div class="inside">
				<h3><?php esc_html_e( 'Custom Styles Locations', 'my-calendar' ); ?></h3>
				<p><?php _e( 'My Calendar custom style files can be saved in any of these locations. CSS files in these locations will be selectable from the stylesheet selector.', 'my-calendar' ); ?></p>
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
				<h3><?php esc_html_e( 'Custom Template Locations', 'my-calendar' ); ?></h3>
				<p><?php _e( 'Default My Calendar templates are found in <code>/wp-content/my-calendar/mc-templates/</code>. Copy those templates into a <code>/mc-templates/</code> directory in your theme to customize.', 'my-calendar' ); ?></p>
				<p><?php _e( 'Legacy My Calendar templates can be loaded as text files (.txt) from any of the allowed style directory locations.', 'my-calendar' ); ?></p>
				<h3><?php esc_html_e( 'Custom Icons Location', 'my-calendar' ); ?></h3>
				<ul>
					<li><code><?php echo str_replace( '/my-calendar', '', plugin_dir_path( __FILE__ ) ) . 'my-calendar-custom/icons/'; ?></code></li>
				</ul>
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

	<?php
	/**
	 * Execute action after My Calendar Help data.
	 *
	 * @hook mc_after_help
	 */
	do_action( 'mc_after_help' );
	?>

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
 * @param string $query Non-translatable version of modal title for search query.
 * @param int    $id Help text ID.
 * @param bool   $display True to echo.
 *
 * @return string
 */
function mc_help_link( $link_text, $modal_title, $query, $id, $display = true ) {
	$url  = esc_url( admin_url( 'admin.php?help=' . (int) $id . '&query=' . urlencode( $query ) . '&page=mc-contextual-help&TB_iframe=true&width=600&height=550&modal_window=true' ) );
	$link = sprintf(
		'<a href="%s" class="thickbox my-calendar-contextual-help" data-title="%s"><span class="dashicons dashicons-editor-help" aria-hidden="true"></span><span class="help-label-text">%s</span></a>',
		$url,
		$modal_title,
		$link_text
	);

	if ( $display ) {
		echo wp_kses_post( $link );
	}

	return $link;
}

/**
 * Load modal for contextual help.
 */
function mc_enqueue_modal_assets() {
	$version = mc_get_version();
	if ( SCRIPT_DEBUG ) {
		$version .= wp_rand( 10000, 100000 );
	}
	// Load only for My Calendar admin pages.
	if ( false !== stripos( get_current_screen()->id, 'my-calendar' ) || 'widgets' === get_current_screen()->id || isset( $_GET['post'] ) && mc_get_option( 'uri_id' ) === $_GET['post'] ) {
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
	$id    = isset( $_REQUEST['help'] ) ? (int) $_REQUEST['help'] : false;
	$query = isset( $_REQUEST['query'] ) ? sanitize_text_field( $_REQUEST['query'] ) : '';
	?>
	<div class="modal-window-container">
	<?php
	echo wp_kses( mc_get_help_text( $id ), mc_kses_elements() );
	$return_url = add_query_arg( 's', $query, 'https://docs.joedolson.com/my-calendar/' );
	$return     = wp_kses_post( '<p class="docs-link"><a target="_parent" href="' . esc_url( $return_url ) . '">' . __( 'Documentation', 'my-calendar' ) . '</a></p>' );
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
 * @param string $return_str Return text if not using default.
 */
function mc_get_help_footer( $return_str = '' ) {
	if ( '' === $return_str ) {
		$return_str = '
		<ul class="help">
			<li>
				<a href="https://docs.joedolson.com/my-calendar/">' . __( 'Documentation', 'my-calendar' ) . '</a>
			</li>
			<li>
				<a href="' . admin_url( 'admin.php?page=my-calendar-shortcodes' ) . '">' . __( 'Shortcode Generator', 'my-calendar' ) . '</a>
			</li>
			<li>
				<a href="' . admin_url( 'admin.php?page=my-calendar-help' ) . '#my-calendar-support">' . __( 'Get Support', 'my-calendar' ) . '</a>
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
				<a href="https://translate.wordpress.org/projects/wp-plugins/my-calendar/">' . __( 'Help translate this plugin!', 'my-calendar' ) . '</a>
			</li>
		</ul>';
	}

	return '<div class="mc-help-links">' . $return_str . '</div>';
}

/**
 * Get navigation keywords and descriptions.
 *
 * @param string $return_type Return data type.
 *
 * @return array|string
 */
function mc_navigation_keywords( $return_type = 'array' ) {
	$keywords = array(
		'nav'        => '<div class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></div> <div class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></div> <span>' . __( 'Primary Previous/Next Buttons', 'my-calendar' ) . '</span>',
		'toggle'     => '<div class="dashicons dashicons-list-view" aria-hidden="true"></div> <div class="dashicons dashicons-calendar"></div> <span>' . __( 'Switch between grid, card, and list views', 'my-calendar' ) . '</span>',
		'jump'       => '<div class="dashicons dashicons-redo" aria-hidden="true"></div> <span>' . __( 'Jump to any other month/year', 'my-calendar' ) . '</span>',
		'print'      => '<div class="dashicons dashicons-list-view" aria-hidden="true"></div> <span>' . __( 'Link to printable view', 'my-calendar' ) . '</span>',
		'timeframe'  => '<div class="dashicons dashicons-clock" aria-hidden="true"></div> <span>' . __( 'Toggle between day, week, and month view', 'my-calendar' ) . '</span>',
		'calendar'   => '<div class="dashicons dashicons-calendar" aria-hidden="true"></div> <span>' . __( 'My Calendar', 'my-calendar' ) . '</span>',
		'key'        => '<div class="dashicons dashicons-admin-network" aria-hidden="true"></div> <span>' . __( 'Categories', 'my-calendar' ) . '</span>',
		'feeds'      => '<div class="dashicons dashicons-rss" aria-hidden="true"></div> <span>' . __( 'iCal Subscription Links', 'my-calendar' ) . '</span>',
		'exports'    => '<div class="dashicons dashicons-calendar-alt" aria-hidden="true"></div> <span>' . __( 'Links to iCal Exports', 'my-calendar' ) . '</span>',
		'locations'  => '<div class="dashicons dashicons-location" aria-hidden="true"></div> <span>' . __( 'Location (dropdown)', 'my-calendar' ) . '</span>',
		'categories' => '<div class="dashicons dashicons-admin-network" aria-hidden="true"></div> <span>' . __( 'Categories (dropdown)', 'my-calendar' ) . '</span>',
		'access'     => '<div class="dashicons dashicons-universal-access" aria-hidden="true"></div> <span>' . __( 'Access (dropdown)', 'my-calendar' ) . '</span>',
		'search'     => '<div class="dashicons dashicons-search" aria-hidden="true"></div> <span>' . __( 'Search', 'my-calendar' ) . '</span>',
	);
	if ( 'array' === $return_type ) {
		return $keywords;
	} else {
		$output = '';
		foreach ( $keywords as $key => $desc ) {
			$output .= '<li><code>' . $key . '</code>' . $desc . '</li>';
		}
		$output = '<ul class="mc_keywords">' . $output . '</ul>';

		return $output;
	}
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
			'text'  => sprintf( __( 'My Calendar shortcodes use keywords to represent the navigation interfaces that can be added to the calendar. The keywords can be added either above or below the calendar, and will appear in the order listed. These keywords are shown in the <a href="%s">My Calendar Display settings</a>.', 'my-calendar' ), admin_url( 'admin.php?page=my-calendar-config#my-calendar-output' ) ) . mc_navigation_keywords( 'html' ),
		),
		'4' => array(
			'title' => __( 'Copying Events', 'my-calendar' ),
			'text'  => __( 'Copying an event (formerly described as creating an additional occurrence) sets up an additional copy of the event with a new set of dates and times. The new event is a completely separate event, associated with the source event via event groups. Marking a set of copied events as a "multi-day event" will cause them to be grouped as a single event in calendar views.', 'my-calendar' ),
		),
		'5' => array(
			'title' => '',
			'text'  => mc_display_template_tags(),
		),
		'6' => array(
			'title' => __( 'Icon List', 'my-calendar' ),
			'text'  => mc_display_icons(),
		),
		'7' => array(
			'title' => __( 'Migrating to custom CSS', 'my-calendar' ),
			'text'  => '<p>' . __( 'Since the first versions, My Calendar has allowed users to edit their stylesheets within the plug-in. Since version 1.7.0, it has supported using custom stylesheets from your theme directory or a custom plugin directory.', 'my-calendar' ) . '</p><p>' . __( 'Editing stylesheets within the plugin has been a problem since the beginning. Because of the possibility your stylesheets are customized, I need to copy and restore your stylesheets on every update. This also significantly restricts my ability to make changes to HTML structure or to fix problems with existing stylesheets.', 'my-calendar' ) . '</p><p>' . __( 'If you migrate your CSS to the custom style directory, you are taking responsibility for future updates to your CSS. If you leave them in place, then from 3.4.0 forward, your styles will be replaced by the latest versions in every update.', 'my-calendar' ) . '</p>',
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
	$output    = ( WP_DEBUG ) ? false : get_transient( 'mc_svg_list' );
	if ( ! $output ) {
		if ( $is_custom ) {
			$dir       = plugin_dir_path( __FILE__ );
			$directory = trailingslashit( str_replace( '/my-calendar', '', $dir ) ) . 'my-calendar-custom/icons';
		} else {
			$directory = trailingslashit( __DIR__ ) . 'images/icons/';
		}
		$iconlist = mc_directory_list( $directory );
		if ( ! empty( $iconlist ) ) {
			$output = '<ul class="checkboxes icon-list">';
		}
		foreach ( $iconlist as $icon ) {
			$img     = mc_get_img( $icon, $is_custom );
			$output .= '<li class="category-icon"><code>' . $icon . '</code>' . $img . '</li>';
		}
		$output .= '</ul>';
		set_transient( 'mc_svg_list', $output, HOUR_IN_SECONDS );
	}
	$append = ( $is_custom ) ? '' : '<p><a target="_parent" href="https://fontawesome.com/license">' . __( 'Icons by Font Awesome', 'my-calendar' ) . '</a></p>';

	return $output . $append;
}
