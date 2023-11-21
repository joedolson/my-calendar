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
<div class="mc-v2 <?php mc_event_classes( $data->event, 'mini' ); ?>">
	<h2 class="mc-title"><?php mc_template_tag( $data, 'title' ); ?></h2>
	<?php mc_template_time( $data, 'list' ); ?>
	<?php mc_template_image( $data, 'list' ); ?>
	<?php mc_template_description( $data, 'list' ); ?>
	<?php mc_template_excerpt( $data, 'list' ); ?>
	<?php mc_template_location( $data, 'list' ); ?>
	<?php mc_template_access( $data, 'list' ); ?>
	<?php mc_template_link( $data, 'list' ); ?>
	<?php mc_template_registration( $data, 'list' ); ?>
	<?php mc_template_author( $data, 'list' ); ?>
	<?php mc_template_host( $data, 'list' ); ?>
	<?php mc_template_share( $data, 'list' ); ?>
</div>
