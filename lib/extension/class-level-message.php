<?php
/**
 * Allows different levels to show messages for that specific level. E.g. upgrade notices.
 *
 * This class also shows the very basics of writing a Mission Control extension module.
 *
 * @package MissionControl/Extension
 */

namespace MissionControl\Extension;
use MissionControl\Extension;
use MissionControl\Utility;
use MissionControl\Plugin;

/**
 * Class MissionControl_Extension_LevelMessage
 */
class LevelMessage extends Extension {

	/**
	 * Update notice.
	 *
	 * @var string
	 */
	private $notice;

	/**
	 * This is going to be used as the settings key.
	 *
	 * @return string
	 */
	public function slug() {
		return 'LevelMessage';
	}

	/**
	 * Extension name.
	 *
	 * @return string
	 */
	public function name() {
		return __( 'Level Message', 'mission-control' );
	}

	/**
	 * Extension description.
	 *
	 * @return string
	 */
	public function description() {
		return __( 'Display a message for specific site levels.' );
	}

	/**
	 * Get the menu slug.
	 *
	 * @return string
	 */
	public function menu_slug() {
		return 'missioncontrol_level_message';
	}

	/**
	 * Add hooks that are relevant to this extension.
	 *
	 * @action init Additional hooks.
	 */
	public function init_hooks() {

		// This module also registers a shortcode that can be used to show level messages.
		add_shortcode( 'mc_level_message', array( $this, 'mc_level_message_shortcode' ) );
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings() {

		$this->process_form();

		$info         = $this->get_info();
		$blog_id      = (int) Utility::get_query( 'blog_id' );
		$settings     = $this->get_settings( get_current_blog_id() );
		$this->notice = '';

		$levels = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_levels( true );

		$content = '<div class="wrap level-message">';

		$content .= '<h2>' . esc_html( $info['name'] ) . '</h2>';
		$content .= wp_kses_post( $this->notice );
		$content .= '<p class="description">' . $info['description'] . '</p>';

		$blog_override = '';
		if ( ! empty( $blog_id ) && is_multisite() ) {
			$blogname = get_blog_option( $blog_id, 'blogname' );
			$blog_override = '<div class="alternate" style="padding: 5px; margin: 10px 0;"><label><input name="site_override" type="checkbox" ' . checked( $settings['site_override'], true, false ) . ' /> ' . sprintf( esc_html__( 'Override settings for site: %s [ID: %d]', 'mission-control' ), $blogname, $blog_id ) . '<p class="description">' . esc_html__( 'Note: If you are not overriding you are changing the default settings for Level Messages.', 'mission-control' ) . '</p></label>';
			$blog_override .= '<input type="hidden" name="blog_id" value="' . $blog_id . '" />';
			$blog_override .= '<input type="hidden" name="previous_site_override" value="' . $settings['site_override'] . '" /></div>';
		}

		$content .= '<form method="post"><table class="widefat">' .
		            wp_nonce_field( 'level-message', '_wpnonce', true, false ) .
		            $blog_override .
		            '   <thead>
		                    <tr><th> ' . __( 'Level', 'mission - control' ) . ' </th><th> ' . __( 'Message', 'mission - control' ) . ' </th ><th> ' . __( 'Visibility', 'mission - control' ) . ' </th></tr>
		                </thead >
		                <tbody > ';

		$counter = 0;
		foreach ( $levels as $key => $level ) {
			$counter += 1;
			if ( ! isset( $settings[ $key ] ) ) {
				$settings[ $key ] = $this->get_level_defaults();
			}

			$css_class = 0 === $counter % 2 ? 'class="alternate"' : '';
			$content .= ' < tr>
			              < td ' . $css_class . ' > ' . esc_html( $level['name'] ) . ' </td >
			               <td ' . $css_class . ' ><textarea class="large-text" name = "message[' . $key . ']" >' . $settings[ $key ]['message'] . '</textarea ></td >
			               <td ' . $css_class . ' >
			                   <fieldset ><label ><input name = "above[' . $key . ']" type = "checkbox" ' . checked( $settings[ $key ]['above_content'], true, false ) . ' /> ' . esc_html__( 'Above content', 'mission - control' ) . ' </label ><br />
			                   <label ><input name = "below[' . $key . ']" type = "checkbox" ' . checked( $settings[ $key ]['below_content'], true, false ) . ' /> ' . esc_html__( 'Below content', 'mission - control' ) . ' </label ><br />
			                   <label ><input  name = "in_archive[' . $key . ']" type = "checkbox" ' . checked( $settings[ $key ]['in_archive'], true, false ) . ' /> ' . esc_html__( 'Allow on post listings', 'mission - control' ) . ' </label ><br />
			                  <label ><input  name = "shortcode[' . $key . ']" type = "checkbox" ' . checked( $settings[ $key ]['in_shortcode'], true, false ) . ' /> ' . esc_html__( 'In shortcode', 'mission - control' ) . ' </label ></fieldset >
			               </td >
			            </tr > ';
		}

		$content .= '   </tbody >
		             </table > ';

		$content .= '<p class="description" > ' . __( 'Choose where to display the messages . Above content will display it above the content( after the title). Below content will display it below the content body . "In shortcode" will display the message using the < strong>&#91;mc_level_message&#93;</strong> shortcode. To hide the message for a level completely, leave all checkboxes unchecked.', 'mission-control' ) . '</p>';

		$content .= '<div><input type="submit" class="button button-primary button-save-level-message" value="' . esc_attr__( 'Save Settings', 'mission-control' ) . '" /></div>';

		$content .= '</form></div>';

		Utility::output( $content );
	}

	/**
	 * Process the settings form.
	 */
	public function process_form() {

		$post_vars = Utility::post_query( false, 'level-message' );
		$messages = isset( $post_vars['message'] ) ? $post_vars['message'] : array();

		if ( ! empty( $post_vars ) ) {
			$this->notice = sprintf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', __( 'Settings saved.', 'mission-control' ) );
		}

		$settings = array();

		if ( count( $messages ) > 0 ) {

			global $themes_allowedtags;

			// Use the allowed tags for themes, but allow overriding. This is used by wp_kses() for the message.
			$allowedtags = apply_filters( 'missioncontrol_level_message_allowed_tags', $themes_allowedtags );

			$settings['site_override'] = isset( $post_vars['site_override'] );

			foreach ( $messages as $key => $message ) {
				$settings[ $key ]['message']       = wp_kses( $message, $allowedtags );
				$settings[ $key ]['above_content'] = isset( $post_vars['above'] ) && isset( $post_vars['above'][ $key ] );
				$settings[ $key ]['below_content'] = isset( $post_vars['below'] ) && isset( $post_vars['below'][ $key ] );
				$settings[ $key ]['in_shortcode']  = isset( $post_vars['shortcode'] ) && isset( $post_vars['shortcode'][ $key ] );
				$settings[ $key ]['in_archive']    = isset( $post_vars['in_archive'] ) && isset( $post_vars['in_archive'][ $key ] );
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
	}

	/**
	 * Default levels settings.
	 *
	 * @return array
	 */
	public function get_level_defaults() {

		return array(
			'above_content' => false,
			'below_content' => false,
			'in_shortcode'  => false,
			'in_archive'    => false,
			'message'       => __( 'This site is powered by MissionControl.', 'mission-control' ),
		);
	}

	/**
	 * Inject level message into the content if setting active.
	 *
	 * @filter the_content
	 *
	 * @param String $content Original content.
	 *
	 * @return string Updated content.
	 */
	public function add_to_content( $content ) {

		$site_level = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_site_level( get_current_blog_id() );
		$settings   = $this->get_settings( get_current_blog_id() );

		$in_archive = (bool) $settings[ $site_level['level'] ]['in_archive'];

		if ( ! is_main_query() || ( ! is_single() && ! is_singular() && ! $in_archive ) ) {
			return $content;
		}

		$above = (bool) $settings[ $site_level['level'] ]['above_content'];
		$below = (bool) $settings[ $site_level['level'] ]['below_content'];

		$message = $settings[ $site_level['level'] ]['message'];
		if ( $above && ! empty( $message ) ) {
			$content = $message . $content;
		}
		if ( $below && ! empty( $message ) ) {
			$content = $content . $message;
		}

		return $content;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @action missioncontrol_enqueue_scripts
	 */
	public function enqueue_scripts() {

		$acceptable = array(
			'mission-control_page_missioncontrol_level_message-network',
			'themes',
		);
		$screen     = get_current_screen();
		if ( ! in_array( $screen->id, $acceptable, true ) ) {
			return;
		}

		$info = $this->get_info();

		wp_enqueue_style( 'missioncontrol_levelmessage', $info['asset_url'] . 'style.css', array(), $this->plugin->info['version'] );

		wp_enqueue_script( 'missioncontrol_levelmessage', $info['asset_url'] . 'script.js', array(
			'hooks',
			'backbone',
			'jquery',
		), $this->plugin->info['version'], false );
	}

	/**
	 * Create `[mc_level_message]` shortcode.
	 *
	 * @return string
	 */
	public function mc_level_message_shortcode() {

		$site_level = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_site_level( get_current_blog_id() );
		$settings   = $this->get_settings( get_current_blog_id() );

		$in_shortcode = (bool) $settings[ $site_level['level'] ]['in_shortcode'];
		if ( $in_shortcode ) {
			return $settings[ $site_level['level'] ]['message'];
		}

		return '';
	}

	/**
	 * This will add a new action to the Sites list table.
	 *
	 * One of the following constants need to be set true for this row action to take effect.
	 * - MISSION_CONTROL_ALL_TABLES_ACTIONS
	 * - MISSION_CONTROL_LEVEL_MESSAGE_TABLE_ACTION
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
		     ( defined( 'MISSION_CONTROL_LEVEL_MESSAGE_TABLE_ACTION' ) && true === MISSION_CONTROL_LEVEL_MESSAGE_TABLE_ACTION )
		) {

			$url             = add_query_arg( array( 'blog_id' => $blog_id ), $this->get_settings_url() );
			$actions[ $url ] = __( 'Level Message', 'mission-control' );
		}

		return $actions;
	}
}
