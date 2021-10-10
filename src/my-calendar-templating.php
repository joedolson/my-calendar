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
 * Template editing page.
 */
function mc_templates_edit() {
	$templates = get_option( 'mc_templates' );
	$requery   = false;

	if ( ! empty( $_POST ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-calendar-nonce' ) ) {
			die( 'Security check failed' );
		}
		$requery = true;
	}

	if ( isset( $_POST['mc_template_key'] ) ) {
		$key = $_POST['mc_template_key'];
	} else {
		$key = ( isset( $_GET['mc_template'] ) ) ? $_GET['mc_template'] : 'grid';
	}

	if ( isset( $_POST['delete'] ) ) {
		delete_option( 'mc_ctemplate_' . $key );
		mc_show_notice( __( 'Custom template deleted', 'my-calendar' ) );
		$key = 'grid';
	} else {
		if ( mc_is_core_template( $key ) && isset( $_POST['add-new'] ) ) {
			mc_show_notice( __( 'Custom templates cannot have the same key as a core template', 'my-calendar' ) );
		} else {
			if ( mc_is_core_template( $key ) && isset( $_POST['mc_template'] ) ) {
				$template          = ( ! empty( $_POST['mc_template'] ) ) ? $_POST['mc_template'] : '';
				$templates[ $key ] = $template;
				update_option( 'mc_templates', $templates );
				update_option( 'mc_use_' . $key . '_template', ( empty( $_POST['mc_use_template'] ) ? 0 : 1 ) );
				// Translators: unique key for template.
				mc_show_notice( sprintf( __( '%s Template saved', 'my-calendar' ), ucfirst( $key ) ) );
			} elseif ( isset( $_POST['mc_template'] ) ) {
				$template = $_POST['mc_template'];
				if ( mc_key_exists( $key ) ) {
					$key = mc_update_template( $key, $template );
				} else {
					$key = mc_create_template( $template, $_POST );
				}
				mc_show_notice( __( 'Custom Template saved', 'my-calendar' ) );
			}
		}
	}

	// Re-fetch templates option after changes have been made.
	$templates           = ( $requery ) ? get_option( 'mc_templates' ) : $templates;
	$globals             = mc_globals();
	$mc_grid_template    = ( '' !== trim( $templates['grid'] ) ) ? $templates['grid'] : $globals['grid_template'];
	$mc_list_template    = ( '' !== trim( $templates['list'] ) ) ? $templates['list'] : $globals['list_template'];
	$mc_mini_template    = ( '' !== trim( $templates['mini'] ) ) ? $templates['mini'] : $globals['mini_template'];
	$mc_details_template = ( '' !== trim( $templates['details'] ) ) ? $templates['details'] : $globals['single_template'];

	if ( isset( $_POST['add-new'] ) && mc_is_core_template( $key ) ) {
		$template = stripslashes( $_POST['mc_template'] );
	} else {
		$template = ( mc_is_core_template( $key ) ) ? ${'mc_' . $key . '_template'} : mc_get_custom_template( $key );
		$template = stripslashes( $template );
	}
	$core = mc_template_description( $key );
	?>
	<div class="wrap my-calendar-admin">
		<h1 class="wp-heading-inline"><?php _e( 'My Calendar Templates', 'my-calendar' ); ?></h1>
		<a href="<?php echo add_query_arg( 'mc_template', 'add-new', admin_url( 'admin.php?page=my-calendar-templates' ) ); ?>" class="page-title-action"><?php _e( 'Add New', 'my-calendar' ); ?></a>
		<hr class="wp-header-end">
		<div class="postbox-container jcd-wide">
			<div class="metabox-holder">
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h2><?php _e( 'Core Templates', 'my-calendar' ); ?></h2>
						<div class="inside">
						<?php
						echo mc_list_core_templates();
						?>
						</div>
					</div>
				</div>
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h2><?php _e( 'Custom Templates', 'my-calendar' ); ?></h2>
						<div class="inside">
						<?php
						echo mc_list_custom_templates();
						echo '<p><a class="button" href="' . add_query_arg( 'mc_template', 'add-new', admin_url( 'admin.php?page=my-calendar-templates#mc-edit-template' ) ) . '">' . __( 'Add New Template', 'my-calendar' ) . '</a></p>';
						?>
						</div>
					</div>
				</div>
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox" id="mc-edit-template">
						<h2><?php _e( 'Edit Template', 'my-calendar' ); ?></h2>
						<div class="inside">
							<?php echo ( '' !== $core ) ? "<div class='template-description'>$core</div>" : ''; ?>
							<form method="post" action="<?php echo add_query_arg( 'mc_template', $key, admin_url( 'admin.php?page=my-calendar-templates' ) ); ?>">
							<?php
							if ( 'add-new' === $key ) {
								?>
								<div>
									<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
								</div>
								<p>
									<label for="mc_template_key"><?php _e( 'Template Description (required)', 'my-calendar' ); ?></label><br />
									<input type="text" class="widefat" name="mc_template_key" id="mc_template_key" value="" required />
								</p>
								<p>
									<label for="mc_template"><?php _e( 'Custom Template', 'my-calendar' ); ?></label><br/>
									<textarea id="mc_template" name="mc_template" class="template-editor widefat" rows="32" cols="76"></textarea>
								</p>

								<p>
									<input type="submit" name="save" class="button-primary" value="<?php _e( 'Add Template', 'my-calendar' ); ?>" />
								</p>
								<?php
							} else {
								?>
								<div>
									<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-calendar-nonce' ); ?>"/>
									<input type="hidden" name="mc_template_key" value="<?php echo esc_attr( $key ); ?>"/>
								</div>
								<?php if ( mc_is_core_template( $key ) ) { ?>
								<p>
									<input type="checkbox" id="mc_use_template" name="mc_use_template" value="1" <?php checked( get_option( 'mc_use_' . $key . '_template' ), '1' ); ?> /> <label for="mc_use_template"><?php _e( 'Use this template', 'my-calendar' ); ?></label>
								</p>
								<?php } ?>
								<p>
									<label for="mc_template">
									<?php
									// Translators: template type.
									printf( __( 'Custom Template (%s)', 'my-calendar' ), $key );
									?>
									</label><br/>
									<textarea id="mc_template" name="mc_template" class="template-editor widefat" rows="32" cols="76"><?php echo $template; ?></textarea>
								</p>
								<p>
									<input type="submit" name="save" class="button-primary" value="<?php _e( 'Update Template', 'my-calendar' ); ?>" />
								<?php if ( ! mc_is_core_template( $key ) ) { ?>
									<input type="submit" name="delete" class="button-secondary" value=<?php _e( 'Delete Template', 'my-calendar' ); ?>" />
								<?php } ?>
								</p>
							<?php } ?>
							</form>
							<p>
								<a href="<?php echo admin_url( 'admin.php?page=my-calendar-templates#templates' ); ?>"><?php _e( 'Templates Help', 'my-calendar' ); ?></a>
							</p>
						</div>
					</div>
				</div>
			</div>
			<div class="metabox-holder">
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h2><?php _e( 'Event Template Tags', 'my-calendar' ); ?></h2>

						<div class='mc_template_tags inside'>
							<p>
								<a href="<?php echo admin_url( 'admin.php?page=my-calendar-templates#templates' ); ?>"><?php _e( 'All Template Tags &raquo;', 'my-calendar' ); ?></a>
							</p>
							<dl>
								<dt><code>{title}</code></dt>
								<dd><?php _e( 'Title of the event.', 'my-calendar' ); ?></dd>

								<dt><code>{link_title}</code></dt>
								<dd><?php _e( 'Title of the event as a link if a URL is present, or the title alone if not.', 'my-calendar' ); ?></dd>

								<dt><code>{time}</code></dt>
								<dd><?php _e( 'Start time for the event.', 'my-calendar' ); ?></dd>

								<dt><code>{date}</code></dt>
								<dd><?php _e( 'Date on which the event begins.', 'my-calendar' ); ?></dd>

								<dt><code>{daterange}</code></dt>
								<dd><?php _e( 'Beginning date to end date; excludes end date if same as beginning.', 'my-calendar' ); ?></dd>

								<dt><code>{multidate}</code></dt>
								<dd><?php _e( 'Multi-day events: an unordered list of dates/times. Otherwise, beginning date/time.', 'my-calendar' ); ?></dd>

								<dt><code>{author}</code></dt>
								<dd><?php _e( 'Author who posted the event.', 'my-calendar' ); ?></dd>

								<dt><code>{host}</code></dt>
								<dd><?php _e( 'Name of the assigned host for the event.', 'my-calendar' ); ?></dd>

								<dt><code>{excerpt}</code></dt>
								<dd><?php _e( 'Short event description.', 'my-calendar' ); ?></dd>

								<dt><code>{description}</code></dt>
								<dd><?php _e( 'Description of the event.', 'my-calendar' ); ?></dd>

								<dt><code>{image}</code></dt>
								<dd><?php _e( 'Image associated with the event.', 'my-calendar' ); ?></dd>

								<dt><code>{link}</code></dt>
								<dd><?php _e( 'URL provided for the event.', 'my-calendar' ); ?></dd>

								<dt><code>{details}</code></dt>
								<dd><?php _e( 'Link to a page containing information about the event.', 'my-calendar' ); ?>
							</dl>

							<h3><?php _e( 'Location Template Tags', 'my-calendar' ); ?></h3>
							<dl>
								<dt><code>{location}</code></dt>
								<dd><?php _e( 'Name of the location of the event.', 'my-calendar' ); ?></dd>

								<dt><code>{street}</code></dt>
								<dd><?php _e( 'First line of the site address.', 'my-calendar' ); ?></dd>

								<dt><code>{street2}</code></dt>
								<dd><?php _e( 'Second line of the site address.', 'my-calendar' ); ?></dd>

								<dt><code>{city}</code></dt>
								<dd><?php _e( 'City', 'my-calendar' ); ?></dd>

								<dt><code>{state}</code></dt>
								<dd><?php _e( 'State', 'my-calendar' ); ?></dd>

								<dt><code>{postcode}</code></dt>
								<dd><?php _e( 'Postal Code', 'my-calendar' ); ?></dd>

								<dt><code>{region}</code></dt>
								<dd><?php _e( 'Custom region.', 'my-calendar' ); ?></dd>

								<dt><code>{country}</code></dt>
								<dd><?php _e( 'Country for the event location.', 'my-calendar' ); ?></dd>

								<dt><code>{sitelink}</code></dt>
								<dd><?php _e( 'Output the URL for the location.', 'my-calendar' ); ?></dd>

								<dt><code>{hcard}</code></dt>
								<dd><?php _e( 'Event address in <a href="http://microformats.org/wiki/hcard">hcard</a> format.', 'my-calendar' ); ?></dd>

								<dt><code>{link_map}</code></dt>
								<dd><?php _e( 'Link to Google Map to the event, if address information is available.', 'my-calendar' ); ?></dd>
							</dl>
							<h3><?php _e( 'Category Template Tags', 'my-calendar' ); ?></h3>

							<dl>
								<dt><code>{category}</code></dt>
								<dd><?php _e( 'Name of the category of the event.', 'my-calendar' ); ?></dd>

								<dt><code>{icon}</code></dt>
								<dd><?php _e( 'URL for the event\'s category icon.', 'my-calendar' ); ?></dd>

								<dt><code>{color}</code></dt>
								<dd><?php _e( 'Hex code for the event\'s category color.', 'my-calendar' ); ?></dd>

								<dt><code>{cat_id}</code></dt>
								<dd><?php _e( 'ID of the category of the event.', 'my-calendar' ); ?></dd>
							</dl>
						</div>
					</div>
				</div>
			</div>
			<?php
			$templates = get_option( 'mc_templates' );
			ksort( $templates );
			foreach ( $templates as $key => $template ) {
				if ( 'title' === $key || 'title_list' === $key || 'title_solo' === $key || 'link' === $key || 'label' === $key || 'rss' === $key ) {
					continue;
				}
				?>
			<div class="metabox-holder">
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h2>
				<?php
				// Translators: name of template being previewed.
				printf( __( 'Template Preview: %s', 'my-calendar' ), ucfirst( $key ) );
				?>
						</h2>
						<div class="template-preview inside">
				<?php
				echo mc_template_description( $key );
				$mc_id       = mc_get_template_tag_preview( false, 'int' );
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
							<iframe onload="resizeIframe(this)" title="<?php _e( 'Event Template Preview', 'my-calendar' ); ?>" src="<?php echo esc_url( $tag_preview ); ?>" width="800" height="600"></iframe>
						</div>
					</div>
				</div>
			</div>
		</div>
				<?php
			}
			?>
			<div id="templates" class="metabox-holder">
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h2><?php _e( 'Template Tag Previews', 'my-calendar' ); ?></h2>

						<div class='mc_template_tags inside'>
							<?php
							echo mc_display_template_tags();
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php
	mc_show_sidebar( '' );
	?>
	</div>
	<?php
}

/**
 * Get template tags for use in previews.
 *
 * @param int|bool $mc_id Event occurrence id.
 * @param string   $return Type of data to return.
 *
 * @return array
 */
function mc_get_template_tag_preview( $mc_id, $return = 'array' ) {
	if ( ! isset( $_GET['mc-event'] ) && ! $mc_id ) {
		$args   = array(
			'before' => 1,
			'after'  => 1,
			'today'  => 'yes',
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

	return ( 'array' === $return ) ? $data : $mc_id;
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
	$event  = false;
	$data   = mc_get_template_tag_preview( $mc_id );
	$temp   = mc_get_template( $template );
	$output = mc_draw_template( $data, $temp );
	$output = html_entity_decode( $output );
	$class  = ( 'list' === $template ) ? 'list-event' : 'calendar-event';
	$class  = ( 'mini' === $template ) ? 'mini-event' : $class;
	$class  = ( 'details' === $template ) ? 'single-event' : $class;

	return '<div class="mc-main ' . $template . '">' . $output . '</div>';
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
		'ical_description',
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
		$tag_output = ( 'code' === $render ) ? '<pre style="white-space:pre-wrap;line-break:anywhere">' . esc_html( $value ) . '</pre>' : $value;
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
	if ( '' !== $oddball ) {
		$output_uncommon = '<h3>' . __( 'Uncommon Template Tags', 'my-calendar' ) . '</h3><div class="mc-template-cards">' . $oddball . '</div>';
	}

	return '<h3>' . $post_title . '</h3><div class="mc-template-cards">' . $output . '</div><h3>' . __( 'Template tags without values for this event', 'my-calendar' ) . '</h3><div class="mc-template-cards">' . $empty . '</div>' . $output_uncommon;
}

/**
 * Check whether the current key refers to a core template
 *
 * @param string $key Template unique key.
 *
 * @return boolean
 */
function mc_is_core_template( $key ) {
	switch ( $key ) {
		case 'grid':
		case 'details':
		case 'list':
		case 'mini':
			return true;
			break;
		default:
			return false;
	}
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
function mc_template_description( $key ) {
	if ( 'add-new' === $key ) {
		return '';
	}

	$return = '';
	switch ( $key ) {
		case 'grid':
			$return = __( '<strong>Core Template:</strong> used in the details pop-up in the main calendar view.', 'my-calendar' );
			break;
		case 'details':
			$return = __( '<strong>Core Template:</strong> used on the single event view.', 'my-calendar' );
			break;
		case 'list':
			$return = __( '<strong>Core Template:</strong> used when viewing events in the main calendar list view.', 'my-calendar' );
			break;
		case 'mini':
			$return = __( '<strong>Core Template:</strong> used in pop-ups for the mini calendar.', 'my-calendar' );
			break;
	}

	if ( ! mc_is_core_template( $key ) ) {
		$return = strip_tags( stripslashes( get_option( "mc_template_desc_$key" ) ) );
	}

	return wpautop( $return );
}

/**
 * List of core templates available
 */
function mc_list_core_templates() {
	$check           = "<span class='dashicons dashicons-yes' aria-hidden='true'></span><span>" . __( 'Enabled', 'my-calendar' ) . '</span>';
	$uncheck         = "<span class='dashicons dashicons-no' aria-hidden='true'></span><span>" . __( 'Not Enabled', 'my-calendar' ) . '</span>';
	$switched        = ( isset( $_POST['mc_use_template'] ) ) ? true : false;
	$type            = ( isset( $_GET['mc_template'] ) ) ? $_GET['mc_template'] : '';
	$grid_enabled    = ( ( 'grid' === $type && $switched ) || get_option( 'mc_use_grid_template' ) === '1' ) ? $check : $uncheck;
	$list_enabled    = ( ( 'list' === $type && $switched ) || get_option( 'mc_use_list_template' ) === '1' ) ? $check : $uncheck;
	$mini_enabled    = ( ( 'mini' === $type && $switched ) || get_option( 'mc_use_mini_template' ) === '1' ) ? $check : $uncheck;
	$details_enabled = ( ( 'details' === $type && $switched ) || get_option( 'mc_use_details_template' ) === '1' ) ? $check : $uncheck;

	$list = "
	<table class='widefat'>
		<thead>
			<tr><th scope='col'>" . __( 'Template', 'my-calendar' ) . '</th><th scope="col">' . __( 'Status', 'my-calendar' ) . '</th><th scope="col">' . __( 'Description', 'my-calendar' ) . "</th>
		</thead>
		<tbody>
			<tr class='alternate'><td><a href='" . add_query_arg( 'mc_template', 'grid', admin_url( 'admin.php?page=my-calendar-templates' ) ) . "'>grid</a></td><td>$grid_enabled</td><td>" . mc_template_description( 'grid' ) . "</td>
			</tr>
			<tr><td><a href='" . add_query_arg( 'mc_template', 'list', admin_url( 'admin.php?page=my-calendar-templates' ) ) . "'>list</a></td><td>$list_enabled</td><td>" . mc_template_description( 'list' ) . "</td>
			</tr>
			<tr class='alternate'><td><a href='" . add_query_arg( 'mc_template', 'mini', admin_url( 'admin.php?page=my-calendar-templates' ) ) . "'>mini</a></td><td>$mini_enabled</td><td>" . mc_template_description( 'mini' ) . "</td>
			</tr>
			<tr><td><a href='" . add_query_arg( 'mc_template', 'details', admin_url( 'admin.php?page=my-calendar-templates' ) ) . "'>details</a></td><td>$details_enabled</td><td>" . mc_template_description( 'details' ) . '</td>
			</tr>
		</tbody>
	</table>';

	return $list;
}


/**
 * List of templates available
 */
function mc_list_custom_templates() {
	$list = "<table class='widefat'>
				<thead>
					<tr><th scope='col'>" . __( 'Template', 'my-calendar' ) . '</th><th scope="col">' . __( 'Description', 'my-calendar' ) . '</th>
				</thead>
				<tbody>';
	global $wpdb;
	$results = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . "options WHERE option_name LIKE '%mc_ctemplate_%'" );
	$class   = 'normal';
	foreach ( $results as $result ) {
		$key   = str_replace( 'mc_ctemplate_', '', $result->option_name );
		$desc  = mc_template_description( $key );
		$class = ( 'alternate' === $class ) ? 'normal' : 'alternate';
		$list .= "<tr class='$class'><td><a href='" . add_query_arg( 'mc_template', $key, admin_url( 'admin.php?page=my-calendar-templates' ) ) . "'>$key</a></td><td>$desc</td></tr>";
	}

	$list .= '</tbody>
	</table>';

	return $list;
}

add_action(
	'admin_enqueue_scripts',
	function() {
		if ( ! function_exists( 'wp_enqueue_code_editor' ) ) {
			return;
		}

		if ( sanitize_title( __( 'My Calendar', 'my-calendar' ) ) . '_page_my-calendar-templates' !== get_current_screen()->id ) {
			return;
		}

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
);
