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
 * Handle updating location posts
 *
 * @param array $where Array with where query.
 * @param array $data saved location data.
 * @param array $post POST data.
 *
 * @return int post ID
 */
function mc_update_location_post( $where, $data, $post ) {
	// if the location save was successful.
	$location_id = $where['location_id'];
	$post_id     = mc_get_location_post( $location_id, false );
	// If, after all that, the post doesn't exist, create it.
	if ( ! get_post_status( $post_id ) ) {
		$post['update'] = 'true';
		$post_id        = mc_create_location_post( $location_id, $data, $post );
	}
	$title       = $data['location_label'];
	$post_status = 'publish';
	$auth        = get_current_user_id();
	$type        = 'mc-locations';
	$my_post     = array(
		'ID'          => $post_id,
		'post_title'  => $title,
		'post_status' => $post_status,
		'post_author' => $auth,
		'post_name'   => sanitize_title( $title ),
		'post_type'   => $type,
	);
	if ( mc_switch_sites() && defined( BLOG_ID_CURRENT_SITE ) ) {
		switch_to_blog( BLOG_ID_CURRENT_SITE );
	}
	$post_id = wp_update_post( $my_post );

	/**
	 * Executed an action when a location post is updated.
	 *
	 * @hook mc_update_location_posts
	 *
	 * @param {int}   $post_id Post ID.
	 * @param {array} $post POST Array of data sent to create post.
	 * @param {array} $data Data for this location.
	 * @param {int}   $location_id Location ID.
	 */
	$post_id = apply_filters( 'mc_update_location_post', $post_id, $_POST, $data, $location_id );
	if ( mc_switch_sites() ) {
		restore_current_blog();
	}

	return $post_id;
}
add_filter( 'mc_modify_location', 'mc_update_location_post', 10, 3 );

/**
 * Create a post for My Calendar location data on save
 *
 * @param bool|int $location_id Result of save action; location ID or false.
 * @param array    $data Saved location data.
 * @param array    $post POST data.
 *
 * @return int|false newly created post ID or false if error.
 */
function mc_create_location_post( $location_id, $data, $post = array() ) {
	if ( ! $location_id ) {
		return false;
	}
	$post_id = mc_get_location_post( $location_id, false );
	// If not post ID or the post ID has no status.
	if ( ! $post_id || ! get_post_status( $post_id ) ) {
		$title       = $data['location_label'];
		$post_status = 'publish';
		$auth        = get_current_user_id();
		$type        = 'mc-locations';
		$my_post     = array(
			'post_title'  => $title,
			'post_status' => $post_status,
			'post_author' => $auth,
			'post_name'   => sanitize_title( $title ),
			'post_date'   => current_time( 'Y-m-d H:i:s' ),
			'post_type'   => $type,
		);
		$post_id     = wp_insert_post( $my_post );
		if ( isset( $post['update'] ) ) {
			mc_update_location_post_relationship( $location_id, $post_id );
		} else {
			mc_transition_location( $location_id, $post_id );
		}

		/**
		 * Executed an action when a location post is created.
		 *
		 * @hook mc_create_location_post
		 *
		 * @param {int}   $post_id Post ID.
		 * @param {array} $post POST Array of data sent to create post.
		 * @param {array} $data Data for this location.
		 * @param {int}   $location_id Location ID.
		 */
		do_action( 'mc_create_location_post', $post_id, $post, $data, $location_id );
		wp_publish_post( $post_id );
	}

	return $post_id;
}
add_filter( 'mc_save_location', 'mc_create_location_post', 10, 3 );

/**
 * Update custom fields for a location.
 *
 * @param int   $post_id Post ID associated with location.
 * @param array $post POST data.
 * @param array $data Saved location data.
 * @param int   $location_id Location ID in table.
 *
 * @return array Errors.
 */
function mc_update_location_custom_fields( $post_id, $post, $data, $location_id ) {
	// Set featured image.
	$attachment_id = ( isset( $post['location_image_id'] ) && is_numeric( $post['location_image_id'] ) ) ? $post['location_image_id'] : false;
	$attachment_id = ( isset( $data['location_image_id'] ) && is_numeric( $data['location_image_id'] ) ) ? $data['location_image_id'] : $attachment_id;
	if ( $attachment_id ) {
		set_post_thumbnail( $post_id, $attachment_id );
	}

	$fields       = mc_location_fields();
	$field_errors = array();
	foreach ( $fields as $name => $field ) {
		if ( isset( $post[ $name ] ) ) {
			if ( ! isset( $field['sanitize_callback'] ) || ( isset( $field['sanitize_callback'] ) && ! function_exists( $field['sanitize_callback'] ) ) ) {
				// if no sanitization is provided, we'll prep it for SQL and strip tags.
				$sanitized = sanitize_text_field( strip_tags( urldecode( $post[ $name ] ) ) );
			} else {
				$sanitized = call_user_func( $field['sanitize_callback'], urldecode( $post[ $name ] ) );
			}
			$success = update_post_meta( $post_id, $name, $sanitized );
			if ( ! $success ) {
				$field_errors[] = $name;
			}
		}
	}

	return $field_errors;
}
add_action( 'mc_update_location_post', 'mc_update_location_custom_fields', 10, 4 );
add_action( 'mc_create_location_post', 'mc_update_location_custom_fields', 10, 4 );

/**
 * Delete custom post type associated with event
 *
 * @param int $result   Result of delete action.
 * @param int $location_id Location ID.
 */
function mc_location_delete_post( $result, $location_id ) {
	global $wpdb;
	$post = mc_get_location_post( $location_id, false );
	if ( $post ) {
		wp_delete_post( $post, true );
		// Delete post relationship.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . my_calendar_location_relationships_table() . ' 	WHERE post_id = %d', $post ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		/**
		 * Executed an action when a location's post is deleted.
		 *
		 * @hook mc_delete_location_posts
		 *
		 * @param {int} $location_id Location deleted.
		 * @param {int} $post Post ID.
		 */
		do_action( 'mc_delete_location_posts', $location_id, $post );
	}
}
add_action( 'mc_delete_location', 'mc_location_delete_post', 10, 2 );

/**
 * Get the location post for a location.
 *
 * @param int  $location_id Location ID.
 * @param bool $type True for full post object.
 *
 * @return object|int|false WP_Post, post ID, or false if not found.
 */
function mc_get_location_post( $location_id, $type = true ) {
	$mcdb     = mc_is_remote_db();
	$post_ids = $mcdb->get_results( $mcdb->prepare( 'SELECT post_id FROM ' . my_calendar_location_relationships_table() . ' WHERE location_id = %d', $location_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	// If there are multiple records for this post, delete extras.
	$post_id = false;
	foreach ( $post_ids as $rid ) {
		$id = $rid->post_id;
		if ( ! 'mc-locations' === get_post_type( $id ) ) {
			$mcdb->query( $mcdb->prepare( 'DELETE FROM ' . my_calendar_location_relationships_table() . ' WHERE post_id = %d', $id ) );
		} else {
			$post_id = $id;
			break;
		}
	}
	if ( ! $post_id ) {
		// Copy location into relationships table.
		$post_id = false;
		$query   = $mcdb->prepare( "SELECT post_id FROM $mcdb->postmeta where meta_key ='_mc_location_id' and meta_value = %d", $location_id );
		$posts   = $mcdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( isset( $posts[0] ) ) {
			$post_id = $posts[0];
		}
		mc_transition_location( $location_id, $post_id );
	}

	return ( $type ) ? get_post( $post_id ) : $post_id;
}

/**
 * Get location ID from post.
 *
 * @param int $post_ID Post ID.
 *
 * @return int
 */
function mc_get_location_id( $post_ID ) {
	$mcdb        = mc_is_remote_db();
	$location_id = $mcdb->get_var( $mcdb->prepare( 'SELECT location_id FROM ' . my_calendar_location_relationships_table() . ' WHERE post_id = %d', $post_ID ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( ! $location_id ) {
		// Migrate from previous method of storing location IDs.
		$location_id = get_post_meta( $post_ID, '_mc_location_id', true );
	}

	return $location_id;
}

/**
 * Update a single field in a location.
 *
 * @param string    $field field name.
 * @param int|float $data data to update to.
 * @param int       $location location ID.
 *
 * @return mixed boolean/int query result
 */
function mc_update_location( $field, $data, $location ) {
	global $wpdb;
	$field = sanitize_key( $field );
	if ( 'location_latitude' === $field || 'location_longitude' === $field ) {
		$result = $wpdb->query( $wpdb->prepare( 'UPDATE ' . my_calendar_locations_table() . " SET $field = %f WHERE location_id=%d", $data, $location ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	} else {
		$result = $wpdb->query( $wpdb->prepare( 'UPDATE ' . my_calendar_locations_table() . " SET $field = %d WHERE location_id=%d", $data, $location ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	}

	return $result;
}

/**
 * Update a location relationship value.
 *
 * @param int $location_id Location ID from location table.
 * @param int $location_post Post ID from posts table.
 *
 * @since 3.3.0
 */
function mc_update_location_post_relationship( $location_id, $location_post ) {
	global $wpdb;
	$location_relationship = $wpdb->get_var( $wpdb->prepare( 'SELECT relationship_id FROM ' . my_calendar_location_relationships_table() . ' WHERE location_id = %d', $location_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$where                 = array( 'relationship_id' => (int) $location_relationship );
	if ( $location_relationship ) {
		$update = $wpdb->update(
			my_calendar_location_relationships_table(),
			array(
				'post_id' => $location_post,
			),
			$where,
			array( '%d' ),
			'%d'
		);
	} else {
		$update = $wpdb->insert(
			my_calendar_location_relationships_table(),
			array(
				'location_id'     => $location_id,
				'post_id'         => $location_post,
				'relationship_id' => $location_relationship,
			),
			array( '%d', '%d', '%d' )
		);
	}

	return $update;
}

/**
 * Insert a new location.
 *
 * @param array $add Array of location details to add.
 *
 * @return boolean|int New location ID or false.
 */
function mc_insert_location( $add ) {
	global $wpdb;
	$add     = array_map( 'mc_kses_post', $add );
	$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%s', '%s', '%s' );
	$results = $wpdb->insert( my_calendar_locations_table(), $add, $formats );
	if ( $results ) {
		$insert_id = $wpdb->insert_id;
	} else {
		$insert_id = false;
	}

	return $insert_id;
}

/**
 * Get count of locations.
 *
 * @return int
 */
function mc_count_locations() {
	global $wpdb;
	$count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . my_calendar_locations_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

	return $count;
}

/**
 * Update a location.
 *
 * @param array $update Array of location details to modify.
 * @param array $where [location_id => int] Location ID to update.
 *
 * @return mixed boolean/int query result.
 */
function mc_modify_location( $update, $where ) {
	global $wpdb;
	$update  = array_map( 'mc_kses_post', $update );
	$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%s', '%s', '%s' );
	$results = $wpdb->update( my_calendar_locations_table(), $update, $where, $formats, '%d' );

	delete_transient( 'mc_location_' . absint( $where['location_id'] ) );

	return $results;
}

/**
 * Delete a single location.
 *
 * @param int    $location Location ID.
 * @param string $type Return type.
 *
 * @return string
 */
function mc_delete_location( $location, $type = 'string' ) {
	global $wpdb;
	$location = (int) ( ( isset( $_GET['location_id'] ) ) ? $_GET['location_id'] : $location );
	$results  = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . my_calendar_locations_table() . ' WHERE location_id=%d', $location ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	/**
	 * Executed an action when a location is deleted.
	 *
	 * @hook mc_delete_location
	 *
	 * @param {int|false} $results Result of database deletion. False if error; number of rows affected if successful.
	 * @param {int} $location Location ID.
	 */
	do_action( 'mc_delete_location', $results, $location );
	if ( $results ) {
		$value            = true;
		$return           = mc_show_notice( __( 'Location deleted successfully', 'my-calendar' ), false );
		$default_location = mc_get_option( 'default_location', false );
		if ( (int) $default_location === $location ) {
			mc_update_option( 'default_location', '' );
		}
	} else {
		$value  = false;
		$return = mc_show_error( __( 'Location could not be deleted', 'my-calendar' ), false );
	}
	delete_transient( 'mc_location_' . $location );

	return ( 'string' === $type ) ? $return : $value;
}

/**
 * Handle results of form submit & display form.
 */
function my_calendar_add_locations() {
	?>
	<div class="wrap my-calendar-admin">
	<?php
	my_calendar_check_db();
	// We do some checking to see what we're doing.
	mc_mass_delete_locations();
	if ( ! empty( $_POST ) && ( ! isset( $_POST['mc_locations'] ) && ! isset( $_POST['mass_delete'] ) ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'My Calendar: Security check failed' );
		}
	}
	$post = map_deep( $_POST, 'wp_kses_post' );

	if ( isset( $post['mode'] ) && 'add' === $post['mode'] ) {
		$add = array(
			'location_label'     => $post['location_label'],
			'location_street'    => $post['location_street'],
			'location_street2'   => $post['location_street2'],
			'location_city'      => $post['location_city'],
			'location_state'     => $post['location_state'],
			'location_postcode'  => $post['location_postcode'],
			'location_region'    => $post['location_region'],
			'location_country'   => $post['location_country'],
			'location_url'       => $post['location_url'],
			'location_longitude' => $post['location_longitude'],
			'location_latitude'  => $post['location_latitude'],
			'location_zoom'      => $post['location_zoom'],
			'location_phone'     => $post['location_phone'],
			'location_phone2'    => $post['location_phone2'],
			'location_access'    => isset( $post['location_access'] ) ? serialize( $post['location_access'] ) : '',
		);

		$location_id = mc_insert_location( $add );
		// If this is being generated from an event/location merge, update the event to the new location ID.
		if ( isset( $_GET['event_source'] ) ) {
			$source = absint( $_GET['event_source'] );
			mc_update_data( $source, 'event_location', $location_id );
		}
		if ( isset( $post['mc_default_location'] ) ) {
			mc_update_option( 'default_location', (int) $location_id );
		}
		/**
		 * Execute an action when a location is saved.
		 *
		 * @hook mc_save_location
		 *
		 * @param {int|false} $results Result of database insertion. Row ID or false.
		 * @param {array} $add Array of location parameters to add.
		 * @param {array} $post POST array.
		 */
		$results = apply_filters( 'mc_save_location', $location_id, $add, $post );
		if ( $results ) {
			$args     = array(
				'mode'        => 'edit',
				'location_id' => $location_id,
			);
			$edit_url = add_query_arg( $args, admin_url( 'admin.php?page=my-calendar-locations' ) );
			// Translators: Link to edit new location.
			mc_show_notice( sprintf( __( 'Location added successfully. <a href="%s">Edit location.</a>', 'my-calendar' ), $edit_url ) );
		} else {
			mc_show_error( __( 'Location could not be added to database', 'my-calendar' ) );
		}
	} elseif ( isset( $_GET['location_id'] ) && 'delete' === $_GET['mode'] ) {
		$loc = absint( $_GET['location_id'] );
		echo mc_delete_location( $loc );
	} elseif ( isset( $_GET['mode'] ) && isset( $_GET['location_id'] ) && 'edit' === $_GET['mode'] && ! isset( $post['mode'] ) ) {
		$cur_loc = (int) $_GET['location_id'];
		mc_show_location_form( 'edit', $cur_loc );
	} elseif ( isset( $post['location_id'] ) && isset( $post['location_label'] ) && 'edit' === $post['mode'] ) {
		$update = array(
			'location_label'     => $post['location_label'],
			'location_street'    => $post['location_street'],
			'location_street2'   => $post['location_street2'],
			'location_city'      => $post['location_city'],
			'location_state'     => $post['location_state'],
			'location_postcode'  => $post['location_postcode'],
			'location_region'    => $post['location_region'],
			'location_country'   => $post['location_country'],
			'location_url'       => $post['location_url'],
			'location_longitude' => $post['location_longitude'],
			'location_latitude'  => $post['location_latitude'],
			'location_zoom'      => $post['location_zoom'],
			'location_phone'     => $post['location_phone'],
			'location_phone2'    => $post['location_phone2'],
			'location_access'    => isset( $post['location_access'] ) ? serialize( $post['location_access'] ) : '',
		);

		$where = array( 'location_id' => (int) $post['location_id'] );
		if ( isset( $post['mc_default_location'] ) ) {
			mc_update_option( 'default_location', (int) $post['location_id'] );
		}
		$default_location = mc_get_option( 'default_location' );
		if ( (int) $post['location_id'] === (int) $default_location && ! isset( $post['mc_default_location'] ) ) {
			mc_update_option( 'default_location', '' );
		}
		$results = mc_modify_location( $update, $where );

		/**
		 * Executed an action when a location is modified.
		 *
		 * @hook mc_modify_location
		 *
		 * @param {array} $where Array [location_id => $id].
		 * @param {array} $update Array of location parameters to update.
		 * @param {array} $post POST array.
		 */
		$results = apply_filters( 'mc_modify_location', $where, $update, $_POST );
		if ( false === $results ) {
			mc_show_error( __( 'Location could not be edited.', 'my-calendar' ) );
		} elseif ( 0 === $results ) {
			mc_show_error( __( 'Location was not changed.', 'my-calendar' ) );
		} else {
			mc_show_notice( __( 'Location edited successfully', 'my-calendar' ) );
		}
		$cur_loc = (int) $_POST['location_id'];
		mc_show_location_form( 'edit', $cur_loc );
	}

	if ( isset( $_GET['mode'] ) && 'edit' !== $_GET['mode'] || isset( $_POST['mode'] ) && 'edit' !== $_POST['mode'] || ! isset( $_GET['mode'] ) && ! isset( $_POST['mode'] ) ) {
		mc_show_location_form( 'add' );
	}
}

/**
 * Create location editing form.
 *
 * @param string    $view type of view add/edit.
 * @param int|false $loc_id Location ID or false for new locations.
 */
function mc_show_location_form( $view = 'add', $loc_id = false ) {
	$cur_loc = false;
	if ( $loc_id ) {
		$update_location = false;
		if ( isset( $_POST['update_gps'] ) ) {
			$update_location = 'force';
		}
		$cur_loc = mc_get_location( $loc_id, $update_location );
	}
	// If creating from event, get data from event & location and merge.
	if ( isset( $_GET['event_source'] ) ) {
		$source   = absint( $_GET['event_source'] );
		$event    = mc_get_event_core( $source );
		$location = mc_get_location( $event->event_location );
		$diff     = mc_event_location_diff( $event );
		foreach ( $diff as $key => $val ) {
			$location->$key = $val[1];
		}
		$cur_loc = $location;
	}
	// If updating from event, merge event data into this location.
	if ( isset( $_GET['merge_source'] ) ) {
		$source = absint( $_GET['merge_source'] );
		$event  = mc_get_event_core( $source );
		$diff   = mc_event_location_diff( $event );
		foreach ( $diff as $key => $val ) {
			$cur_loc->$key = $val[1];
		}
	}
	$has_data = ( is_object( $cur_loc ) ) ? true : false;
	if ( 'add' === $view ) {
		?>
		<h1><?php esc_html_e( 'Add New Location', 'my-calendar' ); ?></h1>
		<?php
	} else {
		?>
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Edit Location', 'my-calendar' ); ?></h1>
		<a href="<?php echo admin_url( 'admin.php?page=my-calendar-locations' ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'my-calendar' ); ?></a>
		<hr class="wp-header-end">
		<?php
	}
	?>
	<div class="postbox-container jcd-wide">
		<div class="metabox-holder">
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2><?php esc_html_e( 'Location Editor', 'my-calendar' ); ?></h2>

					<div class="inside location_form">
					<?php
					$params = array();
					if ( isset( $_GET['location_id'] ) ) {
						$params = array(
							'mode'        => sanitize_text_field( $_GET['mode'] ),
							'location_id' => absint( $_GET['location_id'] ),
						);
						if ( isset( $_GET['merge_source'] ) ) {
							$params['merge_source'] = absint( $_GET['merge_source'] );
						}
					}
					if ( isset( $_GET['event_source'] ) ) {
						$params['event_source'] = absint( $_GET['event_source'] );
					}
					?>
					<form id="my-calendar" method="post" action="<?php echo esc_url( add_query_arg( $params, admin_url( 'admin.php?page=my-calendar-locations' ) ) ); ?>">
						<div class="mc-controls">
							<ul>
								<?php
								if ( 'edit' === $view ) {
									$delete_url = add_query_arg( 'location_id', $loc_id, admin_url( 'admin.php?page=my-calendar-location-manager&mode=delete' ) );
									$view_url   = get_the_permalink( mc_get_location_post( $loc_id, false ) );
									?>
								<li><span class="dashicons dashicons-no" aria-hidden="true"></span><a class="delete" href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'my-calendar' ); ?></a></li>
									<?php
									if ( $view_url && esc_url( $view_url ) ) {
										?>
								<li><span class="dashicons dashicons-laptop" aria-hidden="true"></span><a class="view" href="<?php echo esc_url( $view_url ); ?>"><?php esc_html_e( 'View', 'my-calendar' ); ?></a></li>
										<?php
									}
								}
								?>
								<li><input type="submit" name="save" class="button-primary" value="<?php echo esc_attr( ( 'edit' === $view ) ? __( 'Save Changes', 'my-calendar' ) : __( 'Add Location', 'my-calendar' ) ); ?> "/></li>
							</ul>
						</div>
						<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/></div>
							<?php
							if ( 'add' === $view ) {
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
							<div class="mc-controls footer">
								<ul>
									<?php
									if ( 'edit' === $view ) {
										$delete_url = add_query_arg( 'location_id', $loc_id, admin_url( 'admin.php?page=my-calendar-location-manager&mode=delete' ) );
										$view_url   = get_the_permalink( mc_get_location_post( $loc_id, false ) );
										?>
									<li><span class="dashicons dashicons-no" aria-hidden="true"></span><a class="delete" href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'my-calendar' ); ?></a></li>
										<?php
										if ( $view_url && esc_url( $view_url ) ) {
											?>
								<li><span class="dashicons dashicons-laptop" aria-hidden="true"></span><a class="view" href="<?php echo esc_url( $view_url ); ?>"><?php esc_html_e( 'View', 'my-calendar' ); ?></a></li>
											<?php
										}
									}
									?>
									<li><input type="submit" name="save" class="button-primary" value="<?php echo esc_attr( ( 'edit' === $view ) ? __( 'Save Changes', 'my-calendar' ) : __( 'Add Location', 'my-calendar' ) ); ?> "/></li>
								</ul>
							</div>
						</form>
					</div>
				</div>
			</div>
			<?php
			if ( 'edit' === $view ) {
				?>
				<p>
					<a href="<?php echo admin_url( 'admin.php?page=my-calendar-locations' ); ?>"><?php esc_html_e( 'Add a New Location', 'my-calendar' ); ?></a>
				</p>
				<?php
			}
			?>
		</div>
	</div>
		<?php
		mc_show_sidebar( '' );
		?>
	</div>
	<?php
}

/**
 * Get details about one location.
 *
 * @param int         $location_id Location ID.
 * @param bool|string $update_location Whether to update location on fetch. 'Force' to force update.
 *
 * @return object|false location if found
 */
function mc_get_location( $location_id, $update_location = true ) {
	if ( ! is_admin() ) {
		$location = get_transient( 'mc_location_' . $location_id );
		if ( $location ) {
			return $location;
		}
	}
	$mcdb     = mc_is_remote_db();
	$location = $mcdb->get_row( $mcdb->prepare( 'SELECT * FROM ' . my_calendar_locations_table() . ' WHERE location_id = %d', $location_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( is_object( $location ) ) {
		$location->location_post = mc_get_location_post( $location_id, false );
		$prevent_geolocation     = ( '1' === get_post_meta( $location->location_post, '_mc_geolocate_error', true ) && 'force' !== $update_location ) ? true : false;
		if ( ! $prevent_geolocation ) {
			if ( $update_location ) {
				if ( mc_get_option( 'gmap_api_key' ) ) {
					if ( 'force' === $update_location ) {
						$latitude  = false;
						$longitude = false;
					} else {
						$latitude  = ( '0.000000' === (string) $location->location_latitude ) ? false : true;
						$longitude = ( '0.000000' === (string) $location->location_longitude ) ? false : true;
					}
					if ( ! $latitude || ! $longitude ) {
						$loc = mc_get_location_coordinates( $location_id );
						$lat = isset( $loc['latitude'] ) ? $loc['latitude'] : '';
						$lng = isset( $loc['longitude'] ) ? $loc['longitude'] : '';

						if ( $lat && $lng ) {
							mc_update_location( 'location_longitude', $lng, $location_id );
							mc_update_location( 'location_latitude', $lat, $location_id );
							$location->location_longitude = $lng;
							$location->location_latitude  = $lat;
						} else {
							update_post_meta( $location->location_post, '_mc_geolocate_error', '1' );
						}
					}
				}
			}
		}
	}
	if ( ! is_admin() ) {
		set_transient( 'mc_location_' . $location_id, $location, WEEK_IN_SECONDS );
	}

	return $location;
}

/**
 * Remove event data stored on an event.
 *
 * Helper to prevent unneeded data change notifications & make the transition to single storage easier.
 *
 * @param int $event_id Event ID.
 */
function mc_remove_location_from_event( $event_id ) {
	$fields = array( 'label', 'street', 'street2', 'city', 'state', 'postcode', 'region', 'country', 'url', 'phone', 'phone2', 'longitude', 'latitude' );
	foreach ( $fields as $field ) {
		$format = ( 'longitude' === $field || 'latitude' === $field ) ? '%f' : '%s';
		$value  = ( 'longitude' === $field || 'latitude' === $field ) ? 0 : '';
		mc_update_data( $event_id, 'event_' . $field, $value, $format );
	}
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
	$controls   = mc_get_option( 'location_controls' );
	if ( ! is_array( $controls ) || empty( $controls ) ) {
		return false;
	}
	$controlled = array_keys( $controls );
	if ( in_array( 'event_' . $this_field, $controlled, true ) && ! empty( $controls[ 'event_' . $this_field ] ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Geolocate latitude and longitude of location.
 *
 * @param int|false $location_id Location ID.
 * @param array     $address Array of address parameters.
 *
 * @return array
 */
function mc_get_location_coordinates( $location_id = false, $address = array() ) {
	require_once 'includes/class-geolocation.php';
	$street  = '';
	$street2 = '';
	$city    = '';
	$zip     = '';
	$country = '';

	new Geolocation();
	if ( $location_id ) {
		$location = mc_get_location( $location_id, false );
		$street   = $location->location_street;
		$street2  = $location->location_street2;
		$city     = $location->location_city;
		$zip      = $location->location_postcode;
		$country  = $location->location_country;
	} elseif ( ! empty( $address ) ) {
		$street  = ( isset( $address['street'] ) ) ? $address['street'] : '';
		$street2 = ( isset( $address['street2'] ) ) ? $address['street2'] : '';
		$city    = ( isset( $address['city'] ) ) ? $address['city'] : '';
		$zip     = ( isset( $address['zip'] ) ) ? $address['zip'] : '';
		$country = ( isset( $address['country'] ) ) ? $address['country'] : '';
	}

	$coordinates = Geolocation::get_coordinates( $street, $street2, $city, $zip, $country );

	return $coordinates;
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
	$field    = ( 'location' === $context ) ? 'location_' . $fieldname : 'event_' . $fieldname;
	$selected = trim( $selected );
	$options  = mc_get_option( 'location_controls' );
	$regions  = $options[ 'event_' . $fieldname ];
	$form     = "<select name='$field' id='e_$fieldname'>";
	$form    .= "<option value=''>" . __( 'Select', 'my-calendar' ) . '</option>';
	if ( is_admin() && '' !== $selected ) {
		$form .= "<option value='" . esc_attr( $selected ) . "'>" . esc_html( $selected ) . ' :' . __( '(Not a controlled value)', 'my-calendar' ) . '</option>';
	}
	foreach ( $regions as $key => $value ) {
		$key       = trim( $key );
		$value     = trim( $value );
		$aselected = ( $selected === $key ) ? ' selected="selected"' : '';
		$form     .= "<option value='" . esc_attr( $key ) . "'$aselected>" . esc_html( $value ) . "</option>\n";
	}
	$form .= '</select>';

	return $form;
}

/**
 * Produce the form to submit location data
 *
 * @param boolean   $has_data Whether currently have data.
 * @param object    $data event or location data.
 * @param string    $context whether currently in an event or a location context.
 * @param int|false $group_id Group ID if in group editing.
 *
 * @return string HTML form fields
 */
function mc_locations_fields( $has_data, $data, $context = 'location', $group_id = false ) {
	$return = '<div class="mc-locations" id="location-fields">';
	if ( current_user_can( 'mc_edit_settings' ) && isset( $_GET['page'] ) && 'my-calendar-locations' === $_GET['page'] ) {
		$checked = ( isset( $_GET['location_id'] ) && (int) mc_get_option( 'default_location' ) === (int) $_GET['location_id'] ) ? 'checked="checked"' : '';
		$return .= '<p class="checkbox">';
		$return .= '<input type="checkbox" name="mc_default_location" id="mc_default_location"' . $checked . ' /> <label for="mc_default_location">' . __( 'Default Location', 'my-calendar' ) . '</label>';
		$return .= '</p>';
	}
	$compare   = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_label', false ) : '';
	$return   .= '
	<p>
	<label for="e_label">' . __( 'Name of Location (required)', 'my-calendar' ) . $compare . '</label>';
	$cur_label = ( is_object( $data ) ) ? ( stripslashes( (string) $data->{$context . '_label'} ) ) : '';
	if ( mc_controlled_field( 'label' ) ) {
		$return .= mc_location_controller( 'label', $cur_label, $context );
	} else {
		$return .= '<input type="text" id="e_label" name="' . $context . '_label" value="' . esc_attr( $cur_label ) . '" />';
	}
	$compare1        = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_street', false ) : '';
	$compare2        = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_street2', false ) : '';
	$compare         = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_city', false ) : '';
	$street_address  = ( $has_data ) ? stripslashes( (string) $data->{$context . '_street'} ) : '';
	$street_address2 = ( $has_data ) ? stripslashes( (string) $data->{$context . '_street2'} ) : '';
	$return         .= '</p>';
	$image_field     = '';
	if ( 'location' === $context ) {
		$location_id = ( isset( $_GET['location_id'] ) ) ? (int) $_GET['location_id'] : false;
		$post_id     = ( $location_id ) ? mc_get_location_post( $location_id ) : false;
		$image_field = mc_location_featured_image_field( $post_id );
	}
	$return  .= $image_field;
	$return  .= '
	<div class="locations-container columns">
	<div class="location-primary">
	<fieldset>
	<legend>' . __( 'Location Address', 'my-calendar' ) . '</legend>
	<p>
		<label for="e_street">' . __( 'Street Address', 'my-calendar' ) . $compare1 . '</label> <input type="text" id="e_street" name="' . $context . '_street" value="' . esc_attr( $street_address ) . '" />
	</p>
	<p>
		<label for="e_street2">' . __( 'Street Address (2)', 'my-calendar' ) . $compare2 . '</label> <input type="text" id="e_street2" name="' . $context . '_street2" value="' . esc_attr( $street_address2 ) . '" />
	</p>
	<p>
		<label for="e_city">' . __( 'City', 'my-calendar' ) . $compare . '</label> ';
	$cur_city = ( is_object( $data ) ) ? ( stripslashes( (string) $data->{$context . '_city'} ) ) : '';
	if ( mc_controlled_field( 'city' ) ) {
		$return .= mc_location_controller( 'city', $cur_city, $context );
	} else {
		$return .= '<input type="text" id="e_city" name="' . $context . '_city" value="' . esc_attr( $cur_city ) . '" />';
	}
	$return .= '</p><p>';

	$compare   = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_state', false ) : '';
	$return   .= '<label for="e_state">' . __( 'State/Province', 'my-calendar' ) . $compare . '</label> ';
	$cur_state = ( is_object( $data ) ) ? ( stripslashes( (string) $data->{$context . '_state'} ) ) : '';
	if ( mc_controlled_field( 'state' ) ) {
		$return .= mc_location_controller( 'state', $cur_state, $context );
	} else {
		$return .= '<input type="text" id="e_state" name="' . $context . '_state" size="10" value="' . esc_attr( $cur_state ) . '" />';
	}
	$compare      = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_postcode', false ) : '';
	$return      .= '</p><p><label for="e_postcode">' . __( 'Postal Code', 'my-calendar' ) . $compare . '</label> ';
	$cur_postcode = ( is_object( $data ) ) ? ( stripslashes( (string) $data->{$context . '_postcode'} ) ) : '';
	if ( mc_controlled_field( 'postcode' ) ) {
		$return .= mc_location_controller( 'postcode', $cur_postcode, $context );
	} else {
		$return .= '<input type="text" id="e_postcode" name="' . $context . '_postcode" value="' . esc_attr( $cur_postcode ) . '" />';
	}
	$compare    = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_region', false ) : '';
	$return    .= '</p><p>';
	$return    .= '<label for="e_region">' . __( 'Region', 'my-calendar' ) . $compare . '</label> ';
	$cur_region = ( is_object( $data ) ) ? ( stripslashes( (string) $data->{$context . '_region'} ) ) : '';
	if ( mc_controlled_field( 'region' ) ) {
		$return .= mc_location_controller( 'region', $cur_region, $context );
	} else {
		$return .= '<input type="text" id="e_region" name="' . $context . '_region" value="' . esc_attr( $cur_region ) . '" />';
	}
	$compare     = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_country', false ) : '';
	$return     .= '</p><p><label for="e_country">' . __( 'Country', 'my-calendar' ) . $compare . '</label><div class="mc-autocomplete autocomplete" id="mc-countries-autocomplete"> ';
	$cur_country = ( $has_data ) ? ( stripslashes( (string) $data->{$context . '_country'} ) ) : '';
	if ( mc_controlled_field( 'country' ) ) {
		$return .= mc_location_controller( 'country', $cur_country, $context );
	} else {
		$return .= '<input type="text" class="autocomplete-input" id="e_country" name="' . $context . '_country" size="10" value="' . esc_attr( $cur_country ) . '" />';
	}
	$return .= '<ul class="autocomplete-result-list"></ul>
	</div>';

	$compare_zoom   = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_zoom', false ) : '';
	$compare_phone  = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_phone', false ) : '';
	$compare_phone2 = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_phone2', false ) : '';

	$compare_url  = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_url', false ) : '';
	$compare_lat  = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_latitude', false ) : '';
	$compare_lon  = ( $group_id ) ? mc_compare_group_members( $group_id, 'event_longitude', false ) : '';
	$zoom         = ( $has_data ) ? $data->{$context . '_zoom'} : '16';
	$event_phone  = ( $has_data ) ? stripslashes( (string) $data->{$context . '_phone'} ) : '';
	$event_phone2 = ( $has_data ) ? stripslashes( (string) $data->{$context . '_phone2'} ) : '';
	$event_url    = ( $has_data ) ? stripslashes( (string) $data->{$context . '_url'} ) : '';
	$event_lat    = ( $has_data ) ? stripslashes( (string) $data->{$context . '_latitude'} ) : '';
	$event_lon    = ( $has_data ) ? stripslashes( (string) $data->{$context . '_longitude'} ) : '';
	$update_gps   = ( $has_data && mc_get_option( 'gmap_api_key', '' ) && 'location' === $context ) ? '<p class="checkboxes"><input type="checkbox" value="1" id="update_gps" name="update_gps" /> <label for="update_gps">' . __( 'Update GPS Coordinates', 'my-calendar' ) . '</label></p>' : '';
	$return      .= '</p>
	<p>
	<label for="e_zoom">' . __( 'Initial Zoom', 'my-calendar' ) . $compare_zoom . '</label>
		<select name="' . $context . '_zoom" id="e_zoom">
			<option value="16"' . selected( $zoom, '16', false ) . '>' . __( 'Neighborhood', 'my-calendar' ) . '</option>
			<option value="14"' . selected( $zoom, '14', false ) . '>' . __( 'Small City', 'my-calendar' ) . '</option>
			<option value="12"' . selected( $zoom, '12', false ) . '>' . __( 'Large City', 'my-calendar' ) . '</option>
			<option value="10"' . selected( $zoom, '10', false ) . '>' . __( 'Greater Metro Area', 'my-calendar' ) . '</option>
			<option value="8"' . selected( $zoom, '8', false ) . '>' . __( 'State', 'my-calendar' ) . '</option>
			<option value="6"' . selected( $zoom, '6', false ) . '>' . __( 'Region', 'my-calendar' ) . '</option>
		</select>
	</p>
	</fieldset>
	<fieldset>
	<legend>' . __( 'GPS Coordinates (optional)', 'my-calendar' ) . '</legend>
	<p>
	' . __( 'Coordinates are used for placing the pin on your map when available.', 'my-calendar' ) . '
	</p>
	<div class="columns-flex">
	<p>
		<label for="e_latitude">' . __( 'Latitude', 'my-calendar' ) . $compare_lat . '</label> <input type="text" id="e_latitude" name="' . $context . '_latitude" size="10" value="' . esc_attr( $event_lat ) . '" />
	</p>
	<p>
		<label for="e_longitude">' . __( 'Longitude', 'my-calendar' ) . $compare_lon . '</label> <input type="text" id="e_longitude" name="' . $context . '_longitude" size="10" value="' . esc_attr( $event_lon ) . '" />
	</p>' . $update_gps . '
	</div>
	</fieldset>';
	/**
	 * Append content in the primary column of location fields.
	 *
	 * @hook mc_location_container_primary
	 *
	 * @param {string} HTML content. Default empty string.
	 * @param {object} $data Current display object.
	 * @param {string} $context Location or event. Tells us the structure of the $data object.
	 *
	 * @return {string}
	 */
	$return .= apply_filters( 'mc_location_container_primary', '', $data, $context );
	$return .= '
	</div>
	<div class="location-secondary">
	<fieldset>
	<legend>' . __( 'Location Contact Information', 'my-calendar' ) . '</legend>
	<p>
	<label for="e_phone">' . __( 'Phone', 'my-calendar' ) . $compare_phone . '</label> <input type="text" id="e_phone" name="' . $context . '_phone" value="' . esc_attr( $event_phone ) . '" />
	</p>
	<p>
	<label for="e_phone2">' . __( 'Secondary Phone', 'my-calendar' ) . $compare_phone2 . '</label> <input type="text" id="e_phone2" name="' . $context . '_phone2" value="' . esc_attr( $event_phone2 ) . '" />
	</p>
	<p>
	<label for="e_url">' . __( 'Location URL', 'my-calendar' ) . $compare_url . '</label> <input type="text" id="e_url" name="' . $context . '_url" value="' . esc_attr( $event_url ) . '" />
	</p>
	</fieldset>
	<fieldset>
	<legend>' . __( 'Location Accessibility', 'my-calendar' ) . '</legend>
	<ul class="accessibility-features checkboxes">';

	/**
	 * Filter venue accessibility array.
	 *
	 * @hook mc_venue_accessibility
	 *
	 * @param {array} $access Access parameters.
	 * @param {object} $data Current data object.
	 *
	 * @return {array}
	 */
	$access      = apply_filters( 'mc_venue_accessibility', mc_location_access(), $data );
	$access_list = '';
	if ( $has_data ) {
		if ( 'location' === $context ) {
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
			$checked = ( in_array( $a, $location_access, true ) || in_array( $k, $location_access, true ) ) ? " checked='checked'" : '';
		}
		$item         = sprintf( '<li><input type="checkbox" id="%1$s" name="' . $context . '_access[]" value="%4$s" class="checkbox" %2$s /> <label for="%1$s">%3$s</label></li>', esc_attr( $id ), $checked, esc_html( $label ), esc_attr( $a ) );
		$access_list .= $item;
	}
	$return .= $access_list;
	$return .= '</ul>
	</fieldset>';
	$fields  = mc_display_location_fields( mc_location_fields(), $data, $context );
	$return .= ( '' !== $fields ) ? '<div class="mc-custom-fields mc-locations"><fieldset><legend>' . __( 'Custom Fields', 'my-calendar' ) . '</legend>' . $fields . '</fieldset></div>' : '';
	/**
	 * Append content in the secondary column of location fields.
	 *
	 * @hook mc_location_container_secondary
	 *
	 * @param {string} HTML content. Default empty string.
	 * @param {object} $data Current display object.
	 * @param {string} $context Location or event. Tells us the structure of the $data object.
	 *
	 * @return {string}
	 */
	$return .= apply_filters( 'mc_location_container_secondary', '', $data, $context );
	$return .= '</div>
	</div>
	</div>';

	$api_key  = mc_get_option( 'gmap_api_key' );
	$location = ( $has_data && 'event' === $context ) ? $data->event_location : false;
	if ( $api_key && ! ( 'event' === $context && false === (bool) $location ) ) {
		$return .= '<h3>' . __( 'Location Map', 'my-calendar' ) . '</h3>';
		$map     = mc_generate_map( $data, $context );

		$return .= ( '' === $map ) ? __( 'Not enough information to generate a map', 'my-calendar' ) : $map;
	} else {
		if ( ! $api_key ) {
			// Translators: URL to settings page to add key.
			$return .= sprintf( __( 'Add a <a href="%s">Google Maps API Key</a> to generate a location map.', 'my-calendar' ), admin_url( 'admin.php?page=my-calendar-config#mc-output' ) );
		}
	}

	return $return;
}

/**
 * Return a set of location fields.
 *
 * @return array
 */
function mc_location_fields() {
	$fields['maptype'] = array(
		'title'        => __( 'Map display type', 'my-calendar' ),
		'input_type'   => 'select',
		'input_values' => array( 'Default', 'Roadmap', 'Satellite', 'Hybrid', 'Terrain' ),
	);
	/**
	 * Return custom fields for use in My Calendar locations. Fields should format like:
	 *
	 * ```$fields['location_type']   = array(
	 *    'title'             => 'Location Type',
	 *    'sanitize_callback' => 'sanitize_text_field',
	 *    'display_callback'  => 'esc_html',
	 *    'input_type'        => 'select',
	 *    'input_values'      => array( 'Virtual', 'Private Home', 'Concert Hall', 'Outdoor Venue' ),
	 * );```
	 *
	 * @hook mc_location_fields
	 *
	 * @param {array} Array of custom fields.
	 *
	 * @return {array}
	 */
	$fields = apply_filters( 'mc_location_fields', $fields );

	return $fields;
}

/**
 * Get custom data for a location.
 *
 * @param int|false    $location_id Location ID.
 * @param int|false    $location_post Location Post ID.
 * @param string|false $field Custom field name.
 *
 * @return mixed
 */
function mc_location_custom_data( $location_id = false, $location_post = false, $field = false ) {
	if ( ! $field ) {
		return;
	}
	$fields = mc_location_fields();
	if ( $field && ! in_array( $field, array_keys( $fields ), true ) ) {
		return '';
	}
	$location_id = ( isset( $_GET['location_id'] ) ) ? (int) $_GET['location_id'] : $location_id;
	$value       = '';
	// Quick exit when location post is known.
	if ( $location_post ) {
		return get_post_meta( $location_post, $field, true );
	}
	if ( ! $location_id ) {
		$location_id = ( isset( $_POST['location_id'] ) ) ? (int) $_POST['location_id'] : false;
	}
	if ( $location_id ) {
		$post_id = mc_get_location_post( $location_id, false );
		$value   = get_post_meta( $post_id, $field, true );
	}

	return $value;
}

/**
 * Add custom fields to event data output.
 *
 * @param array  $e Event tag data.
 * @param object $event Event object.
 *
 * @return array
 */
function mc_template_location_fields( $e, $event ) {
	$fields = mc_location_fields();
	foreach ( $fields as $name => $field ) {
		$location_post = false;
		if ( is_object( $event ) && property_exists( $event, 'location' ) ) {
			if ( is_object( $event->location ) && property_exists( $event->location, 'location_post' ) ) {
				$location_post = $event->location->location_post;
			} else {
				// If location data has no backing post, it cannot have custom fields.
				return $e;
			}
		}
		$value = mc_location_custom_data( $event->event_location, $location_post, $name );
		if ( ! isset( $field['display_callback'] ) || ( isset( $field['display_callback'] ) && ! function_exists( $field['display_callback'] ) ) ) {
			// if no display callback is provided.
			$display = stripslashes( $value );
		} else {
			$display = call_user_func( $field['display_callback'], $value, $field );
		}
		$key       = 'location_' . $name;
		$e[ $key ] = $display;
	}

	return $e;
}
add_filter( 'mc_filter_shortcodes', 'mc_template_location_fields', 10, 2 );

/**
 * Output location featured image field.
 *
 * @param int $post_id Post ID for the currently edited post.
 *
 * @return string
 */
function mc_location_featured_image_field( $post_id ) {
	$image       = ( has_post_thumbnail( $post_id ) ) ? get_the_post_thumbnail_url( $post_id, array( '150', '150' ) ) : '';
	$image_id    = ( has_post_thumbnail( $post_id ) ) ? get_post_thumbnail_id( $post_id ) : '';
	$button_text = __( 'Select Featured Image', 'my-calendar' );
	$remove      = '';
	$alt         = '';
	if ( '' !== $image ) {
		$alt         = ( $image_id ) ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';
		$button_text = __( 'Change Featured Image', 'my-calendar' );
		$remove      = '<button type="button" data-context="location" class="button remove-image" aria-describedby="event_image">' . esc_html__( 'Remove Featured Image', 'my-calendar' ) . '</button>';
		$alt         = ( '' === $alt ) ? get_post_meta( $post_id, '_mcs_submitted_alt', true ) : $alt;
		$alt         = ( '' === $alt ) ? $image : $alt;
	}
	$return = '
	<div class="mc-image-upload field-holder">
		<div class="image_fields">
			<input type="hidden" name="location_image_id" value="' . esc_attr( $image_id ) . '" class="textfield" id="l_image_id" /> <input type="hidden" name="location_image" id="l_image" value="' . esc_attr( $image ) . '" /><button type="button" data-context="location" class="button select-image" aria-describedby="location_image">' . $button_text . '</button> ' . $remove . '
		</div>';
	if ( '' !== $image ) {
		$return .= '<div class="event_image" aria-live="assertive"><img id="location_image" src="' . esc_attr( $image ) . '" alt="' . __( 'Current image: ', 'my-calendar' ) . esc_attr( $alt ) . '" /></div>';
	} else {
		$return .= '<div class="event_image" id="event_image"></div>';
	}
	$return .= '</div>';

	return $return;
}

/**
 * Expand custom fields from array to field output
 *
 * @param array  $fields Array of field data.
 * @param object $data Location data.
 * @param string $context Location or event.
 *
 * @return string
 */
function mc_display_location_fields( $fields, $data, $context ) {
	if ( empty( $fields ) ) {
		return '';
	}
	$output      = '';
	$return      = '';
	$location_id = false;
	if ( is_object( $data ) && 'event' === $context ) {
		$location_id = $data->event_location;
	}
	if ( is_object( $data ) && 'location' === $context ) {
		$location_id = $data->location_id;
	}

	/**
	 * Filter available custom fields & set display order.
	 *
	 * @hook mc_order_location_fields
	 *
	 * @param {array} $fields Array of custom fields data.
	 * @param {string} $context Whether we're currently editing a location or an event.
	 *
	 * @return {array}
	 */
	$custom_fields = apply_filters( 'mc_order_location_fields', $fields, $context );
	foreach ( $custom_fields as $name => $field ) {
		$user_value = mc_location_custom_data( $location_id, false, $name );
		$required   = isset( $field['required'] ) ? ' required' : '';
		$req_label  = isset( $field['required'] ) ? ' <span class="required">' . __( 'Required', 'my-calendar' ) . '</span>' : '';
		switch ( $field['input_type'] ) {
			case 'text':
			case 'number':
			case 'email':
			case 'url':
			case 'date':
			case 'tel':
				$output = "<input type='" . $field['input_type'] . "' name='$name' id='$name' value='$user_value'$required />";
				break;
			case 'hidden':
				$output = "<input type='hidden' name='$name' value='$user_value' />";
				break;
			case 'textarea':
				$output = "<textarea rows='6' cols='60' name='$name' id='$name'$required>$user_value</textarea>";
				break;
			case 'select':
				if ( isset( $field['input_values'] ) ) {
					$output = "<select name='$name' id='$name'$required>";
					foreach ( $field['input_values'] as $value ) {
						$value = stripslashes( $value );
						if ( $value === $user_value ) {
							$selected = " selected='selected'";
						} else {
							$selected = '';
						}
						$output .= "<option value='" . esc_attr( stripslashes( $value ) ) . "'$selected>" . esc_html( stripslashes( $value ) ) . "</option>\n";
					}
					$output .= '</select>';
				}
				break;
			case 'checkbox':
			case 'radio':
				if ( isset( $field['input_values'] ) ) {
					$value = $field['input_values'];
					if ( (string) $value === (string) $user_value ) {
						$checked = ' checked="checked"';
					} else {
						$checked = '';
					}
					$output = "<input type='" . $field['input_type'] . "' name='$name' id='$name' value='" . esc_attr( stripslashes( $value ) ) . "'$checked $required />";
				}
				break;
			default:
				$output = "<input type='text' name='$name' id='$name' value='$user_value' $required />";
		}
		if ( 'hidden' !== $field['input_type'] ) {
			$return .= ( 'checkbox' === $field['input_type'] || 'radio' === $field['input_type'] ) ? '<p class="' . $field['input_type'] . '">' . $output . " <label for='$name'>" . $field['title'] . $req_label . '</label></p>' : "<p><label for='$name'>" . $field['title'] . $req_label . '</label> ' . $output . '</p>';
		} else {
			$return .= $output;
		}
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
 * Get a specific field with an location ID
 *
 * @param string $field Specific field to get.
 * @param int    $id Location ID.
 *
 * @return string value as string.
 */
function mc_location_data( $field, $id ) {
	if ( $id ) {
		$mcdb   = mc_is_remote_db();
		$sql    = $mcdb->prepare( "SELECT $field FROM " . my_calendar_locations_table() . ' WHERE location_id = %d', $id );
		$result = $mcdb->get_var( $sql );

		return (string) $result;
	}

	return '';
}

/**
 * Get options list of locations to choose from
 *
 * @param object|false $location Selected location object or false if nothing selected.
 *
 * @return string set of option elements
 */
function mc_location_select( $location = false ) {
	// Grab all locations and list them.
	$list = '';
	$locs = mc_get_locations( 'select-locations' );

	foreach ( $locs as $loc ) {
		// If label is empty, display street.
		if ( '' === (string) $loc->location_label ) {
			$label = $loc->location_street;
		} else {
			$label = $loc->location_label;
		}
		// If neither label nor street, skip.
		if ( '' === (string) $label ) {
			continue;
		}
		$l = '<option value="' . $loc->location_id . '"';
		if ( $location ) {
			if ( (int) $location === (int) $loc->location_id ) {
				$l .= ' selected="selected"';
			}
		}
		$l    .= '>' . mc_kses_post( stripslashes( $label ) ) . '</option>';
		$list .= $l;
	}

	return '<option value="">' . __( 'Select', 'my-calendar' ) . '</option>' . $list;
}

/**
 * Get list of locations (IDs and labels)
 *
 * @param string|array $args array of relevant arguments. If string, get all locations and set context.
 *
 * @return array locations (IDs and labels only)
 */
function mc_get_locations( $args ) {
	global $wpdb;
	if ( is_array( $args ) ) {
		$orderby = ( isset( $args['orderby'] ) ) ? $args['orderby'] : 'location_label';
		$order   = ( isset( $args['order'] ) ) ? $args['order'] : 'ASC';
		$where   = ( isset( $args['where'] ) ) ? $args['where'] : '1';
		$is      = ( isset( $args['is'] ) ) ? $args['is'] : '1';
	} else {
		$orderby = 'location_label';
		$order   = 'ASC';
		$where   = '1';
		$is      = '1';
	}
	if ( ! ( 'ASC' === $order || 'DESC' === $order ) ) {
		// Prevent invalid order parameters.
		$order = 'ASC';
	}
	$valid_args = $wpdb->get_col( 'DESC ' . my_calendar_locations_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( ! ( in_array( $orderby, $valid_args, true ) ) ) {
		// Prevent invalid order columns.
		$orderby = 'location_label';
	}
	$results = $wpdb->get_results( $wpdb->prepare( 'SELECT location_id,location_label,location_street FROM ' . my_calendar_locations_table() . ' WHERE ' . esc_sql( $where ) . ' = %s ORDER BY ' . esc_sql( $orderby ) . ' ' . esc_sql( $order ), $is ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	/**
	 * Filter returned results when searching locations.
	 *
	 * @hook mc_filter_location_results
	 *
	 * @param {array} $results Array of IDs or objects.
	 * @param {array} $args Query arguments.
	 *
	 * @return {array}
	 */
	return apply_filters( 'mc_filter_location_results', $results, $args );
}

/**
 * Search location titles.
 *
 * @param string $query Location query.
 *
 * @return array locations
 */
function mc_core_search_locations( $query = '' ) {
	global $wpdb;
	$search  = '';
	$db_type = mc_get_db_type();
	$query   = esc_sql( $query );
	$length  = strlen( $query );

	if ( '' !== $query ) {
		// Fulltext is supported in InnoDB since MySQL 5.6; minimum required by WP is 5.0 as of WP 5.5.
		// 37% of installs still below 5.6 as of 11/30/2020.
		// 2.4% of installs below 5.6 as of 7/14/2022.
		if ( 'MyISAM' === $db_type && $length > 3 ) {
			/**
			 * Filter the fields used to handle MATCH queries in location searches on MyISAM dbs.
			 *
			 * @hook mc_search_fields
			 *
			 * @param {string} $fields Table columns in locations table.
			 *
			 * @return {string}
			 */
			$search = ' WHERE MATCH(' . apply_filters( 'mc_search_fields', 'location_label' ) . ") AGAINST ( '$query' IN BOOLEAN MODE ) ";
		} else {
			$search = " WHERE location_label LIKE '%$query%' ";
		}
	} else {
		$search = '';
	}

	$locations = $wpdb->get_results( 'SELECT SQL_CALC_FOUND_ROWS location_id, location_label FROM ' . my_calendar_locations_table() . " $search ORDER BY location_label ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

	return $locations;
}


/**
 * Search location titles.
 *
 * @param string $query Location query.
 *
 * @return array locations
 */
function mc_get_countries( $query = '' ) {
	global $wpdb;
	$search  = '';
	$db_type = mc_get_db_type();
	$query   = esc_sql( $query );
	$length  = strlen( $query );

	if ( '' !== $query ) {
		// Fulltext is supported in InnoDB since MySQL 5.6; minimum required by WP is 5.0 as of WP 5.5.
		// 37% of installs still below 5.6 as of 11/30/2020.
		// 2.4% of installs below 5.6 as of 7/14/2022.
		if ( 'MyISAM' === $db_type && $length > 3 ) {
			/**
			 * Filter the fields used to handle MATCH queries in location searches on MyISAM dbs.
			 *
			 * @hook mc_search_fields
			 *
			 * @param {string} $fields Table columns in locations table.
			 *
			 * @return {string}
			 */
			$search = " WHERE MATCH(location_country) AGAINST ( '$query' IN BOOLEAN MODE ) ";
		} else {
			$search = " WHERE location_country LIKE '%$query%' ";
		}
	} else {
		$search = '';
	}

	$locations = $wpdb->get_results( 'SELECT DISTINCT location_country FROM ' . my_calendar_locations_table() . " $search ORDER BY location_country ASC", 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	$return    = array();
	foreach ( $locations as $location ) {
		$return[] = $location['location_country'];
	}

	return $return;
}

/**
 * Return the results of a search for countries.
 *
 * @param string $query A search query.
 *
 * @return array
 */
function mc_default_countries( $query = '' ) {
	$countries = array(
		'AF' => __( 'Afghanistan', 'my-calendar' ),
		'AL' => __( 'Albania', 'my-calendar' ),
		'DZ' => __( 'Algeria', 'my-calendar' ),
		'AS' => __( 'American Samoa', 'my-calendar' ),
		'AD' => __( 'Andorra', 'my-calendar' ),
		'AO' => __( 'Angola', 'my-calendar' ),
		'AI' => __( 'Anguilla', 'my-calendar' ),
		'AQ' => __( 'Antarctica', 'my-calendar' ),
		'AG' => __( 'Antigua and Barbuda', 'my-calendar' ),
		'AR' => __( 'Argentina', 'my-calendar' ),
		'AM' => __( 'Armenia', 'my-calendar' ),
		'AW' => __( 'Aruba', 'my-calendar' ),
		'AU' => __( 'Australia', 'my-calendar' ),
		'AT' => __( 'Austria', 'my-calendar' ),
		'AZ' => __( 'Azerbaijan', 'my-calendar' ),
		'BS' => __( 'Bahamas', 'my-calendar' ),
		'BH' => __( 'Bahrain', 'my-calendar' ),
		'BD' => __( 'Bangladesh', 'my-calendar' ),
		'BB' => __( 'Barbados', 'my-calendar' ),
		'BY' => __( 'Belarus', 'my-calendar' ),
		'BE' => __( 'Belgium', 'my-calendar' ),
		'BZ' => __( 'Belize', 'my-calendar' ),
		'BJ' => __( 'Benin', 'my-calendar' ),
		'BM' => __( 'Bermuda', 'my-calendar' ),
		'BT' => __( 'Bhutan', 'my-calendar' ),
		'BO' => __( 'Bolivia', 'my-calendar' ),
		'BA' => __( 'Bosnia and Herzegovina', 'my-calendar' ),
		'BW' => __( 'Botswana', 'my-calendar' ),
		'BV' => __( 'Bouvet Island', 'my-calendar' ),
		'BR' => __( 'Brazil', 'my-calendar' ),
		'BQ' => __( 'British Antarctic Territory', 'my-calendar' ),
		'IO' => __( 'British Indian Ocean Territory', 'my-calendar' ),
		'VG' => __( 'British Virgin Islands', 'my-calendar' ),
		'BN' => __( 'Brunei', 'my-calendar' ),
		'BG' => __( 'Bulgaria', 'my-calendar' ),
		'BF' => __( 'Burkina Faso', 'my-calendar' ),
		'BI' => __( 'Burundi', 'my-calendar' ),
		'KH' => __( 'Cambodia', 'my-calendar' ),
		'CM' => __( 'Cameroon', 'my-calendar' ),
		'CA' => __( 'Canada', 'my-calendar' ),
		'CT' => __( 'Canton and Enderbury Islands', 'my-calendar' ),
		'CV' => __( 'Cape Verde', 'my-calendar' ),
		'KY' => __( 'Cayman Islands', 'my-calendar' ),
		'CF' => __( 'Central African Republic', 'my-calendar' ),
		'TD' => __( 'Chad', 'my-calendar' ),
		'CL' => __( 'Chile', 'my-calendar' ),
		'CN' => __( 'China', 'my-calendar' ),
		'CX' => __( 'Christmas Island', 'my-calendar' ),
		'CC' => __( 'Cocos [Keeling] Islands', 'my-calendar' ),
		'CO' => __( 'Colombia', 'my-calendar' ),
		'KM' => __( 'Comoros', 'my-calendar' ),
		'CG' => __( 'Congo - Brazzaville', 'my-calendar' ),
		'CD' => __( 'Congo - Kinshasa', 'my-calendar' ),
		'CK' => __( 'Cook Islands', 'my-calendar' ),
		'CR' => __( 'Costa Rica', 'my-calendar' ),
		'HR' => __( 'Croatia', 'my-calendar' ),
		'CU' => __( 'Cuba', 'my-calendar' ),
		'CY' => __( 'Cyprus', 'my-calendar' ),
		'CZ' => __( 'Czech Republic', 'my-calendar' ),
		'CI' => __( 'Côte d’Ivoire', 'my-calendar' ),
		'DK' => __( 'Denmark', 'my-calendar' ),
		'DJ' => __( 'Djibouti', 'my-calendar' ),
		'DM' => __( 'Dominica', 'my-calendar' ),
		'DO' => __( 'Dominican Republic', 'my-calendar' ),
		'NQ' => __( 'Dronning Maud Land', 'my-calendar' ),
		'DD' => __( 'East Germany', 'my-calendar' ),
		'EC' => __( 'Ecuador', 'my-calendar' ),
		'EG' => __( 'Egypt', 'my-calendar' ),
		'SV' => __( 'El Salvador', 'my-calendar' ),
		'GQ' => __( 'Equatorial Guinea', 'my-calendar' ),
		'ER' => __( 'Eritrea', 'my-calendar' ),
		'EE' => __( 'Estonia', 'my-calendar' ),
		'ET' => __( 'Ethiopia', 'my-calendar' ),
		'FK' => __( 'Falkland Islands', 'my-calendar' ),
		'FO' => __( 'Faroe Islands', 'my-calendar' ),
		'FJ' => __( 'Fiji', 'my-calendar' ),
		'FI' => __( 'Finland', 'my-calendar' ),
		'FR' => __( 'France', 'my-calendar' ),
		'GF' => __( 'French Guiana', 'my-calendar' ),
		'PF' => __( 'French Polynesia', 'my-calendar' ),
		'TF' => __( 'French Southern Territories', 'my-calendar' ),
		'FQ' => __( 'French Southern and Antarctic Territories', 'my-calendar' ),
		'GA' => __( 'Gabon', 'my-calendar' ),
		'GM' => __( 'Gambia', 'my-calendar' ),
		'GE' => __( 'Georgia', 'my-calendar' ),
		'DE' => __( 'Germany', 'my-calendar' ),
		'GH' => __( 'Ghana', 'my-calendar' ),
		'GI' => __( 'Gibraltar', 'my-calendar' ),
		'GR' => __( 'Greece', 'my-calendar' ),
		'GL' => __( 'Greenland', 'my-calendar' ),
		'GD' => __( 'Grenada', 'my-calendar' ),
		'GP' => __( 'Guadeloupe', 'my-calendar' ),
		'GU' => __( 'Guam', 'my-calendar' ),
		'GT' => __( 'Guatemala', 'my-calendar' ),
		'GG' => __( 'Guernsey', 'my-calendar' ),
		'GN' => __( 'Guinea', 'my-calendar' ),
		'GW' => __( 'Guinea-Bissau', 'my-calendar' ),
		'GY' => __( 'Guyana', 'my-calendar' ),
		'HT' => __( 'Haiti', 'my-calendar' ),
		'HM' => __( 'Heard Island and McDonald Islands', 'my-calendar' ),
		'HN' => __( 'Honduras', 'my-calendar' ),
		'HK' => __( 'Hong Kong SAR China', 'my-calendar' ),
		'HU' => __( 'Hungary', 'my-calendar' ),
		'IS' => __( 'Iceland', 'my-calendar' ),
		'IN' => __( 'India', 'my-calendar' ),
		'ID' => __( 'Indonesia', 'my-calendar' ),
		'IR' => __( 'Iran', 'my-calendar' ),
		'IQ' => __( 'Iraq', 'my-calendar' ),
		'IE' => __( 'Ireland', 'my-calendar' ),
		'IM' => __( 'Isle of Man', 'my-calendar' ),
		'IL' => __( 'Israel', 'my-calendar' ),
		'IT' => __( 'Italy', 'my-calendar' ),
		'JM' => __( 'Jamaica', 'my-calendar' ),
		'JP' => __( 'Japan', 'my-calendar' ),
		'JE' => __( 'Jersey', 'my-calendar' ),
		'JT' => __( 'Johnston Island', 'my-calendar' ),
		'JO' => __( 'Jordan', 'my-calendar' ),
		'KZ' => __( 'Kazakhstan', 'my-calendar' ),
		'KE' => __( 'Kenya', 'my-calendar' ),
		'KI' => __( 'Kiribati', 'my-calendar' ),
		'KW' => __( 'Kuwait', 'my-calendar' ),
		'KG' => __( 'Kyrgyzstan', 'my-calendar' ),
		'LA' => __( 'Laos', 'my-calendar' ),
		'LV' => __( 'Latvia', 'my-calendar' ),
		'LB' => __( 'Lebanon', 'my-calendar' ),
		'LS' => __( 'Lesotho', 'my-calendar' ),
		'LR' => __( 'Liberia', 'my-calendar' ),
		'LY' => __( 'Libya', 'my-calendar' ),
		'LI' => __( 'Liechtenstein', 'my-calendar' ),
		'LT' => __( 'Lithuania', 'my-calendar' ),
		'LU' => __( 'Luxembourg', 'my-calendar' ),
		'MO' => __( 'Macau SAR China', 'my-calendar' ),
		'MK' => __( 'Macedonia', 'my-calendar' ),
		'MG' => __( 'Madagascar', 'my-calendar' ),
		'MW' => __( 'Malawi', 'my-calendar' ),
		'MY' => __( 'Malaysia', 'my-calendar' ),
		'MV' => __( 'Maldives', 'my-calendar' ),
		'ML' => __( 'Mali', 'my-calendar' ),
		'MT' => __( 'Malta', 'my-calendar' ),
		'MH' => __( 'Marshall Islands', 'my-calendar' ),
		'MQ' => __( 'Martinique', 'my-calendar' ),
		'MR' => __( 'Mauritania', 'my-calendar' ),
		'MU' => __( 'Mauritius', 'my-calendar' ),
		'YT' => __( 'Mayotte', 'my-calendar' ),
		'FX' => __( 'Metropolitan France', 'my-calendar' ),
		'MX' => __( 'Mexico', 'my-calendar' ),
		'FM' => __( 'Micronesia', 'my-calendar' ),
		'MI' => __( 'Midway Islands', 'my-calendar' ),
		'MD' => __( 'Moldova', 'my-calendar' ),
		'MC' => __( 'Monaco', 'my-calendar' ),
		'MN' => __( 'Mongolia', 'my-calendar' ),
		'ME' => __( 'Montenegro', 'my-calendar' ),
		'MS' => __( 'Montserrat', 'my-calendar' ),
		'MA' => __( 'Morocco', 'my-calendar' ),
		'MZ' => __( 'Mozambique', 'my-calendar' ),
		'MM' => __( 'Myanmar [Burma]', 'my-calendar' ),
		'NA' => __( 'Namibia', 'my-calendar' ),
		'NR' => __( 'Nauru', 'my-calendar' ),
		'NP' => __( 'Nepal', 'my-calendar' ),
		'NL' => __( 'Netherlands', 'my-calendar' ),
		'AN' => __( 'Netherlands Antilles', 'my-calendar' ),
		'NT' => __( 'Neutral Zone', 'my-calendar' ),
		'NC' => __( 'New Caledonia', 'my-calendar' ),
		'NZ' => __( 'New Zealand', 'my-calendar' ),
		'NI' => __( 'Nicaragua', 'my-calendar' ),
		'NE' => __( 'Niger', 'my-calendar' ),
		'NG' => __( 'Nigeria', 'my-calendar' ),
		'NU' => __( 'Niue', 'my-calendar' ),
		'NF' => __( 'Norfolk Island', 'my-calendar' ),
		'KP' => __( 'North Korea', 'my-calendar' ),
		'VD' => __( 'North Vietnam', 'my-calendar' ),
		'MP' => __( 'Northern Mariana Islands', 'my-calendar' ),
		'NO' => __( 'Norway', 'my-calendar' ),
		'OM' => __( 'Oman', 'my-calendar' ),
		'PC' => __( 'Pacific Islands Trust Territory', 'my-calendar' ),
		'PK' => __( 'Pakistan', 'my-calendar' ),
		'PW' => __( 'Palau', 'my-calendar' ),
		'PS' => __( 'Palestinian Territories', 'my-calendar' ),
		'PA' => __( 'Panama', 'my-calendar' ),
		'PZ' => __( 'Panama Canal Zone', 'my-calendar' ),
		'PG' => __( 'Papua New Guinea', 'my-calendar' ),
		'PY' => __( 'Paraguay', 'my-calendar' ),
		'YD' => __( 'People\'s Democratic Republic of Yemen', 'my-calendar' ),
		'PE' => __( 'Peru', 'my-calendar' ),
		'PH' => __( 'Philippines', 'my-calendar' ),
		'PN' => __( 'Pitcairn Islands', 'my-calendar' ),
		'PL' => __( 'Poland', 'my-calendar' ),
		'PT' => __( 'Portugal', 'my-calendar' ),
		'PR' => __( 'Puerto Rico', 'my-calendar' ),
		'QA' => __( 'Qatar', 'my-calendar' ),
		'RO' => __( 'Romania', 'my-calendar' ),
		'RU' => __( 'Russia', 'my-calendar' ),
		'RW' => __( 'Rwanda', 'my-calendar' ),
		'BL' => __( 'Saint Barthélemy', 'my-calendar' ),
		'SH' => __( 'Saint Helena', 'my-calendar' ),
		'KN' => __( 'Saint Kitts and Nevis', 'my-calendar' ),
		'LC' => __( 'Saint Lucia', 'my-calendar' ),
		'MF' => __( 'Saint Martin', 'my-calendar' ),
		'PM' => __( 'Saint Pierre and Miquelon', 'my-calendar' ),
		'VC' => __( 'Saint Vincent and the Grenadines', 'my-calendar' ),
		'WS' => __( 'Samoa', 'my-calendar' ),
		'SM' => __( 'San Marino', 'my-calendar' ),
		'SA' => __( 'Saudi Arabia', 'my-calendar' ),
		'SN' => __( 'Senegal', 'my-calendar' ),
		'RS' => __( 'Serbia', 'my-calendar' ),
		'CS' => __( 'Serbia and Montenegro', 'my-calendar' ),
		'SC' => __( 'Seychelles', 'my-calendar' ),
		'SL' => __( 'Sierra Leone', 'my-calendar' ),
		'SG' => __( 'Singapore', 'my-calendar' ),
		'SK' => __( 'Slovakia', 'my-calendar' ),
		'SI' => __( 'Slovenia', 'my-calendar' ),
		'SB' => __( 'Solomon Islands', 'my-calendar' ),
		'SO' => __( 'Somalia', 'my-calendar' ),
		'ZA' => __( 'South Africa', 'my-calendar' ),
		'GS' => __( 'South Georgia and the South Sandwich Islands', 'my-calendar' ),
		'KR' => __( 'South Korea', 'my-calendar' ),
		'ES' => __( 'Spain', 'my-calendar' ),
		'LK' => __( 'Sri Lanka', 'my-calendar' ),
		'SD' => __( 'Sudan', 'my-calendar' ),
		'SR' => __( 'Suriname', 'my-calendar' ),
		'SJ' => __( 'Svalbard and Jan Mayen', 'my-calendar' ),
		'SZ' => __( 'Swaziland', 'my-calendar' ),
		'SE' => __( 'Sweden', 'my-calendar' ),
		'CH' => __( 'Switzerland', 'my-calendar' ),
		'SY' => __( 'Syria', 'my-calendar' ),
		'ST' => __( 'São Tomé and Príncipe', 'my-calendar' ),
		'TW' => __( 'Taiwan', 'my-calendar' ),
		'TJ' => __( 'Tajikistan', 'my-calendar' ),
		'TZ' => __( 'Tanzania', 'my-calendar' ),
		'TH' => __( 'Thailand', 'my-calendar' ),
		'TL' => __( 'Timor-Leste', 'my-calendar' ),
		'TG' => __( 'Togo', 'my-calendar' ),
		'TK' => __( 'Tokelau', 'my-calendar' ),
		'TO' => __( 'Tonga', 'my-calendar' ),
		'TT' => __( 'Trinidad and Tobago', 'my-calendar' ),
		'TN' => __( 'Tunisia', 'my-calendar' ),
		'TR' => __( 'Turkey', 'my-calendar' ),
		'TM' => __( 'Turkmenistan', 'my-calendar' ),
		'TC' => __( 'Turks and Caicos Islands', 'my-calendar' ),
		'TV' => __( 'Tuvalu', 'my-calendar' ),
		'UM' => __( 'U.S. Minor Outlying Islands', 'my-calendar' ),
		'PU' => __( 'U.S. Miscellaneous Pacific Islands', 'my-calendar' ),
		'VI' => __( 'U.S. Virgin Islands', 'my-calendar' ),
		'UG' => __( 'Uganda', 'my-calendar' ),
		'UA' => __( 'Ukraine', 'my-calendar' ),
		'SU' => __( 'Union of Soviet Socialist Republics', 'my-calendar' ),
		'AE' => __( 'United Arab Emirates', 'my-calendar' ),
		'GB' => __( 'United Kingdom', 'my-calendar' ),
		'US' => __( 'United States', 'my-calendar' ),
		'UY' => __( 'Uruguay', 'my-calendar' ),
		'UZ' => __( 'Uzbekistan', 'my-calendar' ),
		'VU' => __( 'Vanuatu', 'my-calendar' ),
		'VA' => __( 'Vatican City', 'my-calendar' ),
		'VE' => __( 'Venezuela', 'my-calendar' ),
		'VN' => __( 'Vietnam', 'my-calendar' ),
		'WK' => __( 'Wake Island', 'my-calendar' ),
		'WF' => __( 'Wallis and Futuna', 'my-calendar' ),
		'EH' => __( 'Western Sahara', 'my-calendar' ),
		'YE' => __( 'Yemen', 'my-calendar' ),
		'ZM' => __( 'Zambia', 'my-calendar' ),
		'ZW' => __( 'Zimbabwe', 'my-calendar' ),
		'AX' => __( 'Åland Islands', 'my-calendar' ),
	);

	/**
	 * Filter the country list before returning.
	 *
	 * @hook mc_countries_filters
	 *
	 * @param {array} $countries countries array for filtering.
	 */
	$countries = apply_filters( 'mc_countries_filters', $countries );
	$results   = array();
	foreach ( $countries as $key => $value ) {
		if ( stripos( $value, $query ) !== false || strtolower( $query ) === strtolower( $key ) ) {
			$results[] = $countries[ $key ];
		}
	}

	return $results;
}

/**
 * Filter theme content to display My Calendar location data.
 *
 * @param string $content Post content.
 *
 * @return string
 */
function mc_display_location_details( $content ) {
	if ( is_singular( 'mc-locations' ) && in_the_loop() && is_main_query() ) {
		$location = mc_get_location_id( get_the_ID() );
		$location = mc_get_location( $location );
		if ( ! is_object( $location ) ) {
			return $content;
		}
		$args = array(
			'ltype'    => 'name',
			'lvalue'   => $location->location_label,
			'type'     => 'events',
			'after'    => 5,
			'before'   => 0,
			'fallback' => __( 'No events currently scheduled at this location.', 'my-calendar' ),
		);
		/**
		 * Filter the arguments used to generate upcoming events for a location. Default ['ltype' => 'name', 'lvalue' => {location_label}, 'type' => 'events', 'after' => 5, 'before' => 0, 'fallback' => 'No events currently scheduled at this location.'].
		 *
		 * @hook mc_display_location_events
		 *
		 * @param {array}  $args Array of upcoming events arguments.
		 * @param {object} $location Location object.
		 *
		 * @return {array}
		 */
		$args    = apply_filters( 'mc_display_location_events', $args, $location );
		$events  = my_calendar_upcoming_events( $args );
		$data    = array(
			'location' => $location,
			'events'   => $events,
		);
		$details = mc_load_template( 'location/single', $data );
		if ( $details ) {
			$content = $details;
		} else {
			$content = '
<div class="mc-view-location">
	<div class="mc-location-content">' . $content . '</div>
	<div class="mc-location-gmap">' . mc_generate_map( $location, 'location' ) . '</div>
	<div class="mc-location-hcard">' . mc_hcard( $location, 'true', 'true', 'location' ) . '</div>
	<div class="mc-location-upcoming"><h2>' . __( 'Upcoming Events', 'my-calendar' ) . '</h2>' . $events . '</div>
</div>';
			/**
			 * Filter the HTML output for single location details.
			 *
			 * @hook mc_location_output
			 *
			 * @param {string} $content Full HTML output.
			 * @param {object} $location Calendar location object.
			 *
			 * @return {string}
			 */
			$content = apply_filters( 'mc_location_output', $content, $location );
		}
	}

	return $content;
}
add_filter( 'the_content', 'mc_display_location_details' );
