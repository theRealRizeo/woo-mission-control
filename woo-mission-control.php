<?php
/**
 * Plugin Name: WooCommerce Subscriptions Mission Control
 * Plugin URI: http://rheinardkorf.com
 * Description: Effortlessly take control of all the sites on your network. Assign levels to your sites and manage the features available to each level.
 * Version: 1.0
 * Author: Rheinard Korf (Modified by Paul Kevin)
 * Author URI: http://rheinardkorf.com
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mission-control
 * Domain Path: languages
 * Network: true
 *
 * @package MissionControl
 *
 * Copyright (C) 2015 Rheinard Korf
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Class MissionControl
 *
 * This class is responsible for setting up the autoloader of the plugin.
 * Once the autoloader is in place it will call $plugin_prefix::setup_plugin() that is found in the $library_path directory
 * within the $plugin_prefix directory.
 *
 * None of the functionality of this plugin should be added to THIS file. $plugin_prefix::setup_plugin() becomes the
 * primary entry point of the plugin.
 *
 * NOTE: This class is outside of the MissionControl namespace.
 */
class MissionControl {

	/**
	 * MissionControl information.
	 *
	 * @var array|bool|mixed
	 */
	private $info = array();

	/**
	 * MissionControl constructor.
	 */
	public function __construct() {

		/**
		 * If not correct version of PHP, then no point in continuing.
		 */
		if ( version_compare( phpversion(), '5.3', '<' ) ) {
			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::warning( $this->version_fail_text() );
			} else {
				add_action( 'admin_notices', array( $this, 'version_fail' ) );
			}
			return;
		}

		$data = array(
			'__FILE__'       => __FILE__,
			'library_path'   => 'lib',
			'assets_path'    => 'assets',
			'ID'             => 'missioncontrol',
			'class_prefix'   => 'MissionControl',
			'cpt_prefix'     => 'mc',
			'setting_prefix' => 'mc',
			'api_version'    => '1',
			'data'           => false,
		);
		$data = array_merge( $data, $this->parse_header_information() );
		$this->info = $this->setup_paths( $data );

		/**
		 * If paths are messed up we need to alert the admin.
		 */
		if ( empty( $this->info['base_name'] ) ) {
			add_action( 'shutdown', array( $this, 'installation_fail' ) );
			return;
		}

		/**
		 * Register the Autoloader.
		 */
		$autoloader_path = $this->info['include_dir'] . 'class-autoloader.php';
		if ( is_readable( $autoloader_path ) ) {
			require_once $autoloader_path;
			$autoloader = 'MissionControl\Autoloader';
			$autoloader = new $autoloader();
			$autoloader->register( $this->info['include_dir'] );
		}

		/**
		 * Load the plugin's text domain.
		 */
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Admin notice for incorrect PHP version.
	 */
	public function version_fail() {
		printf( '<div class="error"><p>%s</p></div>', esc_html( $this->version_fail_text() ) );
	}

	/**
	 * Version failure error message
	 *
	 * @return string
	 */
	private function version_fail_text() {
		return __( 'MissionControl plugin error: Your version of PHP is too old to run this plugin. You must be running PHP 5.3 or higher.', 'mission-control' );
	}

	/**
	 * Paths not correctly setup.
	 */
	public function installation_fail() {
		// Translators: This can't be translated if the plugin has an installation failure.
		$message      = esc_html( sprintf( '%s has not been properly installed. Please remove the plugin and try reinstalling.', 'MissionControl' ) );
		$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );

		echo wp_kses_post( $html_message );
	}

	/**
	 * Quick access to Header property list
	 *
	 * Valid values for $keys are:
	 *
	 * Name              : Name of the Plugin
	 * PluginURI         : URL to Plugin home
	 * Version           : Current Version
	 * Description       : Cited description of the plugin as on Plugins page
	 * Author            : Hyperlinked author name
	 * AuthorURI         : URL to Author
	 * TextDomain        : Used for i18n
	 * DomainPath        : Path of language files (suffix)
	 * Network           : Is this a network only plugin?
	 * Title             : Hyperlinked name of the plugin
	 * AuthorName        : Author name (no link)
	 * LibraryPath       : Path to `lib` for use with the auto loader (often same as PluginDirectory)
	 * Location          : Where the plugin is installed - plugins or mu-plugins
	 * PluginDirectory   : Path to plugin directory (includes library path)
	 * PluginURL         : URL to plugin folder (includes library path)
	 * BaseDirectory     : Path to plugin directory (excludes library path)
	 * BaseURL           : URL to plugin folder (excludes library path)
	 * LanguageDirectory : Full path to language files
	 *
	 * @param bool $key The header data to return (or all if none specified).
	 * @param bool $key_array Returns list of header property keys if true.
	 *
	 * @return mixed
	 */

	/* ---- Convenience Methods ---- */

	/**
	 * Load the plugin's text domain.
	 *
	 * Look for mission-control-<locale>.mo file and load it.
	 *
	 * e.g. mission-control-en_US.mo
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'mission-control', false, $this->info['languages_dir'] );
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {
		return;
	}

	/**
	 * Prevent unserializing
	 */
	private function __wakeup() {
		return;
	}

	/**
	 * Parse file header information into plugin $info.
	 */
	private function parse_header_information() {
		$default_headers = array(
			'name'        => 'Plugin Name',
			'plugin_uri'  => 'Plugin URI',
			'version'     => 'Version',
			'description' => 'Description',
			'author'      => 'Author',
			'author_uri'  => 'Author URI',
			'text_domain' => 'Text Domain',
			'domain_path' => 'Domain Path',
			'network'     => 'Network',
		);

		return get_file_data( __FILE__, $default_headers, 'plugin' );
	}

	/**
	 * Get plugin locations and paths.
	 *
	 * @param array $data Plugin information.
	 *
	 * @return bool|mixed
	 */
	private function setup_paths( $data ) {

		if ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( plugin_dir_path( __FILE__ ) . basename( __FILE__ ) ) ) {
			/**
			 * Normal Plugin Location
			 */
			$data['location']   = 'plugins';
			$data['plugin_dir'] = plugin_dir_path( __FILE__ );
			$data['plugin_url'] = plugins_url( '/', __FILE__ );

			// Must use plugin location.
		} else if ( defined( 'WPMU_PLUGIN_URL' ) && defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . DIRECTORY_SEPARATOR . basename( __FILE__ ) ) ) {
			/**
			 * "Must-Use" Plugin Location
			 */
			$data['location']   = 'mu-plugins';
			$data['plugin_dir'] = WPMU_PLUGIN_DIR . DIRECTORY_SEPARATOR;
			$data['plugin_url'] = WPMU_PLUGIN_URL . '/';
		} else {
			return false;
		}

		$data['base_name']     = dirname( plugin_basename( __FILE__ ) );
		$data['include_dir']   = $data['plugin_dir'] . $data['library_path'] . DIRECTORY_SEPARATOR;
		$data['include_url']   = $data['plugin_url'] . $data['library_path'] . '/';
		$data['assets_dir']    = $data['plugin_dir'] . $data['assets_path'] . DIRECTORY_SEPARATOR;
		$data['assets_url']    = $data['plugin_url'] . $data['assets_path'] . '/';
		$data['languages_dir'] = $data['plugin_dir'] . trim( $data['domain_path'], '/' ) . DIRECTORY_SEPARATOR;
		$data['languages_url'] = $data['plugin_url'] . trim( $data['domain_path'], '/' ) . '/';

		return $data;
	}

	/**
	 * Create the primary plugin object.
	 */
	public function launch_plugin() {
		/**
		 * Create core plugin object.
		 */
		global $mission_control_plugin;
		$core = 'MissionControl\Plugin';
		$mission_control_plugin = new $core( $this->info );
	}
}

/**
 * LAUNCH!
 */
$plugin_bootstrap = new MissionControl();
$plugin_bootstrap->launch_plugin();
