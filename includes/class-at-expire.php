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
		$exploded   = explode( ' ', $expiration );

		echo '<div class="at-metabox-wrap">';
		echo '<label class="label" for="at-expiration-date">Date</label>';
		echo '<input type="date" name="at-expiration[date]" id="at-expiration-date" value="' . esc_attr( $exploded[0] ) . '">';
		echo '<label class="label" for="at-expiration-time">Time</label>';
		echo '<input type="time" name="at-expiration[time]" id="at-expiration-time" value="' . esc_attr( $exploded[1] ) . '">';
		echo '</div>';

	}


	public function save_post( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( isset( $_POST['at-expiration'] ) ) {
			update_post_meta( $post_id, 'at-expiration', implode( ' ', $_POST['at-expiration'] ) );
		}

	}


	public function maybe_expire( $post ) {

		if ( 'archive' === $post->status ) {
			return;
		}

		$expiration = get_post_meta( $post->ID, 'at-expiration', true );

		if ( ! $expiration ) {
			return;
		}

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
