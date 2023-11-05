<?php
/**
 * My Calendar migration tools
 *
 * @category Settings
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Show migration form.
 */
function my_calendar_migration() {
	?>
<div class="wrap my-calendar-admin mc-migration-page" id="mc_migration">
	<h1><?php esc_html_e( 'My Calendar - Migration Tools', 'my-calendar' ); ?></h1>	
	
	<div class="settings postbox-container jcd-wide">
		<div class="metabox-holder">
			<div class="ui-sortable meta-box-sortables">
				<div class="inside">
					<?php
					if ( isset( $_POST['import'] ) && 'true' === $_POST['import'] ) {
						$nonce = $_REQUEST['_wpnonce'];
						if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
							wp_die( 'My Calendar: Security check failed' );
						}
						$source = ( in_array( $_POST['source'], array( 'calendar', 'tribe' ), true ) ) ? $_POST['source'] : false;
						if ( $source ) {
							my_calendar_import( $source );
						}
					}
					if ( function_exists( 'check_calendar' ) && 'true' !== get_option( 'ko_calendar_imported' ) ) {
						?>
						<div id="mc-importer" class='notice notice-info'>
							<p>
								<?php _e( 'You have the Calendar plugin by Kieran O\'Shea installed. You can import those events and categories into My Calendar.', 'my-calendar' ); ?>
							</p>

							<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-config' ) ); ?>">
								<div>
									<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
									<input type="hidden" name="import" value="true" />
									<input type="hidden" name="source" value="calendar" />
									<input type="submit" value="<?php _e( 'Import from Calendar', 'my-calendar' ); ?>" name="import-calendar" class="button-primary"/>
								</div>
							</form>
						</div>
						<?php
					}
					delete_option( 'mc_tribe_imported' );
					if ( function_exists( 'tribe_get_event' ) && 'true' !== get_option( 'mc_tribe_imported' ) ) {
						?>
						<div id="mc-importer" class='notice notice-info'>
							<p>
								<?php _e( 'You have The Events Calendar installed. You can import those events, venues, and categories into My Calendar.', 'my-calendar' ); ?>
							</p>

							<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-config' ) ); ?>">
								<div>
									<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
									<input type="hidden" name="import" value="true" />
									<input type="hidden" name="source" value="tribe" />
									<input type="submit" value="<?php _e( 'Import Events', 'my-calendar' ); ?>" name="import-calendar" class="button-primary"/>
								</div>
							</form>
						</div>
						<?php
					}
					$in_progress = get_option( 'mc_import_running' );
					mc_display_progress( $in_progress );
					?>
				</div>
			</div>
		</div>
	</div>
</div>
	<?php
}