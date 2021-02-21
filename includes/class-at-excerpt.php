<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

namespace AugmentTypes;

class Excerpt {

	private static $instance;


	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		add_action( 'init', array( $this, 'init' ), 11 );
		add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );

	}


	public function init() {

		$args  = array( 'show_ui' => true );
		$types = get_post_types( $args );

		foreach ( $types as $type ) {
			if ( ! post_type_supports( $type, 'excerpt' ) ) {
				continue;
			}

			remove_post_type_support( $type, 'excerpt' );
			add_post_type_support( $type, 'at-excerpt' );
		}

	}


	public function meta_box( $post_type ) {

		if ( ! post_type_supports( $post_type, 'at-excerpt' ) ) {
			return;
		}

		remove_meta_box( 'postexcerpt', $post_type, 'normal' );
		add_meta_box(
			'at_excerpt_editor',
			__( 'Excerpt', 'augment-types' ),
			array( $this, 'excerpt_editor' ),
			$post_type,
			'normal',
			'high'
		);

	}

	public function excerpt_editor( $post ) {

		$excerpt = '';

		if ( $post && $post->post_excerpt ) {
			$excerpt = $post->post_excerpt;
			$excerpt = html_entity_decode( $excerpt );
			$excerpt = wp_kses_decode_entities( $excerpt );
		}

		$options = array(
			'media_buttons' => false,
			'textarea_rows' => 4,
			'editor_height' => 200,
		);

		echo '<div class="at-metabox-wrap">';
		wp_editor( $excerpt, 'excerpt', $options );
		echo '</div>';

	}

}
