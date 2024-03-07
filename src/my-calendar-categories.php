<?php
/**
 * Manage event categories.
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
 * Update a single field of a category
 *
 * @param string $field field name.
 * @param int    $data Data to change.
 * @param int    $category Category ID.
 *
 * @return int|bool Row ID or false.
 */
function mc_update_category( $field, $data, $category ) {
	global $wpdb;
	$field  = sanitize_key( $field );
	$result = $wpdb->query( $wpdb->prepare( 'UPDATE ' . my_calendar_categories_table() . " SET $field = %d WHERE category_id=%d", $data, $category ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	// Delete category caches.
	delete_transient( 'mc_cat_' . $category );
	delete_transient( 'mc_generated_category_styles' );

	return $result;
}

/**
 * List image files in a directory.
 *
 * @param string $directory Path to directory.
 *
 * @return array images in directory.
 */
function mc_directory_list( $directory ) {
	if ( ! function_exists( 'mime_content_type' ) ) {
		return array();
	}
	if ( ! file_exists( $directory ) ) {
		return array();
	}
	$results = ( WP_DEBUG ) ? array() : get_transient( 'mc_icon_list' );
	if ( empty( $results ) ) {
		$results = array();
		$handler = opendir( $directory );
		// keep going until all files in directory have been read.
		while ( false !== ( $file = readdir( $handler ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			// if $file isn't this directory or its parent add it to the results array.
			if ( strlen( $file ) < 5 ) {
				continue;
			}
			$directory = trailingslashit( $directory );
			if ( filesize( $directory . $file ) > 11 ) {
				if ( '.' !== $file && '..' !== $file && ! is_dir( $directory . $file ) && (
						'image/svg_xml' === mime_content_type( $directory . $file ) ||
						'image/svg' === mime_content_type( $directory . $file ) ||
						'image/svg+xml' === mime_content_type( $directory . $file ) ||
						exif_imagetype( $directory . $file ) === IMAGETYPE_GIF ||
						exif_imagetype( $directory . $file ) === IMAGETYPE_PNG ||
						exif_imagetype( $directory . $file ) === IMAGETYPE_JPEG )
				) {
					$results[] = $file;
				}
			}
		}
		closedir( $handler );
		sort( $results, SORT_STRING );
		set_transient( 'mc_icon_list', $results, MONTH_IN_SECONDS );
	}

	return $results;
}

/**
 * Return SQL to select only categories *not* marked as private
 *
 * @return string partial SQL statement
 */
function mc_private_categories() {
	$cats = '';
	if ( ! is_user_logged_in() ) {
		$categories = mc_get_private_categories();
		$cats       = implode( ',', $categories );
		if ( '' !== $cats ) {
			$cats = " AND c.category_id NOT IN ($cats)";
		}
	}

	return $cats;
}

/**
 * Fetch array of private categories.
 *
 * @uses filter mc_private_categories
 *
 * @return array private categories
 */
function mc_get_private_categories() {
	$mcdb       = mc_is_remote_db();
	$table      = my_calendar_categories_table();
	$query      = 'SELECT category_id FROM `' . $table . '` WHERE category_private = 1';
	$results    = $mcdb->get_results( $query );
	$categories = array();
	foreach ( $results as $result ) {
		$categories[] = $result->category_id;
	}

	/**
	 * Filter which categories are considered private.
	 *
	 * @hook mc_private_categories
	 *
	 * @param {array} $categories Array of category objects.
	 *
	 * @return {array}
	 */
	return apply_filters( 'mc_private_categories', $categories );
}

/**
 * Generate form to manage categories
 */
function my_calendar_manage_categories() {
	global $wpdb;
	?>
	<div class="wrap my-calendar-admin">
		<?php
		my_calendar_check_db();
		$append           = array();
		$default_category = mc_get_option( 'default_category' );
		$holiday_category = mc_get_option( 'skip_holidays_category' );
		// We do some checking to see what we're doing.
		if ( ! empty( $_POST ) ) {
			$post  = map_deep( $_POST, 'sanitize_text_field' );
			$nonce = $_REQUEST['_wpnonce'];
			if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
				die( 'My Calendar: Security check failed' );
			}
		}

		if ( isset( $_GET['default'] ) && is_numeric( $_GET['default'] ) ) {
			mc_update_option( 'default_category', (int) $_GET['default'] );
			$default_category = (int) $_GET['default'];
			mc_show_notice( __( 'Default Category Changed', 'my-calendar' ) );
		}

		if ( isset( $post['mode'] ) && 'add' === $post['mode'] ) {
			$cat_id = mc_create_category( $post );

			if ( isset( $post['mc_default_category'] ) ) {
				mc_update_option( 'default_category', $cat_id );
				$append[] = __( 'Default category changed.', 'my-calendar' );
			}

			if ( isset( $post['mc_skip_holidays_category'] ) ) {
				mc_update_option( 'skip_holidays_category', $cat_id );
				$append[] = __( 'Holiday category changed.', 'my-calendar' );
			}

			if ( $cat_id ) {
				$append = implode( ' ', $append );
				mc_show_notice( __( 'Category added successfully', 'my-calendar' ) . ". $append" );
			} else {
				mc_show_error( __( 'Category addition failed.', 'my-calendar' ) );
			}
		} elseif ( isset( $_GET['mode'] ) && isset( $_GET['category_id'] ) && 'delete' === $_GET['mode'] ) {
			$mcnonce = wp_verify_nonce( $_GET['_mcnonce'], 'mcnonce' );
			if ( $mcnonce ) {
				$cat_id  = (int) $_GET['category_id'];
				$results = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . my_calendar_categories_table() . ' WHERE category_id=%d', $cat_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				if ( $results ) {
					// Set events with deleted category as primary to default category as primary.
					$set_category = ( is_numeric( $default_category ) && $cat_id !== (int) $default_category ) ? absint( $default_category ) : 1;
					$cal_results  = $wpdb->query( $wpdb->prepare( 'UPDATE `' . my_calendar_table() . '` SET event_category=%d WHERE event_category=%d', $set_category, $cat_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					// Update existing relationships with this category.
					$rel_results = $wpdb->query( $wpdb->prepare( 'UPDATE `' . my_calendar_category_relationships_table() . '` SET category_id = %d WHERE category_id=%d', $set_category, $cat_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					// clean out duplicates.
					$wpdb->query( 'DELETE cr1 FROM `' . my_calendar_category_relationships_table() . '` AS cr1 INNER JOIN `' . my_calendar_category_relationships_table() . '` AS cr2 WHERE cr1.relationship_id > cr2.relationship_id AND cr1.category_id = cr2.category_id AND cr1.event_id = cr2.event_id' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				} else {
					$cal_results = false;
				}
				if ( $default_category === (string) $cat_id ) {
					mc_update_option( 'default_category', '' );
				}
				if ( $results && ( $cal_results || $rel_results ) ) {
					mc_show_notice( __( 'Category deleted successfully. Categories in calendar updated.', 'my-calendar' ) );
				} elseif ( $results && ! $cal_results ) {
					mc_show_notice( __( 'Category deleted successfully. Category was not in use; categories in calendar not updated.', 'my-calendar' ) );
				} elseif ( ! $results && $cal_results ) {
					mc_show_error( __( 'Category not deleted. Categories in calendar updated.', 'my-calendar' ) );
				}
			} else {
				mc_show_error( 'Invalid security key; please try again!', 'my-calendar' );
			}
		} elseif ( isset( $_GET['mode'] ) && isset( $_GET['category_id'] ) && 'edit' === $_GET['mode'] && ! isset( $post['mode'] ) ) {
			$cur_cat = (int) $_GET['category_id'];
			mc_edit_category_form( 'edit', $cur_cat );
		} elseif ( isset( $post['mode'] ) && isset( $post['category_id'] ) && isset( $post['category_name'] ) && isset( $post['category_color'] ) && 'edit' === $post['mode'] ) {
			$append = array();
			if ( isset( $post['mc_default_category'] ) && $default_category !== $post['category_id'] ) {
				mc_update_option( 'default_category', (int) $post['category_id'] );
				$append[] = __( 'Default category changed.', 'my-calendar' );
			} else {
				if ( $default_category === $post['category_id'] ) {
					mc_update_option( 'default_category', '' );
					$append[] = __( 'Default category removed.', 'my-calendar' );
				}
			}
			if ( isset( $post['mc_skip_holidays_category'] ) && $holiday_category !== $post['category_id'] ) {
				mc_update_option( 'skip_holidays_category', (int) $post['category_id'] );
				$append[] = __( 'Holiday category changed.', 'my-calendar' );
			} else {
				if ( $holiday_category === (string) $post['category_id'] ) {
					mc_update_option( 'skip_holidays_category', '' );
					$append[] = __( 'Holiday category removed.', 'my-calendar' );
				}
			}

			$update  = array(
				'category_name'    => $post['category_name'],
				'category_color'   => $post['category_color'],
				'category_icon'    => $post['category_icon'],
				'category_private' => ( ( isset( $post['category_private'] ) ) ? 1 : 0 ),
			);
			$results = mc_update_cat( $update );
			$append  = implode( ' ', $append );
			if ( $results || '' !== trim( $append ) ) {
				mc_show_notice( __( 'Category edited successfully.', 'my-calendar' ) . " $append" );
			} else {
				mc_show_error( __( 'Category was not changed.', 'my-calendar' ) . " $append" );
			}
			$cur_cat = (int) $post['category_id'];
			mc_edit_category_form( 'edit', $cur_cat );
		}

		if ( isset( $_GET['mode'] ) && 'edit' !== $_GET['mode'] || isset( $post['mode'] ) && 'edit' !== $post['mode'] || ! isset( $_GET['mode'] ) && ! isset( $post['mode'] ) ) {
			mc_edit_category_form( 'add' );
		}
		?>
		</div>
	<?php
}

/**
 * Set up new category relationships for assigned cats to an event
 *
 * @param array $cats array of category IDs.
 * @param int   $event_id My Calendar event ID.
 */
function mc_set_category_relationships( $cats, $event_id ) {
	global $wpdb;
	if ( is_array( $cats ) ) {
		foreach ( $cats as $cat ) {
			$wpdb->insert(
				my_calendar_category_relationships_table(),
				array(
					'event_id'    => $event_id,
					'category_id' => $cat,
				),
				array( '%d', '%d' )
			);
		}
	}
}

/**
 * Update existing category relationships for an event
 *
 * @param array $cats array of category IDs.
 * @param int   $event_id My Calendar event ID.
 */
function mc_update_category_relationships( $cats, $event_id ) {
	global $wpdb;
	$old_cats = mc_get_categories( $event_id, 'testing' );
	if ( $old_cats === $cats ) {
		return;
	}
	$wpdb->delete( my_calendar_category_relationships_table(), array( 'event_id' => $event_id ), '%d' );

	if ( is_array( $cats ) && ! empty( $cats ) ) {
		foreach ( $cats as $cat ) {
			$wpdb->insert(
				my_calendar_category_relationships_table(),
				array(
					'event_id'    => $event_id,
					'category_id' => $cat,
				),
				array( '%d', '%d' )
			);
		}
	}
}

/**
 * Update a category.
 *
 * @param array $category Array of params to update.
 *
 * @return mixed boolean/int query result
 */
function mc_update_cat( $category ) {
	global $wpdb;
	$category_id = (int) $_POST['category_id'];
	$formats     = array( '%s', '%s', '%s', '%d', '%d', '%d' );
	$where       = array(
		'category_id' => $category_id,
	);
	$cat_name    = strip_tags( $category['category_name'] );
	$term_exists = term_exists( $cat_name, 'mc-event-category' );
	if ( ! $term_exists ) {
		$term = wp_insert_term( $cat_name, 'mc-event-category' );
		if ( ! is_wp_error( $term ) ) {
			$term = $term['term_id'];
		} else {
			$term = false;
		}
	} else {
		$term = get_term_by( 'name', $cat_name, 'mc-event-category' );
		$term = $term->term_id;
	}
	$category['category_term'] = $term;
	// Delete category icons from database so they will be updated.
	mc_delete_category_icon( $category_id );

	$result = $wpdb->update( my_calendar_categories_table(), $category, $where, $formats, '%d' );
	delete_transient( 'mc_cat_' . $category_id );

	return $result;
}

/**
 * Create a category.
 *
 * @param array $category Array of params to update.
 *
 * @return mixed boolean|int query result
 */
function mc_create_category( $category ) {
	global $wpdb;

	if ( ! isset( $category['category_name'] ) ) {
		return false;
	}
	$formats     = array( '%s', '%s', '%s', '%d', '%d' );
	$cat_name    = strip_tags( $category['category_name'] );
	$term_exists = term_exists( $cat_name, 'mc-event-category' );
	if ( ! $term_exists ) {
		$term = wp_insert_term( $cat_name, 'mc-event-category' );
		if ( ! is_wp_error( $term ) ) {
			$term = $term['term_id'];
		} else {
			$term = false;
		}
	} else {
		$term = get_term_by( 'name', $cat_name, 'mc-event-category' );
		$term = $term->term_id;
	}
	$add = array(
		'category_name'    => $category['category_name'],
		'category_color'   => isset( $category['category_color'] ) ? $category['category_color'] : '#ffffff',
		'category_icon'    => isset( $category['category_icon'] ) ? $category['category_icon'] : '',
		'category_private' => ( ( isset( $category['category_private'] ) && ( 'on' === $category['category_private'] || '1' === (string) $category['category_private'] ) ) ? 1 : 0 ),
		'category_term'    => $term,
	);

	$add = array_map( 'mc_kses_post', $add );
	/**
	 * Filter data before inserting a new category.
	 *
	 * @hook mc_pre_add_category
	 *
	 * @param {array} $add Data to be inserted.
	 * @param {array} $category Category data passed to insert function.
	 *
	 * @return {array}
	 */
	$add = apply_filters( 'mc_pre_add_category', $add, $category );
	$wpdb->insert( my_calendar_categories_table(), $add, $formats );
	$cat_id = $wpdb->insert_id;
	/**
	 * Execute action after inserting a new category into the My Calendar database.
	 *
	 * @hook mc_post_add_category
	 *
	 * @param {array}  $add Category data array used for DB insert.
	 * @param {int}    $cat_id ID of new category.
	 * @param {string} $category Original array sent to function.
	 */
	do_action( 'mc_post_add_category', $add, $cat_id, $category );

	return $cat_id;
}

/**
 * Count occurrences of this category.
 *
 * @param int $category_id Category ID.
 *
 * @return int
 */
function mc_get_category_count( $category_id ) {
	global $wpdb;
	$result = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(DISTINCT event_id) FROM ' . my_calendar_category_relationships_table() . ' WHERE category_id = %d', $category_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return $result;
}

/**
 * Form to edit a category
 *
 * @param string   $view Edit or create.
 * @param int|bool $cat_id Category ID.
 */
function mc_edit_category_form( $view = 'edit', $cat_id = false ) {
	global $wpdb;
	$dir     = plugin_dir_path( __FILE__ );
	$url     = plugin_dir_url( __FILE__ );
	$cur_cat = false;
	if ( $cat_id ) {
		$cat_id  = (int) $cat_id;
		$cur_cat = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . my_calendar_categories_table() . ' WHERE category_id=%d', $cat_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	} else {
		// If no category ID, change view.
		$view = 'add';
	}
	if ( 'add' === $view ) {
		?>
		<h1><?php esc_html_e( 'Categories', 'my-calendar' ); ?></h1>
		<?php
	} else {
		?>
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Edit Category', 'my-calendar' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-categories' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'my-calendar' ); ?></a>
		<hr class="wp-header-end">
		<?php
	}
	?>
	<div class="postbox-container jcd-wide">
		<div class="metabox-holder">

			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2><?php esc_html_e( 'Category Editor', 'my-calendar' ); ?></h2>

					<div class="inside">
						<form id="my-calendar" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-categories' ) ); ?>">
							<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/></div>
							<?php
							if ( 'add' === $view ) {
								?>
								<div>
									<input type="hidden" name="mode" value="add"/>
									<input type="hidden" name="category_id" value=""/>
								</div>
								<?php
							} else {
								?>
								<div>
									<input type="hidden" name="mode" value="edit"/>
									<input type="hidden" name="category_id" value="<?php echo ( is_object( $cur_cat ) ) ? absint( $cur_cat->category_id ) : ''; ?>" />
								</div>
								<?php
							}
							if ( ! empty( $cur_cat ) && is_object( $cur_cat ) ) {
								$color  = ( strpos( $cur_cat->category_color, '#' ) !== 0 ) ? '#' : '';
								$color .= $cur_cat->category_color;
								$icon   = $cur_cat->category_icon;
							} else {
								$color = '';
								$icon  = '';
							}
							$color = strip_tags( $color );
							if ( ! empty( $cur_cat ) && is_object( $cur_cat ) ) {
								$cat_name = stripslashes( $cur_cat->category_name );
							} else {
								$cat_name = '';
							}
							?>
							<p>
								<label for="cat_name"><?php esc_html_e( 'Category Name', 'my-calendar' ); ?></label>
								<input type="text" id="cat_name" name="category_name" class="input" size="30" value="<?php echo esc_attr( $cat_name ); ?>"/>
								<label for="cat_color"><?php esc_html_e( 'Color', 'my-calendar' ); ?></label>
								<input type="text" id="cat_color" name="category_color" class="mc-color-input" size="10" maxlength="7" value="<?php echo ( '#' !== $color ) ? esc_attr( $color ) : ''; ?>"/>
							</p>
							<?php
							if ( ! function_exists( 'mime_content_type' ) ) {
								?>
								<div class="notice"><p><?php _e( 'Category Icons require the <code>mime_content_type</code> function, which is not available on your system.', 'my-calendar' ); ?></p></div>
								<?php
							}
							?>
							<input type='hidden' name='category_icon' id="mc_category_icon" value='<?php echo esc_attr( $icon ); ?>' />
							<div class="category-icon-selector">
								<label for="cat_icon"><?php esc_html_e( 'Category Icon', 'my-calendar' ); ?></label>
								<div class="mc-autocomplete autocomplete" id="mc-icons-autocomplete">
									<input type="text" class="autocomplete-input" id="cat_icon" name='category_icon' placeholder="<?php _e( 'Search for an icon', 'my-calendar' ); ?>" value="<?php echo esc_attr( $icon ); ?>" />
									<ul class="autocomplete-result-list"></ul>
								</div>
								<?php mc_help_link( __( 'Show Category Icons', 'my-calendar' ), __( 'Category Icons', 'my-calendar' ), 'Category Icons', 6 ); ?>
							</div>
							<?php
							if ( 'add' === $view ) {
								$private_checked = false;
							} else {
								if ( ! empty( $cur_cat ) && is_object( $cur_cat ) && '1' === (string) $cur_cat->category_private ) {
									$private_checked = true;
								} else {
									$private_checked = false;
								}
							}
							$current = ( 'add' === $view ) ? 'false' : $cur_cat->category_id;
							?>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Category Meta', 'my-calendar' ); ?></legend>
								<ul class='checkboxes'>
									<li><input type="checkbox" value="on" name="category_private" id="cat_private"<?php checked( $private_checked, true ); ?> /> <label for="cat_private"><?php esc_html_e( 'Private (logged-in users only)', 'my-calendar' ); ?></label></li>
									<li><input type="checkbox" value="on" name="mc_default_category" id="mc_default_category"<?php checked( mc_get_option( 'default_category' ), $current ); ?> /> <label for="mc_default_category"><?php esc_html_e( 'Default', 'my-calendar' ); ?></label></li>
									<li><input type="checkbox" value="on" name="mc_skip_holidays_category" id="mc_shc"<?php checked( mc_get_option( 'skip_holidays_category' ), $current ); ?> /> <label for="mc_shc"><?php esc_html_e( 'Holiday', 'my-calendar' ); ?></label></li>
								</ul>
								<?php
								/**
								 * Insert custom fields for categories.
								 *
								 * @hook mc_category_fields
								 *
								 * @param {string} $output Field HTML output.
								 * @param {object} $cur_cat Current category object.
								 *
								 * @return {string}
								 */
								echo apply_filters( 'mc_category_fields', '', $cur_cat );
								if ( 'add' === $view ) {
									$save_text = __( 'Add Category', 'my-calendar' );
								} else {
									$save_text = __( 'Save Changes', 'my-calendar' );
								}
								?>
							</fieldset>
							<p>
								<input type="submit" name="save" class="button-primary" value="<?php echo esc_attr( $save_text ); ?> "/>
							</p>
							<?php
							/**
							 * Execute action after category editor form prints to screen.
							 *
							 * @hook mc_post_category_form
							 *
							 * @param {object} $cur_cat Current category object.
							 * @param {string} $view Type of view ('add' or 'edit').
							 */
							do_action( 'mc_post_category_form', $cur_cat, $view );
							?>
						</form>
					</div>
				</div>
			</div>
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2><?php esc_html_e( 'Category List', 'my-calendar' ); ?></h2>

					<div class="inside">
						<?php mc_manage_categories(); ?>
					</div>
				</div>
			</div>
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2><?php esc_html_e( 'Category Settings', 'my-calendar' ); ?></h2>

					<div class="inside">
						<?php echo wp_kses( mc_category_settings(), mc_kses_elements() ); ?>
					</div>
				</div>
			</div>
		<?php
		if ( mc_is_custom_icon() ) {
			?>
			<div class="ui-sortable meta-box-sortables" id="custom-icons">
				<div class="postbox">
					<h2><?php esc_html_e( 'Custom Icons', 'my-calendar' ); ?></h2>

					<div class="inside">
					<ul class="checkboxes icon-list">
			<?php
			$dir       = plugin_dir_path( __FILE__ );
			$url       = plugin_dir_url( __FILE__ );
			$directory = trailingslashit( str_replace( '/my-calendar', '', $dir ) ) . 'my-calendar-custom/icons';
			$path      = str_replace( '/my-calendar', '/my-calendar-custom/icons', $url );
			$iconlist  = mc_directory_list( $directory );
			foreach ( $iconlist as $icon ) {
				echo '<li class="category-icon"><code>' . $icon . '</code><img src="' . $path . '/' . esc_html( $icon ) . '" alt="" aria-hidden="true"></li>';
			}
			?>
					</ul>
					</div>
				</div>
			</div>
			<?php
		} else {
			// Preload icon cache.
			mc_display_icons();
		}
		?>
		</div>
	</div>
	<?php
		mc_show_sidebar( '' );
}

/**
 * Update category settings.
 *
 * @return string Update message.
 */
function mc_category_settings_update() {
	$message = '';
	$nonce   = ( isset( $_POST['_wpnonce'] ) ) ? $_POST['_wpnonce'] : false;
	if ( isset( $_POST['mc_category_settings'] ) && wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
		mc_update_option( 'hide_icons', ( ! empty( $_POST['mc_hide_icons'] ) && 'on' === $_POST['mc_hide_icons'] ) ? 'true' : 'false' );
		mc_update_option( 'apply_color', $_POST['mc_apply_color'] );

		$message = mc_show_notice( __( 'My Calendar Category Configuration Updated', 'my-calendar' ), false );
	}

	return $message;
}

/**
 * Generate category settings form.
 *
 * @return string HTML form.
 */
function mc_category_settings() {
	if ( current_user_can( 'mc_edit_settings' ) ) {
		$color_settings = mc_settings_field(
			array(
				'name'    => 'mc_apply_color',
				'label'   => array(
					'default'    => __( 'Hide category colors', 'my-calendar' ),
					'font'       => __( 'Title text color.', 'my-calendar' ),
					'background' => __( 'Title background color.', 'my-calendar' ),
				),
				'default' => 'default',
				'type'    => 'radio',
				'echo'    => false,
			)
		);
		$icons_settings = mc_settings_field(
			array(
				'name'  => 'mc_hide_icons',
				'label' => __( 'Hide Category icons', 'my-calendar' ),
				'type'  => 'checkbox-single',
				'echo'  => false,
			)
		);
		$settings       = '
		<form method="post" action="' . admin_url( 'admin.php?page=my-calendar-categories' ) . '">
			<div>
				<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'my-calendar-nonce' ) . '" />
			</div>
			<div class="mc-category-settings">
				<fieldset>
				<legend class="screen-reader-text">' . __( 'Color Coding', 'my-calendar' ) . '</legend>
					<ul>' . $color_settings . '</ul>
				</fieldset>
				<ul>
					<li>' . $icons_settings . '</li>
				</ul>
			</div>
			<p>
				<input type="submit" name="mc_category_settings" class="button-primary" value="' . __( 'Save Settings', 'my-calendar' ) . '" />
			</p>
		</form>';

		return $settings;
	}

	return '';
}

/**
 * Get single field about a category.
 *
 * @param int    $cat_id Category ID.
 * @param string $field Field name to get.
 *
 * @return mixed string/int Query result.
 */
function mc_get_category_detail( $cat_id, $field = 'category_name' ) {
	$cat_id   = absint( $cat_id );
	$category = mc_get_category( $cat_id );

	if ( $category ) {
		if ( ! $field ) {
			return $category;
		}

		return (string) $category->$field;
	}
}

/**
 * Fetch the category ID for categories passed by name
 *
 * @param string $category_name Name of category.
 *
 * @return int $cat_id or false.
 */
function mc_category_by_name( $category_name ) {
	$mcdb   = mc_is_remote_db();
	$cat_id = false;
	$sql    = 'SELECT * FROM ' . my_calendar_categories_table() . ' WHERE category_name = %s';
	$cat    = $mcdb->get_row( $mcdb->prepare( $sql, $category_name ) );

	if ( is_object( $cat ) ) {
		$cat_id = $cat->category_id;
	}

	return $cat_id;
}

/**
 * Get all categories or get the ID of the first category; create a category if no default set.
 *
 * @param bool $single False for all categories; true for individual category.
 *
 * @return int|array
 */
function mc_no_category_default( $single = false ) {
	$mcdb = mc_is_remote_db();
	$cats = $mcdb->get_results( 'SELECT * FROM ' . my_calendar_categories_table() . ' ORDER BY category_name ASC' );
	if ( empty( $cats ) ) {
		// need to have categories. Try to create again.
		$cat_id = mc_create_category(
			array(
				'category_name'  => 'General',
				'category_color' => '#243f82',
				'category_icon'  => 'event.svg',
			)
		);

		$cats = $mcdb->get_results( 'SELECT * FROM ' . my_calendar_categories_table() . ' ORDER BY category_name ASC' );
	} else {
		$cat_id = $cats[0]->category_id;
	}
	if ( $single ) {
		return $cat_id;
	}

	return $cats;
}

/**
 * Fetch category object by ID or name.
 *
 * @param int|string $category Category name/id.
 *
 * @return object
 */
function mc_get_category( $category ) {
	if ( is_int( $category ) ) {
		$cat = get_transient( 'mc_cat_' . $category );
		if ( $cat ) {
			return $cat;
		}
	}
	$mcdb = mc_is_remote_db();
	if ( is_int( $category ) ) {
		$sql = 'SELECT * FROM ' . my_calendar_categories_table() . ' WHERE category_id = %d';
		$cat = $mcdb->get_row( $mcdb->prepare( $sql, $category ) );
		set_transient( 'mc_cat_' . $category, $cat, WEEK_IN_SECONDS );
	} else {
		$cat = mc_category_by_name( $category );
	}
	return $cat;
}

/**
 * Generate list of categories to edit.
 */
function mc_manage_categories() {
	if ( current_user_can( 'mc_edit_settings' ) ) {
		$response = mc_category_settings_update();
		echo wp_kses_post( $response );
	}
	global $wpdb;
	$co = ( ! isset( $_GET['co'] ) ) ? 1 : (int) $_GET['co'];
	switch ( $co ) {
		case 1:
			$cat_order = 'category_id';
			break;
		case 2:
			$cat_order = 'category_name';
			break;
		default:
			$cat_order = 'category_id';
	}
	$default_category = (string) mc_get_option( 'default_category' );
	$hide_icon        = ( 'true' === mc_get_option( 'hide_icons' ) ) ? true : false;
	// We pull the categories from the database.
	$categories = $wpdb->get_results( 'SELECT * FROM ' . my_calendar_categories_table() . ' ORDER BY ' . esc_sql( $cat_order ) . ' ASC' );  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( empty( $categories ) ) {
		// If no categories are found, create the default category and re-fetch.
		mc_create_category(
			array(
				'category_name'  => 'General',
				'category_color' => '#243f82',
				'category_icon'  => 'event.svg',
			)
		);
		$categories = $wpdb->get_results( 'SELECT * FROM ' . my_calendar_categories_table() . ' ORDER BY ' . esc_sql( $cat_order ) . ' ASC' );  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	if ( ! empty( $categories ) ) {
		?>
		<table class="widefat striped page fixed mc-categories" id="my-calendar-admin-table">
		<thead>
		<tr>
			<th scope="col">
				<?php
				echo ( '2' === (string) $co ) ? wp_kses_post( '<a href="' . esc_url( admin_url( 'admin.php?page=my-calendar-categories&amp;co=1' ) ) . '">' . __( 'ID', 'my-calendar' ) . '</a>' ) : __( 'ID', 'my-calendar' );
				?>
			</th>
			<th scope="col">
				<?php
				echo ( '1' === (string) $co ) ? wp_kses_post( '<a href="' . esc_url( admin_url( 'admin.php?page=my-calendar-categories&amp;co=2' ) ) . '">' . __( 'Category Name', 'my-calendar' ) . '</a>' ) : __( 'Category Name', 'my-calendar' );
				?>
			</th>
			<th scope="col"><?php esc_html_e( 'Events', 'my-calendar' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Private', 'my-calendar' ); ?></th>
			<?php
			if ( ! $hide_icon ) {
				?>
			<th scope="col"><?php esc_html_e( 'Icon', 'my-calendar' ); ?></th>
				<?php
			}
			?>
			<th scope="col"><?php esc_html_e( 'Color', 'my-calendar' ); ?></th>
		</tr>
		</thead>
		<?php
		foreach ( $categories as $cat ) {
			$icon       = ( ! $hide_icon ) ? mc_category_icon( $cat ) : '';
			$background = ( 0 !== strpos( $cat->category_color, '#' ) ) ? '#' : '' . $cat->category_color;
			$foreground = mc_inverse_color( $background );
			$cat_name   = stripslashes( strip_tags( $cat->category_name, mc_strip_tags() ) );
			?>
		<tr>
			<th scope="row"><?php echo absint( $cat->category_id ); ?></th>
			<td>
			<?php
			$category_event_url = add_query_arg( 'filter', $cat->category_id, admin_url( 'admin.php?page=my-calendar-manage&restrict=category&view=list&limit=all' ) );
			$count              = mc_get_category_count( $cat->category_id );
			$count              = ( $count ) ? '<a href="' . esc_url( $category_event_url ) . '">' . $count . '</a>' : '0';
			echo esc_html( $cat_name );
			// Translators: Name of category being edited.
			$edit_cat = sprintf( __( 'Edit %s', 'my-calendar' ), '<span class="screen-reader-text">' . $cat_name . '</span>' );
			// Translators: Category name.
			$delete_cat = sprintf( __( 'Delete %s', 'my-calendar' ), '<span class="screen-reader-text">' . $cat_name . '</span>' );
			// Translators: Category name.
			$default_text = sprintf( __( 'Set %s as Default', 'my-calendar' ), '<span class="screen-reader-text">' . $cat_name . '</span>' );
			$mcnonce      = wp_create_nonce( 'mcnonce' );
			if ( $default_category === (string) $cat->category_id ) {
				echo ' <strong>' . __( '(Default)', 'my-calendar' ) . '</strong>';
				$default = '<span class="mc_default">' . __( 'Default Category', 'my-calendar' ) . '</span>';
			} else {
				$url     = add_query_arg( '_mcnonce', $mcnonce, admin_url( "admin.php?page=my-calendar-categories&amp;default=$cat->category_id" ) );
				$default = '<a href="' . esc_url( $url ) . '">' . $default_text . '</a>';
			}
			if ( mc_get_option( 'skip_holidays_category' ) === (string) $cat->category_id ) {
				echo ' <strong>' . __( '(Holiday)', 'my-calendar' ) . '</strong>';
			}
			?>
				<div class="row-actions">
					<a href="<?php echo esc_url( admin_url( "admin.php?page=my-calendar-categories&amp;mode=edit&amp;category_id=$cat->category_id" ) ); ?>"
					class='edit'><?php echo wp_kses_post( $edit_cat ); ?></a> | 
					<?php
					echo wp_kses_post( $default );
					// Cannot delete the default category.
					if ( '1' !== (string) $cat->category_id ) {
						echo ' | ';
						?>
						<a href="<?php echo add_query_arg( '_mcnonce', $mcnonce, admin_url( "admin.php?page=my-calendar-categories&amp;mode=delete&amp;category_id=$cat->category_id" ) ); ?>" class="delete" onclick="return confirm('<?php _e( 'Are you sure you want to delete this category?', 'my-calendar' ); ?>')"><?php echo wp_kses_post( $delete_cat ); ?></a>
						<?php
					}
					?>
				</div>
			</td>
			<td><?php echo wp_kses_post( $count ); ?></td>
			<td><?php echo ( '1' === (string) $cat->category_private ) ? __( 'Yes', 'my-calendar' ) : __( 'No', 'my-calendar' ); ?></td>
			<?php
			if ( ! $hide_icon ) {
				if ( 'background' === mc_get_option( 'apply_color' ) ) {
					$icon_bg = $background;
				} else {
					$icon_bg = ( 'default' === mc_get_option( 'apply_color' ) ) ? '' : $foreground;
				}
				if ( ! $icon ) {
					$icon_bg = 'transparent';
				}
				$style = ( '' !== $icon_bg ) ? ' style="background-color:' . esc_attr( $icon_bg ) . '"' : '';
				?>
			<td<?php echo $style; ?>><?php echo ( $icon ) ? wp_kses( $icon, mc_kses_elements() ) : ''; ?></td>
				<?php
			}
			?>
			<td style="background-color:<?php echo esc_attr( $background ); ?>;color: <?php echo esc_attr( $foreground ); ?>;"><?php echo ( '#' !== $background ) ? esc_attr( $background ) : ''; ?></td>
		</tr>
			<?php
		}
		?>
	</table>
		<?php
	} else {
		echo wp_kses_post( '<p>' . __( 'There are no categories in the database - or something has gone wrong!', 'my-calendar' ) . '</p>' );
	}
}

add_action( 'show_user_profile', 'mc_profile' );
add_action( 'edit_user_profile', 'mc_profile' );
add_action( 'profile_update', 'mc_save_profile' );
/**
 * Show user profile data on Edit User pages.
 */
function mc_profile() {
	global $user_ID;
	$user_edit = ( isset( $_GET['user_id'] ) ) ? (int) $_GET['user_id'] : $user_ID;

	if ( user_can( $user_edit, 'mc_add_events' ) && current_user_can( 'manage_options' ) ) {
		$permissions = get_user_meta( $user_edit, 'mc_user_permissions', true );
		$selected    = ( empty( $permissions ) || in_array( 'all', $permissions, true ) || user_can( $user_edit, 'manage_options' ) ) ? ' checked="checked"' : '';
		?>
		<div class="mc-user-permissions">
		<h3><?php esc_html_e( 'My Calendar Editor Permissions', 'my-calendar' ); ?></h3>
		<fieldset><legend style="font-size:1rem;font-weight:600"><?php esc_html_e( 'Allowed Categories', 'my-calendar' ); ?></legend>
			<ul class='checkboxes'>
				<li><input type="checkbox" name="mc_user_permissions[]" value="all" id="mc_edit_all" <?php echo esc_html( $selected ); ?>> <label for="mc_edit_all"><?php esc_html_e( 'Edit All Categories', 'my-calendar' ); ?></li>
				<?php echo mc_category_select( $permissions, true, true, 'mc_user_permissions[]' ); ?>
			</ul>
		</fieldset>
			<?php
			/**
			 * Add custom fields to the My Calendar section of the user profile.
			 *
			 * @hook mc_user_fields
			 *
			 * @param {string} $output HTML for fields.
			 * @param {int}    $user_edit User ID being edited.
			 *
			 * @return {string}
			 */
			echo apply_filters( 'mc_user_fields', '', $user_edit );
			?>
		</div>
		<?php
	}
}

/**
 * Save user profile data
 */
function mc_save_profile() {
	global $user_ID;
	if ( isset( $_POST['user_id'] ) ) {
		$edit_id = (int) $_POST['user_id'];
	} else {
		$edit_id = $user_ID;
	}
	if ( current_user_can( 'manage_options' ) ) {
		if ( isset( $_POST['mc_user_permissions'] ) ) {
			$mc_user_permission = map_deep( $_POST['mc_user_permissions'], 'sanitize_text_field' );
			update_user_meta( $edit_id, 'mc_user_permissions', $mc_user_permission );
		} else {
			delete_user_meta( $edit_id, 'mc_user_permissions' );
		}
	}
	/**
	 * Execute action when saving My Calendar data in a user profile.
	 *
	 * @hook mc_save_user
	 *
	 * @param {object} $int Edited user ID.
	 * @param {array}  $_POST POST data.
	 */
	do_action( 'mc_save_user', $edit_id, $_POST );
}


/**
 * Generate fields to select event categories.
 *
 * @param object|false|int|null|array $data object with event_category value, empty value, or a category ID.
 * @param boolean                     $option Type of form.
 * @param boolean                     $multiple Allow multiple categories to be entered.
 * @param boolean|string              $name Field name for input.
 * @param boolean|string              $id ID for label/input.
 *
 * @return string HTML fields.
 */
function mc_category_select( $data = false, $option = true, $multiple = false, $name = false, $id = false ) {
	if ( ! $name ) {
		$name = 'event_category[]';
	}
	if ( ! $id ) {
		$id = 'mc_cat_';
	}
	// Grab all the categories and list them.
	$list    = '';
	$default = '';
	$cats    = mc_no_category_default();
	if ( ! empty( $cats ) ) {
		/**
		 * Filter the categories available in a category selection interface.
		 *
		 * @hook mc_category_list
		 *
		 * @param {array}  $cats Array of categories.
		 * @param {object} $data An object with selected category data.
		 *
		 * @return {array}
		 */
		$cats = apply_filters( 'mc_category_list', $cats, $data );
		foreach ( $cats as $cat ) {
			$selected = '';
			// if category is private, don't show if user is not logged in.
			if ( '1' === (string) $cat->category_private && ! is_user_logged_in() ) {
				continue;
			}
			// If the current user can't edit a category, don't show it.
			if ( ! mc_can_edit_category( $cat->category_id, wp_get_current_user()->ID ) ) {
				continue;
			}

			if ( ! empty( $data ) ) {
				if ( ! is_object( $data ) ) {
					$category = $data;
				} elseif ( is_array( $data ) && $multiple && 'mc_user_permissions[]' === $name ) {
					$category = $data;
				} elseif ( is_array( $data ) && $multiple && $id ) {
					// This is coming from a widget.
					$category = $data;
				} else {
					if ( $multiple ) {
						$category = ( property_exists( $data, 'user_error' ) ) ? $data->event_categories : mc_get_categories( $data );
					} else {
						$category = $data->event_category;
					}
				}
				if ( $multiple ) {
					if ( is_array( $category ) && in_array( $cat->category_id, $category, true ) ) {
						$selected = ' checked="checked"';
					} elseif ( is_numeric( $category ) && ( (int) $category === (int) $cat->category_id ) ) {
						$selected = ' checked="checked"';
					} elseif ( ! $category ) {
						$selected = ( mc_get_option( 'default_category' ) === (string) $cat->category_id ) ? ' checked="checked"' : '';
					}
				} else {
					if ( (int) $category === (int) $cat->category_id ) {
						$selected = ' selected="selected"';
					}
				}
			} else {
				if ( mc_get_option( 'default_category' ) === (string) $cat->category_id ) {
					// Pass null value to prevent default from being selected.
					$selected = ( null === $data ) ? '' : ' checked="checked"';
				}
			}
			$category_name = strip_tags( stripslashes( trim( $cat->category_name ) ) );
			$category_name = ( '' === $category_name ) ? '(' . __( 'Untitled category', 'my-calendar' ) . ')' : $category_name;
			if ( $multiple ) {
				$icon = '<span style="display:inline-block;max-width:1em;margin-left:6px;vertical-align:middle;">' . mc_category_icon( $cat ) . '</span>';
				$c    = '<li class="mc_cat_' . $cat->category_id . '"><input type="checkbox"' . $selected . ' name="' . esc_attr( $name ) . '" id="' . $id . $cat->category_id . '" value="' . $cat->category_id . '" ' . $selected . ' /> <label for="' . $id . $cat->category_id . '">' . $category_name . $icon . '</label></li>';
			} else {
				$c = '<option value="' . $cat->category_id . '" ' . $selected . '>' . $category_name . '</option>';
			}
			if ( mc_get_option( 'default_category' ) !== (string) $cat->category_id ) {
				$list .= $c;
			} else {
				$default = $c;
			}
		}
	} else {
		$category_url = admin_url( 'admin.php?page=my-calendar-categories' );
		// Translators: URL to add a new category.
		mc_show_error( sprintf( __( 'You do not have any categories created. Please <a href="%s">create at least one category!</a>', 'my-calendar' ), $category_url ) );
	}
	if ( ! $option ) {
		$default = ( mc_get_option( 'default_category' ) ) ? mc_get_option( 'default_category' ) : 1;

		return ( is_object( $data ) ) ? $data->event_category : $default;
	}

	return $default . $list;
}

/**
 * Verify that an event has a valid category relationship.
 *
 * @param object $event Event object.
 */
function mc_check_category_relationships( $event ) {
	global $wpdb;
	$relationship = $wpdb->get_var( $wpdb->prepare( 'SELECT relationship_id FROM ' . my_calendar_category_relationships_table() . ' WHERE event_id = %d AND category_id = %d', $event->event_id, $event->event_category ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( ! $relationship ) {
		$wpdb->insert(
			my_calendar_category_relationships_table(),
			array(
				'event_id'    => $event->event_id,
				'category_id' => $event->event_category,
			),
			array( '%d', '%d' )
		);
	}
}

/**
 * Show category output for editing lists.
 *
 * @param object $event Event object.
 *
 * @return string
 */
function mc_admin_category_list( $event ) {
	if ( ! $event->event_category ) {
		// Events *must* have a category.
		mc_update_event( 'event_category', 1, $event->event_id, '%d' );
	}
	// Verify valid category relationships. Corrects problems caused by deleting a category pre 3.3.3.
	mc_check_category_relationships( $event );
	$cat = mc_get_category_detail( $event->event_category, false );
	if ( ! is_object( $cat ) ) {
		$cat = (object) array(
			'category_color' => '',
			'category_id'    => '',
			'category_name'  => '',
		);
	}
	$color = ( 'default' !== mc_get_option( 'apply_color' ) ) ? $cat->category_color : '';
	$icon  = ( 'true' === mc_get_option( 'hide_icons' ) ) ? '' : mc_category_icon( $event );
	if ( ! $color ) {
		$color = '<span class="category-color no-border">' . $icon . '</span>';
	} else {
		$color   = ( 0 !== strpos( $color, '#' ) ) ? '#' . $color : $color;
		$inverse = mc_inverse_color( $cat->category_color );
		$color   = ( 'background' === mc_get_option( 'apply_color' ) ) ? '<span class="category-color" style="background-color:' . $color . ';">' . $icon . '</span>' : '<span class="category-color" style="background-color:' . $inverse . ';">' . $icon . '</span>';
	}
	$categories = mc_get_categories( $event );
	$cats       = array();
	$string     = $color;
	if ( isset( $_GET['groups'] ) ) {
		$string .= ' ' . esc_html( $cat->category_name );
	} else {
		$args = array(
			'filter'   => $event->event_category,
			'restrict' => 'category',
		);
		if ( is_admin() ) {
			$url = add_query_arg( $args, mc_admin_url( 'admin.php?page=my-calendar-manage' ) );
		} else {
			$url = add_query_arg( $args, get_permalink() );
		}
		$string .= " <a class='mc_filter primary-category' href='" . esc_url( $url ) . "'><span class='screen-reader-text'>" . __( 'Show only: ', 'my-calendar' ) . '</span>' . esc_html( $cat->category_name ) . '</a>';
	}

	if ( is_array( $categories ) ) {
		foreach ( $categories as $category ) {
			$category = (int) $category;
			if ( $category !== (int) $event->event_category ) {
				$filter = mc_admin_url( "admin.php?page=my-calendar-manage&amp;filter=$category&amp;restrict=category" );
				$color  = mc_get_category_detail( $category, 'category_color' );
				$color  = ( 0 !== strpos( $color, '#' ) ) ? '#' . $color : $color;
				$color  = ( '#' !== $color ) ? '<span class="category-color" style="background-color:' . $color . ';"></span>' : '';
				if ( isset( $_GET['groups'] ) ) {
					$cats[] = $color . ' ' . mc_get_category_detail( $category, 'category_name' );
				} else {
					$cats[] = $color . ' <a href="' . $filter . '" class="secondary-category">' . mc_get_category_detail( $category, 'category_name' ) . '</a>';
				}
			}
		}
		if ( count( $cats ) > 0 ) {
			$string .= ', ' . implode( ', ', $cats );
		}
	}

	return $string;
}

/**
 * Get all categories for given event
 *
 * @param object|int     $event Event object or event ID.
 * @param boolean|string $ids Return objects, ids, text, or html output.
 *
 * @return array of values
 */
function mc_get_categories( $event, $ids = true ) {
	$mcdb     = mc_is_remote_db();
	$event_id = ( is_object( $event ) ) ? absint( $event->event_id ) : absint( $event );
	$return   = array();
	$results  = false;
	if ( is_object( $event ) ) {
		$primary = $event->event_category;
		if ( property_exists( $event, 'categories' ) ) {
			$results = $event->categories;
		}
	} elseif ( is_numeric( $event ) ) {
		$primary = mc_get_data( 'event_category', $event_id );
	} else {

		return ( 'html' === $ids || 'text' === $ids ) ? '' : array();
	}

	if ( ! $results ) {
		$relate = my_calendar_category_relationships_table();
		$catego = my_calendar_categories_table();
		$cache  = get_transient( 'mc_categories_' . $event_id );
		if ( $cache ) {
			$results = $cache;
		} else {
			$results = $mcdb->get_results( $mcdb->prepare( 'SELECT * FROM ' . $relate . ' as r JOIN ' . $catego . ' as c ON c.category_id = r.category_id WHERE event_id = %d', $event_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			set_transient( 'mc_categories_' . $event_id, $results, WEEK_IN_SECONDS );
		}
	}
	if ( true === $ids ) {
		if ( $results ) {
			foreach ( $results as $result ) {
				$return[] = $result->category_id;
			}
		} else {
			$return[] = $primary;
		}
	} elseif ( 'html' === $ids || 'text' === $ids ) {
		$return = mc_categories_html( $results, $primary, $ids );
	} elseif ( 'testing' === $ids ) {
		if ( $results ) {
			foreach ( $results as $result ) {
				$return[] = $result->category_id;
			}
		}
	} else {
		$return = ( is_array( $results ) ) ? $results : array( $event->event_category );
	}

	return $return;
}

/**
 * Return HTML representing categories.
 *
 * @param array  $results array of categories.
 * @param int    $primary Primary selected category for event.
 * @param string $output 'html' or 'text'. Include HTML or just raw text.
 *
 * @return String
 */
function mc_categories_html( $results, $primary, $output = 'html' ) {
	$primary_category = mc_get_category( (int) $primary );
	$return[]         = ( 'html' === $output ) ? mc_category_icon( $primary ) . $primary_category->category_name : $primary_category->category_name;
	if ( $results ) {
		foreach ( $results as $result ) {
			$results[ sanitize_key( $result->category_name ) ] = $result;
		}
		ksort( $results );
		foreach ( $results as $result ) {
			if ( ! is_object( $result ) ) {
				$result = (object) $result;
			}
			if ( $result->category_id === $primary_category->category_id ) {
				continue;
			}
			$icon     = ( 'html' === $output ) ? mc_category_icon( $result ) : '';
			$return[] = $icon . $result->category_name;
		}
	}

	$return = implode( ', ', array_unique( $return ) );

	return $return;
}

/**
 * Get category icon by filename.
 *
 * @param string $file File name.
 * @param bool   $is_custom Querying a custom icon.
 *
 * @return string
 */
function mc_get_img( $file, $is_custom = false ) {
	$parent_path = plugin_dir_path( __DIR__ );
	$parent_url  = plugin_dir_url( __DIR__ );
	$url         = plugin_dir_url( __FILE__ );
	$self        = plugin_dir_path( __FILE__ );
	global $wp_filesystem;
	require_once ABSPATH . '/wp-admin/includes/file.php';
	WP_Filesystem();

	if ( $is_custom ) {
		$path = $parent_path . 'my-calendar-custom/icons/';
		$link = $parent_url . 'my-calendar-custom/icons/';
	} else {
		$path = $self . 'images/icons/';
		$link = $url . 'images/icons/';
	}
	$file = ( $is_custom ) ? $file : str_replace( '.png', '.svg', $file );
	if ( false === stripos( $file, '.svg' ) ) {
		if ( $wp_filesystem->exists( $path . $file ) ) {
			return '<img src="' . esc_url( $link . $file ) . '" alt="" />';
		} else {
			return '';
		}
	}
	$src      = $path . $file;
	$label_id = sanitize_title( $file );
	$svg      = ( $wp_filesystem->exists( $src ) ) ? $wp_filesystem->get_contents( $src ) : false;
	$image    = '<img src="data:image/png;base64,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" alt="Error fetching image" />';
	if ( $svg ) {
		$image = $svg;
		// If remote access is blocked, this will not return an SVG.
		if ( 0 === stripos( $image, '<svg' ) ) {
			$image = str_replace( '<svg ', '<svg focusable="false" role="img" aria-labelledby="' . $label_id . '" class="category-icon" ', $image );
			$image = str_replace( '<path ', "<title id='" . $label_id . "'>$file</title><path ", $image );
		}
	}

	return $image;
}

/**
 * Delete category icon from storage. Enables replacement of stored icon if category is modified.
 *
 * @param int $category_id Category ID.
 */
function mc_delete_category_icon( $category_id ) {
	delete_option( 'mc_category_icon_category_' . $category_id );
	delete_option( 'mc_category_icon_event_' . $category_id );
}

/**
 * Produce filepath & name or full img HTML for specific category's icon
 *
 * @param object $event Current event object.
 * @param string $type 'html' to generate HTML.
 *
 * @return string image path or HTML
 */
function mc_category_icon( $event, $type = 'html' ) {
	/**
	 * Override the return value for a category icon.
	 *
	 * @hook mc_override_category_icon
	 *
	 * @param {bool}   $override Return a string value to short circuit the category icon query.
	 * @param {object} $event Event object.
	 * @param {string} $type Type of output - HTML or URL only.
	 *
	 * @return {string|bool}
	 */
	$override = apply_filters( 'mc_override_category_icon', false, $event, $type );
	if ( $override ) {
		return $override;
	}
	if ( is_object( $event ) && property_exists( $event, 'category_icon' ) ) {
		$url   = plugin_dir_url( __FILE__ );
		$image = '';
		// Is this an event context or a category context.
		if ( property_exists( $event, 'occur_id' ) ) {
			$context    = 'event';
			$substitute = '-' . $event->occur_id;
		} else {
			$context    = 'category';
			$substitute = '';
		}
		if ( 'true' !== mc_get_option( 'hide_icons' ) ) {
			if ( '' !== $event->category_icon ) {
				if ( mc_is_custom_icon() ) {
					$path = str_replace( 'my-calendar', 'my-calendar-custom/icons', $url );
					$src  = $path . $event->category_icon;
				} else {
					$path = plugins_url( 'images/icons', __FILE__ ) . '/';
					$src  = $path . str_replace( '.png', '.svg', $event->category_icon );
				}
				$hex      = ( strpos( $event->category_color, '#' ) !== 0 ) ? '#' : '';
				$color    = $hex . $event->category_color;
				$cat_name = __( 'Category', 'my-calendar' ) . ': ' . esc_attr( $event->category_name );
				if ( 'html' === $type ) {
					if ( false !== stripos( $src, '.svg' ) ) {
						$image = get_option( 'mc_category_icon_' . $context . '_' . $event->category_id, '' );
						// If there's a value, but it's not an svg, zero out.
						if ( $image && 0 !== stripos( $image, '<svg' ) ) {
							$image = '';
						}
						if ( '' === $image ) {
							$image = mc_generate_category_icon( $event );
						}
					} else {
						$image = '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $cat_name ) . '" class="category-icon" style="background:' . esc_attr( $color ) . '" />';
					}
				} else {
					$image = $path . $event->category_icon;
				}
			}
		}
		$inverse = mc_inverse_color( $event->category_color );
		if ( 'default' !== mc_get_option( 'apply_color' ) ) {
			$back  = ( 'background' === mc_get_option( 'apply_color' ) ) ? true : false;
			$image = ( $back ) ? str_replace( $event->category_color, $inverse, $image ) : str_replace( $inverse, $event->category_color, $image );
		} else {
			$image = str_replace( array( $event->category_color, $inverse ), 'inherit', $image );
		}
		$image = str_replace( 'cat_' . $event->category_id, 'cat_' . $event->category_id . $substitute, $image );
		/**
		 * Filter the HTML output for a category icon.
		 *
		 * @hook mc_category_icon
		 *
		 * @param {string} $image Image HTML.
		 * @param {object} $event Event object.
		 * @param {string} $type Type of output - HTML or URL only.
		 *
		 * @return {string}
		 */
		return apply_filters( 'mc_category_icon', $image, $event, $type );
	}

	return '';
}

/**
 * Generate SVG output from category icon information. Passed object must include category_color, category_name, category_icon, category_id|occur_id
 *
 * @param object $source Either an event or a category object.
 *
 * @return string
 */
function mc_generate_category_icon( $source ) {
	if ( '' === $source->category_icon ) {
		return '';
	}
	$path  = plugin_dir_path( __FILE__ ) . 'images/icons/';
	$src   = $path . str_replace( '.png', '.svg', $source->category_icon );
	$hex   = ( strpos( $source->category_color, '#' ) !== 0 ) ? '#' : '';
	$color = $hex . $source->category_color;
	$apply = mc_get_option( 'apply_color' );
	if ( 'background' === $apply ) {
		$color = mc_inverse_color( $color );
	}
	if ( 'default' === $apply ) {
		$color = '';
	}
	$cat_name = $source->category_name;
	// Is this an event context or a category context.
	if ( property_exists( $source, 'occur_id' ) ) {
		$cat_name = __( 'Category', 'my-calendar' ) . ': ' . $cat_name;
		$occur_id = $source->occur_id;
		$context  = 'event';
	} else {
		$occur_id = $source->category_id;
		$context  = 'category';
	}
	$label_id = 'cat_' . $occur_id;
	global $wp_filesystem;
	require_once ABSPATH . '/wp-admin/includes/file.php';
	WP_Filesystem();
	$image = ( $wp_filesystem->exists( $src ) ) ? $wp_filesystem->get_contents( $src ) : false;
	if ( 0 === stripos( $image, '<svg' ) ) {
		$image = str_replace( '<svg ', '<svg style="fill:' . $color . '" focusable="false" role="img" aria-labelledby="' . $label_id . '" class="category-icon" ', $image );
		$image = str_replace( '<path ', "<title id='" . $label_id . "'>" . esc_html( $cat_name ) . '</title><path ', $image );

		update_option( 'mc_category_icon_' . $context . '_' . $source->category_id, $image );
	} else {
		$image = '';
	}

	return $image;
}

/**
 * Add a filter to safe CSS files to allow 'fill' on svg icons.
 */
add_filter(
	'safe_style_css',
	function ( $styles ) {
		$styles[] = 'fill';
		return $styles;
	}
);

/**
 * Generate category class for a given date. Must generate a single class; multiple classes would require refactoring to function usage.
 *
 * @param object|array $event_or_category Usually an event, can be category.
 * @param string       $prefix            Prefix to append to category; varies on context.
 *
 * @return string a single class
 */
function mc_category_class( $event_or_category, $prefix ) {
	if ( is_array( $event_or_category ) ) {
		$name = $event_or_category['category'];
		$id   = $event_or_category['category_id'];
	} else {
		$name = $event_or_category->category_name;
		$id   = $event_or_category->category_id;
	}

	$class = sanitize_html_class( trim( str_replace( ' ', '-', $name ) ) );
	$class = ( '' === $class ) ? $id : $class;

	return $prefix . strtolower( $class );
}
