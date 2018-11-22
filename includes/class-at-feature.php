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

		if ( $thumbnail_id ) {
			echo wp_get_attachment_image( $thumbnail_id, 'thumbnail' );
		} else {
			echo '<img src="' . esc_attr( includes_url( '/images/media/' ) ) . 'default.png" />';
		}

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

}
