<?php
/**
 * Control the site quota for each site level.
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
 * Class MissionControl_Extension_QuotaManager
 */
class QuotaManager extends Extension {

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
		return 'QuotaManager';
	}

	/**
	 * Extension name.
	 *
	 * @return string
	 */
	public function name() {
		return __( 'Quota Manager', 'mission-control' );
	}

	/**
	 * Extension description.
	 *
	 * @return string
	 */
	public function description() {
		return __( 'Set the upload quota for each site level.' );
	}

	/**
	 * Get the menu slug.
	 *
	 * @return string
	 */
	public function menu_slug() {
		return 'missioncontrol_quota_manager';
	}

	/**
	 * Get space allowed for the current site.
	 *
	 * @filter get_space_allowed
	 *
	 * @return int
	 */
	public function get_space_allowed() {
		$settings   = $this->get_settings( get_current_blog_id() );
		$site_level = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_site_level( get_current_blog_id() );
		$site_level = $site_level['level'];

		return ! empty( $settings[ $site_level ] ) ? (int) $settings[ $site_level ]['quota'] : 0;
	}

	/**
	 * Should upload space be checked.
	 *
	 * @filter pre_site_option_upload_space_check_disabled
	 *
	 * @param bool $disabled Check the upload space.
	 *
	 * @return bool
	 */
	public function pre_site_option_upload_space_check_disabled( $disabled ) {
		$settings   = $this->get_settings( get_current_blog_id() );
		$site_level = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_site_level( get_current_blog_id() );
		$site_level = $site_level['level'];

		return ! empty( $settings[ $site_level ] ) && (int) $settings[ $site_level ]['quota'] > 0 ? 0 : $disabled;
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings() {

		$info         = $this->get_info();
		$blog_id      = (int) Utility::get_query( 'blog_id' );
		$settings     = $this->get_settings( get_current_blog_id() );
		$this->notice = '';

		$this->process_form();

		$levels = Plugin::get_extension( 'MissionControl\\Module\\Levels' )->get_levels( true );

		$content = '<div class="wrap quota-manager">';

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
		            wp_nonce_field( 'quota-manager', '_wpnonce', true, false ) .
		            $blog_override .
		            '   <thead>
		                    <tr><th>' . __( 'Level', 'mission-control' ) . '</th><th>' . __( 'Quota', 'mission-control' ) . ' <small>(' . __( '0 for unlimited', 'mission-control' ) . ')</small></th></tr>
		                </thead>
		                <tbody>';

		$counter = 0;
		foreach ( $levels as $key => $level ) {

			$value = isset( $settings[ $key ] ) ? (int) $settings[ $key ]['quota'] : 0;

			$counter += 1;

			$css_class = 0 === $counter % 2 ? 'class="alternate"' : '';
			$content .= '<tr>
			               <td ' . $css_class . '>' . esc_html( $level['name'] ) . '</td>
			               <td ' . $css_class . '><input type="number" min="0"  style="width: 100px;" name="quota[' . $key . ']" value="' . esc_attr( $value ) . '" /> MB</td>
			            </tr>';
		}

		$content .= '   </tbody>
		             </table>';

		$content .= '<div><p><input type="submit" class="button button-primary button-save-quota-manager" value="' . esc_attr__( 'Save Settings', 'mission-control' ) . '" /></p></div>';
		$content .= '</form>';
		$content .= '</div>';

		Utility::output( $content );
	}

	/**
	 * Process the Quota Manager admin page.
	 */
	public function process_form() {

		$post_vars = Utility::post_query( false, 'quota-manager' );
		$settings  = array();

		if ( ! empty( $post_vars ) ) {
			$this->notice = sprintf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', __( 'Settings saved.', 'mission-control' ) );
		}

		$quotas = isset( $post_vars['quota'] ) ? $post_vars['quota'] : array();

		if ( 0 < count( $quotas ) ) {
			$settings['site_override'] = isset( $post_vars['site_override'] );

			foreach ( $quotas as $key => $quota ) {
				$settings[ $key ]['quota'] = (int) $quota;
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
	 * Output space availabled and used in the upload UI.
	 *
	 * @action post-plupload-upload-ui
	 *
	 * @return void
	 */
	public function upload_quota_space_display() {
		if ( ! is_multisite() || ! current_user_can( 'upload_files' ) || get_site_option( 'upload_space_check_disabled' ) ) {
			return;
		}

		$quota = get_space_allowed();
		$used  = get_space_used();

		if ( $used > $quota ) {
			$percentused = '100';
		} else {
			$percentused = ( $used / $quota ) * 100;
		}

		$used        = round( $used, 2 );
		$percentused = number_format( $percentused );

		$text = sprintf(
			__( '%1$s MB (%2$s%%) of %3$s MB Space Used' ),
			number_format_i18n( $used, 2 ),
			$percentused,
			number_format_i18n( $quota )
		);

		Utility::output( $text );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @action missioncontrol_enqueue_scripts
	 */
	public function enqueue_scripts() {

		$acceptable = array(
			'mission-control_page_missioncontrol_quota_manager-network',
			'themes',
		);
		$screen     = get_current_screen();
		if ( ! in_array( $screen->id, $acceptable, true ) ) {
			return;
		}

		$info = $this->get_info();

		wp_enqueue_style( 'missioncontrol_quota_manager', $info['asset_url'] . 'style.css', array(), $this->plugin->info['version'] );

		wp_enqueue_script( 'missioncontrol_quota_manager', $info['asset_url'] . 'script.js', array(
			'hooks',
			'backbone',
			'jquery',
		), $this->plugin->info['version'], false );
	}

	/**
	 * This will add a new action to the Sites list table.
	 *
	 * One of the following constants need to be set true for this row action to take effect.
	 * - MISSION_CONTROL_ALL_TABLES_ACTIONS
	 * - MISSION_CONTROL_QUOTA_MANAGER_TABLE_ACTION
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
		     ( defined( 'MISSION_CONTROL_QUOTA_MANAGER_TABLE_ACTION' ) && true === MISSION_CONTROL_QUOTA_MANAGER_TABLE_ACTION )
		) {
			$url             = add_query_arg( array( 'blog_id' => $blog_id ), $this->get_settings_url() );
			$actions[ $url ] = __( 'Quotas', 'mission-control' );
		}

		return $actions;
	}
}
