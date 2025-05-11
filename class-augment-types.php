<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

use AugmentTypes\Admin;
use AugmentTypes\Archive;
use AugmentTypes\Excerpt;
use AugmentTypes\Expire;
use AugmentTypes\Feature;
use AugmentTypes\Sort;


class AugmentTypes {

	private static ?self $instance = null;
	/** @var array<string, mixed> */
	private static array $data;


	public static function instance(): self {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		add_action( 'init', array( $this, 'load_text_domain' ) );
		add_action( 'wpmu_new_blog', array( $this, 'new_blog' ) );
		add_filter( 'term_count', array( $this, 'per_type' ), 10, 3 );
		add_action( 'init', array( Admin::instance(), 'init' ), 20 );
		add_filter( 'plugin_action_links_' . plugin_basename( AUGMENT_TYPES ), array( $this, 'settings_link' ) );

		Sort::instance();
		Feature::instance();
		Archive::instance();
		Excerpt::instance();
		Expire::instance();

	}


	/** @return mixed */
	public static function get_data( string $key ) {

		return self::$data[ $key ];

	}


	public static function activate( bool $network_wide ): void {

		if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
			/** @var wpdb $wpdb */
			global $wpdb;

			$current = $wpdb->blogid;
			$blogs   = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );

			foreach ( $blogs as $blog ) {
				switch_to_blog( $blog );
				self::alter_table();
			}

			switch_to_blog( $current );
		} else {
			self::alter_table();
		}

	}


	public function load_text_domain(): void {

		load_plugin_textdomain( 'augment-types' );

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		self::$data         = get_plugin_data( AUGMENT_TYPES, false, false );
		self::$data['URL']  = plugin_dir_url( AUGMENT_TYPES );
		self::$data['PATH'] = plugin_dir_path( AUGMENT_TYPES );

	}


	public static function new_blog( int $id ): void {

		/** @var wpdb $wpdb */
		global $wpdb;

		if ( is_plugin_active_for_network( plugin_basename( AUGMENT_TYPES ) ) ) {
			$current = $wpdb->blogid;

			switch_to_blog( $id );
			self::alter_table();
			switch_to_blog( $current );
		}

	}


	protected static function alter_table(): void {

		/** @var wpdb $wpdb */
		global $wpdb;

		if ( ! $wpdb->query( "SHOW COLUMNS FROM $wpdb->terms LIKE 'term_order'" ) ) {
			$wpdb->query( "ALTER TABLE $wpdb->terms ADD `term_order` INT( 11 ) NOT NULL DEFAULT '0'" );
		}

	}


	public function per_type( int $value, int $term_id, string $taxonomy ): int {

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


	/**
	 * @param string[] $links
	 *
	 * @return string[]
	 */
	public function settings_link( array $links ): array {

		$settings = sprintf(
			'<a href="%1$s" target="%2$s">%3$s</a>',
			admin_url( Admin::PARENT_PAGE . '?page=' . Admin::OPTION_KEY ),
			'_self',
			__( 'Settings', 'augment-types' ),
		);

		return array_merge( compact( 'settings' ), $links );

	}

}
