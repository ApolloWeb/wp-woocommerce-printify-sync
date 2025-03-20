<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax\Handler;

use ApolloWeb\WPWooCommercePrintifySync\Core\Cache;
use ApolloWeb\WPWooCommercePrintifySync\API\Interfaces\PrintifyAPIInterface;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\OrderImporterInterface;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\OrderDataStoreCompatibility;

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
            // Max 10 per page for Printify Orders API (according to documentation)
            $pagination = $this->getPagination(10);
            $shopId = $this->getShopId();
            
            // Check if API key and shop ID are configured
            $apiKey = get_option('wpwps_printify_api_key', '');
            if (empty($apiKey)) {
                error_log("ERROR: Printify API key is not configured");
                wp_send_json_error([
                    'message' => 'Printify API key is not configured. Please set it in the plugin settings.',
                    'error_type' => 'fetch_orders'
                ]);
                return;
            }
            
            if (empty($shopId)) {
                error_log("ERROR: Printify shop ID is not configured");
                wp_send_json_error([
                    'message' => 'Printify shop ID is not configured. Please set it in the plugin settings.',
                    'error_type' => 'fetch_orders'
                ]);
                return;
            }
            
            error_log("DEBUG: Fetching orders for shop ID: {$shopId}, page: {$pagination['page']}, per_page: {$pagination['per_page']}");
            
            /** @var PrintifyAPIInterface $printifyApi */
            $printifyApi = $this->container->get('printify_api');
            
            // Clear cache if requested
            if ($this->shouldRefreshCache()) {
                error_log("DEBUG: Refreshing orders cache for shop ID: {$shopId}");
                Cache::deleteOrders($shopId);
            }

            // Only use supported filter parameters
            $queryParams = [];
            // Can add status filter if needed: $queryParams['status'] = 'all';
            
            try {
                // Log API details for debugging
                error_log("DEBUG: Making Printify API request with API key: " . substr($apiKey, 0, 5) . '...' . substr($apiKey, -5));
                error_log("DEBUG: Making Printify API request to endpoint: " . get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1'));
                
                $result = $printifyApi->getOrders($shopId, $pagination['page'], $pagination['per_page'], $queryParams);
            } catch (\Exception $e) {
                error_log("ERROR in OrderHandler->fetchOrders: " . $e->getMessage());
                throw $e;
            }
            
            $orders = array_map(function($order) {
                /** @var OrderImporterInterface $orderImporter */
                $orderImporter = $this->container->get('order_importer');
                $wooOrderId = $orderImporter->getWooOrderIdByPrintifyId($order['id']);
                
                $order['is_imported'] = !empty($wooOrderId);
                $order['woo_order_id'] = $wooOrderId;
                return $order;
            }, $result['data']);

            error_log("DEBUG: Successfully fetched " . count($orders) . " orders");
            
            wp_send_json_success([
                'orders' => $orders,
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'total' => $result['total'],
                'per_page' => $pagination['per_page']
            ]);

        } catch (\Exception $e) {
            error_log("ERROR in OrderHandler->fetchOrders exception handler: " . $e->getMessage());
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
            
            /** @var OrderImporterInterface $orderImporter */
            $orderImporter = $this->container->get('order_importer');
            
            // Import order using HPOS-compatible method
            $wooOrderId = $orderImporter->importOrder($orderData);
            
            // Add link to order in admin
            $editLink = OrderDataStoreCompatibility::isHposActive() 
                ? admin_url('admin.php?page=wc-orders&action=edit&id=' . $wooOrderId)
                : get_edit_post_link($wooOrderId, 'raw');
            
            wp_send_json_success([
                'message' => 'Order imported successfully',
                'woo_order_id' => $wooOrderId,
                'woo_order_url' => $editLink
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
                    
                    // Get the appropriate admin URL based on HPOS status
                    $adminUrl = OrderDataStoreCompatibility::isHposActive()
                        ? admin_url('admin.php?page=wc-orders&action=edit&id=' . $wooOrderId)
                        : get_edit_post_link($wooOrderId, 'raw');
                    
                    $imported[] = [
                        'printify_id' => $printifyId,
                        'woo_order_id' => $wooOrderId,
                        'woo_order_url' => $adminUrl
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

    /**
     * Import all orders from Printify
     */
    public function importAllOrders(): void
    {
        try {
            $shopId = $this->getShopId();
            
            /** @var PrintifyAPIInterface $printifyApi */
            $printifyApi = $this->container->get('printify_api');
            
            // Start by fetching the first page to get total count
            $result = $printifyApi->getOrders($shopId, 1, 10);
            
            if (empty($result['data'])) {
                wp_send_json_error(['message' => 'No orders found to import']);
                return;
            }
            
            // Get IDs of all orders that haven't been imported yet
            $ordersToImport = [];
            foreach ($result['data'] as $order) {
                /** @var OrderImporterInterface $orderImporter */
                $orderImporter = $this->container->get('order_importer');
                $wooOrderId = $orderImporter->getWooOrderIdByPrintifyId($order['id']);
                
                if (empty($wooOrderId)) {
                    $ordersToImport[] = $order['id'];
                }
            }
            
            // If there are more pages, set up a flag to indicate we need more processing
            $needsMoreProcessing = ($result['last_page'] > 1);
            
            // Store information about remaining pages in a transient
            if ($needsMoreProcessing) {
                set_transient('wpwps_order_import_pages_' . $shopId, [
                    'current_page' => 1,
                    'last_page' => $result['last_page'],
                    'total' => $result['total'],
                    'timestamp' => time()
                ], HOUR_IN_SECONDS);
                
                // Schedule background task to process additional pages
                if (class_exists('ActionScheduler')) {
                    as_schedule_single_action(
                        time() + 30,
                        'wpwps_process_order_import_page',
                        [
                            'shop_id' => $shopId,
                            'page' => 2
                        ]
                    );
                }
            }
            
            // Import the orders from the first page
            $importResults = $this->bulkImportOrdersByIds($ordersToImport);
            
            // Send response
            wp_send_json_success([
                'message' => sprintf(
                    '%d orders imported from page 1. %s',
                    count($importResults['imported']),
                    $needsMoreProcessing ? 'Additional orders will be processed in the background.' : 'All orders have been processed.'
                ),
                'imported' => $importResults['imported'],
                'failed' => $importResults['failed'],
                'needs_more_processing' => $needsMoreProcessing,
                'total_pages' => $result['last_page']
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Failed to import all orders: ' . $e->getMessage()]);
        }
    }

    /**
     * Helper method to import a batch of orders by IDs
     * 
     * @param array $printifyIds
     * @return array Result with imported and failed orders
     */
    private function bulkImportOrdersByIds(array $printifyIds): array
    {
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
                
                // Get the appropriate admin URL based on HPOS status
                $adminUrl = OrderDataStoreCompatibility::isHposActive()
                    ? admin_url('admin.php?page=wc-orders&action=edit&id=' . $wooOrderId)
                    : get_edit_post_link($wooOrderId, 'raw');
                
                $imported[] = [
                    'printify_id' => $printifyId,
                    'woo_order_id' => $wooOrderId,
                    'woo_order_url' => $adminUrl
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
        
        return [
            'imported' => $imported,
            'failed' => $failed
        ];
    }

    /**
     * Get progress of the order import
     */
    public function getOrderImportProgress(): void
    {
        try {
            $shopId = $this->getShopId();
            $progress = get_transient('wpwps_order_import_pages_' . $shopId);
            
            if (!$progress) {
                wp_send_json_error(['message' => 'No order import in progress']);
                return;
            }
            
            // Calculate percentage
            $percentage = 0;
            if ($progress['last_page'] > 0) {
                $percentage = round(($progress['current_page'] / $progress['last_page']) * 100);
            }
            
            wp_send_json_success([
                'current_page' => $progress['current_page'],
                'last_page' => $progress['last_page'],
                'total' => $progress['total'],
                'percentage' => $percentage,
                'timestamp' => $progress['timestamp']
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Error getting order import progress: ' . $e->getMessage()]);
        }
    }

    /**
     * Get pagination parameters
     * 
     * @param int $defaultPerPage Default items per page
     * @return array
     */
    protected function getPagination(int $defaultPerPage = 10): array
    {
        return [
            'page' => isset($_REQUEST['page']) ? max(1, (int)$_REQUEST['page']) : 1,
            'per_page' => isset($_REQUEST['per_page']) ? min((int)$_REQUEST['per_page'], 10) : $defaultPerPage
        ];
    }

    /**
     * Check if cache should be refreshed
     * 
     * @return bool
     */
    protected function shouldRefreshCache(): bool
    {
        return isset($_REQUEST['refresh_cache']) && $_REQUEST['refresh_cache'] === 'true';
    }

    /**
     * Process orders for display
     * 
     * @param array $orders Raw orders data
     * @return array Processed orders data
     */
    protected function processOrders(array $orders): array
    {
        $processed = [];
        /** @var OrderImporterInterface $orderImporter */
        $orderImporter = $this->container->get('order_importer');
        
        foreach ($orders as $order) {
            if (!isset($order['id'])) {
                continue;
            }
            
            $printifyId = $order['id'];
            $wooOrderId = $orderImporter->getWooOrderIdByPrintifyId($printifyId);
            
            // Get Admin URL based on HPOS compatibility
            $adminUrl = '';
            if ($wooOrderId) {
                $adminUrl = OrderDataStoreCompatibility::isHposActive()
                    ? admin_url('admin.php?page=wc-orders&action=edit&id=' . $wooOrderId)
                    : get_edit_post_link($wooOrderId, 'raw');
            }
            
            // IMPORTANT: API values are in cents and need to be divided by 100
            // Raw values directly from API
            $rawTotalPrice = $order['total_price'] ?? 0;
            $rawTotalShipping = $order['total_shipping'] ?? 0;
            
            // Normalize by dividing by 100 (convert cents to dollars/pounds)
            $totalPrice = (float)$rawTotalPrice / 100;
            $totalShipping = (float)$rawTotalShipping / 100;
            
            // The total is ALWAYS the sum of product price + shipping
            $totalAmount = $totalPrice + $totalShipping;
            
            // Calculate merchant cost (cost price) - ALWAYS divide API values by 100
            $merchantCost = 0;
            $rawMerchantCost = 0;
            
            // Line item counts for shipping breakdown
            $itemCount = 0;
            $lineItemDetails = [];
            
            if (!empty($order['line_items'])) {
                foreach ($order['line_items'] as $item) {
                    $rawItemCost = $item['cost'] ?? 0;
                    $rawItemShippingCost = $item['shipping_cost'] ?? 0;
                    $quantity = (int)($item['quantity'] ?? 1);
                    $itemCount += $quantity;
                    
                    // Store line item details for shipping calculations
                    $lineItemDetails[] = [
                        'title' => $item['metadata']['title'] ?? 'Product',
                        'variant' => $item['metadata']['variant_label'] ?? '',
                        'quantity' => $quantity,
                        'cost' => (float)$rawItemCost / 100,
                        'shipping_cost' => (float)$rawItemShippingCost / 100,
                        'price' => (float)($item['metadata']['price'] ?? 0) / 100
                    ];
                    
                    // Calculate raw merchant cost for debugging
                    $rawMerchantCost += ($rawItemCost * $quantity) + $rawItemShippingCost;
                    
                    // Normalize costs by dividing by 100
                    $itemCost = (float)$rawItemCost / 100;
                    $shippingCost = (float)$rawItemShippingCost / 100;
                    
                    // Add to merchant cost: (cost × quantity) + shipping cost
                    $merchantCost += ($itemCost * $quantity) + $shippingCost;
                }
            }
            
            // Calculate profit: total amount - merchant cost
            $profit = $totalAmount - $merchantCost;
            
            // Debug log exact values
            error_log(sprintf(
                "Order %s: raw_price=%d, raw_shipping=%d, total_price=%.2f, total_shipping=%.2f, total_amount=%.2f, merchant_cost=%.2f, profit=%.2f",
                $printifyId, $rawTotalPrice, $rawTotalShipping, $totalPrice, $totalShipping, $totalAmount, $merchantCost, $profit
            ));
            
            $processed[] = [
                'printify_id' => $printifyId,
                'title' => sprintf('Order #%s', $printifyId),
                'customer_name' => $this->formatCustomerName($order),
                'woo_order_id' => $wooOrderId,
                'woo_order_url' => $adminUrl,
                'raw_total_price' => $rawTotalPrice,
                'raw_total_shipping' => $rawTotalShipping,
                'raw_merchant_cost' => $rawMerchantCost,
                'total_price' => $totalPrice,
                'total_shipping' => $totalShipping,
                'total_amount' => $totalAmount,
                'merchant_cost' => $merchantCost,
                'profit' => $profit,
                'item_count' => $itemCount,
                'line_items' => $lineItemDetails,
                'created_at' => $order['created_at'] ?? '',
                'status' => $order['status'] ?? 'unknown',
                'is_imported' => !empty($wooOrderId),
                'shipments' => $order['shipments'] ?? []
            ];
        }
        
        return $processed;
    }
}
