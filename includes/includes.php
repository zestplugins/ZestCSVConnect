<?php
if ( is_admin() ) {
	// Only admin includes.
	require_once ZYNC_PATH . 'includes/views/features.php';
	require_once ZYNC_PATH . 'includes/admin/class-admin-menu.php';
}
require_once ZYNC_PATH . 'includes/functions.php';
