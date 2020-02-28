<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */


class AT_Expire {

	private static $instance;


	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'the_post', array( $this, 'maybe_expire' ) );

	}


	public function meta_box( $post_type ) {

		add_meta_box(
			'at_expire_settings',
			__( 'Expiration', 'augment-types' ),
			array( $this, 'expire_settings' ),
			$post_type,
			'side',
			'high'
		);

	}

	public function expire_settings( $post ) {

		$expiration = get_post_meta( $post->ID, 'at-expiration', true );

		echo '<div class="at-metabox-wrap">';
		echo '<label class="label" for="at-expiration">Date</label>';
		echo '<input type="date" name="at-expiration" id="at-expiration" value="' . esc_attr( $expiration ) . '">';
		echo '</div>';

	}


	public function save_post( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		$expiration = empty( $_POST['at-expiration'] ) ? false : $_POST['at-expiration'];

		if ( $expiration ) {
			update_post_meta( $post_id, 'at-expiration', $expiration );
		} else {
			delete_post_meta( $post_id, 'at-expiration' );
		}

	}


	public function maybe_expire( $post ) {

		$expiration = get_post_meta( $post->ID, 'at-expiration', true );

		if ( $expiration && strtotime( 'now' ) >= strtotime( $expiration ) ) {
			$postarr = array(
				'ID'          => $post->ID,
				'post_status' => 'archive',
			);

			remove_action( 'save_post', array( $this, 'save_post' ) );
			wp_update_post( $postarr );
			add_action( 'save_post', array( $this, 'save_post' ) );
		}

	}

}
