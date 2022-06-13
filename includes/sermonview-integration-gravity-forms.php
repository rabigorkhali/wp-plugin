<?php

GFForms::include_feed_addon_framework();

/**
 * Gravity Forms SermonView API 2.0 Add-On.
 *
 * Note: field value overrides can be specified in the override_field_value() method, beginning around line 1000
 *
 * "And now, all glory to God, who is able, through his mighty power at work within us,
 *  to do infinitely more than we might ask or think."
 *																									Ephesians 3:20 NLT2
 *
 */
class SermonView_Integration_Gravity_Forms extends GFFeedAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  3.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the SermonView Add-On.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string $_version Contains the version, defined from sermonview.php
	 */
	protected $_version = SERMONVIEW_INTEGRATION_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '1.9.12';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformssermonview';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformssermonview/sermonview.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms SermonView Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'SermonView';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capabilities needed for the SermonView Add-On
	 *
	 * @since  3.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_sermonview', 'gravityforms_sermonview_uninstall' );

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_sermonview';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_sermonview';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_sermonview_uninstall';

	/**
	 * Defines the SermonView list field tag name.
	 *
	 * @since  3.7
	 * @access protected
	 * @var    string $merge_var_name The SermonView list field tag name; used by gform_sermonview_field_value.
	 */
	protected $merge_var_name = '';

	/**
	 * Contains an instance of the SermonView API library, if available.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    object $api If available, contains an instance of the SermonView API library.
	 */
	private $api = null;

	/**
	 * Get an instance of this class.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @return SermonView_Integration_Gravity_Forms
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Autoload the required libraries.
	 *
	 * @since  4.0
	 * @access public
	 *
	 * @uses GFAddOn::is_gravityforms_supported()
	 */
	public function pre_init() {

		parent::pre_init();

//		if ( $this->is_gravityforms_supported() ) {
//
//			// Load the SermonView API library.
//			if ( ! class_exists( 'GF_SermonView_API' ) ) {
//				require_once( 'includes/class-gf-sermonview-api.php' );
//			}
//
//		}

	}

	/**
	 * Plugin starting point. Handles hooks, loading of language files and PayPal delayed payment support.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @uses GFFeedAddOn::add_delayed_payment_support()
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Send data to SermonView only when payment is received.', 'gravityformssermonview' ),
			)
		);

		// process this feed when entry is updated by admin, too
		add_action('gform_after_update_entry',array(&$this,'send_to_sermonview_on_update'),10,2);

		// add override field value filter
		add_filter( 'gform_sermonview_field_value', array(&$this,'override_field_value'), 10, 4 );

		// Now some additional code to add the user role to the body tag classes
		if (is_user_logged_in()) {
			add_filter('body_class', array(&$this, 'add_role_to_body'));
			add_filter('admin_body_class', array(&$this, 'add_role_to_body'));
		}
	}

	public function loadIntegrations($svi) {
		$this->svi = $svi;
		$this->api = $this->svi->api;
		if (is_user_logged_in()) {
			$this->sv_customer = $svi->customer;
			add_filter('gform_field_value', array(&$this, 'sv_customer_data'), 10, 3);
		}
		// now grab the event info, if query string includes event_id
		$event_id = filter_input(INPUT_GET, 'event_id', FILTER_SANITIZE_NUMBER_INT);
		if (!$event_id) {
			$entry_id = filter_input(INPUT_GET, 'entry_id', FILTER_SANITIZE_NUMBER_INT);
			if ($entry_id) {
				$entry = GFAPI::get_entry($entry_id);
				if (!is_wp_error($entry)) {
					$form = GFAPI::get_form($entry['form_id']);
					if (!is_wp_error($form)) {
						foreach ($form['fields'] as $field) {
							if ($field->label == 'SV Event ID') {
								$event_id = $entry[$field->id];
								break;
							}
						}
					}
				}
			}
		}
		$this->initialize_api();
		if ($event_id && is_object($this->api)) {
			$this->sv_event = $this->api->get_event($event_id);
			// add hooks for dynamically populating any field with select $event data
			add_filter('gform_field_value', array(&$this, 'sv_event_data'), 10, 3);
		}
		// should we load the db-driven override values into a local object? If so, do it now - TODO
	}

	/**
	 * Remove unneeded settings.
	 *
	 * @since  4.0
	 * @access public
	 */
	public function uninstall() {

		// don't do anything
		return;

		// never made it here, but kept for a record
		parent::uninstall();

		GFCache::delete( 'sermonview_plugin_settings' );
		delete_option( 'gf_sermonview_settings' );
		delete_option( 'gf_sermonview_version' );

	}

	public function install() {
		// create log table when plugin is installed
		global $wpdb;
		$table_name = $wpdb->prefix . self::$log_file_name;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			date datetime NOT NULL,
			command varchar(256) NOT NULL,
			submission text,
			response text NOT NULL,
			result varchar(256) NOT NULL,
			error varchar(256) DEFAULT NULL,
			PRIMARY KEY (`id`)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

	}

	/**
	 * Register needed styles.
	 *
	 * @since  4.0
	 * @access public
	 *
	 * @return array
	 */
	public function styles() {

		$styles = array(
			array(
				'handle'  => $this->_slug . '_form_settings',
				'src'     => $this->get_base_url() . '/css/form_settings.css',
				'version' => $this->_version,
				'enqueue' => array( 'admin_page' => array( 'form_settings' ) ),
			),
			array(
				'handle'  => 'sermonview-connector',
				'src'     => $this->get_base_url() . '/css/sermonview-connector.css',
				'version' => $this->_version,
				'enqueue' => array( 'admin_page' => array( 'gf_settings' ) ),
			),
		);

		return array_merge( parent::styles(), $styles );

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		$this->initialize_api();

		return array(
			array(
				'title' => 'SermonView API 2.0 Add-On',
				'description' => '<p>' . '<div id="svi-connector-status"><span class="status-label">API Connection Status: </span><span class="status-icon">' . $this->api->connectionStatusIcon() . '</span></div>' .
					'The API settings are now set in the <a href="admin.php?page=sermonview-integration-api">SermonView plugin</a>. The Update Settings button does nothing, and neither does Uninstall Add-On.'
					. '</p>',
				'fields'      => array(
//					array(
//						'name'              => 'api_user',
//						'label'             => 'API User',
//						'type'              => 'text',
//						'class'             => 'medium',
////						'feedback_callback' => array( $this, 'initialize_api' ),
//					),
//					array(
//						'name'              => 'api_key',
//						'label'             => 'API Key',
//						'type'              => 'text',
//						'class'             => 'medium',
////						'feedback_callback' => array( $this, 'initialize_api' ),
//					),
//					array(
//						'name'    => 'which_server',
//						'label'   => 'Server to Access',
//						'tooltip' => 'Which SermonView server API should be accessed?',
//						'type'    => 'radio',
//						'choices' => array(
//							array(
//								'label' => 'Development',
//								'name'	=> 'dev',
//								'value' => 'dev'
//							),
//							array(
//								'label' => 'Production',
//								'name'	=> 'live',
//								'value' => 'live'
//							),
//						),
//					),
				),
			),
		);

	}





	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the feed edit page.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		return array(
			array(
				'title'  => esc_html__( 'SermonView API 2.0 Feed Settings', 'gravityformssermonview' ),
				'fields' => array(
					array(
						'name'     => 'feedName',
						'label'    => esc_html__( 'Name', 'gravityformssermonview' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'gravityformssermonview' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformssermonview' )
						),
					),
					array(
						'name'     => 'actionList',
						'label'    => esc_html__( 'API Action', 'gravityformssermonview' ),
						'type'     => 'action_list',
						'required' => true,
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'API Action', 'gravityformssermonview' ),
							esc_html__( 'Select the SermonView API 2.0 action you would like to feed.', 'gravityformssermonview' )
						),
					),
				),
			),
			array(
				'dependency' => 'actionList',
				'fields'     => array(
					array(
						'name'      => 'mappedFields',
						'label'     => esc_html__( 'Map Fields', 'gravityformssermonview' ),
						'type'      => 'field_map',
						'field_map' => $this->merge_vars_field_map(),
						'tooltip'   => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Map Fields', 'gravityformssermonview' ),
							esc_html__( 'Associate your SermonView merge tags to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'gravityformssermonview' )
						),
					),
				),
			),
			array(
				'dependency' => 'actionList',
				'fields'     => array(
					array(
						'name'      => 'responseFields',
						'label'     => esc_html__( 'Response Fields', 'gravityformssermonview' ),
						'type'      => 'field_map',
						'field_map' => $this->response_vars_field_map(),
						'tooltip'   => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Response Fields', 'gravityformssermonview' ),
							esc_html__( 'Associate the data returned by the SermonView API with Gravity Form fields that will be updated with the response data.', 'gravityformssermonview' )
						),
					),
					array( 'type' => 'save' ),
				),
			),
		);

	}

	/**
	 * Define the markup for the action_list type field.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param array $field The field properties.
	 * @param bool  $echo  Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string
	 */
	public function settings_action_list( $field, $echo = true ) {

		// Initialize HTML string.
		$html = '';

		// If API is not initialized, return.
		if ( ! $this->initialize_api() ) {
			return $html;
		}


		try {

			// Log contact lists request parameters.
			$this->log_debug( __METHOD__ . '(): Retrieving API actions');

			// Get lists.
			$lists = $this->api->getActions(false);

		} catch ( Exception $e ) {

			// Log that contact lists could not be obtained.
			$this->log_error( __METHOD__ . '(): Could not retrieve SermonView API actions; ' . $e->getMessage() );

			// Display error message.
			printf( esc_html__( 'Could not load SermonView API actions. %sError: %s', 'gravityformssermonview' ), '<br/>', $e->getMessage() );

			return;

		}

		// If no lists were found, display error message.
		if ( 0 === $lists['total_actions'] ) {

			// Log that no lists were found.
			$this->log_error( __METHOD__ . '(): Could not load SermonView API actions; no actions found.' );

			// Display error message.
			printf( esc_html__( 'Could not load SermonView API actions. %sError: %s', 'gravityformssermonview' ), '<br/>', esc_html__( 'No actions found.', 'gravityformssermonview' ) );

			return;

		}

		// Log number of lists retrieved.
		$this->log_debug( __METHOD__ . '(): Number of actions: ' . count( $lists['actions'] ) );

		// Initialize select options.
		$options = array(
			array(
				'label' => esc_html__( 'Select an API action', 'gravityformssermonview' ),
				'value' => '',
			),
		);

		// Loop through list of actions.
		foreach ( $lists['actions'] as $list ) {

			// Add list to select options.
			$options[] = array(
				'label' => esc_html( $list['name'] ),
				'value' => esc_attr( $list['id'] ),
			);

		}

		// Add select field properties.
		$field['type']     = 'select';
		$field['choices']  = $options;
		$field['onchange'] = 'jQuery(this).parents("form").submit();';

		// Generate select field.
		$html = $this->settings_select( $field, false );

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Return an array of SermonView list fields which can be mapped to the Form fields/entry meta.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @return array
	 */
	public function merge_vars_field_map() {

		// Initialize field map array.
		$field_map = array();

		// If unable to initialize API, return field map.
		if ( ! $this->initialize_api() ) {
			return $field_map;
		}

		// Get current list ID.
		$list_id = $this->get_setting( 'actionList', 'kit_request' );

		try {

			// Get merge fields.
			$merge_fields = $this->api->getMergeFields( $list_id );

		} catch ( Exception $e ) {

			// Log error.
			$this->log_error( __METHOD__ . '(): Unable to get merge fields for SermonView action; ' . $e->getMessage() );

			return $field_map;

		}
		$this->log_debug( __METHOD__ . '(): Retrieved ' . count($merge_fields['merge_fields']) . ' merge fields for ' . $list_id);

		// If merge fields exist, add to field map.
		if ( ! empty( $merge_fields['merge_fields'] ) ) {

			// Loop through merge fields.
			foreach ( $merge_fields['merge_fields'] as $merge_field ) {

				// Define required field type.
				$field_type = null;

				// If this is an address merge field, set field type to "address".
				if ( 'address' === $merge_field['type'] ) {
					$field_type = array( 'address' );
				}

				// Add to field map.
				$field_map[ $merge_field['name'] ] = array(
					'name'       => $merge_field['name'],
					'label'      => $merge_field['label'],
					'required'   => $merge_field['required'],
					'field_type' => $field_type,
				);

			}

		}

		return $field_map;
	}

	public function response_vars_field_map() {
		// Initialize field map array.
		$field_map = array();

		// If unable to initialize API, return field map.
		if ( ! $this->initialize_api() ) {
			return $field_map;
		}

		// Get current list ID.
		$list_id = $this->get_setting( 'actionList', 'kit_request' );

		try {

			// Get merge fields.
			$merge_fields = $this->api->getResponseFields( $list_id );

		} catch ( Exception $e ) {

			// Log error.
			$this->log_error( __METHOD__ . '(): Unable to get response fields for SermonView action; ' . $e->getMessage() );

			return $field_map;

		}
		$this->log_debug( __METHOD__ . '(): Retrieved ' . count($merge_fields['merge_fields']) . ' response fields for ' . $list_id);

		// If merge fields exist, add to field map.
		if ( ! empty( $merge_fields['merge_fields'] ) ) {

			// Loop through merge fields.
			foreach ( $merge_fields['merge_fields'] as $merge_field ) {

				// Define required field type.
				$field_type = null;

				// If this is an address merge field, set field type to "address".
				if ( 'address' === $merge_field['type'] ) {
					$field_type = array( 'address' );
				}

				// Add to field map.
				$field_map[ $merge_field['name'] ] = array(
					'name'       => $merge_field['name'],
					'label'      => $merge_field['label'],
					'required'   => $merge_field['required'],
					'field_type' => $field_type,
				);

			}

		}

		return $field_map;

	}

	/**
	 * Prevent feeds being listed or created if the API key isn't valid.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->initialize_api();

	}

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feedName'            => esc_html__( 'Name', 'gravityformssermonview' ),
			'action_list_name' => esc_html__( 'API Action', 'gravityformssermonview' ),
		);

	}

	/**
	 * Returns the value to be displayed in the SermonView List column.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_action_list_name( $feed ) {

		// If unable to initialize API, return the list ID.
		if ( ! $this->initialize_api() ) {
			return rgars( $feed, 'meta/actionList' );
		}

		try {

			// Get list.
			$list = $this->api->getActions( rgars( $feed, 'meta/actionList' ) );

			// Return list name.
			return rgar( $list, 'name' );

		} catch ( Exception $e ) {

			// Log error.
			$this->log_error( __METHOD__ . '(): Unable to get SermonView action name for feed list; ' . $e->getMessage() );

			// Return list ID.
			return rgars( $feed, 'meta/actionList' );

		}

	}




	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Process the feed, subscribe the user to the list.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param array $feed  The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form  The form object currently being processed.
	 *
	 * @return array
	 */
	public function process_feed( $feed, $entry, $form ) {

		// Log that we are processing feed.
		$this->log_debug( __METHOD__ . '(): Processing feed.' );
//		$this->log_debug('$form: ' . print_r($form,1) );
//		$this->log_debug('$entry: ' . print_r($entry,1) );

		// If unable to initialize API, log error and return.
		if ( ! $this->initialize_api() ) {
			$this->add_feed_error( esc_html__( 'Unable to process feed because API could not be initialized.', 'gravityformssermonview' ), $feed, $entry, $form );
			return $entry;
		}

		// Initialize array to store merge vars.
		$merge_vars = array();

		// Get field map values.
		$field_map = $this->get_field_map_fields( $feed, 'mappedFields' );

		// Loop through field map.
		foreach ( $field_map as $name => $field_id ) {

			// Set merge var name to current field map name.
			$this->merge_var_name = $name;

			// Get field object.
			$field = GFFormsModel::get_field( $form, $field_id );

			// Get field value.
			$field_value = $this->get_field_value( $form, $entry, $field_id );

			$merge_vars[ $name ] = $field_value;

		}
		$action_id = rgars( $feed, 'meta/actionList' );

		try {

			// Log the subscriber to be added or updated.
			$this->log_debug( __METHOD__ . "(): Submitting data to API: " . print_r( $merge_vars, true ) );

			// Send the feed to SermonView.
			$response = $raw_response = $this->api->submitFeed( $action_id, $merge_vars);

			// Update with response data
			$response = json_decode($response['result']);
			if(is_object($response) && $response->success) {
				$this->log_debug( __METHOD__ . "(): Received response data from server {$action_id}. " . print_r($response,1));
				// Get field map values.
				$field_map = $this->get_field_map_fields( $feed, 'responseFields' );

				// Loop through field map
				foreach($field_map as $name => $field_id) {

					// update the field with response data
					if($field_id > 0) {
						GFAPI::update_entry_field($entry['id'],$field_id,$response->{$name});
					}
				}
			} else {
				if(!is_object($response)) {
					$this->log_error( __METHOD__ . "(): No response data was received from server ({$action_id}): " . print_r($raw_response));
				} else {
					$this->log_error( __METHOD__ . "(): ({$action_id}) Server responded with an error message: " . (is_array($response->message) ? implode(' | ',$response->message) : $response->message));
				}
			}

			// Log that the subscription was added or updated.
			$this->log_debug( __METHOD__ . "(): Data successfully submitted successfully {$action_id}." );

			// now trigger any notifications that require response data from SV API
			$updated_entry = GFAPI::get_entry($entry['id']);
			GFAPI::send_notifications( $form, $updated_entry, 'feed_processed' );


		} catch ( Exception $e ) {

			// Log that subscription could not be added or updated.
			$this->add_feed_error( sprintf( esc_html__( 'Unable to submit data to SermonView API: %s', 'gravityformssermonview' ), $e->getMessage() ), $feed, $entry, $form );

			// Log field errors.
			if ( $e->getErrors() ) {
				$this->log_error( __METHOD__ . '(): Field errors when attempting subscription: ' . print_r( $e->getErrors(), true ) );
			}

			return;

		}
	}

	/**
	 * Returns the value of the selected field.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param array  $form     The form object currently being processed.
	 * @param array  $entry    The entry object currently being processed.
	 * @param string $field_id The ID of the field being processed.
	 *
	 * @uses GFAddOn::get_full_name()
	 * @uses GF_Field::get_value_export()
	 * @uses GFFormsModel::get_field()
	 * @uses GFFormsModel::get_input_type()
	 * @uses SermonView_Integration_Gravity_Forms::get_full_address()
	 * @uses SermonView_Integration_Gravity_Forms::maybe_override_field_value()
	 *
	 * @return array
	 */
	public function get_field_value( $form, $entry, $field_id ) {
		// Set initial field value.
		$field_value = '';

		// Set field value based on field ID.
		switch ( strtolower( $field_id ) ) {

			// Form title.
			case 'form_title':
				$field_value = rgar( $form, 'title' );
				break;

			// Entry creation date.
			case 'date_created':

				// Get entry creation date from entry.
				$date_created = rgar( $entry, strtolower( $field_id ) );

				// If date is not populated, get current date.
				$field_value = empty( $date_created ) ? gmdate( 'Y-m-d H:i:s' ) : $date_created;
				break;

			// Entry IP and source URL.
			case 'ip':
			case 'source_url':
				$field_value = rgar( $entry, strtolower( $field_id ) );
				break;

			default:
				// Get field object.
				$field = GFFormsModel::get_field( $form, $field_id );

				if ( is_object( $field ) ) {

					// Check if field ID is integer to ensure field does not have child inputs.
					$is_integer = $field_id == intval( $field_id );

					// Get field input type.
					$input_type = GFFormsModel::get_input_type( $field );

					if ( $is_integer && 'address' === $input_type ) {

						// Get full address for field value.
						$field_value = $this->get_full_address( $entry, $field_id );

					} else if ( $is_integer && 'name' === $input_type ) {

						// Get full name for field value.
						$field_value = $this->get_full_name( $entry, $field_id );

					} else if ( $is_integer && 'checkbox' === $input_type ) {

						// Initialize selected options array.
						$selected = array();

						// Loop through checkbox inputs.
						foreach ( $field->inputs as $input ) {
							$index = (string) $input['id'];
							if ( ! rgempty( $index, $entry ) ) {
								$selected[] = $this->maybe_override_field_value( rgar( $entry, $index ), $form, $entry, $index );
							}
						}

						// Convert selected options array to comma separated string.
						$field_value = implode( ', ', $selected );

					} else if ( 'phone' === $input_type && $field->phoneFormat == 'standard' ) {

						// Get field value.
						$field_value = rgar( $entry, $field_id );

						// Reformat standard format phone to match SermonView format.
						// Format: NPA-NXX-LINE (404-555-1212) when US/CAN.
						if ( ! empty( $field_value ) && preg_match( '/^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/', $field_value, $matches ) ) {
							$field_value = sprintf( '%s-%s-%s', $matches[1], $matches[2], $matches[3] );
						}

					} else {

						// Use export value if method exists for field.
						if ( is_callable( array( 'GF_Field', 'get_value_export' ) ) ) {
							$field_value = $field->get_value_export( $entry, $field_id );
						} else {
							$field_value = rgar( $entry, $field_id );
						}

					}

				} else {

					// Get field value from entry.
					$field_value = rgar( $entry, $field_id );

				}

		}

		return $this->maybe_override_field_value( $field_value, $form, $entry, $field_id );

	}

	/**
	 * Use the legacy gform_sermonview_field_value filter instead of the framework gform_SLUG_field_value filter.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param string $field_value The field value.
	 * @param array  $form        The form object currently being processed.
	 * @param array  $entry       The entry object currently being processed.
	 * @param string $field_id    The ID of the field being processed.
	 *
	 * @return string
	 */
	public function maybe_override_field_value( $field_value, $form, $entry, $field_id ) {

		return gf_apply_filters( 'gform_sermonview_field_value', array( $form['id'], $field_id ), $field_value, $form['id'], $field_id, $entry, $this->merge_var_name );

	}

	public function override_field_value($value, $form, $field_id, $entry) {
		$parse_url = parse_url(site_url());
		$domain = $parse_url['host'];

		$this->log_debug( __METHOD__ . '(): maybe_override_field_value() $field_id ' . $field_id . ' $form_id . ' . $form . ' $domain ' . $domain);

		// Indestructible and Forecasting Hope event info form
		if (
			$domain == 'www.indestructibleyou.com'							&&	$form == '2'		 ||
			$domain == 'indestructibleyou.com'								&&	$form == '2'		 ||
			$domain == 'www.indestructibleyou.org'							&&	$form == '2'		 ||
			$domain == 'indestructibleyou.org'								&&	$form == '2'		 ||

			$domain == 'www.forecastinghope.com'							&&	$form == '2'		 ||
			$domain == 'forecastinghope.com'								&&	$form == '2'		 ||
			$domain == 'www.forecastinghope.org'							&&	$form == '2'		 ||
			$domain == 'forecastinghope.org'								&&	$form == '2'
		) {
			switch ($field_id) {
				case '255':
					if(empty($value)) {
						$feedArr = array(
							'9' => 'America/Halifax',
							'1' => 'America/New_York',
							'2' => 'America/Chicago',
							'3' => 'America/Denver',
							'4' => 'America/Los_Angeles',
							'5' => 'America/Anchorage',
							'6' => 'Pacific/Honolulu'
						);
						$which_feed = rgar($entry, '246');
						$local_timezone = rgar($entry,'249');

						$start_time = new DateTime('7:00 pm.', new DateTimeZone($feedArr[$which_feed]));
						$start_time->setTimezone(new DateTimeZone($local_timezone));
						$new_value = $start_time->format('g:i a');

						// and update the GF entry with this value
						GFAPI::update_entry_field($entry['id'], $field_id, $new_value);
					}
					break;
				
				case '256':
					if (empty($value)) {
						$feedArr = array(
							'9' => '3:00 p.m. Pacific',
							'1' => '4:00 p.m. Pacific',
							'2' => '5:00 p.m. Pacific',
							'3' => '6:00 p.m. Pacific',
							'4' => '7:00 p.m. Pacific',
							'5' => '8:00 p.m. Pacific',
							'6' => '9:00 p.m. Pacific'
						);
						$which_feed = rgar($entry, '246');
						$new_value = $feedArr[$which_feed];

						// and update the GF entry with this value
						GFAPI::update_entry_field($entry['id'], $field_id, $new_value);
					}
					break;
			}
		}
		// Final Empire event info form
		if (
			$domain == 'www.finalempire.com'							&&	$form == '3'		 ||
			$domain == 'finalempire.com'								&&	$form == '3'		 ||
			$domain == 'www.finalempire.org'							&&	$form == '3'		 ||
			$domain == 'finalempire.org'								&&	$form == '3'
		) {
			switch ($field_id) {
				case '149':
				case '150':
				case '151':
				case '167':
					// do this only if the field is actually empty
					if (empty($value)) {
						// set up array of correct dates
						$scheduleArr = array(
							'1' => array( // Preferred Schedule
								'149' => '2020-01-23',
								'150' => '2020-01-24',
								'151' => '2020-01-25',
								'167' => '2020-01-25'
							),
							'2' => array( // Alternate Schedule 1
								'149' => '2020-01-23',
								'150' => '2020-01-24',
								'151' => '2020-01-25',
								'167' => '2020-01-26'
							),
						);

						// look up selected schedule and set the dates
						$schedule = rgar($entry, '135');
						$new_value = $scheduleArr[$schedule][$field_id];
					}
					break;
			}
		}
		// The Appearing event info form
		if (
			$domain == 'dev.evangelismmarketing.com'	&&	$form == '27'		 ||
			$domain == 'www.appearing.org'						&&	$form == '3'		 ||
			$domain == 'appearing.org'								&&	$form == '3'		 ||
			$domain == 'www.appearing.org'						&&	$form == '5'		 ||
			$domain == 'appearing.org'								&&	$form == '5'
		) {
			switch ($field_id) {
				case '149':
				case '150':
				case '151':
				case '167':
				case '168':
					// do this only if the field is actually empty
					if (empty($value)) {
						// set up array of correct dates
						$scheduleArr = array(
							'1' => array( // Preferred Schedule
								'149' => '2018-10-11',
								'150' => '2018-10-12',
								'151' => '2018-10-13',
								'167' => '2018-10-13',
								'168' => '2018-10-14'
							),
							'2' => array( // Alternate Schedule 1
								'149' => '2018-10-11',
								'150' => '2018-10-12',
								'151' => '2018-10-13',
								'167' => '2018-10-14',
								'168' => '2018-10-15'
							),
							'3' => array( // Alternate Schedule 2
								'149' => '2018-10-12',
								'150' => '2018-10-13',
								'151' => '2018-10-14',
								'167' => '2018-10-15',
								'168' => '2018-10-16'
							),
						);

						// look up selected schedule and set the dates
						$schedule = rgar($entry, '135');
						$new_value = $scheduleArr[$schedule][$field_id];
					}
					break;
			}
		}

		// The Appearing host registration form
		if(
					$domain == 'dev.evangelismmarketing.com'	&&	$form == '25'		 ||
					$domain == 'www.appearing.org'						&&	$form == '2'		 ||
					$domain == 'appearing.org'								&&	$form == '2'
		) {
			switch($field_id) {
				// Church name -- no longer needed, user enters the church name in a text field
//				case '148':
//					if(empty($value)) {
//						// church names are in dropdowns by conference, with field ID 76, and field IDs 78-134. Look for the church name there.
//						for($conf=76;$conf<=134;$conf++) {
//							// skip 77, it's not a church name list
//							if($conf == 77) {continue;}
//							$new_value = rgar( $entry, $conf );
//							if($new_value) {break;}
//						}
//						$new_value = fixSda($new_value);
//					} else {
//						$value = fixSda($value);
//					}
//					break;
			}
		}

		if($new_value) {
			$this->log_debug( __METHOD__ . '(): $field_id ' . $field_id . ' value set to "' . $new_value . '"');
			return $new_value;
		} else {
			return $value;
		}
	}




	// # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * Initializes SermonView API if credentials are valid.
	 *
	 */
	public function initialize_api() {
		// duplication of code, but apparently necessary - LW 6/19/18


		// If API is alredy initialized, return true.
		if ( ! is_null( $this->api ) ) {
			return true;
		}

		// Log validation step.
		$this->log_debug( __METHOD__ . '(): Validating API Info.' );

		// don't know how to pass the already instantiated API object to this object, so I guess create a new one
		$api = new SermonView_Integration_API();
		$api->run();

		try {

			// Retrieve account information.
			$status = $api->getStatus();
			$result = json_decode($status['result']);
			$result = $api->objectifyResult($result);
			if(!$result->success) {
				throw new Exception ($result->message);
			}

			// Assign API library to class.
			$this->api = $api;

			// Log that authentication test passed.
			$this->log_debug( __METHOD__ . '(): SermonView API successfully authenticated.' );

			return true;

		} catch ( Exception $e ) {

			// Log that authentication test failed.
			$this->log_error( __METHOD__ . '(): Unable to authenticate with SermonView; '. $e->getMessage() );

			return false;

		}

	}
	private function connectionStatusIcon() {
		$result = $this->apiConnected();
		if(is_object($result) && $result->success) {
			if(strstr($result->server,'dev')) {
				return '<i class="fa fa-toggle-on status-dev"></i><div class="connection-type">Dev Server</div>';
			} else {
				return '<i class="fa fa-toggle-on status-on"></i>';
			}
		} else {
			return '<i class="fa fa-toggle-on fa-flip-horizontal status-off"></i><div class="connection-type status-off">Disconnected</div>';
		}
	}
	private function apiConnected() {
		if( $this->initialize_api() ) {
			$status = $this->api->getStatus();
			$result = json_decode($status['result']);
			$result = $this->api->objectifyResult($result);
			return $result;
		} else {
			return false;
		}
	}


	/**
	 * Returns the combined value of the specified Address field.
	 * Street 2 and Country are the only inputs not required by SermonView.
	 * If other inputs are missing SermonView will not store the field value, we will pass a hyphen when an input is empty.
	 * SermonView requires the inputs be delimited by 2 spaces.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param array  $entry    The entry currently being processed.
	 * @param string $field_id The ID of the field to retrieve the value for.
	 *
	 * @return array|null
	 */
	public function get_full_address( $entry, $field_id ) {

		// Initialize address array.
		$address = array(
			'addr1'   => str_replace( '  ', ' ', trim( rgar( $entry, $field_id . '.1' ) ) ),
			'addr2'   => str_replace( '  ', ' ', trim( rgar( $entry, $field_id . '.2' ) ) ),
			'city'    => str_replace( '  ', ' ', trim( rgar( $entry, $field_id . '.3' ) ) ),
			'state'   => str_replace( '  ', ' ', trim( rgar( $entry, $field_id . '.4' ) ) ),
			'zip'     => trim( rgar( $entry, $field_id . '.5' ) ),
			'country' => trim( rgar( $entry, $field_id . '.6' ) ),
		);

		// Get address parts.
		$address_parts = array_values( $address );

		// Remove empty address parts.
		$address_parts = array_filter( $address_parts );

		// If no address parts exist, return null.
		if ( empty( $address_parts ) ) {
			return null;
		}

		// Replace country with country code.
		if ( ! empty( $address['country'] ) ) {
			$address['country'] = GF_Fields::get( 'address' )->get_country_code( $address['country'] );
		}

		return $address;

	}

	public function send_to_sermonview_on_update($form,$entry_id) {
		if(function_exists('gf_sermonview')) {
			$entry = GFAPI::get_entry($entry_id);
			gf_sermonview()->maybe_process_feed($entry,$form);
		}
	}
	public function sv_customer_data($value,$field,$name) {
		$values = array(
			'sv_customer_id' => $this->sv_customer->customer_id,
			'sv_customer_firstname' => $this->sv_customer->firstname,
			'sv_customer_lastname' => $this->sv_customer->lastname,
			'sv_customer_fullname' => $this->sv_customer->fullname,
			'sv_customer_email' => $this->sv_customer->email,
			'sv_customer_organization' => $this->sv_customer->organization,
			'sv_customer_church' => $this->sv_customer->organization,
			'sv_customer_street_address' => $this->sv_customer->street_address,
			'sv_customer_street_address_2' => $this->sv_customer->street_address_2,
			'sv_customer_city' => $this->sv_customer->city,
			'sv_customer_state' => $this->sv_customer->state,
			'sv_customer_zip' => $this->sv_customer->zip,
			'sv_customer_country' => $this->sv_customer->country,
			'sv_customer_telephone' => $this->sv_customer->telephone,
			'sv_customer_telephone_alt' => $this->sv_customer->telephone_alt,
		);
    return isset( $values[ $name ] ) ? $values[ $name ] : $value;
	}
	public function sv_event_data($value,$field,$name) {
		$values = array(
			'sv_event_location' => ($this->sv_event->event_church ? $this->sv_event->event_church . ' (' : '') . $this->sv_event->event_city . ', ' . $this->sv_event->event_state . ($this->sv_event->event_church ? ')' : ''),
			'sv_event_church' => $this->sv_event->event_church,
			'sv_event_city' => $this->sv_event->event_city,
			'sv_event_state' => $this->sv_event->event_state,
			'event_id' => $this->sv_event->event_id
		);
		return isset( $values[ $name ] ) ? $values[ $name ] : $value;
	}
	public function add_role_to_body($classes) {
			$current_user = new WP_User(get_current_user_id());
			$user_role = array_shift($current_user->roles);
			if (is_admin()) {
					$classes .= 'role-'. $user_role;
			} else {
					$classes[] = 'role-'. $user_role;
			}
			return $classes;
	}
	public function populate_user_role($value) {
		$current_user = new WP_User(get_current_user_id());
		$user_role = array_shift($current_user->roles);
		return $user_role;
	}
	public function supported_notification_events( $form ) {
			if ( ! $this->has_feed( $form['id'] ) ) {
					return false;
			}

			return array(
							'feed_processed'          => esc_html__( 'After successful SermonView API response', 'SermonView_Integration_Gravity_Forms' ),
//							'event_created'          => esc_html__( 'Event Created', 'SermonView_Integration_Gravity_Forms' ),
//							'kit_requested'            => esc_html__( 'Kit Requested', 'SermonView_Integration_Gravity_Forms' ),
			);
	}
}

function fixSda($name) {
	$name = preg_replace('/of SDA/i','',$name);
	$name = preg_replace('/ SDA/i',' Adventist',$name);
	$name = preg_replace('/ \(.{1,25}\)$/','',$name);
	return trim($name);
}

