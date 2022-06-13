<?php

/**
 * The core plugin class.
 */
class SermonView_Integration {
	public $plugin_name;
	public $version;
	public $customer;
	private static $debug = false;

	public function __construct() {
		if ( defined( 'SERMONVIEW_INTEGRATION_VERSION' ) ) {
			$this->version = SERMONVIEW_INTEGRATION_VERSION;
		} else {
			$this->version = '0.1.0';
		}
		$this->plugin_name = 'sermonview-integration';
	}
	public function run() {
		$this->init();
	}
	private function init() {
		// Handle the SermonView API
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/sermonview-integration-api.php';
		$this->api = new SermonView_Integration_API($this);
		$this->api->run();

		// Handle SermonView master login
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/sermonview-integration-login.php';
		$this->login = new SermonView_Integration_Login($this);
		$this->login->run();

		// SermonView account stuff
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/sermonview-integration-account.php';
		$this->account = new SermonView_Integration_Account($this);
		$this->account->run();

		// SermonView campaign dashboard
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/sermonview-integration-dashboard.php';
		$this->dashboard = new SermonView_Integration_Dashboard($this);
		$this->dashboard->run();

		// SermonView shopping cart
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/sermonview-integration-shopping-cart.php';
		// $this->cart = new SermonView_Integration_Shopping_Cart($this);
		$this->cart = new SermonView_Integration_Shopping_Cart($this);
		$this->cart->run();

		// Admin stuff
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/sermonview-integration-admin.php';
		$this->admin = new SermonView_Integration_Admin($this);
		$this->admin->run();

		// needed for activation messaging
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/sermonview-integration-activator.php';
		$this->activator = new SermonView_Integration_Activator();

		// helper class for forms
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/sermonview-integration-form.php';

		// updater
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/sermonview-integration-updater.php';
		$this->updater = new SermonView_Integration_Updater($this);
		$this->updater->set_repository('sermonview-integration');
		$this->updater->initialize();

		// Gravity forms add-on
		if (method_exists('GFForms', 'include_feed_addon_framework')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/sermonview-integration-gravity-forms.php';
			GFAddOn::register('SermonView_Integration_Gravity_Forms');
		}
		
		// load user & Gravity Forms plugin integrations
		add_action('init', array(&$this, 'loadUser'));
		add_action('init', array(&$this, 'loadGFForms'));
	}
	public function loadUser() {
		if (is_user_logged_in()) {
			// grab the SV customer object from osC
			$current_user = wp_get_current_user();
			// but only if the API is set up
			if (is_object($this->api)) {
				$this->customer = $this->api->get_sermonview_customer($current_user->user_email);
				if ($this->cart->enable_cart_system()) {
					$this->remote_cart = $this->api->get_cart($this->customer->customer_id);
				}
			}
		}
	}
	public function loadGFForms() {
		// Gravity forms add-on
		if (method_exists('GFForms', 'include_feed_addon_framework')) {
			$gfsv = SermonView_Integration_Gravity_Forms::get_instance();
			$gfsv->loadIntegrations($this);
		}
	}
	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    SermonView_Integration_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public function set_plugin_file($file) {
		$this->plugin_file = $file;
	}

	public function get_plugin_file() {
		return $this->plugin_file;
	}

	public static function debug_log($something) {
		if(self::$debug) {
			$debug_file = plugin_dir_path( __FILE__ ) . '../debug.txt';
			$handle = fopen($debug_file,'a') or die('Cannot open file: ' . $debug_file);
			$line = microtime() . ': ' . $something . "\n";
			fwrite($handle,$line);
			fclose($handle);
		}
	}
}