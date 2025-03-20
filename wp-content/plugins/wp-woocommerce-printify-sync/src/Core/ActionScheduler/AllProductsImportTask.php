<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core\ActionScheduler;

use ApolloWeb\WPWooCommercePrintifySync\API\Interfaces\PrintifyAPIInterface;

class AllProductsImportTask
{
    const ACTION_HOOK = 'wpwps_process_all_products_import';
    
    /**
     * Schedule the all products import task
     *
     * @param string $shopId
     * @return void
     */
    public function schedule(string $shopId): void
    {
        if (!class_exists('ActionScheduler')) {
            require_once(WP_PLUGIN_DIR . '/woocommerce/includes/libraries/action-scheduler/action-scheduler.php');
        }
        
        // Schedule the task to run in 10 seconds
        as_schedule_single_action(
            time() + 10, 
            self::ACTION_HOOK, 
            [
                'shop_id' => $shopId,
                'page' => 1
            ]
        );
    }
    
    /**
     * Process one page of products
     *
     * @param string $shopId
     * @param int $page
     * @return void
     */
    public function process(string $shopId, int $page): void
    {
        $container = null;
        
        // Get the container instance from the main plugin
        global $wpwps_plugin;
        if (isset($wpwps_plugin) && method_exists($wpwps_plugin, 'getContainer')) {
            $container = $wpwps_plugin->getContainer();
        }
        
        if (!$container || !$container->has('printify_api')) {
            error_log('Cannot process all products import: Container or API not available');
            return;
        }
        
        /** @var PrintifyAPIInterface $printifyApi */
        $printifyApi = $container->get('printify_api');
        
        try {
            // Fetch a page of products
            $result = $printifyApi->getProducts($shopId, $page, 50);
            
            // If there are products to import, schedule them for batch import
            if (!empty($result['data'])) {
                do_action('wpwps_start_product_import', $result['data'], $shopId);
                
                // Update the progress
                $this->updateProgress($shopId, $page, $result['total'], count($result['data']));
                
                // If there are more pages, schedule the next page
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
                    // All pages have been scheduled, mark as completed
                    $this->completeImport($shopId);
                }
            } else {
                // No products found, mark as completed
                $this->completeImport($shopId);
            }
        } catch (\Exception $e) {
            error_log('Error in All Products Import Task: ' . $e->getMessage());
            
            // Store the error in a transient
            set_transient('wpwps_all_products_import_error_' . $shopId, [
                'message' => $e->getMessage(),
                'timestamp' => time()
            ], HOUR_IN_SECONDS);
        }
    }
    
    /**
     * Mark the import as complete
     *
     * @param string $shopId
     */
    private function completeImport(string $shopId): void
    {
        // Add a flag indicating completion
        set_transient('wpwps_all_products_import_completed_' . $shopId, [
            'timestamp' => time(),
            'message' => 'All products import process completed successfully'
        ], HOUR_IN_SECONDS);
        
        // Trigger action for other plugins to hook into
        do_action('wpwps_all_products_import_completed', $shopId);
    }
    
    /**
     * Update import progress
     *
     * @param string $shopId
     * @param int $page
     * @param int $total
     * @param int $currentPageCount
     */
    private function updateProgress(string $shopId, int $page, int $total, int $currentPageCount): void
    {
        $progressKey = 'wpwps_all_products_import_progress_' . $shopId;
        $progress = get_transient($progressKey) ?: [
            'total' => $total,
            'scheduled' => 0,
            'current_page' => 1,
            'timestamp' => time()
        ];
        
        $progress['scheduled'] += $currentPageCount;
        $progress['current_page'] = $page;
        $progress['percentage'] = round(($page / ceil($total / 50)) * 100);
        $progress['last_updated'] = time();
        
        set_transient($progressKey, $progress, HOUR_IN_SECONDS);
    }
    
    /**
     * Register the action handlers
     *
     * @return void
     */
    public static function register(): void
    {
        add_action(self::ACTION_HOOK, function($args) {
            $task = new self();
            $task->process($args['shop_id'], $args['page']);
        });
    }
}
