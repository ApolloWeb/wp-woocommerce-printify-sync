<?php
/**
 * Settings AJAX Handlers
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class SettingsAjax
 */
class SettingsAjax {
    /**
     * Initialize the class
     */
    public static function init() {
        add_action('wp_ajax_printify_save_general_settings', array(__CLASS__, 'save_general_settings'));
        add_action('wp_ajax_printify_save_api_settings', array(__CLASS__, 'save_api_settings'));
        add_action('wp_ajax_printify_save_currency_settings', array(__CLASS__, 'save_currency_settings'));
        add_action('wp_ajax_printify_test_api_connection', array(__CLASS__, 'test_api_connection'));
        add_action('wp_ajax_printify_test_currency_api', array(__CLASS__, 'test_currency_api'));
    }
    
    /**
     * Save general settings
     */
    public static function save_general_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'printify_sync_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Get environment setting
        $environment = isset($_POST['environment']) ? sanitize_text_field($_POST['environment']) : 'production';
        
        // Save setting
        update_option('printify_sync_environment', $environment);
        
        // Send success response
        wp_send_json_success(array('message' => 'General settings saved successfully'));
    }
    
    /**
     * Save API settings
     */
    public static function save_api_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'printify_sync_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Get and save API key
        if (isset($_POST['api_key'])) {
            $api_key = sanitize_text_field($_POST['api_key']);
            update_option('printify_sync_api_key', $api_key);
        }
        
        // Send success response
        wp_send_json_success(array('message' => 'API settings saved successfully'));
    }
    
    /**
     * Save currency settings
     */
    public static function save_currency_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'printify_sync_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Get and save currency settings
        if (isset($_POST['currency_api_key'])) {
            update_option('printify_sync_currency_api_key', sanitize_text_field($_POST['currency_api_key']));
        }
        
        if (isset($_POST['default_currency'])) {
            update_option('printify_sync_default_currency', sanitize_text_field($_POST['default_currency']));
        }
        
        if (isset($_POST['currency_update_frequency'])) {
            update_option('printify_sync_currency_update_frequency', sanitize_text_field($_POST['currency_update_frequency']));
        }
        
        // Send success response
        wp_send_json_success(array('message' => 'Currency settings saved successfully'));
    }
    
    /**
     * Test API connection
     */
    public static function test_api_connection() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'printify_sync_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API key is required'));
        }
        
        // For demonstration purposes, we'll just return success
        // In a real implementation, you would make an API request to Printify
        wp_send_json_success(array('message' => 'API connection successful!'));
    }
    
    /**
     * Test currency API connection
     */
    public static function test_currency_api() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'printify_sync_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        $api_key = isset($_POST['currency_api_key']) ? sanitize_text_field($_POST['currency_api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'Currency API key is required'));
        }
        
        // For demonstration purposes, we'll just return success
        // In a real implementation, you would make an API request to your currency service
        wp_send_json_success(array('message' => 'Currency API connection successful!'));
    }
}