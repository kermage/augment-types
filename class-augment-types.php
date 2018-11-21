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

	}


	private function autoload( $class ) {

		if ( 0 !== strpos( $class, 'AT' ) ) {
			return;
		}

		$path = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'includes';
		$name = 'class-' . strtolower( str_replace( '_', '-', $class ) );
		$file = $path . DIRECTORY_SEPARATOR . $name . '.php';

		if ( ! class_exists( $class ) && file_exists( $file ) ) {
			require_once $file;
		}

	}

}

// Get the Augment Types plugin running
Augment_Types::instance();
