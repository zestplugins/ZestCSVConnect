<?php

if ( ! class_exists( 'ZyncMenu' ) ) {
	/**
	 * Class ZyncMenu
	 *
	 * This class handles the creation of menus and submenus for the Zync plugin.
	 */
	class ZyncMenu {

		/**
		 * ZyncMenu constructor.
		 *
		 * Adds an action hook to create the admin menu.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
		}

		/**
		 * Adds the main menu and submenus for the Zync plugin.
		 */
		public function add_menu() {
			// Add the main menu.
			add_menu_page(
				__( 'Zync', 'Zync' ),
				__( 'Zync', 'Zync' ),
				'manage_options',
				'zync',
				'zync_user_manager_page',
				'dashicons-admin-users'
			);
			add_submenu_page(
				'zync',
				__( 'Features', 'zesthours' ),
				__( 'Features', 'zesthours' ),
				'manage_options',
				'zync_features',
				'zync_features_page'
			);
			remove_submenu_page( 'zync', 'zync' );
			add_submenu_page(
				'users.php',
				esc_html__( 'Zync users', 'zync' ),
				esc_html__( 'Zync users', 'zync' ),
				'manage_options',
				'zync_user_manager',
				'zync_user_manager_page'
			);
			add_submenu_page(
				'zync',
				__( 'Settings', 'zesthours' ),
				__( 'Settings', 'zesthours' ),
				'manage_options',
				'zync_settings',
				'zync_settings_page'
			);
		}
	}
}

new ZyncMenu();
