<?php
/**
 * Template: Location hCard.
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

$address = isset( $data->address ) ? $data->address : 'true';
$map     = isset( $data->include_map ) ? $data->include_map : 'true';
$maplink = isset( $data->map_link ) ? $data->map_link : '';
$link    = isset( $data->link ) ? $data->link : '';
$label   = isset( $data->label ) ? $data->label : '';
$street  = isset( $data->street ) ? $data->street : '';
$street2 = isset( $data->street2 ) ? $data->street2 : '';
$city    = isset( $data->city ) ? $data->city : '';
$state   = isset( $data->state ) ? $data->state : '';
$zip     = isset( $data->zip ) ? $data->zip : '';
$country = isset( $data->country ) ? $data->country : '';
$phone   = isset( $data->phone ) ? $data->phone : '';
$events  = isset( $data->events_link ) ? $data->events_link : '';

$sub_address = $street . $street2 . $city . $state . $zip . $country . $phone . $events;

if ( ! ( ( false !== $maplink && 'true' === $map ) || ( '' !== $link && 'true' === $address ) ) ) {
	return;
}
?>
<div class="address location vcard">
	<?php if ( 'true' === $address ) { ?>
	<div class="adr h-card">
		<?php if ( '' !== $label ) { ?>
		<div><strong class="location-label"><?php echo wp_kses_post( $link ); ?></strong></div>
		<?php } ?>
		<?php if ( '' !== $sub_address ) { ?>
		<div class="sub-address">
			<?php if ( '' !== $street ) { ?>
			<div class="street-address p-street-address"><?php echo wp_kses_post( $street ); ?></div>
			<?php } ?>
			<?php if ( '' !== $street2 ) { ?>
			<div class="street-address p-extended-address"><?php echo wp_kses_post( $street2 ); ?></div>
			<?php } ?>
			<?php if ( '' !== $city . $state . $zip ) { ?>
			<div>
				<?php if ( '' !== $city ) { ?>
				<span class="locality p-locality"><?php echo wp_kses_post( $city ); ?></span><span class="mc-sep">, </span>
				<?php } ?>
				<?php if ( '' !== $state ) { ?>
				<span class="region p-region"><?php echo wp_kses_post( $state ); ?></span>
				<?php } ?>
				<?php if ( '' !== $zip ) { ?>
				<span class="postal-code p-postal-code"><?php echo wp_kses_post( $zip ); ?></span>
				<?php } ?>
			</div>
			<?php } ?>
			<?php if ( '' !== $country ) { ?>
			<div class="country-name p-country-name"><?php echo wp_kses_post( $country ); ?></div>
			<?php } ?>
			<?php if ( '' !== $phone ) { ?>
			<div class="tel p-tel"><?php echo wp_kses_post( $phone ); ?></div>
			<?php } ?>
			<?php if ( '' !== $events ) { ?>
			<div class="mc-events-link"><?php echo wp_kses_post( $events ); ?></div>
			<?php } ?>
		</div>
		<?php } ?>
	</div>
	<?php } ?>
	<?php if ( 'true' === $map && false !== $maplink && '' !== $maplink ) { ?>
	<div class="map">
		<a href="<?php echo esc_url( $maplink ); ?>" class="url external"><span class="mc-icon" aria-hidden="true"></span><?php esc_html_e( 'Map', 'my-calendar' ); ?><span class="screen-reader-text fn"> <?php echo esc_html( $label ); ?></span></a>
	</div>
	<?php } ?>
</div>
