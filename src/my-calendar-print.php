<?php
/**
 * Output the print view.
 *
 * @category Calendar
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'my_calendar_print_view' );
/**
 * Redirect to print view if query set.
 */
function my_calendar_print_view() {
	if ( isset( $_GET['cid'] ) && 'mc-print-view' === $_GET['cid'] ) {
		my_calendar_print();
		exit;
	}
}

/**
 * Produce print view output.
 */
function my_calendar_print() {
	$mc_version  = mc_get_version();
	$mc_version .= ( SCRIPT_DEBUG ) ? '-' . wp_rand( 10000, 99999 ) : '';
	$url         = plugin_dir_url( __FILE__ );
	// The time string can contain a plus literal, which needs to be re-encoded.
	$time     = ( isset( $_GET['time'] ) ) ? sanitize_text_field( urlencode( $_GET['time'] ) ) : 'month';
	$category = ( isset( $_GET['mcat'] ) ) ? sanitize_text_field( $_GET['mcat'] ) : '';
	$ltype    = ( isset( $_GET['ltype'] ) ) ? sanitize_text_field( $_GET['ltype'] ) : '';
	$lvalue   = ( isset( $_GET['lvalue'] ) ) ? sanitize_text_field( $_GET['lvalue'] ) : '';
	header( 'Content-Type: ' . get_bloginfo( 'html_type' ) . '; charset=' . get_bloginfo( 'charset' ) );
	if ( mc_file_exists( 'mc-print.css' ) ) {
		$stylesheet = mc_get_file( 'mc-print.css', 'url' );
	} else {
		$stylesheet = $url . 'css/mc-print.css';
	}
	$args       = array(
		'type'     => 'print',
		'category' => $category,
		'time'     => $time,
		'ltype'    => $ltype,
		'lvalue'   => $lvalue,
	);
	$return_url = mc_get_uri( false, $args );
	/**
	 * Filter the root URL used to generate the return URL.
	 *
	 * @hook mc_print_return_url
	 *
	 * @param {string} $return_url Referer URL for calendar print view arrived from.
	 * @param {string} $category Category argument.
	 * @param {string} $time Time argument.
	 * @param {string} $ltype Location type argument.
	 * @param {string} $lvalue Location value argument.
	 *
	 * @return {string}
	 */
	$return_url = apply_filters( 'mc_print_return_url', $return_url, $category, $time, $ltype, $lvalue );

	if ( isset( $_GET['href'] ) ) {
		// Only support URLs on the same home_url().
		$ref_url  = esc_url( urldecode( $_GET['href'] ) );
		$ref_root = parse_url( $ref_url )['host'];
		$root     = parse_url( home_url() )['host'];
		$local    = ( false !== stripos( $ref_url, home_url() ) && false !== stripos( $root, $ref_root ) ) ? true : false;
		if ( $ref_url && $local ) {
			$return_url = $ref_url;
		} else {
			wp_die( 'My Calendar: invalid print URL provided.' );
		}
	}
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<!--<![endif]-->
	<head>
		<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>" />
		<meta name="viewport" content="width=device-width" />
		<title><?php echo esc_html( get_bloginfo( 'name' ) ) . ' - ' . esc_html__( 'Calendar: Print View', 'my-calendar' ); ?></title>
		<meta name="generator" content="My Calendar for WordPress" />
		<meta name="robots" content="noindex,nofollow" />
		<!-- Copy mc-print.css to your theme directory if you wish to replace the default print styles -->
		<link rel="stylesheet" href="<?php echo esc_url( includes_url( '/css/dashicons.css' ) ); ?>" type="text/css" media="screen,print" />
		<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'version', $mc_version, $stylesheet ) ); ?>" type="text/css" media="screen,print" />
		<?php
		/**
		 * Execute action in the `head` element of the My Calendar print view, where wp_head() won't be run.
		 *
		 * @hook mc_print_view_head
		 *
		 * @param {string} $output Potential output for My Calendar; default empty string.
		 */
		do_action( 'mc_print_view_head', '' );
		?>
	</head>
	<body>
	<?php
	$calendar = array(
		'name'     => 'print',
		'format'   => 'calendar',
		'category' => $category,
		'time'     => $time,
		'ltype'    => $ltype,
		'lvalue'   => $lvalue,
		'id'       => 'mc-print-view',
		'below'    => 'key',
		'above'    => 'none',
		'json'     => 'false',
	);

	echo wp_kses_post( my_calendar( $calendar ) );

	$add = array_map( 'esc_html', $_GET );
	unset( $add['cid'] );
	unset( $add['feed'] );
	unset( $add['href'] );
	/**
	 * Return to calendar URL from print view.
	 *
	 * @hook mc_return_to_calendar
	 *
	 * @param {string} $return_url URL to return to previous page.
	 * @param {array}  $add Array of parameters added to this URL.
	 *
	 * @return {string}
	 */
	$return_url = apply_filters( 'mc_return_to_calendar', mc_build_url( $add, array( 'feed', 'cid', 'href', 'searched' ), $return_url ), $add );
	if ( $return_url ) {
		echo "<p class='return'><a href='" . esc_url( $return_url ) . "'><span class='dashicons dashicons-arrow-left-alt' aria-hidden='true'></span> " . esc_html__( 'Return to calendar', 'my-calendar' ) . '</a> <a href="javascript:window.print()"><span class="dashicons dashicons-printer" aria-hidden="true"></span> ' . esc_html( __( 'Print', 'my-calendar' ) ) . '</a></p>';
	}
	?>
	</body>
</html>
	<?php
}
