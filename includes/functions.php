<?php

/**
 * Settings page for managing features.
 */
function csv_features_settings_page() {
	$features_table = new Zest_CSV_Connector_Features_List_Table();
	$features_table->prepare_items();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Zync Features', 'zync' ); ?></h1>
		<?php $features_table->display(); ?>
	</div>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			document.querySelectorAll('.feature-toggle').forEach(function(button) {
				button.addEventListener('click', function() {
					var feature = this.dataset.feature;
					var action = this.textContent.trim().toLowerCase();
					var data = {
						action: 'toggle_feature',
						feature: feature,
						enable: action === 'enable' ? '1' : '0',
						nonce: '<?php echo wp_create_nonce('toggle-feature-nonce'); ?>'
					};
					fetch(ajaxurl, {
						method: 'POST',
						body: new URLSearchParams(data),
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						}
					}).then(function(response) {
						if (response.ok) {
							return response.json();
						}
						throw new Error('Network response was not ok.');
					}).then(function(data) {
						if (data.success) {
							window.location.reload();
						} else {
							console.error(data.message);
						}
					}).catch(function(error) {
						console.error('There was a problem with the fetch operation:', error.message);
					});
				});
			});
		});
	</script>
	<?php
}

/**
 * Check if import feature is enabled.
 *
 * @return bool Whether import feature is enabled.
 */
function zest_csv_import_enabled() {
	return (bool) get_option( 'zest_csv_import_enabled', 1 );
}

/**
 * Check if export feature is enabled.
 *
 * @return bool Whether export feature is enabled.
 */
function zest_csv_export_enabled() {
	return (bool) get_option( 'zest_csv_export_enabled', 1 );
}

/**
 * Check if delete feature is enabled.
 *
 * @return bool Whether delete feature is enabled.
 */
function zest_csv_delete_enabled() {
	return (bool) get_option( 'zest_csv_delete_enabled', 1 );
}

/**
 * Main page of the plugin
 */
function csv_user_importer_page() {
	?>
	<div class="wrap">
		<h1 class="nav-tab-wrapper">
			<?php if ( zest_csv_import_enabled() ) : ?>
				<a href="#import-tab" class="nav-tab nav-tab-active"><?php esc_html_e( 'Import', 'zync' ); ?></a>
			<?php endif; ?>
			<?php if ( zest_csv_export_enabled() ) : ?>
				<a href="#export-tab" class="nav-tab"><?php esc_html_e( 'Export', 'zync' ); ?></a>
			<?php endif; ?>
			<?php if ( zest_csv_delete_enabled() ) : ?>
				<a href="#delete-tab" class="nav-tab"><?php esc_html_e( 'Delete', 'zync' ); ?></a>
			<?php endif; ?>
		</h1>

		<?php if ( zest_csv_import_enabled() ) : ?>
			<div id="import-tab" class="tab-content">
				<form method="post" enctype="multipart/form-data">
					<h2><?php esc_html_e( 'Import Users', 'zync' ); ?></h2>
					<?php wp_nonce_field( 'import_users_action', 'import_users_nonce' ); ?>
					<input type="file" name="csv_file" required>
					<p><?php esc_html_e( 'Specify the column number for each user information used in the CSV file starting from 1:', 'zync' ); ?></p>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Username:', 'zync' ); ?></th>
							<td><input type="number" name="username_column" min="1" required placeholder="Column number e.g 1"></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Email:', 'zync' ); ?></th>
							<td><input type="number" name="email_column" min="1" required placeholder="Column number e.g 2"></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'First Name:', 'zync' ); ?></th>
							<td><input type="number" name="first_name_column" min="1" placeholder="Column number e.g 3"></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Last Name:', 'zync' ); ?></th>
							<td><input type="number" name="last_name_column" min="1" placeholder="Column number e.g 4"></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Website:', 'zync' ); ?></th>
							<td><input type="number" name="website_column" min="1" placeholder="Column number e.g 5"></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Role:', 'zync' ); ?></th>
							<td><input type="number" name="role_column" min="1" placeholder="Column number e.g 6"></td>
						</tr>
					</table>
					<p><input type="checkbox" name="has_header" id="has_header"> <label for="has_header"><?php esc_html_e( 'CSV file has header (skips the first row)', 'zync' ); ?></label></p>
					<p><input type="checkbox" name="disable_existing_users" id="disable_existing_users"> <label for="disable_existing_users"><?php esc_html_e( 'Do not update existing users', 'zync' ); ?></label></p>
					<input type="submit" name="import_users" class="button button-primary" value="Import">
				</form>
			</div>
		<?php endif; ?>

		<?php if ( zest_csv_export_enabled() ) : ?>
			<div id="export-tab" class="tab-content" style="display: none;">
				<h2><?php esc_html_e( 'Export Users', 'zync' ); ?></h2>
				<p><?php esc_html_e( 'Click the button below to export all users as a CSV file.', 'zync' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=export_users' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Export', 'zync' ); ?></a>
			</div>
		<?php endif; ?>

		<?php if ( zest_csv_delete_enabled() ) : ?>
			<div id="delete-tab" class="tab-content" style="display: none;">
				<h2><?php esc_html_e( 'Delete Users', 'zync' ); ?></h2>
				<form method="post" enctype="multipart/form-data">
					<h2><?php esc_html_e( 'Delete Users', 'zync' ); ?></h2>
					<?php wp_nonce_field( 'delete_users_action', 'delete_users_nonce' ); ?>
					<p>
						<label for="csv_file"><?php esc_html_e( 'CSV File:', 'zync' ); ?></label>
						<input type="file" name="csv_file" id="csv_file">
					</p>
					<p>
						<label for="username_column"><?php esc_html_e( 'Username Column:', 'zync' ); ?></label>
						<input type="number" name="username_column" id="username_column" min="1">
					</p>
					<p>
						<input type="checkbox" name="has_header" id="has_header">
						<label for="has_header"><?php esc_html_e( 'CSV has header row', 'zync' ); ?></label>
					</p>
					<p><input type="submit" name="submit" class="button button-primary" value="Delete Users"></p>
				</form>
			</div>
		<?php endif; ?>

		<script>
			jQuery(document).ready(function( $) {
				$( '.nav-tab-wrapper a' ).on( 'click', function(e) {
					e.preventDefault();
					var tabId = $(this).attr( 'href' );
					$( '.nav-tab-wrapper a' ).removeClass( 'nav-tab-active' );
					$(this).addClass( 'nav-tab-active' );
					$( '.tab-content' ).hide();
					$(tabId).show();
				});

				// Show the first available tab by default
				$( '.nav-tab-wrapper a:first-of-type' ).click();
			});
		</script>

	</div>
	<?php
}
