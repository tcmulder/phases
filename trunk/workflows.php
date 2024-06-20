<?php
/**
 * Plugin Name:   Workflow
 * Plugin URI:    https://github.com/tcmulder/workflows
 * Description:   Set up custom workflow stages you can apply to your posts, so you can keep track of where your posts are within your publish process.
 * Version:       1.0.0
 * Author:        Tomas Mulder
 * Author URI:    https://www.thinkaquamarine.com
 * Text Domain:   workflows
 * License:       GPL2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Defines common constants for plugin use.
define( 'WORKFLOWS_VERSION', '1.0.0' );
define( 'WORKFLOWS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WORKFLOWS_PLUGIN_URI', plugin_dir_url( __FILE__ ) );
define( 'WORKFLOWS_PLUGIN_FILE', __FILE__ );

// Runs the initial method for the WORKFLOWS class.
require_once( WORKFLOWS_PLUGIN_DIR . 'class-workflows.php' );
WORKFLOWS::init();

// Exposes the API.
require_once( WORKFLOWS_PLUGIN_DIR . 'workflows-api.php' );
