<?php

/* * ***************************************************

  SermonView Integration Dashboard Class
  Larry Witzel
  2/15/18

	Properties and methods for presenting the user's campaign dashboard

  "I try to find common ground with everyone, doing everything I can to save some.
  I do everything to spread the Good News and share in its blessings."
  1 Corinthians 9:22b, 23 NLT2

 * **************************************************** */




class SermonView_Integration_Account {
	public static $settings_name = 'sermonview_account_settings';
	public $settings;

	public static $shortcode = 'sermonview-my-account';

	public function __construct(&$svi) {
		$this->svi = $svi;
		$this->api = $this->svi->api;
	}
	public function run() {
		$this->init();
	}
	private function init() {
		add_option(self::$settings_name); // setup the option, in case it doesn't already exist
		$this->settings = get_option(self::$settings_name); // load the settings

		// form actions
		add_action('admin_post_svi_edit_account', array(&$this, 'action_edit_account'));
		add_action('admin_post_svi_add_address', array(&$this, 'action_add_address'));
		add_action('admin_post_svi_edit_address', array(&$this, 'action_edit_address'));
		add_action('admin_post_svi_delete_address', array(&$this, 'action_delete_address'));

		add_action('admin_post_svi_process_email', array(&$this, 'action_process_email'));
		// add_action('admin_post_svi_delete_email', array(&$this, 'action_delete_email'));
		// add_action('admin_post_svi_make_email_primary', array(&$this, 'action_make_email_primary'));

		// prevent throwing error for redirect after delete address
		// https://tommcfarlin.com/wp_redirect-headers-already-sent/
		add_action('init', array(&$this, 'output_buffer'));

		// remove admin toolbar from subscriber users
		add_action('init', array(&$this, 'remove_admin_bar'));

		// Shortcodes
		add_shortcode(self::$shortcode, array(&$this, 'account'));
		add_shortcode('sermonview-account', array(&$this, 'account'));
		add_shortcode('sermonview-account-greeting', array(&$this, 'account_greeting'));
	}
	public function output_buffer() {
		ob_start();
	}
	public function remove_admin_bar() {
		if(!current_user_can('edit_posts')) {
			add_filter('show_admin_bar', '__return_false');
		}
	}
	public function account() {
		// user must be logged in to view this table
		if(!is_user_logged_in()) {
			auth_redirect();
		}
		$action = filter_input(INPUT_GET,'action',FILTER_SANITIZE_URL);
		switch($action) {
			case 'account':
			default:
				$this->account_main();
				break;
			case 'edit':
				$this->account_edit();
				break;
			case 'emails':
				$this->account_emails();
				break;
			case 'edit_email':
				$this->edit_email($action);
				break;
			case 'add_email':
				$this->add_email($action);
				break;
			case 'delete_email':
				$this->delete_email();
				break;
			case 'make_primary_email':
				$this->make_primary_email();
				break;
			case 'addresses':
				$this->addresses();
				break;
			case 'edit_address':
				$this->edit_address();
				break;
			case 'add_address':
				$this->add_address();
				break;
			case 'delete_address':
				$this->delete_address();
				break;
			case 'set_default_address':
				$this->set_default_address();
				break;
			case 'dashboard':
				wp_redirect(get_page_link($this->svi->dashboard->settings['dashboard_page']));
				break;
			case 'receipt':
				$this->receipt();
				break;
		}

	}
	public function account_greeting() {
		$user = wp_get_current_user();
		ob_start();
	?>
		Hello, <strong><?php echo $user->display_name ?>!</strong> <a href="<?php echo wp_logout_url(); ?>">Logout</a>
<?php
		return ob_get_clean();
	}
	public function account_main() {
		$user = wp_get_current_user();
		$sv_customer = $this->svi->customer;
		$sv_addresses = $this->svi->api->get_addresses_by_email($user->user_email);
		$sv_orders = $this->svi->api->get_orders($sv_customer->customer_id);
		if(!empty($this->svi->dashboard->settings['restrict_to_event_type'])) {
			foreach($sv_orders->orders as $id => $order) {
				if((int)$this->svi->dashboard->settings['restrict_to_event_type'] != 0 && $order->event->event_type_id != $this->svi->dashboard->settings['restrict_to_event_type']) {
					unset($sv_orders->orders->{$id});
				}
			}
		}
		require_once('views/account/index.php');
	}
	public function page_url($type='account',$vars=null) {
		switch ($type) {
			case 'account':
			default:
				$link = get_page_link($this->settings['account_page']);
				break;
			case 'receipt':
				$link = get_page_link($this->settings['account_page']);
				$vars = 'action=receipt&' . $vars;
				break;
		}
		if ($vars) {
			if (strpos($url, '?') === false) {
				$link .= '?' . $vars;
			} else {
				$link .= '&' . trim($vars,'&');
			}
		}
		return $link;
	}
	public function add_nav_menu() {
		return $this->settings['add_nav_menu'] == 'true';
	}
	public function nav_menu_slug() {
		return $this->settings['nav_menu_slug'];
	}
	public function is_pro_theme() {
		return $this->settings['is_pro_theme'] == 'true';
	}

	/////////// Edit Account ///////////////////
	public function account_edit() {
		$sv_customer = $this->svi->customer;
		?>
<div class="wrap svi-form-wrapper">
	<?php	echo $this->back_link('account'); ?>
	<h2>Edit Your Account<div class="subhead">Required fields in <strong>bold.</strong></div></h2>
<?php
		if(!empty($_GET['nonce'])) {
			$post = get_transient('svi_transient_post_' . $_GET['nonce']);
			$message = get_transient('svi_transient_message_' . $_GET['nonce']);
			if(!is_array($message)) {
				echo '<div class="validation_message">' . $message . '</div>';
			} elseif(key_exists('top_message',$message)) {
				echo '<div class="validation_message">' . $message['top_message'] . '</div>';
			}
		} else {
			$post = array();
			$message = '';
		}
		// Confirm $sv_customer exists
		if($sv_customer->customer_exists) {
			// build the form
			$form = new SermonView_Integration_Form(esc_url( admin_url('admin-post.php') ));
			$form->setPost($post);
			$form->setMessage($message);
			$form->addFields($this->fields_edit_account($sv_customer));
			$form->addField(array(
				'field' => 'action',
				'type' => 'hidden',
				'value' => 'svi_edit_account'
			));
			$form->addButton(array(
				'label' => 'Update Account',
				'fa-icon' => 'fa-arrow-right'
			));
			$form->setNonceName('svi_edit_account_form_nonce');
			$form->buildForm();
		} else {
			echo 'An error has occurred. Your account information cannot be loaded. Please contact customer service for assistance.';
		}
?>
</div>
		<?php
	}
	public function action_edit_account() {
		$post = $_POST;
		if(isset($post['_wpnonce']) && wp_verify_nonce($post['_wpnonce'],'svi_edit_account_form_nonce')) {
			// Form validation
			$error = SermonView_Integration_Form::validate($post,$this->fields_edit_account());

			if(sizeof($error) == 0) {
				// submit to SV API to create user
				$result = $this->api->update_sermonview_customer($post);
				if($result->customer_id <= 0) {
					$error = $result->message;
				}
				// if errors, tell user
				if($error) {
					$message = $error;
				} else {
					// Update the WP_User
					$user = wp_get_current_user();
					add_filter('send_email_change_email', '__return_false');
					wp_update_user(
						array(
							'ID' => $user->ID,
							'user_email' => $result->email,
							'first_name' => $result->firstname,
							'last_name' => $result->lastname,
							'display_name' => $result->fullname,
							'nickname' => $result->fullname
						)
					);
					// TODO: Change the username, too - wp_update_user() won't let you do that, perhaps direct DB update?

					// success message and return to main account page
					$action = 'account';
					$message = 'Your account was successfully updated.';
				}

			} else {
				$error['top_message'] = 'Please correct the errors below:';
				$message = $error;
			}
		} else {
			$message = 'Invalid security token. This may be caused by the page sitting open for a while before continuing.';
			$action = 'edit';
		}
		SermonView_Integration_Form::custom_redirect($post,$message,$step,$action);
	}
	public function fields_edit_account($sv_customer=null) {
		if(is_null($sv_customer)) {
			$sv_customer = new stdClass();
		}
		$fields = array(
			array(
				'field' => 'customer_id',
				'validation' => 'required',
				'type' => 'hidden',
				'sv_field' => 'customer_id',
				'value' => (property_exists($sv_customer,'customer_id') ? $sv_customer->customer_id : null)
			),
			array(
				'field' => 'firstname',
				'label' => 'First Name',
				'validation' => 'required',
				'type' => 'text',
				'sv_field' => 'firstname'
			),
			array(
				'field' => 'lastname',
				'label' => 'Last Name',
				'validation' => 'required',
				'type' => 'text',
				'sv_field' => 'lastname'
			),
			array(
				'field' => 'email',
				'label' => 'Email Address',
				'validation' => 'required|email',
				'type' => 'text',
				'sv_field' => 'email',
				'instructions' => 'This email address will be used to communicate important information about your events and orders, such as proofs, tracking info, invoices and account alerts.'
			),
			array(
				'type' => 'divider'
			),
			array(
				'field' => 'phone',
				'label' => 'Mobile Phone',
				'validation' => 'required',
				'type' => 'text',
				'sv_field' => 'telephone'
			),
//			array(
//				'label' => false,
//				'validation' => '',
//				'type' => 'checkbox',
//				'values' => array(
//					array(
//						'field' => 'accept_sms',
//						'label' => 'Yes, I accept SMS text messages',
//						'value' => 'true',
//						'sv_field' => 'accept_sms'
//					)
//				),
//				'sv_field' => 'telephone'
//			),
			array(
				'field' => 'telephone_alt',
				'label' => 'Alternate Phone',
				'validation' => '',
				'type' => 'text',
				'sv_field' => 'telephone_alt'
			)
//			,
//			array(
//				'field' => 'best_contact',
//				'label' => 'Best Contact Method',
//				'validation' => '',
//				'type' => 'dropdown',
//				'values' => array(
//					'text_main' => 'Text to mobile phone',
//					'call_main' => 'Call mobile phone',
//					'call_alt' => 'Call alternate phone',
//					'email' => 'Email'
//				),
//				'sv_field' => 'best_contact'
//			)
		);
		// set default field value to $sv_customer value
		foreach($fields as &$field) {
			if(key_exists('sv_field',$field) && property_exists($sv_customer, $field['sv_field'])) {
				$field['default'] = $sv_customer->{$field['sv_field']};
			} elseif(empty($field['default'])) {
				$field['default'] = null;
			}
			if(key_exists('values',$field) && is_array($field['values'])) {
				foreach($field['values'] as &$subfield) {
					if(is_array($subfield)) {
						if(key_exists('sv_field',$subfield) && property_exists($sv_customer, $subfield['sv_field'])) {
							$subfield['default'] = $sv_customer->{$subfield['sv_field']};
						} else {
							$subfield['default'] = null;
						}
					}
				}
			}
		}
		return $fields;
	}

	/////////// Other Email Addresses //////////
	public function account_emails() {
		$sv_customer = $this->svi->customer;
		require_once('views/account/emails.php');
	}
	public function add_email($action) {
		$sv_customer = $this->svi->customer;
		$email_address = '';
		require_once('views/account/edit_email.php');
	}
	public function edit_email($action) {
		$additional_email_id = filter_input(INPUT_GET, 'eID', FILTER_SANITIZE_NUMBER_INT);
		$sv_customer = $this->svi->customer;
		$email_address = '';
		if (is_array($sv_customer->other_emails)) {
			foreach($sv_customer->other_emails as $email) {
				if($email->id == $additional_email_id) {
					$email_address = $email->email;
				}
			}
		}
		require_once('views/account/edit_email.php');
	}
	public function action_process_email() {
		$post = $_POST;
		$post['customer_id'] = $this->svi->customer->customer_id;
		if((int)$post['additional_email_id'] > 0) {
			$result = $this->api->update_additional_email($post);
		} else {
			$result = $this->api->create_additional_email($post);
		}
		if (!$result->success || $result->success == 'false') {
			$message = 'An error occurred: ' . $result->message;
		}
		$post['_wp_http_referer'] = get_page_link($this->settings['account_page']);
		if(!empty($message)) {
			if((int)$post['additional_email_id'] > 0) {
				$action = 'edit_email';
			} else {
				$action = 'add_email';
			}
		} else {
			$action = 'emails';
		}
		$step = null;
		SermonView_Integration_Form::custom_redirect($post, $message, $step, $action);
	}
	public function delete_email() {
		$additional_email_id = filter_input(INPUT_GET, 'eID', FILTER_SANITIZE_NUMBER_INT);
		$result = $this->api->delete_additional_email($this->svi->customer->customer_id, $additional_email_id);
		if ($result->success && $result->success != 'false') {
			$message = 'Email address was successfully deleted.';
		} else {
			$message = 'An error occurred: ' . $result->message;
		}
		$post = array(
			'_wp_http_referer' =>  get_page_link($this->settings['account_page'])
		);
		$action = 'emails';
		$step = null;
		SermonView_Integration_Form::custom_redirect($post, $message, $step, $action);
	}
	public function make_primary_email()
	{
		$additional_email_id = filter_input(INPUT_GET, 'eID', FILTER_SANITIZE_NUMBER_INT);
		$result = $this->api->make_primary_email($this->svi->customer->customer_id, $additional_email_id);
		if ($result->success && $result->success != 'false') {
			$user = wp_get_current_user();
			add_filter('send_email_change_email', '__return_false');
			wp_update_user(
				array(
					'ID' => $user->ID,
					'user_email' => $result->primary_email_address
				)
			);
			$message = 'Primary email address was successfully updated.';
		} else {
			$message = $result->error;
		}
		$post = array(
			'_wp_http_referer' =>  get_page_link($this->settings['account_page'])
		);
		$action = 'emails';
		$step = null;
		SermonView_Integration_Form::custom_redirect($post, $message, $step, $action);
	}

	/////////// Addresses ///////////////////
	public function addresses() {
		$user = wp_get_current_user();
		$sv_addresses = $this->svi->api->get_addresses_by_email($user->user_email);
?>
<div class="wrap svi-wrapper">
	<?php	echo $this->back_link('account'); ?>
	<h2>Your Addresses</h2>
<?php
	if(!empty($_GET['nonce'])) {
		$message = get_transient('svi_transient_message_' . $_GET['nonce']);
		if(!is_array($message)) {
			echo '<div class="validation_message">' . $message . '</div>';
		} elseif(key_exists('top_message',$message)) {
			echo '<div class="validation_message">' . $message['top_message'] . '</div>';
		}
	}
		if(is_object($sv_addresses->addresses)) {
			echo '<div class="x-container flexmethod max width">';
				echo '<div class="x-column x-sm container x-1-3 svi-address-box svi-add-address-wrapper">';
					echo '<a href="' . get_page_link($this->settings['account_page']) . '?action=add_address"><div class="svi-add-address">' . '<span>+</span>' . 'Add Address' . '</div></a>';
				echo '</div>';
			$col = 1;
			foreach($sv_addresses->addresses as $address) {
				echo '<div class="x-column x-sm container x-1-3 svi-address-box">';
					echo '<div class="svi-address">';
						echo $this->outputAddress($address);
					echo '</div>';
				echo '</div>';
				$col++;
				if($col >= 3) {
					$col = 0;
					echo '</div><div class="x-container flexmethod max width">';
				}
			}
			echo '</div>';
		} else {
			echo '<div class="validation_message">' . 'Addresses could not be loaded. Please contact customer service for assistance.' . '</div>';
		}
?>
</div>
<?php
	}
	public function outputAddress($address,$address_only=false,$no_links=false) {
		$return = '';
		if(!$address_only) {
			$return .= '<div class="svi-address-line">' . '<strong>' . $address->fullname . '</strong>' . '</div>' . "\n";
		}
		if(!empty($address->organization)) {
			$return .= '<div class="svi-address-line">' . $address->organization . '</div>' . "\n";
		}
		$return .= '<div class="svi-address-line">' . $address->street_address . '</div>' . "\n";
		if(!empty($address->street_address_2)) {
			$return .= '<div class="svi-address-line">' . $address->street_address_2 . '</div>' . "\n";
		}
		$return .= '<div class="svi-address-line">' . $address->city . ', ' . $address->state . ' ' . $address->zip . '</div>' . "\n";
		$return .= '<div class="svi-address-line">' . $address->country . '</div>' . "\n";
		if (!$address_only) {
			$return .= '<hr />';
			$return .= '<div class="svi-address-line note">' . $address->note . '</div>' . "\n";
			if($address->is_primary) {
				$return .= '<div class="svi-address-line default">' . 'Primary address' . '</div>' . "\n";
			}
			if($address->is_default_billing) {
				$return .= '<div class="svi-address-line default">' . 'Default billing address' . '</div>' . "\n";
			}
			if ($address->is_default_shipping) {
				$return .= '<div class="svi-address-line default">' . 'Default shipping address' . '</div>' . "\n";
			}
			if(!$address_only && !$no_links) {
				$return .= '<div class="control-links">';
				if(!$address->is_primary) {
					$return .= '<a href="' . get_page_link($this->settings['account_page']) . '?action=set_default_address&aID=' . (int)$address->address_id . '&type=primary">Make Primary Address</a><br />';
				}
				if(!$address->is_default_billing) {
					$return .= '<a href="' . get_page_link($this->settings['account_page']) . '?action=set_default_address&aID=' . (int)$address->address_id . '&type=billing">Make Default Billing</a><br />';
				}
				if(!$address->is_default_shipping) {
					$return .= '<a href="' . get_page_link($this->settings['account_page']) . '?action=set_default_address&aID=' . (int)$address->address_id . '&type=shipping">Make Default Shipping</a><br />';
				}
				$return .= '<a href="' . get_page_link($this->settings['account_page']) . '?action=edit_address&aID=' . (int)$address->address_id . '">Edit</a>' . ' | ';
				$return .= '<a href="' . get_page_link($this->settings['account_page']) . '?action=delete_address&aID=' . (int)$address->address_id . '">Delete</a>';
				$return .= '</div>';
			}
		}
		return $return;
	}
	public function add_address() {
		$sv_customer = $this->svi->customer;
		?>
<div class="wrap svi-form-wrapper">
	<?php	echo $this->back_link('addresses'); ?>
	<h2>Add New Address<div class="subhead">Required fields in <strong>bold.</strong></div></h2>
<?php
	if(!empty($_GET['nonce'])) {
		$post = get_transient('svi_transient_post_' . $_GET['nonce']);
		$message = get_transient('svi_transient_message_' . $_GET['nonce']);
		if(!is_array($message)) {
			echo '<div class="validation_message">' . $message . '</div>';
		} elseif(key_exists('top_message',$message)) {
			echo '<div class="validation_message">' . $message['top_message'] . '</div>';
		}
	} else {
		$post = array();
		$message = '';
	}
		// build the form
		$form = new SermonView_Integration_Form(esc_url( admin_url('admin-post.php') ));
		$form->setPost($post);
		$form->setMessage($message);
		$form->addFields($this->fields_edit_address());
		$form->addField(array(
			'field' => 'action',
			'type' => 'hidden',
			'value' => 'svi_add_address'
		));
		$form->addField(array(
			'field' => 'customer_id',
			'type' => 'hidden',
			'value' => $sv_customer->customer_id
		));
		$return = filter_input(INPUT_GET, 'return', FILTER_SANITIZE_URL);
		$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_URL);
		if($return) {
			$form->addField(array(
				'field' => 'return',
				'type' => 'hidden',
				'value' => $return
			));
			if($type) {
				$form->addField(array(
					'field' => 'type',
					'type' => 'hidden',
					'value' => $type
				));
			}
		}

		$form->addButton(array(
			'label' => 'Add Address',
			'fa-icon' => 'fa-arrow-right'
		));
		$form->setNonceName('svi_add_address_nonce');
		$form->buildForm();
?>
</div>
		<?php
	}
	public function action_add_address() {
		$post = $_POST;
		$action = 'add_address';
		if(isset($post['_wpnonce']) && wp_verify_nonce($post['_wpnonce'],'svi_add_address_nonce')) {
			// Form validation
			$error = SermonView_Integration_Form::validate($post,$this->fields_edit_address());

			if(sizeof($error) == 0) {
				// submit to SV API to create address
				$result = $this->api->create_address($post);
				if(!$result->success) {
					$error = $result->error;
				}
				// if errors, tell user
				if($error) {
					$message = $error;
				} else {
					// success message and return to main account page
					$action = 'addresses';
					$message = 'The address was successfully added.';
				}

			} else {
				$error['top_message'] = 'Please correct the errors below:';
				$message = $error;
			}
		} else {
			$message = 'Invalid security token. This may be caused by the page sitting open for a while before continuing.';
		}
		// if added during checkout, return to checkout
		if($post['return'] == 'checkout') {
			$vars = 'action=process_address&aID=' . $result->address->address_id;
			if ($post['type'] == 'billing') {
				$vars .= '&type=billing';
			}
			$redirect = $this->svi->cart->page_url('checkout',$vars);
			wp_redirect($redirect);
		} else {
			SermonView_Integration_Form::custom_redirect($post,$message,$step,$action);
		}
	}
	public function edit_address() {
		$address_id = filter_input(INPUT_GET, 'aID', FILTER_SANITIZE_NUMBER_INT);
		$response = $this->svi->api->get_address($address_id);
		$address = $response->address;
		?>
<div class="wrap svi-form-wrapper">
	<?php	echo $this->back_link('addresses'); ?>
	<h2>Edit Address<div class="subhead">Required fields in <strong>bold.</strong></div></h2>
<?php
		if(!empty($_GET['nonce'])) {
			$post = get_transient('svi_transient_post_' . $_GET['nonce']);
			$message = get_transient('svi_transient_message_' . $_GET['nonce']);
			if(!is_array($message)) {
				echo '<div class="validation_message">' . $message . '</div>';
			} elseif(key_exists('top_message',$message)) {
				echo '<div class="validation_message">' . $message['top_message'] . '</div>';
			}
		} else {
			$post = array();
			$message = '';
		}
		// Confirm $address exists
		if(is_object($address)) {
			// build the form
			$form = new SermonView_Integration_Form(esc_url( admin_url('admin-post.php') ));
			$form->setPost($post);
			$form->setMessage($message);
			$form->addFields($this->fields_edit_address($address));
			$form->addField(array(
				'field' => 'action',
				'type' => 'hidden',
				'value' => 'svi_edit_address'
			));
			$form->addButton(array(
				'label' => 'Update Address',
				'fa-icon' => 'fa-arrow-right'
			));
			$form->setNonceName('svi_edit_address_nonce');
			$form->buildForm();
		} else {
			echo 'An error has occurred. This address could not be loaded. Please contact customer service for assistance.';
		}
?>
</div>
		<?php
	}
	public function action_edit_address() {
		$post = $_POST;
		$action = 'edit_address';
		if(isset($post['_wpnonce']) && wp_verify_nonce($post['_wpnonce'],'svi_edit_address_nonce')) {
			// Form validation
			$error = SermonView_Integration_Form::validate($post,$this->fields_edit_address());

			if(sizeof($error) == 0) {
				// submit to SV API to create user
				$result = $this->api->update_address($post);
				if(!$result->success) {
					$error = $result->error;
				}
				// if errors, tell user
				if($error) {
					$message = $error;
				} else {
					// success message and return to main account page
					$action = 'addresses';
					$message = 'The address was successfully updated.';
				}

			} else {
				$error['top_message'] = 'Please correct the errors below:';
				$message = $error;
			}
		} else {
			$message = 'Invalid security token. This may be caused by the page sitting open for a while before continuing.';
		}
		SermonView_Integration_Form::custom_redirect($post,$message,$step,$action);
	}
	public function delete_address() {
		$address_id = filter_input(INPUT_GET, 'aID', FILTER_SANITIZE_NUMBER_INT);
		$result = $this->api->delete_address($address_id);
		if($result->success) {
			$message = 'Address was successfully deleted.';
		} else {
			$message = $result->error;
		}
		$post = array(
			'_wp_http_referer' =>  get_page_link($this->settings['account_page'])
		);
		$action = 'addresses';
		$step = null;
		SermonView_Integration_Form::custom_redirect($post,$message,$step,$action);
	}
	public function set_default_address() {
		$address_id = filter_input(INPUT_GET, 'aID', FILTER_SANITIZE_NUMBER_INT);
		$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
		$result = $this->api->set_default_address($address_id,$type);
		if($result->success) {
			$message = 'Address was successfully updated.';
		} else {
			$message = $result->error;
		}
		$post = array(
			'_wp_http_referer' =>  get_page_link($this->settings['account_page'])
		);
		$action = 'addresses';
		$step = null;
		SermonView_Integration_Form::custom_redirect($post,$message,$step,$action);
	}
	public function fields_edit_address($address=null) {
		if(is_null($address)) {
			$address = new stdClass();
		}
		$fields = array(
			array(
				'field' => 'address_id',
				'validation' => (property_exists($address,'address_id') ? 'required' : ''),
				'type' => 'hidden',
				'sv_field' => 'address_id',
				'value' => (property_exists($address,'address_id') ? $address->address_id : null)
			),
			array(
				'field' => 'firstname',
				'label' => 'First Name',
				'validation' => 'required',
				'type' => 'text',
				'sv_field' => 'firstname'
			),
			array(
				'field' => 'lastname',
				'label' => 'Last Name',
				'validation' => 'required',
				'type' => 'text',
				'sv_field' => 'lastname'
			),
			array(
				'field' => 'organization',
				'label' => 'Church/Organization',
				'validation' => '',
				'type' => 'text',
				'sv_field' => 'organization'
			),
			array(
				'field' => 'street',
				'label' => 'Address',
				'validation' => 'required',
				'type' => 'text',
				'sv_field' => 'street_address'
			),
			array(
				'field' => 'street2',
				'label' => 'Apt/Suite/Bldg',
				'validation' => '',
				'type' => 'text',
				'sv_field' => 'street_address_2'
			),
			array(
				'field' => 'city',
				'label' => 'City',
				'validation' => 'required',
				'type' => 'text',
				'sv_field' => 'city'
			),
			array(
				'field' => 'state',
				'label' => 'State/Province',
				'validation' => 'required',
				'type' => 'text',
				'sv_field' => 'state'
			),
			array(
				'field' => 'zip',
				'label' => 'Zip/Postal Code',
				'validation' => 'required',
				'type' => 'text',
				'sv_field' => 'zip'
			),
			array(
				'field' => 'country',
				'label' => 'Country',
				'default' => 'United States',
				'validation' => 'required',
				'type' => 'text',
				'sv_field' => 'country'
			),
			array(
				'field' => 'notes',
				'label' => 'Description',
				'validation' => '',
				'type' => 'text',
				'sv_field' => 'note'
			),
			array(
				'label' => 'Set as',
				'validation' => '',
				'type' => 'checkbox',
				'values' => array(
					array(
						'field' => 'primary_address',
						'label' => 'Primary address',
						'value' => 'true',
						'sv_field' => 'is_primary'
					),
					array(
						'field' => 'default_billing',
						'label' => 'Default billing address',
						'value' => 'true',
						'sv_field' => 'is_default_billing'
					),
					array(
						'field' => 'default_shipping',
						'label' => 'Default shipping address',
						'value' => 'true',
						'sv_field' => 'is_default_shipping'
					),
				)
			)
		);
		// set default field value to $sv_customer value
		foreach($fields as &$field) {
			if(key_exists('sv_field',$field) && property_exists($address, $field['sv_field'])) {
				$field['default'] = $address->{$field['sv_field']};
			} elseif(empty($field['default'])) {
				$field['default'] = null;
			}
			if(key_exists('values',$field) && is_array($field['values'])) {
				foreach($field['values'] as &$subfield) {
					if(is_array($subfield)) {
						if(key_exists('sv_field',$subfield) && property_exists($address, $subfield['sv_field'])) {
							$subfield['default'] = $address->{$subfield['sv_field']};
						} else {
							$subfield['default'] = null;
						}
					}
				}
			}
		}
		// deal with variation in field name
		return $fields;
	}
	///////////////////////// Orders //////////////////////////////////
	public function receipt()	{
		$sv_customer = $this->svi->customer;
		$order_id = filter_input(INPUT_GET, 'oID', FILTER_SANITIZE_NUMBER_INT);
		$response = $this->svi->api->get_order($order_id,$sv_customer->customer_id);
		$order = $response->order;
		$receipt = $response->receipt;
		$checkout_success_message = $response->checkout_success_message;
		if($order->oID != $order_id) {
			// order doesn't exist, redirect to account page
			set_transient('svi_account_message', 'Order #' . $order_id . ' could not be found in your account.', 20);
			$redirect = $this->page_url('account');
			wp_redirect($redirect);
		} else {
			require_once('views/account/receipt.php');
		}
	}


	// helpers
	public function back_link($action=null,$label='Back') {
		return '<a href="' . get_page_link($this->settings['account_page']) . ($action ? '?action=' . $action : '') . '" class="svi-back-link">&lt; ' . $label . '</a>';
	}
}
