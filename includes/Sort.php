<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

// phpcs:disable WordPress.Security.EscapeOutput
// phpcs:disable WordPress.Security.NonceVerification

namespace AugmentTypes;

use AugmentTypes;
use WP_Query;

class Sort {

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
		add_action( 'wp_ajax_at_update_order', array( $this, 'update_order' ) );
		add_action( 'pre_get_posts', array( $this, 'set_posts_order' ) );
		add_filter( 'terms_clauses', array( $this, 'set_terms_order' ), 10, 3 );

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
			/* translators: 1: type label */
			$params['title'] = sprintf( __( 'Sort %s', 'augment-types' ), $type->label );

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
			'edit_others_posts',
			// Menu Slug
			$params['id'],
			// Content Function
			array( $this, 'create' )
		);

	}


	public function create() {

		$screen = get_current_screen();
		$type   = $screen->post_type ? $screen->post_type : 'post';
		$limit  = min( ini_get( 'max_input_vars' ), array_sum( (array) wp_count_posts( $type ) ) );

		$args = array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'no_found_rows'  => true,
			'posts_per_page' => $limit,
			'tax_query'      => array(),
		);

		if ( isset( $_GET['post_status'] ) ) {
			$args['post_status'] = sanitize_key( $_GET['post_status'] );
		}

		$post_type  = get_post_type_object( $type );
		$taxonomies = get_object_taxonomies( $type, 'objects' );

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! isset( $_GET[ $taxonomy->name ] ) ) {
				continue;
			}

			$args['tax_query'][] = array(
				'taxonomy' => $taxonomy->name,
				'terms'    => sanitize_key( $_GET[ $taxonomy->name ] ),
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
							<?php /* translators: 1: type label, 2: item count */ ?>
							<h2 class="hndle"><?php printf( __( 'Sort %1$s ( %2$s )', 'augment-types' ), $post_type->label, '<i>' . $query->post_count . '</i>' ); ?></h2>
							<div id="major-publishing-actions">
								<input id="at-save-order" type="submit" value="<?php _e( 'Update' ); ?>" class="button button-primary button-large">
								<span class="spinner"></span>
							</div>
						</div>

						<div class="postbox">
							<?php /* translators: 1: type label */ ?>
							<h2 class="hndle"><?php printf( __( 'Filter %s', 'augment-types' ), $post_type->label ); ?></h2>
							<div class="inside">
								<?php $this->filters( $type, $taxonomies ); ?>
							</div>
						</div>
					</div>

					<div id="postbox-container-2" class="postbox-container">
						<div class="at-sort-container">
							<div class="at-sort-row header">
								<span class="at-sort-column"><?php _e( 'Title' ); ?></span>
								<span class="at-sort-column column-links"><?php _e( 'Edit' ); ?></span>
								<span class="at-sort-column column-links"><?php _e( 'View' ); ?></span>
							</div>

							<div class="at-sort-body">
								<ul class="at-sort-list">
									<?php
										$walker = new Walker();
										$output = $walker->walk( $query->posts, 0, $args );

										echo wp_kses_post( $output );
									?>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php

	}


	private function filters( $type, $taxonomies ) {

		$statuses = get_post_statuses();

		$statuses['archive'] = __( 'Archived', 'augment-types' );

		$filter = isset( $_GET['post_status'] ) ? sanitize_key( $_GET['post_status'] ) : null;

		?>

		<form action="<?php echo admin_url( 'edit.php' ); ?>" id="the-filters" class="at-filters">
			<input type="hidden" name="post_type" value="<?php echo 'post' !== $type ? esc_attr( $type ) : '0'; ?>">
			<input type="hidden" name="page" value="at-sort_<?php echo esc_attr( $type ); ?>">

			<label>
				<span><?php _e( 'Status' ); ?></span>

				<select name="post_status">
					<option value="0" selected><?php _e( 'All' ); ?></option>
					<?php foreach ( $statuses as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $filter, $value ); ?>>
							<?php echo esc_html( $label ); ?>
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
					$filter  = isset( $_GET[ $name ] ) ? sanitize_key( $_GET[ $name ] ) : null;

					if ( empty( $options ) ) {
						continue;
					}
					?>

				<label>
					<span><?php echo esc_html( $taxonomy->labels->singular_name ); ?></span>

					<select name="<?php echo esc_attr( $name ); ?>">
						<option value="0" selected><?php _e( 'All' ); ?></option>
						<?php foreach ( $options as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $filter, $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endforeach; ?>

			<input type="submit" value="<?php _e( 'Submit' ); ?>" class="button button-primary button-large">
		</form>

		<?php

	}


	public function scripts_styles() {

		if ( ! $this->is_valid_screen() || ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style( 'at-sort-style', AugmentTypes::get_data( 'URL' ) . 'assets/at-sort.css', array(), AugmentTypes::get_data( 'Version' ) );
		wp_enqueue_script( 'at-sort-script', AugmentTypes::get_data( 'URL' ) . 'assets/at-sort.js', array(), AugmentTypes::get_data( 'Version' ), true );

	}


	private function is_valid_screen() {

		$screen = get_current_screen();

		if ( null !== $screen && strpos( $screen->id, '_page_at-sort_' ) !== false ) {
			return true;
		}

		if ( 'edit' !== $screen->base && 'edit-tags' !== $screen->base ) {
			return false;
		}

		if ( is_post_type_hierarchical( $screen->post_type ) || is_taxonomy_hierarchical( $screen->taxonomy ) ) {
			return false;
		}

		if ( isset( $_GET['orderby'] ) || isset( $_GET['paged'] ) ) {
			return false;
		}

		return true;

	}


	public function update_order() {

		if ( ! isset( $_POST['type'], $_POST['data'] ) ) {
			wp_die( '', 403 );
		}

		$type = sanitize_key( $_POST['type'] );
		$data = array_map( 'sanitize_text_field', $_POST['data'] );

		if ( 'posts' === $type ) {
			$this->update_posts_order( $data );
		} elseif ( 'tags' === $type ) {
			$this->update_tags_order( $data );
		} else {
			wp_die( '', 405 );
		}

	}


	public function update_posts_order( $data ) {

		global $wpdb;

		parse_str( $data['filters'], $filters );
		parse_str( $data['orders'], $orders );
		parse_str( $data['items'], $items );

		unset( $filters['post_type'] );
		unset( $filters['page'] );

		$data    = array_merge( $items, $orders );
		$filters = array_filter( $filters );
		$orders  = $data['orders'];

		if ( empty( $filters ) || empty( array_filter( $orders ) ) ) {
			$orders = array_keys( $data['items'] );
		}

		sort( $orders );

		foreach ( $data['items'] as $index => $post ) {
			$wpdb->update( $wpdb->posts, array( 'menu_order' => $orders[ $index ] ), array( 'ID' => $post ) );
		}

		wp_die();

	}


	public function update_tags_order( $data ) {

		global $wpdb;

		parse_str( $data['items'], $data );

		foreach ( $data['items'] as $index => $tag ) {
			$wpdb->update( $wpdb->terms, array( 'term_order' => $index + 1 ), array( 'term_id' => $tag ) );
		}

		wp_die();

	}


	public function set_posts_order( $query ) {

		if ( $query->get( 'orderby' ) || $query->is_search() ) {
			return $query;
		}

		$meta = array(
			'menu_order' => 'ASC',
			'post_date'  => 'DESC',
		);

		$query->set( 'orderby', $meta );

		return $query;

	}


	public function set_terms_order( $pieces, $taxonomies, $args ) {

		if ( ! ( ( is_admin() && isset( $_GET['orderby'] ) ) || ( isset( $args['ignore_term_order'] ) && true === $args['ignore_term_order'] ) ) ) {
			$pieces['orderby'] = 'ORDER BY t.term_order ASC,  t.term_id';
			$pieces['order']   = 'DESC';
		}

		return $pieces;

	}

}
