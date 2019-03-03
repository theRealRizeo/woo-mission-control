<?php
/**
 * This file handles core functionality of MissionControl.
 *
 * @package MissionControl
 */

namespace MissionControl;

/**
 * Class Plugin
 *
 * @package MissionControl
 */
class Plugin extends Base {

	/**
	 * Contains references to all active extensions.
	 *
	 * @var array
	 */
	public $active_extensions = array();

	/**
	 * Array containing all the important plugin information.
	 *
	 * @var array|bool
	 */
	public $info = array();

	/**
	 * Plugin constructor.
	 *
	 * @param bool $info Plugin information.
	 */
	public function __construct( $info ) {
		/**
		 * Call parent constructor for reflection of code.
		 */
		parent::__construct();

		/**
		 * Plugin information array.
		 */
		$this->info = $info;

		/**
		 * Run the init() method on activated modules / extensions
		 */
		$active_extensions = $this->get_active_modules();

		foreach ( $active_extensions as $module ) {

			if ( class_exists( 'MissionControl\\Module\\' . $module ) ) {
				$class = 'MissionControl\\Module\\' . $module;
			} elseif ( class_exists( 'MissionControl\\Extension\\' . $module ) ) {
				$class = 'MissionControl\\Extension\\' . $module;
			} elseif ( class_exists( $module ) ) {
				$class = $module;
			}

			if ( ! empty( $class ) ) {
				$this->active_extensions[ $class ] = new $class( $this );
			}
		}

		/**
		 * Setup the API
		 */
		APILoader::api_init();
	}

	/**
	 * Get an instance of the plugin.
	 *
	 * @return \MissionControl\Plugin
	 */
	public static function instance() {
		global $mission_control_plugin;
		return $mission_control_plugin;
	}

	/**
	 * Get all active modules available.
	 *
	 * Non-core modules are called Extensions.
	 *
	 * @param bool $core true to include all active modules/extension, false to exclude core modules.
	 *
	 * @return array|mixed|void
	 */
	public function get_active_modules( $core = true ) {

		$active_extensions = $this->get_setting( 'active_modules', array() );

		if ( $core ) {
			$active_extensions = array_merge( $this->get_core_modules(), $active_extensions );
		}

		return apply_filters( 'missioncontrol_active_extensions', $active_extensions );
	}

	/**
	 * Get all modules including non-active modules
	 *
	 * @param bool $core true to include all modules/extension, false to exclude core modules.
	 *
	 * @return mixed|void
	 */
	public function get_all_modules( $core = true ) {

		$modules = array();

		if ( $core ) {
			$modules = array_merge( $modules, $this->get_core_modules() );
		}

		return apply_filters( 'missioncontrol_extensions', $modules );
	}

	/**
	 * Get only core modules
	 *
	 * @return array
	 */
	public function get_core_modules() {
		return array(
			'Levels',
			'Extensions',
		);
	}

	/**
	 * Get the module/extension status.
	 *
	 * @param String $slug Class name of module extension (only suffix for core/bundled modules).
	 *
	 * @return string
	 */
	public function module_status( $slug ) {
		$active_extensions = $this->get_active_modules( false );

		return in_array( $slug, $active_extensions, true ) ? 'enabled' : 'disabled';
	}

	/**
	 * Toggle module status.
	 *
	 * @param String $slug Class name of module extension (only suffix for core/bundled modules).
	 *
	 * @return string
	 */
	public function module_toggle_status( $slug ) {
		$active_extensions = $this->get_active_modules( false );
		$key               = array_search( $slug, $active_extensions );

		if ( false !== $key ) {
			unset( $active_extensions[ $key ] );
			$this->update_settings( 'active_modules', $active_extensions );

			return 'disabled';
		} else {
			$active_extensions[] = $slug;
			$this->update_settings( 'active_modules', $active_extensions );

			return 'enabled';
		}
	}

	/**
	 * Add the MissionControl menus.
	 *
	 * @action network_admin_menu
	 * @action admin_menu___
	 */
	public function admin_menu() {

		/**
		 * If we're on a multisite and not network admin, then bail.
		 *
		 * @todo Consider behaviour if not on multisite.
		 */
		if ( ! is_network_admin() ) {
			return;
		}

		add_menu_page( 'Mission Control',
			'Mission Control',
			'manage_options',
			'missioncontrol_main',
			array( $this, 'render_admin_menu' ),
			$this->info['assets_url'] . 'img/icon.svg'
		);

		if ( is_multisite() ) {
			add_submenu_page( 'missioncontrol_main', __( 'All Sites', 'mission-control' ),
				__( 'All Sites', 'mission-control' ),
				'manage_options',
				'sites.php?missioncontrol'
			);
		}

		do_action( 'missioncontrol_submenu', 'missioncontrol_main' );
	}

	/**
	 * Update $submenu_file if on 'All Sites' from Mission Control.
	 *
	 * @filter submenu_file
	 *
	 * @param mixed $submenu_file The original submenu_file.
	 * @param mixed $parent_file  The parent_file.
	 *
	 * @return string
	 */
	public function alter_submenu_file( $submenu_file, $parent_file ) {
		$is_missioncontrol = Utility::get_query( 'missioncontrol' );
		$is_missioncontrol = isset( $is_missioncontrol );
		if ( 'sites.php' === $parent_file && $is_missioncontrol ) {
			return 'sites.php?missioncontrol';
		}

		return $submenu_file;
	}

	/**
	 * Alter menu display for 'All Sites' from MissionControl.
	 *
	 * @action adminmenu
	 *
	 * @todo Improve WordPress core.
	 */
	public function alter_all_sites_menu() {
		global $submenu_file;

		if ( 'sites.php?missioncontrol' === $submenu_file ) {
			Utility::force_missioncontrol_menu();
		}
	}

	/**
	 * Remove MissionControl from individual site plugin lists.
	 *
	 * @action pre_current_active_plugins
	 */
	public function remove_from_site_plugin_list() {

		if ( ! is_network_admin() ) {
			global $wp_list_table;
			unset( $wp_list_table->items['mission-control/mission-control.php'] );
		}
	}

	/**
	 * Render the MissionControl menu.
	 *
	 * @todo This is the same page as the Extensions page. Consider a dashboard in future.
	 */
	public function render_admin_menu() {
		$this->active_extensions['MissionControl\\Module\\Extensions']->render_extensions_page();
	}

	/**
	 * Enqueue MissionControl admin scripts.
	 *
	 * @action admin_enqueue_scripts
	 *
	 * @param String $hook The page hook to enqueue scripts conditionally.
	 */
	public function admin_scripts( $hook ) {

		// Other plugins can allow these scripts to be loaded.
		$load_mission_control_object = apply_filters( 'missioncontrol_admin_scripts', false );

		if ( preg_match( '/missioncontrol/i', $hook ) || $load_mission_control_object ) {

			// Attempt to enqueue Hooks.js.
			if ( ! wp_script_is( 'hooks', $list = 'enqueued' ) ) {
				wp_enqueue_script( 'hooks', $this->info['assets_url'] . 'js/hooks.js', array(), $this->info['version'], false );
			}

			wp_enqueue_script( 'missioncontrol', $this->info['assets_url'] . 'js/missioncontrol.js', array(
				'jquery',
				'backbone',
				'hooks',
			), $this->info['version'], false );

			wp_localize_script( 'missioncontrol', 'MissionControl', array(
				'api'         => array(
					'base'  => esc_url_raw( trailingslashit( rest_url( 'missioncontrol/v' . $this->info['api_version'] ) ) ),
					'nonce' => wp_create_nonce( 'wp_rest' ),
				),
				'date_format' => get_option( 'date_format' ),
			) );

			do_action( 'missioncontrol_enqueue_scripts' );
		}
	}


	/**
	 * Convenience method to get the plugin settings.
	 *
	 * Will get settings from site options if on a network, or regular options for single site install.
	 *
	 * @param mixed $key Key value.
	 * @param mixed $default Value if not found.
	 *
	 * @return mixed|void
	 */
	public function get_setting( $key, $default = null ) {

		if ( is_multisite() ) {
			$settings = get_site_option( 'missioncontrol_options', array() );
		} else {
			$settings = get_option( 'missioncontrol_options', array() );
		}

		if ( empty( $key ) ) {

			return apply_filters( 'missioncontrol_options_all', $settings );
		} else {

			$option = isset( $settings[ $key ] ) ? $settings[ $key ] : $default;

			// Deal with empty arrays.
			$option = is_array( $option ) && empty( $option ) && ! empty( $default ) ? $default : $option;

			return apply_filters( 'missioncontrol_options_' . $key, $option );
		}
	}

	/**
	 * Update a plugin setting.
	 *
	 * Uses site options if on a network or regular options for single site install.
	 *
	 * @param mixed $key Key value.
	 * @param mixed $value New value for the setting.
	 */
	public function update_settings( $key, $value ) {

		$settings = $value;
		if ( is_multisite() ) {
			if ( false !== $key ) {
				$settings         = get_site_option( 'missioncontrol_options', array() );
				$settings[ $key ] = $value;
			}
			update_site_option( 'missioncontrol_options', $settings );
		} else {
			if ( false !== $key ) {
				$settings         = get_option( 'missioncontrol_options', array() );
				$settings[ $key ] = $value;
			}
			update_option( 'missioncontrol_options', $settings );
		}
	}

	/**
	 * Convenience method to get site settings.
	 *
	 * @param int   $blog_id ID for specific blog.
	 * @param mixed $key     Key value for setting.
	 * @param mixed $default Default value if not found.
	 *
	 * @return mixed|void
	 */
	public function get_site_setting( $blog_id, $key, $default = null ) {

		$settings = maybe_unserialize( get_blog_option( $blog_id, 'missioncontrol_settings', array() ) );

		if ( empty( $key ) ) {

			return apply_filters( 'missioncontrol_site_settings_all', $settings, $blog_id );
		} else {

			$option = isset( $settings[ $key ] ) ? $settings[ $key ] : $default;

			return apply_filters( 'missioncontrol_site_settings_' . $key, $option, $blog_id );
		}
	}

	/**
	 * Update a site's setting.
	 *
	 * @param int   $blog_id ID for specific blog.
	 * @param mixed $key     Key value for setting.
	 * @param mixed $value   New value.
	 * @param mixed $reason  Optional reason for updating the settings.
	 */
	public function update_site_settings( $blog_id, $key, $value, $reason = false ) {

		$settings = $value;

		if ( false !== $key ) {
			$settings         = maybe_unserialize( get_blog_option( $blog_id, 'missioncontrol_settings', array() ) );
			$old_value        = isset( $settings[ $key ] ) ? $settings[ $key ] : null;
			$settings[ $key ] = $value;
		} else {
			$old_value = maybe_unserialize( get_blog_option( $blog_id, 'missioncontrol_settings', array() ) );
		}

		update_blog_option( $blog_id, 'missioncontrol_settings', maybe_serialize( $settings ) );

		do_action( 'missioncontrol_site_settings_updated_' . $key, $value, $old_value, $blog_id, $reason );
		do_action( 'missioncontrol_site_settings_updated', $key, $value, $old_value, $blog_id, $reason );
	}

	/**
	 * Get one of the active extensions.
	 *
	 * @param string $extension_class Class name of the extension to get.
	 *
	 * @return mixed
	 */
	public static function get_extension( $extension_class ) {
		$plugin = self::instance();

		if ( ! isset( $plugin->active_extensions ) || ! isset( $plugin->active_extensions[ $extension_class ] ) ) {
			return false;
		}

		return $plugin->active_extensions[ $extension_class ];
	}
}
