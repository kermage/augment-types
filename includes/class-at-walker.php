<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

class AT_Walker extends Walker {

	public $db_fields = array(
		'parent' => 'post_parent',
		'id'     => 'ID',
	);


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
		$template = '<tr id="post-%1$s">
			<td>%2$s</td>
			<td>%3$s</td>
			<td>%4$s</td>
		</tr>';

		$output .= sprintf(
			$template,
			$page->ID,
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

}
