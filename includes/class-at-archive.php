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
		add_action( 'wp_loaded', array( $this, 'rewrites' ) );
		add_action( 'admin_footer-post.php', array( $this, 'post_js' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'edit_js' ) );
		add_filter( 'display_post_states', array( $this, 'post_states' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'set_status' ) );
		add_filter( 'wp_unique_post_slug_is_bad_flat_slug', array( $this, 'reserve_slug' ), 10, 3 );
		add_filter( 'wp_unique_post_slug_is_bad_hierarchical_slug', array( $this, 'reserve_slug' ), 10, 4 );

	}


	public function init() {

		$args = array(
			'label'       => __( 'Archived', 'augment-types' ),
			'public'      => true,
			/* translators: %s: item count */
			'label_count' => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>' ),
		);

		register_post_status( 'archive', $args );

	}


	public function rewrites() {

		$permalink_structure = get_option( 'permalink_structure', '' );

		if ( false === $permalink_structure ) {
			return;
		}

		if ( false === strpos( $permalink_structure, '%postname%' ) ) {
			return;
		}

		add_rewrite_tag( '%at-archive%', '([^&]+)' );

		$args  = array( 'public' => true );
		$types = get_post_types( $args, 'objects' );

		foreach ( $types as $type ) {
			if ( in_array( $type->name, array( 'post', 'page', 'attachment' ), true ) ) {
				continue;
			}

			$slug = $type->rewrite['slug'];

			add_rewrite_rule( '^' . $slug . '/archive/?$', 'index.php?post_type=' . $type->name . '&at-archive=true', 'top' );
			add_rewrite_rule( '^' . $slug . '/archive/page/([0-9]+)/?$', 'index.php?post_type=' . $type->name . '&paged=$matches[1]&at-archive=true', 'top' );
		}

		$page_for_posts = get_option( 'page_for_posts', 0 );

		$slug = get_permalink( $page_for_posts );

		if ( false === $slug ) {
			return;
		}

		$slug = str_replace( home_url( '/' ), '', $slug );

		add_rewrite_rule( '^' . $slug . 'archive/?$', 'index.php?post_type=post&at-archive=true', 'top' );
		add_rewrite_rule( '^' . $slug . 'archive/page/([0-9]+)/?$', 'index.php?post_type=post&paged=$matches[1]&at-archive=true', 'top' );

	}


	public function post_js() {

		global $post;

		if ( 'attachment' === $post->post_type ) {
			return;
		}

		$object = get_post_type_object( $post->post_type );

		if ( null === $object || ! $object->public ) {
			return;
		}

		if ( function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post->ID ) ) {
			return;
		}

		?>

		<script>
			jQuery( document ).ready( function( $ ) {
			<?php if ( 'draft' !== $post->post_status && 'pending' !== $post->post_status ) : ?>
				$( '#post_status' ).append( '<option value="archive"><?php esc_html_e( 'Archived', 'augment-types' ); ?></option>' );
			<?php endif; ?>
			<?php if ( 'archive' === $post->post_status ) : ?>
				postL10n['saveDraft'] = "<?php esc_html_e( 'Save Archive', 'augment-types' ); ?>";
				postL10n['savingText'] = "<?php esc_html_e( 'Saving Archive...', 'augment-types' ); ?>";
				$( '#post_status' ).val( 'archive' );
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

		$object = get_post_type_object( $typenow );

		if ( null === $object || ! $object->public ) {
			return;
		}

		?>

		<script>
			jQuery( document ).ready( function( $ ) {
				$( 'select[name="_status"]' ).append( '<option value="archive"><?php esc_html_e( 'Archived', 'augment-types' ); ?></option>' );
			} );
		</script>

		<?php

	}


	public function post_states( $states, $post ) {

		if ( 'archive' !== $post->post_status || 'archive' === get_query_var( 'post_status' ) ) {
			return $states;
		}

		$states['at-archive'] = __( 'Archived', 'augment-types' );

		return $states;

	}


	public function set_status( WP_Query $query ) {

		if ( is_admin() ) {
			return $query;
		}

		if ( $query->is_archive() || $query->is_home() ) {
			if ( get_query_var( 'at-archive' ) && get_query_var( 'post_type' ) === $query->get( 'post_type' ) ) {
				$query->set( 'post_status', 'archive' );
			}
		}

		if ( $query->get( 'at-archive' ) && ! $query->get( 'post_type' ) ) {
			$query->set( 'name', '0' );
			$query->set_404();
		}

		return $query;

	}


	public function reserve_slug( $is_bad, $slug, $post_type, $post_parent = null ) {

		if ( 'archive' === $slug && ! $post_parent ) {
			return true;
		}

		return $is_bad;

	}

}
