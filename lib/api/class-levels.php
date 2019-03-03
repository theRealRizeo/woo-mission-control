<?php
/**
 * Extends the base API object to register the Levels API extensions.
 *
 * @package MissionControl/API
 */

namespace MissionControl\API;
use MissionControl\APILoader;
use MissionControl\Utility;

/**
 * Class MissionControl_API_Levels
 */
class Levels extends Mobject {

	/**
	 * Reference to the Levels Module.
	 *
	 * @var Levels
	 */
	private $module;

	/**
	 * Factory method.
	 *
	 * @param Levels $module Reference to the Levels Module.
	 *
	 * @return Levels
	 */
	public static function init( $module ) {
		$api         = new Levels();
		$api->module = $module;

		return $api;
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		/**
		 * Level Settings Routes
		 */
		$query_params = array(
			'user_capability' => 'manage_options',
		);
		register_rest_route( APILoader::API_NAMESPACE . '/v' . APILoader::API_VERSION, '/levels', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_levels' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => $query_params,
			),
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_levels' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => $query_params,
			),
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_levels' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => $query_params,
			),

		) );

		/**
		 * Edit Site Level Routes
		 */
		register_rest_route( APILoader::API_NAMESPACE . '/v' . APILoader::API_VERSION, '/levels/(?P<id>[\d]+)', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_site_levels' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => $query_params,
			),
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_site_level' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => $query_params,
			),
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_site_level' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => $query_params,
			),

		) );

	}

	/**
	 * Get the registered site levels via API request.
	 *
	 * @param mixed $request API request.
	 *
	 * @return mixed|void
	 */
	public function get_levels( $request ) {
		return $this->module->plugin->get_setting( 'levels', $this->module->default_levels() );
	}

	/**
	 * Update site levels via API.
	 *
	 * @param mixed $request API request.
	 *
	 * @return mixed|void
	 */
	public function update_levels( $request ) {
		$this->module->update_levels( $request->get_params() );

		return $this->module->plugin->get_setting( 'levels', $this->module->default_levels() );
	}

	/**
	 * Get site specific level via API request.
	 *
	 * @param mixed $request API request.
	 *
	 * @return mixed
	 */
	public function get_site_levels( $request ) {
		$blog_id = (int) $request->get_param( 'id' );

		$details                  = get_blog_details( $blog_id, true );
		$details                  = Utility::object_to_array( $details );
		$details['level_details'] = $this->module->get_site_level( $blog_id );

		return $details;
	}

	/**
	 * Update a site specific level via API.
	 *
	 * @param mixed $request API request.
	 *
	 * @return array
	 */
	public function update_site_level( $request ) {

		$data = array(
			'level'        => $request->get_param( 'level' ),
			'revert_level' => $request->get_param( 'revert_level' ),
			'can_expire'   => $request->get_param( 'can_expire' ) !== 'false' ? true : false,
			'expiry_date'  => $request->get_param( 'can_expire' ) !== 'false' ? strtotime( $request->get_param( 'expiry_date' ) ) : '',
		);

		$blog_id = (int) $request->get_param( 'blog_id' );

		$reason = sanitize_text_field( $request->get_param( 'reason' ) );

		$this->module->update_site_level( $blog_id, $data, $reason );

		return $data;
	}
}
