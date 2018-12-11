<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */


class AT_Feature {

	private static $instance;


	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		add_filter( 'manage_posts_columns', array( $this, 'header' ) );
		add_filter( 'manage_page_posts_columns', array( $this, 'header' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'content' ), 10, 2 );
		add_action( 'manage_page_posts_custom_column', array( $this, 'content' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'form' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_styles' ) );

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

		echo wp_get_attachment_image( $thumbnail_id, 'thumbnail', true, array( 'data-id' => $thumbnail_id ) );

	}


	public function form( $column, $type ) {

		if ( 'at-feature' !== $column ) {
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

		printf( $template, esc_html( $title ), esc_html( $set ), esc_html( $remove ) );

	}


	public function scripts_styles() {

		if ( ! $this->is_valid_screen() ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'at-feature-style', AT_URL . 'assets/at-feature.css', array(), AT_VERSION );
		wp_enqueue_script( 'at-feature-script', AT_URL . 'assets/at-feature.js', array(), AT_VERSION, true );

	}


	private function is_valid_screen() {

		$screen = get_current_screen();

		if ( 'edit' !== $screen->base ) {
			return false;
		}

		return true;

	}

}
