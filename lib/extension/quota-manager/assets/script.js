(
	function ( $ ) {

		// Document ready
		Hooks.add_action( 'missioncontrol_ready', function () {

			setTimeout( function () {
				$( '.notice.is-dismissible' ).fadeOut( 500 );
			}, 500 )
		} );

	}

)( jQuery );