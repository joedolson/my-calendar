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

mc_category_icon( $data );
$time = mc_get_template_tag( $data, 'time' );
if ( $time ) {
	mc_template_tag( $data, 'time' );
	echo ': ';
}
mc_template_tag( $data, 'title' );
