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




class SermonView_Integration_Dashboard
{
	public static $settings_name = 'sermonview_dashboard_settings';
	public $settings;
	public static $shortcode = 'sermonview-dashboard';

	public function __construct($svi)
	{
		$this->svi = $svi;
		$this->api = $this->svi->api;
	}
	public function run()
	{
		$this->init();
	}
	private function init()
	{
		add_option(self::$settings_name); // setup the option, in case it doesn't already exist
		$this->settings = get_option(self::$settings_name); // load the settings

		// Shortcodes
		add_shortcode(self::$shortcode, array(&$this, 'dashboard'));
		add_shortcode('sermonview_host_sites_table', array(&$this, 'host_sites_table'));
		add_shortcode('sermonview-host-sites-table', array(&$this, 'host_sites_table'));
		add_shortcode('sermonview_campaigns_table', array(&$this, 'campaigns_table'));
		add_shortcode('sermonview-campaigns-table', array(&$this, 'campaigns_table'));
		add_shortcode('sermonview-campaigns-table', array(&$this, 'campaigns_table'));
		add_shortcode('sermonview-customer-object', array(&$this, 'customer_object'));
		add_shortcode('sermonview-events-object', array(&$this, 'events_object'));
		add_shortcode('if', array(&$this, 'conditional_display'));

		// load user & event info
		add_action('init', array(&$this, 'loadUser'));
	}
	public function loadUser()
	{
		$this->sv_customer = $this->svi->customer;
		// restrict to events as set in dashboard settings
		$restrict = array();
		$event_type_id = $this->settings['restrict_to_event_type'];
		if (!empty($event_type_id) && $event_type_id != 'none') {
			$restrict['event_type_id'] = $event_type_id;
		}
		$image_ds_no = $this->settings['restrict_to_image_ds'];
		if (!empty($image_ds_no)) {
			$restrict['image_ds_no'] = $image_ds_no;
		}
		$this->events = $this->api->get_events($restrict);
	}
	public function dashboard()
	{
		$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_URL);
		switch ($action) {
			case 'dashboard':
			default:
				$this->dashboard_main();
				break;
			case 'event':
				$this->dashboard_event();
				break;
			case 'interests':
				$this->dashboard_interests();
				break;
			case 'interest_detail':
				$this->dashboard_interest_detail();
				break;
		}
	}
	public function dashboard_main()
	{
?>
		<div class="wrap svi-form-wrapper svi">
			<h2>Campaign Dashboard</h2>
			<?php echo $this->campaigns_table() ?>
		</div>
	<?php
	}
	public function dashboard_event()
	{
		if (!is_user_logged_in()) {
			auth_redirect();
		}
		$event_id = filter_input(INPUT_GET, 'eID', FILTER_SANITIZE_NUMBER_INT);
		$event = $this->api->get_event($event_id, true);
		$has_prereg = !empty($event->event_info->web_address->value);
		$web_address = 'www.' . ($event->event_info->web_domain->value != 'update' ? $event->event_info->web_domain->value . '/' : '') . $event->event_info->web_address->value . '/';
		$web_permalink = (!empty($event->event_info->rs_event_id->value) ? 'www.' . $event->event_info->web_domain->value . '/M' . $event->event_info->rs_event_id->value . '/' : false);
	?>
		<div class="wrap svi-form-wrapper svi">
			<div class="back-link">
				<a href="<?= $this->page_url('home') ?>">&lt; Back</a>
			</div>
			<h2>Campaign Details</h2>
			<div class="x-column x-sm x-1-2">
				<div class="event-title"><?= $event->title ?></div>
				<em>Start date:</em> <?= date('l, F j, Y', strtotime($event->start_date)) ?><br />
				<em>Event type:</em> <?= $event->event_type ?><br />
				<?php if ($has_prereg) { ?>
					<em>Event website:</em> <a href="https://<?= $web_address ?>" target="_blank"><?= $web_address ?></a><?= ($web_permalink ? '<span class="smallText permalink">(<a href="https://' . $web_permalink . '" target="_blank">Permalink</a>)</span>' : ''); ?>
				<?php } ?>
			</div>
			<div class="x-column x-sm x-1-2 last" style="margin-top: 0.8em;">
				<em>Event ID:</em> <?= $event_id ?><br />
				<em>Event location:</em><br />
				<?= $this->location_block($event) ?>
			</div>
			<?php
			if (!empty($event->interests)) {
			?>
				<hr class="x-clear" />
				<?= $this->interest_metrics_table($event) ?>
				<div class="interest-links">
					<a class="x-btn" href="<?= $this->page_url('system') ?>?action=interests&eID=<?= $event->event_id ?>"><i class="fa fa-search"></i>View Pre-Registrations</a> <a class="x-btn" href="<?= $event->interests->csv_link ?>"><i class="fa fa-file-text-o"></i>Download CSV File</a>
				</div>

			<?php
			}
			?>
		</div>
		<hr class="x-clear" />
	<?php
		//		echo '<pre style="margin-top: 3em;">' . print_r($event,1) . '</pre>';
	}
	public function dashboard_interests()
	{
		if (!is_user_logged_in()) {
			auth_redirect();
		}
		$event_id = filter_input(INPUT_GET, 'eID', FILTER_SANITIZE_NUMBER_INT);
		$event = $this->api->get_event($event_id, true);
	?>
		<div class="wrap svi-form-wrapper svi">
			<div class="back-link">
				<a href="<?= $this->page_url('system') ?>?action=event&eID=<?= $event->event_id ?>">&lt; Back</a>
			</div>
			<h2>Event Pre-Registrations</h2>
			<div style="margin-top: -1em;">
				<?= $this->interest_metrics_table($event) ?>
				<a class="x-btn" href="<?= $event->interests->csv_link ?>"><i class="fa fa-file-text-o"></i>Download CSV File</a>
			</div>
			<div class="spacer">&nbsp;</div>
			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="svi-table interests-table">
				<thead>
					<tr class="x-highlight">
						<td>Name</td>
						<td>Email</td>
						<td>Phone</td>
						<td>Seats</td>
						<td>Children</td>
						<td>&nbsp;</td>
					</tr>
				</thead>
				<tbody>
					<?php
					if (is_object($event->interests->interests) && sizeof($event->interests->interests) > 0) {
						foreach ($event->interests->interests as $interest) {
							echo '<tr>';
							echo '<td>' . $interest->firstname . ' ' . $interest->lastname . '</td>';
							echo '<td>' . $interest->email . '</td>';
							echo '<td>' . $interest->phone . '</td>';
							echo '<td>' . $interest->seats . '</td>';
							echo '<td>' . $interest->children . '</td>';
							echo '<td>' . '<a href="' . $this->page_url('system') . '?action=interest_detail&eID=' . $event->event_id . '&iID=' . $interest->id . '">Details</a>' . '</td>';
							echo '</tr>';
						}
					} else {
						echo '<tr><td colspan="6" class="align-center">No one has pre-registered for this event yet.</td></tr>';
					}
					?>
					<tr>
						<td colspan="6">&nbsp;</td>
					</tr>
				</tbody>
			</table>
		</div>
	<?php
	}
	public function dashboard_interest_detail()
	{
		if (!is_user_logged_in()) {
			auth_redirect();
		}
		$event_id = filter_input(INPUT_GET, 'eID', FILTER_SANITIZE_NUMBER_INT);
		$event = $this->api->get_event($event_id, true);
		$interest_id = filter_input(INPUT_GET, 'iID', FILTER_SANITIZE_NUMBER_INT);
	?>
		<div class="wrap svi-form-wrapper svi">
			<h2>Event Pre-Registration Details</h2>
			<?php
			if (is_object($event->interests->interests) && property_exists($event->interests->interests, $interest_id)) {
				$interest = $event->interests->interests->{$interest_id};
			?>
				<div class="x-column x-sm x-1-3">
					<a href="<?= $this->page_url('system') ?>?action=interests&eID=<?= $event->event_id ?>">&lt; Back</a>
				</div>
				<div class="x-column x-sm x-2-3">
					<div class="interest-details">
						<?php
						foreach ($interest as $key => $value) {
							echo '<span class="label">' . self::interest_field_name($key) . ':</span> ' . $value . '<br />';
						}
						?>
					</div>
				</div>
			<?php
			} else {
				echo 'Registration ID ' . $interest_id . ' not found.';
			}
			?>
		</div>
	<?php
		//		echo '<hr class="x-clear" /><pre style="margin-top: 3em;">' . print_r($event->interests,1) . '</pre>';
	}
	public function interest_metrics_table($event)
	{
		ob_start();
	?>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" class="svi-table metrics-table">
			<thead>
				<tr>
					<td colspan="3" class="x-highlight">
						<div class=" header">Guest Pre-Registrations</div>
					</td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Reservations<div class="metric"><?= (int)$event->interests->reservations ?></div>
					</td>
					<td>Seats<div class="metric"><?= (int)$event->interests->seats ?></div>
					</td>
					<td>Children<div class="metric"><?= (int)$event->interests->children ?></div>
					</td>
				</tr>
			</tbody>
		</table>

	<?php
		return ob_get_clean();
	}
	private static function interest_field_name($field)
	{
		$field_name = array(
			'id' => 'Registration No.',
			'firstname' => 'First Name',
			'lastname' => 'Last Name',
			'date' => 'Registration Date',
			'sms' => 'Requested SMS Messages',
		);
		if (key_exists($field, $field_name)) {
			return $field_name[$field];
		} else {
			return ucfirst($field);
		}
	}
	public function location_block($event)
	{
		$output = '';
		$output .= (!empty($event->event_info->location_name->value) ? $event->event_info->location_name->value . '<br />' : '');
		$output .= (!empty($event->event_info->location_street->value) ? $event->event_info->location_street->value . '<br />' : '');
		$output .= $event->event_info->location_city->value . (!empty($event->event_info->location_city->value) && !empty($event->event_info->location_state->value) ? ', ' : '') . $event->event_info->location_state->value . ' ' . $event->event_info->location_zip->value;
		return $output;
	}
	public function host_sites_table()
	{
		// user must be logged in to view this table
		if (!is_user_logged_in()) {
			auth_redirect();
		}
		$events = $this->events;

		$table_rows = '';
		$missing_event_info = $missing_order = false;
		$missing_event_info_count = $missing_order_count = 0;

		if (is_object($events) && is_array($events->events) && sizeof($events->events) > 0) {
			foreach ($events->events as $event) {
				if (empty($event->event_info->design_no->value)) {
					$missing_event_info = true;
					$missing_event_info_count++;
				} elseif (!is_object($event->orders) || sizeof($event->orders) == 0) {
					$missing_order = true;
					$missing_order_count++;
				}

				$table_rows .= '<tr>';
				$table_rows .= '<td>' . ($event->event_church ? $event->event_church . ' (' : '') . $event->event_city . ', ' . $event->event_state . ($event->event_church ? ')' : '') . '</td>';
				$table_rows .= '<td class="align-center">' . date('M j, Y', strtotime($event->start_date)) . '</td>';
				$table_rows .= '<td class="align-center">' . $event->event_id . '</td>';
				if ($this->settings['restrict_to_event_type'] == 20) { // For IND events
					$table_rows .= '<td class="align-center">' . $this->host_event_info_cell($event) . '</td>';
					$table_rows .= '<td class="align-center">' . $this->host_order_cell($event) . '</td>';
				} else {
					$table_rows .= '<td class="align-center">' . '<i class="fa fa-check-square-o"></i>' . '</td>';
					//				$table_rows .= '<td class="center">' . '<a href="#"><i class="fa fa-chevron-circle-right"></i></a>' . '</td>';
					//				$table_rows .= '<td class="center">' . '<i class="fa fa-square-o"></i>' . '</td>';
					$table_rows .= '<td class="align-center">' . $this->host_event_info_cell($event) . '</td>';
					//				$table_rows .= '<td class="align-center"><div class="small-text">Requires<br />Event Details</div></td>';
					$table_rows .= '<td class="align-center">' . $this->host_order_cell($event) . '</td>';
				}
				$table_rows .= '<td class="align-right">' . '<a href="' . $this->page_url('system') . '?action=event&eID=' . $event->event_id . '" class="x-btn x-btn-mini">Snapshot</a>' . '</td>';
				$table_rows .= '</tr>';
				//				$table_rows .= '<tr><td colspan="6">' . str_replace('[products]','["products"]',print_r($event->orders,1)) . '</td></tr>'; // debugger
			}
			$table_rows .= '<tr><td colspan="7">&nbsp;</td></tr>';
		} else {
			$table_rows .= '<tr><td colspan="7">You do not have any ' . $this->campaign_label(2) . ' in your account.</td></tr>';
		}

		ob_start();

		if (!empty($missing_event_info)) {
			$missing_message = 'We are missing event details for ' . ($missing_event_info_count == 1 ? 'one' : 'some') . ' of your sites. Please click the Go button below to provide your event details.';
		} elseif ($missing_order) {
			$missing_message = 'We are missing orders for ' . ($missing_order_count == 1 ? 'one' : 'some') . ' of your sites. Please click the Order button below to order your event resources.';
		}
		if (!empty($missing_message)) {
			echo '<div class="missing-alert x-alert-danger x-alert"><i class="fa fa-warning"></i> <strong>Action Required:</strong> ' . $missing_message . '</div>';
		}
	?>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" class="svi-table">
			<thead>
				<tr>
					<td colspan="7" class="x-highlight">
						<div class="mas header"><?php echo $this->campaign_label(is_object($events) && is_array($events->events) ? count($events->events) : 0); ?></div>
					</td>
				</tr>
				<tr class="cell-header">
					<td>Location</td>
					<td class="align-center">Start Date</td>
					<td class="align-center">Event ID</td>
					<?php
					if ($this->settings['restrict_to_event_type'] == 20) { // For IND events
					?>
						<td class="align-center">Event<br />Details</td>
						<td class="align-center">Order<br />Complete</td>
					<?php } else { ?>
						<td class="align-center">Registered</td>
						<td class="align-center">Event<br />Details</td>
						<td class="align-center">Order<br />Resources</td>
					<?php } ?>
					<td class="align-right"></td>
				</tr>
			</thead>
			<tbody>
				<?php
				echo $table_rows;
				?>
			</tbody>
		</table>
		<?php
		if (!empty($this->settings['addl_signup_target']) && $this->settings['addl_signup_target'] != 'none') {
			echo '<div class="center"><a class="x-btn" href="' . get_page_link($this->settings['addl_signup_target']) . '">' . $this->settings['addl_signup_button_label'] . '</a></div>';
		}
		//		print_r($events);
		return ob_get_clean();
	}
	private function host_event_info_cell($event)
	{
		if (empty($event->event_info->design_no->value)) {
			return '<a href="' . get_page_link($this->settings['event_info_form_target']) . '?event_id=' . $event->event_id . '" class="x-btn x-btn-mini">Go <i class="fa fa-arrow-right"></i></a>';
		} else {
			return '<i class="fa fa-check-square-o"></i>';
		}
	}
	private function host_order_cell($event)
	{
		if (empty($event->event_info->design_no->value)) {
			return '<i class="fa fa-ban resources-icon missing-event-info" title="Please provide your event details first"></i>';
		} elseif (!is_object($event->orders) || sizeof($event->orders) == 0) {
			if ($event->is_old) {
				return '<i class="fa fa-ban resources-icon missing-event-info" title="Ordering is not available for past events"></i>';
			} else {
				if ($this->settings['restrict_to_event_type'] == 20) { // For IND events
					return '<a href="' . (substr($this->settings['order_link'], 0, 4) != 'http' ? 'http://' : '') . $this->settings['order_link'] . (strpos($this->settings['order_link'], '?') !== false ? '&' : '?') . 'eID=' . $event->event_id . '" class="x-btn x-btn-mini" target="_blank">Order</a>';
				} else {
					return '<a href="' . (substr($this->settings['order_link'], 0, 4) != 'http' ? 'http://' : '') . $this->settings['order_link'] . (strpos($this->settings['order_link'], '?') !== false ? '&' : '?') . 'eID=' . $event->event_id . '&customer_id=' . $this->svi->customer->customer_id . '&svID=' . $this->svi->customer->customer_id . '&lh=' . $this->svi->customer->login_hash . '" class="x-btn x-btn-mini" target="_blank">Order &nbsp;<i class="fa fa-external-link"></i></a>';
				}
			}
		} else {
			return '<i class="fa fa-check-square-o' . ($event->is_old ? ' resources-icon missing-event-info" title="Additional ordering is not available for past events"' : '') . '"></i>' . (!$event->is_old ? '<br /><a href="' . (substr($this->settings['order_link'], 0, 4) != 'http' ? 'http://' : '') . $this->settings['order_link'] . (strpos($this->settings['order_link'], '?') !== false ? '&' : '?') . 'eID=' . $event->event_id . '&customer_id=' . $this->svi->customer->customer_id . '&svID=' . $this->svi->customer->customer_id . '&lh=' . $this->svi->customer->login_hash . '" class="small-text" target="_blank">New Order &nbsp;<i class="fa fa-external-link"></i></a>' : '');
		}
	}
	public function campaigns_table()
	{
		// user must be logged in to view this table
		if (!is_user_logged_in()) {
			auth_redirect();
		}

		$events = $this->events;
		ob_start();
		?>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" class="svi-table">
			<thead>
				<tr>
					<td colspan="5" class="x-highlight">
						<div class="mas header"><?php echo $this->campaign_label(is_object($events) && is_array($events->events) ? sizeof($events->events) : 0); ?></div>
					</td>
				</tr>
				<tr class="cell-header">
					<td>Event</td>
					<td>Location</td>
					<td class="align-center">Start Date</td>
					<td class="align-center">Event ID</td>
					<td class="align-right">&nbsp;</td>
				</tr>
			</thead>
			<tbody>
				<?php
				$first_legacy = true;
				if (is_object($events) && is_array($events->events) && sizeof($events->events) > 0) {
					foreach ($events->events as $event) {
						if (empty($event->title) && empty($event->event_church) && empty($event->event_city) && empty($event->event_state)) {
							if ($first_legacy) {
								$first_legacy = false;
								echo '<tr class="cell-header"><td colspan="5">Only limited information is available for the following events</td></tr>';
							}
							echo '<tr>';
							echo '<td colspan="2">' . $event->event_title . '</td>';
							echo '<td class="align-center">' . date('M j, Y', strtotime($event->start_date)) . '</td>';
							echo '<td class="align-center">' . $event->event_id . '</td>';
							echo '<td>&nbsp;</td>';
						} else {
							echo '<tr>';
							echo '<td>' . ($event->title ? $event->title : $event->event_type) . '</td>';
							echo '<td>' . ($event->event_church ? $event->event_church . (strpos(strtolower($event->event_church), 'church') === false ? ' Church' : '') . ' (' : '') . $event->event_city . ', ' . $event->event_state . ($event->event_church ? ')' : '') . '</td>';
							echo '<td class="align-center">' . date('M j, Y', strtotime($event->start_date)) . '</td>';
							echo '<td class="align-center">' . $event->event_id . '</td>';
							echo '<td class="align-right" nowrap="nowrap">' . (!empty($event->event_info->rs_event_id->value) ? '<a href="' . $this->page_url('system') . '?action=interests&eID=' . $event->event_id . '"><i class="fa fa-group light-gray"></i></a> &nbsp;' : '') . '<a href="' . $this->page_url('system') . '?action=event&eID=' . $event->event_id . '" class="x-btn x-btn-mini white x-btn-pill">Snapshot</a>' . '</td>';
						}
						echo '</tr>';
					}
					echo '<tr><td colspan="5">&nbsp;</td></tr>';
				} else {
					echo '<tr><td colspan="5">You do not have any ' . $this->campaign_label() . ' in your account.</td></tr>';
				}
				?>
			</tbody>
		</table>
		<?php
		if (!empty($this->settings['addl_signup_target']) && $this->settings['addl_signup_target'] != 'none') {
			echo '<div class="center"><a class="x-btn" href="' . get_page_link($this->settings['addl_signup_target']) . '">' . $this->settings['addl_signup_button_label'] . '</a></div>';
		}
		//		echo '<pre>' . print_r($events,1) . '</pre>';
		return ob_get_clean();
	}
	public function page_url($type = 'home')
	{
		switch ($type) {
			case 'home':
			default:
				if (!empty($this->settings['dashboard_home_page'])) {
					return get_page_link($this->settings['dashboard_home_page']);
				} else {
					// so legacy sites don't break
					return get_page_link($this->settings['dashboard_page']);
				}
				break;
			case 'system':
				return get_page_link($this->settings['dashboard_page']);
				break;
		}
	}
	public function campaign_label($campaign_count = 0)
	{
		if ($campaign_count == 1) {
			return (!empty($this->settings['campaign_header_label']) ? $this->settings['campaign_header_label'] : 'Campaign');
		} else {
			return (!empty($this->settings['campaign_header_label_plural']) ? $this->settings['campaign_header_label_plural'] : 'Campaigns');
		}
	}
	public function conditional_display($atts, $content)
	{
		foreach ($atts as $key => $value) {
			/* normalize empty attributes */
			if (is_int($key)) {
				$key = $value;
				$value = true;
			}

			$reverse_logic = false;
			if (substr($key, 0, 4) == 'not_') {
				$reverse_logic = true;
				$key = substr($key, 4);
			}

			// the conditional tag parameters
			$values = (true === $value) ? null : array_filter(explode(',', $value));

			// check the condition
			$result = call_user_func(array(&$this, 'if_' . $key), $values);

			if (!isset($result))
				return '';
			if ($result !== $reverse_logic) {
				return do_shortcode($content);
			}
		}

		return '';
	}
	public function if_has_event()
	{
		$override = $this->check_override(__FUNCTION__);
		if ($override != 'not_set') {
			return $override;
		} else {
			return (!is_array($this->events->events) ? false : (bool)sizeof($this->events->events));
		}
	}
	public function if_event_missing_info()
	{
		$override = $this->check_override(__FUNCTION__);
		if ($override != 'not_set') {
			return $override;
		} else {
			if (is_object($this->events) && is_array($this->events->events)) {
				foreach ($this->events->events as $event) {
					if (!$event->event_info->design_no->value) {
						return true;
					}
				}
			}
		}
		return false;
	}
	public function if_event_missing_order()
	{
		$override = $this->check_override(__FUNCTION__);
		if ($override != 'not_set') {
			return $override;
		} else {
			if (is_object($this->events) && is_array($this->events->events)) {
				foreach ($this->events->events as $event) {
					if (!is_array($event->orders) || sizeof($event->orders) == 0) {
						return true;
					}
				}
			}
		}
		return false;
	}
	public function if_no_orders()
	{
		$override = $this->check_override(__FUNCTION__);
		if ($override != 'not_set') {
			return $override;
		} else {
			if (is_object($this->events) && is_array($this->events->events)) {
				foreach ($this->events->events as $event) {
					if (is_array($event->orders) && sizeof($event->orders) > 0) {
						return false;
					}
				}
			}
		}
		return true;
	}
	public function if_has_one_event()
	{
		$override = $this->check_override(__FUNCTION__);
		if ($override != 'not_set') {
			return $override;
		} else {
			return (!is_array($this->events) ? false : (bool)(sizeof($this->events->events) == 1));
		}
	}
	public function if_has_multiple_events()
	{
		$override = $this->check_override(__FUNCTION__);
		if ($override != 'not_set') {
			return $override;
		} else {
			return (!is_array($this->events->events) ? false : (bool)(sizeof($this->events->events) >= 2));
		}
	}
	public function if_has_event_type($values)
	{
		$override = $this->check_override(__FUNCTION__);
		if ($override != 'not_set') {
			return $override;
		} else {
			if (!is_array($this->events->events)) {
				return false;
			} else {
				foreach ($this->events->events as $event) {
					if (in_array($event->event_type_id, $values)) {
						return true;
					}
				}
				return false;
			}
		}
	}
	public function if_has_event_ds_no($values)
	{
		$override = $this->check_override(__FUNCTION__);
		if ($override != 'not_set') {
			return $override;
		} else {
			if (!is_array($this->events->events)) {
				return false;
			} else {
				foreach ($this->events->events as $event) {
					if (in_array($event->event_info->design_no->value, $values)) {
						return true;
					}
				}
				return false;
			}
		}
	}
	private function check_override($var)
	{
		// check $_GET override
		$get_override = filter_input(INPUT_GET, substr($var, 3), FILTER_SANITIZE_NUMBER_INT);
		$not_get_override = filter_input(INPUT_GET, 'not_' . substr($var, 3), FILTER_SANITIZE_NUMBER_INT);
		if ($get_override === '0' || $get_override === '1') return (bool)$get_override;
		if ($not_get_override === '0' || $not_get_override === '1') return !(bool)$get_override;

		// nothing? return 'not_set'
		return 'not_set';
	}
	public function customer_object()
	{
		return $this->output_object('Customer Object', $this->sv_customer);
	}
	public function events_object()
	{
		$output = $this->output_object('Events Object', $this->events);
		$output .= '<pre><code style="display: block;"><div class="expandable"><i class="fa fa-caret-right"></i> ' . htmlentities(print_r($this->events->events, 1)) . '</div></code></pre>';
		return $output;
	}
	private function output_object($title, $object)
	{
		$output = '';
		if (!property_exists($this, 'expandable_jquery_already_there') || !$this->expandable_jquery_already_there) {
			ob_start();
		?>
			<style>
				.expandable {
					max-height: 3em;
					overflow: hidden;
					cursor: pointer;
					transition: max-height 1s;
				}
			</style>
			<script>
				jQuery(function($) {
					$('.expandable').click(function() {
						var status = $(this).attr('status');
						var icon = $(this).find('i.fa')
						if (status == 'open') {
							$(this).css("max-height", "3em");
							icon.removeClass('fa-caret-down').addClass('fa-caret-right');
							$(this).attr('status', 'closed');
						} else {
							$(this).css("max-height", "100%");
							icon.removeClass('fa-caret-right').addClass('fa-caret-down');
							$(this).attr('status', 'open');
						}
					});
				});
			</script>
<?php
			$this->expandable_jquery_already_there = true;
			$output .= ob_get_clean();
		}
		$output .= '<h3>' . $title . '</h3>';
		$output .= '<pre><code style="display: block;"><div class="expandable"><i class="fa fa-caret-right"></i> ' . htmlentities(print_r($object, 1)) . '</div></code></pre>';
		return $output;
	}
}
?>