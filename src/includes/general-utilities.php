<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

function mc_switch_sites() {
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		if ( get_site_option( 'mc_multisite' ) == 2 && my_calendar_table() != my_calendar_table( 'global' ) ) {
			if ( get_option( 'mc_current_table' ) == '1' ) {
				// can post to either, but is currently set to post to central table
				return true;
			}
		} else if ( get_site_option( 'mc_multisite' ) == 1 && my_calendar_table() != my_calendar_table( 'global' ) ) {
			// can only post to central table
			return true;
		}
	}

	return false;
}


function mc_tweet_approval( $prev, $new ) {
	if ( function_exists( 'jd_doTwitterAPIPost' ) && isset( $_POST['mc_twitter'] ) && trim( $_POST['mc_twitter'] ) != '' ) {
		if ( ( $prev == 0 || $prev == 2 ) && $new == 1 ) {
			jd_doTwitterAPIPost( stripslashes( $_POST['mc_twitter'] ) );
		}
	}
}


/**
 * Flatten event array; need an array that isn't multi dimensional
 * Once used in upcoming events?
 */
function mc_flatten_array( $events ) {
	$new_array = array();
	if ( is_array( $events ) ) {
		foreach( $events as $event ) {
			foreach( $event as $e ) {
				$new_array[] = $e;
			}
		}
	}
	
	return $new_array;
}

add_action( 'admin_menu', 'mc_add_outer_box' );

// begin add boxes
function mc_add_outer_box() {
	add_meta_box( 'mcs_add_event', __('My Calendar Event', 'my-calendar'), 'mc_add_inner_box', 'mc-events', 'side','high' );
}

function mc_add_inner_box() {
	global $post;
	$event_id = get_post_meta( $post->ID, '_mc_event_id', true );
	if ( $event_id ) {
		$url     = admin_url( 'admin.php?page=my-calendar&mode=edit&event_id='.$event_id );
		$event   = mc_get_first_event( $event_id );
		$content = "<p><strong>" . strip_tags( $event->event_title, mc_strip_tags() ) . '</strong><br />' . $event->event_begin . ' @ ' . $event->event_time . "</p>";
		if ( $event->event_label != '' ) {
			$content .= "<p>" . sprintf( __( '<strong>Location:</strong> %s', 'my-calendar' ), strip_tags( $event->event_label, mc_strip_tags() ) ) . "</p>";
		}
		$content .= "<p>" . sprintf( __( '<a href="%s">Edit event</a>.', 'my-calendar' ), $url ) . "</p>";
		
		echo $content;
	} 
}

function mc_strip_tags() {
	return '<strong><em><i><b><span>';
}

function mc_is_checked( $theFieldname, $theValue, $theArray = '', $return = false ) {
	if ( ! is_array( get_option( $theFieldname ) ) ) {
		if ( get_option( $theFieldname ) == $theValue ) {
			if ( $return ) {
				return 'checked="checked"';
			} else {
				echo 'checked="checked"';
			}
		}
	} else {
		$theSetting = get_option( $theFieldname );
		if ( ! empty( $theSetting[ $theArray ]['enabled'] ) && $theSetting[ $theArray ]['enabled'] == $theValue ) {
			if ( $return ) {
				return 'checked="checked"';
			} else {
				echo 'checked="checked"';
			}
		}
	}
}

function mc_is_selected( $theFieldname, $theValue, $theArray = '' ) {
	if ( ! is_array( get_option( $theFieldname ) ) ) {
		if ( get_option( $theFieldname ) == $theValue ) {
			return 'selected="selected"';
		}
	} else {
		$theSetting = get_option( $theFieldname );
		if ( $theSetting[ $theArray ]['enabled'] == $theValue ) {
			return 'selected="selected"';
		}
	}

	return '';
}

function mc_option_selected( $field, $value, $type = 'checkbox' ) {
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
	if ( $field == $value ) {
		$output = $result;
	} else {
		$output = '';
	}

	return $output;
}

function jd_option_selected( $field, $value, $type = 'checkbox' ) {
	return mc_option_selected( $field, $value, $type );
}

// This is a hack for people who don't have PHP installed with exif_imagetype
if ( ! function_exists( 'exif_imagetype' ) ) {
	function exif_imagetype( $filename ) {
		if ( ! is_dir( $filename ) && ( list( $width, $height, $type, $attr ) = getimagesize( $filename ) ) !== false ) {
			return $type;
		}

		return false;
	}
}



function mc_inverse_color( $color ) {
	$color = str_replace( '#', '', $color );
	if ( strlen( $color ) != 6 ) {
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

function mc_shift_color( $color ) {
	$color   = str_replace( '#', '', $color );
	$rgb     = ''; // Empty variable
	$percent = ( mc_inverse_color( $color ) == '#ffffff' ) ? - 20 : 20;
	$per     = $percent / 100 * 255; // Creates a percentage to work with. Change the middle figure to control colour temperature
	if ( $per < 0 ) {
		// DARKER
		$per = abs( $per ); // Turns Neg Number to Pos Number
		for ( $x = 0; $x < 3; $x ++ ) {
			$c = hexdec( substr( $color, ( 2 * $x ), 2 ) ) - $per;
			$c = ( $c < 0 ) ? 0 : dechex( $c );
			$rgb .= ( strlen( $c ) < 2 ) ? '0' . $c : $c;
		}
	} else {
		// LIGHTER        
		for ( $x = 0; $x < 3; $x ++ ) {
			$c = hexdec( substr( $color, ( 2 * $x ), 2 ) ) + $per;
			$c = ( $c > 255 ) ? 'ff' : dechex( $c );
			$rgb .= ( strlen( $c ) < 2 ) ? '0' . $c : $c;
		}
	}

	return '#' . $rgb;
}

/**
 * Convert a CSV string into an array
 */
function mc_csv_to_array( $csv, $delimiter = ',', $enclosure = '"', $escape = '\\', $terminator = "\n" ) {
	$r    = array();
	$rows = explode( $terminator, trim( $csv ) );
	foreach ( $rows as $row ) {
		if ( trim( $row ) ) {
			$values          = explode( $delimiter, $row );
			$r[ $values[0] ] = ( isset( $values[1] ) ) ? str_replace( array( $enclosure, $escape ), '', $values[1] ) : $values[0] ;
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

// duplicate of mc_is_url, which really should have been in this file. Bugger.
function _mc_is_url( $url ) {
	return preg_match( '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url );
}

/**
 * Check whether a link is external
 *
 * @param string $link URL
 *
 * @return boolean true if not on current host
 */
function mc_external_link( $link ) {
	if ( ! _mc_is_url( $link ) ) {
		return "class='error-link'";
	}

	$url   = parse_url( $link );
	$host  = $url['host'];
	$site  = parse_url( get_option( 'siteurl' ) );
	$known = $site['host'];
	
	if ( strpos( $host, $known ) === false ) {
		return true;
	}

	return false;
}

/**
 * Replace newline characters in a string
 *
 * @param string $string
 * 
 * @return string string without newline chars
 */
function mc_newline_replace( $string ) {
	return (string) str_replace( array( "\r", "\r\n", "\n" ), '', $string );
}

/**
 * Reverse the order of an array
 *
 * @param array $array Any array
 * @param boolean $boolean true or false arguments for array_reverse
 * @param string $order sort order to use
 *
 * @return array
 */
function reverse_array( $array, $boolean, $order ) {
	if ( $order == 'desc' ) {
		return array_reverse( $array, $boolean );
	} else {
		return $array;
	}
}

/**
 * Debugging handler shortcut
 *
 * @param string $subjecct
 * @param string $body
 * @param string $email target email (if sending via email)
 */
function mc_debug( $subject, $body, $email = false ) {
	if ( defined( 'MC_DEBUG' ) && MC_DEBUG == true ) {
		if ( ! $email ) {
			$email = get_option( 'admin_email' );
		}
		if ( defined( 'MC_DEBUG_METHOD' ) && MC_DEBUG_METHOD == 'email' ) {
			wp_mail( get_option( 'admin_email' ), $subject, print_r( $body ) );
		} else {
			do_action( 'mc_debug', $subject, $body );
		}
	}
}

/**
 * Drop a table
 *
 * @param string name of function used to call table name
 */
function mc_drop_table( $table ) {
	global $wpdb;
	$sql = "DROP TABLE " . $table();
	$wpdb->query( $sql );
}