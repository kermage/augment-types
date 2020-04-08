/* global ajaxurl */

(function( $ ) {

	'use strict';

	var $container = $( '.at-sort-list' );
	var $filters   = $( '#the-filters' );


	function at_order_callback( $this = null ) {
		var nested = [];

		nested.push( $container.sortable( 'serialize' ) );
		$container.find( '.at-sort-list' ).each( function() {
			nested.push( $( this ).sortable( 'serialize' ) );
		});

		$.ajax( {
			type : 'POST',
			url : ajaxurl,
			data : {
				action: 'at_update_order',
				items: nested.join( '&' ),
				type: 'posts',
			},
			beforeSend: function() {
				if ( $this ) {
					$this.attr( 'disabled', true );
					$this.siblings( '.spinner' ).addClass( 'is-active' );
				}

				$container
					.sortable( 'disable' )
					.parents( '.at-sort-container' )
					.addClass( 'sorting' );
			},
			complete: function() {
				if ( $this ) {
					$this.attr( 'disabled', false );
					$this.siblings( '.spinner' ).removeClass( 'is-active' );
				}

				$container
					.sortable( 'enable' )
					.parents( '.at-sort-container' )
					.removeClass( 'sorting' );
			},
		});
	}

	$container.sortable( {
		axis: 'y',
		containment: '.at-sort-list',
		placeholder: 'ui-sortable-placeholder',
		tolerance: 'pointer',
		create: function() {
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
	});

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
