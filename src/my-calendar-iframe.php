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
	$rtl = ( is_rtl() ) ? 'rtl' : 'ltr';
	header( 'Content-Type: ' . get_bloginfo( 'html_type' ) . '; charset=' . get_bloginfo( 'charset' ) );
	if ( mc_is_tag_view() ) {
		$tag_styles = '<style>.mc-template-cards { display: grid; grid-template-columns: repeat( 2, 1fr ); max-width: 100%; column-gap: 16px; }.mc-template-card {padding: 10px;}.mc-tag{background:#e9eaea;color:#000;padding:3px;}.mc-template-card .mc-output {line-break: anywhere;}</style>';
	} else {
		$tag_styles = '';
	}
	$body = '';
	echo '<!DOCTYPE html>
<html dir="' . $rtl . '" lang="' . get_bloginfo( 'language' ) . '">
<head>';
	echo $tag_styles;
	wp_head();
	echo '</head>
<body>';
	$mc_id = ( is_numeric( $_GET['mc_id'] ) ) ? $_GET['mc_id'] : false;
	if ( $mc_id ) {
		if ( mc_is_tag_view() ) {
			$body .= '<div id="mc_event"><div class="single-event vevent">' . mc_display_template_tags( $mc_id, 'html' ) . '</div></div>';
		} else {
			$body .= mc_get_event( $mc_id, 'html' );
		}
	}
	echo $body;
	echo '
</body>
</html>';
}
