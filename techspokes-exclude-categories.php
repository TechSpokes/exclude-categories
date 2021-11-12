<?php
/**
 * Exclude Categories by TechSpokes Inc.
 *
 * @package     TechSpokes\ExcludeCategories
 * @author      Serge Liatko
 * @copyright   2018 Serge Liatko
 * @license     GPL-3.0+
 *
 * @wordpress-plugin
 * Plugin Name: Exclude Categories by TechSpokes Inc.
 * Plugin URI:  https://github.com/TechSpokes/techspokes-exclude-categories.git
 * Description: Allows you to exclude specific categories from blog page and feed in WordPress.
 * Version:     1.0.1
 * Author:      TechSpokes Inc.
 * Author URI:  https://techspokes.com
 * Text Domain: techspokes-exclude-categories
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

// do not load this file directly
defined( 'ABSPATH' ) or die( sprintf( 'Please do not load %s directly', __FILE__ ) );

// load namespace
require_once( dirname( __FILE__ ) . '/autoload.php' );

// load plugin text domain
add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'techspokes-exclude-categories', false, basename( dirname( __FILE__ ) . '/languages' ) );
}, 10, 0 );

// load the plugin
add_action( 'plugins_loaded', array( 'TechSpokes\ExcludeCategories\Plugin', 'getInstance' ), 10, 0 );
