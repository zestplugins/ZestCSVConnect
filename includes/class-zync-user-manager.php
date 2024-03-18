<?php

if ( ! class_exists( 'ZyncUserManager' ) ) {
	/**
	 * Class ZyncUserManager
	 *
	 * This class handles..
	 */
	class ZyncUserManager {

		/**
		 * ZyncUserManager constructor.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'zync_user_manager_handle_delete_users' ) );
			add_action( 'admin_init', array( $this, 'zync_user_manager_handle_import' ) );
			add_action( 'admin_post_export_users', array( $this, 'zync_user_manager_handle_export' ) );
		}

		/**
		 * Handle user import
		 */
		public function zync_user_manager_handle_import() {
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

		/**
		 * Export users as a CSV file.
		 */
		public function zync_user_manager_handle_export() {
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

		/**
		 * Delete Users.
		 */
		public function zync_user_manager_handle_delete_users() {
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
	}
}

new ZyncUserManager();
