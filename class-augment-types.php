<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */


class Augment_Types {

	private static $instance;


	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_styles' ) );

	}


	public function menu() {

		$types  = get_post_types();

		foreach( $types as $type ) {

			if ( $type == 'attachment' ) {
				continue;
			}

			$params['id']     = 'at-sort_' . $type;
			$params['parent'] = 'edit.php';

			if ( $type !== 'post' ) {
				$params['parent'] .= '?post_type=' . $type;
			}

			$this->page( $params );

		}

	}


	private function page( $params ) {

		add_submenu_page(
			// Parent Slug
			$params['parent'],
			// Page Title
			__( 'Sort Types', 'augment-types' ),
			// Menu Title
			__( 'Sort Types', 'augment-types' ),
			// Capability
			'manage_options',
			// Menu Slug
			$params['id'],
			// Content Function
			array( $this, 'create' )
		);

	}


	public function create() {

		?>

		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>
		</div>

		<?php

	}


	public function scripts_styles() {

		if ( ! $this->is_valid_screen() ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style( 'at-style', AT_URL . 'assets/augment-types.css', array(), AT_VERSION );
		wp_enqueue_script( 'at-script', AT_URL . 'assets/augment-types.js', array(), AT_VERSION, true );

	}


	private function is_valid_screen() {

		$screen = get_current_screen();

		if ( strpos( $screen->id, '_page_at-sort_' ) !== false ) {
			return true;
		}

		return false;

	}

}

// Get the Augment Types plugin running
Augment_Types::instance();
