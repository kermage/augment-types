<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

namespace AugmentTypes;

use WP_Post;

class Excerpt {

	private static ?self $instance = null;

	public const TYPE_ARGS = array(
		'show_ui' => true,
	);

	public const EXCLUDED_TYPES = array(
		'wp_block',
	);


	public static function instance(): self {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );

	}


	protected function enabled_types(): array {

		return array_merge( Admin::instance()->option( 'excerpt_enabled' ) );

	}


	public function meta_box( string $post_type ): void {

		if ( ! post_type_supports( $post_type, 'excerpt' ) ) {
			return;
		}

		if ( ! in_array( $post_type, $this->enabled_types(), true ) ) {
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

	public function excerpt_editor( ?WP_Post $post ): void {

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
