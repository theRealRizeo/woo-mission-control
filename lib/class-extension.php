<?php
/**
 * This file handles extensions to MissionControl.
 *
 * Internal extensions are called modules.
 *
 * @package MissionControl
 */

namespace MissionControl;

/**
 * Class MissionControl_Extension
 */
class Extension extends Base {

	/**
	 * Reference to Plugin.
	 *
	 * @var Plugin
	 */
	public $plugin;

	/**
	 * Get an extension setting.
	 *
	 * @param mixed  $extension The name of the extension.
	 * @param String $key       The key of the setting to retrieve. `false` for all settings.
	 * @param mixed  $default   Default value if setting not found.
	 *
	 * @return mixed|void
	 */
	public function get_setting( $extension, $key, $default = null ) {

		if ( is_multisite() ) {
			$settings = get_site_option( $extension . '_options', array() );
		} else {
			$settings = get_option( $extension . '_options', array() );
		}

		if ( empty( $settings ) ) {
			return false;
		}
		$levels = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_levels( true );

		// Remove redundant settings.
		foreach ( array_diff( array_keys( $settings ), array_keys( $levels ) ) as $item ) {
			if ( 'site_override' === $item ) {
				continue;
			}
			unset( $settings[ $item ] );
		}

		// Add missing settings (copy of unassigned).
		foreach ( array_diff( array_keys( $levels ), array_keys( $settings ) ) as $item ) {
			$settings[ $item ] = $settings['unassigned'];
		}

		if ( empty( $key ) ) {
			return apply_filters( $extension . '_all_settings', $settings );
		} else {

			$option = isset( $settings[ $key ] ) ? $settings[ $key ] : $default;

			return apply_filters( $extension . '_setting_' . $key, $option );
		}
	}

	/**
	 * Update a setting for the extension.
	 *
	 * @param mixed  $extension The name of the extension.
	 * @param String $key       The key of the setting to retrieve. `false` for all settings.
	 * @param mixed  $value     New value for the settings.
	 */
	public static function update_setting( $extension, $key, $value ) {

		$settings = $value;
		if ( is_multisite() ) {
			if ( false !== $key ) {
				$settings         = get_site_option( $extension . '_options', array() );
				$settings[ $key ] = $value;
			}
			update_site_option( $extension . '_options', $settings );
		} else {
			if ( false !== $key ) {
				$settings         = get_option( $extension . '_options', array() );
				$settings[ $key ] = $value;
			}
			update_option( $extension . '_options', $settings );
		}
	}

	/**
	 * Get an extension setting for a given site.
	 *
	 * @param mixed  $extension The name of the extension.
	 * @param int    $blog_id   Blog ID to get setting for.
	 * @param String $key       The key of the setting to retrieve. `false` for all settings.
	 * @param mixed  $default   Default value if setting not found.
	 *
	 * @return mixed|void
	 */
	public function get_site_setting( $extension, $blog_id, $key, $default = null ) {

		$settings = maybe_unserialize( get_blog_option( $blog_id, $extension . '_settings', array() ) );

		if ( empty( $key ) ) {

			return apply_filters( $extension . '_site_all_settings', $settings, $blog_id );
		} else {

			$option = isset( $settings[ $key ] ) ? $settings[ $key ] : $default;

			return apply_filters( $extension . '_setting_' . $key, $option, $blog_id );
		}
	}

	/**
	 * Update a setting for the extension for a given site.
	 *
	 * @param mixed  $extension The name of the extension.
	 * @param int    $blog_id   Blog ID to get setting for.
	 * @param String $key       The key of the setting to retrieve. `false` for all settings.
	 * @param mixed  $value     The new value for the setting.
	 */
	public static function update_site_setting( $extension, $blog_id, $key, $value ) {

		$settings = $value;

		if ( false !== $key ) {
			$settings         = maybe_unserialize( get_blog_option( $blog_id, $extension . '_settings', array() ) );
			$settings[ $key ] = $value;
		}

		update_blog_option( $blog_id, $extension . '_settings', maybe_serialize( $settings ) );
	}

	/**
	 * Get settings, checks for site override.
	 *
	 * @param mixed $blog_id Blog ID to get the setting for.
	 *
	 * @return mixed|void
	 */
	public function get_settings( $blog_id = false ) {

		$blog_id = empty( $blog_id ) ? (int) Utility::request_query( 'blog_id' ) : $blog_id;
		$blog_id = empty( $blog_id ) ? 0 : $blog_id;

		// Try site specific settings.
		if ( ! empty( $blog_id ) ) {
			$settings = $this->get_site_setting( $this->slug(), $blog_id, false, false );
		}

		$settings['site_override'] = isset( $settings['site_override'] ) ? $settings['site_override'] : false;

		// Try extension settings.
		return empty( $settings ) || ! $settings['site_override'] ? $this->get_setting( $this->slug(), false ) : $settings;
	}

	/**
	 * A very generic method for adding a submenu page.
	 *
	 * @action missioncontrol_submenu
	 *
	 * @param mixed $parent The parent of the submenu.
	 */
	public function add_submenu( $parent ) {
		add_submenu_page( $parent, $this->name(), $this->name(), 'manage_options', $this->menu_slug(), array(
			$this,
			'render_settings',
		) );
	}

	/**
	 * Return the Extension information.
	 *
	 * @return array
	 */
	public function get_info() {

		$plugin = Plugin::instance();
		$extension_path = $plugin->info['include_url'] . 'extension/' . $this->extension_path( $this->slug() );
		$info = array(
			'slug'         => $this->slug(),
			'name'         => $this->name(),
			'description'  => $this->description(),
			'thumb'        => $extension_path . '/assets/thumbnail.svg',
			'status'       => $plugin->module_status( $this->slug() ),
			'menu_slug'    => $this->menu_slug(),
			'settings_url' => $this->get_settings_url(),
			'asset_url'    => $extension_path . '/assets/',
		);

		return $info;
	}

	/**
	 * Path to extensions.
	 *
	 * @param string $slug Extension slug.
	 *
	 * @return string
	 */
	public function extension_path( $slug ) {
		return strtolower( implode( '-', array_filter( preg_split( '/(?=[A-Z])(?<![A-Z])/', $slug ) ) ) );
	}

	/**
	 * Allows the module information to be returned statically.
	 *
	 * MissionControl Core must be instantiated for this method to be of any use.
	 *
	 * @param String $module Name of the Extension/Module.
	 *
	 * @return array
	 */
	public static function get_extension_info( $module ) {
		$plugin = Plugin::instance();
		// If there is no Core, then don't continue.
		if ( ! isset( $plugin ) ) {
			return array(
				'class'       => $module,
				'slug'        => 'unidentified_' . $module,
				'name'        => $module,
				'description' => __( 'There was a problem getting the extension information.', 'mission-control' ),
				'thumb'       => '',
				'status'      => 'error',
			);
		}

		$class = '';
		if ( class_exists( 'MissionControl\\Module\\' . $module ) ) {
			$class = 'MissionControl\\Module\\' . $module;
		} elseif ( class_exists( 'MissionControl\\Extension\\' . $module ) ) {
			$class = 'MissionControl\\Extension\\' . $module;
		} elseif ( class_exists( $module ) ) {
			$class = $module;
		}

		if ( array_key_exists( $class, $plugin->active_extensions ) ) {
			$obj = $plugin->active_extensions[ $class ];
		} else {
			$obj = new $class();
		}

		if ( ! empty( $obj ) ) {
			return $obj->get_info();
		}

		return array();
	}

	/**
	 * Get the URL to the settings page.
	 *
	 * @return string|void
	 */
	public function get_settings_url() {
		if ( is_multisite() ) {
			return network_admin_url( 'admin.php?page=' . $this->menu_slug() );
		} else {
			return admin_url( 'admin.php?page=' . $this->menu_slug() );
		}
	}
}
