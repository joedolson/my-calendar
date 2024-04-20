<?php
/**
 * Define My Calendar post types.
 *
 * @category Posts
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom fields to permalinks settings page.
 */
function mc_load_permalinks() {
	if ( isset( $_POST['mc_cpt_base'] ) ) {
		mc_update_option( 'cpt_base', sanitize_key( $_POST['mc_cpt_base'] ) );
	}
	$opts = array( 'label_for' => 'mc_cpt_base' );
	// Add a settings field to the permalink page.
	add_settings_field( 'mc_cpt_base', __( 'My Calendar Events base', 'my-calendar' ), 'mc_field_callback', 'permalink', 'optional', $opts );

	if ( isset( $_POST['mc_location_cpt_base'] ) ) {
		mc_update_option( 'location_cpt_base', sanitize_key( $_POST['mc_location_cpt_base'] ) );
	}
	$opts = array( 'label_for' => 'mc_location_cpt_base' );
	// Add a settings field to the permalink page.
	add_settings_field( 'mc_location_cpt_base', __( 'My Calendar Locations base', 'my-calendar' ), 'mc_location_field_callback', 'permalink', 'optional', $opts );

	if ( function_exists( 'mcs_submissions' ) && 'true' === get_option( 'mcs_custom_hosts' ) ) {
		if ( isset( $_POST['mc_hosts_cpt_base'] ) ) {
			mc_update_option( 'hosts_cpt_base', sanitize_key( $_POST['mc_hosts_cpt_base'] ) );
		}
		$opts = array( 'label_for' => 'mc_cpt_base' );
		// Add a settings field to the permalink page.
		add_settings_field( 'mc_hosts_cpt_base', __( 'My Calendar Hosts base', 'my-calendar' ), 'mc_field_callback', 'permalink', 'optional', $opts );
	}
}
add_action( 'load-options-permalink.php', 'mc_load_permalinks' );

/**
 * Custom field callback for permalinks settings
 */
function mc_field_callback() {
	$value = ( '' !== mc_get_option( 'cpt_base' ) ) ? mc_get_option( 'cpt_base' ) : 'mc-events';
	echo '<input type="text" value="' . esc_attr( $value ) . '" name="mc_cpt_base" id="mc_cpt_base" class="regular-text" placeholder="mc-events" />';
}

/**
 * Custom field callback for permalinks settings
 */
function mc_location_field_callback() {
	$value = ( '' !== mc_get_option( 'location_cpt_base' ) ) ? mc_get_option( 'location_cpt_base' ) : 'mc-locations';
	echo '<input type="text" value="' . esc_attr( $value ) . '" name="mc_location_cpt_base" id="mc_location_cpt_base" class="regular-text" placeholder="mc-locations" />';
}

/**
 * Generate arguments for My Calendar post types.
 */
function mc_post_type() {
	$arguments = array(
		'public'              => true,
		'publicly_queryable'  => true,
		/**
		 * Should My Calendar post types be excluded from search. Default false.
		 * Allowing the event post type to be searchable will not provide a true event search, especially with respect to recurring events.
		 * It will not search recurring events by date, only the post content from each event. See https://github.com/joedolson/my-calendar/issues/23.
		 *
		 * @hook mc_event_exclude_from_search
		 *
		 * @param {bool} $show True to exclude from search.
		 *
		 * @return {bool}
		 */
		'exclude_from_search' => apply_filters( 'mc_event_exclude_from_search', true ),
		'show_ui'             => true,
		'can_export'          => false,
		/**
		 * Should My Calendar post types be shown in admin menus. Default false.
		 *
		 * @hook mc_show_custom_posts_in_menu
		 *
		 * @param {bool} $show True to show in menus.
		 *
		 * @return {bool}
		 */
		'show_in_menu'        => apply_filters( 'mc_show_custom_posts_in_menu', false ),
		'menu_icon'           => null,
		'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields' ),
	);

	$loc_arguments             = $arguments;
	$loc_arguments['supports'] = array( 'title', 'custom-fields', 'thumbnail' );
	/**
	 * Should location posts be excluded from search? Default true.
	 *
	 * @hook mc_location_exclude_from_search
	 *
	 * @param {bool} $exclude True to exclude.
	 *
	 * @return {bool}
	 */
	$loc_arguments['exclude_from_search'] = apply_filters( 'mc_location_exclude_from_search', true );

	$host_arguments                 = $arguments;
	$host_arguments['show_in_menu'] = true;
	$host_arguments['menu_icon']    = 'dashicons-superhero';

	$types = array(
		'mc-events'    => array(
			__( 'event', 'my-calendar' ),
			__( 'events', 'my-calendar' ),
			__( 'Event', 'my-calendar' ),
			__( 'Events', 'my-calendar' ),
			$arguments,
		),
		'mc-locations' => array(
			__( 'location', 'my-calendar' ),
			__( 'locations', 'my-calendar' ),
			__( 'Location', 'my-calendar' ),
			__( 'Locations', 'my-calendar' ),
			$loc_arguments,
		),
	);
	if ( function_exists( 'mcs_submissions' ) && 'true' === get_option( 'mcs_custom_hosts' ) ) {
		$types['mc-hosts'] = array(
			__( 'event host', 'my-calendar' ),
			__( 'event hosts', 'my-calendar' ),
			__( 'Event Host', 'my-calendar' ),
			__( 'Event Hosts', 'my-calendar' ),
			$host_arguments,
		);
	}

	return $types;
}

/**
 * Labels for My Calendar post types.
 *
 * @param string $type  Post type key.
 * @param array  $value Array of post type name variations.
 *
 * @return array
 */
function mc_post_type_labels( $type, $value ) {
	$labels = array();
	switch ( $type ) {
		case 'mc-events':
			$labels = array(
				'name'               => $value[3],
				'singular_name'      => $value[2],
				'add_new'            => __( 'Add New Event', 'my-calendar' ),
				'add_new_item'       => __( 'Create New Event', 'my-calendar' ),
				'edit_item'          => __( 'Modify Event', 'my-calendar' ),
				'new_item'           => __( 'New Event', 'my-calendar' ),
				'view_item'          => __( 'View Event', 'my-calendar' ),
				'search_items'       => __( 'Search Events', 'my-calendar' ),
				'not_found'          => __( 'No Event found', 'my-calendar' ),
				'not_found_in_trash' => __( 'No Events found in Trash', 'my-calendar' ),
				'parent_item_colon'  => '',
			);
			break;
		case 'mc-locations':
			$labels = array(
				'name'               => $value[3],
				'singular_name'      => $value[2],
				'add_new'            => __( 'Add New Location', 'my-calendar' ),
				'add_new_item'       => __( 'Create New Location', 'my-calendar' ),
				'edit_item'          => __( 'Modify Location', 'my-calendar' ),
				'new_item'           => __( 'New Location', 'my-calendar' ),
				'view_item'          => __( 'View Location', 'my-calendar' ),
				'search_items'       => __( 'Search Locations', 'my-calendar' ),
				'not_found'          => __( 'No Location found', 'my-calendar' ),
				'not_found_in_trash' => __( 'No Locations found in Trash', 'my-calendar' ),
				'parent_item_colon'  => '',
			);
			break;
		case 'mc-hosts':
			$labels = array(
				'name'               => $value[3],
				'singular_name'      => $value[2],
				'add_new'            => __( 'Add New Host', 'my-calendar' ),
				'add_new_item'       => __( 'Create New Host', 'my-calendar' ),
				'edit_item'          => __( 'Modify Host', 'my-calendar' ),
				'new_item'           => __( 'New Host', 'my-calendar' ),
				'view_item'          => __( 'View Host', 'my-calendar' ),
				'search_items'       => __( 'Search Hosts', 'my-calendar' ),
				'not_found'          => __( 'No Host found', 'my-calendar' ),
				'not_found_in_trash' => __( 'No Hosts found in Trash', 'my-calendar' ),
				'parent_item_colon'  => '',
			);
			break;
	}

	return $labels;
}

/**
 * Register custom post types for events
 */
function mc_posttypes() {
	$types   = mc_post_type();
	$enabled = array( 'mc-events', 'mc-locations' );
	if ( function_exists( 'mcs_submissions' ) && 'true' === get_option( 'mcs_custom_hosts' ) ) {
		$enabled[] = 'mc-hosts';
	}
	foreach ( $enabled as $key ) {
		$value  =& $types[ $key ];
		$labels = mc_post_type_labels( $key, $value );
		$raw    = $value[4];
		$args   = array(
			'labels'              => $labels,
			'public'              => $raw['public'],
			'publicly_queryable'  => $raw['publicly_queryable'],
			'exclude_from_search' => $raw['exclude_from_search'],
			'show_ui'             => $raw['show_ui'],
			'show_in_menu'        => $raw['show_in_menu'],
			'menu_icon'           => ( null === $raw['menu_icon'] ) ? plugins_url( 'images', __FILE__ ) . '/icon.png' : $raw['menu_icon'],
			'query_var'           => true,
			'can_export'          => $raw['can_export'],
			'rewrite'             => array(
				'with_front' => false,
				/**
				 * Filter default calendar post type slugs. Default 'mc-events' for events and 'mc-locations' for locations.
				 *
				 * @hook mc_event_slug
				 *
				 * @param {string} $key Slug.
				 *
				 * @return {string}
				 */
				'slug'       => apply_filters( 'mc_event_slug', $key ),
				/**
				 * Enable feeds for My Calendar post types.
				 *
				 * @hook mc_has_feeds
				 *
				 * @param {bool}   $enabled Default false.
				 * @param {string} $key Post type name.
				 *
				 * @return {bool}
				 */
				'feeds'      => apply_filters( 'mc_has_feeds', false, $key ),
			),
			'hierarchical'        => false,
			'menu_position'       => 20,
			'supports'            => $raw['supports'],
		);
		register_post_type( $key, $args );
	}
}

/**
 * Replace the slug with saved option.
 *
 * @param string $slug Base post type name.
 *
 * @return string New permalink base.
 */
function mc_filter_posttype_slug( $slug ) {
	if ( 'mc-events' === $slug ) {
		$slug = ( '' !== mc_get_option( 'cpt_base' ) ) ? mc_get_option( 'cpt_base' ) : $slug;
	}
	if ( 'mc-locations' === $slug ) {
		$slug = ( '' !== mc_get_option( 'location_cpt_base' ) ) ? mc_get_option( 'location_cpt_base' ) : $slug;
	}
	if ( 'mc-hosts' === $slug ) {
		$slug = ( '' !== mc_get_option( 'hosts_cpt_base' ) ) ? mc_get_option( 'hosts_cpt_base' ) : $slug;
	}

	return $slug;
}
add_filter( 'mc_event_slug', 'mc_filter_posttype_slug' );

add_filter( 'the_posts', 'mc_close_comments' );
/**
 * Most people don't want comments open on events. This will automatically close them.
 *
 * @param array $posts Array of WP Post objects.
 *
 * @return array $posts
 */
function mc_close_comments( $posts ) {
	if ( is_admin() || ! is_single() || empty( $posts ) ) {
		return $posts;
	}

	if ( 'mc-events' === get_post_type( $posts[0]->ID ) ) {
		/**
		 * Filter whether event posts should automatically close comments. Default 'true'.
		 *
		 * @hook mc_autoclose_comments
		 *
		 * @param {bool} $close 'true' to close comments.
		 *
		 * @return {bool}
		 */
		if ( apply_filters( 'mc_autoclose_comments', true ) && 'closed' !== $posts[0]->comment_status ) {
			$posts[0]->comment_status = 'closed';
			$posts[0]->ping_status    = 'closed';
			wp_update_post( $posts[0] );
		}
	}

	return $posts;
}

add_filter( 'default_content', 'mc_posttypes_defaults', 10, 2 );
/**
 * By default, disable comments on event posts on save
 *
 * @param string $post_content unused.
 * @param object $post WP Post object.
 *
 * @return string $post_content;
 */
function mc_posttypes_defaults( $post_content, $post ) {
	if ( $post->post_type ) {
		switch ( $post->post_type ) {
			case 'mc-events':
				$post->comment_status = 'closed';
				break;
		}
	}

	return $post_content;
}

/**
 * Register taxonomies on My Calendar custom post types
 */
function mc_taxonomies() {
	$enabled = array( 'mc-events' );
	foreach ( $enabled as $key ) {
		/**
		 * Filter event category taxonomy slug. Default 'mc-event-category'.
		 *
		 * @hook mc_event_category_slug
		 *
		 * @param {string} $slug Default slug.
		 *
		 * @return {string}
		 */
		$slug = apply_filters( 'mc_event_category_slug', 'mc-event-category' );
		register_taxonomy(
			'mc-event-category',
			// Internal name = machine-readable taxonomy name.
			array( $key ),
			array(
				'hierarchical' => true,
				'label'        => __( 'Event Categories', 'my-calendar' ),
				'query_var'    => true,
				'rewrite'      => array( 'slug' => $slug ),
			)
		);
	}
}

/**
 * Custom post type strings
 *
 * @param array $messages default text.
 *
 * @return array Modified messages array.
 */
function mc_posttypes_messages( $messages ) {
	global $post, $post_ID;
	$enabled = array( 'mc-events', 'mc-locations' );
	if ( function_exists( 'mcs_submissions' ) && 'true' === get_option( 'mcs_custom_hosts' ) ) {
		$enabled[] = 'mc-hosts';
	}
	foreach ( $enabled as $key ) {
		if ( 'mc-events' === $key ) {
			$messages[ $key ] = array(
				0  => '', // Unused. Messages start at index 1.
				// Translators: URL to view event.
				1  => sprintf( __( 'Event updated. <a href="%s">View Event</a>', 'my-calendar' ), esc_url( get_permalink( $post_ID ) ) ),
				2  => __( 'Custom field updated.', 'my-calendar' ),
				3  => __( 'Custom field deleted.', 'my-calendar' ),
				4  => __( 'Event updated.', 'my-calendar' ),
				// Translators: %s: date and time of the revision.
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Event restored to revision from %s', 'my-calendar' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				// Translators: URL to view event.
				6  => sprintf( __( 'Event published. <a href="%s">View event</a>', 'my-calendar' ), esc_url( get_permalink( $post_ID ) ) ),
				7  => sprintf( __( 'Event saved.', 'my-calendar' ) ),
				// Translators: URL to preview event.
				8  => sprintf( __( 'Event submitted. <a target="_blank" href="%s">Preview Event</a>', 'my-calendar' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
				// Translators: Date event scheduled to be published, URL to preview event.
				9  => sprintf( __( 'Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>', 'my-calendar' ), date_i18n( __( 'M j, Y @ G:i', 'my-calendar' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
				// Translators: URL to preview event.
				10 => sprintf( __( 'Event draft updated. <a target="_blank" href="%s">Preview Event</a>', 'my-calendar' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			);
		}
		if ( 'mc-locations' === $key ) {
			$messages[ $key ] = array(
				0  => '', // Unused. Messages start at index 1.
				// Translators: URL to view event.
				1  => sprintf( __( 'Location updated. <a href="%s">View Location</a>', 'my-calendar' ), esc_url( get_permalink( $post_ID ) ) ),
				2  => __( 'Custom field updated.', 'my-calendar' ),
				3  => __( 'Custom field deleted.', 'my-calendar' ),
				4  => __( 'Location updated.', 'my-calendar' ),
				// Translators: %s: date and time of the revision.
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Location restored to revision from %s', 'my-calendar' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				// Translators: URL to view event.
				6  => sprintf( __( 'Location published. <a href="%s">View event</a>', 'my-calendar' ), esc_url( get_permalink( $post_ID ) ) ),
				7  => sprintf( __( 'Location saved.', 'my-calendar' ) ),
				// Translators: URL to preview event.
				8  => sprintf( __( 'Location submitted. <a target="_blank" href="%s">Preview Location</a>', 'my-calendar' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
				// Translators: Date event scheduled to be published, URL to preview event.
				9  => sprintf( __( 'Location scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>', 'my-calendar' ), date_i18n( __( 'M j, Y @ G:i', 'my-calendar' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
				// Translators: URL to preview event.
				10 => sprintf( __( 'Location draft updated. <a target="_blank" href="%s">Preview Location</a>', 'my-calendar' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			);
		}
		if ( 'mc-hosts' === $key ) {
			$messages[ $key ] = array(
				0  => '', // Unused. Messages start at index 1.
				// Translators: URL to view event.
				1  => sprintf( __( 'Host updated. <a href="%s">View Host</a>', 'my-calendar' ), esc_url( get_permalink( $post_ID ) ) ),
				2  => __( 'Custom field updated.', 'my-calendar' ),
				3  => __( 'Custom field deleted.', 'my-calendar' ),
				4  => __( 'Host updated.', 'my-calendar' ),
				// Translators: %s: date and time of the revision.
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Host restored to revision from %s', 'my-calendar' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				// Translators: URL to view event.
				6  => sprintf( __( 'Host published. <a href="%s">View event</a>', 'my-calendar' ), esc_url( get_permalink( $post_ID ) ) ),
				7  => sprintf( __( 'Host saved.', 'my-calendar' ) ),
				// Translators: URL to preview event.
				8  => sprintf( __( 'Host submitted. <a target="_blank" href="%s">Preview host</a>', 'my-calendar' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
				// Translators: Date event scheduled to be published, URL to preview event.
				9  => sprintf( __( 'Host scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>', 'my-calendar' ), date_i18n( __( 'M j, Y @ G:i', 'my-calendar' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
				// Translators: URL to preview event.
				10 => sprintf( __( 'Host draft updated. <a target="_blank" href="%s">Preview Host</a>', 'my-calendar' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			);
		}
	}

	return $messages;
}

/**
 * Pass edits to event description or excerpt back to event if edited in editor.
 *
 * @param int    $post_id Post ID.
 * @param object $post Post object.
 *
 * @return bool|int
 */
function mc_save_post( $post_id, $post ) {
	if ( empty( $_POST ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || isset( $_POST['_inline_edit'] ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ! ( 'mc-events' === get_post_type( $post_id ) ) ) {
		return $post_id;
	}
	// Copy post content to event.
	$event_id = get_post_meta( $post_id, '_mc_event_id', true );
	mc_update_data( $event_id, 'event_desc', $post->post_content, '%s' );
	mc_update_data( $event_id, 'event_short', $post->post_excerpt, '%s' );

	return $post_id;
}
add_action( 'wp_after_insert_post', 'mc_save_post', 10, 2 );
