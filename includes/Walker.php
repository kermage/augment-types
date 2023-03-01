<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

namespace AugmentTypes;

use Walker as CoreWalker;

class Walker extends CoreWalker {

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


	public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

		$indent = '';

		if ( $depth ) {
			$indent = str_repeat( '&mdash; ', $depth );
		}

		if ( '' === $object->post_title ) {
			/* translators: %d: ID of a post. */
			$object->post_title = sprintf( __( '#%d (no title)' ), $object->ID );
		}

		$p_title = $indent . $object->post_title;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$p_title .= isset( $_GET['post_status'] ) ? '' : _post_states( get_post( $object->ID ), false );
		$ev_tmpl  = '<a href="%s" target="_blank">%s</a>';
		$template = '<li id="post-%1$s" class="at-sort-row" data-order="%2$s">
			<span class="at-sort-column">%3$s</span>
			<span class="at-sort-column column-links">%4$s</span>
			<span class="at-sort-column column-links">%5$s</span>';

		$output .= sprintf(
			$template,
			$object->ID,
			$object->menu_order,
			$p_title,
			sprintf(
				$ev_tmpl,
				get_edit_post_link( $object->ID ),
				__( 'Edit' )
			),
			sprintf(
				$ev_tmpl,
				get_permalink( $object->ID ),
				__( 'View' )
			)
		);

	}


	public function end_el( &$output, $object, $depth = 0, $args = array() ) {

		$output .= '</li>';

	}

}
