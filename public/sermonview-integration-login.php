<?php

/* * ***************************************************

  SermonView Integration Login Class
  Larry Witzel
  2/15/18

	SermonView master login properties and methods

  "I try to find common ground with everyone, doing everything I can to save some.
  I do everything to spread the Good News and share in its blessings."
  1 Corinthians 9:22b, 23 NLT2

 * **************************************************** */




class SermonView_Integration_Login {
	public static $settings_name = 'sermonview_login_settings';
	public $settings;

	public static $shortcode = 'sermonview-login';

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

		add_action('wp_head', array(&$this, 'add_header_links'));
		add_action('wp_footer', array(&$this, 'add_footer_links'));
		add_action('admin_post_nopriv_svi_check_email', array(&$this, 'action_check_email'));
		add_action('admin_post_svi_check_email', array(&$this, 'action_check_email'));
		add_action('admin_post_nopriv_svi_login', array(&$this, 'action_login'));
		add_action('admin_post_svi_login', array(&$this, 'action_login'));
		add_action('admin_post_nopriv_svi_signup', array(&$this, 'action_signup'));
		add_action('admin_post_svi_signup', array(&$this, 'action_signup'));
		add_action('admin_post_nopriv_svi_lost_password', array(&$this, 'action_lost_password'));
		add_action('admin_post_svi_lost_password', array(&$this, 'action_lost_password'));
		add_action('admin_post_nopriv_svi_reset_password', array(&$this, 'action_reset_password'));
		add_action('admin_post_svi_reset_password', array(&$this, 'action_reset_password'));
//		add_action('admin_post_nopriv_svi_change_password', array(&$this, 'action_change_password'));
		add_action('admin_post_svi_change_password', array(&$this, 'action_change_password'));

		// filters
		if(!empty($this->settings['login_page'])) {
			add_filter('lostpassword_url', array(&$this, 'lost_password_url'),999999);
			add_filter('login_url', array(&$this, 'login_url'),999999);
			add_action('wp_logout', array(&$this, 'redirect_after_logout'),999999);
		}

		// lengthen nonce to 36 - 72 hours, to avoid most nonce expired errors
		add_filter( 'nonce_life', function () { return 72 * HOUR_IN_SECONDS; } );

		// styles
		add_action('wp_enqueue_scripts', array(&$this,'add_styles'));

		// account menu stuff
		add_filter('wp_nav_menu_items', array(&$this,'add_account_menu'),10,2);

		// Shortcode
		add_shortcode(self::$shortcode, array(&$this, 'select_login_form'));
		add_shortcode('sermonview-login-form', array(&$this, 'select_login_form'));
		add_shortcode('sermonview-nologin-vars', array(&$this, 'nologin_vars'));

		// run autologin (checks for login vars in the function)
		add_action('init', array(&$this, 'auto_login'));
	}
	public function auto_login() {
		$sv_id = filter_input(INPUT_GET,'svID',FILTER_SANITIZE_NUMBER_INT);
		$login_hash = filter_input(INPUT_GET,'lh',FILTER_SANITIZE_STRING);
		if(!$sv_id) {
			$sv_id = filter_input(INPUT_GET,'customer_id',FILTER_SANITIZE_NUMBER_INT);
		}
		if(!empty($sv_id) && !empty($login_hash) && !is_user_logged_in()) {
			$sv_customer = $this->api->get_sermonview_customer_by_cid($sv_id);
			if($sv_customer->login_hash == $login_hash) {
				$user = get_user_by('email',$sv_customer->email);
				if($user) {
					$this->signon_user($user,'login',false);
					// redirect to this page without the specific vars
					$this->redirect_without_vars(array('svID','lh','customer_id'));
				}
			}
		}
	}
	public function redirect_without_vars($vars) {
		$parse_url = parse_url($_SERVER['REQUEST_URI']);
		$getArr = explode('&',$parse_url['query']);
		$newQuery = '';
		foreach($getArr as $getVar) {
			$varArr = explode('=',$getVar);
			if(array_search($varArr[0],$vars) === false) {
				$newQuery .= $getVar . '&';
			}
		}
		$redirect = $parse_url['path'] . ($newQuery ?  '?' . trim($newQuery,'&') : '');
		wp_redirect($redirect);
		exit();
	}
	public function nologin_vars($atts) {
		if (is_user_logged_in()) {
			$get_vars = 'svID=' . $this->svi->customer->id . '&lh=' . $this->svi->customer->login_hash;
			if (strpos($atts['href'], '?') !== false) {
				$get_vars = '&' . $get_vars;
			} else {
				$get_vars = '?' . $get_vars;
			}
			return $atts['href'] . $get_vars;
		} else {
			return $atts['href'];
		}
	}
	public function add_styles() {
		wp_enqueue_style('sermonview-integration-public', plugins_url('sermonview-integration-public.css', __FILE__));
		// let's also add some classes based on X Theme settings
		$css = '.highlight-color {color: ' . get_option('x_site_link_color') . ';}.accent-color {color: ' . get_option('x_site_link_color_hover') . ';}.theme-background-color {background-color: ' . get_option('x_button_background_color') . ';}';
		wp_add_inline_style('sermonview-integration-public',$css);
	}
	public function add_header_links() {
		echo '<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">' . "\n";
		// echo '<link rel="stylesheet" href="' . plugins_url('sermonview-integration-public.css', __FILE__) . '">';
	}
	public function add_footer_links() {
		echo '<script type="text/javascript" src="' . plugins_url('sermonview-integration-public.js', __FILE__) . '"></script>';
	}
	// override URLs
	public function login_url() {
		return get_page_link($this->settings['login_page']);
	}
	public function signup_url() {
		return get_page_link($this->settings['login_page']) . '?action=signup';
	}
	public function lost_password_url() {
		return get_page_link($this->settings['login_page']) . '?action=lost-password';
	}
	public function redirect_after_logout() {
		wp_redirect($this->login_url());
		exit();
	}
	public function add_account_menu($menu, $args) {
		if($args->menu->slug != 'main-menu') {
			// dd($args);
		}
		if($this->svi->account->add_nav_menu() && ($args->theme_location == 'primary' || empty($args->theme_location) ) ) { // check setting from login settings page
			$account_menu = '';
			if(empty($this->svi->account->nav_menu_slug()) || $this->svi->account->nav_menu_slug() == 'all_menus' || $this->svi->account->nav_menu_slug() == $args->menu->slug) {
			  if($this->svi->account->is_pro_theme()) {
				if(is_user_logged_in()) {
					$user = wp_get_current_user();
					$account_menu .= '<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-has-children"><a href="' . $this->svi->account->page_url() . '" class="x-anchor x-anchor-menu-item"><div class="x-anchor-content"><div class="x-anchor-text"><span class="x-anchor-text-primary">My Account</span></div><i class="x-anchor-sub-indicator show" data-x-skip-scroll="true" aria-hidden="false" data-x-icon-s="&#xf107;"></i></div></a>' . "\n";
					$account_menu .= '<ul class="sub-menu x-dropdown" data-x-depth="0" data-x-stem data-x-stem-top >' . "\n";
					$account_menu .= "\t" . '<li class="menu-item menu-item-type-custom menu-item-object-custom"><span class="x-anchor x-anchor-menu-item"><div class="x-anchor-content"><div class="x-anchor-text"><span class="x-anchor-text-primary"><strong>' . $user->display_name . '</strong></span></div></div></span></li>' . "\n";
					if ($this->svi->cart->enable_cart_system()) {
						$account_menu .= "\t" . '<li class="menu-item menu-item-type-custom menu-item-object-custom"><a class="x-anchor x-anchor-menu-item" href="' . $this->svi->cart->page_url('cart')  . '"><div class="x-anchor-content"><div class="x-anchor-text"><span class="x-anchor-text-primary">Cart (' . $this->svi->cart->count_cart() . ' items)</span></div><i class="x-anchor-sub-indicator" data-x-skip-scroll="true" aria-hidden="true" data-x-icon-s="&#xf107;" ></i></div></a></li>' . "\n";
					}

					$account_menu .= "\t" . '<li class="menu-item menu-item-type-custom menu-item-object-custom"><a class="x-anchor x-anchor-menu-item" href="' . $this->svi->dashboard->page_url('home') . '"><div class="x-anchor-content"><div class="x-anchor-text"><span class="x-anchor-text-primary">Campaign Dashboard</span></div><i class="x-anchor-sub-indicator" data-x-skip-scroll="true" aria-hidden="true" data-x-icon-s="&#xf107;" ></i></div></a></li>' . "\n";
					$account_menu .= "\t" . '<li class="menu-item menu-item-type-custom menu-item-object-custom"><a class="x-anchor x-anchor-menu-item" href="' . $this->svi->account->page_url() . '"><div class="x-anchor-content"><div class="x-anchor-text"><span class="x-anchor-text-primary">Account Details</span></div><i class="x-anchor-sub-indicator" data-x-skip-scroll="true" aria-hidden="true" data-x-icon-s="&#xf107;" ></i></div></a></li>' . "\n";
					$account_menu .= "\t" . '<li class="menu-item menu-item-type-custom menu-item-object-custom"><a class="x-anchor x-anchor-menu-item" href="' . wp_logout_url() . '"><div class="x-anchor-content"><div class="x-anchor-text"><span class="x-anchor-text-primary">Log Out</span></div><i class="x-anchor-sub-indicator" data-x-skip-scroll="true" aria-hidden="true" data-x-icon-s="&#xf107;" ></i></div></a></li>' . "\n";
					$account_menu .= '</ul>' . "\n";
					$account_menu .= '</li>' . "\n";
				} else {
					$account_menu = '<li class="menu-item menu-item-type-post_type menu-item-object-page"><a href="' . $this->login_url() . '" class="e169-10 x-anchor x-anchor-menu-item"><div class="x-anchor-content"><div class="x-anchor-text"><span class="x-anchor-text-primary">Log In</span></div></div></a></li>' . "\n";
				}
			  } else {
				if(is_user_logged_in()) {
					$user = wp_get_current_user();
					$account_menu .= '<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-has-children"><a href="' . $this->svi->account->page_url() . '"><span>My Account</span></a>' . "\n";
					$account_menu .= '<ul class="sub-menu">' . "\n";
					$account_menu .= "\t" . '<li class="menu-item menu-item-type-post_type menu-item-object-page"><a><span><strong>' . $user->display_name . '</strong></span></a></li>' . "\n";
					if ($this->svi->cart->enable_cart_system()) {
						$account_menu .= "\t" . '<li class="menu-item menu-item-type-post_type menu-item-object-page"><a href="' . $this->svi->cart->page_url('cart')  . '"><span>Cart (' . $this->svi->cart->count_cart() . ' items)</span></a></li>' . "\n";
					}

					$account_menu .= "\t" . '<li class="menu-item menu-item-type-post_type menu-item-object-page"><a href="' . $this->svi->dashboard->page_url('home') . '"><span>Campaign Dashboard</span></a></li>' . "\n";
					$account_menu .= "\t" . '<li class="menu-item menu-item-type-post_type menu-item-object-page"><a href="' . $this->svi->account->page_url() . '"><span>Account Details</span></a></li>' . "\n";
					$account_menu .= "\t" . '<li class="menu-item menu-item-type-post_type menu-item-object-page"><a href="' . wp_logout_url() . '"><span>Log Out</span></a></li>' . "\n";
					$account_menu .= '</ul>' . "\n";
					$account_menu .= '</li>' . "\n";
				} else {
					$account_menu = '<li class="menu-item menu-item-type-post_type menu-item-object-page"><a href="' . $this->login_url() . '"><span>Log In</span></a>' . "\n";
				}
			  }
			}
			return $menu . "\n" . $account_menu;
		} else {
			return $menu;
		}
	}

	// get the right form for the action required
	public function select_login_form($atts) {
		$atts = shortcode_atts( array(
			'headline' => 'true',
			'type' => 'login',
			'initial_message' => '',
			'login_redirect' => null,
			'signup_redirect' => null
		),$atts);
		ob_start();
		$action = filter_input(INPUT_GET,'action',FILTER_SANITIZE_URL);
		switch($action) {
			case 'login':
			default:
				$this->main_login_form($atts);
				break;
			case 'signup':
				$this->signup_form($atts);
				break;
			case 'lost-password':
				$this->lost_password_form($atts);
				break;
			case 'change-password':
				$this->change_password_form($atts);
				break;
		}
		return ob_get_clean();
	}

	/****************************************
	 * Forms
	 */
	// Login form
	public function main_login_form($atts) {
		$step = filter_input(INPUT_GET, 'step', FILTER_SANITIZE_STRING);
?>
<div class="wrap svi-form-wrapper">
<?php
		$headline = '';
		if(!empty($_GET['nonce'])) {
			$post = get_transient('svi_transient_post_' . $_GET['nonce']);
			$validation_message = '<div class="validation_message">' . get_transient('svi_transient_message_' . $_GET['nonce']) . '</div>';
		} else {
			$post = array();
			$validation_message = '';
		}

		switch($step) {
			case 'email':
			default:
				$headline .= 'Log In / Sign Up<div class="subhead">Whether you want to log in, or sign up for an account, start here.</div>';
				$special_message = $atts['initial_message'];
				break;
			case 'pswd':
				$headline .= 'Log In';
				$special_message = 'We found your account for this site. Please sign in.';
				break;
			case 'sv_pswd':
				$headline .= 'Sign Up<div class="subhead">';
				$special_message = 'This website uses SermonView account information for logins. We found an account with this email address at ';
//				print_r($post['sv_customer']);
				if(key_exists('sv_customer', $post) && is_object($post['sv_customer'])) {
					$sites = $post['sv_customer']->other_sites;
					if(is_array($sites) && sizeof($sites > 0)) {
						if(sizeof($sites) > 1) {
							$site_message = '';
							$count = sizeof($sites);
							if($count > 3) $count = 3; // at most, only show the last 3 used
							for($i=0;$i<$count;$i++) {
								$site_message .= (is_object($sites[$i]) && !empty($sites[$i]->url) ? '<a href="http://' . $sites[$i]->url . '" target="_blank">' : '') . $sites[$i]->site . (is_object($sites[$i]) && !empty($sites[$i]->url) ? '</a>' : '') . ($count == 3 && $i == 0 ? ', ' : ($i + 1 != $count ? ' and ' : ''));
							}
						} else {
								$site_message = (is_object($sites[0]) && !empty($sites[0]->url) ? '<a href="http://' . $sites[0]->url . '" target="_blank">' : '') . $sites[0]->site . (is_object($sites[0]) && !empty($sites[0]->url) ? '</a>' : '');
						}
					}
				}
				if(!$site_message) {
					$site_message = '<a href="https://www.sermonview.com/cart/login.php" target="_blank">SermonView.com</a>';
				}
				$special_message .= $site_message;
				$special_message .= '. Please use that password to log in and set up your account on this site. (If you need to reset your password, please go to ' . ($count > 1 ? 'one of those websites' : 'that website') . ' and click the lost password link there.)';
				$headline .= '</div>';
		}

		// display headline if shortcode doesn't disable it
		if($atts['headline'] && $atts['headline'] != 'false') {
			echo '<h2>' . $headline . '</h2>';
		}
		// but always display the special emssage
		echo (isset($special_message) ? '<h2><div class="subhead">' . $special_message . '</div></h2>' : '');

		// any validation message? Put it here
		echo $validation_message;

		// Confirm email address exists
		if(is_array($post) && key_exists('svi_primary_email',$post) && is_email($post['svi_primary_email'])) {
			$email = esc_attr($post['svi_primary_email']);
		} elseif (is_array($post) && key_exists('svi_email', $post) && is_email($post['svi_email'])) {
			$email = esc_attr($post['svi_email']);
		} else {
			// force back to stage 1
			$step = 'email';
		}

		switch($step) {
			default:
			case 'email':
?>
<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
	<div class="form-row">
		<label for="svi_email" class="edge-right left">Email:</label>
		<div class="input-holder">
			<input name="svi_email" value="<?php echo (is_array($post) && key_exists('svi_email',$post) ? esc_attr($post['svi_email']) : ''); ?>" size="30" required />
		</div>
	</div>
	<div class="form-row">
		<label class="left"></label>
		<div class="input-holder">
<?php if($atts['type'] == 'signup') { ?>
			<button class="button"><i class="fa fa-address-card"></i> Sign Up</button>
			<button class="button white right">Log In <i class="fa fa-arrow-right"></i></button>
<?php } else { ?>
			<button class="button">Log In <i class="fa fa-arrow-right"></i></button>
			<button class="button white right"><i class="fa fa-address-card"></i> Sign Up</button>
<?php } ?>
		</div>
	</div>
	<input type="hidden" name="action" value="svi_check_email" />
	<?php wp_nonce_field('svi_email_form_nonce'); ?>
</form>
<?php
				break;
			case 'pswd':
			case 'sv_pswd':
?>
<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
<?php
				if(key_exists('sv_customer', $post) && is_object($post['sv_customer'])) {
?>
	<div class="form-row">
		<label class="edge-right left">SermonView.com User:</label>
		<div class="input-holder">
			<span class="static"><?php echo $post['sv_customer']->fullname; ?></span>
		</div>
	</div>
	<input type="hidden" name="sv_customer" value="<?php echo rawurlencode(json_encode($post['sv_customer'])); ?>" />
<?php
				}
?>
	<div class="form-row">
		<label class="edge-right left">Email:</label>
		<div class="input-holder">
			<span id="svi-visible-email" class="static"><?php echo $email; ?> <a href="<?php echo $this->login_url(); ?>" class="clear-email"><i class="fa fa-times-circle"></i></a></span>
		</div>
	</div>
	<div class="form-row">
		<label for="svi_password" class="edge-right left"><strong>Password:</strong></label>
		<div class="input-holder">
			<input type="password" name="svi_password" class="svi-input-field" autofocus="autofocus" required />
		</div>
	</div>
	<div class="form-row">
		<label class="left"></label>
		<div class="input-holder">
			<button class="button">Login <i class="fa fa-arrow-right"></i></button>
		</div>
	</div>

	<input type="hidden" name="svi_email" value="<?php echo $email; ?>" />
	<input type="hidden" name="action" value="svi_login" />
	<?php if($atts['signup_redirect']) { ?>
		<input type="hidden" name="signup_redirect" value="<?php echo $atts['signup_redirect']; ?>" />
	<?php } ?>
	<?php if($atts['login_redirect']) { ?>
		<input type="hidden" name="login_redirect" value="<?php echo $atts['login_redirect']; ?>" />
	<?php } ?>
	<?php wp_nonce_field('svi_email_form_nonce'); ?>
</form>
<?php
				if($step == 'pswd') {
					// add password recovery link
					echo '<a href="' . add_query_arg(array('nonce'=>$_GET['nonce'],'first_view'=>1), wp_lostpassword_url( get_permalink() )) . '" title="Lost Password">Lost your password?</a>';
					// echo '<a href="' . wp_lostpassword_url( get_permalink() ) . '" title="Lost Password">Lost your password?</a>';
				}
				break;
		}
?>

</div>

<?php
	}
	public function action_check_email() {
		$post = $_POST;
		$step = null;
		// if(isset($post['_wpnonce']) && wp_verify_nonce($post['_wpnonce'],'svi_email_form_nonce')) {
			if(is_email($post['svi_email'])) {
				if(email_exists($post['svi_email'])) {
					$step = 'pswd';
				} else {
						// user not found. Signup or link with SV account? Or was it just an alternate email address?
						$sv_customer = $this->api->get_sermonview_customer($post['svi_email']);
						if($sv_customer->customer_exists && !$sv_customer->prospect_account) {
							if($sv_customer->email != $post['svi_email']) {
								// user used an alternate email address, which is okay. Check for existing WP account for the primary email address
								if(email_exists($sv_customer->email)) {
									$step = 'pswd';
									$message = 'We found an account with ' . $post['svi_email'] . ' as an alternate email address. The primary email address for this account is noted below. (You can switch your primary email address once you log in, by going to My Account.)';
									$post['svi_primary_email'] = $sv_customer->email;
								} else {
									// no WP account, redirect to signup
									$step = null;
									$action = 'signup';
								}
							} else {
								// SV email exists and is the primary email. Is there an existing WP account for this SV customer ID? Perhaps the email address was changed?
								$user_query = new WP_User_Query( array( 'meta_key' => 'sv_customer_id', 'meta_value' => $sv_customer->customer_id ) );
								if($user_query->get_total() > 0) {
									// change the local email address to match the primary email address of the customer account
									$user = $user_query->get_results();
									add_filter('send_email_change_email', '__return_false');
									wp_update_user(array('ID' => $user[0]->ID, 'user_email' => $sv_customer->email));
									// now go to password
									$step = 'pswd';
								} else {
									// redirect to link screen
									$post['sv_customer'] = $sv_customer;
									$step = 'sv_pswd';
								}
							}
						} else {
							// redirect to signup
							$step = null;
							$action = 'signup';
						}
				}
			} else {
				$message = 'Please enter a valid email address.';
			}
		// } else {
		// 	$message = 'Invalid security token. This may be caused by the page sitting open for a while before continuing.';
		// }
		$this->custom_redirect($post,$message,$step,$action);
	}
	public function action_login() {
		$post = $_POST;
		$step = 'pswd';
		$action = '';
		// if(isset($post['_wpnonce']) && wp_verify_nonce($post['_wpnonce'],'svi_email_form_nonce')) {
			if(is_email($post['svi_email'])) {
				if(empty($post['svi_password'])) {
					$message = 'Please enter your password.';
					if(key_exists('sv_customer',$post)) {
						$step = 'sv_pswd';
						$post['sv_customer'] = json_decode(rawurldecode($post['sv_customer']));
					}
				} else {
					// check if user
					$user = get_user_by('email',$post['svi_email']);
					if($user) {
						// check if user is admin, and if so, log them in using the standard WP login system
						if($user->has_cap('edit_posts') || $user->has_cap('gravityforms_view_entries')) {
							if(wp_check_password($post['svi_password'], $user->user_pass, $user->ID )) {
								$creds = array(
									'user_login' => $post['svi_email'],
									'user_password' => $post['svi_password']
								);
								$signon = wp_signon($creds,is_ssl());
								if(is_wp_error($signon)) {
									$message = "Admin user: " . $signon->get_error_message();
									$step = 'email';
								} else {
									wp_redirect(admin_url());
									exit();
								}
							} else {
								$message = "Admin user: incorrect password";
							}
						} else {
							// is user, but not admin, so check SermonView authentication
							$sv_auth = $this->api->authenticate_sermonview($post);
							if(!$sv_auth->authenticated) {
								$message = $sv_auth->message;
								// check for a changed email address
								$sv_id = get_user_meta($user->ID,'sv_customer_id',true);
								$sv_customer = $this->api->get_sermonview_customer_by_cid($sv_id);
								if(!empty($sv_customer->email) && $sv_customer->email != $post['svi_email']) {
									$message .= ' Was your email changed to ' . $sv_customer->email . '?';
									$post['svi_email'] = $sv_customer->email;
									$step = 'email';
								}
							} else {
								$user = get_user_by('email',$post['svi_email']);
								// update local db with SV information, in case it was updated there
								$sv_customer = $this->api->get_sermonview_customer($post['svi_email']);
								$userdata = array(
									'ID' => $user->ID,
									'display_name' => $sv_customer->fullname,
									'nickname' => $sv_customer->fullname,
									'first_name' => $sv_customer->firstname,
									'last_name' => $sv_customer->lastname
								);
								wp_update_user($userdata);

								// log in
								$this->signon_user($user,'login',true,$post['login_redirect']);
							}
						}
					} else {
						// not user, but is the first time a SermonView customer logged in, so check authentication
						$sv_auth = $this->api->authenticate_sermonview($post);
						if(!$sv_auth->authenticated) {
							if($sv_auth->customer_exists) {
								$post['sv_customer'] = json_decode(rawurldecode($post['sv_customer']));
								$message = 'Incorrect SermonView.com password. Please try again, or <a href="https://www.sermonview.com/cart/password_forgotten.php" target="_blank" style="text-decoration: underline;">reset your password</a>.';
								$step = 'sv_pswd';
							} else {
								$message = 'A SermonView.com account for ' . $post['svi_email'] . ' could not be found.';
								$step = 'email';
							}
						} else {
							// create account
							$sv_customer = $this->api->get_sermonview_customer($post['svi_email']);
							$user = $this->create_wp_user($post, $sv_customer);

							// sign in user
							$this->signon_user($user,'signup',true,$post['signup_redirect']);
						}
					}
				}
			} else {
				$message = 'Please enter a valid email address.';
				$step = 'email';
			}
		// } else {
		// 	$message = 'Invalid security token. This may be caused by the page sitting open for a while before continuing.';
		// 	$step = 'email';
		// }
		$this->custom_redirect($post,$message,$step,$action);
	}
	public function custom_redirect($post,$notice=null,$step=null,$action=null) {
		SermonView_Integration_Form::custom_redirect($post,$notice,$step,$action);
	}
	public function create_wp_user($post,$sv_customer) {
		$userdata = array(
			'user_pass' => $post['svi_password'],
			'user_login' => $post['svi_email'],
			'user_email' => $post['svi_email'],
			'display_name' => $sv_customer->fullname,
			'nickname' => $sv_customer->fullname,
			'first_name' => $sv_customer->firstname,
			'last_name' => $sv_customer->lastname
		);
		$user_id = wp_insert_user($userdata);
		$user = get_user_by('id',$user_id);
		add_user_meta($user_id,'sv_customer_id',$sv_customer->customer_id);
		return $user;
	}
	public function signon_user($user,$source='login',$do_redirect=true,$redirect=null) {
		// check for add-to-cart cookie and deal with it
		if (!empty($_COOKIE['svi_cart_add'])) {
			$sv_id = get_user_meta($user->ID, 'sv_customer_id', true);
			$redirect = $this->svi->cart->cart_add_after_login($sv_id);
			setcookie('svi_cart_add', '', time() - 3600,'/');
		}

		// this is a hack from wp-includes/user.php
		// https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/user.php
		$secure_cookie = is_ssl();
		$secure_cookie = apply_filters( 'secure_signon_cookie', $secure_cookie, array() );
		global $auth_secure_cookie; // XXX ugly hack to pass this to wp_authenticate_cookie
		$auth_secure_cookie = $secure_cookie;

		add_filter('authenticate', 'wp_authenticate_cookie', 30, 3);
		wp_set_auth_cookie($user->ID, false, $secure_cookie);
		do_action( 'wp_login', $user->user_login, $user );

		// redirect to correct page
		if(empty($redirect)) {
			if($source == 'login' && !empty($this->settings['login_redirect_page'])) {
				$redirect = get_page_link($this->settings['login_redirect_page']);
			} elseif($source == 'signup' && !empty($this->settings['signup_redirect_page'])) {
				$redirect = get_page_link($this->settings['signup_redirect_page']);
			} else {
				$redirect = site_url();
			}
		}

		if($do_redirect) {
			wp_redirect($redirect);
			exit();
		}
	}

	// signup form
	public function signup_form($atts) {
?>
<div class="wrap svi-form-wrapper">
<?php
		if($atts['headline'] && $atts['headline'] != 'false') {
?>
<h2>Create Account<div class="subhead">Required fields in <strong>bold.</strong></div></h2>
<?php
		}
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
		}
		// Confirm email address exists
		if(is_array($post) && key_exists('svi_email',$post) && is_email($post['svi_email'])) {
			$email = esc_attr($post['svi_email']);

			// build the form
			$form = new SermonView_Integration_Form(esc_url( admin_url('admin-post.php') ));
			$form->setPost($post);
			$form->setMessage($message);
			$form->addField(array(
				'field' => 'svi-visible-email',
				'label' => 'Email',
				'type' => 'static',
				'value' => $email,
				'clear-email-link' => true
			));
			$form->addFields($this->signup_form_fields());
			$form->addField(array(
				'field' => 'svi_email',
				'type' => 'hidden',
				'value' => $email
			));
			$form->addField(array(
				'field' => 'action',
				'type' => 'hidden',
				'value' => 'svi_signup'
			));
			$form->addButton(array(
				'label' => 'Create Account',
				'fa-icon' => 'fa-arrow-right'
			));
			$form->setNonceName('svi_email_form_nonce');
			$form->buildForm();
?>
</div>
<?php
		} else {
			// force back to stage 1
			echo '<div class="validation_message">An error has occurred.</div>';
			echo '<a href="' . wp_login_url() . '" class="button">Try Again</a>';
		}
	}
	public function action_signup() {
		$post = $_POST;
		$action = 'signup';
		// if(isset($post['_wpnonce']) && wp_verify_nonce($post['_wpnonce'],'svi_email_form_nonce')) {
			if(is_email($post['svi_email'])) {
				// Form validation
				$error = SermonView_Integration_Form::validate($post,$this->signup_form_fields());

				if(sizeof($error) == 0) {
					// submit to SV API to create user
					$result = $this->api->create_sermonview_customer($post);
					if($result->customer_id <= 0) {
						$error = $result->message;
					}
					// if errors, tell user
					if($error) {
						$message = $error;
					} else {
						// create local account && sign in
						$sv_customer = $this->api->get_sermonview_customer($post['svi_email']);
						$user = $this->create_wp_user($post, $sv_customer);
						$this->signon_user($user,'signup',true,$post['signup_redirect']);
					}

				} else {
					$error['top_message'] = 'Please correct the errors below:';
					$message = $error;
				}
			} else {
				$message = 'Please enter a valid email address.';
				$action = '';
				$step = 'email';
			}
		// } else {
		// 	$message = 'Invalid security token. This may be caused by the page sitting open for a while before continuing.';
		// 	$action = '';
		// 	$step = 'email';
		// }
		$this->custom_redirect($post,$message,$step,$action);
	}
	public function signup_form_fields() {
		return array(
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
				'field' => 'church',
				'label' => 'Church',
				'validation' => '',
				'type' => 'text',
				'sv_field' => 'church'
			),
			array(
				'type' => 'divider'
			),
			array(
				'field' => 'address',
				'label' => 'Address',
				'validation' => 'required',
				'type' => 'text',
				'sv_field' => 'address'
			),
			array(
				'field' => 'address_2',
				'label' => 'Apt/Suite/Bldg',
				'validation' => '',
				'type' => 'text',
				'sv_field' => 'address2'
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
				'field' => 'phone',
				'label' => 'Mobile Phone',
				'validation' => 'required',
				'type' => 'text',
				'sv_field' => 'telephone'
			),
			array(
				'field' => 'telephone_alt',
				'label' => 'Alternate Phone',
				'validation' => '',
				'type' => 'text',
				'sv_field' => 'telephone_alt'
			),
			array(
				'type' => 'divider'
			),
			array(
				'field' => 'svi_password',
				'label' => 'Password',
				'validation' => 'required|password',
				'type' => 'password',
				'instructions' => 'Must be at least 8 characters, with lower case, upper case and a number or special character.',
				'sv_field' => 'password'
			),
			array(
				'field' => 'svi_password_2',
				'label' => 'Confirm Password',
				'validation' => 'required',
				'type' => 'password',
				'must_match' => 'svi_password',
				'must_match_error_msg' => 'Confirm Password must be the same as Password'
			)
		);
	}

	// lost password form
	public function lost_password_form($atts) {
		$step = filter_input(INPUT_GET, 'step', FILTER_SANITIZE_STRING);
		$message = false;

		if($step == 'reset_password') {
			// confirm that the nonce is valid for a user
			$code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);
			$users = get_users(
					array(
						'meta_key' => 'password_reset_code',
						'meta_value' => $code,
						'number' => 1,
						'count_total' => false
					)
			);
			$user = reset($users);
			if($user) {
				$email = $user->user_email;
			} else {
				$message = 'We couldn\'t find you through the link provided. Please try again.';
				$step = 'email';
			}
		}
?>
<div class="wrap svi-form-wrapper">
<?php
		if($atts['headline'] && $atts['headline'] != 'false') {
			switch($step) {
				case 'email':
				default:
					echo '<h2>Reset Your Password<div class="subhead">Enter your email address, and we\'ll send a password reset link to you.</div></h2>';
					break;
				case 'reset_password':
					echo '<h2>Reset Your Password<div class="subhead">Enter a new password for your account.</div></h2>';
					break;
			}
		}

		if(!empty($_GET['nonce'])) {
			$post = get_transient('svi_transient_post_' . $_GET['nonce']);
			$message = ($message ? $message : ($_GET['first_view'] != 1 ? get_transient('svi_transient_message_' . $_GET['nonce']) : ''));
			// $message = ($message ? $message : '');
		} else {
			$post = array();
		}

		if($message) {
			echo '<div class="validation_message">' . $message . '</div>';
		}

		switch($step) {
			case 'email':
			default:
?>
<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
	<div class="form-row">
		<label for="svi_email" class="edge-right left">Email:</label>
		<div class="input-holder">
			<input name="svi_email" value="<?php echo (is_array($post) && key_exists('svi_email',$post) ? esc_attr($post['svi_email']) : ''); ?>" size="30" autofocus="autofocus" required />
		</div>
	</div>
	<div class="form-row">
		<label class="left"></label>
		<div class="input-holder">
			<button class="button">Continue <i class="fa fa-arrow-right"></i></button>
		</div>
	</div>
	<input type="hidden" name="action" value="svi_lost_password" />
	<?php wp_nonce_field('svi_email_form_nonce'); ?>
</form>
<?php
				break;
			case 'reset_password':
?>
<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
	<div class="form-row">
		<label class="edge-right left">Email:</label>
		<div class="input-holder">
			<span id="svi-email" class="static"><?php echo ($_GET['email'] ? $_GET['email'] : $email); ?></span>
		</div>
	</div>
	<div class="form-row">
		<label for="svi_password" class="edge-right left"><strong>Password:</strong></label>
		<div class="input-holder">
			<input type="password" name="svi_password" value="<?php echo (is_array($post) && key_exists('svi_password',$post) ? esc_attr($post['svi_password']) : ''); ?>" class="svi-input-field" autofocus="autofocus" required />
		</div>
	</div>
	<div class="form-row">
		<label for="svi_password" class="edge-right left"><strong>Confirm Password:</strong></label>
		<div class="input-holder">
			<input type="password" name="svi_password_2" value="<?php echo (is_array($post) && key_exists('svi_password_2',$post) ? esc_attr($post['svi_password_2']) : ''); ?>" class="svi-input-field" required />
		</div>
	</div>
	<div class="form-row">
		<label class="left"></label>
		<div class="input-holder">
			<button class="button">Reset Password & Login <i class="fa fa-arrow-right"></i></button>
		</div>
	</div>

	<input type="hidden" name="svi_email" value="<?php echo $email; ?>" />
	<input type="hidden" name="action" value="svi_reset_password" />
	<?php wp_nonce_field('svi_email_form_nonce'); ?>
</form>
<?php
				break;
		}
?>
</div>
<?php
	}
	public function action_lost_password() {
		$post = $_POST;
		$action = 'lost-password';
		if(isset($post['_wpnonce']) && wp_verify_nonce($post['_wpnonce'],'svi_email_form_nonce')) {
			if(is_email($post['svi_email'])) {
				// lookup SV customer, then use primary email for email_exists() check
				$sv_customer = $this->api->get_sermonview_customer($post['svi_email']);
				if ($sv_customer->customer_exists && !$sv_customer->prospect_account && email_exists($sv_customer->email)) {
					// save a nonce for the customer, then send the code by email
					$user = get_user_by('email', $sv_customer->email);
					$code = wp_create_nonce('password_reset_code_' . $user->ID);
					add_user_meta($user->ID,'password_reset_code',$code);
					$link = add_query_arg(array('code'=>$code,'step'=>'reset_password','email'=>urlencode($post['svi_email'])), wp_lostpassword_url(get_permalink()));
					$link = preg_replace( '|^http://|', 'https://', $link);
					$response = $this->send_password_reset_email($user, $link, $post['svi_email']);
					if($response['error']) {
						$message = $response['error'];
					} else {
						$action = 'login';
						$step = 'email';
						$message = 'An email has been sent with a link to reset your password.';
					}
				} else {
					$message = 'Sorry, an account with that email address can\'t be found.';
				}
			} else {
				$message = 'Please enter a valid email address.';
			}
		} else {
			$message = 'Invalid security token. This may be caused by the page sitting open for a while before continuing.';
		}
		$this->custom_redirect($post,$message,$step,$action);
	}
	public function send_password_reset_email($user,$link,$email=null) {
		$message = 'Hi, ' . $user->display_name . ',<br /><br />' . "\r\n\r\n";
		$message .= 'Someone requested that your password be reset on the ' . get_bloginfo('name') . ' website, for the email address ' . ($email ? $email : $user->user_email) . '. ' . '<br /><br />' . "\r\n\r\n";
		$message .= 'To reset your password, visit the following link, which will expire in 72 hours: ' . '<br /><br />' . "\r\n\r\n";
		$message .= '<a href="' . $link . '">' . $link . '</a>' . '<br /><br />' . "\r\n\r\n";
		$message .= 'If this was a mistake, just ignore this email and nothing will happen.' . '<br /><br />' . "\r\n\r\n";
		$message .= 'God bless,' . '<br /><br />' . "\r\n\r\n";
		$message .= 'The ' . get_bloginfo('name') . ' Team' . '<br /><br />' . "\r\n\r\n";

		$subject = 'Password reset for the ' . get_bloginfo('name') . ' website';

		add_filter('wp_mail_content_type', function($content_type) {
			return 'text/html';
		});

		$mail = wp_mail(($email ? $email : $user->user_email),$subject,$message);

		remove_filter('wp_mail_content_type', function($content_type) {
			return 'text/html';
		});

		if($mail) {
			return array('success'=>true,'error'=>false);
		} else {
			return array('success'=>false,'error'=>'An error occurred and the email could not be sent.');
		}
	}
	public function action_reset_password() {
		$post = $_POST;
		$action = 'lost-password';
		$step = 'reset_password';
		$error = false;

		// form validation
		if(empty($post['svi_password'])) {
			$error = 'Please enter a new password.';
		} else {
			$password_error = SermonView_Integration_Form::is_not_strong_password($post['svi_password']);
			if($password_error) {
				$error = $password_error;
			} elseif($post['svi_password'] != $post['svi_password_2']) {
				$error = 'The passwords don\'t match.';
			}
		}
		if($error) {
			$message = $error;
		} else {
			// change the password
			$result = $this->api->change_sermonview_password($post);
			if($result->customer_id <= 0) {
				$error = $result->message;
			}
			// if errors, tell user
			if($error) {
				$message = $error;
			} else {
				$user = get_user_by('email',$post['svi_email']);
				// change it locally, too, I guess, but don't send any email notification
				add_filter('send_password_change_email', '__return_false');
				$update_user = wp_update_user( array (
						'ID' => $user->ID,
						'user_pass' => $post['svi_password']
					)
				);
				$this->send_password_change_alert_email($user,$result);

				// we're about to signon the user, so hit the authenticateUser function at SermonView so it get's logged correctly
				$this->api->authenticate_sermonview($post);

				// now signon user
				$this->signon_user($user);
				die();
			}
		}

		$this->custom_redirect($post,$message,$step,$action);
	}
	public function send_password_change_alert_email($user,$result) {
		$message = 'Hi, ' . $user->display_name . ',<br /><br />' . "\r\n\r\n";
		$message .= 'You just changed your password on the ' . get_bloginfo('name') . ' website. Your new password will now be used to log in on these sites:' . '<br /><br />' . "\r\n\r\n";
		if(is_array($result->other_sites)) {
			foreach($result->other_sites as $site) {
				if(!empty($site->site)) {
					$message .= '<div style="margin: 0 0 0 3em;">' . $site->site . '</div>' . "\r\n";
				}
			}
		}
		$message .= '<br />' . "\r\n";
		$message .= 'Keep in mind that changing your password on any of these sites will change the password on all of them.' . '<br /><br />' . "\r\n\r\n";
		$message .= 'God bless,' . '<br /><br />' . "\r\n\r\n";
		$message .= 'The ' . get_bloginfo('name') . ' Team' . '<br /><br />' . "\r\n\r\n";

		$subject = 'New password for the ' . get_bloginfo('name') . ' website';

		add_filter('wp_mail_content_type', function($content_type) {
			return 'text/html';
		});

		$mail = wp_mail($user->user_email,$subject,$message);

		remove_filter('wp_mail_content_type', function($content_type) {
			return 'text/html';
		});

		if($mail) {
			return array('success'=>true,'error'=>false);
		} else {
			return array('success'=>false,'error'=>'An error occurred and the email could not be sent.');
		}
	}

	// change password form
	// lost password form
	public function change_password_form($atts) {
		$message = false;

?>
<div class="wrap svi-form-wrapper">
	<?php	echo $this->svi->account->back_link('account'); ?>
<?php
		if($atts['headline'] && $atts['headline'] != 'false') {
?>
	<h2>
		Change Your Password
		<div class="subhead">
			Enter a new password for your account.
		</div>
	</h2>
<?php
		}

		if(!empty($_GET['nonce'])) {
			$post = get_transient('svi_transient_post_' . $_GET['nonce']);
			$message = ($message ? $message : get_transient('svi_transient_message_' . $_GET['nonce']));
		} else {
			$post = array();
		}

		if($message) {
			echo '<div class="validation_message">' . $message . '</div>';
		}

?>
<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
	<div class="form-row">
		<label for="current_password" class="edge-right left"><strong>Current Password:</strong></label>
		<div class="input-holder">
			<input type="password" name="current_password" value="<?php echo (is_array($post) && key_exists('current_password',$post) ? esc_attr($post['current_password']) : ''); ?>" class="svi-input-field" autofocus="autofocus" required />
		</div>
	</div>
	<hr />
	<div class="form-row">
		<label for="new_password" class="edge-right left"><strong>New Password:</strong></label>
		<div class="input-holder">
			<input type="password" name="new_password" value="<?php echo (is_array($post) && key_exists('new_password',$post) ? esc_attr($post['new_password']) : ''); ?>" class="svi-input-field" required />
		</div>
	</div>
	<div class="form-row">
		<label for="confirm_password" class="edge-right left"><strong>Confirm Password:</strong></label>
		<div class="input-holder">
			<input type="password" name="confirm_password" value="<?php echo (is_array($post) && key_exists('confirm_password',$post) ? esc_attr($post['confirm_password']) : ''); ?>" class="svi-input-field" required />
		</div>
	</div>
	<div class="form-row">
		<label class="left"></label>
		<div class="input-holder">
			<button class="button">Change Password <i class="fa fa-arrow-right"></i></button>
		</div>
	</div>

	<input type="hidden" name="action" value="svi_change_password" />
	<?php wp_nonce_field('svi_email_form_nonce'); ?>
</form>
</div>
<?php
	}
	public function action_change_password() {
		$post = $_POST;
		$action = 'change-password';
		$error = false;

		if(isset($post['_wpnonce']) && wp_verify_nonce($post['_wpnonce'],'svi_email_form_nonce')) {
			// form validation
			if(empty($post['current_password'])) {
				$error = 'Please enter your current password.';
			} elseif(empty($post['new_password'])) {
				$error = 'Please enter a new password.';
			} else {
				$password_error = SermonView_Integration_Form::is_not_strong_password($post['new_password']);
				if($password_error) {
					$error = $password_error;
				} elseif($post['new_password'] != $post['confirm_password']) {
					$error = 'The passwords don\'t match.';
				}
			}

			if(!$error) {
				// authenticate current password
				$user = wp_get_current_user();
				$creds = array(
					'email' => $user->user_email,
					'password' => $post['current_password']
				);
				$sv_auth = $this->api->authenticate_sermonview($creds);
				if(!$sv_auth->authenticated) {
					$error = str_replace('password','current password',$sv_auth->message);
				}
			}

			if($error) {
				$message = $error;
			} else {
				// change the password
				$new_creds = array(
					'email' => $user->user_email,
					'password' => $post['new_password']
				);
				$result = $this->api->change_sermonview_password($new_creds);
				if($result->customer_id <= 0) {
					$error = $result->message;
				}
				// if errors, tell user
				if($error) {
					$message = $error;
				} else {
					// change it locally, too, but don't send any email notification
					add_filter('send_password_change_email', '__return_false');
					$update_user = wp_update_user( array (
							'ID' => $user->ID,
							'user_pass' => $post['new_password']
						)
					);
					$this->send_password_change_alert_email($user,$result);

					// redirect to dashboard page
					wp_redirect('dashboard');
					exit();
				}
			}
		} else {
			$message = 'Invalid security token. This may be caused by the page sitting open for a while before continuing.';
		}
		$this->custom_redirect($post,$message,$step,$action);
	}
}
?>
