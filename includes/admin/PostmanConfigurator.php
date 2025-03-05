<?php
/**
 * Postman Configurator
 *
 * Automatically configures Postman with all required Printify API endpoints.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\API\Printify\PrintifyApiClient;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PostmanConfigurator {
    /**
     * API client
     * 
     * @var PrintifyApiClient
     */
    private $api_client;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_client = PrintifyApiClient::get_instance();
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwprintifysync_generate_postman_collection', array($this, 'generate_postman_collection'));
        add_action('wp_ajax_wpwprintifysync_export_postman_collection', array($this, 'export_postman_collection'));
    }
    
    /**
     * Generate complete Postman collection
     */
    public function generate_postman_collection() {
        check_ajax_referer('wpwprintifysync-postman-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to generate Postman collection.', 'wp-woocommerce-printify-sync')));
        }
        
        // Get plugin settings
        $settings = get_option('wpwprintifysync_settings', array());
        $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
        $shop_id = isset($settings['shop_id']) ? $settings['shop_id'] : '';
        
        // Generate Postman Collection
        $collection = $this->create_postman_collection($api_key, $shop_id);
        
        // Save collection to option for later use
        update_option('wpwprintifysync_postman_collection', json_encode($collection), false);
        
        wp_send_json_success(array(
            'message' => __('Postman collection generated successfully!', 'wp-woocommerce-printify-sync'),
            'collection' => $collection
        ));
    }
    
    /**
     * Export Postman collection as JSON file
     */
    public function export_postman_collection() {
        check_ajax_referer('wpwprintifysync-postman-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to export Postman collection.', 'wp-woocommerce-printify-sync')));
        }
        
        $collection_json = get_option('wpwprintifysync_postman_collection', '');
        
        if (empty($collection_json)) {
            // Generate new collection if none exists
            $settings = get_option('wpwprintifysync_settings', array());
            $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
            $shop_id = isset($settings['shop_id']) ? $settings['shop_id'] : '';
            
            $collection = $this->create_postman_collection($api_key, $shop_id);
            $collection_json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
            update_option('wpwprintifysync_postman_collection', $collection_json, false);
        } else {
            $collection = json_decode($collection_json, true);
        }
        
        // Generate download filename
        $filename = 'printify-api-collection-' . date('Ymd') . '.json';
        
        // Set headers to force download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($collection_json));
        header('Connection: close');
        
        echo $collection_json;
        exit;
    }
    
    /**
     * Create comprehensive Postman collection with all required endpoints
     *
     * @param string $api_key Printify API key
     * @param string $shop_id Printify shop ID
     * @return array Postman collection structure
     */
    private function create_postman_collection($api_key, $shop_id) {
        // Generate unique collection ID
        $collection_id = wp_generate_password(24, false);
        
        // Create base collection structure
        $collection = array(
            'info' => array(
                '_postman_id' => $collection_id,
                'name' => 'Printify API Collection',
                'description' => 'Complete collection of Printify API endpoints for WP WooCommerce Printify Sync plugin',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ),
            'item' => array(),
            'variable' => array(
                array(
                    'key' => 'printify_api_key',
                    'value' => $api_key,
                    'type' => 'string'
                ),
                array(
                    'key' => 'shop_id',
                    'value' => $shop_id,
                    'type' => 'string'
                ),
                array(
                    'key' => 'base_url',
                    'value' => 'https://api.printify.com/v1',
                    'type' => 'string'
                )
            )
        );
        
        // Add authentication section
        $collection['item'][] = $this->create_authentication_folder();
        
        // Add shops section
        $collection['item'][] = $this->create_shops_folder();
        
        // Add products section
        $collection['item'][] = $this->create_products_folder();
        
        // Add orders section
        $collection['item'][] = $this->create_orders_folder();
        
        // Add catalog section
        $collection['item'][] = $this->create_catalog_folder();
        
        // Add webhooks section
        $collection['item'][] = $this->create_webhooks_folder();
        
        // Add uploads section
        $collection['item'][] = $this->create_uploads_folder();
        
        // Add bulk operations section
        $collection['item'][] = $this->create_bulk_operations_folder();
        
        return $collection;
    }
    
    /**
     * Create authentication folder
     *
     * @return array Folder structure
     */
    private function create_authentication_folder() {
        return array(
            'name' => 'Authentication',
            'description' => 'Validate API authentication',
            'item' => array(
                array(
                    'name' => 'Get Shops (Auth Test)',
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
                            'raw' => '{{base_url}}/shops.json',
                            'host' => array('{{base_url}}'),
                            'path' => array('shops.json')
                        ),
                        'description' => 'Use this endpoint to verify your API key is working correctly.'
                    ),
                    'response' => array()
                )
            )
        );
    }
    
    /**
     * Create shops folder with all shop-related endpoints
     *
     * @return array Folder structure
     */
    private function create_shops_folder() {
        return array(
            'name' => 'Shops',
            'description' => 'Endpoints for managing shops',
            'item' => array(
                array(
                    'name' => 'List All Shops',
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
                            'raw' => '{{base_url}}/shops.json',
                            'host' => array('{{base_url}}'),
                            'path' => array('shops.json')
                        )
                    ),
                    'response' => array()
                ),
                array(
                    'name' => 'Get Shop Details',
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
                            'raw' => '{{base_url}}/shops/{{shop_id}}.json',
                            'host' => array('{{base_url}}'),
                            'path' => array('shops', '{{shop_id}}.json')
                        )
                    ),
                    'response' => array()
                ),
                array(
                    'name' => 'Get Shop Connection Status',
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
                            'raw' => '{{base_url}}/shops/{{shop_id}}/connection.json',
                            'host' => array('{{base_url}}'),
                            'path' => array('shops', '{{shop_id}}', 'connection.json')
                        )
                    ),
                    'response' => array()
                )
            )
        );
    }
    
    /**
     * Create products folder with all product-related endpoints
     *
     * @return array Folder structure
     */
    private function create_products_folder() {
        return array(
            'name' => 'Products',
            'description' => 'Endpoints for managing products',
            'item' => array(
                array(
                    'name' => 'List All Products',
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
                            'raw' => '{{base_url}}/shops/{{shop_id}}/products.json?page=1&limit=20',
                            'host' => array('{{base_url}}'),
                            'path' => array('shops', '{{shop_id}}', 'products.json'),
                            'query' => array(
                                array('key' => 'page', 'value' => '1'),
                                array('key' => 'limit', 'value' => '20')
                            )
                        ),
                        'description' => 'Get a list of products with pagination (20 per page).'
                    ),
                    'response' => array()
                ),
                array(
                    'name' => 'Get Product Details',
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
                            'raw' => '{{base_url}}/shops/{{shop_id}}/products/{product_id}.json',
                            'host' => array('{{base_url}}'),
                            'path' => array('shops', '{{shop_id}}', 'products', '{product_id}.json')
                        ),
                        'description' => 'Get detailed information about a specific product. Replace {product_id} with an actual product ID.'
                    ),
                    'response' => array()
                ),
                array(
                    'name' => 'Create New Product',
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
                            'raw' => '{\n    "title": "Sample T-Shirt",\n    "description": "This is a sample product created via API",\n    "blueprint_id": 384,\n    "print_provider_id": 1,\n    "variants": [\n        {\n            "id": 12345,\n            "price": 24.99,\n            "is_enabled": true\n        }\n    ],\n    "print_areas": [\n        {\n            "variant_ids": [12345],\n            "placeholders": [\n                {\n                    "position": "front",\n                    "images": [\n                        {\n                            "id": "YOUR_IMAGE_ID",\n                            "x": 0.5,\n                            "y": 0.5,\n                            "scale": 0.9,\n                            "angle": 0\n                        }\n                    ]\n                }\n            ]\n        }\n    ]\n}'
                        ),
                        'url' => array(
                            'raw' => '{{base_url}}/shops/{{shop_id}}/products.json',
                            'host' => array('{{base_url}}'),
                            'path' => array('shops', '{{shop_id}}', 'products.json')
                        ),
                        'description' => 'Create a new product. Example shows basic T-shirt creation.'
                    ),
                    'response' => array()
                ),
                array(
                    'name' => 'Update Product',
                    'request' => array(
                        'method' => 'PUT',
                        'header' => array(
                            array(
                                'key' => 'Authorization',
                                'value' => 'Bearer {{printify_api_key}}',
                                'type' => 'text'
                            ),
                            array(
                                'key' => 'Content-Type',
                                'value' => 'application/json',
                                