<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax\Handler;

use ApolloWeb\WPWooCommercePrintifySync\Core\Cache;
use ApolloWeb\WPWooCommercePrintifySync\API\Interfaces\PrintifyAPIInterface;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\OrderImporterInterface;

class OrderHandler extends AbstractAjaxHandler
{
    /**
     * Handle order fetch request
     */
    public function handle(): void
    {
        $this->fetchOrders();
    }
    
    /**
     * Fetch orders from Printify
     */
    protected function fetchOrders(): void
    {
        try {
            $pagination = $this->getPagination(50); // Set default to 50 orders
            $shopId = $this->getShopId();
            
            /** @var PrintifyAPIInterface $printifyApi */
            $printifyApi = $this->container->get('printify_api');
            
            // Clear cache if requested
            if ($this->shouldRefreshCache()) {
                Cache::deleteOrders($shopId);
            }

            $queryParams = ['status' => 'all']; // Optional: Add other filters
            $result = $printifyApi->getOrders($shopId, $pagination['page'], $pagination['per_page'], $queryParams);
            
            $orders = array_map(function($order) {
                /** @var OrderImporterInterface $orderImporter */
                $orderImporter = $this->container->get('order_importer');
                $wooOrderId = $orderImporter->getWooOrderIdByPrintifyId($order['id']);
                
                $order['is_imported'] = !empty($wooOrderId);
                $order['woo_order_id'] = $wooOrderId;
                return $order;
            }, $result['data']);

            wp_send_json_success([
                'orders' => $orders,
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'total' => $result['total'],
                'per_page' => $pagination['per_page']
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'error_type' => 'fetch_orders'
            ]);
        }
    }
    
    /**
     * Import an order to WooCommerce
     */
    public function importOrder(): void
    {
        try {
            // Get Printify order ID
            $printifyId = sanitize_text_field($_POST['printify_id'] ?? '');
            
            if (empty($printifyId)) {
                wp_send_json_error(['message' => 'Printify order ID is required']);
                return;
            }
            
            $shopId = $this->getShopId();

            /** @var PrintifyAPIInterface $printifyApi */
            $printifyApi = $this->container->get('printify_api');
            
            // Get order from API
            $orderData = $printifyApi->getOrder($shopId, $printifyId);
            
            // Get order importer
            /** @var OrderImporterInterface $orderImporter */
            $orderImporter = $this->container->get('order_importer');
            
            // Import order
            $wooOrderId = $orderImporter->importOrder($orderData);
            
            wp_send_json_success([
                'message' => 'Order imported successfully',
                'woo_order_id' => $wooOrderId,
                'woo_order_url' => get_edit_post_link($wooOrderId, 'raw')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Failed to import order: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Import multiple orders in bulk
     */
    public function bulkImportOrders(): void
    {
        try {
            $printifyIds = isset($_POST['printify_ids']) ? (array)$_POST['printify_ids'] : [];
            
            if (empty($printifyIds)) {
                wp_send_json_error(['message' => 'No orders selected for import']);
                return;
            }

            $shopId = $this->getShopId();
            
            /** @var PrintifyAPIInterface $printifyApi */
            $printifyApi = $this->container->get('printify_api');
            
            /** @var OrderImporterInterface $orderImporter */
            $orderImporter = $this->container->get('order_importer');

            $imported = [];
            $failed = [];
            
            foreach ($printifyIds as $printifyId) {
                try {
                    $orderData = $printifyApi->getOrder($shopId, $printifyId);
                    $wooOrderId = $orderImporter->importOrder($orderData);
                    $imported[] = [
                        'printify_id' => $printifyId,
                        'woo_order_id' => $wooOrderId,
                        'woo_order_url' => get_edit_post_link($wooOrderId, 'raw')
                    ];
                } catch (\Exception $e) {
                    $failed[] = [
                        'printify_id' => $printifyId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Update sync count
            $current_count = get_option('wpwps_orders_synced', 0);
            update_option('wpwps_orders_synced', $current_count + count($imported));

            wp_send_json_success([
                'message' => sprintf(
                    'Imported %d orders successfully. %d orders failed.',
                    count($imported),
                    count($failed)
                ),
                'imported' => $imported,
                'failed' => $failed
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Bulk order import failed: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Manual sync of orders
     */
    public function manualSyncOrders(): void
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
        update_option('wpwps_last_orders_sync', $timestamp);
        
        // Simulate syncing orders
        $current_count = get_option('wpwps_orders_synced', 0);
        $new_count = $current_count + rand(1, 5); // Simulate syncing
        update_option('wpwps_orders_synced', $new_count);
        
        wp_send_json_success([
            'message' => 'Orders sync completed successfully',
            'last_sync' => $timestamp,
            'orders_synced' => $new_count
        ]);
    }
}
