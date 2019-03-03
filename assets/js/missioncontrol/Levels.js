var MissionControl = MissionControl || {};

(
	function ( $ ) {

		var levels;
		var levels_element;
		var edit_site_level_element;

		function init_screen( response ) {
			levels = response;
			render_levels();
		}

		function render_levels() {

			var footerScript = '<script type="text/javascript">';
			footerScript += 'jQuery(function($) {';
			footerScript += '$(document).ready(function() {';
			var content = '<table class="levels-table widefat" cellspacing="0">' +
			              '<thead>' +
			              '<tr>' +
						  '     <th class="name">' + _MissionControl_Levels.labels.col_name + '</th>' +
						  '     <th class="subscription">' + _MissionControl_Levels.labels.col_subscription + '</th>' +
			              '     <th class="description">' + _MissionControl_Levels.labels.col_description + '</th>' +
			              '</tr>' +
			              '</thead>' +
			              '<tbody>';

			var counter = 0;
			$.each( levels, function ( index, item ) {
				counter += 1;
				var cssClass = counter % 2 > 0 ? 'class="alternate"' : '';
				content += '<tr id="level-' + index + '">' +
						   '<td ' + cssClass + '><input type="text" value="' + item.name + '" /><span class="level-slug">[ ' + index + ' ]</span></td>' +
						   '<td ' + cssClass + '><select class="select-level-' + index + '">' + _MissionControl_Levels.inputs.subscriptions + '</select></td>' +
				           '<td ' + cssClass + '><textarea>' + item.description + '</textarea></td>' +
				           '<td ' + cssClass + '><button class="button-delete-level">&times;</button></td>' +
						   '</tr>';
				footerScript += '$(".select-level-' + index + '").val("'+item.subscription+'").prop("selected", true);';

			} );
			footerScript += '});';
			footerScript += '});';
			content += '</tbody><table>';
			footerScript += '</script>';
			content += footerScript;

			$( levels_element ).empty();
			$( levels_element ).append( content );

			$( levels_element + ' .levels-table tbody' ).sortable( {
				opacity: 0.7,
				cursor: 'pointer',
				axis: 'y',
				placeholder: "level-placeholder",
				update: function ( e, ui ) {
					var order = $( levels_element + ' .levels-table tbody' ).sortable( "toArray" );
					var new_array = {};
					$.each( order, function ( index, item ) {
						var pos = item.replace( 'level-', '' );
						new_array[pos] = levels[pos];
					} );
					levels = new_array;
					render_levels();
				},
				helper: function ( event ) {
					var x = event.clientX, y = event.clientY,
						el = document.elementFromPoint( x, y ),
						parent = $( el ).parents( 'tr' )[0],
						helper = $( parent ).clone(),
						width = $( this ).width();
					$( helper ).css( {'width': width, 'display': 'block', background: 'rgba(0,0,0,0.05)', 'border-radius': 'none'} );
					$( $( helper ).children()[0] ).css( {'width': width} );
					return helper;
				}
			} );

			render_ui();
		}

		function handle_save( response ) {
			var content = '<div class="updated notice is-dismissible">' +
			              '     <p>' + _MissionControl_Levels.labels.levels_updated + '</p>' +
			              '</div>';
			$( '.level-notices' ).empty();
			$( '.level-notices' ).append( content );
			$( '#levels-settings-page' ).append( content );
			$( '.level-notices .notice' ).css( {
				'margin-left': '0',
				'z-index': '100',
				'position': 'absolute',
				'width': $( '.levels-table' ).width()
			} );
			setTimeout( function () {
				$( '.updated.notice' ).fadeOut( 500 );
			}, 500 )
		}

		function render_ui() {

			var content = '';

			content += '<p class="description">' + _MissionControl_Levels.labels.reorder_warning + '</p>';
			content += '<input type="button" class="button button-primary button-save-levels" value="' + _MissionControl_Levels.labels.save_levels + '" />';
			content += '<input type="button" class="button button-secondary button-add-level" value="' + _MissionControl_Levels.labels.new_level + '" />';

			$( levels_element ).append( content );

			$( levels_element + ' .button-save-levels' ).on( 'click', function ( e ) {
				MissionControl.api.request( 'POST', 'levels', levels, handle_save );

			} );

			$( levels_element + ' .button-add-level' ).on( 'click', function ( e ) {

				var slug = make_slug( _MissionControl_Levels.labels.untitled_name );
				levels[ slug ] = {
					name: _MissionControl_Levels.labels.untitled_name,
					subscription: 0,
					description: _MissionControl_Levels.labels.untitled_description
				};

				render_levels();

			} );

			$( levels_element + ' .button-delete-level' ).on( 'click', function ( e ) {
				if ( confirm( _MissionControl_Levels.labels.level_delete_confirm ) ) {
					var item = $( $( this ).parents( 'tr' )[0] ).attr( 'id' ).replace( 'level-', '' );
					delete levels[item];
//					var new_array = {};
//					var counter = 0;
//					$.each( levels, function ( index, item ) {
//						counter += 1;
//						new_array[counter] = item;
//					} );
//					levels = new_array;
					render_levels();
				}
			} );

			$( levels_element + ' input[type="text"], ' + levels_element + ' textarea' ).on( 'keyup', handle_keyup );
			$( levels_element + ' select' ).on( 'change', handle_subscriptions );

		}

		function handle_keyup( e ) {
			var el = e.currentTarget;
			var parent = $( this ).parents( 'tr' )[0];
			var item = $( parent ).attr( 'id' ).replace( 'level-', '' );
			var field = 'input' == el.nodeName.toLowerCase() ? 'name' : 'description';

			levels[item][field] = $( el ).val();

			// If the name changes, we need to change the slug
			if( field === 'name' ) {
				var slug = make_slug( $( el ).val() );

				$( $( parent ).find( '.level-slug' )[0] ).empty();
				$( $( parent ).find( '.level-slug' )[0] ).append( '[ ' + slug + ' ]' );

				$( parent ).attr( 'id', 'level-' + slug );

				// But don't forget to update the levels
				var level_keys = _.keys( levels );
				var new_array = {};
				$.each( level_keys, function( lindex, lkey ) {
					if( lkey === item ) {
						new_array[slug] = levels[lkey];
					} else {
						new_array[lkey] = levels[lkey];
					}
				} );
				levels = new_array;
			}
		}

		function handle_subscriptions( e ) {
			var el = e.currentTarget;
			var parent = $( this ).parents( 'tr' )[0];
			var item = $( parent ).attr( 'id' ).replace( 'level-', '' );
			levels[item]['subscription'] = $( el ).val();
		}

		function make_slug( value ) {

			return value.replace( /\s|\W/g, '_', value ).toLowerCase();

		}


		function init_site_edit_screen( response ) {

			var current_level = response.level_details.level;
			var revert_level = response.level_details.revert_level;
			var level_can_expire = response.level_details.can_expire;
			var expiry_date = response.level_details.expiry_date;

			var el = $( '#edit-site-level-page .init-element' );
			if ( el.length > 0 ) {
				el.detach();
			}
			el = $( '#edit-site-level-page .level-settings-metabox' );
			if ( el.length > 0 ) {
				el.detach();
			}

			var content = '<div class="level-settings-metabox">';

			// Current Level
			content += '<div>' + _MissionControl_Levels.labels.site_level_current + ': <select class="current-site-level">';
			$.each( _MissionControl_Levels.levels, function ( index, item ) {
				var selected = index === current_level ? 'selected="selected"' : '';
				content += '<option value="' + index + '" ' + selected + '>' + item.name + '</option>';
			} );
			content += '</select></div>';

			var checked = level_can_expire ? 'checked="checked"' : '';
			// Level can expire?
			content += '<div><label>' + _MissionControl_Levels.labels.site_level_expire_setting +
			           ': <input type="checkbox" name="level-can-expire" ' + checked + ' />' +
			           '</label></div>';

			if ( typeof expiry_date == "undefined" ) {
				expiry_date = '';
			}
			content += '<div><label>' + _MissionControl_Levels.labels.site_level_expire_text +
			           ': <input type="text" name="level-expiry-date" value="' + expiry_date + '" />' +
			           '</label></div>';

			// Revert Level
			content += '<div>' + _MissionControl_Levels.labels.site_level_expire_revert + ': <select class="revert-site-level">';
			$.each( _MissionControl_Levels.levels, function ( index, item ) {
				var selected = index === revert_level ? 'selected="selected"' : '';
				content += '<option value="' + index + '" ' + selected + '>' + item.name + '</option>';
			} );
			content += '</select></div>';


			content += '</div>'; // metabox

			$( '#edit-site-level-page' ).append( content );

			jQuery( '[name="level-expiry-date"]' ).datepicker( {
				dateFormat: 'yy-mm-dd'
			} );
			if ( expiry_date != '' ) {
				var date = new Date( expiry_date * 1000 ); // Convert to milliseconds
				$( '[name="level-expiry-date"]' ).datepicker( 'setDate', date );
			}
		}

		// Document ready
		Hooks.add_action( 'missioncontrol_ready', function () {

			// Level Settings Page
			levels_element = '#levels-settings-page';
			var levels_page = $( levels_element );
			if ( levels_page.length > 0 ) {
				Hooks.do_action( 'missioncontrol_level_settings_page' );
			}

			// Edit Site Level Page
			edit_site_level_element = '#edit-site-level-page';
			var edit_site_level_page = $( edit_site_level_element );
			if ( edit_site_level_page.length > 0 ) {
				Hooks.do_action( 'missioncontrol_edit_site_level_page' );
			}

			// Setup hooks for "Save Level Settings" button
			$( '.button-save-site-level-settings' ).on( 'click', function () {
				Hooks.do_action( 'missioncontrol_edit_site_level_page_save_clicked' );
			} );

		} );


		// Hook the Level Settings page
		Hooks.add_action( 'missioncontrol_level_settings_page', function () {
			MissionControl.api.request( 'GET', 'levels', false, init_screen );
		} );

		// Hook the Edit Site Level page
		Hooks.add_action( 'missioncontrol_edit_site_level_page', function () {
			var blog_id = parseInt( $( edit_site_level_element ).attr( 'data-id' ) );
			MissionControl.api.request( 'GET', 'levels/' + blog_id, false, init_site_edit_screen );
		}, 1 );

		// Hook the Save Clicked button to update the site level
		Hooks.add_action( 'missioncontrol_edit_site_level_page_save_clicked', function () {
			var blog_id = parseInt( $( edit_site_level_element ).attr( 'data-id' ) );
			var data = {
				level: $( '.current-site-level' ).val(),
				revert_level: $( '.revert-site-level' ).val(),
				can_expire: $( '[name="level-can-expire"]' ).is( ':checked' ),
				expiry_date: $( '[name="level-expiry-date"]' ).val(),
				blog_id: blog_id
			};

			MissionControl.api.request( 'POST', 'levels/' + blog_id, data, function ( response ) {
				var content = '<div id="level-save-message" class="updated notice is-dismissible">' +
				              '     <p>' + _MissionControl_Levels.labels.site_updated + '</p>' +
				              '</div>';
				$( '.level-notices' ).empty();
				$( '.level-notices' ).append( content );
				$( '.level-notices .notice' ).css( {
					'margin-left': '0',
					'z-index': '100',
					'position': 'absolute',
					'width': $( '.wrap' ).width()
				} );
				setTimeout( function () {
					$( '.level-notices .notice' ).fadeOut( 500 );
				}, 500 )
			} );

		}, 1 );
	}
)( jQuery );