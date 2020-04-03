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

		add_action( 'wpmu_new_blog', array( $this, 'new_blog' ) );

		AT_Sort::instance();
		AT_Feature::instance();
		AT_Archive::instance();
		AT_Excerpt::instance();
		AT_Expire::instance();

	}


	private function autoload( $class ) {

		if ( 0 !== strpos( $class, 'AT' ) ) {
			return;
		}

		$name = 'class-' . strtolower( str_replace( '_', '-', $class ) );
		$file = AT_PATH . 'includes' . DIRECTORY_SEPARATOR . $name . '.php';

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


	public static function new_blog( $id ) {

		global $wpdb;

		if ( is_plugin_active_for_network( plugin_basename( AT_FILE ) ) ) {
			$current = $wpdb->blogid;

			switch_to_blog( $id );
			self::_alter_table();
			switch_to_blog( $current );
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
