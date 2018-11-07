(function( $ ) {

	'use strict';

	var $container = $( '.at-sortable' );

	if ( ! $container.length ) {
		return;
	}

	$container.sortable( {
		axis: 'y',
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

}( jQuery ));
