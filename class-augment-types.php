<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */


class Augment_Types {

	private static $instance;


	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		spl_autoload_register( array( $this, 'autoload' ) );

		AT_Sort::instance();
		AT_Feature::instance();
		AT_Archive::instance();
		AT_Excerpt::instance();

	}


	private function autoload( $class ) {

		if ( 0 !== strpos( $class, 'AT' ) ) {
			return;
		}

		$path = __DIR__ . DIRECTORY_SEPARATOR . 'includes';
		$name = 'class-' . strtolower( str_replace( '_', '-', $class ) );
		$file = $path . DIRECTORY_SEPARATOR . $name . '.php';

		if ( ! class_exists( $class ) && file_exists( $file ) ) {
			require_once $file;
		}

	}


	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
			global $wpdb;

			$current = $wpdb->blogid;
			$blogs   = $wpdb->get_col( "SELECT `blog_id` FROM {$wpdb->blogs}" );

			foreach ( $blogs as $blog ) {
				switch_to_blog( $blog );
				self::_alter_table();
			}

			switch_to_blog( $current );
		} else {
			self::_alter_table();
		}

	}


	public static function _alter_table() {

		global $wpdb;

		if ( ! $wpdb->query( "SHOW COLUMNS FROM {$wpdb->terms} LIKE 'term_order'" ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->terms} ADD `term_order` INT( 11 ) NOT NULL DEFAULT '0'" );
		}

	}

}

// Get the Augment Types plugin running
Augment_Types::instance();
