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
<div class="mc-event-container">
	<h3 class="mc-title"><?php mc_template_tag( $data, 'title' ); ?></h3>
	<?php mc_template_time( $data, 'mini' ); ?>
	<?php mc_template_image( $data, 'mini' ); ?>
	<?php mc_template_description( $data, 'mini' ); ?>
	<?php mc_template_excerpt( $data, 'mini' ); ?>
	<?php mc_template_location( $data, 'mini' ); ?>
	<?php mc_template_access( $data, 'mini' ); ?>
	<?php mc_template_link( $data, 'mini' ); ?>
	<?php mc_template_registration( $data, 'mini' ); ?>
	<?php mc_template_author( $data, 'mini' ); ?>
	<?php mc_template_host( $data, 'mini' ); ?>
	<?php mc_template_share( $data, 'mini' ); ?>
</div>
