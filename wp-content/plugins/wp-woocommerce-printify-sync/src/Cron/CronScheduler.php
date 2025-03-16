<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Cron;

class CronScheduler
{
    public const HOURLY_HOOK = 'wpwps_hourly_sync';
    public const DAILY_HOOK = 'wpwps_daily_sync';
    public const WEEKLY_HOOK = 'wpwps_weekly_cleanup';

    public function register(): void
    {
        // Register custom intervals
        add_filter('cron_schedules', [$this, 'addCustomIntervals']);

        // Schedule events if not already scheduled
        if (!wp_next_scheduled(self::HOURLY_HOOK)) {
            wp_schedule_event(time(), 'hourly', self::HOURLY_HOOK);
        }

        if (!wp_next_scheduled(self::DAILY_HOOK)) {
            wp_schedule_event(time(), 'daily', self::DAILY_HOOK);
        }

        if (!wp_next_scheduled(self::WEEKLY_HOOK)) {
            wp_schedule_event(time(), 'weekly', self::WEEKLY_HOOK);
        }

        // Register hooks
        add_action(self::HOURLY_HOOK, [$this, 'handleHourlySync']);
        add_action(self::DAILY_HOOK, [$this, 'handleDailySync']);
        add_action(self::WEEKLY_HOOK, [$this, 'handleWeeklyCleanup']);
    }

    public function addCustomIntervals($schedules): array
    {
        $schedules['weekly'] = [
            'interval' => 7 * DAY_IN_SECONDS,
            'display' => __('Once Weekly', 'wp-woocommerce-printify-sync')
        ];

        return $schedules;
    }

    public function handleHourlySync(): void
    {
        do_action('wpwps_before_hourly_sync');

        // Sync orders
        $this->syncPendingOrders();

        // Update exchange rates
        $this->updateExchangeRates();

        do_action('wpwps_after_hourly_sync');
    }

    public function handleDailySync(): void
    {
        do_action('wpwps_before_daily_sync');

        // Full product sync
        $this->syncAllProducts();

        // Generate reports
        $this->generateDailyReports();

        do_action('wpwps_after_daily_sync');
    }

    public function handleWeeklyCleanup(): void
    {
        do_action('wpwps_before_weekly_cleanup');

        // Clean old logs
        $this->cleanOldLogs();

        // Optimize database tables
        $this->optimizeTables();

        do_action('wpwps_after_weekly_cleanup');
    }

    private function syncPendingOrders(): void
    {
        global $wpdb;

        $pendingOrders = $wpdb->get_col("
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_wpwps_sync_needed'
            AND meta_value = '1'
            LIMIT 50
        ");

        if (!empty($pendingOrders)) {
            $orderSyncProcess = new OrderSyncProcess();
            foreach ($pendingOrders as $orderId) {
                $orderSyncProcess->push_to_queue(['order_id' => $orderId, 'attempts' => 0]);
            }
            $orderSyncProcess->save()->dispatch();
        }
    }

    private function updateExchangeRates(): void
    {
        try {
            $currencyService = new CurrencyService();
            $currencyService->updateRates();
        } catch (\Exception $e) {
            error_log('Exchange rate update failed: ' . $e->getMessage());
        }
    }

    private function syncAllProducts(): void
    {
        global $wpdb;

        $products = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type IN ('product', 'product_variation')
            AND post_status = 'publish'
        ");

        if (!empty($products)) {
            $productSyncProcess = new ProductSyncProcess();
            foreach ($products as $productId) {
                $productSyncProcess->push_to_queue(['product_id' => $productId, 'attempts' => 0]);
            }
            $productSyncProcess->save()->dispatch();
        }
    }

    private function generateDailyReports(): void
    {
        try {
            // Generate sales report
            do_action('wpwps_generate_sales_report');

            // Generate inventory report
            do_action('wpwps_generate_inventory_report');

            // Generate sync status report
            do_action('wpwps_generate_sync_report');
        } catch (\Exception $e) {
            error_log('Daily report generation failed: ' . $e->getMessage());
        }
    }

    private function cleanOldLogs(): void
    {
        global $wpdb;

        // Delete logs older than 30 days
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}wpwps_logs
            WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));

        // Optimize the logs table
        $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}wpwps_logs");
    }

    private function optimizeTables(): void
    {
        global $wpdb;

        $tables = [
            'wpwps_logs',
            'wpwps_sync_history',
            'wpwps_exchange_rates',
            'wpwps_order_mapping'
        ];

        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}{$table}");
        }
    }
}