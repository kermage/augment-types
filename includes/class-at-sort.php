<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

// phpcs:disable WordPress.Security.EscapeOutput
// phpcs:disable WordPress.Security.NonceVerification

class AT_Sort {

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

		if ( substr( $_GET['page'], 0, 8 ) === 'at-sort_' ) {
			$this->current_type = get_post_type_object( str_replace( 'at-sort_', '', $_GET['page'] ) );
		}

	}


	public function menu() {

		$args  = array( 'show_ui' => true );
		$types = get_post_types( $args, 'objects' );

		foreach ( $types as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}

			$params['id']     = 'at-sort_' . $type->name;
			$params['parent'] = 'edit.php';
			$params['title']  = sprintf( __( 'Sort %s', 'augment-types' ), $type->label );

			if ( 'post' !== $type->name ) {
				$params['parent'] .= '?post_type=' . $type->name;
			}

			$this->page( $params );
		}

	}


	private function page( $params ) {

		add_submenu_page(
			// Parent Slug
			$params['parent'],
			// Page Title
			$params['title'],
			// Menu Title
			$params['title'],
			// Capability
			'manage_options',
			// Menu Slug
			$params['id'],
			// Content Function
			array( $this, 'create' )
		);

	}


	public function create() {

		$type = $this->current_type->name;
		$args = array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'DESC',
				'post_date'  => 'DESC',
			),
			'tax_query'      => array(),
		);

		if ( isset( $_GET['post_status'] ) ) {
			$args['post_status'] = $_GET['post_status'];
		}

		$taxonomies = get_object_taxonomies( $type, 'names' );

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
							<h2 class="hndle"><?php _e( 'Type Filters', 'augment-types' ); ?></h2>
							<div class="inside">
								<?php $this->filters( $type ); ?>
							</div>
						</div>
					</div>

					<div id="postbox-container-2" class="postbox-container">
						<table class="wp-list-table widefat fixed striped at-sort">
							<thead>
								<tr>
									<th class="column-links" scope="col"><?php _e( 'Order' ); ?></th>
									<th scope="col"><?php _e( 'Title' ); ?></th>
									<th class="column-links" scope="col"><?php _e( 'Edit' ); ?></th>
									<th class="column-links" scope="col"><?php _e( 'View' ); ?></th>
								</tr>
							</thead>
							<tbody id="the-list">
								<?php while ( $query->have_posts() ) : ?>
									<?php $query->the_post(); ?>
									<tr id="post-<?php the_ID(); ?>">
										<td><?php echo get_post_field( 'menu_order', get_the_ID() ); ?></td>
										<td><?php the_title() . isset( $_GET['post_status'] ) ? '' : _post_states( get_post() ); ?></td>
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
		$statuses   = get_post_statuses();

		$statuses['archive'] = __( 'Archived', 'augment-types' );

		$filter = isset( $_GET['post_status'] ) ? $_GET['post_status'] : null;

		?>

		<form action="<?php echo admin_url( 'edit.php' ); ?>" id="the-filters" class="at-filters">
			<input type="hidden" name="post_type" value="<?php echo 'post' !== $type ? $type : '0'; ?>">
			<input type="hidden" name="page" value="at-sort_<?php echo $type; ?>">

			<label>
				<span><?php _e( 'Status' ); ?></span>

				<select name="post_status">
					<option value="0" selected><?php _e( 'All' ); ?></option>
					<?php foreach ( $statuses as $value => $label ) : ?>
						<?php $selected = $filter === $value ? ' selected' : ''; ?>
						<option value="<?php echo $value; ?>"<?php echo $selected; ?>>
							<?php echo $label; ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<?php foreach ( $taxonomies as $name => $taxonomy ) : ?>
				<?php
					$args    = array(
						'taxonomy' => $name,
						'fields'   => 'id=>name',
					);
					$options = get_terms( $args );
					$filter  = isset( $_GET[ $name ] ) ? $_GET[ $name ] : null;

					if ( empty( $options ) ) {
						continue;
					}
				?>

				<label>
					<span><?php echo $taxonomy->labels->singular_name; ?></span>

					<select name="<?php echo $name; ?>">
						<option value="0" selected><?php _e( 'All' ); ?></option>
						<?php foreach ( $options as $value => $label ) : ?>
							<?php $selected = strval( $value ) === $filter ? ' selected' : ''; ?>
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
		wp_enqueue_style( 'at-sort-style', AT_URL . 'assets/at-sort.css', array(), AT_VERSION );
		wp_enqueue_script( 'at-sort-script', AT_URL . 'assets/at-sort.js', array(), AT_VERSION, true );

	}


	private function is_valid_screen() {

		$screen = get_current_screen();

		if ( strpos( $screen->id, '_page_at-sort_' ) !== false ) {
			return true;
		}

		if ( 'edit' !== $screen->base ) {
			return false;
		}

		if ( is_post_type_hierarchical( $screen->post_type ) ) {
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

		rsort( $order );

		$temp = $order;
		$temp = array_filter( $temp );
		$temp = array_unique( $temp );

		if ( ( count( $data['post'] ) - 1 ) > count( $temp ) ) {
			$order = array_keys( $data['post'] );
		}

		foreach ( $data['post'] as $index => $post ) {
			$args = array(
				'ID'         => $post,
				'menu_order' => $order[ $index ],
			);

			wp_update_post( $args );
		}

		wp_die();

	}


	public function set_order( $query ) {

		if ( $query->get( 'orderby' ) ) {
			return false;
		}

		$meta = array(
			'menu_order' => 'DESC',
			'post_date'  => 'DESC',
		);

		$query->set( 'orderby', $meta );

	}

}
