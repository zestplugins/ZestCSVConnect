<?php
/**
 * Plugin Name: Zync
 * Plugin URI: https://zestplugins.com/
 * Description: Import and export users easily using CSV files.
 * Tags:  import, export, users, csv export, csv import, user export, user import, user management
 * Version: 1.0
 * Author: zestplugins
 * Author URI: https://github.com/frenziecodes
 * License: GNU General Public License V3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.6
 * Tested up to: 6.4.3
 * Text Domain: zync
 * Domain Path: /languages
 *
 * @package Zync
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Define some essentials constants.
if ( ! defined( 'ZYNC_SLUG' ) ) {
	define( 'ZYNC_SLUG', 'Zync' );
	define( 'ZYNC_HANDLE', 'Zync' );
	define( 'ZYNC_DIR', __DIR__ );
	define( 'ZYNC_PATH', plugin_dir_path( __FILE__ ) );
	define( 'ZYNC_URL', plugin_dir_url( __FILE__ ) );
	define( 'ZYNC_ASSETS_URL', ZYNC_URL . 'assets/' );
	define( 'ZYNC_FILE', __FILE__ );
	define( 'ZYNC_VERSION', '1.0.0' );
	define( 'ZYNC_MIN_PHP', '5.6.0' );
	define( 'ZYNC_MIN_WP', '6.0.0' );
}

// Include other essential constants.
require_once ZYNC_PATH . 'includes/constants.php';

// Include common global functions.
require_once ZYNC_PATH . 'includes/functions.php';
// Include required files.
require_once ZYNC_PATH . 'includes/includes.php';

if ( ! function_exists( 'zyn_fs' ) ) {
	// Create a helper function for easy SDK access.
	function zyn_fs() {
		global $zyn_fs;

		if ( ! isset( $zyn_fs ) ) {
			// Activate multisite network integration.
			if ( ! defined( 'WP_FS__PRODUCT_15198_MULTISITE' ) ) {
				define( 'WP_FS__PRODUCT_15198_MULTISITE', true );
			}

			// Include Freemius SDK.
			require_once dirname(__FILE__) . '/freemius/start.php';

			$zyn_fs = fs_dynamic_init( array(
				'id'                  => '15198',
				'slug'                => 'zync',
				'type'                => 'plugin',
				'public_key'          => 'pk_2f2f8e77d738d6be802a3a7d3a089',
				'is_premium'          => false,
				'has_addons'          => false,
				'has_paid_plans'      => false,
				'menu'                => array(
					'slug'           => 'zync',
					'first-path'     => 'users.php?page=zync_user_manager',
					'support'        => false,
				),
			) );
		}

		return $zyn_fs;
	}

	// Init Freemius.
	zyn_fs();
	// Signal that SDK was initiated.
	do_action( 'zyn_fs_loaded' );
}

/**
 * Add custom action link to the plugin's action links.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function zest_csv_connector_management_plugin_listing_links( $links ) {
	if ( zync_importer_enabled() ) {
		$import_link = '<a href="' . admin_url( 'users.php?page=zync_user_manager' ) . '">' . esc_html__( 'Import users', 'zync' ) . '</a>';
		$links[] = $import_link;
	}
	if ( zest_csv_export_enabled() ) {
		$export_link = '<a href="' . admin_url( 'users.php?page=zync_user_manager&tab=export-tab' ) . '">' . esc_html__( 'Export users', 'zync' ) . '</a>';
		$links[] = $export_link;
	}
	if ( zest_csv_delete_enabled() ) {
		$delete_link = '<a href="' . admin_url( 'users.php?page=zync_user_manager&tab=delete-tab' ) . '">' . esc_html__( 'Delete users', 'zync' ) . '</a>';
		$links[] = $delete_link;
	}
	$settings_link     = '<a href="' . admin_url( 'users.php?page=zync_user_manager' ) . '">' . esc_html__( 'User manager', 'zync' ) . '</a>';
	array_push( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'zest_csv_connector_management_plugin_listing_links' );

/**
 * Redirect to the settings page after plugin activation.
 */
function zest_csv_connector_redirect_to_help_page() {
	if ( is_admin() && get_option( 'zest_csv_connector_activation_redirect', false ) ) {
		delete_option( 'zest_csv_connector_activation_redirect' );
		wp_safe_redirect( admin_url( 'users.php?page=zync_user_manager' ) );
		exit;
	}
}
register_activation_hook( __FILE__, 'zest_csv_connector_set_activation_redirect' );

/**
 * Set the activation redirect flag.
 */
function zest_csv_connector_set_activation_redirect() {
	update_option( 'zest_csv_connector_activation_redirect', true );
}
add_action( 'admin_init', 'zest_csv_connector_redirect_to_help_page' );
