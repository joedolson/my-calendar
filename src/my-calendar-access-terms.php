<?php
/**
 * Manage access terms for events and locations.
 *
 * @category Terms
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add a term ID to the array of default accessibility terms.
 *
 * @param int    $term_id The ID of a term to add.
 * @param string $action Add or remove.
 *
 * @return bool true if added; false if not.
 */
function mc_update_default_access_terms( $term_id, $action = 'add' ) {
	$term_id       = (int) $term_id;
	$option        = isset( $_GET['terms'] ) ? 'default_location_terms' : 'default_access_terms';
	$default_terms = (array) mc_get_option( $option, array() );
	if ( ! in_array( $term_id, $default_terms, true ) && 'add' === $action ) {
		$default_terms[] = $term_id;
		mc_update_option( $option, $default_terms );

		return true;
	}
	if ( in_array( $term_id, $default_terms, true ) && 'remove' === $action ) {
		$key = array_search( $term_id, $default_terms, true );
		unset( $default_terms[ $key ] );
		mc_update_option( $option, $default_terms );

		return true;
	}

	return false;
}

/**
 * Generate form to manage categories
 */
function my_calendar_manage_access_terms() {
	$taxonomy = ( isset( $_GET['terms'] ) ) ? 'mc-location-access' : 'mc-event-access';
	?>
	<div class="wrap my-calendar-admin my-calendar-access-terms">
		<?php
		$append         = array();
		$default_access = ( 'mc-event-access' === $taxonomy ) ? mc_get_option( 'default_access_terms' ) : mc_get_option( 'default_location_terms' );
		// We do some checking to see what we're doing.
		if ( ! empty( $_POST ) ) {
			$post  = map_deep( $_POST, 'sanitize_text_field' );
			$nonce = $_REQUEST['_wpnonce'];
			if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
				die( 'My Calendar: Security check failed' );
			}
		}

		if ( isset( $_GET['default'] ) && is_numeric( $_GET['default'] ) ) {
			$add_default_access = (int) $_GET['default'];
			mc_update_default_access_terms( $add_default_access );
			mc_show_notice( __( 'Term Added to Default Event Access Terms', 'my-calendar' ), true, false, 'success' );
		}

		if ( isset( $post['mode'] ) && 'add' === $post['mode'] ) {
			$term_id = mc_create_access_term( $post );

			if ( isset( $post['mc_default_access_term'] ) ) {
				mc_update_default_access_terms( $term_id );
				$append[] = __( 'Term Added to Default Event Access Terms', 'my-calendar' );
			}

			if ( $term_id ) {
				$append = implode( ' ', $append );
				mc_show_notice( __( 'Access term added successfully', 'my-calendar' ) . ". $append", true, false, 'success' );
			} else {
				mc_show_error( __( 'Access term addition failed.', 'my-calendar' ) );
			}
		} elseif ( isset( $_GET['mode'] ) && isset( $_GET['access_term_id'] ) && 'delete' === $_GET['mode'] ) {
			$mcnonce = wp_verify_nonce( $_GET['_mcnonce'], 'mcnonce' );
			if ( $mcnonce ) {
				$term_id = (int) $_GET['access_term_id'];
				$results = wp_delete_term( $term_id, $taxonomy );
				if ( $results ) {
					// handle deleted terms.
				}
				mc_update_default_access_terms( $term_id, 'remove' );
				if ( $results ) {
					mc_show_notice( __( 'Accessibility term deleted successfully.', 'my-calendar' ), true, false, 'success' );
				}
			} else {
				mc_show_error( 'Invalid security key; please try again!', 'my-calendar' );
			}
		} elseif ( isset( $_GET['mode'] ) && isset( $_GET['access_term_id'] ) && 'edit' === $_GET['mode'] && ! isset( $post['mode'] ) ) {
			$cur_cat = (int) $_GET['access_term_id'];
			mc_edit_access_term_form( 'edit', $cur_cat, $taxonomy );
		} elseif ( isset( $post['mode'] ) && isset( $post['access_term_id'] ) && isset( $post['access_term_name'] ) && 'edit' === $post['mode'] ) {
			// This term is in the set, but not checked.
			if ( in_array( (int) $post['access_term_id'], $default_access, true ) && ! isset( $post['mc_default_access_term'] ) ) {
				mc_update_default_access_terms( (int) $post['access_term_id'], 'remove' );
			}
			// This term is checked, but not in the set.
			if ( ! in_array( (int) $post['access_term_id'], $default_access, true ) && isset( $post['mc_default_access_term'] ) ) {
				mc_update_default_access_terms( $post['access_term_id'] );
			}

			$results = wp_update_term(
				$post['access_term_id'],
				$taxonomy,
				array(
					'name' => $post['access_term_name'],
				)
			);
			if ( $results ) {
				mc_show_notice( __( 'Access term edited successfully.', 'my-calendar' ), true, false, 'success' );
			} else {
				mc_show_error( __( 'Access term was not changed.', 'my-calendar' ) );
			}
			$cur_cat = (int) $post['access_term_id'];
			mc_edit_access_term_form( 'edit', $cur_cat, $taxonomy );
		}

		if ( isset( $_GET['mode'] ) && 'edit' !== $_GET['mode'] || isset( $post['mode'] ) && 'edit' !== $post['mode'] || ! isset( $_GET['mode'] ) && ! isset( $post['mode'] ) ) {
			mc_edit_access_term_form( 'add', false, $taxonomy );
		}
		?>
		</div>
	<?php
}

/**
 * Create a access_term.
 *
 * @param array  $access_term Array of params to update.
 * @param string $taxonomy Taxonomy ID for this term.
 *
 * @return mixed boolean|int query result
 */
function mc_create_access_term( $access_term, $taxonomy = 'mc-event-access' ) {
	if ( ! isset( $access_term['access_term_name'] ) ) {
		return false;
	}
	$cat_name    = wp_strip_all_tags( $access_term['access_term_name'] );
	$term_exists = term_exists( $cat_name, $taxonomy );
	if ( ! $term_exists ) {
		$term = wp_insert_term( $cat_name, $taxonomy );
		if ( ! is_wp_error( $term ) ) {
			$term = $term['term_id'];
		} else {
			$term = false;
		}
	} else {
		$term = get_term_by( 'name', $cat_name, 'mc-event-access_term' );
		$term = $term->term_id;
	}

	return $term;
}

/**
 * Form to edit an access_term
 *
 * @param string   $view Edit or create.
 * @param int|bool $term_id access term ID.
 * @param string   $taxonomy Taxonomy for terms.
 */
function mc_edit_access_term_form( $view = 'edit', $term_id = false, $taxonomy = 'mc-event-access' ) {
	$current = false;
	if ( $term_id ) {
		$term_id = (int) $term_id;
		$current = get_term( $term_id, $taxonomy );
	} else {
		// If no access term ID, change view.
		$view = 'add';
	}
	$base_link = admin_url( 'admin.php?page=my-calendar-access-terms' );
	$base_link = isset( $_GET['terms'] ) ? add_query_arg( 'terms', 'locations' ) : $base_link;
	if ( 'add' === $view ) {
		?>
		<h1><?php esc_html_e( 'My Calendar Accessibility Terms', 'my-calendar' ); ?></h1>
		<?php
	} else {
		$heading = ( isset( $_GET['terms'] ) ) ? __( 'Edit Location Accessibility Term', 'my-calendar' ) : __( 'Edit Event Accessibility Term', 'my-calendar' );
		?>
		<h1 class="wp-heading-inline"><?php echo esc_html( $heading ); ?></h1>
		<a href="<?php echo esc_url( $base_link ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'my-calendar' ); ?></a>
		<hr class="wp-header-end">
		<?php
	}
	$link          = admin_url( 'admin.php?page=my-calendar-access-terms' );
	$location_link = add_query_arg( 'terms', 'locations', $link );
	$is_locations  = ( isset( $_GET['terms'] ) ) ? true : false;
	// These two variables raise PHPCS security errors. There is no concern.
	$is_current_events    = ( $is_locations ) ? '' : 'aria-current="page"';
	$is_current_locations = ( $is_locations ) ? 'aria-current="page"' : '';
	?>
	<div class="mc-tablinks">
			<a href="<?php echo esc_url( $link ); ?>" <?php echo $is_current_events; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Event Accessibility Terms', 'my-calendar' ); ?></a>
			<a href="<?php echo esc_url( $location_link ); ?>" <?php echo $is_current_locations; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Location Accessibility Terms', 'my-calendar' ); ?></a>
	</div>
	<div class="postbox-container jcd-wide">
		<div class="metabox-holder">

			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2>
					<?php
						$heading = ( $is_locations ) ? __( 'Location Accessibility Terms Editor', 'my-calendar' ) : __( 'Event Accessibility Terms Editor', 'my-calendar' );
						esc_html_e( 'Accessibility Terms Editor', 'my-calendar' );
					?>
					</h2>

					<div class="inside">
						<form id="my-calendar" method="post" action="<?php echo esc_url( $base_link ); ?>">
							<div>
								<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'my-calendar-nonce' ) ); ?>"/>
							<?php
							if ( 'add' === $view ) {
								?>
								<input type="hidden" name="mode" value="add"/>
								<input type="hidden" name="access_term_id" value=""/>
								<?php
							} else {
								?>
								<input type="hidden" name="mode" value="edit"/>
								<input type="hidden" name="access_term_id" value="<?php echo ( is_object( $current ) ) ? absint( $current->term_id ) : ''; ?>" />
								<?php
							}
							if ( ! empty( $current ) && is_object( $current ) ) {
								$cat_name = $current->name;
								$term_id  = $current->term_id;
							} else {
								$cat_name = '';
								$term_id  = false;
							}
							?>
							</div>
							<div class="mc-term-fields mc-flex">
							<p>
								<label for="cat_name"><?php esc_html_e( 'Accessibility Term', 'my-calendar' ); ?></label>
								<input type="text" id="cat_name" name="access_term_name" class="input" size="30" value="<?php echo esc_attr( $cat_name ); ?>"/>
							</p>
								<p>
							<?php
							$is_default = ( 'add' === $view ) ? 'false' : $term_id;
							$option     = ( $is_locations ) ? 'default_location_terms' : 'default_access_terms';
							$in_array   = in_array( $is_default, mc_get_option( $option ), true ) ? true : false;
							if ( 'add' === $view ) {
								$save_text = __( 'Add Term', 'my-calendar' );
							} else {
								$save_text = __( 'Save Changes', 'my-calendar' );
							}
							?>
									<input type="checkbox" value="on" name="mc_default_access_term" id="mc_default_access_term"<?php checked( $in_array, true ); ?> /> <label for="mc_default_access_term"><?php esc_html_e( 'Enable by default', 'my-calendar' ); ?></label>
								</p>
							</div>
							<p>
								<input type="submit" name="save" class="button-primary" value="<?php echo esc_attr( $save_text ); ?> "/>
							</p>
							<?php
							/**
							 * Execute action after access term editor form prints to screen.
							 *
							 * @hook mc_post_access_term_form
							 *
							 * @param {object} $cur_cat Current access term object.
							 * @param {string} $view Type of view ('add' or 'edit').
							 */
							do_action( 'mc_post_access_term_form', $current, $view );
							?>
						</form>
					</div>
				</div>
			</div>
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<?php
					$heading = ( $is_locations ) ? __( 'Location Accessibility Term List', 'my-calendar' ) : __( 'Event Accessibility Term List', 'my-calendar' );
					?>
					<h2><?php echo esc_html( $heading ); ?></h2>

					<div class="inside">
						<?php
						$taxonomy = ( $is_locations ) ? 'mc-location-access' : 'mc-event-access';
						mc_manage_access_terms( $taxonomy );
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
		mc_show_sidebar( '' );
}

/**
 * Generate list of accessibility terms to edit.
 *
 * @param string $taxonomy Taxonomy slug for the terms being edited.
 */
function mc_manage_access_terms( $taxonomy = 'mc-event-access' ) {
	$is_locations        = ( 'mc-event-access' === $taxonomy ) ? false : true;
	$base_link           = admin_url( 'admin.php?page=my-calendar-access-terms' );
	$base_link           = ( $is_locations ) ? add_query_arg( 'terms', 'locations', $base_link ) : $base_link;
	$default_access_term = ( $is_locations ) ? mc_get_option( 'default_location_terms' ) : mc_get_option( 'default_access_terms' );
	$args                = array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
	);
	$terms               = get_terms( $args );

	if ( ! empty( $terms ) ) {
		?>
		<table class="widefat striped page fixed mc-responsive-table mc-access-terms" id="my-calendar-admin-table">
		<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'ID', 'my-calendar' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Accessibility Service', 'my-calendar' ); ?></th>
		</tr>
		</thead>
		<?php
		foreach ( $terms as $term ) {
			$cat_name = wp_unslash( strip_tags( $term->name, mc_strip_tags() ) );
			?>
		<tr>
			<th scope="row"><?php echo absint( $term->term_id ); ?></th>
			<td>
			<?php
			echo esc_html( $cat_name );
			// Translators: Name of access term being edited.
			$edit_cat = sprintf( __( 'Edit %s', 'my-calendar' ), '<span class="screen-reader-text">' . $cat_name . '</span>' );
			// Translators: access term name.
			$delete_cat = sprintf( __( 'Delete %s', 'my-calendar' ), '<span class="screen-reader-text">' . $cat_name . '</span>' );
			// Translators: access term name.
			$default_text = sprintf( __( 'Add %s to defaults', 'my-calendar' ), '<span class="screen-reader-text">' . $cat_name . '</span>' );
			$mcnonce      = wp_create_nonce( 'mcnonce' );
			if ( in_array( $term->term_id, $default_access_term, true ) ) {
				echo ' <strong>' . esc_html__( '(Default)', 'my-calendar' ) . '</strong>';
				$default = '<span class="mc_default">' . __( 'Default Accessibility Service', 'my-calendar' ) . '</span>';
			} else {
				$url     = add_query_arg(
					array(
						'_mcnonce' => $mcnonce,
						'default'  => $term->term_id,
					),
					$base_link
				);
				$default = '<a href="' . esc_url( $url ) . '">' . $default_text . '</a>';
			}
			$edit_url = add_query_arg(
				array(
					'mode'           => 'edit',
					'access_term_id' => $term->term_id,
				),
				$base_link
			);
			?>
				<div class="row-actions">
					<a href="<?php echo esc_url( $edit_url ); ?>"
					class='edit'><?php echo wp_kses_post( $edit_cat ); ?></a> |
					<?php
					echo wp_kses_post( $default );
					// Cannot delete the default access_term.
					if ( '1' !== (string) $term->term_id ) {
						echo ' | ';
						$delete_link = add_query_arg(
							array(
								'_mcnonce'       => $mcnonce,
								'mode'           => 'delete',
								'access_term_id' => $term->term_id,
							),
							$base_link
						);
						?>
						<a href="<?php echo esc_url( $delete_link ); ?>" class="delete" onclick="return confirm('<?php esc_html_e( 'Are you sure you want to delete this access_term?', 'my-calendar' ); ?>')"><?php echo wp_kses_post( $delete_cat ); ?></a>
						<?php
					}
					?>
				</div>
			</td>
		</tr>
			<?php
		}
		?>
	</table>
		<?php
	} else {
		echo wp_kses_post( '<p>' . __( 'There are no terms in the database - or something has gone wrong!', 'my-calendar' ) . '</p>' );
	}
}

/**
 * Show access term output for editing lists.
 *
 * @param object $event Event object.
 * @param string $taxonomy Taxonomy for terms.
 * @param string $return_type Type of returnata: string[], ids[], objects[].
 *
 * @return array
 */
function mc_get_access_terms( $event, $taxonomy = 'mc-event-access', $return_type = 'string' ) {
	if ( 'mc-event-access' === $taxonomy ) {
		$terms = ( is_object( $event ) && property_exists( $event, 'event_post' ) ) ? wp_get_object_terms( $event->event_post, $taxonomy ) : array();
	} else {
		$location_post = ( is_object( $event ) && property_exists( $event, 'location_post' ) ) ? $event->location_post : false;
		$terms         = ( $location_post ) ? wp_get_object_terms( $location_post, $taxonomy ) : array();
	}
	$return = ( 'string' === $return_type ) ? '' : array();
	if ( 'string' === $return_type ) {
		// This returns an array of strings.
		$title_array = array();
		foreach ( $terms as $term ) {
			$title_array[] = sanitize_html_class( str_replace( ' ', '-', $term->name ) );
		}
		$return = $title_array;
	}
	if ( 'ids' === $return_type ) {
		foreach ( $terms as $term ) {
			$return[] = $term->term_id;
		}
	}
	if ( 'objects' === $return_type ) {
		return $terms;
	}

	return $return;
}

/**
 * Generate access term classes for a given event or location.
 *
 * @param object|array $event_or_location An event or a location.
 * @param string       $prefix            Prefix to append to class.
 *
 * @return string a single class
 */
function mc_access_class( $event_or_location, $prefix ) {
	$class = array();
	if ( property_exists( $event_or_location, 'location' ) ) {
		$class = mc_get_access_terms( $event_or_location, 'mc-event-access', 'string' );
	} elseif ( property_exists( $event_or_location, 'location_label' ) ) {
		$class = mc_get_access_terms( $event_or_location, 'mc-location-access', 'string' );
	}
	$terms = array();
	foreach ( $class as $c ) {
		$terms[] = $prefix . $c;
	}
	$classes = implode( ' ', $terms );

	return ( $classes ) ? strtolower( $classes ) : '';
}

/**
 * Set up access terms input.
 *
 * @param object|boolean $event Event object or false.
 * @param string         $taxonomy Taxonomy for terms.
 *
 * @return string
 */
function mc_admin_access_term_list( $event = false, $taxonomy = 'mc-event-access' ) {
	$terms      = ( $event ) ? mc_get_access_terms( $event, $taxonomy, 'ids' ) : array();
	$args       = array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
	);
	$taxonomies = get_terms( $args );
	$inputs     = '';
	foreach ( $taxonomies as $tax ) {
		$name    = ( 'mc-event-access' === $taxonomy ) ? 'events_access[]' : 'location_access[]';
		$id      = 'access_term_' . absint( $tax->term_id );
		$inputs .= '<li><input type="checkbox" ' . checked( true, in_array( $tax->term_id, $terms, true ), false ) . ' name="' . $name . '" id="' . $id . '" value="' . absint( $tax->term_id ) . '"> <label for="' . $id . '">' . esc_html( $tax->name ) . '</label></li>';
	}

	return $inputs;
}

/**
 * Generate access term classes.
 *
 * @param object|array $event  Event object.
 * @param string       $taxonomy Taxonomy for terms.
 * @param string       $prefix Prefix to append to class; varies on context.
 *
 * @return array an array of classes
 */
function mc_access_term_classes( $event, $taxonomy = 'mc-event-access', $prefix = 'mc-' ) {
	$terms   = wp_get_object_terms( $event->event_post, $taxonomy );
	$classes = array();
	foreach ( $terms as $term ) {
		$classes[] = sanitize_html_class( $prefix . $term->slug );
	}

	return $classes;
}
