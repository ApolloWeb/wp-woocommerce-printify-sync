<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax\Handler;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyHttpClient;

class DebugHandler extends AbstractAjaxHandler
{
    /**
     * Make a direct request to the Printify API Orders endpoint
     */
    public function directOrdersRequest(): void
    {
        // Only allow admins to use this
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }

        try {
            $page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
            $limit = isset($_POST['limit']) ? min((int)$_POST['limit'], 10) : 10;
            
            $shopId = get_option('wpwps_printify_shop_id', '');
            $apiKey = get_option('wpwps_printify_api_key', '');
            $endpoint = get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1');
            
            if (empty($shopId) || empty($apiKey) || empty($endpoint)) {
                wp_send_json_error(['message' => 'API configuration is incomplete']);
                return;
            }
            
            // Create a direct client
            $client = new PrintifyHttpClient($apiKey, $endpoint);
            
            // Make request
            $url = "shops/{$shopId}/orders.json";
            $params = [
                'page' => $page,
                'limit' => $limit
            ];
            
            error_log("DEBUG: Making direct orders request to {$url} with params: " . json_encode($params));
            
            $result = $client->request($url, 'GET', $params);
            
            wp_send_json_success($result);
            
        } catch (\Exception $e) {
            error_log("ERROR in DebugHandler->directOrdersRequest: " . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Clear all Printify-imported data from WooCommerce
     */
    public function clearAllData(): void
    {
        try {
            // Add more logging for troubleshooting
            error_log('==== CLEAR ALL DATA HANDLER CALLED ====');
            error_log('POST data: ' . print_r($_POST, true));
            
            $shopId = get_option('wpwps_printify_shop_id', '');
            
            if (empty($shopId)) {
                error_log('No Printify shop configured');
                wp_send_json_error(['message' => 'No Printify shop configured']);
                return;
            }
            
            error_log('Clearing cache for shop: ' . $shopId);
            
            // Clear cache
            \ApolloWeb\WPWooCommercePrintifySync\Core\Cache::deleteProducts($shopId);
            \ApolloWeb\WPWooCommercePrintifySync\Core\Cache::deleteOrders($shopId);
            
            error_log('Getting service objects');
            
            // Get service objects
            /** @var \ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\ProductImporterInterface $productImporter */
            $productImporter = $this->container->get('product_importer');
            
            /** @var \ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\OrderImporterInterface $orderImporter */
            $orderImporter = $this->container->get('order_importer');
            
            error_log('Deleting products and orders');
            
            // Delete all Printify products from WooCommerce
            $deletedProducts = $productImporter->deleteAllPrintifyProducts();
            
            // Delete all Printify orders from WooCommerce
            $deletedOrders = $orderImporter->deleteAllPrintifyOrders();
            
            error_log("Deleted {$deletedProducts} products and {$deletedOrders} orders");
            
            // Reset counters
            update_option('wpwps_products_synced', 0);
            update_option('wpwps_orders_synced', 0);
            
            error_log('Sending success response');
            
            wp_send_json_success([
                'message' => sprintf('Successfully deleted %d products and %d orders', $deletedProducts, $deletedOrders),
                'deleted_products' => $deletedProducts,
                'deleted_orders' => $deletedOrders
            ]);
            
        } catch (\Exception $e) {
            error_log('Error in clearAllData: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => 'Failed to clear data: ' . $e->getMessage()]);
        }
    }

    /**
     * Simple test endpoint to verify AJAX functionality
     */
    public function testAjax(): void
    {
        try {
            error_log('TEST AJAX ENDPOINT CALLED');
            wp_send_json_success([
                'message' => 'AJAX is working correctly',
                'time' => current_time('mysql'),
                'request_data' => $_REQUEST
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Test failed: ' . $e->getMessage()]);
        }
    }
}
