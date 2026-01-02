<?php
/**
 * My Calendar embed template.
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<title><?php the_title(); ?></title>
<?php wp_head(); ?>
</head>
<body>
<?php
	the_content();
	wp_footer();
?>
</body>
</html>
