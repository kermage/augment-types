<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */


class AT_Excerpt {

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
