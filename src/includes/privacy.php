<?php
/**
 * Privacy Exporter
 *
 * @category Privacy
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wp_privacy_personal_data_exporters', 'my_calendar_exporter', 10 );
/**
 * GDPR Privacy Exporter hook
 *
 * @param array $exporters All registered exporters.
 *
 * @return array<string, mixed>
 */
function my_calendar_exporter( $exporters ) {
	$exporters['my-calendar-exporter'] = array(
		'exporter_friendly_name' => __( 'My Calendar - Privacy Export', 'my-calendar' ),
		'callback'               => 'my_calendar_privacy_export',
	);

	return $exporters;
}

/**
 * GDPR Privacy Exporter
 *
 * @param string $email_address Email address to get data for.
 * @param int    $page Page of data to remove.
 *
 * @return array<string, mixed>
 */
function my_calendar_privacy_export( $email_address, $page = 1 ) {
	global $wpdb;
	$data         = array(
		'data' => array(),
		'done' => true,
	);
	$export_items = array();

	if ( empty( $email_address ) ) {
		return $data;
	}

	// Need to get all events with this email address as host, author, or meta data.
	$posts = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_submitter_details' AND 'meta_value' LIKE %s", '%' . esc_sql( $email_address ) . '%s' ) );
	foreach ( $posts as $post ) {
		$events[] = get_post_meta( $post, '_mc_event_id', true );
	}

	$user = get_user_by( 'email', $email_address );
	if ( $user ) {
		$user_ID  = $user->ID;
		$calendar = $wpdb->get_results( $wpdb->prepare( 'SELECT event_id FROM ' . my_calendar_table() . ' WHERE event_host = %d OR event_author = %d', $user_ID, $user_ID ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $calendar as $obj ) {
			$events[] = $obj->event_id;
		}
	}

	if ( empty( $events ) ) {
		return $data;
	} else {
		foreach ( $events as $e ) {
			$event_export = array();
			$event        = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . my_calendar_table() . ' WHERE event_id = %d', $e ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$meta         = get_post_meta( $event->event_post );

			foreach ( $event as $key => $value ) {
				// Omit empty values.
				if ( empty( $value ) ) {
					continue;
				}
				$event_export[] = array(
					'name'  => $key,
					'value' => $value,
				);
			}
			foreach ( $meta as $mkey => $mvalue ) {
				if ( false !== stripos( $mkey, '_mt_' ) || '_mc_event_data' === $mkey || '_mc_event_desc' === $mkey ) {
					continue;
				}
				// Omit empty values.
				if ( empty( $mvalue[0] ) ) {
					continue;
				}
				$event_export[] = array(
					'name'  => $mkey,
					'value' => $mvalue[0],
				);
			}
			$export_items[] = array(
				'group_id'    => 'my-calendar-export',
				'group_label' => 'My Calendar',
				'item_id'     => "event-$e",
				'data'        => $event_export,
			);
		}

		return array(
			'data' => $export_items,
			'done' => true,
		);
	}
}

add_filter( 'wp_privacy_personal_data_erasers', 'my_calendar_eraser', 10 );
/**
 * GDPR Privacy eraser hook
 *
 * @param array $erasers All registered erasers.
 *
 * @return array<string, mixed>
 */
function my_calendar_eraser( $erasers ) {
	$erasers['my-calendar-eraser'] = array(
		'eraser_friendly_name' => __( 'My Calendar - Eraser', 'my-calendar' ),
		'callback'             => 'my_calendar_privacy_eraser',
	);

	return $erasers;
}

/**
 * GDPR Privacy eraser
 *
 * @param string $email_address Email address to get data for.
 * @param int    $page Page of data to remove.
 *
 * @return array<string, mixed>
 */
function my_calendar_privacy_eraser( $email_address, $page = 1 ) {
	global $wpdb;
	if ( empty( $email_address ) ) {
		return array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
	$deletions = array();
	$updates   = array();

	// Need to get all events with this email address as host, author, or meta data.
	$posts = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_submitter_details' AND 'meta_value' LIKE %s", '%' . esc_sql( $email_address ) . '%s' ) );
	foreach ( $posts as $post ) {
		$deletions[] = get_post_meta( $post, '_mc_event_id', true );
	}

	$user = get_user_by( 'email', $email_address );
	if ( $user ) {
		$user_ID = $user->ID;
		// for deletion, if *author*, delete; if *host*, change host.
		$calendar = $wpdb->get_results( $wpdb->prepare( 'SELECT event_id, event_host, event_author FROM ' . my_calendar_table() . ' WHERE event_host = %d OR event_author = %d', $user_ID, $user_ID ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $calendar as $obj ) {
			if ( absint( $user_ID ) === absint( $obj->event_host ) && absint( $obj->event_host ) !== absint( $obj->event_author ) ) {
				$updates[] = array( $obj->event_id, $obj->event_author );
			} else {
				$deletions[] = $obj->event_id;
			}
		}
	}

	$items_removed  = false;
	$items_retained = false;
	$messages       = array();

	foreach ( $deletions as $delete ) {
		$event_deleted = mc_delete_event( $delete );
		$items_removed = true;
	}

	foreach ( $updates as $update ) {
		$event_updated  = mc_update_event( 'event_host', $update[1], $update[0], '%d' );
		$items_retained = true;
	}

	return array(
		'items_removed'  => $items_removed,
		'items_retained' => $items_retained,
		'messages'       => $messages,
		'done'           => true,
	);
}
