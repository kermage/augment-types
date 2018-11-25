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
		add_action( 'admin_footer-edit.php', array( $this, 'edit_js' ) );
		add_filter( 'display_post_states', array( $this, 'post_states' ), 10, 2 );

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

		if ( 'attachment' === $post->post_type ) {
			return;
		}

		?>

		<script>
			jQuery( document ).ready( function( $ ) {
				$( '#post_status' ).append( '<option value="at-archive"><?php esc_html_e( 'Archived', 'augment-types' ); ?></option>' );
			<?php if ( 'at-archive' === $post->post_status ) : ?>
				$( '#post_status' ).val( 'at-archive' );
				$( '#post-status-display' ).text( '<?php esc_html_e( 'Archived', 'augment-types' ); ?>' );
				$( '#save-post' ).val( '<?php esc_html_e( 'Save Archive', 'augment-types' ); ?>' );
			<?php endif; ?>
			} );
		</script>

		<?php

	}


	public function edit_js() {

		global $typenow;

		if ( 'attachment' === $typenow ) {
			return;
		}

		?>

		<script>
			jQuery( document ).ready( function( $ ) {
				$( 'select[name="_status"]' ).append( '<option value="at-archive"><?php esc_html_e( 'Archived', 'augment-types' ); ?></option>' );
			} );
		</script>

		<?php

	}


	public function post_states( $states, $post ) {

		if ( 'at-archive' !== $post->post_status || 'at-archive' === get_query_var( 'post_status' ) ) {
			return $states;
		}

		$states['at-archive'] = __( 'Archived', 'augment-types' );

		return $states;

	}

}
