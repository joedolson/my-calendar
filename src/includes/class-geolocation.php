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
	 * @param  array  $parameters
	 *
	 * @return object
	 */
	protected static function doCall( $parameters = array() ) {

		// define url
		$url = self::API_URL;

		// add every parameter to the url
		foreach ( $parameters as $key => $value ) {
			$value = sanitize_text_field( urlencode( $value ) );
			$key   = sanitize_text_field( $key );
			$url   = add_query_arg( $key, $value, $url );
		}
		$api_key = ( '' !== get_option( 'mc_gmap_api_key' ) ) ? get_option( 'mc_gmap_api_key' ) : false;
		if ( ! $api_key ) {
			return '';
		}
		$url     = add_query_arg( 'key', sanitize_text_field( $api_key ), $url );

		$response = wp_remote_get( $url );
		$data     = $response['body'];

		// redefine response as json decoded
		$response = json_decode( $data );

		// return the content
		return $response->results;
	}

	/**
	 * Get address using latitude/longitude
	 *
	 * @return array(label, components)
	 * @param  float        $latitude
	 * @param  float        $longitude
	 */
	public static function getAddress( $latitude, $longitude ) {
		$addressSuggestions = self::getAddresses( $latitude, $longitude );

		return $addressSuggestions[0];
	}

	/**
	 * Get possible addresses using latitude/longitude
	 *
	 * @param  float        $latitude
	 * @param  float        $longitude
	 *
	 * @return array(label, street, streetNumber, city, cityLocal, zip, country, countryLabel)
	 */
	public static function getAddresses( $latitude, $longitude ) {
		// init results.
		$addresses = array();

		// define result.
		$addressSuggestions = self::doCall(
			array(
				'latlng' => $latitude . ',' . $longitude,
				'sensor' => 'false',
			)
		);

		// loop addresses
		foreach ( $addressSuggestions as $key => $addressSuggestion ) {
			// init address.
			$address = array();

			// define label.
			$address['label'] = isset( $addressSuggestion->formatted_address ) ?
				$addressSuggestion->formatted_address : null;

			// define address components by looping all address components.
			foreach ( $addressSuggestion->address_components as $component ) {
				$type = $component->types[0];
				$address['components'][ $type ] = array(
					'long_name'  => $component->long_name,
					'short_name' => $component->short_name,
					'types'      => $component->types
				);
			}

			$addresses[ $key ] = $address;
		}

		return $addresses;
	}

	/**
	 * Get coordinates latitude/longitude
	 *
	 * @param  string $street
	 * @param  string $street_number
	 * @param  string $city
	 * @param  string $zip
	 * @param  string $country
	 *
	 * @return array  The latitude/longitude coordinates
	 */
	public static function getCoordinates(
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

		// define result
		$results = self::doCall(
			array(
				'address' => $address,
				'sensor'  => 'false',
			)
		);

		// return coordinates latitude/longitude
		return array(
			'latitude'  => array_key_exists( 0, $results ) ? (float) $results[0]->geometry->location->lat : null,
			'longitude' => array_key_exists( 0, $results ) ? (float) $results[0]->geometry->location->lng : null
		);
	}
}

/**
 * Geolocation Exception
 *
 * @author Jeroen Desloovere <info@jeroendesloovere.be>
 */
class GeolocationException extends \Exception {}
