<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */


class AT_Feature {

	private static $instance;


	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		add_filter( 'manage_posts_columns', array( $this, 'header' ) );
		add_filter( 'manage_page_posts_columns', array( $this, 'header' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'content' ), 10, 2 );
		add_action( 'manage_page_posts_custom_column', array( $this, 'content' ), 10, 2 );
		add_action( 'admin_footer-edit.php', array( $this, 'style' ) );
		add_action( 'quick_edit_custom_box', array( $this, 'form' ), 10, 2 );
		add_action( 'load-edit.php', array( $this, 'page' ) );

	}


	public function header( $columns ) {

		$columns['at-feature'] = __( 'Featured Image', 'augment-types' );

		return $columns;

	}


	public function content( $column, $post_ID ) {

		if ( 'at-feature' !== $column ) {
			return;
		}

		$thumbnail_id = get_post_thumbnail_id( $post_ID );

		echo wp_get_attachment_image( $thumbnail_id, 'thumbnail', true, array( 'data-id' => $thumbnail_id ) );

	}


	public function style() {

		?>

		<style type="text/css">
			.fixed .column-at-feature {
				width: 10%;
				text-align: center;
			}

			.column-at-feature img {
				max-width: 64px;
				width: 100%;
				height: auto;
			}

			@media screen and ( max-width: 1100px ) and ( min-width: 782px ), ( max-width: 480px ) {
				.fixed .column-at-feature {
					width: 14%;
				}
			}

			.inline-edit-col .at-feature-image {
				margin-top: 0.8em;
			}
		</style>

		<script type="text/javascript">
			jQuery( function( $ ) {
				var $wp_inline_edit = inlineEditPost.edit;

				inlineEditPost.edit = function( id ) {
					$wp_inline_edit.apply( this, arguments );

					var $post_id = 0;

					if ( 'object' === typeof( id ) ) {
						$post_id = parseInt( this.getId( id ) );
					}

					if ( ! $post_id ) {
						return;
					}

					var $edit_row = $( '#edit-' + $post_id );
					var $post_row = $( '#post-' + $post_id );
					var $featured_image = $( '.column-at-feature', $post_row ).find( 'img' );

					$( '.at-feature-image', $edit_row ).html( $featured_image.clone() );
				};
			} );
		</script>

		<?php

	}


	public function form( $column, $type ) {

		if ( 'at-feature' !== $column ) {
			return;
		}

		$template = '<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<span class="title">%1$s</span>
				<div class="at-feature-image"></div>
			</div>
		</fieldset>';

		$title  = __( 'Featured Image', 'augment-types' );

		printf( $template, esc_html( $title ) );

	}


	public function page() {

		wp_enqueue_media();

	}

}
