<?php
/**
 * This file handles the MissionControl extensions system.
 *
 * The extension itself is also an internal extension that is always enabled.
 *
 * Internal extensions are called modules.
 *
 * @package MissionControl/Module
 */

namespace MissionControl\Module;
use MissionControl\Base;
use MissionControl\Extension;
use MissionControl\Utility;

/**
 * Class MissionControl_Module_Extensions
 */
class Extensions extends Base {

	/**
	 * The MissionControl Extensions API.
	 *
	 * @var Extensions $api
	 */
	private $api;

	/**
	 * Factory method.
	 *
	 * @param Plugin $plugin The plugin.
	 *
	 * @return Extensions
	 */
	public static function init( $plugin ) {
		$module_levels = new self( $plugin );
		return $module_levels;
	}

	/**
	 * Extensions constructor.
	 *
	 * @param Plugin $plugin The plugin.
	 */
	public function __construct( $plugin ) {
		parent::__construct();
		$this->plugin = $plugin;
	}

	/**
	 * Add Extension menu item to the MissionControl menu.
	 *
	 * @todo: When replacing the primary menu with a dashboard, this menu item needs to be enabled. (remove ___ below)
	 *
	 * @action missioncontrol_submenu___
	 *
	 * @param mixed $parent The parent menu page.
	 */
	public function add_extensions_menu( $parent ) {

		add_submenu_page( $parent, __( 'Extensions', 'mission-control' ), __( 'Extensions', 'mission-control' ), 'manage_options', 'missioncontrol_extensions', array(
			$this,
			'render_extensions_page',
		) );
		do_action( 'missioncontrol_submenu_extensions_rendered' );
	}

	/**
	 * The extensions menu page.
	 */
	public function render_extensions_page() {
		$content = '<div class="wrap"><h1>' . esc_html__( 'Mission Control', 'mission-control' ) . '</h1>';
		$content .= '<p class="description">' . esc_html__( 'On this page please select the extensions you would like to use to restrict or add features to the sites in your network.', 'mission-control' ) . '</p>';
		$content .= '<p class="description">' . esc_html__( 'Additional extensions may be added by other plugins. Enable those plugins first to make the extension appear here.', 'mission-control' ) . '</p>';

		do_action( 'missioncontrol_extensions_admin_pre' );
		$content = apply_filters( 'missioncontrol_extensions_admin_pre_content', $content );

		/**
		 * This div will be updated via the Extensions API.
		 *
		 * DO NOT REMOVE this div. It's "id" is very important.
		 */
		$content .= '<div id="extensions-list-page">' . esc_html__( 'Retrieving extensions...', 'mission-control' ) . '</div></div>';

		do_action( 'missioncontrol_extensions_admin_post' );
		$content = apply_filters( 'missioncontrol_extensions_admin_post_content', $content );

		Utility::output( $content );
	}

	/**
	 * Register the Extensions API.
	 *
	 * @action missioncontrol_register_endpoints
	 */
	public function api_endpoint() {
		$this->api = \MissionControl\API\Extensions::init( $this );
		$this->api->register_routes();
	}

	/**
	 * Enqueue scripts for the Extensions API.
	 *
	 * @action missioncontrol_enqueue_scripts
	 */
	public function add_scripts() {

		wp_enqueue_script( 'missioncontrol_extensions', $this->plugin->info['assets_url'] . 'js/missioncontrol/extensions.js', array(
			'missioncontrol',
			'hooks',
			'jquery-ui-sortable',
		), $this->plugin->info['version'], false );

		wp_localize_script( 'missioncontrol_extensions', '_MissionControl_Extensions', array(
			'labels' => array(
				'activate'   => __( 'Activate', 'mission-control' ),
				'deactivate' => __( 'Deactivate', 'mission-control' ),
				'settings'   => __( 'Settings', 'mission-control' ),
			),
		) );

		wp_enqueue_style( 'missioncontrol_extensions', $this->plugin->info['assets_url'] . 'css/missioncontrol/extensions.css', array(), $this->plugin->info['version'] );

	}

	/**
	 * Get an array of MissionControl extensions.
	 *
	 * @param bool $active_only true returns only active extensions, false returns ALL extensions.
	 *
	 * @return array
	 */
	public function get_extensions( $active_only = false ) {

		// Extensions are just modules that are non-core. They also have additional convenience methods.
		if ( ! $active_only ) {
			$extensions = $this->plugin->get_all_modules( false );
		} else {
			$extensions = $this->plugin->get_active_modules( false );
		}

		$extensions_details = array();
		foreach ( $extensions as $extension ) {
			$extensions_details[] = Extension::get_extension_info( $extension );
		}

		return $extensions_details;
	}

	/**
	 * A filter to add the bundled extensions.
	 *
	 * A good example of how other extensions will add themselves to the API.
	 *
	 * @filter missioncontrol_extensions
	 *
	 * @param array $extensions Array of available extensions.
	 *
	 * @return array Updated array of available extensions.
	 */
	public function bundled_extensions( $extensions ) {

		$extensions = array_merge( array(
			'PluginControl',
			'ThemeControl',
			'LevelMessage',
			'QuotaManager',
		), $extensions );

		return $extensions;
	}
}
