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
 * Generate stylesheet editor
 */
function my_calendar_style_edit() {
	$message = '';
	if ( ! current_user_can( 'mc_edit_styles' ) ) {
		echo wp_kses_post( '<p>' . __( 'You do not have permission to customize styles on this site.', 'my-calendar' ) . '</p>' );

		return;
	}

	if ( isset( $_POST['mc_edit_style'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			wp_die( 'My Calendar: Security check failed' );
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
			if ( isset( $_POST['new_style_text_var'] ) ) {
				$key = sanitize_text_field( $_POST['new_style_text_var']['key'] );
				$val = sanitize_text_field( $_POST['new_style_text_var']['val'] );
				if ( $key && $val ) {
					if ( 0 !== strpos( $key, '--' ) ) {
						$key = '--' . $key;
					}
					$styles['text'][ $key ] = $val;
				}
			}
			foreach ( $_POST['style_vars'] as $key => $value ) {
				if ( 'text' === $key ) {
					foreach ( $value as $var => $text ) {
						if ( '' !== trim( $text ) ) {
							$styles['text'][ $var ] = sanitize_text_field( $text );
						}
					}
				} else {
					if ( '' !== trim( $value ) ) {
						$styles[ $key ] = sanitize_text_field( $value );
					}
				}
			}
			if ( isset( $_POST['delete_var'] ) ) {
				$delete = map_deep( $_POST['delete_var'], 'sanitize_text_field' );
				foreach ( $delete as $del ) {
					unset( $styles[ $del ] );
				}
			}
			if ( isset( $_POST['delete_var_text'] ) ) {
				$delete = map_deep( $_POST['delete_var_text'], 'sanitize_text_field' );
				foreach ( $delete as $del ) {
					unset( $styles['text'][ $del ] );
				}
			}
			mc_update_option( 'style_vars', $styles );
		}

		$message .= ' ' . __( 'Style Settings Saved', 'my-calendar' ) . '.';

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
	?>
	<div class="my-calendar-style-settings">
	<?php
	echo mc_stylesheet_selector();
	$file = mc_get_option( 'css_file' );
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-design' ) ); ?>">
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>" />
		<input type="hidden" value="true" name="mc_edit_style" />
		<input type="hidden" name="mc_css_file" value="<?php echo esc_attr( $file ); ?>" />
		<fieldset class="mc-css-variables">
			<legend><?php esc_html_e( 'CSS Variables', 'my-calendar' ); ?></legend>
			<?php
			$output      = '';
			$text_output = '';
			$styles      = mc_get_option( 'style_vars' );
			$styles      = mc_style_variables( $styles );
			foreach ( $styles as $var => $style ) {
				if ( 'text' === $var ) {
					foreach ( $style as $variable => $value ) {
						$variable_id = 'mc' . sanitize_key( $variable );
						if ( ! in_array( $variable, array_keys( mc_style_variables()['text'] ), true ) ) {
							// Translators: CSS variable name.
							$delete = " <input type='checkbox' id='delete_var_$variable_id' name='delete_var_text[]' value='" . esc_attr( $variable ) . "' /><label for='delete_var_$variable_id'>" . sprintf( esc_html__( 'Delete %s', 'my-calendar' ), '<span class="screen-reader-text">' . $variable . '</span>' ) . '</label>';
						} else {
							$delete = '';
						}
						$text_output .= "<li><label for='$variable_id'>" . esc_html( $variable ) . "</label> <input class='mc-text-input' type='text' id='$variable_id' data-variable='$variable' name='style_vars[text][$variable]' value='" . esc_attr( $value ) . "' />$delete</li>";
					}
				} else {
					$var_id = 'mc' . sanitize_key( $var );
					if ( ! in_array( $var, array_keys( mc_style_variables() ), true ) ) {
						// Translators: CSS variable name.
						$delete = " <input type='checkbox' id='delete_var_$var_id' name='delete_var[]' value='" . esc_attr( $var ) . "' /><label for='delete_var_$var_id'>" . sprintf( esc_html__( 'Delete %s', 'my-calendar' ), '<span class="screen-reader-text">' . $var . '</span>' ) . '</label>';
					} else {
						$delete = '';
					}
					$output .= "<li><label for='$var_id'>" . esc_html( $var ) . "</label> <input class='mc-color-input' type='text' id='$var_id' data-variable='$var' name='style_vars[$var]' value='" . esc_attr( $style ) . "' />$delete</li>";
				}
			}
			if ( $output ) {
				echo '<h3>' . __( 'Color Variables', 'my-calendar' ) . '</h3>';
				echo wp_kses( "<ul class='mc-variables'>$output</ul>", mc_kses_elements() );
			}
			?>
			<div class="mc-new-variable">
				<p>
					<label for='new_style_var_key'><?php esc_html_e( 'New color variable', 'my-calendar' ); ?></label>
					<input type='text' name='new_style_var[key]' id='new_style_var_key' />
				</p>
				<p>
					<label for='new_style_var_val'><?php esc_html_e( 'Color', 'my-calendar' ); ?></label>
					<input type='text' class="mc-color-input" name='new_style_var[val]' id='new_style_var_val' />
				</p>
			</div>
			<?php
			if ( $text_output ) {
				echo '<h3>' . __( 'Style Variables', 'my-calendar' ) . '</h3>';
				echo wp_kses( "<ul class='mc-variables'>$text_output</ul>", mc_kses_elements() );
			}
			?>
			<div class="mc-new-variable">
				<p>
					<label for='new_style_var_text_key'><?php esc_html_e( 'New text variable', 'my-calendar' ); ?></label>
					<input type='text' name='new_style_var_text[key]' id='new_style_var_text_key' />
				</p>
				<p>
					<label for='new_style_var_text_val'><?php esc_html_e( 'Value', 'my-calendar' ); ?></label>
					<input type='text' class="mc-text-input" name='new_style_var_text[val]' id='new_style_var_text_val' />
				</p>
			</div>
		</fieldset>
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
		<p>
				<input type="submit" name="save" class="button-primary button-adjust" value="<?php esc_attr_e( 'Save Changes', 'my-calendar' ); ?>" />
		</p>
	</form>
	</div>
	<div class="my-calendar-style-preview">
	<?php
	echo do_shortcode( '[my_calendar]' );
	?>
	</div>
	<?php
}

/**
 * Display color contrast array of custom variables.
 *
 * @return string
 */
function mc_display_contrast_variables() {
	$styles = mc_get_option( 'style_vars', array() );
	$styles = mc_style_variables( $styles );
	// Eliminate text settings.
	unset( $styles['text'] );
	$colors = array();
	// Eliminate duplicate colors and transparency. Only compare unique colors.
	foreach ( $styles as $variable => $color ) {
		if ( in_array( $color, $colors, true ) || 'transparent' === $color || strlen( $color ) > 7 ) {
			unset( $styles[ $variable ] );
		}
		$colors[] = $color;
	}
	$comp = $styles;
	$body = '';
	$head = '<th>' . __( 'Variable', 'my-calendar' ) . '</th>';
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
		$text = '<p>' . __( 'Fails', 'my-calendar' ) . '</p>';
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
	$dir              = plugin_dir_path( __DIR__ );
	$options          = '';
	$return           = '
	<div class="style-selector">
	<form method="post" action="' . esc_url( admin_url( 'admin.php?page=my-calendar-design' ) ) . '">
		<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'my-calendar-nonce' ) . '"/><input type="hidden" value="true" name="mc_choose_style"/>';
	$custom_directory = str_replace( '/my-calendar/', '', $dir ) . '/my-calendar-custom/styles/';
	$directory        = __DIR__ . '/styles/';
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
		$filepath = mc_get_style_path( $value );
		$path     = pathinfo( $filepath );
		if ( isset( $path['extension'] ) && 'css' === $path['extension'] ) {
			$selected = ( $current === $value ) ? ' selected="selected"' : '';
			$options .= "<option value='$value'$selected>$value</option>\n";
		}
	}
	$options .= '</optgroup>';
	$return  .= '
		<div>
			<p>
				<label for="mc_css_file">' . __( 'Select Theme (optional)', 'my-calendar' ) . '</label><br />
				<select name="mc_css_file" id="mc_css_file"><option value="">' . __( 'None', 'my-calendar' ) . '</option>' . $options . '</select>
			</p>
			<p>
				<input type="submit" name="save" class="button-primary" value="' . __( 'Choose Style', 'my-calendar' ) . '"/>
			</p>
		</div>
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
	if ( ! $filename ) {
		return '';
	}
	if ( 0 === strpos( $filename, 'mc_custom_' ) ) {
		$filename  = str_replace( 'mc_custom_', '', $filename );
		$stylefile = ( 'path' === $type ) ? str_replace( '/my-calendar/', '', $dir ) . '/my-calendar-custom/styles/' . $filename : str_replace( '/my-calendar/', '', $url ) . '/my-calendar-custom/styles/' . $filename;
	} else {
		$stylefile = ( 'path' === $type ) ? __DIR__ . '/styles/' . $filename : plugins_url( 'styles', __FILE__ ) . '/' . $filename;
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
	while ( false !== ( $file = readdir( $handler ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
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
	$color   = str_replace( '#', '', $color );
	$rgb     = array();
	$opacity = 1;
	if ( strlen( $color ) === 8 ) {
		$opacity = hexdec( substr( $color, 6, 2 ) );
		$color   = substr( $color, 0, 6 );
	}
	if ( strlen( $color ) !== 6 ) {
		return array( 0, 0, 0 );
	}
	for ( $x = 0; $x < 3; $x++ ) {
		$rgb[ $x ] = hexdec( substr( $color, ( 2 * $x ), 2 ) );
	}
	$rgb['opacity'] = $opacity;

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
	$fore_color   = $color1;
	$fore_opacity = '';
	$back_opacity = '';
	if ( '#' === substr( $fore_color, 0, 1 ) ) {
		$fore_color = str_replace( '#', '', $fore_color );
	}
	if ( 3 === strlen( $fore_color ) ) {
		$color6char  = $fore_color[0] . $fore_color[0];
		$color6char .= $fore_color[1] . $fore_color[1];
		$color6char .= $fore_color[2] . $fore_color[2];
		$fore_color  = $color6char;
	}
	if ( 8 === strlen( $fore_color ) ) {
		$fore_opacity = substr( $fore_color, 6, 2 );
		$fore_color   = substr( $fore_color, 0, 6 );
	}
	if ( preg_match( '/^#?([0-9a-f]{1,2}){3}$/i', $fore_color ) ) {
		$echo_hex_fore = str_replace( '#', '', $fore_color );
	} else {
		$echo_hex_fore = 'FFFFFF';
	}
	$back_color = $color2;
	if ( '#' === substr( $back_color, 0, 1 ) ) {
		$back_color = str_replace( '#', '', $back_color );
	}
	if ( 3 === strlen( $back_color ) ) {
		$color6char  = $back_color[0] . $back_color[0];
		$color6char .= $back_color[1] . $back_color[1];
		$color6char .= $back_color[2] . $back_color[2];
		$back_color  = $color6char;
	}
	if ( 8 === strlen( $back_color ) ) {
		$back_opacity = substr( $back_color, 6, 2 );
		$back_color   = substr( $back_color, 0, 6 );
	}
	if ( preg_match( '/^#?([0-9a-f]{1,2}){3}$/i', $back_color ) ) {
		$echo_hex_back = str_replace( '#', '', $back_color );
	} else {
		$echo_hex_back = 'FFFFFF';
	}
	$color  = mc_hex2rgb( $echo_hex_fore . $fore_opacity );
	$color2 = mc_hex2rgb( $echo_hex_back . $back_opacity );
	$rfore  = $color[0];
	$gfore  = $color[1];
	$bfore  = $color[2];
	$rback  = $color2[0];
	$gback  = $color2[1];
	$bback  = $color2[2];
	$colors = array(
		'hex1'   => $echo_hex_fore . $fore_opacity,
		'hex2'   => $echo_hex_back . $back_opacity,
		'red1'   => $rfore,
		'green1' => $gfore,
		'blue1'  => $bfore,
		'op1'    => $color['opacity'],
		'red2'   => $rback,
		'green2' => $gback,
		'blue2'  => $bback,
		'op2'    => $color2['opacity'],
	);

	return $colors;
}
