(function( $ ) {

	'use strict';

	var $container = $( '#the-list' );

	if ( ! $container.length ) {
		return;
	}

	$container.sortable( {
		axis: 'y',
		containment: '.wp-list-table',
		placeholder: 'ui-sortable-placeholder',
		tolerance: 'pointer',
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
		update : function( e, ui ) {
			$.ajax( {
				type : 'POST',
				url : ajaxurl,
				data : {
					action: 'at_update_order',
					items: $container.sortable( 'serialize' ),
				},
			});
		},
	});

	var $filters = $( '#the-filters' );

	if ( ! $filters.length ) {
		return;
	}

	$filters.on( 'submit', function( e ) {
		$( this ).find( 'select' )
			.filter( function() {
				return ( this.value === '0' );
			})
			.prop( 'name', '' );
	} );

}( jQuery ));
