<?php
// Step 1: Create the Settings Page Markup
function interesttracker_settings_page_markup() {
    include(plugin_dir_path(__FILE__) . '../public/views/setting-page.php');
}

// Step 2: Add Settings Page Action
function interesttracker_settings_page_action() {
    add_options_page('Interest Tracker Settings', 'Interest Tracker', 'manage_options', 'interesttracker-settings', 'interesttracker_settings_page_markup');
}
add_action('admin_menu', 'interesttracker_settings_page_action');

// Step 3: Define the Settings
function interesttracker_register_settings() {
    // Register settings
    register_setting('interesttracker_settings_group', 'interesttracker_api_key', 'sanitize_callback');
    register_setting('interesttracker_settings_group', 'interesttracker_api_endpoint', 'sanitize_callback');
}
add_action('admin_init', 'interesttracker_register_settings');

// Step 4: Save Settings
function sanitize_callback($input) {
    // Sanitize and validate input before saving
    return sanitize_text_field($input);
}

// Step 5: Enqueue Bootstrap CSS
function interesttracker_enqueue_admin_styles() {
    wp_enqueue_style('bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css');
}
add_action('admin_enqueue_scripts', 'interesttracker_enqueue_admin_styles');
