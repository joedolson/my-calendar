<?php
/**
 * Manage My Calendar templates.
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
 * Save template changes.
 */
function mc_templates_do_edit() {
	if ( isset( $_GET['page'] ) && 'my-calendar-design' === $_GET['page'] ) {
		if ( ! empty( $_POST ) ) {
			$nonce = $_REQUEST['_wpnonce'];
			if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
				wp_die( 'My Calendar: Security check failed' );
			}
		}
		if ( isset( $_POST['mc_template_key'] ) ) {
			$key = sanitize_text_field( $_POST['mc_template_key'] );
		} else {
			$key = isset( $_GET['mc_template'] ) ? sanitize_text_field( $_GET['mc_template'] ) : '';
		}
		if ( isset( $_POST['delete'] ) ) {
			delete_option( 'mc_ctemplate_' . $key );
			wp_safe_redirect( admin_url( 'admin.php?page=my-calendar-design&action=deleted#my-calendar-templates' ) );
		} else {
			if ( mc_is_core_template( $key ) && isset( $_POST['add-new'] ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=my-calendar-design&action=duplicate#my-calendar-templates' ) );
			} else {
				if ( mc_is_core_template( $key ) && isset( $_POST['mc_template'] ) ) {
					// Curly braces are not allowed in style attributes, so replace plain color template tags with invalid color before sanitizing.
					$template = ( ! empty( $_POST['mc_template'] ) ) ? wp_kses_post( stripslashes( str_replace( array( '{color}', '{inverse}' ), array( '#fff1a', '#000a1' ), $_POST['mc_template'] ) ) ) : '';
					// Restore template tag after sanitizing.
					$template          = str_replace( array( '#fff1a', '#000a1' ), array( '{color}', '{inverse}' ), $template );
					$templates         = mc_get_option( 'templates', array() );
					$templates[ $key ] = $template;
					mc_update_option( 'templates', $templates );
					mc_update_option( 'use_' . $key . '_template', ( empty( $_POST['mc_use_template'] ) ? 0 : 1 ) );
					wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=my-calendar-design&action=core&mc_template=' . $key . '#my-calendar-templates' ) ) );
				} elseif ( isset( $_POST['mc_template'] ) ) {
					// Curly braces are not allowed in style attributes, so replace plain color template tags with invalid color before sanitizing.
					$template = ( ! empty( $_POST['mc_template'] ) ) ? wp_kses_post( stripslashes( str_replace( array( '{color}', '{inverse}' ), array( '#fff1a', '#000a1' ), $_POST['mc_template'] ) ) ) : '';
					// Restore template tag after sanitizing.
					$template = str_replace( array( '#fff1a', '#000a1' ), array( '{color}', '{inverse}' ), $template );
					if ( mc_key_exists( $key ) ) {
						$key = mc_update_template( $key, $template );
					} else {
						$key = mc_create_template( $template, $_POST );
					}
					wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=my-calendar-design&action=custom&mc_template=' . $key . '#my-calendar-templates' ) ) );
				}
			}
		}
	}
}
add_action( 'admin_init', 'mc_templates_do_edit' );

/**
 * Documentation for PHP templates.
 */
function mc_php_templates_docs() {
	$intro     = '<p>' . __( 'PHP templates are enabled. To customize templates, copy one or more of the following files into your theme directory.', 'my-calendar' ) . '</p>';
	$intro    .= '<p><a href="https://docs.joedolson.com/my-calendar/php-templates/">' . __( 'Read the documentation.', 'my-calendar' ) . '</a></p>';
	$file_list = '<h3>' . __( 'Available Templates', 'my-calendar' ) . '</h3><ul class="mc-file-list">
		<li><code>/mc-templates/</code>
			<ul>
				<li><code>/mc-templates/event/</code>
					<ul>
						<li><code>/mc-templates/event/calendar-title.php</code></li>
						<li><code>/mc-templates/event/calendar.php</code></li>
						<li><code>/mc-templates/event/card-title.php</code></li>
						<li><code>/mc-templates/event/card.php</code></li>
						<li><code>/mc-templates/event/list-title.php</code></li>
						<li><code>/mc-templates/event/list.php</code></li>
						<li><code>/mc-templates/event/mini-title.php</code></li>
						<li><code>/mc-templates/event/mini.php</code></li>
						<li><code>/mc-templates/event/calendar-title.php</code></li>
						<li><code>/mc-templates/event/next.php</code></li>
						<li><code>/mc-templates/event/now.php</code></li>
						<li><code>/mc-templates/event/single-title.php</code></li>
						<li><code>/mc-templates/event/single.php</code></li>
						<li><code>/mc-templates/event/today.php</code></li>
						<li><code>/mc-templates/event/upcoming.php</code></li>
					</ul>
				</li>
				<li><code>/mc-templates/location/</code>
				<ul>
					<li><code>/mc-templates/location/location-single.php</code></li>
				</ul>
			</li>
		</ul>';

	return $intro . $file_list;
}

/**
 * Template editing page.
 */
function mc_templates_edit() {
	if ( ! current_user_can( 'mc_edit_templates' ) ) {
		echo wp_kses_post( '<p>' . __( 'You do not have permission to customize templates on this site.', 'my-calendar' ) . '</p>' );
		return;
	}
	if ( 'true' === mc_get_option( 'disable_legacy_templates' ) ) {
		echo mc_php_templates_docs();
		return;
	}
	$templates = mc_get_option( 'templates', array() );
	$key       = ( isset( $_GET['mc_template'] ) ) ? sanitize_text_field( $_GET['mc_template'] ) : false;

	if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] ) {
		mc_show_notice( __( 'Custom template deleted', 'my-calendar' ) );
		$key = '';
	} else {
		if ( mc_is_core_template( $key ) && isset( $_GET['action'] ) && 'duplicate' === $_GET['action'] ) {
			mc_show_notice( __( 'Custom templates cannot have the same key as a core template', 'my-calendar' ) );
		} else {
			if ( mc_is_core_template( $key ) && isset( $_GET['action'] ) && 'core' === $_GET['action'] ) {
				// Translators: unique key for template.
				mc_show_notice( sprintf( __( '%s Template saved', 'my-calendar' ), ucfirst( $key ) ) );
			} elseif ( isset( $_GET['action'] ) && 'custom' === $_GET['action'] ) {
				mc_show_notice( __( 'Custom Template saved', 'my-calendar' ) );
			}
		}
	}

	$template = ( mc_is_core_template( $key ) ) ? $templates[ $key ] : mc_get_custom_template( $key );
	$template = stripslashes( $template );
	$core     = mc_admin_template_description( $key );
	if ( $key ) {
		?>
	<div class="mc-postbox" id="mc-edit-template">
		<h2>
		<?php
		$heading = ( 'add-new' === $key ) ? __( 'Add New Template', 'my-calendar' ) : __( 'Edit Template', 'my-calendar' );
		echo esc_html( $heading );
		?>
		</h2>
		<div class="mc-inside">
			<?php echo ( '' !== $core ) ? wp_kses_post( "<div class='template-description'>$core</div>" ) : ''; ?>
			<form method="post" action="<?php echo esc_url( add_query_arg( 'mc_template', $key, admin_url( 'admin.php?page=my-calendar-design' ) ) ); ?>#my-calendar-templates">
				<div>
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
				</div>
			<?php
			if ( 'add-new' === $key ) {
				?>
				<p>
					<label for="mc_template_key"><?php esc_html_e( 'Template Description (required)', 'my-calendar' ); ?></label><br />
					<input type="text" class="widefat" name="mc_template_key" id="mc_template_key" value="" required />
				</p>
				<p>
					<label for="mc_template"><?php esc_html_e( 'Custom Template', 'my-calendar' ); ?></label><br/>
					<textarea id="mc_template" name="mc_template" class="template-editor widefat" rows="16" cols="76"></textarea>
				</p>
				<p>
					<input type="submit" name="save" class="button-primary" value="<?php esc_attr_e( 'Add Template', 'my-calendar' ); ?>" /> <a class="button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=my-calendar-design' ) ); ?>#my-calendar-templates"><?php esc_html_e( 'Cancel', 'my-calendar' ); ?></a>
				</p>
				<?php
			} else {
				if ( mc_is_core_template( $key ) ) {
					?>
				<p>
					<input type="checkbox" id="mc_use_template" name="mc_use_template" value="1" <?php checked( mc_get_option( 'use_' . $key . '_template' ), '1' ); ?> /> <label for="mc_use_template"><?php esc_html_e( 'Use this template', 'my-calendar' ); ?></label>
				</p>
					<?php
				}
				?>
				<p>
					<label for="mc_template">
					<?php
					// Translators: template type.
					printf( __( 'Custom Template (%s)', 'my-calendar' ), $key );
					?>
					</label><br/>
					<textarea id="mc_template" name="mc_template" class="template-editor widefat" rows="16" cols="76"><?php echo esc_textarea( $template ); ?></textarea>
				</p>
				<p>
					<input type="submit" name="save" class="button-primary" value="<?php esc_attr_e( 'Update Template', 'my-calendar' ); ?>" />
				<?php if ( ! mc_is_core_template( $key ) ) { ?>
					<input type="submit" name="delete" class="button-secondary" value="<?php esc_attr_e( 'Delete Template', 'my-calendar' ); ?>" />
				<?php } ?>
				</p>
			<?php } ?>
			</form>
		</div>
	</div>
		<?php
	}
	?>
	<div class="ui-sortable meta-box-sortables" id="core-templates">
		<div class="mc-postbox">
			<h2><?php esc_html_e( 'Core Templates', 'my-calendar' ); ?></h2>
			<div class="mc-inside">
			<?php
			echo wp_kses( mc_list_core_templates( $key ), mc_kses_elements() );
			?>
			</div>
		</div>
	</div>
	<div class="ui-sortable meta-box-sortables">
		<div class="mc-postbox">
			<h2><?php esc_html_e( 'Custom Templates', 'my-calendar' ); ?></h2>
			<div class="mc-inside">
			<?php
			echo wp_kses( mc_list_custom_templates( $key ), mc_kses_elements() );
			echo ( 'add-new' !== $key ) ? wp_kses_post( '<p><a class="button" href="' . esc_url( add_query_arg( 'mc_template', 'add-new', admin_url( 'admin.php?page=my-calendar-design' ) ) ) . '#my-calendar-templates">' . __( 'Add Custom Template', 'my-calendar' ) . '</a></p>' ) : '';
			?>
			</div>
		</div>
	</div>
	<div class="ui-sortable meta-box-sortables">
		<div class="mc-postbox">
			<h2 class="mc-flex">
			<?php
			esc_html_e( 'Template Tags', 'my-calendar' );
			mc_help_link( __( 'Template Tag Help', 'my-calendar' ), __( 'Template Tags', 'my-calendar' ), 'template-tags', 5 );
			?>
			</h2>

			<div class='mc_template_tags mc-inside'>
				<h3><?php esc_html_e( 'Event Tags', 'my-calendar' ); ?></h3>
				<dl>
					<dt><code>{title}</code></dt>
					<dd><?php esc_html_e( 'Title of the event.', 'my-calendar' ); ?></dd>

					<dt><code>{link_title}</code></dt>
					<dd><?php esc_html_e( 'Title of the event as a link if a URL is present, or the title alone if not.', 'my-calendar' ); ?></dd>

					<dt><code>{time}</code></dt>
					<dd><?php esc_html_e( 'Start time for the event.', 'my-calendar' ); ?></dd>

					<dt><code>{date}</code></dt>
					<dd><?php esc_html_e( 'Date on which the event begins.', 'my-calendar' ); ?></dd>

					<dt><code>{daterange}</code></dt>
					<dd><?php esc_html_e( 'Beginning date to end date; excludes end date if same as beginning.', 'my-calendar' ); ?></dd>

					<dt><code>{excerpt}</code></dt>
					<dd><?php esc_html_e( 'Short event description.', 'my-calendar' ); ?></dd>

					<dt><code>{description}</code></dt>
					<dd><?php esc_html_e( 'Description of the event.', 'my-calendar' ); ?></dd>

					<dt><code>{image}</code></dt>
					<dd><?php esc_html_e( 'Featured image with the event.', 'my-calendar' ); ?></dd>

					<dt><code>{link}</code></dt>
					<dd><?php esc_html_e( 'URL provided for the event.', 'my-calendar' ); ?></dd>

					<dt><code>{details}</code></dt>
					<dd><?php esc_html_e( 'Link to a page containing information about the event.', 'my-calendar' ); ?>
				</dl>

				<h3><?php esc_html_e( 'Location Tags', 'my-calendar' ); ?></h3>
				<dl>
					<dt><code>{location}</code></dt>
					<dd><?php esc_html_e( 'Name of the location of the event.', 'my-calendar' ); ?></dd>

					<dt><code>{street}</code></dt>
					<dd><?php esc_html_e( 'First line of the site address.', 'my-calendar' ); ?></dd>

					<dt><code>{street2}</code></dt>
					<dd><?php esc_html_e( 'Second line of the site address.', 'my-calendar' ); ?></dd>

					<dt><code>{city}</code></dt>
					<dd><?php esc_html_e( 'City', 'my-calendar' ); ?></dd>

					<dt><code>{state}</code></dt>
					<dd><?php esc_html_e( 'State', 'my-calendar' ); ?></dd>

					<dt><code>{postcode}</code></dt>
					<dd><?php esc_html_e( 'Postal Code', 'my-calendar' ); ?></dd>

					<dt><code>{country}</code></dt>
					<dd><?php esc_html_e( 'Country for the event location.', 'my-calendar' ); ?></dd>

					<dt><code>{sitelink}</code></dt>
					<dd><?php esc_html_e( 'Output the URL for the location.', 'my-calendar' ); ?></dd>

					<dt><code>{hcard}</code></dt>
					<dd><?php esc_html_e( 'HTML Formatted event address.', 'my-calendar' ); ?></dd>

					<dt><code>{link_map}</code></dt>
					<dd><?php esc_html_e( 'Link to Map to the event, if address information is available.', 'my-calendar' ); ?></dd>
				</dl>
			</div>
		</div>
	</div>
	<?php
	$templates = (array) mc_get_option( 'templates', array() );
	ksort( $templates );
	foreach ( $templates as $key => $template ) {
		if ( 'title' === $key || 'title_list' === $key || 'title_solo' === $key || 'title_card' === $key || 'link' === $key || 'label' === $key || 'rss' === $key ) {
			continue;
		}
		?>
	<div class="ui-sortable meta-box-sortables">
		<div class="mc-postbox">
				<h2>
		<?php
		// Translators: name of template being previewed.
		printf( __( 'Template Preview: %s', 'my-calendar' ), ucfirst( $key ) );
		?>
				</h2>
				<div class="template-preview mc-inside">
		<?php
		echo wp_kses_post( mc_admin_template_description( $key ) );
		$mc_id = mc_get_template_tag_preview( false, 'int' );
		if ( $mc_id ) {
			$view_url    = mc_get_details_link( $mc_id );
			$tag_preview = add_query_arg(
				array(
					'iframe'   => 'true',
					'showtags' => 'true',
					'template' => $key,
					'mc_id'    => $mc_id,
				),
				$view_url
			);
			?>
				<div class="mc-template-preview">
					<iframe class="mc-iframe" onload="resizeIframe(this)" title="<?php esc_attr_e( 'Event Template Preview', 'my-calendar' ); ?>" src="<?php echo esc_url( $tag_preview ); ?>" width="800" height="600"></iframe>
				</div>
			<?php
		}
		?>
			</div>
		</div>
	</div>
		<?php
	}
}

/**
 * Get template tags for use in previews.
 *
 * @param int|bool $mc_id Event occurrence id.
 * @param string   $return_type Type of data to return.
 *
 * @return array|int
 */
function mc_get_template_tag_preview( $mc_id, $return_type = 'array' ) {
	$event = ( 'array' === $return_type ) ? array() : 0;
	$data  = array();
	if ( ! isset( $_GET['mc-event'] ) && ! $mc_id ) {
		$args   = array(
			'before' => 1,
			'after'  => 1,
		);
		$events = mc_get_all_events( $args );
		if ( isset( $events[0] ) ) {
			$event = $events[0];
			$mc_id = $event->occur_id;
		}
	} else {
		$mc_id = ( $mc_id ) ? $mc_id : absint( $_GET['mc-event'] );
		$event = mc_get_event( $mc_id );
	}
	if ( $event ) {
		$data = mc_create_tags( $event );
	}

	return ( 'array' === $return_type ) ? $data : $mc_id;
}

/**
 * Display a specific template rendered.
 *
 * @param string   $template Template identifier.
 * @param int|bool $mc_id Event occurrence ID.
 *
 * @return string
 */
function mc_display_template_preview( $template, $mc_id = false ) {
	$data = mc_get_template_tag_preview( $mc_id );
	if ( empty( $data ) ) {
		return '';
	}
	$temp   = mc_get_template( $template );
	$output = mc_draw_template( $data, $temp );
	$output = html_entity_decode( $output );
	$class  = ( 'list' === $template ) ? 'list-event' : 'calendar-event';
	$class  = ( 'mini' === $template ) ? 'mini-event' : $class;
	$class  = ( 'card' === $template ) ? 'card-event' : $class;
	$class  = ( 'details' === $template ) ? 'single-event' : $class;

	return '<div class="mc-main ' . $class . ' ' . $template . '">' . $output . '</div>';
}

/**
 * Display a list of all available template tags.
 *
 * @param int|bool $mc_id Event occurence ID.
 * @param string   $render 'code' or 'html'.
 *
 * @return string
 */
function mc_display_template_tags( $mc_id = false, $render = 'code' ) {
	$event   = false;
	$data    = mc_get_template_tag_preview( $mc_id );
	$output  = '';
	$empty   = '';
	$oddball = '';

	// Translators: Event title being shown.
	$post_title = sprintf( __( 'Template tags for &ldquo;%1$s&rdquo;, on %2$s', 'my-calendar' ), $data['title'], $data['date'] );
	ksort( $data );
	if ( empty( $data ) ) {
		return __( 'Template tag index will display after you create an event.', 'my-calendar' );
	}
	// In preview, don't show all items.
	$skipping = array(
		'author_id',
		'cat_id',
		'category_id',
		'dateid',
		'duration',
		'event_span',
		'dtend',
		'dtstart',
		'group',
		'guid',
		'host_id',
		'ical_categories',
		'ical_category',
		'ical_desc',
		'ical_end',
		'ical_location',
		'ical_start',
		'ical_recur',
		'event_status',
		'id',
		'location_source',
		'post',
		'shortdesc',
		'repeats',
		'skip_holiday',
		'term',
		'description_raw',
		'description_stripped',
		'shortdesc_raw',
		'shortdesc_stripped',
		'details_ical',
		'ical_date_end',
		'ical_date_start',
		'ical_excerpt',
		'ical_location',
	);
	foreach ( $data as $key => $value ) {
		$uncommon = false;
		if ( in_array( $key, $skipping, true ) ) {
			if ( 'preview' === $render ) {
				continue;
			} else {
				$uncommon = true;
			}
		}
		$tag_output = ( 'code' === $render ) ? '<pre>' . esc_html( $value ) . '</pre>' : wp_kses_post( $value );
		if ( '' === $value ) {
			$empty .= '<section class="mc-template-card"><div class="mc-tag-' . $key . '"><code>{' . $key . '}</code></div>';
			$empty .= '<div class="mc-output-' . $key . '">' . $tag_output . '</div></section>';
		} elseif ( true === $uncommon ) {
			$oddball .= '<section class="mc-template-card"><div class="mc-tag mc-tag-' . $key . '"><code>{' . $key . '}</code></div>';
			$oddball .= '<div class="mc-output mc-output-' . $key . '">' . $tag_output . '</div></section>';
		} else {
			$output .= '<section class="mc-template-card"><div class="mc-tag mc-tag-' . $key . '"><code>{' . $key . '}</code></div>';
			$output .= '<div class="mc-output mc-output-' . $key . '">' . $tag_output . '</div></section>';
		}
	}
	$output_uncommon = '';
	if ( '' !== $oddball ) {
		$output_uncommon = '<h3>' . __( 'Uncommon Template Tags', 'my-calendar' ) . '</h3><div class="mc-template-cards">' . $oddball . '</div>';
	}

	return '<h3>' . $post_title . '</h3><div class="mc-template-cards">' . $output . '</div><h3>' . __( 'Template tags without values for this event', 'my-calendar' ) . '</h3><div class="mc-template-cards">' . $empty . '</div>' . $output_uncommon;
}

/**
 * Get stored data for custom template
 *
 * @param string $key Template unique key.
 *
 * @return string template
 */
function mc_get_custom_template( $key ) {
	$return = get_option( "mc_ctemplate_$key" );

	return $return;
}

/**
 * Check whether key exists in database
 *
 * @param string $key Template unique key.
 *
 * @return boolean
 */
function mc_key_exists( $key ) {
	// Keys are md5 hashed, so should always be 32 chars.
	if ( 32 !== strlen( $key ) ) {
		return false;
	}

	if ( 'missing' !== get_option( "mc_ctemplate_$key", 'missing' ) ) {
		return true;
	}

	return false;
}

/**
 * Create a new template from posted data. If this template key already exists, will update existing.
 *
 * @param string $template Full template.
 * @param array  $post POST data or array of relevant data.
 *
 * @return string $key
 */
function mc_create_template( $template, $post = array() ) {
	$key         = md5( $template );
	$description = strip_tags( $post['mc_template_key'] );
	update_option( "mc_template_desc_$key", $description );
	update_option( "mc_ctemplate_$key", $template );

	return $key;
}

/**
 * Update an existing template from posted data
 *
 * @param string $key template key.
 * @param string $template full template.
 *
 * @return string $key
 */
function mc_update_template( $key, $template ) {
	update_option( "mc_ctemplate_$key", $template );

	return $key;
}

/**
 * Get description of current key
 *
 * @param string $key Template unique key.
 *
 * @return string
 */
function mc_admin_template_description( $key ) {
	if ( 'add-new' === $key ) {
		return '';
	}

	$return = '';
	switch ( $key ) {
		case 'grid':
			$return = __( '<strong>Core Template:</strong> used in the details popup in the main calendar view.', 'my-calendar' );
			break;
		case 'details':
			$return = __( '<strong>Core Template:</strong> used on the single event view.', 'my-calendar' );
			break;
		case 'list':
			$return = __( '<strong>Core Template:</strong> used when viewing events in the main calendar list view.', 'my-calendar' );
			break;
		case 'mini':
			$return = __( '<strong>Core Template:</strong> used in popups for the mini calendar.', 'my-calendar' );
			break;
		case 'card':
			$return = __( '<strong>Core Template:</strong> used for event display in the Card view.', 'my-calendar' );
			break;
	}

	if ( ! mc_is_core_template( $key ) ) {
		$return = strip_tags( stripslashes( get_option( "mc_template_desc_$key" ) ) );
	}

	return wpautop( $return );
}

/**
 * List of core templates available
 *
 * @param string $current Currently visible.
 *
 * @return string
 */
function mc_list_core_templates( $current = '' ) {
	$check           = "<span class='dashicons dashicons-yes' aria-hidden='true'></span><span>" . __( 'Enabled', 'my-calendar' ) . '</span>';
	$uncheck         = "<span class='dashicons dashicons-no' aria-hidden='true'></span><span>" . __( 'Not Enabled', 'my-calendar' ) . '</span>';
	$switched        = ( isset( $_POST['mc_use_template'] ) ) ? true : false;
	$type            = ( isset( $_GET['mc_template'] ) ) ? $_GET['mc_template'] : '';
	$grid_enabled    = ( ( 'grid' === $type && $switched ) || mc_get_option( 'use_grid_template' ) === '1' ) ? $check : $uncheck;
	$list_enabled    = ( ( 'list' === $type && $switched ) || mc_get_option( 'use_list_template' ) === '1' ) ? $check : $uncheck;
	$mini_enabled    = ( ( 'mini' === $type && $switched ) || mc_get_option( 'use_mini_template' ) === '1' ) ? $check : $uncheck;
	$card_enabled    = ( ( 'card' === $type && $switched ) || mc_get_option( 'use_card_template' ) === '1' ) ? $check : $uncheck;
	$details_enabled = ( ( 'details' === $type && $switched ) || mc_get_option( 'use_details_template' ) === '1' ) ? $check : $uncheck;

	$list = "
	<table class='widefat'>
		<thead>
			<tr><th scope='col'>" . __( 'Template', 'my-calendar' ) . '</th><th scope="col">' . __( 'Status', 'my-calendar' ) . '</th><th scope="col">' . __( 'Description', 'my-calendar' ) . "</th>
		</thead>
		<tbody>
			<tr class='alternate'>
				<td><a " . ( ( 'grid' === $current ) ? ' aria-current="true"' : '' ) . " href='" . esc_url( add_query_arg( 'mc_template', 'grid', admin_url( 'admin.php?page=my-calendar-design' ) ) ) . "#my-calendar-templates'>grid</a></td><td>$grid_enabled</td><td>" . mc_admin_template_description( 'grid' ) . '</td>
			</tr>
			<tr>
				<td><a ' . ( ( 'list' === $current ) ? ' aria-current="true"' : '' ) . " href='" . esc_url( add_query_arg( 'mc_template', 'list', admin_url( 'admin.php?page=my-calendar-design' ) ) ) . "#my-calendar-templates'>list</a></td><td>$list_enabled</td><td>" . mc_admin_template_description( 'list' ) . "</td>
			</tr>
			<tr class='alternate'>
				<td><a " . ( ( 'mini' === $current ) ? ' aria-current="true"' : '' ) . " href='" . esc_url( add_query_arg( 'mc_template', 'mini', admin_url( 'admin.php?page=my-calendar-design' ) ) ) . "#my-calendar-templates'>mini</a></td><td>$mini_enabled</td><td>" . mc_admin_template_description( 'mini' ) . '</td>
			</tr>
			<tr>
				<td><a ' . ( ( 'card' === $current ) ? ' aria-current="true"' : '' ) . " href='" . esc_url( add_query_arg( 'mc_template', 'card', admin_url( 'admin.php?page=my-calendar-design' ) ) ) . "#my-calendar-templates'>card</a></td><td>$card_enabled</td><td>" . mc_admin_template_description( 'card' ) . '</td>
			</tr>
			<tr class="alternate">
				<td><a ' . ( ( 'details' === $current ) ? ' aria-current="true"' : '' ) . " href='" . esc_url( add_query_arg( 'mc_template', 'details', admin_url( 'admin.php?page=my-calendar-design' ) ) ) . "#my-calendar-templates'>details</a></td><td>$details_enabled</td><td>" . mc_admin_template_description( 'details' ) . '</td>
			</tr>
		</tbody>
	</table>';

	return $list;
}


/**
 * List of templates available
 *
 * @param string $current Currently visible template.
 *
 * @return string
 */
function mc_list_custom_templates( $current = '' ) {
	$header = "<table class='widefat'>
				<thead>
					<tr><th scope='col'>" . __( 'Template', 'my-calendar' ) . '</th><th scope="col">' . __( 'Description', 'my-calendar' ) . '</th>
				</thead>
				<tbody>';
	$body   = '';
	global $wpdb;
	$results = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . "options WHERE option_name LIKE '%mc_ctemplate_%'" );
	$class   = 'normal';
	foreach ( $results as $result ) {
		$key   = str_replace( 'mc_ctemplate_', '', $result->option_name );
		$desc  = mc_admin_template_description( $key );
		$class = ( 'alternate' === $class ) ? 'normal' : 'alternate';
		$curr  = ( $current === $key ) ? ' aria-current="true"' : '';
		$body .= "<tr class='$class'><td><a $curr href='" . esc_url( add_query_arg( 'mc_template', $key, admin_url( 'admin.php?page=my-calendar-design' ) ) ) . "#my-calendar-templates'>$key</a></td><td>$desc</td></tr>";
	}

	$list = $header . $body . '</tbody>
	</table>';

	return ( '' !== $body ) ? $list : '';
}

add_action(
	'admin_enqueue_scripts',
	function () {
		if ( ! function_exists( 'wp_enqueue_code_editor' ) ) {
			return;
		}

		if ( sanitize_title( __( 'My Calendar', 'my-calendar' ) ) . '_page_my-calendar-design' !== get_current_screen()->id ) {
			return;
		}

		if ( isset( $_GET['mc_template'] ) ) {
			// Enqueue code editor and settings for manipulating HTML.
			$settings = wp_enqueue_code_editor( array( 'type' => 'text/html' ) );

			// Bail if user disabled CodeMirror.
			if ( false === $settings ) {
				return;
			}

			wp_add_inline_script(
				'code-editor',
				sprintf(
					'jQuery( function() { wp.codeEditor.initialize( "mc_template", %s ); } );',
					wp_json_encode( $settings )
				)
			);
		}
	}
);
