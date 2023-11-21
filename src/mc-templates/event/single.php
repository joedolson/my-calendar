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
	<?php mc_template_time( $data, 'single' ); ?>
	<?php mc_template_image( $data, 'single' ); ?>
	<?php mc_template_description( $data, 'single' ); ?>
	<?php mc_template_excerpt( $data, 'single' ); ?>
	<?php mc_template_location( $data, 'single' ); ?>
	<?php mc_template_access( $data, 'single' ); ?>
	<?php mc_template_link( $data, 'single' ); ?>
	<?php mc_template_registration( $data, 'single' ); ?>
	<?php mc_template_author( $data, 'single' ); ?>
	<?php mc_template_host( $data, 'single' ); ?>
	<?php mc_template_share( $data, 'single' ); ?>
</div>

