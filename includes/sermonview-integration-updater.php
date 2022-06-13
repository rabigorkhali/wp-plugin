<?php

/* * ***************************************************

  SermonView Integration Updater Class
  Larry Witzel
  7/10/2018

  Description: Hook into the WordPress update system, integrating with GitHub to update the plugin automatically

  "I try to find common ground with everyone, doing everything I can to save some.
  I do everything to spread the Good News and share in its blessings."
  1 Corinthians 9:22b, 23 NLT2

	Take from: https://www.smashingmagazine.com/2015/08/deploy-wordpress-plugins-with-github-using-transients/

	Note: As of 2/10/2020, API authentication through query parameters is deprecated: https://developer.github.com/changes/2020-02-10-deprecating-auth-through-query-param/

 * **************************************************** */




class SermonView_Integration_Updater {

	private $file;

	public $plugin;

	private $basename;

	private $active;

	private $username;

	private $repository;

	private $authorize_token;

	private $github_response;

	public function __construct( &$svi=null ) {

		$this->file = $svi->get_plugin_file();
		$this->svi = $svi;

		add_action( 'admin_init', array( $this, 'set_plugin_properties' ) );

		return $this;
	}

	public function set_plugin_properties() {
		$this->plugin	= get_plugin_data( $this->file );
		$this->basename = plugin_basename( $this->file );
		$this->active	= is_plugin_active( $this->basename );
		$this->svi->version = $this->plugin['Version'];
	}

	public function set_username( $username ) {
		$this->username = $username;
	}

	public function set_repository( $repository ) {
		$this->repository = $repository;
	}

	public function authorize( $token ) {
		$this->authorize_token = $token;
	}

	private function get_repository_info() {
	    if ( is_null( $this->github_response ) ) { // Do we have a response?
	        $request_uri = 'https://wpu.ctsmg.com/releases/' . $this->repository; // Build URI
			$this->github_response = json_decode( wp_remote_retrieve_body( wp_remote_get( $request_uri, array('headers'=>$headers) ) ), true ); // Get JSON and parse it
	    }
	}

	public function initialize() {
		add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'modify_transient' ), 10, 1 );
		add_filter( 'plugins_api', array( &$this, 'plugin_popup' ), 10000, 3);
		add_filter( 'upgrader_post_install', array( &$this, 'after_install' ), 10, 3 );
	}

	public function modify_transient( $transient ) {

		if( property_exists( $transient, 'checked') ) { // Check if transient has a checked property

			if( $checked = $transient->checked ) { // Did Wordpress check for updates?

				$this->get_repository_info(); // Get the repo info

				if(key_exists($this->basename,$checked)) {
					$out_of_date = version_compare( $this->github_response['tag_name'], $checked[ $this->basename ], 'gt' ); // Check if we're out of date
				} else {
					$out_of_date = false;
				}
				
				if( $out_of_date ) {

					$new_files = $this->github_response['zipball_url']; // Get the ZIP

					$slug = current( explode('/', $this->basename ) ); // Create valid slug

					$plugin = array( // setup our plugin info
						'url' => $this->plugin["PluginURI"],
						'slug' => $slug,
						'package' => $new_files,
						'new_version' => $this->github_response['tag_name']
					);

					$transient->response[$this->basename] = (object) $plugin; // Return it in response
				}
			}
		}

		return $transient; // Return filtered transient
	}

	public function plugin_popup( $result, $action, $args ) {

		if( ! empty( $args->slug ) ) { // If there is a slug

			if( $args->slug == current( explode( '/' , $this->basename ) ) ) { // And it's our slug

				$this->get_repository_info(); // Get our repo info

				// Set it to an array
				$plugin = array(
					'name'				=> $this->plugin["Name"],
					'slug'				=> $this->basename,
					'requires'					=> '4.4',
					'tested'						=> '4.9.7',
					'rating'						=> '100.0',
					'num_ratings'				=> '1',
					'downloaded'				=> '4',
					'added'							=> '2018-07-10',
					'version'			=> $this->github_response['tag_name'],
					'author'			=> $this->plugin["AuthorName"],
					'author_profile'	=> $this->plugin["AuthorURI"],
					'last_updated'		=> $this->github_response['published_at'],
					'homepage'			=> $this->plugin["PluginURI"],
					'short_description' => $this->plugin["Description"],
					'sections'			=> array(
						'Description'	=> $this->plugin["Description"],
						'Updates'		=> $this->github_response['body'],
					),
					'download_link'		=> $this->github_response['zipball_url']
				);

				if ( $action == 'query_plugins' || $action == 'plugin_information' ) {
					$plugin = (object) $plugin;
				}

				return $plugin; // Return the data
			}

		}
		return $result; // Otherwise return default
	}

	public function after_install( $response, $hook_extra, $result ) {
		global $wp_filesystem; // Get global FS object

		$install_directory = plugin_dir_path( $this->file ); // Our plugin directory
		$wp_filesystem->move( $result['destination'], $install_directory ); // Move files to the plugin dir
		$result['destination'] = $install_directory; // Set the destination for the rest of the stack

		if ( $this->active ) { // If it was active
			activate_plugin( $this->basename ); // Reactivate
		}

		return $result;
	}

}