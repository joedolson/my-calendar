<?php
/**
 * Template: Single Location.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Location templates use the location object from $data->location, and can query any function that requires a location object or location ID. The $event object is not available in this template.
 */
?>
<div class="mc-location mc-view-location">
	<div class="mc-location-gmap">
		<?php echo wp_kses_post( mc_generate_map( $data->location, 'location' ) ); ?>
	</div>
	<div class="mc-location-hcard">
		<?php echo wp_kses_post( mc_hcard( $data->location, 'true', 'true', 'location' ) ); ?>
	</div>
	<?php mc_template_location_access( $data ); ?>
	<div class="mc-location-upcoming">
		<h2><?php esc_html_e( 'Upcoming Events', 'my-calendar' ); ?></h2>
		<?php echo wp_kses_post( $data->events ); ?>
	</div>
</div>
