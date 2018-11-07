<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */


class Augment_Types {

	private static $instance;
	private $current_type = null;


	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	private function __construct() {

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_styles' ) );
		add_action( 'wp_ajax_at_update_order', array( $this, 'update_order' ) );

	}


	public function init() {

		if ( empty( $_GET ) ) {
			return;
		}

		if ( ! isset( $_GET['page'] ) ) {
			return;
		}

		if ( substr( $_GET['page'], 0, 8 ) == 'at-sort_' ) {
			$this->current_type = get_post_type_object( str_replace( 'at-sort_', '', $_GET['page'] ) );
		}

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

		$args = array(
			'post_type'      =>  $this->current_type->name,
			'posts_per_page' => -1,
		);

		$query = new WP_Query( $args );

		?>

		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>

			<ul class="at-sortable">
				<?php while( $query->have_posts() ) : ?>
					<?php $query->the_post(); ?>
					<li id="post-<?php the_ID(); ?>"><?php the_title(); ?></li>
				<?php endwhile; ?>
			</ul>
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


	public function update_order() {

		parse_str( $_POST['items'], $data );

		foreach ( $data['post'] as $index => $post ) {
			wp_update_post( array(
				'ID' => $post,
				'menu_order' => $index,
			) );
		}

		wp_die();

	}

}

// Get the Augment Types plugin running
Augment_Types::instance();
