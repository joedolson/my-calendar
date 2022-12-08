<?php
/**
 * Manage My Calendar scripting.
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
 * Save changes to script configuration.
 */
function my_calendar_behaviors_save() {
	if ( isset( $_POST['mc-js-save'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'My Calendar: Security check failed' );
		}

		mc_update_option( 'calendar_javascript', ( empty( $_POST['calendar_js'] ) ) ? 0 : 1 );
		mc_update_option( 'list_javascript', ( empty( $_POST['list_js'] ) ) ? 0 : 1 );
		mc_update_option( 'mini_javascript', ( empty( $_POST['mini_js'] ) ) ? 0 : 1 );
		mc_update_option( 'ajax_javascript', ( empty( $_POST['ajax_js'] ) ) ? 0 : 1 );

		$mc_show_js = ( '' === $_POST['mc_show_js'] ) ? '' : $_POST['mc_show_js'];
		mc_update_option( 'show_js', $mc_show_js );

		wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=my-calendar-design&scriptaction=saved#my-calendar-scripts' ) ) );
	}
}
add_action( 'admin_init', 'my_calendar_behaviors_save' );

/**
 * Edit or configure scripts used with My Calendar
 */
function my_calendar_behaviors_edit() {
	if ( ! current_user_can( 'mc_edit_behaviors' ) ) {
		echo wp_kses_post( '<p>' . __( 'You do not have permission to customize scripts on this site.', 'my-calendar' ) . '</p>' );
		return;
	}
	if ( isset( $_GET['scriptaction'] ) && 'saved' === $_GET['scriptaction'] ) {
		mc_show_notice( __( 'Behavior Settings saved', 'my-calendar' ) );
	}
	$mc_show_js = mc_get_option( 'show_js' );
	?>
	<form id="my-calendar" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-design' ) ); ?>#my-calendar-scripts">
		<div>
			<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
		</div>
		<p>
			<label for="mc_show_js"><?php esc_html_e( 'Insert scripts on these pages (comma separated post IDs)', 'my-calendar' ); ?></label>
			<input type="text" id="mc_show_js" name="mc_show_js" value="<?php echo esc_attr( stripslashes( $mc_show_js ) ); ?>"/>
		</p>

		<div class='controls'>
			<ul class="checkboxes">
				<li>
					<input type="checkbox" id="calendar_js" name="calendar_js" value="1" <?php mc_is_checked( 'mc_calendar_javascript', 1 ); ?>/>
					<label for="calendar_js"><?php esc_html_e( 'Disable Grid JS', 'my-calendar' ); ?></label>
				</li>
				<li>
					<input type="checkbox" id="list_js" name="list_js" value="1" <?php mc_is_checked( 'mc_list_javascript', 1 ); ?> />
					<label for="list_js"><?php esc_html_e( 'Disable List JS', 'my-calendar' ); ?></label>
				</li>
				<li>
					<input type="checkbox" id="mini_js" name="mini_js" value="1" <?php mc_is_checked( 'mc_mini_javascript', 1 ); ?> />
					<label for="mini_js"><?php esc_html_e( 'Disable Mini JS', 'my-calendar' ); ?></label>
				</li>
				<li>
					<input type="checkbox" id="ajax_js" name="ajax_js" value="1" <?php mc_is_checked( 'mc_ajax_javascript', 1 ); ?> />
					<label for="ajax_js"><?php esc_html_e( 'Disable AJAX', 'my-calendar' ); ?></label></li>
			</ul>
		</div>
		<p>
			<input type="submit" name="mc-js-save" class="button-primary" value="<?php echo esc_attr( __( 'Save', 'my-calendar' ) ); ?>"/>
		</p>
	</form>
	<?php
}
