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

	}


	public function menu() {

		$types  = get_post_types();

		foreach( $types as $type ) {

			$params['id']     = 'at-sort_' . $type;
			$params['parent'] = 'edit.php?post_type=' . $type;

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

}

// Get the Augment Types plugin running
Augment_Types::instance();
