<?php
/**
 * Manage My Calendar settings
 *
 * @category Settings
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a My Calendar setting.
 *
 * @param string $key Setting key.
 *
 * @return mixed A boolean false return means the setting doesn't exist.
 */
function mc_get_option( $key ) {
	$options = get_option( 'my_calendar_options', mc_default_options() );
	if ( ! is_array( $options ) ) {
		$options = mc_default_options();
	}
	$default = mc_default_options();
	$options = array_merge( $default, $options );
	$new_key = str_replace( 'mc_', '', $key );
	$value   = isset( $options[ $new_key ] ) ? $options[ $new_key ] : false;
	if ( ( ( 0 !== $value && ! $value ) || '' === $options[ $new_key ] ) && ( isset( $default[ $new_key ] ) && ! empty( $default[ $new_key ] ) ) ) {
		return $default[ $new_key ];
	}

	return ( is_array( $value ) ) ? $value : (string) $value;
}

/**
 * Save a My Calendar setting.
 *
 * @param string $key Setting key.
 * @param mixed  $value Setting value.
 *
 * @return bool
 */
function mc_update_option( $key, $value = '' ) {
	$options = get_option( 'my_calendar_options', mc_default_options() );
	if ( ! is_array( $options ) ) {
		$options = mc_default_options();
	}
	$options[ $key ] = $value;
	$return          = update_option( 'my_calendar_options', $options );

	return $return;
}

/**
 * Save a group of My Calendar settings.
 *
 * @param array $options An array of settings.
 *
 * @return bool
 */
function mc_update_options( $options ) {
	if ( empty( $options ) ) {
		return false;
	}
	$settings = get_option( 'my_calendar_options' );
	$options  = array_merge( $settings, $options );

	return update_option( 'my_calendar_options', $options );
}

/**
 * Generate input & field for a My Calendar setting.
 *
 * @param array $args {
 *     Array of settings arguments.
 *
 *     @type string       $name Name of the option used in name attribute. Required.
 *     @type string|array $label Input label or array of labels (for radio or checkbox groups).
 *     @type string|array $default Default value or values when option not set.
 *     @type string       $note Note associated using aria-describedby.
 *     @type array        $atts Array of attributes to use on the input.
 *     @type string       $type Type of input field.
 *     @type boolean      $echo True to echo, false to return.
 * }
 *
 * @return string|void
 */
function mc_settings_field( $args = array() ) {
	$name    = ( isset( $args['name'] ) ) ? $args['name'] : '';
	$label   = ( isset( $args['label'] ) ) ? $args['label'] : '';
	$default = ( isset( $args['default'] ) ) ? $args['default'] : '';
	$note    = ( isset( $args['note'] ) ) ? $args['note'] : '';
	$atts    = ( isset( $args['atts'] ) ) ? $args['atts'] : array();
	$type    = ( isset( $args['type'] ) ) ? $args['type'] : 'text';
	$echo    = ( isset( $args['echo'] ) ) ? $args['echo'] : true;
	$wrap    = ( isset( $args['wrap'] ) ) ? $args['wrap'] : array();
	$element = '';
	$close   = '';
	if ( ! empty( $wrap ) ) {
		$el    = isset( $wrap['element'] ) ? $wrap['element'] : 'p';
		$class = '';
		$id    = '';
		if ( isset( $wrap['class'] ) && '' !== $wrap['class'] ) {
			$class = ' class="' . $wrap['class'] . '"';
		}
		if ( isset( $wrap['id'] ) && '' !== $wrap['id'] ) {
			$id = ' id="' . $wrap['id'] . '"';
		}
		$element = "<$el$class$id>";
		$close   = "</$el>";
	}

	if ( is_string( $args ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			__( 'Since My Calendar 3.4.0, these function arguments must be an array.', 'my-calendar' ),
			'3.4.0'
		);
	}
	$options    = '';
	$attributes = '';
	$return     = '';
	if ( 'text' === $type || 'url' === $type || 'email' === $type ) {
		$base_atts = array(
			'size' => '30',
		);
	} else {
		$base_atts = $atts;
	}
	$value = mc_get_option( $name );
	$atts  = array_merge( $base_atts, $atts );
	if ( is_array( $atts ) && ! empty( $atts ) ) {
		foreach ( $atts as $key => $val ) {
			$attributes .= " $key='" . esc_attr( $val ) . "'";
		}
	}
	if ( 'checkbox' !== $type ) {
		if ( is_array( $default ) ) {
			$hold = '';
		} else {
			$hold = $default;
		}
		$value = ( '' !== $value ) ? esc_attr( stripslashes( $value ) ) : $hold;
	} else {
		$value = ( ! empty( $value ) ) ? (array) $value : $default;
	}
	switch ( $type ) {
		case 'text':
		case 'url':
		case 'email':
			if ( $note ) {
				$note = sprintf( str_replace( '%', '', $note ), "<code>$value</code>" );
				$note = "<span id='$name-note'><i class='dashicons dashicons-editor-help' aria-hidden='true'></i>$note</span>";
				$aria = " aria-describedby='$name-note'";
			} else {
				$note = '';
				$aria = '';
			}
			$return = "$element<label class='label-$type' for='$name'>$label</label> <input type='$type' id='$name' name='$name' value='" . esc_attr( $value ) . "'$aria$attributes />$close $note";
			break;
		case 'hidden':
			$return = "<input type='hidden' id='$name' name='$name' value='" . esc_attr( $value ) . "' />";
			break;
		case 'textarea':
			if ( $note ) {
				$note = sprintf( $note, "<code>$value</code>" );
				$note = "<span id='$name-note'><i class='dashicons dashicons-editor-help' aria-hidden='true'></i>$note</span>";
				$aria = " aria-describedby='$name-note'";
			} else {
				$note = '';
				$aria = '';
			}
			$return = "$element<label class='label-textarea' for='$name'>$label</label><br /><textarea id='$name' name='$name'$aria$attributes>" . esc_attr( $value ) . "</textarea>$close$note";
			break;
		case 'checkbox-single':
			$checked = checked( 'true', mc_get_option( str_replace( 'mc_', '', $name ) ), false );
			if ( $note ) {
				$note = "<div id='$name-note'><i class='dashicons dashicons-editor-help' aria-hidden='true'></i>" . sprintf( $note, "<code>$value</code>" ) . '</div>';
				$aria = " aria-describedby='$name-note'";
			} else {
				$note = '';
				$aria = '';
			}
			$return = "$element<input type='checkbox' id='$name' name='$name' value='on' $checked$attributes$aria /> <label for='$name' class='label-checkbox'>$label</label>$close$note";
			break;
		case 'checkbox':
		case 'radio':
			if ( $note ) {
				$note = sprintf( $note, "<code>$value</code>" );
				$note = "<span id='$name-note'><i class='dashicons dashicons-editor-help' aria-hidden='true'></i>$note</span>";
				$aria = " aria-describedby='$name-note'";
			} else {
				$note = '';
				$aria = '';
			}
			$att_name = $name;
			if ( 'checkbox' === $type ) {
				$att_name = $name . '[]';
			}
			if ( is_array( $label ) ) {
				foreach ( $label as $k => $v ) {
					if ( 'radio' === $type ) {
						$checked = ( $k === $value ) ? ' checked="checked"' : '';
					} else {
						$checked = ( in_array( $k, $value, true ) ) ? ' checked="checked"' : '';
					}
					$options .= "<li>$element<input type='$type' id='$name-$k' value='" . esc_attr( $k ) . "' name='$att_name'$aria$attributes$checked /> <label class='label-$type' for='$name-$k'>$v</label>$close</li>";
				}
			}
			$return = "$options $note";
			break;
		case 'select':
			if ( $note ) {
				$note = sprintf( $note, "<code>$value</code>" );
				$note = "<span id='$name-note'><i class='dashicons dashicons-editor-help' aria-hidden='true'></i>$note</span>";
				$aria = " aria-describedby='$name-note'";
			} else {
				$note = '';
				$aria = '';
			}
			if ( is_array( $default ) ) {
				foreach ( $default as $k => $v ) {
					$checked  = ( (string) $k === (string) $value ) ? ' selected="selected"' : '';
					$options .= "<option value='" . esc_attr( $k ) . "'$checked>$v</option>";
				}
			}
			$return = "
				<label class='label-select' for='$name'>$label</label>
				$element<select id='$name' name='$name'$aria$attributes />
					$options
				</select>$close
			$note";
			break;
	}

	if ( true === $echo ) {
		echo wp_kses( $return, mc_kses_elements() );
	} else {
		return $return;
	}
}

/**
 * Update Management Settings.
 *
 * @param array $post POST data.
 */
function mc_update_management_settings( $post ) {
	// Management settings.
	$option           = array();
	$mc_api_enabled   = ( ! empty( $post['mc_api_enabled'] ) && 'on' === $post['mc_api_enabled'] ) ? 'true' : 'false';
	$mc_remote        = ( ! empty( $post['mc_remote'] ) && 'on' === $post['mc_remote'] ) ? 'true' : 'false';
	$mc_drop_tables   = ( ! empty( $post['mc_drop_tables'] ) && 'on' === $post['mc_drop_tables'] ) ? 'true' : 'false';
	$mc_drop_settings = ( ! empty( $post['mc_drop_settings'] ) && 'on' === $post['mc_drop_settings'] ) ? 'true' : 'false';
	// Handle My Calendar primary URL. Storing URL string removed in 3.5.0.
	$option['use_permalinks']    = ( ! empty( $post['mc_use_permalinks'] ) ) ? 'true' : 'false';
	$option['uri_id']            = absint( $post['mc_uri_id'] );
	$option['api_enabled']       = $mc_api_enabled;
	$option['remote']            = $mc_remote;
	$option['drop_tables']       = $mc_drop_tables;
	$option['drop_settings']     = $mc_drop_settings;
	$option['default_sort']      = absint( $post['mc_default_sort'] );
	$option['default_direction'] = sanitize_text_field( $post['mc_default_direction'] );

	mc_update_options( $option );

	if ( 2 === (int) get_site_option( 'mc_multisite' ) ) {
		$mc_current_table = ( isset( $post['mc_current_table'] ) ) ? (int) $post['mc_current_table'] : 0;
		mc_update_option( 'current_table', $mc_current_table );
	}
}

/**
 * Get array of My Calendar user capabilities.
 *
 * @return array
 */
function mc_get_user_capabilities() {
	$caps = array(
		'mc_add_events'     => __( 'Add Events', 'my-calendar' ),
		'mc_publish_events' => __( 'Publish Events', 'my-calendar' ),
		'mc_approve_events' => __( 'Approve Events', 'my-calendar' ),
		'mc_manage_events'  => __( 'Manage Events', 'my-calendar' ),
		'mc_edit_locations' => __( 'Edit Locations', 'my-calendar' ),
		'mc_edit_cats'      => __( 'Edit Categories', 'my-calendar' ),
		'mc_edit_styles'    => __( 'Edit Styles', 'my-calendar' ),
		'mc_edit_behaviors' => __( 'Manage Scripts', 'my-calendar' ),
		'mc_edit_templates' => __( 'Edit Templates', 'my-calendar' ),
		'mc_edit_settings'  => __( 'Edit Settings', 'my-calendar' ),
		'mc_view_help'      => __( 'View Help', 'my-calendar' ),
	);
	/**
	 * Add custom capabilities to the array of My Calendar permissions. New capabilities can be assigned to roles in the My Calendar settings.
	 *
	 * @hook mc_capabilities
	 *
	 * @param {array} Array of My Calendar capabilities in format ['capability' => 'Visible Label'].
	 *
	 * @return {array}
	 */
	$caps = apply_filters( 'mc_capabilities', $caps );

	return $caps;
}

/**
 * Update Permissions settings.
 *
 * @param array $post POST data.
 */
function mc_update_permissions_settings( $post ) {
	$perms = $post['mc_caps'];
	$caps  = mc_get_user_capabilities();

	foreach ( $perms as $key => $value ) {
		$role = get_role( $key );
		if ( is_object( $role ) ) {
			foreach ( $caps as $k => $v ) {
				if ( isset( $value[ $k ] ) ) {
					$role->add_cap( $k );
				} else {
					$role->remove_cap( $k );
				}
			}
		}
	}
}

/**
 * Update output settings.
 *
 * @param array $post POST data.
 */
function mc_update_output_settings( $post ) {
	$options                     = array();
	$options['open_uri']         = ( ! empty( $post['mc_open_uri'] ) ) ? $post['mc_open_uri'] : 'off';
	$options['mini_uri']         = $post['mc_mini_uri'];
	$options['open_day_uri']     = ( ! empty( $post['mc_open_day_uri'] ) ) ? $post['mc_open_day_uri'] : '';
	$options['show_list_info']   = ( ! empty( $post['mc_show_list_info'] ) && 'on' === $post['mc_show_list_info'] ) ? 'true' : 'false';
	$options['list_link_titles'] = ( ! empty( $post['mc_list_link_titles'] ) && 'on' === $post['mc_list_link_titles'] ) ? 'true' : 'false';
	$options['hide_past_dates']  = ( ! empty( $post['mc_hide_past_dates'] ) && 'on' === $post['mc_hide_past_dates'] ) ? 'true' : 'false';
	$options['show_months']      = (int) $post['mc_show_months'];
	$options['map_service']      = ( in_array( $post['mc_map_service'], array( 'mapquest', 'bing', 'google', 'none' ), true ) ) ? $post['mc_map_service'] : 'google';
	$options['maptype']          = ( in_array( $post['mc_maptype'], array( 'roadmap', 'satellite', 'hybrid', 'terrain' ), true ) ) ? $post['mc_maptype'] : 'roadmap';

	// Calculate sequence for navigation elements.
	$top    = array();
	$bottom = array();
	$nav    = $post['mc_nav'];
	$set    = 'top';
	foreach ( $nav as $n ) {
		if ( 'calendar' === $n ) {
			$set = 'bottom';
		} else {
			if ( 'top' === $set ) {
				$top[] = $n;
			} else {
				$bottom[] = $n;
			}
		}
	}
	$options['bottomnav']      = ( empty( $bottom ) ) ? 'none' : implode( ',', $bottom );
	$options['topnav']         = ( empty( $top ) ) ? 'none' : implode( ',', $top );
	$views                     = ( empty( $post['mc_views'] ) ) ? array() : $post['mc_views'];
	$single                    = ( empty( $post['mc_display_single'] ) ) ? array() : $post['mc_display_single'];
	$main                      = ( empty( $post['mc_display_main'] ) ) ? array() : $post['mc_display_main'];
	$card                      = ( empty( $post['mc_display_card'] ) ) ? array() : $post['mc_display_card'];
	$mini                      = ( empty( $post['mc_display_mini'] ) ) ? array() : $post['mc_display_mini'];
	$options['display_single'] = array_map( 'sanitize_text_field', $single );
	$options['display_main']   = array_map( 'sanitize_text_field', $main );
	$options['display_card']   = array_map( 'sanitize_text_field', $card );
	$options['display_mini']   = array_map( 'sanitize_text_field', $mini );
	$options['views']          = array_map( 'sanitize_text_field', $views );
	$options['gmap_api_key']   = ( ! empty( $post['mc_gmap_api_key'] ) ) ? strip_tags( $post['mc_gmap_api_key'] ) : '';
	$options['show_weekends']  = ( ! empty( $post['mc_show_weekends'] ) && 'on' === $post['mc_show_weekends'] ) ? 'true' : 'false';
	$options['convert']        = ( ! empty( $post['mc_convert'] ) ) ? $post['mc_convert'] : 'false';

	$options['disable_legacy_templates'] = ( ! empty( $post['mc_disable_legacy_templates'] ) && 'on' === $post['mc_disable_legacy_templates'] ) ? 'true' : 'false';

	$templates               = mc_get_option( 'templates' );
	$templates['title']      = $post['mc_title_template'];
	$templates['title_solo'] = $post['mc_title_template_solo'];
	$templates['title_list'] = $post['mc_title_template_list'];
	$templates['title_card'] = $post['mc_title_template_card'];
	$options['templates']    = $templates;

	mc_update_options( $options );
}

/**
 * Update input settings.
 *
 * @param array $post POST data.
 */
function mc_update_input_settings( $post ) {
	$options                         = array();
	$mc_input_options_administrators = ( isset( $post['mc_input_options_administrators'] ) ) ? 'true' : 'false';
	$mc_input_options                = array(
		'event_short'    => ( isset( $post['mci_event_short'] ) ) ? 'on' : 'off',
		'event_desc'     => ( isset( $post['mci_event_desc'] ) ) ? 'on' : 'off',
		'event_category' => ( isset( $post['mci_event_category'] ) ) ? 'on' : 'off',
		'event_image'    => ( isset( $post['mci_event_image'] ) ) ? 'on' : 'off',
		'event_link'     => ( isset( $post['mci_event_link'] ) ) ? 'on' : 'off',
		'event_recurs'   => ( isset( $post['mci_event_recurs'] ) ) ? 'on' : 'off',
		'event_open'     => ( isset( $post['mci_event_open'] ) ) ? 'on' : 'off',
		'event_location' => ( isset( $post['mci_event_location'] ) ) ? 'on' : 'off',
		'event_access'   => ( isset( $post['mci_event_access'] ) ) ? 'on' : 'off',
		'event_host'     => ( isset( $post['mci_event_host'] ) ) ? 'on' : 'off',
	);

	$options['input_options']                = $mc_input_options;
	$options['input_options_administrators'] = $mc_input_options_administrators;

	mc_update_options( $options );
}

/**
 * Update text settings.
 *
 * @param array $post POST data.
 */
function mc_update_text_settings( $post ) {
	// This is the <title> element, and should not contain HTML.
	$options['event_title_template'] = $post['mc_event_title_template'];
	foreach ( $post as $key => $value ) {
		// If POST is set, change the sanitizing for settings in this group.
		$post[ $key ] = isset( $_POST[ $key ] ) ? wp_kses_post( $_POST[ $key ] ) : $value;
	}
	$options['heading_text']        = isset( $_POST['mc_heading_text'] ) ? wp_kses_post( $_POST['mc_heading_text'] ) : $post['mc_heading_text'];
	$options['notime_text']         = $post['mc_notime_text'];
	$options['hosted_by']           = $post['mc_hosted_by'];
	$options['posted_by']           = $post['mc_posted_by'];
	$options['buy_tickets']         = $post['mc_buy_tickets'];
	$options['event_accessibility'] = $post['mc_event_accessibility'];
	$options['view_full']           = $post['mc_view_full'];
	$options['previous_events']     = $post['mc_previous_events'];
	$options['next_events']         = $post['mc_next_events'];
	$options['today_events']        = $post['mc_today_events'];
	$options['week_caption']        = $post['mc_week_caption'];
	$options['caption']             = $post['mc_caption'];
	$templates                      = mc_get_option( 'templates' );
	$templates['label']             = $post['mc_details_label'];
	$templates['link']              = $post['mc_link_label'];
	$options['templates']           = $templates;
	// Date/time.
	$options['date_format']  = wp_unslash( $post['mc_date_format'] );
	$options['week_format']  = wp_unslash( $post['mc_week_format'] );
	$options['time_format']  = wp_unslash( $post['mc_time_format'] );
	$options['month_format'] = wp_unslash( $post['mc_month_format'] );

	mc_update_options( $options );
}

/**
 * Save email settings
 *
 * @param array $post POST array.
 */
function mc_update_email_settings( $post ) {
	$options                       = array();
	$options['event_mail']         = ( ! empty( $post['mc_event_mail'] ) && 'on' === $post['mc_event_mail'] ) ? 'true' : 'false';
	$options['html_email']         = ( ! empty( $post['mc_html_email'] ) && 'on' === $post['mc_html_email'] ) ? 'true' : 'false';
	$options['event_mail_to']      = $post['mc_event_mail_to'];
	$options['event_mail_from']    = $post['mc_event_mail_from'];
	$options['event_mail_subject'] = $post['mc_event_mail_subject'];
	$options['event_mail_message'] = ( 'true' === $options['html_email'] && isset( $_POST['mc_event_mail_message'] ) ) ? wp_kses_post( $_POST['mc_event_mail_message'] ) : $post['mc_event_mail_message'];
	$options['event_mail_bcc']     = $post['mc_event_mail_bcc'];

	mc_update_options( $options );
}

/**
 * Generate URL to export settings.
 */
function mc_export_settings_url() {
	$nonce = wp_create_nonce( 'mc-export-settings' );
	$url   = add_query_arg( 'mc-export-settings', $nonce, admin_url( 'admin.php?my-calendar-config' ) );

	return $url;
}

/**
 * Export settings
 */
function mc_export_settings() {
	if ( isset( $_GET['mc-export-settings'] ) ) {
		$nonce = wp_verify_nonce( $_GET['mc-export-settings'], 'mc-export-settings' );
		if ( $nonce ) {
			$date     = gmdate( 'Y-m-d', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			$settings = get_option( 'my_calendar_options' );
			header( 'Content-Type: application/json' );
			header( 'Content-Disposition: attachment; filename=my-calendar-' . sanitize_title( get_bloginfo( 'name' ) ) . '-' . $date . '.json' );
			header( 'Pragma: no-cache' );
			wp_send_json( $settings, 200 );
		}
	}
}
add_action( 'admin_init', 'mc_export_settings' );

/**
 * Import settings
 */
function mc_import_settings() {
	if ( isset( $_FILES['mc-import-settings'] ) ) {
		$nonce = wp_verify_nonce( $_POST['_wpnonce'], 'my-calendar-nonce' );
		if ( $nonce ) {
			$settings = ( 0 !== (int) $_FILES['mc-import-settings']['size'] ) ? file_get_contents( $_FILES['mc-import-settings']['tmp_name'] ) : false;
			if ( ! $settings ) {
				$return = __( 'No settings file provided.', 'my-calendar' );
			} else {
				$settings = json_decode( $settings, ARRAY_A );
				if ( null === $settings ) {
					$return = json_last_error();
				} else {
					$valid = mc_validate_settings( $settings );
					if ( ! $valid ) {
						$return = __( "The file uploaded doesn't seem to be a valid collection of My Calendar settings.", 'my-calendar' );
					} else {
						$settings = map_deep( $settings, 'sanitize_textarea_field' );
						// Remove the calendar location ID from imported settings. Set to local value if present.
						if ( isset( $settings['uri_id'] ) ) {
							if ( mc_get_option( 'uri_id' ) ) {
								$settings['uri_id'] = mc_get_option( 'uri_id' );
							} else {
								unset( $settings['uri_id'] );
							}
						}
						update_option( 'my_calendar_options', $settings );
						$return = __( 'My Calendar settings have been replaced with the imported values.', 'my-calendar' );
					}
				}
				return $return;
			}
		}
	}
	return '';
}

/**
 * Validate an array of settings against the default settings. Checks for 20 matching keys in random order.
 *
 * @param array $settings An array of data expected to be My Calendar settings.
 *
 * @return bool
 */
function mc_validate_settings( $settings ) {
	$defaults = mc_default_options();
	$keys     = array_keys( $settings );
	shuffle( $keys );
	// Settings may not be identical to default settings, as not all settings are configured by default. However, they should be similar.
	$i = 0;
	foreach ( $keys as $key ) {
		$exists = in_array( $key, array_keys( $defaults ), true ) ? true : false;
		if ( $exists ) {
			++$i;
		}
		if ( $i > 20 ) {
			return true;
			break;
		}
	}

	return false;
}

/**
 * Build settings form.
 */
function my_calendar_settings() {
	my_calendar_check();
	if ( ! empty( $_POST ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'My Calendar: Security check failed' );
		}
		// Custom sanitizing.
		$post = map_deep( $_POST, 'sanitize_textarea_field' );
		if ( isset( $post['mc_manage'] ) ) {
			$before_permalinks = mc_get_option( 'use_permalinks' );
			mc_update_management_settings( $post );
			mc_show_notice( __( 'My Calendar Management Settings saved', 'my-calendar' ) );
		}
		if ( isset( $post['mc_permissions'] ) ) {
			mc_update_permissions_settings( $post );
			mc_show_notice( __( 'My Calendar Permissions Updated', 'my-calendar' ) );
		}
		// Output.
		if ( isset( $post['mc_show_months'] ) ) {
			// Restore HTML in keys that permit HTML.
			$post['mc_title_template']      = wp_kses_post( $_POST['mc_title_template'] );
			$post['mc_title_template_list'] = wp_kses_post( $_POST['mc_title_template_list'] );
			$post['mc_title_template_solo'] = wp_kses_post( $_POST['mc_title_template_solo'] );
			mc_update_output_settings( $post );
			mc_show_notice( __( 'Display Settings saved', 'my-calendar' ) );
		}
		// Input.
		if ( isset( $post['mc_input'] ) ) {
			mc_update_input_settings( $post );
			mc_show_notice( __( 'Input Settings saved', 'my-calendar' ) );
		}
		if ( current_user_can( 'manage_network' ) && is_multisite() ) {
			if ( isset( $post['mc_network'] ) ) {
				$mc_multisite = (int) $post['mc_multisite'];
				update_site_option( 'mc_multisite', $mc_multisite );
				$mc_multisite_show = (int) $post['mc_multisite_show'];
				update_site_option( 'mc_multisite_show', $mc_multisite_show );
				mc_show_notice( __( 'Multisite settings saved', 'my-calendar' ) );
			}
		}
		// custom text.
		if ( isset( $post['mc_previous_events'] ) ) {
			mc_update_text_settings( $post );
			mc_show_notice( __( 'Custom text settings saved', 'my-calendar' ) );
		}

		// Save email settings.
		if ( isset( $post['mc_email'] ) ) {
			mc_update_email_settings( $post );
			mc_show_notice( __( 'Email notice settings saved', 'my-calendar' ) );
		}

		/**
		 * Run when settings are saved. Default ''.
		 *
		 * @hook mc_save_settings
		 *
		 * @param {string} Message after updating settings sent to `mc_show_notice()`.
		 * @param {array}  $post POST global.
		 */
		$settings = do_action( 'mc_save_settings', '', $post );
		if ( is_string( $settings ) && '' !== $settings ) {
			mc_show_notice( $settings );
		}

		$return = mc_import_settings();
		if ( $return ) {
			mc_show_notice( $return );
		}
	}

	// Pull templates for passing into functions.
	$templates              = mc_get_option( 'templates' );
	$mc_title_template      = ( isset( $templates['title'] ) ) ? esc_attr( stripslashes( $templates['title'] ) ) : '';
	$mc_title_template_solo = ( isset( $templates['title_solo'] ) ) ? esc_attr( stripslashes( $templates['title_solo'] ) ) : '';
	$mc_title_template_list = ( isset( $templates['title_list'] ) ) ? esc_attr( stripslashes( $templates['title_list'] ) ) : '';
	$mc_title_template_card = ( isset( $templates['title_card'] ) ) ? esc_attr( stripslashes( $templates['title_card'] ) ) : '';
	$mc_details_label       = ( isset( $templates['label'] ) ) ? esc_attr( stripslashes( $templates['label'] ) ) : '';
	$mc_link_label          = ( isset( $templates['link'] ) ) ? esc_attr( stripslashes( $templates['link'] ) ) : '';
	?>

	<div class="wrap my-calendar-admin mc-settings-page" id="mc_settings">
	<?php my_calendar_check_db(); ?>
	<h1><?php esc_html_e( 'My Calendar Settings', 'my-calendar' ); ?></h1>
	<div class="mc-tabs">
		<div class="tabs" role="tablist" data-default="my-calendar-manage">
			<button type="button" role="tab" aria-selected="false"  id="tab_manage" aria-controls="my-calendar-manage"><?php esc_html_e( 'General', 'my-calendar' ); ?></button>
			<button type="button" role="tab" aria-selected="false"  id="tab_text" aria-controls="my-calendar-text"><?php esc_html_e( 'Text', 'my-calendar' ); ?></button>
			<button type="button" role="tab" aria-selected="false"  id="tab_output" aria-controls="mc-output"><?php esc_html_e( 'Display', 'my-calendar' ); ?></button>
			<button type="button" role="tab" aria-selected="false"  id="tab_input" aria-controls="my-calendar-input"><?php esc_html_e( 'Input', 'my-calendar' ); ?></button>
			<?php
			if ( current_user_can( 'manage_network' ) && is_multisite() ) {
				?>
				<button type="button" role="tab" aria-selected="false" id="tab_multi" aria-controls="my-calendar-multisite"><?php esc_html_e( 'Multisite', 'my-calendar' ); ?></button>
				<?php
			}
			?>
			<button type="button" role="tab" aria-selected="false" id="tab_permissions" aria-controls="my-calendar-permissions"><?php esc_html_e( 'Permissions', 'my-calendar' ); ?></button>
			<button type="button" role="tab" id="tab_email" aria-selected="false" aria-controls="my-calendar-email"><?php esc_html_e( 'Notifications', 'my-calendar' ); ?></button>
			<?php
			/**
			 * Add additional buttons to collection of settings tabs.
			 *
			 * @hook mc_settings_section_links
			 *
			 * @param {string} HTML to output.
			 *
			 * @return {string}
			 */
			$links = apply_filters( 'mc_settings_section_links', '' );
			echo $links;
			?>
		</div>
		<div class="settings postbox-container jcd-wide">
<div class="metabox-holder">
	<div class="ui-sortable meta-box-sortables">
		<div class="wptab postbox" aria-labelledby="tab_manage" role="tabpanel" id="my-calendar-manage">
			<h2><?php esc_html_e( 'My Calendar Management', 'my-calendar' ); ?></h2>
			<div class="inside">
				<?php
				if ( current_user_can( 'administrator' ) ) {
					?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-config#my-calendar-manage' ) ); ?>">
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Management', 'my-calendar' ); ?></legend>
							<ul>
								<?php
								$page_title = '';
								$edit_link  = '';
								$note       = '';
								if ( mc_get_option( 'uri_id' ) ) {
									$page_title = get_post( absint( mc_get_option( 'uri_id' ) ) )->post_title;
									$edit_link  = esc_url( get_edit_post_link( absint( mc_get_option( 'uri_id' ) ) ) );
									// Translators: Editing URL for calendar page.
									$note = sprintf( __( 'Search for a different page or <a href="%s">edit the current calendar page</a>.', 'my-calendar' ), $edit_link );
								}
								?>
								<li id="mc-pages-autocomplete" class="mc-autocomplete autocomplete">
								<?php
								mc_settings_field(
									array(
										'name'    => 'mc_uri_query',
										'label'   => __( 'Set My Calendar Primary Page', 'my-calendar' ),
										'default' => $page_title,
										'note'    => $note,
										'atts'    => array(
											'size'  => '20',
											'class' => 'autocomplete-input',
										),
									)
								);
								?>
								<ul class="autocomplete-result-list"></ul>
								<?php
								mc_settings_field(
									array(
										'name'  => 'mc_uri_id',
										'label' => '',
										'type'  => 'hidden',
									)
								);
								?>
								</li>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'    => 'mc_default_sort',
										'label'   => __( 'Default sort for Admin Events', 'my-calendar' ),
										'default' => array(
											'1' => __( 'ID', 'my-calendar' ),
											'2' => __( 'Title', 'my-calendar' ),
											'4' => __( 'Date/Time', 'my-calendar' ),
											'5' => __( 'Author', 'my-calendar' ),
											'6' => __( 'Category', 'my-calendar' ),
											'7' => __( 'Location', 'my-calendar' ),
										),
										'type'    => 'select',
									)
								);
								?>
								</li>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'    => 'mc_default_direction',
										'label'   => __( 'Default sort direction', 'my-calendar' ),
										'default' => array(
											'ASC'  => __( 'Ascending', 'my-calendar' ),
											'DESC' => __( 'Descending', 'my-calendar' ),
										),
										'type'    => 'select',
									)
								);
								?>
								</li>
								<?php
								if ( isset( $_POST['mc_use_permalinks'] ) && ( ! ( 'on' === $_POST['mc_use_permalinks'] && 'true' === $before_permalinks ) ) ) {
									$url = admin_url( 'options-permalink.php#mc_cpt_base' );
									// Translators: URL for WordPress Settings > Permalinks.
									$note = ' <span class="mc-notice">' . sprintf( __( 'Go to <a href="%s">permalink settings</a> to set the base URL for events.', 'my-calendar' ) . '</span>', $url );
								} else {
									$note = '';
								}
								?>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'  => 'mc_use_permalinks',
										'label' => __( 'Use Pretty Permalinks for Events', 'my-calendar' ),
										'note'  => $note,
										'type'  => 'checkbox-single',
									)
								);
								?>
								</li>
								<?php
								if ( (int) get_site_option( 'mc_multisite' ) === 2 && my_calendar_table() !== my_calendar_table( 'global' ) ) {
									mc_settings_field(
										array(
											'name'    => 'mc_current_table',
											'label'   => array(
												'0' => __( 'Currently editing my local calendar', 'my-calendar' ),
												'1' => __( 'Currently editing the network calendar', 'my-calendar' ),
											),
											'default' => '0',
											'type'    => 'radio',
										)
									);
								} else {
									if ( mc_get_option( 'remote' ) !== 'true' && current_user_can( 'manage_network' ) && is_multisite() && is_main_site() ) {
										?>
										<li><?php esc_html_e( 'You are currently working in the primary site for this network; your local calendar is also the global table.', 'my-calendar' ); ?></li>
										<?php
									}
								}
								?>
							</ul>
						</fieldset>
						<fieldset>
							<legend><?php esc_html_e( 'Advanced', 'my-calendar' ); ?></legend>
							<ul>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'  => 'mc_remote',
										'label' => __( 'Get data (events, categories and locations) from a remote database', 'my-calendar' ),
										'type'  => 'checkbox-single',
									)
								);
								?>
								</li>
								<?php
								if ( 'true' === mc_get_option( 'remote' ) && ! function_exists( 'mc_remote_db' ) ) {
									$class = 'visible';
								} else {
									$class = 'hidden';
								}
								?>
								<li class="mc_remote_info <?php echo $class; ?>"><?php _e( "Add this code to your theme's <code>functions.php</code> file:", 'my-calendar' ); ?>
<pre>
function mc_remote_db() {
	$mcdb = new wpdb('DB_USER','DB_PASSWORD','DB_NAME','DB_ADDRESS');

	return $mcdb;
}
</pre>
									<?php _e( 'You will need to allow remote connections from this site to the site hosting your My Calendar events. Replace the above placeholders with the host-site information. The two sites must have the same WP table prefix. While this option is enabled, you may not enter or edit events through this installation.', 'my-calendar' ); ?>
								</li>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'  => 'mc_api_enabled',
										'label' => __( 'Enable events API', 'my-calendar' ),
										'type'  => 'checkbox-single',
									)
								);
								if ( 'true' === mc_get_option( 'api_enabled' ) ) {
									$url = add_query_arg(
										array(
											'to'     => current_time( 'Y-m-d' ),
											'from'   => mc_date( 'Y-m-d', time() - MONTH_IN_SECONDS ),
											'mc-api' => 'json',
										),
										home_url()
									);
									// Translators: Linked URL to API endpoint.
									printf( ' <code>' . __( 'API URL: %s', 'my-calendar' ) . '</code>', '<a href="' . esc_html( $url ) . '">' . esc_url( $url ) . '</a>' );
								}
								?>
								</li>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'  => 'mc_drop_tables',
										'label' => __( 'Drop database tables on uninstall', 'my-calendar' ),
										'type'  => 'checkbox-single',
									)
								);
								?>
								</li>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'    => 'mc_drop_settings',
										'label'   => __( 'Delete plugin settings on uninstall', 'my-calendar' ),
										'default' => 'true',
										'type'    => 'checkbox-single',
									)
								);
								?>
								</li>
							</ul>
						</fieldset>
						<p>
							<input type="submit" name="mc_manage" class="button-primary" value="<?php _e( 'Save Management Settings', 'my-calendar' ); ?>"/>
						</p>
					</form>
					<div class="mc-extended-settings">
						<h3><?php _e( 'Import and Export Settings', 'my-calendar' ); ?></h3>
						<p><a href="<?php echo mc_export_settings_url(); ?>"><?php _e( 'Export settings', 'my-calendar' ); ?></a></p>
						<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-config#my-calendar-manage' ) ); ?>">
							<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
							<p class="mc-input-settings">
								<label for="mc-import-settings"><?php _e( 'Import Settings', 'my-calendar' ); ?></label>
								<input type="file" name="mc-import-settings" id="mc-import-settings" accept="application/json" /> 
								<input type="submit" class="button-secondary" value="<?php _e( 'Import Settings', 'my-calendar' ); ?>">	
							</p>
						</form>
						<h3><?php esc_html_e( 'Settings on other screens', 'my-calendar' ); ?></h3>
						<?php
							$current_location_slug = ( '' === mc_get_option( 'location_cpt_base' ) ) ? __( 'mc-locations', 'my-calendar' ) : mc_get_option( 'location_cpt_base' );
							$current_event_slug    = ( '' === mc_get_option( 'cpt_base' ) ) ? __( 'mc-events', 'my-calendar' ) : mc_get_option( 'cpt_base' );
						?>
						<ul>
							<li><?php esc_html_e( 'Settings > Permalinks', 'my-calendar' ); ?>: <a aria-describedby='mc-current-events-slug' href="<?php echo esc_url( admin_url( 'options-permalink.php#mc_cpt_base' ) ); ?>"><?php esc_html_e( 'Events permalink slug', 'my-calendar' ); ?></a> <span id="mc-current-events-slug">(<?php echo $current_event_slug; ?>)</span></li>
							<li><?php esc_html_e( 'Settings > Permalinks', 'my-calendar' ); ?>: <a aria-describedby='mc-current-location-slug' href="<?php echo esc_url( admin_url( 'options-permalink.php#mc_location_cpt_base' ) ); ?>"><?php esc_html_e( 'Location permalink slug', 'my-calendar' ); ?></a> <span id="mc-current-location-slug">(<?php echo $current_location_slug; ?>)</span></li>
							<li><?php esc_html_e( 'Settings > General', 'my-calendar' ); ?>: <a href="<?php echo esc_url( admin_url( 'options-general.php#start_of_week' ) ); ?>"><?php esc_html_e( 'First day of the week', 'my-calendar' ); ?></a></li>
						</ul>
					</div>
					<?php
				} else {
					_e( 'My Calendar management settings are only available to administrators.', 'my-calendar' );
				}
				?>
			</div>
		</div>

		<div class="wptab postbox initial-hidden" aria-labelledby="tab_text" role="tabpanel" id="my-calendar-text">
			<h2><?php esc_html_e( 'Text Settings', 'my-calendar' ); ?></h2>

			<div class="inside">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-config#my-calendar-text' ) ); ?>">
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
					<fieldset>
						<legend><?php esc_html_e( 'Main Calendar View', 'my-calendar' ); ?></legend>
						<ul>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_previous_events',
									'label' => __( 'Previous events link', 'my-calendar' ),
									'note'  => __( 'Use <code>{date}</code> to display date in navigation.', 'my-calendar' ),
									'atts'  => array(
										'placeholder' => __( 'Previous', 'my-calendar' ),
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_next_events',
									'label' => __( 'Next events link', 'my-calendar' ),
									'note'  => __( 'Use <code>{date}</code> to display date in navigation.', 'my-calendar' ),
									'atts'  => array(
										'placeholder' => __( 'Next', 'my-calendar' ),
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_today_events',
									'label' => __( 'Today\'s events link', 'my-calendar' ),
									'atts'  => array(
										'placeholder' => __( 'Today', 'my-calendar' ),
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_week_caption',
									'label' => __( 'Week view caption:', 'my-calendar' ),
									'note'  => __( 'Available tag: <code>{date format=""}</code>', 'my-calendar' ),
									'atts'  => array(
										// Translators: date template tag.
										'placeholder' => sprintf( __( 'Week of %s', 'my-calendar' ), '{date format="M jS"}' ),
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_heading_text',
									'label' => __( 'Calendar month heading', 'my-calendar' ),
									'note'  => __( 'Use <code>{date}</code> to display month/year in heading.', 'my-calendar' ),
									'atts'  => array(
										'placeholder' => 'Events in {date}',
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_caption',
									'label' => __( 'Extended caption:', 'my-calendar' ),
									'note'  => __( 'Follows month/year in calendar heading.', 'my-calendar' ),
								)
							);
							?>
							</li>
						</ul>
					</fieldset>
					<fieldset>
						<legend><?php esc_html_e( 'Single Event View', 'my-calendar' ); ?></legend>
						<ul>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_notime_text',
									'label' => __( 'Label for all-day events', 'my-calendar' ),
									'atts'  => array(
										'placeholder' => __( 'All Day', 'my-calendar' ),
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_hosted_by',
									'label' => __( 'Hosted by', 'my-calendar' ),
									'atts'  => array(
										'placeholder' => __( 'Hosted by', 'my-calendar' ),
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_posted_by',
									'label' => __( 'Posted by', 'my-calendar' ),
									'atts'  => array(
										'placeholder' => __( 'Posted by', 'my-calendar' ),
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_buy_tickets',
									'label' => __( 'Buy tickets', 'my-calendar' ),
									'atts'  => array(
										'placeholder' => __( 'Buy Tickets', 'my-calendar' ),
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_event_accessibility',
									'label' => __( 'Event Accessibility Heading', 'my-calendar' ),
									'atts'  => array(
										'placeholder' => __( 'Event Accessibility', 'my-calendar' ),
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_view_full',
									'label' => __( 'View full calendar', 'my-calendar' ),
									'atts'  => array(
										'placeholder' => __( 'View full calendar', 'my-calendar' ),
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'    => 'mc_details_label',
									'label'   => __( 'Read more text', 'my-calendar' ),
									'default' => $mc_details_label,
									'atts'    => array(
										'placeholder' => __( 'Details about', 'my-calendar' ) . ' {title}',
									),
									'note'    => __( 'Tags: <code>{title}</code>, <code>{location}</code>, <code>{color}</code>, <code>{icon}</code>, <code>{date}</code>, <code>{time}</code>.', 'my-calendar' ),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'    => 'mc_link_label',
									'label'   => __( 'More information text', 'my-calendar' ),
									'default' => $mc_link_label,
									'atts'    => array(
										'placeholder' => __( 'More information', 'my-calendar' ),
									),
									'note'    => "<a href='" . admin_url( 'admin.php?page=my-calendar-design#my-calendar-templates' ) . "'>" . __( 'Templating Help', 'my-calendar' ) . '</a>',
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_event_title_template',
									'label' => __( 'Browser tab title element template', 'my-calendar' ),
									'atts'  => array(
										'placeholder' => '{title} &raquo; {date}',
									),
									// Translators: Current title template (code).
									'note'  => __( 'Current: %s', 'my-calendar' ),
								)
							);
							?>
							</li>
						</ul>
					</fieldset>
					<fieldset>
						<legend><?php esc_html_e( 'Date/Time Formats', 'my-calendar' ); ?></legend>
						<div><input type='hidden' name='mc_dates' value='true'/></div>
						<ul>
							<?php
							$month_format = ( '' === mc_get_option( 'month_format' ) ) ? date_i18n( 'F Y' ) : date_i18n( mc_get_option( 'month_format' ) );
							$time_format  = date_i18n( mc_time_format() );
							$week_format  = ( '' === mc_get_option( 'week_format' ) ) ? date_i18n( 'M j, \'y' ) : date_i18n( mc_get_option( 'week_format' ) );
							$date_format  = ( '' === mc_get_option( 'date_format' ) ) ? date_i18n( get_option( 'date_format' ) ) : date_i18n( mc_get_option( 'date_format' ) );
							?>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_date_format',
									'label' => __( 'Primary Date Format', 'my-calendar' ),
									'note'  => $date_format,
									'atts'  => array(
										'placeholder' => get_option( 'date_format' ),
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_time_format',
									'label' => __( 'Time Format', 'my-calendar' ),
									'note'  => $time_format,
									'atts'  => array(
										'placeholder' => get_option( 'time_format' ),
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_month_format',
									'label' => __( 'Month Format (calendar headings)', 'my-calendar' ),
									'note'  => $month_format,
									'atts'  => array(
										'placeholder' => 'F Y',
									),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_week_format',
									'label' => __( 'Date in grid mode, week view', 'my-calendar' ),
									'note'  => $week_format,
									'atts'  => array(
										'placeholder' => "M j, 'y",
									),
								)
							);
							?>
							</li>
						</ul>
					</fieldset>
					<p>
						<input type="submit" name="save" class="button-primary" value="<?php _e( 'Save Custom Text', 'my-calendar' ); ?>"/>
					</p>
				</form>
				<p>
				<?php _e( 'Date formats use syntax from the <a href="https://www.php.net/manual/en/datetime.format.php#refsect1-datetime.format-parameters">PHP <code>date()</code> function</a>. Save to update sample output.', 'my-calendar' ); ?>
				</p>
			</div>
		</div>
		<div class="wptab initial-hidden" aria-labelledby="tab_output" role="tabpanel" id="mc-output">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-config#mc-output' ) ); ?>">
				<div class="postbox">
					<h2><?php esc_html_e( 'Display Settings', 'my-calendar' ); ?></h2>

					<div class="inside">
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
						<input type="submit" name="save" class="button screen-reader-text" value="<?php _e( 'Save Display Settings', 'my-calendar' ); ?>" /></p>
						<fieldset>
							<legend><?php esc_html_e( 'Enabled Views', 'my-calendar' ); ?></legend>
							<ul class="checkboxes">
								<?php
								$default_views = array(
									'calendar' => __( 'Grid', 'my-calendar' ),
									'card'     => __( 'Card', 'my-calendar' ),
									'list'     => __( 'List', 'my-calendar' ),
									'mini'     => __( 'Mini', 'my-calendar' ),
								);
								mc_settings_field(
									array(
										'name'    => 'mc_views',
										'label'   => $default_views,
										'default' => array( 'calendar', 'list', 'mini' ),
										'type'    => 'checkbox',
									)
								);
								?>
							</ul>
						</fieldset>
						<fieldset>
							<legend><?php esc_html_e( 'Title Templates', 'my-calendar' ); ?></legend>
							<ul class="my-calendar-text">
								<li>
								<?php
								mc_settings_field(
									array(
										'name'    => 'mc_title_template',
										'label'   => __( 'Event title (Grid)', 'my-calendar' ),
										'default' => $mc_title_template,
										'atts'    => array(
											'placeholder' => '{title}',
										),
										'note'    => "<a href='" . admin_url( 'admin.php?page=my-calendar-design#my-calendar-templates' ) . "'>" . __( 'Templating Help', 'my-calendar' ) . '</a>',
									)
								);
								?>
								</li>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'    => 'mc_title_template_list',
										'label'   => __( 'Event title (List)', 'my-calendar' ),
										'default' => $mc_title_template_list,
										'atts'    => array(
											'placeholder' => '{title}',
										),
										'note'    => "<a href='" . admin_url( 'admin.php?page=my-calendar-design#my-calendar-templates' ) . "'>" . __( 'Templating Help', 'my-calendar' ) . '</a>',
									)
								);
								?>
								</li>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'    => 'mc_title_template_card',
										'label'   => __( 'Event title (Card)', 'my-calendar' ),
										'default' => $mc_title_template_card,
										'atts'    => array(
											'placeholder' => '{title}',
										),
										'note'    => "<a href='" . admin_url( 'admin.php?page=my-calendar-design#my-calendar-templates' ) . "'>" . __( 'Templating Help', 'my-calendar' ) . '</a>',
									)
								);
								?>
								</li>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'    => 'mc_title_template_solo',
										'label'   => __( 'Event title (Single)', 'my-calendar' ),
										'default' => $mc_title_template_solo,
										'atts'    => array(
											'placeholder' => '{title}',
										),
										'note'    => "<a href='" . admin_url( 'admin.php?page=my-calendar-design#my-calendar-templates' ) . "'>" . __( 'Templating Help', 'my-calendar' ) . '</a>',
									)
								);
								?>
								</li>
							</ul>
						</fieldset>
						<fieldset id='calendar-output' class='mc-output-tabs'>
							<legend><?php esc_html_e( 'Event Display Fields', 'my-calendar' ); ?></legend>
							<p>
							<?php
								mc_settings_field(
									array(
										'name'  => 'mc_disable_legacy_templates',
										'label' => __( 'Enable PHP templating', 'my-calendar' ),
										'type'  => 'checkbox-single',
										// Translators: link to documentation on PHP templates.
										'note'  => sprintf( __( 'PHP templates will replace any custom templates already in use. <a href="%s">See documentation.</a>', 'my-calendar' ), 'https://docs.joedolson.com/my-calendar/php-templates/' ),
									)
								);
							?>
							</p>
							<div class="mc-tabs">
								<div class="tabs" role="tablist" data-default="single-event-output">
									<button type="button" role="tab" aria-selected="false" id="tab_single_output" aria-controls="single-event-output"><?php esc_html_e( 'Single Event', 'my-calendar' ); ?></button>
									<button type="button" role="tab" aria-selected="false" id="tab_card_output" aria-controls="calendar-main-output"><?php esc_html_e( 'Card', 'my-calendar' ); ?></button>
									<button type="button" role="tab" aria-selected="false" id="tab_main_output" aria-controls="calendar-main-output"><?php esc_html_e( 'Single Event Popup', 'my-calendar' ); ?></button>
									<button type="button" role="tab" aria-selected="false" id="tab_mini_output" aria-controls="mini-calendar-popup"><?php esc_html_e( 'Mini Calendar Popup', 'my-calendar' ); ?></button>
								</div>
								<div role='tabpanel' aria-labelledby='tab_single_output' class='wptab' id='single-event-output'>
									<p>
									<?php
									_e( 'Choose fields to show in the single event view.', 'my-calendar' );
									echo ' ';
									// Translators: URL to single event view template editing screen.
									printf( __( 'The <a href="%s">single event view template</a> overrides these settings.', 'my-calendar' ), esc_url( admin_url( 'admin.php?page=my-calendar-design&mc_template=details#my-calendar-templates' ) ) );
									?>
									</p>
									<ul class="checkboxes">
									<?php
									$default_display_fields = array(
										'author'      => __( 'Author', 'my-calendar' ),
										'host'        => __( 'Host', 'my-calendar' ),
										'ical'        => __( 'iCal Download', 'my-calendar' ),
										'gcal'        => __( 'Share to Google Calendar', 'my-calendar' ),
										'gmap'        => __( 'Display Map', 'my-calendar' ),
										'gmap_link'   => __( 'Link to Map', 'my-calendar' ),
										'address'     => __( 'Location Address', 'my-calendar' ),
										'excerpt'     => __( 'Excerpt', 'my-calendar' ),
										'description' => __( 'Description', 'my-calendar' ),
										'image'       => __( 'Featured Image', 'my-calendar' ),
										'tickets'     => __( 'Registration Settings', 'my-calendar' ),
										'link'        => __( 'More Information', 'my-calendar' ),
										'more'        => __( 'Read More Link', 'my-calendar' ),
										'access'      => __( 'Accessibility', 'my-calendar' ),
									);
									mc_settings_field(
										array(
											'name'    => 'mc_display_single',
											'label'   => $default_display_fields,
											'default' => array( 'author', 'ical', 'address', 'gcal', 'description', 'image', 'tickets', 'access', 'link', 'gmap_link' ),
											'type'    => 'checkbox',
										)
									);
									unset( $default_display_fields['gmap'] );
									?>
									</ul>
									<div class="mc-input-with-note">
										<?php
										mc_settings_field(
											array(
												'name'  => 'mc_gmap_api_key',
												'label' => __( 'Google Maps API Key', 'my-calendar' ),
												'note'  => '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key">' . __( 'Create your Google Maps API key', 'my-calendar' ) . '</a>',
												'atts'  => array(
													'id' => 'mc_gmap_id',
												),
												'wrap'  => array(
													'element' => 'p',
													'class'   => 'mc_gmap_api_key',
												),
											)
										);
										?>
									</div>
								</div>
								<div role='tabpanel' aria-labelledby='tab_card_output' class='wptab' id='calendar-card-output'>
									<p>
									<?php
									_e( 'Choose fields to show in the card view.', 'my-calendar' );
									echo ' ';
									// Translators: URL to single event view template editing screen.
									printf( __( 'The <a href="%1$s">card view template</a> overrides these settings.', 'my-calendar' ), esc_url( admin_url( 'admin.php?page=my-calendar-design&mc_template=card#my-calendar-templates' ) ) );
									?>
									</p>
									<ul class="checkboxes">
									<?php
									mc_settings_field(
										array(
											'name'    => 'mc_display_card',
											'label'   => $default_display_fields,
											'default' => array( 'address', 'excerpt', 'image', 'tickets', 'access', 'gmap_link', 'more' ),
											'type'    => 'checkbox',
										)
									);
									?>
									</ul>
								</div>
								<div role='tabpanel' aria-labelledby='tab_main_output' class='wptab' id='calendar-main-output'>
									<p>
									<?php
									_e( 'Choose fields to show in the calendar popup and expanded list views.', 'my-calendar' );
									echo ' ';
									// Translators: URL to single event view template editing screen.
									printf( __( 'The <a href="%1$s">grid view template</a> overrides these settings for the calendar popup, and the <a href="%2$s">list view template</a> overrides these settings in list view.', 'my-calendar' ), esc_url( admin_url( 'admin.php?page=my-calendar-design&mc_template=grid#my-calendar-templates' ) ), esc_url( admin_url( 'admin.php?page=my-calendar-design&mc_template=list#my-calendar-templates' ) ) );
									?>
									</p>
									<ul class="checkboxes">
									<?php
									mc_settings_field(
										array(
											'name'    => 'mc_display_main',
											'label'   => $default_display_fields,
											'default' => array( 'address', 'excerpt', 'image', 'tickets', 'access', 'gmap_link', 'more' ),
											'type'    => 'checkbox',
										)
									);
									?>
									</ul>
								</div>
								<div role='tabpanel' aria-labelledby='tab_mini_output' class='wptab' id='mini-calendar-popup'>
									<p>
									<?php
									_e( 'Choose fields to show in the mini calendar popup.', 'my-calendar' );
									echo ' ';
									// Translators: URL to single event view template editing screen.
									printf( __( 'The <a href="%s">mini view template</a> overrides these settings.', 'my-calendar' ), esc_url( admin_url( 'admin.php?page=my-calendar-design&mc_template=mini#my-calendar-templates' ) ) );
									?>
									</p>
									<ul class="checkboxes">
									<?php
									mc_settings_field(
										array(
											'name'    => 'mc_display_mini',
											'label'   => $default_display_fields,
											'default' => array( 'excerpt', 'image', 'more' ),
											'type'    => 'checkbox',
										)
									);
									?>
									</ul>
								</div>
							</div>
						</fieldset>
					</div>
				</div>
				<div class="postbox">
					<h2><?php esc_html_e( 'Calendar Navigation', 'my-calendar' ); ?></h2>
					<div class="inside">
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Update calendar layout', 'my-calendar' ); ?></legend>
							<?php
							$topnav       = explode( ',', mc_get_option( 'topnav' ) );
							$calendar     = array( 'calendar' );
							$botnav       = explode( ',', mc_get_option( 'bottomnav' ) );
							$order        = array_merge( $topnav, $calendar, $botnav );
							$nav_elements = mc_navigation_keywords();
							?>
							<div class='mc-sortable-update' aria-live='assertive'></div>
							<ul class='mc-sortable' id="mc-sortable-nav">
							<?php
							$inserted = array();
							$class    = 'visible';
							$i        = 1;
							foreach ( $order as $k ) {
								$k = trim( $k );
								$v = ( isset( $nav_elements[ $k ] ) ) ? $nav_elements[ $k ] : false;
								if ( false !== $v ) {
									$inserted[ $k ] = $v;
									$label          = $k;
									// Translators: control to move down.
									$down_label = sprintf( __( 'Move %s Down', 'my-calendar' ), $label );
									// Translators: control to move up.
									$up_label = sprintf( __( 'Move %s Up', 'my-calendar' ), $label );
									// Translators: control to hide.
									$hide_label = sprintf( __( 'Hide %s', 'my-calendar' ), $label );
									$hide       = ( 'calendar' === $k ) ? '' : "<button class='hide' type='button'><span class='screen-reader-text'>" . $hide_label . "</span><i class='dashicons dashicons-visibility' aria-hidden='true'></i></button>";
									$buttons    = "<button class='up' type='button'><i class='dashicons dashicons-arrow-up' aria-hidden='true'></i><span class='screen-reader-text'>" . $up_label . "</span></button> <button class='down' type='button'><i class='dashicons dashicons-arrow-down' aria-hidden='true'></i><span class='screen-reader-text'>" . $down_label . '</span></button> ' . $hide;
									$buttons    = "<div class='mc-buttons'>$buttons</div>";
									echo wp_kses( "<li class='ui-state-default mc-$k mc-$class'>$buttons <code>$label</code> $v <input type='hidden' name='mc_nav[]' value='$k' /></li>", mc_kses_elements() );
									++$i;
								}
							}
							$missed = array_diff( $nav_elements, $inserted );
							$i      = 1;
							foreach ( $missed as $k => $v ) {
								// Translators: control to move down.
								$down_label = sprintf( __( 'Move %s Down', 'my-calendar' ), $k );
								// Translators: control to move up.
								$up_label = sprintf( __( 'Move %s Up', 'my-calendar' ), $k );
								// Translators: control to hide.
								$hide_label = sprintf( __( 'Show %s', 'my-calendar' ), $k );
								$buttons    = "<button class='up' type='button'><i class='dashicons dashicons-arrow-up' aria-hidden='true'></i><span class='screen-reader-text'>" . $up_label . "</span></button> <button class='down' type='button'><i class='dashicons dashicons-arrow-down' aria-hidden='true'></i><span class='screen-reader-text'>" . $down_label . "</span></button> <button class='hide' type='button'><i class='dashicons dashicons-hidden' aria-hidden='true'></i><span class='screen-reader-text'>" . $hide_label . '</span></button>';
								$buttons    = "<div class='mc-buttons'>$buttons</div>";
								echo wp_kses( "<li class='ui-state-default mc-$k mc-hidden'>$buttons <code>$k</code> $v <input type='hidden' name='mc_nav[]' value='$k' disabled /></li>", mc_kses_elements() );
								++$i;
							}
							?>
							</ul>
						</fieldset>
					</div>
				</div>
				<div class="postbox">
					<h2><?php esc_html_e( 'View Options', 'my-calendar' ); ?></h2>

					<div class="inside">
						<ul>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_show_months',
									'label' => __( 'How many months of events to show at a time:', 'my-calendar' ),
									'atts'  => array(
										'size' => '3',
									),
									'type'  => 'text',
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'    => 'mc_map_service',
									'label'   => __( 'Mapping service', 'my-calendar' ),
									'default' => array(
										'bing'   => __( 'Bing Maps', 'my-calendar' ),
										'google' => __( 'Google Maps', 'my-calendar' ),
										'none'   => __( 'None', 'my-calendar' ),
									),
									'note'    => __( 'Map setting currently only supports map links; embedded maps are still only supported for Google Maps.', 'my-calendar' ),
									'type'    => 'select',
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'    => 'mc_maptype',
									'label'   => __( 'Default map display', 'my-calendar' ),
									'default' => array(
										'roadmap'   => __( 'Road map', 'my-calendar' ),
										'satellite' => __( 'Satellite', 'my-calendar' ),
										'hybrid'    => __( 'Hybrid (Satellite/Road)', 'my-calendar' ),
										'terrain'   => __( 'Terrain', 'my-calendar' ),
									),
									'note'    => __( 'Map setting currently only supports map links; embedded maps are still only supported for Google Maps.', 'my-calendar' ),
									'type'    => 'select',
								)
							);
							?>
							</li>
						</ul>				
						<fieldset>
							<legend><?php esc_html_e( 'Grid Options', 'my-calendar' ); ?></legend>
							<ul>
								<li>
								<?php
								$atts = array();
								$note = '';
								if ( '' === mc_get_option( 'uri_id' ) || '0' === mc_get_option( 'uri_id' ) ) {
									$atts = array( 'disabled' => 'disabled' );
									$note = ' (' . __( 'Set a main calendar page first.', 'my-calendar' ) . ')';
								}
								mc_settings_field(
									array(
										'name'    => 'mc_open_uri',
										'label'   => __( 'Calendar Links', 'my-calendar' ),
										'default' => array(
											'false' => __( 'Open links as a popup', 'my-calendar' ),
											'true'  => __( 'Open event links in single event view', 'my-calendar' ),
											'none'  => __( 'Disable event links', 'my-calendar' ),
										),
										'note'    => $note,
										'atts'    => $atts,
										'type'    => 'select',
									)
								);
								?>
								</li>								
								<li>
								<?php
								mc_settings_field(
									array(
										'name'    => 'mc_convert',
										'label'   => __( 'Mobile View', 'my-calendar' ),
										'default' => array(
											'true' => __( 'Switch to list view', 'my-calendar' ),
											'mini' => __( 'Switch to mini calendar', 'my-calendar' ),
											'none' => __( 'No change', 'my-calendar' ),
										),
										'type'    => 'select',
									)
								);
								?>
								</li>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'  => 'mc_show_weekends',
										'label' => __( 'Show Weekends on Calendar', 'my-calendar' ),
										'type'  => 'checkbox-single',
									)
								);
								?>
								</li>
							</ul>
						</fieldset>
						<fieldset>
							<legend><?php esc_html_e( 'List Options', 'my-calendar' ); ?></legend>
							<ul>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'  => 'mc_show_list_info',
										'label' => __( 'Show the first event\'s title and the number of events that day next to the date.', 'my-calendar' ),
										'type'  => 'checkbox-single',
									)
								);
								?>
								</li>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'  => 'mc_list_link_titles',
										'label' => __( 'Show events in list view.', 'my-calendar' ),
										'type'  => 'checkbox-single',
									)
								);
								?>
								</li>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'  => 'mc_hide_past_dates',
										'label' => __( 'Hide past dates in initial list view.', 'my-calendar' ),
										'type'  => 'checkbox-single',
									)
								);
								?>
								</li>
							</ul>
						</fieldset>
						<fieldset>
							<legend><?php esc_html_e( 'Mini Calendar Options', 'my-calendar' ); ?></legend>
							<ul>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'  => 'mc_mini_uri',
										'label' => __( 'Target link for mini calendar dates', 'my-calendar' ),
										'atts'  => array(
											'size' => '60',
										),
										'type'  => 'url',
									)
								);
								?>
								</li>
								<?php
								$disabled = ( ! mc_get_option( 'uri_id' ) && ! mc_get_option( 'mini_uri' ) ) ? array( 'disabled' => 'disabled' ) : array();
								if ( ! empty( $disabled ) ) {
									// Ensure that this option is set to a valid value if no URI configured.
									mc_update_option( 'open_day_uri', 'false' );
								}
								?>
								<li>
								<?php
								$open_day_options = array(
									'false'          => __( 'Event popup ', 'my-calendar' ),
									'true'           => __( 'daily view page (above)', 'my-calendar' ),
									'current'        => __( 'current page (if singular)', 'my-calendar' ),
									'listanchor'     => __( 'in-page anchor on main calendar page (list)', 'my-calendar' ),
									'calendaranchor' => __( 'in-page anchor on main calendar page (grid)', 'my-calendar' ),
								);
								mc_settings_field(
									array(
										'name'    => 'mc_open_day_uri',
										'label'   => __( 'Link action for mini calendar', 'my-calendar' ),
										'default' => $open_day_options,
										'atts'    => $disabled,
										'type'    => 'select',
									)
								);
								?>
								</li>
							</ul>
						</fieldset>

						<p><input type="submit" name="save" class="button-primary" value="<?php _e( 'Save Display Settings', 'my-calendar' ); ?>"/></p>
					</div>
				</div>
			</form>
		</div>
		<div class="wptab initial-hidden" aria-labelledby="tab_input" role="tabpanel" id="my-calendar-input">
			<div class="postbox">
				<h2><?php esc_html_e( 'Calendar Input Fields', 'my-calendar' ); ?></h2>

				<div class="inside">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-config#my-calendar-input' ) ); ?>">
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
						<fieldset>
							<legend><?php esc_html_e( 'Event editing fields to show', 'my-calendar' ); ?></legend>
							<div><input type='hidden' name='mc_input' value='true'/></div>
							<ul class="checkboxes">
								<?php
								$output        = '';
								$input_options = mc_get_option( 'input_options' );
								$input_labels  = array(
									'event_short'    => __( 'Excerpt', 'my-calendar' ),
									'event_desc'     => __( 'Description', 'my-calendar' ),
									'event_category' => __( 'Categories', 'my-calendar' ),
									'event_image'    => __( 'Featured Image', 'my-calendar' ),
									'event_link'     => __( 'More Information', 'my-calendar' ),
									'event_recurs'   => __( 'Repetition Pattern', 'my-calendar' ),
									'event_open'     => __( 'Registration Settings', 'my-calendar' ),
									'event_location' => __( 'Event Location', 'my-calendar' ),
									'event_access'   => __( 'Accessibility', 'my-calendar' ),
									'event_host'     => __( 'Host', 'my-calendar' ),
								);

								// If input options isn't an array, assume that plugin wasn't upgraded, and reset to default.
								// Array of all options in default position.
								$defaults = mc_input_defaults();
								if ( ! is_array( $input_options ) ) {
									mc_update_option(
										'input_options',
										$defaults
									);
									$input_options = mc_get_option( 'input_options' );
								}
								// Merge saved input options with default on, so all are displayed.
								$input_options = array_merge( $defaults, $input_options );
								asort( $input_labels );
								foreach ( $input_labels as $key => $value ) {
									$enabled = ( isset( $input_options[ $key ] ) ) ? $input_options[ $key ] : false;
									$checked = ( 'on' === $enabled ) ? "checked='checked'" : '';
									$output .= "<li><input type='checkbox' id='mci_$key' name='mci_$key' $checked /> <label for='mci_$key'>$value</label></li>";
								}
								echo wp_kses( $output, mc_kses_elements() );
								?>
								<li>
								<?php
								mc_settings_field(
									array(
										'name'  => 'mc_input_options_administrators',
										'label' => __( 'Administrators see all input options', 'my-calendar' ),
										'type'  => 'checkbox-single',
									)
								);
								?>
								</li>
							</ul>
						</fieldset>
						<p>
							<input type="submit" name="save" class="button-primary" value="<?php _e( 'Save Input Settings', 'my-calendar' ); ?>"/>
						</p>
					</form>
				</div>
			</div>
			<div class="postbox">
				<h2><?php esc_html_e( 'Location Controls', 'my-calendar' ); ?></h2>

				<div class="inside">
					<?php echo wp_kses( mc_location_controls(), mc_kses_elements() ); ?>
				</div>
			</div>
		</div>

	<?php
	if ( current_user_can( 'manage_network' ) && is_multisite() ) {
		?>
		<div class="wptab postbox initial-hidden" aria-labelledby="tab_multi" role="tabpanel" id="my-calendar-multisite">
			<h2><?php esc_html_e( 'Multisite Settings (Network Administrators only)', 'my-calendar' ); ?></h2>

			<div class="inside">
				<p><?php esc_html_e( 'The central calendar is the calendar associated with the primary site in your WordPress Multisite network.', 'my-calendar' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-config#my-calendar-multisite' ) ); ?>">
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
					<input type='hidden' name='mc_network' value='true'/>
					<fieldset>
						<legend><?php esc_html_e( 'Multisite configuration - input', 'my-calendar' ); ?></legend>
						<ul>
							<li>
								<input type="radio" value="0" id="ms0" name="mc_multisite"<?php checked( get_site_option( 'mc_multisite' ), '0' ); ?> /> <label for="ms0"><?php esc_html_e( 'Site owners may only post to their local calendar.', 'my-calendar' ); ?></label>
							</li>
							<li>
								<input type="radio" value="1" id="ms1" name="mc_multisite"<?php checked( get_site_option( 'mc_multisite' ), '1' ); ?> /> <label for="ms1"><?php esc_html_e( 'Site owners may only post to the central calendar.', 'my-calendar' ); ?></label>
							</li>
							<li>
								<input type="radio" value="2" id="ms2" name="mc_multisite"<?php checked( get_site_option( 'mc_multisite' ), 2 ); ?> /> <label for="ms2"><?php esc_html_e( 'Site owners may manage either calendar', 'my-calendar' ); ?></label>
							</li>
						</ul>
						<p>
							<em><?php esc_html_e( 'Changes only effect input permissions. Public-facing calendars will be unchanged.', 'my-calendar' ); ?></em>
						</p>
					</fieldset>
					<fieldset>
						<legend><?php esc_html_e( 'Multisite configuration - output', 'my-calendar' ); ?></legend>
						<ul>
							<li>
								<input type="radio" value="0" id="mss0" name="mc_multisite_show"<?php checked( get_site_option( 'mc_multisite_show' ), '0' ); ?> />
								<label for="mss0"><?php esc_html_e( 'Sub-site calendars show events from their local calendar.', 'my-calendar' ); ?></label>
							</li>
							<li>
								<input type="radio" value="1" id="mss1" name="mc_multisite_show"<?php checked( get_site_option( 'mc_multisite_show' ), '1' ); ?> />
								<label for="mss1"><?php esc_html_e( 'Sub-site calendars show events from the central calendar.', 'my-calendar' ); ?></label>
							</li>
						</ul>
					</fieldset>
					<p>
						<input type="submit" name="save" class="button-primary" value="<?php _e( 'Save Multisite Settings', 'my-calendar' ); ?>"/>
					</p>
				</form>
			</div>
		</div>
		<?php
	}
	?>

		<div class="wptab postbox initial-hidden" aria-labelledby="tab_permissions" role="tabpanel" id="my-calendar-permissions">
			<h2><?php esc_html_e( 'My Calendar Permissions', 'my-calendar' ); ?></h2>

			<div class="inside">
	<?php
	if ( current_user_can( 'administrator' ) ) {
		?>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-config#my-calendar-permissions' ) ); ?>">
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
						<div class="mc-permissions-wrapper">
		<?php
		$tabs = '<div class="tabs" role="tablist" data-default="container_mc_editor">';
		global $wp_roles;
		$role_container = '';
		$roles          = $wp_roles->get_names();
		$caps           = mc_get_user_capabilities();

		foreach ( $roles as $role => $rolename ) {
			if ( 'administrator' === $role ) {
				continue;
			}
			$tabs           .= "<button type='button' role='tab' aria-selected='false' id='tab_$role' aria-controls='container_mc_$role'>" . $rolename . '</button>' . PHP_EOL;
			$role_container .= "<div role='tabpanel' aria-labelledby='tab_$role' class='wptab mc_$role mc_permissions' id='container_mc_$role'>" . PHP_EOL . "<fieldset id='mc_$role' class='roles'><legend>$rolename</legend>" . PHP_EOL;
			$role_container .= "<input type='hidden' value='none' name='mc_caps[" . $role . "][none]' /><ul class='mc-settings checkboxes'>";
			foreach ( $caps as $cap => $name ) {
				$role_container .= mc_cap_checkbox( $role, $cap, $name );
			}
			$role_container .= '</ul></fieldset></div>' . PHP_EOL;
		}
		$tabs .= '</div>';
		echo '<div class="mc-tabs vertical">';
		echo wp_kses( $tabs . $role_container, mc_kses_elements() );
		echo '</div>';
		?>
						</div>
						<p>
							<input type="submit" name="mc_permissions" class="button-primary" value="<?php _e( 'Save Permissions', 'my-calendar' ); ?>"/>
						</p>
					</form>
		<?php
	} else {
		_e( 'My Calendar permission settings are only available to administrators.', 'my-calendar' );
	}
	?>
			</div>
		</div>

		<div class="wptab postbox initial-hidden" aria-labelledby="tab_email" role="tabpanel" id="my-calendar-email">
			<h2><?php esc_html_e( 'Calendar Email Settings', 'my-calendar' ); ?></h2>

			<div class="inside">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-config#my-calendar-email' ) ); ?>">
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
					<fieldset>
						<legend><?php esc_html_e( 'Email Notifications', 'my-calendar' ); ?></legend>
						<div><input type='hidden' name='mc_email' value='true'/></div>
						<ul>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_event_mail',
									'label' => __( 'Send Email Notifications when new events are added.', 'my-calendar' ),
									'type'  => 'checkbox-single',
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_html_email',
									'label' => __( 'Send HTML email', 'my-calendar' ),
									'type'  => 'checkbox-single',
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'    => 'mc_event_mail_to',
									'label'   => __( 'Notification messages are sent to:', 'my-calendar' ),
									'default' => get_bloginfo( 'admin_email' ),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'    => 'mc_event_mail_from',
									'label'   => __( 'Notification messages are sent from:', 'my-calendar' ),
									'default' => get_bloginfo( 'admin_email' ),
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'  => 'mc_event_mail_bcc',
									'label' => __( 'BCC on notifications (one per line):', 'my-calendar' ),
									'atts'  => array(
										'cols' => 60,
										'rows' => 6,
									),
									'type'  => 'textarea',
								)
							);
							?>
							</li>
							<li>
							<?php
							mc_settings_field(
								array(
									'name'    => 'mc_event_mail_subject',
									'label'   => __( 'Email subject', 'my-calendar' ),
									'default' => get_bloginfo( 'name' ) . ': ' . __( 'New event added', 'my-calendar' ),
									'atts'    => array(
										'size' => 60,
									),
								)
							);
							?>
							</li>
							<li>
	<?php
	mc_settings_field(
		array(
			'name'    => 'mc_event_mail_message',
			'label'   => __( 'Message Body', 'my-calendar' ),
			'default' => __( 'New Event:', 'my-calendar' ) . "\n\n{title}: {date}, {time} - {event_status}\n\nEdit Event: {edit_link}",
			'note'    => "<a href='" . admin_url( 'admin.php?page=my-calendar-design#my-calendar-templates' ) . "'>" . __( 'Templating Help', 'my-calendar' ) . '</a>',
			'atts'    => array(
				'cols' => 60,
				'rows' => 6,
			),
			'type'    => 'textarea',
		)
	);
	?>
							</li>
						</ul>
					</fieldset>
					<p>
						<input type="submit" name="save" class="button-primary" value="<?php _e( 'Save Email Settings', 'my-calendar' ); ?>"/>
					</p>
				</form>
			</div>
		</div>
	</div>

	<?php
	/**
	 * Render content after settings panels have displayed. Default ''.
	 *
	 * @hook mc_after_settings
	 * @param {string} $after_settings Output content.
	 *
	 * @return {string}
	 */
	$after_settings = apply_filters( 'mc_after_settings', '' );
	echo $after_settings;
	?>

	</div>
	</div>

	<?php mc_show_sidebar(); ?>
	</div>
	</div>
	<?php
}

/**
 * Update settings for how location inputs are limited.
 */
function mc_update_location_controls() {
	if ( isset( $_POST['mc_locations'] ) && 'true' === $_POST['mc_locations'] ) {
		$nonce = $_POST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'Invalid nonce' );
		}
		$locations            = isset( $_POST['mc_location_controls'] ) ? map_deep( $_POST['mc_location_controls'], 'sanitize_textarea_field' ) : array();
		$mc_location_controls = array();
		foreach ( $locations as $key => $value ) {
			$mc_location_controls[ $key ] = mc_csv_to_array( $value[0] );
		}
		mc_update_option( 'location_controls', $mc_location_controls );
		mc_show_notice( __( 'Location Controls Updated', 'my-calendar' ) );
	}
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
			'event_label'    => __( 'Name of Location', 'my-calendar' ),
			'event_city'     => __( 'City', 'my-calendar' ),
			'event_state'    => __( 'State/Province', 'my-calendar' ),
			'event_postcode' => __( 'Postal code', 'my-calendar' ),
			'event_region'   => __( 'Region', 'my-calendar' ),
			'event_country'  => __( 'Country', 'my-calendar' ),
		);
		$mc_location_controls = mc_get_option( 'location_controls' );

		$output = $response . '
		<p>' . __( 'Save custom values to change location text fields into dropdowns. One field per line.  Format: <code>saved_value,Displayed Value</code>', 'my-calendar' ) . '</p>
		<form method="post" action="' . admin_url( 'admin.php?page=my-calendar-config#my-calendar-input' ) . '">
		<div><input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'my-calendar-nonce' ) . '" /></div>
		<div><input type="hidden" name="mc_locations" value="true" /></div>
		<fieldset>
			<legend class="screen-reader-text">' . __( 'Restrict Location Input', 'my-calendar' ) . '</legend>
			<div id="mc-accordion" class="mc-locations-control">';
		foreach ( $location_fields as $field => $label ) {
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
			if ( '' !== trim( $locations ) ) {
				$class  = ' class="active-limit"';
				$active = ' (' . __( 'active limits', 'my-calendar' ) . ')';
			}
			$holder  = strtolower( $label ) . ',' . ucfirst( $label );
			$output .= '<h4' . $class . '><span class="dashicons" aria-hidden="true"> </span><button type="button" class="button-link">' . $label . $active . '</button></h4>';
			// Translators: Name of field being restricted.
			$output .= '<div><label for="loc_values_' . $field . '">' . sprintf( __( 'Controls for %s', 'my-calendar' ), ucfirst( $label ) ) . '</label><br/><textarea name="mc_location_controls[' . $field . '][]" id="loc_values_' . $field . '" cols="80" rows="6" placeholder="' . $holder . '">' . trim( $locations ) . '</textarea></div>';
		}
		$output .= "
			</div>
			<p><input type='submit' class='button secondary' value='" . __( 'Update Location Controls', 'my-calendar' ) . "'/></p>
		</fieldset>
		</form>";

		return $output;
	}

	return '';
}

/**
 * Check whether given role has defined capability.
 *
 * @param string $role Name of a role defined in WordPress.
 * @param string $cap Name of capability to check for.
 *
 * @return string
 */
function mc_check_caps( $role, $cap ) {
	$role = get_role( $role );
	if ( $role->has_cap( $cap ) ) {
		return ' checked="checked"';
	}

	return '';
}

/**
 * Checkbox for displaying capabilities.
 *
 * @param string $role Name of a role.
 * @param string $cap Name of a capability.
 * @param string $name Display name of role.
 *
 * @return string HTML checkbox.
 */
function mc_cap_checkbox( $role, $cap, $name ) {
	return "<li><input type='checkbox' id='mc_caps_{$role}_$cap' name='mc_caps[$role][$cap]' value='on'" . mc_check_caps( $role, $cap ) . " /> <label for='mc_caps_{$role}_$cap'>$name</label></li>";
}
