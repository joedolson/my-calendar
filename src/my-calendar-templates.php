<?php
/**
 * Draw templates for My Calendar events.
 *
 * @category Calendar
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Echoing version of mc_draw_template(). Draws and prints a template.
 *
 * @param array  $tags associative array of information to be parsed.
 * @param string $template Template containing tags wrapped in curly braces using keys of passed array.
 * @param string $type Information about view type to inform templates of variations in rendering.
 */
function mc_template( $tags, $template, $type = 'list' ) {
	echo wp_kses( mc_draw_template( $tags, $template, $type ), mc_kses_elements() );
}

/**
 * Draw array of information into a template with {$key} formatted tags
 *
 * @param array       $data associative array of information to be parsed.
 * @param string      $template template containing braced tags (e.g. `{title}`) using keys of passed array.
 * @param string      $type my calendar needs to render a different link for list versions and other views.
 * @param object|bool $event Event object. Optional.
 *
 * @return string HTML output of template.
 */
function mc_draw_template( $data, $template, $type = 'list', $event = false ) {
	if ( is_object( $event ) ) {
		$description_fields = array( 'excerpt', 'description', 'description_raw', 'description_stripped', 'ical_excerpt', 'shortdesc', 'shortdesc_raw', 'shortdesc_stripped', 'ical_desc' );
		/**
		 * Filter fields that `mc_inner_content` runs on in templates.
		 *
		 * @hook mc_inner_content_template_fields
		 *
		 * @param {array}  $description_fields Array of template tags containing event description data.
		 * @param {object} $event Event object.
		 *
		 * @return {array}
		 */
		$description_fields = apply_filters( 'mc_inner_content_template_fields', $description_fields, $event );
		foreach ( $description_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				/**
				 * Filter event content inside wrapper.
				 *
				 * @hook mc_inner_content
				 *
				 * @param {string} $detail Event details.
				 * @param {object} $event My Calendar event object.
				 * @param {string} $type View type.
				 * @param {string} $time View timeframe. Empty when run as a pre-templating filter.
				 *
				 * @return {string} Returning any non-empty string will shortcircuit template drawing.
				 */
				$data[ $field ] = apply_filters( 'mc_inner_content', $data[ $field ], $event, $type, '' );
			}
		}
	}
	$template = stripcslashes( $template );
	// If there are no brace characters, there is nothing to replace.
	if ( strpos( $template, '{' ) === false ) {
		return trim( $template );
	}
	// If the data passed is not an array or is empty, return empty string.
	if ( ! is_array( $data ) || empty( $data ) ) {
		return '';
	}
	foreach ( $data as $key => $value ) {
		if ( is_object( $value ) ) {
			// If a value is an object, ignore it.
		} else {
			if ( strpos( $template, '{' . $key ) !== false ) {
				if ( 'list' !== $type ) {
					if ( 'link' === $key && '' === $value ) {
						$value = mc_get_uri( false, $data );
					}
					if ( 'guid' !== $key ) {
						$value = htmlentities( $value );
					}
				}
				if ( strpos( $template, '{' . $key . ' ' ) !== false ) {
					// only do preg_match if appropriate.
					preg_match_all( '/{' . $key . '\b(?>\s+(?:before="([^"]*)"|after="([^"]*)"|format="([^"]*)")|[^\s]+|\s+){0,3}}/', $template, $matches, PREG_PATTERN_ORDER );
					if ( $matches ) {
						$number = count( $matches[0] );
						for ( $i = 0; $i < $number; $i++ ) {
							$orig   = $value;
							$before = $matches[1][ $i ];
							$after  = $matches[2][ $i ];
							$format = $matches[3][ $i ];
							if ( '' !== $format ) {
								$value = date_i18n( stripslashes( $format ), strtotime( stripslashes( $value ) ) );
							}
							$value    = ( '' === (string) trim( $value ) ) ? '' : $before . $value . $after;
							$search   = $matches[0][ $i ];
							$template = str_replace( $search, $value, $template );
							$value    = $orig;
						}
					}
				} else {
					// don't do preg match for simple templates.
					$template = stripcslashes( str_replace( '{' . $key . '}', $value, $template ) );
				}
			}
			// End {$key check.
		}
	}
	/**
	 * Filter a rendered My Calendar template after parsing.
	 *
	 * @hook mc_template
	 *
	 * @param {string} $template Formatted HTML output of a template.
	 * @param {array}  $array Array of arguments passed to template.
	 * @param {string} $type Type of view being rendered.
	 * @param {object} $event Event object.
	 *
	 * @return {string} Formatted HTML.
	 */
	$template = apply_filters( 'mc_template', $template, $data, $type, $event );

	return stripslashes( trim( $template ) );
}

/**
 * Set up a template based on a reference passed in shortcode or settings.
 *
 * @param string $template Template passed.
 * @param string $default_template Default template for this context.
 *
 * @return string
 */
function mc_setup_template( $template, $default_template ) {
	// allow reference by file to external template.
	$template = ( 'default' === $template ) ? '' : $template;
	if ( '' !== $template && mc_file_exists( $template ) ) {
		$template = file_get_contents( mc_get_file( $template ) );
	}
	if ( mc_key_exists( $template ) ) {
		$template = mc_get_custom_template( $template );
	}

	return ( '' !== $template ) ? $template : $default_template;
}

/**
 * Setup string version of address data
 *
 * @param object $event object containing location properties.
 * @param string $source event or location.
 *
 * @return string stringified address info
 */
function mc_map_string( $event, $source = 'event' ) {
	if ( ! is_object( $event ) ) {
		return '';
	}
	$event = mc_get_event_location( $event, $source );
	if ( $event ) {
		$map_string = $event->location_street . ' ' . $event->location_street2 . ' ' . $event->location_city . ' ' . $event->location_state . ' ' . $event->location_postcode . ' ' . $event->location_country;
	}

	return $map_string;
}

/**
 * Set up link to Mapping service.
 *
 * @param object $event object containing location properties.
 * @param string $request source of request.
 * @param string $source event/location.
 *
 * @return string URL or link depending on request
 */
function mc_maplink( $event, $request = 'map', $source = 'event' ) {
	$event = mc_get_event_location( $event, $source );
	if ( ! $event ) {
		return '';
	}
	$map_string = mc_map_string( $event, $source );
	// If the string is empty, then exit.
	if ( '' === $map_string ) {
		return '';
	}
	$map_target = mc_get_option( 'map_service' );
	if ( 'gcal' === $request ) {
		return $map_string;
	}

	$url        = $event->location_url;
	$map_label  = strip_tags( stripslashes( ( '' !== trim( $event->location_label ) ) ? $event->location_label : '' ), mc_strip_tags() );
	$zoom       = ( '0' !== $event->location_zoom ) ? $event->location_zoom : '15';
	$map_string = str_replace( ' ', '+', $map_string );
	if ( '0.000000' !== $event->location_longitude && '0.000000' !== $event->location_latitude && 'mapquest' !== $map_target ) {
		$dir_lat    = ( $event->location_latitude > 0 ) ? 'N' : 'S';
		$latitude   = abs( $event->location_latitude );
		$dir_long   = ( $event->location_longitude > 0 ) ? 'E' : 'W';
		$longitude  = abs( $event->location_longitude );
		$map_string = $latitude . $dir_lat . ',' . $longitude . $dir_long;
	}
	// Translators: Name of location.
	$label = sprintf( __( 'Map<span> to %s</span>', 'my-calendar' ), $map_label );
	/**
	 * Label for link to Maps for event location.
	 *
	 * @hook mc_map_label
	 *
	 * @param {string} $label Map to {event name}.
	 * @param {string} $map_label Location name.
	 *
	 * @return {string} Label used inside map link.
	 */
	$label = apply_filters( 'mc_map_label', $label, $map_label );
	if ( strlen( trim( $map_string ) ) > 6 ) {
		switch ( $map_target ) {
			case 'bing':
				$map_url = "https://bing.com/maps/?level=$zoom&amp;where1=$map_string";
				break;
			case 'mapquest':
				$map_url = "https://mapquest.com/search/$map_string?zoom=$zoom&amp;center=$map_string";
				break;
			case 'none':
				$map_url = '';
				break;
			default:
				$map_url = "https://maps.google.com/maps?z=$zoom&amp;daddr=$map_string";
				break;
		}
		/**
		 * Google maps URL.
		 *
		 * @hook mc_map_url
		 *
		 * @param {string} $url Link to event location on Google Maps.
		 * @param {object} $event Event object.
		 *
		 * @return {string} Link.
		 */
		$map_url = apply_filters( 'mc_map_url', $map_url, $event );
		$map     = '<a href="' . esc_url( $map_url ) . '" class="map-link external">' . $label . '</a>';
	} elseif ( esc_url( $url ) ) {
		$map_url = $url;
		$map     = "<a href=\"$map_url\" class='map-link external map-url'>" . $label . '</a>';
	} else {
		$map_url = '';
		$map     = '';
	}
	if ( 'url' === $request || 'location' === $source ) {
		return $map_url;
	} else {
		return $map;
	}
}

/**
 * Set up link to push events into Google Calendar.
 *
 * @param string $dtstart date begin.
 * @param string $dtend date end.
 * @param string $url link to event.
 * @param string $title Title of event.
 * @param string $location string version of location.
 * @param string $description info about event.
 *
 * @return string Google add to cal url
 */
function mc_google_cal( $dtstart, $dtend, $url, $title, $location, $description ) {
	$source = 'https://www.google.com/calendar/render?action=TEMPLATE';
	$base   = "&dates=$dtstart/$dtend";
	$base  .= '&sprop=website:' . $url;
	$base  .= '&text=' . urlencode( $title );
	/**
	 * Filter `location` parameter added to Google Calendar link. Default `&location=$location`. Return value needs to be URL encoded.
	 *
	 * @hook mc_gcal_location
	 *
	 * @param {string} $param Encoded parameter.
	 * @param {string} $location Unencoded original stringified location..
	 *
	 * @return {string} Encoded parameter.
	 */
	$base .= apply_filters( 'mc_gcal_location', '&location=' . urlencode( trim( $location ) ), $location );
	$base .= '&sprop=name:' . urlencode( get_bloginfo( 'name' ) );
	/**
	 * Filter `details` parameter added to Google Calendar link. Default `&details=$description`. Return value needs to be URL encoded.
	 *
	 * @hook mc_gcal_description
	 *
	 * @param {string} $param Encoded parameter.
	 * @param {string} $description Unencoded original description.
	 *
	 * @return {string} Encoded parameter.
	 */
	$base .= apply_filters( 'mc_gcal_description', '&details=' . urlencode( stripcslashes( trim( $description ) ) ), $description );
	$base .= '&sf=true&output=xml';

	return $source . $base;
}

/**
 * Get the featured image for a location.
 *
 * @param object $event object with location properties.
 * @param string $source Source type.
 *
 * @return string
 */
function mc_location_image( $event, $source = 'event' ) {
	$event = mc_get_event_location( $event, $source );
	if ( ! $event ) {
		return '';
	}
	$source = 'location';
	$post   = ( absint( $event->location_post ) ) ? $event->location_post : mc_get_location_post( $event->location_id );
	$image  = get_the_post_thumbnail( $post, 'full' );

	return $image;
}

/**
 * Format an hcard for event location
 *
 * @param object $event object with location properties.
 * @param string $address Whether to return the address.
 * @param string $map Whether to return the map.
 * @param string $source event/location.
 *
 * @return string hcard
 */
function mc_hcard( $event, $address = 'true', $map = 'true', $source = 'event' ) {
	$event = mc_get_event_location( $event, $source );
	if ( ! $event ) {
		return '';
	}
	$source  = 'location';
	$the_map = mc_maplink( $event, 'url', $source );
	$url     = esc_url( $event->location_url );
	$label   = strip_tags( stripslashes( $event->location_label ), mc_strip_tags() );
	$street  = strip_tags( stripslashes( $event->location_street ), mc_strip_tags() );
	$street2 = strip_tags( stripslashes( $event->location_street2 ), mc_strip_tags() );
	$city    = strip_tags( stripslashes( $event->location_city ), mc_strip_tags() );
	$state   = strip_tags( stripslashes( $event->location_state ), mc_strip_tags() );
	$state   = strip_tags( stripslashes( $event->location_state ), mc_strip_tags() );
	$zip     = strip_tags( stripslashes( $event->location_postcode ), mc_strip_tags() );
	$zip     = strip_tags( stripslashes( $event->location_postcode ), mc_strip_tags() );
	$country = strip_tags( stripslashes( $event->location_country ), mc_strip_tags() );
	$country = strip_tags( stripslashes( $event->location_country ), mc_strip_tags() );
	$phone   = strip_tags( stripslashes( $event->location_phone ), mc_strip_tags() );
	$loc_id  = absint( $event->location_id );
	if ( ! $url && ! $label && ! $street && ! $street2 && ! $city && ! $state && ! $zip && ! $country && ! $phone ) {
		return '';
	}
	$distance = '';
	if ( property_exists( $event, 'distance_in_miles' ) ) {
		$dist     = ( 'en_US' === get_locale() ) ? $event->distance_in_miles : ( 1.60934 * $event->distance_in_miles );
		$unit     = ( 'en_US' === get_locale() ) ? ' miles' : ' km';
		$dist     = round( $dist, 1 ) . $unit;
		$distance = ' (' . $dist . ')';
	}
	if ( is_admin() && isset( $_GET['page'] ) && 'my-calendar-location-manager' === $_GET['page'] ) {
		$link = "<a href='" . add_query_arg( 'location_id', $loc_id, admin_url( 'admin.php?page=my-calendar-locations&mode=edit' ) ) . "' class='location-link edit p-name p-org u-url'><span class='dashicons dashicons-edit' aria-hidden='true'></span> <span id='location$event->location_id'>$label</span></a>";
	} else {
		$link = ( '' !== $url ) ? "<a href='$url' class='location-link external p-name p-org u-url'>$label</a>" : $label;
		$link = $link . $distance;
	}
	$post   = ( absint( $event->location_post ) ) ? $event->location_post : mc_get_location_post( $loc_id );
	$events = ( $post && ! is_single( $post ) && ! is_admin() && 'mc-locations' === get_post_type( $post ) ) ? '<a class="location-link" href="' . esc_url( get_the_permalink( $post ) ) . '">' . __( 'View Location', 'my-calendar' ) . '</a>' : '';
	/**
	 * Filter link to location-specific events in hcard.
	 *
	 * @hook mc_location_events_link
	 *
	 * @param {string} $events HTML link to location permalink.
	 * @param {object} $post Location post object.
	 * @param {object} $event Event object being mapped.
	 *
	 * @return {string} Link.
	 */
	$events = apply_filters( 'mc_location_events_link', $events, $post, $event );
	$hcard  = '<div class="address location vcard">';
	if ( 'true' === $address ) {
		$hcard .= '<div class="adr h-card">';
		$hcard .= ( '' !== $label ) ? '<div><strong class="location-link">' . $link . '</strong></div>' : '';
		$hcard .= ( '' === $street . $street2 . $city . $state . $zip . $country . $phone . $events ) ? '' : "<div class='sub-address'>";
		$hcard .= ( '' !== $street ) ? '<div class="street-address p-street-address">' . $street . '</div>' : '';
		$hcard .= ( '' !== $street2 ) ? '<div class="street-address p-extended-address">' . $street2 . '</div>' : '';
		$hcard .= ( '' !== $city . $state . $zip ) ? '<div>' : '';
		$hcard .= ( '' !== $city ) ? '<span class="locality p-locality">' . $city . '</span><span class="mc-sep">, </span>' : '';
		$hcard .= ( '' !== $state ) ? '<span class="region p-region">' . $state . '</span> ' : '';
		$hcard .= ( '' !== $zip ) ? ' <span class="postal-code p-postal-code">' . $zip . '</span>' : '';
		$hcard .= ( '' !== $city . $state . $zip ) ? '</div>' : '';
		$hcard .= ( '' !== $country ) ? '<div class="country-name p-country-name">' . $country . '</div>' : '';
		$hcard .= ( '' !== $phone ) ? '<div class="tel p-tel">' . $phone . '</div>' : '';
		$hcard .= ( '' !== $events ) ? '<div class="mc-events-link">' . $events . '</div>' : '';
		$hcard .= ( '' === $street . $street2 . $city . $state . $zip . $country . $phone . $events ) ? '' : '</div>';
		$hcard .= '</div>';
	}
	if ( 'true' === $map && false !== $the_map ) {
		$the_link = "<a href='$the_map' class='url external'>" . __( 'Map', 'my-calendar' ) . "<span class='screen-reader-text fn'> $label</span></a>";
		$hcard   .= ( '' !== $the_map ) ? "<div class='map'>$the_link</div>" : '';
	}
	$hcard .= '</div>';
	$hcard  = ( ( false !== $the_map && 'true' === $map ) || ( '' !== $link && 'true' === $address ) ) ? $hcard : '';

	/**
	 * Filter location hcard HTML output.
	 *
	 * @hook mc_hcard
	 *
	 * @param {string} $hcard Formatted HTML output.
	 * @param {object} $event Event or location object.
	 * @param {string} $address 'true' to include the location address on the card.
	 * @param {string} $map 'true' to include the map link on the card.
	 * @param {string} $source 'event' or 'location'.
	 *
	 * @return {string} Formatted HTML hcard.
	 */
	return apply_filters( 'mc_hcard', $hcard, $event, $address, $map, $source );
}

/**
 * Produces the array of event details used for drawing templates
 *
 * @param object $event Event object.
 * @param string $context Context being executed in.
 *
 * @return array event data
 */
function mc_create_tags( $event, $context = 'filters' ) {
	if ( ! is_object( $event ) ) {
		return array();
	}
	$location = mc_get_event_location( $event, 'event' );
	/**
	 * Execute action before tags are created.
	 *
	 * @hook mc_tags_created
	 *
	 * @param {object} $object Event object.
	 * @param {string} $context Current execution context.
	 */
	do_action( 'mc_create_tags', $event, $context );
	$calendar_id = '';
	if ( 'filters' !== $context && 'related' !== $context ) {
		$calendar_id = $context;
	}
	$e           = array();
	$e['post']   = $event->event_post;
	$date_format = mc_date_format();
	/**
	 * Filter template tag array and add author data. Runs before other template tags are created. Use `mc_filter_shortcodes` to modify existing template tags.
	 *
	 * @hook mc_insert_author_data
	 *
	 * @param {array}  $e Array to hold event template tags.
	 * @param {object} $event Event object.
	 *
	 * @return {array} Template tag array.
	 */
	$e = apply_filters( 'mc_insert_author_data', $e, $event );
	/**
	 * Filter template tag array and add image data. Runs before other template tags are created. Use `mc_filter_shortcodes` to modify existing template tags.
	 *
	 * @hook mc_filter_image_data
	 *
	 * @param {array}  $e Array to hold event template tags.
	 * @param {object} $event Event object.
	 *
	 * @return {array} Template tag array.
	 */
	$e           = apply_filters( 'mc_filter_image_data', $e, $event );
	$e['access'] = mc_expand( get_post_meta( $event->event_post, '_mc_event_access', true ) );

	// Date & time fields.
	$real_end_date   = ( isset( $event->occur_end ) ) ? $event->occur_end : $event->event_end . ' ' . $event->event_endtime;
	$real_end_time   = ( mc_is_all_day( $event ) ) ? strtotime( $real_end_date ) + 60 : strtotime( $real_end_date );
	$real_begin_date = ( isset( $event->occur_begin ) ) ? $event->occur_begin : $event->event_begin . ' ' . $event->event_time;
	$dtstart         = mc_format_timestamp( strtotime( $real_begin_date ), $context );
	$dtend           = mc_format_timestamp( $real_end_time, $context );
	$recur_start     = mc_format_timestamp( strtotime( $event->event_begin . ' ' . $event->event_time ), $context );
	$recur_end       = mc_format_timestamp( strtotime( $event->event_end . ' ' . $event->event_endtime ), $context );
	/**
	 * Start date format used in 'date_utc'. Default from My Calendar settings.
	 *
	 * @hook mc_date_utc_format
	 *
	 * @param {string} $format Date Format in PHP date format.
	 * @param {string} $context 'template_begin_ts'.
	 *
	 * @return {string} Date format.
	 */
	$e['date_utc'] = date_i18n( apply_filters( 'mc_date_utc_format', $date_format, 'template_begin_ts' ), $event->ts_occur_begin );
	/**
	 * End date format used in 'date_end_utc'. Default from My Calendar settings.
	 *
	 * @hook mc_date_utc_format
	 *
	 * @param {string} $format Date Format in PHP date format.
	 * @param {string} $context 'template_end_ts'.
	 *
	 * @return {string} Date format.
	 */
	$e['date_end_utc'] = date_i18n( apply_filters( 'mc_date_utc_format', $date_format, 'template_end_ts' ), $event->ts_occur_end );
	$notime            = esc_html( mc_notime_label( $event ) );
	$e['time']         = ( '00:00:00' === mc_date( 'H:i:s', strtotime( $real_begin_date ), false ) ) ? $notime : mc_date( mc_time_format(), strtotime( $real_begin_date ), false );
	$e['time24']       = ( '00:00' === mc_date( 'G:i', strtotime( $real_begin_date ), false ) ) ? $notime : mc_date( mc_time_format(), strtotime( $real_begin_date ), false );
	$endtime           = ( '23:59:59' === $event->event_end ) ? '00:00:00' : mc_date( 'H:i:s', strtotime( $real_end_date ), false );
	$e['endtime']      = ( $real_end_date === $real_begin_date || '1' === $event->event_hide_end || '23:59:59' === mc_date( 'H:i:s', strtotime( $real_end_date ), false ) ) ? '' : date_i18n( mc_time_format(), strtotime( $endtime ) );
	$e['runtime']      = mc_runtime( $event->ts_occur_begin, $event->ts_occur_end, $event );
	$e['duration']     = mc_duration( $event );
	$e['dtstart']      = mc_date( 'Y-m-d\TH:i:s', strtotime( $real_begin_date ), false );  // Date: hcal formatted.
	$hcal_dt_end       = ( mc_is_all_day( $event ) ) ? strtotime( $real_end_date ) + 60 : strtotime( $real_end_date );
	$e['dtend']        = mc_date( 'Y-m-d\TH:i:s', $hcal_dt_end, false );    // Date: hcal formatted end.
	$e['userstart']    = '<time class="mc-user-time" data-label="' . __( 'Local time:', 'my-calendar' ) . '">' . mc_date( 'Y-m-d\TH:i:s\Z', $event->ts_occur_begin, false ) . '</time>';
	$e['userend']      = '<time class="mc-user-time" data-label="' . __( 'Local time:', 'my-calendar' ) . '">' . mc_date( 'Y-m-d\TH:i:s\Z', $event->ts_occur_end, false ) . '</time>';
	$e['datebadge']    = '<time class="mc-date-badge" datetime="' . mc_date( 'Y-m-d', strtotime( $real_begin_date ) ) . '"><span class="month">' . mc_date( 'M', strtotime( $real_begin_date ) ) . '</span><span class="day">' . mc_date( 'j', strtotime( $real_begin_date ) ) . '</span></time>';
	/**
	 * Start date format used in 'date' and 'daterange' template tags. Fallback value for `datespan`. Default from My Calendar settings.
	 *
	 * @hook mc_daterange_begin_format
	 *
	 * @param {string} $format Date Format in PHP date format.
	 * @param {string} $context 'template_begin'.
	 *
	 * @return {string} Date format.
	 */
	$date = date_i18n( apply_filters( 'mc_daterange_begin_format', $date_format, 'template_begin' ), strtotime( $real_begin_date ) );
	/**
	 * End date format used in 'enddate' and 'daterange' template tags. Default from My Calendar settings.
	 *
	 * @hook mc_daterange_end_format
	 *
	 * @param {string} $format Date Format in PHP date format.
	 * @param {string} $context 'template_end'.
	 *
	 * @return {string} Date format.
	 */
	$date_end = date_i18n( apply_filters( 'mc_daterange_end_format', $date_format, 'template_end' ), strtotime( $real_end_date ) );
	$date_arr = array(
		'occur_begin' => $real_begin_date,
		'occur_end'   => $real_end_date,
	);
	$date_obj = (object) $date_arr;
	if ( '1' === $event->event_span ) {
		$dates = mc_event_date_span( $event->event_group_id, $event->event_span, array( 0 => $date_obj ) );
	} else {
		$dates = array();
	}
	$e['datetime']  = mc_time_html( $event, 'grid' );
	$e['date']      = ( '1' !== $event->event_span ) ? $date : mc_format_date_span( $dates, 'simple', $date );
	$e['enddate']   = $date_end;
	$e['daterange'] = ( $date === $date_end ) ? "<span class='mc_db'>$date</span>" : "<span class='mc_db'>$date</span> <span>&ndash;</span> <span class='mc_de'>$date_end</span>";
	$e['timerange'] = ( ( $e['time'] === $e['endtime'] ) || 1 === (int) $event->event_hide_end || '23:59:59' === mc_date( 'H:i:s', strtotime( $real_end_date ), false ) ) ? $e['time'] : "<span class='mc_tb'>" . $e['time'] . "</span> <span>&ndash;</span> <span class='mc_te'>" . $e['endtime'] . '</span>';
	$e['datespan']  = ( 1 === (int) $event->event_span || ( $e['date'] !== $e['enddate'] ) ) ? mc_format_date_span( $dates ) : $date;
	$e['multidate'] = mc_format_date_span( $dates, 'complex', "<span class='fallback-date'>$date</span><span class='separator'>,</span> <span class='fallback-time'>$e[time]</span>&ndash;<span class='fallback-endtime'>$e[endtime]</span>" );
	$e['began']     = $event->event_begin; // returns date of first occurrence of an event.
	$e['recurs']    = mc_event_recur_string( $event, $real_begin_date );
	$e['repeats']   = $event->event_repeats;

	// Category fields.
	$e['cat_id']          = $event->event_category;
	$e['category_id']     = $event->event_category;
	$e['category']        = stripslashes( $event->category_name );
	$e['ical_category']   = strip_tags( stripslashes( $event->category_name ) );
	$e['categories']      = ( property_exists( $event, 'categories' ) ) ? mc_categories_html( $event->categories, $event->event_category ) : mc_get_categories( $event, 'html' );
	$e['ical_categories'] = ( property_exists( $event, 'categories' ) ) ? mc_categories_html( $event->categories, $event->event_category, 'text' ) : mc_get_categories( $event, 'text' );
	$e['term']            = intval( $event->category_term );
	$e['icon']            = mc_category_icon( $event, 'img' );
	$e['icon_html']       = mc_category_icon( $event );
	$e['color']           = $event->category_color;

	$hex          = ( strpos( $event->category_color, '#' ) !== 0 ) ? '#' : '';
	$color        = $hex . $event->category_color;
	$inverse      = mc_inverse_color( $color );
	$e['inverse'] = $inverse;

	// This is because widgets now strip out style attributes.
	$e['color_css']       = "<span style='background-color: $event->category_color; color: $inverse'>";
	$e['close_color_css'] = '</span>';

	// Special.
	$e['skip_holiday'] = ( 0 === (int) $event->event_holiday ) ? 'false' : 'true';
	$e['event_status'] = ( 1 === (int) $event->event_approved ) ? __( 'Published', 'my-calendar' ) : __( 'Draft', 'my-calendar' );

	// General text fields.
	$title                     = mc_search_highlight( $event->event_title );
	$e['title']                = stripslashes( $title );
	$e['description']          = wpautop( stripslashes( $event->event_desc ) );
	$e['description_raw']      = stripslashes( $event->event_desc );
	$e['description_stripped'] = strip_tags( stripslashes( $event->event_desc ) );
	$e['shortdesc']            = wpautop( stripslashes( $event->event_short ) );
	$e['shortdesc_raw']        = stripslashes( $event->event_short );
	$e['shortdesc_stripped']   = strip_tags( stripslashes( $event->event_short ) );

	// Registration fields.
	$e['event_tickets']      = $event->event_tickets;
	$e['event_registration'] = stripslashes( wp_kses_data( $event->event_registration ) );

	// Links.
	$templates  = mc_get_option( 'templates' );
	$e_template = ( ! empty( $templates['label'] ) ) ? stripcslashes( $templates['label'] ) : __( 'Details about', 'my-calendar' ) . ' {title}';
	/**
	 * Filter template for the `{details}` output. Default: `Details about {title}`.
	 *
	 * @hook mc_details_template
	 *
	 * @param {string} $e_template String with template tags.
	 * @param {object} $event Event object.
	 *
	 * @return {string} Unparsed template.
	 */
	$e_template   = apply_filters( 'mc_details_template', $e_template, $event );
	$tags         = array( '{title}', '{location}', '{color}', '{icon}', '{date}', '{time}' );
	$replacements = array(
		stripslashes( $e['title'] ),
		stripslashes( ( $location ) ? $location->location_label : '' ),
		$event->category_color,
		$event->category_icon,
		$e['date'],
		$e['time'],
	);

	$e_label   = str_replace( $tags, $replacements, $e_template );
	$classes   = mc_get_event_classes( $event, 'template' );
	$nofollow  = ( stripos( $classes, 'past-event' ) !== false ) ? 'rel="nofollow"' : '';
	$e_link    = mc_get_details_link( $event );
	$e['link'] = mc_event_link( $event );
	if ( $e['link'] ) {
		$e['link_image'] = str_replace( "alt=''", "alt='" . esc_attr( $e['title'] ) . "'", "<a href='" . esc_url( $e['link'] ) . "' $nofollow>" . $e['image'] . '</a>' );
		$e['link_title'] = "<a href='" . esc_url( $event->event_link ) . "' $nofollow>" . $e['title'] . '</a>';
	} else {
		$e['link_image'] = $e['image'];
		$e['link_title'] = $e['title'];
	}

	$e['details_link'] = $e_link;
	$e['details_ical'] = remove_query_arg( 'mc_id', $e_link ); // In ical series exports, it's impossible to get the actual details link.
	$e['details']      = "<a href='" . esc_url( $e_link ) . "' class='mc-details' $nofollow>$e_label</a>";
	$e['linking']      = ( '' !== $e['link'] ) ? $event->event_link : $e_link;

	$rel = $nofollow;
	if ( mc_external_link( $e['linking'] ) ) {
		if ( $rel ) {
			$rel = 'rel="external nofollow"';
		} else {
			$rel = 'rel="external"';
		}
	}
	$e['linking_title'] = ( '' !== $e['linking'] ) ? "<a href='" . esc_url( $e['linking'] ) . "' $rel>" . $e['title'] . '</a>' : $e['title'];

	if ( 'related' !== $context && ( mc_is_single_event() ) ) {
		/**
		 * HTML format for displaying related events on a single event view. Default `{date}, {time}`.
		 *
		 * @hook mc_related_template
		 *
		 * @param {string} $template Template to use to draw a related event.
		 * @param {object} $event Event object.
		 *
		 * @return {string} Unparsed template.
		 */
		$related_template = apply_filters( 'mc_related_template', '{date}, {time}', $event );
		$e['related']     = '<ul class="related-events">' . mc_list_group( $event->event_group_id, $event->event_id, $related_template ) . '</ul>';
	} else {
		$e['related'] = '';
	}

	// location fields.
	$e['location_source'] = $event->event_location;
	$map_gcal             = '';
	if ( is_object( $location ) ) {
		$sitelink_html = "<div class='url link'><a href='" . esc_url( $location->location_url ) . "' class='location-link external'>";

		// Translators: Location name.
		$sitelink_html     .= sprintf( __( 'Visit web site<span class="screen-reader-text">: %s</span>', 'my-calendar' ), $location->location_label );
		$sitelink_html     .= '</a></div>';
		$e['sitelink_html'] = $sitelink_html;
		$e['sitelink']      = $location->location_url;

		$map           = mc_maplink( $location, 'map', 'location' );
		$map_url       = mc_maplink( $location, 'url', 'location' );
		$map_gcal      = mc_maplink( $location, 'gcal', 'location' );
		$e['location'] = stripslashes( $location->location_label );
		$e['street']   = stripslashes( $location->location_street );
		$e['street2']  = stripslashes( $location->location_street2 );
		/**
		 * Format a phone number for display in template tags.
		 *
		 * @hook mc_phone_format
		 *
		 * @param {string} $number Phone number as saved in `$location->location_phone`.
		 * @param {string} $context 'phone'.
		 *
		 * @return {string} Formatted number.
		 */
		$e['phone'] = apply_filters( 'mc_phone_format', stripslashes( $location->location_phone ), 'phone' );
		/**
		 * Format a phone number for display in template tags.
		 *
		 * @hook mc_phone_format
		 *
		 * @param {string} $number Phone number as saved in `$location->location_phone`.
		 * @param {string} $context 'phone2'.
		 *
		 * @return {string} Formatted number.
		 */
		$e['phone2']          = apply_filters( 'mc_phone_format', stripslashes( $location->location_phone2 ), 'phone2' );
		$e['city']            = stripslashes( $location->location_city );
		$e['state']           = stripslashes( $location->location_state );
		$e['postcode']        = stripslashes( $location->location_postcode );
		$e['country']         = stripslashes( $location->location_country );
		$e['region']          = $location->location_region;
		$e['hcard']           = stripslashes( mc_hcard( $location, 'true', 'true', 'location' ) );
		$e['link_map']        = $map;
		$e['map_url']         = $map_url;
		$e['map']             = mc_generate_map( $location, 'location' );
		$e['location_access'] = mc_expand( unserialize( $location->location_access ) );
		$e['ical_location']   = trim( $location->location_label . ' ' . $location->location_street . ' ' . $location->location_street2 . ' ' . $location->location_city . ' ' . $location->location_state . ' ' . $location->location_postcode );
	} else {
		// Until 3.5, these were populated from the events table.
		$e['location']        = '';
		$e['street']          = '';
		$e['street2']         = '';
		$e['phone']           = '';
		$e['phone2']          = '';
		$e['city']            = '';
		$e['state']           = '';
		$e['postcode']        = '';
		$e['country']         = '';
		$e['region']          = '';
		$e['hcard']           = '';
		$e['link_map']        = '';
		$e['map_url']         = '';
		$e['map']             = '';
		$e['location_access'] = '';
		$e['ical_location']   = '';
	}

	$strip_desc = mc_newline_replace( strip_tags( $event->event_desc ) ) . ' ' . $e['link'];
	if ( mc_is_all_day( $event ) ) {
		$google_start = mc_date( 'Ymd', strtotime( $dtstart ), false );
		$google_end   = mc_date( 'Ymd', strtotime( $dtend ), false );
	} else {
		$google_start = $dtstart;
		$google_end   = $dtend;
	}
	$e['gcal']      = mc_google_cal( $google_start, $google_end, $e_link, stripcslashes( $e['title'] ), $map_gcal, $strip_desc );
	$e['gcal_link'] = "<a href='" . esc_url( $e['gcal'] ) . "' class='gcal external' rel='nofollow' aria-describedby='mc_$event->occur_id-title-$calendar_id'>" . __( 'Google Calendar', 'my-calendar' ) . '</a>';

	// IDs.
	$e['dateid']     = $event->occur_id; // Unique ID for this date of this event.
	$e['id']         = $event->event_id;
	$e['group']      = $event->event_group_id;
	$e['event_span'] = $event->event_span;

	// ICAL.
	$e['ical_desc']       = $strip_desc;
	$e['ical_start']      = ( mc_is_all_day( $event ) ) ? mc_date( 'Ymd', strtotime( $recur_start ), false ) : $recur_start;
	$e['ical_end']        = ( mc_is_all_day( $event ) ) ? mc_date( 'Ymd', strtotime( $recur_end ) + 60, false ) : $recur_end;
	$e['ical_date_start'] = ( mc_is_all_day( $event ) ) ? mc_date( 'Ymd', strtotime( $dtstart ), false ) : $dtstart;
	$e['ical_date_end']   = ( mc_is_all_day( $event ) ) ? mc_date( 'Ymd', strtotime( $dtend ) + 60, false ) : $dtend;
	$e['ical_recur']      = mc_generate_rrule( $event );
	$ical_link            = mc_build_url(
		array( 'vcal' => $event->occur_id ),
		array(
			'month',
			'dy',
			'yr',
			'ltype',
			'loc',
			'mcat',
			'format',
			'time',
		),
		mc_get_uri( $event )
	);
	$e['ical']            = $ical_link;
	$e['ical_html']       = "<a class='ical' rel='nofollow' href='" . esc_url( $ical_link ) . "' aria-describedby='mc_$event->occur_id-title-$calendar_id'>" . __( 'iCal', 'my-calendar' ) . '</a>';

	/**
	 * Filter all template tags after generation.
	 *
	 * @hook mc_filter_shortcodes
	 *
	 * @param {array}  $e Array of values to be used in template tags.
	 * @param {object} $event Event object.
	 *
	 * @return {array} Array of template tags.
	 */
	$e = apply_filters( 'mc_filter_shortcodes', $e, $event );
	/**
	 * Execute action when tags are created.
	 *
	 * @hook mc_tags_created
	 *
	 * @param {object} $object Event object.
	 * @param {string} $context Current execution context.
	 */
	do_action( 'mc_tags_created', $event, $context );

	return $e;
}

/**
 * Get the label for all day events.
 *
 * @param object $event Event object.
 *
 * @return string.
 */
function mc_notime_label( $event ) {
	$notime  = '';
	$default = mc_get_option( 'notime_text' );
	if ( is_object( $event ) && property_exists( $event, 'event_post' ) ) {
		$notime  = get_post_meta( $event->event_post, '_event_time_label', true );
		$default = ( metadata_exists( 'post', $event->event_post, '_event_time_label' ) ) ? '' : $default;
	}
	$notime = ( '' !== $notime ) ? $notime : $default;

	/**
	 * Label to use in place of time for an event with no fixed time.
	 *
	 * @hook mc_notime_label
	 *
	 * @param {string} $notime Default value from settings or event post meta.
	 * @param {object} $event Event object.
	 *
	 * @return {string} Text describing when the event occurs. E.g. 'all day' or 'to be determined'.
	 */
	return apply_filters( 'mc_notime_label', $notime, $event );
}

/**
 * Get link to event's details page.
 *
 * @param object|int $event Full event object or event occurrence ID.
 *
 * @return string URL.
 */
function mc_get_details_link( $event ) {
	if ( is_numeric( $event ) ) {
		$event = mc_get_event( $event );
	}
	if ( ! is_object( $event ) ) {
		return '';
	}
	$restore = false;
	if ( is_multisite() && property_exists( $event, 'site_id' ) && get_current_blog_id() !== $event->site_id ) {
		switch_to_blog( $event->site_id );
		$restore = true;
	}
	$uri = mc_get_uri( $event );

	/**
	 * Check whether permalinks are enabled.
	 *
	 * @hook mc_use_permalinks
	 *
	 * @param {string} $option Value of mc_use_permalinks setting.
	 *
	 * @return {bool} True value if permalinks are enabled.
	 */
	$permalinks   = apply_filters( 'mc_use_permalinks', mc_get_option( 'use_permalinks' ) );
	$permalinks   = ( 1 === $permalinks || true === $permalinks || 'true' === $permalinks ) ? true : false;
	$details_link = mc_event_link( $event );
	if ( 0 !== (int) $event->event_post && 'true' !== mc_get_option( 'remote' ) && $permalinks ) {
		$details_link = add_query_arg( 'mc_id', $event->occur_id, get_permalink( $event->event_post ) );
	} else {
		if ( mc_get_uri( 'boolean' ) ) {
			$details_link = mc_build_url(
				array( 'mc_id' => $event->occur_id ),
				array(
					'month',
					'dy',
					'yr',
					'ltype',
					'loc',
					'mcat',
					'format',
					'feed',
					'page_id',
					'p',
					'mcs',
					'time',
					'page',
					'mode',
					'event_id',
				),
				$uri
			);
		}
	}
	/**
	 * URL to an event's permalink page.
	 *
	 * @hook mc_customize_details_link
	 *
	 * @param {string} $details_link Link to event details page/permalink.
	 * @param {object} $event Event object.
	 *
	 * @return {string} URL.
	 */
	$details_link = apply_filters( 'mc_customize_details_link', $details_link, $event );

	if ( $restore ) {
		restore_current_blog();
	}

	return $details_link;
}

/**
 * Get primary My Calendar URI from settings.
 *
 * @param object|string|bool $event Event object, string requesting boolean result, or boolean false.
 * @param array              $args  Any arguments passed.
 *
 * @uses filter 'mc_get_uri'
 *
 * @return string|boolean URL or boolean.
 */
function mc_get_uri( $event = false, $args = array() ) {
	// For a brief period of time, mc_uri was a post ID.
	// Convert mc_uri to mc_uri_id.
	$mc_uri = mc_get_option( 'uri' );
	$mc_id  = mc_get_option( 'uri_id' );
	if ( is_numeric( $mc_uri ) && ! $mc_id ) {
		mc_update_option( 'uri_id', $mc_uri );
	}
	$mc_id = mc_get_option( 'uri_id' );
	$uri   = ( get_permalink( $mc_id ) ) ? get_permalink( $mc_id ) : false;

	if ( 'boolean' === $event ) {
		if ( ! _mc_is_url( $uri ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Link to the My Calendar main calendar view.
	 *
	 * @hook mc_get_uri
	 *
	 * @param {string}             $link String to return if event link is expired.
	 * @param {object|string|bool} $event Event object, string requesting boolean result, or boolean false.
	 * @param {array}              $args Current view arguments. (Optional).
	 *
	 * @return {string} URL.
	 */
	return apply_filters( 'mc_get_uri', $uri, $event, $args );
}

/**
 * Get the templated label for a details link
 *
 * @param object $event event.
 * @param array  $e tags array.
 *
 * @return string label
 */
function mc_get_details_label( $event, $e ) {
	$templates  = mc_get_option( 'templates' );
	$e_template = ( ! empty( $templates['label'] ) ) ? stripcslashes( $templates['label'] ) : __( 'Read more', 'my-calendar' );
	$e_label    = wp_kses(
		mc_draw_template( $e, $e_template ),
		array(
			'span' => array(
				'class' => array(
					'screen-reader-text',
				),
			),
			'em',
			'strong',
		)
	);

	return $e_label;
}

/**
 * Format a timestamp for use in iCal.
 *
 * @param integer $os timestamp.
 *
 * @return string formatted time
 */
function mc_format_timestamp( $os ) {
	$os_time = mktime( mc_date( 'H', $os, false ), mc_date( 'i', $os, false ), mc_date( 's', $os, false ), mc_date( 'm', $os, false ), mc_date( 'd', $os, false ), mc_date( 'Y', $os, false ) );
	$time    = mc_date( 'Ymd\THi00', $os_time, false );

	return $time;
}

/**
 * Get a human-readable version of the duration of an event
 *
 * @param string $start start date/time.
 * @param string $end  end date/time.
 * @param object $event event object.
 *
 * @return string human readable time
 */
function mc_runtime( $start, $end, $event ) {
	$return = '';
	if ( ! ( $event->event_hide_end || $start === $end || '23:59:59' === mc_date( 'H:i:s', strtotime( $end ), false ) ) ) {
		$return = human_time_diff( $start, $end );
	}

	return $return;
}

/**
 * Return ISO8601 duration marker
 *
 * @param object $event event object.
 *
 * @return string ISO8601 duration format
 */
function mc_duration( $event ) {
	$start = $event->occur_begin;
	$end   = $event->occur_end;

	$datetime1 = new DateTime( $start );
	$datetime2 = new DateTime( $end );
	$interval  = $datetime1->diff( $datetime2 );

	$duration  = '';
	$duration .= ( 0 !== (int) $interval->y ) ? $interval->y . 'Y' : '';
	$duration .= ( 0 !== (int) $interval->m ) ? $interval->m . 'M' : '';
	if ( '23' === (string) $interval->h && '59' === (string) $interval->i ) {
		$d         = ( 0 === (int) $interval->d ) ? 1 : $interval->d + 1;
		$duration .= 'D' . $d;
		$duration .= 'TH0M0';
	} else {
		$duration .= ( 0 !== (int) $interval->d ) ? $interval->d . 'D' : '';
		$duration .= ( 0 !== (int) $interval->h ) ? 'T' . $interval->h . 'H' : '';
		$duration .= ( 0 !== (int) $interval->i ) ? $interval->i . 'M' : '';
	}
	$duration = 'P' . $duration;

	return $duration;
}

/**
 * Get event link if not designated to expire & expired.
 *
 * @param object $event Event Object.
 *
 * @return string
 */
function mc_event_link( $event ) {
	$link = '';
	if ( ! is_object( $event ) ) {
		return $link;
	}
	$expired = mc_event_expired( $event );
	if ( 0 === (int) $event->event_link_expires ) {
		$link = esc_url( $event->event_link );
	} else {
		if ( $expired ) {
			/**
			 * Link to return if an event's link has expired. Default empty string.
			 *
			 * @hook mc_event_expired_link
			 *
			 * @param {string} $link String to return if event link is expired.
			 * @param {object} $event Event object.
			 *
			 * @return {string} Link or empty string.
			 */
			$link = apply_filters( 'mc_event_expired_link', '', $event );
		} else {
			$link = esc_url( $event->event_link );
		}
	}

	return $link;
}

/**
 * Test if event has already passed.
 *
 * @param object $event Event object.
 *
 * @return boolean
 */
function mc_event_expired( $event ) {
	if ( is_object( $event ) && property_exists( $event, 'occur_end' ) ) {
		if ( my_calendar_date_xcomp( $event->occur_end, current_time( 'Y-m-d' ) ) ) {
			/**
			 * Execute action once an event is over.
			 *
			 * @hook mc_event_expired
			 *
			 * @param {object} $object Event object.
			 */
			do_action( 'mc_event_expired', $event );

			return true;
		}
	}

	return false;
}

/**
 * Get location from event.
 *
 * @param object $event Either a location or an event object with location data.
 * @param string $source Source type being checked.
 *
 * @return object|bool
 */
function mc_get_event_location( $event, $source ) {
	if ( 'location' === $source ) {
		return $event;
	}
	// This is an event with a location object property.
	if ( 'event' === $source && property_exists( $event, 'location' ) && is_object( $event->location ) ) {
		$location = $event->location;

		return $location;
	}
	// This is an event with a location ID, but no attached object.
	if ( 'event' === $source && property_exists( $event, 'event_location' ) && $event->event_location ) {
		$location = mc_get_location( $event->event_location );

		return $location;
	}

	// If we've gotten this far without finding location info, return false.
	return false;
}

/**
 * Generate script and HTML for Google Maps embed if API key present
 *
 * @param object|array $event Object containing location parameters or array of objects.
 * @param string       $source event or location.
 * @param bool         $multiple True if event contains multiple locations.
 * @param bool         $geolocate True if map is generated from geolocation data.
 *
 * @return string HTML
 */
function mc_generate_map( $event, $source = 'event', $multiple = false, $geolocate = false ) {
	if ( ! is_object( $event ) && ! $multiple ) {
		return '';
	}
	if ( ! is_array( $event ) && $multiple ) {
		return '';
	}

	$event    = mc_get_event_location( $event, $source );
	$id       = '0';
	$api_key  = mc_get_option( 'gmap_api_key' );
	$markers  = '';
	$loc_list = '';
	$out      = '';
	/**
	 * Default map width. Default value `100%`.
	 *
	 * @hook mc_map_width
	 *
	 * @param {string} $width Width parameter passed to map container style attribute.
	 * @param {object} $event Event or location object containing location information.
	 *
	 * @return {string} Value.
	 */
	$width = apply_filters( 'mc_map_width', '100%', $event );
	/**
	 * Default map height. Default value `300px`.
	 *
	 * @hook mc_map_height
	 *
	 * @param {string} $height Height parameter passed to map container style attribute.
	 * @param {object} $event Event or location object containing location information.
	 *
	 * @return {string} Value.
	 */
	$height = apply_filters( 'mc_map_height', '300px', $event );
	$styles = " style='width: $width;height: $height'";

	if ( $api_key ) {
		$locations = ( is_object( $event ) ) ? array( $event ) : $event;
		if ( is_array( $locations ) ) {
			$multiple = ( count( $locations ) > 1 ) ? true : false;
			foreach ( $locations as $location ) {
				$id     = wp_rand();
				$loc_id = $location->location_id;
				/**
				 * URL to Google Map marker image.
				 *
				 * @hook mc_map_icon
				 *
				 * @param {string} $icon    Formatted HTML to be returned.
				 * @param {object} $location Event or location object containing location information.
				 * @param {string} $source 'location'. Event source removed in 3.5.
				 *
				 * @return {string} URL to icon.
				 */
				$category_icon = apply_filters( 'mc_map_icon', '//maps.google.com/mapfiles/marker_green.png', $location, 'location' );
				$address       = addslashes( mc_map_string( $location, 'location' ) );

				if ( '0.000000' !== $location->location_longitude && '0.000000' !== $location->location_latitude ) {
					$lat    = $location->location_latitude;
					$lng    = $location->location_longitude;
					$latlng = true;
				} else {
					$lat    = '';
					$lng    = '';
					$latlng = false;
				}

				if ( strlen( $address ) < 10 && ! $latlng ) {
					return '';
				}
				$hcard  = mc_hcard( $location, 'true', false, 'location' );
				$title  = esc_attr( $location->location_label );
				$marker = wp_kses(
					str_replace(
						array( '</div>', '<br />', '<br><br>' ),
						'<br>',
						$hcard
					),
					array(
						'br'     => array(),
						'strong' => array(),
					)
				);
				/**
				 * Source HTML for a single location marker.
				 *
				 * @hook mc_map_html
				 *
				 * @param {string} $marker Formatted HTML to be returned.
				 * @param {object} $location Event object containing location information.
				 *
				 * @return {string} Formatted HTML to be parsed by Google Maps JS.
				 */
				$html      = apply_filters( 'mc_map_html', $marker, $location );
				$markers  .= PHP_EOL . "<div class='marker' data-address='$address' data-title='$title' data-icon='$category_icon' data-lat='$lat' data-lng='$lng'>$html</div>" . PHP_EOL;
				$loc_list .= ( $multiple ) ? '<div class="mc-location-details" id="mc-location-' . $id . '-' . $loc_id . '">' . $hcard . '</div>' : '';
			}
			/**
			 * Source HTML for generating a map of calendar locations.
			 *
			 * @hook mc_gmap_html
			 *
			 * @param {string}       $output Formatted HTML to be returned.
			 * @param {object|array} $event Object or array of objects containing one or more objects with location information.
			 *
			 * @return {string} Formatted HTML to be parsed by Google Maps JS.
			 */
			$markers = apply_filters( 'mc_gmap_html', $markers, $event );
			$class   = ( $geolocate ) ? 'mc-geolocated' : 'mc-address';
			$maptype = mc_location_custom_data( $loc_id, $location->location_post, 'maptype' );
			$maptype = ( $maptype ) ? strtolower( $maptype ) : mc_get_option( 'maptype' );
			$map     = "<div class='mc-gmap-markers $class' id='mc_gmap_$id' data-maptype='" . esc_attr( $maptype ) . "'$styles>" . $markers . '</div>';
			$locs    = ( $loc_list ) ? '<div class="mc-gmap-location-list"><h2 class="screen-reader-text">' . __( 'Locations', 'my-calendar' ) . '</h2>' . $loc_list . '</div>' : '';
			$out     = '<div class="mc-maps">' . $map . $locs . '</div>';
		}
	} else {
		if ( current_user_can( 'manage_options' ) ) {
			$out = wpautop( __( 'You need a Google Maps API key to display the location map.', 'my-calendar' ) );
		}
	}

	return $out;
}

/**
 * Expand access data into a list of features.
 *
 * @param array $data Either event or location accessibility data.
 *
 * @return string list of features.
 */
function mc_expand( $data ) {
	$output = '';
	if ( is_array( $data ) ) {
		foreach ( $data as $key => $value ) {
			$class = ( isset( $value ) ) ? sanitize_html_class( $value ) : '';
			$label = ( isset( $value ) ) ? $value : false;
			if ( ! $label ) {
				continue;
			}
			$output .= "<li class='$class'><span>$label</span></li>\n";
		}
		$output = ( $output ) ? "<ul class='mc-access'>" . $output . '</ul>' : '';
	}

	/**
	 * HTML output from an internal data array, e.g. accessibility features.
	 *
	 * @hook mc_expand
	 *
	 * @param {string} $output Formatted HTML to be returned.
	 * @param {array} $data Array of data being parsed.
	 *
	 * @return {string} Formatted HTML.
	 */
	return apply_filters( 'mc_expand', $output, $data );
}

/**
 * Get the full date span of a set of events for display.
 *
 * @param int   $group_id Group ID.
 * @param int   $event_span Whether these events constitute one event.
 * @param array $dates Start and end dates of current event.
 *
 * @return array
 */
function mc_event_date_span( $group_id, $event_span, $dates = array() ) {
	$mcdb = mc_is_remote_db();
	// Cache as transient to save db queries.
	if ( get_transient( 'mc_event_date_span_' . $group_id . '_' . $event_span ) ) {
		return get_transient( 'mc_event_date_span_' . $group_id . '_' . $event_span );
	}
	$group_id = (int) $group_id;
	if ( 0 === (int) $group_id && 1 !== (int) $event_span ) {

		return $dates;
	} else {
		$dates = $mcdb->get_results( $mcdb->prepare( 'SELECT occur_begin, occur_end FROM ' . my_calendar_event_table() . ' WHERE occur_group_id = %d ORDER BY occur_begin ASC', $group_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		set_transient( 'mc_event_date_span_' . $group_id . '_' . $event_span, $dates, HOUR_IN_SECONDS );

		return $dates;
	}
}

/**
 * Format a date span.
 *
 * @param array  $dates to format.
 * @param string $display type of display to use.
 * @param string $default_output value if no dates passed.
 *
 * @return string
 */
function mc_format_date_span( $dates, $display = 'simple', $default_output = '' ) {
	if ( ! $dates ) {
		return $default_output;
	}
	$count = count( $dates );
	$last  = $count - 1;
	if ( 'simple' === $display ) {
		$begin = $dates[0]->occur_begin;
		$end   = $dates[ $last ]->occur_end;

		/**
		 * Starting date format used in 'date', 'datespan', and 'multidate' template tags. Default from My Calendar settings.
		 *
		 * @hook mc_date_format
		 *
		 * @param {string} $format Date Format in PHP date format.
		 * @param {string} $context 'date_span_begin'.
		 *
		 * @return {string} Date format.
		 */
		$begin = date_i18n( apply_filters( 'mc_date_format', mc_date_format(), 'date_span_begin' ), strtotime( $begin ) );
		/**
		 * End date format used in 'date', 'datespan', and 'multidate' template tags. Default from My Calendar settings.
		 *
		 * @hook mc_date_format
		 *
		 * @param {string} $format Date Format in PHP date format.
		 * @param {string} $context 'date_span_end'.
		 *
		 * @return {string} Date format.
		 */
		$end    = date_i18n( apply_filters( 'mc_date_format', mc_date_format(), 'date_span_end' ), strtotime( $end ) );
		$return = $begin . ' <span>&ndash;</span> ' . $end;
	} else {
		$return = '<ul class="multidate">';
		foreach ( $dates as $date ) {
			$begin         = $date->occur_begin;
			$end           = $date->occur_end;
			$day_begin     = mc_date( 'Y-m-d', strtotime( $begin ), false );
			$day_end       = mc_date( 'Y-m-d', strtotime( $end ), false );
			$bformat       = '<span class="multidate-date">' . date_i18n( mc_date_format(), strtotime( $begin ) ) . "</span> <span class='multidate-time'>" . date_i18n( mc_time_format(), strtotime( $begin ) ) . '</span>';
			$endtimeformat = ( '00:00:00' === $date->occur_end ) ? '' : ' ' . mc_time_format();
			$eformat       = ( $day_begin !== $day_end ) ? mc_date_format() . $endtimeformat : $endtimeformat;
			$span          = ( '' !== $eformat ) ? " <span>&ndash;</span> <span class='multidate-end'>" : '';
			$endspan       = ( '' !== $eformat ) ? '</span>' : '';
			$return       .= "<li>$bformat" . $span . date_i18n( $eformat, strtotime( $end ) ) . "$endspan</li>";
		}
		$return .= '</ul>';
	}

	return $return;
}

add_filter( 'mc_insert_author_data', 'mc_author_data', 10, 2 );
/**
 * Include data about event author in event array.
 *
 * @param array  $e Array of event details.
 * @param object $event Event object.
 *
 * @return array $e
 */
function mc_author_data( $e, $event ) {
	if ( 0 !== (int) $event->event_author ) {
		$author = get_userdata( $event->event_author );
		if ( $author ) {
			$e['author']       = $author->display_name;
			$e['gravatar']     = get_avatar( $author->user_email );
			$e['author_email'] = $author->user_email;
			$e['author_id']    = $event->event_author;
		}
		if ( function_exists( 'mcs_submissions' ) && 'true' === get_option( 'mcs_custom_hosts' ) ) {
			if ( ! $event->event_host || ! ( get_post_type( $event->event_host ) ) ) {
				$host = false;
			} else {
				$host = get_post( $event->event_host );
			}
			if ( $host ) {
				$e['host']          = $host->post_title;
				$e['host_id']       = $event->event_host;
				$e['host_email']    = get_post_meta( $host->ID, '_mcs_host_email', true );
				$e['host_gravatar'] = ( '' === get_the_post_thumbnail( $host ) ) ? get_avatar( $e['host_email'] ) : get_the_post_thumbnail( $host );
				$e['host_phone']    = get_post_meta( $host->ID, '_mcs_host_phone', true );
				$e['host_url']      = get_post_meta( $host->ID, '_mcs_host_url', true );
				$e['host_bio']      = $host->post_content;
			}
		} else {
			$host = get_userdata( $event->event_host );
			if ( $host ) {
				$e['host']          = ( '' === $host->display_name ) ? $author->display_name : $host->display_name;
				$e['host_id']       = $event->event_host;
				$e['host_email']    = ( '' === $host->user_email ) ? $author->user_email : $host->user_email;
				$e['host_gravatar'] = ( '' === $host->user_email ) ? $e['gravatar'] : get_avatar( $host->user_email );
			}
		}
	} else {
		$e['author']        = 'Public Submitter';
		$e['host']          = 'Public Submitter';
		$e['host_email']    = '';
		$e['author_email']  = '';
		$e['gravatar']      = '';
		$e['host_gravatar'] = '';
		$e['author_id']     = false;
		$e['host_id']       = false;
	}

	return $e;
}

add_filter( 'mc_filter_shortcodes', 'mc_auto_excerpt', 10, 2 );
/**
 * Custom excerpt for use in templates.
 *
 * @param array  $e Array of event details.
 * @param object $event Event object.
 *
 * @return array $e
 */
function mc_auto_excerpt( $e, $event ) {
	$description = $e['description'];
	$shortdesc   = $e['shortdesc'];
	$excerpt     = '';
	if ( '' !== $description && '' === $shortdesc ) { // if description is empty, this won't work, so skip it.
		/**
		 * Length of My Calendar generated excerpts in words. Default 55.
		 *
		 * @hook mc_excerpt_length
		 *
		 * @param {int} $num_words Number of words to use.
		 *
		 * @return {int}
		 */
		$num_words = apply_filters( 'mc_excerpt_length', 55 );
		$excerpt   = wp_trim_words( $description, $num_words );
	} else {
		$excerpt = $shortdesc;
	}

	$e['search_excerpt'] = mc_search_highlight( $description, $shortdesc );
	$e['excerpt']        = $excerpt;
	$e['ical_excerpt']   = mc_newline_replace( strip_tags( $excerpt ) );

	return $e;
}

/**
 * Generate a string with highlighted search terms.
 *
 * @param string $string1 Default highlight text.
 * @param string $string2 Alternate text to use if first might be blank.
 * @param string $term Search term.
 *
 * @return string
 */
function mc_search_highlight( $string1, $string2 = '', $term = '' ) {
	$append = '';
	if ( '' !== $term ) {
		$append = ' ' . trim( $term );
	}
	if ( isset( $_REQUEST['mcs'] ) ) {
		$term = sanitize_text_field( trim( $_REQUEST['mcs'] ) ) . $append;
	} else {
		return $string1;
	}
	$terms = explode( ' ', $term );
	// If neither description nor short, return early.
	if ( '' === $string1 . $string2 ) {
		return '';
	}
	// If no full description, use short.
	if ( '' === $string1 ) {
		$use = $string2;
	} else {
		$use = $string1;
	}
	$use    = wp_strip_all_tags( $use );
	$length = strlen( $use );
	$start  = 0;
	if ( $length > 160 ) {
		foreach ( $terms as $t ) {
			$positions[] = stripos( $use, $t );
		}
		// Use the first term referenced for positioning.
		sort( $positions );
		$position = $positions[0];
		// Search term not found.
		if ( false === $position ) {
			return substr( $use, 0, 160 );
		}
		if ( 0 === $position ) {
			$start = 0;
		} else {
			$start = ( ( $position - 20 ) > 0 ) ? ( $position - 15 ) : 0;
		}
	}
	$extract  = substr( $use, $start, 160 );
	$ellipsis = '';
	if ( strlen( $extract ) < $length ) {
		$ellipsis = '...';
		// Remove first and last words, which are likely to be partial.
		$clean = explode( ' ', $extract );
		unset( $clean[0] );
		array_pop( $clean );
		$extract = implode( ' ', $clean );
	}

	foreach ( $terms as $t ) {
		$extract = mc_str_replace_word_i( $t, $extract );
	}

	$excerpt = $ellipsis . $extract . $ellipsis;

	return $excerpt;
}

/**
 * Search text and wrap search terms.
 *
 * @param string $needle Word to find.
 * @param string $haystack Source text.
 *
 * @return string
 */
function mc_str_replace_word_i( $needle, $haystack ) {
	$keyword  = $needle;
	$needle   = str_replace( '/', '\\/', preg_quote( $needle ) ); // allow '/' in keywords.
	$pattern  = "/\b$needle(?!([^<]+)?>)\b/i";
	$type     = 'all';
	$haystack = preg_replace_callback(
		$pattern,
		function ( $m ) use ( $type, $keyword ) {
			return '<strong class="mc_search_term">' . $m[0] . '</strong>';
		},
		$haystack
	);

	return $haystack;
}

/**
 * Get template for a specific usage.
 *
 * @param string $template name of template.
 *
 * @return string Template HTML/tags
 */
function mc_get_template( $template ) {
	$templates = mc_get_option( 'templates' );
	$keys      = array( 'title', 'title_list', 'title_solo', 'title_card', 'link', 'mini', 'list', 'details', 'grid', 'card' );

	if ( ! in_array( $template, $keys, true ) ) {
		$template = '';
	} else {
		$template = ( isset( $templates[ $template ] ) ) ? $templates[ $template ] : '';
	}

	return trim( $template );
}

add_filter( 'mc_filter_image_data', 'mc_image_data', 10, 2 );
/**
 * Event image data.
 *
 * @param array  $e Array of event details.
 * @param object $event Event object.
 *
 * @return array $e
 */
function mc_image_data( $e, $event ) {
	/**
	 * Attributes added to My Calendar event images. Default `array( 'class' => 'mc-image' )`. See `get_the_post_thumbnail()` docs at WordPress.org.
	 *
	 * @hook mc_post_thumbnail_atts
	 *
	 * @param {array} $atts Array of image attributes.
	 *
	 * @return {array} Array of image attributes.
	 */
	$atts = apply_filters( 'mc_post_thumbnail_atts', array( 'class' => 'mc-image' ) );
	if ( isset( $event->event_post ) && is_numeric( $event->event_post ) && get_post_status( $event->event_post ) && has_post_thumbnail( $event->event_post ) ) {
		$e['full'] = get_the_post_thumbnail( $event->event_post );
		$sizes     = get_intermediate_image_sizes();
		$attach    = get_post_thumbnail_id( $event->event_post );
		foreach ( $sizes as $size ) {
			$src                 = wp_get_attachment_image_src( $attach, $size );
			$e[ $size ]          = get_the_post_thumbnail( $event->event_post, $size, $atts );
			$e[ $size . '_url' ] = $src[0];
		}
		if ( isset( $e['large'] ) && '' !== $e['large'] ) {
			$e['image_url'] = strip_tags( $e['large'] );
			$e['image']     = $e['large'];
		} else {
			/**
			 * Default image size used for event listings when 'large' image not available. Default 'thumbnail'.
			 *
			 * @hook mc_default_image_size
			 *
			 * @param {string} $size Default image size
			 *
			 * @return {string} Image size description key.
			 */
			$image_size     = apply_filters( 'mc_default_image_size', 'thumbnail' );
			$e['image_url'] = strip_tags( $e[ $image_size ] );
			$e['image']     = $e[ $image_size ];
		}
	} else {
		$sizes = get_intermediate_image_sizes();
		// create empty array values so that template tags will be removed even if post doesn't exist.
		foreach ( $sizes as $size ) {
			$e[ $size ]          = '';
			$e[ $size . '_url' ] = '';
		}
		$e['image_url'] = ( '' !== $event->event_image ) ? $event->event_image : '';
		$e['image']     = ( '' !== $event->event_image ) ? "<img src='$event->event_image' alt='' class='mc-image' />" : '';
	}

	return $e;
}

/**
 * Event recurrance string description.
 *
 * @param object $event Event Object.
 * @param string $begin Date event begins.
 *
 * @return string
 */
function mc_event_recur_string( $event, $begin ) {
	$recurs      = str_split( $event->event_recur, 1 );
	$recur       = $recurs[0];
	$every       = ( isset( $recurs[1] ) ) ? str_replace( $recurs[0], '', $event->event_recur ) : 1;
	$month_date  = mc_date( 'dS', strtotime( $begin ), false );
	$day_name    = date_i18n( 'l', strtotime( $begin ) );
	$week_number = mc_ordinal( mc_week_of_month( mc_date( 'j', strtotime( $begin ), false ) ) + 1 );
	switch ( $recur ) {
		case 'S':
			$event_recur = __( 'Does not recur', 'my-calendar' );
			break;
		case 'D':
			if ( 1 === (int) $every ) {
				$event_recur = __( 'Daily', 'my-calendar' );
			} else {
				// Translators: Number of days between recurrences.
				$event_recur = sprintf( __( 'Every %d days', 'my-calendar' ), $every );
			}
			break;
		case 'E':
			$event_recur = __( 'Daily, weekdays only', 'my-calendar' );
			break;
		case 'W':
			if ( 1 === (int) $every ) {
				$event_recur = __( 'Weekly', 'my-calendar' );
			} else {
				// Translators: Number of weeks between recurrences.
				$event_recur = sprintf( __( 'Every %d weeks', 'my-calendar' ), $every );
			}
			break;
		case 'B':
			$event_recur = __( 'Bi-weekly', 'my-calendar' );
			break;
		case 'M':
			if ( 1 === (int) $every ) {
				// Translators: The ordinal number of the month for the recurrence.
				$event_recur = sprintf( __( 'the %s of each month', 'my-calendar' ), $month_date );
			} else {
				// Translators: Ordinal number of each n months.
				$event_recur = sprintf( __( 'the %1$s of every %2$s months', 'my-calendar' ), $month_date, mc_ordinal( $every ) );
			}
			break;
		case 'U':
			// Translators: The {number} {day name} of each month.
			$event_recur = sprintf( __( 'the %1$s %2$s of each month', 'my-calendar' ), $week_number, $day_name );
			break;
		case 'Y':
			if ( 1 === (int) $every ) {
				$event_recur = __( 'Annually', 'my-calendar' );
			} else {
				// Translators: Number of years.
				$event_recur = sprintf( __( 'Every %d years', 'my-calendar' ), $every );
			}
			break;
		default:
			$event_recur = '';
	}

	/**
	 * Text representation of a recurring event pattern.
	 *
	 * @hook mc_event_recur_string
	 *
	 * @param {string} $event_recur Template HTML closing tag.
	 * @param {object} $event Event object.
	 *
	 * @return {string}
	 */
	return apply_filters( 'mc_event_recur_string', $event_recur, $event );
}

/**
 * Generate JSON/LD Schema for event.
 *
 * @param object $e Event object.
 * @param array  $tags Event tag array.
 *
 * @return array
 */
function mc_event_schema( $e, $tags = array() ) {
	$event   = ( empty( $tags ) ) ? mc_create_tags( $e ) : $tags;
	$wp_time = mc_ts( true )['db'];
	$wp_time = str_replace( array( ':30:00', ':00:00' ), array( ':30', ':00' ), $wp_time );
	$image   = ( $event['image_url'] ) ? $event['image_url'] : get_site_icon_url();
	$schema  = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Event',
		'name'        => $event['title'],
		'description' => $event['excerpt'],
		'image'       => $image,
		'url'         => $event['linking'],
		'startDate'   => $event['dtstart'] . $wp_time,
		'endDate'     => $event['dtend'] . $wp_time,
		'duration'    => $event['duration'],
	);
	if ( property_exists( $e, 'location' ) && is_object( $e->location ) ) {
		$location                      = $e->location;
		$loc                           = mc_location_schema( $location );
		$schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';

	} else {
		// Location is a mandatory field for Google.
		$loc                           = array(
			'@type' => 'VirtualLocation',
			'url'   => $event['linking'],
		);
		$schema['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
	}
	$schema['location'] = $loc;
	/**
	 * Filter JSON/LD event schema data. https://schema.org/event.
	 *
	 * @hook mc_event_schema
	 *
	 * @param {array } $schema Schema data.
	 * @param {object} $e Event data.
	 * @param {array}  $event Event tag array.
	 *
	 * @return array
	 */
	return apply_filters( 'mc_event_schema', $schema, $e, $event );
}

/**
 * Generate JSON/LD Schema for location.
 *
 * @param object $location Location object.
 *
 * @return array
 */
function mc_location_schema( $location ) {
	$location_post = ( absint( $location->location_post ) ) ? $location->location_post : mc_get_location_post( $location->location_id );
	$schema        = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Place',
		'name'        => $location->location_label,
		'description' => '',
		'url'         => get_permalink( $location_post ),
		'address'     => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $location->location_street,
			'addressLocality' => $location->location_city,
			'addressRegion'   => $location->location_state,
			'postalCode'      => $location->location_postcode,
			'addressCountry'  => $location->location_country,
		),
		'telephone'   => ( $location->location_phone ) ? $location->location_phone : 'n/a',
		'sameAs'      => $location->location_url,
	);
	if ( ! empty( $location->location_latitude ) && 0 !== (int) $location->location_latitude ) {
		$schema['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => $location->location_latitude,
			'longitude' => $location->location_longitude,
		);
	}
	/**
	 * Filter array used to generate location schema. See https://schema.org/location.
	 *
	 * @hook mc_location_schema
	 *
	 * @param {array}  $schema Schema data for an event venue.
	 * @param {object} $location My Calendar location object.
	 *
	 * @return array
	 */
	return apply_filters( 'mc_location_schema', $schema, $location );
}

/**
 * Get an author or host card for display on events.
 *
 * @param object $event Event object.
 * @param string $type Type of card.
 *
 * @return string
 */
function mc_template_user_card( $event, $type ) {
	/**
	 * Filter to enable or disable avatars.
	 *
	 * @hook mc_use_avatars
	 *
	 * @param {bool}   $avatars false to disable avatars.
	 * @param {object} $event My Calendar event object.
	 *
	 * @return {bool}
	 */
	$avatars = apply_filters( 'mc_use_avatars', true, $event );
	$card    = '';
	$type    = ( 'author' === $type ) ? 'author' : 'host';
	$user    = ( 'author' === $type ) ? $event->event_author : $event->event_host;
	$a       = false;
	if ( 0 !== (int) $user && is_numeric( $user ) ) {
		if ( function_exists( 'mcs_submissions' ) && 'true' === get_option( 'mcs_custom_hosts' ) && 'host' === $type ) {
			$a = get_post( $event->event_host );
			if ( $a ) {
				$avatar = ( '' === get_the_post_thumbnail( $a ) ) ? get_avatar( get_post_meta( $a->ID, '_mcs_host_email', true ) ) : get_the_post_thumbnail( $a );
				$name   = $a->post_title;
			} else {
				$avatar = ( $avatars ) ? get_avatar( $user ) : '';
				$a      = get_userdata( $user );
				$name   = $a->display_name;
			}
		} else {
			$avatar = ( $avatars ) ? get_avatar( $user ) : '';
			$a      = get_userdata( $user );
			if ( is_object( $a ) ) {
				$name = $a->display_name;
			} else {
				$name = '';
			}
		}
		if ( $a ) {
			if ( 'author' === $type ) {
				$text = ( '' !== mc_get_option( 'posted_by' ) ) ? mc_get_option( 'posted_by' ) : __( 'Posted by', 'my-calendar' );
			} else {
				$text = ( '' !== mc_get_option( 'hosted_by' ) ) ? mc_get_option( 'hosted_by' ) : __( 'Hosted by', 'my-calendar' );
			}
			$card = $avatar . '<p class="event-' . $type . '"><span class="posted">' . $text . '</span> <span class="' . $type . '-name">' . $name . "</span></p>\n";
			if ( $avatars ) {
				$card = '	<div class="mc-' . $type . '-card">' . $card . '</div>';
			}
		}
	}

	return $card;
}

/**
 * Templating getter for new templating system.
 *
 * @param object $event Object containing event and view data.
 * @param string $key Array key for data to fetch.
 *
 * @return string
 */
function mc_get_template_tag( $event, $key ) {
	if ( ! empty( $event->tags ) ) {
		$data = $event->tags;
	} else {
		$data = mc_create_tags( $event->event );
	}
	$value = ( isset( $data[ $key ] ) ) ? $data[ $key ] : '';

	return $value;
}

/**
 * Print template values for PHP templating system. Backwards compatible with display settings.
 *
 * @param object $data Calendar view object with (at minimum) property 'event' and 'tags'.
 * @param string $key Array key in the tags array for data to fetch.
 */
function mc_template_tag( $data, $key = 'calendar' ) {
	echo mc_kses_post( mc_get_template_tag( $data, $key ) );
}

/**
 * Print time in PHP templates. Backwards compatible with display settings.
 *
 * @param object $data Calendar view data.
 * @param string $type View type.
 */
function mc_template_time( $data, $type = 'calendar' ) {
	$event = $data->event;
	echo mc_kses_post( mc_time_html( $event, $type ) );
}

/**
 * Print author in PHP templates. Backwards compatible with display settings.
 *
 * @param object $data Calendar view data.
 * @param string $type View type.
 */
function mc_template_author( $data, $type = 'calendar' ) {
	$event  = $data->event;
	$author = '';
	if ( mc_output_is_visible( 'author', $type, $event ) ) {
		$author = mc_template_user_card( $event, 'author' );
	}

	echo wp_kses_post( $author );
}

/**
 * Print host in PHP templates. Backwards compatible with display settings.
 *
 * @param object $data Calendar view data.
 * @param string $type View type.
 */
function mc_template_host( $data, $type = 'calendar' ) {
	$event = $data->event;
	$host  = '';
	if ( mc_output_is_visible( 'host', $type, $event ) ) {
		$host = mc_template_user_card( $event, 'host' );
	}

	echo wp_kses_post( $host );
}

/**
 * Print accessibility features in PHP templates. Backwards compatible with display settings.
 *
 * @param object $data Calendar view data.
 * @param string $type View type.
 */
function mc_template_access( $data, $type = 'calendar' ) {
	$event  = $data->event;
	$access = '';
	if ( mc_output_is_visible( 'access', $type, $event ) ) {
		$access_heading = ( '' !== mc_get_option( 'event_accessibility', '' ) ) ? mc_get_option( 'event_accessibility' ) : __( 'Event Accessibility', 'my-calendar' );
		$access_content = mc_expand( get_post_meta( $event->event_post, '_mc_event_access', true ) );
		$sublevel       = 'h2';
		if ( 'mini' === $type || 'list' === $type || 'list' === $data->time ) {
			// In the mini calendar, levels are reduced one because there are multiple events.
			if ( 'list' === $data->time ) {
				$sublevel = 'h4';
			} else {
				$sublevel = 'h3';
			}
		}
		/**
		 * Filter subheading levels inside event content.
		 *
		 * @hook mc_subheading_level
		 *
		 * @param {string} $el Element name. Default 'h4' in grouped templates, h2 on single templates.
		 * @param {string} $type View type.
		 * @param {string} $time View timeframe.
		 * @param {string} $template Current template.
		 *
		 * @return {string}
		 */
		$sublevel = apply_filters( 'mc_subheading_level', $sublevel, $type, $data->time, 'php' );
		if ( $access_content ) {
			$access = '<div class="mc-accessibility"><' . $sublevel . '>' . $access_heading . '</' . $sublevel . '>' . $access_content . '</div>';
		}
		$access = ( '' !== $access ) ? '<div class="mc-access-information">' . $access . '</div>' : '';
	}

	echo wp_kses_post( $access );
}

/**
 * Print share features in PHP templates. Backwards compatible with display settings.
 *
 * @param object $data Calendar view data.
 * @param string $type View type.
 */
function mc_template_share( $data, $type = 'calendar' ) {
	$event = $data->event;
	$more  = '';
	$gcal  = '';
	$vcal  = '';
	if ( ( ! isset( $_GET['mc_id'] ) ) && mc_output_is_visible( 'more', $type, $event ) ) {
		$details_label = mc_get_details_label( $event, $data->tags );
		$details_link  = mc_get_details_link( $event );
		$event_title   = mc_draw_event_title( $event, $data->tags, $type, '' );
		$aria          = '';
		// If the event title is already in the details label, omit ARIA.
		if ( false === stripos( strip_tags( $details_label ), strip_tags( $event_title ) ) ) {
			$aria = " aria-label='" . esc_attr( "$details_label: " . strip_tags( $event_title ) ) . "'";
		}
		if ( _mc_is_url( $details_link ) ) {
			$more = "	<p class='mc-details'><a$aria href='" . esc_url( $details_link ) . "'>$details_label</a></p>\n";
		}
	}
	$more = apply_filters( 'mc_details_grid_link', $more, $event );
	if ( mc_output_is_visible( 'gcal', $type, $event ) ) {
		$gcal = "	<p class='gcal'>" . mc_draw_template( $data->tags, '{gcal_link}' ) . '</p>';
	}

	if ( mc_output_is_visible( 'ical', $type, $event ) ) {
		$vcal = "	<p class='ical'>" . mc_draw_template( $data->tags, '{ical_html}' ) . '</p>';
	}
	$sharing = ( '' === trim( $vcal . $gcal . $more ) ) ? '' : '	<div class="sharing">' . $vcal . $gcal . $more . '</div>';

	echo wp_kses_post( $sharing );
}

/**
 * Print featured image in PHP templates. Backwards compatible with display settings.
 *
 * @param object       $data Calendar view data.
 * @param string       $type View type.
 * @param string|array $size Image size as expected by `get_the_post_thumbnail`.
 */
function mc_template_image( $data, $type = 'calendar', $size = '' ) {
	$event = $data->event;
	$img   = '';
	if ( mc_output_is_visible( 'image', $type, $event ) ) {
		$img = mc_get_event_image( $event, $data->tags, $size );
	}

	echo wp_kses_post( $img );
}

/**
 * Print description content in PHP templates. Backwards compatible with display settings.
 *
 * @param object $data Calendar view data.
 * @param string $type View type.
 */
function mc_template_description( $data, $type = 'calendar' ) {
	$event       = $data->event;
	$description = '';
	if ( mc_output_is_visible( 'description', $type, $event ) ) {
		if ( '' !== trim( $event->event_desc ) ) {
			$description = wpautop( stripcslashes( mc_kses_post( $event->event_desc ) ), 1 );
			$description = "	<div class='longdesc description'>$description</div>";
		}
	}

	echo wp_kses_post( $description );
}

/**
 * Print registration information in PHP templates. Backwards compatible with display settings.
 *
 * @param object $data Calendar view data.
 * @param string $type View type.
 */
function mc_template_registration( $data, $type = 'calendar' ) {
	$event   = $data->event;
	$tickets = '';
	if ( mc_output_is_visible( 'tickets', $type, $event ) ) {
		$info     = wpautop( stripcslashes( mc_kses_post( $event->event_registration ) ) );
		$url      = esc_url( $event->event_tickets );
		$external = ( $url && mc_external_link( $url ) ) ? 'external' : '';
		$text     = ( '' !== mc_get_option( 'buy_tickets', '' ) ) ? mc_get_option( 'buy_tickets' ) : __( 'Buy Tickets', 'my-calendar' );
		$tickets  = ( $url ) ? "<a class='$external' href='" . $url . "'>" . $text . '</a>' : '';
		if ( '' !== trim( $info . $tickets ) ) {
			$tickets = '<div class="mc-registration">' . $info . $tickets . '</div>';
		}
	}

	echo wp_kses_post( $tickets );
}

/**
 * Print excerpt in PHP templates. Backwards compatible with display settings.
 *
 * @param object $data Calendar view data.
 * @param string $type View type.
 */
function mc_template_excerpt( $data, $type = 'calendar' ) {
	$event = $data->event;
	$short = '';
	if ( mc_output_is_visible( 'excerpt', $type, $event ) ) {
		if ( '' !== trim( $event->event_short ) ) {
			$short = wpautop( stripcslashes( mc_kses_post( $event->event_short ) ), 1 );
			$short = "<div class='shortdesc description'>$short</div>";
		}
	}

	echo wp_kses_post( $short );
}

/**
 * Print return link in PHP templates. Backwards compatible with display settings.
 *
 * @param object $data Calendar view data.
 * @param string $type View type.
 */
function mc_template_return( $data, $type = 'calendar' ) {
	$event = $data->event;
	/**
	 * Filter URL appended on single event view to return to calendar.
	 *
	 * @hook mc_return_uri
	 *
	 * @param {string} $url Calendar URL.
	 * @param {object} $event My Calendar event object.
	 *
	 * @return {string}
	 */
	$return_url = apply_filters( 'mc_return_uri', mc_get_uri( $event ), $event );
	$text       = ( '' !== mc_get_option( 'view_full', '' ) ) ? mc_get_option( 'view_full' ) : __( 'View full calendar', 'my-calendar' );
	$return     = ( 'single' === $type ) ? "	<p class='view-full'><a href='$return_url'>" . $text . '</a></p>' : '';

	echo wp_kses_post( $return );
}

/**
 * Print location in PHP templates. Backwards compatible with display settings.
 *
 * @param object $data Calendar view data.
 * @param string $type View type.
 */
function mc_template_location( $data, $type = 'calendar' ) {
	$event   = $data->event;
	$address = '';
	$map     = '';

	if ( mc_output_is_visible( 'address', $type, $event ) ) {
		$show_add = mc_output_is_visible( 'address', $type, $event ) ? 'true' : 'false';
		$show_map = mc_output_is_visible( 'gmap_link', $type, $event ) ? 'true' : 'false';

		$address = mc_hcard( $event, $show_add, $show_map );
	}
	if ( mc_output_is_visible( 'gmap', $type, $event ) ) {
		$map = ( is_singular( 'mc-events' ) || 'single' === $type ) ? mc_generate_map( $event ) : '';
	}
	$location = ( '' === trim( $map . $address ) ) ? '' : '	<div class="mc-location">' . $map . $address . '</div>';

	echo wp_kses_post( $location );
}

/**
 * Print external link in PHP templates. Backwards compatible with display settings.
 *
 * @param object $data Calendar object.
 * @param string $type View type.
 */
function mc_template_link( $data, $type = 'calendar' ) {
	$event      = $data->event;
	$event_link = mc_event_link( $event );
	$link       = '';
	if ( '' !== $event_link && mc_output_is_visible( 'link', $type, $event ) ) {
		$external_class = ( mc_external_link( $event_link ) ) ? "$type-link external url" : "$type-link url";
		$link_template  = ( '' !== mc_get_template( 'link' ) ) ? mc_get_template( 'link' ) : __( 'More information', 'my-calendar' );
		$link_text      = mc_draw_template( $data->tags, $link_template );
		$link           = "<p><a href='" . esc_url( $event_link ) . "' class='$external_class' aria-describedby='mc_{$event->occur_id}-title-$data->id'>" . $link_text . '</a></p>';
	}

	echo wp_kses_post( $link );
}
