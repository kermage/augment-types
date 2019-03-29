/* global ajaxurl */

(function( $ ) {

	'use strict';

	var $container = $( '#the-list' );
	var $filters   = $( '#the-filters' );


	function at_order_callback() {
		$.ajax( {
			type : 'POST',
			url : ajaxurl,
			data : {
				action: 'at_update_order',
				items: $container.sortable( 'serialize' ),
			},
			beforeSend: function() {
				$container
					.sortable( 'disable' )
					.parents( '.wp-list-table' )
					.addClass( 'sorting' );
			},
			complete: function() {
				$container
					.sortable( 'enable' )
					.parents( '.wp-list-table' )
					.removeClass( 'sorting' );
			},
		});
	}


	$container.sortable( {
		axis: 'y',
		containment: '.wp-list-table',
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
	});


	$filters.on( 'submit', function() {
		$( this ).find( 'input, select' )
			.filter( function() {
				return ( this.value === '0' );
			})
			.prop( 'name', '' );

		at_order_callback();
	} );

}( jQuery ));
