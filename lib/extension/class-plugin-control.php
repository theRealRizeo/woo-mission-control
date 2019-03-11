<?php
/**
 * Control the plugins that are available for each site level.
 *
 * List available plugins.
 * Set "always activated" plugins.
 * Set "auto-activate" for level updates.
 *
 * @package MissionControl/Extension
 */

namespace MissionControl\Extension;

use MissionControl\Extension;
use MissionControl\Plugin;
use MissionControl\Utility;

/**
 * Class MissionControl_Extension_PluginControl
 */
class PluginControl extends Extension {

	/**
	 * Flags to avoid endless loops.
	 *
	 * @var array
	 */
	public static $flags = array();

	/**
	 * Store plugin list upon first call to get_plugins().
	 *
	 * @var array
	 */
	private $plugin_list;

	/**
	 * API Endpoint
	 *
	 * @var PluginControl\API\EndPoint
	 */
	private $api;

	/**
	 * This is going to be used as the settings key.
	 *
	 * @return string
	 */
	public function slug() {
		return 'PluginControl';
	}

	/**
	 * Extension name.
	 *
	 * @return string
	 */
	public function name() {
		return __( 'Plugin Control', 'mission-control' );
	}

	/**
	 * Extension description.
	 *
	 * @return string
	 */
	public function description() {
		return __( 'Manage which plugins to restrict or grant access to for given levels.' );
	}

	/**
	 * Get the menu slug.
	 *
	 * @return string
	 */
	public function menu_slug() {
		return 'missioncontrol_plugin_control';
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings() {

		self::process_form();

		$info     = $this->get_info();
		$blog_id  = (int) Utility::get_query( 'blog_id' );
		$settings = $this->get_settings();
		$levels   = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_levels( true );

		$content = '<div class="wrap">';

		$content .= '<h2>' . esc_html( $info['name'] ) . '</h2>';
		$content .= '<p class="description">' . $info['description'] . '</p>';
		$content .= '<p class="description">' . esc_html__( 'Select plugins that should always be active and which should be auto-activated on level changes. Note: Network activation takes priority.', 'mission-control' ) . '</p>';

		$blog_override = '';
		if ( ! empty( $blog_id ) && is_multisite() ) {
			$blogname = get_blog_option( $blog_id, 'blogname' );
			$blog_override = '<div class="alternate" style="padding: 5px; margin: 10px 0;"><label><input name="site_override" type="checkbox" ' . checked( $settings['site_override'], true, false ) . ' /> ' . sprintf( esc_html__( 'Override settings for site: %s [ID: %d]', 'mission-control' ), $blogname, $blog_id ) . '<p class="description">' . esc_html__( 'Note: If you are not overriding you are changing the default settings for Level Messages.', 'mission-control' ) . '</p></label>';
			$blog_override .= '<input type="hidden" name="blog_id" value="' . $blog_id . '" />';
			$blog_override .= '<input type="hidden" name="previous_site_override" value="' . $settings['site_override'] . '" /></div>';
		}

		$content .= '<form method="post"><table class="widefat">' .
		            wp_nonce_field( 'plugin-control', '_wpnonce', true, false ) .
		            $blog_override .
		            '   <thead>
		                    <tr><th>' . __( 'Level', 'mission-control' ) . '</th><th>' . __( 'Available plugins', 'mission-control' ) . '</th><th>' . __( 'Always Active', 'mission-control' ) . '</th><th>' . __( 'Auto-Activate', 'mission-control' ) . '</th></tr>
		                </thead>
					<tbody>';

		$plugins = $this->get_plugins();

		$counter = 0;
		foreach ( $levels as $key => $level ) {
			$counter += 1;

			$css_class = 0 === $counter % 2 ? 'class="alternate"' : '';
			$content .= '<tr data-row="' . $counter . '">
			               <td ' . $css_class . '>' . esc_html( $level['name'] ) . '</td>
			               <td ' . $css_class . ' data-col="1">
			                   <a class="select-all">' . esc_html__( 'select all', 'mission-control' ) . '</a>
			                 | <a class="deselect-all">' . esc_html__( 'deselect all', 'mission-control' ) . '</a> ';

			if ( 1 !== $counter ) {
				$content .= '      | <a class="copy-above">' . esc_html__( 'copy above', 'mission-control' ) . '</a> ';
			}

			$content .= '       <fieldset>';

			foreach ( $plugins as $plugin_key => $plugin ) {
				if ( ! isset( $settings[ $key ] ) ) {
					$settings[ $key ] = array();
				}
				if ( ! isset( $settings[ $key ]['available'] ) ) {
					$settings[ $key ]['available'] = array();
				}
				if ( ! isset( $settings[ $key ]['available'][ $plugin_key ] ) ) {
					$settings[ $key ]['available'][ $plugin_key ] = false;
				}

				$content .= '   <label><input name="available[' . $key . '][' . $plugin_key . ']" type="checkbox" ' . checked( $settings[ $key ]['available'][ $plugin_key ], true, false ) . ' /> ' . esc_html( $plugin['Name'] ) . '</label><br/>';
			}

			$content .= '       </fieldset>
			            	</td>
			                	<td ' . $css_class . ' data-col="2">
									<a class="select-all">' . esc_html__( 'select all', 'mission-control' ) . '</a>
			                        | <a class="deselect-all" > ' . esc_html__( 'deselect all', 'mission - control' ) . ' </a > ';

			if ( 1 !== $counter ) {
				$content .= ' | <a class="copy-above" > ' . esc_html__( 'copy above', 'mission - control' ) . ' </a > ';
			}

			$content .= '       <fieldset > ';
			foreach ( $plugins as $plugin_key => $plugin ) {
				if ( ! isset( $settings[ $key ] ) ) {
					$settings[ $key ] = array();
				}
				if ( ! isset( $settings[ $key ]['always_active'] ) ) {
					$settings[ $key ]['always_active'] = array();
				}
				if ( ! isset( $settings[ $key ]['always_active'][ $plugin_key ] ) ) {
					$settings[ $key ]['always_active'][ $plugin_key ] = false;
				}

				$content .= ' < label><input name = "always_active[' . $key . '][' . $plugin_key . ']" type = "checkbox" ' . checked( $settings[ $key ]['always_active'][ $plugin_key ], true, false ) . ' /> ' . esc_html( $plugin['Name'] ) . ' </label ><br />';
			}

			$content .= '       </fieldset >
			               </td >
			            	   <td ' . $css_class . ' data-col="3" >
			                   <a class="select-all" > ' . esc_html__( 'select all', 'mission - control' ) . ' </a >
			                   | <a class="deselect-all" > ' . esc_html__( 'deselect all', 'mission - control' ) . ' </a > ';

			if ( 1 !== $counter ) {
				$content .= ' | <a class="copy-above" > ' . esc_html__( 'copy above', 'mission - control' ) . ' </a > ';
			}

			$content .= '       <fieldset > ';

			foreach ( $plugins as $plugin_key => $plugin ) {
				if ( ! isset( $settings[ $key ] ) ) {
					$settings[ $key ] = array();
				}
				if ( ! isset( $settings[ $key ]['auto_active'] ) ) {
					$settings[ $key ]['auto_active'] = array();
				}
				if ( ! isset( $settings[ $key ]['auto_active'][ $plugin_key ] ) ) {
					$settings[ $key ]['auto_active'][ $plugin_key ] = false;
				}

				$content .= ' < label><input name = "auto_active[' . $key . '][' . $plugin_key . ']" type = "checkbox" ' . checked( $settings[ $key ]['auto_active'][ $plugin_key ], true, false ) . ' /> ' . esc_html( $plugin['Name'] ) . ' </label ><br />';
			}

			$content .= '       </fieldset >
			               </td >
			            </tr > ';
		}

		$content .= '   </tbody >
   					</table > ';

		$content .= '<div ><p ><input type = "submit" class="button button-primary button-save-plugin-control" value = "' . esc_attr__( 'Save Settings', 'mission-control' ) . '" /></p ></div > ';
		$content .= '</form > ';
		$content .= '</div > '; // .wrap

		Utility::output( $content );
	}

	/**
	 * Process the Plugin Control admin form.
	 */
	public function process_form() {

		$post_vars = Utility::post_query( false, 'plugin-control' );
		$settings  = array();

		if ( empty( $post_vars ) ) {
			return;
		}

		Utility::output( sprintf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', __( 'Settings saved.', 'mission-control' ) ) );

		$levels  = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_levels( true );
		$plugins = $this->get_plugins();

		$settings['site_override'] = isset( $post_vars['site_override'] );

		foreach ( $levels as $key => $level ) {
			$settings[ $key ] = array(
				'available'     => array(),
				'auto_active'   => array(),
				'always_active' => array(),
			);

			$available_post     = isset( $post_vars['available'] ) && isset( $post_vars['available'][ $key ] );
			$auto_active_post   = isset( $post_vars['auto_active'] ) && isset( $post_vars['auto_active'][ $key ] );
			$always_active_post = isset( $post_vars['always_active'] ) && isset( $post_vars['always_active'][ $key ] );

			foreach ( $plugins as $plugin_key => $plugin ) {
				if ( $available_post ) {
					$settings[ $key ]['available'][ $plugin_key ] = isset( $post_vars['available'][ $key ][ $plugin_key ] );
				} else {
					$settings[ $key ]['available'][ $plugin_key ] = false;
				}
				if ( $auto_active_post ) {
					$settings[ $key ]['auto_active'][ $plugin_key ] = isset( $post_vars['auto_active'][ $key ][ $plugin_key ] );
				} else {
					$settings[ $key ]['auto_active'][ $plugin_key ] = false;
				}
				if ( $always_active_post ) {
					$settings[ $key ]['always_active'][ $plugin_key ] = isset( $post_vars['always_active'][ $key ][ $plugin_key ] );
				} else {
					$settings[ $key ]['always_active'][ $plugin_key ] = false;
				}
			}
		}

		if ( $settings['site_override'] ) {
			Extension::update_site_setting( $this->slug(), (int) $post_vars['blog_id'], false, $settings );
		} else {
			if ( isset( $post_vars['previous_site_override'] ) && $post_vars['previous_site_override'] ) {
				Extension::update_site_setting( $this->slug(), (int) $post_vars['blog_id'], 'site_override', false );
			} else {
				Extension::update_setting( $this->slug(), false, $settings );
			}
		}
	}

	/**
	 * Get a list of available plugins.
	 *
	 * @return mixed|void
	 */
	public function get_plugins() {

		if ( empty( $this->plugin_list ) ) {
			$this->plugin_list = get_plugins();

			// Ignore Network only plugins.
			foreach ( $this->plugin_list as $key => $plugin ) {
				if ( ! empty( $plugin['Network'] ) ) {
					unset( $this->plugin_list[ $key ] );
				}
			}

			// Don't control self.
			unset( $this->plugin_list['mission-control/mission-control.php'] );
		}

		return apply_filters( 'missioncontrol_plugincontrol_plugin_list', $this->plugin_list );
	}

	/**
	 * Load Plugin Control admin scripts.
	 *
	 * @filter missioncontrol_admin_scripts
	 *
	 * @param bool $load Load admin scripts.
	 *
	 * @return bool
	 */
	public function admin_scripts( $load ) {
		$screen = get_current_screen();
		if ( 'plugins' === $screen->id ) {
			return true;
		}

		return $load;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @action missioncontrol_enqueue_scripts
	 */
	public function enqueue_scripts() {
		$info = $this->get_info();

		$acceptable = array(
			'mission-control_page_missioncontrol_plugin_control-network',
		);
		$screen     = get_current_screen();
		if ( ! in_array( $screen->id, $acceptable, true ) ) {
			return;
		}

		wp_enqueue_style( 'missioncontrol_plugincontrol', $info['asset_url'] . 'style.css', array(), $this->plugin->info['version'] );

		wp_enqueue_script( 'missioncontrol_themecontrol', $info['asset_url'] . 'script.js', array(
			'hooks',
			'backbone',
		), $this->plugin->info['version'], false );
	}

	/**
	 * Update on site level update.
	 *
	 * @action missioncontrol_site_level_updated
	 *
	 * @param int   $blog_id Id for blog to update.
	 * @param mixed $data    New data.
	 */
	public function site_level_updated( $blog_id, $data ) {

		$settings   = $this->get_settings( $blog_id );
		$site_level = $data['level'];

		// Not OK on classic WordPress VIP hosted sites.
		// OK on VIP GO hosted sites.
		// @codingStandardsIgnoreStart
		switch_to_blog( $blog_id );
		// @codingStandardsIgnoreEnd

		$active_plugins = get_option( 'active_plugins' );

		$always_active = array_filter( (array) $settings[ $site_level ]['always_active'] );
		$always_active = array_keys( $always_active );
		$available     = array_filter( (array) $settings[ $site_level ]['available'] );
		$available     = array_keys( $available );
		$allowed       = array_merge( $available, $always_active );
		$auto_activate = array_filter( (array) $settings[ $site_level ]['auto_active'] );
		$auto_activate = array_keys( $auto_activate );

		// Move to REST
		$needs_activating   = array_diff( $always_active, $active_plugins ); // Get plugins that need to be activated.
		$needs_deactivating = array_diff( $active_plugins, $allowed ); // Get plugins that need to be deactivated.

		$needs_activating = array_unique( array_merge( $needs_activating, $auto_activate ) ); // Auto-activate for site change.

		activate_plugins( $needs_activating );
		deactivate_plugins( $needs_deactivating );

		restore_current_blog();
	}

	/**
	 * All plugins filter.
	 *
	 * @filter all_plugins
	 *
	 * @param array $plugins All the plugins.
	 *
	 * @return mixed
	 */
	public function all_plugins( $plugins ) {

		if ( is_network_admin() || is_main_site() ) {
			return $plugins;
		}

		$settings      = $this->get_settings( get_current_blog_id() );
		$levels        = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_levels( true );
		$site_level    = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_site_level( get_current_blog_id() );
		$site_level    = $site_level['level'];
		$always_active = is_array( $settings[ $site_level ]['always_active'] ) ? array_keys( array_filter( $settings[ $site_level ]['always_active'] ) ) : array();

		foreach ( $plugins as $plugin_file => $plugin ) {

			// If its a network only plugin, don't include it.
			$hide_network = apply_filters( 'missioncontrol_plugincontrol_hide_network', true );

			// Filter plugins not available on any plan.
			$found = false;
			foreach ( array_keys( $levels ) as $level ) {

				if ( $found ) {
					break;
				}

				$level_plugins = isset( $settings[ $level ] ) && isset( $settings[ $level ]['available'] ) ? array_keys( array_filter( $settings[ $level ]['available'] ) ) : array();
				$found         = in_array( $plugin_file, $level_plugins, true ) || $found;
			}

			if ( ( $hide_network && ! empty( $plugin['Network'] ) ) || 'mission-control/mission-control.php' === $plugin_file || ( ! $found && ! in_array( $plugin_file, $always_active, true ) ) ) {
				unset( $plugins[ $plugin_file ] );
			}
		}

		return $plugins;
	}

	/**
	 * Deactivate plugins that are not allowed.
	 *
	 * @action activated_plugin
	 *
	 * @param mixed $plugin Newly activated plugin.
	 */
	public function activated_plugin( $plugin ) {

		if ( is_network_admin() || is_main_site() ) {
			return;
		}

		// Always allow the MissionControl plugin.
		$is_mission_control = plugin_basename( $this->plugin->info['__FILE__'] ) === $plugin;

		$settings   = $this->get_settings( get_current_blog_id() );
		$site_level = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_site_level( get_current_blog_id() );
		$site_level = $site_level['level'];

		$always_active = is_array( $settings[ $site_level ]['always_active'] ) ? array_keys( array_filter( $settings[ $site_level ]['always_active'] ) ) : array();
		$available     = is_array( $settings[ $site_level ]['available'] ) ? array_keys( array_filter( $settings[ $site_level ]['available'] ) ) : array();
		$allowed       = array_merge( $available, $always_active );

		if ( ! in_array( $plugin, $allowed, true ) && ! $is_mission_control ) {
			deactivate_plugins( array( $plugin ) );
		}
	}

	/**
	 * Add plugin action links.
	 *
	 * @filter plugin_action_links
	 *
	 * @param array $actions     The actions array.
	 * @param mixed $plugin_file The plugin file.
	 *
	 * @return mixed
	 */
	public function plugin_action_links( $actions, $plugin_file ) {

		if ( is_network_admin() || is_main_site() ) {
			return $actions;
		}

		$settings   = $this->get_settings( get_current_blog_id() );
		$site_level = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_site_level( get_current_blog_id() );
		$site_level = $site_level['level'];

		$always_active = array_keys( array_filter( $settings[ $site_level ]['always_active'] ) );
		$available     = array_keys( array_filter( $settings[ $site_level ]['available'] ) );

		$is_available = true;
		$is_always    = false;
		if ( ! in_array( $plugin_file, $available, true ) ) {
			unset( $actions['activate'] );
			$is_available = false;
		}

		if ( in_array( $plugin_file, $always_active, true ) ) {
			unset( $actions['deactivate'] );
			$actions['network_active'] = __( 'Always Active', 'mission-control' );
			$is_always                 = true;
		}

		if ( ! $is_available && ! $is_always && ! isset( $actions['network_active'] ) ) {

			$levels           = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_levels( true );
			$available_levels = array();
			foreach ( array_keys( $levels ) as $level ) {
				$level_plugins = isset( $settings[ $level ] ) && isset( $settings[ $level ]['available'] ) ? array_keys( array_filter( $settings[ $level ]['available'] ) ) : array();

				if ( in_array( $plugin_file, $level_plugins, true ) ) {
					$available_levels[ $level ] = $levels[ $level ]['name'];
				}
			}

			if ( ! empty( $available_levels ) ) {

				$actions['available_levels'] = '<span class="dashicons dashicons-admin-network"></span>' . implode( ', ', $available_levels );
			}
		}

		return $actions;
	}

	/**
	 * Enable or disable active plugins.
	 *
	 * @action pre_current_active_plugins
	 */
	public function pre_current_active_plugins() {

		if ( is_main_site() ) {
			return;
		}

		$settings   = $this->get_settings( get_current_blog_id() );
		$site_level = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_site_level( get_current_blog_id() );
		$site_level = $site_level['level'];

		$active_plugins = get_option( 'active_plugins' );
		$always_active  = is_array( $settings[ $site_level ]['always_active'] ) ? array_keys( array_filter( $settings[ $site_level ]['always_active'] ) ) : array();
		$available      = is_array( $settings[ $site_level ]['available'] ) ? array_keys( array_filter( $settings[ $site_level ]['available'] ) ) : array();
		$allowed        = array_merge( $available, $always_active );

		$needs_activating   = array_diff( $always_active, $active_plugins ); // Get plugins that need to be activated.
		$needs_deactivating = array_diff( $active_plugins, $allowed ); // Get plugins that need to be deactivated.

		activate_plugins( $needs_activating );
		deactivate_plugins( $needs_deactivating );
	}

	/**
	 * This will add a new action to the Sites list table.
	 *
	 * One of the following constants need to be set true for this row action to take effect.
	 * - MISSION_CONTROL_ALL_TABLES_ACTIONS
	 * - MISSION_CONTROL_PLUGIN_CONTROL_TABLE_ACTION
	 *
	 * @filter missioncontrol_site_level_actions
	 *
	 * @param array $actions Existing actions.
	 * @param int   $blog_id Site ID for given row.
	 *
	 * @return mixed
	 */
	public function add_extension_action( $actions, $blog_id ) {

		if ( ( defined( 'MISSION_CONTROL_ALL_TABLES_ACTIONS' ) && true === MISSION_CONTROL_ALL_TABLES_ACTIONS ) ||
		     ( defined( 'MISSION_CONTROL_PLUGIN_CONTROL_TABLE_ACTION' ) && true === MISSION_CONTROL_PLUGIN_CONTROL_TABLE_ACTION )
		) {

			$url             = add_query_arg( array( 'blog_id' => $blog_id ), $this->get_settings_url() );
			$actions[ $url ] = __( 'Plugins', 'mission-control' );
		}

		return $actions;
	}

	/**
	 * Add additional kses allowed html.
	 *
	 * @filter missioncontrol_allowed_html
	 *
	 * @param array $allowed_html The allowed html.
	 *
	 * @return mixed
	 */
	public function allow_table_data_attributes( $allowed_html ) {

		$allowed_html['tr'] = array_merge( $allowed_html['tr'], array(
			'data-row' => true,
		) );
		$allowed_html['td'] = array_merge( $allowed_html['td'], array(
			'data-col' => true,
		) );

		return $allowed_html;
	}
}
