<?php
/**
 * Postman Manager
 *
 * Handles Postman collection management and API testing features.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\API\Printify\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PostmanManager {
    /**
     * Singleton instance
     *
     * @var PostmanManager
     */
    private static $instance = null;
    
    /**
     * Collection data
     * 
     * @var array
     */
    private $collection = array();
    
    /**
     * API client
     * 
     * @var PrintifyApiClient
     */
    private $api_client = null;
    
    /**
     * Get singleton instance
     *
     * @return PostmanManager
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->api_client = PrintifyApiClient::get_instance();
        $this->init_collection();
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_menu_page'));
        
        // Register scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add AJAX handlers
        add_action('wp_ajax_wpwprintifysync_test_endpoint', array($this, 'ajax_test_endpoint'));
        add_action('wp_ajax_wpwprintifysync_save_collection', array($this, 'ajax_save_collection'));
        add_action('wp_ajax_wpwprintifysync_import_collection', array($this, 'ajax_import_collection'));
        add_action('wp_ajax_wpwprintifysync_export_collection', array($this, 'ajax_export_collection'));
    }
    
    /**
     * Initialize collection
     */
    private function init_collection() {
        $default_collection = array(
            'info' => array(
                'name' => 'Printify API Collection',
                'description' => 'A collection for testing Printify API endpoints.',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ),
            'item' => array(
                array(
                    'name' => 'Authentication',
                    'description' => 'Endpoints for testing API authentication',
                    'item' => array(
                        array(
                            'name' => 'Get Shops',
                            'request' => array(
                                'method' => 'GET',
                                'header' => array(
                                    array(
                                        'key' => 'Authorization',
                                        'value' => 'Bearer {{printify_api_key}}',
                                        'type' => 'text'
                                    )
                                ),
                                'url' => array(
                                    'raw' => 'https://api.printify.com/v1/shops.json',
                                    'protocol' => 'https',
                                    'host' => array('api', 'printify', 'com'),
                                    'path' => array('v1', 'shops.json')
                                )
                            ),
                            'response' => array()
                        )
                    )
                ),
                array(
                    'name' => 'Products',
                    'description' => 'Endpoints for product management',
                    'item' => array(
                        array(
                            'name' => 'Get Products',
                            'request' => array(
                                'method' => 'GET',
                                'header' => array(
                                    array(
                                        'key' => 'Authorization',
                                        'value' => 'Bearer {{printify_api_key}}',
                                        'type' => 'text'
                                    )
                                ),
                                'url' => array(
                                    'raw' => 'https://api.printify.com/v1/shops/{{shop_id}}/products.json?page=1&limit=10',
                                    'protocol' => 'https',
                                    'host' => array('api', 'printify', 'com'),
                                    'path' => array('v1', 'shops', '{{shop_id}}', 'products.json'),
                                    'query' => array(
                                        array('key' => 'page', 'value' => '1'),
                                        array('key' => 'limit', 'value' => '10')
                                    )
                                )
                            ),
                            'response' => array()
                        ),
                        array(
                            'name' => 'Get Product',
                            'request' => array(
                                'method' => 'GET',
                                'header' => array(
                                    array(
                                        'key' => 'Authorization',
                                        'value' => 'Bearer {{printify_api_key}}',
                                        'type' => 'text'
                                    )
                                ),
                                'url' => array(
                                    'raw' => 'https://api.printify.com/v1/shops/{{shop_id}}/products/{{product_id}}.json',
                                    'protocol' => 'https',
                                    'host' => array('api', 'printify', 'com'),
                                    'path' => array('v1', 'shops', '{{shop_id}}', 'products', '{{product_id}}.json')
                                )
                            ),
                            'response' => array()
                        )
                    )
                ),
                array(
                    'name' => 'Orders',
                    'description' => 'Endpoints for order management',
                    'item' => array(
                        array(
                            'name' => 'Get Orders',
                            'request' => array(
                                'method' => 'GET',
                                'header' => array(
                                    array(
                                        'key' => 'Authorization',
                                        'value' => 'Bearer {{printify_api_key}}',
                                        'type' => 'text'
                                    )
                                ),
                                'url' => array(
                                    'raw' => 'https://api.printify.com/v1/shops/{{shop_id}}/orders.json?page=1&limit=10',
                                    'protocol' => 'https',
                                    'host' => array('api', 'printify', 'com'),
                                    'path' => array('v1', 'shops', '{{shop_id}}', 'orders.json'),
                                    'query' => array(
                                        array('key' => 'page', 'value' => '1'),
                                        array('key' => 'limit', 'value' => '10')
                                    )
                                )
                            ),
                            'response' => array()
                        ),
                        array(
                            'name' => 'Get Order',
                            'request' => array(
                                'method' => 'GET',
                                'header' => array(
                                    array(
                                        'key' => 'Authorization',
                                        'value' => 'Bearer {{printify_api_key}}',
                                        'type' => 'text'
                                    )
                                ),
                                'url' => array(
                                    'raw' => 'https://api.printify.com/v1/shops/{{shop_id}}/orders/{{order_id}}.json',
                                    'protocol' => 'https',
                                    'host' => array('api', 'printify', 'com'),
                                    'path' => array('v1', 'shops', '{{shop_id}}', 'orders', '{{order_id}}.json')
                                )
                            ),
                            'response' => array()
                        ),
                        array(
                            'name' => 'Create Order',
                            'request' => array(
                                'method' => 'POST',
                                'header' => array(
                                    array(
                                        'key' => 'Authorization',
                                        'value' => 'Bearer {{printify_api_key}}',
                                        'type' => 'text'
                                    ),
                                    array(
                                        'key' => 'Content-Type',
                                        'value' => 'application/json',
                                        'type' => 'text'
                                    )
                                ),
                                'body' => array(
                                    'mode' => 'raw',
                                    'raw' => '{\n    "external_id": "test-order-123",\n    "line_items": [\n        {\n            "product_id": "{{product_id}}",\n            "variant_id": {{variant_id}},\n            "quantity": 1\n        }\n    ],\n    "shipping_method": "standard",\n    "shipping_address": {\n        "first_name": "John",\n        "last_name": "Doe",\n        "address1": "123 Main St",\n        "city": "New York",\n        "state": "NY",\n        "zip": "10001",\n        "country": "US",\n        "email": "john.doe@example.com",\n        "phone": "+1234567890"\n    }\n}'
                                ),
                                'url' => array(
                                    'raw' => 'https://api.printify.com/v1/shops/{{shop_id}}/orders.json',
                                    'protocol' => 'https',
                                    'host' => array('api', 'printify', 'com'),
                                    'path' => array('v1', 'shops', '{{shop_id}}', 'orders.json')
                                )
                            ),
                            'response' => array()
                        )
                    )
                ),
                array(
                    'name' => 'Webhooks',
                    'description' => 'Endpoints for webhook management',
                    'item' => array(
                        array(
                            'name' => 'Get Webhooks',
                            'request' => array(
                                'method' => 'GET',
                                'header' => array(
                                    array(
                                        'key' => 'Authorization',
                                        'value' => 'Bearer {{printify_api_key}}',
                                        'type' => 'text'
                                    )
                                ),
                                'url' => array(
                                    'raw' => 'https://api.printify.com/v1/shops/{{shop_id}}/webhooks.json',
                                    'protocol' => 'https',
                                    'host' => array('api', 'printify', 'com'),
                                    'path' => array('v1', 'shops', '{{shop_id}}', 'webhooks.json')
                                )
                            ),
                            'response' => array()
                        ),
                        array(
                            'name' => 'Create Webhook',
                            'request' => array(
                                'method' => 'POST',
                                'header' => array(
                                    array(
                                        'key' => 'Authorization',
                                        'value' => 'Bearer {{printify_api_key}}',
                                        'type' => 'text'
                                    ),
                                    array(
                                        'key' => 'Content-Type',
                                        'value' => 'application/json',
                                        'type' => 'text'
                                    )
                                ),
                                'body' => array(
                                    'mode' => 'raw',
                                    'raw' => '{\n    "url": "{{webhook_url}}",\n    "events": ["product:published", "order:created", "order:shipping_details_updated"]\n}'
                                ),
                                'url' => array(
                                    'raw' => 'https://api.printify.com/v1/shops/{{shop_id}}/webhooks.json',
                                    'protocol' => 'https',
                                    'host' => array('api', 'printify', 'com'),
                                    'path' => array('v1', 'shops', '{{shop_id}}', 'webhooks.json')
                                )
                            ),
                            'response' => array()
                        )
                    )
                )
            )
        );
        
        $saved_collection = get_option('wpwprintifysync_postman_collection');
        $this->collection = $saved_collection ? json_decode($saved_collection, true) : $default_collection;
    }
    
    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'wpwprintifysync',
            __('API Tester', 'wp-woocommerce-printify-sync'),
            __('API Tester', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwprintifysync-api-tester',
            array($this, 'render_page')
        );
    }
    
    /**
     * Enqueue scripts
     *
     * @param string $hook Hook suffix
     */
    public function enqueue_scripts($hook) {
        if ('printify-sync_page_wpwprintifysync-api-tester' !== $hook) {
            return;
        }
        
        // Register and enqueue styles
        wp_register_style('wpwprintifysync-postman', WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/css/postman.css', array(), WPWPRINTIFYSYNC_VERSION);
        wp_enqueue_style('wpwprintifysync-postman');
        
        // Register and enqueue scripts
        wp_register_script('wpwprintifysync-postman', WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/postman.js', array('jquery', 'wp-util'), WPWPRINTIFYSYNC_VERSION, true);
        
        $settings = get_option('wpwprintifysync_settings', array());
        $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
        $shop_id = isset($settings['shop_id']) ? $settings['shop_id'] : '';
        
        wp_localize_script('wpwprintifysync-postman', 'wpwprintifysyncPostman', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwprintifysync-postman-nonce'),
            'collection' => $this->collection,
            'variables' => array(
                'printify_api_key' => $api_key,
                'shop_id' => $shop_id,
                'webhook_url' => site_url('wp-json/wpwprintif