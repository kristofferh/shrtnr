<?php
	// Make sure this is a legitimate uninstall request
	if(!defined( 'ABSPATH') || !defined('WP_UNINSTALL_PLUGIN') || !current_user_can('delete_plugins')) {
		exit();
	}
	
	function shrtnr_uninstall() {
		global $wpdb;
		$tables = array('shrtnr', 'shrtnr_stats');
		foreach($tables as $table) {
			$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . $table);
		}
		// this should already be done when deactivating plugin, but do it again just to be sure
		delete_option('shrtnr_version');
		delete_option('shrtnr_settings');
	}

	shrtnr_uninstall();
	
?>