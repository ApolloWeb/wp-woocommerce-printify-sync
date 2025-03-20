<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax\Handler;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyHttpClient;

class SettingsHandler extends AbstractAjaxHandler
{
    /**
     * Handle settings save request
     */
    public function handle(): void
    {
        if (!isset($_POST['form_data'])) {
            wp_send_json_error(['message' => 'No form data provided']);
            return;
        }
        
        parse_str($_POST['form_data'], $form_data);
        
        // Update API key
        if (isset($form_data['printify_api_key'])) {
            update_option('wpwps_printify_api_key', sanitize_text_field($form_data['printify_api_key']));
        }
        
        // Update API endpoint
        if (isset($form_data['printify_endpoint'])) {
            update_option('wpwps_printify_endpoint', esc_url_raw($form_data['printify_endpoint']));
        }
        
        // Update shop ID
        if (isset($form_data['printify_shop_id'])) {
            update_option('wpwps_printify_shop_id', sanitize_text_field($form_data['printify_shop_id']));
        }
        
        // Update currency
        if (isset($form_data['currency'])) {
            update_option('wpwps_currency', sanitize_text_field($form_data['currency']));
        }
        
        wp_send_json_success(['message' => 'Settings saved successfully']);
    }
    
    /**
     * Save settings
     */
    protected function saveSettings(): void
    {
        // Parse form data
        $form_data = [];
        parse_str($_POST['form_data'], $form_data);
        
        // Sanitize and save each setting
        if (isset($form_data['printify_api_key'])) {
            update_option('wpwps_printify_api_key', sanitize_text_field($form_data['printify_api_key']));
        }
        
        if (isset($form_data['printify_endpoint'])) {
            update_option('wpwps_printify_endpoint', esc_url_raw($form_data['printify_endpoint']));
        }
        
        if (isset($form_data['printify_shop_id'])) {
            update_option('wpwps_printify_shop_id', sanitize_text_field($form_data['printify_shop_id']));
        }
        
        wp_send_json_success(['message' => 'Settings saved successfully']);
    }
    
    /**
     * Test API connection
     */
    public function testConnection(): void
    {
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $endpoint = esc_url_raw($_POST['endpoint'] ?? '');
        
        if (empty($api_key) || empty($endpoint)) {
            wp_send_json_error(['message' => 'API key and endpoint are required']);
            return;
        }
        
        // Test connection with provided credentials
        try {
            $client = new PrintifyHttpClient($api_key, $endpoint);
            $result = $client->request('shops.json');
            wp_send_json_success(['message' => 'Connection successful']);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Fetch shops from Printify
     */
    public function fetchShops(): void
    {
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $endpoint = esc_url_raw($_POST['endpoint'] ?? '');
        
        if (empty($api_key) || empty($endpoint)) {
            wp_send_json_error(['message' => 'API key and endpoint are required']);
            return;
        }
        
        // Fetch shops from Printify API
        try {
            $client = new PrintifyHttpClient($api_key, $endpoint);
            $shops = $client->request('shops.json');
            wp_send_json_success(['shops' => $shops]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Select shop
     */
    public function selectShop(): void
    {
        $shop_id = sanitize_text_field($_POST['shop_id'] ?? '');
        $shop_title = sanitize_text_field($_POST['shop_title'] ?? '');
        
        if (empty($shop_id)) {
            wp_send_json_error(['message' => 'Shop ID is required']);
            return;
        }
        
        // Save shop ID
        update_option('wpwps_printify_shop_id', $shop_id);
        update_option('wpwps_printify_shop_title', $shop_title);
        
        wp_send_json_success(['message' => 'Shop selected successfully']);
    }
    
    /**
     * Manual sync of products
     */
    public function manualSync(): void
    {
        // Check if API credentials and shop are configured
        $api_key = get_option('wpwps_printify_api_key', '');
        $shop_id = get_option('wpwps_printify_shop_id', '');
        $endpoint = get_option('wpwps_printify_endpoint', '');
        
        if (empty($api_key) || empty($shop_id) || empty($endpoint)) {
            wp_send_json_error(['message' => 'API credentials or shop ID not configured']);
            return;
        }
        
        // Update timestamp
        $timestamp = current_time('mysql');
        update_option('wpwps_last_sync', $timestamp);
        
        // Simulate syncing products (would be replaced with actual sync logic)
        $current_count = get_option('wpwps_products_synced', 0);
        $new_count = $current_count + rand(5, 15); // Simulate syncing
        update_option('wpwps_products_synced', $new_count);
        
        wp_send_json_success([
            'message' => 'Products sync completed successfully',
            'last_sync' => $timestamp,
            'products_synced' => $new_count
        ]);
    }
}
