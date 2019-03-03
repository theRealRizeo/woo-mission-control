var MissionControl = MissionControl || {};


(
	function ( $ ) {

		// Document ready
		Hooks.add_action( 'missioncontrol_ready', function () {

			$( '.select-all' ).on( 'click', function( e ) {

				var checkboxes = $( e.currentTarget ).parents( 'td' ).find( 'input[type="checkbox"]' );

				$( checkboxes ).each( function( idx, cbox ) {
					$( cbox ).prop( 'checked', true );
				} );

			} );

			$( '.deselect-all' ).on( 'click', function( e ) {

				var checkboxes = $( e.currentTarget ).parents( 'td' ).find( 'input[type="checkbox"]' );

				$( checkboxes ).each( function( idx, cbox ) {
					$( cbox ).prop( 'checked', false );
				} );

			} );

			$( '.copy-above' ).on( 'click', function( e ) {

				var checkboxes = $( e.currentTarget ).parents( 'td' ).find( 'input[type="checkbox"]' );
				var row = $( $( e.currentTarget ).parents( 'tr' )[0] ).attr( 'data-row' );
				var col = $( $( e.currentTarget ).parents( 'td' )[0] ).attr( 'data-col' );
				var toCopy = $( '[data-row="' + ( row - 1 ) + '"] [data-col="' + col + '"] input[type="checkbox"]' );
				console.log( e.currentTarget );
				$( checkboxes ).each( function( idx, cbox ) {
					$( cbox ).prop( 'checked', $( toCopy[ idx ] ).prop( 'checked' ) );
				} );

			} );

			$( '.notice.is-dismissible' ).css( {
				'margin-left': '0',
				'z-index': '100',
				'position': 'absolute',
				'width': $( '.widefat' ).width()
			} );

			setTimeout( function () {
				$( '.notice.is-dismissible' ).fadeOut( 500 );
			}, 500 )

		} );

	}
)( jQuery );