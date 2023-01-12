<?php
/**
 * Manage My Calendar styles.
 *
 * @category Core
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migrate CSS file to custom.
 */
function mc_migrate_css() {
	if ( isset( $_GET['migrate'] ) ) {
		if ( 'false' === $_GET['migrate'] ) {
			mc_update_option( 'migrated', 'true' );
			mc_show_notice( __( 'Thank you! Your site will be updated to the latest version of core CSS at every update.', 'my-calendar' ) );
		} else {
			$verify = wp_verify_nonce( $_GET['migrate'], 'mc-migrate-css' );
			if ( ! $verify ) {
				wp_die( 'My Calendar: Permissions not granted to migrate CSS.', 'my-calendar' );
			} else {
				global $wp_filesystem;
				WP_Filesystem();
				$style       = mc_get_option( 'css_file' );
				$stylefile   = mc_get_style_path();
				$newfileroot = str_replace( '/my-calendar/', '/my-calendar-custom/', plugin_dir_path( __FILE__ ) );
				$newfiledir  = trailingslashit( $newfileroot ) . 'styles/';
				$newfilepath = trailingslashit( $newfiledir ) . $style;
				if ( ! $wp_filesystem->exists( $newfileroot ) ) {
					$wp_filesystem->mkdir( $newfileroot );
				}
				if ( ! $wp_filesystem->exists( $newfiledir ) ) {
					$wp_filesystem->mkdir( $newfiledir );
				}
				$wrote_migration = $wp_filesystem->copy( $stylefile, $newfilepath, true );
				if ( $wrote_migration ) {
					$new = 'mc_custom_' . $style;
					mc_update_option( 'css_file', $new );
					mc_update_option( 'migrated', 'true' );
					mc_show_notice( __( 'CSS migrated to custom directory.', 'my-calendar' ) );
				} else {
					mc_show_error( __( 'CSS migration failed. You may need to migrate your file via FTP.', 'my-calendar' ) );
				}
			}
		}
	}
}


/**
 * Re-migrate CSS file to custom location from invalid location.
 */
function mc_remigrate_css() {
	if ( isset( $_GET['remigrate'] ) ) {
		if ( 'false' === $_GET['remigrate'] ) {
			mc_update_option( 'remigrated', 'true' );
			mc_show_notice( __( 'All right! Leaving your CSS file where it is.', 'my-calendar' ) );
		} else {
			$verify = wp_verify_nonce( $_GET['remigrate'], 'mc-remigrate-css' );
			if ( ! $verify ) {
				wp_die( 'My Calendar: Permissions not granted to migrate CSS.', 'my-calendar' );
			} else {
				global $wp_filesystem;
				WP_Filesystem();
				$path  = str_replace( '/my-calendar', '', plugin_dir_path( __DIR__ ) ) . 'styles/';
				$files = mc_css_list( $path );
				if ( ! empty( $files ) ) {
					$style = $files[0];
				}
				$stylefile   = trailingslashit( $path ) . $style;
				$newfileroot = str_replace( '/my-calendar/', '/my-calendar-custom/', plugin_dir_path( __FILE__ ) );
				$newfiledir  = trailingslashit( $newfileroot ) . 'styles/';
				$newfilepath = trailingslashit( $newfiledir ) . $style;
				if ( ! $wp_filesystem->exists( $newfileroot ) ) {
					$wp_filesystem->mkdir( $newfileroot );
				}
				if ( ! $wp_filesystem->exists( $newfiledir ) ) {
					$wp_filesystem->mkdir( $newfiledir );
				}
				$wrote_migration = $wp_filesystem->copy( $stylefile, $newfilepath, true );
				if ( $wrote_migration ) {
					$new = 'mc_custom_' . $style;
					mc_update_option( 'css_file', $new );
					mc_update_option( 'remigrated', 'true' );
					mc_show_notice( __( 'CSS migrated to the proper custom directory.', 'my-calendar' ) );
				} else {
					mc_show_error( __( 'CSS migration failed. You may need to migrate your file via FTP.', 'my-calendar' ) );
				}
			}
		}
	}
}

/**
 * Show CSS migration notice.
 */
function mc_migrate_notice() {
	if ( ! ( 'true' === mc_get_option( 'migrated' ) ) && current_user_can( 'mc_edit_styles' ) ) {
		if ( ! mc_is_custom_style( mc_get_option( 'css_file' ) ) ) {
			$nonce       = wp_create_nonce( 'mc-migrate-css' );
			$migrate_url = add_query_arg( 'migrate', $nonce, admin_url( 'admin.php?page=my-calendar-design' ) );
			$dismiss_url = add_query_arg( 'migrate', 'false', admin_url( 'admin.php?page=my-calendar-design' ) );
			// Translators: 1) URL for link to migrate styles. 2) URL to dismiss message and use existing styles. 3) Help link.
			mc_show_notice( sprintf( __( 'The CSS Style editor will be removed in My Calendar 3.5. Migrate custom CSS into the My Calendar custom directory at <code>/wp-content/plugins/my-calendar-custom/</code>. <a href="%1$s" class="button-secondary">Migrate CSS now</a> <a href="%2$s" class="button-primary">Keep My Calendar\'s styles</a> %3$s', 'my-calendar' ), $migrate_url, $dismiss_url, mc_help_link( __( 'Learn more', 'my-calendar' ), __( 'Migrating to Custom CSS', 'my-calendar' ), 'Custom CSS', 7, false ) ) );
		} else {
			mc_show_notice( __( 'The CSS Style editor will be removed in My Calendar 3.5. You are already using custom CSS, and no changes are required.', 'my-calendar' ) );
		}
	}
}

/**
 * Show CSS migration apology.
 *
 * @return void
 */
function mc_remigrate_notice() {
	if ( ! ( 'true' === mc_get_option( 'remigrated' ) ) ) {
		$path  = str_replace( '/my-calendar', '', plugin_dir_path( __FILE__ ) ) . 'styles/';
		$files = mc_css_list( $path );
		if ( ! empty( $files ) ) {
			$migrations  = implode( ',', $files );
			$nonce       = wp_create_nonce( 'mc-remigrate-css' );
			$migrate_url = add_query_arg( 'remigrate', $nonce, admin_url( 'admin.php?page=my-calendar-design' ) );
			$dismiss_url = add_query_arg( 'remigrate', 'false', admin_url( 'admin.php?page=my-calendar-design' ) );
			// Translators: 1) URL for link to remigrate styles. 2) URL to dismiss message. 3) Help link.
			mc_show_notice( sprintf( __( 'Your previously migrated CSS file (<code>%1$s</code>) got put in the wrong directory. <a href="%2$s" class="button-secondary">Move it to the right place?</a> <a href="%3$s" class="button-primary">No, thanks.</a>', 'my-calendar' ), $migrations, $migrate_url, $dismiss_url ) );
		}
	}
}

/**
 * Generate stylesheet editor
 */
function my_calendar_style_edit() {
	$edit_files = true;
	$message    = '';
	if ( ! current_user_can( 'mc_edit_styles' ) ) {
		echo wp_kses_post( '<p>' . __( 'You do not have permission to customize styles on this site.', 'my-calendar' ) . '</p>' );
		return;
	}
	if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT === true ) {
		$edit_files = false;
		mc_show_error( __( 'File editing is disallowed in your WordPress installation. Edit your stylesheets offline.', 'my-calendar' ) );
	}
	mc_migrate_css();
	mc_migrate_notice();
	mc_remigrate_css();
	mc_remigrate_notice();
	if ( isset( $_POST['mc_edit_style'] ) || isset( $_POST['mc_reset_style'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'My Calendar: Security check failed' );
		}
		if ( isset( $_POST['mc_reset_style'] ) ) {
			if ( ! empty( $_POST['reset_styles'] ) ) {
				$stylefile        = mc_get_style_path();
				$styles           = mc_default_style();
				$wrote_old_styles = mc_write_styles( $stylefile, $styles );
				if ( $wrote_old_styles ) {
					$message = '<p>' . __( 'Stylesheet updated to match core version.', 'my-calendar' ) . '</p>';
				}
			}
		} else {
			$my_calendar_style = ( isset( $_POST['style'] ) ) ? stripcslashes( $_POST['style'] ) : false;
			$mc_css_file       = stripcslashes( sanitize_file_name( $_POST['mc_css_file'] ) );

			if ( $edit_files ) {
				$stylefile    = mc_get_style_path( $mc_css_file );
				$wrote_styles = ( false !== $my_calendar_style ) ? mc_write_styles( $stylefile, $my_calendar_style ) : 'disabled';
			} else {
				$wrote_styles = false;
			}

			if ( 'disabled' === $wrote_styles ) {
				$message = '<p>' . __( 'Styles are disabled, and were not edited.', 'my-calendar' ) . '</p>';
			} else {
				$message = ( true === $wrote_styles ) ? '<p>' . __( 'The stylesheet has been updated.', 'my-calendar' ) . ' <a href="' . add_query_arg( 'migrate', $nonce, admin_url( 'admin.php?page=my-calendar-design' ) ) . '">' . __( 'Migrate your CSS to the Custom CSS Directory', 'my-calendar' ) . '</a></p>' : '<p><strong>' . __( 'Write Error! Please verify write permissions on the style file.', 'my-calendar' ) . '</strong></p>';
			}

			$mc_show_css = ( empty( $_POST['mc_show_css'] ) ) ? '' : stripcslashes( sanitize_text_field( $_POST['mc_show_css'] ) );
			mc_update_option( 'show_css', $mc_show_css );
			$use_styles = ( empty( $_POST['use_styles'] ) ) ? '' : 'true';
			mc_update_option( 'use_styles', $use_styles );

			if ( ! empty( $_POST['style_vars'] ) ) {
				$styles = mc_get_option( 'style_vars' );
				if ( isset( $_POST['new_style_var'] ) ) {
					$key = sanitize_text_field( $_POST['new_style_var']['key'] );
					$val = sanitize_text_field( $_POST['new_style_var']['val'] );
					if ( $key && $val ) {
						if ( 0 !== strpos( $key, '--' ) ) {
							$key = '--' . $key;
						}
						$styles[ $key ] = $val;
					}
				}
				foreach ( $_POST['style_vars'] as $key => $value ) {
					if ( '' !== trim( $value ) ) {
						$styles[ $key ] = sanitize_text_field( $value );
					}
				}
				if ( isset( $_POST['delete_var'] ) ) {
					$delete = $_POST['delete_var'];
					foreach ( $delete as $del ) {
						unset( $styles[ $del ] );
					}
				}
				mc_update_option( 'style_vars', $styles );
			}

			$message .= ' ' . __( 'Style Settings Saved', 'my-calendar' ) . '.';
		}
		mc_show_notice( $message );
	}
	if ( isset( $_POST['mc_choose_style'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'My Calendar: Security check failed' );
		}
		$mc_css_file = stripcslashes( sanitize_file_name( $_POST['mc_css_file'] ) );

		mc_update_option( 'css_file', $mc_css_file );
		$message = '<p><strong>' . __( 'New theme selected.', 'my-calendar' ) . '</strong></p>';
		echo wp_kses_post( "<div id='message' class='updated fade'>$message</div>" );
	}

	$mc_show_css = mc_get_option( 'show_css' );
	$stylefile   = mc_get_style_path();
	if ( $stylefile ) {
		global $wp_filesystem;
		WP_Filesystem();
		$file              = $wp_filesystem->get_contents( $stylefile );
		$my_calendar_style = $file;
		$mc_current_style  = mc_default_style();
	} else {
		$mc_current_style  = '';
		$my_calendar_style = __( 'Sorry. The file you are looking for doesn\'t appear to exist. Please check your file name and location!', 'my-calendar' );
	}
	$left_string  = normalize_whitespace( $my_calendar_style );
	$right_string = normalize_whitespace( $mc_current_style );
	if ( $right_string ) { // If right string is blank, there is no default.
		if ( isset( $_GET['diff'] ) ) {
			?>
			<div id="diff">
			<div class="reset-styles notice">
				<div class="faux-p">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-design' ) ); ?>" class="inline-form">
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
						<input type="hidden" value="true" name="mc_reset_style"/>
						<input type="hidden" name="mc_css_file" value="<?php echo esc_attr( mc_get_option( 'css_file' ) ); ?>"/>
						<input type="checkbox" id="reset_styles" name="reset_styles" <?php echo esc_attr( ( mc_is_custom_style( mc_get_option( 'css_file' ) ) ) ? 'disabled' : '' ); ?> /> <label for="reset_styles"><?php esc_html_e( 'Reset stylesheet to match core version', 'my-calendar' ); ?></label>
						<input type="submit" name="save" class="button-primary button-adjust" value="<?php esc_attr_e( 'Reset Styles', 'my-calendar' ); ?>" />
					</form>
					<a class="button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-design' ) ); ?>"><?php esc_html_e( 'Return to editing', 'my-calendar' ); ?></a>
				</div>
			</div>
			<?php
			$diff = wp_text_diff(
				$left_string,
				$right_string,
				array(
					'title'       => __( 'Comparing Your Style with latest installed version of My Calendar', 'my-calendar' ),
					'title_right' => __( 'Latest (from plugin)', 'my-calendar' ),
					'title_left'  => __( 'Current (in use)', 'my-calendar' ),
				)
			);
			echo wp_kses_post( $diff );
			?>
			</div>
			<?php
		}
		if ( trim( $left_string ) !== trim( $right_string ) && ! isset( $_GET['diff'] ) ) {
			mc_show_error( __( 'There have been updates to the stylesheet.', 'my-calendar' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=my-calendar-design&diff' ) ) . '">' . __( 'Compare Your Stylesheet with latest installed version of My Calendar.', 'my-calendar' ) . '</a>' );
		}
	}
	echo mc_stylesheet_selector();
	if ( ! isset( $_GET['diff'] ) ) {
		$file = mc_get_option( 'css_file' );
		?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-design' ) ); ?>">
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
		<input type="hidden" value="true" name="mc_edit_style" />
		<input type="hidden" name="mc_css_file" value="<?php echo esc_attr( $file ); ?>" />
		<div class="mc-input-with-note">
			<p>
				<label for="mc_show_css"><?php esc_html_e( 'Load CSS only on selected pages', 'my-calendar' ); ?></label><br />
				<input type="text" id="mc_show_css" name="mc_show_css" value="<?php echo esc_attr( $mc_show_css ); ?>" aria-describedby="mc_css_info" />
			</p>
			<span id="mc_css_info"><i class="dashicons dashicons-editor-help" aria-hidden="true"></i><?php esc_html_e( 'Comma-separated post IDs', 'my-calendar' ); ?></span>
		</div>
		<p>
			<input type="checkbox" id="use_styles" name="use_styles" <?php checked( mc_get_option( 'use_styles' ), 'true' ); ?> />
			<label for="use_styles"><?php esc_html_e( 'Disable styles', 'my-calendar' ); ?></label>
		</p>
		<fieldset class="mc-css-variables">
			<legend><?php esc_html_e( 'CSS Color Variables', 'my-calendar' ); ?></legend>
			<?php
			$output = '';
			$styles = mc_get_option( 'style_vars' );
			foreach ( $styles as $var => $style ) {
				$var_id = 'mc' . sanitize_key( $var );
				if ( ! in_array( $var, array( '--primary-dark', '--primary-light', '--secondary-light', '--secondary-dark', '--highlight-dark', '--highlight-light' ), true ) ) {
					// Translators: CSS variable name.
					$delete = " <input type='checkbox' id='delete_var_$var_id' name='delete_var[]' value='" . esc_attr( $var ) . "' /><label for='delete_var_$var_id'>" . sprintf( esc_html__( 'Delete %s', 'my-calendar' ), '<span class="screen-reader-text">' . $var . '</span>' ) . '</label>';
				} else {
					$delete = '';
				}
				$output .= "<li><label for='$var_id'>" . esc_html( $var ) . "</label> <input class='mc-color-input' type='text' id='$var_id' name='style_vars[$var]' value='" . esc_attr( $style ) . "' />$delete</li>";
			}
			if ( $output ) {
				echo wp_kses( "<ul class='checkboxes'>$output</ul>", mc_kses_elements() );
			}
			?>
			<p>
				<label for='new_style_var_key'><?php esc_html_e( 'New variable:', 'my-calendar' ); ?></label>
				<input type='text' name='new_style_var[key]' id='new_style_var_key' /> 
				<label for='new_style_var_val'><?php esc_html_e( 'Color:', 'my-calendar' ); ?></label>
				<input type='text' class="mc-color-input" name='new_style_var[val]' id='new_style_var_val' />
			</p>
		</fieldset>
		<fieldset style="position:relative;">
			<legend><?php esc_html_e( 'CSS Style Editor', 'my-calendar' ); ?></legend>
			<?php
			if ( mc_is_custom_style( mc_get_option( 'css_file' ) ) ) {
				echo wp_kses_post( '<div class="style-editor-notice"><p class="mc-editor-not-available">' . __( 'The editor is not available for custom CSS files. Edit your custom CSS locally, then upload your changes.', 'my-calendar' ) . '</p></div>' );
			} else {
				$nonce       = wp_create_nonce( 'mc-migrate-css' );
				$migrate_url = '<a href="' . add_query_arg( 'migrate', $nonce, admin_url( 'admin.php?page=my-calendar-design' ) ) . '" class="button-secondary">' . __( 'Migrate to custom CSS', 'my-calendar' ) . '</a>';
				$disabled    = ( $edit_files || mc_get_option( 'use_styles' ) === 'true' ) ? '' : ' disabled="disabled"';
				?>
				<p class="mc-label-with-button"><label for="style">
				<?php
				// Translators: file name being edited.
				echo sprintf( esc_html__( 'Edit %s', 'my-calendar' ), '<code>' . $file . '</code>' );
				?>
				</label><?php echo $migrate_url; ?></p><textarea <?php echo esc_attr( $disabled ); ?> class="style-editor" id="style" name="style" rows="30" cols="80"><?php echo esc_textarea( $my_calendar_style ); ?></textarea>
				<?php
			}
			?>
			<p>
				<input type="submit" name="save" class="button-primary button-adjust" value="<?php esc_attr_e( 'Save Changes', 'my-calendar' ); ?>" />
			</p>
		</fieldset>
	</form>
		<?php
	}
}

/**
 * Display color contrast array of custom variables.
 *
 * @return string
 */
function mc_display_contrast_variables() {
	$styles = mc_get_option( 'style_vars', array() );
	$comp   = $styles;
	$body   = '';
	$head   = '<th>' . __( 'Variable', 'my-calendar' ) . '</th>';
	foreach ( $styles as $var => $color ) {
		$head .= '<th scope="col">' . str_replace( '--', '', $var ) . '</th>';
		$row   = '<tr><th scope="row">' . str_replace( '--', '', $var ) . '</th>';
		foreach ( $comp as $var => $c ) {
			$compare = ( $color === $c ) ? '' : mc_test_contrast( $color, $c );
			// Translators: variable name.
			$row .= '<td><span class="comparison">' . sprintf( esc_html__( 'with %s:', 'my-calendar' ), str_replace( '--', '', $var ) ) . ' </span>' . $compare . '</td>';
		}
		$row  .= '</tr>';
		$body .= $row;
	}
	$header = '<thead><tr>' . $head . '</tr></thead>';
	$body   = '<tbody>' . $body . '</tbody>';

	$output = '<table class="mc-contrast-table striped"><caption>' . __( 'Accessible Color Combinations', 'my-calendar' ) . '</caption>' . $header . $body . '</table>';

	return $output;
}

/**
 * Test contrast and return output.
 *
 * @param string $color1 Color one.
 * @param string $color2 Color two.
 *
 * @return string
 */
function mc_test_contrast( $color1, $color2 ) {
	$colors   = mc_contrast( $color1, $color2 );
	$contrast = mc_luminosity( $colors['red1'], $colors['red2'], $colors['green1'], $colors['green2'], $colors['blue1'], $colors['blue2'] );
	$text     = '<p>' . __( 'Meets WCAG', 'my-calendar' ) . '</p>';
	$class    = '';
	if ( $contrast < 3.0 ) {
		return '<span>invalid</span>';
	}
	if ( $contrast < 4.5 && $contrast > 3.0 ) {
		$class = 'large-text';
		$text  = '<p>' . __( 'Large text only', 'my-calendar' ) . '</p>';
	}
	return '<div class="' . $class . '" style="color:' . $color1 . '; background: ' . $color2 . '">' . $contrast . $text . '</div>';
}

/**
 * Display stylesheet selector as added component in sidebar.
 *
 * @return string
 */
function mc_stylesheet_selector() {
	$deprecated       = array( 'dark.css', 'inherit.css', 'light.css', 'my-calendar.css', 'refresh.css', 'twentyfourteen.css', 'twentyfifteen.css' );
	$dir              = plugin_dir_path( __DIR__ );
	$options          = '';
	$return           = '
	<div class="style-selector">
	<form method="post" action="' . esc_url( admin_url( 'admin.php?page=my-calendar-design' ) ) . '">
		<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'my-calendar-nonce' ) . '"/><input type="hidden" value="true" name="mc_choose_style"/>';
	$custom_directory = str_replace( '/my-calendar/', '', $dir ) . '/my-calendar-custom/styles/';
	$directory        = dirname( __FILE__ ) . '/styles/';
	$files            = mc_css_list( $custom_directory );
	if ( ! empty( $files ) ) {
		$options .= '<optgroup label="' . __( 'Your Custom Stylesheets', 'my-calendar' ) . '">';
		foreach ( $files as $value ) {
			$test     = 'mc_custom_' . $value;
			$filepath = mc_get_style_path( $test );
			$path     = pathinfo( $filepath );
			if ( 'css' === $path['extension'] ) {
				$selected = ( mc_get_option( 'css_file' ) === $test ) ? ' selected="selected"' : '';
				$options .= "<option value='mc_custom_$value'$selected>$value</option>\n";
			}
		}
		$options .= '</optgroup>';
	}
	$files    = mc_css_list( $directory );
	$options .= '<optgroup label="' . __( 'Installed Stylesheets', 'my-calendar' ) . '">';
	$current  = mc_get_option( 'css_file' );
	foreach ( $files as $value ) {
		$append = '';
		if ( in_array( $value, $deprecated, true ) && $value !== $current ) {
			continue;
		}
		if ( in_array( $value, $deprecated, true ) && $value === $current ) {
			$append = ' (' . __( 'Deprecated', 'my-calendar' ) . ')';
		}
		$filepath = mc_get_style_path( $value );
		$path     = pathinfo( $filepath );
		if ( isset( $path['extension'] ) && 'css' === $path['extension'] ) {
			$selected = ( $current === $value ) ? ' selected="selected"' : '';
			$options .= "<option value='$value'$selected>$value$append</option>\n";
		}
	}
	$options .= '</optgroup>';
	$return  .= '
		<fieldset>
			<p>
				<label for="mc_css_file">' . __( 'Select My Calendar Theme', 'my-calendar' ) . '</label><br />
				<select name="mc_css_file" id="mc_css_file">' . $options . '</select>
			</p>
			<p>
				<input type="submit" name="save" class="button-primary" value="' . __( 'Choose Style', 'my-calendar' ) . '"/>
			</p>
		</fieldset>
	</form>';
	$link     = add_query_arg( 'mcpreview', mc_get_option( 'css_file' ), mc_get_uri() );
	$return  .= '<a href="' . esc_url( $link ) . '" class="preview-link" data-css="' . esc_attr( mc_get_option( 'css_file' ) ) . '">' . __( 'Preview Stylesheet', 'my-calendar' ) . '</a></div>';

	return $return;
}

/**
 * Get path for given filename or current selected stylesheet.
 *
 * @param string|false $filename File name or false for current selection.
 * @param string       $type path or url.
 *
 * @return mixed string/boolean
 */
function mc_get_style_path( $filename = false, $type = 'path' ) {
	$url = plugin_dir_url( __DIR__ );
	$dir = plugin_dir_path( __DIR__ );
	if ( ! $filename ) {
		$filename = mc_get_option( 'css_file' );
	}
	if ( 0 === strpos( $filename, 'mc_custom_' ) ) {
		$filename  = str_replace( 'mc_custom_', '', $filename );
		$stylefile = ( 'path' === $type ) ? str_replace( '/my-calendar/', '', $dir ) . '/my-calendar-custom/styles/' . $filename : str_replace( '/my-calendar/', '', $url ) . '/my-calendar-custom/styles/' . $filename;
	} else {
		$stylefile = ( 'path' === $type ) ? dirname( __FILE__ ) . '/styles/' . $filename : plugins_url( 'styles', __FILE__ ) . '/' . $filename;
	}
	if ( 'path' === $type ) {
		if ( is_file( $stylefile ) ) {
			return $stylefile;
		} else {
			return false;
		}
	} else {
		return $stylefile;
	}
}

/**
 * Fetch the styles for the current selected style
 *
 * @param string|false $filename File name or false to return defined default stylesheet.
 * @param string       $return content, filename, or both.
 *
 * @return string|array File name, file content, or array with both.
 */
function mc_default_style( $filename = false, $return = 'content' ) {
	if ( ! $filename ) {
		$mc_css_file = mc_get_option( 'css_file', '' );
	} else {
		$mc_css_file = trim( $filename );
	}
	$mc_current_file = dirname( __FILE__ ) . '/templates/' . $mc_css_file;
	global $wp_filesystem;
	WP_Filesystem();
	if ( $mc_css_file && $wp_filesystem->exists( $mc_current_file ) ) {
		$file             = $wp_filesystem->get_contents( $mc_current_file );
		$mc_current_style = $file;
		switch ( $return ) {
			case 'content':
				return $mc_current_style;
			case 'path':
				return $mc_current_file;
			case 'both':
				return array( $mc_current_file, $mc_current_style );
		}
	}

	return '';
}

/**
 * List CSS files in a directory
 *
 * @param string $directory File directory.
 *
 * @return array list of CSS files
 */
function mc_css_list( $directory ) {
	if ( ! file_exists( $directory ) ) {
		return array();
	}
	$results = array();
	$handler = opendir( $directory );
	// Keep going until all files in directory have been read.
	while ( false !== ( $file = readdir( $handler ) ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		// If $file isn't this directory or parent, add it to the results array.
		if ( '.' !== $file && '..' !== $file ) {
			$results[] = $file;
		}
	}
	closedir( $handler );
	sort( $results, SORT_STRING );

	return $results;
}

/**
 * Write updated styles to file
 *
 * @param string $file File to write to.
 * @param string $style New styles to write.
 *
 * @return boolean;
 */
function mc_write_styles( $file, $style ) {
	if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT === true ) {
		return false;
	}

	$standard        = dirname( __FILE__ ) . '/styles/';
	$files           = mc_css_list( $standard );
	$accepted_styles = array();
	foreach ( $files as $f ) {
		$filepath = mc_get_style_path( $f );
		$path     = pathinfo( $filepath );
		if ( 'css' === $path['extension'] ) {
			$accepted_styles[] = $filepath;
		}
	}

	if ( in_array( $file, $accepted_styles, true ) ) {
		$is_writable = wp_is_writable( $file );
		if ( $is_writable ) {
			global $wp_filesystem;
			WP_Filesystem();
			$saved = $wp_filesystem->put_contents( $file, $style );

			return $saved;
		} else {
			return false;
		}
	}

	return false;
}

add_action(
	'admin_enqueue_scripts',
	function() {
		if ( ! function_exists( 'wp_enqueue_code_editor' ) ) {
			return;
		}

		if ( sanitize_title( __( 'My Calendar', 'my-calendar' ) ) . '_page_my-calendar-design' !== get_current_screen()->id ) {
			return;
		}

		// Enqueue code editor and settings for manipulating HTML.
		$settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );

		// Bail if user disabled CodeMirror.
		if ( false === $settings ) {
			return;
		}

		wp_add_inline_script(
			'code-editor',
			sprintf(
				'jQuery( function() { wp.codeEditor.initialize( "style", %s ); } );',
				wp_json_encode( $settings )
			)
		);
	}
);


/**
 * Measure the relative luminosity between two RGB values.
 *
 * @param int $r Red value 1.
 * @param int $r2 Red value 2.
 * @param int $g Green value 1.
 * @param int $g2 Green value 2.
 * @param int $b Blue value 1.
 * @param int $b2 Blue value 2.
 *
 * @return float luminosity ratio between 1.0 and 21.0.
 */
function mc_luminosity( $r, $r2, $g, $g2, $b, $b2 ) {
	$rs_rgb = $r / 255;
	$gs_rgb = $g / 255;
	$bs_rgb = $b / 255;
	$r_new  = ( $rs_rgb <= 0.03928 ) ? $rs_rgb / 12.92 : pow( ( $rs_rgb + 0.055 ) / 1.055, 2.4 );
	$g_new  = ( $gs_rgb <= 0.03928 ) ? $gs_rgb / 12.92 : pow( ( $gs_rgb + 0.055 ) / 1.055, 2.4 );
	$b_new  = ( $bs_rgb <= 0.03928 ) ? $bs_rgb / 12.92 : pow( ( $bs_rgb + 0.055 ) / 1.055, 2.4 );

	$rs_rgb2 = $r2 / 255;
	$gs_rgb2 = $g2 / 255;
	$bs_rgb2 = $b2 / 255;
	$r2_new  = ( $rs_rgb2 <= 0.03928 ) ? $rs_rgb2 / 12.92 : pow( ( $rs_rgb2 + 0.055 ) / 1.055, 2.4 );
	$g2_new  = ( $gs_rgb2 <= 0.03928 ) ? $gs_rgb2 / 12.92 : pow( ( $gs_rgb2 + 0.055 ) / 1.055, 2.4 );
	$b2_new  = ( $bs_rgb2 <= 0.03928 ) ? $bs_rgb2 / 12.92 : pow( ( $bs_rgb2 + 0.055 ) / 1.055, 2.4 );

	if ( $r + $g + $b <= $r2 + $g2 + $b2 ) {
		$l2 = ( .2126 * $r_new + 0.7152 * $g_new + 0.0722 * $b_new );
		$l1 = ( .2126 * $r2_new + 0.7152 * $b2_new + 0.0722 * $b2_new );
	} else {
		$l1 = ( .2126 * $r_new + 0.7152 * $g_new + 0.0722 * $b_new );
		$l2 = ( .2126 * $r2_new + 0.7152 * $g2_new + 0.0722 * $b2_new );
	}
	$luminosity = round( ( $l1 + 0.05 ) / ( $l2 + 0.05 ), 2 );

	return $luminosity;
}

/**
 * Convert an RGB value to a HEX value.
 *
 * @param int|array $r Red value or array with r, g, b keys.
 * @param int       $g Green value.
 * @param int       $b Blue value.
 *
 * @return string Hexadecimal color equivalent of passed RGB value.
 */
function mc_rgb2hex( $r, $g = - 1, $b = - 1 ) {
	if ( is_array( $r ) && sizeof( $r ) === 3 ) {
		list( $r, $g, $b ) = $r;
	}
	$r = intval( $r );
	$g = intval( $g );
	$b = intval( $b );

	$r = dechex( $r < 0 ? 0 : ( $r > 255 ? 255 : $r ) );
	$g = dechex( $g < 0 ? 0 : ( $g > 255 ? 255 : $g ) );
	$b = dechex( $b < 0 ? 0 : ( $b > 255 ? 255 : $b ) );

	$color  = ( strlen( $r ) < 2 ? '0' : '' ) . $r;
	$color .= ( strlen( $g ) < 2 ? '0' : '' ) . $g;
	$color .= ( strlen( $b ) < 2 ? '0' : '' ) . $b;

	return '#' . $color;
}

/**
 * Convert a Hexadecimal color value to RGB.
 *
 * @param string $color Hexadecimal value for a color.
 *
 * @return array of RGB values in R,G,B order.
 */
function mc_hex2rgb( $color ) {
	$color = str_replace( '#', '', $color );
	if ( strlen( $color ) !== 6 ) {
		return array( 0, 0, 0 );
	}
	$rgb = array();
	for ( $x = 0; $x < 3; $x ++ ) {
		$rgb[ $x ] = hexdec( substr( $color, ( 2 * $x ), 2 ) );
	}

	return $rgb;
}

/**
 * Calculate the luminosity ratio between two color values.
 *
 * @param string $color1 First color.
 * @param string $color2 Second color.
 *
 * @return array
 */
function mc_contrast( $color1, $color2 ) {
	$fore_color = $color1;
	if ( '#' === $fore_color[0] ) {
		$fore_color = str_replace( '#', '', $fore_color );
	}
	if ( 3 === strlen( $fore_color ) ) {
		$color6char  = $fore_color[0] . $fore_color[0];
		$color6char .= $fore_color[1] . $fore_color[1];
		$color6char .= $fore_color[2] . $fore_color[2];
		$fore_color  = $color6char;
	}
	if ( preg_match( '/^#?([0-9a-f]{1,2}){3}$/i', $fore_color ) ) {
		$echo_hex_fore = str_replace( '#', '', $fore_color );
	} else {
		$echo_hex_fore = 'FFFFFF';
	}
	$back_color = $color2;
	if ( '#' === $back_color[0] ) {
		$back_color = str_replace( '#', '', $back_color );
	}
	if ( 3 === strlen( $back_color ) ) {
		$color6char  = $back_color[0] . $back_color[0];
		$color6char .= $back_color[1] . $back_color[1];
		$color6char .= $back_color[2] . $back_color[2];
		$back_color  = $color6char;
	}
	if ( preg_match( '/^#?([0-9a-f]{1,2}){3}$/i', $back_color ) ) {
		$echo_hex_back = str_replace( '#', '', $back_color );
	} else {
		$echo_hex_back = 'FFFFFF';
	}
	$color  = mc_hex2rgb( $echo_hex_fore );
	$color2 = mc_hex2rgb( $echo_hex_back );
	$rfore  = $color[0];
	$gfore  = $color[1];
	$bfore  = $color[2];
	$rback  = $color2[0];
	$gback  = $color2[1];
	$bback  = $color2[2];
	$colors = array(
		'hex1'   => $echo_hex_fore,
		'hex2'   => $echo_hex_back,
		'red1'   => $rfore,
		'green1' => $gfore,
		'blue1'  => $bfore,
		'red2'   => $rback,
		'green2' => $gback,
		'blue2'  => $bback,
	);

	return $colors;
}
