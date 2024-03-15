<?php
/*
Plugin Name: Interest Tracker Interest Creator
Plugin URI: https://example.com/interest-tracker
Description: A plugin to track user interests and create custom recommendations.
Version: 1.0
Author: Your Name
Author URI: https://example.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: interest-tracker
*/

// Activation hook
register_activation_hook(__FILE__, 'interesttracker_activation_hook');
function interesttracker_activation_hook() {
    // Add activation tasks here if needed
}

// Include settings file
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';

// Add settings link to plugin action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'interesttracker_settings_link');
function interesttracker_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=interesttracker-settings">Settings</a>';
    array_unshift($links, $settings_link); // Add settings link at the beginning
    return $links;
}
