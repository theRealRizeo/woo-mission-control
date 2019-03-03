<?php
/**
 * This file handles the initialisation of the MissionControl API.
 *
 * @package MissionControl/API
 */

namespace MissionControl;

/**
 * Class APILoader
 */
class APILoader {

	const API_NAMESPACE = 'missioncontrol';
	const API_VERSION = '1';
	const API_CACHE = 'missioncontrol_api_loader_cache';

	/**
	 * Initialise the MissionControl REST API.
	 */
	public static function api_init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_endpoints' ) );
	}

	/**
	 * Register API end points.
	 *
	 * These will be registered by other classes extending the API.
	 */
	public static function register_endpoints() {

		/**
		 * Hook this action to register new API end points.
		 *
		 * @see MissionControl_API_Levels as an example.
		 */
		do_action( 'missioncontrol_register_endpoints' );
	}

	/**
	 * Generate a random secret that will only be valid for 5 minutes.
	 *
	 * @todo: Remove if no longer required.
	 *
	 * @param int $blog_id Id if the blog to generate secret for.
	 *
	 * @return string
	 */
	public static function get_secret( $blog_id ) {

		$time_offset = ceil( (int) date( 'i', time() ) / 5 );
		$secret = md5( wp_json_encode( array( self::API_NAMESPACE, 'api', $blog_id, $time_offset ) ) );
		return $secret;
	}

	/**
	 * Get the API url for a given blog.
	 *
	 * @param bool|int $blog_id The blog.
	 * @param string   $path    Additional path to add to URL.
	 *
	 * @return null|string|\WP_Site
	 */
	public static function get_extension_api_url( $blog_id = false, $path = '' ) {
		$blog_id = false === $blog_id ? get_current_blog_id() : (int) $blog_id;
		$site = get_site( $blog_id );
		$site = set_url_scheme( 'http://' . $site->domain . $site->path );
		if ( is_multisite() ) {
			$site = sprintf( '%s%s/%s/v%s%s', $site, \rest_get_url_prefix(), APILoader::API_NAMESPACE, APILoader::API_VERSION, $path );
		} else {
			$site = rest_url( $path );
		}

		return $site;
	}
}
