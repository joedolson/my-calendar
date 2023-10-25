<?php
/**
 * Template: Single Event, Single view.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

/**
 * Event templates access any template tags using the function `mc_template_tag`. The object $event is available in all templates.
 */
?>
<div class="mc-event mc-single mc-grid">
	<?php mc_template_tag( $data, 'image' ); ?>
	<?php mc_template_tag( $data, 'datetime' ); ?>
	<?php mc_template_tag( $data, 'description' ); ?>
</div>

