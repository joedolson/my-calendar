<?php
/**
 * Template: Single Event, Upcoming events view.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

?>
<li class="mc-events <?php mc_event_classes( $data->event, 'now' ); ?>"><?php mc_template( $data->tags, $data->template, 'list' ); ?></li>
