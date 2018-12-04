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

		echo wp_get_attachment_image( get_post_thumbnail_id( $post_ID ), array( 64, 64 ), true );

	}


	public function style() {

		?>

		<style type="text/css">
			.fixed .column-at-feature {
				width: 10%;
				text-align: center;
			}

			@media screen and ( max-width: 1100px ) and ( min-width: 782px ), ( max-width: 480px ) {
				.fixed .column-at-feature {
					width: 14%;
				}
			}
		</style>

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
