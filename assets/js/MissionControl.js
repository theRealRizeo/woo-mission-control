/**
 * Create `MissionControl` object
 */
var MissionControl = MissionControl || {};

/**
 * Create `api` object
 */
MissionControl.api = MissionControl.api || {};

(
	function ( $ ) {

		/**
		 * Create API `request` method
		 *
		 * @param method
		 * @param endpoint
		 * @param data
		 * @param callback
		 * @param cookie_auth
		 */
		MissionControl.api.request = function ( method, endpoint, data, callback, cookie_auth ) {

			if ( typeof method === "undefined" || typeof endpoint === "undefined" || typeof callback === "undefined" ) {
				return;
			}

			/**
			 * By default we will use Cookie Authentication
			 * @see: http://v2.wp-api.org/guide/authentication/
 			 */
			if( typeof cookie_auth === "undefined" ) {
				cookie_auth = true;
			}

			$.ajax( {
				url: MissionControl.api.base + endpoint,
				method: method,
				beforeSend: function ( xhr ) {
					if( cookie_auth ) {
						xhr.setRequestHeader( 'X-WP-Nonce', MissionControl.api.nonce );
					}
				},
				data: data
			} ).done( function ( response ) {
				if ( typeof callback === "function" ) {
					callback( response );
				}
			} );
		};

		var _mc_runtime = {};

		_mc_runtime.handle_response = function( response ) {
			console.log( response );
		};

		$( document ).ready( function ( $ ) {

			Hooks.do_action( 'missioncontrol_ready' );
//			MissionControl.api.request( 'GET', 'levels', false, _mc_runtime.handle_response );

		} );

	}
)( jQuery );

