<?php
/**
 * Plugin Name:   Post Edit Phases
 * Plugin URI:    https://github.com/tcmulder/phases
 * Description:   Set up phases you can apply to your posts (like to do, doing, done) so you can keep track of where your posts are within your publish process.
 * Version:       1.0.0
 * Author:        Tomas Mulder
 * Author URI:    https://www.thinkaquamarine.com
 * Text Domain:   phases
 * License:       GPL2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Defines common constants for plugin use.
define( 'PHASES_VERSION', '1.0.0' );
define( 'PHASES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PHASES_PLUGIN_URI', plugin_dir_url( __FILE__ ) );
define( 'PHASES_PLUGIN_FILE', __FILE__ );

// Runs the initial method for the PHASES class.
require_once( PHASES_PLUGIN_DIR . 'class-phases.php' );
PHASES::init();

// Exposes the API.
require_once( PHASES_PLUGIN_DIR . 'phases-api.php' );
