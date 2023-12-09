<?php
/**
 * Template: Single Event, List view.
 *
 * Contents are inside the `article` wrapper for event data, after the `header` element.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

?>
<div class="mc-event-container">
	<div class="mc-image-container">
		<?php mc_template_image( $data, 'card' ); ?>
	</div>
	<div class="mc-content-container">
		<div class="mc-card-date">
			<?php mc_template_tag( $data, 'datebadge' ); ?>
		</div>
		<div class="mc-card-content">
			<?php mc_template_tag( $data, 'time' ); ?>
			<?php mc_template_description( $data, 'card' ); ?>
			<?php mc_template_excerpt( $data, 'card' ); ?>
			<?php mc_template_location( $data, 'card' ); ?>
			<?php mc_template_access( $data, 'card' ); ?>
			<?php mc_template_link( $data, 'card' ); ?>
			<?php mc_template_registration( $data, 'card' ); ?>
			<?php mc_template_author( $data, 'card' ); ?>
			<?php mc_template_host( $data, 'card' ); ?>
			<?php mc_template_share( $data, 'card' ); ?>
		</div>
	</div>
</div>
