var MissionControl = MissionControl || {};

(
	function ( $ ) {


		// Document ready
		Hooks.add_action( 'missioncontrol_ready', function () {

			// Level Settings Page
			var extensions_element = '#extensions-list-page';
			var levels_page = $( extensions_element );
			if ( levels_page.length > 0 ) {
				Hooks.do_action( 'MissionControl\\Extension\\list_page' );
			}

		} );

		// Hook the Extension Settings page
		Hooks.add_action( 'MissionControl\\Extension\\list_page', function () {
			MissionControl.api.request( 'GET', 'extensions', false, function ( response ) {
				var content = '';
				$.each( response, function ( index, item ) {

					var enabled = "enabled" === item.status,
						status_class = enabled ? 'active' : 'inactive',
						status_label = enabled ? _MissionControl_Extensions.labels.deactivate : _MissionControl_Extensions.labels.activate,
						settings_link = typeof item.settings_url !== "undefined" && item.settings_url != '' ? item.settings_url : '';

					status_class = "error" === item.status ? 'disabled' : status_class;

					settings_link = settings_link != '' && enabled ? '<a href="' + settings_link + '">' + _MissionControl_Extensions.labels.settings + '</a>' : '';

					content += '<div class="extension-container" data-id="' + item.slug + '">' +
					           '    <h2>' + item.name + '</h2>' +
					           '    <img src="' + item.thumb + '" alt="' + item.name + ' thumbnail" />' +
					           '    <p class="description">' + item.description + '</p>' +
					           '    <div class="extension-foot">' +
					           '        <input class="button ' + status_class + '" data-id="' + item.slug + '" type="button" value="' + status_label + '" />' +
					           settings_link +
					           '    </div>' +
					           '</div>';
				} );

				$( '#extensions-list-page' ).empty();
				$( '#extensions-list-page' ).append( content );

				$( '#extensions-list-page [type="button"]' ).on( 'click', function ( e ) {

					var button = e.currentTarget,
						module = $( button ).attr( 'data-id' ),
						data = {
							module: module
						};


					// Activate or deactivate
					MissionControl.api.request( 'POST', 'extensions', data, function ( response ) {
						var enabled = 'enabled' === response.status;
						var label = enabled ? _MissionControl_Extensions.labels.deactivate : _MissionControl_Extensions.labels.activate;
						var status_class = enabled ? 'active' : 'inactive';

						// Update Button
						$( button ).removeClass( 'active' );
						$( button ).removeClass( 'inactive' );
						$( button ).addClass( status_class );

						$( button ).val( label );
						$( button ).blur();

						// Update Menu
						var parent = $( '[id="toplevel_page_missioncontrol_main"]' )[0],
							menu_ul = $( parent ).find( 'ul.wp-submenu' )[0],
							menu_item = false;

						if ( typeof response.module.menu_slug !== "undefined" && response.module.menu_slug != '' ) {
							menu_item = $( menu_ul ).find( '[href*="' + response.module.menu_slug + '"]' )[0];
						}

						// Get the right footer
						var footer = $( '[data-id="' + response.module.slug + '"]' ).parents('.extension-foot')[0];

						// Remove settings link
						var link = $( $( footer ).find( 'a' )[0] );

						$( link ).detach();

						if ( enabled && typeof response.module.settings_url !== "undefined" ) {

							// Add menu item if it does not exist
							if ( typeof menu_item === 'undefined' || false === menu_item ) {
								var item_content = '<li><a href="' + response.module.settings_url + '">' + response.module.name + '</a></li>';
								$( menu_ul ).append( item_content );
							}

							$( footer ).append( '<a href="' + response.module.settings_url + '">' + _MissionControl_Extensions.labels.settings + '</a>' );

						} else {
							// Remove menu item if it exists
							if ( typeof menu_item !== 'undefined' && false !== menu_item ) {
								$( menu_item ).detach();
//								$( $( footer ).find( 'a' )[0] ).detach();
							}
						}

					} );

				} );

			} );

		} );


	}
)( jQuery );
