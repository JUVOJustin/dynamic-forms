<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://juvo-design.de
 * @since             1.0.0
 * @package           Dynamic_Forms
 *
 * @wordpress-plugin
 * Plugin Name:       Dynamic Forms
 * Plugin URI:        https://juvo-design.de
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Justin Vogt
 * Author URI:        https://juvo-design.de
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires Plugins:  advanced-custom-fields-pro
 * Text Domain:       calendar-booking
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
use Dynamic_Forms\Activator;
use Dynamic_Forms\Deactivator;
use Dynamic_Forms\Dynamic_Forms;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin absolute path
 */
define( 'DYNAMIC_FORMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'DYNAMIC_FORMS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Use Composer PSR-4 Autoloading
 */
require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * The code that runs during plugin activation.
 */
function activate_dynamic_forms() {
    Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_dynamic_forms() {
    Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_dynamic_forms' );
register_deactivation_hook( __FILE__, 'deactivate_dynamic_forms' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_dynamic_forms() {
	$plugin = new Dynamic_Forms();
	$plugin->run();
}
run_dynamic_forms();
