<?php
/**
 * General utilities, not directly related to events display, management, or organization.
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
 * Switch sites in multisite environment.
 *
 * @return boolean
 */
function mc_switch_sites() {
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		if ( get_site_option( 'mc_multisite' ) === '2' && my_calendar_table() !== my_calendar_table( 'global' ) ) {
			if ( mc_get_option( 'current_table' ) === '1' ) {
				// can post to either, but is currently set to post to central table.
				return true;
			}
		} elseif ( get_site_option( 'mc_multisite' ) === '1' && my_calendar_table() !== my_calendar_table( 'global' ) ) {
			// can only post to central table.
			return true;
		}
	}

	return false;
}

/**
 * Send a Tweet on approval of event
 *
 * @param string $previous_status Previous status.
 * @param string $new_status New status.
 *
 * @return void
 */
function mc_tweet_approval( $previous_status, $new_status ) {
	if ( function_exists( 'wpt_post_to_twitter' ) && isset( $_POST['mc_twitter'] ) && trim( $_POST['mc_twitter'] ) !== '' ) {
		if ( ( 0 === (int) $previous_status || 2 === (int) $previous_status ) && 1 === (int) $new_status ) {
			wpt_post_to_twitter( stripslashes( $_POST['mc_twitter'] ) );
		}
	}
}

/**
 * Flatten event array; need an array that isn't multi dimensional
 * Once used in upcoming events?
 *
 * @param array $events Array of events.
 *
 * @return array<int, mixed>
 */
function mc_flatten_array( $events ) {
	$new_array = array();
	if ( is_array( $events ) ) {
		foreach ( $events as $event ) {
			foreach ( $event as $e ) {
				$new_array[] = $e;
			}
		}
	}

	return $new_array;
}

add_action( 'admin_menu', 'mc_add_outer_box' );
/**
 * Add meta boxes
 *
 * @return void
 */
function mc_add_outer_box() {
	add_meta_box( 'mcs_add_event', __( 'My Calendar Event', 'my-calendar' ), 'mc_add_inner_box', 'mc-events', 'side', 'high' );
}

/**
 * Add inner metabox
 *
 * @return void
 */
function mc_add_inner_box() {
	global $post;
	$event_id = get_post_meta( $post->ID, '_mc_event_id', true );
	if ( $event_id ) {
		$url     = admin_url( 'admin.php?page=my-calendar&mode=edit&event_id=' . $event_id );
		$event   = mc_get_first_event( $event_id );
		$content = '<p><strong>' . strip_tags( $event->event_title, mc_strip_tags() ) . '</strong><br />' . $event->event_begin . ' @ ' . $event->event_time . '</p>';
		if ( ! mc_is_recurring( $event ) ) {
			$recur    = mc_event_recur_string( $event, $event->event_begin );
			$content .= wpautop( $recur );
		}
		$elabel = '';
		if ( property_exists( $event, 'location' ) && is_object( $event->location ) ) {
			$elabel = $event->location->location_label;
		}
		if ( '' !== $elabel ) {
			// Translators: Name of event location.
			$content .= '<p>' . sprintf( __( '<strong>Location:</strong> %s', 'my-calendar' ), strip_tags( $elabel, mc_strip_tags() ) ) . '</p>';
		}
		// Translators: Event URL.
		$content .= '<p>' . sprintf( __( '<a href="%s">Edit event</a>.', 'my-calendar' ), $url ) . '</p>';

		echo $content;
	}
}

/**
 * Pass group of allowed tags to strip_tags
 *
 * @return string Allowed tags parseable by strip_tags.
 */
function mc_strip_tags() {

	return apply_filters( 'mc_strip_tags', '<strong><em><i><b><span><br><a><time><img>' );
}

/**
 * Pass group of allowed tags to strip_tags
 *
 * @return string Allowed tags parseable by strip_tags in admin.
 */
function mc_admin_strip_tags() {

	return '<strong><em><i><b><span><a><code><pre><br>';
}

if ( ! function_exists( 'exif_imagetype' ) ) {
	/**
	 * This is a hack for people who don't have PHP installed with exif_imagetype
	 *
	 * @param string $filename Name of file.
	 *
	 * @return string|bool type of file.
	 */
	function exif_imagetype( $filename ) {
		if ( ! is_dir( $filename ) && ( list( $width, $height, $type, $attr ) = getimagesize( $filename ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.NonVariableAssignmentFound
			return $type;
		}

		return false;
	}
}

/**
 * Return default state of link expiration checkbox. Replaces option.
 *
 * @return bool Default false.
 */
function mc_event_link_expires() {
	$return  = false;
	$default = get_option( 'mc_event_link_expires' ); // Option only exists prior to 3.3.0.
	if ( 'true' === $default ) {
		$return = true;
	}

	return apply_filters( 'mc_event_link_expires', $return );
}

/**
 * Return default state of fifth week checkbox. Replaces option.
 *
 * @return bool Default true.
 */
function mc_no_fifth_week() {
	$return  = true;
	$default = get_option( 'mc_no_fifth_week' ); // Option only exists prior to 3.3.0.
	if ( 'false' === $default ) {
		$return = false;
	}

	return apply_filters( 'mc_no_fifth_week', $return );
}

/**
 * Return default state of skip holidays checkbox. Replaces option.
 *
 * @return bool Default false.
 */
function mc_skip_holidays() {
	$return  = false;
	$default = get_option( 'mc_skip_holidays' ); // Option only exists before 3.3.0.
	if ( 'true' === $default ) {
		$return = true;
	}

	return apply_filters( 'mc_skip_holidays', $return );
}

/**
 * Checks the contrast ratio of color & returns the optimal color to use with it.
 *
 * @param string $color hex value.
 *
 * @return string white or black hex value
 */
function mc_inverse_color( $color ) {
	$color = str_replace( '#', '', $color );
	if ( strlen( $color ) !== 6 ) {
		return '#000000';
	}
	$rgb       = '';
	$total     = 0;
	$red       = 0.299 * ( 255 - hexdec( substr( $color, 0, 2 ) ) );
	$green     = 0.587 * ( 255 - hexdec( substr( $color, 2, 2 ) ) );
	$blue      = 0.114 * ( 255 - hexdec( substr( $color, 4, 2 ) ) );
	$luminance = 1 - ( ( $red + $green + $blue ) / 255 );
	if ( $luminance < 0.5 ) {
		return '#ffffff';
	} else {
		return '#000000';
	}
}

/**
 * Shift color to an acceptable alternate color. Shifts dark colors darker and light colors lighter.
 *
 * @param string $color Color hex.
 *
 * @return string New color hex
 */
function mc_shift_color( $color ) {
	$color   = str_replace( '#', '', $color );
	$rgb     = '';
	$percent = ( mc_inverse_color( $color ) === '#ffffff' ) ? - 20 : 20;
	$per     = $percent / 100 * 255;
	// Percentage to work with. Change middle figure to control color temperature.
	if ( $per < 0 ) {
		// DARKER.
		$per = abs( $per ); // Turns Neg Number to Pos Number.
		for ( $x = 0; $x < 3; $x++ ) {
			$c    = hexdec( substr( $color, ( 2 * $x ), 2 ) ) - $per;
			$c    = ( $c < 0 ) ? 0 : dechex( $c );
			$rgb .= ( strlen( $c ) < 2 ) ? '0' . $c : $c;
		}
	} else {
		// LIGHTER.
		for ( $x = 0; $x < 3; $x++ ) {
			$c    = hexdec( substr( $color, ( 2 * $x ), 2 ) ) + $per;
			$c    = ( $c > 255 ) ? 'ff' : dechex( $c );
			$rgb .= ( strlen( $c ) < 2 ) ? '0' . $c : $c;
		}
	}

	return '#' . $rgb;
}

/**
 * Convert a CSV string into an array
 *
 * @param string $csv Data.
 * @param string $delimiter to use.
 * @param string $enclosure to wrap strings.
 * @param string $escape character.
 * @param string $terminator end of line character.
 *
 * @return array
 */
function mc_csv_to_array( $csv, $delimiter = ',', $enclosure = '"', $escape = '\\', $terminator = "\n" ) {
	$r    = array();
	$rows = explode( $terminator, trim( $csv ) );
	foreach ( $rows as $row ) {
		if ( trim( $row ) ) {
			$values          = explode( $delimiter, $row );
			$r[ $values[0] ] = ( isset( $values[1] ) ) ? str_replace( array( $enclosure, $escape ), '', $values[1] ) : $values[0];
		}
	}

	return $r;
}

/**
 * Return string for HTML email types
 */
function mc_html_type() {

	return 'text/html';
}

/**
 * Test if a string is a properly formatted URL.
 *
 * @param string $url URL.
 *
 * @return int|false URL, if valid.
 */
function _mc_is_url( $url ) {

	return preg_match( '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url );
}

/**
 * Check whether a link is external
 *
 * @param string $link URL.
 *
 * @return boolean true if not on current host
 */
function mc_external_link( $link ) {
	if ( ! _mc_is_url( $link ) ) {
		return true; // If this is not a valid URL, consider it to be external.
	}

	$url   = parse_url( $link );
	$host  = $url['host'];
	$site  = parse_url( get_option( 'siteurl' ) );
	$known = $site['host'];

	if ( false === strpos( $host, $known ) ) {
		return true;
	}

	return false;
}

/**
 * Replace newline characters in a string
 *
 * @param string $input_string Any string.
 *
 * @return string string without newline chars
 */
function mc_newline_replace( $input_string ) {

	return (string) str_replace( array( "\r", "\r\n", "\n" ), '', $input_string );
}

/**
 * Reverse the order of an array
 *
 * @param array   $input_array Any array.
 * @param boolean $boolean true or false arguments for array_reverse.
 * @param string  $order sort order to use.
 *
 * @return array
 */
function reverse_array( $input_array, $boolean, $order ) {
	if ( 'desc' === $order ) {
		return array_reverse( $input_array, $boolean );
	}
	return $input_array;
}

/**
 * Debugging handler shortcut
 *
 * @param string $subject Text for email subject.
 * @param string $body Text for email body.
 * @param string $email target email (if sending via email).
 */
function mc_debug( $subject, $body, $email = '' ) {
	if ( defined( 'MC_DEBUG' ) && true === MC_DEBUG ) {
		if ( ! $email ) {
			$email = get_option( 'admin_email' );
		}
		if ( defined( 'MC_DEBUG_METHOD' ) && 'email' === MC_DEBUG_METHOD ) {
			wp_mail( get_option( 'admin_email' ), $subject, print_r( $body ) );
		} else {
			/**
			 * Execute a custom debug action during an mc_debug call. Runs if MC_DEBUG_METHOD is not 'email'.
			 *
			 * @hook mc_debug
			 *
			 * @param {string} $subject Subject line of email debugging message.
			 * @param {string} $body Body of email debugging message.
			 */
			do_action( 'mc_debug', $subject, $body );
		}
	}
}

/**
 * Get users as options in a select
 *
 * @param string $selected Group of selected users. Comma-separated IDs.
 * @param string $group Type of roles to fetch.
 * @param string $return_type Type of return; string of select options or array.
 *
 * @return string|array <option> elements or an array of possible values.
 */
function mc_selected_users( $selected = '', $group = 'authors', $return_type = 'select' ) {
	/**
	 * Filter the list of users used to select authors or hosts.
	 *
	 * @hook mc_custom_user_select
	 *
	 * @param {string}     $output Output that should replace data.
	 * @param {string|int} $selected The currently selected user.
	 * @param {string}     $group Whether this function is returning hosts or authors.
	 * @param {string}     $return Whether this should return fully realized <option> values or an array of data.
	 *
	 * @return {string|array}
	 */
	$options = apply_filters( 'mc_custom_user_select', '', $selected, $group, $return_type );
	if ( '' !== $options ) {
		return $options;
	}
	$selected = explode( ',', $selected );
	$users    = mc_get_users( $group );
	$values   = array();
	foreach ( $users as $u ) {
		if ( in_array( $u->ID, $selected, true ) ) {
			$checked = ' selected="selected"';
		} else {
			$checked = '';
		}
		$display_name = ( '' === $u->display_name ) ? $u->user_nicename : $u->display_name;
		$options     .= '<option value="' . $u->ID . '"' . $checked . ">$display_name</option>\n";
		$values[]     = array(
			'value' => $u->ID,
			'label' => $display_name,
		);
	}

	return ( 'select' === $return_type ) ? $options : $values;
}

/**
 * Get users.
 *
 * @param string $group Not used except in filters.
 *
 * @return array of users
 */
function mc_get_users( $group = 'authors' ) {
	global $blog_id;
	$users = apply_filters( 'mc_get_users', false, $group, $blog_id );
	if ( $users ) {
		return $users;
	}
	$count = count_users( 'time' );
	$args  = array(
		'blog_id' => $blog_id,
		'orderby' => 'display_name',
		'fields'  => array( 'ID', 'user_nicename', 'display_name' ),
	);
	$args  = apply_filters( 'mc_filter_user_arguments', $args, $count, $group );
	$users = new WP_User_Query( $args );

	return $users->get_results();
}

/**
 * Display an update message.
 *
 * @param string         $message Update message.
 * @param boolean        $display Echo or return. Default true (echo).
 * @param boolean|string $code Message code.
 *
 * @return string
 */
function mc_show_notice( $message, $display = true, $code = false ) {
	if ( trim( $message ) === '' ) {
		return '';
	}
	$message = strip_tags( apply_filters( 'mc_filter_notice', $message, $code ), mc_admin_strip_tags() );
	$message = "<div class='updated'><p>$message</p></div>";
	if ( $display ) {
		echo wp_kses_post( $message );
	}
	return $message;
}

/**
 * Display an error message.
 *
 * @param string         $message Error message.
 * @param boolean        $display Echo or return. Default true (echo).
 * @param boolean|string $code Message code.
 *
 * @return string
 */
function mc_show_error( $message, $display = true, $code = false ) {
	if ( trim( $message ) === '' ) {
		return '';
	}
	$message = strip_tags( apply_filters( 'mc_filter_error', $message, $code ), mc_admin_strip_tags() );
	$message = "<div class='error'><p>$message</p></div>";
	if ( $display ) {
		echo $message;
	}
	return $message;
}
