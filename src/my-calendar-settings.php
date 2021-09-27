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
 * Generate input & field for a My Calendar setting.
 *
 * @param string  $name Name of option.
 * @param string  $label Label for input.
 * @param string  $default default value if not set.
 * @param string  $note Note to associate with field via aria-describedby.
 * @param array   $atts Array of keys and values to use as input attributes.
 * @param string  $type Field type for option.
 * @param boolean $echo True to echo, false to return.
 */
function mc_settings_field( $name, $label, $default = '', $note = '', $atts = array(), $type = 'text', $echo = true ) {
	$options    = '';
	$attributes = '';
	if ( 'text' === $type || 'url' === $type || 'email' === $type ) {
		$base_atts = array(
			'size' => '30',
		);
	} else {
		$base_atts = $atts;
	}
	$atts = array_merge( $base_atts, $atts );
	if ( is_array( $atts ) && ! empty( $atts ) ) {
		foreach ( $atts as $key => $val ) {
			$attributes .= " $key='$val'";
		}
	}
	$value = ( '' !== get_option( $name, '' ) ) ? esc_attr( stripslashes( get_option( $name ) ) ) : $default;
	switch ( $type ) {
		case 'text':
		case 'url':
		case 'email':
			if ( $note ) {
				$note = sprintf( $note, "<code>$value</code>" );
				$note = "<span id='$name-note'><i class='dashicons dashicons-editor-help' aria-hidden='true'></i>$note</span>";
				$aria = " aria-describedby='$name-note'";
			} else {
				$note = '';
				$aria = '';
			}
			$return = "<label for='$name'>$label</label> <input type='$type' id='$name' name='$name' value='" . esc_attr( $value ) . "'$aria$attributes /> $note";
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
			$return = "<label for='$name'>$label</label><br /><textarea id='$name' name='$name'$aria$attributes>" . esc_attr( $value ) . "</textarea>$note";
			break;
		case 'checkbox-single':
			$checked = mc_is_checked( $name, 'true', '', true );
			if ( $note ) {
				$note = sprintf( $note, "<code>$value</code>" );
			} else {
				$note = '';
			}
			$return = "<input type='checkbox' id='$name' name='$name' value='on' $checked$attributes /> <label for='$name' class='checkbox-label'>$label $note</label>";
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
			foreach ( $label as $k => $v ) {
				$checked  = ( $k === $value ) ? ' checked="checked"' : '';
				$options .= "<li><input type='radio' id='$name-$k' value='" . esc_attr( $k ) . "' name='$name'$aria$attributes$checked /> <label for='$name-$k'>$v</label></li>";
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
					$checked  = ( $k === $value ) ? ' selected="selected"' : '';
					$options .= "<option value='" . esc_attr( $k ) . "'$checked>$v</option>";
				}
			}
			$return = "
			<label for='$name'>$label</label>
				<select id='$name' name='$name'$aria$attributes />
					$options
				</select>
			$note";
			break;
	}

	if ( true === $echo ) {
		echo $return;
	} else {
		return $return;
	}
}

/**
 * Display the admin configuration page
 */
function my_calendar_import() {
	if ( 'true' !== get_option( 'ko_calendar_imported' ) ) {
		global $wpdb;
		$events         = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'calendar', 'ARRAY_A' );
		$event_ids      = array();
		$events_results = false;
		foreach ( $events as $key ) {
			$endtime        = ( '00:00:00' === $key['event_time'] ) ? '00:00:00' : date( 'H:i:s', strtotime( "$key[event_time] +1 hour" ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$data           = array(
				'event_title'    => $key['event_title'],
				'event_desc'     => $key['event_desc'],
				'event_begin'    => $key['event_begin'],
				'event_end'      => $key['event_end'],
				'event_time'     => $key['event_time'],
				'event_endtime'  => $endtime,
				'event_recur'    => $key['event_recur'],
				'event_repeats'  => $key['event_repeats'],
				'event_author'   => $key['event_author'],
				'event_category' => $key['event_category'],
				'event_hide_end' => 1,
				'event_link'     => ( isset( $key['event_link'] ) ) ? $key['event_link'] : '',
			);
			$format         = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s' );
			$update         = $wpdb->insert( my_calendar_table(), $data, $format );
			$events_results = ( $update ) ? true : false;
			$event_ids[]    = $wpdb->insert_id;
		}
		foreach ( $event_ids as $value ) { // propagate event instances.
			$sql   = 'SELECT event_begin, event_time, event_end, event_endtime FROM ' . my_calendar_table() . ' WHERE event_id = %d';
			$event = $wpdb->get_results( $wpdb->prepare( $sql, $value ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$event = $event[0];
			$dates = array(
				'event_begin'   => $event->event_begin,
				'event_end'     => $event->event_end,
				'event_time'    => $event->event_time,
				'event_endtime' => $event->event_endtime,
			);
			mc_increment_event( $value, $dates );
		}
		$cats         = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'calendar_categories', 'ARRAY_A' );
		$cats_results = false;
		foreach ( $cats as $key ) {
			$name         = esc_sql( $key['category_name'] );
			$color        = esc_sql( $key['category_colour'] );
			$id           = (int) $key['category_id'];
			$catsql       = 'INSERT INTO ' . my_calendar_categories_table() . ' SET category_id=%1$d, category_name=%2$s, category_color=%3$s ON DUPLICATE KEY UPDATE category_name=%2$s, category_color=%3$s;';
			$cats_results = $wpdb->query( $wpdb->prepare( $catsql, $id, $name, $color ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$message   = ( false !== $cats_results ) ? __( 'Categories imported successfully.', 'my-calendar' ) : __( 'Categories not imported.', 'my-calendar' );
		$e_message = ( false !== $events_results ) ? __( 'Events imported successfully.', 'my-calendar' ) : __( 'Events not imported.', 'my-calendar' );
		$return    = "<div id='message' class='updated fade'><ul><li>$message</li><li>$e_message</li></ul></div>";
		echo $return;
		if ( false !== $cats_results && false !== $events_results ) {
			update_option( 'ko_calendar_imported', 'true' );
		}
	}
}

/**
 * Update Management Settings.
 *
 * @param array $post POST data.
 */
function mc_update_management_settings( $post ) {
	// Management settings.
	$mc_api_enabled   = ( ! empty( $post['mc_api_enabled'] ) && 'on' === $post['mc_api_enabled'] ) ? 'true' : 'false';
	$mc_remote        = ( ! empty( $post['mc_remote'] ) && 'on' === $post['mc_remote'] ) ? 'true' : 'false';
	$mc_drop_tables   = ( ! empty( $post['mc_drop_tables'] ) && 'on' === $post['mc_drop_tables'] ) ? 'true' : 'false';
	$mc_drop_settings = ( ! empty( $post['mc_drop_settings'] ) && 'on' === $post['mc_drop_settings'] ) ? 'true' : 'false';
	// Handle My Calendar primary URL.
	$mc_uri = get_option( 'mc_uri' );
	if ( isset( $post['mc_uri'] ) && ! isset( $post['mc_uri_id'] ) ) {
		$mc_uri = $post['mc_uri'];
	} elseif ( isset( $post['mc_uri_id'] ) && is_numeric( $post['mc_uri_id'] ) ) {
		if ( get_post( absint( $post['mc_uri_id'] ) ) ) {
			$mc_uri = get_permalink( absint( $post['mc_uri_id'] ) );
		} else {
			$mc_uri = isset( $post['mc_uri'] ) ? $post['mc_uri'] : get_option( 'mc_uri' );
		}
	}
	update_option( 'mc_use_permalinks', ( ! empty( $post['mc_use_permalinks'] ) ) ? 'true' : 'false' );
	update_option( 'mc_uri', $mc_uri );
	update_option( 'mc_uri_id', absint( $post['mc_uri_id'] ) );
	// End handling of primary URL.
	update_option( 'mc_api_enabled', $mc_api_enabled );
	update_option( 'mc_remote', $mc_remote );
	update_option( 'mc_drop_tables', $mc_drop_tables );
	update_option( 'mc_drop_settings', $mc_drop_settings );
	update_option( 'mc_default_sort', $post['mc_default_sort'] );
	update_option( 'mc_default_direction', $post['mc_default_direction'] );
	if ( 2 === (int) get_site_option( 'mc_multisite' ) ) {
		$mc_current_table = ( isset( $post['mc_current_table'] ) ) ? (int) $post['mc_current_table'] : 0;
		update_option( 'mc_current_table', $mc_current_table );
	}
}

/**
 * Update Permissions settings.
 *
 * @param array $post POST data.
 */
function mc_update_permissions_settings( $post ) {
	$perms = $post['mc_caps'];
	$caps  = array(
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
	$mc_open_day_uri = ( ! empty( $post['mc_open_day_uri'] ) ) ? $post['mc_open_day_uri'] : '';
	$mc_open_uri     = ( ! empty( $post['mc_open_uri'] ) ) ? $post['mc_open_uri'] : 'off';
	update_option( 'mc_open_uri', $mc_open_uri );
	update_option( 'mc_no_link', ( ! empty( $post['mc_no_link'] ) && 'on' === $post['mc_no_link'] ) ? 'true' : 'false' );
	update_option( 'mc_mini_uri', $post['mc_mini_uri'] );
	update_option( 'mc_open_day_uri', $mc_open_day_uri );
	update_option( 'mc_display_author', ( ! empty( $post['mc_display_author'] ) && 'on' === $post['mc_display_author'] ) ? 'true' : 'false' );
	update_option( 'mc_display_host', ( ! empty( $post['mc_display_host'] ) && 'on' === $post['mc_display_host'] ) ? 'true' : 'false' );
	update_option( 'mc_show_event_vcal', ( ! empty( $post['mc_show_event_vcal'] ) && 'on' === $post['mc_show_event_vcal'] ) ? 'true' : 'false' );
	update_option( 'mc_show_gcal', ( ! empty( $post['mc_show_gcal'] ) && 'on' === $post['mc_show_gcal'] ) ? 'true' : 'false' );
	update_option( 'mc_show_list_info', ( ! empty( $post['mc_show_list_info'] ) && 'on' === $post['mc_show_list_info'] ) ? 'true' : 'false' );
	update_option( 'mc_show_list_events', ( ! empty( $post['mc_show_list_events'] ) && 'on' === $post['mc_show_list_events'] ) ? 'true' : 'false' );
	update_option( 'mc_show_months', (int) $post['mc_show_months'] );
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
	$top    = ( empty( $top ) ) ? 'none' : implode( ',', $top );
	$bottom = ( empty( $bottom ) ) ? 'none' : implode( ',', $bottom );
	update_option( 'mc_bottomnav', $bottom );
	update_option( 'mc_topnav', $top );
	update_option( 'mc_show_map', ( ! empty( $post['mc_show_map'] ) && 'on' === $post['mc_show_map'] ) ? 'true' : 'false' );
	update_option( 'mc_gmap', ( ! empty( $post['mc_gmap'] ) && 'on' === $post['mc_gmap'] ) ? 'true' : 'false' );
	update_option( 'mc_gmap_api_key', ( ! empty( $post['mc_gmap_api_key'] ) ) ? strip_tags( $post['mc_gmap_api_key'] ) : '' );
	update_option( 'mc_show_address', ( ! empty( $post['mc_show_address'] ) && 'on' === $post['mc_show_address'] ) ? 'true' : 'false' );
	update_option( 'mc_display_more', ( ! empty( $post['mc_display_more'] ) && 'on' === $post['mc_display_more'] ) ? 'true' : 'false' );
	update_option( 'mc_event_registration', ( ! empty( $post['mc_event_registration'] ) && 'on' === $post['mc_event_registration'] ) ? 'true' : 'false' );
	update_option( 'mc_short', ( ! empty( $post['mc_short'] ) && 'on' === $post['mc_short'] ) ? 'true' : 'false' );
	update_option( 'mc_desc', ( ! empty( $post['mc_desc'] ) && 'on' === $post['mc_desc'] ) ? 'true' : 'false' );
	update_option( 'mc_event_link', ( ! empty( $post['mc_event_link'] ) && 'on' === $post['mc_event_link'] ) ? 'true' : 'false' );
	update_option( 'mc_image', ( ! empty( $post['mc_image'] ) && 'on' === $post['mc_image'] ) ? 'true' : 'false' );
	update_option( 'mc_show_weekends', ( ! empty( $post['mc_show_weekends'] ) && 'on' === $post['mc_show_weekends'] ) ? 'true' : 'false' );
	update_option( 'mc_title', ( ! empty( $post['mc_title'] ) && 'on' === $post['mc_title'] ) ? 'true' : 'false' );
	update_option( 'mc_convert', ( ! empty( $post['mc_convert'] ) ) ? $post['mc_convert'] : 'false' );
}

/**
 * Update input settings.
 *
 * @param array $post POST data.
 */
function mc_update_input_settings( $post ) {
	$mc_input_options_administrators = ( ! empty( $post['mc_input_options_administrators'] ) && 'on' === $post['mc_input_options_administrators'] ) ? 'true' : 'false';
	$mc_input_options                = array(
		'event_short'    => ( ! empty( $post['mci_event_short'] ) && $post['mci_event_short'] ) ? 'on' : 'off',
		'event_desc'     => ( ! empty( $post['mci_event_desc'] ) && $post['mci_event_desc'] ) ? 'on' : 'off',
		'event_category' => ( ! empty( $post['mci_event_category'] ) && $post['mci_event_category'] ) ? 'on' : 'off',
		'event_image'    => ( ! empty( $post['mci_event_image'] ) && $post['mci_event_image'] ) ? 'on' : 'off',
		'event_link'     => ( ! empty( $post['mci_event_link'] ) && $post['mci_event_link'] ) ? 'on' : 'off',
		'event_recurs'   => ( ! empty( $post['mci_event_recurs'] ) && $post['mci_event_recurs'] ) ? 'on' : 'off',
		'event_open'     => ( ! empty( $post['mci_event_open'] ) && $post['mci_event_open'] ) ? 'on' : 'off',
		'event_location' => ( ! empty( $post['mci_event_location'] ) && $post['mci_event_location'] ) ? 'on' : 'off',
		'event_specials' => ( ! empty( $post['mci_event_specials'] ) && $post['mci_event_specials'] ) ? 'on' : 'off',
		'event_access'   => ( ! empty( $post['mci_event_access'] ) && $post['mci_event_access'] ) ? 'on' : 'off',
		'event_host'     => ( ! empty( $post['mci_event_host'] ) && $post['mci_event_host'] ) ? 'on' : 'off',
	);
	update_option( 'mc_input_options', $mc_input_options );
	update_option( 'mc_input_options_administrators', $mc_input_options_administrators );
}

/**
 * Update text settings.
 *
 * @param array $post POST data.
 */
function mc_update_text_settings( $post ) {
	$mc_title_template       = $post['mc_title_template'];
	$mc_title_template_solo  = $post['mc_title_template_solo'];
	$mc_title_template_list  = $post['mc_title_template_list'];
	$mc_details_label        = $post['mc_details_label'];
	$mc_link_label           = $post['mc_link_label'];
	$mc_event_title_template = $post['mc_event_title_template'];
	$mc_notime_text          = $post['mc_notime_text'];
	$mc_hosted_by            = $post['mc_hosted_by'];
	$mc_posted_by            = $post['mc_posted_by'];
	$mc_buy_tickets          = $post['mc_buy_tickets'];
	$mc_view_full            = $post['mc_view_full'];
	$mc_previous_events      = $post['mc_previous_events'];
	$mc_next_events          = $post['mc_next_events'];
	$mc_week_caption         = $post['mc_week_caption'];
	$mc_caption              = $post['mc_caption'];
	$templates               = get_option( 'mc_templates' );
	$templates['title']      = $mc_title_template;
	$templates['title_solo'] = $mc_title_template_solo;
	$templates['title_list'] = $mc_title_template_list;
	$templates['label']      = $mc_details_label;
	$templates['link']       = $mc_link_label;
	update_option( 'mc_templates', $templates );
	update_option( 'mc_event_title_template', $mc_event_title_template );
	update_option( 'mc_notime_text', $mc_notime_text );
	update_option( 'mc_hosted_by', $mc_hosted_by );
	update_option( 'mc_posted_by', $mc_posted_by );
	update_option( 'mc_buy_tickets', $mc_buy_tickets );
	update_option( 'mc_view_full', $mc_view_full );
	update_option( 'mc_week_caption', $mc_week_caption );
	update_option( 'mc_next_events', $mc_next_events );
	update_option( 'mc_previous_events', $mc_previous_events );
	update_option( 'mc_caption', $mc_caption );
	// Date/time.
	update_option( 'mc_date_format', stripslashes( $post['mc_date_format'] ) );
	update_option( 'mc_multidate_format', stripslashes( $post['mc_multidate_format'] ) );
	update_option( 'mc_week_format', stripslashes( $post['mc_week_format'] ) );
	update_option( 'mc_time_format', stripslashes( $post['mc_time_format'] ) );
	update_option( 'mc_month_format', stripslashes( $post['mc_month_format'] ) );
}

/**
 * Build settings form.
 */
function my_calendar_settings() {
	my_calendar_check();
	if ( ! empty( $_POST ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			die( 'Security check failed' );
		}
		if ( isset( $_POST['mc_manage'] ) ) {
			$before_permalinks = get_option( 'mc_use_permalinks' );
			mc_update_management_settings( $_POST );
			mc_show_notice( __( 'My Calendar Management Settings saved', 'my-calendar' ) );
		}
		if ( isset( $_POST['mc_permissions'] ) ) {
			mc_update_permissions_settings( $_POST );
			mc_show_notice( __( 'My Calendar Permissions Updated', 'my-calendar' ) );
		}
		// Output.
		if ( isset( $_POST['mc_show_months'] ) ) {
			mc_update_output_settings( $_POST );
			mc_show_notice( __( 'Output Settings saved', 'my-calendar' ) );
		}
		// INPUT.
		if ( isset( $_POST['mc_input'] ) ) {
			mc_update_input_settings( $_POST );
			mc_show_notice( __( 'Input Settings saved', 'my-calendar' ) );
		}
		if ( current_user_can( 'manage_network' ) && is_multisite() ) {
			if ( isset( $_POST['mc_network'] ) ) {
				$mc_multisite = (int) $_POST['mc_multisite'];
				update_site_option( 'mc_multisite', $mc_multisite );
				$mc_multisite_show = (int) $_POST['mc_multisite_show'];
				update_site_option( 'mc_multisite_show', $mc_multisite_show );
				mc_show_notice( __( 'Multisite settings saved', 'my-calendar' ) );
			}
		}
		// custom text.
		if ( isset( $_POST['mc_previous_events'] ) ) {
			mc_update_text_settings( $_POST );
			mc_show_notice( __( 'Custom text settings saved', 'my-calendar' ) );
		}
		// Mail function by Roland.
		if ( isset( $_POST['mc_email'] ) ) {
			$mc_event_mail         = ( ! empty( $_POST['mc_event_mail'] ) && 'on' === $_POST['mc_event_mail'] ) ? 'true' : 'false';
			$mc_html_email         = ( ! empty( $_POST['mc_html_email'] ) && 'on' === $_POST['mc_html_email'] ) ? 'true' : 'false';
			$mc_event_mail_to      = $_POST['mc_event_mail_to'];
			$mc_event_mail_from    = $_POST['mc_event_mail_from'];
			$mc_event_mail_subject = $_POST['mc_event_mail_subject'];
			$mc_event_mail_message = $_POST['mc_event_mail_message'];
			$mc_event_mail_bcc     = $_POST['mc_event_mail_bcc'];
			update_option( 'mc_event_mail_to', $mc_event_mail_to );
			update_option( 'mc_event_mail_from', $mc_event_mail_from );
			update_option( 'mc_event_mail_subject', $mc_event_mail_subject );
			update_option( 'mc_event_mail_message', $mc_event_mail_message );
			update_option( 'mc_event_mail_bcc', $mc_event_mail_bcc );
			update_option( 'mc_event_mail', $mc_event_mail );
			update_option( 'mc_html_email', $mc_html_email );
			mc_show_notice( __( 'Email notice settings saved', 'my-calendar' ) );
		}

		$settings = apply_filters( 'mc_save_settings', '', $_POST );
	}
	// Pull templates for passing into functions.
	$templates              = get_option( 'mc_templates' );
	$mc_title_template      = ( isset( $templates['title'] ) ) ? esc_attr( stripslashes( $templates['title'] ) ) : '';
	$mc_title_template_solo = ( isset( $templates['title_solo'] ) ) ? esc_attr( stripslashes( $templates['title_solo'] ) ) : '';
	$mc_title_template_list = ( isset( $templates['title_list'] ) ) ? esc_attr( stripslashes( $templates['title_list'] ) ) : '';
	$mc_details_label       = ( isset( $templates['label'] ) ) ? esc_attr( stripslashes( $templates['label'] ) ) : '';
	$mc_link_label          = ( isset( $templates['link'] ) ) ? esc_attr( stripslashes( $templates['link'] ) ) : '';
	?>

	<div class="wrap my-calendar-admin mc-settings-page" id="mc_settings">
	<?php my_calendar_check_db(); ?>
	<h1><?php _e( 'My Calendar Settings', 'my-calendar' ); ?></h1>
	<div class="mc-tabs">
		<div class="tabs" role="tablist" data-default="my-calendar-manage">
			<button type="button" role="tab" aria-selected="false"  id="tab_manage" aria-controls="my-calendar-manage"><?php _e( 'General', 'my-calendar' ); ?></button>
			<button type="button" role="tab" aria-selected="false"  id="tab_text" aria-controls="my-calendar-text"><?php _e( 'Text', 'my-calendar' ); ?></button>
			<button type="button" role="tab" aria-selected="false"  id="tab_output" aria-controls="mc-output"><?php _e( 'Output', 'my-calendar' ); ?></button>
			<button type="button" role="tab" aria-selected="false"  id="tab_input" aria-controls="my-calendar-input"><?php _e( 'Input', 'my-calendar' ); ?></button>
			<?php
			if ( current_user_can( 'manage_network' ) && is_multisite() ) {
				?>
				<button type="button" role="tab" aria-selected="false"  id="tab_multi" aria-controls="my-calendar-multisite"><?php _e( 'Multisite', 'my-calendar' ); ?></button>
				<?php
			}
			?>
			<button type="button" role="tab" aria-selected="false"  id="tab_permissions" aria-controls="my-calendar-permissions"><?php _e( 'Permissions', 'my-calendar' ); ?></button>
			<button type="button" role="tab" id="tab_email" aria-selected="false" aria-controls="my-calendar-email"><?php _e( 'Notifications', 'my-calendar' ); ?></button>
			<?php echo apply_filters( 'mc_settings_section_links', '' ); ?>
		</div>
		<div class="settings postbox-container jcd-wide">
		<div class="metabox-holder">
	<?php
	if ( isset( $_POST['import'] ) && 'true' === $_POST['import'] ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			die( 'Security check failed' );
		}
		my_calendar_import();
	}
	if ( 'true' !== get_option( 'ko_calendar_imported' ) ) {
		if ( function_exists( 'check_calendar' ) ) {
			?>
			<div class='import upgrade-db'>
				<p>
					<?php _e( 'You have the Calendar plugin by Kieran O\'Shea installed. You can import those events and categories into My Calendar.', 'my-calendar' ); ?>
				</p>

				<form method="post" action="<?php echo admin_url( 'admin.php?page=my-calendar-config' ); ?>">
					<div>
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
						<input type="hidden" name="import" value="true" />
						<input type="submit" value="<?php _e( 'Import from Calendar', 'my-calendar' ); ?>" name="import-calendar" class="button-primary"/>
					</div>
				</form>
			</div>
			<?php
		}
	}
	?>
	<div class="ui-sortable meta-box-sortables">
		<div class="wptab postbox" aria-labelledby="tab_manage" role="tabpanel" id="my-calendar-manage">
			<h2><?php _e( 'My Calendar Management', 'my-calendar' ); ?></h2>

			<div class="inside">
				<?php
				if ( current_user_can( 'administrator' ) ) {
					?>
					<form method="post" action="<?php echo admin_url( 'admin.php?page=my-calendar-config#my-calendar-manage' ); ?>">
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
						<fieldset>
							<legend class="screen-reader-text"><?php _e( 'Management', 'my-calendar' ); ?></legend>
							<ul>
								<?php
								$has_uri    = mc_get_uri( 'boolean' );
								$page_title = '';
								$permalink  = '';
								if ( get_option( 'mc_uri_id' ) ) {
									$page_title = get_post( absint( get_option( 'mc_uri_id' ) ) )->post_title;
									$permalink  = esc_url( get_permalink( absint( get_option( 'mc_uri_id' ) ) ) );
								}
								?>
								<li id="mc-pages-autocomplete" class="mc-autocomplete autocomplete">
								<?php
								mc_settings_field(
									'mc_uri_query',
									__( 'Calendar Page Location', 'my-calendar' ),
									$page_title,
									'',
									array(
										'size'  => '20',
										'class' => 'autocomplete-input',
									),
									'text'
								);
								?>
								<ul class="autocomplete-result-list"></ul>
								<?php
								mc_settings_field( 'mc_uri_id', '', '', '', array(), 'hidden' );
								?>
								</li>
								<li>
								<?php
								mc_settings_field(
									'mc_default_sort',
									__( 'Default sort order for Admin Events List', 'my-calendar' ),
									array(
										'1' => __( 'ID', 'my-calendar' ),
										'2' => __( 'Title', 'my-calendar' ),
										'4' => __( 'Date/Time', 'my-calendar' ),
										'5' => __( 'Author', 'my-calendar' ),
										'6' => __( 'Category', 'my-calendar' ),
										'7' => __( 'Location', 'my-calendar' ),
									),
									'',
									array(),
									'select'
								);
								?>
								</li>
								<li>
								<?php
								mc_settings_field(
									'mc_default_direction',
									__( 'Default sort direction', 'my-calendar' ),
									array(
										'ASC'  => __( 'Ascending', 'my-calendar' ),
										'DESC' => __( 'Descending', 'my-calendar' ),
									),
									'',
									array(),
									'select'
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
								<li><?php mc_settings_field( 'mc_use_permalinks', __( 'Use Pretty Permalinks for Events', 'my-calendar' ), '', $note, array(), 'checkbox-single' ); ?></li>
								<?php
								if ( (int) get_site_option( 'mc_multisite' ) === 2 && my_calendar_table() !== my_calendar_table( 'global' ) ) {
									mc_settings_field(
										'mc_current_table',
										array(
											'0' => __( 'Currently editing my local calendar', 'my-calendar' ),
											'1' => __( 'Currently editing the network calendar', 'my-calendar' ),
										),
										'0',
										'',
										array(),
										'radio'
									);
								} else {
									if ( get_option( 'mc_remote' ) !== 'true' && current_user_can( 'manage_network' ) && is_multisite() && is_main_site() ) {
										?>
										<li><?php _e( 'You are currently working in the primary site for this network; your local calendar is also the global table.', 'my-calendar' ); ?></li>
										<?php
									}
								}
								?>
							</ul>
						</fieldset>
						<fieldset>
							<legend><?php _e( 'Advanced', 'my-calendar' ); ?></legend>
							<ul>
	<li><?php mc_settings_field( 'mc_remote', __( 'Get data (events, categories and locations) from a remote database', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
								<?php
								if ( 'true' === get_option( 'mc_remote' ) && ! function_exists( 'mc_remote_db' ) ) {
									?>
								<li><?php _e( 'Add this code to your theme\'s <code>functions.php</code> file:', 'my-calendar' ); ?>
<pre>
function mc_remote_db() {
	$mcdb = new wpdb('DB_USER','DB_PASSWORD','DB_NAME','DB_ADDRESS');

	return $mcdb;
}
</pre>
									<?php _e( 'You will need to allow remote connections from this site to the site hosting your My Calendar events. Replace the above placeholders with the host-site information. The two sites must have the same WP table prefix. While this option is enabled, you may not enter or edit events through this installation.', 'my-calendar' ); ?>
								</li>
									<?php
								}
								?>
								<li><?php mc_settings_field( 'mc_api_enabled', __( 'Enable external API', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?>
								<?php
								if ( 'true' === get_option( 'mc_api_enabled' ) ) {
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
								<li><?php mc_settings_field( 'mc_drop_tables', __( 'Drop MySQL tables on uninstall', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
								<li><?php mc_settings_field( 'mc_drop_settings', __( 'Delete plugin settings on uninstall', 'my-calendar' ), 'true', '', array(), 'checkbox-single' ); ?></li>
							</ul>
						</fieldset>
						<p>
							<input type="submit" name="mc_manage" class="button-primary" value="<?php _e( 'Save Management Settings', 'my-calendar' ); ?>"/>
						</p>
					</form>
					<h3><?php _e( 'Settings on other screens', 'my-calendar' ); ?></h3>
					<?php
						$current_location_slug = ( '' === get_option( 'mc_location_cpt_base', '' ) ) ? __( 'mc-locations', 'my-calendar' ) : get_option( 'mc_location_cpt_base' );
						$current_event_slug    = ( '' === get_option( 'mc_cpt_base', '' ) ) ? __( 'mc-events', 'my-calendar' ) : get_option( 'mc_cpt_base' );
					?>
					<ul>
						<li><?php _e( 'Options > Permalinks', 'my-calendar' ); ?>: <a aria-describedby='mc-current-location-slug' href="<?php echo admin_url( 'options-permalink.php#mc_location_cpt_base' ); ?>"><?php _e( 'Location permalink slug', 'my-calendar' ); ?></a> <span id="mc-current-location-slug">(<?php echo $current_location_slug; ?>)</span></li>
						<li><?php _e( 'Options > Permalinks', 'my-calendar' ); ?>: <a aria-describedby='mc-current-events-slug' href="<?php echo admin_url( 'options-permalink.php#mc_cpt_base' ); ?>"><?php _e( 'Events permalink slug', 'my-calendar' ); ?></a> <span id="mc-current-events-slug">(<?php echo $current_event_slug; ?>)</span></li>
						<li><?php _e( 'Options > General', 'my-calendar' ); ?>: <a href="<?php echo admin_url( 'options-general.php#start_of_week' ); ?>"><?php _e( 'First day of the week', 'my-calendar' ); ?></a></li>
					</ul>
					<?php
				} else {
					_e( 'My Calendar management settings are only available to administrators.', 'my-calendar' );
				}
				?>
			</div>
		</div>

		<div class="wptab postbox initial-hidden" aria-labelledby="tab_text" role="tabpanel" id="my-calendar-text">
			<h2><?php _e( 'Text Settings', 'my-calendar' ); ?></h2>

			<div class="inside">
				<form method="post" action="<?php echo admin_url( 'admin.php?page=my-calendar-config#my-calendar-text' ); ?>">
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
					<fieldset>
						<legend><?php _e( 'Templating', 'my-calendar' ); ?></legend>
						<ul>
							<li><?php mc_settings_field( 'mc_title_template', __( 'Event title (Grid)', 'my-calendar' ), $mc_title_template, "<a href='" . admin_url( 'admin.php?page=my-calendar-templates#templates' ) . "'>" . __( 'Templating Help', 'my-calendar' ) . '</a>' ); ?></li>
							<li><?php mc_settings_field( 'mc_title_template_solo', __( 'Event title (Single)', 'my-calendar' ), $mc_title_template_solo, "<a href='" . admin_url( 'admin.php?page=my-calendar-templates#templates' ) . "'>" . __( 'Templating Help', 'my-calendar' ) . '</a>' ); ?></li>
							<li><?php mc_settings_field( 'mc_title_template_list', __( 'Event title (List)', 'my-calendar' ), $mc_title_template_list, "<a href='" . admin_url( 'admin.php?page=my-calendar-templates#templates' ) . "'>" . __( 'Templating Help', 'my-calendar' ) . '</a>' ); ?></li>
						</ul>
					</fieldset>
					<fieldset>
						<legend><?php _e( 'Main Calendar View', 'my-calendar' ); ?></legend>
						<ul>
							<li><?php mc_settings_field( 'mc_previous_events', __( 'Previous events link', 'my-calendar' ), __( 'Previous', 'my-calendar' ), __( 'Use <code>{date}</code> to display date in navigation.', 'my-calendar' ) ); ?></li>
							<li><?php mc_settings_field( 'mc_next_events', __( 'Next events link', 'my-calendar' ), __( 'Next', 'my-calendar' ), __( 'Use <code>{date}</code> to display date in navigation.', 'my-calendar' ) ); ?></li>
							<li>
							<?php
							mc_settings_field(
								'mc_week_caption',
								__( 'Week view caption:', 'my-calendar' ),
								'',
								__( 'Available tag: <code>{date format=""}</code>', 'my-calendar' ),
								array(
									// Translators: date template tag.
									'placeholder' => sprintf( __( 'Week of %s', 'my-calendar' ), '{date format="M jS"}' ),
								)
							);
							?>
							</li>
							<li><?php mc_settings_field( 'mc_caption', __( 'Extended caption:', 'my-calendar' ), '', __( 'Follows month/year in calendar heading.', 'my-calendar' ) ); ?></li>
						</ul>
					</fieldset>
					<fieldset>
						<legend><?php _e( 'Single Event View', 'my-calendar' ); ?></legend>
						<ul>
							<li><?php mc_settings_field( 'mc_notime_text', __( 'Label for all-day events', 'my-calendar' ), 'All Day' ); ?></li>
							<li><?php mc_settings_field( 'mc_hosted_by', __( 'Hosted by', 'my-calendar' ), 'Hosted by' ); ?></li>
							<li><?php mc_settings_field( 'mc_posted_by', __( 'Posted by', 'my-calendar' ), 'Posted by' ); ?></li>
							<li><?php mc_settings_field( 'mc_buy_tickets', __( 'Buy tickets', 'my-calendar' ), 'Buy tickets' ); ?></li>
							<li><?php mc_settings_field( 'mc_view_full', __( 'View full calendar', 'my-calendar' ), 'View full calendar' ); ?></li>
							<li><?php mc_settings_field( 'mc_details_label', __( 'Read more text', 'my-calendar' ), $mc_details_label, __( 'Tags: <code>{title}</code>, <code>{location}</code>, <code>{color}</code>, <code>{icon}</code>, <code>{date}</code>, <code>{time}</code>.', 'my-calendar' ) ); ?></li>
							<li><?php mc_settings_field( 'mc_link_label', __( 'More information text', 'my-calendar' ), $mc_link_label, "<a href='" . admin_url( 'admin.php?page=my-calendar-templates#templates' ) . "'>" . __( 'Templating Help', 'my-calendar' ) . '</a>' ); ?></li>
							<li>
							<?php
							// Translators: Current title template (code).
							mc_settings_field( 'mc_event_title_template', __( 'Browser tab title element template', 'my-calendar' ), '{title} &raquo; {date}', __( 'Current: %s', 'my-calendar' ) );
							?>
							</li>
						</ul>
					</fieldset>
					<fieldset>
						<legend><?php _e( 'Date/Time Formats', 'my-calendar' ); ?></legend>
						<div><input type='hidden' name='mc_dates' value='true'/></div>
						<ul>
							<?php
							$month_format = ( '' === get_option( 'mc_month_format', '' ) ) ? date_i18n( 'F Y' ) : date_i18n( get_option( 'mc_month_format' ) );
							$time_format  = ( '' === get_option( 'mc_time_format', '' ) ) ? date_i18n( get_option( 'time_format' ) ) : date_i18n( get_option( 'mc_time_format' ) );
							$week_format  = ( '' === get_option( 'mc_week_format', '' ) ) ? date_i18n( 'M j, \'y' ) : date_i18n( get_option( 'mc_week_format' ) );
							$date_format  = ( '' === get_option( 'mc_date_format', '' ) ) ? date_i18n( get_option( 'date_format' ) ) : date_i18n( get_option( 'mc_date_format' ) );
							$tomorrow     = date( 'j' ) + 1; // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
							$multi_format = ( '' === get_option( 'mc_multidate_format', '' ) ) ? date_i18n( str_replace( '%d', $tomorrow, 'F j-%d, Y' ) ) : date_i18n( str_replace( '%d', $tomorrow, get_option( 'mc_multidate_format' ) ) );
							?>
							<li><?php mc_settings_field( 'mc_date_format', __( 'Primary Date Format', 'my-calendar' ), '', $date_format ); ?></li>
							<li><?php mc_settings_field( 'mc_time_format', __( 'Time Format', 'my-calendar' ), '', $time_format ); ?></li>
							<li><?php mc_settings_field( 'mc_month_format', __( 'Month Format (calendar headings)', 'my-calendar' ), '', $month_format ); ?></li>
							<li><?php mc_settings_field( 'mc_week_format', __( 'Date in grid mode, week view', 'my-calendar' ), '', $week_format ); ?></li>
							<li><?php mc_settings_field( 'mc_multidate_format', __( 'Date Format for multi-day events', 'my-calendar' ), 'F j-%d, Y', $multi_format . ' (' . __( 'Use <code>&#37;d</code> to represent the end date.', 'my-calendar' ) . ')' ); ?></li>
						</ul>
					</fieldset>
					<p>
						<input type="submit" name="save" class="button-primary" value="<?php _e( 'Save Custom Text', 'my-calendar' ); ?>"/>
					</p>
				</form>
				<p>
				<?php _e( 'Date formats use syntax from the <a href="http://php.net/date">PHP <code>date()</code> function</a>. Save to update sample output.', 'my-calendar' ); ?>
				</p>
			</div>
		</div>

		<div class="wptab postbox initial-hidden" aria-labelledby="tab_output" role="tabpanel" id="mc-output">
			<h2><?php _e( 'Output Settings', 'my-calendar' ); ?></h2>

			<div class="inside">
				<form method="post" action="<?php echo admin_url( 'admin.php?page=my-calendar-config#mc-output' ); ?>">
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
					<input type="submit" name="save" class="button screen-reader-text" value="<?php _e( 'Save Output Settings', 'my-calendar' ); ?>" /></p>
						<ul>
							<li>
							<?php
							$atts = array();
							$note = '';
							if ( '' === get_option( 'mc_uri_id', '' ) || '0' === get_option( 'mc_uri_id' ) ) {
								$atts = array( 'disabled' => 'disabled' );
								$note = ' (' . __( 'Set a main calendar page first.', 'my-calendar' ) . ')';
							}
							mc_settings_field(
								'mc_open_uri',
								__( 'Calendar Links', 'my-calendar' ),
								array(
									'false' => __( 'Open links as a pop-up', 'my-calendar' ),
									'true'  => __( 'Open event links in single event view', 'my-calendar' ),
									'none'  => __( 'Disable event links', 'my-calendar' ),
								),
								$note,
								$atts,
								'select'
							);
							?>
							</li>
						</ul>

					<fieldset>
						<legend><?php _e( 'Re-order calendar layout', 'my-calendar' ); ?></legend>
						<?php
						$topnav       = explode( ',', get_option( 'mc_topnav' ) );
						$calendar     = array( 'calendar' );
						$botnav       = explode( ',', get_option( 'mc_bottomnav' ) );
						$order        = array_merge( $topnav, $calendar, $botnav );
						$nav_elements = array(
							'nav'        => '<div class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></div> <div class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></div> <span>' . __( 'Primary Previous/Next Buttons', 'my-calendar' ) . '</span>',
							'toggle'     => '<div class="dashicons dashicons-list-view" aria-hidden="true"></div> <div class="dashicons dashicons-calendar"></div> <span>' . __( 'Switch between list and grid views', 'my-calendar' ) . '</span>',
							'jump'       => '<div class="dashicons dashicons-redo" aria-hidden="true"></div> <span>' . __( 'Jump to any other month/year', 'my-calendar' ) . '</span>',
							'print'      => '<div class="dashicons dashicons-list-view" aria-hidden="true"></div> <span>' . __( 'Link to printable view', 'my-calendar' ) . '</span>',
							'timeframe'  => '<div class="dashicons dashicons-clock" aria-hidden="true"></div> <span>' . __( 'Toggle between day, week, and month view', 'my-calendar' ) . '</span>',
							'calendar'   => '<div class="dashicons dashicons-calendar" aria-hidden="true"></div> <span>' . __( 'The calendar', 'my-calendar' ) . '</span>',
							'key'        => '<div class="dashicons dashicons-admin-network" aria-hidden="true"></div> <span>' . __( 'Categories', 'my-calendar' ) . '</span>',
							'feeds'      => '<div class="dashicons dashicons-rss" aria-hidden="true"></div> <span>' . __( 'RSS and iCal Subscription Links', 'my-calendar' ) . '</span>',
							'exports'    => '<div class="dashicons dashicons-calendar-alt" aria-hidden="true"></div> <span>' . __( 'Links to iCal Exports', 'my-calendar' ) . '</span>',
							'locations'  => '<div class="dashicons dashicons-location" aria-hidden="true"></div> <span>' . __( 'Location (dropdown)', 'my-calendar' ) . '</span>',
							'categories' => '<div class="dashicons dashicons-admin-network" aria-hidden="true"></div> <span>' . __( 'Categories (dropdown)', 'my-calendar' ) . '</span>',
							'access'     => '<div class="dashicons dashicons-universal-access" aria-hidden="true"></div> <span>' . __( 'Access (dropdown)', 'my-calendar' ) . '</span>',
							'search'     => '<div class="dashicons dashicons-search" aria-hidden="true"></div> <span>' . __( 'Search', 'my-calendar' ) . '</span>',
						);
						echo "<div id='mc-sortable-update' aria-live='assertive'></div>";
						echo "<ul id='mc-sortable'>";
						$inserted = array();
						$class    = 'visible';
						$count    = count( $nav_elements );
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
								$buttons    = "<button class='up' type='button'><i class='dashicons dashicons-arrow-up' aria-hidden='true'></i><span class='screen-reader-text'>" . $up_label . "</span></button> <button class='down' type='button'><i class='dashicons dashicons-arrow-down' aria-hidden='true'></i><span class='screen-reader-text'>" . $down_label . '</span></button> ' . "<button class='hide' type='button'><i class='dashicons dashicons-visibility' aria-hidden='true'></i><span class='screen-reader-text'>" . $hide_label . '</span></button>';
								$buttons    = "<div class='mc-buttons'>$buttons</div>";
								echo "<li class='ui-state-default mc-$k mc-$class'>$buttons <code>$label</code> $v <input type='hidden' name='mc_nav[]' value='$k' /></li>";
								$i ++;
							}
						}
						$missed = array_diff( $nav_elements, $inserted );
						$i      = 1;
						$count  = count( $missed );
						foreach ( $missed as $k => $v ) {
							if ( $i !== $count ) {
								// Translators: control to move down.
								$down_label = sprintf( __( 'Move %s Down', 'my-calendar' ), $k );
								// Translators: control to move up.
								$up_label = sprintf( __( 'Move %s Up', 'my-calendar' ), $k );
								// Translators: control to hide.
								$hide_label = sprintf( __( 'Show %s', 'my-calendar' ), $k );
								$buttons    = "<button class='up' type='button'><i class='dashicons dashicons-arrow-up' aria-hidden='true'></i><span class='screen-reader-text'>" . $up_label . "</span></button> <button class='down' type='button'><i class='dashicons dashicons-arrow-down' aria-hidden='true'></i><span class='screen-reader-text'>" . $down_label . "</span></button> <button class='hide' type='button'><i class='dashicons dashicons-hidden' aria-hidden='true'></i><span class='screen-reader-text'>" . $hide_label . '</span></button>';
							} else {
								$buttons = "<button class='up' type='button'><i class='dashicons dashicons-arrow-up' aria-hidden='true'></i><span class='screen-reader-text'>" . $up_label . "</span></button> <button class='hide' type='button'><i class='dashicons dashicons-hidden' aria-hidden='true'></i><span class='screen-reader-text'>" . $hide_label . '</span></button>';
							}
							$buttons = "<div class='mc-buttons'>$buttons</div>";
							echo "<li class='ui-state-default mc-$k mc-hidden'>$buttons <code>$k</code> $v <input type='hidden' name='mc_nav[]' value='$k' disabled /></li>";
							$i ++;
						}

						echo '</ul>';
						?>
					</fieldset>

					<fieldset>
						<legend><?php _e( 'Single Event Details', 'my-calendar' ); ?></legend>
						<p>
						<?php
						_e( 'Choose fields to show in the frontend pop-up or single event views.', 'my-calendar' );
						echo ' ';
						_e( 'Custom templates override these settings.', 'my-calendar' );
						?>
						<ul class="checkboxes">
							<li><?php mc_settings_field( 'mc_display_author', __( 'Author', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_display_host', __( 'Host', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_show_event_vcal', __( 'Link to single event iCal download', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_show_gcal', __( 'Share to Google Calendar', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_show_map', __( 'Link to Google Map', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_gmap', __( 'Google Map (single view only)', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_show_address', __( 'Location Address', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_short', __( 'Excerpt', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_desc', __( 'Description', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_image', __( 'Featured Image', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_event_registration', __( 'Registration settings', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_event_link', __( 'External link', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_display_more', __( 'More details link', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
						</ul>
						<p class="mc_gmap_api_key"><?php mc_settings_field( 'mc_gmap_api_key', __( 'Google Maps API Key', 'my-calendar' ), '', '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key">' . __( 'Create your Google Maps API key', 'my-calendar' ) . '</a>', array( 'id' => 'mc_gmap_id' ) ); ?></p>
					</fieldset>

					<fieldset>
						<legend><?php _e( 'Grid Options', 'my-calendar' ); ?></legend>
						<ul>
							<li><?php mc_settings_field( 'mc_show_weekends', __( 'Show Weekends on Calendar', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_title', __( 'Include event title in details pop-up', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li>
							<?php
							mc_settings_field(
								'mc_convert',
								__( 'Mobile View', 'my-calendar' ),
								array(
									'true' => __( 'Switch to list view', 'my-calendar' ),
									'mini' => __( 'Switch to mini calendar', 'my-calendar' ),
									'none' => __( 'No change', 'my-calendar' ),
								),
								'',
								array(),
								'select'
							);
							?>
							</li>
						</ul>
					</fieldset>

					<fieldset>
						<legend><?php _e( 'List Options', 'my-calendar' ); ?></legend>
						<ul>
							<li><?php mc_settings_field( 'mc_show_months', __( 'How many months of events to show at a time:', 'my-calendar' ), '', '', array( 'size' => '3' ), 'text' ); ?></li>
							<li><?php mc_settings_field( 'mc_show_list_info', __( 'Show the first event\'s title and the number of events that day next to the date.', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_show_list_events', __( 'Show all event titles next to the date.', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
						</ul>
					</fieldset>

					<fieldset>
						<legend><?php _e( 'Mini Calendar Options', 'my-calendar' ); ?></legend>
						<ul>
							<li><?php mc_settings_field( 'mc_mini_uri', __( 'Target link for mini calendar dates', 'my-calendar' ), '', '', array( 'size' => '60' ), 'url' ); ?></li>
							<?php
							$disabled = ( ! get_option( 'mc_uri' ) && ! get_option( 'mc_mini_uri' ) ) ? array( 'disabled' => 'disabled' ) : array();
							if ( ! empty( $disabled ) ) {
								// Ensure that this option is set to a valid value if no URI configured.
								update_option( 'mc_open_day_uri', 'false' );
							}
							?>
							<li>
							<?php
							mc_settings_field(
								'mc_open_day_uri',
								__( 'Link action for mini calendar', 'my-calendar' ),
								array(
									'false'          => __( 'Event pop-up ', 'my-calendar' ),
									'true'           => __( 'daily view page (above)', 'my-calendar' ),
									'listanchor'     => __( 'in-page anchor on main calendar page (list)', 'my-calendar' ),
									'calendaranchor' => __( 'in-page anchor on main calendar page (grid)', 'my-calendar' ),
								),
								'',
								$disabled,
								'select'
							);
							?>
							</li>
						</ul>
					</fieldset>

					<p><input type="submit" name="save" class="button-primary" value="<?php _e( 'Save Output Settings', 'my-calendar' ); ?>"/></p>
				</form>
			</div>
		</div>

		<div class="wptab postbox initial-hidden" aria-labelledby="tab_input" role="tabpanel" id="my-calendar-input">
			<h2><?php _e( 'Calendar Input Fields', 'my-calendar' ); ?></h2>

			<div class="inside">
				<form method="post" action="<?php echo admin_url( 'admin.php?page=my-calendar-config#my-calendar-input' ); ?>">
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
					<fieldset>
						<legend><?php _e( 'Event editing fields to show', 'my-calendar' ); ?></legend>
						<div><input type='hidden' name='mc_input' value='true'/></div>
						<ul class="checkboxes">
							<?php
							$output        = '';
							$input_options = get_option( 'mc_input_options' );
							$input_labels  = array(
								'event_short'    => __( 'Excerpt', 'my-calendar' ),
								'event_desc'     => __( 'Description', 'my-calendar' ),
								'event_category' => __( 'Categories', 'my-calendar' ),
								'event_image'    => __( 'Featured Image', 'my-calendar' ),
								'event_link'     => __( 'External Link', 'my-calendar' ),
								'event_recurs'   => __( 'Repetition Pattern', 'my-calendar' ),
								'event_open'     => __( 'Registration Settings', 'my-calendar' ),
								'event_location' => __( 'Event Location', 'my-calendar' ),
								'event_specials' => __( 'Special Scheduling Options', 'my-calendar' ),
								'event_access'   => __( 'Accessibility', 'my-calendar' ),
								'event_host'     => __( 'Host', 'my-calendar' ),
							);

							// If input options isn't an array, assume that plugin wasn't upgraded, and reset to default.
							// Array of all options in default position.
							$defaults = mc_input_defaults();
							if ( ! is_array( $input_options ) ) {
								update_option(
									'mc_input_options',
									$defaults
								);
								$input_options = get_option( 'mc_input_options' );
							}
							// Merge saved input options with default on, so all are displayed.
							$input_options = array_merge( $defaults, $input_options );
							asort( $input_labels );
							foreach ( $input_labels as $key => $value ) {
								$enabled = ( isset( $input_options[ $key ] ) ) ? $input_options[ $key ] : false;
								$checked = ( 'on' === $enabled ) ? "checked='checked'" : '';
								$output .= "<li><input type='checkbox' id='mci_$key' name='mci_$key' $checked /> <label for='mci_$key'>$value</label></li>";
							}
							echo $output;
							?>
							<li><?php mc_settings_field( 'mc_input_options_administrators', __( 'Administrators see all input options', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
						</ul>
					</fieldset>
					<p>
						<input type="submit" name="save" class="button-primary" value="<?php _e( 'Save Input Settings', 'my-calendar' ); ?>"/>
					</p>
				</form>
				<h3><?php _e( 'Location Controls', 'my-calendar' ); ?></h3>

				<?php echo mc_location_controls(); ?>
			</div>
		</div>

	<?php
	if ( current_user_can( 'manage_network' ) && is_multisite() ) {
		?>
		<div class="wptab postbox initial-hidden" aria-labelledby="tab_multi" role="tabpanel" id="my-calendar-multisite">
			<h2><?php _e( 'Multisite Settings (Network Administrators only)', 'my-calendar' ); ?></h2>

			<div class="inside">
				<p><?php _e( 'The central calendar is the calendar associated with the primary site in your WordPress Multisite network.', 'my-calendar' ); ?></p>
				<form method="post" action="<?php echo admin_url( 'admin.php?page=my-calendar-config#my-calendar-multisite' ); ?>">
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
					<input type='hidden' name='mc_network' value='true'/>
					<fieldset>
						<legend><?php _e( 'Multisite configuration - input', 'my-calendar' ); ?></legend>
						<ul>
							<li>
								<input type="radio" value="0" id="ms0" name="mc_multisite"<?php echo mc_option_selected( get_site_option( 'mc_multisite' ), '0' ); ?> /> <label for="ms0"><?php _e( 'Site owners may only post to their local calendar', 'my-calendar' ); ?></label>
							</li>
							<li>
								<input type="radio" value="1" id="ms1" name="mc_multisite"<?php echo mc_option_selected( get_site_option( 'mc_multisite' ), '1' ); ?> /> <label for="ms1"><?php _e( 'Site owners may only post to the central calendar', 'my-calendar' ); ?></label>
							</li>
							<li>
								<input type="radio" value="2" id="ms2" name="mc_multisite"<?php echo mc_option_selected( get_site_option( 'mc_multisite' ), 2 ); ?> /> <label for="ms2"><?php _e( 'Site owners may manage either calendar', 'my-calendar' ); ?></label>
							</li>
						</ul>
						<p>
							<em><?php _e( 'Changes only effect input permissions. Public-facing calendars will be unchanged.', 'my-calendar' ); ?></em>
						</p>
					</fieldset>
					<fieldset>
						<legend><?php _e( 'Multisite configuration - output', 'my-calendar' ); ?></legend>
						<ul>
							<li>
								<input type="radio" value="0" id="mss0" name="mc_multisite_show"<?php echo mc_option_selected( get_site_option( 'mc_multisite_show' ), '0' ); ?> />
								<label for="mss0"><?php _e( 'Sub-site calendars show events from their local calendar.', 'my-calendar' ); ?></label>
							</li>
							<li>
								<input type="radio" value="1" id="mss1" name="mc_multisite_show"<?php echo mc_option_selected( get_site_option( 'mc_multisite_show' ), '1' ); ?> />
								<label for="mss1"><?php _e( 'Sub-site calendars show events from the central calendar.', 'my-calendar' ); ?></label>
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
			<h2><?php _e( 'My Calendar Permissions', 'my-calendar' ); ?></h2>

			<div class="inside">
	<?php
	if ( current_user_can( 'administrator' ) ) {
		?>

					<form method="post" action="<?php echo admin_url( 'admin.php?page=my-calendar-config#my-calendar-permissions' ); ?>">
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
						<div class="mc-permissions-wrapper">
		<?php
		$tabs = '<div class="tabs" role="tablist" data-default="container_mc_editor">';
		global $wp_roles;
		$role_container = '';
		$roles          = $wp_roles->get_names();
		$caps           = array(
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
		foreach ( $roles as $role => $rolename ) {
			if ( 'administrator' === $role ) {
				continue;
			}
			$tabs           .= '<button type="button" role="tab" aria-selected="false" id="tab_' . $role . '" aria-controls="container_mc_' . $role . '">' . $rolename . '</button>';
			$role_container .= "<div role='tabpanel' aria-labelledby='tab_$role' class='wptab mc_$role mc_permissions' id='container_mc_$role'><fieldset id='mc_$role' class='roles'><legend>$rolename</legend>";
			$role_container .= "<input type='hidden' value='none' name='mc_caps[" . $role . "][none]' /><ul class='mc-settings checkboxes'>";
			foreach ( $caps as $cap => $name ) {
				$role_container .= mc_cap_checkbox( $role, $cap, $name );
			}
			$role_container .= '</ul></fieldset></div>';
		}
		$tabs .= '</div>';
		echo '<div class="mc-tabs vertical">';
		echo $tabs;
		echo $role_container;
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
			<h2><?php _e( 'Calendar Email Settings', 'my-calendar' ); ?></h2>

			<div class="inside">
				<form method="post" action="<?php echo admin_url( 'admin.php?page=my-calendar-config#my-calendar-email' ); ?>">
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
					<fieldset>
						<legend><?php _e( 'Email Notifications', 'my-calendar' ); ?></legend>
						<div><input type='hidden' name='mc_email' value='true'/></div>
						<ul>
							<li><?php mc_settings_field( 'mc_event_mail', __( 'Send Email Notifications when new events are added.', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_html_email', __( 'Send HTML email', 'my-calendar' ), '', '', array(), 'checkbox-single' ); ?></li>
							<li><?php mc_settings_field( 'mc_event_mail_to', __( 'Notification messages are sent to:', 'my-calendar' ), get_bloginfo( 'admin_email' ) ); ?></li>
							<li><?php mc_settings_field( 'mc_event_mail_from', __( 'Notification messages are sent from:', 'my-calendar' ), get_bloginfo( 'admin_email' ) ); ?></li>
							<li>
	<?php
	mc_settings_field(
		'mc_event_mail_bcc',
		__( 'BCC on notifications (one per line):', 'my-calendar' ),
		'',
		'',
		array(
			'cols' => 60,
			'rows' => 6,
		),
		'textarea'
	);
	?>
							</li>
							<li><?php mc_settings_field( 'mc_event_mail_subject', __( 'Email subject', 'my-calendar' ), get_bloginfo( 'name' ) . ': ' . __( 'New event added', 'my-calendar' ), '', array( 'size' => 60 ) ); ?></li>
							<li>
	<?php
	mc_settings_field(
		'mc_event_mail_message',
		__( 'Message Body', 'my-calendar' ),
		__( 'New Event:', 'my-calendar' ) . "\n{title}: {date}, {time} - {event_status}",
		"<br /><a href='" . admin_url( 'admin.php?page=my-calendar-templates#templates' ) . "'>" . __( 'Templating Help', 'my-calendar' ) . '</a>',
		array(
			'cols' => 60,
			'rows' => 6,
		),
		'textarea'
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

	<?php echo apply_filters( 'mc_after_settings', '' ); ?>

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
		$mc_location_controls = get_option( 'mc_location_controls' );

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
