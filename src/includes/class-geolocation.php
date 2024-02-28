<?php
/**
 * Geolocation class.
 *
 * @category Locations
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Geolocation
 *
 * Get latitude/longitude or address using Google Maps API
 *
 * @author Jeroen Desloovere <info@jeroendesloovere.be>
 * @modified Joe Dolson <plugins@joedolson.com> Converted to use WP HTTP.
 */
class Geolocation {
	// API URL.
	const API_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

	/**
	 * Do call
	 *
	 * @param array $parameters Query array.
	 *
	 * @return array
	 */
	protected static function call( $parameters = array() ) {

		// define url.
		$url = self::API_URL;

		// add every parameter to the url.
		foreach ( $parameters as $key => $value ) {
			$value = sanitize_text_field( urlencode( $value ) );
			$key   = sanitize_text_field( $key );
			$url   = add_query_arg( $key, $value, $url );
		}
		$api_key = ( '' !== mc_get_option( 'gmap_api_key', '' ) ) ? mc_get_option( 'gmap_api_key' ) : false;
		if ( ! $api_key ) {
			return array();
		}
		$url      = add_query_arg( 'key', sanitize_text_field( $api_key ), $url );
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return array();
		}
		$data = $response['body'];

		// redefine response as json decoded.
		$response = json_decode( $data );

		// return the content.
		return $response->results;
	}

	/**
	 * Get address using latitude/longitude
	 *
	 * @param float $latitude Latitude.
	 * @param float $longitude Longitude.
	 *
	 * @return array (label, components)
	 */
	public static function get_address( $latitude, $longitude ) {
		$address_suggestions = self::get_addresses( $latitude, $longitude );

		return $address_suggestions[0];
	}

	/**
	 * Get possible addresses using latitude/longitude
	 *
	 * @param float $latitude Latitude.
	 * @param float $longitude Longitude.
	 *
	 * @return array(label, street, streetNumber, city, cityLocal, zip, country, countryLabel)
	 */
	public static function get_addresses( $latitude, $longitude ) {
		// init results.
		$addresses = array();

		// define result.
		$address_suggestions = self::call(
			array(
				'latlng' => $latitude . ',' . $longitude,
				'sensor' => 'false',
			)
		);
		if ( empty( $address_suggestions ) ) {
			return $addresses;
		}

		// loop addresses.
		foreach ( $address_suggestions as $key => $address_suggestion ) {
			// init address.
			$address = array();

			// define label.
			$address['label'] = isset( $address_suggestion->formatted_address ) ?
				$address_suggestion->formatted_address : null;

			// define address components by looping all address components.
			foreach ( $address_suggestion->address_components as $component ) {
				$type                           = $component->types[0];
				$address['components'][ $type ] = array(
					'long_name'  => $component->long_name,
					'short_name' => $component->short_name,
					'types'      => $component->types,
				);
			}

			$addresses[ $key ] = $address;
		}

		return $addresses;
	}

	/**
	 * Get coordinates latitude/longitude
	 *
	 * @param  string $street Street address.
	 * @param  string $street_number Additional street address.
	 * @param  string $city City.
	 * @param  string $zip Zip.
	 * @param  string $country Country.
	 *
	 * @return array  The latitude/longitude coordinates
	 */
	public static function get_coordinates(
		$street = null,
		$street_number = null,
		$city = null,
		$zip = null,
		$country = null
	) {
		// init item.
		$item = array();

		// add street.
		if ( ! empty( $street ) ) {
			$item[] = $street;
		}
		// add street number.
		if ( ! empty( $street_number ) ) {
			$item[] = $street_number;
		}
		// add city.
		if ( ! empty( $city ) ) {
			$item[] = $city;
		}
		// add zip.
		if ( ! empty( $zip ) ) {
			$item[] = $zip;
		}
		// add country.
		if ( ! empty( $country ) ) {
			$item[] = $country;
		}

		// define value.
		$address = implode( ' ', $item );

		// define result.
		$results = self::call(
			array(
				'address' => $address,
				'sensor'  => 'false',
			)
		);
		if ( empty( $results ) ) {
			return array();
		}
		// return coordinates latitude/longitude.
		return array(
			'latitude'  => array_key_exists( 0, $results ) ? (float) $results[0]->geometry->location->lat : null,
			'longitude' => array_key_exists( 0, $results ) ? (float) $results[0]->geometry->location->lng : null,
		);
	}
}

/**
 * Geolocation Exception
 *
 * @author Jeroen Desloovere <info@jeroendesloovere.be>
 */
class GeolocationException extends \Exception {} // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
