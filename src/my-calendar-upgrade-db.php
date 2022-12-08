<?php
/**
 * Upgrade Database.
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
 * Check whether the My Calendar database is up to date
 */
function my_calendar_check_db() {
	if ( 'true' === mc_get_option( 'remote' ) && function_exists( 'mc_remote_db' ) ) {
		return;
	}

	global $wpdb;
	$cols         = $wpdb->get_col( 'DESC ' . my_calendar_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$needs_update = false;

	if ( ! in_array( 'event_tickets', $cols, true ) ) {
		$needs_update = true;
	}

	if ( isset( $_POST['upgrade'] ) && 'true' === $_POST['upgrade'] ) {
		mc_upgrade_db();
		?>
		<div class='upgrade-db updated'>
			<p><?php esc_html_e( 'My Calendar Database is updated.', 'my-calendar' ); ?></p>
		</div>
		<?php
	} elseif ( $needs_update ) {
		if ( 'my-calendar-config' === $_GET['page'] ) {
			?>
			<div class='upgrade-db error'>
				<p>
					<?php esc_html_e( 'The My Calendar database needs to be updated.', 'my-calendar' ); ?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-config' ) ); ?>">
					<div>
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
						<input type="hidden" name="upgrade" value="true" />
					</div>
					<p>
						<input type="submit" value="<?php esc_attr_e( 'Update now', 'my-calendar' ); ?>" name="update-calendar" class="button-primary"/>
					</p>
				</form>
			</div>
			<?php
		} else {
			?>
			<div class='upgrade-db error'>
			<p>
				<?php esc_html_e( 'The My Calendar database needs to be updated.', 'my-calendar' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-config' ) ); ?>"><?php esc_html_e( 'Update now', 'my-calendar' ); ?></a>
			</p>
			</div>
			<?php
		}
	}
}

/**
 * Migrate settings from individual options to a single collective option.
 */
function mc_migrate_settings() {
	$options = array(
		'display_single'               => get_option( 'mc_display_single' ),
		'display_main'                 => get_option( 'mc_display_main' ),
		'display_mini'                 => get_option( 'mc_display_mini' ),
		'use_permalinks'               => get_option( 'mc_use_permalinks' ),
		'use_styles'                   => get_option( 'mc_use_styles' ),
		'show_months'                  => get_option( 'mc_show_months' ),
		'calendar_javascript'          => get_option( 'mc_calendar_javascript' ),
		'list_javascript'              => get_option( 'mc_list_javascript' ),
		'mini_javascript'              => get_option( 'mc_mini_javascript' ),
		'ajax_javascript'              => get_option( 'mc_ajax_javascript' ),
		'show_js'                      => get_option( 'mc_show_js' ),
		'notime_text'                  => get_option( 'mc_notime_text' ),
		'hide_icons'                   => get_option( 'mc_hide_icons' ),
		'event_link_expires'           => get_option( 'mc_event_link_expires' ),
		'apply_color'                  => get_option( 'mc_apply_color' ),
		'input_options'                => get_option( 'mc_input_options' ),
		'input_options_administrators' => get_option( 'mc_input_options_administrators' ),
		'default_admin_view'           => get_option( 'mc_default_admin_view' ),
		'event_mail'                   => get_option( 'mc_event_mail' ),
		'event_mail_to'                => get_option( 'mc_event_mail_to' ),
		'event_mail_from'              => get_option( 'mc_event_mail_from' ),
		'event_mail_subject'           => get_option( 'mc_event_mail_subject' ),
		'event_mail_message'           => get_option( 'mc_event_mail_message' ),
		'week_format'                  => get_option( 'mc_week_format' ),
		'date_format'                  => get_option( 'mc_date_format' ),
		'templates'                    => get_option( 'mc_templates' ),
		'css_file'                     => get_option( 'mc_css_file' ),
		'style_vars'                   => get_option( 'mc_style_vars' ),
		'show_weekends'                => get_option( 'mc_show_weekends' ),
		'convert'                      => get_option( 'mc_convert' ),
		'multisite_show'               => get_option( 'mc_multisite_show' ),
		'topnav'                       => get_option( 'mc_topnav' ),
		'bottomnav'                    => get_option( 'mc_bottomnav' ),
		'default_direction'            => get_option( 'mc_default_direction' ),
		'remote'                       => get_option( 'mc_remote' ),
		'gmap_api_key'                 => get_option( 'mc_gmap_api_key' ),
	);
	add_option( 'my_calendar_options', $options );
	// Remove old options.
	delete_option( 'mc_display_single' );
	delete_option( 'mc_display_main' );
	delete_option( 'mc_display_mini' );
	delete_option( 'mc_use_permalinks' );
	delete_option( 'mc_use_styles' );
	delete_option( 'mc_show_months' );
	delete_option( 'mc_calendar_javascript' );
	delete_option( 'mc_list_javascript' );
	delete_option( 'mc_mini_javascript' );
	delete_option( 'mc_ajax_javascript' );
	delete_option( 'mc_show_js' );
	delete_option( 'mc_notime_text' );
	delete_option( 'mc_hide_icons' );
	delete_option( 'mc_event_link_expires' );
	delete_option( 'mc_apply_color' );
	delete_option( 'mc_input_options' );
	delete_option( 'mc_input_options_administrators' );
	delete_option( 'mc_default_admin_view' );
	delete_option( 'mc_event_mail' );
	delete_option( 'mc_event_mail_to' );
	delete_option( 'mc_event_mail_from' );
	delete_option( 'mc_event_mail_subject' );
	delete_option( 'mc_event_mail_message' );
	delete_option( 'mc_week_format' );
	delete_option( 'mc_date_format' );
	delete_option( 'mc_templates' );
	delete_option( 'mc_css_file' );
	delete_option( 'mc_style_vars' );
	delete_option( 'mc_show_weekends' );
	delete_option( 'mc_convert' );
	delete_option( 'mc_multisite_show' );
	delete_option( 'mc_topnav' );
	delete_option( 'mc_bottomnav' );
	delete_option( 'mc_default_direction' );
	delete_option( 'mc_show_event_vcal' );
	delete_option( 'mc_remote' );
	delete_option( 'mc_gmap_api_key' );
}

/**
 * Execute DB upgrade.
 */
function mc_upgrade_db() {
	$globals = mc_globals();

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $globals['initial_db'] );
	dbDelta( $globals['initial_occur_db'] );
	dbDelta( $globals['initial_cat_db'] );
	dbDelta( $globals['initial_rel_db'] );
	dbDelta( $globals['initial_loc_db'] );
	dbDelta( $globals['initial_loc_rel_db'] );
	update_option( 'mc_db_version', mc_get_version() );
}
