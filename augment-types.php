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
 * Version:     1.9.0
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
	define( 'AT_VERSION', '1.9.0' );
}

if ( ! defined( 'AT_FILE' ) ) {
	define( 'AT_FILE', __FILE__ );
}

if ( ! defined( 'AT_URL' ) ) {
	define( 'AT_URL', plugin_dir_url( AT_FILE ) );
}

if ( ! defined( 'AT_PATH' ) ) {
	define( 'AT_PATH', plugin_dir_path( AT_FILE ) );
}

// Load the main Augment Types class
require_once AT_PATH . 'class-' . basename( AT_FILE );

register_activation_hook( AT_FILE, array( 'Augment_Types', 'activate' ) );

// Instantiate the Augment Types updater
require_once AT_PATH . 'class-external-update-manager.php';
new External_Update_Manager( AT_FILE, 'https://raw.githubusercontent.com/kermage/augment-types/wp-update/data.json' );
