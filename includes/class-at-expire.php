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
		add_filter( 'manage_posts_columns', array( $this, 'column_header' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'column_content' ), 10, 2 );

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
		echo '<label class="label" for="at-expiration-date">Date</label>';
		echo '<input type="date" name="at-expiration[date]" id="at-expiration-date" value="' . esc_attr( wp_date( 'Y-m-d', strtotime( $expiration ) ) ) . '">';
		echo '<label class="label" for="at-expiration-time">Time</label>';
		echo '<input type="time" name="at-expiration[time]" id="at-expiration-time" value="' . esc_attr( wp_date( 'H:i', strtotime( $expiration ) ) ) . '">';
		echo '</div>';

	}


	public function save_post( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( isset( $_POST['at-expiration'] ) ) {
			$expiration = '';

			if ( ! empty( array_filter( $_POST['at-expiration'] ) ) ) {
				if ( empty( $_POST['at-expiration']['date'] ) ) {
					$_POST['at-expiration']['date'] = wp_date( 'Y-m-d' );
				}

				$imploded   = implode( ' ', $_POST['at-expiration'] );
				$difference = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
				$adjusted   = strtotime( $imploded ) - $difference;
				$expiration = gmdate( 'Y-m-d H:i:s', $adjusted );
			}

			update_post_meta( $post_id, 'at-expiration', $expiration );
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


	public function column_header( $columns ) {

		$columns['at-expire'] = __( 'Expiration', 'augment-types' );

		return $columns;

	}


	public function column_content( $column, $post_ID ) {

		if ( 'at-expire' !== $column ) {
			return;
		}

		$expiration = get_post_meta( $post_ID, 'at-expiration', true );

		echo wp_date( 'Y-m-d H:i:s', strtotime( $expiration ) );

	}

}
