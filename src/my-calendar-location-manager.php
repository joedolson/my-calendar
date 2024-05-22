<?php
/**
 * Manage Locations
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
 * List of locations to edit.
 */
function my_calendar_manage_locations() {
	?>
	<div class="wrap my-calendar-admin">
	<?php
	my_calendar_check_db();
	// We do some checking to see what we're doing.
	mc_mass_delete_locations();
	mc_clean_duplicate_locations();
	if ( ! empty( $_POST ) && ( ! isset( $_POST['mc_locations'] ) && ! isset( $_POST['mass_delete'] ) ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'My Calendar: Security check failed' );
		}
	}
	if ( isset( $_GET['location_id'] ) && 'delete' === $_GET['mode'] ) {
		$verify = isset( $_GET['nonce'] ) ? wp_verify_nonce( $_GET['nonce'], 'my-calendar-delete-location' ) : false;
		$loc    = absint( $_GET['location_id'] );
		if ( isset( $_GET['confirm'] ) && $verify ) {
			echo wp_kses_post( mc_delete_location( $loc ) );
		} else {
			$nonce = wp_create_nonce( 'my-calendar-delete-location' );
			$args  = array(
				'location_id' => $loc,
				'nonce'       => $nonce,
			);
			// Translators: Delete link.
			$notice = sprintf( __( 'Are you sure you want to delete this location? %s', 'my-calendar' ), '<a class="button delete" href="' . esc_url( add_query_arg( $args, admin_url( 'admin.php?page=my-calendar-location-manager&mode=delete&confirm=true' ) ) ) . '">' . __( 'Delete', 'my-calendar' ) . '</a>' );
			mc_show_notice( $notice );
		}
	}
	if ( isset( $_GET['default'] ) && is_numeric( $_GET['default'] ) ) {
		$mcnonce = wp_verify_nonce( $_GET['_mcnonce'], 'mcnonce' );
		if ( $mcnonce ) {
			mc_update_option( 'default_location', (int) $_GET['default'] );
			mc_show_notice( __( 'Default Location Changed', 'my-calendar' ) );
		} else {
			mc_show_error( __( 'Invalid security check; please try again!', 'my-calendar' ) );
		}
	}
	?>
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Locations', 'my-calendar' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-locations' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'my-calendar' ); ?></a>
		<?php
		if ( '' === mc_get_option( 'location_cpt_base', '' ) ) {
			?>
			<a class="page-title-action" href="<?php echo esc_url( admin_url( 'options-permalink.php#mc_location_cpt_base' ) ); ?>"><?php esc_html_e( 'Update location permalinks', 'my-calendar' ); ?></a>
			<?php
		}
		?>
		<hr class="wp-header-end">
	<div class="postbox-container jcd-wide">
		<div class="metabox-holder">
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2><?php esc_html_e( 'Manage Locations', 'my-calendar' ); ?></h2>

					<div class="inside">
						<?php mc_manage_locations(); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
		<?php mc_show_sidebar(); ?>
	</div>
	<?php
}

/**
 * Fetch default location data.
 *
 * @return string
 */
function mc_default_location() {
	$default = mc_get_option( 'default_location' );
	$output  = '';
	if ( $default ) {
		$location = mc_get_location( $default );
		if ( ! $location ) {
			return '';
		}
		$output  = mc_hcard( $location, 'true', false, 'location' );
		$output .= '<p><a href="' . admin_url( "admin.php?page=my-calendar-locations&amp;mode=edit&amp;location_id=$default" ) . '">' . __( 'Edit Default Location', 'my-calendar' ) . '</a></p>';
	}
	if ( ! $output ) {
		$output = '<p>' . __( 'No default location selected.', 'my-calendar' ) . '</p>';
	}

	return $output;
}


/**
 * Mass replace locations.
 *
 * @return mixed boolean/int query result.
 */
function mc_clean_duplicate_locations() {
	global $wpdb;
	// Mass delete locations.
	if ( ! empty( $_POST['mass_edit'] ) && isset( $_POST['mass_replace'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		$post  = map_deep( $_POST, 'sanitize_text_field' );
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'My Calendar: Security check failed' );
		}
		$locations = $post['mass_edit'];
		$replace   = absint( $post['mass_replace_id'] );
		$location  = mc_get_location( $replace );
		if ( ! $location ) {
			// If this isn't a valid location, don't continue.
			echo mc_show_error( __( 'An invalid ID was provided for the replacement location.', 'my-calendar' ) );
			return;
		}
		$i       = 0;
		$total   = 0;
		$deleted = array();
		foreach ( $locations as $value ) {
			// If the replacement location is checked, ignore it.
			if ( (int) $replace === (int) $value ) {
				continue;
			}
			$total  = count( $locations );
			$result = mc_delete_location( $value, 'bool' );
			if ( ! $result ) {
				$failed[] = absint( $value );
			} else {
				$deleted[] = absint( $value );
			}
			$wpdb->update(
				my_calendar_table(),
				array(
					'event_location' => $replace,
				),
				array(
					'event_location' => $value,
				),
				'%d',
				'%d'
			);
			++$i;
		}
		if ( ! empty( $deleted ) ) {
			/**
			 * Run when action to clean up duplicate locations is run.
			 *
			 * @hook mc_clean_duplicate_locations
			 *
			 * @param {array} $deleted Array of location IDs successfully deleted.
			 * @param {array} $failed Array of location IDs that were not successfully deleted.
			 */
			do_action( 'mc_clean_duplicate_locations', $deleted, $failed );
			// Translators: Number of locations deleted, number selected.
			$message = mc_show_notice( sprintf( __( '%1$d locations deleted successfully out of %2$d selected', 'my-calendar' ), count( $deleted ), $total ), false );
		} else {
			$message = mc_show_error( __( 'Your locations have not been deleted. Please investigate.', 'my-calendar' ), false );
		}
		echo wp_kses_post( $message );
	}
}

/**
 * Mass delete locations.
 *
 * @return mixed boolean/int query result.
 */
function mc_mass_delete_locations() {
	global $wpdb;
	// Mass delete locations.
	if ( ! empty( $_POST['mass_edit'] ) && isset( $_POST['mass_delete'] ) ) {
		$post  = map_deep( $_POST, 'sanitize_text_field' );
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'My Calendar: Security check failed' );
		}
		$locations = $post['mass_edit'];
		$i         = 0;
		$total     = 0;
		$deleted   = array();
		$failed    = array();
		foreach ( $locations as $value ) {
			$total  = count( $locations );
			$result = mc_delete_location( $value, 'bool' );
			if ( ! $result ) {
				$failed[] = absint( $value );
			} else {
				$deleted[] = absint( $value );
			}
			++$i;
		}
		if ( ! empty( $deleted ) ) {
			/**
			 * Run when multiple locations are deleted.
			 *
			 * @hook mc_mass_delete_locations
			 *
			 * @param {array} $deleted Array of location IDs successfully deleted.
			 * @param {array} $failed Array of location IDs that were not successfully deleted.
			 */
			do_action( 'mc_mass_delete_locations', $deleted, $failed );
			// Translators: Number of locations deleted, number selected.
			$message = mc_show_notice( sprintf( __( '%1$d locations deleted successfully out of %2$d selected', 'my-calendar' ), count( $deleted ), $total ), false );
		} else {
			$message = mc_show_error( __( 'Your locations have not been deleted. Please investigate.', 'my-calendar' ), false );
		}
		echo wp_kses_post( $message );
	}
}

/**
 * Generate list of locations.
 */
function mc_manage_locations() {
	global $wpdb;
	$orderby = 'location_label';
	$sortby  = 'location';

	if ( isset( $_GET['orderby'] ) ) {
		$sortby = $_GET['orderby'];
		switch ( $sortby ) {
			case 'city':
				$orderby = 'location_city';
				break;
			case 'state':
				$orderby = 'location_state';
				break;
			case 'id':
				$orderby = 'location_id';
				break;
			default:
				$orderby = 'location_label';
		}
	}
	$order       = 'ASC';
	$query_order = 'DESC';
	if ( isset( $_GET['order'] ) ) {
		switch ( $_GET['order'] ) {
			case 'asc':
				$order       = 'DESC';
				$query_order = 'ASC';
				break;
			case 'desc':
				$order       = 'ASC';
				$query_order = 'DESC';
				break;
			default:
				$order       = 'ASC';
				$query_order = 'DESC';
		}
	}
	// Pull the locations from the database.
	$items_per_page = 50;
	$search         = '';
	$current        = empty( $_GET['paged'] ) ? 1 : intval( $_GET['paged'] );
	if ( isset( $_POST['mcl'] ) ) {
		$query   = esc_sql( $_POST['mcl'] );
		$length  = strlen( $query );
		$db_type = mc_get_db_type();
		if ( '' !== $query ) {
			if ( 'MyISAM' === $db_type && $length > 3 ) {
				/**
				 * Customize admin search MATCH columns when db is MyISAM.
				 *
				 * @hook mc_search_fields
				 *
				 * @param {string} $fields Comma-separated list of column names.
				 *
				 * @return {string}
				 */
				$search = ' WHERE MATCH(' . apply_filters( 'mc_search_fields', 'location_label,location_city,location_state,location_region,location_country,location_street,location_street2,location_phone' ) . ") AGAINST ( '$query' IN BOOLEAN MODE ) ";
			} else {
				$search = " WHERE location_label LIKE '%$query%' OR location_city LIKE '%$query%' OR location_state LIKE '%$query%' OR location_region LIKE '%$query%' OR location_country LIKE '%$query%' OR location_street LIKE '%$query%' OR location_street2 LIKE '%$query%' OR location_phone LIKE '%$query%' ";
			}
		} else {
			$search = '';
		}
	}

	$query_limit = ( ( $current - 1 ) * $items_per_page );
	$locations   = $wpdb->get_results( $wpdb->prepare( 'SELECT SQL_CALC_FOUND_ROWS location_id FROM ' . my_calendar_locations_table() . " $search ORDER BY $orderby $query_order LIMIT %d, %d", $query_limit, $items_per_page ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	$found_rows  = $wpdb->get_col( 'SELECT FOUND_ROWS();' );
	$items       = $found_rows[0];
	$pagination  = '';

	$num_pages = ceil( $items / $items_per_page );
	if ( $num_pages > 1 ) {
		$page_links = paginate_links(
			array(
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'prev_text' => __( '&laquo; Previous<span class="screen-reader-text"> Locations</span>', 'my-calendar' ),
				'next_text' => __( 'Next<span class="screen-reader-text"> Locations</span> &raquo;', 'my-calendar' ),
				'total'     => $num_pages,
				'current'   => $current,
				'mid_size'  => 1,
			)
		);
		$pagination = sprintf( "<div class='tablenav'><div class='tablenav-pages'>%s</div></div>", $page_links );
	}

	if ( ! empty( $locations ) ) {
		?>
	<div class="mc-admin-header locations">
		<?php echo wp_kses_post( $pagination ); ?>
		<div class='mc-search'>
			<form action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-location-manager' ) ); ?>" method="post" role='search'>
				<div>
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
				</div>
				<div>
					<label for="mc_search" class='screen-reader-text'><?php esc_html_e( 'Search Locations', 'my-calendar' ); ?></label>
					<input type='text' name='mcl' id='mc_search' value='<?php echo ( isset( $_POST['mcl'] ) ) ? esc_attr( $_POST['mcl'] ) : ''; ?>'/>
					<input type='submit' value='<?php esc_attr_e( 'Search', 'my-calendar' ); ?>' class='button-secondary' />
				</div>
			</form>
		</div>
	</div>
	<form action="<?php echo esc_url( add_query_arg( $_GET, admin_url( 'admin.php' ) ) ); ?>" method="post">
		<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/></div>
		<div class='mc-actions'>
			<input type="submit" class="button-secondary delete" name="mass_delete" value="<?php esc_attr_e( 'Delete locations', 'my-calendar' ); ?>" />
			<div class="mass-replace-wrap">
				<input type="checkbox" name="mass_replace_on" disabled id="mass_replace_on" value="true"><label for="mass_replace_on"><?php esc_attr_e( 'Merge duplicates', 'my-calendar' ); ?></label>
				<div class="mass-replace-container">
					<label for="mass-replace"><?php _e( 'Replacement ID', 'my-calendar' ); ?></label><input type="text" size="4" id="mass-replace" name="mass_replace_id" class="mass-replace" value="" />
					<input type="submit" class="button-secondary delete" name="mass_replace" value="<?php _e( 'Replace', 'my-calendar' ); ?>" />
				</div>
			</div>
			<div><input type='checkbox' class='selectall' id='mass_edit' data-action="mass_edit" /> <label for='mass_edit'><?php esc_html_e( 'Check all', 'my-calendar' ); ?></label></div>
		</div>
		<table class="widefat striped page" id="my-calendar-admin-table">
			<caption class="screen-reader-text"><?php esc_html_e( 'Location list. Use column headers to sort.', 'my-calendar' ); ?></caption>
			<thead>
			<tr>
				<?php
				$admin_url = admin_url( "admin.php?page=my-calendar-location-manager&paged=$current&order=" . strtolower( $order ) );
				$url       = add_query_arg( 'orderby', 'id', $admin_url );
				$col_head  = mc_table_header( __( 'ID', 'my-calendar' ), $order, $sortby, 'id', $url );
				$url       = add_query_arg( 'orderby', 'location', $admin_url );
				$col_head .= mc_table_header( __( 'Location', 'my-calendar' ), $order, $sortby, 'location', $url );
				$url       = add_query_arg( 'orderby', 'city', $admin_url );
				$col_head .= mc_table_header( __( 'City', 'my-calendar' ), $order, $sortby, 'city', $url );
				$url       = add_query_arg( 'orderby', 'state', $admin_url );
				$col_head .= mc_table_header( __( 'State/Province', 'my-calendar' ), $order, $sortby, 'state', $url );
				echo wp_kses( $col_head, mc_kses_elements() );
				/**
				 * Add custom column table headers to Location Manager.
				 *
				 * @hook mc_location_manager_headers
				 *
				 * @param {string} $headers HTML output. Appends HTML in the end column of the location manager table row.
				 *
				 * @return {string}
				 */
				$headers = apply_filters( 'mc_location_manager_headers', '' );
				echo $headers;
				?>
			</tr>
			</thead>
			<tbody>
			<?php
			$default_location = mc_get_option( 'default_location', '' );
			if ( $default_location ) {
				$default = mc_get_location( $default_location );
				echo wp_kses( mc_location_manager_row( $default ), mc_kses_elements() );
			}
			foreach ( $locations as $loc ) {
				if ( (int) $default_location === (int) $loc->location_id ) {
					continue;
				}
				$location = mc_get_location( $loc->location_id );
				echo wp_kses( mc_location_manager_row( $location ), mc_kses_elements() );
			}
			?>
			</tbody>
		</table>
		<div class="mc-actions">
		<p>
			<input type="submit" class="button-secondary delete" name="mass_delete" value="<?php _e( 'Delete locations', 'my-calendar' ); ?>" />
		</p>
		</div>
		</form>
		<?php
	} else {
		if ( isset( $_POST['mcl'] ) ) {
			echo '<p>' . esc_html__( 'No results found for your search query.', 'my-calendar' ) . '</p>';
		}
		echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=my-calendar-locations' ) ) . '">' . __( 'Create a new location', 'my-calendar' ) . '</a></p>';
	}
}

/**
 * Verify that a location has valid fields. If a location has no valid data, delete it.
 *
 * @param object $location Location object.
 *
 * @return bool
 */
function mc_verify_location( $location ) {
	if ( ! is_object( $location ) ) {
		return true;
	}
	$location_id = $location->location_id;
	$copy        = clone $location;
	// Unset location ID and location Post, which will always exist.
	$copy->location_id   = '';
	$copy->location_post = '';
	$json                = json_encode( $copy );
	if ( '{"location_id":"","location_label":"","location_street":"","location_street2":"","location_city":"","location_state":"","location_postcode":"","location_region":"","location_url":"","location_country":"","location_longitude":"0.000000","location_latitude":"0.000000","location_zoom":"16","location_phone":"","location_phone2":"","location_access":"","location_post":""}' === $json ) {
		if ( $location_id ) {
			mc_delete_location( $location_id );
			mc_location_delete_post( true, $location_id );
		}

		return false;
	}

	return true;
}

/**
 * Generate the location manager row for a location.
 *
 * @param object $location Location object.
 *
 * @return string
 */
function mc_location_manager_row( $location ) {
	$card   = mc_hcard( $location, 'true', 'false', 'location' );
	$verify = mc_verify_location( $location );
	if ( ! $verify ) {
		return '';
	}

	if ( (int) mc_get_option( 'default_location' ) === (int) $location->location_id ) {
		$card    = str_replace( '</strong>', ' ' . __( '(Default)', 'my-calendar' ) . '</strong>', $card );
		$default = '<span class="mc_default">' . __( 'Default Location', 'my-calendar' ) . '</span>';
	} else {
		$mcnonce = wp_create_nonce( 'mcnonce' );
		$url     = add_query_arg( '_mcnonce', $mcnonce, admin_url( "admin.php?page=my-calendar-location-manager&amp;default=$location->location_id" ) );
		$default = '<a href="' . esc_url( $url ) . '">' . __( 'Set as Default', 'my-calendar' ) . '</a>';
	}
	$delete_url = admin_url( "admin.php?page=my-calendar-location-manager&amp;mode=delete&amp;location_id=$location->location_id" );
	$view_url   = get_the_permalink( mc_get_location_post( $location->location_id, false ) );
	$edit_url   = admin_url( "admin.php?page=my-calendar-locations&amp;mode=edit&amp;location_id=$location->location_id" );
	$view_link  = '';
	if ( $view_url && esc_url( $view_url ) ) {
		$view_link = "<a href='" . esc_url( $view_url ) . "' class='view' aria-describedby='location" . absint( $location->location_id ) . "'>" . esc_html__( 'View', 'my-calendar' ) . '</a> | ';
	}

	/**
	 * Add custom column table cells to Location Manager.
	 *
	 * @hook mc_location_manager_cells
	 *
	 * @param {string} $custom_location_cells HTML output. Appends HTML in the end column of the location manager table row.
	 * @param {object} $location Locatino object.
	 * @return {string}
	 */
	$custom_location_cells = apply_filters( 'mc_location_manager_cells', '', $location );

	$row  = '';
	$row .= '
	<tr>
		<th scope="row">
			<input type="checkbox" value="' . absint( $location->location_id ) . '" name="mass_edit[]" id="mc' . absint( $location->location_id ) . '"/>
			<label for="mc' . absint( $location->location_id ) . '">' . $location->location_id . '</label>
		</th>
		<td>' . $card . '
			<div class="row-actions">' . $view_link . '
				<a href="' . esc_url( $edit_url ) . '" class="edit" aria-describedby="location' . absint( $location->location_id ) . '">' . esc_html__( 'Edit', 'my-calendar' ) . '</a> | ' . $default . ' | 
				<a href="' . esc_url( $delete_url ) . '" class="delete" aria-describedby="location' . absint( $location->location_id ) . '">' . esc_html__( 'Delete', 'my-calendar' ) . '</a>
			</div>
		</td>
		<td>' . esc_html( $location->location_city ) . '</td>
		<td>' . esc_html( $location->location_state ) . '</td>' . $custom_location_cells . '
	</tr>';

	return $row;
}
