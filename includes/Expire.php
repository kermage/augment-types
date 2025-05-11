<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

namespace AugmentTypes;

use AugmentTypes;
use WP_Post;

class Expire {

	private static ?self $instance = null;

	public const EXCLUDED_TYPES = array(
		'attachment',
	);


	public static function instance(): self {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'the_post', array( $this, 'maybe_expire' ) );
		add_filter( 'manage_posts_columns', array( $this, 'column_header' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_filter( 'manage_pages_columns', array( $this, 'column_header' ), 10, 2 );
		add_action( 'manage_pages_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_styles' ) );

	}


	/** @return string[] */
	protected function enabled_types(): array {

		return array_merge( Admin::instance()->option( 'expire_enabled' ) );

	}


	public function meta_box( string $post_type ): void {

		if ( ! in_array( $post_type, $this->enabled_types(), true ) ) {
			return;
		}

		add_meta_box(
			'at_expire_settings',
			__( 'Expiration', 'augment-types' ),
			array( $this, 'expire_settings' ),
			$post_type,
			'side',
			'high'
		);

	}

	public function expire_settings( WP_Post $post ): void {

		$expiration  = get_post_meta( $post->ID, 'at-expiration', true );
		$expiry_date = (string) wp_date( 'Y-m-d', strtotime( $expiration ) );
		$expiry_time = (string) wp_date( 'H:i', strtotime( $expiration ) );

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


	public function save_post( int $post_id ): void {

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

			if ( get_post_meta( $post_id, 'at-expiration', true ) === $expiration ) {
				return;
			}

			update_post_meta( $post_id, 'at-expiration', $expiration );
		}

	}


	public function maybe_expire( WP_Post $post ): void {

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


	public function column_header( array $columns, string $post_type = 'page' ): array {

		if ( ! in_array( $post_type, $this->enabled_types(), true ) ) {
			return $columns;
		}

		$columns['at-expire'] = __( 'Expiration', 'augment-types' );

		return $columns;

	}


	public function column_content( string $column, int $post_ID ): void {

		if ( 'at-expire' !== $column ) {
			return;
		}

		$expiration = get_post_meta( $post_ID, 'at-expiration', true );

		if ( ! $expiration ) {
			echo '&mdash;';
			return;
		}

		echo esc_html( (string) wp_date( get_option( 'date_format' ), strtotime( $expiration ) ) );
		echo '<br>';
		echo esc_html( (string) wp_date( get_option( 'time_format' ), strtotime( $expiration ) ) );

	}


	public function scripts_styles(): void {

		if ( ! $this->is_valid_screen() ) {
			return;
		}

		wp_enqueue_style( 'at-expire-style', AugmentTypes::get_data( 'URL' ) . 'assets/at-expire.css', array(), AugmentTypes::get_data( 'Version' ) );

	}


	private function is_valid_screen(): bool {

		$screen = get_current_screen();

		if ( null === $screen ) {
			return false;
		}

		if ( ! in_array( $screen->post_type, $this->enabled_types(), true ) ) {
			return false;
		}

		return in_array( $screen->base, array( 'edit', 'post' ), true );

	}

}
