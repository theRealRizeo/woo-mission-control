<?php
/**
 * This file handles the MissionControl levels system.
 *
 * Levels an internal extension (a module) that is always enabled.
 *
 * @package MissionControl/Module
 */

namespace MissionControl\Module;
use MissionControl\Base;
use MissionControl\Plugin;
use MissionControl\Utility;

/**
 * Class MissionControl_Module_Extensions
 */
class Levels extends Base {

	/**
	 * The MissionControl Levels API.
	 *
	 * @var Levels $api
	 */
	private $api;

	/**
	 * Reference to the plugin
	 *
	 * @var Plugin
	 */
	public $plugin;

	/**
	 * Factory method.
	 *
	 * @param Plugin $plugin The plugin.
	 *
	 * @return Levels
	 */
	public static function init( $plugin ) {
		$module_levels = new self( $plugin );

		return $module_levels;
	}

	/**
	 * Levels constructor.
	 *
	 * @param Plugin $plugin The plugin.
	 */
	public function __construct( $plugin ) {
		parent::__construct();
		$this->hook_by_reflection();
		$this->plugin = $plugin;
		add_action( 'woocommerce_checkout_subscription_created', array( $this, 'subscription_created' ), 10, 3 );
		add_action( 'woocommerce_subscription_status_updated', array( $this, 'subscription_status' ), 10, 3 );
	}

	function subscription_created( $subscription, $order, $cart ) {
		if ( is_main_site() ) {
			$current_site 	= get_current_site();
			$domain 		= $current_site->domain;

			$meta 	= apply_filters( 'signup_create_blog_meta', array( 'lang_id' => 1, 'public' => 0, 'archived' => 1 ) );
			$levels = $this->get_levels();
			foreach ( $levels as $key => $level ) {
				if ( $subscription->has_product( $level['subscription'] ) ) {
					$path 	= get_post_meta( $order->get_id(), '_billing_site_path', true );
					$title 	= get_post_meta( $order->get_id(), '_billing_site_title', true );
					if ( is_subdomain_install() ) {
						$domain = $path . '.' . preg_replace( '|^www\\.|', '', $domain );
					}
					$new_blog_id = wpmu_create_blog( $domain, $path, $title, $subscription->get_user_id(), $meta );
					if ( $new_blog_id ) {
						$site_level = $level;
						$site_level['level'] 		  = $key;
						$site_level['previous_level'] = 'unassigned';
						$site_level['can_expire']     = false;
						$site_level['expiry_date']    = '';
						$this->update_site_level( $new_blog_id, $site_level, 'Registered' );
						update_post_meta( $subscription->get_id(), '_subscription_site', $new_blog_id );
						update_post_meta( $order->get_id(), '_subscription_site', $new_blog_id );
					}
				}
			}
		}
	}

	function subscription_status( $subscription, $to_status, $from_status ) {
		if ( is_main_site() ) {
			$site_id = get_post_meta( $subscription->get_id(), '_subscription_site', true );
			if ( $site_id ) {
				if ( $to_status == 'active' ) {
					update_blog_status( $site_id, 'archived', 0 );
					update_blog_status( $site_id, 'public', 1 );
				} else {
					update_blog_status( $site_id, 'archived', 1 );
					update_blog_status( $site_id, 'public', 0 );
				}
			}
		}
	}

	/**
	 * Add the "Edit Levels" menu option.
	 *
	 * @action missioncontrol_submenu
	 *
	 * @param mixed $parent The menu parent.
	 */
	public function add_levels_menu( $parent ) {

		add_submenu_page( $parent, __( 'Edit Levels', 'mission-control' ), __( 'Edit Levels', 'mission-control' ), 'manage_options', 'missioncontrol_levels', array(
			$this,
			'render_levels_page',
		) );
		do_action( 'missioncontrol_submenu_levels_rendered' );
	}

	/**
	 * The Levels menu page.
	 */
	public function render_levels_page() {
		$content = '<div class="wrap"><h1>' . esc_html__( 'Site Levels', 'mission-control' ) . '</h1>';
		$content .= '<div class="level-notices"></div>';
		$content .= '<p class="description">' . esc_html__( 'Levels are the basic building blocks of Mission Control. Define your levels here and apply settings to these levels using Mission Control plugin extensions.', 'mission-control' ) . '</p>';
		$content .= '<p class="description">' . esc_html__( 'Activate extensions from the "Mission Control > Settings" or using other third-party plugins.', 'mission-control' ) . '</p>';

		do_action( 'missioncontrol_levels_admin_pre' );
		$content = apply_filters( 'missioncontrol_levels_admin_pre_content', $content );

		/**
		 * This div will be updated via the Levels API.
		 *
		 * DO NOT REMOVE this div. It's "id" is very important.
		 */
		$content .= '<div id="levels-settings-page">' . esc_html__( 'Retrieving levels...', 'mission-control' ) . '</div></div>';

		do_action( 'missioncontrol_levels_admin_post' );
		$content = apply_filters( 'missioncontrol_levels_admin_post_content', $content );

		Utility::output( $content );
	}

	/**
	 * Update MissionControl levels.
	 *
	 * @param mixed $levels     New level settings.
	 * @param bool  $old_levels Old level settings.
	 */
	public function update_levels( $levels, $old_levels = false ) {

		$level_settings = array();

		foreach ( $levels as $level ) {
			$slug                    = $this->make_slug( sanitize_text_field( $level['name'] ) );
			$level_settings[ $slug ] = array(
				'name'        => isset( $level['name'] ) ? sanitize_text_field( $level['name'] ) : sprintf( __( 'Level "%s"', 'mission-control' ), $slug ),
				'subscription'=> isset( $level['subscription'] ) ? sanitize_text_field( $level['subscription'] ) : 0,
				'description' => isset( $level['description'] ) ? sanitize_text_field( $level['description'] ) : sprintf( __( 'Level "%s" description.', 'mission-control' ), $slug ),
			);
		}

		if ( empty( $old_levels ) ) {
			$old_levels = $this->plugin->get_setting( 'levels', $this->default_levels() );
		}

		$this->plugin->update_settings( 'levels', $level_settings );
		do_action( 'missioncontrol_levels_update_levels', $level_settings, $old_levels );
	}

	/**
	 * Register the Levels API.
	 *
	 * @action missioncontrol_register_endpoints
	 */
	public function api_endpoint() {
		$this->api = \MissionControl\API\Levels::init( $this );
		$this->api->register_routes();
	}

	/**
	 * Enqueue scripts for the Levels API.
	 *
	 * @action missioncontrol_enqueue_scripts
	 */
	public function add_scripts() {

		wp_enqueue_script( 'missioncontrol_levels', $this->plugin->info['assets_url'] . 'js/missioncontrol/levels.js', array(
			'missioncontrol',
			'hooks',
			'underscore',
			'jquery-ui-sortable',
		), $this->plugin->info['version'], false );

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );

		wp_localize_script( 'missioncontrol_levels', '_MissionControl_Levels', array(
			'labels' => array(
				'col_level'                 => __( 'Level', 'mission-control' ),
				'col_name'                  => __( 'Name', 'mission-control' ),
				'col_subscription'          => __( 'WooComerce Subscription', 'mission-control' ),
				'col_description'           => __( 'Description', 'mission-control' ),
				'save_levels'               => __( 'Save Levels', 'mission-control' ),
				'new_level'                 => __( 'New Level', 'mission-control' ),
				'untitled_name'             => __( 'Untitled', 'mission-control' ),
				'untitled_description'      => __( 'Level description.', 'mission-control' ),
				'level_delete_confirm'      => __( 'Are you sure you want to delete this level. Please note that other levels and level rules may be effected by this action.', 'mission-control' ),
				'levels_updated'            => __( 'Levels updated.', 'mission-control' ),
				'site_updated'              => __( 'Site updated.', 'mission-control' ),
				'reorder_warning'           => __( 'NOTE: Level order does not determine a level\'s importance as all settings are assigned per level, however, other plugins may take advantage of the level order.<br/>WARNING: If you delete a level it may impact on existing sites. Please make sure you test.', 'mission-control' ),
				'site_level_expire_setting' => __( 'Level has expiry', 'mission-control' ),
				'site_level_expire_text'    => __( 'This level will expire on', 'mission-control' ),
				'site_level_expire_revert'  => __( 'After expiry, revert to', 'mission-control' ),
				'site_level_current'        => __( 'Current level', 'mission-control' ),
				'day'                       => __( 'Day', 'mission-control' ),
				'month'                     => __( 'Month', 'mission-control' ),
				'year'                      => __( 'Year', 'mission-control' ),
			),
			'levels' => $this->get_levels( true ),
			'inputs' => array(
				'subscriptions' => $this->list_subscriptions()
			)
		) );

		wp_enqueue_style( 'missioncontrol_levels', $this->plugin->info['assets_url'] . 'css/missioncontrol/levels.css', array(), $this->plugin->info['version'] );
	}

	/**
	 * List subscriptions
	 * Returns select options of subscriptions
	 * 
	 * @since 1.0.0
	 * 
	 * @return string
	 */
	public function list_subscriptions() {
		$options 	= "";
		if ( Dependencies::woocommerce_subs_active_check() ) {
			$options 	= "<option value='0'>" . __( 'Select One', 'mission-control' ) . "</option>";
			$products = wc_get_products( array(
				'limit' 	=> 10,
				'orderby' 	=> 'date',
				'order' 	=> 'DESC'
			) );
			foreach ( $products as $product ) {
				if ( $product->is_type( array( 'subscription', 'subscription_variation', 'variable-subscription' ) ) ) {
					$options .= "<option value='{$product->get_id()}'>{$product->get_name()}</option>";
				}
			}
		}
		return $options;
	}

	/**
	 * Get the registered levels.
	 *
	 * @param bool $zero true includes an unassigned level, false only includes registered levels.
	 *
	 * @return array|mixed|void
	 */
	public function get_levels( $zero = false ) {
		$levels = $this->plugin->get_setting( 'levels', $this->default_levels() );

		if ( true === $zero ) {
			$levels = array_merge(
				array(
					'unassigned' => array(
						'name'        => __( 'Unassigned', 'mission-control' ),
						'subscription'=> 0,
						'description' => __( 'This site has no assigned levels. Rules can still be applied to "unassigned" sites.', 'mission-control' ),
					),
				),
				$levels
			);
		}

		return $levels;
	}

	/**
	 * Get the default levels if none are registered.
	 *
	 * @return mixed|void
	 */
	public function default_levels() {

		$defaults = apply_filters( 'missioncontrol_settings_default_levels', array(
			'basic'   => array(
				'name'        => __( 'Basic', 'mission-control' ),
				'subscription'=> 0,
				'description' => __( 'Basic site with limited features. A great starting point for a website., ', 'mission-control' ),
			),
			'premium' => array(
				'name'        => __( 'Premium', 'mission-control' ),
				'subscription'=> 0,
				'description' => __( 'Premium site with additional features and less restrictions. Take your website to the next level.', 'mission-control' ),
			),
		) );

		return $defaults;
	}

	/**
	 * Level utility to create level slugs.
	 *
	 * @param mixed $value The original string to turn into a slug.
	 *
	 * @return mixed|string
	 */
	public function make_slug( $value ) {
		$slug = preg_replace( '/\s|\W/', '_', $value );
		$slug = strtolower( $slug );

		return $slug;
	}

	/**
	 * Add the Mission Control column to the 'All Sites' page.
	 *
	 * @filter wpmu_blogs_columns
	 *
	 * @param mixed $columns Original columns.
	 *
	 * @return array
	 */
	public function add_site_listing_columns( $columns ) {
		$inserted = array( 
			'site_level' => '<div style="text-align: left;">' . __( 'Mission Control', 'mission-control' ) . '</div>',
			'site_sub'	 => '<div style="text-align: left;">' . __( 'Subscription', 'mission-control' ) . '</div>'
		 );

		$columns = array_merge(
			array_slice( $columns, 0, 2 ),
			$inserted,
			array_slice( $columns, 2 )
		);

		return $columns;
	}

	/**
	 * Add the details for the Mission Control column on the 'All Sites' page.
	 *
	 * @action manage_sites_custom_column
	 *
	 * @param mixed $column_name The current column.
	 * @param int   $blog_id     The id of the site (in the row).
	 */
	public function add_site_listing_column_details( $column_name, $blog_id ) {
		if ( 'site_level' === $column_name ) {
			$level         = $this->get_site_level( $blog_id );
			$levels        = $this->get_levels( true );

			// Deal with levels that may no longer exist.
			if ( isset( $level['level'] ) && ! array_key_exists( $level['level'], $levels ) ) {
				if ( ! empty( $level['revert_level'] ) && array_key_exists( $level['revert_level'], $levels ) ) {
					$level['level'] = $level['revert_level'];
				} else {
					$level['level'] = 'unassigned';
				}
				$this->update_site_level( $blog_id, $level, 'Error detecting previous level.' );
			}

			$level_details = apply_filters( 'missioncontrol_site_level_details', '' );
			$level_details = empty( $level_details ) ? '&nbsp;' : wp_kses( $level_details, wp_kses_allowed_html() );

			if ( isset( $level['expiry_date'] ) ) {
				$expires = '<small style="float: right;">' . sprintf( __( 'Exp. %s', 'mission-control' ), date( 'Y/m/d', (int) $level['expiry_date'] ) ) . '</small>';
				$expires = empty( $level['expiry_date'] ) ? '' : $expires;
			} else {
				$expires = '';
			}

			// Level Description and Details.
			Utility::output( '<strong>' . $levels[ $level['level'] ]['name'] . '</strong> ' . $expires );
			if ( 'excerpt' === Utility::get_query( 'mode' ) ) {
				Utility::output( '<p>' . $level_details . '</p>' );
			}

			// Row Actions.
			$settings = '';

			// Allow extensions to add additional row actions.
			$level_actions = apply_filters( 'missioncontrol_site_level_actions', array(), $blog_id );

			foreach ( $level_actions as $action_url => $action_label ) {

				$action_label = wp_kses( $action_label, wp_kses_allowed_html() );
				$action_url   = esc_url( $action_url );
				$settings .= ' | <span class="edit"><a href="' . $action_url . '">' . $action_label . '</a></span>';
			}

			$content = '<div class="row-actions">
						<span class="edit"><a href="' . esc_url( network_admin_url( 'admin.php?page=missioncontrol_edit_site_level&blog_id=' . $blog_id ) ) . '">' . __( 'Edit Level', 'mission-control' ) . '</a></span>'
			           . $settings . '
				 </div>';

			Utility::output( $content );
		} else if ( 'site_sub' === $column_name ) {
			switch_to_blog( $blog_id );
			$email = get_bloginfo( 'admin_email' );
			restore_current_blog();

			$user = get_user_by( 'email', $email );
			if ( $user && function_exists( 'wcs_get_users_subscriptions' ) ) {
				$users_subscriptions = wcs_get_users_subscriptions( $user->ID );
				if ( count( $users_subscriptions ) > 0 ) {
					foreach ( $users_subscriptions as $subscription ) {
						Utility::output( '<a href="'.$subscription->get_view_order_url().'">'.$subscription->get_status() .'</a>' );
					} 
				} else {
					Utility::output( __( 'No Subscription', 'mission-control' ) );
				}
			}
		}
	}

	/**
	 * Get the current level for a site.
	 *
	 * @param int $blog_id The blog id.
	 *
	 * @return mixed|void
	 */
	public function get_site_level( $blog_id ) {

		$level_details = $this->plugin->get_site_setting( $blog_id, 'level_details', array(
			'level'          => 'unassigned',
			'previous_level' => 'unassigned',
			'revert_level'   => 'unassigned',
			'can_expire'     => false,
			'expiry_date'    => '',
		) );

		// Check validity and demote if required.
		$now = date( 'Y-m-d', time() );
		$now = strtotime( $now );

		// If a level can expire, check it for expiration.
		if ( isset( $level_details['can_expire'] ) && ! empty( $level_details['can_expire'] ) && $now > $level_details['expiry_date'] && $level_details['level'] !== $level_details['revert_level'] ) {
			$level_details['previous_level'] = $level_details['level'];
			$level_details['level']          = $level_details['revert_level'];
			$level_details['can_expire']     = false;
			$level_details['expiry_date']    = '';
			$this->update_site_level( $blog_id, $level_details, 'TIME_ELAPSED' );
			do_action( 'missioncontrol_site_level_expired', $blog_id, $level_details );
		}

		return $level_details;
	}

	/**
	 * Update a particular site's level.
	 *
	 * @param int   $blog_id The site to update.
	 * @param mixed $data    The new value.
	 * @param bool  $reason  An optional reason why the site changed.
	 */
	public function update_site_level( $blog_id, $data, $reason = false ) {
		$this->plugin->update_site_settings( $blog_id, 'level_details', $data, $reason );
		do_action( 'missioncontrol_site_level_updated', $blog_id, $data, $reason );
	}

	/**
	 * The level pages menu page.
	 *
	 * @action network_admin_menu
	 * @action admin_menu
	 */
	public function level_pages_init() {
		add_submenu_page( 'options.php', __( 'Edit Site Level', 'mission-control' ),
			__( 'Edit Site Level', 'mission-control' ),
			'manage_options',
			'missioncontrol_edit_site_level',
			array( $this, 'render_edit_site_level' )
		);

		do_action( 'missioncontrol_level_pages_init', 'missioncontrol_edit_site_level' );
	}

	/**
	 * Render the page for editing a site's level.
	 */
	public function render_edit_site_level() {

		// No ID passed in.
		if ( null === Utility::get_query( 'blog_id' ) ) {
			wp_die( esc_html__( 'You are trying to edit a site\'s levels, but there is no site specified. Did you mean to do this?', 'mission-control' ) );

			return;
		}

		// Non existing blog passed in.
		$blog_id = (int) Utility::get_query( 'blog_id' );
		$blogname = get_blog_option( $blog_id, 'blogname' );
		if ( empty( $blog_id ) || empty( $blogname ) ) {
			wp_die( sprintf( esc_html__( 'The blog you are trying to edit does not appear to exist. Please try to select one from %s.', 'mission-control' ), '<a href="' . esc_url( network_admin_url( 'sites.php' ) ) . '">' . esc_html__( '"All Sites"', 'mission-control' ) . '</a>' ) );

			return;
		}

		$content = '<div class="wrap"><h1>' . sprintf( esc_html__( 'Edit Site Level : %s [ID: %d]', 'mission-control' ), $blogname, $blog_id ) . '</h1>';
		$content .= '<div class="level-notices"></div>';
		do_action( 'missioncontrol_edit_site_level_pre' );
		$content = apply_filters( 'missioncontrol_edit_site_level_pre_content', $content );

		/**
		 * This div will be updated via the Levels API.
		 *
		 * DO NOT REMOVE this div. It's "id" is very important.
		 */
		$content .= '<div id="edit-site-level-page" data-id="' . (int) $blog_id . '">
						<div class="init-element">' . esc_html__( 'Retrieving site information...', 'mission-control' ) . '</div>
					</div>
					<div><input type="button" class="button button-primary button-save-site-level-settings" value="' . esc_attr__( 'Save Level Settings', 'mission-control' ) . '" /></div>
			</div>';

		do_action( 'missioncontrol_edit_site_level_post' );
		$content = apply_filters( 'missioncontrol_edit_site_level_post_content', $content );

		Utility::output( $content );
	}

	/**
	 * Update parent file for non-menu pages.
	 *
	 * @filter parent_file
	 *
	 * @param mixed $parent_file The parent file.
	 *
	 * @return mixed
	 */
	public function parent_file( $parent_file ) {
		$page = Utility::get_query( 'page' );
		if ( 'missioncontrol_edit_site_level' === $page ) {
			return 'missioncontrol_main';
		}
		return $parent_file;
	}

	/**
	 * Update submenu file for non-menu pages.
	 *
	 * @filter submenu_file
	 *
	 * @param mixed $submenu_file Submenu file.
	 * @param mixed $parent_file Parent file.
	 *
	 * @return mixed
	 */
	public function submenu_file( $submenu_file, $parent_file ) {
		$page = Utility::get_query( 'page' );

		if ( 'missioncontrol_edit_site_level' === $page && 'missioncontrol_main' === $parent_file ) {
			return 'sites.php?missioncontrol';
		}
		return $submenu_file;
	}

	/**
	 * Force Mission Control menu.
	 *
	 * @action admin_menu
	 */
	public function alter_all_sites_menu() {
		$page = Utility::get_query( 'page' );
		if ( 'missioncontrol_edit_site_level' === $page ) {
			Utility::force_missioncontrol_menu();
		}
	}
}

