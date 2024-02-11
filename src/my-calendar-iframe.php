<?php
/**
 * Output the iframe view.
 *
 * @category Calendar
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'my_calendar_iframe_view' );
/**
 * Redirect to print view if query set.
 */
function my_calendar_iframe_view() {
	if ( mc_is_iframe() ) {
		echo my_calendar_iframe();
		exit;
	}
}

/**
 * Produce print view output.
 */
function my_calendar_iframe() {
	header( 'Content-Type: ' . get_bloginfo( 'html_type' ) . '; charset=' . get_bloginfo( 'charset' ) );
	$body = '';
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<?php
	wp_head();
	if ( mc_is_tag_view() ) {
		?>
<style>html{margin-top:0 !important;}.mc-template-cards { display: grid; grid-template-columns: repeat( 2, 1fr ); max-width: 100%; column-gap: 16px; }.mc-template-card {padding: 10px;}.mc-tag{background:#e9eaea;color:#000;padding:3px;}.mc-template-card .mc-output {line-break: anywhere;}</style>
		<?php
	} else {
		?>
<style>html{margin-top:0!important;}</style>
		<?php
	}
	?>
</head>
<body>
	<?php
	$mc_id = ( is_numeric( $_GET['mc_id'] ) ) ? absint( $_GET['mc_id'] ) : false;
	if ( $mc_id ) {
		if ( mc_is_tag_view() ) {
			if ( isset( $_GET['template'] ) ) {
				$template = sanitize_text_field( $_GET['template'] );
				$body    .= mc_display_template_preview( $template, $mc_id );
			} else {
				$body .= '<div id="mc_event"><div class="single-event mc-event">' . mc_display_template_tags( $mc_id, 'preview' ) . '</div></div>';
			}
		} else {
			$body .= mc_get_event( $mc_id, 'html' );
		}
	}
	echo mc_kses_post( $body );
	?>
</body>
</html>
	<?php
}
