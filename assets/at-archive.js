( function( $, wp ) {

	if ( ! wp ) {
		return;
	}

	var $statusSelect = $( '#at-status-select' );
	var $statusSaving = $( '#at-status-saving' );
	var currentStatus = '';

	wp.data.subscribe( function () {
		var isSavingPost = wp.data.select( 'core/editor' ).isSavingPost();
		var isAutosavingPost = wp.data.select( 'core/editor' ).isAutosavingPost();
		var newStatus = wp.data.select( 'core/editor' ).getCurrentPostAttribute( 'status' );

		if ( isSavingPost && ! isAutosavingPost && currentStatus !== newStatus ) {
			var $selectedOption = $statusSelect.find( 'option[value=' + newStatus + ']' );

			$statusSelect.val( '' );
			$statusSaving.val( '' );
			$( '#at-status-current strong' ).html( $selectedOption.text() );

			currentStatus = newStatus;
		}
	} );

	$( '#at-status-submit' ).on( 'click', function() {
		var savingValue = $statusSelect.val();

		if ( ! savingValue ) {
			return;
		}

		$statusSaving.val( savingValue );

		currentStatus = wp.data.select( 'core/editor' ).getCurrentPostAttribute( 'status' );

		wp.data.dispatch( 'core/editor' ).editPost( {
			status: savingValue
		} );

		wp.data.dispatch( 'core/editor' ).savePost();
	} );

} )( jQuery, window.wp );
