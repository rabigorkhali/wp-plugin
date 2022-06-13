<?php

/* * ***************************************************

  SermonView Integration Shopping Cart Class
  Larry Witzel
  9/13/19

	Properties and methods for SermonView shopping cart and checkout

  "I try to find common ground with everyone, doing everything I can to save some.
  I do everything to spread the Good News and share in its blessings."
  1 Corinthians 9:22b, 23 NLT2

 * **************************************************** */




class SermonView_Integration_Shopping_Cart {
	public static $settings_name = 'sermonview_cart_settings';
	public $settings;
    public static $cart_shortcode = 'sermonview-cart';
	public static $checkout_shortcode = 'sermonview-checkout';
	public static $product_shortcode = 'sermonview-product';
	public static $catalog_shortcode = 'sermonview-product-list';

	public function __construct($svi) {
		$this->svi = $svi;
		$this->api = $this->svi->api;
	}
	public function run() {
		$this->init();
	}
	private function init() {
		add_option(self::$settings_name); // setup the option, in case it doesn't already exist
		$this->settings = get_option(self::$settings_name); // load the settings

		// Shortcodes
		add_shortcode(self::$cart_shortcode, array(&$this, 'cart'));
		add_shortcode(self::$checkout_shortcode, array(&$this, 'checkout'));
		add_shortcode(self::$product_shortcode, array(&$this, 'product'));
		add_shortcode(self::$catalog_shortcode, array(&$this, 'list'));
		add_shortcode('sermonview-product-add-button', array(&$this, 'product_add_button'));
		add_shortcode('sermonview-event-required-notice', array(&$this, 'display_event_required_notice'));
        
        // load user & cart info
		add_action('init', array(&$this, 'loadUser'));

		// remove wooCommerce ajax calls
		add_action('wp_print_scripts', array(&$this, 'remove_wc_cart_fragments'), 100);

		// enqueue some specific scripts for the shopping cart functions
		add_action('wp_enqueue_scripts', array(&$this,'specific_scripts'));

		// add shopping cart to menu
		add_filter('wp_nav_menu_items', array(&$this, 'add_cart_to_menu'), 10, 2);

		$this->event_required_notice = false;
	}
	public function specific_scripts() {
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_style('wp-jquery-ui-dialog');
		// wp_enqueue_script('jquery-ui-accordion');
	}
    public function loadUser()
    {
        $this->sv_customer = $this->svi->customer;
	}

	/* ADD CART ICON TO MENU */
	public function add_cart_to_menu($menu, $args) {
		if ($this->svi->account->add_nav_menu() && $this->enable_cart_system() && ($args->theme_location == 'primary' || empty($args->theme_location))) {
			$account_ment = '';
			if (is_user_logged_in()) {
				if (count($this->svi->remote_cart->cart->products) > 0) {
					if (empty($this->svi->account->nav_menu_slug()) || $this->svi->account->nav_menu_slug() == 'all_menus' || $this->svi->account->nav_menu_slug() == $args->menu->slug) {
						if($this->svi->account->is_pro_theme()) {
							$account_menu .= '<li class="menu-item menu-item-type-post_type menu-item-object-page svi-menu-cart"><a href="' . $this->page_url('cart') . '" class="x-anchor x-anchor-menu-item"><div class="x-anchor-content"><div class="x-anchor-text"><span class="x-anchor-text-primary" id="svi-menu-cart-icon"><i class="fa fa-shopping-cart fa-2x"></i><span>' . $this->count_cart() . '</span></span></div></div></a>' . "\n";
							$account_menu .= '</li>' . "\n";
						
						} else {
							$account_menu .= '<li class="menu-item menu-item-type-post_type menu-item-object-page svi-menu-cart"><a href="' . $this->page_url('cart') . '"><span id="svi-menu-cart-icon"><i class="fa fa-shopping-cart fa-2x"></i><span>' . $this->count_cart() . '</span></span></a>' . "\n";
							$account_menu .= '</li>' . "\n";
						}
					}
				}
			}
			return $menu . "\n" . $account_menu;
		} else {
			return $menu;
		}
	}

	/* CART FUNCTIONS */
	public function cart() {
		$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_URL);
		if (!is_user_logged_in()) {
			if($action=='add') {
				$this->cart_add_cookie();
			}
			auth_redirect();
		}
		switch($action) {
			default:
				return $this->cart_main();
				break;
			case 'update':
				return $this->cart_update();
				break;
			case 'remove':
				return $this->cart_remove();
				break;
			case 'add':
				return $this->cart_add();
				break;
		}
	}
	private function cart_main() {
        $sv_customer = $this->svi->customer;
		$response = $this->svi->remote_cart;
        $cart = $response->cart;
        ob_start();
        require_once('views/cart/shopping_cart.php');
        return ob_get_clean();
	}
	private function cart_update() {
		$sv_customer = $this->svi->customer;
		$post = $_POST;
		$response = $this->svi->api->update_cart($sv_customer->customer_id,$post['qty']);
		// dd($post);
		wp_redirect($this->page_url('cart'));
	}
	private function cart_remove() {
		$sv_customer = $this->svi->customer;
		$opID = filter_input(INPUT_GET, 'opID', FILTER_SANITIZE_NUMBER_INT);
		$remove = array($opID => 0);
		$response = $this->svi->api->update_cart($sv_customer->customer_id,$remove);
		wp_redirect($this->page_url('cart'));
	}
	private function cart_add() {
		$sv_customer = $this->svi->customer;
		$pID = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
		$qty = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT);
		$eID = filter_input(INPUT_POST, 'event_id', FILTER_SANITIZE_NUMBER_INT);
		$catID = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_NUMBER_INT);
		$return_id = filter_input(INPUT_POST, 'return_id', FILTER_SANITIZE_NUMBER_INT);

		$response = $this->svi->api->add_to_cart($sv_customer->customer_id,$pID,$qty,$eID,$catID,$return_id);
		wp_redirect($this->page_url('cart', ($return_id ? '&return_id=' . $return_id : '') . ($eID ? '&eID=' . $eID : '') . ($catID ? '&catID=' . $catID : '')));
	}
	private function cart_add_cookie() {
		$pID = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
		$qty = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT);
		$eID = filter_input(INPUT_POST, 'event_id', FILTER_SANITIZE_NUMBER_INT);
		$catID = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_NUMBER_INT);
		$return_id = filter_input(INPUT_POST, 'return_id', FILTER_SANITIZE_NUMBER_INT);
		setcookie('svi_cart_add',json_encode(compact('pID','qty','eID','catID','return_id')),time()+60*60*24*30,'/');
	}
	public function cart_add_after_login($sv_customer_id) {
		$svi_cart_add = $_COOKIE['svi_cart_add'];
		$addCartArr = json_decode(stripslashes($svi_cart_add));
		$response = $this->svi->api->add_to_cart(
			$sv_customer_id,
			$addCartArr->pID,
			$addCartArr->qty,
			$addCartArr->eID,
			$addCartArr->catID,
			$addCartArr->return_id
		);

		// send back the redirect page
		return $this->page_url('cart', ($return_id ? '&return_id=' . $return_id : '') . ($eID ? '&eID=' . $eID : '') . ($catID ? '&catID=' . $catID : ''));
	}

	/* CHECKOUT FUNCTIONS */
	public function checkout()
	{
		if (!is_user_logged_in()) {
			auth_redirect();
		}
		$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_URL);
		switch ($action) {
			case 'shipping':
			default:
				return $this->checkout_shipping();
				break;
			case 'process_shipping':
				return $this->checkout_process_shipping();
				break;
			case 'change_address':
				return $this->checkout_address();
				break;
			case 'process_address':
				$this->checkout_process_address();
				break;
			case 'billing':
				return $this->checkout_billing();
				break;
			case 'process_order':
				return $this->checkout_process_order();
				break;
		}
	}
	private function checkout_shipping() {
		$action = 'shipping';
		$sv_customer = $this->svi->customer;
		$response = $this->svi->api->get_cart($sv_customer->customer_id,'shipping');
		if($response->shipping->total_weight == 0) {
			return $this->checkout_billing();
		}
		$cart = $response->cart;
		if($this->count_cart() == 0) {
			wp_redirect($this->page_url('cart'));
		}
		$addresses = $this->svi->api->get_addresses($sv_customer->customer_id);
		$mixed_shipping = $this->check_mixed_shipping();

		ob_start();
		require_once('views/cart/checkout_shipping.php');
		return ob_get_clean();
	}
	private function checkout_process_shipping() {
		$post = $_POST;
		$sv_customer = $this->svi->customer;
		$response = $this->svi->api->get_cart($sv_customer->customer_id,'shipping');
		if(!empty($post['set_default'])) {
			$this->svi->api->set_default_address($response->cart->delivery->address_id,'shipping');
		}
		$error = false;
		if($response->shipping->total_weight > 0) {
			if(!empty($post['shipping_method'])) {
				$shipping_quote = json_decode(rawurldecode($post['shipping_quote_json']));
				if (json_last_error() === JSON_ERROR_NONE) {
					$method_id = explode('_',$post['shipping_method']);
					$no_method_found = true;
					foreach($shipping_quote as $service) {
						if($service->id == $method_id[0]) {
							foreach($service->methods as $method) {
								if($method->id == $method_id[1]) {
									$no_method_found = false;
									$this->svi->api->update_cart_shipping($sv_customer->customer_id,$post['shipping_method'],$method->title,$method->cost, $method->discounted);
								}
							}
						}
					}
					if($no_method_found) {
						$error = 'Our system was not able to update your shopping cart with your selected shipping method. Please contact customer service to complete your order.';
					}
				} else {
					$error = 'Our system was not able to update your shopping cart with your selected shipping method. Please contact customer service to complete your order.';
				}
			} else {
				$error = 'Please select a shipping method.';
			}
		}
		if ($error) {
			$post['_wp_http_referer'] = get_page_link($this->page_url('checkout'));
			SermonView_Integration_Form::custom_redirect($post, $error, null, 'shipping');
		} else {
			wp_redirect($this->page_url('checkout','action=billing'));
		}
	}
	private function checkout_address() {
		$sv_customer = $this->svi->customer;
		$addresses = $this->svi->api->get_addresses($sv_customer->customer_id);
		$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_URL);
		ob_start();
		require_once('views/cart/checkout_address.php');
		return ob_get_clean();
	}
	private function checkout_process_address() {
		$sv_customer = $this->svi->customer;
		$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_URL);
		$aID = filter_input(INPUT_GET, 'aID', FILTER_SANITIZE_URL);
		$response = $this->svi->api->update_cart_address($sv_customer->customer_id,$aID,$type);
		wp_redirect($this->page_url('checkout',($type ? 'action='.$type : '')));
	}
	private function checkout_billing() {
		if (!empty($_GET['nonce'])) {
			$post = get_transient('svi_transient_post_' . $_GET['nonce']);
		} else {
			$post = array();
		}
		$action = 'billing';
		$sv_customer = $this->svi->customer;
		$response = $this->svi->api->get_cart($sv_customer->customer_id, 'billing');
		$cart = $response->cart;
		if($response->shipping->total_weight > 0 && empty($cart->info->shipping_method_id)) { // a shipping method must have previously been selected
			$error = 'Please select a shipping method.';
			$post['_wp_http_referer'] = get_page_link($this->page_url('checkout'));
			SermonView_Integration_Form::custom_redirect($post, $error, null, 'shipping');
		}
		$billing_methods = array();
		foreach($response->billing as $method) {
			if(!$this->settings['disable_payment_' . $method->id]) {
				$billing_methods[] = $method;
			}
		}
		$addresses = $this->svi->api->get_addresses($sv_customer->customer_id);
		$mixed_shipping = $this->check_mixed_shipping();
		$mixed_tax = $this->check_mixed_tax();

		ob_start();
		require_once('views/cart/checkout_billing.php');
		return ob_get_clean();
	}
	private function checkout_process_order() {
		$post = $_POST;
		$sv_customer = $this->svi->customer;
		if (!empty($post['set_default'])) {
			$this->svi->api->set_default_address($this->svi->remote_cart->cart->billing->address_id, 'billing');
		}
		if(empty($post['payment_method'])) {
			$error = 'Please select a payment method';
		}
		if(!$error) {
			$post['customer_id'] = $sv_customer->customer_id;
			$response = $this->svi->api->process_order($post);
			if(!$response->success) {
				if($response->error) {
					$error = $response->error;
				} else {
					$error = 'An error has occurred. Please contact customer service to complete your order.';
				}
			}
		}

		if ($error) {
			$post['_wp_http_referer'] = get_page_link($this->page_url('checkout','action=billing'));
			SermonView_Integration_Form::custom_redirect($post, $error, null, 'billing');
		} else {
			set_transient('svi_checkout_message_' . $response->order->order_id, 'Your order was successfully submitted. Thank you for the opportunity to serve you!', 28800);
			wp_redirect($this->svi->account->page_url('receipt','from=checkout_success&oID='.$response->order->order_id));
		}
	}

	/* PRODUCT CATALOG FUNCTIONS */
	public function product() {
		$product_id = filter_input(INPUT_GET, 'pID', FILTER_SANITIZE_NUMBER_INT);
		$category_id = filter_input(INPUT_GET, 'catID', FILTER_SANITIZE_NUMBER_INT);
		$return_id = filter_input(INPUT_GET, 'return_id', FILTER_SANITIZE_NUMBER_INT);
		if($product_id > 0) {
			$response = $this->svi->api->get_product($product_id,$category_id);
			$product = $response->product;
			$img_src = $response->img_src;
			$category = $response->category;

			if ($category->event_info_ds_number) {
				if (is_user_logged_in()) {
					if ($category->event_info_ds_number) {
						$restrictions = array(
							'image_ds_no' => $category->event_info_ds_number,
							'not_old' => 1
						);
					}
					$event_lookup = $this->svi->api->get_events($restrictions);
					$events = $event_lookup->events;
					if (sizeof($events) > 1) {
						// more than one event -- have we already selected which one to apply?
						$eID = filter_input(INPUT_GET, 'eID', FILTER_SANITIZE_NUMBER_INT);
						foreach ($events as $test_event) {
							if ($test_event->event_id == $eID) {
								$event = $test_event;
							}
						}
					} elseif (sizeof($events) == 1) {
						$event = $events[0];
					}
				}
				require('views/cart/event_required_notice.php');
			}

			ob_start();
			require_once('views/cart/product.php');
			return ob_get_clean();
		} else {
			return 'Product not found.';
		}
	}
	public function list($atts) {
		if (!empty($atts['product_id'])) {
			$products = $this->api->get_products_by_pID($atts['product_id']);
		} elseif (!empty($atts['category_id'])) {
			$products = $this->api->get_products_by_cID($atts['category_id']);
		} else {
			return '[sermonview-product-list] shortcode requires product_id or category_id';
		}

		if(!empty($atts['columns']) && (int)$atts['columns'] > 0 && (int)$atts['columns'] < 7) {
			$columns = (int)$atts['columns'];
		} else {
			$columns = 3;
		}

		$hide_products_arr = array();
		if(!empty($atts['hide_product_id'])) {
			foreach(explode(',',$atts['hide_product_id']) as $product_id) {
				$hide_products_arr[$product_id] = $product_id;
			}
		}
		$category = $products->category;
		$notice = $this->event_required_notice($category,$atts);
		if($notice) {
			require('views/cart/event_required_notice.php');
		}

		ob_start();
		require_once('views/cart/product_list.php');
		return ob_get_clean();

	}
	private function event_lookup($restrictions) {
		if (!empty($this->events)) {
			$events = $this->events;
			$event = $this->event;
		} else {
			$event_lookup = $this->svi->api->get_events($restrictions);
			$events = $event_lookup->events;
			if (sizeof($events) > 1) {
				// more than one event -- have we already selected which one to apply?
				$eID = filter_input(INPUT_GET, 'eID', FILTER_SANITIZE_NUMBER_INT);
				foreach ($events as $test_event) {
					if ($test_event->event_id == $eID) {
						$event = $test_event;
					}
				}
			} elseif (sizeof($events) == 1) {
				$event = $events[0];
			}
			$this->events = $events;
			$this->event = $event;
		}
		$response = new stdClass;
		$response->events = $events;
		$response->event = $event;
		return $response;
	}
	private function event_required_notice($category,$atts) {
		// require_event is not implemented in osC API
		if($this->event_required_notice) {
			return $this->event_required_notice;
		}

		$notice = false;
		if ($category->event_info_ds_number || key_exists('require_event', $atts)) {
			if (is_user_logged_in()) {
				if ($category->event_info_ds_number) {
					$restrictions = array(
						'image_ds_no' => $category->event_info_ds_number,
						'not_old' => 1
					);
				} elseif (key_exists('require_event', $atts)) {
					// not implemented in osC API
					$restrictions = array(
						'event_type_id' => $atts['require_event'],
						'not_old' => 1
					);
				}
				$event_lookup = $this->event_lookup($restrictions);
				$events = $event_lookup->events;
				$event = $event_lookup->event;

				$notice = false;
				if (!sizeof($events)) {
					// no matching events
					$notice = array(
						'message' => $category->event_info_required_message . (!empty($category->event_info_link) && $category->event_info_link != 'none' ? ' <a href="' . $category->event_info_link . '">You may do that here</a>.' : '') . ' If you have already provided your host site info, please confirm that you&rsquo;ve logged in to the correct account, or contact us for additional help.',
						'type' => 'warning',
						'popup' => 'event-required'
					);
				} else {
					if ($event) {
						if(sizeof($events) > 1 || !key_exists('hide_for_single_event',$atts) || $atts['hide_for_single_event'] == false) {
							$notice = array(
								'message' => 'You are ordering for this event: ' . $event->event_description /* . ' (eID ' . $event->event_id . ')' */ . (sizeof($events) > 1 ? '<div class="smallText pull-right" style="opacity: 0.7;"><a href="#" class="select-event-btn">Change Event</a></div>' : ''),
								'type' => 'success',
								'popup' => 'choose-event',
								'popup_msg' => 'Please select the event for which you are ordering:',
								'auto_open' => false
							);
						}
						// remove the cookie that blocks the autopopup if an event hasn't been set, because one is now set, and if one isn't set in the future the user should be alerted
						setcookie('sermonview_selectevent_notice', 'true', 1);
					} else {
						// user needs to pick which event to apply
						$notice = array(
							'message' => 'You have more than one event in your account associated with these products. Please <a href="#" class="select-event-btn">select the event</a> for which you&rsquo;re ordering.',
							'type' => 'alert',
							'popup' => 'choose-event',
							'auto_open' => true
						);
					}
				}
			} else {
				$notice = array(
					'message' => $category->event_info_required_message . ' Please provide your <a href="' . $category->event_info_link . '" target="_blank">event information</a> before continuing, or <a href="' . $this->svi->login->login_url() . '">log in</a> so we can find your event information.',
					'type' => 'warning',
					'popup' => 'event-required'
				);
			}
		}
		$this->event_required_notice = $notice;
		$this->events = $events;
		$this->event = $event;
		return $notice;
	}
	public function display_event_required_notice($atts) {
		if(!is_array($atts) || !key_exists('category_id',$atts)) {
			return '[sermonview-event-required-notice] shortcode requires a category_id';
		}
		if (!empty($atts['category_id'])) {
			if (!empty($this->products[$atts['category_id']])) {
				$products = $this->products[$atts['category_id']];
			} else {
				$products = $this->api->get_products_by_cID($atts['category_id']);
				$this->products[$atts['category_id']] = $products;
			}
			$category = $products->category;
		}
		$notice = $this->event_required_notice($category, $atts);
		if ($notice) {
			ob_start();
			require('views/cart/event_required_notice.php');
			return ob_get_clean();
		}
	}
	public function product_add_button($atts) {
		$enforced_code = '';
		if (!empty($atts['category_id'])) {
			if(!empty($this->products[$atts['category_id']])) {
				$products = $this->products[$atts['category_id']];
			} else {
				$products = $this->api->get_products_by_cID($atts['category_id']);
				$this->products[$atts['category_id']] = $products;
			}
			$category = $products->category;
			$notice = $this->event_required_notice($category,$atts); // this loads $this->events && $this->event, if not previously loaded
			$event = $this->event;
		} else {
			$notice = false;
			$event = false;
		}

		$button = '<form action="' . $this->page_url() . '?action=add" method="post">';
		$button .= '<input type="hidden" name="product_id" value="' . $atts['product_id'] . '" />';
		$button .= '<input type="hidden" name="quantity" value="' . ((int)$atts['quantity'] > 0 ? (int)$atts['quantity'] : 1) . '" />';
		if(!empty($atts['category_id'])) {
			$button .= '<input type="hidden" name="category_id" value="' . $atts['category_id'] . '" />';
		}
		if(!empty($atts['return_id'])) {
			$button .= '<input type="hidden" name="return_id" value="' . $atts['return_id'] . '" />';
		}
		if ($event) {
			$button .= '<input type="hidden" name="event_id" value="' . $event->event_id .'" />';
		}
        if ($notice && $notice['type'] != 'success' && $atts['do_not_enforce_category_restrction'] != 1) {
			// $button .= '<div class="event-info-notice-add-product">' . $notice['message'] . '</div>';
			$button .= '<button disabled="disabled" title="' . strip_tags($notice['message']) . '" class="x-btn white' . (!empty($atts['class']) ? ' ' . $atts['class'] : '') . '"' . (!empty($atts['id']) ? ' id="' . $atts['id'] . '"' : '') . '>';
		} else {
			$button .= '<button class="x-btn' . (!empty($atts['class']) ? ' ' . $atts['class'] : '') . '"' . (!empty($atts['id']) ? ' id="' . $atts['id'] . '"' : '') . '>';
		}
		$button .= (!empty($atts['label']) ? $atts['label'] : 'Buy Now');
		$button .= (!empty($atts['fa_icon']) ? '<i class="fa fa-' . $atts['fa_icon'] . ' icon-right"></i>' : '');
		$button .= '</button>';
		$button .= '</form>';
		return $button;
	}

	// helper functions
    public function page_url($type = 'cart',$vars=null)
    {
        switch ($type) {
            case 'cart':
            default:
				$link = get_page_link($this->settings['cart_page']);
                break;
            case 'checkout':
				$link = get_page_link($this->settings['checkout_page']);
				break;
			case 'product':
				$link = get_page_link($this->settings['product_page']);
				break;
			case 'catalog':
				$link = get_page_link($this->settings['catalog_page']);
				break;
		}
		if($vars) {
			if (strpos($link, '?') === false) {
				$link .= '?' . $vars;
			} else {
				$link .= '&' . trim($vars,'&');
			}
		}
		return $link;
    }
	public function enable_cart_system() {
		return (key_exists('enable_cart',$this->settings) && $this->settings['enable_cart'] == 'true');
	}

	public function count_cart() {
		$count = 0;
		foreach($this->svi->remote_cart->cart->products as $product) {
			if(!$product->automagic) {
				$count++;
			}
		}
		return $count;
	}

	public function check_mixed_shipping() {
		$free = false;
		$paid = false;
		foreach($this->svi->remote_cart->cart->products as $product) {
			if(!$product->automagic) {
				if(empty($product->free_shipping_notice)) {
					$paid = true;
				} else {
					$free = true;
				}
			}
		}
		return ($paid && $free);
	}
	public function check_mixed_tax() {
		$taxed_at_rate = false;
		$taxed_at_zero = false;
		foreach ($this->svi->remote_cart->cart->products as $product) {
			if (!$product->automagic && $product->taxable) {
				if ((float)$product->tax > 0) {
					$taxed_at_rate = true;
				} else {
					$taxed_at_zero = true;
				}
			}
		}
		return ($taxed_at_rate && $taxed_at_zero);
	}

	public function customer_object() {
		return $this->output_object('Customer Object',$this->sv_customer);
	}
	private function output_object($title,$object) {
		$output = '';
		if(!property_exists($this,'expandable_jquery_already_there') || !$this->expandable_jquery_already_there) {
			ob_start();
?>
<style>
	.expandable {max-height: 3em; overflow: hidden; cursor: pointer; transition: max-height 1s;}
</style>
<script>
	jQuery(function($){
		$('.expandable').click(function(){
			var status = $(this).attr('status');
			var icon = $(this).find('i.fa')
			if(status == 'open') {
				$(this).css("max-height","3em");
				icon.removeClass('fa-caret-down').addClass('fa-caret-right');
				$(this).attr('status','closed');
			} else {
				$(this).css("max-height","100%");
				icon.removeClass('fa-caret-right').addClass('fa-caret-down');
				$(this).attr('status','open');
			}
		});
	});
</script>
<?php
			$this->expandable_jquery_already_there = true;
			$output .= ob_get_clean();
		}
		$output .= '<h3>' . $title . '</h3>';
		$output .= '<pre><code style="display: block;"><div class="expandable"><i class="fa fa-caret-right"></i> ' . htmlentities(print_r($object,1)) . '</div></code></pre>';
		return $output;
	}
	function remove_wc_cart_fragments()	{
		// remove WooCommerce ajax script, which resulted in multiple pings to SV API for a single page load
		wp_dequeue_script('wc-cart-fragments');

		return true;
	}
	function setFieldValue($html,$post,$default=array(),$skip=array()) {
		$field_dom = DOMDocument::loadHTML($html);
		$inputs = $field_dom->getElementsByTagName('input');
		foreach ($inputs as $input) {
			if(in_array($input->getAttribute('name'),$skip)) {
				return false;
			} else {
				if (!empty($post[$input->getAttribute('name')])) {
					switch ($input->getAttribute('type')) {
						default:
							$input->setAttribute('value', $post[$input->getAttribute('name')]);
							break;
						case 'radio':
							if ($input->getAttribute('value') == $post[$input->getAttribute('name')]) {
								$input->setAttribute('checked', 'checked');
							}
							break;
						case 'checkbox':
							$input->setAttribute('checked', 'checked');
							break;
					}
				} elseif(key_exists($input->getAttribute('name'),$default)) {
					switch ($input->getAttribute('type')) {
						default:
							$input->setAttribute('value', $default[$input->getAttribute('name')]);
							break;
						case 'radio':
							if ($input->getAttribute('value') == $default[$input->getAttribute('name')]) {
								$input->setAttribute('checked', 'checked');
							}
							break;
						case 'checkbox':
							$input->setAttribute('checked', 'checked');
							break;
					}
				}
			}
		}
		$selects = $field_dom->getElementsByTagName('select');
		foreach ($selects as $select) {
			if (in_array($select->getAttribute('name'), $skip)) {
				return false;
			} else {
				if (!empty($post[$select->getAttribute('name')])) {
					$options = $select->getElementsByTagName('option');
					foreach ($options as $option) {
						if ($option->getAttribute('value') == $post[$select->getAttribute('name')]) {
							$option->setAttribute('selected', 'selected');
						}
					}
				} elseif (key_exists($select->getAttribute('name'), $default)) {
					$options = $select->getElementsByTagName('option');
					foreach ($options as $option) {
						if ($option->getAttribute('value') == $default[$select->getAttribute('name')]) {
							$option->setAttribute('selected', 'selected');
						}
					}
				}
			}
		}
		// return just the HTML fragment, without all the extra HTML wrapper (https://www.php.net/manual/en/domdocument.savehtml.php#85165)
		return preg_replace('/^<!DOCTYPE.+?>/', '', str_replace(array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $field_dom->saveHTML()));
	}
}
?>
