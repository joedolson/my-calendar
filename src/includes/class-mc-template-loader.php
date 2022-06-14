<?php
/**
 * Template Loader for My Calendar
 *
 * @category Templates
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

/**
 * Template loader for My Calendar.
 *
 * Only need to specify class properties here.
 */
class Mc_Template_Loader extends Gamajo_Template_Loader {

	/**
	 * Prefix for filter names.
	 *
	 * @since 1.0.0
	 * @var string $filter_prefix Filter prefix.
	 */
	protected $filter_prefix = 'mc';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @since 1.0.0
	 * @var string $theme_template_directory Theme template directory slug.
	 */
	protected $theme_template_directory = 'mc-templates';

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * @since 1.0.0
	 * @var string $plugin_directory Path to root plugin directory.
	 */
	protected $plugin_directory = MC_DIRECTORY;

	/**
	 * Directory name where templates are found in this plugin.
	 *
	 * @since 1.0.0
	 * @var string $plugin_template_directory Plugin template directory slug.
	 */
	protected $plugin_template_directory = 'mc-templates';
}
