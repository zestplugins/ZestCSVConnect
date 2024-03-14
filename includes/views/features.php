<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Zest_CSV_Connector_Features_List_Table extends WP_List_Table {
	/**
	 * Prepare the feature items for the table
	 */
	public function prepare_items() {
		// Define feature columns.
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		// Populate feature items.
		$data = $this->feature_data();

		// Register column headers.
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Paginate feature items.
		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->items = array_slice( $data, ( $current_page - 1 ) * $per_page, $per_page );
	}

	/**
	 * Define feature columns
	 */
	public function get_columns() {
		$columns = array(
			'cb'            => '<input type="checkbox" />',
			'name'          => __( 'Name', 'zest-csv-connector' ),
			'description'   => __( 'Description', 'zest-csv-connector' ),
			'enabled'       => __( 'Status', 'zest-csv-connector' ),
		);

		return $columns;
	}

	/**
	 * Retrieve feature data
	 */
	private function feature_data() {
		// Retrieve feature settings with documentation links.
		$features = array(
			array(
				'name'          => 'import',
				'description'   => __( 'Import Users using CSV files', 'zest-csv-connector' ),
				'enabled'       => zest_csv_import_enabled(),
				'documentation' => 'http://zestplugins.com/how-to-import-wordpress-users-zestcsvconnect/',
			),
			array(
				'name'          => 'export',
				'description'   => __( 'Export Users to CSV files', 'zest-csv-connector' ),
				'enabled'       => zest_csv_export_enabled(),
				'documentation' => 'https://example.com/export-documentation',
			),
			array(
				'name'          => 'delete',
				'description'   => __( 'Delete Users using CSV files', 'zest-csv-connector' ),
				'enabled'       => zest_csv_delete_enabled(),
				'documentation' => 'https://example.com/delete-documentation',
			),
			// Add more features as needed.
		);

		return $features;
	}

	/**
	 * Render the feature name column.
	 */
	public function column_name( $item ) {
		$actions = array(
			'toggle' => sprintf( '<a class="feature-toggle" data-feature="%s">%s</a>', $item['name'], $item['enabled'] ? __( 'Deactivate', 'zest-csv-connector' ) : __( 'Activate', 'zest-csv-connector' ) ),
		);

		return sprintf( '%1$s %2$s', $item['name'], $this->row_actions( $actions ) );
	}

	/**
	 * Render the feature description column with "View Documentation" link.
	 */
	public function column_description( $item ) {
		$description = $item['description'];
		$documentation_link = isset( $item['documentation'] ) ? $item['documentation'] : '';

		// Render the description with documentation link.
		$output = $description;
		if ( ! empty( $documentation_link ) ) {
			$output .= '<br><a href="' . esc_url( $documentation_link ) . '" target="_blank">' . __( 'View Documentation', 'zest-csv-connector' ) . '</a>';
		}

		return $output;
	}

	/**
	 * Render the feature status column.
	 */
	public function column_enabled( $item ) {
		return $item['enabled'] ? __( 'Enabled', 'zest-csv-connector' ) : __( 'Disabled', 'zest-csv-connector' );
	}
}
