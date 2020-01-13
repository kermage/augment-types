/* global wp inlineEditPost */

(function( $ ) {

	'use strict';

	$( 'body' ).on( 'click', '.at-feature .editinline', function( e ) {
		e.preventDefault();
	});

	$( 'body' ).on( 'click', '.at-feature-set', function( e ) {
		e.preventDefault();

		var $this       = $( this );
		var media_frame = wp.media( {
			title: 'Featured Image',
			library : { type : 'image' },
			button: { text: 'Set featured image' },
		});

		media_frame.on( 'select', function() {
			var selection = media_frame.state().get( 'selection' ).first().toJSON();
			var img_size  = selection.sizes.thumbnail;

			if ( img_size === undefined ) {
				img_size = selection.sizes.full;
			}

			$this.html( '<img src="' + img_size.url + '" />' )
				.next().val( selection.id )
				.next().show();
		});

		media_frame.on( 'open', function() {
			var selection = media_frame.state().get( 'selection' );
			var selected  = $this.next().val();

			if ( selected ) {
				selection.add( wp.media.attachment( selected ) );
			}
		});

		media_frame.open();
	});

	$( 'body' ).on( 'click', '.at-feature-remove', function( e ) {
		e.preventDefault();

		$( this ).hide()
			.prev().val( '-1' )
			.prev().html( 'Set featured image' );
	});

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

		var $edit_row          = $( '#edit-' + $post_id );
		var $post_row          = $( '#post-' + $post_id );
		var $featured_image    = $( '.column-at-feature', $post_row ).find( 'img' );
		var $featured_image_id = $featured_image.attr( 'data-id' );

		if ( $featured_image_id ) {
			$( ':input[name="_thumbnail_id"]', $edit_row ).val( $featured_image_id );
			$( '.at-feature-set', $edit_row ).html( $featured_image.clone() );
			$( '.at-feature-remove', $edit_row ).show();
		} else {
			$( '.at-feature-remove', $edit_row ).hide();
		}
	};

}( jQuery ));
