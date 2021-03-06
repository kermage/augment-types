<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */


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

		self::$data         = get_plugin_data( AT_FILE );
		self::$data['URL']  = plugin_dir_url( AT_FILE );
		self::$data['PATH'] = plugin_dir_path( AT_FILE );

		add_action( 'wpmu_new_blog', array( $this, 'new_blog' ) );
		add_filter( 'term_count', array( $this, 'per_type' ), 10, 3 );

		\AugmentTypes\Sort::instance();
		\AugmentTypes\Feature::instance();
		\AugmentTypes\Archive::instance();
		\AugmentTypes\Excerpt::instance();
		\AugmentTypes\Expire::instance();

	}


	public static function get_data( $key ) {

		return self::$data[ $key ];

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


	public function per_type( $value, $term_id, $taxonomy ) {

		$screen = get_current_screen();

		if ( null === $screen || 'edit-tags' !== $screen->base ) {
			return $value;
		}

		$args = array(
			'post_type'      => $screen->post_type,
			'post_status'    => 'any',
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
