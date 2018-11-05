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

	}

}

// Get the Augment Types plugin running
Augment_Types::instance();
