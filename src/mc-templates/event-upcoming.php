<?php
/**
 * Template: Single Event, Upcoming events view.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

?>
<strong class="mc-title"><?php mc_template_tag( $event, 'title' ); ?></strong> - <?php mc_template_tag( $event, 'datetime' ); ?>
<?php mc_template_tag( $event, 'excerpt' ); ?>
