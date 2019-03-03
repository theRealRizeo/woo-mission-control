<?php
/**
 * This file contains convenient methods for actions required throughout the plugin.
 *
 * @package MissionControl
 */

namespace MissionControl;

/**
 * Class MissionControl_Utility
 */
class Utility {

	/**
	 * Convert a PHP object into an array.
	 *
	 * @param object $object The object to map into an array.
	 *
	 * @return mixed
	 */
	public static function object_to_array( $object ) {
		if ( is_object( $object ) ) {
			$object = get_object_vars( $object );
		}

		if ( is_array( $object ) ) {
			return array_map( array( __CLASS__, 'object_to_array' ), $object );
		} else {
			return $object;
		}
	}

	/**
	 * Convert an array into a PHP object.
	 *
	 * @param array $array The array to turn into an object.
	 *
	 * @return mixed
	 */
	public static function array_to_object( $array ) {
		if ( is_array( $array ) ) {
			return (object) array_map( array( __CLASS__, 'array_to_object' ), $array );
		} else {
			return $array;
		}
	}

	/**
	 * Echo out escaped and sanitised content.
	 *
	 * @param mixed $content   The content to output.
	 * @param bool  $script_ok Allow a <script> snippet.
	 */
	public static function output( $content, $script_ok = false ) {

		/**
		 * Allowed HTML attributes for form elements.
		 *
		 * @link: https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes
		 */
		$form_attributes = array(
			'accept'          => true,
			'accept-charset'  => true,
			'accesskey'       => true,
			'action'          => true,
			'alt'             => true,
			'autocomplete'    => true,
			'autofocus'       => true,
			'autosave'        => true,
			'checked'         => true,
			'class'           => true,
			'cols'            => true,
			'contenteditable' => true,
			'dir'             => true,
			'dirname'         => true,
			'disabled'        => true,
			'draggable'       => true,
			'dropzone'        => true,
			'enctype'         => true,
			'for'             => true,
			'form'            => true,
			'formaction'      => true,
			'height'          => true,
			'hidden'          => true,
			'id'              => true,
			'itemprop'        => true,
			'lang'            => true,
			'list'            => true,
			'max'             => true,
			'maxlength'       => true,
			'method'          => true,
			'min'             => true,
			'multiple'        => true,
			'name'            => true,
			'novalidate'      => true,
			'pattern'         => true,
			'placeholder'     => true,
			'readonly'        => true,
			'required'        => true,
			'rows'            => true,
			'selected'        => true,
			'size'            => true,
			'spellcheck'      => true,
			'src'             => true,
			'step'            => true,
			'style'           => true,
			'tabindex'        => true,
			'target'          => true,
			'title'           => true,
			'type'            => true,
			'usemap'          => true,
			'value'           => true,
			'width'           => true,
			'wrap'            => true,
		);

		$form_elements = array(
			'button'   => $form_attributes,
			'datalist' => $form_attributes,
			'fieldset' => $form_attributes,
			'form'     => $form_attributes,
			'input'    => $form_attributes,
			'keygen'   => $form_attributes,
			'label'    => $form_attributes,
			'legend'   => $form_attributes,
			'optgroup' => $form_attributes,
			'option'   => $form_attributes,
			'output'   => $form_attributes,
			'select'   => $form_attributes,
			'textarea' => $form_attributes,
		);

		$allowed_html = array_merge( wp_kses_allowed_html( 'post' ), $form_elements );

		/**
		 * Add data attributes.
		 */
		$allowed_html['div'] = array_merge( $allowed_html['div'], array(
			'data-id' => true,
		) );

		if ( true === $script_ok ) {
			$allowed_html = array_merge( array(
				'script' => array(
					'type' => true,
				),
			), $allowed_html );
		}

		$allowed_html = apply_filters( 'missioncontrol_allowed_html', $allowed_html );

		echo wp_kses( wp_unslash( $content ), $allowed_html );
	}

	/**
	 * Convenience method for $_GET variables.
	 *
	 * Pass in false to return a sanitised version of the $_GET array.
	 *
	 * @uses MissionControl_Utility::input_query
	 *
	 * @param mixed $name Array key of item in the $_GET global array.
	 *
	 * @return mixed
	 */
	public static function get_query( $name = false ) {

		return self::input_query( 'get', false, false, $name );
	}

	/**
	 * Convenience method for $_POST variables.
	 *
	 * Accessing $_POST using this method expects a valid nonce.
	 *
	 * Pass in false to return a sanitised version of the $_POST array.
	 *
	 * @uses MissionControl_Utility::input_query
	 *
	 * @param mixed  $name        Array key of item in the $_POST global array.
	 * @param int    $action      Nonce action.
	 * @param String $nonce_field Nonce field to check.
	 *
	 * @return mixed
	 */
	public static function post_query( $name = false, $action = -1, $nonce_field = '_wpnonce' ) {

		return self::input_query( 'post', $action, $nonce_field, $name );
	}

	/**
	 * Convenience method for $_REQUEST variables.
	 *
	 * Pass in false to return a sanitised version of the $_REQUEST array.
	 *
	 * @uses MissionControl_Utility::input_query
	 *
	 * @param mixed $name Array key of item in the $_REQUEST global array.
	 *
	 * @return mixed
	 */
	public static function request_query( $name = false ) {

		return self::input_query( 'request', false, false, $name );
	}

	/**
	 * This method retrieves $_GET, $_POST, and $_REQUEST items.
	 *
	 * @param mixed $type        Type of request.
	 * @param int   $action      Nonce action if dealing with POST.
	 * @param mixed $nonce_field Nonce field if dealing with POST.
	 * @param mixed $name        Array key of the item in the array.
	 *
	 * @return null
	 */
	public static function input_query( $type = 'get', $action = -1, $nonce_field = '_wpnonce', $name = false ) {

		$query = false;

		if ( 'get' === strtolower( $type ) || 'request' === strtolower( $type ) || ( 'post' === strtolower( $type ) && ! empty( $_POST[ $nonce_field ] ) && wp_verify_nonce( sanitize_key( $_POST[ $nonce_field ] ), $action ) ) ) { // WPCS: input var okay.

			if ( false === $name ) {
				// @codingStandardsIgnoreStart
				switch ( strtolower( $type ) ) {
					case 'get':
						$query = $_GET;
						break;
					case 'post':
						$query = $_POST;
						break;
					case 'request':
						$query = $_REQUEST;
						break;
				}
				array_walk_recursive( $query, array( new self, 'sanitize_array_item' ) );
				// @codingStandardsIgnoreEnd
			} else {
				if ( isset( $_GET[ $name ] ) || isset( $_POST[ $name ] ) || isset( $_REQUEST[ $name ] ) ) { // WPCS: input var okay.
					// @codingStandardsIgnoreStart
					switch ( strtolower( $type ) ) {
						case 'get':
							$query = $_GET[ $name ];
							break;
						case 'post':
							$query = $_POST[ $name ];
							break;
						case 'request':
							$query = $_REQUEST[ $name ];
							break;
					}

					if ( is_array( $query ) ) {
						array_walk_recursive( $query, array( new self, 'sanitize_array_item' ) );
					}
					// @codingStandardsIgnoreEnd
				}
			}
		}

		if ( ! empty( $query ) || '' === $query ) {
			return $query;
		}

		return null;
	}

	/**
	 * Used by array_walk_recursive to sanitize array items.
	 *
	 * @param mixed $item Array item.
	 * @param mixed $key  Array key.
	 *
	 * @return string
	 */
	public static function sanitize_array_item( &$item, $key ) {
		return sanitize_text_field( wp_unslash( $item ) );
	}

	/**
	 * Force enable the Mission Control menu.
	 */
	public static function force_missioncontrol_menu() {
		$content = '<script type="text/javascript">
					jQuery( ".wp-has-current-submenu.wp-menu-open" ).removeClass("wp-has-current-submenu").removeClass("wp-menu-open").addClass("wp-not-current-submenu");
					jQuery( "#toplevel_page_missioncontrol_main" ).removeClass("wp-not-current-submenu").addClass("wp-has-current-submenu").addClass("wp-menu-open").addClass("current");
					</script>';

		self::output( $content, true );
	}
}
