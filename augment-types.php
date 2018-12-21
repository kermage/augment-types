<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

/**
 * Plugin Name: Augment Types
 * Plugin URI:  https://github.com/kermage/augment-types
 * Author:      Gene Alyson Fortunado Torcende
 * Author URI:  mailto:genealyson.torcende@gmail.com
 * Description: Add essential functionalities to WordPress Post Types.
 * Version:     1.0.0-beta
 * License:     GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

// Accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==================================================
Global constants
================================================== */

if ( ! defined( 'AT_VERSION' ) ) {
	define( 'AT_VERSION', '1.0.0-beta' );
}

if ( ! defined( 'AT_FILE' ) ) {
	define( 'AT_FILE', __FILE__ );
}

if ( ! defined( 'AT_URL' ) ) {
	define( 'AT_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'AT_PATH' ) ) {
	define( 'AT_PATH', plugin_dir_path( __FILE__ ) );
}

// Load the main Augment Types class
require_once AT_PATH . 'class-' . basename( __FILE__ );

// Instantiate the Augment Types updater
require_once AT_PATH . 'class-external-update-manager.php';
new External_Update_Manager( __FILE__, 'https://raw.githubusercontent.com/kermage/augment-types/wp-update/data.json' );
