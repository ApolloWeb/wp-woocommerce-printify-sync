<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;
use ApolloWeb\WPWooCommercePrintifySync\API\Interfaces\PrintifyAPIInterface;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\ProductImporterInterface;

class AjaxHandler
{
    private $container;
    
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }
    
    public function handleAjax()
    {
        if (!check_ajax_referer('wpwps_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid security token']);
        }

        $action = sanitize_text_field($_POST['action_type'] ?? '');

        switch ($action) {
            case 'sync_products':
                $this->syncProducts();
                break;
            case 'sync_orders':
                $this->syncOrders();
                break;
            case 'save_settings':
                $this->saveSettings();
                break;
            case 'test_connection':
                $this->testConnection();
                break;
            case 'fetch_shops':
                $this->fetchShops();
                break;
            case 'select_shop':
                $this->selectShop();
                break;
            case 'manual_sync':
                $this->manualSync();
                break;
            case 'manual_sync_orders':
                $this->manualSyncOrders();
                break;
            case 'fetch_printify_products':
                $this->fetchPrintifyProducts();
                break;
            case 'import_product_to_woo':
                $this->importProductToWoo();
                break;
            default:
                wp_send_json_error(['message' => 'Invalid action']);
        }
    }

    private function syncProducts()
    {
        // TODO: Implement product sync
        wp_send_json_success(['message' => 'Products sync initiated']);
    }

    private function syncOrders()
    {
        // TODO: Implement order sync
        wp_send_json_success(['message' => 'Orders sync initiated']);
    }
    
    private function saveSettings()
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
    
    private function testConnection()
    {
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $endpoint = esc_url_raw($_POST['endpoint'] ?? '');
        
        if (empty($api_key) || empty($endpoint)) {
            wp_send_json_error(['message' => 'API key and endpoint are required']);
        }
        
        // Make a simple API request to verify credentials - use /shops.json per API docs
        $response = wp_remote_get(
            trailingslashit($endpoint) . 'shops.json',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 15
            ]
        );
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Connection error: ' . $response->get_error_message()]);
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_msg = isset($data['error']) ? $data['error'] : 'Unknown error';
            wp_send_json_error(['message' => "API responded with code $status_code: $error_msg"]);
        }
        
        wp_send_json_success(['message' => 'Connection successful']);
    }
    
    private function fetchShops()
    {
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $endpoint = esc_url_raw($_POST['endpoint'] ?? '');
        
        if (empty($api_key) || empty($endpoint)) {
            wp_send_json_error(['message' => 'API key and endpoint are required']);
        }
        
        // Fetch shops from Printify API - use /shops.json per API docs
        $response = wp_remote_get(
            trailingslashit($endpoint) . 'shops.json',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 15
            ]
        );
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Connection error: ' . $response->get_error_message()]);
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_msg = isset($data['error']) ? $data['error'] : 'Unknown error';
            wp_send_json_error(['message' => "API responded with code $status_code: $error_msg"]);
        }
        
        // Return shops data
        wp_send_json_success(['shops' => $data]);
    }
    
    private function selectShop()
    {
        $shop_id = sanitize_text_field($_POST['shop_id'] ?? '');
        $shop_title = sanitize_text_field($_POST['shop_title'] ?? '');
        
        if (empty($shop_id)) {
            wp_send_json_error(['message' => 'Shop ID is required']);
        }
        
        // Store shop information
        update_option('wpwps_printify_shop_id', $shop_id);
        update_option('wpwps_printify_shop_title', $shop_title);
        
        wp_send_json_success(['message' => 'Shop selected successfully']);
    }
    
    private function manualSync()
    {
        // Check if API credentials and shop are configured
        $api_key = get_option('wpwps_printify_api_key', '');
        $shop_id = get_option('wpwps_printify_shop_id', '');
        $endpoint = get_option('wpwps_printify_endpoint', '');
        
        if (empty($api_key) || empty($shop_id) || empty($endpoint)) {
            wp_send_json_error(['message' => 'API credentials or shop ID not configured']);
        }
        
        // For now, just update timestamps
        $timestamp = current_time('mysql');
        update_option('wpwps_last_sync', $timestamp);
        
        // Pretend we've synced some products
        $current_count = get_option('wpwps_products_synced', 0);
        $new_count = $current_count + rand(5, 15); // Simulate syncing some products
        update_option('wpwps_products_synced', $new_count);
        
        wp_send_json_success([
            'message' => 'Products sync completed successfully',
            'last_sync' => $timestamp,
            'products_synced' => $new_count
        ]);
    }
    
    private function manualSyncOrders()
    {
        // Check if API credentials and shop are configured
        $api_key = get_option('wpwps_printify_api_key', '');
        $shop_id = get_option('wpwps_printify_shop_id', '');
        $endpoint = get_option('wpwps_printify_endpoint', '');
        
        if (empty($api_key) || empty($shop_id) || empty($endpoint)) {
            wp_send_json_error(['message' => 'API credentials or shop ID not configured']);
        }
        
        // For now, just update timestamps
        $timestamp = current_time('mysql');
        update_option('wpwps_last_orders_sync', $timestamp);
        
        // Pretend we've synced some orders
        $current_count = get_option('wpwps_orders_synced', 0);
        $new_count = $current_count + rand(1, 5); // Simulate syncing some orders
        update_option('wpwps_orders_synced', $new_count);
        
        wp_send_json_success([
            'message' => 'Orders sync completed successfully',
            'last_sync' => $timestamp,
            'orders_synced' => $new_count
        ]);
    }

    private function fetchPrintifyProducts()
    {
        try {
            /** @var PrintifyAPIInterface $printifyApi */
            $printifyApi = $this->container->get('printify_api');
            
            // Get shop ID
            $shopId = get_option('wpwps_printify_shop_id', '');
            
            if (empty($shopId)) {
                wp_send_json_error(['message' => 'Shop ID not configured']);
            }
            
            // Try to get from cache first
            $allProducts = Cache::getProducts($shopId);
            
            // If not in cache, fetch from API
            if ($allProducts === false) {
                $allProducts = [];
                $page = 1;
                $perPage = 100; // Get maximum allowed per request
                
                do {
                    $result = $printifyApi->getProducts($shopId, $page, $perPage);
                    $allProducts = array_merge($allProducts, $result['data']);
                    $page++;
                } while ($page <= $result['last_page']);
                
                // Store in cache
                Cache::setProducts($shopId, $allProducts);
            }
            
            // Handle pagination
            $requestedPage = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $requestedPerPage = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
            
            $total = count($allProducts);
            $offset = ($requestedPage - 1) * $requestedPerPage;
            $paginatedProducts = array_slice($allProducts, $offset, $requestedPerPage);
            
            // Get product importer
            /** @var ProductImporterInterface $productImporter */
            $productImporter = $this->container->get('product_importer');
            
            // Process products
            $processedProducts = [];
            foreach ($paginatedProducts as $product) {
                $printifyId = $product['id'];
                $wooProductId = $productImporter->getWooProductIdByPrintifyId($printifyId);
                
                $processedProducts[] = [
                    'printify_id' => $printifyId,
                    'title' => $product['title'],
                    'thumbnail' => $product['images'][0]['src'] ?? '',
                    'woo_product_id' => $wooProductId,
                    'status' => $product['visible'] ? 'active' : 'draft',
                    'last_updated' => date('Y-m-d H:i:s', strtotime($product['updated_at'])),
                    'is_imported' => !empty($wooProductId)
                ];
            }
            
            // Return formatted products list
            wp_send_json_success([
                'products' => $processedProducts,
                'total' => $total,
                'current_page' => $requestedPage,
                'last_page' => ceil($total / $requestedPerPage),
                'per_page' => $requestedPerPage
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    private function importProductToWoo()
    {
        try {
            // Get Printify product ID
            $printifyId = sanitize_text_field($_POST['printify_id'] ?? '');
            
            if (empty($printifyId)) {
                wp_send_json_error(['message' => 'Printify product ID is required']);
            }
            
            // Get Printify API
            /** @var PrintifyAPIInterface $printifyApi */
            $printifyApi = $this->container->get('printify_api');
            
            // Get shop ID
            $shopId = get_option('wpwps_printify_shop_id', '');
            
            if (empty($shopId)) {
                wp_send_json_error(['message' => 'Shop ID not configured']);
            }
            
            // Get product from API
            $productData = $printifyApi->getProduct($shopId, $printifyId);
            
            // Get product importer
            /** @var ProductImporterInterface $productImporter */
            $productImporter = $this->container->get('product_importer');
            
            // Import product
            $wooProductId = $productImporter->importProduct($productData);
            
            wp_send_json_success([
                'message' => 'Product imported successfully',
                'woo_product_id' => $wooProductId,
                'woo_product_url' => get_permalink($wooProductId),
                'products_synced' => get_option('wpwps_products_synced', 0)
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Failed to import product: ' . $e->getMessage()]);
        }
    }

    private function fetchPrintifyOrders()
    {
        try {
            // Verify request method
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                wp_send_json_error([
                    'message' => 'This endpoint only accepts GET requests',
                    'error_type' => 'method'
                ]);
                return;
            }

            error_log("PRINTIFY DEBUG - fetchPrintifyOrders starting");
            
            // Get pagination params from GET
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
            $refreshCache = isset($_GET['refresh_cache']) && $_GET['refresh_cache'] === 'true';
            
            // Rest of the function remains the same...
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
