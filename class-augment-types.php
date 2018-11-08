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
		add_action( 'pre_get_posts', array( $this, 'set_order' ) );

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
			'orderby'        => array(
				'menu_order' => 'ASC',
				'post_date'  => 'DESC'
			),
			'tax_query'      => array(),
		);

		$taxonomies = get_object_taxonomies( $this->current_type->name, 'names' );

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! isset( $_GET[ $taxonomy ] ) ) {
				continue;
			}

			$args['tax_query'][] = array(
				'taxonomy' => $taxonomy,
				'terms'    => $_GET[ $taxonomy ],
			);
		}

		$query = new WP_Query( $args );

		?>

		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="postbox-container-1" class="postbox-container">
						<div class="postbox">
							<h2 class="hndle">Type Filters</h2>
							<div class="inside">
								<?php $this->filters( $this->current_type->name ); ?>
							</div>
						</div>
					</div>

					<div id="postbox-container-2" class="postbox-container">
						<table class="wp-list-table widefat fixed striped at-sort">
							<thead>
								<tr>
									<th scope="col">Title</th>
									<th class="column-links" scope="col">Edit</th>
									<th class="column-links" scope="col">View</th>
								</tr>
							</thead>
							<tbody id="the-list">
								<?php while( $query->have_posts() ) : ?>
									<?php $query->the_post(); ?>
									<tr id="post-<?php the_ID(); ?>">
										<td><?php the_title(); ?></td>
										<td><?php printf( '<a href="%s" target="_blank">%s</a>', get_edit_post_link(), __( 'Edit' ) ); ?></td>
										<td><?php printf( '<a href="%s" target="_blank">%s</a>', get_permalink(), __( 'View' ) ); ?></td>
									</tr>
								<?php endwhile; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

		<?php

	}


	private function filters( $type ) {

		$taxonomies = get_object_taxonomies( $type, 'objects' );

		?>

		<form action="<?php echo admin_url( 'edit.php' ); ?>" id="the-filters" class="at-filters">
			<input type="hidden" name="page" value="at-sort_<?php echo $type; ?>">

			<?php foreach ( $taxonomies as $name => $taxonomy ) : ?>
				<?php
					$options = get_terms( array(
						'taxonomy' => $name,
						'fields'   => 'id=>name',
					) );

					$filter = isset( $_GET[ $name ] ) ? $_GET[ $name ] : null;
				?>

				<label>
					<span><?php echo $taxonomy->label ?></span>

					<select name="<?php echo $name; ?>">
						<option value="0" selected>Show all</option>
						<?php foreach ( $options as $value => $label ) : ?>
							<?php $selected = $filter === strval( $value ) ? ' selected' : ''; ?>
							<option value="<?php echo $value; ?>"<?php echo $selected; ?>>
								<?php echo $label; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endforeach; ?>

			<input type="submit" value="Submit" class="button button-primary button-large">
		</form>

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

		if ( 'edit' !== $screen->base ) {
			return false;
		}

		if ( isset( $_GET['orderby'] ) ) {
			return false;
		}

		return true;

	}


	public function update_order() {

		parse_str( $_POST['items'], $data );

		$order = array();

		foreach ( $data['post'] as $post ) {
			$order[] = get_post_field( 'menu_order', $post );
		}

		sort( $order );

		foreach ( $data['post'] as $index => $post ) {
			wp_update_post( array(
				'ID' => $post,
				'menu_order' => $order[ $index ],
			) );
		}

		wp_die();

	}


	public function set_order( $query ) {

		if ( $query->get( 'orderby' ) ) {
			return false;
		}

		$meta = array(
			'menu_order' => 'ASC',
			'post_date'  => 'DESC'
		);

		$query->set( 'orderby', $meta );

	}

}

// Get the Augment Types plugin running
Augment_Types::instance();
