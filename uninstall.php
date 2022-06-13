<?php

/**
 * Fired when the plugin is uninstalled.
 *
 */

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/sermonview-integration-api.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/sermonview-integration-login.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/sermonview-integration-dashboard.php';

$options = array(
	SermonView_Integration_API::$settings_name,
	SermonView_Integration_Login::$settings_name,
	SermonView_Integration_Dashboard::$settings_name,
);

foreach($options as $option_name) {
	delete_option($option_name);

	// for site options in Multisite
	delete_site_option($option_name);
}
// drop a custom database table
global $wpdb;
$table_name = $wpdb->prefix . 'svi_log';
$wpdb->query("DROP TABLE IF EXISTS " . $table_name);