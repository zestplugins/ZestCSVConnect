<?php
/**
 * Plugin Name: Zync
 * Plugin URI: https://zestplugins.com/
 * Description: Import and export users easily using CSV files.
 * Tags:  import, export, users, csv export, csv import, user export, user import, user management
 * Version: 1.0
 * Author: zestplugins
 * Author URI: https://zestplugins.com/
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

if ( ! function_exists( 'zes_fs' ) ) {
	// Create a helper function for easy SDK access.
	function zes_fs() {
		global $zes_fs;

		if ( ! isset( $zes_fs ) ) {
			// Activate multisite network integration.
			if ( ! defined( 'WP_FS__PRODUCT_15130_MULTISITE' ) ) {
				define( 'WP_FS__PRODUCT_15130_MULTISITE', true );
			}

			// Include Freemius SDK.
			require_once dirname( __FILE__) . '/freemius/start.php';

			$zes_fs = fs_dynamic_init( array(
				'id'                  => '15130',
				'slug'                => 'Zync',
				'type'                => 'plugin',
				'public_key'          => 'pk_1fabaf2cf4254c0722d54a41ff057',
				'is_premium'          => true,
				'premium_suffix'      => 'starter',
				// If your plugin is a serviceware, set this option to false.
				'has_premium_version' => true,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'trial'               => array(
					'days'               => 7,
					'is_require_payment' => false,
				),
				'menu'                => array(
					'first-path'     => 'users.php?page=csv_user_importer',
					'support'        => false,
				),
			) );
		}

		return $zes_fs;
	}

	// Init Freemius.
	zes_fs();
	// Signal that SDK was initiated.
	do_action( 'zes_fs_loaded' );
}

/**
 * Add custom action link to the plugin's action links.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function zest_csv_connector_management_plugin_listing_links( $links ) {
	if ( zest_csv_import_enabled() ) {
		$import_link = '<a href="' . admin_url( 'users.php?page=csv_user_importer' ) . '">' . esc_html__( 'Import', 'zync' ) . '</a>';
		$links[] = $import_link;
	}
	if ( zest_csv_export_enabled() ) {
		$export_link = '<a href="' . admin_url( 'users.php?page=csv_user_importer&tab=export-tab' ) . '">' . esc_html__( 'Export', 'zync' ) . '</a>';
		$links[] = $export_link;
	}
	if ( zest_csv_delete_enabled() ) {
		$delete_link = '<a href="' . admin_url( 'users.php?page=csv_user_importer&tab=delete-tab' ) . '">' . esc_html__( 'Delete', 'zync' ) . '</a>';
		$links[] = $delete_link;
	}
	$settings_link     = '<a href="' . admin_url( 'users.php?page=csv_user_importer' ) . '">' . esc_html__( 'Settings', 'zync' ) . '</a>';
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
		wp_safe_redirect( admin_url( 'users.php?page=csv_user_importer' ) );
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

/**
 * Handle user import
 */
function csv_user_importer_handle_import() {
	if ( isset( $_POST['import_users'] ) && wp_verify_nonce( $_POST['import_users_nonce'], 'import_users_action' ) ) {
		if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
			echo '<div class="error"><p>' . esc_html__( 'Please select a CSV file to import.', 'zync' ) . '</p></div>';
			return;
		}

		$file = $_FILES['csv_file']['tmp_name'];

		// Check for file errors.
		if ( ! file_exists( $file ) ) {
			echo '<div class="error"><p>' . esc_html__( 'Failed to open the CSV file.', 'zync' ) . '</p></div>';
			return;
		}

		// Check file size.
		$max_file_size = 1048576; // 1MB
		if ( filesize( $file ) > $max_file_size ) {
			echo '<div class="error"><p>' . esc_html__( 'CSV file size exceeds the maximum limit of 1MB.', 'zync' ) . '</p></div>';
			return;
		}

		$handle = fopen( $file, 'r' );
		if ( false !== $handle ) {

			// Check if the index exists before accessing it and then convert to integer.
			$username_column        = isset( $_POST['username_column'] ) ? ( intval( $_POST['username_column'] ) - 1 ) : 0;
			$email_column           = isset( $_POST['email_column'] ) ? ( intval( $_POST['email_column'] ) - 1 ) : 0;
			$first_name_column      = isset( $_POST['first_name_column'] ) ? ( intval( $_POST['first_name_column'] ) - 1 ) : 0;
			$last_name_column       = isset( $_POST['last_name_column'] ) ? ( intval( $_POST['last_name_column'] ) - 1 ) : 0;
			$website_column         = isset( $_POST['website_column'] ) ? ( intval( $_POST['website_column'] ) - 1 ) : 0;
			$role_column            = isset( $_POST['role_column'] ) ? ( intval( $_POST['role_column'] ) - 1 ) : 0;
			$has_header             = isset( $_POST['has_header'] ) && 'on' === $_POST['has_header'];
			$disable_existing_users = isset( $_POST['disable_existing_users'] ) && 'on' === $_POST['disable_existing_users'];

			if ( $has_header ) {
				// Skip the first row if the CSV has a header.
				fgetcsv( $handle );
			}

			while ( ( $data = fgetcsv( $handle, 1000, ',' ) ) !== false ) {
				$username   = isset( $data[ $username_column ] ) ? $data[ $username_column ] : '';
				$email      = isset( $data[ $email_column ] ) ? $data[ $email_column ] : '';
				$first_name = isset( $data[ $first_name_column ] ) ? $data[ $first_name_column ] : '';
				$last_name  = isset( $data[ $last_name_column ] ) ? $data[ $last_name_column ] : '';
				$website    = isset( $data[ $website_column ] ) ? $data[ $website_column ] : '';
				$role       = isset( $data[ $role_column ] ) ? $data[ $role_column ] : '';
				$password   = wp_generate_password();

				// Check if the user already exists and skip if required.
				if ( $disable_existing_users && email_exists( $email ) ) {
					continue;
				}

				// Check if the username already exists.
				if ( username_exists( $username ) ) {
					continue;
				}

				// Create the new user.
				$user_id = wp_insert_user(
					array(
						'user_login' => $username,
						'user_email' => $email,
						'first_name' => $first_name,
						'last_name'  => $last_name,
						'user_url'   => $website,
						'role'       => $role,
						'user_pass'  => $password,
					)
				);

				if ( is_wp_error( $user_id ) ) {
					echo '<div class="error"><p>' . esc_html__( 'Error creating user:', 'zync' ) . ' ' . esc_html( $user_id->get_error_message() ) . '</p></div>';
					continue;
				}
			}

			fclose( $handle );
			echo '<div class="updated"><p>' . esc_html__( 'Users imported successfully.', 'zync' ) . '</p></div>';
		} else {
			echo '<div class="error"><p>' . esc_html__( 'Failed to open the CSV file.', 'zync' ) . '</p></div>';
		}
	}
}
add_action( 'admin_init', 'csv_user_importer_handle_import' );

/**
 * Export users as a CSV file.
 */
function csv_user_importer_handle_export() {
	if ( isset( $_GET['action'] ) && 'export_users' === $_GET['action'] ) {
		$users    = get_users();
		$csv_data = "Username,Email,First Name,Last Name,Website,Role\n";

		foreach ( $users as $user ) {
			$username   = $user->user_login;
			$email      = $user->user_email;
			$first_name = $user->first_name;
			$last_name  = $user->last_name;
			$website    = $user->user_url;
			$role       = implode( ', ', $user->roles );

			$csv_data .= "$username,$email,$first_name,$last_name,$website,$role\n";
		}

		$timestamp = gmdate( 'Y-m-d' );
		$filename  = "users_export_$timestamp.csv";

		header( 'Content-Type: application/csv' );
		header( "Content-Disposition: attachment; filename=$filename" );

		echo $csv_data;
		exit();
	}
}
add_action( 'admin_post_export_users', 'csv_user_importer_handle_export' );

/**
 * Delete Users
 */
function csv_user_importer_handle_delete_users() {
	if ( isset( $_POST['submit'] ) && isset( $_FILES['csv_file'] ) && wp_verify_nonce( wp_unslash( $_POST['delete_users_nonce'], 'delete_users_action' ) ) ) {
		$file = $_FILES['csv_file'];
		$csv_file = fopen( $file['tmp_name'], 'r' );

		if ( $csv_file ) {
			// Skip the header row if it exists.
			if ( isset( $_POST['has_header'] ) && wp_unslash( $_POST['has_header'] ) === 'on' ) {
				fgetcsv( $csv_file );
			}

			$username_column = isset( $_POST['username_column'] ) ? intval( $_POST['username_column'] ) - 1 : 0;
			$deleted_count   = 0;

			while ( ( $data = fgetcsv( $csv_file ) ) !== false ) {
				$username = isset( $data[ $username_column ] ) ? trim( $data[ $username_column ] ) : '';

				if ( '' !== $username ) {
					// Get the user by username.
					$user = get_user_by( 'login', $username );

					if ( $user ) {
						// Delete the user.
						if ( wp_delete_user( $user->ID ) ) {
							printf(
								'<p>%s %s</p>',
								esc_html__( 'User deleted successfully:', 'zync' ),
								esc_html( $username )
							);
							$deleted_count++;
						} else {
							printf(
								'<p>%s %s</p>',
								esc_html__( 'Failed to delete the user:', 'zync' ),
								esc_html( $username )
							);
						}
					} else {
						printf(
							'<p>%s %s</p>',
							esc_html__( 'User not found:', 'zync' ),
							esc_html( $username )
						);
					}
				}
			}

			fclose( $csv_file );

			printf(
				'<p>%d %s</p>',
				esc_html( $deleted_count ),
				esc_html__( 'users deleted.', 'zync' )
			);
		}
	}
}
add_action( 'admin_init', 'csv_user_importer_handle_delete_users' );

/**
 * AJAX handler for toggling feature status
 */
function toggle_feature_status() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'toggle-feature-nonce' ) ) {
		wp_send_json_error( __( 'Invalid nonce', 'zync' ) );
	}

	$feature = isset( $_POST['feature'] ) ? sanitize_key( $_POST['feature'] ) : '';
	$enable = isset( $_POST['enable'] ) ? intval( $_POST['enable'] ) : 0;

	if ( empty( $feature ) ) {
		wp_send_json_error( __( 'Invalid feature name', 'zync' ) );
	}

	// Toggle feature status.
	switch ( $feature ) {
		case 'import':
			update_option( 'zest_csv_import_enabled', $enable );
			break;
		case 'export':
			update_option( 'zest_csv_export_enabled', $enable );
			break;
		case 'delete':
			update_option( 'zest_csv_delete_enabled', $enable );
			break;
		// Add more feature toggling logic as needed.
	}

	wp_send_json_success( __( 'Feature status updated successfully', 'zync' ) );
}
add_action( 'wp_ajax_toggle_feature', 'toggle_feature_status' );
