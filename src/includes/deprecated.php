<?php
/**
 * This file holds functions that have been removed or deprecated,
 * but are kept in case 3rd party code is using the function independently.
 *
 * @category Utilities
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

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
		__( 'This function was deprecated in My Calendar 3.4.0, and should not be used.', 'my-calendar' ),
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
		__( 'This function was deprecated in My Calendar 3.4.0, and should not be used.', 'my-calendar' ),
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
		__( 'This function was deprecated in My Calendar 3.4.0, and should not be used.', 'my-calendar' ),
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
