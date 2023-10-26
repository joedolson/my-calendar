<?php
/**
 * Template: Single Event, Today's events view.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

?>
<li class="<?php mc_event_classes( $data, 'today' ); ?>"><strong class="mc-title"><?php mc_template_tag( $data, 'title' ); ?></strong> - <?php mc_template_tag( $data, 'datetime' ); ?>
<?php mc_template_tag( $data, 'excerpt' ); ?></li>