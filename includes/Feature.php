<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

namespace AugmentTypes;

use AugmentTypes;

class Feature {

	private static $instance;


	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'quick_edit_custom_box', array( $this, 'form' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_styles' ) );

	}


	public function init() {

		$args  = array( 'show_ui' => true );
		$types = get_post_types( $args );

		foreach ( $types as $type ) {
			if ( ! post_type_supports( $type, 'thumbnail' ) ) {
				continue;
			}

			add_filter( 'manage_' . $type . '_posts_columns', array( $this, 'header' ) );
			add_action( 'manage_' . $type . '_posts_custom_column', array( $this, 'content' ), 10, 2 );
		}

	}


	public function header( $columns ) {

		$columns['at-feature'] = __( 'Featured Image', 'augment-types' );

		return $columns;

	}


	public function content( $column, $post_ID ) {

		if ( 'at-feature' !== $column ) {
			return;
		}

		$thumbnail_id = get_post_thumbnail_id( $post_ID );
		$image_sizes  = wp_get_additional_image_sizes();
		$thumb_size   = isset( $image_sizes['post-thumbnail'] ) ? 'post-thumbnail' : array( 266, 266 );

		echo '<a href="#" class="editinline">';
		echo wp_get_attachment_image( (int) $thumbnail_id, $thumb_size, true, array( 'data-id' => $thumbnail_id ) );
		echo '</a>';

	}


	public function form( $column, $type ) {

		if ( 'at-feature' !== $column ) {
			return;
		}

		if ( ! post_type_supports( $type, 'thumbnail' ) ) {
			return;
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}

		$template = '<fieldset class="inline-edit-col-right inline-edit-at-feature">
			<div class="inline-edit-col">
				<span class="title">%1$s</span>
				<div class="at-feature-image">
					<a href="#" class="at-feature-set">%2$s</a>
					<input type="hidden" name="_thumbnail_id" value="" />
					<a href="#" class="at-feature-remove">%3$s</a>
				</div>
			</div>
		</fieldset>';

		$title  = __( 'Featured Image', 'augment-types' );
		$set    = __( 'Set featured image', 'augment-types' );
		$remove = __( 'Remove featured image', 'augment-types' );

		printf( $template, esc_html( $title ), esc_html( $set ), esc_html( $remove ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}


	public function scripts_styles() {

		if ( ! $this->is_valid_screen() ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'at-feature-style', AugmentTypes::get_data( 'URL' ) . 'assets/at-feature.css', array(), AugmentTypes::get_data( 'Version' ) );
		wp_enqueue_script( 'at-feature-script', AugmentTypes::get_data( 'URL' ) . 'assets/at-feature.js', array(), AugmentTypes::get_data( 'Version' ), true );

	}


	private function is_valid_screen() {

		$screen = get_current_screen();

		if ( null === $screen || 'edit' !== $screen->base ) {
			return false;
		}

		if ( ! post_type_supports( $screen->post_type, 'thumbnail' ) ) {
			return false;
		}

		return true;

	}

}
