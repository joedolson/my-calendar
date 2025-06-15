<?php
/**
 * Template: Single Event, Single view.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-calendar/
 */

$title_template = mc_get_template( 'title_solo' );
if ( mc_template_settings( 'title_solo' ) !== $title_template ) {
	// If the title template has been modified, use that.
	echo wp_kses_post( mc_draw_template( $data, $title_template ) );
} else {
	mc_template_tag( $data, 'title' );
}
