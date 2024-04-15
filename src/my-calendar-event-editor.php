<?php
/**
 * Event Editor. Creation & Editing of events.
 *
 * @category Events
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle post generation, updating, and processing after event is saved
 *
 * @param string   $action edit, copy, add.
 * @param array    $data saved event data.
 * @param int      $event_id My Calendar event ID.
 * @param bool|int $result Results of DB query.
 *
 * @return int|false post ID or false if post ID not available or created.
 */
function mc_event_post( $action, $data, $event_id, $result = false ) {
	// if the event save was successful.
	$post_id = false;
	// Check if this is an ajax request.
	$post_post = isset( $_POST['post'] ) ? $_POST['post'] : array();
	$post_data = ( wp_doing_ajax() && ! empty( $_POST ) ) ? $post_post : $_POST;
	if ( $post_data !== $_POST && is_string( $post_data ) ) {
		parse_str( $post_data, $post );
	} else {
		$post = $post_data;
	}
	$post = map_deep( $post_data, 'wp_kses_post' );
	if ( 'add' === $action || 'copy' === $action ) {
		$post_id = mc_create_event_post( $data, $event_id );
	} elseif ( 'edit' === $action ) {
		if ( isset( $data['event_post'] ) && 'group' === $data['event_post'] ) {
			$post_id = mc_get_event_post( $event_id );
		} else {
			if ( isset( $post['event_post'] ) && ( 0 === (int) $post['event_post'] || '' === $post['event_post'] ) ) {
				$post_id = mc_create_event_post( $data, $event_id );
			} else {
				$post_id = ( isset( $post['event_post'] ) ) ? absint( $post['event_post'] ) : false;
				$post_id = ( isset( $data['event_post'] ) ) ? absint( $data['event_post'] ) : $post_id;
			}
		}
		// If, after all that, the post doesn't exist, create it.
		if ( ! get_post_status( $post_id ) ) {
			mc_create_event_post( $data, $event_id );
		}
		$categories = mc_get_categories( $event_id );
		$terms      = array();
		$privacy    = 'publish';

		foreach ( $categories as $category ) {
			$term = mc_get_category_detail( $category, 'category_term' );
			if ( ! $term ) {
				$term = wp_insert_term( mc_get_category_detail( $category, 'category_name' ), 'mc-event-category' );
				$term = ( ! is_wp_error( $term ) ) ? $term['term_id'] : false;
				if ( $term ) {
					mc_update_category( 'category_term', $term, $category );
				}
			}
			// if any selected category is private, make private.
			if ( 'private' !== $privacy ) {
				$status = ( '1' === mc_get_category_detail( $category, 'category_private' ) ) ? 'private' : $privacy;
			}
			$terms[] = (int) $term;
		}
		$event_in_trash = ( isset( $data['event_approved'] ) && 2 === (int) $data['event_approved'] ) ? true : false;
		if ( $event_in_trash ) {
			$status = 'trash';
		} else {
			$status = $privacy;
		}

		$title = $data['event_title'];
		/**
		 * Filter post template when event post is generated.
		 *
		 * @hook mc_post_template
		 *
		 * @param {string} $template Template keyword or structure.
		 * @param {array}  $terms Terms attached to this event.
		 *
		 * @return {string}
		 */
		$template          = apply_filters( 'mc_post_template', 'details', $terms );
		$data['shortcode'] = "[my_calendar_event event='$event_id' template='$template' list='']";
		$description       = $data['event_desc'];
		$excerpt           = $data['event_short'];
		$auth              = ( isset( $data['event_author'] ) ) ? $data['event_author'] : get_current_user_id();
		$type              = 'mc-events';
		$my_post           = array(
			'ID'           => $post_id,
			'post_title'   => $title,
			'post_content' => $description,
			'post_status'  => $status,
			'post_author'  => $auth,
			'post_name'    => sanitize_title( $title ),
			'post_type'    => $type,
			'post_excerpt' => $excerpt,
		);
		if ( mc_switch_sites() && defined( BLOG_ID_CURRENT_SITE ) ) {
			switch_to_blog( BLOG_ID_CURRENT_SITE );
		}
		$post_id = wp_update_post( $my_post );
		wp_set_object_terms( $post_id, $terms, 'mc-event-category' );
		if ( '' === $data['event_image'] ) {
			delete_post_thumbnail( $post_id );
		} else {
			// check POST data.
			$attachment_id = ( isset( $post['event_image_id'] ) && is_numeric( $post['event_image_id'] ) ) ? $post['event_image_id'] : false;
			$attachment_id = ( isset( $data['event_image_id'] ) && is_numeric( $data['event_image_id'] ) ) ? $data['event_image_id'] : $attachment_id;
			if ( $attachment_id ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}
		// Set location access.
		$access       = ( isset( $post['event_access'] ) ) ? $post['event_access'] : array();
		$access_terms = implode( ',', array_values( $access ) );
		mc_update_event( 'event_access', $access_terms, $event_id, '%s' );
		mc_add_post_meta_data( $post_id, $post, $data, $event_id );
		/**
		 * Execute action when an event's post is updated.
		 *
		 * @hook mc_update_event_post
		 *
		 * @param {int}   $post_id Post ID.
		 * @param {array} $post POST data.
		 * @param {array} $data Submitted event data.
		 * @param {int}   $event_id Event ID.
		 */
		do_action( 'mc_update_event_post', $post_id, $post, $data, $event_id );
		if ( mc_switch_sites() ) {
			restore_current_blog();
		}
	}

	return $post_id;
}

/**
 * Add post meta data to an event post.
 *
 * @param int   $post_id Post ID.
 * @param array $post Post object.
 * @param array $data Event POST data or event data.
 * @param int   $event_id Event ID.
 */
function mc_add_post_meta_data( $post_id, $post, $data, $event_id ) {
	// access features for the event.
	$description = isset( $data['event_desc'] ) ? $data['event_desc'] : '';
	$image       = isset( $data['event_image'] ) ? esc_url_raw( $data['event_image'] ) : '';
	$guid        = get_post_meta( $post_id, '_mc_guid', true );
	if ( '' === $guid ) {
		$guid = md5( $post_id . $event_id . $data['event_title'] );
		update_post_meta( $post_id, '_mc_guid', $guid );
	}
	update_post_meta( $post_id, '_mc_event_shortcode', $data['shortcode'] );
	$events_access = '';
	if ( isset( $post['events_access'] ) ) {
		$events_access = map_deep( $post['events_access'], 'sanitize_text_field' );
	} else {
		// My Calendar Rest API.
		if ( isset( $post['data'] ) && isset( $post['data']['events_access'] ) ) {
			$events_access = $post['data']['events_access'];
		}
	}
	$time_label = '';
	if ( isset( $_POST['event_time_label'] ) ) {
		$time_label = sanitize_text_field( $_POST['event_time_label'] );
	} else {
		// My Calendar Rest API.
		if ( isset( $post['data'] ) && isset( $post['data']['event_time_label'] ) ) {
			$time_label = $post['data']['event_time_label'];
		}
	}
	$same_day = '';
	if ( isset( $_POST['event_same_day'] ) ) {
		$same_day = sanitize_text_field( $_POST['event_same_day'] );
	} else {
		// My Calendar Rest API.
		if ( isset( $post['data'] ) && isset( $post['data']['event_same_day'] ) ) {
			$same_day = $post['data']['event_same_day'];
		}
	}
	if ( $events_access ) {
		update_post_meta( $post_id, '_mc_event_access', $events_access );
	}
	if ( $time_label ) {
		update_post_meta( $post_id, '_event_time_label', $time_label );
	}
	if ( $same_day ) {
		update_post_meta( $post_id, '_event_same_day', 'true' );
	}

	$mc_event_id = get_post_meta( $post_id, '_mc_event_id', true );
	if ( ! $mc_event_id ) {
		update_post_meta( $post_id, '_mc_event_id', $event_id );
	}
	update_post_meta( $post_id, '_mc_event_desc', $description );
	update_post_meta( $post_id, '_mc_event_image', $image );
	// This is only used by My Tickets, so only the first date occurrence is required.
	if ( isset( $data['event_begin'] ) ) {
		$event_date = ( is_array( $data['event_begin'] ) ) ? $data['event_begin'][0] : $data['event_begin'];
		update_post_meta( $post_id, '_mc_event_date', strtotime( $event_date ) );
	}
	$location_id = ( isset( $post['location_preset'] ) && is_numeric( $post['location_preset'] ) ) ? (int) $post['location_preset'] : false;
	if ( $location_id ) { // only change location ID if dropdown set.
		update_post_meta( $post_id, '_mc_event_location', $location_id );
		mc_update_event( 'event_location', $location_id, $event_id );
	}
	$previous_data = get_post_meta( $post_id, '_mc_event_data', true );
	if ( is_array( $previous_data ) ) {
		$data = array_merge( $previous_data, $data );
	}
	update_post_meta( $post_id, '_mc_event_data', $data );
}

/**
 * Create a post for My Calendar event data on save
 *
 * @param array $data Saved event data.
 * @param int   $event_id Newly-saved event ID.
 *
 * @return int newly created post ID
 */
function mc_create_event_post( $data, $event_id ) {
	$post_id = mc_get_event_post( $event_id );
	// Check if this is an ajax request.
	$post_post = isset( $_POST['post'] ) ? $_POST['post'] : array();
	$post_data = ( wp_doing_ajax() && ! empty( $_POST ) ) ? $post_post : $_POST;
	if ( $post_data !== $_POST && is_string( $post_data ) ) {
		parse_str( $post_data, $post );
	} else {
		$post = $post_data;
	}
	$post = map_deep( $post_data, 'wp_kses_post' );
	if ( ! $post_id ) {
		$categories = mc_get_categories( $event_id );
		$terms      = array();
		$term       = null;
		$privacy    = 'publish';
		foreach ( $categories as $category ) {
			$term = mc_get_category_detail( $category, 'category_term' );
			// if any selected category is private, make private.
			if ( 'private' !== $privacy ) {
				$privacy = ( '1' === mc_get_category_detail( $category, 'category_private' ) ) ? 'private' : 'publish';
			}
			$terms[] = (int) $term;
		}
		$title = $data['event_title'];
		/**
		 * Filter post template when event post is generated.
		 *
		 * @hook mc_post_template
		 *
		 * @param {string} $template Template keyword or structure.
		 * @param {array}  $terms Terms attached to this event.
		 *
		 * @return {string}
		 */
		$template          = apply_filters( 'mc_post_template', 'details', $terms );
		$data['shortcode'] = "[my_calendar_event event='$event_id' template='$template' list='']";
		$description       = isset( $data['event_desc'] ) ? $data['event_desc'] : '';
		$excerpt           = isset( $data['event_short'] ) ? $data['event_short'] : '';
		$location_id       = isset( $data['event_location'] ) ? $data['event_location'] : 0;
		if ( isset( $post['location_preset'] ) && is_numeric( $post['location_preset'] ) ) {
			$location_id = (int) $post['location_preset'];
		} elseif ( isset( $data['location_preset'] ) && is_numeric( $data['location_preset'] ) ) {
			$location_id = $data['location_preset'];
		}
		$post_status = $privacy;
		$auth        = $data['event_author'];
		$type        = 'mc-events';
		$my_post     = array(
			'post_title'   => $title,
			'post_content' => $description,
			'post_status'  => $post_status,
			'post_author'  => $auth,
			'post_name'    => sanitize_title( $title ),
			'post_date'    => current_time( 'Y-m-d H:i:s' ),
			'post_type'    => $type,
			'post_excerpt' => $excerpt,
		);
		$post_id     = wp_insert_post( $my_post );
		wp_set_object_terms( $post_id, $terms, 'mc-event-category' );
		$attachment_id = false;
		if ( isset( $post['event_image_id'] ) ) {
			$attachment_id = (int) $post['event_image_id'];
		} elseif ( isset( $data['event_image_id'] ) ) {
			$attachment_id = (int) $data['event_image_id'];
		}
		if ( $attachment_id ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}
		mc_update_event( 'event_post', $post_id, $event_id );
		mc_update_event( 'event_location', $location_id, $event_id );
		mc_add_post_meta_data( $post_id, $post, $data, $event_id );
		/**
		 * Execute action when an event's post is updated.
		 *
		 * @hook mc_update_event_post
		 *
		 * @param {int}   $post_id Post ID.
		 * @param {array} $post POST data.
		 * @param {array} $data Submitted event data.
		 * @param {int}   $event_id Event ID.
		 */
		do_action( 'mc_update_event_post', $post_id, $post, $data, $event_id );
		wp_publish_post( $post_id );
	}

	return $post_id;
}

/**
 * Delete event posts when event is deleted
 *
 * @param array $deleted Array of event IDs.
 */
function mc_event_delete_posts( $deleted ) {
	foreach ( $deleted as $delete ) {
		$posts = get_posts(
			array(
				'post_type'  => 'mc-events',
				'meta_key'   => '_mc_event_id',
				'meta_value' => $delete,
			)
		);
		if ( isset( $posts[0] ) && is_object( $posts[0] ) ) {
			$post_id = $posts[0]->ID;
			wp_delete_post( $post_id, true );
		}
	}
}

/**
 * Delete custom post type associated with event
 *
 * @param int $event_id Event ID.
 * @param int $post_id Post ID.
 */
function mc_event_delete_post( $event_id, $post_id ) {
	/**
	 * Execute action when an event's post is deleted.
	 *
	 * @hook mc_deleted_post
	 *
	 * @param {int} $event_id Event ID.
	 * @param {int} $post_id Post ID.
	 */
	do_action( 'mc_deleted_post', $event_id, $post_id );
	wp_delete_post( $post_id, true );
}

/**
 * Update a single field in an event.
 *
 * @param string         $field database column.
 * @param mixed          $data value to be saved.
 * @param string|integer $event could be integer or string.
 * @param string         $type signifier representing data type of $data (e.g. %d or %s).
 *
 * @return int Number of rows affected.
 */
function mc_update_event( $field, $data, $event, $type = '%d' ) {
	global $wpdb;
	$field = sanitize_key( $field );
	if ( '%d' === $type ) {
		$sql = 'UPDATE ' . my_calendar_table() . " SET $field = %d WHERE event_id=%d";
	} elseif ( '%s' === $type ) {
		$sql = 'UPDATE ' . my_calendar_table() . " SET $field = %s WHERE event_id=%d";
	} else {
		$sql = 'UPDATE ' . my_calendar_table() . " SET $field = %f WHERE event_id=%d";
	}
	$result = $wpdb->query( $wpdb->prepare( $sql, $data, $event ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return $result;
}

/**
 * Generate inner wrapper for editing and managing events
 */
function my_calendar_edit() {
	mc_check_imports();

	$action   = ! empty( $_POST['event_action'] ) ? sanitize_text_field( $_POST['event_action'] ) : '';
	$event_id = ! empty( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : '';

	if ( isset( $_GET['mode'] ) ) {
		$action = sanitize_text_field( $_GET['mode'] );
		if ( 'edit' === $action || 'copy' === $action ) {
			$event_id = (int) $_GET['event_id'];
		}
	}

	if ( isset( $_POST['event_action'] ) ) {
		$post  = map_deep( $_POST, 'mc_kses_post' );
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'My Calendar: Security check failed' );
		}

		global $mc_output;
		$count = 0;

		if ( isset( $post['event_begin'] ) && is_array( $post['event_begin'] ) ) {
			$count = count( $post['event_begin'] );
		} else {
			$response = my_calendar_save( $action, $mc_output, (int) $post['event_id'] );
			echo wp_kses_post( $response['message'] );
		}
		for ( $i = 0; $i < $count; $i++ ) {
			$mc_output = mc_check_data( $action, $post, $i );
			if ( 'add' === $action || 'copy' === $action ) {
				$response = my_calendar_save( $action, $mc_output );
			} else {
				$response = my_calendar_save( $action, $mc_output, (int) $post['event_id'] );
			}
			echo wp_kses_post( $response['message'] );
		}
		if ( isset( $post['ref'] ) ) {
			$url = urldecode( sanitize_text_field( $post['ref'] ) );
			// A link to the main calendar is always shown. Only show this if it was a different calendar.
			if ( mc_get_uri() !== $url ) {
				mc_show_notice( "<a href='" . esc_url( $url ) . "'>" . __( 'Return to Calendar', 'my-calendar' ) . '</a>' );
			}
		}
	}
	?>

	<div class="wrap my-calendar-admin">
	<?php
	my_calendar_check_db();
	if ( '2' === get_site_option( 'mc_multisite' ) ) {
		if ( '0' === get_option( 'mc_current_table' ) ) {
			$message = __( 'Currently editing your local calendar', 'my-calendar' );
		} else {
			$message = __( 'Currently editing your central calendar', 'my-calendar' );
		}
		mc_show_notice( $message );
	}
	if ( 'edit' === $action ) {
		?>
		<h1 id="mc-edit" class="wp-heading-inline"><?php esc_html_e( 'Edit Event', 'my-calendar' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'my-calendar' ); ?></a>
		<hr class="wp-header-end">
		<?php
		if ( empty( $event_id ) ) {
			mc_show_error( __( 'You must provide an event ID to edit events.', 'my-calendar' ) );
		} else {
			mc_edit_event_form( 'edit', $event_id );
		}
	} elseif ( 'copy' === $action ) {
		?>
		<h1><?php esc_html_e( 'Copy Event', 'my-calendar' ); ?></h1>
		<?php
		if ( empty( $event_id ) ) {
			mc_show_error( __( 'You must provide an event ID to copy events.', 'my-calendar' ) );
		} else {
			mc_edit_event_form( 'copy', $event_id );
		}
	} else {
		?>
		<h1><?php esc_html_e( 'Add Event', 'my-calendar' ); ?></h1>
		<?php
		mc_edit_event_form();
	}
	mc_show_sidebar();
	?>
	</div>
	<?php
}

/**
 * Save an event to the database
 *
 * @param string      $action Type of action.
 * @param array       $output Checked event data & post data.
 * @param int|boolean $event_id Event ID or false for new events.
 *
 * @return array Array with event_id, event_post, and message keys.
 */
function my_calendar_save( $action, $output, $event_id = false ) {
	global $wpdb;
	$proceed    = (bool) $output[0];
	$post       = $output[4];
	$message    = '';
	$event_post = false;
	$formats    = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d' );

	if ( ( 'add' === $action || 'copy' === $action ) && true === $proceed ) {
		$add  = $output[2]; // add format here.
		$data = $output[2];
		$cats = $add['event_categories'];

		unset( $add['event_categories'] );
		// My Calendar no longer saves location data to the events table, and it'll cause problems if it's in the array.
		if ( isset( $add['event_label'] ) ) {
			unset( $add['event_label'] );
			unset( $add['event_street'] );
			unset( $add['event_street2'] );
			unset( $add['event_city'] );
			unset( $add['event_state'] );
			unset( $add['event_postcode'] );
			unset( $add['event_region'] );
			unset( $add['event_country'] );
			unset( $add['event_url'] );
			unset( $add['event_phone'] );
			unset( $add['event_phone2'] );
			unset( $add['event_longitude'] );
			unset( $add['event_latitude'] );
		}
		/**
		 * Filter updated event data prior to saving.
		 *
		 * @hook mc_before_save_insert
		 *
		 * @param {array} $add Newly added event data.
		 *
		 * @return {array}
		 */
		$add      = apply_filters( 'mc_before_save_insert', $add );
		$result   = $wpdb->insert( my_calendar_table(), $add, $formats );
		$event_id = $wpdb->insert_id;
		mc_increment_event( $event_id );
		mc_set_category_relationships( $cats, $event_id );
		if ( ! $result ) {
			$message = mc_show_error( __( "I'm sorry! I couldn't add that event to the database.", 'my-calendar' ), false );
		} else {
			// do an action using the $action and processed event data.
			$event_error = '';
			$event_link  = '';
			$edit_link   = '';

			$event_post = mc_event_post( $action, $data, $event_id, $result );
			/**
			 * Run action when an event is saved.
			 *
			 * @hook mc_save_event
			 *
			 * @param {string}    $action Current action: edit, copy, add.
			 * @param {array}     $data Data updated.
			 * @param {int}       $event_id Event ID.
			 * @param {int|false} $result Result of the DB update query.
			 */
			do_action( 'mc_save_event', $action, $data, $event_id, $result );
			$event = mc_get_first_event( $event_id );

			if ( 'true' === mc_get_option( 'event_mail' ) ) {
				// insert_id is last occurrence inserted in the db.
				if ( 1 === (int) $event->event_flagged ) {
					/**
					 * Action executed when an event is marked as spam and eemail notifications are enabled.
					 *
					 * @hook mc_notify_event_spam
					 *
					 * @param {object} $event Event object.
					 */
					do_action( 'mc_notify_event_spam', $event );
				} else {
					my_calendar_send_email( $event );
				}
			}
			if ( is_admin() && ! wp_doing_ajax() ) {
				$edit_link = add_query_arg(
					array(
						'event_id' => $event_id,
						'mode'     => 'edit',
					),
					admin_url( 'admin.php?page=my-calendar' )
				);
			} else {
				$edit_link = ( function_exists( 'mcs_submit_url' ) ) ? mcs_submit_url( $event_id, $event ) : '';
			}
			if ( '0' === (string) $add['event_approved'] ) {
				$edit_event = '';
				if ( mc_can_edit_event( $event_id ) && '' !== $edit_link ) {
					$edit_event = sprintf( ' <a href="%s">' . __( 'Continue editing event.', 'my-calendar' ) . '</a>', $edit_link );
				}
				$message = mc_show_notice( __( 'Event draft saved.', 'my-calendar' ) . $edit_event, false, 'draft-saved' );
			} else {
				// jd_doTwitterAPIPost was changed to wpt_post_to_twitter on 1.19.2017.
				if ( function_exists( 'wpt_post_to_twitter' ) && isset( $post['mc_twitter'] ) && '' !== trim( $post['mc_twitter'] ) ) {
					wpt_post_to_twitter( stripslashes( $post['mc_twitter'] ) );
				}
				$event_ids = mc_get_occurrences( $event_id );
				if ( ! empty( $event_ids ) ) {
					$event_link  = mc_get_details_link( $event_ids[0]->occur_id );
					$event_error = mc_error_check( $event_ids[0]->occur_event_id );
				}

				if ( '' !== trim( $event_error ) ) {
					$message = $event_error;
				} else {
					$message = __( 'Event added. It will now show on the calendar.', 'my-calendar' );
					if ( $event_link ) {
						// Translators: URL to view event in calendar.
						$message .= sprintf( __( ' <a href="%s" class="button">View Event</a>', 'my-calendar' ), $event_link );
						if ( mc_can_edit_event( $event_id ) && '' !== $edit_link ) {
							// Translators: URL to edit event.
							$message .= sprintf( __( ' <a href="%s" class="button">Edit Event</a>', 'my-calendar' ), $edit_link );
						}
					} else {
						$message .= __( ' No link was generated for this event. There may be an unknown error.', 'my-calendar' );
					}
					$message = mc_show_notice( $message, false, 'new-event' );
				}
			}
		}
	}

	if ( 'edit' === $action && true === $proceed ) {
		$result = true;
		// Translators: URL to view calendar.
		$url = sprintf( __( 'View <a href="%s">your calendar</a>.', 'my-calendar' ), mc_get_uri() );
		if ( mc_can_edit_event( $event_id ) ) {
			$update = isset( $output['checked'] ) ? $output['checked'] : $output[2];
			// If the previously stored location is not the same as a new location, remove event location fields.
			if ( isset( $post['preset_location'] ) && ( $post['preset_location'] !== $post['location_preset'] ) && ! empty( $post['event_label'] ) ) {
				mc_remove_location_from_event( $event_id );
			}
			// Update event category data.
			$cats = $update['event_categories'];
			unset( $update['event_categories'] );
			mc_update_category_relationships( $cats, $event_id );

			/**
			 * Filter updated event data prior to saving.
			 *
			 * @hook mc_before_save_update
			 *
			 * @param {array} $update Updated event data.
			 * @param {int}   $event_id Event ID.
			 *
			 * @return {array}
			 */
			$update       = apply_filters( 'mc_before_save_update', $update, $event_id );
			$endtime      = mc_date( 'H:i:00', mc_strtotime( $update['event_endtime'] ), false );
			$prev_eb      = ( isset( $post['prev_event_begin'] ) ) ? $post['prev_event_begin'] : '';
			$prev_et      = ( isset( $post['prev_event_time'] ) ) ? $post['prev_event_time'] : '';
			$prev_ee      = ( isset( $post['prev_event_end'] ) ) ? $post['prev_event_end'] : '';
			$prev_eet     = ( isset( $post['prev_event_endtime'] ) ) ? $post['prev_event_endtime'] : '';
			$update_time  = mc_date( 'H:i:00', mc_strtotime( $update['event_time'] ), false );
			$date_changed = ( $update['event_begin'] !== $prev_eb || $update_time !== $prev_et || $update['event_end'] !== $prev_ee || ( $endtime !== $prev_eet && ( '' !== $prev_eet && '23:59:59' !== $endtime ) ) ) ? true : false;
			if ( isset( $post['event_instance'] ) ) {
				// compares the information sent to the information saved for a given event.
				$is_changed     = mc_compare( $update, $event_id );
				$event_instance = (int) $post['event_instance'];
				if ( $is_changed ) {
					// if changed, create new event, match group id, update instance to reflect event connection, same group id.
					// if group ID == 0, need to add group ID to both records.
					// if a single instance is edited, it should not inherit recurring settings from parent.
					$update['event_recur'] = 'S1';
					if ( 0 === (int) $update['event_group_id'] ) {
						$update['event_group_id'] = $event_id;
						mc_update_data( $event_id, 'event_group_id', $event_id );
					}
					// retain saved location unless actively changed.
					if ( isset( $post['preset_location'] ) && 'none' === $post['location_preset'] ) {
						$location                 = absint( $post['preset_location'] );
						$update['event_location'] = $location;
					}
					$wpdb->insert( my_calendar_table(), $update, $formats );
					// need to get this variable into URL for form submit.
					$new_event = $wpdb->insert_id;
					mc_update_category_relationships( $cats, $new_event );
					$result = mc_update_instance( $event_instance, $new_event, $update );
				} else {
					if ( $update['event_begin'][0] === $post['prev_event_begin'] && $update['event_end'][0] === $post['prev_event_end'] ) {
						// There were no changes at all.
					} else {
						// Only dates were changed.
						$result  = mc_update_instance( $event_instance, $event_id, $update );
						$message = mc_show_notice( __( 'Date/time information for this event has been updated.', 'my-calendar' ) . " $url", false, 'date-updated' );
					}
				}
			} else {
				$result = $wpdb->update(
					my_calendar_table(),
					$update,
					array(
						'event_id' => $event_id,
					),
					$formats,
					'%d'
				);

				if ( ! isset( $post['event_recur'] ) && isset( $post['event_repeats'] ) ) {
					unset( $post['event_repeats'] );
				}
				// Only execute new increments if 'event_repeats' is present or date/time has changed.
				if ( isset( $post['event_repeats'] ) || $date_changed ) {
					if ( isset( $post['prev_event_repeats'] ) && isset( $post['prev_event_recur'] ) ) {
						$recur_changed = ( $update['event_repeats'] !== $post['prev_event_repeats'] || $update['event_recur'] !== $post['prev_event_recur'] ) ? true : false;
					} else {
						$recur_changed = false;
					}
					if ( $date_changed || $recur_changed ) {
						// Function mc_increment_event uses previous events and re-uses same ID if new has same date as old event.
						$instances = mc_get_instances( $event_id );
						mc_delete_instances( $event_id );
						// Delete previously created custom & deleted instance records.
						$post_ID = mc_get_data( 'event_post', $event_id );
						delete_post_meta( $post_ID, '_mc_custom_instances' );
						delete_post_meta( $post_ID, '_mc_deleted_instances' );
						mc_increment_event( $event_id, array(), false, $instances );
					}
				}
			}
			$data       = $update;
			$event_post = mc_event_post( $action, $data, $event_id, $result );
			/**
			 * Run action when an event is saved.
			 *
			 * @hook mc_save_event
			 *
			 * @param {string}    $action Current action: edit, copy, add.
			 * @param {array}     $data Data updated.
			 * @param {int}       $event_id Event ID.
			 * @param {int|false} $result Result of the DB update query.
			 */
			do_action( 'mc_save_event', $action, $data, $event_id, $result );
			// Delete transient caches.
			delete_transient( 'mc_categories_' . $event_id );
			delete_transient( 'mc_first_event_cache_' . $event_id );
			delete_transient( 'mc_get_date_bounds' );
			if ( false === $result ) {
				$message = mc_show_error( __( 'Your event was not updated.', 'my-calendar' ) . " $url", false );
			} else {
				// do an action using the $action and processed event data.
				$new_event_status = ( current_user_can( 'mc_approve_events' ) ) ? 1 : 0;
				// check for event_approved provides support for older versions of My Calendar Pro.
				if ( isset( $post['event_approved'] ) && $post['event_approved'] !== $new_event_status ) {
					$new_event_status = absint( $post['event_approved'] );
				}
				if ( isset( $post['prev_event_status'] ) ) {
					/**
					 * Execute an action when an event changes status.
					 *
					 * @hook mc_transition_event
					 *
					 * @param {int}    $prev_event_status Previous status.
					 * @param {int}    $new_event_status New status.
					 * @param {string} $action Action being performed.
					 * @param {array}  $data Submitted event data.
					 * @param {int}    $event_id Event ID.
					 */
					do_action( 'mc_transition_event', (int) $post['prev_event_status'], $new_event_status, $action, $data, $event_id );
				}
				$message = mc_show_notice( __( 'Event updated successfully', 'my-calendar' ) . ". $url", false, 'event-updated' );
			}
		} else {
			$message = mc_show_error( __( 'You do not have sufficient permissions to edit that event.', 'my-calendar' ), false );
		}
	}

	$message        = $message . "\n" . $output[3]; // Errors.
	$saved_response = array(
		'event_id'   => $event_id,
		'event_post' => $event_post,
		'message'    => $message,
	);
	mc_update_count_cache();

	/**
	 * Filter message returned to user after saving an event.
	 *
	 * @hook mc_event_saved_message
	 *
	 * @param {array} $saved_response Array with event ID and message text.
	 *
	 * @return {array}
	 */
	return apply_filters( 'mc_event_saved_message', $saved_response );
}

/**
 * Delete an event given event ID
 *
 * @param int $event_id Event ID.
 *
 * @return string message
 */
function mc_delete_event( $event_id ) {
	global $wpdb;
	// Deal with deleting an event from the database.
	if ( empty( $event_id ) ) {
		$message = mc_show_error( __( "You can't delete an event if you haven't submitted an event id", 'my-calendar' ), false );
	} else {
		$event_id = absint( $event_id );
		$event_in = false;
		$instance = false;
		$post_id  = mc_get_data( 'event_post', $event_id );
		if ( empty( $_POST['event_instance'] ) ) {
			// Delete from instance table.
			$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . my_calendar_event_table() . ' WHERE occur_event_id=%d', $event_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			// Delete from event table.
			$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . my_calendar_table() . ' WHERE event_id=%d', $event_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$result = $wpdb->get_results( $wpdb->prepare( 'SELECT event_id FROM ' . my_calendar_table() . ' WHERE event_id=%d', $event_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			// Delete category relationship records.
			$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . my_calendar_category_relationships_table() . ' WHERE event_id=%d', $event_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$event_in = absint( $_POST['event_instance'] );
			$result   = $wpdb->get_results( $wpdb->prepare( 'DELETE FROM ' . my_calendar_event_table() . ' WHERE occur_id=%d', $event_in ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$instance = true;
		}
		if ( empty( $result ) || empty( $result[0]->event_id ) ) {
			// Do an action using the event_id.
			if ( $instance ) {
				/**
				 * Action run when a single date of an event is deleted.
				 *
				 * @hook mc_delete_event_instance
				 *
				 * @param {int} $event_id Event ID.
				 * @param {int} $post_id Post ID.
				 * @param {int} $event_in Event instance ID.
				 */
				do_action( 'mc_delete_event_instance', $event_id, $post_id, $event_in );
			} else {
				/**
				 * Action run when an event is deleted.
				 *
				 * @hook mc_delete_event
				 *
				 * @param {int} $event_id Event ID.
				 * @param {int} $post_id Event Post ID.
				 */
				do_action( 'mc_delete_event', $event_id, $post_id );
			}
			$message = mc_show_notice( __( 'Event deleted successfully', 'my-calendar' ), false, 'event-deleted' );
		} else {
			$message = mc_show_error( __( 'Despite issuing a request to delete, the event still remains in the database. Please investigate.', 'my-calendar' ), false );
		}
	}
	mc_update_count_cache();

	return $message;
}

/**
 * Get form data for an event ID
 *
 * @param int|false $event_id My Calendar event ID or false if submission had errors.
 *
 * @return array|object|string submitted or saved data. Error message string if event not found.
 */
function mc_form_data( $event_id = false ) {
	global $wpdb, $submission;
	if ( false !== $event_id ) {
		$event_id = absint( $event_id );
		$data     = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . my_calendar_table() . ' WHERE event_id=%d LIMIT 1', $event_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( empty( $data ) ) {
			return mc_show_error( __( "Sorry! We couldn't find an event with that ID.", 'my-calendar' ), false );
		}
		$data = $data[0];
		// Recover users entries if there was an error.
		if ( ! empty( $submission ) ) {
			if ( property_exists( $data, 'event_id' ) ) {
				$submission->event_id = $data->event_id;
			}
			$data = $submission;
		}
	} else {
		// Deal with possibility that form was submitted but not saved due to error - recover user's entries.
		$data = $submission;
	}

	return $data;
}

/**
 * The event edit form for the manage events admin page
 *
 * @param string    $mode add, edit, or copy.
 * @param int|false $event_id My Calendar event ID (false for new events).
 *
 * @return void
 */
function mc_edit_event_form( $mode = 'add', $event_id = false ) {
	global $submission;

	if ( $event_id && ! mc_can_edit_event( $event_id ) ) {
		mc_show_error( __( 'You do not have permission to edit this event.', 'my-calendar' ) );

		return;
	}

	if ( $event_id ) {
		$data = mc_form_data( $event_id );
	} else {
		$data = $submission;
	}

	/**
	 * Filter notices. This is really handled and used as an action, but is still labeled a filter for backwards compatibility.
	 *
	 * @hook mc_event_notices
	 *
	 * @param {string} $output Output (unused).
	 * @param {object} $data Event object.
	 * @param {int}    $event_id Event ID.
	 *
	 * @return void
	 */
	apply_filters( 'mc_event_notices', '', $data, $event_id );

	if ( is_object( $data ) && 1 !== (int) $data->event_approved && 'edit' === $mode ) {
		if ( 0 === (int) $data->event_approved ) {
			mc_show_error( __( '<strong>Draft</strong>: Publish this event to show it on the calendar.', 'my-calendar' ) );
		} else {
			mc_show_error( __( '<strong>Trash</strong>: Remove from the trash to show this event on the calendar.', 'my-calendar' ) );
		}
	}

	mc_form_fields( $data, $mode, $event_id );
}

/**
 * Whether we should show the edit fields for an enabled block of fields.
 *
 * @param string $field Name of field group.
 *
 * @return boolean
 */
function mc_show_edit_block( $field ) {
	$admin = ( 'true' === mc_get_option( 'input_options_administrators', 'true' ) && current_user_can( 'manage_options' ) ) ? true : false;
	// Backwards compatibility. Collapsed location field settings into a single setting in 3.3.0.
	$field = ( 'event_location_dropdown' === $field ) ? 'event_location' : $field;
	$input = mc_get_option( 'input_options' );
	// Array of all options in default position.
	$defaults = mc_input_defaults();

	$input = array_merge( $defaults, $input );
	$user  = get_current_user_id();
	$show  = get_user_meta( $user, 'mc_show_on_page', true );
	if ( empty( $show ) || $show < 1 ) {
		$show = mc_get_option( 'input_options' );
	}
	// if this doesn't exist in array, leave it on.
	if ( ! isset( $input[ $field ] ) || ! isset( $show[ $field ] ) ) {
		return true;
	}
	if ( $admin ) {
		if ( isset( $show[ $field ] ) && 'on' === $show[ $field ] ) {
			return true;
		} else {
			return false;
		}
	} else {
		if ( 'off' === $input[ $field ] || '' === $input[ $field ] ) {
			return false;
		} elseif ( 'off' === $show[ $field ] ) {
			return false;
		} else {
			return true;
		}
	}
}

/**
 * Does an editing block contain visible fields.
 *
 * @param string $field Name of field group.
 *
 * @return bool
 */
function mc_edit_block_is_visible( $field ) {
	$admin = ( 'true' === mc_get_option( 'input_options_administrators' ) && current_user_can( 'manage_options' ) ) ? true : false;
	$input = mc_get_option( 'input_options' );
	// Array of all options in default position.
	$defaults = mc_input_defaults();

	$input  = array_merge( $defaults, $input );
	$user   = get_current_user_id();
	$screen = get_current_screen();
	$show   = get_user_meta( $user, 'mc_show_on_page', true );
	if ( empty( $show ) || $show < 1 ) {
		$show = mc_get_option( 'input_options' );
	}
	if ( empty( $show ) ) {
		$show = $defaults;
	}

	// if this doesn't exist in array, return false. Field is hidden.
	if ( ! isset( $input[ $field ] ) && ! isset( $show[ $field ] ) ) {
		return false;
	}
	if ( $admin ) {
		// Why is $show empty? I'm not getting the user option? May not exist?
		if ( isset( $show[ $field ] ) && 'on' === $show[ $field ] ) {
			return true;
		} else {
			return false;
		}
	} else {
		if ( 'off' === $input[ $field ] || '' === $input[ $field ] ) {
			return false;
		} elseif ( isset( $show[ $field ] ) && 'off' === $show[ $field ] ) {
			return false;
		} else {
			return true;
		}
	}
}

/**
 * Determine whether any of a set of fields are enabled.
 *
 * @param array $fields Array of field keys.
 *
 * @return bool
 */
function mc_show_edit_blocks( $fields ) {
	foreach ( $fields as $field ) {
		if ( mc_edit_block_is_visible( $field ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Generate date picker output.
 *
 * @param array $args Array of field arguments.
 *
 * @return string
 */
function mc_datepicker_html( $args ) {
	if ( isset( $args['first-day-of-week'] ) ) {
		$firstday = (int) $args['first-day-of-week'];
	} else {
		$sweek                     = absint( get_option( 'start_of_week', '1' ) );
		$firstday                  = ( 1 === $sweek || 0 === $sweek ) ? $sweek : 0;
		$args['first-day-of-week'] = (int) $firstday;
	}

	$id       = isset( $args['id'] ) ? esc_attr( $args['id'] ) : 'id_arg_missing';
	$name     = isset( $args['name'] ) ? esc_attr( $args['name'] ) : 'name_arg_missing';
	$value    = isset( $args['value'] ) ? esc_attr( $args['value'] ) : '';
	$required = isset( $args['required'] ) ? 'required' : '';
	$output   = "<duet-date-picker first-day-of-week='$firstday' identifier='$id' name='$name' value='$value' $required></duet-date-picker><input type='date' id='$id' name='$name' value='$value' $required class='duet-fallback' />";
	/**
	 * Filter the My Calendar datepicker output.
	 *
	 * @hook mc_datepicker_html
	 *
	 * @param {string} $output Default datepicker output.
	 * @param {array}  $args Datepicker setup arguments.
	 *
	 * @return {string}
	 */
	$output = apply_filters( 'mc_datepicker_html', $output, $args );

	return $output;
}

/**
 * Show a block of enabled fields.
 *
 * @param string    $field name of field group.
 * @param boolean   $has_data Whether fields have data.
 * @param object    $data Current data.
 * @param boolean   $display whether to return or echo.
 * @param string    $default_str Default string value.
 * @param int|false $group_id If in group editing, group ID.
 *
 * @return string.
 */
function mc_show_block( $field, $has_data, $data, $display = true, $default_str = '', $group_id = false ) {
	global $user_ID;
	$return     = '';
	$checked    = '';
	$value      = '';
	$show_block = mc_show_edit_block( $field );
	$pre        = '<div class="ui-sortable meta-box-sortables"><div class="postbox">';
	$post       = '</div></div>';
	switch ( $field ) {
		case 'event_host':
			if ( $show_block ) {
				$host   = ( empty( $data->event_host ) ) ? $user_ID : $data->event_host;
				$select = mc_selected_users( $host, 'hosts' );
				$return = '
					<p>
					<label for="e_host">' . __( 'Host', 'my-calendar' ) . '</label>
					<select id="e_host" name="event_host">' .
						$select
					. '</select>
				</p>';
			}
			break;
		case 'event_author':
			if ( $show_block && is_object( $data ) && ( '0' === $data->event_author || ! get_user_by( 'ID', $data->event_author ) ) ) {
				$author = ( empty( $data->event_author ) ) ? $user_ID : $data->event_author;
				$select = mc_selected_users( $author, 'authors' );
				$return = '
					<p>
					<label for="e_author">' . __( 'Author', 'my-calendar' ) . '</label>
					<select id="e_author" name="event_author">
						<option value="0" selected="selected">Public Submitter</option>' .
						$select
					. '</select>
				</p>';
			} else {
				$return = '<input type="hidden" name="event_author" value="' . $default_str . '" />';
			}
			break;
		case 'event_desc':
			if ( $show_block ) {
				global $current_screen;
				// Because wp_editor cannot return a value, event_desc fields cannot be filtered if its enabled.
				$value = ( $has_data ) ? stripslashes( $data->event_desc ) : '';
				/**
				 * Filter the editor to use a custom content editor field.
				 *
				 * @hook mc_custom_content_editor
				 *
				 * @param {string|bool} $custom_editor Bool to use default editor, otherwise the HTML output for your editor.
				 * @param {string}      $value Event description.
				 * @param {object}      $data Event object.
				 *
				 * @return {string|bool}
				 */
				$custom_editor = apply_filters( 'mc_custom_content_editor', false, $value, $data );
				if ( false !== $custom_editor ) {
					$return = $custom_editor;
				} else {
					if ( 'post' === $current_screen->base ) {
						$return = '<div class="event_description">
										<label for="content">' . __( 'Event Description', 'my-calendar' ) . '</label>
										<label for="content">' . __( 'Event Description', 'my-calendar' ) . '</label>
										<textarea id="content" name="content" class="event_desc" rows="8" cols="80">' . esc_textarea( stripslashes( $value ) ) . '</textarea>
									</div>';
					} else {
						echo '
						<div class="event_description">
						<label for="content">' . __( 'Event Description', 'my-calendar' ) . '</label>';
						if ( user_can_richedit() ) {
							wp_editor( $value, 'content', array( 'textarea_rows' => 20 ) );
						} else {
							echo '<textarea id="content" name="content" class="event_desc" rows="8" cols="80">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
						}
						echo '</div>';
					}
				}
			}
			break;
		case 'event_short':
			if ( $show_block ) {
				$value  = ( $has_data ) ? $data->event_short : '';
				$return = '
				<p>
					<label for="e_short">' . __( 'Excerpt', 'my-calendar' ) . '</label><br /><textarea id="e_short" name="event_short" rows="3" cols="80">' . esc_textarea( stripslashes( $value ) ) . '</textarea>
				</p>';
			}
			break;
		case 'event_image':
			if ( $has_data && property_exists( $data, 'event_post' ) ) {
				$image    = ( has_post_thumbnail( $data->event_post ) ) ? get_the_post_thumbnail_url( $data->event_post, 'post-thumbnail' ) : $data->event_image;
				$image_id = ( has_post_thumbnail( $data->event_post ) ) ? get_post_thumbnail_id( $data->event_post ) : '';
			} else {
				$image    = ( $has_data && '' !== $data->event_image ) ? $data->event_image : '';
				$image_id = '';
			}
			if ( $show_block ) {
				$button_text = __( 'Select Featured Image', 'my-calendar' );
				$remove      = '';
				$alt         = '';
				if ( '' !== $image ) {
					$alt         = ( $image_id ) ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';
					$button_text = __( 'Change Featured Image', 'my-calendar' );
					$remove      = '<button type="button" data-context="event" class="button remove-image" aria-describedby="event_image">' . esc_html__( 'Remove Featured Image', 'my-calendar' ) . '</button>';
					$alt         = ( '' === $alt ) ? get_post_meta( $data->event_post, '_mcs_submitted_alt', true ) : $alt;
					$alt         = ( '' === $alt ) ? $data->event_image : $alt;
				}
				$return = '
				<div class="mc-image-upload field-holder">
					<div class="image_fields">
						<input type="hidden" name="event_image_id" value="' . esc_attr( $image_id ) . '" class="textfield" id="e_image_id" /><input type="hidden" name="event_image" id="e_image" value="' . esc_attr( $image ) . '" /> <button type="button" data-context="event" class="button select-image" aria-describedby="event_image">' . $button_text . '</button> ' . $remove . '
					</div>';
				if ( '' !== $image ) {
					$image   = ( has_post_thumbnail( $data->event_post ) ) ? get_the_post_thumbnail_url( $data->event_post ) : $data->event_image;
					$return .= '<div class="event_image" aria-live="assertive"><img id="event_image" src="' . esc_attr( $image ) . '" alt="' . __( 'Current image: ', 'my-calendar' ) . esc_attr( $alt ) . '" /></div>';
				} else {
					$return .= '<div class="event_image" id="event_image"></div>';
				}
				$return .= '</div>';
			} else {
				$return = '<input type="hidden" name="event_image" value="' . esc_attr( $image ) . '" />';
			}
			break;
		case 'event_category':
			if ( $show_block ) {
				$add_category = current_user_can( 'mc_edit_cats' ) ? '<input class="mc-srt" type="checkbox" name="event_category_new" id="event_category_new" value="true" /> <label for="event_category_new" class="button"><span class="dashicons dashicons-plus" aria-hidden="true"></span>' . __( 'Add Categories', 'my-calendar' ) . '</label>' : '';
				$select       = mc_category_select( $data, true, false );
				$return       = '<fieldset class="categories"><legend>' . __( 'Categories', 'my-calendar' ) . '</legend><ul class="checkboxes">' .
					mc_category_select( $data, true, true ) .
					'<li class="event-new-category"> ' . $add_category . '</li>
				</ul></fieldset>';
				$return      .= '<div class="new-event-category">
					<p><label for="event_category_name">' . __( 'Category Name', 'my-calendar' ) . '</label> <input type="text" value="" id="event_category_name" name="event_category_name" disabled /> <button type="button" class="button add-category">' . __( 'Add Category', 'my-calendar' ) . '</button></p>
				</div>';
				$return      .= '
					<p class="mc-primary-category">
						<label for="e_category">' . __( 'Primary Category', 'my-calendar' ) . '</label>
						<select name="primary_category" id="e_category">' . $select . '</select>
					</p>';
			} else {
				$categories = mc_get_categories( $data );
				$return     = '<div>';
				if ( is_array( $categories ) ) {
					foreach ( $categories as $category ) {
						$return .= '<input type="hidden" name="event_category[]" value="' . absint( $category ) . '" />';
					}
				} else {
					$return .= '<input type="hidden" name="event_category[]" value="1" />';
				}
				$return .= '</div>';
			}
			break;
		case 'event_link':
			if ( $show_block ) {
				$value = ( $has_data ) ? esc_url( $data->event_link ) : '';
				if ( $has_data && '1' === $data->event_link_expires ) {
					$checked = ' checked="checked"';
				} elseif ( $has_data && '0' === $data->event_link_expires ) {
					$checked = '';
				} elseif ( mc_event_link_expires() ) {
					$checked = ' checked="checked"';
				}
				$return = '
					<p>
						<label for="e_link">' . __( 'More Information', 'my-calendar' ) . '</label> <input type="url" placeholder="https://" id="e_link" name="event_link" size="40" value="' . $value . '" /> <input type="checkbox" value="1" id="e_link_expires" name="event_link_expires"' . $checked . ' /> <label for="e_link_expires">' . __( 'Link will expire after event', 'my-calendar' ) . '</label>
					</p>';
			}
			break;
		case 'event_recurs':
			if ( is_object( $data ) ) {
				$event_recur = ( is_object( $data ) ) ? $data->event_recur : '';
				$recurs      = str_split( $event_recur, 1 );
				$recur       = $recurs[0];
				$every       = ( isset( $recurs[1] ) ) ? str_replace( $recurs[0], '', $event_recur ) : 1;
				if ( 1 === (int) $every && 'B' === $recur ) {
					$every = 2;
				}
				$prev = '<input type="hidden" name="prev_event_repeats" value="' . $data->event_repeats . '" /><input type="hidden" name="prev_event_recur" value="' . $data->event_recur . '" />';
			} else {
				$recur = false;
				$every = 1;
				$prev  = '';
			}
			if ( is_object( $data ) && null !== $data->event_repeats ) {
				$repeats = $data->event_repeats;
			} else {
				$repeats = '';
			}
			if ( $repeats && is_numeric( $repeats ) ) {
				$occurrences = mc_get_occurrences( $data->event_id );
				$last        = array_pop( $occurrences );
				$event       = mc_get_instance_data( $last->occur_id );
				$repeats     = gmdate( 'Y-m-d', strtotime( $event->occur_begin ) );
			}
			$hol_checked   = ( mc_skip_holidays() && ! $has_data ) ? true : false;
			$fifth_checked = ( mc_no_fifth_week() && ! $has_data ) ? true : false;
			if ( $has_data ) {
				$hol_checked   = ( '1' === $data->event_holiday ) ? true : $hol_checked;
				$fifth_checked = ( '1' === $data->event_fifth_week ) ? true : $fifth_checked;
			}
			$holiday_category = mc_get_option( 'skip_holidays_category' );
			if ( $holiday_category ) {
				$category_name = mc_get_category_detail( $holiday_category );
				$category_name = ( $category_name ) ? '&ldquo;' . $category_name . '&rdquo;' : __( 'your "Holiday" Category', 'my-calendar' );
				// Translators: name of category designated for holidays.
				$holiday_option = '<p class="holiday-schedule"><label for="e_holiday">' . sprintf( __( 'Cancel event if it occurs on a date with an event in %s', 'my-calendar' ), $category_name ) . '</label> <input type="checkbox" value="true" id="e_holiday" name="event_holiday"' . checked( true, $hol_checked, false ) . ' />
				</p>';
			} else {
				$holiday_option = '<input type="hidden" name="event_holiday" value="' . esc_attr( mc_skip_holidays() ) . '" />';
			}
			if ( $show_block && empty( $_GET['date'] ) ) {
				$warning = '';
				$class   = '';
				if ( $has_data && false !== mc_admin_instances( $data->event_id ) ) {
					$class   = 'disable-recurrences';
					$warning = '<div class="recurrences-disabled"><p><span>' . __( 'Editing the repetition pattern will regenerate scheduled dates for this event.', 'my-calendar' ) . '</span><button type="button" class="button enable-repetition" aria-expanded="false"><span class="dashicons dashicons-arrow-right" aria-hidden="true"></span>' . __( 'Edit Repetition Pattern', 'my-calendar' ) . '</button></p></div>';
				}
				$args        = array(
					'value' => $repeats,
					'id'    => 'e_repeats',
					'name'  => 'event_repeats',
				);
				$date_picker = mc_datepicker_html( $args );
				$recur_data  = ( $has_data ) ? mc_recur_string( $data ) : '';
				$active      = ( $has_data && '' !== $recur_data ) ? ' active' : '';
				$recur_info  = '<div class="mc_recur_string ' . $active . '" aria-live="polite"><p>' . $recur_data . '</p></div>';
				$return      = $pre . '
	<h2>' . __( 'Repetition Pattern', 'my-calendar' ) . mc_help_link( __( 'Help', 'my-calendar' ), __( 'Repetition Pattern', 'my-calendar' ), 'repetition pattern', '2', false ) . '</h2>
	<div class="inside recurrences ' . $class . '">' . $prev . $recur_info . $warning . '
		<fieldset class="recurring">
		<legend class="screen-reader-text">' . __( 'Recurring Events', 'my-calendar' ) . '</legend>
			<div class="columns">
			<p>
				<label for="e_every">' . __( 'Frequency', 'my-calendar' ) . '</label> <input type="number" name="event_every" id="e_every" size="4" min="1" max="99" maxlength="2" value="' . esc_attr( $every ) . '" />
			</p>
			<p>
				<label for="e_recur">' . __( 'Period', 'my-calendar' ) . '</label>
				<select name="event_recur" id="e_recur">
					' . mc_recur_options( $recur ) . '
				</select>
			</p>
			<p>
				<label for="e_repeats">Repeat Until</label>
				' . $date_picker . '
			</p>
			</div>
		</fieldset>
		' . $holiday_option . '
		<p class="fifth-week-schedule">
			<label for="e_fifth_week">' . __( 'If event falls on the 5th week of the month in a month with four weeks, move it one week earlier.', 'my-calendar' ) . '</label>
			<input type="checkbox" value="true" id="e_fifth_week" name="event_fifth_week"' . checked( true, $fifth_checked, false ) . ' />
		</p>
		' . mc_additional_dates( $data ) . '
	</div>
							' . $post;
			} else {
				if ( '' === $every && '' === $repeats ) {
					$every   = 'S';
					$repeats = '0';
				}
				$return = '
				<div>' . $prev . '<input type="hidden" name="event_repeats" value="' . esc_attr( $repeats ) . '" /><input type="hidden" name="event_every" value="' . esc_attr( $every ) . '" /><input type="hidden" name="event_recur" value="' . esc_attr( $recur ) . '" /></div>';
			}
			break;
		case 'event_access':
			if ( $show_block ) {
				/**
				 * Custom fields after event accessibility selections.
				 *
				 * @hook mc_event_access_fields
				 *
				 * @param {string} $output HTML output.
				 * @param {bool}   $has_data Has Data.
				 * @param {object} $data Event object.
				 *
				 * @return {string}
				 */
				$mc_event_accessibility_fields = apply_filters( 'mc_event_access_fields', '', $has_data, $data );
				$label                         = __( 'Accessibility', 'my-calendar' );
				$return                        = $pre . '<h2>' . $label . '</h2><div class="inside">' . mc_event_accessibility( '', $data, $label ) . $mc_event_accessibility_fields . '</div>' . $post;
			}
			break;
		case 'event_open':
			if ( $show_block ) {
				/**
				 * Filter event registration fields.
				 *
				 * @hook mc_event_registration
				 *
				 * @param {string} $output HTML output. Default empty.
				 * @param {bool}   $has_data Whether this event has data.
				 * @param {object} $data Event data object.
				 * @param {string} $context Indicates this is running in the admin.
				 *
				 * @return {string}
				 */
				$event_registration_output = apply_filters( 'mc_event_registration', '', $has_data, $data, 'admin' );
				$return                    = $pre . '<h2>' . __( 'Registration Settings', 'my-calendar' ) . '</h2><div class="inside"><fieldset><legend class="screen-reader-text">' . __( 'Event Registration', 'my-calendar' ) . '</legend>' . $event_registration_output . '</fieldset></div>' . $post;
			} else {
				$tickets      = ( $has_data ) ? esc_url( $data->event_tickets ) : '';
				$registration = ( $has_data ) ? esc_attr( $data->event_registration ) : '';
				$return       = '
				<div><input type="hidden"  name="event_tickets" value="' . $tickets . '" /><input type="hidden" name="event_registration" value="' . $registration . '" /></div>';
			}
			break;
		case 'event_location':
			if ( $show_block ) {
				$return = mc_locations_fields( $has_data, $data, 'event', $group_id );
			}
			break;
		default:
			return;
	}
	/**
	 * Filter the content of an editing block.
	 *
	 * @hook mc_show_block
	 *
	 * @param {string} $return HTML output of editing fields.
	 * @param {object} $data Event object.
	 * @param {string} $field Field hook.
	 * @param {bool}   $has_data If has data.
	 *
	 * @return {string}
	 */
	$return = apply_filters( 'mc_show_block', $return, $data, $field, $has_data );
	if ( true === $display ) {
		echo $return;
	}

	return $return;
}

/**
 * Generate editing panel for adding additional dates.
 *
 * @param object $data Event data object for editing.
 *
 * @return string
 */
function mc_additional_dates( $data ) {
	$output = '';
	if ( isset( $_GET['mode'] ) && 'edit' === $_GET['mode'] ) {
		$edit_url  = '';
		$edit_desc = '';
		$date      = false;
		if ( isset( $_GET['date'] ) ) {
			$date     = (int) $_GET['date'];
			$edit_url = esc_url( admin_url( 'admin.php?page=my-calendar&mode=edit&event_id=' . $data->event_id ) );
			// Translators: event editing URL.
			$edit_desc = sprintf( '<p>' . __( 'Editing a single date of an event changes only that date. <a href="%s">Edit the root event</a> to change the event series.', 'my-calendar' ) . '</p>', $edit_url );
		}
		$instances = mc_admin_instances( $data->event_id, $date );
		$input     = mc_recur_datetime_input( $data );
		$output    = "
		<div id='mc-scheduled-dates'>
			<button type='button' aria-expanded='false' class='toggle-dates button'><span class='dashicons dashicons-arrow-right' aria-hidden='true'></span>" . esc_html__( 'View scheduled dates', 'my-calendar' ) . '</button>
			<div id="mc-view-scheduled-dates">' . $edit_desc . "
				<div class='mc_response' aria-live='assertive'></div>
				<ul class='columns instance-list'>
					$instances
				</ul>
				<p><button data-action='shiftback' type='button' class='add-occurrence button-secondary' aria-expanded='false'><span class='dashicons dashicons-plus' aria-hidden='true'> </span>" . esc_html__( 'Add another date', 'my-calendar' ) . "</button></p>
				<div class='mc_add_new'>
					$input
					<p>
					<button type='button' data-action='shiftback' class='save-occurrence button-secondary clear'>" . esc_html__( 'Add Date', 'my-calendar' ) . '</button>
					</p>
				</div>
			</div>
		</div>';
	}

	return $output;
}

/**
 * Display all enabled form fields.
 *
 * @param object|null $data Passed data; null for new events.
 * @param string      $mode Copy/edit/add.
 * @param int         $event_id Event ID.
 */
function mc_form_fields( $data, $mode, $event_id ) {
	global $user_ID;
	$has_data = ( is_object( $data ) ) ? true : false;
	if ( $data ) {
		// This was previously only shown if $data was an object. Don't know why.
		$test = mc_test_occurrence_overlap( $data );
	}
	$instance = ( isset( $_GET['date'] ) ) ? (int) $_GET['date'] : false;
	if ( $instance ) {
		$ins = mc_get_instance_data( $instance );
		if ( property_exists( $ins, 'occur_event_id' ) ) {
			$event_id = $ins->occur_event_id;
			$data     = mc_get_first_event( $event_id );
		}
	}
	?>
	<div class="postbox-container jcd-wide">
		<div class="metabox-holder">
		<?php
		if ( 'add' === $mode || 'copy' === $mode ) {
			$query_args = array();
		} else {
			$query_args = array(
				'mode'     => $mode,
				'event_id' => $event_id,
			);
			if ( $instance ) {
				$query_args['date'] = $instance;
			}
		}
		/**
		 * Insert content before the event form. Not in the form.
		 *
		 * @hook mc_before_event_form
		 *
		 * @param {string} $output HTML output. Default empty string.
		 * @param {int}    $event_id Event ID.
		 *
		 * @return {string}
		 */
		echo apply_filters( 'mc_before_event_form', '', $event_id );
		$action       = add_query_arg( $query_args, admin_url( 'admin.php?page=my-calendar' ) );
		$group_id     = ( ! empty( $data->event_group_id ) && 'copy' !== $mode ) ? $data->event_group_id : mc_group_id();
		$event_author = ( 'edit' !== $mode ) ? $user_ID : $data->event_author;
		?>
<form id="my-calendar" method="post" action="<?php echo esc_url( $action ); ?>">
<div>
	<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
	<?php
	if ( isset( $_GET['ref'] ) ) {
		echo '<input type="hidden" name="ref" value="' . esc_url( $_GET['ref'] ) . '" />';
	}
	?>
	<input type="hidden" name="event_group_id" value="<?php echo absint( $group_id ); ?>" />
	<input type="hidden" name="event_action" value="<?php echo esc_attr( $mode ); ?>" />
	<?php
	if ( ! empty( $_GET['date'] ) ) {
		echo '<input type="hidden" name="event_instance" value="' . (int) $_GET['date'] . '"/>';
	}
	?>
	<input type="hidden" name="event_id" value="<?php echo (int) $event_id; ?>"/>
	<?php
	if ( 'edit' === $mode ) {
		if ( $has_data && ( ! property_exists( $data, 'event_post' ) || ! $data->event_post ) ) {
			$array_data = (array) $data;
			$post_id    = mc_event_post( 'add', $array_data, $event_id );
		} else {
			$post_id = ( $has_data ) ? absint( $data->event_post ) : false;
		}
		echo '<input type="hidden" name="event_post" value="' . esc_attr( $post_id ) . '" />';
	} else {
		$post_id = false;
	}
	?>
	<input type="hidden" name="event_nonce_name" value="<?php echo wp_create_nonce( 'event_nonce' ); ?>" />
</div>

<div class="ui-sortable meta-box-sortables event-primary">
	<div class="postbox">
		<?php
			// Translators: Event title.
			$text = ( 'edit' === $mode ) ? sprintf( __( 'Editing Event: "%s"', 'my-calendar' ), stripslashes( $data->event_title ) ) : __( 'Add Event', 'my-calendar' );
		?>
		<h2><?php echo esc_html( $text ); ?></h2>
		<div class="inside">
		<div class='mc-controls'>
			<?php
			if ( $post_id ) {
				$deleted = get_post_meta( $post_id, '_mc_deleted_instances', true );
				$custom  = get_post_meta( $post_id, '_mc_custom_instances', true );
				if ( $deleted || $custom ) {
					$notice = '';
					if ( $deleted ) {
						$notice = __( 'Some dates in this event have been deleted.', 'my-calendar' );
					}
					if ( $custom ) {
						$notice = __( 'The dates for this event have been added or modified.', 'my-calendar' );
					}
					$notice .= ' ' . __( 'Changing the date or repetition pattern will reset its scheduled dates.', 'my-calendar' );
					mc_show_notice( $notice );
				}
			}
			echo mc_controls( $mode, $has_data, $data );
			?>
		</div>
			<?php
			if ( ! empty( $_GET['date'] ) ) {
				$event      = mc_get_event( $instance );
				$date       = date_i18n( mc_date_format(), mc_strtotime( $event->occur_begin ) );
				$edit_url   = esc_url( admin_url( 'admin.php?page=my-calendar&mode=edit&event_id=' . $data->event_id ) );
				$edit_event = sprintf( ' <a href="%s">' . __( 'Edit the root event.', 'my-calendar' ) . '</a>', $edit_url );
				// Translators: Date of a specific event occurrence.
				$message = sprintf( __( 'You are editing the <strong>%s</strong> date of this event. Other dates for this event will not be changed.', 'my-calendar' ), $date ) . $edit_event;
				mc_show_notice( $message );
			} elseif ( isset( $_GET['date'] ) && empty( $_GET['date'] ) ) {
				mc_show_notice( __( 'The ID for an event date was not provided. <strong>You are editing this entire recurring event series.</strong>', 'my-calendar' ) );
			}
			?>
			<fieldset class="details">
				<legend class="screen-reader-text"><?php esc_html_e( 'Event', 'my-calendar' ); ?></legend>
				<p>
					<label for="e_title"><?php esc_html_e( 'Event Title', 'my-calendar' ); ?></label><br/>
					<input type="text" id="e_title" name="event_title" size="50" maxlength="255" value="<?php echo ( $has_data ) ? stripslashes( esc_attr( $data->event_title ) ) : ''; ?>" />
				</p>
				<?php
				if ( is_object( $data ) && 1 === (int) $data->event_flagged ) {
					$flagged = ( '0' === $data->event_flagged ) ? true : false;
					?>
					<div class="error">
						<p>
							<input type="checkbox" value="0" id="e_flagged" name="event_flagged"<?php checked( $flagged, true ); ?> />
							<label for="e_flagged"><?php esc_html_e( 'This event is not spam', 'my-calendar' ); ?></label>
						</p>
					</div>
					<?php
				}
				/**
				 * Container for custom fields inserted after the event title field.
				 *
				 * @hook mc_insert_custom_fields
				 *
				 * @param {string} $output Output HTML.
				 * @param {bool}   $has_data Has data.
				 * @param {object} $data Event object.
				 *
				 * @return {string}
				 */
				apply_filters( 'mc_insert_custom_fields', '', $has_data, $data );
				if ( function_exists( 'wpt_post_to_twitter' ) && current_user_can( 'wpt_can_tweet' ) ) {
					if ( ! ( 'edit' === $mode && 1 === (int) $data->event_approved ) ) {
						$mc_allowed = absint( ( get_option( 'wpt_tweet_length' ) ) ? get_option( 'wpt_tweet_length' ) : 140 );
						?>
						<p class='mc-twitter'>
							<label for='mc_twitter'><?php esc_html_e( 'Post a Status Update (via XPoster)', 'my-calendar' ); ?></label><br/>
							<textarea cols='70' rows='2' id='mc_twitter' name='mc_twitter' data-allowed="<?php echo absint( $mc_allowed ); ?>"><?php echo esc_textarea( stripslashes( apply_filters( 'mc_twitter_text', '', $data ) ) ); ?></textarea>
						</p>
						<?php
					}
				}
				mc_show_block( 'event_desc', $has_data, $data );
				mc_show_block( 'event_short', $has_data, $data );
				mc_show_block( 'event_category', $has_data, $data );
				?>
			</fieldset>
		</div>
	</div>
</div>

<div class="ui-sortable meta-box-sortables event-date-time">
	<div class="postbox">
		<h2><?php esc_html_e( 'Date and Time', 'my-calendar' ); ?></h2>

		<div class="inside">
			<?php
			if ( is_object( $data ) ) { // Information for rewriting recurring data.
				?>
				<input type="hidden" name="prev_event_begin" value="<?php echo esc_attr( $data->event_begin ); ?>"/>
				<input type="hidden" name="prev_event_time" value="<?php echo esc_attr( $data->event_time ); ?>"/>
				<input type="hidden" name="prev_event_end" value="<?php echo esc_attr( $data->event_end ); ?>"/>
				<input type="hidden" name="prev_event_endtime" value="<?php echo esc_attr( $data->event_endtime ); ?>"/>
				<?php
				if ( mc_is_recurring( $data ) ) {
					echo '<p><span>' . esc_html__( 'Editing dates and times will regenerate recurring dates for this event.', 'my-calendar' ) . '</span></p>';
				}
			}
			?>
			<fieldset class="datetime">
				<legend class="screen-reader-text"><?php esc_html_e( 'Event Date and Time', 'my-calendar' ); ?></legend>
				<div id="e_schedule">
					<?php
					/**
					 * Filter My Calendar default date/time inputs.
					 *
					 * @hook mc_datetime_inputs
					 *
					 * @param {string} $output HTML output.
					 * @param {bool}   $has_data If event has data.
					 * @param {object} $data Event object.
					 * @param {string} $context Admin context.
					 *
					 * @return {string}
					 */
					echo apply_filters( 'mc_datetime_inputs', '', $has_data, $data, 'admin' );
					if ( 'edit' !== $mode ) {
						$span_checked = '';
						if ( $has_data && '1' === $data->event_span ) {
							$span_checked = ' checked="checked"';
						} elseif ( $has_data && '0' === $data->event_span ) {
							$span_checked = '';
						}
						?>
					<p class="event_span">
						<button type="button" class="add_field button button-secondary"><span class="dashicons dashicons-plus" aria-hidden="true"></span><?php esc_html_e( 'Add Copy', 'my-calendar' ); ?></button> <?php mc_help_link( __( 'Help', 'my-calendar' ), __( 'Copy an event', 'my-calendar' ), 'copy an event', 4 ); ?>
					</p>
					<ol class="mc-repeat-events">
						<li id="event1" class="datetime-template enabled">
							<fieldset class="new-field">
							<legend>
							<?php
							// Translators: placeholder for number of occurrences added.
							printf( __( 'Event Copy %1$s', 'my-calendar' ), '<span class="number_of">2</span>' );
							?>
							</legend>
							<?php
							mc_repeatable_datetime_input( '', $has_data, $data );
							?>
							</fieldset>
							<div class="buttons">
								<button type="button" class="add_field button button-secondary"><span class="dashicons dashicons-plus" aria-hidden="true"></span><?php esc_html_e( 'Add Copy', 'my-calendar' ); ?></button> <?php mc_help_link( __( 'Help', 'my-calendar' ), __( 'Copy an event', 'my-calendar' ), 'copy an event', '1' ); ?>
							</div>
						</li>
					</ol>
					<p class="event_span checkboxes">
						<input type="checkbox" value="1" id="e_span" name="event_span"<?php echo $span_checked; ?> />
						<label for="e_span"><?php esc_html_e( 'These are one multi-day event.', 'my-calendar' ); ?></label>
					</p>
						<?php
					}
					?>
				</div>
			</fieldset>
		</div>
	</div>
</div>
	<?php
	mc_show_block( 'event_recurs', $has_data, $data );
	if ( mc_show_edit_block( 'event_image' ) ) {
		?>
		<div class="ui-sortable meta-box-sortables event-image">
			<div class="postbox">
				<h2><?php esc_html_e( 'Featured Image', 'my-calendar' ); ?></h2>
				<div class="inside">
		<?php
		mc_show_block( 'event_image', $has_data, $data );
		?>
				</div>
			</div>
		</div>
		<?php
	}
	if ( mc_show_edit_blocks( array( 'event_host', 'event_author', 'event_link' ) ) ) {
		?>
		<div class="ui-sortable meta-box-sortables event-details">
			<div class="postbox">
				<h2><?php esc_html_e( 'Event Details', 'my-calendar' ); ?></h2>
				<div class="inside">
		<?php
	}
	mc_show_block( 'event_host', $has_data, $data );
	mc_show_block( 'event_author', $has_data, $data, true, $event_author );
	mc_show_block( 'event_link', $has_data, $data );
	if ( mc_show_edit_blocks( array( 'event_host', 'event_author', 'event_link' ) ) ) {
		?>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Render custom fields in the admin.
	 *
	 * @hook mc_event_details
	 *
	 * @param {string} $output HTML output. Default empty string.
	 * @param {bool}   $has_data If event has data.
	 * @param {object} $data Event object.
	 * @param {string} $context Admin context.
	 *
	 * @return {string}
	 */
	$custom_fields = apply_filters( 'mc_event_details', '', $has_data, $data, 'admin' );
	if ( '' !== $custom_fields ) {
		?>
<div class="ui-sortable meta-box-sortables event-custom-fields">
	<div class="postbox">
		<h2><?php esc_html_e( 'Event Custom Fields', 'my-calendar' ); ?></h2>
		<div class="inside">
			<?php echo $custom_fields; ?>
		</div>
	</div>
</div>
		<?php
	}
	if ( mc_show_edit_block( 'event_location' ) ) {
		?>

<div class="ui-sortable meta-box-sortables event-location">
	<div class="postbox">
		<h2><?php esc_html_e( 'Event Location', 'my-calendar' ); ?></h2>

		<div class="inside location_form">
			<fieldset class="locations">
				<legend class='screen-reader-text'><?php esc_html_e( 'Event Location', 'my-calendar' ); ?></legend>
		<?php
		echo mc_event_location_dropdown_block( $data );
		mc_show_block( 'event_location', $has_data, $data );
		?>
			</fieldset>
		</div>
	</div>
</div>
		<?php
	}
	mc_show_block( 'event_access', $has_data, $data );
	mc_show_block( 'event_open', $has_data, $data );
	?>
	<div class="ui-sortable meta-box-sortables event-ui-footer">
		<div class="postbox">
			<div class="inside">
				<div class='mc-controls footer'>
					<?php echo mc_controls( $mode, $has_data, $data, 'footer' ); ?>
				</div>
			</div>
		</div>
		<?php
		if ( $has_data ) {
			if ( 0 !== (int) $data->event_group_id ) {
				?>
		<div class="postbox">
			<h2><?php esc_html_e( 'Event Group', 'my-calendar' ); ?></h2>
			<div class="inside">
				<?php $edit_group_url = admin_url( 'admin.php?page=my-calendar-manage&groups=true&mode=edit&event_id=' . $data->event_id . '&group_id=' . $data->event_group_id ); ?>
				<p><a href='<?php echo esc_url( $edit_group_url ); ?>'><?php esc_html_e( 'Edit other events in the same event group.', 'my-calendar' ); ?></a></p>
				<ul class="bullets instance-list">
					<?php mc_grouped_events( $data->event_group_id, '<p>{current}{begin}{end}</p>' ); ?>
				</ul>
			</div>
		</div>
				<?php
			}
			if ( current_user_can( 'mc_edit_templates' ) || current_user_can( 'manage_options' ) ) {
				?>
		<div class="postbox">
			<h2><button type="button" class="button-link toggle-inside"><span class="dashicons dashicons-plus" aria-hidden="true"></span><?php esc_html_e( 'Preview Template Output', 'my-calendar' ); ?></button></h2>
			<div class="inside">
				<p><?php esc_html_e( 'Template tags are used to build custom templates. Preview the output of selected template tags for this event.', 'my-calendar' ); ?></p>
				<div class="mc-preview">
					<?php
					$first = mc_get_first_event( $data->event_id );
					if ( ! $first ) {
						esc_html_e( 'Unable to retrieve template tags for this event.', 'my-calendar' );
						$tag_preview = '';
					} else {
						$view_url = mc_get_details_link( $first );
						if ( ! mc_event_published( $data ) ) {
							$view_url = add_query_arg( 'preview', 'true', mc_get_details_link( $first ) );
						}
						$tag_url     = admin_url( "admin.php?page=my-calendar-design&mc-event=$first->occur_id#my-calendar-templates" );
						$tag_preview = add_query_arg(
							array(
								'iframe'   => 'true',
								'showtags' => 'true',
								'mc_id'    => $first->occur_id,
							),
							$view_url
						);
					}
					?>
					<div class="mc-template-tag-preview">
						<iframe class="mc-iframe" title="<?php echo esc_attr( __( 'Event Template Tag Preview', 'my-calendar' ) ); ?>" src="<?php echo esc_url( $tag_preview ); ?>" width="800" height="600"></iframe>
					</div>
				</div>
			</div>
		</div>
				<?php
			}
		}
		?>
	</div>
</form>
</div>
	</div>
	<?php
}

/**
 * Produce Event location dropdown.
 *
 * @param object|int $data Current event data or selected location ID.
 * @param bool       $hide_extras Default: false. Hide current location and add new buttons.
 *
 * @return string
 */
function mc_event_location_dropdown_block( $data, $hide_extras = false ) {
	$current_location = '';
	$event_location   = false;
	$fields           = '';
	$autocomplete     = false;
	$count            = mc_count_locations();
	/**
	 * Filter the number of locations required to trigger a switch between a select input and an autocomplete.
	 *
	 * @hook mc_convert_locations_select_to_autocomplete
	 *
	 * @param {int} $count Number of locations that will remain a select. Default 90.
	 *
	 * @return {int}
	 */
	if ( $count > apply_filters( 'mc_convert_locations_select_to_autocomplete', 90 ) ) {
		$autocomplete = true;
	}
	if ( is_object( $data ) ) {
		$selected = '';
		if ( property_exists( $data, 'event_location' ) ) {
			$event_location = $data->event_location;
		}
	} else {
		$event_location = $data;
	}
	if ( 0 !== $count ) {
		$fields .= ( $event_location ) ? '<label for="l_preset">' . __( 'Change location:', 'my-calendar' ) . '</label>' : '<label for="l_preset">' . __( 'Choose location:', 'my-calendar' ) . '</label>';
		if ( ! $autocomplete ) {
			$locs             = mc_get_locations( 'select-locations' );
			$text             = ( $event_location ) ? __( 'No change', 'my-calendar' ) : __( 'No location', 'my-calendar' );
			$aria_describedby = ( $event_location ) ? ' aria-describedby="mc-current-location"' : '';
			$fields          .= '
			 <select name="location_preset" id="l_preset"' . $aria_describedby . '>
				<option value="none">' . $text . '</option>';
			foreach ( $locs as $loc ) {
				if ( is_object( $loc ) ) {
					$base_loc = strip_tags( stripslashes( $loc->location_label ), mc_strip_tags() );
					if ( ! $event_location ) {
						$selected = ( is_numeric( mc_get_option( 'default_location' ) ) && (int) mc_get_option( 'default_location' ) === (int) $loc->location_id ) ? ' selected="selected"' : '';
					} else {
						if ( $event_location && ! is_object( $data ) ) {
							$selected = ( (int) $event_location === (int) $loc->location_id ) ? ' selected="selected"' : '';
						} else {
							$selected = '';
						}
					}
					if ( (int) $loc->location_id === (int) $event_location ) {
						$location_link = ( current_user_can( 'mc_edit_locations' ) ) ? add_query_arg(
							array(
								'mode'        => 'edit',
								'location_id' => $event_location,
							),
							admin_url( 'admin.php?page=my-calendar-locations' )
						) : false;
						// Translators: name of currently selected location.
						$loc_name = ( $location_link ) ? '<a href="' . esc_url( $location_link ) . '" target="blank">' . sprintf( __( 'Edit %s', 'my-calendar' ), $base_loc ) . ' (' . __( 'New tab', 'my-calendar' ) . ')</a>' : $base_loc;
						// Translators: Link to edit current location, e.g. 'Edit %s'.
						$current_location  = "<div id='mc-current-location'><span class='dashicons dashicons-location' aria-hidden='true'></span>" . sprintf( __( 'Current location: %s', 'my-calendar' ), $loc_name ) . '</div>';
						$current_location .= "<input type='hidden' id='preset_l' name='preset_location' value='$event_location' />";
					}
					$fields .= "<option value='" . $loc->location_id . "'$selected />" . $base_loc . '</option>';
				}
			}
			$fields .= '</select>';
		} else {
			$location_label = ( $event_location && is_numeric( $event_location ) ) ? mc_get_location( $event_location )->location_label : '';
			$fields        .= '<div id="mc-locations-autocomplete" class="mc-autocomplete autocomplete">
				<input class="autocomplete-input" type="text" placeholder="' . __( 'Search locations...', 'my-calendar' ) . '" id="l_preset" value="' . esc_attr( $location_label ) . '" />
				<ul class="autocomplete-result-list"></ul>
				<input type="hidden" name="location_preset" id="mc_event_location_value" value="' . esc_attr( $event_location ) . '" />
			</div>';
		}
	} else {
		$fields .= '<input type="hidden" name="location_preset" value="none" />
		<p>
		<a href="' . admin_url( 'admin.php?page=my-calendar-locations' ) . '">' . __( 'Add a location', 'my-calendar' ) . '</a>
		</p>';
	}
	if ( is_object( $data ) ) {
		$differences = mc_event_location_diff( $data );
		if ( $differences ) {
			$add_url   = add_query_arg( 'event_source', $data->event_id, admin_url( 'admin.php?page=my-calendar-locations' ) );
			$merge_url = add_query_arg( 'merge_source', $data->event_id, admin_url( 'admin.php?page=my-calendar-locations&mode=edit&location_id=' . absint( $data->event_location ) ) );
			// translators: 1) URL to create a new location with this data; 2) URL to update the existing location.
			$current_location .= '<p>' . sprintf( __( 'Some of the location data saved in the event is different from the stored location. <a href="%1$s">Create a new location</a> or <a href="%2$s">update the saved location</a>?', 'my-calendar' ), $add_url, $merge_url ) . '</p><ul class="checkboxes">';
			foreach ( $differences as $key => $value ) {
				$current_location .= '<li><strong>' . ucfirst( str_replace( 'location_', '', $key ) ) . '</strong><br /><em>Location:</em> ' . stripslashes( esc_html( $value[0] ) ) . '<br /><em>Event:</em> ' . stripslashes( esc_html( $value[1] ) ) . '</li>';
			}
			$current_location .= '</ul>';
		}
	}
	$current_location = ( $hide_extras ) ? '' : $current_location;
	$output           = $current_location . '<div class="mc-event-location-dropdown">' . '<div class="location-input">' . $fields . '</div>';
	if ( ! $hide_extras ) {
		$output .= ( current_user_can( 'mc_edit_locations' ) && ! isset( $_GET['group_id'] ) ) ? '<div class="location-toggle"><button type="button" aria-expanded="false" aria-controls="location-fields" class="add-location button button-secondary"><span class="dashicons dashicons-plus" aria-hidden="true"></span><span>' . __( 'Add a new location', 'my-calendar' ) . '</span></button></div>' : '';
	}
	$output .= '</div>';

	return $output;
}

/**
 * Compare the location data in the event table with the location data in a location.
 *
 * @since 3.5.0
 *
 * @param object $event Event object.
 * @param string $return_type Data to return. 'location' or 'diff'.
 *
 * @return bool|array Returns boolean if 'diff'; array if 'location'.
 */
function mc_event_location_diff( $event, $return_type = 'diff' ) {
	$location_id = ( $event && property_exists( $event, 'event_location' ) ) ? $event->event_location : '';
	if ( ! $location_id ) {
		return false;
	}
	$location = mc_get_location( $location_id );

	// If no location name, presume this has no data.
	if ( is_object( $event ) && '' === trim( $event->event_label ) || ! $event->event_label ) {
		return false;
	}

	$event_location = array(
		'location_id'        => $event->event_location,
		'location_label'     => $event->event_label,
		'location_street'    => $event->event_street,
		'location_street2'   => $event->event_street2,
		'location_city'      => $event->event_city,
		'location_state'     => $event->event_state,
		'location_postcode'  => $event->event_postcode,
		'location_region'    => $event->event_region,
		'location_url'       => $event->event_url,
		'location_country'   => $event->event_country,
		'location_longitude' => $event->event_longitude,
		'location_latitude'  => $event->event_latitude,
		'location_zoom'      => $event->event_zoom,
		'location_phone'     => $event->event_phone,
		'location_phone2'    => $event->event_phone2,
	);
	if ( 'location' === $return_type ) {
		return $event_location;
	}
	$location = (array) $location;
	// Location post isn't in event data.
	unset( $location['location_post'] );
	// Location access data saved differently in the two datasets.
	unset( $location['location_access'] );
	$differences = array();
	if ( $location !== $event_location ) {
		foreach ( $location as $key => $value ) {
			// Ignore fields that have no values; these will be copied from the location if a new location is created.
			if ( $value !== $event_location[ $key ] && $event_location[ $key ] && '0.000000' !== $event_location[ $key ] ) {
				$differences[ $key ] = array( $value, $event_location[ $key ] );
			}
		}
		return $differences;
	}

	return false;
}

/**
 * Return valid accessibility features for events.
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
	$event_access = apply_filters( 'mc_event_access_choices', $choices );

	return $event_access;
}

/**
 * Form to select accessibility features.
 *
 * @param string $form Form HTML.
 * @param object $data Event data.
 * @param string $label Primary label for fields.
 */
function mc_event_accessibility( $form, $data, $label ) {
	$note_value    = '';
	$events_access = array();
	$class         = ( is_admin() ) ? 'screen-reader-text' : 'mc-event-access';
	$form         .= "
		<fieldset class='accessibility'>
			<legend class='$class'>$label</legend>
			<ul class='accessibility-features checkboxes'>";
	$access        = mc_event_access();
	if ( ! empty( $data ) ) {
		if ( property_exists( $data, 'event_post' ) ) {
			$events_access = get_post_meta( $data->event_post, '_mc_event_access', true );
		} else {
			$events_access = array();
		}
	}
	foreach ( $access as $k => $a ) {
		$id      = "events_access_$k";
		$label   = $a;
		$checked = '';
		if ( is_array( $events_access ) ) {
			$checked = ( in_array( $k, $events_access, true ) || in_array( $a, $events_access, true ) ) ? ' checked="checked"' : '';
		}
		$item  = sprintf( '<li><input type="checkbox" id="%1$s" name="events_access[]" value="%4$s" class="checkbox" %2$s /> <label for="%1$s">%3$s</label></li>', esc_attr( $id ), $checked, esc_html( $label ), esc_attr( $a ) );
		$form .= $item;
	}
	if ( isset( $events_access['notes'] ) ) {
		$note_value = esc_attr( $events_access['notes'] );
	}
	$form .= '<li class="events_access_notes"><label for="events_access_notes">' . __( 'Notes', 'my-calendar' ) . '</label> <input type="text" id="events_access_notes" name="events_access[notes]" value="' . esc_attr( $note_value ) . '" /></li>';
	$form .= '</ul>
	</fieldset>';

	return $form;
}

/**
 * Review data submitted and verify.
 *
 * @param string $action Type of action being performed.
 * @param array  $post Post data.
 * @param int    $i If multiple events submitted, which index this is.
 * @param bool   $ignore_required Pass 'true' to ignore required fields.
 *
 * @return array Modified data and information about approval.
 */
function mc_check_data( $action, $post, $i, $ignore_required = false ) {
	global $submission;
	$user = wp_get_current_user();
	/**
	 * Filter posted data before performing event data checks.
	 *
	 * @hook mc_pre_checkdata
	 *
	 * @param {array}  $post Post data.
	 * @param {string} $action Action performed (edit, copy, add).
	 * @param {int}    $i Current event index if parsing multiple events.
	 *
	 * @return {array}
	 */
	$post               = apply_filters( 'mc_pre_checkdata', $post, $action, $i );
	$submit             = array();
	$errors             = '';
	$approved           = 0;
	$every              = '';
	$recur              = '';
	$events_access      = '';
	$begin              = '';
	$end                = '';
	$short              = '';
	$time               = '';
	$endtime            = '';
	$event_label        = '';
	$event_street       = '';
	$event_street2      = '';
	$event_city         = '';
	$event_state        = '';
	$event_postcode     = '';
	$event_region       = '';
	$event_country      = '';
	$event_url          = '';
	$event_image        = '';
	$event_phone        = '';
	$event_phone2       = '';
	$event_access       = '';
	$event_tickets      = '';
	$event_registration = '';
	$event_author       = '';
	$expires            = '';
	$event_zoom         = '';
	$host               = '';
	$event_fifth_week   = '';
	$event_holiday      = '';
	$event_group_id     = '';
	$event_span         = '';
	$event_hide_end     = '';
	$event_longitude    = '';
	$event_latitude     = '';
	$saved_location     = '';
	$event_link         = '';
	$repeats            = '';
	$title              = '';
	$event_link         = '';
	$desc               = '';
	$primary            = 1;

	if ( version_compare( PHP_VERSION, '7.4', '<' ) && get_magic_quotes_gpc() ) { //phpcs:ignore PHPCompatibility.FunctionUse.RemovedFunctions.get_magic_quotes_gpcDeprecated
		$post = array_map( 'stripslashes_deep', $post );
	}
	if ( ! wp_verify_nonce( $post['event_nonce_name'], 'event_nonce' ) ) {
		return array();
	}

	if ( 'add' === $action || 'edit' === $action || 'copy' === $action || 'check' === $action ) {
		$title  = ! empty( $post['event_title'] ) ? trim( $post['event_title'] ) : '';
		$desc   = ! empty( $post['content'] ) ? trim( $post['content'] ) : '';
		$short  = ! empty( $post['event_short'] ) ? trim( $post['event_short'] ) : '';
		$recurs = ( isset( $post['prev_event_recur'] ) ) ? $post['prev_event_recur'] : '';
		$recur  = ! empty( $post['event_recur'] ) ? trim( $post['event_recur'] ) : $recurs;
		if ( ! isset( $post['event_recur'] ) && isset( $post['event_repeats'] ) ) {
			unset( $post['event_repeats'] );
		}
		$every = ! empty( $post['event_every'] ) ? (int) $post['event_every'] : 1;
		if ( strlen( $recur > 1 ) ) {
			$recur = substr( $recur, 0, 1 );
		}
		$begin = trim( $post['event_begin'][ $i ] );
		$end   = ( ! empty( $post['event_end'] ) ) ? trim( $post['event_end'][ $i ] ) : $begin;
		// if this is an all weekdays event, and it's scheduled to start on a weekend, the math gets nasty.
		// ...AND there's no reason to allow it, since weekday events will NEVER happen on the weekend.
		if ( 'E' === $recur && 0 === ( (int) mc_date( 'w', mc_strtotime( $begin ), false ) || 6 === (int) mc_date( 'w', mc_strtotime( $begin ), false ) ) ) {
			// Move the start date of an all weekdays event to the next week day if it's been set to start on the weekend.
			$newbegin = $begin;
			$newend   = $end;
			// if Sunday, move forward one day.
			if ( 0 === (int) mc_date( 'w', mc_strtotime( $begin ), false ) ) {
				$newbegin = my_calendar_add_date( $begin, 1 );
				if ( ! empty( $post['event_end'][ $i ] ) ) {
					$newend = my_calendar_add_date( $end, 1 );
				} else {
					$newend = $newbegin;
				}
				// if Saturday, move forward two days.
			} elseif ( 6 === (int) mc_date( 'w', mc_strtotime( $begin ), false ) ) {
				$newbegin = my_calendar_add_date( $begin, 2 );
				if ( ! empty( $post['event_end'][ $i ] ) ) {
					$newend = my_calendar_add_date( $end, 2 );
				} else {
					$newend = $newbegin;
				}
			}
			$begin = $newbegin;
			$end   = $newend;
		} else {
			$begin = ! empty( $post['event_begin'][ $i ] ) ? trim( $post['event_begin'][ $i ] ) : '';
			$end   = ! empty( $post['event_end'][ $i ] ) ? trim( $post['event_end'][ $i ] ) : $begin;
		}

		$begin = mc_date( 'Y-m-d', mc_strtotime( $begin ), false );// regardless of entry format, convert.
		$time  = ! empty( $post['event_time'][ $i ] ) ? trim( $post['event_time'][ $i ] ) : '';
		if ( '' !== $time ) {
			/**
			 * Filter default event length. Events without a specified end time are considered to occupy one hour by default.
			 *
			 * @hook mc_default_event_length
			 *
			 * @param {string} $default_modifier Event length in a human-language string interpretable by strtotime(). Default '1 hour'.
			 *
			 * @return {string}
			 */
			$default_modifier = apply_filters( 'mc_default_event_length', '1 hour' );
			$endtime          = ! empty( $post['event_endtime'][ $i ] ) ? trim( $post['event_endtime'][ $i ] ) : mc_date( 'H:i:s', mc_strtotime( $time . ' +' . $default_modifier ), false );
			if ( empty( $post['event_endtime'][ $i ] ) && mc_date( 'H', mc_strtotime( $endtime ), false ) === '00' ) {
				// If one hour pushes event into next day, reset to 11:59pm.
				$endtime = '23:59:00';
			}
		} else {
			$endtime = ! empty( $post['event_endtime'][ $i ] ) ? trim( $post['event_endtime'][ $i ] ) : '';
		}
		$time    = ( '' === $time || '00:00:00' === $time ) ? '00:00:00' : $time; // Set at midnight if not provided.
		$endtime = ( '' === $endtime && '00:00:00' === $time ) ? '23:59:59' : $endtime; // Set at end of night if np.

		// Prevent setting enddate to incorrect value on copy.
		if ( mc_strtotime( $end ) < mc_strtotime( $begin ) && 'copy' === $action ) {
			$end = mc_date( 'Y-m-d', ( mc_strtotime( $begin ) + ( mc_strtotime( $post['prev_event_end'] ) - mc_strtotime( $post['prev_event_begin'] ) ) ), false );
		}
		if ( isset( $post['event_allday'] ) && 0 !== (int) $post['event_allday'] ) {
			$time    = '00:00:00';
			$endtime = '23:59:59';
		}

		if ( $endtime < $time && $end === $begin ) {
			// If the endtime is earlier than the start time but the dates are the same.
			// the user most likely is creating a late night event that ends on the next day.
			$end = mc_date( 'Y-m-d', mc_strtotime( $end . ' + 1 day' ) );
		}
		// Verify formats.
		$time    = mc_date( 'H:i:s', mc_strtotime( $time ), false );
		$endtime = mc_date( 'H:i:s', mc_strtotime( $endtime ), false );
		$end     = mc_date( 'Y-m-d', mc_strtotime( $end ), false ); // regardless of entry format, convert.
		$repeat  = ( isset( $post['prev_event_repeats'] ) ) ? $post['prev_event_repeats'] : 0;
		$repeats = ( isset( $post['event_repeats'] ) ) ? trim( $post['event_repeats'] ) : $repeat;
		$host    = ! empty( $post['event_host'] ) ? $post['event_host'] : $user->ID;
		$primary = false;

		if ( isset( $post['event_category'] ) ) {
			$cats = map_deep( $post['event_category'], 'absint' );
			if ( is_array( $cats ) ) {
				// Set first category as primary.
				$primary = ( is_numeric( $cats[0] ) ) ? $cats[0] : 1;
				// If passed, set primary_category as primary.
				$primary = isset( $post['primary_category'] ) ? absint( $post['primary_category'] ) : $primary;
				// Check whether primary category is private.
				$private = mc_get_category_detail( $primary, 'category_private' );
				if ( 1 !== (int) $private ) {
					// If primary category is not private, check whether any other selected categories are private, and switch to primary.
					foreach ( $cats as $cat ) {
						$private = mc_get_category_detail( $cat, 'category_private' );
						// If a selected category is private, set that category as primary instead.
						if ( 1 === (int) $private ) {
							$primary = $cat;
						}
					}
				}
				// Backwards compatibility for old versions of My Calendar Pro.
			} else {
				$primary = $cats;
				$cats    = array( $cats );
			}
		} else {
			$default = mc_get_option( 'default_category' );
			$default = ( ! $default ) ? mc_no_category_default( true ) : $default;
			$cats    = array( $default );
			$primary = $default;
		}
		/**
		 * Filter primary category decision. Primary category is normally the alphabetically first listed category or the first selected private category.
		 *
		 * @hook mc_set_primary_category
		 *
		 * @param {int}   $primary Primary category ID.
		 * @param {array} $cats All selected categories.
		 * @param {array} $post Submitted query.
		 *
		 * @return {int}
		 */
		$primary      = apply_filters( 'mc_set_primary_category', $primary, $cats, $post );
		$event_author = ( isset( $post['event_author'] ) && is_numeric( $post['event_author'] ) ) ? $post['event_author'] : 0;
		$event_link   = ! empty( $post['event_link'] ) ? trim( $post['event_link'] ) : '';
		$expires      = ! empty( $post['event_link_expires'] ) ? $post['event_link_expires'] : '0';
		$approved     = ( current_user_can( 'mc_approve_events' ) ) ? 1 : 0;
		// Check for event_approved provides support for older versions of My Calendar Pro.
		if ( isset( $post['event_approved'] ) && $post['event_approved'] !== $approved ) {
			$approved = absint( $post['event_approved'] );
		}

		$saved_location     = ! empty( $post['preset_location'] ) ? $post['preset_location'] : '';
		$select_location    = ( ! empty( $post['location_preset'] ) ) ? $post['location_preset'] : '';
		$event_tickets      = ( isset( $post['event_tickets'] ) ) ? trim( $post['event_tickets'] ) : '';
		$event_registration = ( isset( $post['event_registration'] ) ) ? trim( $post['event_registration'] ) : '';
		$event_image        = ( isset( $post['event_image'] ) ) ? esc_url_raw( $post['event_image'] ) : '';
		$event_fifth_week   = ! empty( $post['event_fifth_week'] ) ? 1 : 0;
		$event_holiday      = ! empty( $post['event_holiday'] ) ? 1 : 0;
		$group_id           = (int) $post['event_group_id'];
		$event_group_id     = ( ( is_array( $post['event_begin'] ) && count( $post['event_begin'] ) > 1 ) || mc_event_is_grouped( $group_id ) ) ? $group_id : 0;
		$event_span         = ( ! empty( $post['event_span'] ) && 0 !== (int) $event_group_id ) ? 1 : 0;
		$event_hide_end     = ( ! empty( $post['event_hide_end'] ) ) ? (int) $post['event_hide_end'] : 0;
		$event_hide_end     = ( '' === $time || '23:59:59' === $time ) ? 1 : $event_hide_end; // Hide end time on all day events.
		$events_access      = ( ! empty( $post['events_access'] ) ) ? $post['events_access'] : array();
		// Set location.
		if ( 'none' === $select_location && ( empty( $post['event_label'] ) && ! is_numeric( $saved_location ) ) ) {
			// If no event data defined, do nothing.
		} else {
			// Is a preset chosen?
			$location_to_set = ( is_numeric( $select_location ) ) ? $select_location : $saved_location;
			if ( ! is_numeric( $location_to_set ) ) {
				// The location to set is not an integer, but 'event_label' exists, so add a new location.
				$event_label       = ! empty( $post['event_label'] ) ? $post['event_label'] : '';
				$event_street      = ! empty( $post['event_street'] ) ? $post['event_street'] : '';
				$event_street2     = ! empty( $post['event_street2'] ) ? $post['event_street2'] : '';
				$event_city        = ! empty( $post['event_city'] ) ? $post['event_city'] : '';
				$event_state       = ! empty( $post['event_state'] ) ? $post['event_state'] : '';
				$event_postcode    = ! empty( $post['event_postcode'] ) ? $post['event_postcode'] : '';
				$event_region      = ! empty( $post['event_region'] ) ? $post['event_region'] : '';
				$event_country     = ! empty( $post['event_country'] ) ? $post['event_country'] : '';
				$event_url         = ! empty( $post['event_url'] ) ? $post['event_url'] : '';
				$event_longitude   = ! empty( $post['event_longitude'] ) ? $post['event_longitude'] : '';
				$event_latitude    = ! empty( $post['event_latitude'] ) ? $post['event_latitude'] : '';
				$event_zoom        = ! empty( $post['event_zoom'] ) ? $post['event_zoom'] : '';
				$event_phone       = ! empty( $post['event_phone'] ) ? $post['event_phone'] : '';
				$event_phone2      = ! empty( $post['event_phone2'] ) ? $post['event_phone2'] : '';
				$event_access      = ! empty( $post['event_access'] ) ? $post['event_access'] : '';
				$event_access      = ! empty( $post['event_access_hidden'] ) ? unserialize( $post['event_access_hidden'] ) : $event_access;
				$has_location_data = false;

				if ( '' !== trim( $event_label . $event_street . $event_street2 . $event_city . $event_state . $event_postcode . $event_region . $event_country . $event_url . $event_longitude . $event_latitude . $event_zoom . $event_phone . $event_phone2 ) ) {
					$has_location_data = true;
				}
				// Don't save this location if we're only doing data validation.
				if ( 'check' !== $action ) {
					if ( $has_location_data && 0 === $i ) {
						// Only add this with the first event, if adding multiples.
						$add_loc        = array(
							'location_label'     => $event_label,
							'location_street'    => $event_street,
							'location_street2'   => $event_street2,
							'location_city'      => $event_city,
							'location_state'     => $event_state,
							'location_postcode'  => $event_postcode,
							'location_region'    => $event_region,
							'location_country'   => $event_country,
							'location_url'       => $event_url,
							'location_longitude' => $event_longitude,
							'location_latitude'  => $event_latitude,
							'location_zoom'      => $event_zoom,
							'location_phone'     => $event_phone,
							'location_phone2'    => $event_phone2,
							'location_access'    => ( is_array( $event_access ) ) ? serialize( $event_access ) : '',
						);
						$loc_id         = mc_insert_location( $add_loc );
						$saved_location = $loc_id;
						/**
						 * Execute an action when a location is created during event editing.
						 *
						 * @hook mc_save_location
						 *
						 * @param {int|false} $loc_id Result of database insertion. Row ID or false.
						 * @param {array} $add_loc Array of location parameters to add.
						 * @param {array} $add_loc Array passed from event creation.
						 */
						$results = apply_filters( 'mc_save_location', $loc_id, $add_loc, $add_loc );
					}
				}
			}
		}
		// Perform validation on the submitted dates - checks for valid years and months.
		if ( mc_checkdate( $begin ) && mc_checkdate( $end ) ) {
			// Make sure dates are equal or end date is later than start date.
			if ( mc_strtotime( "$end $endtime" ) < mc_strtotime( "$begin $time" ) ) {
				$errors .= mc_show_error( __( 'Your event end date must be either after or the same as your event begin date', 'my-calendar' ), false );
			}
		} else {
			$errors .= mc_show_error( __( 'Your date format is correct but one or more of your dates is invalid. Check for number of days in month and leap year related errors.', 'my-calendar' ), false );
		}

		// Check for a valid or empty time.
		$time            = ( '' === $time ) ? '23:59:59' : mc_date( 'H:i:00', mc_strtotime( $time ), false );
		$time_format_one = '/^([0-1][0-9]):([0-5][0-9]):([0-5][0-9])$/';
		$time_format_two = '/^([2][0-3]):([0-5][0-9]):([0-5][0-9])$/';
		if ( preg_match( $time_format_one, $time ) || preg_match( $time_format_two, $time ) ) {
		} else {
			$errors .= mc_show_error( __( 'The time field must either be blank or be entered in the format hh:mm am/pm', 'my-calendar' ), false );
		}
		// Check for a valid or empty end time.
		if ( preg_match( $time_format_one, $endtime ) || preg_match( $time_format_two, $endtime ) || '' === $endtime ) {
		} else {
			$errors .= mc_show_error( __( 'The end time field must either be blank or be entered in the format hh:mm am/pm', 'my-calendar' ), false );
		}
		// Check for valid URL (blank or starting with http://).
		if ( ! ( '' === $event_link || preg_match( '/^(http)(s?)(:)\/\//', $event_link ) ) ) {
			$event_link = 'http://' . $event_link;
		}
	}
	// A title is required, and can't be more than 255 characters.
	$title_length = strlen( $title );
	if ( ! ( $title_length >= 1 && $title_length <= 255 ) ) {
		$title = __( 'Untitled Event', 'my-calendar' );
	}
	// Run checks on recurrence profile.
	$valid_recur = array( 'W', 'B', 'M', 'U', 'Y', 'D', 'E' );
	if ( ( 0 === (int) $repeats && 'S' === $recur ) || ( ( $repeats >= 0 ) && in_array( $recur, $valid_recur, true ) ) ) {
		$recur = $recur . $every;
	} else {
		// if it's not valid, assign a default value.
		$repeats = 0;
		$recur   = 'S1';
	}
	if ( isset( $post['mcs_check_conflicts'] ) ) {
		$conflicts = mcs_check_conflicts( $begin, $time, $end, $endtime, $saved_location );
		/**
		 * Filter the results of a check for time/location conflicts.
		 *
		 * @hook mcs_check_conflicts
		 *
		 * @param {array|bool} $conflicts False if no conflicts, array of conflicting events if found.
		 * @param {array} $post Query.
		 *
		 * @return {array|bool}
		 */
		$conflicts = apply_filters( 'mcs_check_conflicts', $conflicts, $post );
		if ( $conflicts ) {
			$conflict_id = $conflicts[0]->occur_id;
			$conflict_ev = mc_get_event( $conflict_id );
			if ( '1' === $conflict_ev->event_approved ) {
				$conflict = mc_get_details_link( $conflict_ev );
				// Translators: URL to event details.
				$errors .= mc_show_error( sprintf( __( 'That event conflicts with a <a href="%s">previously scheduled event</a>.', 'my-calendar' ), $conflict ), false, 'conflict' );
			} else {
				if ( mc_can_edit_event( $conflict_ev->event_id ) ) {
					$referer = urlencode( mc_get_current_url() );
					$link    = admin_url( "admin.php?page=my-calendar&mode=edit&event_id=$conflict_ev->event_id&ref=$referer" );
					// Translators: Link to edit event draft.
					$error = sprintf( __( 'That event conflicts with a <a href="%s">previously submitted draft</a>.', 'my-calendar' ), $link );
				} else {
					$error = __( 'That event conflicts with an unpublished draft event.', 'my-calendar' );
				}
				$errors .= mc_show_error( $error, false, 'draft-conflict' );
			}
		}
	}
	$spam_content = ( '' !== $desc ) ? $desc : $short;
	$spam         = mc_spam( $event_link, $spam_content, $post );
	// Likelihood that event will be flagged as spam, have a zero start time and be legit is minimal. Just kill it.
	if ( 1 === (int) $spam && '1970-01-01' === $begin ) {
		die;
	}

	$current_user = wp_get_current_user();
	$event_author = ( $event_author === $current_user->ID || current_user_can( 'mc_manage_events' ) ) ? $event_author : $current_user->ID;
	$primary      = ( ! $primary ) ? 1 : $primary;
	$cats         = ( isset( $cats ) && is_array( $cats ) ) ? $cats : array( 1 );
	// Set transient with start date/time of this event for 15 minutes.
	set_transient(
		'mc_last_event',
		array(
			'begin' => $begin,
			'time'  => $time,
		),
		60 * 15
	);
	$submit = array(
		// Begin strings.
		'event_begin'        => $begin,
		'event_end'          => $end,
		'event_title'        => $title,
		'event_desc'         => force_balance_tags( $desc ),
		'event_short'        => force_balance_tags( $short ),
		'event_time'         => $time,
		'event_endtime'      => $endtime,
		'event_link'         => $event_link,
		'event_recur'        => $recur,
		'event_image'        => $event_image,
		'event_access'       => ( is_array( $event_access ) ) ? serialize( $event_access ) : '',
		'event_tickets'      => $event_tickets,
		'event_registration' => $event_registration,
		'event_repeats'      => $repeats,
		// Begin integers.
		'event_author'       => $event_author,
		'event_category'     => $primary,
		'event_link_expires' => $expires,
		'event_approved'     => $approved,
		'event_host'         => $host,
		'event_flagged'      => $spam,
		'event_fifth_week'   => $event_fifth_week,
		'event_holiday'      => $event_holiday,
		'event_group_id'     => $event_group_id,
		'event_span'         => $event_span,
		'event_hide_end'     => $event_hide_end,
		'event_location'     => $saved_location,
		// Array: removed before DB insertion.
		'event_categories'   => $cats,
	);

	/**
	 * Generate errors for required fields.
	 *
	 * @hook mc_fields_required
	 *
	 * @param {string} $errors HTML output for errors.
	 * @param {array}  $submit Submitted data being tested.
	 *
	 * @return {string}
	 */
	$errors = ( $ignore_required ) ? $errors : apply_filters( 'mc_fields_required', $errors, $submit );

	// Create submission object.
	if ( '' !== $errors || 'check' === $action ) {
		$submission                     = ( ! is_object( $submission ) ) ? new stdClass() : $submission;
		$submission->event_id           = ( isset( $_GET['event_id'] ) && is_numeric( $_GET['event_id'] ) ) ? $_GET['event_id'] : false;
		$submission->event_title        = $title;
		$submission->event_desc         = $desc;
		$submission->event_begin        = $begin;
		$submission->event_end          = $end;
		$submission->event_time         = $time;
		$submission->event_endtime      = $endtime;
		$submission->event_recur        = $recur;
		$submission->event_repeats      = $repeats;
		$submission->event_host         = $host;
		$submission->event_category     = $primary;
		$submission->event_link         = $event_link;
		$submission->event_link_expires = $expires;
		$submission->event_label        = $event_label;
		$submission->event_street       = $event_street;
		$submission->event_street2      = $event_street2;
		$submission->event_city         = $event_city;
		$submission->event_state        = $event_state;
		$submission->event_postcode     = $event_postcode;
		$submission->event_country      = $event_country;
		$submission->event_region       = $event_region;
		$submission->event_url          = $event_url;
		$submission->event_longitude    = $event_longitude;
		$submission->event_latitude     = $event_latitude;
		$submission->event_zoom         = $event_zoom;
		$submission->event_phone        = $event_phone;
		$submission->event_phone2       = $event_phone2;
		$submission->event_author       = $event_author;
		$submission->event_short        = $short;
		$submission->event_approved     = $approved;
		$submission->event_image        = $event_image;
		$submission->event_fifth_week   = $event_fifth_week;
		$submission->event_holiday      = $event_holiday;
		$submission->event_flagged      = 0;
		$submission->event_group_id     = $event_group_id;
		$submission->event_span         = $event_span;
		$submission->event_hide_end     = $event_hide_end;
		$submission->event_access       = ( is_array( $event_access ) ) ? serialize( $event_access ) : '';
		$submission->events_access      = ( is_array( $events_access ) ) ? serialize( $events_access ) : '';
		$submission->event_tickets      = $event_tickets;
		$submission->event_registration = $event_registration;
		$submission->event_categories   = $cats;
		$submission->user_error         = true;
		$submission->event_post         = ( isset( $_GET['event_id'] ) && is_numeric( $_GET['event_id'] ) ) ? mc_get_event_post( $_GET['event_id'] ) : false;
	}

	if ( '' === $errors ) {
		$ok = true;

		$submit = array_map( 'mc_kses_post', $submit );
	} else {
		$ok = false;
	}

	$data = array( $ok, $submission, $submit, $errors, $post );

	return $data;
}

/**
 * Compare whether event date or recurrence characteristics have changed.
 *
 * @param array $update data being saved in update.
 * @param int   $id id of event being modified.
 *
 * @return boolean false if unmodified.
 */
function mc_compare( $update, $id ) {
	$event         = mc_get_first_event( $id );
	$update_string = '';
	$event_string  = '';

	foreach ( $update as $k => $v ) {
		// Event_recur and event_repeats always set to single and 0; event_begin and event_end need to be checked elsewhere.
		if ( 'event_recur' !== $k && 'event_repeats' !== $k && 'event_begin' !== $k && 'event_end' !== $k ) {
			$update_string .= trim( $v );
			$event_string  .= trim( $event->$k );
		}
	}
	$update_hash = md5( $update_string );
	$event_hash  = md5( $event_string );
	if ( $update_hash === $event_hash ) {
		return false;
	} else {
		return true;
	}
}

/**
 * Update a single event instance.
 *
 * @param int   $event_instance Instance ID.
 * @param int   $event_id New event ID.
 * @param array $update New date array.
 *
 * Return query result.
 */
function mc_update_instance( $event_instance, $event_id, $update = array() ) {
	global $wpdb;
	if ( ! empty( $update ) ) {
		$formats = array( '%d', '%s', '%s', '%d' );
		$begin   = $update['event_begin'] . ' ' . $update['event_time'];
		$end     = $update['event_end'] . ' ' . $update['event_endtime'];
		$data    = array(
			'occur_event_id' => $event_id,
			'occur_begin'    => $begin,
			'occur_end'      => $end,
			'occur_group_id' => $update['event_group_id'],
		);
	} else {
		$formats  = array( '%d', '%d' );
		$group_id = mc_get_data( 'event_group_id', $event_id );
		$data     = array(
			'occur_event_id' => $event_id,
			'occur_group_id' => $group_id,
		);
	}

	$result = $wpdb->update( my_calendar_event_table(), $data, array( 'occur_id' => $event_instance ), $formats, '%d' );

	return $result;
}

/**
 * Delete an instance from the database and record in deleted instances.
 *
 * @param int    $occur_id Instance ID.
 * @param int    $event_id Event ID.
 * @param string $begin Beginning time.
 * @param string $end Ending time.
 *
 * @return int|bool
 */
function mc_delete_instance( $occur_id, $event_id, $begin, $end ) {
	global $wpdb;
	$delete = 'DELETE FROM `' . my_calendar_event_table() . '` WHERE occur_id = %d';
	$result = $wpdb->query( $wpdb->prepare( $delete, $occur_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	$event_post  = mc_get_event_post( $event_id );
	$instances   = get_post_meta( $event_post, '_mc_deleted_instances', true );
	$instances   = ( ! is_array( $instances ) ) ? array() : $instances;
	$instances[] = array(
		'occur_event_id' => $event_id,
		'occur_begin'    => $begin,
		'occur_end'      => $end,
	);
	update_post_meta( $event_post, '_mc_deleted_instances', $instances );

	return $result;
}

/**
 * Insert a single instance to the database.
 *
 * @param array $args ['id', 'event_date', 'event_end', 'event_time', 'event_endtime', 'group'].
 *
 * @return int New instance ID.
 */
function mc_insert_instance( $args ) {
	global $wpdb;
	$event_end     = $args['event_end'];
	$event_date    = $args['event_date'];
	$event_endtime = $args['event_endtime'];
	$event_time    = $args['event_time'];
	$event_id      = $args['id'];
	$group_id      = ( '' === $args['group'] ) ? $args['id'] : (int) $args['group'];

	// event end can not be earlier than event start.
	if ( ! $event_end || strtotime( $event_end ) < strtotime( $event_date ) ) {
		$event_end = $event_date;
	}

	$begin = strtotime( $event_date . ' ' . $event_time );
	$end   = ( '' !== $event_endtime ) ? strtotime( $event_end . ' ' . $event_endtime ) : strtotime( $event_end . ' ' . $event_time ) + HOUR_IN_SECONDS;

	$format = array( '%d', '%s', '%s', '%d' );
	$data   = array(
		'occur_event_id' => $event_id,
		'occur_begin'    => mc_date( 'Y-m-d  H:i:s', $begin, false ),
		'occur_end'      => mc_date( 'Y-m-d  H:i:s', $end, false ),
		'occur_group_id' => $group_id,
	);
	$wpdb->insert( my_calendar_event_table(), $data, $format );
	$id          = $wpdb->insert_id;
	$event_post  = mc_get_event_post( $event_id );
	$instances   = get_post_meta( $event_post, '_mc_custom_instances', true );
	$instances   = ( ! is_array( $instances ) ) ? array() : $instances;
	$instances[] = $data;
	update_post_meta( $event_post, '_mc_custom_instances', $instances );

	return $id;
}

/**
 * Update a single arbitrary field in event table
 *
 * @param int    $event_id Event to modify.
 * @param string $field column name for field.
 * @param mixed  $value required value for field.
 * @param string $format type of data format.
 *
 * @return int|false $result Updated row or false.
 */
function mc_update_data( $event_id, $field, $value, $format = '%d' ) {
	global $wpdb;
	$data   = array( $field => $value );
	$result = $wpdb->update( my_calendar_table(), $data, array( 'event_id' => $event_id ), $format, '%d' );

	return $result;
}

/**
 * Generate normal date time input fields
 *
 * @param string  $form Previous defined values.
 * @param boolean $has_data Whether field has data.
 * @param object  $data form data object.
 * @param int     $instance [not used here].
 * @param string  $context rendering context [not used].
 *
 * @return string submission form part
 */
function mc_standard_datetime_input( $form, $has_data, $data, $instance, $context = 'admin' ) {
	if ( $has_data ) {
		$event_begin = esc_attr( $data->event_begin );
		$event_end   = esc_attr( $data->event_end );

		if ( isset( $_GET['date'] ) ) {
			$event       = mc_get_event( (int) $_GET['date'] );
			$event_begin = mc_date( 'Y-m-d', mc_strtotime( $event->occur_begin ), false );
			$event_end   = mc_date( 'Y-m-d', mc_strtotime( $event->occur_end ), false );
		}
		// Set event end to empty if matches begin. Makes input and changes easier.
		if ( $event_begin === $event_end ) {
			$event_end = '';
		}
		$starttime = ( mc_is_all_day( $data ) ) ? '' : mc_date( 'H:i', mc_strtotime( $data->event_time ), false );
		$endtime   = ( mc_is_all_day( $data ) ) ? '' : mc_date( 'H:i', mc_strtotime( $data->event_endtime ), false );
	} else {
		// Set start date/time to last event.
		$transient_start = get_transient( 'mc_last_event' );
		if ( is_array( $transient_start ) ) {
			$event_begin = $transient_start['begin'];
			$starttime   = $transient_start['time'];
		} else {
			$event_begin = mc_date( 'Y-m-d' );
			$starttime   = '';
		}
		$event_end = '';
		$endtime   = '';
	}

	$allday       = ( $has_data && ( mc_is_all_day( $data ) ) ) ? ' checked="checked"' : '';
	$hide         = ( $has_data && '1' === $data->event_hide_end ) ? ' checked="checked"' : '';
	$allday_label = ( $has_data ) ? mc_notime_label( $data ) : mc_get_option( 'notime_text' );
	$same_day     = ( $has_data && 'true' === get_post_meta( $data->event_post, '_event_same_day', true ) ) ? ' checked="checked"' : '';
	$args         = array(
		'value' => $event_begin,
		'id'    => 'mc_event_date',
		'name'  => 'event_begin[]',
	);
	$picker_begin = mc_datepicker_html( $args );
	$args         = array(
		'value' => $event_end,
		'id'    => 'mc_event_enddate',
		'name'  => 'event_end[]',
	);
	$picker_end   = mc_datepicker_html( $args );
	/**
	 * Set the latest time an event can be scheduled for.
	 *
	 * @hook mc_time_max
	 *
	 * @param {string} $max Time string. Default 00:00.
	 *
	 * @return {string}
	 */
	$max = apply_filters( 'mc_time_max', '00:00' );
	/**
	 * Set the earliest time an event can be scheduled for.
	 *
	 * @hook mc_time_min
	 *
	 * @param {string} $min Time string. Default 00:00.
	 *
	 * @return {string}
	 */
	$min    = apply_filters( 'mc_time_min', '00:00' );
	$attrs  = ( '00:00' !== $min || '00:00' !== $max ) ? ' max="' . $max . '" min="' . $min . '"' : '';
	$append = '';
	$range  = '';
	$aria   = '';
	if ( '00:00' !== $max || '00:00' !== $min ) {
		// Translators: starting time, ending time.
		$range  = '<p id="mc_time_range_allowed">' . sprintf( __( 'Times must be between %1$s and %2$s', 'my-calendar' ), mc_date( mc_time_format(), strtotime( $min ) ), mc_date( mc_time_format(), strtotime( $max ) ) ) . '</p>';
		$aria   = ' aria-describedby="mc_time_range_allowed"';
		$append = '<span class="validity"><span class="dashicons dashicons-no" aria-hidden="true"></span>' . __( 'Invalid time', 'my-calendar' ) . '</span>';
	}

	$form .= $range . '<div class="columns">
		<p>
		<label for="mc_event_time">' . __( 'Start Time', 'my-calendar' ) . '</label>
		<input type="time" id="mc_event_time" name="event_time[]" ' . $aria . $attrs . ' size="8" value="' . esc_attr( $starttime ) . '" />' . $append . '
		</p>
		<p>
		<label for="mc_event_endtime">' . __( 'End Time', 'my-calendar' ) . '<span class="hidden">' . __( ' (hidden)', 'my-calendar' ) . '</span></label>
		<input type="time" id="mc_event_endtime" name="event_endtime[]" ' . $aria . $attrs . ' size="8" value="' . esc_attr( $endtime ) . '" />' . $append . '
		</p>
		<p>
		<label for="mc_event_date" id="eblabel">' . __( 'Date', 'my-calendar' ) . '</label> ' . $picker_begin . '
		</p>
		<p>
			<label for="mc_event_enddate" id="eelabel" aria-labelledby="eelabel event_date_error"><em>' . __( 'End Date (optional)', 'my-calendar' ) . '</em></label> ' . $picker_end . '<span id="event_date_error" aria-live="assertive"><span class="dashicons dashicons-no" aria-hidden="true"></span>' . __( 'Your selected end date is before your start date.', 'my-calendar' ) . '</span>
		</p>
	</div>
	<ul class="checkboxes">
		<li><input type="checkbox" value="1" id="e_allday" name="event_allday"' . $allday . ' /> <label for="e_allday">' . __( 'All day event', 'my-calendar' ) . '</label> <span class="event_time_label"><label for="e_time_label">' . __( 'Time label:', 'my-calendar' ) . '</label> <input type="text" name="event_time_label" id="e_time_label" value="' . esc_attr( $allday_label ) . '" /> </li>
		<li><input type="checkbox" value="1" id="e_hide_end" name="event_hide_end"' . $hide . ' /> <label for="e_hide_end">' . __( 'Hide end time', 'my-calendar' ) . '</label></li>
		<li><input type="checkbox" value="1" id="e_same_day" name="event_same_day"' . $same_day . ' /> <label for="e_same_day">' . __( 'Same day event', 'my-calendar' ) . '</label></li>
	</ul>';

	return $form;
}

/**
 * Repeatable date/time input form.
 *
 * @param string  $form Previous defined values.
 * @param boolean $has_data Whether field has data.
 * @param object  $data form data object.
 * @param string  $context rendering context [not used].
 */
function mc_repeatable_datetime_input( $form, $has_data, $data, $context = 'admin' ) {
	echo mc_get_repeatable_datetime_input( $form, $has_data, $data, $context = 'admin' );
}

/**
 * Repeatable date/time input form.
 *
 * @param string  $form Previous defined values.
 * @param boolean $has_data Whether field has data.
 * @param object  $data form data object.
 * @param string  $context rendering context [not used].
 *
 * @return string submission form part
 */
function mc_get_repeatable_datetime_input( $form, $has_data, $data, $context = 'admin' ) {
	if ( $has_data ) {
		$event_begin = $data->event_begin;
		$event_end   = $data->event_end;

		if ( isset( $_GET['date'] ) ) {
			$event       = mc_get_event( (int) $_GET['date'] );
			$event_begin = mc_date( 'Y-m-d', mc_strtotime( $event->occur_begin ), false );
			$event_end   = mc_date( 'Y-m-d', mc_strtotime( $event->occur_end ), false );
		}
		// Set event end to empty if matches begin. Makes input and changes easier.
		if ( $event_begin === $event_end ) {
			$event_end = '';
		}
		$starttime = ( mc_is_all_day( $data ) ) ? '' : mc_date( 'H:i', mc_strtotime( $data->event_time ), false );
		$endtime   = ( mc_is_all_day( $data ) ) ? '' : mc_date( 'H:i', mc_strtotime( $data->event_endtime ), false );
	} else {
		$event_end = '';
		$starttime = '';
		$endtime   = '';
	}

	$form .= '<div class="mc-buttons"><button type="button" class="del_field button button-delete">' . __( 'Cancel', 'my-calendar' ) . '</button><button type="button" class="remove_field button button-delete hidden">' . __( 'Remove', 'my-calendar' ) . '</button></div>
	<div class="columns">
		<p>
			<label for="mc_event_time_">' . __( 'Start Time', 'my-calendar' ) . '</label>
		<input type="time" class="event-time" id="mc_event_time_" name="event_time[]" size="8" value="' . esc_attr( $starttime ) . '" disabled />
		</p>
		<p>
			<label for="mc_event_endtime_">' . __( 'End Time', 'my-calendar' ) . '</label>
		<input type="time" class="event-end" id="mc_event_endtime_" name="event_endtime[]" size="8" value="' . esc_attr( $endtime ) . '" disabled />
		</p>
		<p>
			<label for="mc_event_date_" id="eblabel">' . __( 'Date', 'my-calendar' ) . '</label> <input type="date" class="event-begin" id="mc_event_date_" name="event_begin[]" value="" disabled />
		</p>
		<p>
			<label for="mc_event_enddate_" id="eelabel"><em>' . __( 'End Date (optional)', 'my-calendar' ) . '</em></label> <input type="date" class="event-end" id="mc_event_enddate_" name="event_end[]" value="' . esc_attr( $event_end ) . '" disabled />
		</p>
	</div>';

	return $form;
}

/**
 * Date time inputs to add a single instance to recurring event info
 *
 * @param object $data Source event data.
 *
 * @return string form HTML
 */
function mc_recur_datetime_input( $data ) {
	$event_begin  = ( $data->event_begin ) ? $data->event_begin : mc_date( 'Y-m-d' );
	$event_end    = ( $data->event_end && $data->event_end !== $data->event_begin ) ? $data->event_end : '';
	$starttime    = ( $data->event_time ) ? $data->event_time : '';
	$endtime      = ( $data->event_endtime ) ? $data->event_endtime : '';
	$args         = array(
		'value' => $event_begin,
		'id'    => 'r_begin',
		'name'  => 'recur_begin[]',
	);
	$picker_begin = mc_datepicker_html( $args );
	$args         = array(
		'value' => $event_end,
		'id'    => 'r_end',
		'name'  => 'recur_end[]',
	);
	$picker_end   = mc_datepicker_html( $args );

	$form = '
	<div class="columns">
		<p>
			<label for="r_time">' . __( 'Start Time', 'my-calendar' ) . '</label>
			<input type="time" id="r_time" name="recur_time[]" size="8" value="' . esc_attr( $starttime ) . '" />
		</p>
		<p>
			<label for="r_endtime">' . __( 'End Time', 'my-calendar' ) . '</label>
			<input type="time" id="r_endtime" name="recur_endtime[]" size="8" value="' . esc_attr( $endtime ) . '" />
		</p>
		<p>
			<label for="r_begin">' . __( 'Date', 'my-calendar' ) . '</label> ' . $picker_begin . '
		</p>
		<p>
			<label for="r_end"><em>' . __( 'End Date (optional)', 'my-calendar' ) . '</em></label>
			 ' . $picker_end . '
		</p>
	</div>';

	return $form;
}

/**
 * Generate standard event registration info fields.
 *
 * @param string  $form Form HTML.
 * @param boolean $has_data Does this event have data.
 * @param object  $data Data for event.
 * @param string  $context Context displayed in.
 *
 * @return string HTML output for form
 */
function mc_standard_event_registration( $form, $has_data, $data, $context = 'admin' ) {
	if ( $has_data ) {
		$tickets      = $data->event_tickets;
		$registration = stripslashes( esc_attr( $data->event_registration ) );
	} else {
		$tickets      = '';
		$registration = '';
	}

	$form .= "<p>
				<label for='event_tickets'>" . __( 'Tickets URL', 'my-calendar' ) . "</label> <input type='url' name='event_tickets' id='event_tickets' value='" . esc_attr( $tickets ) . "' />
			</p>
			<p>
				<label for='event_registration'>" . __( 'Registration Information', 'my-calendar' ) . "</label> <textarea name='event_registration'id='event_registration'cols='40'rows='4'/>$registration</textarea>
			</p>";

	/**
	 * Filter event registration form for event input.
	 *
	 * @hook mc_event_registration_form
	 *
	 * @param {string} $form Default form HTML output.
	 * @param {bool}   $has_data If this event has data.
	 * @param {object} $data Event object.
	 * @param {string} $context Admin context.
	 *
	 * @return {string}
	 */
	return apply_filters( 'mc_event_registration_form', $form, $has_data, $data, 'admin' );
}

add_action( 'save_post', 'mc_post_update_event' );
/**
 * When updating event post, make sure changed featured image is copied into event.
 *
 * @param int $id Post ID.
 */
function mc_post_update_event( $id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $id ) || ! ( get_post_type( $id ) === 'mc-events' ) ) {
		return $id;
	}
	// If event image fields are empty, don't image that's being removed back to the event.
	if ( isset( $_POST['event_image'] ) && '' === $_POST['event_image'] || isset( $_POST['event_image_id'] ) && '' === $_POST['event_image_id'] ) {
		return $id;
	}
	$post           = get_post( $id );
	$featured_image = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
	$event_id       = get_post_meta( $post->ID, '_mc_event_id', true );
	if ( esc_url( $featured_image ) ) {
		mc_update_data( $event_id, 'event_image', $featured_image, '%s' );
	}
}

/**
 * Generate controls for a given event
 *
 * @param string  $mode Context of event editing page.
 * @param boolean $has_data Does this event have data.
 * @param object  $event Event data.
 * @param string  $position location of form.
 *
 * @return string output controls
 */
function mc_controls( $mode, $has_data, $event, $position = 'header' ) {
	$controls = array();

	if ( 'edit' === $mode ) {
		$publish_text = __( 'Update', 'my-calendar' );
		$event_id     = $event->event_id;
		$mcnonce      = wp_create_nonce( '_mcnonce' );
		$url          = add_query_arg(
			array(
				'mode'     => 'delete',
				'event_id' => $event_id,
				'_mcnonce' => $mcnonce,
			),
			admin_url( 'admin.php?page=my-calendar-manage' )
		);
		if ( isset( $_GET['date'] ) ) {
			$id = ( is_numeric( $_GET['date'] ) ) ? absint( $_GET['date'] ) : false;
			if ( $id ) {
				$url = add_query_arg( 'date', $id, $url );
			}
		}
		$controls['delete'] = "<span class='dashicons dashicons-no' aria-hidden='true'></span><a href='" . esc_url( $url ) . "' class='delete'>" . __( 'Delete', 'my-calendar' ) . '</a>';
		/**
		 * Check whether permalinks are enabled.
		 *
		 * @hook mc_use_permalinks
		 *
		 * @param {string} $option Value of mc_use_permalinks setting.
		 *
		 * @return {string} 'true' value if permalinks are enabled.
		 */
		if ( 'true' === apply_filters( 'mc_use_permalinks', mc_get_option( 'use_permalinks' ) ) ) {
			$post_id          = $event->event_post;
			$post_link        = ( $post_id ) ? get_edit_post_link( $post_id ) : false;
			$controls['post'] = ( $post_link ) ? sprintf( "<span class='dashicons dashicons-admin-post' aria-hidden='true'></span><a href='%s'>" . __( 'Edit Event Post', 'my-calendar' ) . '</a>', $post_link ) : '';
		}
	} else {
		$publish_text = __( 'Publish', 'my-calendar' );
	}

	if ( $has_data && is_object( $event ) ) {
		$first    = mc_get_first_event( $event->event_id );
		$view_url = mc_get_details_link( $first );
		if ( mc_event_published( $event ) ) {
			$controls['view'] = "<span class='dashicons dashicons-laptop' aria-hidden='true'></span><a href='" . esc_url( $view_url ) . "' class='view'>" . __( 'View', 'my-calendar' ) . '</a>';
		} elseif ( current_user_can( 'mc_manage_events' ) ) {
			$controls['view'] = "<span class='dashicons dashicons-laptop' aria-hidden='true'></span><a href='" . add_query_arg( 'preview', 'true', $view_url ) . "' class='view'>" . __( 'Preview', 'my-calendar' ) . '</a>';
		}
		$manage_text        = __( 'Events', 'my-calendar' );
		$controls['manage'] = "<span class='dashicons dashicons-calendar' aria-hidden='true'></span>" . '<a href="' . admin_url( 'admin.php?page=my-calendar-manage' ) . '">' . $manage_text . '</a>';
	}
	if ( 'footer' === $position ) {
		if ( 'edit' === $mode ) {
			$controls['publish'] = '<input type="submit" name="save" class="button-primary" value="' . esc_attr( $publish_text ) . '" />';
		} else {
			if ( current_user_can( 'mc_approve_events' ) || current_user_can( 'mc_publish_events' ) ) {
				$controls['publish'] = '<button name="event_approved" value="0" class="button-secondary">' . __( 'Save Draft', 'my-calendar' ) . '</button> <button name="event_approved" value="1" class="button-primary">' . esc_attr( $publish_text ) . '</button>';
			} else {
				$controls['publish'] = '<button name="event_approved" value="0" class="button-secondary">' . __( 'Save Draft', 'my-calendar' ) . '</button>';
			}
		}
	}
	// Event Status settings: draft, published, trash, (custom).
	$statuses = mc_event_statuses();
	if ( 'header' === $position ) {
		$status_control = '';
		if ( 'edit' === $mode ) {
			$controls['publish']     = '<input type="submit" name="save" class="button-primary" value="' . esc_attr( $publish_text ) . '" />';
			$controls['prev_status'] = "<input type='hidden' name='prev_event_status' value='" . absint( $event->event_approved ) . "' />";
			if ( current_user_can( 'mc_approve_events' ) || current_user_can( 'mc_publish_events' ) ) { // Added by Roland P.
				foreach ( $statuses as $code => $label ) {
					$status_control .= '<option value="' . $code . '"' . selected( $event->event_approved, $code, false ) . '>' . $label . '</option>';
				}
			} else {
				unset( $statuses['1'] );
				foreach ( $statuses as $code => $label ) {
					$status_control .= '<option value="' . $code . '"' . selected( $event->event_approved, $code, false ) . '>' . $label . '</option>';
				}
			}
		} else { // Case: adding new event (if user can, then 1, else 0).
			$status_control = '';
			if ( current_user_can( 'mc_approve_events' ) || current_user_can( 'mc_publish_events' ) ) {
				$controls['publish'] = '<button name="event_approved" value="0" class="button-secondary">' . __( 'Save Draft', 'my-calendar' ) . '</button> <button name="event_approved" value="1" class="button-primary">' . esc_attr( $publish_text ) . '</button>';
			} else {
				$controls['publish'] = '<button name="event_approved" value="0" class="button-secondary">' . __( 'Save Draft', 'my-calendar' ) . '</button>';
			}
		}
		$controls['status'] = ( '' !== $status_control ) ? "
					<label for='e_approved' class='screen-reader-text'>" . __( 'Status', 'my-calendar' ) . "</label>
					<select name='event_approved' id='e_approved'>
						$status_control
					</select>" : '';
	}
	/**
	 * Filter array of controls shown on event editor.
	 *
	 * @hook mc_event_controls
	 *
	 * @param {array}  $controls Array of HTML strings to output in header and footer of event editor.
	 * @param {string} $mode Context of event editing page.
	 * @param {object} $event Event data.
	 * @param {string} $position location of form.
	 *
	 * @return {array}
	 */
	$controls = apply_filters( 'mc_filter_event_controls', $controls, $mode, $event, $position = 'header' );

	$controls_output = '';
	foreach ( $controls as $key => $control ) {
		if ( 'prev_status' !== $key ) {
			$control = ( '' !== $control ) ? '<li>' . $control . '</li>' : '';
		}

		$controls_output .= $control;
	}

	return '<ul>' . $controls_output . '</ul>';
}

/**
 * Get the array of available event status codes.
 *
 * @return array
 */
function mc_event_statuses() {
	// Switch to select status.
	$statuses = array(
		'1' => __( 'Publish', 'my-calendar' ),
		'0' => __( 'Draft', 'my-calendar' ),
		'2' => __( 'Trash', 'my-calendar' ),
	);
	/**
	 * Filter available event status types.
	 *
	 * @hook mc_event_statuses
	 *
	 * @param {array} Array of statuses where key is the integer value of the status and the value is the status label.
	 *
	 * @return {array}
	 */
	$statuses = apply_filters( 'mc_event_statuses', $statuses );

	return $statuses;
}

/**
 * Get a list of event in a group and list admin editing links
 *
 * @param int    $id group ID.
 * @param string $template Template format.
 */
function mc_grouped_events( $id, $template = '' ) {
	$id     = (int) $id;
	$output = '';

	$results = mc_get_grouped_events( $id );
	if ( ! empty( $results ) ) {
		foreach ( $results as $result ) {
			$first = mc_get_first_event( $result->event_id );
			if ( ! is_object( $first ) ) {
				continue;
			}
			$event   = $first->occur_event_id;
			$current = '<a href="' . admin_url( 'admin.php?page=my-calendar' ) . '&amp;mode=edit&amp;event_id=' . $event . '">';
			$close   = '</a>';
			$begin   = date_i18n( mc_date_format(), strtotime( $first->occur_begin ) ) . ', ' . mc_date( mc_time_format(), strtotime( $first->occur_begin ), false );
			$array   = array(
				'current' => $current,
				'begin'   => $begin,
				'end'     => $close,
			);

			$current_output = ( '' === $template ) ? $current . $begin : mc_draw_template( $array, $template );
			$output        .= "<li>$current_output</li>";
		}
	} else {
		$output = '<li>' . __( 'No grouped events', 'my-calendar' ) . '</li>';
	}

	echo wp_kses_post( $output );
}

/**
 * Generate recurrence options list
 *
 * @param string $value current event's value.
 * @param string $return_type Return type: <option>s or array of values.
 *
 * @return string|array form options or array of values.
 */
function mc_recur_options( $value, $return_type = 'select' ) {
	$options = '';
	$values  = array(
		array(
			'value'  => 'S',
			'label'  => __( 'Does not recur', 'my-calendar' ),
			'period' => 'none',
		),
		array(
			'value'  => 'D',
			'label'  => __( 'Daily', 'my-calendar' ),
			'period' => 24 * HOUR_IN_SECONDS,
		),
		array(
			'value'  => 'E',
			'label'  => __( 'Daily, weekdays only', 'my-calendar' ),
			'period' => 24 * HOUR_IN_SECONDS,
		),
		array(
			'value'  => 'W',
			'label'  => __( 'Weekly', 'my-calendar' ),
			'period' => 7 * DAY_IN_SECONDS,

		),
		array(
			'value'  => 'M',
			'label'  => __( 'Monthly by date (the 24th of each month)', 'my-calendar' ),
			'period' => MONTH_IN_SECONDS,
		),
		array(
			'value'  => 'U',
			'label'  => __( 'Monthly by day (the 3rd Monday of each month)', 'my-calendar' ),
			'period' => MONTH_IN_SECONDS,
		),
		array(
			'value'  => 'Y',
			'label'  => __( 'Yearly', 'my-calendar' ),
			'period' => YEAR_IN_SECONDS,
		),
	);
	if ( 'select' === $return_type ) {
		foreach ( $values as $key => $val ) {
			// Biweekly is just a subset of weekly types. No longer an option to choose.
			if ( 'B' === $value && 'W' === $val['value'] ) {
				$value = 'W';
			}
			$options .= '<option data-period="' . $val['period'] . '" value="' . esc_attr( $val['value'] ) . '" ' . selected( $val['value'], $value, false ) . '>' . esc_html( $val['label'] ) . '</option>';
		}
	} else {
		return $values;
	}

	return $options;
}

add_filter( 'mc_instance_data', 'mc_reuse_id', 10, 3 );
/**
 * If an instance ID is for the same starting date (date *only*), use same ID
 *
 * @param array  $data data to be inserted into occurrences.
 * @param string $begin Starting time for the new occurrence.
 * @param array  $instances Array of previous instances for this event.
 *
 * @return array new data to insert
 */
function mc_reuse_id( $data, $begin, $instances ) {
	$begin = sanitize_key( mc_date( 'Y-m-d', $begin, false ) );
	$keys  = array_keys( $instances );
	if ( ! empty( $instances ) && in_array( $begin, $keys, true ) ) {
		$restore_id       = $instances[ $begin ];
		$data['occur_id'] = $restore_id;
	}

	return $data;
}

add_filter( 'mc_instance_format', 'mc_reuse_id_format', 10, 3 );
/**
 * If an instance ID is for the same starting date (date *only*), return format for altered insertion.
 *
 * @param array  $format Original formats array.
 * @param string $begin Starting time for the new occurrence.
 * @param array  $instances Array of previous instances for this event.
 *
 * @return array new formats for data
 */
function mc_reuse_id_format( $format, $begin, $instances ) {
	$begin = sanitize_key( mc_date( 'Y-m-d', $begin, false ) );
	$keys  = array_keys( $instances );
	if ( ! empty( $instances ) && in_array( $begin, $keys, true ) ) {
		$format = array( '%d', '%s', '%s', '%d', '%d' );
	}

	return $format;
}

/**
 * Given a recurrence pattern and a start date/time, increment the additional instances of an event.
 *
 * @param integer $id Event ID in my_calendar db.
 * @param array   $post an array of POST data (or array containing dates).
 * @param boolean $test true if testing.
 * @param array   $instances When rebuilding, an array of all prior event dates & ids.
 *
 * @return array
 */
function mc_increment_event( $id, $post = array(), $test = false, $instances = array() ) {
	$event = mc_get_event_core( $id, true );
	if ( ! $event ) {
		return array();
	}
	$data   = array();
	$return = array();
	if ( empty( $post ) ) {
		$orig_begin = $event->event_begin . ' ' . $event->event_time;
		$orig_end   = $event->event_end . ' ' . $event->event_endtime;
	} else {
		$post_begin   = ( isset( $post['event_begin'] ) ) ? $post['event_begin'] : '';
		$post_time    = ( isset( $post['event_time'] ) ) ? $post['event_time'] : '';
		$post_end     = ( isset( $post['event_end'] ) ) ? $post['event_end'] : '';
		$post_endtime = ( isset( $post['event_endtime'] ) ) ? $post['event_endtime'] : '';
		$orig_begin   = $post_begin . ' ' . $post_time;
		$orig_end     = $post_end . ' ' . $post_endtime;
	}
	// Calculate time offset for date and time.
	$begin_diff = strtotime( gmdate( 'Y-m-d H:i:s', strtotime( $orig_begin ) ) ) - strtotime( gmdate( 'Y-m-d', strtotime( $orig_begin ) ) );
	$end_diff   = strtotime( gmdate( 'Y-m-d H:i:s', strtotime( $orig_end ) ) ) - strtotime( gmdate( 'Y-m-d', strtotime( $orig_end ) ) );

	$group_id = $event->event_group_id;
	$recurs   = str_split( $event->event_recur, 1 );
	$recur    = $recurs[0];
	// Can't use 2nd value directly if it's two digits.
	$every = ( isset( $recurs[1] ) ) ? str_replace( $recurs[0], '', $event->event_recur ) : 1;
	if ( 'S' !== $recur ) {

		// If this event had a rep of 0, translate that.
		if ( is_numeric( $event->event_repeats ) ) {
			// Backwards compatibility.
			$event_repetition = ( 0 !== (int) $event->event_repeats ) ? $event->event_repeats : _mc_increment_values( $recur );
			$post_until       = false;
		} else {
			$post_until = $event->event_repeats;
			// Set event repetition to 1. Autoincrement up as needed.
			$event_repetition = ( my_calendar_date_xcomp( $orig_end, $post_until ) ) ? 1 : false;
		}
		// Toggle recurrence type to single if invalid repeat target passed.
		$recur      = ( ! $event_repetition ) ? 'S' : $recur;
		$numforward = (int) $event_repetition;
		switch ( $recur ) {
			// Daily.
			case 'D':
				for ( $i = 0; $i <= $numforward; $i++ ) {
					$begin = my_calendar_add_date( $orig_begin, $i * $every, 0, 0 );
					$end   = my_calendar_add_date( $orig_end, $i * $every, 0, 0 );

					$data = array(
						'occur_event_id' => $id,
						'occur_begin'    => mc_date( 'Y-m-d  H:i:s', $begin, false ),
						'occur_end'      => mc_date( 'Y-m-d  H:i:s', $end, false ),
						'occur_group_id' => $group_id,
					);
					if ( true === $test && $i > 0 ) {
						return $data;
					}
					if ( $post_until ) {
						if ( $begin <= strtotime( $post_until ) ) {
							++$numforward;
						} else {
							continue;
						}
					}
					$return[] = $data;
					mc_insert_recurring( $data, $id, $begin, $instances, $test, 'daily' );
				}
				break;
			// Weekdays only.
			case 'E':
				// Every = $every = e.g. every 14 weekdays.
				// Num forward = $numforward = e.g. 7 times.
				for ( $i = 0; $i <= $numforward; $i++ ) {
					$begin = strtotime( $event->event_begin . ' ' . ( $every * $i ) . ' weekdays' ) + $begin_diff;
					$end   = strtotime( $event->event_end . ' ' . ( $every * $i ) . ' weekdays' ) + $end_diff;
					$data  = array(
						'occur_event_id' => $id,
						'occur_begin'    => mc_date( 'Y-m-d  H:i:s', $begin, false ),
						'occur_end'      => mc_date( 'Y-m-d  H:i:s', $end, false ),
						'occur_group_id' => $group_id,
					);
					if ( true === $test && $i > 0 ) {
						return $data;
					}
					if ( $post_until ) {
						if ( $begin <= strtotime( $post_until ) ) {
							++$numforward;
						} else {
							continue;
						}
					}
					$return[] = $data;
					mc_insert_recurring( $data, $id, $begin, $instances, $test, 'weekday' );
				}
				break;
			// Weekly.
			case 'W':
				for ( $i = 0; $i <= $numforward; $i++ ) {
					$begin = my_calendar_add_date( $orig_begin, ( $i * 7 ) * $every, 0, 0 );
					$end   = my_calendar_add_date( $orig_end, ( $i * 7 ) * $every, 0, 0 );
					$data  = array(
						'occur_event_id' => $id,
						'occur_begin'    => mc_date( 'Y-m-d  H:i:s', $begin, false ),
						'occur_end'      => mc_date( 'Y-m-d  H:i:s', $end, false ),
						'occur_group_id' => $group_id,
					);
					if ( true === $test && $i > 0 ) {
						return $data;
					}
					if ( $post_until ) {
						if ( $begin <= strtotime( $post_until ) ) {
							++$numforward;
						} else {
							continue;
						}
					}
					$return[] = $data;
					mc_insert_recurring( $data, $id, $begin, $instances, $test, 'weekly' );
				}
				break;
			// Biweekly.
			case 'B':
				for ( $i = 0; $i <= $numforward; $i++ ) {
					$begin = my_calendar_add_date( $orig_begin, ( $i * 14 ), 0, 0 );
					$end   = my_calendar_add_date( $orig_end, ( $i * 14 ), 0, 0 );
					$data  = array(
						'occur_event_id' => $id,
						'occur_begin'    => mc_date( 'Y-m-d  H:i:s', $begin, false ),
						'occur_end'      => mc_date( 'Y-m-d  H:i:s', $end, false ),
						'occur_group_id' => $group_id,
					);
					if ( true === $test && $i > 0 ) {
						return $data;
					}
					if ( $post_until ) {
						if ( $begin <= strtotime( $post_until ) ) {
							++$numforward;
						} else {
							continue;
						}
					}
					$return[] = $data;
					mc_insert_recurring( $data, $id, $begin, $instances, $test, 'biweekly' );
				}
				break;
			// Monthly by date.
			case 'M':
				for ( $i = 0; $i <= $numforward; $i++ ) {
					$begin = my_calendar_add_date( $orig_begin, 0, $i * $every, 0 );
					$end   = my_calendar_add_date( $orig_end, 0, $i * $every, 0 );
					$data  = array(
						'occur_event_id' => $id,
						'occur_begin'    => mc_date( 'Y-m-d  H:i:s', $begin, false ),
						'occur_end'      => mc_date( 'Y-m-d  H:i:s', $end, false ),
						'occur_group_id' => $group_id,
					);
					if ( true === $test && $i > 0 ) {
						return $data;
					}
					if ( $post_until ) {
						if ( $begin <= strtotime( $post_until ) ) {
							++$numforward;
						} else {
							continue;
						}
					}
					$return[] = $data;
					mc_insert_recurring( $data, $id, $begin, $instances, $test, 'monthly' );
				}
				break;
			// Monthly by day.
			case 'U':
				// The numeric week of the month this event occurs in.
				$week_of_event = mc_week_of_month( mc_date( 'd', strtotime( $event->event_begin ), false ) );
				$newbegin      = my_calendar_add_date( $orig_begin, 28, 0, 0 );
				$newend        = my_calendar_add_date( $orig_end, 28, 0, 0 );
				$data          = array(
					'occur_event_id' => $id,
					'occur_begin'    => mc_date( 'Y-m-d  H:i:s', strtotime( $orig_begin ), false ),
					'occur_end'      => mc_date( 'Y-m-d  H:i:s', strtotime( $orig_end ), false ),
					'occur_group_id' => $group_id,
				);
				mc_insert_recurring( $data, $id, strtotime( $orig_begin ), $instances, $test, 'month-by-day' );

				$numforward = ( $numforward - 1 );
				for ( $i = 0; $i <= $numforward; $i++ ) {
					$skip = false;
					// If next week is a different month, and we're supposed move the event, return true.
					$move_event = mc_move_date( $newbegin, $event );
					$week       = mc_week_of_month( mc_date( 'd', $newbegin, false ) );
					if ( $week === $week_of_event || $move_event ) {
						// If this is the correct week or is the last week of the month and dates are adjustable.
					} else {
						// If this is not the correct week generate a new date + 1 week.
						$oldbegin   = $newbegin;
						$oldend     = $newend;
						$newbegin   = my_calendar_add_date( mc_date( 'Y-m-d  H:i:s', $newbegin, false ), 7, 0, 0 );
						$newend     = my_calendar_add_date( mc_date( 'Y-m-d  H:i:s', $newend, false ), 7, 0, 0 );
						$week       = mc_week_of_month( mc_date( 'd', $newbegin, false ) );
						$move_event = mc_move_date( $newbegin, $event );
						if ( $week === $week_of_event || true === $move_event ) {
							// If $newbegin is the correct week or is the last week of the month and dates are adjustable.
						} else {
							// Restore values because we're now in the wrong month.
							$newbegin = $oldbegin;
							$newend   = $oldend;
							$skip     = true;
						}
					}
					// Skip on events starting on 5th week but not moved to the 4th week in months without 5 weeks.
					if ( ! $skip ) {
						$data = array(
							'occur_event_id' => $id,
							'occur_begin'    => mc_date( 'Y-m-d  H:i:s', $newbegin, false ),
							'occur_end'      => mc_date( 'Y-m-d  H:i:s', $newend, false ),
							'occur_group_id' => $group_id,
						);
						if ( true === $test && $i > 0 ) {
							return $data;
						}
						if ( $post_until ) {
							if ( $newbegin <= strtotime( $post_until ) ) {
								++$numforward;
							} else {
								continue;
							}
						}
						$return[] = $data;
						mc_insert_recurring( $data, $id, $newbegin, $instances, $test, 'month-by-day' );
						// Jump forward 4 weeks.
					} else {
						++$numforward;
					}
					$newbegin = my_calendar_add_date( mc_date( 'Y-m-d  H:i:s', $newbegin, false ), 28, 0, 0 );
					$newend   = my_calendar_add_date( mc_date( 'Y-m-d  H:i:s', $newend, false ), 28, 0, 0 );
				}
				break;
			// Annual.
			case 'Y':
				for ( $i = 0; $i <= $numforward; $i++ ) {
					$begin = my_calendar_add_date( $orig_begin, 0, 0, $i * $every );
					$end   = my_calendar_add_date( $orig_end, 0, 0, $i * $every );
					$data  = array(
						'occur_event_id' => $id,
						'occur_begin'    => mc_date( 'Y-m-d  H:i:s', $begin, false ),
						'occur_end'      => mc_date( 'Y-m-d  H:i:s', $end, false ),
						'occur_group_id' => $group_id,
					);
					if ( true === $test && $i > 0 ) {
						return $data;
					}
					if ( $post_until ) {
						if ( $begin <= strtotime( $post_until ) ) {
							++$numforward;
						} else {
							continue;
						}
					}
					$return[] = $data;
					mc_insert_recurring( $data, $id, $begin, $instances, $test, 'annual' );
				}
				break;
		}
	} else {
		$begin = strtotime( $orig_begin );
		$end   = strtotime( $orig_end );
		$data  = array(
			'occur_event_id' => $id,
			'occur_begin'    => mc_date( 'Y-m-d  H:i:s', $begin, false ),
			'occur_end'      => mc_date( 'Y-m-d  H:i:s', $end, false ),
			'occur_group_id' => $group_id,
		);
		mc_insert_recurring( $data, $id, $begin, $instances, $test, 'single' );
	}

	if ( true === $test ) {
		return $return;
	}

	return $data;
}

/**
 * Check whether a last-week of the month recurring date needs to be moved.
 * Checks a given date & an object to see whether the event should only exist in the 5th week or is intended for the last week, regardless of number, then tests the date to see which week it is.
 *
 * @param int    $newbegin A datestamp to check against.
 * @param object $event The My Calendar event object source to generate recurrences for.
 *
 * @return bool
 */
function mc_move_date( $newbegin, $event ) {
	$week_of_event = mc_week_of_month( mc_date( 'd', strtotime( $event->event_begin ), false ) );
	$fifth_week    = ( 1 === (int) $event->event_fifth_week ) ? true : false;
	if ( ! $fifth_week ) {
		return false;
	}
	// Is the date one week ahead of the current date in the next month. If so, this is the last week.
	$next_week_diff = ( mc_date( 'm', $newbegin, false ) === mc_date( 'm', my_calendar_add_date( mc_date( 'Y-m-d', $newbegin, false ), 7, 0, 0 ), false ) ) ? false : true;
	$week           = mc_week_of_month( mc_date( 'd', $newbegin, false ) );
	// If next week is not this month, and event should move, return true. This is the last week of the month.
	$move_event = ( ( ( $week + 1 ) === $week_of_event ) && ( true === $next_week_diff ) ) ? true : false;

	return $move_event;
}

/**
 * Insert a recurring event instance.
 *
 * @param array       $data Instance data.
 * @param int         $id Event ID.
 * @param string      $begin Beginning date.
 * @param array       $instances Array of existing dates used when rebuilding values.
 * @param bool|string $test Whether we're testing values or generating dates.
 * @param string      $context Type of recurring event.
 */
function mc_insert_recurring( $data, $id, $begin, $instances, $test, $context ) {
	global $wpdb;
	$format = array( '%d', '%s', '%s', '%d' );
	if ( ! $test ) {
		/**
		 * Short circuit inserting a recurring event. Return true if event should not be inserted.
		 *
		 * @hook mc_insert_recurring
		 *
		 * @param {bool}   $insert True to skip inserting.
		 * @param {array}  $data Event date info.
		 * @param {int}    $id Event ID.
		 * @param {string} $context Type of recurring event.
		 *
		 * @return {bool}
		 */
		$insert = apply_filters( 'mc_insert_recurring', false, $data, $id, $context );
		if ( ! $insert ) {
			/**
			 * Filter recurring event instance data array.
			 *
			 * @hook mc_instance_data
			 *
			 * @param {array}  $format Array of data passed to insert query.
			 * @param {string} $begin Beginning date.
			 * @param {array}  $instances Original instances.
			 *
			 * @return {array}
			 */
			$data = apply_filters( 'mc_instance_data', $data, $begin, $instances );
			/**
			 * Filter recurring event instance database format array.
			 *
			 * @hook mc_instance_format
			 *
			 * @param {array}  $format Array of placeholder formats in insert query.
			 * @param {string} $begin Beginning date.
			 * @param {array}  $instances Original instances.
			 *
			 * @return {array}
			 */
			$format = apply_filters( 'mc_instance_format', $format, $begin, $instances );
			$wpdb->insert( my_calendar_event_table(), $data, $format );
		}
	}
}

/**
 * Execute a refresh of the My Calendar primary URL cache if caching plug-in installed.
 *
 * @param string $action Type of action performed.
 * @param array  $data Data passed to filter.
 * @param int    $event_id Event ID being affected.
 * @param int    $result Result of calendar save query.
 */
function mc_refresh_cache( $action, $data, $event_id, $result ) {
	$mc_uri_id = mc_get_option( 'uri_id' );
	/**
	 * Filter URLS that should be refreshed in caches.
	 *
	 * @hook mc_cached_pages_to_refresh
	 *
	 * @param {array}  $to_refresh Array of post IDs to clear cache on.
	 * @param {string} $action My Calendar action executing.
	 * @param {array}  $data Data passed from event.
	 * @param {int}    $event_id Event ID.
	 * @param {int}    $result Result of calendar database query.
	 *
	 * @return {array}
	 */
	$to_refresh = apply_filters( 'mc_cached_pages_to_refresh', array( $mc_uri_id ), $action, $data, $event_id, $result );

	foreach ( $to_refresh as $calendar ) {
		if ( ! $calendar || ! get_post( $calendar ) ) {
			continue;
		}
		// W3 Total Cache.
		if ( function_exists( 'w3tc_pgcache_flush_post' ) ) {
			w3tc_pgcache_flush_post( $calendar );
		}

		// WP Super Cache.
		if ( function_exists( 'wp_cache_post_change' ) ) {
			wp_cache_post_change( $calendar );
		}

		// WP Rocket.
		if ( function_exists( 'rocket_clean_post' ) ) {
			rocket_clean_post( $calendar );
		}

		// WP Fastest Cache.
		if ( isset( $GLOBALS['wp_fastest_cache'] ) && method_exists( $GLOBALS['wp_fastest_cache'], 'singleDeleteCache' ) ) {
			$GLOBALS['wp_fastest_cache']->singleDeleteCache( false, $calendar );
		}

		// Comet Cache.
		if ( class_exists( 'comet_cache' ) ) {
			comet_cache::clearPost( $calendar );
		}

		// Cache Enabler.
		if ( class_exists( 'Cache_Enabler' ) ) {
			Cache_Enabler::clear_page_cache_by_post_id( $calendar );
		}

		if ( class_exists( 'WPO_Page_Cache' ) ) {
			WPO_Page_Cache::delete_single_post_cache( $calendar );
		}
	}
}
add_action( 'mc_save_event', 'mc_refresh_cache', 10, 4 );
