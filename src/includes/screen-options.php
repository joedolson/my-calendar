<?php
/**
 * Implement screen options on pages where needed.
 *
 * @category Utilities
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implement show on page selection fields.
 *
 * @return void
 */
function mc_event_editing() {
	$args = array(
		'label'   => __( 'Event editing fields to show', 'my-calendar' ),
		'default' => mc_get_option( 'input_options' ),
		'option'  => 'mc_show_on_page',
	);
	add_screen_option( 'mc_show_on_page', $args );
}

/**
 * Get event input default values.
 *
 * @return array<string, string>
 */
function mc_input_defaults() {
	return apply_filters(
		'mc_input_defaults',
		array(
			'event_short'    => 'on',
			'event_desc'     => 'on',
			'event_category' => 'on',
			'event_image'    => 'on',
			'event_link'     => 'on',
			'event_recurs'   => 'on',
			'event_open'     => 'on',
			'event_location' => 'on',
			'event_access'   => 'on',
			'event_host'     => 'on',
		)
	);
}

add_filter( 'screen_settings', 'mc_show_event_editing', 10, 2 );
/**
 * Show event editing options for user
 *
 * @param string $status string.
 * @param object $screen Object - screen object.
 *
 * @return string
 */
function mc_show_event_editing( $status, $screen ) {
	$return = $status;
	if ( 'toplevel_page_my-calendar' === $screen->base ) {
		$input_options    = get_user_meta( get_current_user_id(), 'mc_show_on_page', true );
		$settings_options = mc_get_option( 'input_options' );
		if ( ! is_array( $input_options ) ) {
			$input_options = $settings_options;
		}
		$defaults = mc_input_defaults();

		$input_options = array_merge( $defaults, $input_options );
		// cannot change these keys.
		$input_labels = array(
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

		$output = '';
		asort( $input_labels );
		foreach ( $input_labels as $key => $value ) {
			$enabled = ( isset( $input_options[ $key ] ) ) ? $input_options[ $key ] : false;
			$checked = ( 'on' === $enabled ) ? "checked='checked'" : '';
			$allowed = ( isset( $settings_options[ $key ] ) && 'on' === $settings_options[ $key ] ) ? true : false;
			if ( current_user_can( 'manage_options' ) || $allowed ) {
				$output .= "<label for='mci_$key'><input type='checkbox' id='mci_$key' name='mc_show_on_page[$key]' value='on' $checked /> $value</label>";
			} else {
				// don't display options if this user can't use them.
				$output .= "<input type='hidden' name='mc_show_on_page[$key]' value='off' />";
			}
		}
		$button  = get_submit_button( __( 'Apply', 'my-calendar' ), 'button', 'screen-options-apply', false );
		$return .= '
	<fieldset>
	<legend>' . __( 'Event editing fields to show', 'my-calendar' ) . "</legend>
	<div class='metabox-prefs'>
		<div><input type='hidden' name='wp_screen_options[option]' value='mc_show_on_page' /></div>
		<div><input type='hidden' name='wp_screen_options[value]' value='yes' /></div>
		$output
	</div>
	</fieldset>
	<br class='clear'>
	$button";
	}

	return $return;
}

add_filter( 'set-screen-option', 'mc_set_event_editing', 11, 3 );
/**
 * Save settings for screen options
 *
 * @param string       $status string.
 * @param string       $option option name.
 * @param array|string $value rows to use.
 *
 * @return array<string>|string
 */
function mc_set_event_editing( $status, $option, $value ) {
	if ( 'mc_show_on_page' === $option ) {
		$defaults = mc_input_defaults();
		$value    = array();
		foreach ( $defaults as $k => $v ) {
			if ( isset( $_POST['mc_show_on_page'][ $k ] ) ) {
				$value[ $k ] = 'on';
			} else {
				$value[ $k ] = 'off';
			}
		}
		update_user_meta( get_current_user_ID(), 'mc_show_on_page', $value );
	}

	return $value;
}

/**
 * Add the screen option for num per page.
 *
 * @return void
 */
function mc_add_screen_option() {
	$items_per_page = apply_filters( 'mc_num_per_page_default', 50 );
	$option         = 'per_page';
	$args           = array(
		'label'   => 'Events',
		'default' => $items_per_page,
		'option'  => 'mc_num_per_page',
	);
	add_screen_option( $option, $args );
}

/**
 * Add help tab on events.
 *
 * @return void
 */
function mc_add_help_tab() {
	$screen  = get_current_screen();
	$content = '<ul>
			<li>' . __( '<strong>Published</strong>: Events are live and visible.', 'my-calendar' ) . '</li>
			<li>' . __( '<strong>Draft</strong>: Events in progress, not visible.', 'my-calendar' ) . '</li>
			<li>' . __( '<strong>Trash</strong>: Events intended for deletion, not visible.', 'my-calendar' ) . '</li>
			<li>' . __( '<strong>Archive</strong>: Events still visible, but removed from "Published" list.', 'my-calendar' ) . '</li>
			<li>' . __( '<strong>Spam</strong>: Events identified as potential spam, not visible.', 'my-calendar' ) . '</li>
			<li>' . __( '<strong>Private</strong>: In a private category, only visible to logged-in users.', 'my-calendar' ) . '</li>
			<li>' . __( '<strong>Invalid</strong>: There is something wrong with the dates assigned for this event, and it should be checked.', 'my-calendar' ) . '</li>
		</ul>';

	$screen->add_help_tab(
		array(
			'id'      => 'mc_help_tab',
			'title'   => __( 'Event Statuses', 'my-calendar' ),
			'content' => $content,
		)
	);
}

/**
 * Add help tab on locations.
 *
 * @return void
 */
function mc_location_help_tab() {
	$screen       = get_current_screen();
	$settings_url = admin_url( 'admin.php?page=my-calendar-config#my-calendar-input' );
	$content      = '<h2>' . __( 'Merge Duplicates', 'my-calendar' ) . '</h2><ul><li>' . __( 'Check the locations you wish to remove', 'my-calendar' ) . '</li><li>' . __( 'Check the "Merge Duplicates" option at the top of the table', 'my-calendar' ) . '</li><li>' . __( 'Enter the ID of the location you want to have replace these locations', 'my-calendar' ) . '</li><li>' . __( 'Submit the form', 'my-calendar' ) . '</li></ul><p>' . __( 'The checked locations will be deleted. Events using those locations will be updated to use the provided ID', 'my-calendar' ) . '</p>';
	// Translators: URL to change location controls.
	$limits = '<h2>' . __( 'Location Controls', 'my-calendar' ) . '</h2><p>' . sprintf( __( 'You can limit the values available for locations using the <a href="%s">Location Control</a> settings at <code>My Calendar > Settings > Inputs</code>.', 'my-calendar' ), $settings_url ) . '</p>';
	$screen->add_help_tab(
		array(
			'id'      => 'mc_location_help_tab',
			'title'   => __( 'Merging Duplicate Locations', 'my-calendar' ),
			'content' => $content,
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mc_location_help_tab_limits',
			'title'   => __( 'Limit Location Inputs', 'my-calendar' ),
			'content' => $limits,
		)
	);
}
