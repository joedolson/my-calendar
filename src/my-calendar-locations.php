<?php
/**
 * Update & Add Locations
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
 * Update a single field in a location.
 *
 * @param string $field field name.
 * @param mixed  $data data to update to.
 * @param int    $location location ID.
 *
 * @return mixed boolean/int query result
 */
function mc_update_location( $field, $data, $location ) {
	global $wpdb;
	$field  = sanitize_key( $field );
	$result = $wpdb->query( $wpdb->prepare( 'UPDATE ' . my_calendar_locations_table() . " SET $field = %d WHERE location_id=%d", $data, $location ) ); // WPCS: unprepared SQL ok.

	return $result;
}

/**
 * Update settings for how location inputs are limited.
 */
function mc_update_location_controls() {
	if ( isset( $_POST['mc_locations'] ) && 'true' == $_POST['mc_locations'] ) {
		$nonce = $_POST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'Invalid nonce' );
		}
		$locations            = $_POST['mc_location_controls'];
		$mc_location_controls = array();
		foreach ( $locations as $key => $value ) {
			$mc_location_controls[ $key ] = mc_csv_to_array( $value[0] );
		}
		update_option( 'mc_location_controls', $mc_location_controls );
		mc_show_notice( __( 'Location Controls Updated', 'my-calendar' ) );
	}
}

/**
 * Insert a new location.
 *
 * @param array $add Array of location details to add.
 *
 * @return mixed boolean/int query result.
 */
function mc_insert_location( $add ) {
	global $wpdb;
	$add     = array_map( 'mc_kses_post', $add );
	$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%s', '%s', '%s' );
	$results = $wpdb->insert( my_calendar_locations_table(), $add, $formats );

	return $results;
}

/**
 * Update a location.
 *
 * @param array $update Array of location details to modify.
 * @param int   $where Location ID to update.
 *
 * @return mixed boolean/int query result.
 */
function mc_modify_location( $update, $where ) {
	global $wpdb;
	$update  = array_map( 'mc_kses_post', $update );
	$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%s', '%s', '%s' );
	$results = $wpdb->update( my_calendar_locations_table(), $update, $where, $formats, '%d' );

	return $results;
}

/**
 * Handle results of form submit & display form.
 */
function my_calendar_add_locations() {
	global $wpdb;
	?>
	<div class="wrap my-calendar-admin">
	<?php
	my_calendar_check_db();
	// We do some checking to see what we're doing.
	mc_mass_delete_locations();
	if ( ! empty( $_POST ) && ( ! isset( $_POST['mc_locations'] ) && ! isset( $_POST['mass_delete'] ) ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			die( 'Security check failed' );
		}
	}
	if ( isset( $_POST['mode'] ) && 'add' == $_POST['mode'] ) {
		$add = array(
			'location_label'     => $_POST['location_label'],
			'location_street'    => $_POST['location_street'],
			'location_street2'   => $_POST['location_street2'],
			'location_city'      => $_POST['location_city'],
			'location_state'     => $_POST['location_state'],
			'location_postcode'  => $_POST['location_postcode'],
			'location_region'    => $_POST['location_region'],
			'location_country'   => $_POST['location_country'],
			'location_url'       => $_POST['location_url'],
			'location_longitude' => $_POST['location_longitude'],
			'location_latitude'  => $_POST['location_latitude'],
			'location_zoom'      => $_POST['location_zoom'],
			'location_phone'     => $_POST['location_phone'],
			'location_phone2'    => $_POST['location_phone2'],
			'location_access'    => isset( $_POST['location_access'] ) ? serialize( $_POST['location_access'] ) : '',
		);

		$results = mc_insert_location( $add );
		do_action( 'mc_save_location', $results, $add );
		if ( $results ) {
			mc_show_notice( __( 'Location added successfully', 'my-calendar' ) );
		} else {
			mc_show_error( __( 'Location could not be added to database', 'my-calendar' ) );
		}
	} elseif ( isset( $_GET['location_id'] ) && 'delete' == $_GET['mode'] ) {
		$results = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . my_calendar_locations_table() . ' WHERE location_id=%d', $_GET['location_id'] ) ); // WPCS: unprepared SQL ok.
		do_action( 'mc_delete_location', $results, (int) $_GET['location_id'] );
		if ( $results ) {
			mc_show_notice( __( 'Location deleted successfully', 'my-calendar' ) );
		} else {
			mc_show_error( __( 'Location could not be deleted', 'my-calendar' ) );
		}
	} elseif ( isset( $_GET['mode'] ) && isset( $_GET['location_id'] ) && 'edit' == $_GET['mode'] && ! isset( $_POST['mode'] ) ) {
		$cur_loc = (int) $_GET['location_id'];
		mc_show_location_form( 'edit', $cur_loc );
	} elseif ( isset( $_POST['location_id'] ) && isset( $_POST['location_label'] ) && 'edit' == $_POST['mode'] ) {
		$update = array(
			'location_label'     => $_POST['location_label'],
			'location_street'    => $_POST['location_street'],
			'location_street2'   => $_POST['location_street2'],
			'location_city'      => $_POST['location_city'],
			'location_state'     => $_POST['location_state'],
			'location_postcode'  => $_POST['location_postcode'],
			'location_region'    => $_POST['location_region'],
			'location_country'   => $_POST['location_country'],
			'location_url'       => $_POST['location_url'],
			'location_longitude' => $_POST['location_longitude'],
			'location_latitude'  => $_POST['location_latitude'],
			'location_zoom'      => $_POST['location_zoom'],
			'location_phone'     => $_POST['location_phone'],
			'location_phone2'    => $_POST['location_phone2'],
			'location_access'    => isset( $_POST['location_access'] ) ? serialize( $_POST['location_access'] ) : '',
		);

		$where   = array( 'location_id' => (int) $_POST['location_id'] );
		$results = mc_modify_location( $update, $where );

		do_action( 'mc_modify_location', $where, $update );
		if ( false === $results ) {
			mc_show_error( __( 'Location could not be edited.', 'my-calendar' ) );
		} elseif ( 0 == $results ) {
			mc_show_error( __( 'Location was not changed.', 'my-calendar' ) );
		} else {
			mc_show_notice( __( 'Location edited successfully', 'my-calendar' ) );
		}
		$cur_loc = (int) $_POST['location_id'];
		mc_show_location_form( 'edit', $cur_loc );

	}

	if ( isset( $_GET['mode'] ) && 'edit' != $_GET['mode'] || isset( $_POST['mode'] ) && 'edit' != $_POST['mode'] || ! isset( $_GET['mode'] ) && ! isset( $_POST['mode'] ) ) {
		mc_show_location_form( 'add' );
	}
}

/**
 * Create location editing form.
 *
 * @param string $view type of view add/edit.
 * @param int    $loc_id Location ID.
 */
function mc_show_location_form( $view = 'add', $loc_id = '' ) {
	$cur_loc = false;
	if ( '' != $loc_id ) {
		$cur_loc = mc_get_location( $loc_id );
	}
	$has_data = ( empty( $cur_loc ) ) ? false : true;
	if ( 'add' == $view ) {
		?>
		<h1><?php _e( 'Add New Location', 'my-calendar' ); ?></h1>
		<?php
	} else {
		?>
		<h1 class="wp-heading-inline"><?php _e( 'Edit Location', 'my-calendar' ); ?></h1>
		<a href="<?php echo admin_url( 'admin.php?page=my-calendar-locations' ); ?>" class="page-title-action"><?php _e( 'Add New', 'my-calendar' ); ?></a>
		<hr class="wp-header-end">
		<?php
	}
	?>
	<div class="postbox-container jcd-wide">
		<div class="metabox-holder">

			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2><?php _e( 'Location Editor', 'my-calendar' ); ?></h2>

					<div class="inside location_form">
						<form id="my-calendar" method="post" action="<?php echo admin_url( 'admin.php?page=my-calendar-locations' ); ?>">
							<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/></div>
							<?php
							if ( 'add' == $view ) {
								?>
								<div>
									<input type="hidden" name="mode" value="add" />
									<input type="hidden" name="location_id" value="" />
								</div>
								<?php
							} else {
								?>
								<div>
									<input type="hidden" name="mode" value="edit"/>
									<input type="hidden" name="location_id" value="<?php echo $cur_loc->location_id; ?>"/>
								</div>
								<?php
							}
							echo mc_locations_fields( $has_data, $cur_loc, 'location' );
							?>
							<p>
								<input type="submit" name="save" class="button-primary" value="<?php echo ( 'edit' == $view ) ? __( 'Save Changes', 'my-calendar' ) : __( 'Add Location', 'my-calendar' ); ?> &raquo;"/>
							</p>
						</form>
					</div>
				</div>
			</div>
			<?php
			if ( 'edit' == $view ) {
				?>
				<p>
					<a href="<?php echo admin_url( 'admin.php?page=my-calendar-locations' ); ?>"><?php _e( 'Add a New Location', 'my-calendar' ); ?> &raquo;</a>
				</p>
				<?php
			}
			?>
		</div>
	</div>
		<?php
		$controls = array( __( 'Location Controls', 'my-calendar' ) => mc_location_controls() );
		mc_show_sidebar( '', $controls );
		?>
	</div>
	<?php
}

/**
 * Get details about one location.
 *
 * @param int $location_id Location ID.
 *
 * @return object location
 */
function mc_get_location( $location_id ) {
	global $wpdb;
	$location = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . my_calendar_locations_table() . ' WHERE location_id = %d', $location_id ) ); // WPCS: unprepared SQL ok.

	return $location;
}

/**
 * Check whether this location field has pre-entered controls on input
 *
 * @param string $this_field field name.
 *
 * @return boolean true if location field is controlled
 */
function mc_controlled_field( $this_field ) {
	$this_field = trim( $this_field );
	$controls   = get_option( 'mc_location_controls' );
	if ( ! is_array( $controls ) || empty( $controls ) ) {
		return false;
	}
	$controlled = array_keys( $controls );
	if ( in_array( 'event_' . $this_field, $controlled ) && ! empty( $controls[ 'event_' . $this_field ] ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Return select element with the controlled values for a location field
 *
 * @param string $fieldname Name of field.
 * @param string $selected currently selected value.
 * @param string $context current context: entering new location or new event.
 *
 * @return string HTML select element with values
 */
function mc_location_controller( $fieldname, $selected, $context = 'location' ) {
	$field    = ( 'location' == $context ) ? 'location_' . $fieldname : 'event_' . $fieldname;
	$selected = esc_attr( trim( $selected ) );
	$options  = get_option( 'mc_location_controls' );
	$regions  = $options[ 'event_' . $fieldname ];
	$form     = "<select name='$field' id='e_$fieldname'>";
	$form    .= "<option value=''>" . __( 'Select', 'my-calendar' ) . '</option>';
	if ( is_admin() && '' != $selected ) {
		$form .= "<option value='$selected'>$selected :" . __( '(Not a controlled value)', 'my-calendar' ) . '</option>';
	}
	foreach ( $regions as $key => $value ) {
		$key       = esc_attr( trim( $key ) );
		$value     = esc_html( trim( $value ) );
		$aselected = ( $selected == $key ) ? ' selected="selected"' : '';
		$form     .= "<option value='$key'$aselected>$value</option>\n";
	}
	$form .= '</select>';

	return $form;
}

/**
 * Location controls for limiting location submission options.
 *
 * @return string HTML controls.
 */
function mc_location_controls() {
	if ( current_user_can( 'mc_edit_settings' ) ) {
		$response             = mc_update_location_controls();
		$location_fields      = array(
			'event_label',
			'event_city',
			'event_state',
			'event_country',
			'event_postcode',
			'event_region',
		);
		$mc_location_controls = get_option( 'mc_location_controls' );

		$output = $response . '
		<form method="post" action="' . admin_url( 'admin.php?page=my-calendar-locations' ) . '">
		<div><input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'my-calendar-nonce' ) . '" /></div>
		<div><input type="hidden" name="mc_locations" value="true" /></div>
		<fieldset>
			<legend>' . __( 'Limit Input Options', 'my-calendar' ) . '</legend>
			<div id="mc-accordion">';
		foreach ( $location_fields as $field ) {
			$locations = '';
			$class     = '';
			$active    = '';
			if ( is_array( $mc_location_controls ) && isset( $mc_location_controls[ $field ] ) ) {
				foreach ( $mc_location_controls[ $field ] as $key => $value ) {
					$key        = esc_html( trim( $key ) );
					$value      = esc_html( trim( $value ) );
					$locations .= stripslashes( "$key,$value" ) . PHP_EOL;
				}
			}
			if ( '' != trim( $locations ) ) {
				$class  = ' class="active-limit"';
				$active = ' (' . __( 'active limits', 'my-calendar' ) . ')';
			}
			$output .= '<h4' . $class . '><span class="dashicons" aria-hidden="true"> </span><button type="button" class="button-link">' . ucfirst( str_replace( 'event_', '', $field ) ) . $active . '</button></h4>';
			// Translators: Name of field being restricted, e.g. "Location Controls for State".
			$output .= '<div><label for="loc_values_' . $field . '">' . sprintf( __( 'Location Controls for %s', 'my-calendar' ), ucfirst( str_replace( 'event_', '', $field ) ) ) . '</label><br/><textarea name="mc_location_controls[' . $field . '][]" id="loc_values_' . $field . '" cols="80" rows="6">' . trim( $locations ) . '</textarea></div>';
		}
		$output .= "
			</div>
			<p><input type='submit' class='button secondary' value='" . __( 'Save Location Controls', 'my-calendar' ) . "'/></p>
		</fieldset>
		</form>";

		return $output;
	}
}

/**
 * Produce the form to submit location data
 *
 * @param boolean $has_data Whether currently have data.
 * @param object  $data event or location data.
 * @param string  $context whether currently in an event or a location context.
 *
 * @return string HTML form fields
 */
function mc_locations_fields( $has_data, $data, $context = 'location' ) {
	$return = '<div class="mc-locations">';
	if ( current_user_can( 'mc_edit_locations' ) && 'event' == $context ) {
		$return .= '<p class="checkboxes"><input type="checkbox" value="on" name="mc_copy_location" id="mc_copy_location" /> <label for="mc_copy_location">' . __( 'Copy this location into the locations table', 'my-calendar' ) . '</label></p>';
	}
	$return   .= '
	<p class="checkbox">
	<label for="e_label">' . __( 'Name of Location (e.g. <em>Joe\'s Bar and Grill</em>)', 'my-calendar' ) . '</label>';
	$cur_label = ( ! empty( $data ) ) ? ( stripslashes( $data->{$context . '_label'} ) ) : '';
	if ( mc_controlled_field( 'label' ) ) {
		$return .= mc_location_controller( 'label', $cur_label, $context );
	} else {
		$return .= '<input type="text" id="e_label" name="' . $context . '_label" size="40" value="' . esc_attr( $cur_label ) . '" />';
	}
	$street_address  = ( $has_data ) ? esc_attr( stripslashes( $data->{$context . '_street'} ) ) : '';
	$street_address2 = ( $has_data ) ? esc_attr( stripslashes( $data->{$context . '_street2'} ) ) : '';
	$return         .= '
	</p>
	<div class="locations-container">
	<div class="location-primary">
	<fieldset>
	<legend>' . __( 'Location Address', 'my-calendar' ) . '</legend>
	<p>
		<label for="e_street">' . __( 'Street Address', 'my-calendar' ) . '</label> <input type="text" id="e_street" name="' . $context . '_street" size="40" value="' . $street_address . '" />
	</p>
	<p>
		<label for="e_street2">' . __( 'Street Address (2)', 'my-calendar' ) . '</label> <input type="text" id="e_street2" name="' . $context . '_street2" size="40" value="' . $street_address2 . '" />
	</p>
	<p>
		<label for="e_city">' . __( 'City', 'my-calendar' ) . '</label> ';
	$cur_city        = ( ! empty( $data ) ) ? ( stripslashes( $data->{$context . '_city'} ) ) : '';
	if ( mc_controlled_field( 'city' ) ) {
		$return .= mc_location_controller( 'city', $cur_city, $context );
	} else {
		$return .= '<input type="text" id="e_city" name="' . $context . '_city" size="40" value="' . esc_attr( $cur_city ) . '" />';
	}
	$return   .= '</p><p>';
	$return   .= '<label for="e_state">' . __( 'State/Province', 'my-calendar' ) . '</label> ';
	$cur_state = ( ! empty( $data ) ) ? ( stripslashes( $data->{$context . '_state'} ) ) : '';
	if ( mc_controlled_field( 'state' ) ) {
		$return .= mc_location_controller( 'state', $cur_state, $context );
	} else {
		$return .= '<input type="text" id="e_state" name="' . $context . '_state" size="10" value="' . esc_attr( $cur_state ) . '" />';
	}
	$return      .= '</p><p><label for="e_postcode">' . __( 'Postal Code', 'my-calendar' ) . '</label> ';
	$cur_postcode = ( ! empty( $data ) ) ? ( stripslashes( $data->{$context . '_postcode'} ) ) : '';
	if ( mc_controlled_field( 'postcode' ) ) {
		$return .= mc_location_controller( 'postcode', $cur_postcode, $context );
	} else {
		$return .= '<input type="text" id="e_postcode" name="' . $context . '_postcode" size="40" value="' . esc_attr( $cur_postcode ) . '" />';
	}
	$return    .= '</p><p>';
	$return    .= '<label for="e_region">' . __( 'Region', 'my-calendar' ) . '</label> ';
	$cur_region = ( ! empty( $data ) ) ? ( stripslashes( $data->{$context . '_region'} ) ) : '';
	if ( mc_controlled_field( 'region' ) ) {
		$return .= mc_location_controller( 'region', $cur_region, $context );
	} else {
		$return .= '<input type="text" id="e_region" name="' . $context . '_region" size="40" value="' . esc_attr( $cur_region ) . '" />';
	}
	$return     .= '</p><p><label for="e_country">' . __( 'Country', 'my-calendar' ) . '</label> ';
	$cur_country = ( $has_data ) ? ( stripslashes( $data->{$context . '_country'} ) ) : '';
	if ( mc_controlled_field( 'country' ) ) {
		$return .= mc_location_controller( 'country', $cur_country, $context );
	} else {
		$return .= '<input type="text" id="e_country" name="' . $context . '_country" size="10" value="' . esc_attr( $cur_country ) . '" />';
	}
	$zoom         = ( $has_data ) ? $data->{$context . '_zoom'} : '16';
	$event_phone  = ( $has_data ) ? esc_attr( stripslashes( $data->{$context . '_phone'} ) ) : '';
	$event_phone2 = ( $has_data ) ? esc_attr( stripslashes( $data->{$context . '_phone2'} ) ) : '';
	$event_url    = ( $has_data ) ? esc_attr( stripslashes( $data->{$context . '_url'} ) ) : '';
	$event_lat    = ( $has_data ) ? esc_attr( stripslashes( $data->{$context . '_latitude'} ) ) : '';
	$event_lon    = ( $has_data ) ? esc_attr( stripslashes( $data->{$context . '_longitude'} ) ) : '';
	$return      .= '</p>
	<p>
	<label for="e_zoom">' . __( 'Initial Zoom', 'my-calendar' ) . '</label>
		<select name="' . $context . '_zoom" id="e_zoom">
			<option value="16"' . mc_option_selected( $zoom, '16', 'option' ) . '>' . __( 'Neighborhood', 'my-calendar' ) . '</option>
			<option value="14"' . mc_option_selected( $zoom, '14', 'option' ) . '>' . __( 'Small City', 'my-calendar' ) . '</option>
			<option value="12"' . mc_option_selected( $zoom, '12', 'option' ) . '>' . __( 'Large City', 'my-calendar' ) . '</option>
			<option value="10"' . mc_option_selected( $zoom, '10', 'option' ) . '>' . __( 'Greater Metro Area', 'my-calendar' ) . '</option>
			<option value="8"' . mc_option_selected( $zoom, '8', 'option' ) . '>' . __( 'State', 'my-calendar' ) . '</option>
			<option value="6"' . mc_option_selected( $zoom, '6', 'option' ) . '>' . __( 'Region', 'my-calendar' ) . '</option>
		</select>
	</p>
	</fieldset>
	<fieldset>
	<legend>' . __( 'GPS Coordinates (optional)', 'my-calendar' ) . '</legend>
	<p>
	' . __( 'If you supply GPS coordinates for your location, they will be used in place of any other address information to provide your map link.', 'my-calendar' ) . '
	</p>
	<p>
	<label for="e_latitude">' . __( 'Latitude', 'my-calendar' ) . '</label> <input type="text" id="e_latitude" name="' . $context . '_latitude" size="10" value="' . $event_lat . '" /> <label for="e_longitude">' . __( 'Longitude', 'my-calendar' ) . '</label> <input type="text" id="e_longitude" name="' . $context . '_longitude" size="10" value="' . $event_lon . '" />
	</p>
	</fieldset>
	</div>
	<div class="location-secondary">
	<fieldset>
	<legend>' . __( 'Location Contact Information', 'my-calendar' ) . '</legend>
	<p>
	<label for="e_phone">' . __( 'Phone', 'my-calendar' ) . '</label> <input type="text" id="e_phone" name="' . $context . '_phone" size="32" value="' . $event_phone . '" />
	</p>
	<p>
	<label for="e_phone2">' . __( 'Secondary Phone', 'my-calendar' ) . '</label> <input type="text" id="e_phone2" name="' . $context . '_phone2" size="32" value="' . $event_phone2 . '" />
	</p>
	<p>
	<label for="e_url">' . __( 'Location URL', 'my-calendar' ) . '</label> <input type="text" id="e_url" name="' . $context . '_url" size="40" value="' . $event_url . '" />
	</p>
	</fieldset>
	<fieldset>
	<legend>' . __( 'Location Accessibility', 'my-calendar' ) . '</legend>
	<ul class="accessibility-features checkboxes">';
	$access       = apply_filters( 'mc_venue_accessibility', mc_location_access() );
	$access_list  = '';
	if ( $has_data ) {
		if ( 'location' == $context ) {
			$location_access = unserialize( $data->{$context . '_access'} );
		} else {
			if ( property_exists( $data, 'event_location' ) ) {
				$event_location = $data->event_location;
			} else {
				$event_location = false;
			}
			$location_access = unserialize( mc_location_data( 'location_access', $event_location ) );
		}
	} else {
		$location_access = array();
	}
	foreach ( $access as $k => $a ) {
		$id      = "loc_access_$k";
		$label   = $a;
		$checked = '';
		if ( is_array( $location_access ) ) {
			$checked = ( in_array( $a, $location_access ) || in_array( $k, $location_access ) ) ? " checked='checked'" : '';
		}
		$item         = sprintf( '<li><input type="checkbox" id="%1$s" name="' . $context . '_access[]" value="%4$s" class="checkbox" %2$s /> <label for="%1$s">%3$s</label></li>', esc_attr( $id ), $checked, esc_html( $label ), esc_attr( $a ) );
		$access_list .= $item;
	}
	$return .= $access_list;
	$return .= '</ul>
	</fieldset></div>
	</div>
	</div>';

	$api_key = get_option( 'mc_gmap_api_key' );
	if ( $api_key ) {
		$return .= '<h3>' . __( 'Location Map', 'my-calendar' ) . '</h3>';
		$map     = mc_generate_map( $data, $context );

		$return .= ( '' == $map ) ? __( 'Not enough information to generate a map', 'my-calendar' ) : $map;
	} else {
		// Translators: URL to settings page to add key.
		$return .= sprintf( __( 'Add a <a href="%s">Google Maps API Key</a> to generate a location map.', 'my-calendar' ), admin_url( 'admin.php?page=my-calendar-config#mc-output' ) );
	}

	return $return;
}

/**
 * Array of location access features
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

	return apply_filters( 'mc_location_access_choices', $location_access );
}

/**
 * Get a specific field with an location ID
 *
 * @param string $field Specific field to get.
 * @param int    $id Location ID.
 *
 * @return mixed value
 */
function mc_location_data( $field, $id ) {
	if ( $id ) {
		global $wpdb;
		$mcdb = $wpdb;
		if ( 'true' == get_option( 'mc_remote' ) && function_exists( 'mc_remote_db' ) ) {
			$mcdb = mc_remote_db();
		}
		$field  = $field;
		$sql    = $mcdb->prepare( "SELECT $field FROM " . my_calendar_locations_table() . ' WHERE location_id = %d', $id );
		$result = $mcdb->get_var( $sql );

		return $result;
	}
}


/**
 * Get options list of locations to choose from
 *
 * @param object $location location object.
 *
 * @return string set of option elements
 */
function mc_location_select( $location = false ) {
	// Grab all locations and list them.
	$list = '';
	$locs = mc_get_locations( 'select-locations' );

	foreach ( $locs as $loc ) {
		$l = '<option value="' . $loc->location_id . '"';
		if ( $location ) {
			if ( $location == $loc->location_id ) {
				$l .= ' selected="selected"';
			}
		}
		$l    .= '>' . mc_kses_post( stripslashes( $loc->location_label ) ) . '</option>';
		$list .= $l;
	}

	return '<option value="">' . __( 'Select', 'my-calendar' ) . '</option>' . $list;
}

/**
 * Get list of locations (IDs and labels)
 *
 * @param array $args array of relevant arguments.
 *
 * @return array locations (IDs and labels only)
 */
function mc_get_locations( $args ) {
	global $wpdb;
	if ( is_array( $args ) ) {
		$context = ( isset( $args['context'] ) ) ? $args['context'] : 'general';
		$orderby = ( isset( $args['orderby'] ) ) ? $args['orderby'] : 'location_label';
		$order   = ( isset( $args['order'] ) ) ? $args['order'] : 'ASC';
		$where   = ( isset( $args['where'] ) ) ? $args['where'] : '1';
		$is      = ( isset( $args['is'] ) ) ? $args['is'] : '1';
	} else {
		$context = $args;
		$orderby = 'location_label';
		$order   = 'ASC';
		$where   = '1';
		$is      = '1';
	}
	if ( ! ( 'ASC' == $order || 'DESC' == $order ) ) {
		// Prevent invalid order parameters.
		$order = 'ASC';
	}
	$valid_args = $wpdb->get_col( 'DESC ' . my_calendar_locations_table() ); // WPCS: unprepared SQL ok.
	if ( ! ( in_array( $orderby, $valid_args ) ) ) {
		// Prevent invalid order columns.
		$orderby = 'location_label';
	}
	$results = $wpdb->get_results( $wpdb->prepare( 'SELECT location_id,location_label FROM ' . my_calendar_locations_table() . ' WHERE %s = %s ORDER BY ' . $orderby . ' ' . $order, $where, $is ) ); // WPCS: unprepared SQL ok.

	return apply_filters( 'mc_filter_results', $results, $args );
}
