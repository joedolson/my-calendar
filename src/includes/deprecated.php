<?php
/**
 * This file holds functions that have been removed or deprecated,
 * but are kept in case 3rd party code is using the function independently.
 *
 * @category Utilities
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Old function for checking value of an option field in a select
 *
 * @deprecated 3.4.0 2022-12-09.
 *
 * @param string             $field Name of the field.
 * @param string|int|boolean $value Current value.
 * @param string             $array_key if this setting is an array, the array key.
 *
 * @return string selected=selected
 */
function mc_is_selected( $field, $value, $array_key = '' ) {
	_doing_it_wrong(
		__FUNCTION__,
		esc_html__( 'This function was deprecated in My Calendar 3.4.0, and should not be used.', 'my-calendar' ),
		'3.4.0'
	);
	if ( ! is_array( get_option( $field ) ) ) {
		if ( get_option( $field ) === (string) $value ) {
			return 'selected="selected"';
		}
	} else {
		$setting = get_option( $field );
		if ( (string) $setting[ $array_key ]['enabled'] === (string) $value ) {
			return 'selected="selected"';
		}
	}

	return '';
}

/**
 * Old function for checking value of an option field.
 *
 * @deprecated 3.3.0
 *
 * @param string             $field Name of the field.
 * @param string|int|boolean $value Current value.
 * @param string             $type checkbox, radio, option.
 *
 * @return string
 */
function mc_option_selected( $field, $value, $type = 'checkbox' ) {
	_doing_it_wrong(
		__FUNCTION__,
		esc_html__( 'This function was deprecated in My Calendar 3.4.0, and should not be used.', 'my-calendar' ),
		'3.4.0'
	);
	switch ( $type ) {
		case 'radio':
		case 'checkbox':
			$result = ' checked="checked"';
			break;
		case 'option':
			$result = ' selected="selected"';
			break;
		default:
			$result = '';
			break;
	}
	if ( $field === $value ) {
		$output = $result;
	} else {
		$output = '';
	}

	return $output;
}

/**
 * Old function for checking value of an option field
 *
 * @deprecated 3.4.0 2022-12-09.
 *
 * @param string             $field Name of the field.
 * @param string|int|boolean $value Current value.
 * @param string             $array_key if this setting is an array, the array key.
 * @param boolean            $should_return whether to return or echo.
 *
 * @return string checked=checked
 */
function mc_is_checked( $field, $value, $array_key = '', $should_return = false ) {
	_doing_it_wrong(
		__FUNCTION__,
		esc_html__( 'This function was deprecated in My Calendar 3.4.0, and should not be used.', 'my-calendar' ),
		'3.4.0'
	);
	if ( ! is_array( get_option( $field ) ) ) {
		if ( get_option( $field ) === (string) $value ) {
			if ( $should_return ) {
				return 'checked="checked"';
			} else {
				echo 'checked="checked"';
			}
		}
	} else {
		$setting = get_option( $field );
		if ( ! empty( $setting[ $array_key ]['enabled'] ) && (string) $setting[ $array_key ]['enabled'] === (string) $value ) {
			if ( $should_return ) {
				return 'checked="checked"';
			} else {
				echo 'checked="checked"';
			}
		}
	}

	return '';
}

/**
 * Return valid accessibility features for events. Only used to migrate data into 3.7.
 *
 * @return array
 */
function mc_event_access() {
	$choices = array(
		'1'  => __( 'Audio Description', 'my-calendar' ),
		'2'  => __( 'ASL Interpretation', 'my-calendar' ),
		'3'  => __( 'ASL Interpretation with voicing', 'my-calendar' ),
		'4'  => __( 'Deaf-Blind ASL', 'my-calendar' ),
		'5'  => __( 'Real-time Captioning', 'my-calendar' ),
		'6'  => __( 'Scripted Captioning', 'my-calendar' ),
		'7'  => __( 'Assisted Listening Devices', 'my-calendar' ),
		'8'  => __( 'Tactile/Touch Tour', 'my-calendar' ),
		'9'  => __( 'Braille Playbill', 'my-calendar' ),
		'10' => __( 'Large Print Playbill', 'my-calendar' ),
		'11' => __( 'Sensory Friendly', 'my-calendar' ),
		'12' => __( 'Other', 'my-calendar' ),
	);
	/**
	 * Filter available event accessibility options.
	 *
	 * @hook mc_event_access_choices
	 *
	 * @param {array} $choices Indexed array of choices. Events store only the index.
	 *
	 * @return {array}
	 */
	$events_access = apply_filters( 'mc_event_access_choices', $choices );

	return $events_access;
}

/**
 * Array of location access features. Only used to migrate data into 3.7.
 *
 * @return array
 */
function mc_location_access() {
	$location_access = array(
		'1'  => __( 'Accessible Entrance', 'my-calendar' ),
		'2'  => __( 'Accessible Parking Designated', 'my-calendar' ),
		'3'  => __( 'Accessible Restrooms', 'my-calendar' ),
		'4'  => __( 'Accessible Seating', 'my-calendar' ),
		'5'  => __( 'Accessible Transportation Available', 'my-calendar' ),
		'6'  => __( 'Wheelchair Accessible', 'my-calendar' ),
		'7'  => __( 'Courtesy Wheelchairs', 'my-calendar' ),
		'8'  => __( 'Bariatric Seating Available', 'my-calendar' ),
		'9'  => __( 'Elevator to all public areas', 'my-calendar' ),
		'10' => __( 'Braille Signage', 'my-calendar' ),
		'11' => __( 'Fragrance-Free Policy', 'my-calendar' ),
		'12' => __( 'Other', 'my-calendar' ),
	);

	/**
	 * Filter choices available for location accessibility services.
	 *
	 * @hook mc_location_access_choices
	 *
	 * @param {array} Array of location choices (numeric keys, string values.)
	 *
	 * @return {array}
	 */
	return apply_filters( 'mc_location_access_choices', $location_access );
}

/**
 * Function deprecated: mc_expand was deprecated in 3.7.0. Kept to prevent fatal errors if used for templating.
 *
 * @param array $data Array of data to format.
 *
 * @return string
 */
function mc_expand( $data ) {
	return '';
}
