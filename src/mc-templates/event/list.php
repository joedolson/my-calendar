<?php
/**
 * Template: Single Event, List view.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$js_option   = mc_get_option( 'list_javascript' );
$list_titles = mc_get_option( 'list_link_titles' );
$show_title  = true;
// When JS disabled or disclosure & titles included in visible output,
// hide titles to avoid a broken hierarchy and duplicate title.
if ( 'modal' !== $js_option && 'true' === $list_titles ) {
	$show_title = false;
}

?>
<div class="mc-event-container">
	<?php if ( $show_title ) : ?>
		<h2 class="event-title mc-title"><?php mc_template_tag( $data, 'title' ); ?></h2>
	<?php endif; ?>
	<div class="mc-image-container">
		<?php mc_template_time( $data, 'list' ); ?>
		<?php mc_template_image( $data, 'list' ); ?>
	</div>
	<div class="mc-content-container">
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
</div>
