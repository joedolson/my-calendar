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
<div class="mc-event mc-single mc-list">
	<h2 class="mc-title"><?php mc_template_tag( $data['event'], 'title' ); ?></h2>
	<?php mc_template_tag( $data['event'], 'datetime' ); ?>
</div>
