/* global ajaxurl */

(function( $ ) {

	'use strict';

	var $container = $( '#the-list, .at-sort-list' );
	var $filters   = $( '#the-filters' );

	function at_order_callback( $this = null ) {
		var type    = $( 'body' ).hasClass( 'edit-tags-php' ) ? 'tag' : 'post';
		var nested = [];

		$container.first().find( '.ui-sortable-handle' ).each( function() {
			nested.push( type + '[]=' + $( this ).attr( 'id' ).split( '-' ).pop() );
		});

		$.ajax( {
			type : 'POST',
			url : ajaxurl,
			data : {
				action: 'at_update_order',
				items: nested.join( '&' ),
				type: type + 's',
				filters: $filters.serialize(),
			},
			beforeSend: function() {
				if ( $this ) {
					$this.attr( 'disabled', true );
					$this.siblings( '.spinner' ).addClass( 'is-active' );
				}

				$container
					.sortable( 'disable' )
					.parents( '.at-sort-container, .wp-list-table' )
					.addClass( 'sorting' );
			},
			complete: function() {
				if ( $this ) {
					$this.attr( 'disabled', false );
					$this.siblings( '.spinner' ).removeClass( 'is-active' );
				}

				$container
					.sortable( 'enable' )
					.parents( '.at-sort-container, .wp-list-table' )
					.removeClass( 'sorting' );
			},
		});
	}

	$container.sortable( {
		axis: 'y',
		placeholder: 'ui-sortable-placeholder',
		create: function() {
			$( this ).height( $( this ).height() );

			$( document ).on( 'keydown', function( e ) {
				var key = e.key || e.keyCode;

				if ( 'Escape' === key || 27 === key ) {
					$container.sortable( 'cancel' );
				}
			});
		},
		start: function( e, ui ) {
			ui.placeholder.width( ui.item.width() );
			ui.placeholder.height( ui.item.height() );
			ui.placeholder.empty();
		},
		helper: function( e, ui ) {
			ui.children().each( function() {
				$( this ).width( $( this ).width() );
			});

			return ui;
		},
		update: function() {
			if ( $filters.length ) {
				return;
			}

			if ( ! $container.sortable( 'option', 'locked' ) ) {
				at_order_callback();
			}

			$container.sortable( 'option', 'locked', false );
		},
	} ).filter( '#the-list' ).addClass( 'at-sort-list' );

	$filters.on( 'submit', function() {
		$( this ).find( 'input, select' )
			.filter( function() {
				return ( this.value === '0' );
			})
			.prop( 'name', '' );
	} );

	$( '#at-save-order' ).on( 'click', function( e ) {
		e.preventDefault();
		at_order_callback( $( this ) );
	} );

}( jQuery ));
