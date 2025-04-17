<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApi;
use ApolloWeb\WPWooCommercePrintifySync\Api\ChatGPTApi;

/**
 * AJAX Handler for admin actions
 */
class AjaxHandler {
    /**
     * Initialize AJAX handlers
     */
    public function init() {
        // Printify API AJAX endpoints
        add_action('wp_ajax_wpwps_test_printify_connection', [$this, 'test_printify_connection']);
        add_action('wp_ajax_wpwps_get_printify_shops', [$this, 'get_printify_shops']);
        add_action('wp_ajax_wpwps_set_printify_shop', [$this, 'set_printify_shop']);
        
        // ChatGPT API AJAX endpoints
        add_action('wp_ajax_wpwps_test_chatgpt_connection', [$this, 'test_chatgpt_connection']);
        
        // Webhook management AJAX endpoints
        add_action('wp_ajax_wpwps_get_webhooks', [$this, 'get_webhooks']);
        add_action('wp_ajax_wpwps_create_webhook', [$this, 'create_webhook']);
        add_action('wp_ajax_wpwps_delete_webhook', [$this, 'delete_webhook']);
    }
    
    /**
     * Test Printify API connection
     */
    public function test_printify_connection() {
        check_ajax_referer('wpwps_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        $api = new PrintifyApi();
        $result = $api->testConnection();
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
            return;
        }
        
        // Format the shops data for the dropdown
        $shops = [];
        if (is_array($result)) {
            foreach ($result as $shop) {
                if (isset($shop['id']) && isset($shop['title'])) {
                    $shops[] = [
                        'id' => $shop['id'],
                        'name' => $shop['title']
                    ];
                }
            }
        }
        
        wp_send_json_success([
            'message' => __('Connection successful! Your Printify shops have been loaded.', 'wp-woocommerce-printify-sync'),
            'shops' => $shops
        ]);
    }
    
    /**
     * Get Printify shops
     */
    public function get_printify_shops() {
        check_ajax_referer('wpwps_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        $api = new PrintifyApi();
        $result = $api->testConnection();
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
            return;
        }
        
        // Format the shops data for the dropdown
        $shops = [];
        if (is_array($result)) {
            foreach ($result as $shop) {
                if (isset($shop['id']) && isset($shop['title'])) {
                    $shops[] = [
                        'id' => $shop['id'],
                        'name' => $shop['title']
                    ];
                }
            }
        }
        
        wp_send_json_success([
            'shops' => $shops
        ]);
    }
    
    /**
     * Set Printify shop
     */
    public function set_printify_shop() {
        check_ajax_referer('wpwps_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        $shop_id = isset($_POST['shop_id']) ? sanitize_text_field($_POST['shop_id']) : '';
        
        if (empty($shop_id)) {
            wp_send_json_error([
                'message' => __('No shop ID provided', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        $options = get_option('wpwps_options', []);
        $options['printify_shop_id'] = $shop_id;
        update_option('wpwps_options', $options);
        
        wp_send_json_success([
            'message' => __('Shop ID saved successfully', 'wp-woocommerce-printify-sync'),
            'shop_id' => $shop_id
        ]);
    }
    
    /**
     * Test ChatGPT API connection
     */
    public function test_chatgpt_connection() {
        check_ajax_referer('wpwps_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        $api = new ChatGPTApi();
        $result = $api->testConnection();
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
            return;
        }
        
        wp_send_json_success([
            'message' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
            'estimated_tokens' => $result['estimated_monthly_tokens'],
            'estimated_cost' => $result['estimated_monthly_cost'],
            'within_budget' => $result['within_budget'],
            'model' => $result['model']
        ]);
    }
    
    /**
     * Get webhooks
     */
    public function get_webhooks() {
        check_ajax_referer('wpwps_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        $api = new PrintifyApi();
        $result = $api->getWebhooks();
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
            return;
        }
        
        wp_send_json_success([
            'webhooks' => $result
        ]);
    }
    
    /**
     * Create webhook
     * According to Printify API docs: https://developers.printify.com/#create-webhook
     */
    public function create_webhook() {
        check_ajax_referer('wpwps_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        $event = isset($_POST['event']) ? sanitize_text_field($_POST['event']) : '';
        
        if (empty($event)) {
            wp_send_json_error([
                'message' => __('No event provided', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        // Valid webhook events based on Printify API docs
        $valid_events = [
            'product.update',
            'product.delete',
            'product.published',
            'order.created',
            'order.update',
            'shipping.update'
        ];
        
        if (!in_array($event, $valid_events)) {
            wp_send_json_error([
                'message' => __('Invalid event type', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        $url = site_url('/wp-json/wpwps/v1/webhook/');
        
        $api = new PrintifyApi();
        $result = $api->createWebhook($event, $url);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
            return;
        }
        
        // Store webhook information in the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwps_webhooks';
        
        // Check if webhook already exists in our DB
        $webhook_id = isset($result['id']) ? $result['id'] : '';
        
        if (!empty($webhook_id)) {
            // Store in database
            $wpdb->insert(
                $table_name,
                [
                    'printify_id' => $webhook_id,
                    'topic' => $event,
                    'url' => $url,
                    'status' => 'active'
                ],
                ['%s', '%s', '%s', '%s']
            );
        }
        
        wp_send_json_success([
            'message' => __('Webhook created successfully', 'wp-woocommerce-printify-sync'),
            'event' => $event,
            'id' => $webhook_id
        ]);
    }
    
    /**
     * Delete webhook
     */
    public function delete_webhook() {
        check_ajax_referer('wpwps_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        $webhook_id = isset($_POST['webhook_id']) ? sanitize_text_field($_POST['webhook_id']) : '';
        
        if (empty($webhook_id)) {
            wp_send_json_error([
                'message' => __('No webhook ID provided', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        $api = new PrintifyApi();
        $result = $api->deleteWebhook($webhook_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
            return;
        }
        
        wp_send_json_success([
            'message' => __('Webhook deleted successfully', 'wp-woocommerce-printify-sync'),
            'webhook_id' => $webhook_id
        ]);
    }
}
