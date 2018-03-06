<?php
/**
 * Manage My Calendar events groups
 *
 * @category Events
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate the Grouped event editing form
 */
function my_calendar_group_edit() {
	global $wpdb;
	// First some quick cleaning up
	$action   = ! empty( $_POST['event_action'] ) ? $_POST['event_action'] : '';
	$event_id = ! empty( $_POST['event_id'] ) ? $_POST['event_id'] : '';
	$group_id = ! empty( $_POST['group_id'] ) ? $_POST['group_id'] : '';

	if ( isset( $_GET['mode'] ) ) {
		if ( $_GET['mode'] == 'edit' ) {
			$action   = "edit";
			$event_id = (int) $_GET['event_id'];
			$group_id = (int) $_GET['group_id'];
		}
	}

	if ( isset( $_POST['event_action'] ) ) {
		global $mc_output;
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( "Security check failed" );
		}
		switch ( $_POST['event_action'] ) {
			case 'edit':
				if ( isset( $_POST['apply'] ) && is_array( $_POST['apply'] ) ) {
					$mc_output = mc_check_group_data( $action, $_POST );
					foreach ( $_POST['apply'] as $event_id ) {
						$response = my_calendar_save_group( $action, $mc_output, $event_id );
						echo $response;
					}
				}
				break;
			case 'break':
				foreach ( $_POST['break'] as $event_id ) {
					$update  = array( 'event_group_id' => 0 );
					$formats = array( '%d' );

					$result = $wpdb->update(
						my_calendar_table(),
						$update,
						array( 'event_id' => $event_id ),
						$formats,
						'%d' );

					$url = sprintf( __( 'View <a href="%s">your calendar</a>.', 'my-calendar' ), mc_get_uri() );
					if ( $result === false ) {
						$message = "<div class='error'><p><strong>" . __( 'Error', 'my-calendar' ) . ":</strong>" . __( 'Event not updated.', 'my-calendar' ) . " $url</p></div>";
					} elseif ( $result === 0 ) {
						$message = "<div class='updated'><p>#$event_id: " . __( 'Nothing was changed in that update.', 'my-calendar' ) . "  $url</p></div>";
					} else {
						$message = "<div class='updated'><p>#$event_id: " . __( 'Event updated successfully', 'my-calendar' ) . ". $url</p></div>";
					}
				}
				break;
			case 'group':
				if ( isset( $_POST['group'] ) && is_array( $_POST['group'] ) ) {
					$events = $_POST['group'];
					sort( $events );
					foreach ( $events as $event_id ) {
						$group_id = $events[0];
						$update   = array( 'event_group_id' => $group_id );
						$formats  = array( '%d' );

						$result = $wpdb->update(
							my_calendar_table(),
							$update,
							array( 'event_id' => $event_id ),
							$formats,
							'%d' );

						if ( $result === false ) {
							$message = "<div class='error'><p><strong>" . __( 'Error', 'my-calendar' ) . ":</strong>" . __( 'Event not grouped.', 'my-calendar' ) . "</p></div>";
						} elseif ( $result === 0 ) {
							$message = "<div class='updated'><p>#$event_id: " . __( 'Nothing was changed in that update.', 'my-calendar' ) . "</p></div>";
						} else {
							$message = "<div class='updated'><p>#$event_id: " . __( 'Event grouped successfully', 'my-calendar' ) . "</p></div>";
						}
					}
				}
				break;
		}
	} ?>

	<div class="wrap my-calendar-admin" id="my-calendar"><?php
	my_calendar_check_db();
	if ( $action == 'edit' ) {
		?>
		<h1><?php _e( 'Edit Event Group', 'my-calendar' ); ?></h1>
		<?php
		if ( empty( $event_id ) || empty( $group_id ) ) {
			echo "
			<div class=\"error\">
				<p>" . __( "You must provide an event group id in order to edit it", 'my-calendar' ) . "</p>
			</div>";
		} else {
			mc_edit_groups( 'edit', $event_id, $group_id );
		}
	} else {
		?>
		<h1><?php _e( 'Manage Event Groups', 'my-calendar' ); ?></h1>
		<p>
			<?php _e( 'When you choose a group of events to edit, the form will be pre-filled with the content from the event you started from. You will also see a set of checkboxes to choose which events you want to apply these changes to.', 'my-calendar' ); ?>
		</p>

		<div class="postbox-container jcd-wide">
		<div class="metabox-holder">
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2><?php _e( 'Manage Event Groups', 'my-calendar' ); ?></h2>

					<div class="inside">
						<p><?php _e( 'Select an event group to edit.', 'my-calendar' ); ?></p>
					</div>
				</div>
			</div>
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2><?php _e( 'Create/Modify Groups', 'my-calendar' ); ?></h2>
					<?php mc_list_groups(); ?>
				</div>
			</div>
		</div>
		</div><?php
	}
	mc_show_sidebar(); ?>
	</div><?php
}

/**
 * Save data within a group of events.
 *
 * @param string $action Type of action: add, edit
 * @param array $output Data and status of data check
 * @param int $event_id Event ID 
 *
 * @return message
 */
function my_calendar_save_group( $action, $output, $event_id = false ) {
	global $wpdb, $event_author;
	$proceed = $output[0];
	$message = '';
	if ( $action == 'edit' && $proceed == true ) {
		$event_author = (int) ( $_POST['event_author'] );
		if ( mc_can_edit_event( $event_id ) ) {
			$update  = $output[2];
			$cats     = $update['event_categories'];
			unset( $update['event_categories'] );	
			mc_update_category_relationships( $cats, $event_id );					
			
			$update  = apply_filters( 'mc_update_group_data', $update, $event_author, $action, $event_id );
			$formats = array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%d',
				'%f',
				'%f'
			);

			$result = $wpdb->update(
				my_calendar_table(),
				$update,
				array( 'event_id' => $event_id ),
				$formats,
				'%d' );

			$url = sprintf( __( 'View <a href="%s">your calendar</a>.', 'my-calendar' ), mc_get_uri() );
			do_action( 'mc_save_event', 'edit', $update, $event_id, $result ); // same as action on basic save
			do_action( 'mc_save_grouped_events', $result, $event_id, $update );
			if ( $result === false ) {
				$message = "<div class='error'><p><strong>#$event_id; " . __( 'Error', 'my-calendar' ) . ":</strong>" . __( 'Your event was not updated.', 'my-calendar' ) . " $url</p></div>";
			} elseif ( $result === 0 ) {
				$message = "<div class='updated'><p>#$event_id: " . __( 'Nothing was changed in that update.', 'my-calendar' ) . " $url</p></div>";
			} else {
				$message = "<div class='updated'><p>#$event_id: " . __( 'Event updated successfully', 'my-calendar' ) . ". $url</p></div>";
			}
		} else {
			$message = "<div class='error'><p><strong>#$event_id: " . __( 'You do not have sufficient permissions to edit that event.', 'my-calendar' ) . "</strong></p></div>";
		}
	}
	$message = $message . "\n" . $output[3];

	return $message;
}

/**
 * Get event data for a group. Sourced from the passed event ID.
 *
 * @param int $event_id
 *
 * @return mixed string/array/object
 */
function mc_group_data( $event_id = false ) {
	global $wpdb, $submission;
	if ( $event_id !== false ) {
		if ( intval( $event_id ) != $event_id ) {
			return "<div class=\"error\"><p>" . __( 'Sorry! That\'s an invalid event key.', 'my-calendar' ) . "</p></div>";
		} else {
			$data = $wpdb->get_results( "SELECT * FROM " . my_calendar_table() . " WHERE event_id='" . (int) $event_id . "' LIMIT 1" );
			if ( empty( $data ) ) {
				return "<div class=\"error\"><p>" . __( "Sorry! We couldn't find an event with that ID.", 'my-calendar' ) . "</p></div>";
			}
			$data = $data[0];
		}
		// Recover users entries if they exist; in other words if editing an event went wrong
		if ( ! empty( $submission ) ) {
			$data = $submission;
		}
	} else {
		// Deal with possibility that form was submitted but not saved due to error - recover user's entries here
		$data = $submission;
	}

	return $data;
}

/**
 * Compare events within a group to see if they currently have the same information.
 *
 * @param int $group_id Group ID
 * @param string $field Column name of field to compare. Optional.
 *
 * @return boolean True of information is the same.
 */
function mc_compare_group_members( $group_id, $field = false ) {
	global $wpdb;
	if ( ! $field ) {
		$query = "SELECT event_title, event_desc, event_short, event_link, event_label,
					event_street, event_street2, event_city, event_state, event_postcode, 
					event_region, event_country, event_url, event_image, event_category, 
					event_link_expires, event_zoom, event_phone, event_host, event_longitude, event_latitude 
			  FROM " . my_calendar_table() . " WHERE event_group_id = $group_id";
	} else {
		// just comparing a single field
		$query = "SELECT $field FROM " . my_calendar_table() . " WHERE event_group_id = $group_id";
	}
	$results = $wpdb->get_results( $query, ARRAY_N );
	$count   = count( $results );
	for ( $i = 0; $i < $count; $i ++ ) {
		$n = ( ( $i + 1 ) > $count - 1 ) ? 0 : $i + 1;
		if ( md5( implode( '', $results[ $i ] ) ) != md5( implode( '', $results[ $n ] ) ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Show members of group and provide options to select which to edit.
 *
 * @param int $group_id Group ID
 * @param string $type Context of form.
 *
 * @return string form
 */
function mc_group_form( $group_id, $type = 'break' ) {
	global $wpdb;
	$event_id = (int) $_GET['event_id'];
	$nonce    = wp_create_nonce( 'my-calendar-nonce' );
	$query    = $wpdb->prepare( "SELECT event_id, event_begin, event_time FROM " . my_calendar_table() . " WHERE event_group_id = %d", $group_id );
	$results  = $wpdb->get_results( $query );
	if ( $type == 'apply' ) {
		$warning = ( ! mc_compare_group_members( $group_id ) ) ? "<p class='warning'>" . __( '<strong>NOTE:</strong> The group editable fields for the events in this group do not match', 'my-calendar' ) . "</p>" : '<p>' . __( 'The group editable fields for the events in this group match.', 'my-calendar' ) . '</p>';
	} else {
		$warning = '';
	}
	$class  = ( $type == 'break' ) ? 'break' : 'apply';
	$group  = "<div class='group $class'>";
	$group .= $warning;
	$group .= ( $type == 'apply' ) ? "<fieldset><legend>" . __( 'Apply these changes to:', 'my-calendar' ) . "</legend>" : '';
	$group .= ( $type == 'break' ) ? "<form method='post' action='" . admin_url( "admin.php?page=my-calendar-groups&amp;mode=edit&amp;event_id=$event_id&amp;group_id=$group_id" ) . "'>
	<div><input type='hidden' value='" . esc_attr( $group_id ) . "' name='group_id' /><input type='hidden' value='" . esc_attr( $type ) . "' name='event_action' /><input type='hidden' name='_wpnonce' value='$nonce' />
	</div>" : '';
	$group  .= "<ul class='checkboxes'>";
	$checked = ( $type == 'apply' ) ? ' checked="checked"' : '';
	foreach ( $results as $result ) {
		$date = date_i18n( 'D, j M, Y', strtotime( $result->event_begin ) );
		$time = date_i18n( 'g:i a', strtotime( $result->event_time ) );
		$group .= "<li><input type='checkbox' name='$type" . "[]' value='$result->event_id' id='$type$result->event_id'$checked /> <label for='break$result->event_id'><a href='#event$result->event_id'>#$result->event_id</a>: $date; $time</label></li>\n";
	}
	$group .= "<li><input type='checkbox' class='selectall' id='$type'$checked /> <label for='$type'><b>" . __( 'Check/Uncheck all', 'my-calendar' ) . "</b></label></li>\n</ul>";
	$group .= ( $type == 'apply' ) ? "</fieldset>" : '';
	$group .= ( $type == 'break' ) ? "<p><input type='submit' class='button' value='" . __( 'Remove checked events from this group', 'my-calendar' ) . "' /></p></form>" : '';
	$group .= "</div>";

	return $group;
}

/**
 * The event edit form for the manage events admin page
 * 
 * @param string mode
 *
 */
function mc_edit_groups( $mode = 'edit', $event_id = false, $group_id = false ) {
	global $submission;
	$message = $group = '';
	if ( $event_id != false ) {
		$data = mc_group_data( $event_id );
	} else {
		$data = $submission;
	}
	if ( $group_id != false ) {
		$group = mc_group_form( $group_id, 'break' );
	} else {
		$message .= __( 'You must provide a group ID to edit groups', 'my-calendar' );
	}
	echo ( $message != '' ) ? "<div class='error'><p>$message</p></div>" : '';
	echo $group;
	
	my_calendar_print_group_fields( $data, $mode, $event_id, $group_id );
}

function my_calendar_print_group_fields( $data, $mode, $event_id, $group_id = '' ) {
	global $user_ID;
	$current_user = wp_get_current_user();
	$has_data     = ( empty( $data ) ) ? false : true;
	$user         = get_userdata( $user_ID );
	$input_all    = ( get_option( 'mc_input_options_administrators' ) == 'true' && current_user_can( 'manage_options' ) ) ? true : false;
	$mc_input     = get_option( 'mc_input_options' );
	$group_id     = ( ! empty( $data->event_group_id ) ) ? $data->event_group_id : mc_group_id();
	
	$title = $description = $short = $image = '';
	
	if ( ! empty( $data ) ) {
		$title       = stripslashes( $data->event_title );
		$description = stripslashes( $data->event_desc );
		$short       = stripslashes( $data->event_short );
		$image       = $data->event_image;
	}
	?>
	<div class="postbox-container jcd-wide">
	<div class="metabox-holder">
	<form method="post"
	      action="<?php echo admin_url( "admin.php?page=my-calendar-groups&amp;mode=edit&amp;event_id=$event_id&amp;group_id=$group_id" ); ?>">
	<div>
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
		<input type="hidden" name="group_id" value="<?php echo absint( $group_id ); ?>"/>
		<input type="hidden" name="event_action" value="<?php echo esc_attr( $mode ); ?>"/>
		<input type="hidden" name="event_id" value="<?php echo absint( $event_id ); ?>"/>
		<input type="hidden" name="event_author" value="<?php echo absint( $user_ID ); ?>"/>
		<input type="hidden" name="event_post" value="<?php echo absint( $data->event_post ); ?>"/>
		<input type="hidden" name="event_nonce_name" value="<?php echo wp_create_nonce( 'event_nonce' ); ?>"/>
	</div>	
	<div class="ui-sortable meta-box-sortables">
		<div class="postbox">
			<h2><?php _e( 'Manage Event Groups', 'my-calendar' ); ?></h2>

			<div class="inside">
				<div class="mc-controls">
					<ul><li><input type="submit" name="save" class="button-primary" value="<?php _e( 'Edit Event Group', 'my-calendar' ); ?>"/></li></ul>
				</div>	
				<p>
					<label for="e_title"><?php _e( 'Event Title', 'my-calendar' ); ?>
						<span><?php _e( '(required)', 'my-calendar' ); ?></span><?php if ( ! mc_compare_group_members( $group_id, 'event_title' ) ) {
							echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
						} ?></label><br/>
						<input type="text" id="e_title" name="event_title" size="60" value="<?php esc_attr_e( $title ); ?>" />
				</p>
				<?php
				$apply = mc_group_form( $group_id, 'apply' );
				echo $apply;
				if ( $data->event_repeats == 0 && ( $data->event_recur == 'S1' || $data->event_recur == 'S' ) ) {
					?>
					<p>
						<input type="checkbox" value="1" id="e_span" name="event_span"
						<?php 
						if ( ! empty( $data ) && $data->event_span == '1' ) {
							echo " checked=\"checked\"";
						} elseif ( ! empty( $data ) && $data->event_span == '0' ) {
							echo "";
						} elseif ( get_option( 'mc_event_span' ) == 'true' ) {
							echo " checked=\"checked\"";
						} 
						?> /> <label for="e_span"><?php _e( 'Selected dates are a single multi-day event.', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_span' ) ) {
								echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
						} ?></label>
					</p>
				<?php 
				} else { 
				?>
					<div><input type='hidden' name='event_span' value='<?php esc_attr_e( $data->event_span ); ?>'/></div>
				<?php 
				}
				if ( $mc_input['event_desc'] == 'on' || $input_all ) { ?>
					<div id="group_description">
						<label for="content"><?php _e( 'Event Description (<abbr title="hypertext markup language">HTML</abbr> allowed)', 'my-calendar' );
							if ( ! mc_compare_group_members( $group_id, 'event_desc' ) ) {
								echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
							} ?></label><br/><?php
						wp_editor( esc_attr( $description ), 'content', array( 'textarea_rows' => 10 ) ); ?>
					</div>
				<?php 
				}
				if ( $mc_input['event_short'] == 'on' || $input_all ) { ?>
					<p>
						<label
							for="e_short"><?php _e( 'Event Short Description (<abbr title="hypertext markup language">HTML</abbr> allowed)', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_short' ) ) {
								echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
							} 
							?></label><br/>
							<textarea id="e_short" name="event_short" rows="2" cols="80"><?php esc_attr_e( $short ); ?></textarea>
					</p>
				<?php
				}
				if ( mc_show_edit_block( 'event_image' ) ) {
					?>
					<div class='mc-image-upload field-holder'>
						<?php if ( ! empty( $data->event_image ) ) { ?>
							<div class="event_image"><img src="<?php echo esc_url( $image ); ?>" alt="" /></div>
						<?php } else { ?>
							<div class="event_image"></div>
						<?php } ?>
						<input type="hidden" name="event_image_id" value="" class="textfield" id="e_image_id"/>
						<label
							for="e_image"><?php _e( "Add an image:", 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_image' ) ) {
								echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
							} 
							?></label> <input type="text" name="event_image" id="e_image" size="60" value="<?php echo esc_url( $image ); ?>" placeholder="http://yourdomain.com/image.jpg"/> <a href="#" class="button textfield-field"><?php _e( "Upload", 'my-calendar' ); ?></a>
					</div>
				<?php 
				} else { 
				?>
					<div>
						<input type="hidden" name="event_image"
							   value="<?php if ( $has_data ) {
								   echo esc_attr( $data->event_image );
							   } ?>"/>
						<?php if ( ! empty( $data->event_image ) ) { ?>
							<div class="event_image"><img src="<?php echo esc_attr( $data->event_image ); ?>"
														  alt=""/>
							</div>
						<?php } ?>
					</div>
				<?php 
				} 
				?>
				<p>
					<label for="e_host"><?php _e( 'Event Host', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_host' ) ) {
							echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
					} 
					?></label>
					<select id="e_host" name="event_host">
						<?php
						// Grab hosts and list them
						$userList = mc_get_users( 'hosts' );
						foreach ( $userList as $u ) {
							echo '<option value="' . $u->ID . '"';
							if ( is_object( $data ) && $data->event_host == $u->ID ) {
								echo ' selected="selected"';
							} elseif ( is_object( $u ) && $u->ID == $user->ID && empty( $data->event_host ) ) {
								echo ' selected="selected"';
							}
							$display_name = ( $u->display_name == '' ) ? $u->user_nicename : $u->display_name;
							echo ">$display_name</option>\n";
						}
						?>
					</select>
				</p>
				<?php 
				if ( $mc_input['event_category'] == 'on' || $input_all ) {
					$match = '';
					if ( ! mc_compare_group_members( $group_id, 'event_category' ) ) {
						$match = " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
					}			
					
					if ( get_option( 'mc_multiple_categories' ) !== 'true' ) {
						$select   = mc_category_select( $data, true, false );		
						$return   = '
							<p class="mc_category">
								<label for="event_category">' . __( 'Category', 'my-calendar-submissions' ) . $match . '</label>
								<select class="widefat" name="event_category" id="e_category">' .
									$select
								.'</select>
							</p>';
					} else {
						$return = '<fieldset><legend>' . __( 'Categories', 'my-calendar' ) . $match . '</legend><ul class="checkboxes">' .
							mc_category_select( $data, true, true ) . '
						</ul></fieldset>';
					}					
					
					echo $return;
				} else { ?>
					<div>
						<input type="hidden" name="event_category" value="1"/>
					</div>
				<?php 
				} 
				if ( $mc_input['event_link'] == 'on' || $input_all ) { ?>
					<p>
						<label
							for="e_link"><?php _e( 'Event Link (Optional)', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_link' ) ) {
								echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
							} ?></label> <input type="text" id="e_link" name="event_link" size="40"
												value="<?php if ( ! empty( $data ) ) {
													echo esc_url( $data->event_link );
												} ?>"/> <input type="checkbox" value="1" id="e_link_expires"
															   name="event_link_expires"<?php if ( ! empty( $data ) && $data->event_link_expires == '1' ) {
							echo " checked=\"checked\"";
						} elseif ( ! empty( $data ) && $data->event_link_expires == '0' ) {
							echo "";
						} elseif ( get_option( 'mc_event_link_expires' ) == 'true' ) {
							echo " checked=\"checked\"";
						} ?> /> <label
							for="e_link_expires"><?php _e( 'Link will expire after event.', 'my-calendar' ); ?></label>
					</p>
				<?php 
				} 
				?>
			</div>
		</div>
	</div>
	<?php 
	if ( $mc_input['event_open'] == 'on' || $input_all ) { // add a "don't change" option here ?>
		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h2><?php _e( 'Event Registration Options', 'my-calendar' ); ?></h2>

				<div class="inside">
					<fieldset>
						<legend><?php _e( 'Event Registration Status', 'my-calendar' ); ?></legend>
						<?php echo apply_filters( 'mc_event_registration', '', $has_data, $data, 'admin' ); ?>
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
	if (( $mc_input['event_location'] == 'on' || $mc_input['event_location_dropdown'] == 'on' ) || $input_all) { 
	?>

	<div class="ui-sortable meta-box-sortables">
		<div class="postbox">
			<h2><?php _e( 'Event Location', 'my-calendar' ); ?></h2>

			<div class="inside location_form">
				<fieldset>
					<legend><?php _e( 'Event Location', 'my-calendar' ); ?></legend>
					<?php 
					} 
					if ( $mc_input['event_location_dropdown'] == 'on' || $input_all ) {
						$locations = mc_get_locations( 'group-manager' );

						if ( ! empty( $locations ) ) {
							?>
							<p>
								<label
									for="location_preset"><?php _e( 'Choose a preset location:', 'my-calendar' ); ?></label>
								<select name="location_preset" id="location_preset">
									<option value="none"> --</option>
									<?php
									foreach ( $locations as $location ) {
										echo "<option value=\"" . $location->location_id . "\">" . esc_html( stripslashes( $location->location_label ) ) . "</option>";
									}
									?>
								</select>
							</p>
						<?php
						} else {
						?>
							<input type="hidden" name="location_preset" value="none"/>
							<p>
								<a href="<?php echo admin_url( "admin.php?page=my-calendar-locations" ); ?>"><?php _e( 'Add recurring locations for later use.', 'my-calendar' ); ?></a>
							</p>
						<?php
						}
					} else { ?>
						<input type="hidden" name="location_preset" value="none"/>
					<?php 
					}
					if ( $mc_input['event_location'] == 'on' || $input_all ) { ?>
						<p>
							<label
								for="e_label"><?php _e( 'Name of Location (e.g. <em>Joe\'s Bar and Grill</em>)', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_label' ) ) {
									echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
								} ?></label><br/><input type="text" id="e_label" name="event_label" size="40"
							                            value="<?php if ( ! empty( $data ) ) {
								                            esc_attr_e( stripslashes( $data->event_label ) );
							                            } ?>"/>
						</p>
						<p>
							<label
								for="e_street"><?php _e( 'Street Address', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_street' ) ) {
									echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
								} ?></label> <input type="text" id="e_street" name="event_street" size="40"
							                        value="<?php if ( ! empty( $data ) ) {
								                        esc_attr_e( stripslashes( $data->event_street ) );
							                        } ?>"/>
						</p>
						<p>
							<label
								for="e_street2"><?php _e( 'Street Address (2)', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_street2' ) ) {
									echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
								} ?></label> <input type="text" id="e_street2" name="event_street2" size="40"
							                        value="<?php if ( ! empty( $data ) ) {
								                        esc_attr_e( stripslashes( $data->event_street2 ) );
							                        } ?>"/>
						</p>
						<p>
							<label
								for="e_city"><?php _e( 'City', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_city' ) ) {
									echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
								} ?></label> <input type="text" id="e_city" name="event_city" size="40"
							                        value="<?php if ( ! empty( $data ) ) {
								                        esc_attr_e( stripslashes( $data->event_city ) );
							                        } ?>"/>
							<label
								for="e_state"><?php _e( 'State/Province', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_state' ) ) {
									echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
								} ?></label> <input type="text" id="e_state" name="event_state" size="10"
							                        value="<?php if ( ! empty( $data ) ) {
								                        esc_attr_e( stripslashes( $data->event_state ) );
							                        } ?>"/>
						</p>
						<p>
							<label
								for="e_postcode"><?php _e( 'Postal Code', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_postcode' ) ) {
									echo " <span>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
								} ?></label> <input type="text" id="e_postcode" name="event_postcode" size="10"
							                        value="<?php if ( ! empty( $data ) ) {
								                        esc_attr_e( stripslashes( $data->event_postcode ) );
							                        } ?>"/>
							<label
								for="e_region"><?php _e( 'Region', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_region' ) ) {
									echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
								} ?></label> <input type="text" id="e_region" name="event_region" size="40"
							                        value="<?php if ( ! empty( $data ) ) {
								                        esc_attr_e( stripslashes( $data->event_region ) );
							                        } ?>"/>
						</p>
						<p>
							<label
								for="e_country"><?php _e( 'Country', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_country' ) ) {
									echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
								} ?></label> <input type="text" id="e_country" name="event_country" size="10"
							                        value="<?php if ( ! empty( $data ) ) {
								                        esc_attr_e( stripslashes( $data->event_country ) );
							                        } ?>"/>
						</p>
						<p>
							<label
								for="e_zoom"><?php _e( 'Initial Zoom', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_zoom' ) ) {
									echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
								} ?></label>
							<select name="event_zoom" id="e_zoom">
								<option value="16"<?php if ( ! empty( $data ) && ( $data->event_zoom == 16 ) ) {
									echo " selected=\"selected\"";
								} ?>><?php _e( 'Neighborhood', 'my-calendar' ); ?></option>
								<option value="14"<?php if ( ! empty( $data ) && ( $data->event_zoom == 14 ) ) {
									echo " selected=\"selected\"";
								} ?>><?php _e( 'Small City', 'my-calendar' ); ?></option>
								<option value="12"<?php if ( ! empty( $data ) && ( $data->event_zoom == 12 ) ) {
									echo " selected=\"selected\"";
								} ?>><?php _e( 'Large City', 'my-calendar' ); ?></option>
								<option value="10"<?php if ( ! empty( $data ) && ( $data->event_zoom == 10 ) ) {
									echo " selected=\"selected\"";
								} ?>><?php _e( 'Greater Metro Area', 'my-calendar' ); ?></option>
								<option value="8"<?php if ( ! empty( $data ) && ( $data->event_zoom == 8 ) ) {
									echo " selected=\"selected\"";
								} ?>><?php _e( 'State', 'my-calendar' ); ?></option>
								<option value="6"<?php if ( ! empty( $data ) && ( $data->event_zoom == 6 ) ) {
									echo " selected=\"selected\"";
								} ?>><?php _e( 'Region', 'my-calendar' ); ?></option>
							</select>
						</p>
						<p>
							<label
								for="e_phone"><?php _e( 'Phone', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_phone' ) ) {
									echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
								} ?></label> <input type="text" id="e_phone" name="event_phone" size="32"
							                        value="<?php if ( ! empty( $data ) ) {
								                        esc_attr_e( stripslashes( $data->event_phone ) );
							                        } ?>"/>
						</p>
						<p>
							<label
								for="e_url"><?php _e( 'Location URL', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_url' ) ) {
									echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
								} ?></label> <input type="text" id="e_url" name="event_url" size="40"
							                        value="<?php if ( ! empty( $data ) ) {
								                        esc_attr_e( stripslashes( $data->event_url ) );
							                        } ?>"/>
						</p>
						<fieldset>
							<legend><?php _e( 'GPS Coordinates (optional)', 'my-calendar' ); ?></legend>
							<p>
								<label
									for="e_latitude"><?php _e( 'Latitude', 'my-calendar' ); ?><?php if ( ! mc_compare_group_members( $group_id, 'event_latitude' ) ) {
										echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
									} ?><?php if ( ! mc_compare_group_members( $group_id, 'event_longitude' ) ) {
										echo " <span class='nomatch'>" . __( 'Fields do not match', 'my-calendar' ) . "</span>";
									} ?></label> <input type="text" id="e_latitude" name="event_latitude" size="10"
								                        value="<?php if ( ! empty( $data ) ) {
									                        esc_attr_e( stripslashes( $data->event_latitude ) );
								                        } ?>"/>
								<label for="e_longitude"><?php _e( 'Longitude', 'my-calendar' ); ?></label> <input
									type="text" id="e_longitude" name="event_longitude" size="10"
									value="<?php if ( ! empty( $data ) ) {
										esc_attr_e( stripslashes( $data->event_longitude ) );
									} ?>"/>
							</p>
						</fieldset>
						<fieldset>
							<legend><?php _e( 'Location Accessibility', 'my-calendar' ); ?></legend>
							<ul class='checkboxes'>
								<?php
								$access      = apply_filters( 'mc_venue_accessibility', mc_location_access() );
								$access_list = '';
								if ( ! empty( $data ) ) {
									$location_access = unserialize( $data->event_access );
								} else {
									$location_access = array();
								}
								foreach ( $access as $k => $a ) {
									$id      = "loc_access_$k";
									$label   = $a;
									$checked = '';
									if ( is_array( $location_access ) ) {
										$checked = ( in_array( $k, $location_access ) ) ? " checked='checked'" : '';
									}
									$item = sprintf( '<li><input type="checkbox" id="%1$s" name="event_access[]" value="%4$s" class="checkbox" %2$s /> <label for="%1$s">%3$s</label></li>', esc_attr( $id ), $checked, esc_html( $label ), esc_attr( $k ) );
									$access_list .= $item;
								}
								echo $access_list;
								?>
							</ul>
						</fieldset>
	<?php 
	}  
	if (( $mc_input['event_location'] == 'on' || $mc_input['event_location_dropdown'] == 'on' ) || $input_all) { ?>
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
 * @param string $action Type of action 
 * @param array $post of event data
 *
 * @return mixed array/object $data checked array or object if error found
 */
function mc_check_group_data( $action, $post ) {
	$post = apply_filters( 'mc_groups_pre_checkdata', $post, $action );
	global $wpdb, $current_user, $submission;
	$url_ok   = 0;
	$title_ok = 0;
	$submit   = array();
	if ( get_magic_quotes_gpc() ) {
		$post = array_map( 'stripslashes_deep', $post );
	}
	if ( ! wp_verify_nonce( $post['event_nonce_name'], 'event_nonce' ) ) {
		return '';
	}
	$errors = "";
	if ( $action == 'add' || $action == 'edit' || $action == 'copy' ) {
		$title              = ! empty( $post['event_title'] ) ? trim( $post['event_title'] ) : '';
		$desc               = ! empty( $post['content'] ) ? trim( $post['content'] ) : '';
		$short              = ! empty( $post['event_short'] ) ? trim( $post['event_short'] ) : '';
		$host               = ! empty( $post['event_host'] ) ? $post['event_host'] : $current_user->ID;
		if ( isset( $post['event_category'] ) ) {
			$cats    = $post['event_category'];
			// set first category as primary
			$primary = ( is_numeric( $cats[0] ) ) ? $cats[0] : 1;			
			foreach( $cats as $cat ) {
				$private = mc_get_category_detail( $cat,'category_private' );
				// if a selected category is private, set that category as primary instead.
				if ( $private == 1 ) {
					$primary = $cat;
				}
			}
		}

		$event_link         = ! empty( $post['event_link'] ) ? trim( $post['event_link'] ) : '';
		$expires            = ! empty( $post['event_link_expires'] ) ? $post['event_link_expires'] : '0';
		$location_preset    = ! empty( $post['location_preset'] ) ? $post['location_preset'] : '';
		$event_tickets      = ! empty( $post['event_tickets'] ) ? trim( $post['event_tickets'] ) : '';
		$event_registration = ! empty( $post['event_registration'] ) ? trim( $post['event_registration'] ) : '';
		$event_image        = esc_url_raw( $post['event_image'] );
		$event_span         = ! empty( $post['event_span'] ) ? 1 : 0;
		// set location
		if ( $location_preset != 'none' ) {
			$sql             = "SELECT * FROM " . my_calendar_locations_table() . " WHERE location_id = $location_preset";
			$location        = $wpdb->get_row( $sql );
			$event_label     = $location->location_label;
			$event_street    = $location->location_street;
			$event_street2   = $location->location_street2;
			$event_city      = $location->location_city;
			$event_state     = $location->location_state;
			$event_postcode  = $location->location_postcode;
			$event_region    = $location->location_region;
			$event_country   = $location->location_country;
			$event_url       = $location->location_url;
			$event_longitude = $location->location_longitude;
			$event_latitude  = $location->location_latitude;
			$event_zoom      = $location->location_zoom;
			$event_phone     = $location->location_phone;
			$event_access    = $location->location_access;
		} else {
			$event_label     = ! empty( $post['event_label'] ) ? $post['event_label'] : '';
			$event_street    = ! empty( $post['event_street'] ) ? $post['event_street'] : '';
			$event_street2   = ! empty( $post['event_street2'] ) ? $post['event_street2'] : '';
			$event_city      = ! empty( $post['event_city'] ) ? $post['event_city'] : '';
			$event_state     = ! empty( $post['event_state'] ) ? $post['event_state'] : '';
			$event_postcode  = ! empty( $post['event_postcode'] ) ? $post['event_postcode'] : '';
			$event_region    = ! empty( $post['event_region'] ) ? $post['event_region'] : '';
			$event_country   = ! empty( $post['event_country'] ) ? $post['event_country'] : '';
			$event_url       = ! empty( $post['event_url'] ) ? $post['event_url'] : '';
			$event_longitude = ! empty( $post['event_longitude'] ) ? $post['event_longitude'] : '';
			$event_latitude  = ! empty( $post['event_latitude'] ) ? $post['event_latitude'] : '';
			$event_zoom      = ! empty( $post['event_zoom'] ) ? $post['event_zoom'] : '';
			$event_phone     = ! empty( $post['event_phone'] ) ? $post['event_phone'] : '';
			$event_access    = ! empty( $post['event_access'] ) ? $post['event_access'] : array();
			$event_access    = ! empty( $post['event_access_hidden'] ) ? unserialize( $post['event_access_hidden'] ) : $event_access;
		}
		// We check to make sure the URL is acceptable (blank or starting with http://)
		if ( ! ( $event_link == '' || preg_match( '/^(http)(s?)(:)\/\//', $event_link ) ) ) {
			$event_link = "http://" . $event_link;
		}		
	}
	// A title is required, and can't be more than 255 characters.
	$title_length = strlen( $title );
	if ( ! ( $title_length >= 1 && $title_length <= 255 ) ) {
		$title = __( 'Untitled Event', 'my-calendar' );
	}	

	$proceed = true;
	$submit  = array(
		// strings
		'event_title'        => $title,
		'event_desc'         => $desc,
		'event_short'        => $short,
		'event_link'         => $event_link,
		'event_label'        => $event_label,
		'event_street'       => $event_street,
		'event_street2'      => $event_street2,
		'event_city'         => $event_city,
		'event_state'        => $event_state,
		'event_postcode'     => $event_postcode,
		'event_region'       => $event_region,
		'event_country'      => $event_country,
		'event_url'          => $event_url,
		'event_image'        => $event_image,
		'event_phone'        => $event_phone,
		'event_access'       => serialize( $event_access ),
		'event_tickets'      => $event_tickets,
		'event_registration' => $event_registration,
		// integers
		'event_category'     => $primary,
		'event_link_expires' => $expires,
		'event_zoom'         => $event_zoom,
		'event_host'         => $host,
		'event_span'         => $event_span,
		// floats
		'event_longitude'    => $event_longitude,
		'event_latitude'     => $event_latitude,
		// array (not saved directly)
		'event_categories'   => $cats
	);
	
	$submit = array_map( 'mc_kses_post', $submit );
	
	if ( $action == 'edit' ) {
		unset( $submit['event_author'] );
	}
	
	$data = array( $proceed, false, $submit, $errors );

	return $data;
}


/**
 * Used on the manage events admin page to display a list of events
 */
function mc_list_groups() {
	global $wpdb;
	$current_user = wp_get_current_user();
	$user = $current_user->ID;

	$sortby = ( isset( $_GET['sort'] ) ) ? (int) $_GET['sort'] : get_option( 'mc_default_sort' );
	if ( isset( $_GET['order'] ) ) {
		$sortdir = ( isset( $_GET['order'] ) && $_GET['order'] == 'ASC' ) ? 'ASC' : 'default';
	} else {
		$sortdir = 'default';
	}
	if ( empty( $sortby ) ) {
		$sortbyvalue = 'event_begin';
	} else {
		switch ( $sortby ) {
			case 1:
				$sortbyvalue = 'event_ID';
				break;
			case 2:
				$sortbyvalue = 'event_title';
				break;
			case 3:
				$sortbyvalue = 'event_desc';
				break;
			case 4:
				$sortbyvalue = 'event_begin';
				break;
			case 5:
				$sortbyvalue = 'event_author';
				break;
			case 6:
				$sortbyvalue = 'event_category';
				break;
			case 7:
				$sortbyvalue = 'event_label';
				break;
			case 8:
				$sortbyvalue = 'group_id';
				break;
			default:
				$sortbyvalue = 'event_begin';
		}
	}
	$sortbydirection = ( $sortdir == 'default' ) ? 'DESC' : $sortdir;
	$sorting         = ( $sortbydirection == 'DESC' ) ? "&amp;order=ASC" : '';

	$current        = empty( $_GET['paged'] ) ? 1 : intval( $_GET['paged'] );
	$screen         = get_current_screen();
	$option         = $screen->get_option( 'per_page', 'option' );
	$items_per_page = get_user_meta( $user, $option, true );
	if ( empty( $items_per_page ) || $items_per_page < 1 ) {
		$items_per_page = $screen->get_option( 'per_page', 'default' );
	}
	$limit = ( isset( $_GET['limit'] ) ) ? $_GET['limit'] : 'all';
	switch ( $limit ) {
		case 'all':
			$limit = '';
			break;
		case 'grouped':
			$limit = 'WHERE event_group_id <> 0';
			break;
		case 'ungrouped':
			$limit = 'WHERE event_group_id = 0';
			break;
		default:
			$limit = '';
	}
	$events     = $wpdb->get_results( "SELECT SQL_CALC_FOUND_ROWS * FROM " . my_calendar_table() . " $limit ORDER BY $sortbyvalue $sortbydirection LIMIT " . ( ( $current - 1 ) * $items_per_page ) . ", " . $items_per_page );
	$found_rows = $wpdb->get_col( "SELECT FOUND_ROWS();" );
	$items      = $found_rows[0];
	?>
	<div class='inside'><?php
		?>
		<ul class="links">
			<li>
				<a <?php echo ( isset( $_GET['limit'] ) && $_GET['limit'] == 'groupeed' ) ? ' class="active-link"' : ''; ?>
					href="<?php echo admin_url( 'admin.php?page=my-calendar-groups&amp;limit=grouped#my-calendar-admin-table' ); ?>"><?php _e( 'Grouped Events', 'my-calendar' ); ?></a>
			</li>
			<li>
				<a <?php echo ( isset( $_GET['limit'] ) && $_GET['limit'] == 'ungrouped' ) ? ' class="active-link"' : ''; ?>
					href="<?php echo admin_url( 'admin.php?page=my-calendar-groups&amp;limit=ungrouped#my-calendar-admin-table' ); ?>"><?php _e( 'Ungrouped Events', 'my-calendar' ); ?></a>
			</li>
			<li>
				<a <?php echo ( isset( $_GET['limit'] ) && $_GET['limit'] == 'all' || ! isset( $_GET['limit'] ) ) ? ' class="active-link"' : ''; ?>
					href="<?php echo admin_url( 'admin.php?page=my-calendar-groups#my-calendar-admin-table' ); ?>"><?php _e( 'All', 'my-calendar' ); ?></a>
			</li>
		</ul>
	<p><?php _e( 'Check a set of events to group them for mass editing.', 'my-calendar' ); ?></p><?php
	$num_pages = ceil( $items / $items_per_page );
	if ( $num_pages > 1 ) {
		$page_links = paginate_links( array(
			'base'      => add_query_arg( 'paged', '%#%' ),
			'format'    => '',
			'prev_text' => __( '&laquo; Previous<span class="screen-reader-text"> Events</span>', 'my-calendar' ),
			'next_text' => __( 'Next<span class="screen-reader-text"> Events</span> &raquo;', 'my-calendar' ),
			'total'     => $num_pages,
			'current'   => $current,
			'mid_size'  => 1
		) );
		printf( "<div class='tablenav'><div class='tablenav-pages'>%s</div></div>", $page_links );
	}
	if ( ! empty( $events ) ) {
		?>
		<form action="<?php echo admin_url( "admin.php?page=my-calendar-groups" ); ?>" method="post">
			<div>
				<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
				<input type="hidden" name="event_action" value="group"/>
			</div>
			<p style="position:relative;display:inline-block;">
				<input type="submit" class="button-primary group"
				       value="<?php _e( 'Group checked events for mass editing', 'my-calendar' ); ?>"/>
			</p>
	</div>
	<table class="widefat wp-list-table" id="my-calendar-admin-table">
		<thead>
		<tr>
			<th scope="col" style="width: 50px;"><a
					href="<?php echo admin_url( "admin.php?page=my-calendar-groups&amp;sort=1$sorting" ); ?>"><?php _e( 'ID', 'my-calendar' ) ?></a>
			</th>
			<th scope="col"><a
					href="<?php echo admin_url( "admin.php?page=my-calendar-groups&amp;sort=8$sorting" ); ?>"><?php _e( 'Group', 'my-calendar' ) ?></a>
			</th>
			<th scope="col"><a
					href="<?php echo admin_url( "admin.php?page=my-calendar-groups&amp;sort=2$sorting" ); ?>"><?php _e( 'Title', 'my-calendar' ) ?></a>
			</th>
			<th scope="col"><a
					href="<?php echo admin_url( "admin.php?page=my-calendar-groups&amp;sort=7$sorting" ); ?>"><?php _e( 'Where', 'my-calendar' ) ?></a>
			</th>
			<th scope="col"><a
					href="<?php echo admin_url( "admin.php?page=my-calendar-groups&amp;sort=4$sorting" ); ?>"><?php _e( 'Date/Time', 'my-calendar' ) ?></a>
			</th>
			<th scope="col"><a
					href="<?php echo admin_url( "admin.php?page=my-calendar-groups&amp;sort=5$sorting" ); ?>"><?php _e( 'Author', 'my-calendar' ) ?></a>
			</th>
			<th scope="col"><a
					href="<?php echo admin_url( "admin.php?page=my-calendar-groups&amp;sort=6$sorting" ); ?>"><?php _e( 'Category', 'my-calendar' ) ?></a>
			</th>
		</tr>
		</thead>
		<?php
		$class      = '';
		$sql        = "SELECT * FROM " . my_calendar_categories_table();
		$categories = $wpdb->get_results( $sql );

		foreach ( $events as $event ) {
			$class      = ( $class == 'alternate' ) ? '' : 'alternate';
			$spam       = ( $event->event_flagged == 1 ) ? ' spam' : '';
			$spam_label = ( $event->event_flagged == 1 ) ? '<strong>Possible spam:</strong> ' : '';
			$author     = ( $event->event_author != 0 ) ? get_userdata( $event->event_author ) : 'Public Submitter';
			$can_edit   = mc_can_edit_event( $event );
			if ( $event->event_link != '' ) {
				$title = "<a href='" . esc_attr( $event->event_link ) . "'>" . strip_tags( $event->event_title, mc_strip_tags() ) . "</a>";
			} else {
				$title = $event->event_title;
			} ?>
		<tr class="<?php echo $class; echo $spam; ?>" id="event<?php echo $event->event_id; ?>">
			<th scope="row"><input type="checkbox" value="<?php echo $event->event_id; ?>" name="group[]"
			                       id="mc<?php echo $event->event_id; ?>" <?php echo ( mc_event_is_grouped( $event->event_group_id ) ) ? ' disabled="disabled"' : ''; ?> />
				<label for="mc<?php echo $event->event_id; ?>"><?php echo $event->event_id; ?></label></th>
			<th scope="row"><?php echo ( $event->event_group_id == 0 ) ? '-' : $event->event_group_id; ?></th>
			<td title="<?php echo esc_attr( substr( strip_tags( stripslashes( $event->event_desc ) ), 0, 240 ) ); ?>">
				<strong><?php if ( $can_edit ) { ?>
					<a href="<?php echo admin_url( "admin.php?page=my-calendar&amp;mode=edit&amp;event_id=$event->event_id" ); ?>"
					   class='edit'>
						<?php
						}
						echo $spam_label;
						echo strip_tags( stripslashes( $title ) ); ?>
					<?php if ( $can_edit ) {
						echo "</a>";
					} ?></strong>

				<div class='row-actions' style="visibility:visible;">
					<?php if ( $can_edit ) { ?>
						<a href="<?php echo admin_url( "admin.php?page=my-calendar&amp;mode=edit&amp;event_id=$event->event_id" ); ?>"
						   class='edit'><?php _e( 'Edit Event', 'my-calendar' ); ?></a> |
						<?php if ( mc_event_is_grouped( $event->event_group_id ) ) { ?>
							<a href="<?php echo admin_url( "admin.php?page=my-calendar-groups&amp;mode=edit&amp;event_id=$event->event_id&amp;group_id=$event->event_group_id" ); ?>"
							   class='edit group'><?php _e( 'Edit Group', 'my-calendar' ); ?></a>
						<?php } else { ?>
							<em><?php _e( 'Ungrouped', 'my-calendar' ); ?></em>
						<?php
						}
					} else {
						_e( "Not editable.", 'my-calendar' );
					} ?>
				</div>
			</td>
			<td><?php echo strip_tags( stripslashes( $event->event_label ) ); ?></td>
			<?php if ( $event->event_endtime != "23:59:59" ) {
				$eventTime = date_i18n( get_option( 'mc_time_format' ), strtotime( $event->event_time ) );
			} else {
				$eventTime = mc_notime_label( $event );
			} ?>
			<td><?php
				$date_format = ( get_option( 'mc_date_format' ) == '' ) ? get_option( 'date_format' ) : get_option( 'mc_date_format' );
				$begin       = date_i18n( $date_format, strtotime( $event->event_begin ) );
				echo "$begin, $eventTime"; ?>
				<div class="recurs">
					<?php echo mc_recur_string( $event ); ?>
				</div>
			</td>
			<td><?php echo ( is_object( $author ) ) ? $author->display_name : $author; ?></td>
			<?php
			$this_category = $event->event_category;
			foreach ( $categories as $key => $value ) {
				if ( $value->category_id == $this_category ) {
					$this_cat = $categories[ $key ];
				}
			}
			?>
			<td>
				<div class="category-color"
				     style="background-color:<?php echo strip_tags( ( strpos( $this_cat->category_color, '#' ) !== 0 ) ? '#' : '' );
				     echo $this_cat->category_color; ?>;"></div> <?php echo mc_kses_post( stripslashes( $this_cat->category_name ) ); ?>
			</td>
			<?php unset( $this_cat ); ?>
			</tr><?php
		} ?>
	</table>
		<div class="mc-controls">
			<p>
				<input type="submit" class="button-secondary group" value="<?php _e( 'Group checked events for mass editing', 'my-calendar' ); ?>"/>
			</p>
		</div>
		</form><?php
	} else {
		?>
		<div class="inside"><p><?php _e( "There are no events in the database!", 'my-calendar' ) ?></p></div><?php
	}
}