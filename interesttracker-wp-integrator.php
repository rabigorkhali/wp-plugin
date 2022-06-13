<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
Plugin Name: InterestTracker WordPress Integrator
Description: Allows InterestTracker to create integrations with Gravity Forms on a WordPress site, so form responses are imported as interests.
Version: 0.0.0
Author: Larry Witzel for SermonView
Text Domain: interesttracker-wp-integrator
Plugin URI: https://github.com/lwitzel/interesttracker-wp-integrator
Author URI: https://github.com/lwitzel/

Copyright (c) 2022 SermonView. All rights reserved.

This program is protected intellectual property of SermonView. It may not be distributed or modified without the prior express written consent of the intellectual property owner.

SermonView Integration
Larry Witzel
6/13/2022

"I am the living one. I died, but look—I am alive forever and ever! And I hold the keys of death and the grave."
			Revelation 1:18 NLT2


 * **************************************************** */


// NEW VERSION: Version number must be incremented here AND in the plugin info block above
define('SERMONVIEW_INTEGRATION_VERSION', '0.0.0');

define('SERMONVIEW_INTEGRATION_PLUGIN_BASENAME', plugin_basename(__FILE__));

function activate_sermonview_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/sermonview-integration-activator.php';
	SermonView_Integration_Activator::activate();
}
function deactivate_sermonview_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/sermonview-integration-deactivator.php';
	SermonView_Integration_Deactivator::deactivate();
}
register_activation_hook( __FILE__, 'activate_sermonview_integration' );
register_deactivation_hook( __FILE__, 'deactivate_sermonview_integration' );

require_once plugin_dir_path( __FILE__ ) . 'includes/sermonview-integration.php';
$svi_plugin = new SermonView_Integration;
$svi_plugin->set_plugin_file(__FILE__);
$svi_plugin->run();

// helpful codeigniter-style die and dump
if (!function_exists('dd')) {
	function dd($val) {
		echo '<pre>' . print_r($val,1) . '</pre>';
		die();
	}
}
