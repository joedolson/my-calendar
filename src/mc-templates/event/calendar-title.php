<?php
/**
 * Template: Single Event, Grid view.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

$title_template = mc_get_template( 'title' );
if ( mc_template_settings( 'title' ) !== $title_template ) {
	// If the title template has been modified, use that.
	echo wp_kses_post( mc_draw_template( $data, $title_template ) );
} else {
	mc_category_icon( $data );
	$time = mc_get_template_tag( $data, 'time' );
	if ( $time ) {
		mc_template_tag( $data, 'time' );
		echo ': ';
	}
	mc_template_tag( $data, 'title' );
}
