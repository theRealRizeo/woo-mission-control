<?php
/**
 * Control the Themes that are available to different site levels.
 *
 * Available themes can be activated. Non-available themes can be shown as teaser items.
 *
 * This class also shows the very basics of writing a Mission Control extension module.
 *
 * @package MissionControl/Extension
 */

namespace MissionControl\Extension;
use MissionControl\Extension;
use MissionControl\Plugin;
use MissionControl\Utility;

/**
 * Class MissionControl_Extension_PluginControl
 */
class ThemeControl extends Extension {

	/**
	 * Flags to avoid endless loops.
	 *
	 * @var array
	 */
	public static $flags = array();

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
		return 'ThemeControl';
	}

	/**
	 * Extension name.
	 *
	 * @return string
	 */
	public function name() {
		return __( 'Theme Control', 'mission-control' );
	}

	/**
	 * Extension description.
	 *
	 * @return string
	 */
	public function description() {
		return __( 'Manage which themes to restrict or grant access to for given levels.' );
	}

	/**
	 * Get the menu slug.
	 *
	 * @return string
	 */
	public function menu_slug() {
		return 'missioncontrol_theme_control';
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings() {

		$info         = $this->get_info();
		$blog_id      = (int) Utility::get_query( 'blog_id' );
		$settings     = $this->get_settings();
		$levels       = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_levels( true );
		$this->notice = '';

		self::process_form();

		$content = '<div class="wrap theme-control">';
		$content .= '<h2>' . esc_html( $info['name'] ) . '</h2>';
		$content .= wp_kses_post( $this->notice );
		$content .= '<p class="description">' . $info['description'] . '</p>';
		$content .= '<p class="description">' . esc_html__( 'Themes selected as available do not need network activation. Unavailable themes can be made visible to tease users to upgrade. Note: Available themes will always be visible.', 'mission-control' ) . '</p>';

		$blog_override = '';
		if ( ! empty( $blog_id ) && is_multisite() ) {
			$blogname = get_blog_option( $blog_id, 'blogname' );
			$blog_override = '<div class="alternate" style="padding: 5px; margin: 10px 0;"><label><input name="site_override" type="checkbox" ' . checked( $settings['site_override'], true, false ) . ' /> ' . sprintf( esc_html__( 'Override settings for site: %s [ID: %d]', 'mission-control' ), $blogname, $blog_id ) . '<p class="description">' . esc_html__( 'Note: If you are not overriding you are changing the default settings for Level Messages.', 'mission-control' ) . '</p></label>';
			$blog_override .= '<input type="hidden" name="blog_id" value="' . $blog_id . '" />';
			$blog_override .= '<input type="hidden" name="previous_site_override" value="' . $settings['site_override'] . '" /></div>';
		}

		$content .= '<form method="post"><table class="widefat">' .
		            wp_nonce_field( 'theme-control', '_wpnonce', true, false ) .
		            $blog_override .
		            '   <thead>
		                   <tr><th>' . __( 'Level', 'mission-control' ) . '</th><th>' . __( 'Available Themes', 'mission-control' ) . '</th><th>' . __( 'Visible Themes', 'mission-control' ) . '</th></tr>
		               </thead>
		               <tbody>';

		$themes  = wp_get_themes();
		$counter = 0;
		foreach ( $levels as $key => $level ) {
			$counter += 1;

			$css_class = ( 0 === $counter % 2 ) ? 'class="alternate"' : '';
			$content .= '<tr>
			               <td ' . $css_class . '>' . esc_html( $level['name'] ) . '</td>
			               <td ' . $css_class . '>
			                   <fieldset>';

			foreach ( $themes as $theme ) {
				if ( ! isset( $settings[ $key ] ) ) {
					$settings[ $key ] = array();
				}
				if ( ! isset( $settings[ $key ]['available'] ) ) {
					$settings[ $key ]['available'] = array();
				}
				if ( ! isset( $settings[ $key ]['available'][ $theme->stylesheet ] ) ) {
					$settings[ $key ]['available'][ $theme->stylesheet ] = false;
				}

				$content .= '   <label><input name="available[' . $key . '][' . $theme->stylesheet . ']" type="checkbox" ' . checked( $settings[ $key ]['available'][ $theme->stylesheet ], true, false ) . ' /> ' . esc_html( $theme->name ) . '</label><br/>';
			}

			$content .= '       </fieldset>
			               </td>
			               <td ' . $css_class . '>
			                   <fieldset>';

			foreach ( $themes as $theme ) {
				if ( ! isset( $settings[ $key ] ) ) {
					$settings[ $key ] = array();
				}
				if ( ! isset( $settings[ $key ]['visible'] ) ) {
					$settings[ $key ]['visible'] = array();
				}
				if ( ! isset( $settings[ $key ]['visible'][ $theme->stylesheet ] ) ) {
					$settings[ $key ]['visible'][ $theme->stylesheet ] = false;
				}

				$content .= '   <label><input name="visible[' . $key . '][' . $theme->stylesheet . ']" type="checkbox" ' . checked( $settings[ $key ]['visible'][ $theme->stylesheet ], true, false ) . ' /> ' . esc_html( $theme->name ) . '</label><br/>';
			}

			$content .= '       </fieldset>
			            	</td>
			            </tr>';
		}

		$content .= '   </tbody>
		            </table>';

		$content .= '<div><p><input type="submit" class="button button-primary button-save-theme-control" value="' . esc_attr__( 'Save Settings', 'mission-control' ) . '" /></p></div>';
		$content .= '</form>';
		$content .= '</div>'; // .wrap

		Utility::output( $content );
	}

	/**
	 * Process the ThemeControl admin page.
	 */
	public function process_form() {

		$post_vars = Utility::post_query( false, 'theme-control' );
		$settings  = array();

		if ( empty( $post_vars ) ) {
			return;
		}

		$this->notice = sprintf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', __( 'Settings saved.', 'mission-control' ) );

		$levels = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_levels( true );
		$themes = wp_get_themes();

		$settings['site_override'] = isset( $post_vars['site_override'] );

		foreach ( $levels as $key => $level ) {
			$settings[ $key ] = array(
				'available' => array(),
				'visible'   => array(),
			);

			$available_post = isset( $post_vars['available'] ) && isset( $post_vars['available'][ $key ] );
			$visible_post   = isset( $post_vars['visible'] ) && isset( $post_vars['visible'][ $key ] );

			foreach ( $themes as $theme ) {
				if ( $available_post ) {
					$settings[ $key ]['available'][ $theme->stylesheet ] = isset( $post_vars['available'][ $key ][ $theme->stylesheet ] );
				} else {
					$settings[ $key ]['available'][ $theme->stylesheet ] = false;
				}
				if ( $visible_post ) {
					$settings[ $key ]['visible'][ $theme->stylesheet ] = isset( $post_vars['visible'][ $key ][ $theme->stylesheet ] );
				} else {
					$settings[ $key ]['visible'][ $theme->stylesheet ] = false;
				}
			}
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

	/**
	 * Load Mission Control admin scripts?
	 *
	 * @param bool $load Load admin scripts.
	 *
	 * @filter missioncontrol_admin_scripts
	 *
	 * @return bool
	 */
	public function admin_scripts( $load ) {

		$screen = get_current_screen();
		if ( 'themes' === $screen->id ) {
			return true;
		}

		return $load;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @action missioncontrol_enqueue_scripts
	 */
	public function enqueue_scripts() {

		$acceptable = array(
			'mission-control_page_missioncontrol_theme_control-network',
			'themes',
		);
		$screen     = get_current_screen();
		if ( ! in_array( $screen->id, $acceptable, true ) ) {
			return;
		}

		$info = $this->get_info();

		wp_enqueue_style( 'missioncontrol_themecontrol', $info['asset_url'] . 'style.css', array(), $this->plugin->info['version'] );

		wp_enqueue_script( 'missioncontrol_themecontrol', $info['asset_url'] . 'script.js', array(
			'hooks',
			'backbone',
			'jquery',
		), $this->plugin->info['version'], false );

		$site_level = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_site_level( get_current_blog_id() );
		$site_level = $site_level['level'];

		wp_localize_script( 'missioncontrol_themecontrol', '_MissionControl_ThemeControl', array(
			'required_level_label' => __( 'Only on: ', 'mission-control' ),
			'site_level'           => $site_level,
		) );
	}

	/**
	 * Add additional scripts to Customizer.
	 *
	 * @action customize_controls_print_footer_scripts, 30
	 */
	public function customize_controls_print_footer_scripts() {

		$site_level       = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_site_level( get_current_blog_id() );
		$site_level       = $site_level['level'];
		$settings         = $this->get_settings( get_current_blog_id() );
		$available_themes = isset( $settings[ $site_level ] ) && isset( $settings[ $site_level ]['available'] ) ? array_keys( array_filter( $settings[ $site_level ]['available'] ) ) : array();
		$visible_themes   = isset( $settings[ $site_level ] ) && isset( $settings[ $site_level ]['visible'] ) ? array_keys( array_filter( $settings[ $site_level ]['visible'] ) ) : array();

		if ( ! in_array( get_stylesheet(), $available_themes, true ) && in_array( get_stylesheet(), $visible_themes, true ) ) {
			echo '<script>jQuery(\'.button.button-primary.save\' ).detach();</script>';
		}
	}

	/**
	 * Filter allowed themes.
	 *
	 * @filter pre_option_allowedthemes
	 *
	 * @param array $allowedthemes Allowed themes.
	 *
	 * @return array
	 */
	public function allowed_themes( $allowedthemes ) {
		if ( empty( $allowedthemes ) ) {
			$allowedthemes = array();
		}

		$site_level       = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_site_level( get_current_blog_id() );
		$site_level       = $site_level['level'];
		$settings         = $this->get_settings( get_current_blog_id() );
		$available_themes = isset( $settings[ $site_level ] ) && isset( $settings[ $site_level ]['available'] ) ? array_keys( array_filter( $settings[ $site_level ]['available'] ) ) : array();
		$visible_themes   = isset( $settings[ $site_level ] ) && isset( $settings[ $site_level ]['visible'] ) ? array_keys( array_filter( $settings[ $site_level ]['visible'] ) ) : array();

		$addedthemes = array();

		foreach ( array_unique( array_merge( $available_themes, $visible_themes ) ) as $added_theme ) {
			$addedthemes[ $added_theme ] = true;
		}

		$allowedthemes = array_merge( $allowedthemes, $addedthemes );

		return $allowedthemes;
	}

	/**
	 * Update Customizer display for themes.
	 *
	 * @param mixed $themes Themes to prepare.
	 *
	 * @filter wp_prepare_themes_for_js
	 *
	 * @return array|bool
	 */
	public function prepare_themes_for_js( $themes ) {

		$themes = array_filter( $themes );

		if ( isset( self::$flags['wp_prepare_themes_for_js'] ) &&
		     true === self::$flags['wp_prepare_themes_for_js']
		) {
			return empty( $themes ) ? false : $themes;
		}

		foreach ( $themes as $stylesheet => $theme ) {

			// Nothing to do for empty themes, they will be filtered anyway.
			if ( empty( $theme ) ) {
				continue;
			}

			// Don't allow deleting of themes.
			if ( ! is_customize_preview() ) {
				unset( $themes[ $stylesheet ]['actions']['delete'] );
			}
		}

		// Avoid loop.
		self::$flags['wp_prepare_themes_for_js'] = true;

		$levels = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_levels( true );

		$site_level = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_site_level( get_current_blog_id() );
		$site_level = $site_level['level'];
		$settings   = $this->get_settings( get_current_blog_id() );

		$available_themes = isset( $settings[ $site_level ] ) && isset( $settings[ $site_level ]['available'] ) ? array_keys( array_filter( $settings[ $site_level ]['available'] ) ) : array();
		$visible_themes   = isset( $settings[ $site_level ] ) && isset( $settings[ $site_level ]['visible'] ) ? array_keys( array_filter( $settings[ $site_level ]['visible'] ) ) : array();
		$to_merge         = array();
		foreach ( array_unique( array_merge( $available_themes, $visible_themes ) ) as $added_theme ) {
			$to_merge[] = wp_get_theme( $added_theme );
		}
		$merge_themes = wp_prepare_themes_for_js( $to_merge );

		foreach ( $merge_themes as $theme ) {
			$theme['themecontrol'] = array(
				'allowed' => in_array( $theme['id'], $available_themes, true ),
				'plans'   => array(),
			);

			foreach ( array_keys( $levels ) as $level ) {
				$level_themes = isset( $settings[ $level ] ) && isset( $settings[ $level ]['available'] ) ? array_keys( array_filter( $settings[ $level ]['available'] ) ) : array();

				if ( in_array( $theme['id'], $level_themes, true ) ) {
					$theme['themecontrol']['plans'][ $level ] = $levels[ $level ]['name'];
				}
			}

			$themes[ $theme['id'] ] = $theme;
		}

		return $themes;
	}

	/**
	 * This will add a new action to the Sites list table.
	 *
	 * One of the following constants need to be set true for this row action to take effect.
	 * - MISSION_CONTROL_ALL_TABLES_ACTIONS
	 * - MISSION_CONTROL_THEME_CONTROL_TABLE_ACTION
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
		     ( defined( 'MISSION_CONTROL_THEME_CONTROL_TABLE_ACTION' ) && true === MISSION_CONTROL_THEME_CONTROL_TABLE_ACTION )
		) {
			$url             = add_query_arg( array( 'blog_id' => $blog_id ), $this->get_settings_url() );
			$actions[ $url ] = __( 'Themes', 'mission-control' );
		}

		return $actions;
	}
}
