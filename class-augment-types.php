<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

use AugmentTypes\Archive;
use AugmentTypes\Excerpt;
use AugmentTypes\Expire;
use AugmentTypes\Feature;
use AugmentTypes\Sort;


class AugmentTypes {

	private static $instance;
	private static $data;


	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		self::$data         = get_plugin_data( AUGMENT_TYPES );
		self::$data['URL']  = plugin_dir_url( AUGMENT_TYPES );
		self::$data['PATH'] = plugin_dir_path( AUGMENT_TYPES );

		add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );
		add_action( 'wpmu_new_blog', array( $this, 'new_blog' ) );
		add_filter( 'term_count', array( $this, 'per_type' ), 10, 3 );

		Sort::instance();
		Feature::instance();
		Archive::instance();
		Excerpt::instance();
		Expire::instance();

	}


	public static function get_data( $key ) {

		return self::$data[ $key ];

	}


	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
			global $wpdb;

			$current = $wpdb->blogid;
			$blogs   = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );

			foreach ( $blogs as $blog ) {
				switch_to_blog( $blog );
				self::_alter_table();
			}

			switch_to_blog( $current );
		} else {
			self::_alter_table();
		}

	}


	public function load_text_domain() {

		load_plugin_textdomain( 'augment-types' );

	}


	public static function new_blog( $id ) {

		global $wpdb;

		if ( is_plugin_active_for_network( plugin_basename( AUGMENT_TYPES ) ) ) {
			$current = $wpdb->blogid;

			switch_to_blog( $id );
			self::_alter_table();
			switch_to_blog( $current );
		}

	}


	public static function _alter_table() {

		global $wpdb;

		if ( ! $wpdb->query( "SHOW COLUMNS FROM $wpdb->terms LIKE 'term_order'" ) ) {
			$wpdb->query( "ALTER TABLE $wpdb->terms ADD `term_order` INT( 11 ) NOT NULL DEFAULT '0'" );
		}

	}


	public function per_type( $value, $term_id, $taxonomy ) {

		$screen = get_current_screen();

		if ( null === $screen || 'edit-tags' !== $screen->base ) {
			return $value;
		}

		$args = array(
			'post_type'      => $screen->post_type,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => $taxonomy,
					'terms'    => $term_id,
				),
			),
		);

		$query = new WP_Query( $args );

		return $query->post_count;

	}

}
