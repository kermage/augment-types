<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

namespace AugmentTypes;

use AugmentTypes;

class Expire {

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
		add_filter( 'manage_pages_columns', array( $this, 'column_header' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_styles' ) );

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

		$expiration  = get_post_meta( $post->ID, 'at-expiration', true );
		$expiry_date = wp_date( 'Y-m-d', strtotime( $expiration ) );
		$expiry_time = wp_date( 'H:i', strtotime( $expiration ) );

		wp_nonce_field( 'at-expiration-' . $post->ID, 'at-expiration-nonce' );

		?>
<div class="at-metabox-wrap">
	<p>
		<label class="label" for="at-expiration-date"><?php esc_html_e( 'Date', 'augment-types' ); ?></label>
		<input type="date" name="at-expiration[date]" id="at-expiration-date" value="<?php echo esc_attr( $expiry_date ); ?>">
	</p>
	<p>
		<label class="label" for="at-expiration-time"><?php esc_html_e( 'Time', 'augment-types' ); ?></label>
		<input type="time" name="at-expiration[time]" id="at-expiration-time" value="<?php echo esc_attr( $expiry_time ); ?>">
	</p>
</div>
		<?php

	}


	public function save_post( $post_id ) {

		if ( empty( $_POST['at-expiration-nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['at-expiration-nonce'], 'at-expiration-' . $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['at-expiration'] ) ) {
			$expiration = '';

			if ( ! empty( array_filter( $_POST['at-expiration'] ) ) ) {
				if ( empty( $_POST['at-expiration']['date'] ) ) {
					$_POST['at-expiration']['date'] = wp_date( 'Y-m-d' );
				}

				$imploded   = implode( ' ', array_map( 'sanitize_text_field', $_POST['at-expiration'] ) );
				$difference = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
				$adjusted   = strtotime( $imploded ) - $difference;
				$expiration = gmdate( 'Y-m-d H:i:s', $adjusted );
			}

			if ( $expiration === get_post_meta( $post_id, 'at-expiration', true ) ) {
				return;
			}

			update_post_meta( $post_id, 'at-expiration', $expiration );
		}

	}


	public function maybe_expire( $post ) {

		if ( 'archive' === $post->post_status ) {
			return;
		}

		$expiration = get_post_meta( $post->ID, 'at-expiration', true );

		if ( ! $expiration ) {
			return;
		}

		if ( time() >= (int) strtotime( $expiration ) ) {
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

		if ( ! $expiration ) {
			echo '&mdash;';
			return;
		}

		echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $expiration ) ) );
		echo '<br>';
		echo esc_html( wp_date( get_option( 'time_format' ), strtotime( $expiration ) ) );

	}


	public function scripts_styles() {

		if ( ! $this->is_valid_screen() ) {
			return;
		}

		wp_enqueue_style( 'at-expire-style', AugmentTypes::get_data( 'URL' ) . 'assets/at-expire.css', array(), AugmentTypes::get_data( 'Version' ) );

	}


	private function is_valid_screen() {

		$screen = get_current_screen();

		return ! ( null === $screen || ! in_array( $screen->base, array( 'edit', 'post' ), true ) );

	}

}
