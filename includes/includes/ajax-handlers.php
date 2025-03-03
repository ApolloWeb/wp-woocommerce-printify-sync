<?php
/**
 * AJAX handler functions for the settings page
 */

// Save settings via AJAX
add_action('wp_ajax_save_printify_setting', 'printify_sync_save_setting');
function printify_sync_save_setting() {
    // Check nonce for security
    check_ajax_referer('printify_sync_settings', 'security');
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    
    $field_name = sanitize_text_field($_POST['field_name']);
    $field_value = sanitize_text_field($_POST['field_value']);
    
    // Validate the input based on the field
    switch ($field_name) {
        case 'notification_email':
            if (!is_email($field_value)) {
                wp_send_json_error('Invalid email address');
                return;
            }
            break;
            
        case 'notification_phone':
            if (!preg_match('/^\+?[1-9]\d{1,14}$/', $field_value)) {
                wp_send_json_error('Invalid phone number');
                return;
            }
            break;
    }
    
    // Save the option
    update_option($field_name, $field_value);
    
    // Send success response
    wp_send_json_success([
        'message' => 'Setting saved successfully',
        'field' => $field_name,
        'value' => $field_value
    ]);
}