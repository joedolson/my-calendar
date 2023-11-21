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

?>
<div class="mc-v2 <?php mc_event_classes( $data->event, 'calendar' ); ?>">
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
