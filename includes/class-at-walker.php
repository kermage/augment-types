<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

namespace AugmentTypes;

class AT_Walker extends \Walker {

	public $db_fields = array(
		'parent' => 'post_parent',
		'id'     => 'ID',
	);


	public function start_lvl( &$output, $depth = 0, $args = array() ) {

		$output .= '<ul class="at-sort-list">';

	}


	public function end_lvl( &$output, $depth = 0, $args = array() ) {

		$output .= '</ul>';

	}


	public function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {

		$indent = '';

		if ( $depth ) {
			$indent = str_repeat( '&mdash; ', $depth );
		}

		if ( '' === $page->post_title ) {
			/* translators: %d: ID of a post. */
			$page->post_title = sprintf( __( '#%d (no title)' ), $page->ID );
		}

		$p_title  = $indent . $page->post_title;
		$p_title .= isset( $_GET['post_status'] ) ? '' : _post_states( get_post( $page->ID ), false );
		$ev_tmpl  = '<a href="%s" target="_blank">%s</a>';
		$template = '<li id="post-%1$s" class="at-sort-row" data-order="%2$s">
			<span class="at-sort-column">%3$s</span>
			<span class="at-sort-column column-links">%4$s</span>
			<span class="at-sort-column column-links">%5$s</span>';

		$output .= sprintf(
			$template,
			$page->ID,
			$page->menu_order,
			$p_title,
			sprintf(
				$ev_tmpl,
				get_edit_post_link( $page->ID ),
				__( 'Edit' )
			),
			sprintf(
				$ev_tmpl,
				get_permalink( $page->ID ),
				__( 'View' )
			)
		);

	}


	public function end_el( &$output, $page, $depth = 0, $args = array() ) {

		$output .= '</li>';

	}

}
