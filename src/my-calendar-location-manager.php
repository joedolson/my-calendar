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
	if ( ! empty( $_POST ) && ( ! isset( $_POST['mc_locations'] ) && ! isset( $_POST['mass_delete'] ) ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			die( 'Security check failed' );
		}
	}
	if ( isset( $_GET['location_id'] ) && 'delete' === $_GET['mode'] ) {
		$loc = absint( $_GET['location_id'] );
		echo mc_delete_location( $loc );
	}
	if ( isset( $_GET['default'] ) && is_numeric( $_GET['default'] ) ) {
		update_option( 'mc_default_location', (int) $_GET['default'] );
		mc_show_notice( __( 'Default Location Changed', 'my-calendar' ) );
	}
	?>
		<h1 class="wp-heading-inline"><?php _e( 'Manage Locations', 'my-calendar' ); ?></h1>
		<a href="<?php echo admin_url( 'admin.php?page=my-calendar-locations' ); ?>" class="page-title-action"><?php _e( 'Add New', 'my-calendar' ); ?></a>
		<hr class="wp-header-end">
	<div class="postbox-container jcd-wide">
		<div class="metabox-holder">
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2><?php _e( 'Manage Locations', 'my-calendar' ); ?></h2>

					<div class="inside">
						<?php mc_manage_locations(); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
		<?php
		$default = array( __( 'Default Location', 'my-calendar' ) => mc_default_location() );
		mc_show_sidebar( '', $default );
		?>
	</div>
	<?php
}

/**
 * Fetch default location data.
 *
 * @return string
 */
function mc_default_location() {
	$default = get_option( 'mc_default_location' );
	$output  = '';
	if ( $default ) {
		$location = mc_get_location( $default );
		$output   = mc_hcard( $location, 'true', false, 'location' );
		$output  .= '<p><a href="' . admin_url( "admin.php?page=my-calendar-locations&amp;mode=edit&amp;location_id=$default" ) . '">' . __( 'Edit Default Location', 'my-calendar' ) . '</a></p>';
	}
	if ( ! $output ) {
		$output = '<p>' . __( 'No default location selected.', 'my-calendar' ) . '</p>';
	}
	if ( '' === get_option( 'mc_location_cpt_base', '' ) ) {
		$output .= '<p><a class="button" href="' . admin_url( 'options-permalink.php#mc_location_cpt_base' ) . '">' . __( 'Update your location permalink slug', 'my-calendar' ) . '</a></p>';
	}

	return $output;
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
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			die( 'Security check failed' );
		}
		$locations = $_POST['mass_edit'];
		$i         = 0;
		$total     = 0;
		$deleted   = array();
		$prepare   = array();
		foreach ( $locations as $value ) {
			$total     = count( $locations );
			$deleted[] = absint( $value );
			$prepare[] = '%d';
			$i ++;
		}
		$prepared = implode( ',', $prepare );
		$result   = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . my_calendar_locations_table() . " WHERE location_id IN ($prepared)", $deleted ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		if ( 0 !== $result && false !== $result ) {
			// Argument: array of event IDs.
			do_action( 'mc_mass_delete_locations', $deleted );
			// Translators: Number of locations deleted, number selected.
			$message = mc_show_notice( sprintf( __( '%1$d locations deleted successfully out of %2$d selected', 'my-calendar' ), $i, $total ), false );
		} else {
			$message = mc_show_error( __( 'Your locations have not been deleted. Please investigate.', 'my-calendar' ), false );
		}
		echo $message;
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
		switch ( $_GET['orderby'] ) {
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
		$query   = $_POST['mcl'];
		$db_type = mc_get_db_type();
		if ( '' !== $query ) {
			if ( 'MyISAM' === $db_type ) {
				$query  = esc_sql( $query ); // Prepare query.
				$search = ' WHERE MATCH(' . apply_filters( 'mc_search_fields', 'location_label,location_city,location_state,location_region,location_street,location_street2,location_phone' ) . ") AGAINST ( '$query' IN BOOLEAN MODE ) ";
			} else {
				$query  = esc_sql( $query ); // Prepare query.
				$search = " WHERE location_label LIKE '%$query%' OR location_city LIKE '%$query%' OR location_state LIKE '%$query%' OR location_region LIKE '%$query%' OR location_street LIKE '%$query%' OR location_street2 LIKE '%$query%' OR location_phone LIKE '%$query%' ";
			}
		} else {
			$search = '';
		}
	}

	$query_limit = ( ( $current - 1 ) * $items_per_page );
	$locations   = $wpdb->get_results( $wpdb->prepare( 'SELECT SQL_CALC_FOUND_ROWS location_id FROM ' . my_calendar_locations_table() . " $search ORDER BY $orderby $query_order LIMIT %d, %d", $query_limit, $items_per_page ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	$found_rows  = $wpdb->get_col( 'SELECT FOUND_ROWS();' );
	$items       = $found_rows[0];

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
		printf( "<div class='tablenav'><div class='tablenav-pages'>%s</div></div>", $page_links );
	}

	if ( ! empty( $locations ) ) {
		?>
		<div class='mc-search'>
			<form action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-location-manager' ) ); ?>" method="post">
				<div>
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
				</div>
				<div>
					<label for="mc_search" class='screen-reader-text'><?php _e( 'Search', 'my-calendar' ); ?></label>
					<input type='text' role='search' name='mcl' id='mc_search' value='<?php echo ( isset( $_POST['mcl'] ) ) ? esc_attr( $_POST['mcl'] ) : ''; ?>'/>
					<input type='submit' value='<?php _e( 'Search Locations', 'my-calendar' ); ?>' class='button-secondary' />
				</div>
			</form>
		</div>
	<form action="<?php echo esc_url( add_query_arg( $_GET, admin_url( 'admin.php' ) ) ); ?>" method="post">
		<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/></div>
		<div class='mc-actions'>
			<input type="submit" class="button-secondary delete" name="mass_delete" value="<?php _e( 'Delete locations', 'my-calendar' ); ?>" />
			<div><input type='checkbox' class='selectall' id='mass_edit' data-action="mass_edit" /> <label for='mass_edit'><?php _e( 'Check all', 'my-calendar' ); ?></label></div>
		</div>
		<table class="widefat page" id="my-calendar-admin-table">
			<caption class="screen-reader-text"><?php _e( 'Location list. Use column headers to sort.', 'my-calendar' ); ?></caption>
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
				echo $col_head;
				?>
				<?php echo apply_filters( 'mc_location_manager_headers', '' ); ?>
			</tr>
			</thead>
			<?php
			$class = '';
			foreach ( $locations as $loc ) {
				$location = mc_get_location( $loc->location_id );
				$class    = ( 'alternate' === $class ) ? '' : 'alternate';
				?>
				<tr class="<?php echo $class; ?>">
					<th scope="row">
						<input type="checkbox" value="<?php echo $location->location_id; ?>" name="mass_edit[]" id="mc<?php echo $location->location_id; ?>"/>
						<label for="mc<?php echo $location->location_id; ?>"><?php echo $location->location_id; ?></label>
					</th>
					<td>
					<?php
					$card       = mc_hcard( $location, 'true', 'false', 'location' );
					$delete_url = admin_url( "admin.php?page=my-calendar-location-manager&amp;mode=delete&amp;location_id=$location->location_id" );
					$view_url   = get_the_permalink( mc_get_location_post( $location->location_id, false ) );
					$edit_url   = admin_url( "admin.php?page=my-calendar-locations&amp;mode=edit&amp;location_id=$location->location_id" );
					if ( (int) get_option( 'mc_default_location' ) === (int) $location->location_id ) {
						$card    = str_replace( '</strong>', ' ' . __( '(Default)', 'my-calendar' ) . '</strong>', $card );
						$default = '<span class="mc_default">' . __( 'Default Location', 'my-calendar' ) . '</span>';
					} else {
						$url     = admin_url( "admin.php?page=my-calendar-location-manager&amp;default=$location->location_id" );
						$default = '<a href="' . esc_url( $url ) . '">' . __( 'Set as Default', 'my-calendar' ) . '</a>';
					}
					echo $card;
					?>
						<div class='row-actions'>
							<?php
							if ( esc_url( $view_url ) ) {
								?>
							<a href="<?php echo $view_url; ?>" class='view' aria-describedby='location<?php echo $location->location_id; ?>'><?php _e( 'View', 'my-calendar' ); ?></a> |
								<?php
							}
							?>
							<a href="<?php echo $edit_url; ?>" class='edit' aria-describedby='location<?php echo $location->location_id; ?>'><?php _e( 'Edit', 'my-calendar' ); ?></a> |
							<?php echo $default; ?> | 
							<a href="<?php echo $delete_url; ?>" class="delete" aria-describedby='location<?php echo $location->location_id; ?>' onclick="return confirm('<?php _e( 'Are you sure you want to delete this location?', 'my-calendar' ); ?>')"><?php _e( 'Delete', 'my-calendar' ); ?></a>
						</div>
					</td>
					<td><?php echo esc_html( $location->location_city ); ?></td>
					<td><?php echo esc_html( $location->location_state ); ?></td>
					<?php echo apply_filters( 'mc_location_manager_cells', '', $location ); ?>
				</tr>
				<?php
			}
			?>
		</table>
		<div class="mc-actions">
		<p>
			<input type="submit" class="button-secondary delete" name="mass_delete" value="<?php _e( 'Delete locations', 'my-calendar' ); ?>" />
		</p>
		</div>
		</form>
		<?php
	} else {
		echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=my-calendar-locations' ) ) . '">' . __( 'Create a new location', 'my-calendar' ) . '</a></p>';
	}
}
