<?php
/**
 * Design settings page.
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
function my_calendar_design() {
	?>

	<div class="wrap my-calendar-admin">
		<h1><?php esc_html_e( 'Design', 'my-calendar' ); ?></h1>
		<div class="mc-tabs">
			<div class="tabs" role="tablist" data-default="my-calendar-style">
				<button type="button" role="tab" aria-selected="false"  id="tab_style" aria-controls="my-calendar-style"><?php esc_html_e( 'Style Editor', 'my-calendar' ); ?></button>
				<button type="button" role="tab" aria-selected="false"  id="tab_templates" aria-controls="my-calendar-templates"><?php esc_html_e( 'Templates', 'my-calendar' ); ?></button>
				<button type="button" role="tab" aria-selected="false"  id="tab_scripts" aria-controls="my-calendar-scripts"><?php esc_html_e( 'Scripts', 'my-calendar' ); ?></button>
			</div>
			<div class="postbox-container jcd-wide">
				<div class="metabox-holder">

					<div class="ui-sortable meta-box-sortables" id="my-calendar-styles">
						<div class="wptab postbox" aria-labelledby="tab_start" role="tabpanel" id="my-calendar-style">
							<h2 id="styles"><?php esc_html_e( 'Style Editor', 'my-calendar' ); ?></h2>
							<div class="inside">
							<?php my_calendar_style_edit(); ?>
							</div>
							<?php echo mc_display_contrast_variables(); ?>
						</div>
					</div>

					<div class="ui-sortable meta-box-sortables" id="templates">
						<div class="wptab postbox" aria-labelledby="tab_templates" role="tabpanel" id="my-calendar-templates">
							<h2>
							<?php
							_e( 'Template Editor', 'my-calendar' );
							mc_help_link( __( 'Template Tag Help', 'my-calendar' ), __( 'Template Tags', 'my-calendar' ), 'template tags', 5 );
							?>
							</h2>
							<?php
							echo ( isset( $_GET['mc_template'] ) && 'add-new' === $_GET['mc_template'] ) ? '' : wp_kses_post( '<p><a class="button" href="' . esc_url( add_query_arg( 'mc_template', 'add-new', admin_url( 'admin.php?page=my-calendar-design' ) ) ) . '#my-calendar-templates">' . __( 'Add New Template', 'my-calendar' ) . '</a></p>' );
							?>
							<div class="inside">
							<?php mc_templates_edit(); ?>
							</div>
						</div>
					</div>

					<div class="ui-sortable meta-box-sortables" id="scripts">
						<div class="wptab postbox" aria-labelledby="tab_scripts" role="tabpanel" id="my-calendar-scripts">
							<h2><?php esc_html_e( 'Script Manager', 'my-calendar' ); ?></h2>

							<div class="inside">
							<?php my_calendar_behaviors_edit(); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php mc_show_sidebar(); ?>

	</div>
	<?php
}
