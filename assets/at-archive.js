( function( $, wp ) {
	if ( ! wp ) {
		return;
	}

	wp.data.subscribe( function () {
		var isSavingPost = wp.data.select('core/editor').isSavingPost();
		var isAutosavingPost = wp.data.select('core/editor').isAutosavingPost();
		var $statusSelect = $( '#at-status-select' );
		var $selectedOption = $statusSelect.find( 'option:selected' );

		if ( isSavingPost && ! isAutosavingPost && $statusSelect.val() && $selectedOption.length ) {
			$( '#at-status-current strong' ).html( $selectedOption.text() );
			$statusSelect.val( '' );
		}
	} );

} )( jQuery, window.wp );
