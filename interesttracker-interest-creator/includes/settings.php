<?php
// Step 1: Create the Settings Page
function interesttracker_settings_page() {
    // Add a menu item under Settings
    add_options_page('Interest Tracker Settings', 'Interest Tracker', 'manage_options', 'interesttracker-settings', 'interesttracker_settings_page_markup');
}
add_action('admin_menu', 'interesttracker_settings_page');

// Step 2: Define the Settings
function interesttracker_register_settings() {
    // Register settings
    register_setting('interesttracker_settings_group', 'interesttracker_api_key', 'sanitize_callback');
    register_setting('interesttracker_settings_group', 'interesttracker_api_endpoint', 'sanitize_callback');
}
add_action('admin_init', 'interesttracker_register_settings');

// Step 4: Create the Settings Page Markup
function interesttracker_settings_page_markup() {
    ?>
    <div class="wrap">
        <h1>Interest Tracker Settings</h1>
        <form method="post" action="options.php">
            <?php
            // Output security fields for the registered setting 'interesttracker_settings_group'
            settings_fields('interesttracker_settings_group');

            // Get saved options
            $api_key = get_option('interesttracker_api_key');
            $api_endpoint = get_option('interesttracker_api_endpoint');
            ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API KEY</th>
                    <td><input type="text" name="interesttracker_api_key" value="<?php echo esc_attr($api_key); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">API ENDPOINT</th>
                    <td><input type="text" name="interesttracker_api_endpoint" value="<?php echo esc_attr($api_endpoint); ?>" /></td>
                </tr>
            </table>

            <?php
            // Submit button
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// Step 5: Save Settings
function sanitize_callback($input) {
    // Sanitize and validate input before saving
    return sanitize_text_field($input);
}
