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
						<div class="wptab postbox" aria-labelledby="tab_style" role="tabpanel" id="my-calendar-style">
							<h2 id="styles"><?php esc_html_e( 'Style Editor', 'my-calendar' ); ?></h2>
							<div class="inside">
							<?php my_calendar_style_edit(); ?>
							</div>
							<?php mc_display_contrast_variables(); ?>
						</div>
					</div>

					<div class="ui-sortable meta-box-sortables" id="templates">
						<div class="wptab postbox" aria-labelledby="tab_templates" role="tabpanel" id="my-calendar-templates">
						<?php
							$disable_templates = ( 'true' === mc_get_option( 'disable_legacy_templates' ) ) ? true : false;
							if ( $disable_templates ) {
								echo '<h2>' . esc_html__( 'Template Documentation', 'my-calendar' ) . '</h2>';
							} else {
								echo '<h2>' . esc_html__( 'Template Editor (Legacy)', 'my-calendar' ) . '</h2>';
								echo '<p><span class="mc-flex">';
								echo ( isset( $_GET['mc_template'] ) && 'add-new' === $_GET['mc_template'] ) ? '' : wp_kses_post( '<a class="button" href="' . esc_url( add_query_arg( 'mc_template', 'add-new', admin_url( 'admin.php?page=my-calendar-design' ) ) ) . '#my-calendar-templates">' . __( 'Add New Template', 'my-calendar' ) . '</a>' );
								mc_help_link( __( 'Template Tag Help', 'my-calendar' ), __( 'Template Tags', 'my-calendar' ), 'template tags', 5 );
								echo '</span></p>';
							}
							?>
							<div class="inside">
								<h3><?php esc_html_e( 'Default List Template', 'my-calendar' ); ?></h3>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-design#my-calendar-templates' ) ); ?>">
									<div>
										<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'my-calendar-nonce' ) ); ?>" />
									<?php
									if ( ! empty( $_POST ) ) {
										$nonce = $_REQUEST['_wpnonce'];
										if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
											wp_die( 'My Calendar: Security check failed' );
										}
										// Custom sanitizing.
										$post = map_deep( $_POST, 'sanitize_textarea_field' );
										if ( isset( $post['mc_list_template'] ) ) {
											mc_update_option( 'list_template', $post['mc_list_template'] );
											mc_show_notice( __( 'My Calendar List Template Updated', 'my-calendar' ), true, false, 'success' );
										}
									}
									$template_options = mc_select_preset_templates();
									mc_settings_field(
										array(
											'name'    => 'mc_list_template',
											'label'   => __( 'Default List Template', 'my-calendar' ),
											'default' => $template_options,
											'note'    => __( 'Default format for upcoming event, search results, and other list widgets and shortcodes. Any custom template overrides this.', 'my-calendar' ),
											'type'    => 'select',
										)
									);
									?>
									</div>
									<p>
										<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'my-calendar' ); ?>">
									</p>
								</form>
								<div class="list-templates">
									<h4><?php esc_html_e( 'Preview', 'my-calendar' ); ?></h4>
									<p><?php esc_html_e( 'The preview shows your next few upcoming events.', 'my-calendar' ); ?>
									<?php
									$default   = '<strong>{timerange after=", "}{daterange}</strong> &#8211; {linking_title}';
									$templates = array( 'list', 'list_preset_1', 'list_preset_2', 'list_preset_3', 'list_preset_4' );
									foreach ( $templates as $template ) {
										echo '<div class="mc-list-preview ' . esc_attr( $template ) . '">';
										$template  = ( 'list' === $template ) ? $default : $template;
										$atts      = array(
											'before'   => 0,
											'after'    => 3,
											'template' => $template,
										);
										echo my_calendar_insert_upcoming( $atts );
										echo '</div>';
									}
									?>
								</div>
							<?php
							echo '<p>';
							// translators: URL for the PHP templating docs.
							printf( wp_kses_post( __( 'Learn about the <a href="%s">PHP templating system in My Calendar</a>.', 'my-calendar' ) ), 'https://docs.joedolson.com/my-calendar/php-templates/' );
							echo '</p>';
							mc_templates_edit();
							?>
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

/**
 * Get the array of possible options for preset templates.
 *
 * @return array
 */
function mc_select_preset_templates() {
	$template_options = array(
		'list'          => '--',
		'list_preset_1' => __( 'Template 1: (short date, linked title, time, brief location, image)', 'my-calendar' ),
		'list_preset_2' => __( 'Template 2: (short date, linked time, title and brief location, image)', 'my-calendar' ),
		'list_preset_3' => __( 'Template 3: (full date, time, linked title, full location)', 'my-calendar' ),
		'list_preset_4' => __( 'Template 4: (responsive cards: image, linked title, time, brief location)', 'my-calendar' ),
	);
	/**
	 * Filter the selectable list of template options in settings. 
	 * Keys must start with 'list_preset_'. Use filter `mc_preset_template` 
	 * to return a template.
	 *
	 * @hook mc_select_preset_templates
	 *
	 * @param {array} Array of key/value pairs with a preset type and description.
	 */
	$template_options = apply_filters( 'mc_select_preset_templates', $template_options );

	return $template_options;
}

