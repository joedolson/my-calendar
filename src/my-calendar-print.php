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
		echo my_calendar_print();
		exit;
	}
}

/**
 * Produce print view output.
 */
function my_calendar_print() {
	$url      = plugin_dir_url( __FILE__ );
	$time     = ( isset( $_GET['time'] ) ) ? $_GET['time'] : 'month';
	$category = ( isset( $_GET['mcat'] ) ) ? $_GET['mcat'] : ''; // These are sanitized elsewhere.
	$ltype    = ( isset( $_GET['ltype'] ) ) ? $_GET['ltype'] : '';
	$lvalue   = ( isset( $_GET['lvalue'] ) ) ? $_GET['lvalue'] : '';
	header( 'Content-Type: ' . get_bloginfo( 'html_type' ) . '; charset=' . get_bloginfo( 'charset' ) );
	if ( mc_file_exists( 'mc-print.css' ) ) {
		$stylesheet = mc_get_file( 'mc-print.css', 'url' );
	} else {
		$stylesheet = $url . 'css/mc-print.css';
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
		<link rel="stylesheet" href="<?php echo esc_url( $stylesheet ); ?>" type="text/css" media="screen,print" />
		<?php do_action( 'mc_print_view_head', '' ); ?>
	</head>
	<body>
	<?php
	$args = array(
		'type'     => 'print',
		'category' => $category,
		'time'     => $time,
		'ltype'    => $ltype,
		'lvalue'   => $lvalue,
	);

	$calendar = array(
		'name'     => 'print',
		'format'   => 'calendar',
		'category' => $category,
		'time'     => $time,
		'ltype'    => $ltype,
		'lvalue'   => $lvalue,
		'id'       => 'mc-print-view',
		'below'    => 'none',
		'above'    => 'none',
	);

	echo mc_kses_post( my_calendar( $calendar ) );
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
		$ref_url = esc_url( urldecode( $_GET['href'] ) );
		if ( $ref_url ) {
			$return_url = $ref_url;
		}
	}

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
		echo wp_kses_post( "<p class='return'>&larr; <a href='" . esc_url( $return_url ) . "'>" . esc_html__( 'Return to calendar', 'my-calendar' ) . '</a></p>' );
	}
	?>
	</body>
</html>
	<?php
}
