<?php
/**
 * Load template for My Calendar embedding templates.
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

define( 'MC_TEMPLATES', trailingslashit( __DIR__ ) . 'templates/' );
add_action( 'template_redirect', 'mc_embed_template' );
/**
 * Load Template.
 *
 * This template must be named "my-calendar-template.php".
 *
 * First, this function will look in the child theme
 * then in the parent theme and if no template is found
 * in either theme, the default template will be loaded
 * from the plugin's folder.
 *
 * This function is hooked into the "template_redirect"
 * action and terminates script execution.
 *
 * @return void
 * @since 2020-12-14
 */
function mc_embed_template() {
	// Return early if there is no reason to proceed.
	if ( ! isset( $_GET['embed'] ) ) {
		return;
	}
	add_filter( 'show_admin_bar', '__return_false' );

	// Check to see if there is a template in the theme.
	$template = locate_template( array( 'my-calendar-template.php' ) );
	if ( ! empty( $template ) ) {
		require_once $template;
		exit;
	} else {
		// Use plugin's template file.
		require_once MC_TEMPLATES . 'my-calendar-template.php';
		exit;
	}
}
