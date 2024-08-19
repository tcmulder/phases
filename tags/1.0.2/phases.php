<?php
/**
 * Plugin Name:   Post Edit Phases
 * Plugin URI:    https://github.com/tcmulder/phases
 * Description:   Keep track of the status of your posts as you work through each phase of creating them.
 * Version:       1.0.1
 * Author:        Tomas Mulder
 * Author URI:    https://www.thinkaquamarine.com
 * Text Domain:   phases
 * License:       GPL2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exits if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Defines common constants for plugin use.
define( 'PHASES_VERSION', '1.0.0' );
define( 'PHASES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PHASES_PLUGIN_URI', plugin_dir_url( __FILE__ ) );
define( 'PHASES_PLUGIN_FILE', __FILE__ );

// Runs the initial method for the Phases class.
require_once( PHASES_PLUGIN_DIR . 'class-phases.php' );
PHASES::init();
