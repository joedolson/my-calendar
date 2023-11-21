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
<div class="mc-event-container">
	<div class="mc-image-container">
		<?php mc_template_time( $data, 'calendar' ); ?>
		<?php mc_template_image( $data, 'calendar' ); ?>
	</div>
	<div class="mc-content-container">
		<?php mc_template_description( $data, 'calendar' ); ?>
		<?php mc_template_excerpt( $data, 'calendar' ); ?>
		<?php mc_template_location( $data, 'calendar' ); ?>
		<?php mc_template_access( $data, 'calendar' ); ?>
		<?php mc_template_link( $data, 'calendar' ); ?>
		<?php mc_template_registration( $data, 'calendar' ); ?>
		<?php mc_template_author( $data, 'calendar' ); ?>
		<?php mc_template_host( $data, 'calendar' ); ?>
		<?php mc_template_share( $data, 'calendar' ); ?>
	</div>
</div>

