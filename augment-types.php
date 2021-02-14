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
 * Version:     1.10.0
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

if ( ! defined( 'AT_FILE' ) ) {
	define( 'AT_FILE', __FILE__ );
}

// Load the main Augment Types class
require_once plugin_dir_path( AT_FILE ) . 'class-' . basename( AT_FILE );

// Get the Augment Types plugin running
Augment_Types::instance();
register_activation_hook( AT_FILE, array( 'Augment_Types', 'activate' ) );

// Instantiate the Augment Types updater
require_once plugin_dir_path( AT_FILE ) . 'class-external-update-manager.php';
EUM_Handler::run( AT_FILE, 'https://raw.githubusercontent.com/kermage/augment-types/master/update-data.json' );
