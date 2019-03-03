var MissionControl = MissionControl || {};
var wp = wp || {};

var _MissionControl_ThemeControl = _MissionControl_ThemeControl || {};

_MissionControl_ThemeControl.event_bridge = {};

(
	function ( $ ) {

		// Document ready
		Hooks.add_action( 'missioncontrol_ready', function () {

			_MissionControl_ThemeControl.event_bridge = _.extend( {}, Backbone.Events );

			override_views();

			_MissionControl_ThemeControl.event_bridge.on( 'theme:rendered', function ( e ) {
			} );

		} );


		function remove_button( view ) {
			var data = view.model.toJSON();
			console.log(data.themecontrol);
			if ( typeof (
					data.themecontrol
				) !== 'undefined' && ! data.themecontrol.allowed ) {
				$( view.$el ).find( '.button.activate' ).detach();
			}
		}

		function add_level_plans( view ) {
			var data = view.model.toJSON();
			console.log(data.themecontrol);
			if ( typeof (
					data.themecontrol
				) !== 'undefined' && ! data.themecontrol.allowed ) {
//				$( view.$el ).append( '<div class="theme-unavailable" style="background: #5897fb; color: #fff; padding: 2px 10px; box-sizing: border-box; position: absolute;top: 0px; width:100%;"><p>' + _MissionControl_ThemeControl.required_level_label + _.toArray( data.themecontrol.plans ).join( ', ' ) + '</p></div>' );
				$( view.$el ).append( '<div class="theme-unavailable" style=""><p><span class="dashicons dashicons-admin-network"></span> ' + _.toArray( data.themecontrol.plans ).join( ', ' ) + '</p></div>' );
			}
		}


		function override_views() {

			wp.themes.view.Theme.prototype.render_o = wp.themes.view.Theme.prototype.render;
			wp.themes.view.Theme.prototype.render = function () {
				this.render_o( this );
				remove_button( this );
				add_level_plans( this );
				_MissionControl_ThemeControl.event_bridge.trigger( 'theme:rendered' );
			};

			wp.themes.view.Details.prototype.render_o = wp.themes.view.Details.prototype.render;
			wp.themes.view.Details.prototype.render = function () {
				this.render_o( this );
				remove_button( this );
				_MissionControl_ThemeControl.event_bridge.trigger( 'theme:detail:rendered' );
			}


		}

		setTimeout( function () {
			$( '.notice.is-dismissible' ).fadeOut( 500 );
		}, 500 )

	}
)( jQuery );