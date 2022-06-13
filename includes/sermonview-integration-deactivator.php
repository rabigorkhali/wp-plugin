<?php

class SermonView_Integration_Deactivator {
	public static function deactivate() {
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/sermonview-integration-api.php';

		global $wpdb;
		$table_name = SermonView_Integration_API::logTableName();
		$wpdb->query("drop table if exists " . $table_name);


		/** /
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
		/**/
	}
}