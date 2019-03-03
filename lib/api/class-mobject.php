<?php
/**
 * A base API object that need to be extended.
 *
 * @package MissionControl/API
 */

namespace MissionControl\API;

/**
 * Class MissionControl_API_Object
 */
class Mobject {

	/**
	 * Override to register routes then define callbacks in the new class
	 */
	public function register_routes() {}

	/**
	 * By default check for 'manage_options' capability.
	 *
	 * Add `user_capability` arg to the request or
	 * override in new class for other checks.
	 *
	 * If multisite, superuser will always return true. Override this method for other checks.

	 * @param mixed $request WordPress REST API permission request.
	 *
	 * @return bool
	 */
	public function permission( $request ) {
		$args = $request->get_attributes();
		$args = isset( $args['args'] ) ? $args['args'] : array();
		$cap = isset( $args['user_capability'] ) ? sanitize_text_field( $args['user_capability'] ) : 'manage_options';
		$valid = current_user_can( $cap );
		return $valid;
	}
}
