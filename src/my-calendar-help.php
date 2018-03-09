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
						echo '<li>' . sprintf( __( 'View <a href="%s">your calendar</a>', 'my-calendar' ), mc_get_uri() ) . '</li>';
					}
					?>
				</ul>
				<?php do_action( 'mc_before_help' ); ?>
				<ul class="mc-settings checkboxes">
					<li><a href="#mc-generator"><?php _e( 'Shortcode Generator', 'my-calendar' ); ?></a></li>
					<li><a href="#files"><?php _e( 'Custom Files', 'my-calendar' ); ?></a></li>
					<li><a href="#get-support"><?php _e( 'Get Support', 'my-calendar' ); ?></a></li>
				</ul>
			</div>
		</div>
	</div>

	<div class="ui-sortable meta-box-sortables" id="mc-generator">
		<div class="postbox">
			<h2 id="generator"><?php _e( 'My Calendar Shortcode Generator', 'my-calendar' ); ?></h2>

			<div class="inside mc-tabs">
				<?php mc_generate(); ?>
				<ul class='tabs' role="tablist">
					<li><a href='#mc_main' role="tab" id='tab_mc_main' aria-controls='mc_main'><?php _e( 'Main', 'my-calendar' ); ?></a></li>
					<li><a href='#mc_upcoming' role="tab" id='tab_mc_upcoming' aria-controls='mc_upcoming'><?php _e( 'Upcoming', 'my-calendar' ); ?></a></li>
					<li><a href='#mc_today' role="tab" id='tab_mc_today' aria-controls='mc_today'><?php _e( 'Today', 'my-calendar' ); ?></a></li>
					<?php echo apply_filters( 'mc_generator_tabs', '' ); ?>
				</ul>
				<div class='wptab mc_main' id='mc_main' aria-live='assertive' aria-labelledby='tab_mc_main' role="tabpanel">
					<?php mc_generator( 'main' ); ?>
				</div>
				<div class='wptab mc_upcoming' id='mc_upcoming' aria-live='assertive' aria-labelledby='tab_mc_upcoming' role="tabpanel">
					<?php mc_generator( 'upcoming' ); ?>
				</div>
				<div class='wptab mc_today' id='mc_today' aria-live='assertive' aria-labelledby='tab_mc_today' role="tabpanel">
					<?php mc_generator( 'today' ); ?>
				</div>
				<?php echo apply_filters( 'mc_generator_tab_content', '' ); ?>
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
					<li><code><?php echo str_replace( '/my-calendar', '', plugin_dir_path( __FILE__ ) ) . 'my-calendar-custom/styles/'; ?></code></li>
					<li><code><?php echo get_template_directory(); ?></code></li>
				</ul>
				<p>
					<?php _e( 'Custom print, mobile, and tablet stylesheet file names: <code>mc-print.css</code>, <code>mc-mobile.css</code>, and <code>mc-tablet.css</code>.', 'my-calendar' ); ?>
				</p>
			</div>
		</div>
	</div>

	<?php do_action( 'mc_after_help' ); ?>

	<div class="ui-sortable meta-box-sortables" id="get-support">
		<div class="postbox">
			<h2 id="support"><?php _e( 'Get Plug-in Support', 'my-calendar' ); ?></h2>

			<div class="inside">
				<div class='mc-support-me'>
					<p>
					<?php
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
