<?php
/**
 * My Calendar AJAX actions. Miscellaneous tasks run in the admin via AJAX actions.
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

add_action( 'wp_ajax_mc_core_autocomplete_search_pages', 'mc_core_autocomplete_search_pages' );
/**
 * Add post lookup for assigning My Calendar main page
 */
function mc_core_autocomplete_search_pages() {
	if ( isset( $_REQUEST['action'] ) && 'mc_core_autocomplete_search_pages' === $_REQUEST['action'] ) {
		$security = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $security, 'mc-search-pages' ) ) {
			wp_send_json(
				array(
					'success'  => 0,
					'response' => array( 'error' => 'Invalid security value.' ),
				)
			);
		}
		$query    = sanitize_text_field( $_REQUEST['data'] );
		$args     = array(
			's'         => $query,
			'post_type' => 'any',
			'orderby'   => 'relevance',
		);
		$posts    = get_posts( $args );
		$response = array();
		foreach ( $posts as $post ) {
			$response[] = array(
				'post_id'    => absint( $post->ID ),
				'post_title' => esc_html( html_entity_decode( strip_tags( $post->post_title ) ) ),
			);
		}
		wp_send_json(
			array(
				'success'  => 1,
				'response' => $response,
			)
		);
	}
}

add_action( 'wp_ajax_mc_core_autocomplete_search_icons', 'mc_core_autocomplete_search_icons' );
/**
 * Add SVG icon lookup for category pages.
 */
function mc_core_autocomplete_search_icons() {
	if ( isset( $_REQUEST['action'] ) && 'mc_core_autocomplete_search_icons' === $_REQUEST['action'] ) {
		$security = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $security, 'mc-search-icons' ) ) {
			wp_send_json(
				array(
					'success'  => 0,
					'response' => array( 'error' => 'Invalid security value.' ),
				)
			);
		}

		$query = sanitize_text_field( $_REQUEST['data'] );
		$dir   = plugin_dir_path( __FILE__ );
		if ( mc_is_custom_icon() ) {
			$is_custom = true;
			$directory = trailingslashit( str_replace( '/my-calendar', '', $dir ) ) . 'my-calendar-custom/icons/';
			$iconlist  = mc_directory_list( $directory );
		} else {
			$is_custom = false;
			$directory = trailingslashit( dirname( __FILE__ ) ) . 'images/icons/';
			$iconlist  = mc_directory_list( $directory );
		}
		$results  = array_filter(
			$iconlist,
			function( $el ) use ( $query ) {
				return ( false !== stripos( $el, $query ) );
			}
		);
		$response = array();
		foreach ( $results as $result ) {
			$response[] = array(
				'filename' => esc_attr( $result ),
				'svg'      => mc_get_img( $result, $is_custom ),
			);
		}
		wp_send_json(
			array(
				'success'  => 1,
				'response' => $response,
			)
		);
	}
}

add_action( 'wp_ajax_add_category', 'mc_ajax_add_category' );
/**
 * Delete a single occurrence of an event from the event manager.
 */
function mc_ajax_add_category() {
	if ( ! check_ajax_referer( 'mc-add-category-nonce', 'security', false ) ) {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => __( 'Invalid Security Check', 'my-calendar' ),
			)
		);
	}

	if ( current_user_can( 'mc_edit_cats' ) ) {
		global $wpdb;
		$category_name = sanitize_text_field( $_REQUEST['category_name'] );
		if ( '' === trim( $category_name ) ) {
			wp_send_json(
				array(
					'success'  => 0,
					'response' => esc_html__( 'Empty category name.', 'my-calendar' ),
				)
			);
		}
		$category_id = mc_create_category(
			array(
				'category_name'  => $category_name,
				'category_color' => '',
				'category_icon'  => '',
			)
		);

		if ( $category_id ) {
			wp_send_json(
				array(
					'success'     => 1,
					'response'    => esc_html__( 'New Category Created.', 'my-calendar' ),
					'category_id' => $category_id,
				)
			);
		} else {
			wp_send_json(
				array(
					'success'     => 0,
					'response'    => esc_html__( 'Category not created.', 'my-calendar' ),
					'category_id' => $category_id,
				)
			);
		}
	} else {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => esc_html__( 'You are not authorized to perform this action', 'my-calendar' ),
			)
		);
	}
}

add_action( 'wp_ajax_display_recurrence', 'mc_ajax_display_recurrence' );
/**
 * Display the recurring settings in human-readable format.
 */
function mc_ajax_display_recurrence() {
	if ( ! check_ajax_referer( 'mc-recurrence-nonce', 'security', false ) ) {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => __( 'Invalid Security Check', 'my-calendar' ),
			)
		);
	}

	$recur  = sanitize_text_field( $_REQUEST['recur'] );
	$every  = (int) $_REQUEST['every'];
	$until  = sanitize_text_field( $_REQUEST['until'] );
	$args   = array(
		'recur' => $recur,
		'every' => $every,
		'until' => $until,
	);
	$output = mc_recur_string( false, $args );

	wp_send_json(
		array(
			'success'  => 1,
			'args'     => $args,
			'response' => $output,
		)
	);
}

add_action( 'wp_ajax_delete_occurrence', 'mc_ajax_delete_occurrence' );
/**
 * Delete a single occurrence of an event from the event manager.
 */
function mc_ajax_delete_occurrence() {
	if ( ! check_ajax_referer( 'mc-delete-nonce', 'security', false ) ) {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => __( 'Invalid Security Check', 'my-calendar' ),
			)
		);
	}

	if ( current_user_can( 'mc_manage_events' ) ) {
		global $wpdb;
		$occur_id = (int) $_REQUEST['occur_id'];
		$event_id = (int) $_REQUEST['event_id'];
		$begin    = sanitize_text_field( $_REQUEST['occur_begin'] );
		$end      = sanitize_text_field( $_REQUEST['occur_end'] );
		$delete   = 'DELETE FROM `' . my_calendar_event_table() . '` WHERE occur_id = %d';
		$result   = $wpdb->query( $wpdb->prepare( $delete, $occur_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$event_post  = mc_get_event_post( $event_id );
		$instances   = get_post_meta( $event_post, '_mc_deleted_instances', true );
		$instances   = ( ! is_array( $instances ) ) ? array() : $instances;
		$instances[] = array(
			'occur_event_id' => $event_id,
			'occur_begin'    => $begin,
			'occur_end'      => $end,
		);
		update_post_meta( $event_post, '_mc_deleted_instances', $instances );

		if ( $result ) {
			wp_send_json(
				array(
					'success'  => 1,
					'response' => esc_html__( 'Event instance has been deleted.', 'my-calendar' ),
				)
			);
		} else {
			wp_send_json(
				array(
					'success'  => 0,
					'response' => esc_html__( 'Event instance was not deleted.', 'my-calendar' ),
				)
			);
		}
	} else {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => esc_html__( 'You are not authorized to perform this action', 'my-calendar' ),
			)
		);
	}
}

add_action( 'wp_ajax_add_date', 'mc_ajax_add_date' );
/**
 * Add a single additional date for an event from the event manager.
 */
function mc_ajax_add_date() {
	if ( ! check_ajax_referer( 'mc-delete-nonce', 'security', false ) ) {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => __( 'Invalid Security Check', 'my-calendar' ),
			)
		);
	}
	if ( current_user_can( 'mc_manage_events' ) ) {
		global $wpdb;
		$event_id = (int) $_REQUEST['event_id'];

		if ( 0 === $event_id ) {
			wp_send_json(
				array(
					'success'  => 0,
					'response' => __( 'No event ID in that request.', 'my-calendar' ),
				)
			);
		}

		$event_date    = sanitize_text_field( $_REQUEST['event_date'] );
		$event_end     = isset( $_REQUEST['event_end'] ) ? sanitize_text_field( $_REQUEST['event_end'] ) : $event_date;
		$event_time    = sanitize_text_field( $_REQUEST['event_time'] );
		$event_endtime = isset( $_REQUEST['event_endtime'] ) ? sanitize_text_field( $_REQUEST['event_endtime'] ) : '';
		$group_id      = (int) $_REQUEST['group_id'];

		// event end can not be earlier than event start.
		if ( ! $event_end || strtotime( $event_end ) < strtotime( $event_date ) ) {
			$event_end = $event_date;
		}

		$begin = strtotime( $event_date . ' ' . $event_time );
		$end   = ( '' !== $event_endtime ) ? strtotime( $event_end . ' ' . $event_endtime ) : strtotime( $event_end . ' ' . $event_time ) + HOUR_IN_SECONDS;

		$format      = array( '%d', '%s', '%s', '%d' );
		$data        = array(
			'occur_event_id' => $event_id,
			'occur_begin'    => mc_date( 'Y-m-d  H:i:s', $begin, false ),
			'occur_end'      => mc_date( 'Y-m-d  H:i:s', $end, false ),
			'occur_group_id' => $group_id,
		);
		$result      = $wpdb->insert( my_calendar_event_table(), $data, $format );
		$id          = $wpdb->insert_id;
		$event_post  = mc_get_event_post( $event_id );
		$instances   = get_post_meta( $event_post, '_mc_custom_instances', true );
		$instances   = ( ! is_array( $instances ) ) ? array() : $instances;
		$instances[] = $data;
		update_post_meta( $event_post, '_mc_custom_instances', $instances );

		if ( $result ) {
			wp_send_json(
				array(
					'success'  => 1,
					'id'       => (int) $id,
					'response' => esc_html__( 'A new date has been added to this event.', 'my-calendar' ),
				)
			);
		} else {
			wp_send_json(
				array(
					'success'  => 0,
					'response' => esc_html__( 'Sorry! I failed to add that date to your event.', 'my-calendar' ),
				)
			);
		}
	} else {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => esc_html__( 'You are not authorized to perform this action', 'my-calendar' ),
			)
		);
	}
}

/**
 * Get information about locations.
 */
function mc_core_autocomplete_search_locations() {
	if ( isset( $_REQUEST['action'] ) && 'mc_core_autocomplete_search_locations' === $_REQUEST['action'] ) {
		$security = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $security, 'mc-search-locations' ) ) {
			wp_send_json(
				array(
					'success'  => 0,
					'response' => array( 'error' => 'Invalid security value.' ),
				)
			);
		}
		$query = sanitize_text_field( $_REQUEST['data'] );

		$locations = mc_core_search_locations( $query );
		$response  = array();
		foreach ( $locations as $location ) {
			$response[] = array(
				'location_id'    => (int) $location->location_id,
				'location_label' => esc_html( strip_tags( $location->location_label ) ),
			);
		}
		wp_send_json(
			array(
				'success'  => 1,
				'response' => $response,
			)
		);
	}
}
add_action( 'wp_ajax_mc_core_autocomplete_search_locations', 'mc_core_autocomplete_search_locations' );
add_action( 'wp_ajax_nopriv_mc_core_autocomplete_search_locations', 'mc_core_autocomplete_search_locations' );
