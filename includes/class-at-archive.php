<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */


class AT_Archive {

	private static $instance;


	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_footer-post.php', array( $this, 'post_js' ) );

	}


	public function init() {

		$args = array(
			'label'       => __( 'Archived' ),
			'public'      => true,
			'label_count' => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>' ),
		);

		register_post_status( 'at-archive', $args );

	}


	public function post_js() {

		global $post;

		?>

		<script>
			jQuery( document ).ready( function( $ ) {
				$( '#post_status' ).append( '<option value="at-archive"><?php esc_html_e( 'Archived', 'augment-types' ); ?></option>' );
			<?php if ( 'at-archive' === $post->post_status ) : ?>
				$( '#post_status' ).val( 'at-archive' );
				$( '#post-status-display' ).text( '<?php esc_html_e( 'Archived', 'augment-types' ); ?>' );
			<?php endif; ?>
			} );
		</script>

		<?php

	}

}
