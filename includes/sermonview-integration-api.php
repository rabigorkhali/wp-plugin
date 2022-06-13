<?php

/* * ***************************************************

  SermonView Integration API Class
  Larry Witzel
  2/15/18

	Methods and properties for interacting with the SermonView API

  "I try to find common ground with everyone, doing everything I can to save some.
  I do everything to spread the Good News and share in its blessings."
  1 Corinthians 9:22b, 23 NLT2

 * **************************************************** */




class SermonView_Integration_API {
	public static $settings_name = 'sermonview_api_settings';
	public $settings;

	// Domain settings
	private static $api_dev_domain = 'dev-larry.sermonview.com';
	private static $api_live_domain = 'www.sermonview.com';

	private static $log_file_name = 'svi_log';

	public function __construct(&$svi=null) {
		if($svi) {
			$this->svi = $svi;
		}
	}
	public function run() {
		$this->init();
	}
	private function init() {
		add_option(self::$settings_name); // setup the option, in case it doesn't already exist
		$this->settings = get_option(self::$settings_name); // load the settings

	}
	public function getStatus() {
		return $this->hitApi('getStatus');
	}

	public function apiConnected() {
		$status = $this->hitApi('getStatus');
		$result = json_decode($status['result']);
		return $result;
	}
	public function connectionStatusIcon() {
		$result = $this->apiConnected();
		if($result->success) {
			if(strstr($result->server,'dev')) {
				return '<i class="fa fa-toggle-on status-dev"></i><div class="connection-type">Dev Server</div>';
			} else {
				return '<i class="fa fa-toggle-on status-on"></i>';
			}
		} else {
			return '<i class="fa fa-toggle-on fa-flip-horizontal status-off"></i><div class="connection-type status-off">Disconnected</div>';
		}
	}
	public static function logTableName() {
		global $wpdb;
		return $wpdb->prefix . self::$log_file_name;
	}
	/*************************************
	 * Old Gravity Forms plugin functions
	 */
	public function getActions($action_id=false) {
		$server = $this->hitApi('getActions');
		$result = json_decode($server['result'],true);
		if($result['success']) {
			$actions = $result['actions'];

			// gotta add a key to each element
			$actionsArr = array();
			foreach($actions as $action) {
				$actionsArr[$action['id']] = $action;
			}

			if($action_id) {
				return $actionsArr[$action_id];
			} else {
				return array(
					'total_actions' => count($actions),
					'actions' => $actionsArr
				);
			}
		}
	}
	public function getMergeFields($action_id=null) {
		if(!empty($action_id)) {
			$server = $this->hitApi('getDataModel','action_id=' . $action_id);
			$result = json_decode($server['result'],true);
			if($result['success']) {
				$merge_fields = $result['data_model'];
				return array(
					'total_items' => count($merge_fields),
					'merge_fields' => $merge_fields
				);
			}
		}
	}
	public function getResponseFields($action_id=null) {
		if(!empty($action_id)) {
			$server = $this->hitApi('getDataModel','action_id=' . $action_id . '&type=response');
			$result = json_decode($server['result'],true);
			if($result['success']) {
				$merge_fields = $result['data_model'];
				return array(
					'total_items' => count($merge_fields),
					'merge_fields' => $merge_fields
				);
			}
		}
	}

	public function submitFeed($action_id,$vars) {
		$vars = $this->prepPostFromArray($vars);
		switch($action_id) {
			case 'kit_request':
			case 'request_kit':
				return $this->requestKit($vars);
				break;
			case 'create_event':
				return $this->createEvent($vars);
				break;
			case 'update_event':
				return $this->updateEvent($vars);
				break;
		}
	}
	public function requestKit($vars) {
		return $this->hitApi('requestKit',$vars);
	}
	public function createEvent($vars) {
		return $this->hitApi('createEvent',$vars);
	}
	public function updateEvent($vars) {
		return $this->hitApi('updateEvent',$vars);
	}


	/*************************************
	 * SermonView API function
	 */
	public function authenticate_sermonview($creds) {
		// make this friendly
		if(empty($creds['email']) && !empty($creds['svi_email'])) $creds['email'] = $creds['svi_email'];
		if(empty($creds['password']) && !empty($creds['svi_password'])) $creds['password'] = $creds['svi_password'];

		$response = $this->hitApi('authenticateCustomer','email='.rawurlencode($creds['email']).'&password='.  rawurlencode($creds['password']));
		return json_decode($response['result']);
	}

	/* Customer */
	public function get_sermonview_customer($email) {
		$response = $this->hitApi('getCustomer','email='.rawurlencode($email));
		return json_decode($response['result']);
	}
	public function get_sermonview_customer_by_cid($cID) {
		$response = $this->hitApi('getCustomer','customer_id='.(int)$cID);
		return json_decode($response['result']);
	}
	public function create_sermonview_customer($post) {
		$apiPostArr = array();
		foreach($this->svi->login->signup_form_fields() as $field) {
			if(key_exists('sv_field',$field)) {
				$apiPostArr[$field['sv_field']] = rawurlencode($post[$field['field']]);
			}
		}
		$apiPostArr['email'] = rawurlencode($post['svi_email']);
		$apiPostArr['adventist'] = $this->login_settings['flag_adventist'];
		$apiPostArr['prospect'] = $this->login_settings['set_prospect'];

		$post_val = $this->prepPostFromArray($apiPostArr);
		$response = $this->hitApi('createCustomer',$post_val);
		return json_decode($response['result']);
	}
	public function change_sermonview_password($post) {
		if(empty($post['email']) && !empty($post['svi_email'])) $post['email'] = $post['svi_email'];
		if(empty($post['password']) && !empty($post['svi_password'])) $post['password'] = $post['svi_password'];

		$response = $this->hitApi('changeCustomerPassword','email='.rawurlencode($post['email']).'&password='.  rawurlencode($post['password']));
		return json_decode($response['result']);
	}
	public function update_sermonview_customer($post) {
		$apiPostArr = $this->buildPostArray($post, $this->svi->account->fields_edit_account());
		$post_val = $this->prepPostFromArray($apiPostArr);
		$response = $this->hitApi('updateCustomer',$post_val);
		return json_decode($response['result']);
	}

	/* Additional email addresses */
	public function update_additional_email($post) {
		$post_vars = 'customer_id=' . (int)$post['customer_id'] . '&additional_email_id=' . (int)$post['additional_email_id'] . '&email=' . urlencode($post['email']);
		$response = $this->hitApi('updateAdditionalEmail', $post_vars);
		return json_decode($response['result']);
	}
	public function create_additional_email($post) {
		$post_vars = 'customer_id=' . (int) $post['customer_id'] . '&email=' . urlencode($post['email']);
		$response = $this->hitApi('createAdditionalEmail', $post_vars);
		return json_decode($response['result']);
	}
	public function make_primary_email($customer_id, $additional_email_id) {
		$post_vars = 'customer_id=' . (int) $customer_id . '&additional_email_id=' . (int) $additional_email_id;
		$response = $this->hitApi('makePrimaryEmail', $post_vars);
		return json_decode($response['result']);
	}
	public function delete_additional_email($customer_id,$additional_email_id) {
		$post_vars = 'customer_id=' . (int) $customer_id . '&additional_email_id=' . (int) $additional_email_id;
		$response = $this->hitApi('deleteAdditionalEmail',$post_vars);
		return json_decode($response['result']);
	}

	/* Addresses */
	public function get_addresses($customer_id) {
		$response = $this->hitApi('getAddresses','customer_id='.(int)$customer_id);
		return json_decode($response['result']);
	}
	public function get_addresses_by_email($email) {
		$response = $this->hitApi('getAddresses','email='.rawurlencode($email));
		return json_decode($response['result']);
	}
	public function get_address($address_id) {
		$response = $this->hitApi('getAddress','address_id='.(int)$address_id);
		return json_decode($response['result']);
	}
	public function update_address($post) {
		// build the submission
		$apiPostArr = $this->buildPostArray($post, $this->svi->account->fields_edit_address());
		$post_val = $this->prepPostFromArray($apiPostArr);
		$response = $this->hitApi('updateAddress',$post_val);
		return json_decode($response['result']);
	}
	public function create_address($post) {
		// build the submission
		$apiPostArr = $this->buildPostArray($post, $this->svi->account->fields_edit_address());
		$apiPostArr['customer_id'] = $post['customer_id'];
		$post_val = $this->prepPostFromArray($apiPostArr);
		$response = $this->hitApi('createAddress',$post_val);
		return json_decode($response['result']);
	}
	public function delete_address($address_id) {
		$response = $this->hitApi('deleteAddress','address_id='.(int)$address_id);
		return json_decode($response['result']);
	}
	public function set_default_address($address_id,$type='primary') {
		$vars = 'address_id=' . (int)$address_id;
		switch($type) {
			case 'primary':
			default:
				$vars .= '&is_primary=true';
				break;
			case 'shipping':
				$vars .= '&is_default_shipping=true';
				break;
			case 'billing':
				$vars .= '&is_default_billing=true';
				break;
		}
		$response = $this->hitApi('updateAddress',$vars);
		return json_decode($response['result']);
	}

	/* Events */
	public function get_event_types() {
		$server = $this->hitApi('getEventTypes');
		$result = json_decode($server['result'],true);
		if($result['success']) {
			return $result['event_types'];
		}
	}
	public function get_events($restrictions=false) {
		// automatically restricted to user, as well as event type and/or image as set in the admin dashboard settings
		if(is_user_logged_in()) {
			$user = wp_get_current_user();
			$post_val = 'email='.rawurlencode($user->user_email);
			if(is_array($restrictions)) {
				foreach($restrictions as $key => $value) {
					$post_val .= '&' . $key . '=' . $value;
				}
			}
			$response = $this->hitApi('getEvents',$post_val);
			$events = json_decode($response['result']);
			foreach($events->events as &$event) {
				if(empty($event->title)) {
					$event->title = $event->event_info->title->value;
				}
			}
			return $events;
		} else {
			return false;
		}
	}
	public function get_event($event_id,$include_interests=false) {
		$params = array(
			'event_id' => $event_id
		);
		if($include_interests) {
			$params['include_interests'] = true;
		}
		$events = $this->get_events($params);
		return $events->events[0];
	}

	/* Orders */
	public function get_orders($customer_id) {
		$post_val = 'customer_id=' . $customer_id . '&hide_zero=1';
		$response = $this->hitApi('getOrders', $post_val);
		return json_decode($response['result']);
	}
	public function get_order($order_id, $customer_id) {
		$post_val = 'order_id=' . $order_id . '&customer_id=' . $customer_id;
		$response = $this->hitApi('getOrder', $post_val);
		return json_decode($response['result']);
	}
	public function get_receipt_details() {
		$response = $this->hitApi('getReceiptDetails');
		return json_decode($response['result']);
	}

	/* Products */
	public function get_product($product_id,$category_id=null) {
		$post_val = 'product_id=' . (int)$product_id;
		if((int)$category_id) {
			$post_val .= '&category_id=' . (int)$category_id;
		}
		$response = $this->hitApi('getProduct',$post_val);
		return json_decode($response['result']);
	}
	public function get_products_by_pID($product_id) {
		$post_val = 'product_id=' . $product_id;
		$response = $this->hitApi('getProducts',$post_val);
		return json_decode($response['result']);
	}
	public function get_products_by_cID($category_id) {
		$post_val = 'category_id=' . $category_id;
		$response = $this->hitApi('getProducts',$post_val);
		return json_decode($response['result']);
	}

	/* Shopping cart */
	public function get_cart($customer_id,$extras=null) {
		$post_val = 'customer_id=' . $customer_id;
		switch($extras) {
			case 'shipping':
				$post_val .= '&include_shipping=1';
				break;
			case 'billing':
				$post_val .= '&include_billing=1';
				break;
		}
		$response = $this->hitApi('getCart', $post_val);
		return json_decode($response['result']);
	}
	public function add_to_cart($customer_id, $product_id, $quantity, $event_id = null, $category_id = null,$return_id = null) {
		$post_val = 'customer_id=' . $customer_id . '&product_id=' . $product_id . '&quantity=' . $quantity;
		if ($event_id) {
			$post_val .= '&event_id=' . $event_id;
		}
		if ($category_id) {
			$post_val .= '&category_id=' . $category_id;
		}
		if ($return_id) {
			$post_val .= '&return_id=' . $return_id;
		}
		$response = $this->hitApi('addProductToCart', $post_val);
		return json_decode($response['result']);
	}
	public function update_cart($customer_id,$product_array) {
		$post_val = 'customer_id=' . $customer_id;
		$i=0;
		foreach($product_array as $opID => $qty) {
			$post_val .= '&products[' . $i . '][orders_products_id]=' . $opID;
			$post_val .= '&products[' . $i . '][quantity]=' . $qty;
			$i++;
		}
		$response = $this->hitApi('updateCart', $post_val);
		return json_decode($response['result']);
	}
	public function update_cart_address($customer_id,$aID,$type='shipping') {
		switch($type) {
			case 'shipping':
			default:
				$prefix = 'shipping';
				break;
			case 'billing':
				$prefix = 'billing';
				break;
		}
		$post_val = 'customer_id=' . $customer_id . '&' . $prefix . '_address_id=' . $aID;
		$response = $this->hitApi('updateCart',$post_val);
		return json_decode($response['result']);
	}
	public function update_cart_shipping($customer_id,$id,$title,$cost,$discounted=false) {
		$post_val = 'customer_id=' . $customer_id .
					 '&shipping_method_id=' . $id . 
					 '&shipping_method_title=' . urlencode($title) .
					 '&shipping_method_cost=' . $cost .
					 '&shipping_method_discounted=' . $discounted;
		$response = $this->hitApi('updateCart', $post_val);
		return json_decode($response['result']);
	}
	public function process_order($post) {
		$post['receipt_link'] = urlencode($this->svi->account->page_url('receipt', 'svID=' . $this->svi->customer->customer_id . '&lh=' . $this->svi->customer->login_hash . '&oID='));
		if(!empty($this->svi->shopping_cart->settings['receipt_address'])) {
			$post['receipt_address'] = $this->svi->shopping_cart->settings['receipt_address'];
		}
		if(!empty($this->settings['receipt_img'])) {
			$post['receipt_img'] = wp_get_attachment_image($this->settings['receipt_img'], 'post-thumbnail', false, array( 'id' => 'receipt_image' ) );
		}
		$response = $this->hitApi('processOrder',$this->prepPostFromArray($post));
		return json_decode($response['result']);
	}
	public function get_payment_methods() {
		$response = $this->hitApi('getPaymentMethods');
		return json_decode($response['result']);
	}

	// General API functions
	private function buildPostArray($post,$fields) {
		$apiPostArr = array();
		foreach($fields as $field) {
			if(key_exists('sv_field',$field)) {
				$apiPostArr[$field['sv_field']] =	rawurlencode($post[$field['field']]);
			}
			// deal with subfields
			if(key_exists('values',$field) && is_array($field['values'])) {
				foreach($field['values'] as $subfield) {
					if(key_exists('sv_field',$subfield) && key_exists($subfield['field'],$post)) {
						$apiPostArr[$subfield['sv_field']] = rawurlencode($post[$subfield['field']]);
					}
				}
			}
		}
		return $apiPostArr;
	}
	private function prepPostFromArray($array) {
		if(is_array($array)) {
			$post = '';
			foreach($array as $key => $value) {
				// let's force + to be %2B
				$value = str_replace('+', '%2B', $value);
				$post .= $key . '=' . $value . '&';
			}
			return trim($post,'&');
		} else {
			return $array;
		}
	}
	private function hitApi($command,$message=null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, 'https://' . $this->apiRootDomain() . '/cart/admin-ngm07/api2.php?action=' . $command);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		// curl_setopt($ch, CURLOPT_BUFFERSIZE, 4); // this small buffer size slows down php7 servers (but seems to not cause any problem for php5.3)
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSLVERSION, 1);
		if($this->settings['which_server'] == 'dev') {
			curl_setopt($ch, CURLOPT_USERPWD, 'ngmadmin:ngmdev');
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Api-User: ' . $this->settings['api_user'],'Api-Key: ' . $this->settings['api_key']));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
		$server['result'] = curl_exec($ch);
		$server['error'] = curl_errno($ch);
		$server['error_msg'] = curl_error($ch) . ' (' . curl_errno($ch) . ')';

		curl_close($ch);

		$this->logApiHit($command,$message,$server);
		return $server;
	}
	private function logApiHit($command,$message,$server) {
		if($this->settings['enable_log']) {
			global $wpdb;
			$result = json_decode($server['result']);
			$result = $this->objectifyResult($result);
			$dataArr = array(
				'date' => current_time('mysql'),
				'command' => $command,
				'submission' => $message,
				'response' => $server['result'],
				'result' => ($result->success == 'true' ? 'success' : 'error'),
				'error' => $server['error_msg']
			);
			if($this->settings['enable_backtrace']) {
				$dataArr['backtrace'] = json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS ,10));
			}
			$wpdb->insert($wpdb->prefix . self::$log_file_name,$dataArr);
			if($result->success != 'true') {
				// TODO: EMAIL ERRORS
				// wp_mail('lwitzel@narrowgatemail.com','SermonView Master Login Plugin API Error',print_r($dataArr,1));
			}
		}
	}
	private function apiRootDomain() {
		switch($this->settings['which_server']) {
			case 'live':
				return self::$api_live_domain;
				break;
			case 'dev':
			default:
				return self::$api_dev_domain;
				break;
		}
	}
	public function objectifyResult($result) {
		if(!is_object($result)) {
			$result = new stdClass();
			$result->success = false;
			$result->message = '';
		}
		return $result;
	}
	public function log($query=null,$limit=null) {
		global $wpdb;
		return $wpdb->get_results("select * from " . $this->logTableName() . ($query ? " where " . $query : '') . " order by date desc limit " . ($limit ?? '50') );
	}
	public function getLogCommands() {
		global $wpdb;
		$commands_q = $wpdb->get_results("select distinct command from " . $this->logTableName());
		$responseArr = array();
		foreach($commands_q as $command) {
			$responseArr[] = $command->command;
		}
		sort($responseArr);
		return $responseArr;
	}
	public function logRecordCount($query) {
		global $wpdb;
		$count = $wpdb->get_row("select count(id) as count from " . $this->logTableName() . ($query ? " where " . $query : ''));
		return $count->count;
	}
}
?>
