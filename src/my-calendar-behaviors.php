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
		$options                        = array();
		$options['calendar_javascript'] = ( empty( $_POST['calendar_js'] ) ) ? 0 : sanitize_text_field( $_POST['calendar_js'] );
		$options['list_javascript']     = ( empty( $_POST['list_js'] ) ) ? 0 : sanitize_text_field( $_POST['list_js'] );
		$options['mini_javascript']     = ( empty( $_POST['mini_js'] ) ) ? 0 : sanitize_text_field( $_POST['mini_js'] );
		$options['ajax_javascript']     = ( empty( $_POST['ajax_js'] ) ) ? 0 : 1;
		$options['show_js']             = ( '' === $_POST['mc_show_js'] ) ? '' : sanitize_text_field( $_POST['mc_show_js'] );

		mc_update_options( $options );

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
			<fieldset>
				<legend><?php _e( 'Grid JavaScript', 'my-calendar' ); ?></legend>
				<ul class="checkboxes">
				<li>
					<input type="radio" id="calendar_js_disabled" name="calendar_js" value="1" <?php checked( mc_get_option( 'calendar_javascript' ), '1' ); ?>/>
					<label for="calendar_js_disabled"><?php esc_html_e( 'Disable JS', 'my-calendar' ); ?></label>
				</li>
				<li>
					<input type="radio" id="calendar_js_modal" name="calendar_js" value="modal" <?php checked( mc_get_option( 'calendar_javascript' ), 'modal' ); ?>/>
					<label for="calendar_js_modal"><?php esc_html_e( 'Modal', 'my-calendar' ); ?></label>
				</li>
				<li>
					<input type="radio" id="calendar_js_widget" name="calendar_js" value="disclosure" <?php checked( mc_get_option( 'calendar_javascript' ), 'disclosure' ); ?>/>
					<label for="calendar_js_widget"><?php esc_html_e( 'Disclosure Widget', 'my-calendar' ); ?></label>
				</li>
				</ul>
			</fieldset>
			<fieldset>
				<legend><?php _e( 'List JavaScript', 'my-calendar' ); ?></legend>
				<ul class="checkboxes">
				<li>
					<input type="radio" id="list_js_disabled" name="list_js" value="1" <?php checked( mc_get_option( 'list_javascript' ), '1' ); ?>/>
					<label for="list_js_disabled"><?php esc_html_e( 'Disable JS', 'my-calendar' ); ?></label>
				</li>
				<li>
					<input type="radio" id="list_js_modal" name="list_js" value="modal" <?php checked( mc_get_option( 'list_javascript' ), 'modal' ); ?>/>
					<label for="list_js_modal"><?php esc_html_e( 'Modal', 'my-calendar' ); ?></label>
				</li>
				<li>
					<input type="radio" id="list_js_widget" name="list_js" value="disclosure" <?php checked( mc_get_option( 'list_javascript' ), 'disclosure' ); ?>/>
					<label for="list_js_widget"><?php esc_html_e( 'Disclosure Widget', 'my-calendar' ); ?></label>
				</li>
				</ul>
			</fieldset>
			<fieldset>
				<legend><?php _e( 'Mini Widget JavaScript', 'my-calendar' ); ?></legend>
				<ul class="checkboxes">
				<li>
					<input type="radio" id="mini_js_disabled" name="mini_js" value="1" <?php checked( mc_get_option( 'mini_javascript' ), '1' ); ?>/>
					<label for="mini_js_disabled"><?php esc_html_e( 'Disable JS', 'my-calendar' ); ?></label>
				</li>
				<li>
					<input type="radio" id="mini_js_modal" name="mini_js" value="modal" <?php checked( mc_get_option( 'mini_javascript' ), 'modal' ); ?>/>
					<label for="mini_js_modal"><?php esc_html_e( 'Modal', 'my-calendar' ); ?></label>
				</li>
				<li>
					<input type="radio" id="mini_js_widget" name="mini_js" value="disclosure" <?php checked( mc_get_option( 'mini_javascript' ), 'disclosure' ); ?>/>
					<label for="mini_js_widget"><?php esc_html_e( 'Disclosure Widget', 'my-calendar' ); ?></label>
				</li>
				</ul>
			</fieldset>
			<ul class="checkboxes">
				<li>
					<input type="checkbox" id="ajax_js" name="ajax_js" value="1" <?php checked( mc_get_option( 'ajax_javascript' ), '1' ); ?> />
					<label for="ajax_js"><?php esc_html_e( 'Disable AJAX', 'my-calendar' ); ?></label></li>
			</ul>
		</div>
		<p>
			<input type="submit" name="mc-js-save" class="button-primary" value="<?php echo esc_attr( __( 'Save', 'my-calendar' ) ); ?>"/>
		</p>
	</form>
	<?php
}
