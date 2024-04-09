<?php
/**
 * Manage My Calendar events groups
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
 * Generate the Grouped event editing form
 */
function my_calendar_group_edit() {
	global $wpdb;
	// First some quick cleaning up.
	$action   = ! empty( $_POST['event_action'] ) ? sanitize_text_field( $_POST['event_action'] ) : '';
	$event_id = ! empty( $_POST['event_id'] ) ? (int) $_POST['event_id'] : '';
	$group_id = ! empty( $_POST['group_id'] ) ? (int) $_POST['group_id'] : '';

	if ( isset( $_GET['mode'] ) ) {
		if ( 'edit' === $_GET['mode'] ) {
			$action   = 'edit';
			$event_id = (int) $_GET['event_id'];
			$group_id = (int) $_GET['group_id'];
		}
	}

	if ( isset( $_POST['event_action'] ) ) {
		global $mc_output;
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'My Calendar: Security check failed' );
		}
		$message = '';
		switch ( $_POST['event_action'] ) {
			case 'edit':
				if ( isset( $_POST['apply'] ) && is_array( $_POST['apply'] ) ) {
					$post      = map_deep( $_POST, 'wp_kses_post' );
					$mc_output = mc_check_group_data( $action, $post );
					foreach ( $post['apply'] as $event_id ) {
						$event_id = absint( $event_id );
						$response = my_calendar_save_group( $action, $mc_output, $event_id, $post );
						echo $response;
					}
				}
				break;
			case 'break':
				$events = map_deep( $_POST['break'], 'absint' );
				foreach ( $events as $event_id ) {
					$update  = array( 'event_group_id' => 0 );
					$formats = array( '%d' );
					$result  = $wpdb->update( my_calendar_table(), $update, array( 'event_id' => $event_id ), $formats, '%d' );
					// Translators: Calendar URL.
					$url = sprintf( __( 'View <a href="%s">your calendar</a>.', 'my-calendar' ), mc_get_uri() );
					if ( false === $result ) {
						$message = mc_show_error( __( 'Event not updated.', 'my-calendar' ) . " $url", false );
					} elseif ( 0 === $result ) {
						$message = mc_show_notice( "#$event_id: " . __( 'Nothing was changed in that update.', 'my-calendar' ) . "  $url", false );
					} else {
						$message = mc_show_notice( "#$event_id: " . __( 'Event updated successfully', 'my-calendar' ) . ". $url", false );
					}
				}
				break;
			case 'group':
				if ( isset( $_POST['group'] ) && is_array( $_POST['group'] ) ) {
					$events = map_deep( $_POST['group'], 'absint' );
					sort( $events );
					foreach ( $events as $event_id ) {
						$group_id = $events[0];
						$update   = array( 'event_group_id' => $group_id );
						$formats  = array( '%d' );
						$result   = $wpdb->update( my_calendar_table(), $update, array( 'event_id' => $event_id ), $formats, '%d' );

						if ( false === $result ) {
							$message = mc_show_error( __( 'Event not grouped.', 'my-calendar' ), false );
						} elseif ( 0 === $result ) {
							$message = mc_show_notice( "#$event_id: " . __( 'Nothing was changed in that update.', 'my-calendar' ), false );
						} else {
							// Translators: Event group ID.
							$message = mc_show_notice( sprintf( __( 'Group %s: Events grouped successfully', 'my-calendar' ), "#$event_id" ), false );
						}
					}
				}
				break;
		}
		echo $message;
	}
	?>

	<div class="wrap my-calendar-admin" id="my-calendar">
	<?php
	my_calendar_check_db();
	if ( 'edit' === $action ) {
		echo '<h1>' . __( 'Edit Event Group', 'my-calendar' ) . '</h1>';
		if ( empty( $event_id ) || empty( $group_id ) ) {
			mc_show_error( __( 'You must provide an event group id in order to edit it', 'my-calendar' ) );
		} else {
			mc_edit_groups( 'edit', $event_id, $group_id );
		}
	} else {
		?>
		<h1><?php esc_html_e( 'Event Groups', 'my-calendar' ); ?></h1>
		<p>
			<?php _e( 'When you choose a group of events to edit, the form will be pre-filled with the content from the event you choose.', 'my-calendar' ); ?>
		</p>
		<div class="mc-tablinks">
			<a href="<?php echo admin_url( 'admin.php?page=my-calendar-manage' ); ?>"><?php esc_html_e( 'My Events', 'my-calendar' ); ?></strong>
			<a href="#my-calendar-admin-table" aria-current="page"><?php esc_html_e( 'Event Groups', 'my-calendar' ); ?></a>
		</div>
		<div class="postbox-container jcd-wide">
			<div class="metabox-holder">
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h2><?php esc_html_e( 'Event Groups', 'my-calendar' ); ?></h2>
						<?php mc_list_groups(); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	mc_show_sidebar();
	?>
	</div>
	<?php
}

/**
 * Save data within a group of events.
 *
 * @param string $action Type of action: add, edit.
 * @param array  $output Data and status of data check.
 * @param int    $event_id Event ID.
 * @param array  $post POST data.
 *
 * @return string message
 */
function my_calendar_save_group( $action, $output, $event_id, $post = array() ) {
	global $wpdb, $event_author;
	$proceed = $output[0];
	$message = '';

	if ( 'edit' === $action && true === $proceed ) {
		$event_author = (int) ( $_POST['event_author'] );
		if ( mc_can_edit_event( $event_id ) ) {
			$update = $output[2];
			$cats   = $update['event_categories'];
			unset( $update['event_categories'] );
			mc_update_category_relationships( $cats, $event_id );

			/**
			 * Filter calendar update data before saving an individual event when managing groups.
			 *
			 * @hook mc_update_group_data
			 *
			 * @param {array} $update Event update data for groups.
			 * @param {string} $event_author Author for these events.
			 * @param {string} $action Action performed.
			 * @param {int}    $event_id Event ID being updated.
			 *
			 * @return {array}
			 */
			$update  = apply_filters( 'mc_update_group_data', $update, $event_author, $action, $event_id );
			$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d' );
			$result  = $wpdb->update( my_calendar_table(), $update, array( 'event_id' => $event_id ), $formats, '%d' );

			$edit_url = '<a href="' . admin_url( "admin.php?page=my-calendar&amp;mode=edit&amp;event_id=$event_id" ) . '" class="button-secondary">' . __( 'Edit Event', 'my-calendar' ) . '</a>';
			$view_url = ' <a href="' . mc_get_details_link( mc_get_first_event( $event_id ) ) . '" class="button-secondary">' . __( 'View Event', 'my-calendar' ) . '</a>';
			$url      = $edit_url . $view_url;
			// Make sure POST data is available in update array.
			$update = array_merge( $update, $post );
			// Same as action on basic save.
			mc_event_post( 'edit', $update, $event_id, $result );
			/**
			 * Run action when an event is saved.
			 *
			 * @hook mc_save_event
			 *
			 * @param {string}    $action Current action: edit, copy, add.
			 * @param {array}     $update Data updated.
			 * @param {int}       $event_id Event ID.
			 * @param {int|false} $result Result of the DB update query.
			 */
			do_action( 'mc_save_event', 'edit', $update, $event_id, $result );
			/**
			 * Run action when a group of events are saved.
			 *
			 * @hook mc_save_grouped_events
			 *
			 * @param {int|false} $result Current action: edit, copy, add.
			 * @param {int}       $event_id Event ID.
			 * @param {array} $update Updated data.
			 */
			do_action( 'mc_save_grouped_events', $result, $event_id, $update );
			if ( false === $result ) {
				$message = mc_show_error( "#$event_id; " . __( 'Your event was not updated.', 'my-calendar' ) . " $url", false );
			} elseif ( 0 === $result ) {
				$message = mc_show_notice( "#$event_id: " . __( 'Nothing was changed in that update.', 'my-calendar' ) . " $url", false );
			} else {
				$message = mc_show_notice( "#$event_id: " . __( 'Event updated successfully', 'my-calendar' ) . ". $url", false );
			}
		} else {
			$message = mc_show_error( "#$event_id: " . __( 'You do not have sufficient permissions to edit that event.', 'my-calendar' ), false );
		}
	}
	$message = $message . "\n" . $output[3];

	return $message;
}

/**
 * Compare events within a group to see if they currently have the same information.
 *
 * @param int          $group_id Group ID.
 * @param string|false $field Column name of field to compare. False to check all grouped fields.
 * @param bool         $display False to return if single field comparison.
 *
 * @return string|boolean True if information is the same for multiple fields; string for single field checks.
 */
function mc_compare_group_members( $group_id, $field = false, $display = true ) {
	global $wpdb;
	if ( ! $field ) {
		$query = 'SELECT event_title, event_desc, event_short, event_link, event_url, event_image, event_category, event_link_expires, event_host FROM ' . my_calendar_table() . ' WHERE event_group_id = %d';
	} else {
		// Just comparing a single field.
		$query = "SELECT $field FROM " . my_calendar_table() . ' WHERE event_group_id = %d';
	}
	$results = $wpdb->get_results( $wpdb->prepare( $query, $group_id ), ARRAY_N ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$count   = count( $results );
	for ( $i = 0; $i < $count; $i++ ) {
		$n = ( ( $i + 1 ) > $count - 1 ) ? 0 : $i + 1;
		if ( md5( implode( '', $results[ $i ] ) ) !== md5( implode( '', $results[ $n ] ) ) ) {
			if ( $field ) {
				$message = ' <span class="nomatch">' . __( 'Fields do not match', 'my-calendar' ) . '</span>';
				if ( $display ) {
					echo wp_kses_post( $message );
				} else {
					return $message;
				}
			} else {
				return false;
			}
		}
	}

	return true;
}

/**
 * Show members of group and provide options to select which to edit.
 *
 * @param int    $group_id Group ID.
 * @param string $type Context of form.
 *
 * @return string form
 */
function mc_group_form( $group_id, $type = 'break' ) {
	$event_id = (int) $_GET['event_id'];
	$nonce    = wp_create_nonce( 'my-calendar-nonce' );
	$results  = mc_get_grouped_events( $group_id );
	if ( 'apply' === $type ) {
		$warning = ( ! mc_compare_group_members( $group_id ) ) ? '<p class="unmatched warning">' . __( '<strong>Warning:</strong> Editable fields for the events in this group do not match.', 'my-calendar' ) . '</p>' : '<p class="matched">' . __( 'Editable fields for the events in this group match.', 'my-calendar' ) . '</p>';
	} else {
		$warning = '';
	}
	$class   = ( 'break' === $type ) ? 'break' : 'apply';
	$group   = "<div class='group mc-actions $class'>";
	$group  .= $warning;
	$group  .= ( 'apply' === $type ) ? '<fieldset><legend>' . __( 'Apply changes to:', 'my-calendar' ) . '</legend>' : '';
	$group  .= ( 'break' === $type ) ? "<form method='post' action='" . admin_url( "admin.php?page=my-calendar-manage&groups=true&amp;mode=edit&amp;event_id=$event_id&amp;group_id=$group_id" ) . "'>
	<div><input type='hidden' value='" . esc_attr( $group_id ) . "' name='group_id' /><input type='hidden' value='" . esc_attr( $type ) . "' name='event_action' /><input type='hidden' name='_wpnonce' value='$nonce' />
	</div>" : '';
	$group  .= "<ul class='checkboxes'>";
	$checked = ( 'apply' === $type ) ? ' checked="checked"' : '';
	foreach ( $results as $result ) {
		$first = mc_get_first_event( $result->event_id );
		if ( ! is_object( $first ) ) {
			continue;
		}
		$date   = date_i18n( 'D, j M, Y', $first->ts_occur_begin );
		$time   = date_i18n( 'g:i a', $first->ts_occur_begin );
		$title  = esc_html( stripslashes( $first->event_title ) );
		$group .= "<li><input type='checkbox' name='$type" . "[]' value='$first->event_id' id='$type$first->event_id'$checked /> <label for='break$first->event_id'>$title<br />$date, $time</label></li>\n";
	}
	$group .= "<li><input type='checkbox' class='selectall' data-action='$type' id='$type'$checked /> <label for='$type'><b>" . __( 'Check/Uncheck all', 'my-calendar' ) . "</b></label></li>\n</ul>";
	$group .= ( 'apply' === $type ) ? '</fieldset>' : '';
	$group .= ( 'break' === $type ) ? "<p><input type='submit' class='button' value='" . __( 'Remove checked events from this group', 'my-calendar' ) . "' /></p></form>" : '';
	$group .= '</div>';

	return $group;
}

/**
 * The event edit form for the manage events admin page
 *
 * @param string $mode Editing mode.
 * @param int    $event_id Event ID.
 * @param int    $group_id Group ID.
 */
function mc_edit_groups( $mode = 'edit', $event_id = 0, $group_id = 0 ) {
	global $submission;
	$event_id = ( 0 === $event_id ) ? false : $event_id;
	$group_id = ( 0 === $group_id ) ? false : $group_id;
	$message  = '';
	$group    = '';
	$data     = mc_form_data( $event_id );

	if ( false !== $group_id ) {
		$group = mc_group_form( $group_id, 'break' );
	} else {
		$message .= __( 'You must provide a group ID to edit groups', 'my-calendar' );
	}
	mc_show_error( $message );
	echo wp_kses( $group, mc_kses_elements() );

	my_calendar_print_group_fields( $data, $mode, $event_id );
}

/**
 * Generate form to edit group editable fields.
 *
 * @param object $data Event object data.
 * @param string $mode Editing mode.
 * @param int    $event_id Event ID.
 */
function my_calendar_print_group_fields( $data, $mode, $event_id ) {
	global $user_ID;
	$has_data    = ( is_object( $data ) ) ? true : false;
	$user        = get_userdata( $user_ID );
	$group_id    = ( ! empty( $data->event_group_id ) ) ? $data->event_group_id : mc_group_id();
	$title       = '';
	$description = '';
	$short       = '';
	$image       = '';

	if ( is_object( $data ) ) {
		$title       = stripslashes( $data->event_title );
		$description = stripslashes( $data->event_desc );
		$short       = stripslashes( $data->event_short );
		$image       = $data->event_image;
	}
	?>
	<div class="postbox-container jcd-wide">
	<div class="metabox-holder">
	<form method="post" action="<?php echo admin_url( "admin.php?page=my-calendar-manage&groups=true&amp;mode=edit&amp;event_id=$event_id&amp;group_id=$group_id" ); ?>">
	<div>
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
		<input type="hidden" name="group_id" value="<?php echo absint( $group_id ); ?>" />
		<input type="hidden" name="event_action" value="<?php echo esc_attr( $mode ); ?>" />
		<input type="hidden" name="event_id" value="<?php echo absint( $event_id ); ?>" />
		<input type="hidden" name="event_author" value="<?php echo absint( $user_ID ); ?>" />
		<input type="hidden" name="event_post" value="group" />
		<input type="hidden" name="event_nonce_name" value="<?php echo wp_create_nonce( 'event_nonce' ); ?>" />
	</div>
	<div class="ui-sortable meta-box-sortables">
		<div class="postbox">
			<h2><?php esc_html_e( 'Manage Event Groups', 'my-calendar' ); ?></h2>

			<div class="inside">
				<div class="mc-controls">
					<ul>
						<li>
						<?php
						$manage_text = __( 'Manage groups', 'my-calendar' );
						echo "<span class='dashicons dashicons-calendar' aria-hidden='true'></span>" . '<a href="' . admin_url( 'admin.php?page=my-calendar-manage&groups=true' ) . '">' . $manage_text . '</a>';
						?>
						</li>
						<li><input type="submit" name="save" class="button-primary" value="<?php _e( 'Update Event Group', 'my-calendar' ); ?>"/></li>
					</ul>
				</div>
				<p>
					<label for="e_title"><?php esc_html_e( 'Event Title', 'my-calendar' ); ?> <span><?php esc_html_e( '(required)', 'my-calendar' ); ?></span>
					<?php mc_compare_group_members( $group_id, 'event_title' ); ?>
					</label><br/>
					<input type="text" id="e_title" class="widefat" name="event_title" value="<?php echo esc_attr( $title ); ?>" />
				</p>
				<?php
				echo mc_group_form( $group_id, 'apply' );

				if ( '0' === $data->event_repeats && ( 'S1' === $data->event_recur || 'S' === $data->event_recur ) ) {
					$span_checked = '';
					if ( is_object( $data ) && '1' === $data->event_span ) {
						$span_checked = ' checked="checked"';
					} elseif ( is_object( $data ) && '0' === $data->event_span ) {
						$span_checked = '';
					}
					?>
					<p>
						<input type="checkbox" value="1" id="e_span" name="event_span" <?php echo $span_checked; ?> />
						<label for="e_span">
						<?php
						_e( 'Selected dates are a single multi-day event.', 'my-calendar' );
						mc_compare_group_members( $group_id, 'event_span' );
						?>
						</label>
					</p>
					<?php
				} else {
					?>
					<div><input type='hidden' name='event_span' value='<?php echo esc_attr( $data->event_span ); ?>' /></div>
					<?php
				}
				if ( mc_show_edit_block( 'event_desc' ) ) {
					?>
					<div id="group_description">
						<label for="content">
						<?php
						_e( 'Event Description', 'my-calendar' );
						mc_compare_group_members( $group_id, 'event_desc' );
						?>
						</label><br/>
						<?php wp_editor( $description, 'content', array( 'textarea_rows' => 10 ) ); ?>
					</div>
					<?php
				}
				if ( mc_show_edit_block( 'event_short' ) ) {
					?>
					<p>
						<label for="e_short">
						<?php
						_e( 'Excerpt', 'my-calendar' );
						mc_compare_group_members( $group_id, 'event_short' );
						?>
						</label><br/>
						<textarea id="e_short" name="event_short" rows="2" cols="80"><?php echo esc_textarea( stripslashes( $short ) ); ?></textarea>
					</p>
					<?php
				}
				if ( mc_show_edit_block( 'event_category' ) ) {
					$match        = mc_compare_group_members( $group_id, 'event_category', false );
					$select       = mc_category_select( $data, true, false );
					$add_category = current_user_can( 'mc_edit_cats' ) ? '<input class="mc-srt" type="checkbox" name="event_category_new" id="event_category_new" value="true" /> <label for="event_category_new" class="button"><span class="dashicons dashicons-plus" aria-hidden="true"></span>' . __( 'Add Categories', 'my-calendar' ) . '</label>' : '';
					$addnew       = '<div class="new-event-category">
					<p><label for="event_category_name">' . __( 'Category Name', 'my-calendar' ) . '</label> <input type="text" value="" id="event_category_name" name="event_category_name" disabled /> <button type="button" class="button add-category">' . __( 'Add Category', 'my-calendar' ) . '</button></p>
				</div>';
					$return       = '<fieldset class="categories"><legend>' . __( 'Categories', 'my-calendar' ) . $match . '</legend><ul class="checkboxes">' . mc_category_select( $data, true, true ) . '<li class="event-new-category"> ' . $add_category . '</li></ul></fieldset>' . $addnew . '<p class="mc-primary-category"><label for="event_category">' . __( 'Primary Category', 'my-calendar' ) . '</label><select name="primary_category" id="e_category">' . $select . '</select></p>';
					echo wp_kses( $return, mc_kses_elements() );
				} else {
					?>
					<div>
						<input type="hidden" name="event_category" value="1" />
					</div>
					<?php
				}
				?>
					</div>
				</div>
			</div>
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2><?php esc_html_e( 'Featured Image', 'my-calendar' ); ?></h2>
					<div class="inside">
					<?php
					if ( mc_show_edit_block( 'event_image' ) ) {
						?>
						<div class='mc-image-upload field-holder'>
							<div class="image_fields">
							<?php
							mc_compare_group_members( $group_id, 'event_image' );
							if ( $has_data && property_exists( $data, 'event_post' ) ) {
								$image    = ( has_post_thumbnail( $data->event_post ) ) ? get_the_post_thumbnail_url( $data->event_post ) : $data->event_image;
								$image_id = ( has_post_thumbnail( $data->event_post ) ) ? get_post_thumbnail_id( $data->event_post ) : '';
							} else {
								$image    = ( $has_data && '' !== $data->event_image ) ? $data->event_image : '';
								$image_id = '';
							}
							$button_text = __( 'Select Featured Image', 'my-calendar' );
							$remove      = '';
							$alt         = '';
							if ( '' !== $image ) {
								$alt         = ( $image_id ) ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';
								$remove      = '<button type="button" class="button remove-image" aria-describedby="event_image">' . esc_html__( 'Remove Featured Image', 'my-calendar' ) . '</button>';
								$button_text = __( 'Change Featured Image', 'my-calendar' );
								$alt         = ( '' === $alt ) ? $data->event_image : $alt;
							}
							?>
							<input type="hidden" name="event_image_id" value="<?php echo esc_attr( $image_id ); ?>" class="textfield" id="e_image_id" /><input type="hidden" name="event_image" id="e_image" value="<?php echo esc_url( $image ); ?>" /> <button type='button' class="button select-image"><?php echo $button_text; ?></button> <?php echo $remove; ?>
							</div>
							<?php
							if ( ! empty( $data->event_image ) ) {
								echo '<div class="event_image" aria-live="assertive"><img id="event_image" src="' . esc_url( $image ) . '" alt="' . __( 'Current image: ', 'my-calendar' ) . esc_attr( $alt ) . '" /></div>';
							} else {
								echo '<div class="event_image" id="event_image"></div>';
							}
							?>
						</div>
					</div>
				</div>
			</div>
						<?php
					} else {
						?>
						<div>
							<input type="hidden" name="event_image" value="<?php echo ( $has_data ) ? esc_attr( $data->event_image ) : ''; ?>" />
							<?php
							if ( ! empty( $data->event_image ) ) {
								echo '<div class="event_image"><img src="' . esc_attr( $data->event_image ) . '" alt="" /></div>';
							}
							?>
						</div>
						<?php
					}
					?>
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2><?php esc_html_e( 'Event Details', 'my-calendar' ); ?></h2>
					<div class="inside">
					<p>
						<label for="e_host">
						<?php
						_e( 'Event Host', 'my-calendar' );
						mc_compare_group_members( $group_id, 'event_host' );
						?>
						</label>
						<select id="e_host" name="event_host">
							<?php
							// Grab hosts and list them.
							$user_list = mc_get_users( 'hosts' );
							foreach ( $user_list as $u ) {
								echo '<option value="' . $u->ID . '"';
								if ( is_object( $data ) && absint( $data->event_host ) === absint( $u->ID ) ) {
									echo ' selected="selected"';
								} elseif ( is_object( $u ) && $u->ID === $user->ID && empty( $data->event_host ) ) {
									echo ' selected="selected"';
								}
								$display_name = ( '' === $u->display_name ) ? $u->user_nicename : $u->display_name;
								echo ">$display_name</option>\n";
							}
							?>
						</select>
					</p>
					<?php
					if ( mc_show_edit_block( 'event_link' ) ) {
						$exp_checked = '';
						if ( is_object( $data ) && '1' === $data->event_link_expires ) {
							$exp_checked = ' checked="checked"';
						} elseif ( is_object( $data ) && '0' === $data->event_link_expires ) {
							$exp_checked = '';
						} elseif ( mc_event_link_expires() ) {
							$exp_checked = ' checked="checked"';
						}
						?>
						<p>
							<label for="e_link">
							<?php
							_e( 'Event Link (Optional)', 'my-calendar' );
							mc_compare_group_members( $group_id, 'event_link' );
							?>
							</label>
							<input type="text" placeholder="https://" id="e_link" name="event_link" size="40" value="<?php echo ( is_object( $data ) ) ? esc_url( $data->event_link ) : ''; ?>" />
							<input type="checkbox" value="1" id="e_link_expires" name="event_link_expires"<?php echo $exp_checked; ?> />
							<label for="e_link_expires"><?php esc_html_e( 'Link will expire after event.', 'my-calendar' ); ?></label>
						</p>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	<?php
	/**
	 * Filter event registration fields.
	 *
	 * @hook mc_event_registration
	 *
	 * @param {string} $event_registration_output HTML output. Default empty.
	 * @param {bool}   $has_data Whether this event has data.
	 * @param {object} $data Event data object.
	 * @param {string} $context Indicates this is running in the admin.
	 *
	 * @return {string}
	 */
	$event_registration_output = apply_filters( 'mc_event_registration', '', $has_data, $data, 'admin' );
	if ( mc_show_edit_block( 'event_open' ) && '' !== $event_registration_output ) {
		?>
		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h2><?php esc_html_e( 'Registration Settings', 'my-calendar' ); ?></h2>

				<div class="inside">
					<fieldset>
						<legend><?php esc_html_e( 'Event Registration Status', 'my-calendar' ); ?></legend>
						<?php echo $event_registration_output; ?>
					</fieldset>
				</div>
			</div>
		</div>
		<?php
	} else {
		?>
	<div>
		<input type="hidden" name="event_tickets" value="<?php echo ( $has_data ) ? esc_attr( $data->event_tickets ) : ''; ?>"/>
		<input type="hidden" name="event_registration" value="<?php echo ( $has_data ) ? esc_attr( $data->event_registration ) : ''; ?>"/>
	</div>
		<?php
	}
	if ( mc_show_edit_block( 'event_location' ) ) {
		?>
	<div class="ui-sortable meta-box-sortables">
		<div class="postbox">
			<h2><?php esc_html_e( 'Event Location', 'my-calendar' ); ?></h2>

			<div class="inside location_form">
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Event Location', 'my-calendar' ); ?></legend>
		<?php echo mc_event_location_dropdown_block( $data ); ?>
				</fieldset>
			</div>
		</div>
	</div>
		<?php
	}
	?>
	</form>
	</div>
	</div>
	<?php
}

/**
 * Check data to be submitted to save
 *
 * @param string $action Type of action.
 * @param array  $post of event data.
 *
 * @return mixed array/object $data checked array or object if error found
 */
function mc_check_group_data( $action, $post ) {
	/**
	 * Filter posted event group data prior to running event data checks.
	 *
	 * @hook mc_groups_pre_checkdata
	 *
	 * @param {array}  $post POST data.
	 * @param {string} $action Type of action running.(add, edit, or copy.)
	 *
	 * @return {array}
	 */
	$post = apply_filters( 'mc_groups_pre_checkdata', $post, $action );
	global $current_user;

	$submit = array();
	if ( version_compare( PHP_VERSION, '7.4', '<' ) && get_magic_quotes_gpc() ) { //phpcs:ignore PHPCompatibility.FunctionUse.RemovedFunctions.get_magic_quotes_gpcDeprecated
		$post = array_map( 'stripslashes_deep', $post );
	}
	if ( ! wp_verify_nonce( $post['event_nonce_name'], 'event_nonce' ) ) {
		return '';
	}
	$errors = '';
	if ( 'add' === $action || 'edit' === $action || 'copy' === $action ) {
		$title   = ! empty( $post['event_title'] ) ? trim( $post['event_title'] ) : '';
		$desc    = ! empty( $post['content'] ) ? trim( $post['content'] ) : '';
		$short   = ! empty( $post['event_short'] ) ? trim( $post['event_short'] ) : '';
		$host    = ! empty( $post['event_host'] ) ? $post['event_host'] : $current_user->ID;
		$cats    = array();
		$primary = 1;
		if ( isset( $post['event_category'] ) ) {
			$cats = $post['event_category'];
			if ( is_array( $cats ) && ! empty( $cats ) ) {
				// Set first category as primary.
				$primary = ( is_numeric( $cats[0] ) ) ? $cats[0] : 1;
				foreach ( $cats as $cat ) {
					$private = mc_get_category_detail( $cat, 'category_private' );
					// If a selected category is private, set that category as primary instead.
					if ( 1 === (int) $private ) {
						$primary = $cat;
					}
				}
			} else {
				$primary = $post['event_category'];
				$cats    = array( $cats );
			}
		}

		$event_link         = ! empty( $post['event_link'] ) ? trim( $post['event_link'] ) : '';
		$expires            = ! empty( $post['event_link_expires'] ) ? $post['event_link_expires'] : '0';
		$location_preset    = ! empty( $post['location_preset'] ) ? $post['location_preset'] : '';
		$event_tickets      = ! empty( $post['event_tickets'] ) ? trim( $post['event_tickets'] ) : '';
		$event_registration = ! empty( $post['event_registration'] ) ? trim( $post['event_registration'] ) : '';
		$event_image        = esc_url_raw( $post['event_image'] );
		$event_span         = ! empty( $post['event_span'] ) ? 1 : 0;

		// We check to make sure the URL is acceptable (blank or starting with http://).
		if ( ! ( '' === $event_link || preg_match( '/^(http)(s?)(:)\/\//', $event_link ) ) ) {
			$event_link = 'https://' . $event_link;
		}
		// A title is required, and can't be more than 255 characters.
		$title_length = strlen( $title );
		if ( ! ( $title_length >= 1 && $title_length <= 255 ) ) {
			$title = __( 'Untitled Event', 'my-calendar' );
		}
		$proceed = true;
		$submit  = array(
			// Begin strings.
			'event_title'        => $title,
			'event_desc'         => $desc,
			'event_short'        => $short,
			'event_link'         => $event_link,
			'event_image'        => $event_image,
			'event_tickets'      => $event_tickets,
			'event_registration' => $event_registration,
			// Begin integers.
			'event_category'     => $primary,
			'event_link_expires' => $expires,
			'event_host'         => $host,
			'event_span'         => $event_span,
			'event_location'     => $location_preset,
			// Array (not saved directly).
			'event_categories'   => $cats,
		);

		$submit = array_map( 'mc_kses_post', $submit );
		$data   = array( $proceed, false, $submit, $errors );
	} else {
		$proceed = false;
		$data    = array( $proceed, false, array(), __( 'Invalid Action', 'my-calendar' ) );
	}

	return $data;
}


/**
 * Used on the manage events admin page to display a list of events
 */
function mc_list_groups() {
	global $wpdb;
	$user = wp_get_current_user()->ID;

	$sortby = ( isset( $_GET['sort'] ) ) ? $_GET['sort'] : mc_get_option( 'default_sort' );
	if ( isset( $_GET['order'] ) ) {
		$sortdir = ( isset( $_GET['order'] ) && 'ASC' === $_GET['order'] ) ? 'ASC' : 'default';
	} else {
		$sortdir = 'default';
	}
	if ( empty( $sortby ) ) {
		$sortbyvalue = 'event_begin';
	} else {
		switch ( $sortby ) {
			case '1':
				$sortbyvalue = 'event_ID';
				break;
			case '2':
				$sortbyvalue = 'event_title';
				break;
			case '3':
				$sortbyvalue = 'event_desc';
				break;
			case '4':
				$sortbyvalue = 'event_begin';
				break;
			case '5':
				$sortbyvalue = 'event_author';
				break;
			case '6':
				$sortbyvalue = 'event_category';
				break;
			case '7':
				$sortbyvalue = 'event_label';
				break;
			case '8':
				$sortbyvalue = 'group_id';
				break;
			default:
				$sortbyvalue = 'event_begin';
		}
	}
	$sortbydirection = ( 'default' === $sortdir ) ? 'DESC' : $sortdir;
	$sort            = ( 'DESC' === $sortbydirection ) ? 'ASC' : 'DESC';

	$current        = empty( $_GET['paged'] ) ? 1 : intval( $_GET['paged'] );
	$screen         = get_current_screen();
	$option         = $screen->get_option( 'per_page', 'option' );
	$items_per_page = get_user_meta( $user, $option, true );
	if ( empty( $items_per_page ) || $items_per_page < 1 ) {
		$items_per_page = $screen->get_option( 'per_page', 'default' );
	}
	$limit = ( isset( $_GET['limit'] ) ) ? $_GET['limit'] : 'published';
	switch ( $limit ) {
		case 'published':
			$limit = 'WHERE event_approved != 2';
			break;
		case 'grouped':
			$limit = 'WHERE event_group_id <> 0 AND event_approved != 2';
			break;
		case 'ungrouped':
			$limit = 'WHERE event_group_id = 0 AND event_approved != 2';
			break;
		default:
			$limit = '';
	}
	$query_limit = ( ( $current - 1 ) * $items_per_page );
	$events      = $wpdb->get_results( $wpdb->prepare( 'SELECT SQL_CALC_FOUND_ROWS * FROM ' . my_calendar_table() . " $limit ORDER BY $sortbyvalue $sortbydirection LIMIT %d, %d", $query_limit, $items_per_page ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	$found_rows  = $wpdb->get_col( 'SELECT FOUND_ROWS();' );
	$items       = $found_rows[0];
	?>
	<div class='inside'>
		<div class="mc-admin-header">
		<ul class="links">
			<li>
				<a <?php echo ( isset( $_GET['limit'] ) && 'grouped' === $_GET['limit'] ) ? ' class="active-link"' : ''; ?> href="<?php echo admin_url( 'admin.php?page=my-calendar-manage&groups=true&amp;limit=grouped#my-calendar-admin-table' ); ?>"><?php esc_html_e( 'Grouped Events', 'my-calendar' ); ?></a>
			</li>
			<li>
				<a <?php echo ( isset( $_GET['limit'] ) && 'ungrouped' === $_GET['limit'] ) ? ' class="active-link"' : ''; ?> href="<?php echo admin_url( 'admin.php?page=my-calendar-manage&groups=true&amp;limit=ungrouped#my-calendar-admin-table' ); ?>"><?php esc_html_e( 'Ungrouped Events', 'my-calendar' ); ?></a>
			</li>
			<li>
				<a <?php echo ( isset( $_GET['limit'] ) && 'all' === $_GET['limit'] || ! isset( $_GET['limit'] ) ) ? ' class="active-link"' : ''; ?> href="<?php echo admin_url( 'admin.php?page=my-calendar-manage&groups=true#my-calendar-admin-table' ); ?>"><?php esc_html_e( 'All', 'my-calendar' ); ?></a>
			</li>
		</ul>
	<?php
	$num_pages = ceil( $items / $items_per_page );
	if ( $num_pages > 1 ) {
		$page_links = paginate_links(
			array(
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'prev_text' => __( '&laquo; Previous<span class="screen-reader-text"> Events</span>', 'my-calendar' ),
				'next_text' => __( 'Next<span class="screen-reader-text"> Events</span> &raquo;', 'my-calendar' ),
				'total'     => $num_pages,
				'current'   => $current,
				'mid_size'  => 1,
			)
		);
		printf( "<div class='tablenav'><div class='tablenav-pages'>%s</div></div>", $page_links );
	}
	?>
		</div>
	<?php
	if ( ! empty( $events ) ) {
		?>
		<form action="<?php echo admin_url( 'admin.php?page=my-calendar-manage&groups=true' ); ?>" method="post">
			<div>
				<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
				<input type="hidden" name="event_action" value="group"/>
			</div>
			<p class="mc-group-buttons mc-actions">
				<input type="submit" class="button-primary group" value="<?php _e( 'Group events', 'my-calendar' ); ?>" />
			</p>
			<table class="widefat wp-list-table mc-groups-table" id="my-calendar-admin-table">
				<caption class="screen-reader-text"><?php esc_html_e( 'Grouped Events list. Use column headers to sort.', 'my-calendar' ); ?></caption>
				<thead>
					<tr>
						<?php
						$admin_url = admin_url( "admin.php?page=my-calendar-manage&groups=true&order=$sort&paged=$current" );
						$url       = add_query_arg( 'sort', '1', $admin_url );
						$col_head  = mc_table_header( __( 'ID', 'my-calendar' ), $sort, $sortby, '1', $url );
						$url       = add_query_arg( 'sort', '8', $admin_url );
						$col_head .= mc_table_header( __( 'Group', 'my-calendar' ), $sort, $sortby, '8', $url );
						$url       = add_query_arg( 'sort', '2', $admin_url );
						$col_head .= mc_table_header( __( 'Title', 'my-calendar' ), $sort, $sortby, '2', $url );
						$url       = add_query_arg( 'sort', '7', $admin_url );
						$col_head .= mc_table_header( __( 'Location', 'my-calendar' ), $sort, $sortby, '7', $url );
						$url       = add_query_arg( 'sort', '4', $admin_url );
						$col_head .= mc_table_header( __( 'Date/Time', 'my-calendar' ), $sort, $sortby, '4', $url );
						$url       = add_query_arg( 'sort', '5', $admin_url );
						$col_head .= mc_table_header( __( 'Author', 'my-calendar' ), $sort, $sortby, '5', $url );
						$url       = add_query_arg( 'sort', '6', $admin_url );
						$col_head .= mc_table_header( __( 'Category', 'my-calendar' ), $sort, $sortby, '6', $url );
						echo $col_head;
						?>
					</tr>
				</thead>
				<?php
				$class = '';
				foreach ( $events as $event ) {
					$is_grouped = mc_event_is_grouped( $event->event_group_id );
					$class      = ( 'alternate' === $class ) ? 'even' : 'alternate';
					$spam       = ( '1' === $event->event_flagged ) ? ' spam' : '';
					$spam_label = ( '1' === $event->event_flagged ) ? '<strong>Possible spam:</strong> ' : '';
					$author     = ( '0' !== $event->event_author ) ? get_userdata( $event->event_author ) : 'Public Submitter';
					$can_edit   = mc_can_edit_event( $event );
					if ( '' !== trim( $event->event_link ) ) {
						$title = "<a href='" . esc_attr( $event->event_link ) . "'>" . strip_tags( $event->event_title, mc_strip_tags() ) . '</a>';
					} else {
						$title = $event->event_title;
					}
					?>
				<tr class="<?php echo "$class $spam"; ?>" id="event<?php echo $event->event_id; ?>">
					<th scope="row">
						<input type="checkbox" aria-describedby="event_<?php echo $event->event_id; ?>" value="<?php echo $event->event_id; ?>" name="group[]" id="mc<?php echo $event->event_id; ?>" <?php echo ( $is_grouped ) ? ' disabled="disabled"' : ''; ?> />
						<label for="mc<?php echo $event->event_id; ?>"><span class="screen-reader-text"><?php esc_html_e( 'Group event', 'my-calendar' ); ?></span><?php echo $event->event_id; ?></label>
					</th>
					<th scope="row">
						<?php echo ( '0' === $event->event_group_id ) ? '-' : $event->event_group_id; ?>
					</th>
					<td>
						<strong>
						<?php
						$edit_link = '';
						if ( $can_edit ) {
							if ( $is_grouped ) {
								$edit_link = admin_url( "admin.php?page=my-calendar-manage&groups=true&amp;mode=edit&amp;event_id=$event->event_id&amp;group_id=$event->event_group_id" );
							}
							if ( $edit_link ) {
								echo '<a href="' . esc_url( $edit_link ) . '" class="edit">';
							}
						}
						echo $spam_label;
						echo '<span id="event_' . $event->event_id . '">' . strip_tags( stripslashes( $title ) ) . '</span>';
						if ( $can_edit && $edit_link ) {
							echo '</a>';
						}
						if ( ! $is_grouped ) {
							echo ' - <em>' . __( 'Ungrouped', 'my-calendar' ) . '</em>';

						}
						?>
						</strong>

						<div class='row-actions'>
							<?php
							if ( $can_edit ) {
								?>
								<a href="<?php echo admin_url( "admin.php?page=my-calendar&amp;mode=edit&amp;event_id=$event->event_id" ); ?>" class='edit' aria-describedby='event_<?php echo $event->event_id; ?>'><?php esc_html_e( 'Edit Event', 'my-calendar' ); ?></a>
								<?php
								if ( $is_grouped ) {
									?>
									| <a href="<?php echo admin_url( "admin.php?page=my-calendar-manage&groups=true&amp;mode=edit&amp;event_id=$event->event_id&amp;group_id=$event->event_group_id" ); ?>" class='edit group'><?php esc_html_e( 'Edit Group', 'my-calendar' ); ?></a>
									<?php
								}
							} else {
								_e( 'Not editable.', 'my-calendar' );
							}
							?>
						</div>
					</td>
					<td>
					<?php
					$elabel = '';
					if ( property_exists( $event, 'location' ) && is_object( $event->location ) ) {
						$elabel = $event->location->location_label;
					}
					echo strip_tags( stripslashes( $elabel ) );
					?>
					</td>
					<?php
					if ( '23:59:59' !== $event->event_endtime ) {
						$event_time = date_i18n( mc_time_format(), strtotime( $event->event_time ) );
					} else {
						$event_time = mc_notime_label( $event );
					}
					?>
					<td>
					<?php
						$begin = date_i18n( mc_date_format(), strtotime( $event->event_begin ) );
						echo esc_html( "$begin, $event_time" );
					?>
						<div class="recurs">
							<?php echo mc_recur_string( $event ); ?>
						</div>
					</td>
					<td><?php echo ( is_object( $author ) ) ? $author->display_name : $author; ?></td>
					<td>
					<?php echo mc_admin_category_list( $event ); ?>
					</td>
					</tr>
					<?php
				}
				?>
			</table>
		<div class="mc-controls footer">
			<p class="mc-actions mc-group-buttons">
				<input type="submit" class="button-primary group" value="<?php _e( 'Group events', 'my-calendar' ); ?>"/>
			</p>
		</div>
		</form>
		<?php
	} else {
		?>
		<p class="mc-none"><?php esc_html_e( 'No events found.', 'my-calendar' ); ?></p>
		<?php
	}
	echo '</div>';
}
