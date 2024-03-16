<?php
/**
 * Event Manager. Listing and organization of events.
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
 * Handle a bulk action.
 *
 * @param string $action type of action.
 * @param array  $events Optional. Array of event IDs to act on.
 *
 * @return string bulk action details.
 */
function mc_bulk_action( $action, $events = array() ) {
	global $wpdb;
	$events  = ( empty( $events ) ) ? $_POST['mass_edit'] : $events;
	$i       = 0;
	$total   = 0;
	$ids     = array();
	$prepare = array();
	$sql     = '';

	foreach ( $events as $value ) {
		$value = (int) $value;
		$total = count( $events );
		if ( 'delete' === $action ) {
			$result = $wpdb->get_results( $wpdb->prepare( 'SELECT event_author FROM ' . my_calendar_table() . ' WHERE event_id = %d', $value ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( mc_can_edit_event( $value ) ) {
				$occurrences = 'DELETE FROM ' . my_calendar_event_table() . ' WHERE occur_event_id = %d';
				$wpdb->query( $wpdb->prepare( $occurrences, $value ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$ids[]     = (int) $value;
				$prepare[] = '%d';
				++$i;
			}
		}
		if ( 'delete' !== $action && current_user_can( 'mc_approve_events' ) ) {
			$ids[]     = (int) $value;
			$prepare[] = '%d';
			++$i;
		}
	}
	$prepared = implode( ',', $prepare );

	switch ( $action ) {
		case 'delete':
			$sql = 'DELETE FROM ' . my_calendar_table() . ' WHERE event_id IN (' . $prepared . ')';
			break;
		case 'unarchive':
			$sql = 'UPDATE ' . my_calendar_table() . ' SET event_status = 1 WHERE event_id IN (' . $prepared . ')';
			break;
		case 'archive':
			$sql = 'UPDATE ' . my_calendar_table() . ' SET event_status = 0 WHERE event_id IN (' . $prepared . ')';
			break;
		case 'approve': // Synonymous with publish.
			$sql = 'UPDATE ' . my_calendar_table() . ' SET event_approved = 1 WHERE event_id IN (' . $prepared . ')';
			break;
		case 'draft':
			$sql = 'UPDATE ' . my_calendar_table() . ' SET event_approved = 0 WHERE event_id IN (' . $prepared . ')';
			break;
		case 'trash':
			$sql = 'UPDATE ' . my_calendar_table() . ' SET event_approved = 2 WHERE event_id IN (' . $prepared . ')';
			break;
		case 'unspam':
			$sql = 'UPDATE ' . my_calendar_table() . ' SET event_flagged = 0 WHERE event_id IN (' . $prepared . ')';
			// send notifications.
			foreach ( $ids as $id ) {
				$post_ID   = mc_get_event_post( $id );
				$submitter = get_post_meta( $post_ID, '_submitter_details', true );
				if ( is_array( $submitter ) && ! empty( $submitter ) ) {
					$name  = $submitter['first_name'] . ' ' . $submitter['last_name'];
					$email = $submitter['email'];
					/**
					 * Run action when a publically submitted event is un-spammed.
					 *
					 * @hook mcs_complete_submission
					 *
					 * @param {string} $name Submitter's name.
					 * @param {string} $email Submitter's email.
					 * @param {int}    $id Event ID.
					 * @param {string} $action Action performed ('edit').
					 */
					do_action( 'mcs_complete_submission', $name, $email, $id, 'edit' );
				}
			}
			break;
	}
	/**
	 * Add custom bulk actions.
	 *
	 * @hook mc_bulk_actions
	 *
	 * @param {string} $action Declared action.
	 * @param {array}  $ids Array of event IDs being requested.
	 */
	do_action( 'mc_bulk_actions', $action, $ids );

	$result = ( '' !== $sql ) ? $wpdb->query( $wpdb->prepare( $sql, $ids ) ) : false; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	mc_update_count_cache();
	$results = array(
		'count'  => $i,
		'total'  => $total,
		'ids'    => $ids,
		'result' => $result,
	);

	return mc_bulk_message( $results, $action );
}

/**
 * Generate a notification for bulk actions.
 *
 * @param array  $results of bulk action.
 * @param string $action Type of action.
 *
 * @return string message
 */
function mc_bulk_message( $results, $action ) {
	$count   = $results['count'];
	$total   = $results['total'];
	$ids     = $results['ids'];
	$result  = $results['result'];
	$error   = '';
	$success = '';
	switch ( $action ) {
		case 'delete':
			// Translators: Number of events deleted, number selected.
			$success = __( '%1$d events deleted successfully out of %2$d selected.', 'my-calendar' );
			$error   = __( 'Your events have not been deleted. Please investigate.', 'my-calendar' );
			break;
		case 'trash':
			// Translators: Number of events trashed, number of events selected.
			$success = __( '%1$d events trashed successfully out of %2$d selected.', 'my-calendar' );
			$error   = __( 'Your events have not been trashed. Please investigate.', 'my-calendar' );
			break;
		case 'approve':
			// Translators: Number of events published, number of events selected.
			$success = __( '%1$d events published out of %2$d selected.', 'my-calendar' );
			$error   = __( 'Your events have not been published. Were these events already published? Please investigate.', 'my-calendar' );
			break;
		case 'draft':
			// Translators: Number of events converted to draft, number of events selected.
			$success = __( '%1$d events switched to drafts out of %2$d selected.', 'my-calendar' );
			$error   = __( 'Your events have not been switched to drafts. Were these events already drafts? Please investigate.', 'my-calendar' );
			break;
		case 'archive':
			// Translators: Number of events archived, number of events selected.
			$success = __( '%1$d events archived successfully out of %2$d selected.', 'my-calendar' );
			$error   = __( 'Your events have not been archived. Please investigate.', 'my-calendar' );
			break;
		case 'unarchive':
			// Translators: Number of events removed from archive, number of events selected.
			$success = __( '%1$d events removed from archive successfully out of %2$d selected.', 'my-calendar' );
			$error   = __( 'Your events have not been removed from the archive. Were these events already archived? Please investigate.', 'my-calendar' );
			break;
		case 'unspam':
			// Translators: Number of events removed from archive, number of events selected.
			$success = __( '%1$d events successfully unmarked as spam out of %2$d selected.', 'my-calendar' );
			$error   = __( 'Your events were not removed from spam. Please investigate.', 'my-calendar' );
			break;
	}

	if ( 0 !== $result && false !== $result ) {
		$diff = 0;
		if ( $result < $count ) {
			$diff = ( $count - $result );
			// Translators: Sprintf as a 3rd argument if this string is appended to prior error. # of unchanged events.
			$success .= ' ' . _n( '%3$d event was not changed in that update.', '%3$d events were not changed in that update.', $diff, 'my-calendar' );
		}
		/**
		 * Dynamic action executed when a group of events is bulk modified.
		 *
		 * @hook mc_mass_{$action}_events
		 *
		 * @param {array} $ids Array of event IDs being handled.
		 */
		do_action( 'mc_mass_' . $action . '_events', $ids );
		$message = mc_show_notice( sprintf( $success, $result, $total, $diff ) );
	} else {
		$message = mc_show_error( $error, false );
	}

	return $message;
}

/**
 * Generate form for listing events that are editable by current user
 */
function my_calendar_manage() {
	my_calendar_check();
	global $wpdb;
	if ( isset( $_GET['mode'] ) && 'delete' === $_GET['mode'] ) {
		$event_id = ( isset( $_GET['event_id'] ) ) ? absint( $_GET['event_id'] ) : false;
		$result   = $wpdb->get_results( $wpdb->prepare( 'SELECT event_title, event_author FROM ' . my_calendar_table() . ' WHERE event_id=%d', $event_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( mc_can_edit_event( $event_id ) ) {
			if ( isset( $_GET['date'] ) ) {
				$event_instance = (int) $_GET['date'];
				$inst           = $wpdb->get_var( $wpdb->prepare( 'SELECT occur_begin FROM ' . my_calendar_event_table() . ' WHERE occur_id=%d', $event_instance ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$instance_date  = '(' . mc_date( 'Y-m-d', mc_strtotime( $inst ), false ) . ')';
			} else {
				$instance_date = '';
			} ?>
			<div class="error">
				<form action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-manage' ) ); ?>" method="post">
					<p><strong><?php esc_html_e( 'Delete Event', 'my-calendar' ); ?>:</strong> <?php esc_html_e( 'Are you sure you want to delete this event?', 'my-calendar' ); ?>
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
						<input type="hidden" value="delete" name="event_action" />
						<?php
						if ( ! empty( $_GET['date'] ) ) {
							?>
						<input type="hidden" name="event_instance" value="<?php echo (int) $_GET['date']; ?>"/>
							<?php
						}
						if ( isset( $_GET['ref'] ) ) {
							?>
						<input type="hidden" name="ref" value="<?php echo esc_url( $_GET['ref'] ); ?>" />
							<?php
						}
						?>
						<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>"/>
						<?php
							$event_info = ' &quot;' . stripslashes( $result[0]['event_title'] ) . "&quot; $instance_date";
							// Translators: Title & date of event to delete.
							$delete_text = sprintf( __( 'Delete %s', 'my-calendar' ), $event_info );
						?>
						<input type="submit" name="submit" class="button-secondary delete" value="<?php echo esc_attr( $delete_text ); ?>"/>
				</form>
			</div>
			<?php
		} else {
			mc_show_error( __( 'You do not have permission to delete that event.', 'my-calendar' ) );
		}
	}

	// Approve and show an Event ...originally by Roland.
	if ( isset( $_GET['mode'] ) && 'publish' === $_GET['mode'] ) {
		$mcnonce = wp_verify_nonce( $_GET['_mcnonce'], 'mcnonce' );
		if ( $mcnonce ) {
			if ( current_user_can( 'mc_approve_events' ) ) {
				$event_id = absint( $_GET['event_id'] );
				// Publish the event.
				$wpdb->get_results( $wpdb->prepare( 'UPDATE ' . my_calendar_table() . ' SET event_approved = 1 WHERE event_id=%d', $event_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				// Remove spam flag if present.
				$wpdb->get_results( $wpdb->prepare( 'UPDATE ' . my_calendar_table() . ' SET event_flagged = 0 WHERE event_id=%d', $event_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$event   = mc_get_event_core( $event_id );
				$private = mc_private_event( $event, false );
				$status  = ( $private ) ? 'private' : 'publish';
				wp_update_post(
					array(
						'ID'          => mc_get_event_post( $event_id ),
						'post_status' => $status,
					)
				);
				mc_update_count_cache();
			} else {
				mc_show_error( __( 'You do not have permission to approve that event.', 'my-calendar' ) );
			}
		} else {
			mc_show_error( __( 'Invalid security check; please try again!', 'my-calendar' ) );
		}
	}

	// Reject and hide an Event ...by Roland.
	if ( isset( $_GET['mode'] ) && 'reject' === $_GET['mode'] ) {
		$mcnonce = wp_verify_nonce( $_GET['_mcnonce'], 'mcnonce' );
		if ( $mcnonce ) {
			if ( current_user_can( 'mc_approve_events' ) ) {
				$event_id = absint( $_GET['event_id'] );
				$wpdb->get_results( $wpdb->prepare( 'UPDATE ' . my_calendar_table() . ' SET event_approved = 2 WHERE event_id=%d', $event_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				wp_update_post(
					array(
						'ID'          => mc_get_event_post( $event_id ),
						'post_status' => 'trash',
					)
				);
				mc_update_count_cache();
			} else {
				mc_show_error( __( 'You do not have permission to trash that event.', 'my-calendar' ) );
			}
		} else {
			mc_show_error( __( 'Invalid security check; please try again!', 'my-calendar' ) );
		}
	}

	if ( ! empty( $_POST['mc_bulk_actions'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'My Calendar: Security check failed' );
		}
		if ( isset( $_POST['mc_bulk_actions'] ) ) {
			$action  = sanitize_text_field( $_POST['mc_bulk_actions'] );
			$results = '';
			switch ( $action ) {
				case 'mass_delete':
					mc_bulk_action( 'delete' );
					break;
				case 'mass_trash':
					mc_bulk_action( 'trash' );
					break;
				case 'mass_publish':
					mc_bulk_action( 'approve' );
					break;
				case 'mass_draft':
					mc_bulk_action( 'draft' );
					break;
				case 'mass_archive':
					mc_bulk_action( 'archive' );
					break;
				case 'mass_undo_archive':
					mc_bulk_action( 'unarchive' );
					break;
				case 'mass_not_spam':
					mc_bulk_action( 'unspam' );
					break;
			}

			echo wp_kses_post( $results );
		}
	}
	?>
	<div class='wrap my-calendar-admin'>
		<h1 id="mc-manage" class="wp-heading-inline"><?php esc_html_e( 'Events', 'my-calendar' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'my-calendar' ); ?></a>
		<hr class="wp-header-end">
		<div class="mc-tablinks">
			<a href="#my-calendar-admin-table" aria-current="page"><?php esc_html_e( 'My Events', 'my-calendar' ); ?></strong>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-manage&groups=true' ) ); ?>"><?php esc_html_e( 'Event Groups', 'my-calendar' ); ?></a>
		</div>
		<div class="postbox-container jcd-wide">
			<div class="metabox-holder">
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h2 class="mc-heading-inline"><?php esc_html_e( 'My Events', 'my-calendar' ); ?></h2>
							<?php
								$grid     = ( 'grid' === mc_get_option( 'default_admin_view' ) ) ? true : false;
								$grid_url = admin_url( 'admin.php?page=my-calendar-manage&view=grid' );
								$list_url = admin_url( 'admin.php?page=my-calendar-manage&view=list' );
							?>
						<ul class="mc-admin-mode">
							<li><span class="dashicons dashicons-calendar" aria-hidden="true"></span><a <?php echo ( $grid ) ? 'aria-current="true"' : ''; ?> href="<?php echo esc_url( $grid_url ); ?>"><?php esc_html_e( 'Grid View', 'my-calendar' ); ?></a></li>
							<li><span class="dashicons dashicons-list-view" aria-hidden="true"></span><a <?php echo ( $grid ) ? '' : 'aria-current="true"'; ?>  href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( 'List View', 'my-calendar' ); ?></a></li>
						</ul>

						<div class="inside">
							<?php
							// If any subsidiary post actions (e.g. delete an event), handle before display.
							mc_handle_post();
							if ( $grid ) {
								$calendar = array(
									'name'     => 'admin',
									'format'   => 'calendar',
									'category' => 'all',
									'time'     => 'month',
									'id'       => 'mc-admin-view',
									'below'    => 'categories,locations,access',
									'above'    => 'nav,jump,search',
								);
								if ( mc_count_locations() > 200 ) {
									$calendar['below'] = 'categories,access';
								}
								/**
								 * Filter arguments used to display the calendar grid view for admins.
								 *
								 * @hook mc_filter_admin_grid_args
								 *
								 * @param {array} $calendar Calendar display arguments.
								 *
								 * @return {array}
								 */
								apply_filters( 'mc_filter_admin_grid_args', $calendar );
								echo my_calendar( $calendar );
							} else {
								mc_list_events();
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php
		$problems = mc_list_problems();
		mc_show_sidebar( '', $problems );
		?>
	</div>
	<?php
}

/**
 * Generate screens for editing and managing events & event groups.
 */
function my_calendar_manage_screen() {
	if ( ! isset( $_GET['groups'] ) ) {
		my_calendar_manage();
	} else {
		my_calendar_group_edit();
	}
}

/**
 * Show bulk actions dropdown in event manager.
 *
 * @return string
 */
function mc_show_bulk_actions() {
	$bulk_actions = array(
		'mass_publish'      => __( 'Publish', 'my-calendar' ),
		'mass_not_spam'     => __( 'Not spam', 'my-calendar' ),
		'mass_draft'        => __( 'Switch to Draft', 'my-calendar' ),
		'mass_trash'        => __( 'Trash', 'my-calendar' ),
		'mass_archive'      => __( 'Archive', 'my-calendar' ),
		'mass_undo_archive' => __( 'Remove from Archive', 'my-calendar' ),
		'mass_delete'       => __( 'Delete', 'my-calendar' ),
	);

	if ( ! current_user_can( 'mc_approve_events' ) || isset( $_GET['limit'] ) && 'published' === $_GET['limit'] ) {
		unset( $bulk_actions['mass_publish'] );
	}
	if ( ! current_user_can( 'mc_manage_events' ) || isset( $_GET['limit'] ) && 'trashed' === $_GET['limit'] ) {
		unset( $bulk_actions['mass_trash'] );
	}
	if ( isset( $_GET['limit'] ) && 'draft' === $_GET['limit'] ) {
		unset( $bulk_actions['mass_draft'] );
	}
	if ( isset( $_GET['restrict'] ) && 'archived' === $_GET['restrict'] ) {
		unset( $bulk_actions['mass_archive'] );
	} else {
		unset( $bulk_actions['mass_undo_archive'] );
	}
	if ( ! ( isset( $_GET['restrict'] ) && 'flagged' === $_GET['restrict'] ) ) {
		unset( $bulk_actions['mass_not_spam'] );
	}

	/**
	 * Filter Event manager bulk actions.
	 *
	 * @hook mc_bulk_actions
	 *
	 * @param {array} $bulk_actions Array of bulk actions currently available.
	 *
	 * @return {array}
	 */
	$bulk_actions = apply_filters( 'mc_bulk_actions', $bulk_actions );
	$options      = '';
	foreach ( $bulk_actions as $action => $label ) {
		$options .= '<option value="' . $action . '">' . $label . '</option>';
	}

	return $options;
}

/**
 * Handle event list/grid POST actions.
 *
 * @return void
 */
function mc_handle_post() {
	$action   = ! empty( $_POST['event_action'] ) ? $_POST['event_action'] : '';
	$event_id = ! empty( $_POST['event_id'] ) ? $_POST['event_id'] : '';
	if ( 'delete' === $action ) {
		$verify = wp_verify_nonce( $_POST['_wpnonce'], 'my-calendar-nonce' );
		if ( ! $verify ) {
			wp_die( 'My Calendar: Could not verify your request.', 'my-calendar' );
		} else {
			$message = mc_delete_event( $event_id );
			echo wp_kses_post( $message );
		}
	}
}

/**
 * Get the current sorting characteristics for the admin event list.
 *
 * @return array
 */
function mc_get_event_list_sorting() {
	$user_direction    = get_user_meta( wp_get_current_user()->ID, '_mc_default_direction', true );
	$default_direction = ( '' !== $user_direction ) ? $user_direction : mc_get_option( 'default_direction' );
	$user_sort         = get_user_meta( wp_get_current_user()->ID, '_mc_default_sort', true );
	$default_sort      = ( '' !== $user_sort ) ? $user_sort : mc_get_option( 'default_sort' );

	if ( isset( $_GET['order'] ) ) {
		$sortbydirection = ( isset( $_GET['order'] ) && 'ASC' === $_GET['order'] ) ? 'ASC' : $default_direction;
		$sortbydirection = ( isset( $_GET['order'] ) && 'DESC' === $_GET['order'] ) ? 'DESC' : $sortbydirection;

		update_user_meta( wp_get_current_user()->ID, '_mc_default_direction', $sortbydirection );
	} else {
		$sortbydirection = $default_direction;
	}
	if ( isset( $_GET['sort'] ) ) {
		$sortby = absint( $_GET['sort'] );

		update_user_meta( wp_get_current_user()->ID, '_mc_default_sort', $sortby );
	} else {
		$sortby = $default_sort;
	}

	if ( empty( $sortby ) ) {
		$sortbyvalue = 'event_begin';
	} else {
		switch ( $sortby ) {
			case 1:
				$sortbyvalue = "event_ID $sortbydirection";
				break;
			case 2:
			case 3:
				$sortbyvalue = "event_title $sortbydirection";
				break;
			case 4:
				$sortbyvalue = "event_begin $sortbydirection, event_time $sortbydirection";
				break;
			case 5:
				$sortbyvalue = "event_author $sortbydirection";
				break;
			case 6:
				$sortbyvalue = "event_category $sortbydirection";
				break;
			case 7:
				$sortbyvalue = "event_label $sortbydirection";
				break;
			default:
				$sortbyvalue = "event_begin $sortbydirection, event_time $sortbydirection";
		}
	}
	$sort = ( 'DESC' === $sortbydirection ) ? 'ASC' : 'DESC';

	return array(
		'sort'      => $sortbyvalue,
		'direction' => $sort,
		'sortby'    => $sortby,
	);
}

/**
 * Get event list limits.
 *
 * @return string
 */
function mc_get_event_status_limit() {
	$status = ( isset( $_GET['limit'] ) ) ? sanitize_text_field( $_GET['limit'] ) : '';
	// Filter by status.
	switch ( $status ) {
		case 'all':
			$limit = '';
			break;
		case 'draft':
			$limit = 'WHERE event_approved = 0';
			break;
		case 'published':
			$limit = 'WHERE event_approved = 1';
			break;
		case 'trashed':
			$limit = 'WHERE event_approved = 2';
			break;
		default:
			$limit = 'WHERE event_approved != 2';
	}

	return $limit;
}

/**
 * Event list search form output.
 *
 * @param string $context String to differentiate for/ID attributes if on page twice.
 */
function mc_admin_event_search( $context = '' ) {
	$search_text = ( isset( $_POST['mcs'] ) ) ? sanitize_text_field( $_POST['mcs'] ) : '';
	$args        = map_deep( $_GET, 'sanitize_text_field' );
	?>
	<div class='mc-search'>
	<form action="<?php echo esc_url( add_query_arg( $args, admin_url( 'admin.php' ) ) ); ?>" method="post" role='search'>
		<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
		</div>
		<div>
			<label for="mc_search<?php echo esc_attr( $context ); ?>" class='screen-reader-text'><?php esc_html_e( 'Search Events', 'my-calendar' ); ?></label>
			<input type='text' name='mcs' id='mc_search<?php echo esc_attr( $context ); ?>' value='<?php echo esc_attr( $search_text ); ?>' />
			<input type='submit' value='<?php echo esc_attr( __( 'Search', 'my-calendar' ) ); ?>' class='button-secondary'/>
		</div>
	</form>
	</div>
	<?php
}

/**
 * Get query limit pagination values for DB query.
 */
function mc_get_query_limit() {
	$current        = empty( $_GET['paged'] ) ? 1 : intval( $_GET['paged'] );
	$user           = get_current_user_id();
	$screen         = get_current_screen();
	$option         = $screen->get_option( 'per_page', 'option' );
	$items_per_page = get_user_meta( $user, $option, true );
	if ( empty( $items_per_page ) || $items_per_page < 1 ) {
		$items_per_page = $screen->get_option( 'per_page', 'default' );
	}
	$query_limit = ( ( $current - 1 ) * $items_per_page );

	return array(
		'query'          => $query_limit,
		'current'        => $current,
		'items_per_page' => $items_per_page,
	);
}

/**
 * Get filter type and filtered value.
 *
 * @return array
 */
function mc_get_filter() {
	$restrict = ( isset( $_GET['restrict'] ) ) ? sanitize_text_field( $_GET['restrict'] ) : 'all';

	switch ( $restrict ) {
		case 'all':
			$filter = '';
			break;
		case 'where':
			$filter   = ( isset( $_GET['filter'] ) ) ? absint( $_GET['filter'] ) : '';
			$restrict = 'event_location';
			break;
		case 'author':
			$filter   = ( isset( $_GET['filter'] ) ) ? (int) $_GET['filter'] : '';
			$restrict = 'event_author';
			break;
		case 'category':
			$filter   = ( isset( $_GET['filter'] ) ) ? (int) $_GET['filter'] : '';
			$restrict = 'event_category';
			break;
		case 'flagged':
			$filter   = ( isset( $_GET['filter'] ) ) ? (int) $_GET['filter'] : '';
			$restrict = 'event_flagged';
			break;
		default:
			$filter = '';
	}

	return array(
		'filter'   => $filter,
		'restrict' => $restrict,
	);
}

/**
 * Used on the manage events admin page to display a list of events
 */
function mc_list_events() {
	global $wpdb;
	if ( current_user_can( 'mc_approve_events' ) || current_user_can( 'mc_manage_events' ) || current_user_can( 'mc_add_events' ) ) {
		// Check current user's last sort.
		$sort            = mc_get_event_list_sorting();
		$sortbyvalue     = $sort['sort'];
		$sortbydirection = $sort['direction'];
		$sortby          = $sort['sortby'];
		$limit           = mc_get_event_status_limit();
		$filters         = mc_get_filter();
		$filter          = $filters['filter'];
		$restrict        = $filters['restrict'];
		$allow_filters   = true;

		if ( ! current_user_can( 'mc_manage_events' ) && ! current_user_can( 'mc_approve_events' ) ) {
			$restrict      = 'event_author';
			$filter        = get_current_user_id();
			$allow_filters = false;
		}
		// Set up filter format for location names.
		if ( 'event_label' === $restrict ) {
			$filter = "'$filter'";
		}
		$join = '';
		// Set up filter format for categories.
		if ( 'event_category' === $restrict ) {
			$cat_limit       = mc_select_category( $filter );
			$join            = ( isset( $cat_limit[0] ) ) ? $cat_limit[0] : '';
			$select_category = ( isset( $cat_limit[1] ) ) ? $cat_limit[1] : '';
			$limit          .= ' ' . $select_category;
		}
		// Set up standard filter limits - normal database fields.
		if ( '' === $limit && '' !== $filter ) {
			$limit = "WHERE $restrict = $filter";
		} elseif ( '' !== $limit && '' !== $filter && 'event_category' !== $restrict ) {
			$limit .= " AND $restrict = $filter";
		}
		// Define default limits if none otherwise set.
		if ( '' === $limit ) {
			$limit .= ( 'event_flagged' !== $restrict ) ? ' WHERE event_flagged = 0' : '';
		} else {
			$limit .= ( 'event_flagged' !== $restrict ) ? ' AND event_flagged = 0' : '';
		}
		// Define search query parameters.
		if ( isset( $_POST['mcs'] ) || isset( $_GET['mcs'] ) ) {
			$query  = $_REQUEST['mcs'];
			$limit .= mc_prepare_search_query( $query );
		}
		// Get page and pagination values.
		$query = mc_get_query_limit();
		// Set event status limits.
		$limit .= ( 'archived' !== $restrict ) ? ' AND e.event_status = 1' : ' AND e.event_status = 0';
		// Toggle query type depending on whether we're limiting categories, which requires a join.
		if ( 'event_category' !== $sortbyvalue ) {
			$events = $wpdb->get_results( $wpdb->prepare( 'SELECT SQL_CALC_FOUND_ROWS e.event_id FROM ' . my_calendar_table() . " AS e $join $limit ORDER BY $sortbyvalue " . 'LIMIT %d, %d', $query['query'], $query['items_per_page'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$limit  = str_replace( array( 'WHERE ' ), '', $limit );
			$limit  = ( strpos( $limit, 'AND' ) === 0 ) ? $limit : 'AND ' . $limit;
			$events = $wpdb->get_results( $wpdb->prepare( 'SELECT DISTINCT SQL_CALC_FOUND_ROWS e.event_id FROM ' . my_calendar_table() . ' AS e ' . $join . ' JOIN ' . my_calendar_categories_table() . " AS c WHERE e.event_category = c.category_id $limit ORDER BY c.category_name $sortbydirection " . 'LIMIT %d, %d', $query['query'], $query['items_per_page'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		}
		$found_rows = $wpdb->get_col( 'SELECT FOUND_ROWS();' );
		$items      = $found_rows[0];
		$num_pages  = ceil( $items / $query['items_per_page'] );
		if ( $num_pages > 1 ) {
			$page_links = paginate_links(
				array(
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'prev_text' => __( '&laquo; Previous<span class="screen-reader-text"> Events</span>', 'my-calendar' ),
					'next_text' => __( 'Next<span class="screen-reader-text"> Events</span> &raquo;', 'my-calendar' ),
					'total'     => $num_pages,
					'current'   => $query['current'],
					'mid_size'  => 2,
				)
			);
			printf( "<div class='tablenav'><div class='tablenav-pages'>%s</div></div>", $page_links );
		}

		// Display a link to clear filters if set.
		$filtered = '';
		if ( '' !== $filter && $allow_filters ) {
			$filtered = "<a class='mc-clear-filters' href='" . admin_url( 'admin.php?page=my-calendar-manage' ) . "'><span class='dashicons dashicons-no' aria-hidden='true'></span> " . __( 'Clear filters', 'my-calendar' ) . '</a>';
		}
		?>
		<div class="mc-admin-header">
			<?php
			// Display links to different statuses.
			echo wp_kses( mc_status_links( $allow_filters ), mc_kses_elements() );
			// Display event search.
			mc_admin_event_search();
			?>
		</div>
		<?php
		if ( ! empty( $events ) ) {
			?>
			<form action="<?php echo esc_url( add_query_arg( $_GET, admin_url( 'admin.php' ) ) ); ?>" method="post">
				<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
				<div class='mc-actions'>
					<label for="mc_bulk_actions" class="screen-reader-text"><?php esc_html_e( 'Bulk actions', 'my-calendar' ); ?></label>
					<select name="mc_bulk_actions" id="mc_bulk_actions">
						<option value=""><?php esc_html_e( 'Bulk actions', 'my-calendar' ); ?></option>
						<?php echo mc_show_bulk_actions(); ?>
					</select>
					<input type="submit" class="button-secondary" value="<?php echo esc_attr( __( 'Apply', 'my-calendar' ) ); ?>" />
					<div><input type='checkbox' class='selectall' id='mass_edit' data-action="mass_edit" /> <label for='mass_edit'><?php esc_html_e( 'Check all', 'my-calendar' ); ?></label></div>
				</div>

			<table class="widefat striped wp-list-table" id="my-calendar-admin-table">
				<caption class="screen-reader-text"><?php esc_html_e( 'Event list. Use column headers to sort.', 'my-calendar' ); ?></caption>
				<thead>
					<tr>
					<?php
					$admin_url = admin_url( "admin.php?page=my-calendar-manage&order=$sortbydirection&paged=" . $query['current'] );
					$url       = add_query_arg( 'sort', '1', $admin_url );
					$col_head  = mc_table_header( __( 'ID', 'my-calendar' ), $sortbydirection, $sortby, '1', $url );
					$url       = add_query_arg( 'sort', '2', $admin_url );
					$col_head .= mc_table_header( __( 'Title', 'my-calendar' ), $sortbydirection, $sortby, '2', $url );
					$url       = add_query_arg( 'sort', '7', $admin_url );
					$col_head .= mc_table_header( __( 'Location', 'my-calendar' ), $sortbydirection, $sortby, '7', $url );
					$url       = add_query_arg( 'sort', '4', $admin_url );
					$col_head .= mc_table_header( __( 'Date/Time', 'my-calendar' ), $sortbydirection, $sortby, '4', $url );
					$url       = add_query_arg( 'sort', '5', $admin_url );
					$col_head .= mc_table_header( __( 'Author', 'my-calendar' ), $sortbydirection, $sortby, '5', $url );
					$url       = add_query_arg( 'sort', '6', $admin_url );
					$col_head .= mc_table_header( __( 'Category', 'my-calendar' ), $sortbydirection, $sortby, '6', $url );
					echo mc_kses_post( $col_head );
					?>
					</tr>
				</thead>
				<tbody>
				<?php mc_admin_events_table( $events ); ?>
				</tbody>
			</table>
			<div class="mc-actions">
				<label for="mc_bulk_actions_footer" class="screen-reader-text"><?php esc_html_e( 'Bulk actions', 'my-calendar' ); ?></label>
				<select name="mc_bulk_actions" id="mc_bulk_actions_footer">
					<option value=""><?php esc_html_e( 'Bulk actions', 'my-calendar' ); ?></option>
					<?php echo mc_show_bulk_actions(); ?>
				</select>
				<input type="submit" class="button-secondary" value="<?php echo esc_attr( __( 'Apply', 'my-calendar' ) ); ?>" />
				<input type='checkbox' class='selectall' id='mass_edit_footer' data-action="mass_edit" /> <label for='mass_edit_footer'><?php esc_html_e( 'Check all', 'my-calendar' ); ?></label>
			</div>
		</form>
		<div class='mc-admin-footer'>
			<?php
			$status_links = mc_status_links( $allow_filters );
			echo wp_kses( $status_links . $filtered, mc_kses_elements() );
			mc_admin_event_search( '_footer' );
			?>
		</div>
			<?php
		} else {
			if ( isset( $_POST['mcs'] ) ) {
				echo '<p>' . esc_html__( 'No results found for your search query.', 'my-calendar' ) . '</p>';
			}
			if ( ! isset( $_GET['restrict'] ) && ( ! isset( $_GET['limit'] ) || isset( $_GET['limit'] ) && 'all' === $_GET['limit'] ) ) {
				?>
				<p class='mc-create-event'><a href="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar' ) ); ?>" class="button button-hero"><?php esc_html_e( 'Create an event', 'my-calendar' ); ?></a></p>
				<?php
			} else {
				?>
				<p class='mc-none'><?php esc_html_e( 'No events found.', 'my-calendar' ); ?></p>
				<?php
			}
		}
	}
}

/**
 * Output event table data for My Calendar admin events.
 *
 * @param array $events Array of objects representing events.
 */
function mc_admin_events_table( $events ) {
	global $wpdb;
	$class = '';

	foreach ( array_keys( $events ) as $key ) {
		$e       =& $events[ $key ];
		$event   = mc_get_first_event( $e->event_id );
		$invalid = false;
		if ( ! is_object( $event ) ) {
			$event   = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . my_calendar_table() . ' WHERE event_id = %d', $e->event_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$invalid = true;
		}
		$class   = ( $invalid ) ? 'invalid' : '';
		$pending = ( 0 === (int) $event->event_approved ) ? 'pending' : '';
		$trashed = ( 2 === (int) $event->event_approved ) ? 'trashed' : '';
		$author  = ( 0 !== (int) $event->event_author ) ? get_userdata( $event->event_author ) : 'Public Submitter';

		if ( 1 === (int) $event->event_flagged && ( isset( $_GET['restrict'] ) && 'flagged' === $_GET['restrict'] ) ) {
			$spam       = 'spam';
			$pending    = '';
			$spam_label = '<strong>' . esc_html__( 'Possible spam', 'my-calendar' ) . ':</strong> ';
		} else {
			$spam       = '';
			$spam_label = '';
		}

		$trash    = ( '' !== $trashed ) ? ' - ' . __( 'Trash', 'my-calendar' ) : '';
		$draft    = ( '' !== $pending ) ? ' - ' . __( 'Draft', 'my-calendar' ) : '';
		$inv      = ( $invalid ) ? ' - ' . __( 'Invalid Event', 'my-calendar' ) : '';
		$limit    = ( isset( $_GET['limit'] ) ) ? sanitize_text_field( $_GET['limit'] ) : 'all';
		$private  = ( mc_private_event( $event, false ) ) ? ' - ' . __( 'Private', 'my-calendar' ) : '';
		$check    = mc_test_occurrence_overlap( $event, true );
		$problem  = ( '' !== $check ) ? 'problem' : '';
		$edit_url = admin_url( "admin.php?page=my-calendar&amp;mode=edit&amp;event_id=$event->event_id" );
		$copy_url = admin_url( "admin.php?page=my-calendar&amp;mode=copy&amp;event_id=$event->event_id" );
		$view_url = ( $invalid ) ? '' : mc_get_details_link( $event );

		$group_url  = admin_url( "admin.php?page=my-calendar-manage&amp;groups=true&amp;mode=edit&amp;event_id=$event->event_id&amp;group_id=$event->event_group_id" );
		$mcnonce    = wp_create_nonce( 'mcnonce' );
		$delete_url = add_query_arg( '_mcnonce', $mcnonce, admin_url( "admin.php?page=my-calendar-manage&amp;mode=delete&amp;event_id=$event->event_id" ) );
		$can_edit   = mc_can_edit_event( $event );
		if ( current_user_can( 'mc_manage_events' ) || current_user_can( 'mc_approve_events' ) || $can_edit ) {
			?>
			<tr class="<?php echo sanitize_html_class( "$class $spam $pending $trashed $problem" ); ?>">
				<th scope="row">
					<input type="checkbox" value="<?php echo absint( $event->event_id ); ?>" name="mass_edit[]" id="mc<?php echo $event->event_id; ?>" aria-describedby='event<?php echo absint( $event->event_id ); ?>' />
					<label for="mc<?php echo absint( $event->event_id ); ?>">
					<?php
					// Translators: Event ID.
					printf( __( "<span class='screen-reader-text'>Select event </span>%d", 'my-calendar' ), absint( $event->event_id ) );
					?>
					</label>
				</th>
				<td>
					<strong>
					<?php
					if ( $can_edit ) {
						?>
						<a href="<?php echo esc_url( $edit_url ); ?>" class='edit'><span class="dashicons dashicons-edit" aria-hidden="true"></span>
						<?php
					}
					echo $spam_label;
					echo '<span id="event' . absint( $event->event_id ) . '">' . esc_html( stripslashes( $event->event_title ) ) . '</span>';
					if ( $can_edit ) {
						echo '</a>';
						if ( '' !== $check ) {
							// Translators: URL to edit event.
							echo wp_kses_post( '<br /><strong class="error">' . sprintf( __( 'There is a problem with this event. <a href="%s">Edit</a>', 'my-calendar' ), esc_url( $edit_url ) ) . '</strong>' );
						}
					}
					echo wp_kses_post( $private . $trash . $draft . $inv );
					?>
					</strong>

					<div class='row-actions'>
						<?php
						if ( mc_event_published( $event ) ) {
							?>
							<a href="<?php echo esc_url( $view_url ); ?>" class='view' aria-describedby='event<?php echo absint( $event->event_id ); ?>'><?php esc_html_e( 'View', 'my-calendar' ); ?></a> |
							<?php
						} elseif ( current_user_can( 'mc_manage_events' ) ) {
							?>
							<a href="<?php echo esc_url( add_query_arg( 'preview', 'true', $view_url ) ); ?>" class='view' aria-describedby='event<?php echo absint( $event->event_id ); ?>'><?php esc_html_e( 'Preview', 'my-calendar' ); ?></a> |
							<?php
						}
						if ( $can_edit ) {
							?>
							<a href="<?php echo esc_url( $copy_url ); ?>" class='copy' aria-describedby='event<?php echo absint( $event->event_id ); ?>'><?php esc_html_e( 'Copy', 'my-calendar' ); ?></a>
							<?php
						}
						if ( $can_edit ) {
							if ( mc_event_is_grouped( $event->event_group_id ) ) {
								?>
								| <a href="<?php echo esc_url( $group_url ); ?>" class='edit group' aria-describedby='event<?php echo absint( $event->event_id ); ?>'><?php esc_html_e( 'Edit Group', 'my-calendar' ); ?></a>
								<?php
							}
							?>
							| <a href="<?php echo esc_url( $delete_url ); ?>" class="delete" aria-describedby='event<?php echo absint( $event->event_id ); ?>'><?php esc_html_e( 'Delete', 'my-calendar' ); ?></a>
							<?php
						} else {
							_e( 'Not editable.', 'my-calendar' );
						}
						?>
						|
						<?php
						if ( current_user_can( 'mc_approve_events' ) && $can_edit ) {
							if ( 1 === (int) $event->event_approved ) {
								$mo = 'reject';
								$te = __( 'Trash', 'my-calendar' );
							} else {
								$mo = 'publish';
								$te = __( 'Publish', 'my-calendar' );
							}
							?>
							<a href="<?php echo esc_url( add_query_arg( '_mcnonce', $mcnonce, admin_url( "admin.php?page=my-calendar-manage&amp;mode=$mo&amp;limit=$limit&amp;event_id=$event->event_id" ) ) ); ?>" class='<?php echo esc_attr( $mo ); ?>' aria-describedby='event<?php echo absint( $event->event_id ); ?>'><?php echo esc_html( $te ); ?></a>
							<?php
						} else {
							switch ( $event->event_approved ) {
								case 1:
									_e( 'Published', 'my-calendar' );
									break;
								case 2:
									_e( 'Trashed', 'my-calendar' );
									break;
								default:
									_e( 'Awaiting Approval', 'my-calendar' );
							}
						}
						?>
					</div>
				</td>
				<td>
					<?php
					$elabel = '';
					if ( property_exists( $event, 'location' ) && is_object( $event->location ) ) {
						$filter = $event->event_location;
						$elabel = $event->location->location_label;
					}
					if ( '' !== $elabel ) {
						?>
					<a class='mc_filter' href='<?php echo esc_url( mc_admin_url( 'admin.php?page=my-calendar-manage&amp;filter=' . urlencode( $filter ) . '&amp;restrict=where' ) ); ?>'><span class="screen-reader-text"><?php esc_html_e( 'Show only: ', 'my-calendar' ); ?></span><?php echo esc_html( stripslashes( $elabel ) ); ?></a>
						<?php
					}
					?>
				</td>
				<td>
				<?php
				if ( '23:59:59' !== $event->event_endtime ) {
					$event_time = date_i18n( mc_time_format(), mc_strtotime( $event->event_time ) );
				} else {
					$event_time = mc_notime_label( $event );
				}
				$begin = date_i18n( mc_date_format(), mc_strtotime( $event->event_begin ) );
				echo esc_html( "$begin, $event_time" );
				?>
					<div class="recurs">
						<?php echo wp_kses_post( mc_recur_string( $event ) ); ?>
					</div>
				</td>
				<?php
				$auth   = ( is_object( $author ) ) ? $author->ID : 0;
				$filter = mc_admin_url( "admin.php?page=my-calendar-manage&amp;filter=$auth&amp;restrict=author" );
				$author = ( is_object( $author ) ? $author->display_name : $author );
				?>
				<td>
					<a class='mc_filter' href="<?php echo esc_url( $filter ); ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Show only: ', 'my-calendar' ); ?></span><?php echo esc_html( $author ); ?>
					</a>
				</td>
				<td>
				<?php echo mc_admin_category_list( $event ); ?>
				</td>
			</tr>
			<?php
		}
	}
}

/**
 * Get next available group ID
 *
 * @return int
 */
function mc_group_id() {
	global $wpdb;
	$result = $wpdb->get_var( 'SELECT MAX(event_id) FROM ' . my_calendar_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$next   = $result + 1;

	return $next;
}

/**
 * Check whether an event is a member of a group
 *
 * @param int $group_id Event Group ID.
 *
 * @return boolean
 */
function mc_event_is_grouped( $group_id ) {
	global $wpdb;
	if ( 0 === (int) $group_id ) {
		return false;
	} else {
		$value = $wpdb->get_var( $wpdb->prepare( 'SELECT count( event_group_id ) FROM ' . my_calendar_table() . ' WHERE event_group_id = %d', $group_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $value > 1 ) {

			return true;
		} else {

			return false;
		}
	}
}

/**
 * Can the current user edit this category?
 *
 * @param int $category Category ID.
 * @param int $user User ID.
 *
 * @return boolean
 */
function mc_can_edit_category( $category, $user ) {
	$permissions = ( '' === get_user_meta( $user, 'mc_user_permissions', true ) ) ? array() : get_user_meta( $user, 'mc_user_permissions', true );
	/**
	 * Filter permissions for users editing a category.
	 *
	 * @hook mc_user_permissions
	 *
	 * @param {array} $permissions User meta data for this user's category permissions.
	 * @param {int}   $category Category ID.
	 * @param {int}   $user User ID.
	 *
	 * @return {array} Array of categories this user can edit.
	 */
	$permissions = apply_filters( 'mc_user_permissions', $permissions, $category, $user );

	if ( ( ! $permissions || empty( $permissions ) ) || in_array( 'all', $permissions, true ) || in_array( $category, $permissions, true ) || current_user_can( 'manage_options' ) ) {
		return true;
	}

	return false;
}

/**
 * Unless an admin, authors can only edit their own events if they don't have mc_manage_events capabilities.
 *
 * @param object|boolean|int $event Event object or event ID.
 * @param string             $datatype 'event' or 'instance'.
 *
 * @return boolean
 */
function mc_can_edit_event( $event = false, $datatype = 'event' ) {
	global $wpdb;
	if ( ! $event ) {

		return false;
	}

	/**
	 * Filter permissions to edit an event via the My Calendar Pro REST API..
	 *
	 * @hook mc_api_can_edit_event
	 *
	 * @param {bool} $return True if API user can edit this event.
	 * @param {object|int}  $event The ID of the current event or an event object.
	 *
	 * @return {bool}
	 */
	$api = apply_filters( 'mc_api_can_edit_event', false, $event );
	if ( $api ) {

		return $api;
	}

	if ( ! is_user_logged_in() ) {

		return false;
	}

	if ( is_object( $event ) ) {
		$event_id     = $event->event_id;
		$event_author = $event->event_author;
	} else {
		$event_id = absint( $event );
		if ( ! $event_id ) {
			return false;
		}
		if ( 'event' === $datatype ) {
			$event = mc_get_first_event( $event );
			if ( ! is_object( $event ) ) {
				$event = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . my_calendar_table() . ' WHERE event_id=%d LIMIT 1', $event_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		} else {
			$event = mc_get_event( $event_id );
		}
		$event_author = $event->event_author;
	}

	$current_user    = wp_get_current_user();
	$user            = $current_user->ID;
	$categories      = mc_get_categories( $event );
	$has_permissions = true;
	if ( is_array( $categories ) ) {
		foreach ( $categories as $cat ) {
			// If user doesn't have access to all relevant categories, prevent editing.
			if ( ! $has_permissions ) {
				continue;
			}
			$has_permissions = mc_can_edit_category( $cat, $user );
		}
	}
	$return = false;

	if ( ( current_user_can( 'mc_manage_events' ) && $has_permissions ) || ( $user === (int) $event_author ) ) {

		$return = true;
	}

	/**
	 * Filter permissions to edit an event.
	 *
	 * @hook mc_can_edit_event
	 *
	 * @param {bool} $return True if user can edit this event.
	 * @param {int}  $event_id The ID of the current event.
	 *
	 * @return {bool}
	 */
	return apply_filters( 'mc_can_edit_event', $return, $event_id );
}

/**
 * Determine max values to increment
 *
 * @param string $recur Type of recurrence.
 */
function _mc_increment_values( $recur ) {
	switch ( $recur ) {
		case 'S': // Single.
			return 0;
		case 'D': // Daily.
			return 500;
		case 'E': // Weekdays.
			return 400;
		case 'W': // Weekly.
			return 240;
		case 'B': // Biweekly.
			return 240;
		case 'M': // Monthly.
		case 'U':
			return 240;
		case 'Y':
			return 50;
		default:
			return false;
	}
}

/**
 * Deletes all instances of an event without deleting the event details. Sets stage for rebuilding event instances.
 *
 * @param int $id Event ID.
 */
function mc_delete_instances( $id ) {
	global $wpdb;
	$id = (int) $id;
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . my_calendar_event_table() . ' WHERE occur_event_id = %d', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	// After bulk deletion, optimize table.
	$wpdb->query( 'OPTIMIZE TABLE ' . my_calendar_event_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}


/**
 * Check for events with known occurrence overlap problems.
 */
function mc_list_problems() {
	$events   = get_posts(
		array(
			'post_type'  => 'mc-events',
			'meta_key'   => '_occurrence_overlap',
			'meta_value' => 'false',
		)
	);
	$list     = array();
	$problems = array();

	if ( is_array( $events ) && count( $events ) > 0 ) {
		foreach ( $events as $event ) {
			$event_id  = get_post_meta( $event->ID, '_mc_event_id', true );
			$event_url = admin_url( 'admin.php?page=my-calendar&mode=edit&event_id=' . absint( $event_id ) );
			$list[]    = '<a href="' . esc_url( $event_url ) . '">' . esc_html( $event->post_title ) . '</a>';
		}
	}

	if ( ! empty( $list ) ) {
		$problems = array( 'Problem Events' => '<ul><li>' . implode( '</li><li>', $list ) . '</li></ul>' );
	}

	return $problems;
}
