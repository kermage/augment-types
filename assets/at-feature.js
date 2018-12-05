/* global wp inlineEditPost */

(function( $ ) {

	'use strict';

	var $wp_inline_edit = inlineEditPost.edit;


	inlineEditPost.edit = function( id ) {
		$wp_inline_edit.apply( this, arguments );

		var $post_id = 0;

		if ( 'object' === typeof( id ) ) {
			$post_id = parseInt( this.getId( id ) );
		}

		if ( ! $post_id ) {
			return;
		}

		var $edit_row = $( '#edit-' + $post_id );
		var $post_row = $( '#post-' + $post_id );
		var $featured_image = $( '.column-at-feature', $post_row ).find( 'img' );

		$( '.at-feature-image', $edit_row ).html( $featured_image.clone() );
	};

}( jQuery ));
