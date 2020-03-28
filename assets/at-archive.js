( function( $, wp ) {
	if ( ! wp ) {
		return;
	}

	wp.data.subscribe( function () {
		var isSavingPost = wp.data.select('core/editor').isSavingPost();
		var isAutosavingPost = wp.data.select('core/editor').isAutosavingPost();
		var $selectedOption = $( '#at-status-select option:selected' );

		if ( isSavingPost && ! isAutosavingPost && $selectedOption.length ) {
			$( '#at-status-current strong' ).html( $selectedOption.text() );
		}
	} );

} )( jQuery, window.wp );
