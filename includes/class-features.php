<?php

if ( ! class_exists( 'ZyncFeatures' ) ) {
	/**
	 * Class ZyncFeatures
	 *
	 * This class handles the features.
	 */
	class ZyncFeatures {

		/**
		 * ZyncFeatures constructor.
		 */
		public function __construct() {
			add_action( 'wp_ajax_toggle_feature', array( $this, 'toggle_feature_status' ) );
		}

		/**
		 * AJAX handler for toggling feature status
		 */
		public function toggle_feature_status() {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'toggle-feature-nonce' ) ) {
				wp_send_json_error( __( 'Invalid nonce', 'zync' ) );
			}

			$feature = isset( $_POST['feature'] ) ? sanitize_key( $_POST['feature'] ) : '';
			$enable  = isset( $_POST['enable'] ) ? intval( $_POST['enable'] ) : 0;

			if ( empty( $feature ) ) {
				wp_send_json_error( __( 'Invalid feature name', 'zync' ) );
			}

			// Toggle feature status.
			switch ( $feature ) {
				case 'import':
					update_option( 'zync_importer_enabled', $enable );
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
	}
}

new ZyncFeatures();
