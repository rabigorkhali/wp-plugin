<?php

class SermonView_Integration_Activator
{
	public function __construct()
	{
		if (get_transient('sermonview-integration-just-activated')) {
			add_action('admin_notices', array(get_called_class(), 'activation_message'));
			delete_transient('sermonview-integration-just-activated');
		}
	}
	public static function activate()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/sermonview-integration-api.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/sermonview-integration-login.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/sermonview-integration-dashboard.php';

		$options = array(
			SermonView_Integration_API::$settings_name,
			SermonView_Integration_Login::$settings_name,
			SermonView_Integration_Dashboard::$settings_name,
		);
		foreach ($options as $option_name) {
			add_option($option_name);
		}

		// Create log table when plugin is installed
		global $wpdb;
		$table_name = SermonView_Integration_API::logTableName();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`date` datetime NOT NULL,
					`command` varchar(256) NOT NULL,
					`submission` text,
					`response` mediumtext NOT NULL,
					`result` varchar(256) NOT NULL,
					`error` varchar(256) DEFAULT NULL,
					`backtrace` mediumtext,
					PRIMARY KEY (`id`)
				) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		set_transient('sermonview-integration-just-activated', true);
	}
	public static function activation_message()
	{
		?>
		<div class="notice notice-success is-dismissible">
			<p>The SermonView Integration plugin has been activated, please check your <a href="admin.php?page=sermonview-integration-api">settings</a>.</p>
		</div>
<?php
	}
}
