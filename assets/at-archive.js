( function( $, wp ) {

	if ( ! wp ) {
		return;
	}

	var $statusSelect = $( '#at-status-select' );
	var currentStatus = wp.data.select( 'core/editor' ).getCurrentPostAttribute( 'status' );

	wp.data.subscribe( function () {
		var isSavingPost = wp.data.select( 'core/editor' ).isSavingPost();
		var isAutosavingPost = wp.data.select( 'core/editor' ).isAutosavingPost();
		var newStatus = wp.data.select( 'core/editor' ).getCurrentPostAttribute( 'status' );

		if ( isSavingPost && ! isAutosavingPost && $statusSelect.val() && currentStatus !== newStatus ) {
			var $selectedOption = $statusSelect.find( 'option:selected' );

			$( '#at-status-current strong' ).html( $selectedOption.text() );

			currentStatus = newStatus;
		}
	} );

	$( '#at-status-submit' ).on( 'click', function() {
		wp.data.dispatch( 'core/editor' ).editPost( {
			status: $statusSelect.val()
		} );

		wp.data.dispatch( 'core/editor' ).savePost();
	} );

} )( jQuery, window.wp );
