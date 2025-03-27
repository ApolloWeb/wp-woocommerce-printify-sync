<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\BaseServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyClient;

class SyncServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     * 
     * @return void
     */
    public function register(): void
    {
        // Register cron schedules
        add_filter('cron_schedules', [$this, 'addCronSchedules']);
        
        // Register cron hooks
        add_action('wpwps_sync_products_hook', [$this, 'scheduledProductSync']);
        add_action('wpwps_sync_inventory_hook', [$this, 'scheduledInventorySync']);
        add_action('wpwps_sync_orders_hook', [$this, 'scheduledOrderSync']);
        add_action('wpwps_cleanup_logs_hook', [$this, 'scheduledCleanupLogs']);
        
        // Activation and deactivation of scheduled tasks will be handled in bootActivation and bootDeactivation
    }
    
    /**
     * Bootstrap any application services when the plugin is activated.
     * 
     * @return void
     */
    public function bootActivation(): void
    {
        // Schedule cron jobs based on settings
        $this->scheduleTasks();
    }
    
    /**
     * Bootstrap any application services when the plugin is deactivated.
     * 
     * @return void
     */
    public function bootDeactivation(): void
    {
        // Clear scheduled hooks
        $this->clearScheduledTasks();
    }
    
    /**
     * Add custom cron schedules.
     * 
     * @param array $schedules
     * @return array
     */
    public function addCronSchedules(array $schedules): array
    {
        // Add custom intervals
        $schedules['every_fifteen_minutes'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __('Every 15 Minutes', 'wp-woocommerce-printify-sync'),
        ];
        
        $schedules['every_thirty_minutes'] = [
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display' => __('Every 30 Minutes', 'wp-woocommerce-printify-sync'),
        ];
        
        return $schedules;
    }
    
    /**
     * Schedule tasks based on plugin settings.
     * 
     * @return void
     */
    public function scheduleTasks(): void
    {
        $settings = get_option('wpwps_settings', []);
        $autoSync = isset($settings['auto_sync']) ? (bool)$settings['auto_sync'] : false;
        
        if (!$autoSync) {
            return;
        }
        
        $syncInterval = isset($settings['sync_interval']) ? $settings['sync_interval'] : 'hourly';
        
        // Products sync - less frequent
        if (!wp_next_scheduled('wpwps_sync_products_hook')) {
            wp_schedule_event(time(), $syncInterval, 'wpwps_sync_products_hook');
        }
        
        // Inventory sync - more frequent
        if (!wp_next_scheduled('wpwps_sync_inventory_hook')) {
            // Choose a more frequent schedule for inventory
            $inventoryInterval = $syncInterval == 'daily' ? 'twicedaily' : 'every_thirty_minutes';
            wp_schedule_event(time(), $inventoryInterval, 'wpwps_sync_inventory_hook');
        }
        
        // Orders sync - more frequent
        if (!wp_next_scheduled('wpwps_sync_orders_hook')) {
            // Choose a more frequent schedule for orders
            $ordersInterval = $syncInterval == 'daily' ? 'twicedaily' : 'every_thirty_minutes';
            wp_schedule_event(time(), $ordersInterval, 'wpwps_sync_orders_hook');
        }
        
        // Log cleanup - daily
        if (!wp_next_scheduled('wpwps_cleanup_logs_hook')) {
            wp_schedule_event(time(), 'daily', 'wpwps_cleanup_logs_hook');
        }
    }
    
    /**
     * Clear all scheduled tasks.
     * 
     * @return void
     */
    public function clearScheduledTasks(): void
    {
        $hooks = [
            'wpwps_sync_products_hook',
            'wpwps_sync_inventory_hook',
            'wpwps_sync_orders_hook',
            'wpwps_cleanup_logs_hook',
        ];
        
        foreach ($hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }
    
    /**
     * Scheduled task to sync products.
     * 
     * @return void
     */
    public function scheduledProductSync(): void
    {
        $settings = get_option('wpwps_settings', []);
        $syncProducts = isset($settings['sync_products']) ? (bool)$settings['sync_products'] : true;
        
        if (!$syncProducts) {
            return;
        }
        
        try {
            $api = $this->getPrintifyClient();
            
            // Log start of sync
            $this->logSync('product', 'Scheduled product sync started', 'info');
            
            // Get all products from Printify and sync them
            $page = 1;
            $limit = 20; // Process in batches
            $totalSynced = 0;
            
            do {
                $result = $api->syncProducts($page, $limit);
                $totalSynced += count($result['products'] ?? []);
                $morePagesExist = $result['pagination']['current_page'] < $result['pagination']['total_pages'];
                $page++;
            } while ($morePagesExist);
            
            // Log completion
            $this->logSync('product', sprintf(
                __('Scheduled product sync completed. %d products synced.', 'wp-woocommerce-printify-sync'),
                $totalSynced
            ), 'success');
            
        } catch (\Exception $e) {
            $this->logSync('product', sprintf(
                __('Scheduled product sync failed: %s', 'wp-woocommerce-printify-sync'),
                $e->getMessage()
            ), 'error');
        }
    }
    
    /**
     * Scheduled task to sync inventory.
     * 
     * @return void
     */
    public function scheduledInventorySync(): void
    {
        $settings = get_option('wpwps_settings', []);
        $syncInventory = isset($settings['sync_inventory']) ? (bool)$settings['sync_inventory'] : true;
        
        if (!$syncInventory) {
            return;
        }
        
        try {
            $api = $this->getPrintifyClient();
            
            // Log start of sync
            $this->logSync('inventory', 'Scheduled inventory sync started', 'info');
            
            // Sync inventory
            $result = $api->syncInventory();
            
            // Log completion
            $this->logSync('inventory', sprintf(
                __('Scheduled inventory sync completed. %d products updated.', 'wp-woocommerce-printify-sync'),
                $result['count']
            ), 'success');
            
        } catch (\Exception $e) {
            $this->logSync('inventory', sprintf(
                __('Scheduled inventory sync failed: %s', 'wp-woocommerce-printify-sync'),
                $e->getMessage()
            ), 'error');
        }
    }
    
    /**
     * Scheduled task to sync orders.
     * 
     * @return void
     */
    public function scheduledOrderSync(): void
    {
        $settings = get_option('wpwps_settings', []);
        $syncOrders = isset($settings['sync_orders']) ? (bool)$settings['sync_orders'] : true;
        
        if (!$syncOrders) {
            return;
        }
        
        try {
            $api = $this->getPrintifyClient();
            
            // Log start of sync
            $this->logSync('order', 'Scheduled order sync started', 'info');
            
            // Sync orders from last 24 hours
            $result = $api->syncOrders('24h');
            
            // Log completion
            $this->logSync('order', sprintf(
                __('Scheduled order sync completed. %d orders synced.', 'wp-woocommerce-printify-sync'),
                $result['count']
            ), 'success');
            
        } catch (\Exception $e) {
            $this->logSync('order', sprintf(
                __('Scheduled order sync failed: %s', 'wp-woocommerce-printify-sync'),
                $e->getMessage()
            ), 'error');
        }
    }
    
    /**
     * Scheduled task to clean up old logs.
     * 
     * @return void
     */
    public function scheduledCleanupLogs(): void
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpwps_sync_logs';
        $daysToKeep = 30; // Keep logs for 30 days
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE time < %s",
            $cutoffDate
        ));
        
        if ($deleted !== false) {
            $this->logSync('system', sprintf(
                __('Log cleanup completed. %d old log entries removed.', 'wp-woocommerce-printify-sync'),
                $deleted
            ), 'info');
        } else {
            $this->logSync('system', __('Failed to clean up old logs.', 'wp-woocommerce-printify-sync'), 'error');
        }
    }
    
    /**
     * Manual sync products.
     * 
     * @param int $page Page number
     * @param int $limit Products per page
     * @return array
     */
    public function manualProductSync(int $page = 1, int $limit = 20): array
    {
        try {
            $api = $this->getPrintifyClient();
            
            // Log start of sync
            $this->logSync('product', 'Manual product sync started', 'info');
            
            // Sync products
            $result = $api->syncProducts($page, $limit);
            
            // Log completion
            $this->logSync('product', sprintf(
                __('Manual product sync completed. %d products synced.', 'wp-woocommerce-printify-sync'),
                count($result['products'] ?? [])
            ), 'success');
            
            return [
                'success' => true,
                'message' => sprintf(
                    __('%d products synced successfully.', 'wp-woocommerce-printify-sync'),
                    count($result['products'] ?? [])
                ),
                'data' => $result,
            ];
            
        } catch (\Exception $e) {
            $this->logSync('product', sprintf(
                __('Manual product sync failed: %s', 'wp-woocommerce-printify-sync'),
                $e->getMessage()
            ), 'error');
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Manual sync inventory.
     * 
     * @return array
     */
    public function manualInventorySync(): array
    {
        try {
            $api = $this->getPrintifyClient();
            
            // Log start of sync
            $this->logSync('inventory', 'Manual inventory sync started', 'info');
            
            // Sync inventory
            $result = $api->syncInventory();
            
            // Log completion
            $this->logSync('inventory', sprintf(
                __('Manual inventory sync completed. %d products updated.', 'wp-woocommerce-printify-sync'),
                $result['count']
            ), 'success');
            
            return [
                'success' => true,
                'message' => sprintf(
                    __('Inventory updated for %d products.', 'wp-woocommerce-printify-sync'),
                    $result['count']
                ),
                'data' => $result,
            ];
            
        } catch (\Exception $e) {
            $this->logSync('inventory', sprintf(
                __('Manual inventory sync failed: %s', 'wp-woocommerce-printify-sync'),
                $e->getMessage()
            ), 'error');
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Manual sync orders.
     * 
     * @param string $timeframe Timeframe (e.g., '24h', '7d')
     * @return array
     */
    public function manualOrderSync(string $timeframe = '24h'): array
    {
        try {
            $api = $this->getPrintifyClient();
            
            // Log start of sync
            $this->logSync('order', 'Manual order sync started', 'info');
            
            // Sync orders
            $result = $api->syncOrders($timeframe);
            
            // Log completion
            $this->logSync('order', sprintf(
                __('Manual order sync completed. %d orders synced.', 'wp-woocommerce-printify-sync'),
                $result['count']
            ), 'success');
            
            return [
                'success' => true,
                'message' => sprintf(
                    __('%d orders synced successfully.', 'wp-woocommerce-printify-sync'),
                    $result['count']
                ),
                'data' => $result,
            ];
            
        } catch (\Exception $e) {
            $this->logSync('order', sprintf(
                __('Manual order sync failed: %s', 'wp-woocommerce-printify-sync'),
                $e->getMessage()
            ), 'error');
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get sync logs.
     * 
     * @param string $type Log type (product, inventory, order, system)
     * @param int $limit Number of logs to retrieve
     * @param int $offset Offset for pagination
     * @return array
     */
    public function getSyncLogs(string $type = '', int $limit = 50, int $offset = 0): array
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpwps_sync_logs';
        $where = '';
        $values = [];
        
        if (!empty($type)) {
            $where = 'WHERE type = %s';
            $values[] = $type;
        }
        
        $limitClause = 'LIMIT %d OFFSET %d';
        $values[] = $limit;
        $values[] = $offset;
        
        $query = "SELECT * FROM {$table} {$where} ORDER BY time DESC {$limitClause}";
        $logs = $wpdb->get_results($wpdb->prepare($query, $values), ARRAY_A);
        
        // Count total
        $countQuery = "SELECT COUNT(*) FROM {$table} {$where}";
        $total = (int)$wpdb->get_var($wpdb->prepare($countQuery, $type ? [$type] : []));
        
        return [
            'logs' => $logs,
            'total' => $total,
            'page' => floor($offset / $limit) + 1,
            'pages' => ceil($total / $limit),
        ];
    }
    
    /**
     * Log sync event.
     * 
     * @param string $type Event type (product, inventory, order, system)
     * @param string $message Event message
     * @param string $status Event status (info, success, error)
     * @param array $data Additional data
     * @return void
     */
    protected function logSync(string $type, string $message, string $status, array $data = []): void
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpwps_sync_logs';
        
        $wpdb->insert(
            $table,
            [
                'time' => current_time('mysql'),
                'type' => $type,
                'message' => $message,
                'status' => $status,
                'data' => !empty($data) ? wp_json_encode($data) : null,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );
    }
    
    /**
     * Get Printify API client.
     * 
     * @return PrintifyClient
     * @throws \Exception
     */
    protected function getPrintifyClient(): PrintifyClient
    {
        $settings = get_option('wpwps_settings', []);
        $apiKey = $settings['api_key'] ?? '';
        $shopId = $settings['shop_id'] ?? '';
        
        if (empty($apiKey)) {
            throw new \Exception(__('API key is not configured.', 'wp-woocommerce-printify-sync'));
        }
        
        if (empty($shopId)) {
            throw new \Exception(__('Shop ID is not configured.', 'wp-woocommerce-printify-sync'));
        }
        
        return new PrintifyClient($apiKey, $shopId);
    }
}