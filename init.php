<?php
/**
 * My Calendar, Accessible Events Manager for WordPress
 *
 * @package     MyCalendar
 * @author      Joe Dolson
 * @copyright   2009-2024 Joe Dolson
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: My Calendar
 * Plugin URI:  http://www.joedolson.com/my-calendar/
 * Description: Accessible WordPress event calendar plugin. Show events from multiple calendars on pages, in posts, or in widgets.
 * Author:      Joseph C Dolson
 * Author URI:  http://www.joedolson.com
 * Text Domain: my-calendar
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/license/gpl-2.0.txt
 * Domain Path: lang
 * Version:     3.5.0-rc2
 */

/*
	Copyright 2009-2024  Joe Dolson (email : joe@joedolson.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/src/my-calendar.php';

register_activation_hook( __FILE__, 'mc_plugin_activated' );
register_deactivation_hook( __FILE__, 'mc_plugin_deactivated' );
