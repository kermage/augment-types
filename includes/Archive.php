<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

namespace AugmentTypes;

use AugmentTypes;

class Archive {

	private static $instance;


	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'wp_loaded', array( $this, 'rewrites' ) );
		add_action( 'admin_footer-post.php', array( $this, 'post_js' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'edit_js' ) );
		add_filter( 'display_post_states', array( $this, 'post_states' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'set_status' ) );
		add_filter( 'wp_unique_post_slug_is_bad_flat_slug', array( $this, 'reserve_slug' ), 10, 3 );
		add_filter( 'wp_unique_post_slug_is_bad_hierarchical_slug', array( $this, 'reserve_slug' ), 10, 4 );
		add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'scripts_styles' ) );

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
			if ( in_array( $type->name, array( 'post', 'page', 'attachment' ), true ) || ! $type->rewrite ) {
				continue;
			}

			$slug = $type->name;

			if ( isset( $type->rewrite['slug'] ) ) {
				$slug = $type->rewrite['slug'];
			}

			add_rewrite_rule( '^' . $slug . '/archive/?$', 'index.php?post_type=' . $type->name . '&at-archive=true', 'top' );
			add_rewrite_rule( '^' . $slug . '/archive/page/([0-9]+)/?$', 'index.php?post_type=' . $type->name . '&paged=$matches[1]&at-archive=true', 'top' );
		}

		$slug = get_option( 'page_for_posts', 0 );
		$slug = get_permalink( $slug );
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

		if ( ! current_user_can( 'delete_others_posts' ) ) {
			return;
		}

		?>

		<script>
			jQuery( document ).ready( function( $ ) {
				$( '.misc-pub-post-status' ).hide();
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

		if ( ! current_user_can( 'delete_others_posts' ) ) {
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


	public function set_status( $query ) {

		if ( is_admin() || ! $query ) {
			return $query;
		}

		global $wp_query;

		if ( ! $wp_query ) {
			return $query;
		}

		if ( $query->is_archive() || $query->is_home() ) {
			global $wp_post_statuses;

			$wp_post_statuses['archive']->public = false;

			if ( $query->is_main_query() && get_query_var( 'at-archive' ) && get_query_var( 'post_type' ) === $query->get( 'post_type' ) ) {
				$query->set( 'post_status', 'archive' );
			}
		}

		return $query;

	}


	public function reserve_slug( $is_bad, $slug, $post_type, $post_parent = null ) {

		if ( 'archive' === $slug && ! $post_parent ) {
			return true;
		}

		return $is_bad;

	}


	public function meta_box( $post_type ) {

		add_meta_box(
			'at_archive_select',
			__( 'Status', 'augment-types' ),
			array( $this, 'archive_select' ),
			$post_type,
			'side',
			'high'
		);

	}

	public function archive_select( $post ) {

		$statuses = get_post_stati( array( 'show_in_admin_all_list' => true ), 'objects' );
		$classic  = ! ( function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post->ID ) );

		if ( 'auto-draft' === $post->post_status ) {
			$post->post_status = 'draft';
		}

		$current = sprintf(
			/* translators: %s: Post status label. */
			__( 'Current: %s', 'augment-types' ),
			sprintf(
				'<strong>%s</strong>',
				esc_html( $statuses[ $post->post_status ]->label )
			)
		);

		wp_nonce_field( 'at-archive-' . $post->ID, 'at-archive-nonce' );

		?>
<div class="at-metabox-wrap">
	<p id="at-status-current"><?php echo wp_kses_post( $current ); ?></p>
	<p>
		<label class="label" for="at-status-select"><?php esc_html_e( 'Change', 'augment-types' ); ?></label>

		<select id="at-status-select" name="<?php echo esc_attr( $classic ? 'at-post-status' : '' ); ?>">
			<option value="" selected>&mdash; <?php esc_html_e( 'Select', 'augment-types' ); ?> &mdash;</option>

			<?php foreach ( $statuses as $value => $status ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>">
					<?php echo esc_html( $status->label ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<button id="at-status-submit" type="submit" class="button"><?php esc_html_e( 'Save', 'augment-types' ); ?></button>

		<?php if ( ! $classic ) : ?>
			<input type="hidden" id="at-status-saving" name="at-post-status">
		<?php endif; ?>
	</p>
</div>
		<?php

	}


	public function save_post( $post_id, $post ) {

		if ( empty( $_POST['at-archive-nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['at-archive-nonce'], 'at-archive-' . $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! empty( $_POST['at-post-status'] ) ) {
			$saved_status = sanitize_key( $_POST['at-post-status'] );

			if ( $saved_status === $post->post_status ) {
				return;
			}

			$postarr = array(
				'ID'          => $post_id,
				'post_status' => $saved_status,
			);

			remove_action( 'save_post', array( $this, 'save_post' ) );
			wp_update_post( $postarr );
			add_action( 'save_post', array( $this, 'save_post' ) );
		}

	}


	public function scripts_styles() {

		if ( ! $this->is_valid_screen() ) {
			return;
		}

		wp_enqueue_script( 'at-archive-script', AugmentTypes::get_data( 'URL' ) . 'assets/at-archive.js', array(), AugmentTypes::get_data( 'Version' ), true );

	}


	private function is_valid_screen() {

		$screen = get_current_screen();

		return ! ( null === $screen || ! in_array( $screen->base, array( 'edit', 'post' ), true ) );

	}

}
