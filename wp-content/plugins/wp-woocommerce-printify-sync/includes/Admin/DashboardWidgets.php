<?php
/**
 * Dashboard widgets handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Plugin;
use ApolloWeb\WPWooCommercePrintifySync\Core\TemplateEngine;

/**
 * Class DashboardWidgets
 */
class DashboardWidgets {
    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private $plugin;
    
    /**
     * Template engine
     *
     * @var TemplateEngine
     */
    private $template;

    /**
     * Constructor
     *
     * @param Plugin $plugin Plugin instance.
     */
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
        $this->template = new TemplateEngine();
        
        add_action('wp_dashboard_setup', [$this, 'registerWidgets']);
    }

    /**
     * Register dashboard widgets
     *
     * @return void
     */
    public function registerWidgets() {
        // Only add widgets if user has WooCommerce capabilities
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        // Email Queue widget
        wp_add_dashboard_widget(
            'wpwps_email_queue',
            __('Printify Sync - Email Queue', 'wp-woocommerce-printify-sync'),
            [$this, 'renderEmailQueueWidget'],
            null,
            null,
            'normal',
            'high'
        );
        
        // Import Progress widget
        wp_add_dashboard_widget(
            'wpwps_import_progress',
            __('Printify Sync - Import Progress', 'wp-woocommerce-printify-sync'),
            [$this, 'renderImportProgressWidget'],
            null,
            null,
            'normal',
            'high'
        );
        
        // Sync Status widget
        wp_add_dashboard_widget(
            'wpwps_sync_status',
            __('Printify Sync - Sync Status', 'wp-woocommerce-printify-sync'),
            [$this, 'renderSyncStatusWidget'],
            null,
            null,
            'side',
            'high'
        );
        
        // Sales Summary widget
        wp_add_dashboard_widget(
            'wpwps_sales_summary',
            __('Printify Sync - Sales Summary', 'wp-woocommerce-printify-sync'),
            [$this, 'renderSalesSummaryWidget'],
            null,
            null,
            'side',
            'high'
        );
    }

    /**
     * Render email queue widget
     *
     * @return void
     */
    public function renderEmailQueueWidget() {
        // Get email queue data
        $email_queue = $this->getEmailQueueData();
        
        // Render template
        $this->template->render('wpwps-admin/widgets/email-queue', [
            'queue' => $email_queue,
            'total' => count($email_queue),
            'next_run' => $this->getNextEmailProcessTime(),
        ]);
    }

    /**
     * Render import progress widget
     *
     * @return void
     */
    public function renderImportProgressWidget() {
        // Get import progress data
        $import_jobs = $this->getImportJobsData();
        
        // Render template
        $this->template->render('wpwps-admin/widgets/import-progress', [
            'jobs' => $import_jobs,
            'total' => count($import_jobs),
            'active' => $this->countActiveImportJobs($import_jobs),
        ]);
    }

    /**
     * Render sync status widget
     *
     * @return void
     */
    public function renderSyncStatusWidget() {
        // Get sync status data
        $sync_data = $this->getSyncStatusData();
        
        // Render template
        $this->template->render('wpwps-admin/widgets/sync-status', [
            'last_sync' => $sync_data['last_sync'],
            'next_sync' => $sync_data['next_sync'],
            'products_count' => $sync_data['products_count'],
            'orders_count' => $sync_data['orders_count'],
            'sync_success_rate' => $sync_data['sync_success_rate'],
        ]);
    }

    /**
     * Render sales summary widget
     *
     * @return void
     */
    public function renderSalesSummaryWidget() {
        // Get sales summary data
        $sales_data = $this->getSalesSummaryData();
        
        // Render template
        $this->template->render('wpwps-admin/widgets/sales-summary', [
            'today' => $sales_data['today'],
            'yesterday' => $sales_data['yesterday'],
            'this_week' => $sales_data['this_week'],
            'this_month' => $sales_data['this_month'],
            'chart_data' => $sales_data['chart_data'],
        ]);
    }

    /**
     * Get email queue data
     *
     * @return array Email queue data
     */
    private function getEmailQueueData() {
        global $wpdb;
        
        // Example query - adjust according to actual database structure
        $table_name = $wpdb->prefix . 'wpwps_email_queue';
        $query = "SELECT * FROM $table_name WHERE status = 'pending' ORDER BY priority DESC, created_at ASC LIMIT 10";
        
        // Return empty array if table doesn't exist yet (before plugin activation)
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [];
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        return $results ?: [];
    }

    /**
     * Get next email process time
     *
     * @return string Formatted time
     */
    private function getNextEmailProcessTime() {
        $next_scheduled = $this->plugin->getService('scheduler')->nextScheduled('wpwps_process_email_queue');
        
        if (!$next_scheduled) {
            return __('Not scheduled', 'wp-woocommerce-printify-sync');
        }
        
        return human_time_diff(time(), $next_scheduled) . ' ' . __('from now', 'wp-woocommerce-printify-sync');
    }

    /**
     * Get import jobs data
     *
     * @return array Import jobs data
     */
    private function getImportJobsData() {
        // Example data retrieval from Action Scheduler
        $jobs = [];
        
        if (function_exists('as_get_scheduled_actions')) {
            $product_imports = as_get_scheduled_actions([
                'group' => 'wpwps',
                'hook' => 'wpwps_import_product_batch',
                'status' => ['pending', 'in-progress'],
            ]);
            
            $order_imports = as_get_scheduled_actions([
                'group' => 'wpwps',
                'hook' => 'wpwps_import_order_batch',
                'status' => ['pending', 'in-progress'],
            ]);
            
            foreach ($product_imports as $import) {
                $jobs[] = [
                    'id' => $import->get_id(),
                    'type' => 'product',
                    'status' => $import->get_status(),
                    'args' => $import->get_args(),
                    'scheduled_date' => $import->get_scheduled_date_gmt(),
                ];
            }
            
            foreach ($order_imports as $import) {
                $jobs[] = [
                    'id' => $import->get_id(),
                    'type' => 'order',
                    'status' => $import->get_status(),
                    'args' => $import->get_args(),
                    'scheduled_date' => $import->get_scheduled_date_gmt(),
                ];
            }
        }
        
        return $jobs;
    }

    /**
     * Count active import jobs
     *
     * @param array $jobs Import jobs.
     * @return int Count of active jobs
     */
    private function countActiveImportJobs($jobs) {
        $active = 0;
        
        foreach ($jobs as $job) {
            if ($job['status'] === 'in-progress') {
                $active++;
            }
        }
        
        return $active;
    }

    /**
     * Get sync status data
     *
     * @return array Sync status data
     */
    private function getSyncStatusData() {
        $last_sync = get_option('wpwps_last_sync_time', 0);
        $next_sync = $this->plugin->getService('scheduler')->nextScheduled('wpwps_stock_sync');
        
        // Get product count with Printify metadata
        $products_count = $this->getProductsCount();
        
        // Get orders count with Printify metadata
        $orders_count = $this->getOrdersCount();
        
        // Calculate sync success rate
        $sync_success = get_option('wpwps_sync_success_count', 0);
        $sync_total = get_option('wpwps_sync_total_count', 0);
        $sync_success_rate = $sync_total > 0 ? round(($sync_success / $sync_total) * 100) : 100;
        
        return [
            'last_sync' => $last_sync ? human_time_diff(time(), $last_sync) . ' ' . __('ago', 'wp-woocommerce-printify-sync') : __('Never', 'wp-woocommerce-printify-sync'),
            'next_sync' => $next_sync ? human_time_diff(time(), $next_sync) . ' ' . __('from now', 'wp-woocommerce-printify-sync') : __('Not scheduled', 'wp-woocommerce-printify-sync'),
            'products_count' => $products_count,
            'orders_count' => $orders_count,
            'sync_success_rate' => $sync_success_rate,
        ];
    }

    /**
     * Get products count with Printify metadata
     *
     * @return int Products count
     */
    private function getProductsCount() {
        $products = wc_get_products([
            'limit' => -1,
            'status' => 'publish',
            'return' => 'ids',
            'meta_query' => [
                [
                    'key' => '_printify_product_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);
        
        return count($products);
    }

    /**
     * Get orders count with Printify metadata
     *
     * @return int Orders count
     */
    private function getOrdersCount() {
        $orders = wc_get_orders([
            'limit' => -1,
            'return' => 'ids',
            'meta_query' => [
                [
                    'key' => '_printify_order_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);
        
        return count($orders);
    }

    /**
     * Get sales summary data
     *
     * @return array Sales summary data
     */
    private function getSalesSummaryData() {
        // Today's sales
        $today_sales = $this->getSalesByDateRange('today');
        
        // Yesterday's sales
        $yesterday_sales = $this->getSalesByDateRange('yesterday');
        
        // This week's sales
        $this_week_sales = $this->getSalesByDateRange('week');
        
        // This month's sales
        $this_month_sales = $this->getSalesByDateRange('month');
        
        // Generate chart data for the last 7 days
        $chart_data = $this->generateSalesChartData();
        
        return [
            'today' => $today_sales,
            'yesterday' => $yesterday_sales,
            'this_week' => $this_week_sales,
            'this_month' => $this_month_sales,
            'chart_data' => $chart_data,
        ];
    }

    /**
     * Get sales by date range
     *
     * @param string $range Date range (today, yesterday, week, month).
     * @return array Sales data
     */
    private function getSalesByDateRange($range) {
        $now = current_time('timestamp');
        
        switch ($range) {
            case 'today':
                $start_date = strtotime('today', $now);
                $end_date = $now;
                break;
                
            case 'yesterday':
                $start_date = strtotime('yesterday', $now);
                $end_date = strtotime('today', $now) - 1;
                break;
                
            case 'week':
                $start_date = strtotime('monday this week', $now);
                $end_date = $now;
                break;
                
            case 'month':
                $start_date = strtotime('first day of this month', $now);
                $end_date = $now;
                break;
                
            default:
                return ['total' => 0, 'count' => 0];
        }
        
        // Query orders with Printify metadata
        $orders = wc_get_orders([
            'date_created' => $start_date . '...' . $end_date,
            'status' => ['completed', 'processing'],
            'meta_query' => [
                [
                    'key' => '_printify_order_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);
        
        $total = 0;
        $count = count($orders);
        
        foreach ($orders as $order) {
            $total += $order->get_total();
        }
        
        return [
            'total' => wc_price($total),
            'count' => $count,
        ];
    }

    /**
     * Generate sales chart data for the last 7 days
     *
     * @return array Chart data
     */
    private function generateSalesChartData() {
        $chart_data = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => __('Orders', 'wp-woocommerce-printify-sync'),
                    'data' => [],
                    'backgroundColor' => 'rgba(150, 88, 138, 0.2)',
                    'borderColor' => 'rgba(150, 88, 138, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => __('Revenue', 'wp-woocommerce-printify-sync'),
                    'data' => [],
                    'backgroundColor' => 'rgba(75, 79, 157, 0.2)',
                    'borderColor' => 'rgba(75, 79, 157, 1)',
                    'borderWidth' => 1,
                    'yAxisID' => 'y2',
                ],
            ],
        ];
        
        // Get data for the last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = strtotime("-$i days");
            $chart_data['labels'][] = date_i18n('M j', $date);
            
            $start_of_day = strtotime('midnight', $date);
            $end_of_day = strtotime('tomorrow', $start_of_day) - 1;
            
            // Query orders with Printify metadata
            $orders = wc_get_orders([
                'date_created' => $start_of_day . '...' . $end_of_day,
                'status' => ['completed', 'processing'],
                'meta_query' => [
                    [
                        'key' => '_printify_order_id',
                        'compare' => 'EXISTS',
                    ],
                ],
            ]);
            
            $total = 0;
            $count = count($orders);
            
            foreach ($orders as $order) {
                $total += $order->get_total();
            }
            
            $chart_data['datasets'][0]['data'][] = $count;
            $chart_data['datasets'][1]['data'][] = round($total, 2);
        }
        
        return $chart_data;
    }
}
