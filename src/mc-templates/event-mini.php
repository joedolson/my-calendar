<?php
/**
 * Template: Single Event, Mini view.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

?>
<div class="mc-event mc-single">
    <h2 class="mc-title"><?php mc_template_tag( $event, 'title' ); ?></h2>
    <?php mc_template_tag( $event, 'datetime' ); ?>
    <?php mc_template_tag( $event, 'description' ); ?>
</div>

