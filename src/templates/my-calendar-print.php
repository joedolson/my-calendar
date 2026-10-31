<?php
/**
 * My Calendar print template.
 *
 * Template Name: My Calendar Print
 * Description: Template for the print view of the My Calendar plugin.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>" />
		<meta name="viewport" content="width=device-width" />
		<title><?php echo esc_html( get_bloginfo( 'name', 'display' ) ) . ' - ' . esc_html__( 'Calendar: Print View', 'my-calendar' ); ?></title>
		<meta name="robots" content="noindex,nofollow" />
		<?php
		/**
		 * Execute action in the `head` element of the My Calendar print view, where wp_head() won't be run.
		 *
		 * @hook mc_print_view_head
		 *
		 * @param string $output Potential output for My Calendar; default empty string.
		 */
		do_action( 'mc_print_view_head', '' );
		?>
		<?php wp_head(); ?>
	</head>
	<body>
	<?php
	my_calendar_print_view();
	my_calendar_print_return_url();
	?>
	</body>
</html>
