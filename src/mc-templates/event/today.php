<?php
/**
 * Template: Single Event, Today's events view.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<li class="mc-events <?php mc_event_classes( $data->event, 'today', array( $data->class ) ); ?>"><?php mc_template( $data->tags, $data->template, 'list' ); ?></li>
