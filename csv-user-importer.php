<?php
/**
 * Plugin Name: Import and Export users via CSV
 * Plugin URI: https://wpnizzle.com/PluginPages/usersync.html
 * Description: Import and export users easily using CSV files.
 * Tags: spam, email, address, harvest, obfuscate, protection, email protection, antispam, email address, encode, encrypt, link, mailto, obfuscate, protect, spambot
 * Version: 0.1
 * Author: WpNizzle
 * Author URI: https://wpnizzle.com/
 * License: GNU General Public License V3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.6
 * Tested up to: 6.2.2
 * Text Domain: csv-user-importer
 * Domain Path: /languages
 */

/**
 * Register the plugin's main menu item
 */
function csv_user_importer_menu() {
    add_submenu_page(
        'users.php',
        'CSV User Manager',
        'CSV User Manager',
        'manage_options',
        'csv_user_importer',
        'csv_user_importer_page'
    );
}
add_action( 'admin_menu', 'csv_user_importer_menu' );

/**
 * Main page of the plugin
 */
function csv_user_importer_page() {
    ?>
    <div class="wrap">
        <h1 class="nav-tab-wrapper">
            <a href="#import-tab" class="nav-tab nav-tab-active">Import</a>
            <a href="#export-tab" class="nav-tab">Export</a>
            <a href="#delete-tab"	class="nav-tab">Delete</a>
        </h1>

        <div id="import-tab" class="tab-content">
            <form method="post" enctype="multipart/form-data">
                <h2>Import Users</h2>
                <?php wp_nonce_field( 'import_users_action', 'import_users_nonce' ); ?>
                <input type="file" name="csv_file" required>
                <p>Specify the column number for each user information used in the CSV file starting from 1:</p>
                <table class="form-table">
                    <tr>
                        <th scope="row">Username:</th>
                        <td><input type="number" name="username_column" min="1" required placeholder="Column number e.g 1"></td>
                    </tr>
                    <tr>
                        <th scope="row">Email:</th>
                        <td><input type="number" name="email_column" min="1" required placeholder="Column number e.g 2"></td>
                    </tr>
                    <tr>
                        <th scope="row">First Name:</th>
                        <td><input type="number" name="first_name_column" min="1" placeholder="Column number e.g 3"></td>
                    </tr>
                    <tr>
                        <th scope="row">Last Name:</th>
                        <td><input type="number" name="last_name_column" min="1" placeholder="Column number e.g 4"></td>
                    </tr>
                    <tr>
                        <th scope="row">Website:</th>
                        <td><input type="number" name="website_column" min="1" placeholder="Column number e.g 5"></td>
                    </tr>
                    <tr>
                        <th scope="row">Role:</th>
                        <td><input type="number" name="role_column" min="1" placeholder="Column number e.g 6"></td>
                    </tr>
                </table>
                <p><input type="checkbox" name="has_header" id="has_header"> <label for="has_header">CSV file has header (skips the first row)</label></p>
                <p><input type="checkbox" name="send_welcome_email" id="send_welcome_email"> <label for="send_welcome_email">Send WordPress new user welcome email with password</label></p>
                <p><input type="checkbox" name="disable_existing_users" id="disable_existing_users"> <label for="disable_existing_users">Do not update existing users</label></p>
                <h3>Email Message </h3>
                <p>N/B password will be included automatically with this custom email.</p>
                <p><textarea name="email_message" rows="5" cols="50"></textarea></p>
                <input type="submit" name="import_users" class="button button-primary" value="Import">
            </form>
        </div>

        <div id="export-tab" class="tab-content" style="display: none;">
            <h2>Export Users</h2>
            <p>Click the button below to export all users as a CSV file.</p>
            <a href="<?php echo esc_url( admin_url( 'admin-post.php?action=export_users' ) ); ?>" class="button button-primary">Export</a>
        </div>

        <div id="delete-tab" class="tab-content" style="display: none;">
            <h2>Delete Users</h2>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('delete_users_action', 'delete_users_nonce'); ?>
                <p>
                    <label for="csv_file">CSV File:</label>
                    <input type="file" name="csv_file" id="csv_file">
                </p>
                <p>
                    <label for="username_column">Username Column:</label>
                    <input type="number" name="username_column" id="username_column" min="1">
                </p>
                <p>
                    <input type="checkbox" name="has_header" id="has_header">
                    <label for="has_header">CSV has header row</label>
                </p>
                <p><input type="submit" name="submit" class="button button-primary" value="Delete Users"></p>
            </form>
        </div>

        <script>
                jQuery(document).ready(function($) {
                        $('.nav-tab-wrapper a').on('click', function(e) {
                                e.preventDefault();
                                var tabId = $(this).attr('href');
                                $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                                $(this).addClass('nav-tab-active');
                                $('.tab-content').hide();
                                $(tabId).show();
                        });

                        // Show the Import tab by default
                        $('#import-tab').show();
                });
        </script>

    </div>
    <?php
}

/**
 * Handle user import
 */
function csv_user_importer_handle_import() {
    if ( isset( $_POST['import_users'] ) && wp_verify_nonce( $_POST['import_users_nonce'], 'import_users_action' ) ) {
        if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
            echo '<div class="error"><p>Please select a CSV file to import.</p></div>';
            return;
        }

        $file = $_FILES['csv_file']['tmp_name'];

        // Check for file errors
        if ( UPLOAD_ERR_OK !==$_FILES['csv_file']['error']  ) {
            echo '<div class="error"><p>Failed to upload the CSV file.</p></div>';
            return;
        }

        // Check file size
        $max_file_size = 1048576; // 1MB
        if ( $_FILES['csv_file']['size'] > $max_file_size ) {
            echo '<div class="error"><p>CSV file size exceeds the maximum limit of 1MB.</p></div>';
            return;
        }

        // // Validate file type
        // $file_info = wp_check_filetype( basename( $file ), array( 'csv' ) );
        // if ( $file_info['ext'] !== 'csv' ) {
        //     echo '<div class="error"><p>Invalid file type. Only CSV files are allowed.</p></div>';
        //     return;
        // }

        $handle = fopen( $file, 'r' );
        if ( false !== $handle ) {

            $username_column        = intval( $_POST['username_column'] ) - 1;
            $email_column           = intval( $_POST['email_column'] ) - 1;
            $first_name_column      = intval( $_POST['first_name_column'] ) - 1;
            $last_name_column       = intval( $_POST['last_name_column'] ) - 1;
            $website_column         = intval( $_POST['website_column'] ) - 1;
            $role_column            = intval( $_POST['role_column'] ) - 1;
            $has_header             = isset( $_POST['has_header'] ) && 'on' === $_POST['has_header'];
            $send_welcome_email     = isset( $_POST['send_welcome_email'] ) && 'on' === $_POST['send_welcome_email'];
            $disable_existing_users = isset( $_POST['disable_existing_users'] ) && 'on' === $_POST['disable_existing_users'];
            $email_message          = $_POST['email_message'];

            if ( $has_header ) {
                // Skip the first row if the CSV has a header.
                fgetcsv( $handle );
            }

            $email_sent = 0; // Counter for successful email sends.

            while ( ( $data = fgetcsv( $handle, 1000, ',' ) ) !== false ) {
                $username   = isset( $data[ $username_column ] ) ? $data[ $username_column ] : '';
                $email      = isset( $data[ $email_column ] ) ? $data[ $email_column ] : '';
                $first_name = isset( $data[ $first_name_column ] ) ? $data[ $first_name_column ] : '';
                $last_name  = isset( $data[ $last_name_column ] ) ? $data[ $last_name_column ] : '';
                $website    = isset( $data[ $website_column ] ) ? $data[ $website_column ] : '';
                $role       = isset( $data[ $role_column ] ) ? $data[ $role_column ] : '';
                $password   = wp_generate_password();

                // Check if the user already exists and skip if required
                if ( $disable_existing_users && email_exists( $email ) ) {
                    continue;
                }

                // Check if the username already exists
                if ( username_exists( $username ) ) {
                continue;
                }

                // Create the new user
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
                    echo '<div class="error"><p>Error creating user: ' . $user_id->get_error_message() . '</p></div>';
                    continue;
                }

                if ( $send_welcome_email ) {
                    if ( csv_user_importer_send_welcome_email( $user_id, $email_message ) ) {
                        $email_sent++;
                    } else {
                        echo '<div class="error"><p>Failed to send the welcome email to user: ' . $email . '</p></div>';
                    }
                }
            }

            fclose( $handle );

            if ( $email_sent > 0 ) {
                echo '<div class="updated"><p>Users imported successfully. ' . $email_sent . ' welcome emails sent.</p></div>';
            } else {
                echo '<div class="updated"><p>Users imported successfully.</p></div>';
            }
        } else {
            echo '<div class="error"><p>Failed to open the CSV file.</p></div>';
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
 * Send welcome email to the newly imported user.
 *
 * @param int    $user_id       The user ID.
 * @param string $email_message The welcome email message.
 *
 * @return bool True if the email was sent successfully, false otherwise.
 */
function csv_user_importer_send_welcome_email( $user_id, $email_message ) {
    $user    = get_user_by( 'ID', $user_id );
    $to      = $user->user_email;
    $subject = 'Welcome to our site!';
    $message = "Dear $user->display_name,\n\n$email_message";

    $headers[] = 'From: Your Website <noreply@example.com>';

    return wp_mail( $to, $subject, $message, $headers );
}

/**
 * Delete Users
 */
function csv_user_importer_handle_delete_users() {
    if (isset($_POST['submit']) && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file'];
        $csv_file = fopen($file['tmp_name'], 'r');

        if ($csv_file) {
            // Skip the header row if it exists
            if (isset($_POST['has_header']) && $_POST['has_header'] == 'on') {
                fgetcsv($csv_file);
            }

            $username_column = isset($_POST['username_column']) ? intval($_POST['username_column']) - 1 : 0;
            $deleted_count = 0;

            while (($data = fgetcsv($csv_file)) !== false) {
                $username = isset($data[$username_column]) ? trim($data[$username_column]) : '';

                if ($username !== '') {
                    // Get the user by username
                    $user = get_user_by('login', $username);

                    if ($user) {
                        // Delete the user
                        if (wp_delete_user($user->ID)) {
                            echo '<p>User deleted successfully: ' . $username . '</p>';
                            $deleted_count++;
                        } else {
                            echo '<p>Failed to delete the user: ' . $username . '</p>';
                        }
                    } else {
                        echo '<p>User not found: ' . $username . '</p>';
                    }
                }
            }

            fclose($csv_file);

            echo '<p>' . $deleted_count . ' users deleted.</p>';
        }
    }
}
add_action('admin_init', 'csv_user_importer_handle_delete_users');

