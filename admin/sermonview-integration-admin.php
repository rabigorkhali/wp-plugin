<?php

/* * ***************************************************

  SermonView Integration Admin Class
  Larry Witzel
  2/15/18

	Handles admin settings

  "I try to find common ground with everyone, doing everything I can to save some.
  I do everything to spread the Good News and share in its blessings."
  1 Corinthians 9:22b, 23 NLT2

 * **************************************************** */



if (!class_exists('SermonView_Integration_Admin')) {
	class SermonView_Integration_Admin
	{
		private $properties;

		public function __construct(&$svi)
		{
			$this->svi = $svi;
			$this->api = $this->svi->api;
			$this->login = $this->svi->login;
			$this->account = $this->svi->account;
			$this->dashboard = $this->svi->dashboard;
			$this->cart = $this->svi->cart;
		}
		public function run()
		{
			$this->init();
		}
		private function init()
		{
			add_action('admin_menu', array(&$this, 'add_menu_items'));
			add_action('admin_init', array($this, 'settings_init'));
			add_action('admin_head', array(&$this, 'add_header_links'));
			add_action('admin_footer', array(&$this, 'add_footer_links'));

			// Add SV customer_id to Users list
			add_filter('manage_users_columns', array(&$this, 'add_sv_customer_id_column'));
			add_action('manage_users_custom_column', array(&$this, 'show_sv_customer_id_column_content'), 10, 3);

			// nuke password field in edit-user.php
			add_action('edit_user_profile', array(&$this, 'nukeUserPasswordField'), 9999999);

			// show special pages in page list
			add_filter('display_post_states', array(&$this, 'add_display_post_states'), 10, 2);

			add_action('admin_post_svi_clear_api_log', array(&$this, 'action_clear_api_log'));

			// script for media selector
			add_action('admin_enqueue_scripts', array(&$this,'load_wp_media_files'));

			add_action('wp_ajax_svi_setting_get_image',array(&$this,'ajax_get_image'));
		}
		public function load_wp_media_files($page) {
			if($page == 'admin.php' && $_GET['page'] == 'sermonview-integration-account') {
				wp_enqueue_media();
			}
		}
		public function ajax_get_image() {
			$image_id = filter_input( INPUT_GET, 'img_id', FILTER_VALIDATE_INT );
			$target_id = filter_input( INPUT_GET, 'target_id', FILTER_SANITIZE_URL);
			if(!empty($target_id)) {
				if(!empty($image_id)) {
					$image = wp_get_attachment_image($image_id, 'medium', false, array( 'id' => $target_id ) );
				} else {
					switch($target_id) {
						case 'receipt_img_image':
							$receipt = $this->api->get_receipt_details()->receipt;
							$image = '<img src="' . $receipt->logo . '" id="' . $target_id . '" />';
							break;
					}
				}
				$data = array(
					'image' => $image,
					'target_id' => $target_id
				);
				wp_send_json_success($data);
			} else {
				wp_send_json_error();
			}
		}
		public function add_display_post_states($post_states, $post)
		{
			if ($this->login->settings['login_page'] == $post->ID) {
				$post_states['svi_page_for_login'] = __('SermonView Login Page', 'sermonview-integration');
			}
			if ($this->account->settings['account_page'] == $post->ID) {
				$post_states['svi_page_for_account'] = __('SermonView Account Page', 'sermonview-integration');
			}
			if ($this->dashboard->settings['dashboard_page'] == $post->ID) {
				$post_states['svi_page_for_dashboard'] = __('SermonView Dashboard System Page', 'sermonview-integration');
			}
			if ($this->dashboard->settings['dashboard_home_page'] == $post->ID) {
				$post_states['svi_page_for_dashboard'] = __('SermonView Dashboard Home Page', 'sermonview-integration');
			}
			if ($this->cart->settings['cart_page'] == $post->ID) {
				$post_states['svi_page_for_cart'] = __('SermonView Shopping Cart Page', 'sermonview-integration');
			}
			if ($this->cart->settings['checkout_page'] == $post->ID) {
				$post_states['svi_page_for_checkout'] = __('SermonView Checkout Page', 'sermonview-integration');
			}
			if ($this->cart->settings['product_page'] == $post->ID) {
				$post_states['svi_page_for_product'] = __('SermonView Product Page', 'sermonview-integration');
			}
			if ($this->cart->settings['catalog_page'] == $post->ID) {
				$post_states['svi_page_for_catalog'] = __('SermonView Root Catalog Page', 'sermonview-integration');
			}
			return $post_states;
		}
		public function add_menu_items()
		{
			add_menu_page('SermonView Integration', 'SermonView', 'manage_options', $this->svi->get_plugin_name(), array(&$this, 'admin_options_page'), $this->shermon_icon(), 98);
			add_submenu_page($this->svi->get_plugin_name(), 'Overview', 'Overview', 'manage_options', $this->svi->get_plugin_name(), array(&$this, 'admin_options_page'));
			add_submenu_page($this->svi->get_plugin_name(), 'Login', 'Login', 'manage_options', $this->svi->get_plugin_name() . '-login', array(&$this, 'admin_options_page'));
			add_submenu_page($this->svi->get_plugin_name(), 'Account', 'Account', 'manage_options', $this->svi->get_plugin_name() . '-account', array(&$this, 'admin_options_page'));
			add_submenu_page($this->svi->get_plugin_name(), 'Dashboard', 'Dashboard', 'manage_options', $this->svi->get_plugin_name() . '-dashboard', array(&$this, 'admin_options_page'));
			add_submenu_page($this->svi->get_plugin_name(), 'Cart', 'Shopping Cart', 'manage_options', $this->svi->get_plugin_name() . '-cart', array(&$this, 'admin_options_page'));
			//			add_submenu_page($this->svi->get_plugin_name(),'GF Overrides','GF Overrides','manage_options',$this->svi->get_plugin_name() . '-gf-overrides', array(&$this,'admin_options_page'));
			add_submenu_page($this->svi->get_plugin_name(), 'API', 'API', 'manage_options', $this->svi->get_plugin_name() . '-api', array(&$this, 'admin_options_page'));
			add_submenu_page($this->svi->get_plugin_name(), 'Log', 'Log', 'manage_options', $this->svi->get_plugin_name() . '-log', array(&$this, 'admin_options_page'));

			// add settings link to plugin listing
			add_filter('plugin_action_links_' . SERMONVIEW_INTEGRATION_PLUGIN_BASENAME, array(&$this, 'plugin_settings_link'));
		}
		public function plugin_settings_link($links)
		{
			$settings = array('settings' => '<a href="admin.php?page=' . $this->svi->get_plugin_name() . '">' . __('Settings') . '</a>');
			return array_merge($settings, $links);
		}
		private function shermon_icon()
		{
			// menu icons must be Base64 SVG - see $icon_url here: https://developer.wordpress.org/reference/functions/add_menu_page/
			// converted shermon graphic to paths in Photoshop, then saved as SVG file
			// then converted SVG to Base64 here: https://www.browserling.com/tools/image-to-base64
			return 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiBwcmVzZXJ2ZUFzcGVjdFJhdGlvPSJ4TWlkWU1pZCIgd2lkdGg9IjIyMCIgaGVpZ2h0PSIyMjUiIHZpZXdCb3g9IjAgMCAyMjAgMjI1Ij4KICA8ZGVmcz4KICAgIDxzdHlsZT4KICAgICAgLmNscy0xIHsKICAgICAgICBmaWxsOiAjMWE1ODg4OwogICAgICAgIGZpbGwtcnVsZTogZXZlbm9kZDsKICAgICAgfQogICAgPC9zdHlsZT4KICA8L2RlZnM+CiAgPHBhdGggZD0iTTE3My4wMDAsMjExLjAwMCBDMTczLjAwMCwxOTMuMDAyIDE3My4wMDAsMTc0Ljk5OCAxNzMuMDAwLDE1Ny4wMDAgQzE3My4wMDAsMTQ5LjQwNyAxNzQuMzA3LDEzNi43NDEgMTcyLjAwMCwxMzEuMDAwIEMxNzEuNjY3LDEzMS4wMDAgMTcxLjMzMywxMzEuMDAwIDE3MS4wMDAsMTMxLjAwMCBDMTcwLjY2NywxMjkuNjY3IDE3MC4zMzMsMTI4LjMzMyAxNzAuMDAwLDEyNy4wMDAgQzE2OS4zMzMsMTI2LjY2NyAxNjguNjY3LDEyNi4zMzMgMTY4LjAwMCwxMjYuMDAwIEMxNjguMDAwLDEyNS4zMzMgMTY4LjAwMCwxMjQuNjY3IDE2OC4wMDAsMTI0LjAwMCBDMTY3LjMzMywxMjMuNjY3IDE2Ni42NjcsMTIzLjMzMyAxNjYuMDAwLDEyMy4wMDAgQzE2NS4wMDAsMTIxLjY2NyAxNjQuMDAwLDEyMC4zMzMgMTYzLjAwMCwxMTkuMDAwIEMxNTEuMzkzLDExMC44MDcgMTIwLjU3NCwxMTQuMDAwIDEwMS4wMDAsMTE0LjAwMCBDODQuNTQ5LDExNC4wMDAgNjYuNzEyLDExMi4xMjIgNTcuMDAwLDExOS4wMDAgQzU2LjY2NywxMTkuNjY3IDU2LjMzMywxMjAuMzMzIDU2LjAwMCwxMjEuMDAwIEM1NC42NjcsMTIyLjAwMCA1My4zMzMsMTIzLjAwMCA1Mi4wMDAsMTI0LjAwMCBDNTIuMDAwLDEyNC42NjcgNTIuMDAwLDEyNS4zMzMgNTIuMDAwLDEyNi4wMDAgQzUxLjMzMywxMjYuMzMzIDUwLjY2NywxMjYuNjY3IDUwLjAwMCwxMjcuMDAwIEM0NS44MTksMTMzLjY0NSA0Ny4wMDAsMTQ2LjI4NSA0Ny4wMDAsMTU3LjAwMCBDNDcuMDAwLDE3NC45OTggNDcuMDAwLDE5My4wMDIgNDcuMDAwLDIxMS4wMDAgQzM3LjkxMSwyMTEuMDgwIDI0LjI0MSwyMTEuODEyIDE4LjAwMCwyMDkuMDAwIEMxOC4wMDAsMjA4LjY2NyAxOC4wMDAsMjA4LjMzMyAxOC4wMDAsMjA4LjAwMCBDMTcuMzMzLDIwOC4wMDAgMTYuNjY3LDIwOC4wMDAgMTYuMDAwLDIwOC4wMDAgQzE1LjY2NywyMDcuMzMzIDE1LjMzMywyMDYuNjY3IDE1LjAwMCwyMDYuMDAwIEMxNC4zMzMsMjA2LjAwMCAxMy42NjcsMjA2LjAwMCAxMy4wMDAsMjA2LjAwMCBDMTIuMzMzLDIwNS4wMDAgMTEuNjY3LDIwNC4wMDAgMTEuMDAwLDIwMy4wMDAgQzEwLjMzMywyMDMuMDAwIDkuNjY3LDIwMy4wMDAgOS4wMDAsMjAzLjAwMCBDOC42NjcsMjAyLjMzMyA4LjMzMywyMDEuNjY3IDguMDAwLDIwMS4wMDAgQzcuNjY3LDIwMS4wMDAgNy4zMzMsMjAxLjAwMCA3LjAwMCwyMDEuMDAwIEMtMi4xOTYsMTg4LjQxMiAtMC4wMDAsMTY1LjY1MiAtMC4wMDAsMTQ0LjAwMCBDLTAuMDAwLDExNS4zMzYgLTAuMDAwLDg2LjY2NCAtMC4wMDAsNTguMDAwIEMwLjAwMCw0NS44NzcgLTEuODM5LDI2Ljc3NCAyLjAwMCwxOC4wMDAgQzIuMzMzLDE4LjAwMCAyLjY2NywxOC4wMDAgMy4wMDAsMTguMDAwIEMzLjMzMywxNi42NjcgMy42NjcsMTUuMzMzIDQuMDAwLDE0LjAwMCBDNC42NjcsMTMuNjY3IDUuMzMzLDEzLjMzMyA2LjAwMCwxMy4wMDAgQzYuMDAwLDEyLjMzMyA2LjAwMCwxMS42NjcgNi4wMDAsMTEuMDAwIEM2LjY2NywxMC42NjcgNy4zMzMsMTAuMzMzIDguMDAwLDEwLjAwMCBDOS4zMzMsOC4zMzMgMTAuNjY3LDYuNjY2IDEyLjAwMCw1LjAwMCBDMTIuNjY3LDUuMDAwIDEzLjMzMyw1LjAwMCAxNC4wMDAsNS4wMDAgQzE0LjMzMyw0LjMzMyAxNC42NjcsMy42NjcgMTUuMDAwLDMuMDAwIEMxNS42NjcsMy4wMDAgMTYuMzMzLDMuMDAwIDE3LjAwMCwzLjAwMCBDMTcuMDAwLDIuNjY3IDE3LjAwMCwyLjMzMyAxNy4wMDAsMi4wMDAgQzE5LjAwMCwxLjY2NyAyMS4wMDAsMS4zMzMgMjMuMDAwLDEuMDAwIEMyMy4wMDAsMC42NjcgMjMuMDAwLDAuMzMzIDIzLjAwMCwwLjAwMCBDMzguOTk4LDAuMDAwIDU1LjAwMiwwLjAwMCA3MS4wMDAsMC4wMDAgQzEwMC45OTcsMC4wMDAgMTMxLjAwMywwLjAwMCAxNjEuMDAwLDAuMDAwIEMxNzAuOTAzLC0wLjAwMCAxOTMuODUyLC0xLjg2OCAyMDEuMDAwLDEuMDAwIEMyMDEuMDAwLDEuMzMzIDIwMS4wMDAsMS42NjcgMjAxLjAwMCwyLjAwMCBDMjAyLjMzMywyLjMzMyAyMDMuNjY3LDIuNjY3IDIwNS4wMDAsMy4wMDAgQzIwNS4zMzMsMy42NjcgMjA1LjY2Nyw0LjMzMyAyMDYuMDAwLDUuMDAwIEMyMDYuNjY3LDUuMDAwIDIwNy4zMzMsNS4wMDAgMjA4LjAwMCw1LjAwMCBDMjA4LjMzMyw1LjY2NyAyMDguNjY3LDYuMzMzIDIwOS4wMDAsNy4wMDAgQzIxMS4wMDAsOC42NjcgMjEzLjAwMCwxMC4zMzMgMjE1LjAwMCwxMi4wMDAgQzIxNS42NjcsMTQuMDAwIDIxNi4zMzMsMTYuMDAwIDIxNy4wMDAsMTguMDAwIEMyMTcuMzMzLDE4LjAwMCAyMTcuNjY3LDE4LjAwMCAyMTguMDAwLDE4LjAwMCBDMjE4LjAwMCwxOS4wMDAgMjE4LjAwMCwyMC4wMDAgMjE4LjAwMCwyMS4wMDAgQzIxOC4zMzMsMjEuMDAwIDIxOC42NjcsMjEuMDAwIDIxOS4wMDAsMjEuMDAwIEMyMTkuMDAwLDIyLjMzMyAyMTkuMDAwLDIzLjY2NyAyMTkuMDAwLDI1LjAwMCBDMjIxLjk4NywzNC40NDcgMjIwLjAwMCw1My4zODkgMjIwLjAwMCw2NS4wMDAgQzIyMC4wMDAsOTQuNjY0IDIyMC4wMDAsMTI0LjMzNiAyMjAuMDAwLDE1NC4wMDAgQzIyMC4wMDAsMTY0LjQ1MyAyMjEuODgwLDE4MS41OTQgMjE5LjAwMCwxOTAuMDAwIEMyMTguNjY3LDE5MC4wMDAgMjE4LjMzMywxOTAuMDAwIDIxOC4wMDAsMTkwLjAwMCBDMjE3LjY2NywxOTEuNjY2IDIxNy4zMzMsMTkzLjMzNCAyMTcuMDAwLDE5NS4wMDAgQzIxNi4zMzMsMTk1LjMzMyAyMTUuNjY3LDE5NS42NjcgMjE1LjAwMCwxOTYuMDAwIEMyMTUuMDAwLDE5Ni42NjcgMjE1LjAwMCwxOTcuMzMzIDIxNS4wMDAsMTk4LjAwMCBDMjE0LjMzMywxOTguMzMzIDIxMy42NjcsMTk4LjY2NyAyMTMuMDAwLDE5OS4wMDAgQzIxMy4wMDAsMTk5LjY2NyAyMTMuMDAwLDIwMC4zMzMgMjEzLjAwMCwyMDEuMDAwIEMyMTIuMDAwLDIwMS42NjcgMjExLjAwMCwyMDIuMzMzIDIxMC4wMDAsMjAzLjAwMCBDMjEwLjAwMCwyMDMuMzMzIDIxMC4wMDAsMjAzLjY2NyAyMTAuMDAwLDIwNC4wMDAgQzIwOS4zMzMsMjA0LjAwMCAyMDguNjY3LDIwNC4wMDAgMjA4LjAwMCwyMDQuMDAwIEMyMDcuMzMzLDIwNS4wMDAgMjA2LjY2NywyMDYuMDAwIDIwNi4wMDAsMjA3LjAwMCBDMTk4LjgwMywyMTEuNzIxIDE4NC45OTUsMjExLjA5NCAxNzMuMDAwLDIxMS4wMDAgWk0xMDYuMDAwLDE5LjAwMCBDMTA2LjAwMCwxOS4zMzMgMTA2LjAwMCwxOS42NjcgMTA2LjAwMCwyMC4wMDAgQzEwNC4wMDAsMjAuMDAwIDEwMi4wMDAsMjAuMDAwIDEwMC4wMDAsMjAuMDAwIEMxMDAuMDAwLDIwLjMzMyAxMDAuMDAwLDIwLjY2NyAxMDAuMDAwLDIxLjAwMCBDOTkuMDAwLDIxLjAwMCA5OC4wMDAsMjEuMDAwIDk3LjAwMCwyMS4wMDAgQzk3LjAwMCwyMS4zMzMgOTcuMDAwLDIxLjY2NyA5Ny4wMDAsMjIuMDAwIEM5Ni4wMDAsMjIuMDAwIDk1LjAwMCwyMi4wMDAgOTQuMDAwLDIyLjAwMCBDOTQuMDAwLDIyLjMzMyA5NC4wMDAsMjIuNjY3IDk0LjAwMCwyMy4wMDAgQzkyLjAwMCwyMy42NjcgOTAuMDAwLDI0LjMzMyA4OC4wMDAsMjUuMDAwIEM4OC4wMDAsMjUuMzMzIDg4LjAwMCwyNS42NjcgODguMDAwLDI2LjAwMCBDODcuMzMzLDI2LjAwMCA4Ni42NjcsMjYuMDAwIDg2LjAwMCwyNi4wMDAgQzg1LjAwMCwyNy4zMzMgODQuMDAwLDI4LjY2NyA4My4wMDAsMzAuMDAwIEM4Mi4zMzMsMzAuMDAwIDgxLjY2NywzMC4wMDAgODEuMDAwLDMwLjAwMCBDODEuMDAwLDMwLjMzMyA4MS4wMDAsMzAuNjY3IDgxLjAwMCwzMS4wMDAgQzgwLjAwMCwzMS42NjcgNzkuMDAwLDMyLjMzMyA3OC4wMDAsMzMuMDAwIEM3OC4wMDAsMzMuNjY3IDc4LjAwMCwzNC4zMzMgNzguMDAwLDM1LjAwMCBDNzcuMDAwLDM1LjY2NyA3Ni4wMDAsMzYuMzMzIDc1LjAwMCwzNy4wMDAgQzc1LjAwMCwzNy42NjcgNzUuMDAwLDM4LjMzMyA3NS4wMDAsMzkuMDAwIEM3NC4zMzMsMzkuMzMzIDczLjY2NywzOS42NjcgNzMuMDAwLDQwLjAwMCBDNzIuNjY3LDQxLjY2NyA3Mi4zMzMsNDMuMzMzIDcyLjAwMCw0NS4wMDAgQzcxLjY2Nyw0NS4wMDAgNzEuMzMzLDQ1LjAwMCA3MS4wMDAsNDUuMDAwIEM3MS4wMDAsNDUuNjY3IDcxLjAwMCw0Ni4zMzMgNzEuMDAwLDQ3LjAwMCBDNzAuNjY3LDQ3LjAwMCA3MC4zMzMsNDcuMDAwIDcwLjAwMCw0Ny4wMDAgQzY5LjY2Nyw0OS42NjYgNjkuMzMzLDUyLjMzNCA2OS4wMDAsNTUuMDAwIEM2My45OTUsNzIuMDI4IDc3LjUxMSw5MS4wMTEgODcuMDAwLDk3LjAwMCBDODcuNjY3LDk3LjAwMCA4OC4zMzMsOTcuMDAwIDg5LjAwMCw5Ny4wMDAgQzg5LjAwMCw5Ny4zMzMgODkuMDAwLDk3LjY2NyA4OS4wMDAsOTguMDAwIEM5MS4wMDAsOTguNjY3IDkzLjAwMCw5OS4zMzMgOTUuMDAwLDEwMC4wMDAgQzk1LjAwMCwxMDAuMzMzIDk1LjAwMCwxMDAuNjY3IDk1LjAwMCwxMDEuMDAwIEM5Ni4wMDAsMTAxLjAwMCA5Ny4wMDAsMTAxLjAwMCA5OC4wMDAsMTAxLjAwMCBDMTAxLjMxNiwxMDIuMjUwIDExMi4xNTAsMTA0Ljc4MCAxMTguMDAwLDEwMy4wMDAgQzEzMS45MDcsOTguNzY5IDE0My41MjYsOTAuNTY5IDE0OS4wMDAsNzguMDAwIEMxNDkuMDAwLDc3LjAwMCAxNDkuMDAwLDc2LjAwMCAxNDkuMDAwLDc1LjAwMCBDMTQ5LjMzMyw3NS4wMDAgMTQ5LjY2Nyw3NS4wMDAgMTUwLjAwMCw3NS4wMDAgQzE1MC4wMDAsNzQuMDAwIDE1MC4wMDAsNzMuMDAwIDE1MC4wMDAsNzIuMDAwIEMxNTAuMzMzLDcyLjAwMCAxNTAuNjY3LDcyLjAwMCAxNTEuMDAwLDcyLjAwMCBDMTUxLjAwMCw3MC4zMzMgMTUxLjAwMCw2OC42NjYgMTUxLjAwMCw2Ny4wMDAgQzE1MS4zMzMsNjcuMDAwIDE1MS42NjcsNjcuMDAwIDE1Mi4wMDAsNjcuMDAwIEMxNTMuMzY0LDYyLjIyNiAxNTEuMzEzLDUwLjA0NyAxNTAuMDAwLDQ3LjAwMCBDMTQ5LjY2Nyw0Ny4wMDAgMTQ5LjMzMyw0Ny4wMDAgMTQ5LjAwMCw0Ny4wMDAgQzE0OS4wMDAsNDYuMzMzIDE0OS4wMDAsNDUuNjY3IDE0OS4wMDAsNDUuMDAwIEMxNDguNjY3LDQ1LjAwMCAxNDguMzMzLDQ1LjAwMCAxNDguMDAwLDQ1LjAwMCBDMTQ4LjAwMCw0NC4wMDAgMTQ4LjAwMCw0My4wMDAgMTQ4LjAwMCw0Mi4wMDAgQzE0Ny4zMzMsNDEuNjY3IDE0Ni42NjcsNDEuMzMzIDE0Ni4wMDAsNDEuMDAwIEMxNDUuNjY3LDM5LjY2NyAxNDUuMzMzLDM4LjMzMyAxNDUuMDAwLDM3LjAwMCBDMTQzLjY2NywzNi4wMDAgMTQyLjMzMywzNS4wMDAgMTQxLjAwMCwzNC4wMDAgQzE0MC42NjcsMzMuMDAwIDE0MC4zMzMsMzIuMDAwIDE0MC4wMDAsMzEuMDAwIEMxMzkuMzMzLDMxLjAwMCAxMzguNjY3LDMxLjAwMCAxMzguMDAwLDMxLjAwMCBDMTM3LjAwMCwyOS42NjcgMTM2LjAwMCwyOC4zMzMgMTM1LjAwMCwyNy4wMDAgQzEzNC4zMzMsMjcuMDAwIDEzMy42NjcsMjcuMDAwIDEzMy4wMDAsMjcuMDAwIEMxMzIuNjY3LDI2LjMzMyAxMzIuMzMzLDI1LjY2NyAxMzIuMDAwLDI1LjAwMCBDMTMxLjMzMywyNS4wMDAgMTMwLjY2NywyNS4wMDAgMTMwLjAwMCwyNS4wMDAgQzEzMC4wMDAsMjQuNjY3IDEzMC4wMDAsMjQuMzMzIDEzMC4wMDAsMjQuMDAwIEMxMjguNjY3LDIzLjY2NyAxMjcuMzMzLDIzLjMzMyAxMjYuMDAwLDIzLjAwMCBDMTI2LjAwMCwyMi42NjcgMTI2LjAwMCwyMi4zMzMgMTI2LjAwMCwyMi4wMDAgQzEyNC4wMDAsMjEuNjY3IDEyMi4wMDAsMjEuMzMzIDEyMC4wMDAsMjEuMDAwIEMxMjAuMDAwLDIwLjY2NyAxMjAuMDAwLDIwLjMzMyAxMjAuMDAwLDIwLjAwMCBDMTE1LjMzNCwxOS42NjcgMTEwLjY2NiwxOS4zMzMgMTA2LjAwMCwxOS4wMDAgWk0xMDIuMDAwLDI1LjAwMCBDMTIyLjMxOCwyNC42NDQgMTMwLjkwMiwyOC4wNDcgMTM5LjAwMCw0MC4wMDAgQzEzOS42NjcsNDAuMzMzIDE0MC4zMzMsNDAuNjY3IDE0MS4wMDAsNDEuMDAwIEMxNDEuMzMzLDQyLjAwMCAxNDEuNjY3LDQzLjAwMCAxNDIuMDAwLDQ0LjAwMCBDMTQyLjMzMyw0NC4wMDAgMTQyLjY2Nyw0NC4wMDAgMTQzLjAwMCw0NC4wMDAgQzE0My4wMDAsNDUuMDAwIDE0My4wMDAsNDYuMDAwIDE0My4wMDAsNDcuMDAwIEMxNDMuMzMzLDQ3LjAwMCAxNDMuNjY3LDQ3LjAwMCAxNDQuMDAwLDQ3LjAwMCBDMTQ0LjAwMCw0Ny42NjcgMTQ0LjAwMCw0OC4zMzMgMTQ0LjAwMCw0OS4wMDAgQzE0NC4zMzMsNDkuMDAwIDE0NC42NjcsNDkuMDAwIDE0NS4wMDAsNDkuMDAwIEMxNDUuMDAwLDUwLjAwMCAxNDUuMDAwLDUxLjAwMCAxNDUuMDAwLDUyLjAwMCBDMTQ1LjMzMyw1Mi4wMDAgMTQ1LjY2Nyw1Mi4wMDAgMTQ2LjAwMCw1Mi4wMDAgQzE0Ni4wMDAsNTQuMzMzIDE0Ni4wMDAsNTYuNjY3IDE0Ni4wMDAsNTkuMDAwIEMxNDYuMzMzLDU5LjAwMCAxNDYuNjY3LDU5LjAwMCAxNDcuMDAwLDU5LjAwMCBDMTQ2LjMzMyw2My42NjYgMTQ1LjY2Nyw2OC4zMzQgMTQ1LjAwMCw3My4wMDAgQzE0NC42NjcsNzMuMDAwIDE0NC4zMzMsNzMuMDAwIDE0NC4wMDAsNzMuMDAwIEMxNDQuMDAwLDc0LjAwMCAxNDQuMDAwLDc1LjAwMCAxNDQuMDAwLDc2LjAwMCBDMTQxLjU5NCw4MS40MDkgMTM2Ljk3NSw4NS4xMDcgMTMzLjAwMCw4OS4wMDAgQzEzMi4zMzMsOTAuMDAwIDEzMS42NjcsOTEuMDAwIDEzMS4wMDAsOTIuMDAwIEMxMzAuMzMzLDkyLjAwMCAxMjkuNjY3LDkyLjAwMCAxMjkuMDAwLDkyLjAwMCBDMTI5LjAwMCw5Mi4zMzMgMTI5LjAwMCw5Mi42NjcgMTI5LjAwMCw5My4wMDAgQzEyNy4wMDAsOTMuNjY3IDEyNS4wMDAsOTQuMzMzIDEyMy4wMDAsOTUuMDAwIEMxMjMuMDAwLDk1LjMzMyAxMjMuMDAwLDk1LjY2NyAxMjMuMDAwLDk2LjAwMCBDMTIyLjAwMCw5Ni4wMDAgMTIxLjAwMCw5Ni4wMDAgMTIwLjAwMCw5Ni4wMDAgQzEyMC4wMDAsOTYuMzMzIDEyMC4wMDAsOTYuNjY3IDEyMC4wMDAsOTcuMDAwIEMxMTguMzMzLDk3LjAwMCAxMTYuNjY3LDk3LjAwMCAxMTUuMDAwLDk3LjAwMCBDMTE1LjAwMCw5Ny4zMzMgMTE1LjAwMCw5Ny42NjcgMTE1LjAwMCw5OC4wMDAgQzExMC4xNDIsOTkuMzgzIDEwMi43OTAsOTcuMDQ3IDEwMC4wMDAsOTYuMDAwIEM5OS4wMDAsOTYuMDAwIDk4LjAwMCw5Ni4wMDAgOTcuMDAwLDk2LjAwMCBDOTcuMDAwLDk1LjY2NyA5Ny4wMDAsOTUuMzMzIDk3LjAwMCw5NS4wMDAgQzk1LjAwMCw5NC4zMzMgOTMuMDAwLDkzLjY2NyA5MS4wMDAsOTMuMDAwIEM5MS4wMDAsOTIuNjY3IDkxLjAwMCw5Mi4zMzMgOTEuMDAwLDkyLjAwMCBDOTAuMzMzLDkyLjAwMCA4OS42NjcsOTIuMDAwIDg5LjAwMCw5Mi4wMDAgQzg4LjMzMyw5MS4wMDAgODcuNjY3LDkwLjAwMCA4Ny4wMDAsODkuMDAwIEM4NC4zMzQsODYuNjY3IDgxLjY2Niw4NC4zMzMgNzkuMDAwLDgyLjAwMCBDNzguMzMzLDgwLjAwMCA3Ny42NjcsNzguMDAwIDc3LjAwMCw3Ni4wMDAgQzc2LjY2Nyw3Ni4wMDAgNzYuMzMzLDc2LjAwMCA3Ni4wMDAsNzYuMDAwIEM3Ni4wMDAsNzUuMzMzIDc2LjAwMCw3NC42NjcgNzYuMDAwLDc0LjAwMCBDNzUuNjY3LDc0LjAwMCA3NS4zMzMsNzQuMDAwIDc1LjAwMCw3NC4wMDAgQzc1LjAwMCw3My4wMDAgNzUuMDAwLDcyLjAwMCA3NS4wMDAsNzEuMDAwIEM3NC42NjcsNzEuMDAwIDc0LjMzMyw3MS4wMDAgNzQuMDAwLDcxLjAwMCBDNzMuNjY3LDY2LjY2NyA3My4zMzMsNjIuMzMzIDczLjAwMCw1OC4wMDAgQzczLjMzMyw1OC4wMDAgNzMuNjY3LDU4LjAwMCA3NC4wMDAsNTguMDAwIEM3NC4wMDAsNTYuMDAwIDc0LjAwMCw1NC4wMDAgNzQuMDAwLDUyLjAwMCBDNzQuMzMzLDUyLjAwMCA3NC42NjcsNTIuMDAwIDc1LjAwMCw1Mi4wMDAgQzc1LjMzMyw1MC4wMDAgNzUuNjY3LDQ4LjAwMCA3Ni4wMDAsNDYuMDAwIEM3Ni4zMzMsNDYuMDAwIDc2LjY2Nyw0Ni4wMDAgNzcuMDAwLDQ2LjAwMCBDNzcuMDAwLDQ1LjMzMyA3Ny4wMDAsNDQuNjY3IDc3LjAwMCw0NC4wMDAgQzc3LjY2Nyw0My42NjcgNzguMzMzLDQzLjMzMyA3OS4wMDAsNDMuMDAwIEM3OS4wMDAsNDIuMzMzIDc5LjAwMCw0MS42NjcgNzkuMDAwLDQxLjAwMCBDNzkuNjY3LDQwLjY2NyA4MC4zMzMsNDAuMzMzIDgxLjAwMCw0MC4wMDAgQzgxLjAwMCwzOS4zMzMgODEuMDAwLDM4LjY2NyA4MS4wMDAsMzguMDAwIEM4MS42NjcsMzcuNjY3IDgyLjMzMywzNy4zMzMgODMuMDAwLDM3LjAwMCBDODQuMzMzLDM1LjMzNCA4NS42NjcsMzMuNjY2IDg3LjAwMCwzMi4wMDAgQzg3LjY2NywzMi4wMDAgODguMzMzLDMyLjAwMCA4OS4wMDAsMzIuMDAwIEM4OS4zMzMsMzEuMzMzIDg5LjY2NywzMC42NjcgOTAuMDAwLDMwLjAwMCBDOTAuNjY3LDMwLjAwMCA5MS4zMzMsMzAuMDAwIDkyLjAwMCwzMC4wMDAgQzkyLjAwMCwyOS42NjcgOTIuMDAwLDI5LjMzMyA5Mi4wMDAsMjkuMDAwIEM5My4zMzMsMjguNjY3IDk0LjY2NywyOC4zMzMgOTYuMDAwLDI4LjAwMCBDOTYuMDAwLDI3LjY2NyA5Ni4wMDAsMjcuMzMzIDk2LjAwMCwyNy4wMDAgQzk4LjAwMCwyNi42NjcgMTAwLjAwMCwyNi4zMzMgMTAyLjAwMCwyNi4wMDAgQzEwMi4wMDAsMjUuNjY3IDEwMi4wMDAsMjUuMzMzIDEwMi4wMDAsMjUuMDAwIFpNOTIuMDAwLDEyMS4wMDAgQzEwMy45OTksMTIxLjAwMCAxMTYuMDAxLDEyMS4wMDAgMTI4LjAwMCwxMjEuMDAwIEMxMjcuMzg3LDEyMy4yMjYgMTI3LjE3MywxMjMuNTA5IDEyNi4wMDAsMTI1LjAwMCBDMTI1LjY2NywxMjUuMDAwIDEyNS4zMzMsMTI1LjAwMCAxMjUuMDAwLDEyNS4wMDAgQzEyNS4wMDAsMTI1LjY2NyAxMjUuMDAwLDEyNi4zMzMgMTI1LjAwMCwxMjcuMDAwIEMxMjQuMzMzLDEyNy4zMzMgMTIzLjY2NywxMjcuNjY3IDEyMy4wMDAsMTI4LjAwMCBDMTIzLjAwMCwxMjguNjY3IDEyMy4wMDAsMTI5LjMzMyAxMjMuMDAwLDEzMC4wMDAgQzEyMi4zMzMsMTMwLjMzMyAxMjEuNjY3LDEzMC42NjcgMTIxLjAwMCwxMzEuMDAwIEMxMjEuMDAwLDEzMS42NjcgMTIxLjAwMCwxMzIuMzMzIDEyMS4wMDAsMTMzLjAwMCBDMTIwLjY2NywxMzMuMDAwIDEyMC4zMzMsMTMzLjAwMCAxMjAuMDAwLDEzMy4wMDAgQzExOS42NjcsMTM0LjAwMCAxMTkuMzMzLDEzNS4wMDAgMTE5LjAwMCwxMzYuMDAwIEMxMTguNjY3LDEzNi4wMDAgMTE4LjMzMywxMzYuMDAwIDExOC4wMDAsMTM2LjAwMCBDMTE4LjAwMCwxMzYuMzMzIDExOC4wMDAsMTM2LjY2NyAxMTguMDAwLDEzNy4wMDAgQzExOC4zMzMsMTM3LjAwMCAxMTguNjY3LDEzNy4wMDAgMTE5LjAwMCwxMzcuMDAwIEMxMTkuMDAwLDEzOS4wMDAgMTE5LjAwMCwxNDEuMDAwIDExOS4wMDAsMTQzLjAwMCBDMTE5LjMzMywxNDMuMDAwIDExOS42NjcsMTQzLjAwMCAxMjAuMDAwLDE0My4wMDAgQzEyMC4wMDAsMTQ1LjMzMyAxMjAuMDAwLDE0Ny42NjcgMTIwLjAwMCwxNTAuMDAwIEMxMjAuMzMzLDE1MC4wMDAgMTIwLjY2NywxNTAuMDAwIDEyMS4wMDAsMTUwLjAwMCBDMTIxLjAwMCwxNTIuMzMzIDEyMS4wMDAsMTU0LjY2NyAxMjEuMDAwLDE1Ny4wMDAgQzEyMS4zMzMsMTU3LjAwMCAxMjEuNjY3LDE1Ny4wMDAgMTIyLjAwMCwxNTcuMDAwIEMxMjQuMzMzLDE3NC45OTggMTI2LjY2NywxOTMuMDAyIDEyOS4wMDAsMjExLjAwMCBDMTI3LjY2NywyMTEuNjY3IDEyNi4zMzMsMjEyLjMzMyAxMjUuMDAwLDIxMy4wMDAgQzEyNC42NjcsMjEzLjY2NyAxMjQuMzMzLDIxNC4zMzMgMTI0LjAwMCwyMTUuMDAwIEMxMjMuMzMzLDIxNS4wMDAgMTIyLjY2NywyMTUuMDAwIDEyMi4wMDAsMjE1LjAwMCBDMTIxLjAwMCwyMTYuMzMzIDEyMC4wMDAsMjE3LjY2NyAxMTkuMDAwLDIxOS4wMDAgQzExNS45MzIsMjIxLjIwNCAxMTMuMDAyLDIyMS42MTYgMTExLjAwMCwyMjUuMDAwIEMxMDkuMzM0LDIyNC4wMDAgMTA3LjY2NiwyMjMuMDAwIDEwNi4wMDAsMjIyLjAwMCBDMTA2LjAwMCwyMjEuNjY3IDEwNi4wMDAsMjIxLjMzMyAxMDYuMDAwLDIyMS4wMDAgQzEwNS4zMzMsMjIxLjAwMCAxMDQuNjY3LDIyMS4wMDAgMTA0LjAwMCwyMjEuMDAwIEMxMDMuMzMzLDIyMC4wMDAgMTAyLjY2NywyMTkuMDAwIDEwMi4wMDAsMjE4LjAwMCBDMTAxLjMzMywyMTguMDAwIDEwMC42NjcsMjE4LjAwMCAxMDAuMDAwLDIxOC4wMDAgQzk5LjMzMywyMTcuMDAwIDk4LjY2NywyMTYuMDAwIDk4LjAwMCwyMTUuMDAwIEM5Ny4xNjEsMjE0LjQwMyA5MS4yMjYsMjExLjQyNCA5MS4wMDAsMjExLjAwMCBDOTEuMDAwLDIwOS4zMzMgOTEuMDAwLDIwNy42NjcgOTEuMDAwLDIwNi4wMDAgQzkzLjQ5MCwyMDMuMTEwIDkxLjcyOCwxOTYuMzc2IDkzLjAwMCwxOTIuMDAwIEM5Ni42MTgsMTc5LjU0OSA5Ni4zNzksMTYzLjUxMiAxMDAuMDAwLDE1MS4wMDAgQzEwMC4wMDAsMTQ4LjY2NyAxMDAuMDAwLDE0Ni4zMzMgMTAwLjAwMCwxNDQuMDAwIEMxMDAuMzMzLDE0NC4wMDAgMTAwLjY2NywxNDQuMDAwIDEwMS4wMDAsMTQ0LjAwMCBDMTAxLjAwMCwxNDEuNjY3IDEwMS4wMDAsMTM5LjMzMyAxMDEuMDAwLDEzNy4wMDAgQzEwMS4zMzMsMTM3LjAwMCAxMDEuNjY3LDEzNy4wMDAgMTAyLjAwMCwxMzcuMDAwIEMxMDIuMzc1LDEzNS42MzcgMTAxLjcwOCwxMzYuOTM4IDEwMS4wMDAsMTM2LjAwMCBDOTkuNTQ1LDEzMC4wMDggOTMuNTg3LDEyNi43MDMgOTIuMDAwLDEyMS4wMDAgWiIgY2xhc3M9ImNscy0xIi8+Cjwvc3ZnPgo=';
		}
		// action callbacks
		public function admin_options_page()
		{
			$current_page_id = $this->current_page_id();
			if (empty($this->api->settings)) {
				echo '<div class="notice notice-error"><p>User name and password must be set up before other settings can be set.</p></div>';
				$current_page_id = 'api_setup';
			}
			$current_page = null;
			$tabs = $this->nav_tabs_array();
			foreach ($tabs as $page => $details) {
				if ($details['id'] == $current_page_id) {
					$current_page = $page;
				}
			}
			?>
			<div class="wrap">
				<div id="svi-connector-status"><span class="version-number">Version <?php echo $this->svi->version; ?> | </span><span class="status-label">API Connection Status: </span><span class="status-icon"><?= $this->api->connectionStatusIcon() ?></span></div>
				<h2>SermonView Integration Settings</h2>
				<?= $this->nav_tabs($current_page) ?>
				<?php
							switch ($current_page_id) {
								case 'overview':
								default:
									$this->page_overview();
									break;
								case 'login_settings':
									$this->page_login_settings();
									break;
								case 'account_settings':
									$this->page_account_settings();
									break;
								case 'dashboard_settings':
									$this->page_dashboard_settings();
									break;
								case 'cart_settings':
									$this->page_shopping_cart_settings();
									break;
								case 'gf_override_settings':
									$this->page_gf_override_settings();
									break;
								case 'api_setup':
									$this->page_api_setup();
									break;
								case 'api_log':
									$this->page_log();
									break;
								case 'dev-page':
									$this->page_dev();
									break;
							}
							?>
			</div>
		<?php
				}
				private function page_overview()
				{
					?>
			<div class="svi">
				<h3>Plugin Overview</h3>
				<p>The SermonView Integration plugin is used to help a WordPress site interact with the SermonView API, allowing a WordPress site to:</p>
				<ul>
					<li>use SermonView username/password combinations for user login</li>
					<li>send Gravity Forms submissions to the SermonView jobs console</li>
					<li>provide an overview of the status of a customer's campaigns</li>
					<li>deliver pre-registrations, interests and other campaign data to customers</li>
				</ul>
				<h3>Shortcodes</h3>
				<p>The following shortcodes are available to pages and posts on this website:</p>
				<h4>Account & Dashboard</h4>
				<ul>
					<li><strong>[<?php echo SermonView_Integration_Login::$shortcode; ?>]</strong> Generates the login form. This must be placed on at least one page, which should be set as the Login Page in the Login Settings. May include the following attributes:
						<ul>
							<li><em>login_redirect:</em> a link to redirect the user upon successful login. For example:
								<pre>[<?php echo SermonView_Integration_Login::$shortcode; ?> login_redirect="/campaign/"]</pre>
							</li>
							<li><em>signup_redirect:</em> a link to redirect the user upon successful signup. For example:
								<pre>[<?php echo SermonView_Integration_Login::$shortcode; ?> signup_redirect="/campaign-signup/"]</pre>
							</li>
						</ul>
					</li>
					<li><strong>[<?php echo SermonView_Integration_Account::$shortcode; ?>]</strong> Generates the account information block, including viewing and editing customer addresses.</li>
					<li><strong>[<?php echo SermonView_Integration_Dashboard::$shortcode; ?>]</strong> Generates dashboard information for customer campaigns.</li>
					<li><strong>[sermonview-campaigns-table]</strong> Generates just the campaigns list table for customer campaigns.</li>
					<li><strong>[sermonview-host-sites-table]</strong> Generates a custom table specifically for national events, such as VOP bridge events.</li>
					<li><strong>[sermonview-account-greeting]</strong> Generates just the account greeting: "Hi, [firstname] [lastname]!"</li>
				</ul>
				<h4>Shopping Cart</h4>
				<ul>
					<li><strong>[sermonview-product-list]</strong> Generates a list of products. Must include exactly one of these attributes:
						<ul>
							<li><em>product_id:</em> A comma delimited list of two or more SermonView product ID's used to select which products to display. For example:
								<pre>[sermonview-product-list product_id="8187,8186,8191"]</pre>
							</li>
							<li><em>category_id:</em> A comma delimited list of one or more SermonView category ID's. This will display all active products assigned to that category. (If the product_id attribute is provided, category_id is ignored.) For example:
								<pre>[sermonview-product-list category_id="70"]</pre>
							</li>
						</ul>
						Other potential attributes:
						<ul>
							<li><em>columns:</em> Sets the number of columns used to display the product list. Valid values are 1 through 5. Defaults to 3.</li>
							<li><em>hide_product_id:</em> If you wish to hide one or more products from displaying in the list (for example, if you have manually built a featured product table at the top of the page), you may put a comma-delimited list of product ID's here.</li>
							<!-- <li><em>require_event:</em> If a category has event restrictions in osCommerce, these are also applied to this shopping cart when a single category is chosen for category_id. In all other cases, adding this attribute will force the user to select ANY event before an order can be started.</li> -->
						</ul>
					</li>
					<li><strong>[<?php echo SermonView_Integration_Shopping_Cart::$cart_shortcode; ?>]</strong> Generates the customer shopping cart.</li>
					<li><strong>[<?php echo SermonView_Integration_Shopping_Cart::$checkout_shortcode; ?>]</strong> Generates the checkout form.</li>
				</ul>
				<h4>Product Page</h4>
				<ul>
					<li><strong>[<?php echo SermonView_Integration_Shopping_Cart::$product_shortcode; ?>]</strong> Page for displaying a product.</li>
				</ul>
				<h4>Custom Category/Product Pages</h4>
				<ul>
					<li><strong>[sermonview-event-required-notice]</strong> Generates the notice about requiring a specific event for products on the page, along with code for selecting which event to use when multiple qualifying events are found in a customer's account. Place this at the top of any page that includes a [sermonview-product-add-button] tag with a category attribute.</li>
					<li><strong>[sermonview-product-add-button]</strong> Generates a buy now button that adds a product to the cart. Must include these attributes:
						<ul>
							<li><em>product_id:</em> (Required) The SermonView product ID of the product to add to the shopping cart.</li>
							<li><em>quantity:</em> The quantity to add to the cart. If not provided, defaults to 1.</li>
							<li><em>category_id:</em> The category of the product. Used in shipping cart navigation, to return to a category view. <strong>If a category_id is provided, any event-related category restrictions will be enforced.</strong></li>
							<li><em>do_not_enforce_category_restrictions:</em> If set to 1 (true), then an event-related category restrictions will NOT be enforced.</li>
							<li><em>return_id:</em> This is the product ID of the product you'd like the user to return to when clicking on the Keep Shopping button after adding this product to the cart.</li>
							<li><em>class:</em> Any classes you want added to the button.</li>
							<li><em>id:</em> The ID tag you want applied to the button, if any.</li>
							<li><em>label:</em> The label of the button. If empty, defaults to Buy Now.</li>
							<li><em>fa_icon:</em> The class of a Font Awesome icon to display after the label. Should be everything after the "fa-" in the class name.
								<pre>[sermonview-product-add-button product_id=7415 category_id=74 quantity=1 class="x-button x-jumbo" label="Buy Me" fa_icon="chevron-right"]</pre></li>
						</ul>
					</li>
				</ul>
				<h4>Development</h4>
				<ul>
					<li><strong>[sermonview-customer-object]</strong> For coding/debugging, outputs the complete customer object.</li>
					<li><strong>[sermonview-events-object]</strong> For coding/debugging, outputs the complete events object.</li>
				</ul>
				<h3>Conditional Blocks</h3>
				<p>If you would like to display certain content on a page only when a certain condition is true, you can use a conditional block shortcode. Here are the conditional block shortcodes that are available:</p>
				<ul>
					<li><strong>[if has_event]</strong> Displays block if customer has one or more qualifying events.</li>
					<li><strong>[if event_missing_info]</strong> Displays block if at least one of the events is missing event info.</li>
					<li><strong>[if event_missing_order]</strong> Displays block if at least one of the events has no associated orders.</li>
					<li><strong>[if no_orders]</strong> Displays block if none of the customer's events has any orders.</li>
					<li><strong>[if has_one_event]</strong> Displays block if customer has exactly one qualifying event.</li>
					<li><strong>[if has_multiple_events]</strong> Displays block if customer has two or more qualifying events.</li>
					<li><strong>[if has_event_type=#]</strong> (Replace "#" with an event type ID.) Displays block if customer has an event with this event type ID. Can separate multiple values with commas, and will display block if customer has any event matching any of the event type IDs. For example: [if has_event_type=6,8]</li>
					<li><strong>[if has_event_ds_no=#]</strong> (Replace "#" with a DS number.) Displays block if customer has an event with using this DS-number image. Can separate multiple values with commas, and will display block if customer has any event matching any of the DS numbers. For example: [if has_event_ds_no=8077,7571]</li>
				</ul>
				<h4 style="font-size: 1.2em;">How to use conditional blocks</h4>
				<p>Place the text you want displayed between the opening and closing conditional block shortcode, like this:</p>
				<code>[if has_event]The text.[/if]</code>
				<h4>OR</h4>
				<p>Using multiple parameters, the content is displayed when either of the conditions are met ("OR" comparison). For example, show if customer has no orders or is missing event info for one of the events:</p>
				<code>[if no_orders event_missing_info]The text.[/if]</code>
				<h4>AND</h4>
				<p>To set multiple conditions, you can nest the shortcode ("AND" comparison). For example, show if customer has only one event and it is missing event info:</p>
				<code>[if has_one_event][if event_missing_info]The text.[/if][/if]</code>
				<h4>NOT</h4>
				<p>You can also check for the reverse condition, by adding "not_" to the attribute. For example, show if every customer's event has at least one order associate to it:</p>
				<code>[if not_event_missing_order]The text.[/if]</code>
				<h4>Testing Conditional Blocks</h4>
				<p>You can test your display output by setting the attribute value as a $_GET variable. For example, to see what the output looks like when event_missing_info is true, add this to the URL:</p>
				<code>?event_missing_info=1</code>
				<p>or, if there are other $_GET variables in the URL (if there is already a ? question mark in the URL), add this to the end:</p>
				<code>&event_missing_info=1</code>
				<p>You can also check for the opposite condition, by adding "not_" to the front of the variable:</p>
				<code>?not_event_missing_info=0</code>

			</div>
		<?php
				}
				private function page_api_setup()
				{
					?>
			<form method="post" action="options.php">
				<?php
							// This prints out all hidden setting fields
							settings_fields('svi_api_settings');
							do_settings_sections($this->svi->get_plugin_name() . '-api');
							submit_button();
							?>
			</form>
		<?php
				}
				private function page_login_settings()
				{
					?>
			<form method="post" action="options.php">
				<?php
							// This prints out all hidden setting fields
							settings_fields('svi_login_settings');
							do_settings_sections($this->svi->get_plugin_name() . '-login');
							submit_button();
							?>
			</form>
		<?php
				}
				private function page_account_settings()
				{
					?>
			<form method="post" action="options.php">
				<?php
							// This prints out all hidden setting fields
							settings_fields('svi_account_settings');
							do_settings_sections($this->svi->get_plugin_name() . '-account');
							submit_button();
							?>
			</form>
		<?php
				}
				private function page_dashboard_settings()
				{
					?>
			<form method="post" action="options.php">
				<?php
							// This prints out all hidden setting fields
							settings_fields('svi_dashboard_settings');
							do_settings_sections($this->svi->get_plugin_name() . '-dashboard');
							submit_button();
							?>
			</form>
		<?php
				}
				private function page_shopping_cart_settings()
				{
					?>
			<form method="post" action="options.php">
				<?php
							// This prints out all hidden setting fields
							settings_fields('svi_cart_settings');
							do_settings_sections($this->svi->get_plugin_name() . '-cart');
							submit_button();
							?>
			</form>
		<?php
				}
				private function page_gf_override_settings()
				{
					?>
			<form method="post" action="options.php">
				<?php
							// This prints out all hidden setting fields
							settings_fields('svi_gf_override_settings');
							do_settings_sections($this->svi->get_plugin_name() . '-gf-override');
							submit_button();
							?>
			</form>
		<?php
				}
				private function page_log()
				{
					?>
			<div id="svi-logging-status"><span class="status-label">Logging status: </span><span class="status-icon"><i class="fa fa-toggle-on <?php echo ($this->api->settings['enable_log'] ? 'status-on' : 'fa-flip-horizontal status-off'); ?>"></i><?php echo ($this->api->settings['enable_log'] ? '<div class="connection-type status-on">On</div>' : '<div class="connection-type status-off">Off</div>'); ?></span></div>
			<h3>API Log</h3>
			<p>All interactions with the SermonView API are logged in the database in the <?php echo $this->api->logTableName(); ?> table.</p>
			<?php
						$log_id = filter_input(INPUT_GET, 'details', FILTER_SANITIZE_NUMBER_INT);
						if ($log_id) {
							$this->page_log_details($log_id);
						} else {
							$query = '';
							$per_page = 100;
							$search_command = filter_input(INPUT_GET, 'command', FILTER_SANITIZE_STRING);
							if ($search_command) {
								$query .= " command LIKE '" . $search_command . "' AND";
							}
							$search_value = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
							if ($search_value) {
								$query .= " (" .
									"command LIKE '%" . $search_value . "%' " .
									"OR submission LIKE '%" . $search_value . "%' " .
									"OR response LIKE '%" . $search_value . "%' " .
									"OR result LIKE '%" . $search_value . "%' " .
									"OR error LIKE '%" . $search_value . "%' " .
									") AND";
							}
							$paged = filter_input(INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT);
							if (!$paged) {
								$paged = 1;
							}
							$limit = ($per_page * ($paged - 1)) . ', ' . ($per_page * $paged);

							$log_commands = $this->api->getLogCommands();
							$query = trim($query, 'AND');
							$link = '?page=' . $this->svi->get_plugin_name() . '-log' . ($search_command ? '&command=' . $search_command : '') . ($search_value ? '&search=' . $search_value : '');
							?>
				<div class="svi-search-row">
					<form action="" method="get">
						<div class="pull-right">
							<?php echo $this->pagination_links($this->api->logRecordCount($query), $per_page, $paged, $link); ?>
						</div>
						<input type="hidden" name="page" value="<?php echo $this->svi->get_plugin_name() . '-log'; ?>" />
						Command: <select name="command" onchange="this.form.submit()" class="svi-input-field">
							<option value=""></option>
							<?php
											foreach ($log_commands as $command) {
												echo '<option value="' . $command . '"' . ($command == $search_command ? ' selected="selected"' : '') . '>' . $command . '</option>';
											}
											?>
						</select>
						&nbsp; Search: <input type="text" name="search" value="<?php echo $search_value; ?>" size="8" class="svi-input-field" onchange="this.form.submit()" />
					</form>
				</div>
				<table width="100%" class="svi-log-table widefat" cellspacing="0">
					<thead>
						<tr>
							<th>Timestamp</th>
							<th>Command</th>
							<th>Submission</th>
							<th>Response</th>
							<th>Result</th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						<?php
										$response_length = 100;
										$i = 0;
										foreach ($this->api->log($query, $limit) as $row) {
											echo '<tr' . (!$i % 2 ? ' class="alternate"' : '') . '>';
											echo '<td>' . date('n/j/y', strtotime($row->date)) . '<br />' . date('H:i:s', strtotime($row->date)) . '</td>';
											echo '<td>' . $row->command . '</td>';
											echo '<td>' . urldecode(str_replace('&', '<br />', $row->submission)) . '</td>';
											// echo '<td>' . strlen($row->response) > $response_length ? 'long' : $row->response . '</td>';
											echo '<td>' . (strlen($row->response) > $response_length ? substr($row->response, 0, $response_length) . '&hellip;' : $row->response) . '</td>';
											echo '<td>' . $row->result . '</td>';
											echo '<td>' . '<a href="?page=' . $this->svi->get_plugin_name() . '-log' . '&details=' . $row->id . '"><i class="fa fa-info-circle"></i></a>' . '</td>';
											echo '</tr>';
											echo "\n";
											$i++;
										}
										?>
					</tbody>
				</table>
				<div class="pull-right" style="margin-top: 0.5em;">
					<form action="admin-post.php" method="post">
						<input type="hidden" name="action" value="svi_clear_api_log" />
						<button class="link red" onclick="return confirm('Are you sure you want to clear the log? This action cannot be undone.');">Clear log</button>
					</form>
				</div>
			<?php
						}
					}
					public function action_clear_api_log() {
						global $wpdb;
						$wpdb->query("truncate table " . $this->api->logTableName());
						wp_redirect('admin.php' . '?page=' . $this->svi->get_plugin_name() . '-log');
					}
					private function page_log_details($id)
					{
						$log = $this->api->log("id='" . (int) $id . "'");
						$response = json_decode($log[0]->response);
						if (json_last_error() === JSON_ERROR_NONE) {
							$response_output = print_r($response, 1);
						} else {
							$response_output = $log[0]->response;
						}
						$backtrace = json_decode($log[0]->backtrace);
						if(json_last_error() == JSON_ERROR_NONE) {
							$backtrace_output = print_r($backtrace,1);
						} else {
							$backtrace_output = $log[0]->backtrace;
						}
						echo '<a href="?page=' . $this->svi->get_plugin_name() . '-log' . '"><i class="fa fa-angle-double-left"></i> Back</a>';
						echo '<div class="svi-log-details">';
						echo '<div><label>Timestamp</label><pre>' . $log[0]->date . '</pre></div>';
						echo '<div><label>Command</label><pre>' . $log[0]->command . '</pre></div>';
						echo '<div><label>Submission</label><pre>' . urldecode(str_replace('&', '<br />', $log[0]->submission)) . '</pre></div>';
						echo '<div><label>Result</label><pre>' . $log[0]->result . ($log[0]->result == 'error' ? ' (' . $log[0]->error . ')' : '') . '</pre></div>';
						echo '<div><label>Response</label><pre>' . $response_output . '</pre></div>';
						echo '<div><label>Backtrace</label><pre>' . $backtrace_output . '</pre></div>';
						echo '</div>';
					}
					private function page_dev()
					{
						?>
			<h3>Larry's Dev Page</h3>
			Place for running code-level testing
			<?php

					}
					public function add_header_links()
					{
						echo '<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">' . "\n";
						echo '<link rel="stylesheet" href="' . plugins_url('sermonview-integration-admin.css', __FILE__) . '">';
					}
					public function add_footer_links()
					{
						echo '<script type="text/javascript" src="' . plugins_url('sermonview-integration-admin.js', __FILE__) . '"></script>';
					}
					public function settings_init()
					{
						if(!empty($_GET['page']) && $_GET['page'] == 'sermonview-integration-account') {
							$receipt = $this->api->get_receipt_details()->receipt;
						}
					
						////////////// LOGIN SETTINGS /////////////////////
						register_setting(
							'svi_login_settings', // Option group
							SermonView_Integration_Login::$settings_name // Option name
							// ,array( $this, 'sanitize' ) // Sanitize
						);
						add_settings_section(
							'login_settings',
							'Login Settings',
							array($this, 'print_page_setting_instructions'),
							$this->svi->get_plugin_name() . '-login'
						);
						add_settings_field(
							'login_page',
							'Login Page',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-login',
							'login_settings',
							array(
								'field' => 'login_page',
								'properties' => array(
									'value_type' => 'dropdown',
									'options' => $this->pagesArray()
								),
								'input_name' =>  SermonView_Integration_Login::$settings_name,
								'options_array' => $this->login->settings
							)
						);
						add_settings_field(
							'login_redirect_page',
							'Login Redirect Page',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-login',
							'login_settings',
							array(
								'field' => 'login_redirect_page',
								'properties' => array(
									'instructions' => 'After login, non-admin user will be redirected to this page, unless overridden with the login_redirect attribute in the login form shortcode.',
									'value_type' => 'dropdown',
									'options' => $this->pagesArray()
								),
								'input_name' => SermonView_Integration_Login::$settings_name,
								'options_array' => $this->login->settings
							)
						);
						add_settings_field(
							'signup_redirect_page',
							'Signup Redirect Page',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-login',
							'login_settings',
							array(
								'field' => 'signup_redirect_page',
								'properties' => array(
									'instructions' => 'After sign up, non-admin user will be redirected to this page, unless overridden with the signup_redirect attribute in the login form shortcode.',
									'value_type' => 'dropdown',
									'options' => $this->pagesArray()
								),
								'input_name' => SermonView_Integration_Login::$settings_name,
								'options_array' => $this->login->settings
							)
						);
						add_settings_field(
							'flag_adventist',
							'New Accounts Adventist?',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-login',
							'login_settings',
							array(
								'field' => 'flag_adventist',
								'properties' => array(
									'value_type' => 'radio',
									'instructions' => 'When set to Yes, all new customer accounts in SermonView will be flagged as Adventist.',
									'options' => array(
										'true' => 'Yes',
										'false' => 'No'
									)
								),
								'input_name' => SermonView_Integration_Login::$settings_name,
								'options_array' => $this->login->settings
							)
						);
						add_settings_field(
							'set_prospect',
							'New Accounts Prospect?',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-login',
							'login_settings',
							array(
								'field' => 'set_prospect',
								'properties' => array(
									'value_type' => 'radio',
									'instructions' => 'When set to Yes, all new customer accounts in SermonView will be set to Prospect, and existing Prospect accounts won\'t be converted to Basic.',
									'options' => array(
										'true' => 'Yes',
										'false' => 'No'
									)
								),
								'input_name' => SermonView_Integration_Login::$settings_name,
								'options_array' => $this->login->settings
							)
						);
						//////////////// ACCOUNT SETTINGS /////////////////
						register_setting(
							'svi_account_settings', // Option group
							SermonView_Integration_Account::$settings_name // Option name
						);
						add_settings_section(
							'account_settings',
							'Account Settings',
							array($this, 'print_account_setting_instructions'),
							$this->svi->get_plugin_name() . '-account'
						);
						add_settings_field(
							'account_page',
							'Account Page',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-account',
							'account_settings',
							array(
								'field' => 'account_page',
								'properties' => array(
									'instructions' => 'This is the page with the [' . SermonView_Integration_Account::$shortcode . '] shortcode in it. Account info and updates are handled here, including customer addresses and orders.',
									'value_type' => 'dropdown',
									'options' => $this->pagesArray()
								),
								'input_name' =>  SermonView_Integration_Account::$settings_name,
								'options_array' => $this->account->settings
							)
						);
						add_settings_field(
							'add_nav_menu',
							'Add account menu to top menu?',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-account',
							'account_settings',
							array(
								'field' => 'add_nav_menu',
								'properties' => array(
									'value_type' => 'radio',
									'instructions' => 'When set to Yes, the Log In/My Account menu is automatically added to the end of the Primary menu.',
									'options' => array(
										'true' => 'Yes',
										'false' => 'No'
									)
								),
								'input_name' =>  SermonView_Integration_Account::$settings_name,
								'options_array' => $this->account->settings
							)
						);
						add_settings_field(
							'nav_menu_slug',
							'Which menu to add the account menu to?',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-account',
							'account_settings',
							array(
								'field' => 'nav_menu_slug',
								'properties' => array(
									'instructions' => 'This is the menu that the account menu will be added to.',
									'value_type' => 'dropdown',
									'options' => $this->menusArray()
								),
								'input_name' =>  SermonView_Integration_Account::$settings_name,
								'options_array' => $this->account->settings
							)
						);
						add_settings_field(
							'is_pro_theme',
							'Does this site use the Pro theme, instead of X theme?',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-account',
							'account_settings',
							array(
								'field' => 'is_pro_theme',
								'properties' => array(
									'value_type' => 'radio',
									'instructions' => 'Set this to true to generate Pro-specific CSS for the account menu.',
									'options' => array(
										'true' => 'Yes',
										'false' => 'No'
									)
								),
								'input_name' =>  SermonView_Integration_Account::$settings_name,
								'options_array' => $this->account->settings
							)
						);
						add_settings_field(
							'receipt_address',
							'Address for receipts',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-account',
							'account_settings',
							array(
								'field' => 'receipt_address',
								'properties' => array(
									'value_type' => 'textarea',
									'instructions' => 'This address appears on SermonView receipts generated on this site. If blank, the address block is pulled via API from osCommerce.',
									'height' => '8',
									'width' => '40'
								),
								'input_name' =>  SermonView_Integration_Account::$settings_name,
								'options_array' => $this->account->settings
							)
						);
						$field = 'receipt_img';
						add_settings_field(
							$field,
							'Image for receipts',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-account',
							'account_settings',
							array(
								'field' => $field,
								'properties' => array(
									'value_type' => 'image_preview',
									'instructions' => 'This image is displayed on printed receipts. Default is pulled via API from osCommerce.',
									'image_tag' => (!empty($this->account->settings[$field]) ? wp_get_attachment_image($this->account->settings[$field], 'thumbnail', false, array( 'id' => $field . '_image' ) ) : '<img src="' . $receipt->logo . '" id="' . $field . '_image' . '" />')
								),
								'input_name' =>  SermonView_Integration_Account::$settings_name,
								'options_array' => $this->account->settings
							)
						);

						//////////////// DASHBOARD SETTINGS /////////////////
						register_setting(
							'svi_dashboard_settings', // Option group
							SermonView_Integration_Dashboard::$settings_name // Option name
						);
						add_settings_section(
							'dashboard_settings',
							'Dashboard Settings',
							array($this, 'print_dashboard_setting_instructions'),
							$this->svi->get_plugin_name() . '-dashboard'
						);
						add_settings_field(
							'dashboard_page',
							'Dashboard System Page',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-dashboard',
							'dashboard_settings',
							array(
								'field' => 'dashboard_page',
								'properties' => array(
									'instructions' => 'This is the page with the [' . SermonView_Integration_Dashboard::$shortcode . '] shortcode on it. Most dashboard functions are handled here.',
									'value_type' => 'dropdown',
									'options' => $this->pagesArray()
								),
								'input_name' =>  SermonView_Integration_Dashboard::$settings_name,
								'options_array' => $this->dashboard->settings
							)
						);
						add_settings_field(
							'dashboard_home_page',
							'Dashboard Home Page',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-dashboard',
							'dashboard_settings',
							array(
								'field' => 'dashboard_home_page',
								'properties' => array(
									'instructions' => 'This is the dashboard home page. This can be different, to show the host site table or to customize content on the page.',
									'value_type' => 'dropdown',
									'options' => $this->pagesArray()
								),
								'input_name' =>  SermonView_Integration_Dashboard::$settings_name,
								'options_array' => $this->dashboard->settings
							)
						);
						add_settings_field(
							'restrict_to_event_type',
							'Restrict to Events with Type',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-dashboard',
							'dashboard_settings',
							array(
								'field' => 'restrict_to_event_type',
								'properties' => array(
									'instructions' => 'If set, this website won\'t see any events that are not of this type.',
									'value_type' => 'dropdown',
									'options' => $this->eventTypesArray()
								),
								'input_name' =>  SermonView_Integration_Dashboard::$settings_name,
								'options_array' => $this->dashboard->settings
							)
						);
						add_settings_field(
							'restrict_to_image_ds',
							'Restrict to Events with Image',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-dashboard',
							'dashboard_settings',
							array(
								'field' => 'restrict_to_image_ds',
								'properties' => array(
									'instructions' => 'If set, this website won\'t see any sites that do not use this image. (This is a legacy setting used prior to event types changing for each new VOP event.)',
									'value_type' => 'text',
									'prefix' => 'DS-'
								),
								'input_name' =>  SermonView_Integration_Dashboard::$settings_name,
								'options_array' => $this->dashboard->settings
							)
						);
						add_settings_field(
							'campaign_header_label',
							'Header Label for Campaign List - Single Campaign',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-dashboard',
							'dashboard_settings',
							array(
								'field' => 'campaign_header_label',
								'properties' => array(
									'value_type' => 'text'
								),
								'input_name' =>  SermonView_Integration_Dashboard::$settings_name,
								'options_array' => $this->dashboard->settings
							)
						);
						add_settings_field(
							'campaign_header_label_plural',
							'Header Label for Campaign List - Multiple Campaigns',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-dashboard',
							'dashboard_settings',
							array(
								'field' => 'campaign_header_label_plural',
								'properties' => array(
									'value_type' => 'text'
								),
								'input_name' =>  SermonView_Integration_Dashboard::$settings_name,
								'options_array' => $this->dashboard->settings
							)
						);
						add_settings_field(
							'event_info_form_target',
							'Event Info Form Target Page',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-dashboard',
							'dashboard_settings',
							array(
								'field' => 'event_info_form_target',
								'properties' => array(
									'instructions' => 'This is the page with the Event Info form for a host site.',
									'value_type' => 'dropdown',
									'options' => $this->pagesArray()
								),
								'input_name' =>  SermonView_Integration_Dashboard::$settings_name,
								'options_array' => $this->dashboard->settings
							)
						);
						add_settings_field(
							'addl_signup_target',
							'Additional Signup Target Page',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-dashboard',
							'dashboard_settings',
							array(
								'field' => 'addl_signup_target',
								'properties' => array(
									'instructions' => 'If set, dashboard will show a button to the page for creating another event.',
									'value_type' => 'dropdown',
									'options' => $this->pagesArray(true)
								),
								'input_name' =>  SermonView_Integration_Dashboard::$settings_name,
								'options_array' => $this->dashboard->settings
							)
						);
						add_settings_field(
							'addl_signup_button_label',
							'Additional Signup Button Label',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-dashboard',
							'dashboard_settings',
							array(
								'field' => 'addl_signup_button_label',
								'properties' => array(
									'instructions' => 'The label for the button that goes to the page for creating another event.',
									'value_type' => 'text'
								),
								'input_name' =>  SermonView_Integration_Dashboard::$settings_name,
								'options_array' => $this->dashboard->settings
							)
						);
						add_settings_field(
							'order_link',
							'Link to Order',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-dashboard',
							'dashboard_settings',
							array(
								'field' => 'order_link',
								'properties' => array(
									'instructions' => 'The link to order resources on SermonView.',
									'value_type' => 'text',
									'size' => 50
								),
								'input_name' =>  SermonView_Integration_Dashboard::$settings_name,
								'options_array' => $this->dashboard->settings
							)
						);
						//////////////// SHOPPING CART SETTINGS /////////////////
						register_setting(
							'svi_cart_settings', // Option group
							SermonView_Integration_Shopping_Cart::$settings_name // Option name
						);
						add_settings_section(
							'cart_settings',
							'Shopping Cart Settings',
							array($this, 'print_cart_setting_instructions'),
							$this->svi->get_plugin_name() . '-cart'
						);
						add_settings_field(
							'enable_cart',
							'Enable shopping cart?',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-cart',
							'cart_settings',
							array(
								'field' => 'enable_cart',
								'properties' => array(
									'value_type' => 'radio',
									'instructions' => 'Enabling the shopping cart system also the shopping cart icon to the header nav, if Add Account Menu is enabled in Account tab.',
									'options' => array(
										'true' => 'Yes',
										'false' => 'No'
									)
								),
								'input_name' =>  SermonView_Integration_Shopping_Cart::$settings_name,
								'options_array' => $this->cart->settings
							)
						);
						add_settings_field(
							'catalog_page',
							'Root Catalog Page',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-cart',
							'cart_settings',
							array(
								'field' => 'catalog_page',
								'properties' => array(
									'instructions' => 'This is the page that "Continue Shopping" links point to by default. It probably has the [' . SermonView_Integration_Shopping_Cart::$catalog_shortcode . '] shortcode on it, which displays a list of products, but a shortcode isn\'t required. You can put whatever content here that you want.',
									'value_type' => 'dropdown',
									'options' => $this->pagesArray()
								),
								'input_name' =>  SermonView_Integration_Shopping_Cart::$settings_name,
								'options_array' => $this->cart->settings
							)
						);
						add_settings_field(
							'product_page',
							'Product Page',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-cart',
							'cart_settings',
							array(
								'field' => 'product_page',
								'properties' => array(
									'instructions' => 'This is the page with the [' . SermonView_Integration_Shopping_Cart::$product_shortcode . '] shortcode on it, which displays product details for a single product. Links generated by the [' . SermonView_Integration_Shopping_Cart::$catalog_shortcode . '] shortcode will point to this page.',
									'value_type' => 'dropdown',
									'options' => $this->pagesArray()
								),
								'input_name' =>  SermonView_Integration_Shopping_Cart::$settings_name,
								'options_array' => $this->cart->settings
							)
						);
						add_settings_field(
							'cart_page',
							'Shopping Cart Page',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-cart',
							'cart_settings',
							array(
								'field' => 'cart_page',
								'properties' => array(
									'instructions' => 'This is the page with the [' . SermonView_Integration_Shopping_Cart::$cart_shortcode . '] shortcode on it, which displays the customer\'s shopping cart.',
									'value_type' => 'dropdown',
									'options' => $this->pagesArray()
								),
								'input_name' =>  SermonView_Integration_Shopping_Cart::$settings_name,
								'options_array' => $this->cart->settings
							)
						);
						add_settings_field(
							'checkout_page',
							'Checkout Page',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-cart',
							'cart_settings',
							array(
								'field' => 'checkout_page',
								'properties' => array(
									'instructions' => 'This is the page with the [' . SermonView_Integration_Shopping_Cart::$checkout_shortcode . '] shortcode on it, which handles the order checkout process.',
									'value_type' => 'dropdown',
									'options' => $this->pagesArray()
								),
								'input_name' =>  SermonView_Integration_Shopping_Cart::$settings_name,
								'options_array' => $this->cart->settings
							)
						);
						add_settings_field(
							'enable_authnet_seal',
							'Enable Authorize.net Merchant Seal?',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-cart',
							'cart_settings',
							array(
								'field' => 'enable_authnet_seal',
								'properties' => array(
									'value_type' => 'radio',
									'instructions' => '<div style="float: left; margin-right: 1.2em;"><script type="text/javascript" language="javascript">var ANS_customer_id="3a93122c-99f9-456e-a7f4-bac5afbc1d31";</script><script type="text/javascript" language="javascript" src="//VERIFY.AUTHORIZE.NET/anetseal/seal.js" ></script></div><p class="description">This will display the Authorize.net Merchant Seal in the credit card payment area. <strong>IMPORTANT!</strong> If you enable this, this domain must be added to the verified URL list in Authorize.net (<a href="https://support.authorize.net/s/article/Authorize-Net-Verified-Merchant-Seal-How-It-Works-Configuration" target="_blank">instructions</a>). If you don\'t and a user clicks on the seal, they will see an error.</p>',
									'options' => array(
										'true' => 'Yes',
										'false' => 'No'
									)
								),
								'input_name' =>  SermonView_Integration_Shopping_Cart::$settings_name,
								'options_array' => $this->cart->settings
							)
						);
						$payment_methods = $this->api->get_payment_methods();
						$i = 1;
						foreach($payment_methods->payment_methods as $method) {
							add_settings_field(
								'disable_payment_' . $method->id,
								($i == 1 ? 'Disable payment method' : ''),
								array($this, 'generate_form_field'),
								$this->svi->get_plugin_name() . '-cart',
								'cart_settings',
								array(
									'field' => 'disable_payment_' . $method->id,
									'properties' => array(
										'value_type' => 'checkbox',
										'label' => trim(strip_tags($method->module),'&nbsp;') . ' (' . $method->id . ')',
										'instructions' => ($i == sizeof($payment_methods->payment_methods) ? 'Selecting a payment method here will disable it from being used on this website. This list is generated by the SermonView shopping cart system.' : null),
										'wrapper_style' => 'margin-top: ' . ($i != 1 ? '-20px;' : '-10px;') . ($i != sizeof($payment_methods->payment_methods) ? 'margin-bottom: -20px;' : '')
									),
									'input_name' =>  SermonView_Integration_Shopping_Cart::$settings_name,
									'options_array' => $this->cart->settings
								)
							);
							$i++;
						}

						///////////// API SETTINGS ////////////////////////
						register_setting(
							'svi_api_settings', // Option group
							SermonView_Integration_API::$settings_name // Option name
						);
						add_settings_section(
							'api_settings',
							'API Settings',
							array($this, 'print_api_instructions'),
							$this->svi->get_plugin_name() . '-api'
						);
						add_settings_field(
							'api_user',
							'API User Name',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-api',
							'api_settings',
							array(
								'field' => 'api_user',
								'properties' => array(
									'value_type' => 'text'
								),
								'input_name' =>  SermonView_Integration_API::$settings_name,
								'options_array' => $this->api->settings
							)
						);
						add_settings_field(
							'api_key',
							'API Key',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-api',
							'api_settings',
							array(
								'field' => 'api_key',
								'properties' => array(
									'value_type' => 'text'
								),
								'input_name' => SermonView_Integration_API::$settings_name,
								'options_array' => $this->api->settings
							)
						);
						add_settings_field(
							'which_server',
							'Server to Access',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-api',
							'api_settings',
							array(
								'field' => 'which_server',
								'properties' => array(
									'value_type' => 'radio',
									'instructions' => 'Which SermonView server API should be accessed?',
									'options' => array(
										'dev' => 'Development',
										'live' => 'Production'
									)
								),
								'input_name' => SermonView_Integration_API::$settings_name,
								'options_array' => $this->api->settings
							)
						);
						add_settings_field(
							'enable_log',
							'API Logging',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-api',
							'api_settings',
							array(
								'field' => 'enable_log',
								'properties' => array(
									'value_type' => 'radio',
									'instructions' => 'When turned on, all calls to the API are logged.',
									'options' => array(
										'1' => 'On',
										'0' => 'Off'
									)
								),
								'input_name' => SermonView_Integration_API::$settings_name,
								'options_array' => $this->api->settings
							)
						);
						add_settings_field(
							'enable_backtrace',
							'API Backtracing',
							array($this, 'generate_form_field'),
							$this->svi->get_plugin_name() . '-api',
							'api_settings',
							array(
								'field' => 'enable_backtrace',
								'properties' => array(
									'value_type' => 'radio',
									'instructions' => 'When turned on, a backtrace is included in the API log. This can cause a performance hit and makes the log table in the database huge. So do not turn this on unless you are diagnosing a problem that requires it, and only for a short period of time.',
									'options' => array(
										'1' => 'On',
										'0' => 'Off'
									)
								),
								'input_name' => SermonView_Integration_API::$settings_name,
								'options_array' => $this->api->settings
							)
						);
					}
					// helper functions
					private function pagesArray($include_none = false)
					{
						$returnArr = array();
						if ($include_none) {
							$returnArr['none'] = 'None';
						}
						$pages = get_pages();
						foreach ($pages as $page) {
							$returnArr[$page->ID] = $page->post_title;
						}
						return $returnArr;
					}
					private function eventTypesArray()
					{
						$returnArr = array(
							'none' => 'None (do not restrict by event type)'
						);
						$event_types = $this->api->get_event_types();
						if (is_array($event_types)) {
							foreach ($event_types as $event_type) {
								$returnArr[$event_type['id']] = $event_type['event_type'];
							}
						}
						return $returnArr;
					}
					private function menusArray() {
						$returnArr = array(
							'all_menus' => 'All Menus'
						);
						$menus = wp_get_nav_menus();
						foreach($menus as $menu) {
							$returnArr[$menu->slug] = $menu->name;
						}
						return $returnArr;
					}
					private function current_page()
					{
						if (isset($_GET['page'])) {
							$current = $_GET['page'];
							if (isset($_GET['action'])) {
								$current .= "&action=" . $_GET['action'];
							}
						}
						return $current;
					}
					private function current_page_id()
					{
						$current = $this->current_page();
						$tabs_array = $this->nav_tabs_array();
						if (key_exists($current, $tabs_array) && key_exists('id', $tabs_array[$current])) {
							return $tabs_array[$current]['id'];
						} else {
							return false;
						}
					}
					private function nav_tabs_array()
					{
						$plugin_tabs = array(
							$this->svi->get_plugin_name() => array(
								'title' => __('Overview', 'sermonview-integration'),
								'id' => 'overview'
							),
							$this->svi->get_plugin_name() . '-login' => array(
								'title' => __('Login', 'sermonview-integration'),
								'id' => 'login_settings'
							),
							$this->svi->get_plugin_name() . '-account' => array(
								'title' => __('Account', 'sermonview-integration'),
								'id' => 'account_settings'
							),
							$this->svi->get_plugin_name() . '-dashboard' => array(
								'title' => __('Dashboard', 'sermonview-integration'),
								'id' => 'dashboard_settings'
							),
							$this->svi->get_plugin_name() . '-cart' => array(
								'title' => __('Shopping Cart', 'sermonview-integration'),
								'id' => 'cart_settings'
							),
							//					$this->svi->get_plugin_name() . '-gf-overrides' => array(
							//						'title' => __('GF Overrides', 'sermonview-integration'),
							//						'id' => 'gf_override_settings'
							//					),
							$this->svi->get_plugin_name() . '-api' => array(
								'title' => __('API', 'sermonview-integration'),
								'id' => 'api_setup'
							),
							$this->svi->get_plugin_name() . '-log' => array(
								'title' => __('Log', 'sermonview-integration'),
								'id' => 'api_log'
							),
							//					$this->svi->get_plugin_name() . '-dev' => array(
							//						'title' => __('Dev Page', 'sermonview-integration'),
							//						'id' => 'dev-page'
							//					)
						);
						return $plugin_tabs;
					}
					private function nav_tabs($current = null)
					{
						if (empty($current)) {
							$current = $this->current_page();
						}
						$plugin_tabs = $this->nav_tabs_array();
						$content = '';
						$content .= '<h2 class="nav-tab-wrapper">';
						foreach ($plugin_tabs as $location => $details) {
							if ($current == $location) {
								$class = ' nav-tab-active';
							} else {
								$class = '';
							}
							$content .= '<a class="nav-tab' . $class . '" href="?page=' . $location . '">' . $details['title'] . '</a>';
						}
						$content .= '</h2>';
						return $content;
					}
					public function print_section_info($args)
					{
						echo $this->properties[$args['id']]['instructions'];
					}
					public function print_api_instructions()
					{
						echo 'User name and password for SermonView API.';
					}
					public function print_page_setting_instructions()
					{
						echo 'Select a page to use for login forms. This page should have the [' . SermonView_Integration_Login::$shortcode . '] shortcode in it.';
					}
					public function print_account_setting_instructions()
					{
						echo 'Select a page to use for displaying the user account info, including orders.';
					}
					public function print_dashboard_setting_instructions()
					{
						echo 'Settings for the campaign dashboard.';
					}
					public function print_cart_setting_instructions()
					{
						echo 'Select pages for the shopping cart and checkout.';
					}
					public function generate_form_field($args)
					{
						extract($args);
						extract($properties);
						if(!empty($wrapper_style)) {
							echo '<div style="' . $wrapper_style . '">';
						}
						if ($value_type == 'checkbox') {
							echo '<label><input type="checkbox" id="' . $field . '" name="' . $input_name . '[' . $field . ']" value="1" ' . (isset($options_array[$field]) && $options_array[$field] == '1' ? 'checked="checked"' : '') . '" /> ' . $label . '</label>';
						} elseif ($value_type == 'radio') {
							foreach ($properties['options'] as $value => $label) {
								echo '<input type="radio" id="' . $field . '_' . $value . '" name="' . $input_name . '[' . $field . ']" value="' . $value . '"' . (is_array($options_array) && key_exists($field, $options_array) ? checked($value, $options_array[$field], false) : '') . ' /><label for="' . $field . '_' . $value . '">' . $label . '</label><br />';
							}
						} elseif ($value_type == 'password') {
							echo '<input type="password" id="' . $field . '" name="' . $input_name . '[' . $field . ']" value="' . (isset($options_array[$field]) ? esc_attr($options_array[$field]) : '') . '" class="svi-input-field" />';
						} elseif ($value_type == 'dropdown') {
							echo '<select id="' . $field . '" name="' . $input_name . '[' . $field . ']" class="svi-input-field" />';
							foreach ($properties['options'] as $value => $label) {
								$parents = get_post_ancestors($value);
								if (sizeof($parents) > 0) {
									$front_pad = str_pad('', sizeof($parents) * 18, '&nbsp;', STR_PAD_LEFT) . '--';
								} else {
									$front_pad = '';
								}
								echo '<option id="' . $field . '_' . $value . '" name="' . $input_name . '[' . $field . ']" value="' . $value . '"' . selected($value, $options_array[$field], false) . ' />' . $front_pad . $label . '</option>';
							}
							echo '</select>';
						} elseif ($value_type == 'image_preview') {
							echo '<div class="image-preview-block">';
							echo '<a href="#" data-target-id="' . $field . '" class="media-picker" />';
							echo $image_tag;
							echo '</a>';
							echo '<div><span class="reset-default-span' . (empty($options_array[$field]) ? ' hidden' : '') . '"><a href="#" data-target-id="' . $field . '" class="media-reset">Reset to Default</a></span><span class="default-image-span' . (!empty($options_array[$field]) ? ' hidden' : '') . '">Default image</span>' . ' | <a href="#" data-target-id="' . $field . '" class="media-picker" />Change Image</a>' . ' <span class="spinner"></span></div>';
							echo '</a>';
							echo '<input type="hidden" id="' . $field . '" name="' . $input_name . '[' . $field . ']" value="' . (!empty($options_array[$field]) ? esc_attr($options_array[$field]) : '') . '" />';
							echo '</div>';
						} elseif($value_type == 'textarea') {
							echo (isset($prefix) ? $prefix : '') . '<textarea id="' . $field . '" name="' . $input_name . '[' . $field . ']"' . (isset($width) ? ' cols="' . $width . '" ' : '') . (isset($height) ? ' rows="' . $height . '" ' : '') . ' class="svi-input-field" />' . (!empty($options_array[$field]) ? esc_attr($options_array[$field]) : '') . '</textarea>';
						} else {
							echo (isset($prefix) ? $prefix : '') . '<input type="text" id="' . $field . '" name="' . $input_name . '[' . $field . ']" value="' . (!empty($options_array[$field]) ? esc_attr($options_array[$field]) : '') . '" ' . (isset($size) ? 'size="' . $size . '" ' : '') . 'class="svi-input-field" />';
						}
						if (!empty($instructions)) {
							echo '<p class="description">' . $instructions . '</p>';
						}
						if(!empty($wrapper_style)) {
							echo '</div>';
						}
					}

					public function nukeUserPasswordField()
					{
						$user_id = $_GET['user_id'];
						$admin = user_can($user_id, 'edit_posts');
						if (!$admin) {
							$customer_id = get_user_meta($user_id, 'sv_customer_id', true);
							?>
				<script>
					(function($) {
						$('tr#password').html('<th><label for="pass1">New Password</label></th><td>This site uses the SermonView Master Login plugin, which logs in users with the SermonView site authentication system. To change this user\'s password, use the the SermonView <a href="https://www.sermonview.com/cart/admin-ngm07/customers.php?action=snapshot&cID=<?php echo $customer_id; ?>" target="_blank">Online Admin</a>.</td>');

					})(jQuery);
				</script>

				<h2>SermonView Master Login</h2>
				<table class="form-table">
					<tr>
						<th><label for="sv-id">Customer ID</label></th>
						<td><?php echo $customer_id; ?> (<a href="https://www.sermonview.com/cart/admin-ngm07/customers.php?action=snapshot&cID=<?php echo $customer_id; ?>" target="_blank">Customer Snapshot</a>)</td>
					</tr>
				</table>
			<?php
						}
					}

					// show SV customer_id in users list
					public function add_sv_customer_id_column($columns)
					{
						$columns['sv_customer_id'] = 'SV Customer ID';
						return $columns;
					}
					public function show_sv_customer_id_column_content($value, $column_name, $user_id)
					{
						switch ($column_name) {
							case 'sv_customer_id':
								$customer_id = get_user_meta($user_id, 'sv_customer_id', true);
								return '<a href="http://' . ($this->api->settings['which_server'] == 'dev' ? 'dev-larry' : 'www') . '.sermonview.com/cart/admin-ngm07/customers.php?action=snapshot&cID=' . $customer_id . '"  target="_blank">' . $customer_id . '</a>';
								break;
							default:
								return $value;
								break;
						}
						return $value;
					}
					private function pagination_links($records, $per_page, $paged, $link)
					{
						if (strpos($link, '?') === false) {
							$link .= '?';
						}
						$page_count = ceil($records / $per_page);
						ob_start();
						?>
				<div class="tablenav-pages">
					<span class="displaying-num"><?php echo number_format($records, 0); ?> records</span>
					<span class="pagination-links">
						<?php if ($paged < 3) { ?>
							<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
						<?php } else { ?>
							<a class="first-page button" href="<?php echo $link . '&paged=1'; ?>"><span class="screen-reader-text">First page</span><span aria-hidden="true">«</span></a>
						<?php } ?>
						<?php if ($paged == 1) { ?>
							<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
						<?php } else { ?>
							<a class="prev-page button" href="<?php echo $link . '&paged=' . ($paged - 1); ?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>
						<?php } ?>
						<span class="paging-input">
							<label for="current-page-selector" class="screen-reader-text">Current Page</label>
							<input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $paged; ?>" size="1" aria-describedby="table-paging" onchange="this.form.submit()">
							<span class="tablenav-paging-text"> of <span class="total-pages"><?php echo $page_count; ?></span></span>
						</span>
						<?php if ($paged == $page_count) { ?>
							<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
						<?php } else { ?>
							<a class="next-page button" href="<?php echo $link . '&paged=' . ($paged + 1); ?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
						<?php } ?>
						<?php if ($paged > $page_count - 2) { ?>
							<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
						<?php } else { ?>
							<a class="last-page button" href="<?php echo $link . '&paged=' . $page_count; ?>"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>
						<?php } ?>
					</span>
				</div>

	<?php
				return ob_get_clean();
			}
		}
	}

	if (!function_exists('wp_password_change_notification')) {
		function wp_password_change_notification()
		{
			// do nothing - overriding default of sending emails to admin on password changes
		}
	}

	// backport the gzdecode() function added in PHP 5.4 to PHP 5.3 - used in Cornerstone 3.1.2
	// not needed with Cornerstone 3.1.3
	if (!function_exists('gzdecode')) {
		function gzdecode($data)
		{
			return gzinflate(substr($data, 10, -8));
		}
	}
