<?php
/**
 * Template: Single Location.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

/**
 * Location templates use the location object from $data->location, and can query any function that requires a location object or location ID. The $event object is not available in this template.
 */
?>
<div class="mc-location mc-view-location">
	<div class="mc-location-gmap"><?php echo mc_generate_map( $data->location, 'location' ); ?></div>
	<div class="mc-location-hcard"><?php echo mc_hcard( $data->location, 'true', 'true', 'location' ); ?></div>
	<div class="mc-location-upcoming"><h2><?php _e( 'Upcoming Events', 'my-calendar' ); ?></h2><?php echo $data->events; ?></div>
</div>
