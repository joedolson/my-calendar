<?php
/**
 * Template: Single Event, Today's events view.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

?>
<li class="mc-events <?php mc_event_classes( $data->event, 'today' ); ?>"><?php mc_template( $data->tags, $data->template, 'list' ); ?></li>
