/* global ajaxurl */

(function( $ ) {

	'use strict';

	var $container = $( '#the-list, .at-sort-list' );
	var $filters   = $( '#the-filters' );

	function at_order_callback( $this = null ) {
		var type = $( 'body' ).hasClass( 'edit-tags-php' ) ? 'tags' : 'posts';
		var data = {
			filters: $filters.serialize(),
			items: '',
			orders: '',
		};
		var deferreds = [];

		$container.each( function() {
			data.items = '';
			data.orders = '';

			$( this ).find( '> .ui-sortable-handle' ).each( function( index ) {
				data.items += '&items[]=' + $( this ).attr( 'id' ).split( '-' ).pop();
				data.orders += '&orders[]=' + ( 'posts' ===  type ? $( this ).data( 'order' ) : index );
			});

			deferreds.push( at_post_ajax( type, data, $this ) );
		});

		$.when.apply( 0, deferreds ).done( function() {
			window.location.reload();
		});
	}

	function at_post_ajax( type, data, $this ) {
		return $.ajax( {
			type : 'POST',
			url : ajaxurl,
			data : {
				action: 'at_update_order',
				type: type,
				data: data,
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
					$container.sortable( 'option', 'locked', true );
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
