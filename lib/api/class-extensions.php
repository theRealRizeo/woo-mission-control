<?php
/**
 * Extends the base API object to register the Extensions API.
 *
 * @package MissionControl/API
 */

namespace MissionControl\API;
use MissionControl\API;
use MissionControl\APILoader;
use MissionControl\Extension;
use MissionControl\Plugin;

/**
 * Class MissionControl_API_Extensions
 */
class Extensions extends Mobject {

	/**
	 * Reference to the Extensions Module.
	 *
	 * @var \MissionControl\Extension
	 */
	private $module;

	/**
	 * Factory method.
	 *
	 * @param \MissionControl\Extension $module The extensions module.
	 *
	 * @return \MissionControl\Extension
	 */
	public static function init( $module ) {
		$api         = new Extensions();
		$api->module = $module;

		return $api;
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		/**
		 * Extensions Page Routes
		 */
		$query_params = array(
			'user_capability' => 'manage_options',
		);
		register_rest_route( APILoader::API_NAMESPACE . '/v' . APILoader::API_VERSION, '/extensions', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_extensions' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => $query_params,
			),
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggle_extension' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => $query_params,
			),
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'toggle_extension' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => $query_params,
			),
		) );
	}

	/**
	 * Get the available extensions via API.
	 *
	 * @return array
	 */
	public function get_extensions() {
		return $this->module->get_extensions();
	}

	/**
	 * Enable or disable an extension via the API.
	 *
	 * @param mixed $request API request.
	 *
	 * @return array
	 */
	public function toggle_extension( $request ) {
		$module = sanitize_text_field( $request->get_param( 'module' ) );

		$response = array(
			'status' => Plugin::instance()->module_toggle_status( $module ),
			'module' => Extension::get_extension_info( $module ),
		);

		return $response;
	}
}
