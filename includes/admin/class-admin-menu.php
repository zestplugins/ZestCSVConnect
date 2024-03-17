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
				'zync_menu',
				'csv_user_importer_page',
				'dashicons-admin-users'
			);
			add_submenu_page(
				'zync_menu',
				__( 'Features', 'zesthours' ),
				__( 'Features', 'zesthours' ),
				'manage_options',
				'csv_features_settings',
				'csv_features_settings_page'
			);
			remove_submenu_page( 'zync_menu', 'zync_menu' );
			add_submenu_page(
				'users.php',
				esc_html__( 'Zync users', 'zync' ),
				esc_html__( 'Zync users', 'zync' ),
				'manage_options',
				'csv_user_importer',
				'csv_user_importer_page'
			);
		}
	}
}

new ZyncMenu();
