<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core\ActionScheduler;

use ApolloWeb\WPWooCommercePrintifySync\API\Interfaces\PrintifyAPIInterface;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\OrderImporterInterface;

class OrderImportTask
{
    const ACTION_HOOK = 'wpwps_process_order_import_page';
    
    /**
     * Register the action handlers
     *
     * @return void
     */
    public static function register(): void
    {
        add_action(self::ACTION_HOOK, [self::class, 'processOrderImportPage'], 10, 2);
    }
    
    /**
     * Process one page of order imports
     *
     * @param string $shopId
     * @param int $page
     * @return void
     */
    public static function processOrderImportPage(string $shopId, int $page): void
    {
        global $wpwps_plugin;
        if (!isset($wpwps_plugin) || !method_exists($wpwps_plugin, 'getContainer')) {
            error_log('Cannot process order import: Plugin container not available');
            return;
        }
        
        $container = $wpwps_plugin->getContainer();
        
        if (!$container->has('printify_api') || !$container->has('order_importer')) {
            error_log('Cannot process order import: Required services not available');
            return;
        }
        
        /** @var PrintifyAPIInterface $printifyApi */
        $printifyApi = $container->get('printify_api');
        
        /** @var OrderImporterInterface $orderImporter */
        $orderImporter = $container->get('order_importer');
        
        try {
            // Get import progress
            $progress = get_transient('wpwps_order_import_pages_' . $shopId);
            if (!$progress) {
                error_log('Order import progress not found for shop: ' . $shopId);
                return;
            }
            
            // Fetch the current page of orders
            $result = $printifyApi->getOrders($shopId, $page, 10);
            
            if (empty($result['data'])) {
                // No orders on this page, we're done
                self::completeOrderImport($shopId);
                return;
            }
            
            // Find orders to import
            $ordersToImport = [];
            foreach ($result['data'] as $order) {
                $wooOrderId = $orderImporter->getWooOrderIdByPrintifyId($order['id']);
                if (empty($wooOrderId)) {
                    $ordersToImport[] = $order['id'];
                }
            }
            
            // Import the orders
            if (!empty($ordersToImport)) {
                self::bulkImportOrdersByIds($ordersToImport, $shopId, $printifyApi, $orderImporter);
            }
            
            // Update progress
            $progress['current_page'] = $page;
            set_transient('wpwps_order_import_pages_' . $shopId, $progress, HOUR_IN_SECONDS);
            
            // If there are more pages, schedule the next one
            if ($page < $result['last_page']) {
                as_schedule_single_action(
                    time() + 30, // 30 seconds delay to prevent rate limiting
                    self::ACTION_HOOK,
                    [
                        'shop_id' => $shopId,
                        'page' => $page + 1
                    ]
                );
            } else {
                // We're done with all pages
                self::completeOrderImport($shopId);
            }
        } catch (\Exception $e) {
            error_log('Error in OrderImportTask: ' . $e->getMessage());
        }
    }
    
    /**
     * Helper method to import a batch of orders by IDs
     * 
     * @param array $printifyIds
     * @param string $shopId
     * @param PrintifyAPIInterface $printifyApi
     * @param OrderImporterInterface $orderImporter
     * @return array Result with imported and failed orders
     */
    private static function bulkImportOrdersByIds(
        array $printifyIds, 
        string $shopId, 
        PrintifyAPIInterface $printifyApi, 
        OrderImporterInterface $orderImporter
    ): array {
        $imported = [];
        $failed = [];
        
        foreach ($printifyIds as $printifyId) {
            try {
                $orderData = $printifyApi->getOrder($shopId, $printifyId);
                $wooOrderId = $orderImporter->importOrder($orderData);
                $imported[] = $printifyId;
            } catch (\Exception $e) {
                error_log('Failed to import order ' . $printifyId . ': ' . $e->getMessage());
                $failed[] = $printifyId;
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
     * Mark the order import as complete
     *
     * @param string $shopId
     */
    private static function completeOrderImport(string $shopId): void
    {
        // Remove the progress transient
        delete_transient('wpwps_order_import_pages_' . $shopId);
        
        // Add a completion transient
        set_transient('wpwps_order_import_completed_' . $shopId, [
            'timestamp' => time(),
            'message' => 'All orders have been imported'
        ], HOUR_IN_SECONDS);
        
        // Trigger action for other plugins to hook into
        do_action('wpwps_order_import_completed', $shopId);
    }
}
