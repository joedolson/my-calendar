<?php
/**
 * Template: Single Event, List view.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

?>
<div class="mc-v2 <?php mc_event_classes( $data->event, 'card' ); ?>">
	<h2 class="mc-title"><?php mc_template_tag( $data, 'title' ); ?></h2>
	<?php mc_template_time( $data->event, 'card' ); ?>
	<?php mc_template_image( $data->event, 'card' ); ?>
	<?php mc_template_description( $data->event, 'card' ); ?>
	<?php mc_template_excerpt( $data->event, 'card' ); ?>
	<?php mc_template_location( $data->event, 'card' ); ?>
	<?php mc_template_access( $data->event, 'card' ); ?>
	<?php mc_template_link( $data->event, 'card' ); ?>
	<?php mc_template_registration( $data->event, 'card' ); ?>
	<?php mc_template_author( $data->event, 'card' ); ?>
	<?php mc_template_host( $data->event, 'card' ); ?>
	<?php mc_template_share( $data->event, 'card' ); ?>
</div>
