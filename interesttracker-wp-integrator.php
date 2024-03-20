<?php

/**
 * Plugin Name: SermonView Interest Creator Rabi
 * Plugin URI: https://interesttracker.org/
 * Description: The theme of this epoch is a WordPress plug-in for InterestTracker which is an add-on to Gravity forms, which will allow form responses to flow into InterestTracker.
 * Version: 0.4
 * Author: sermon-view
 * Author URI: https://www.rabigorkhali.com.np
 **/

/* PLUGIN UPDATE CHECK */
require 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/lwitzel/interesttracker-wp-integrator/',
    __FILE__,
    'interesttracker-wp-integrator'
);

/* Set the branch that contains the stable release.*/
$myUpdateChecker->setBranch('rabi-if1-i1');
/* Optional: If you're using a private repository, specify the access token like this:*/
$myUpdateChecker->setAuthentication('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');
/* PLUGIN UPDATE CHECK */

// Activation hook
register_activation_hook(__FILE__, 'interesttracker_activation_hook');
function interesttracker_activation_hook()
{
    // Add activation tasks here if needed
}

// Include settings file
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';

// Add settings link to plugin action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'interesttracker_settings_link');
function interesttracker_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=interesttracker-settings">Settings</a>';
    array_unshift($links, $settings_link); // Add settings link at the beginning
    return $links;
}
