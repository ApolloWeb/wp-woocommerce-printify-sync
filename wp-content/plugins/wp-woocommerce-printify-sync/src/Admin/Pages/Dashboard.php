<?php
/**
 * Dashboard page.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Pages
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\TemplateRenderer;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;

/**
 * Dashboard admin page.
 */
class Dashboard {
    /**
     * PrintifyAPI instance.
     *
     * @var PrintifyAPI
     */
    private $api;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * TemplateRenderer instance.
     *
     * @var TemplateRenderer
     */
    private $template;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->logger = new Logger();
        $this->api = new PrintifyAPI($this->logger);
        $this->template = new TemplateRenderer();
    }

    /**
     * Initialize Dashboard page.
     *
     * @return void
     */
    public function init() {
        add_action('wp_ajax_wpwps_get_dashboard_data', [$this, 'getDashboardData']);
    }

    /**
     * Render dashboard page.
     *
     * @return void
     */
    public function render() {
        $settings = get_option('wpwps_settings', []);
        $shop_name = isset($settings['shop_name']) ? $settings['shop_name'] : '';
        
        // Get product counts.
        $product_counts = $this->getProductCounts();
        
        // Get order counts.
        $order_counts = $this->getOrderCounts();
        
        // Get recent logs.
        $recent_logs = $this->logger->getLogs(10);
        
        // Render template.
        $this->template->render('dashboard', [
            'shop_name' => $shop_name,
            'product_counts' => $product_counts,
            'order_counts' => $order_counts,
            'recent_logs' => $recent_logs,
            'nonce' => wp_create_nonce('wpwps_dashboard_nonce'),
        ]);
    }

    /**
     * Get product counts.
     *
     * @return array
     */
    private function getProductCounts() {
        global $wpdb;
        
        // Count Printify products.
        $printify_count = $wpdb->get_var(
            "SELECT COUNT(post_id) FROM $wpdb->postmeta 
            WHERE meta_key = '_printify_product_id' 
            AND post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'product' AND post_status = 'publish')"
        );
        
        // Count all products.
        $all_count = $wpdb->get_var(
            "SELECT COUNT(ID) FROM $wpdb->posts 
            WHERE post_type = 'product' 
            AND post_status = 'publish'"
        );
        
        $non_printify_count = $all_count - $printify_count;
        
        return [
            'printify' => $printify_count ?: 0,
            'non_printify' => $non_printify_count ?: 0,
            'all' => $all_count ?: 0,
        ];
    }

    /**
     * Get order counts.
     *
     * @return array
     */
    private function getOrderCounts() {
        if (!function_exists('wc_get_order_statuses')) {
            return [
                'processing' => 0,
                'completed' => 0,
                'pending' => 0,
                'all' => 0,
            ];
        }
        
        // Get WooCommerce order counts.
        $counts = [];
        
        $counts['processing'] = wc_orders_count('processing');
        $counts['completed'] = wc_orders_count('completed');
        $counts['pending'] = wc_orders_count('pending');
        
        $order_statuses = array_keys(wc_get_order_statuses());
        $counts['all'] = 0;
        
        foreach ($order_statuses as $status) {
            $counts['all'] += wc_orders_count($status);
        }
        
        return $counts;
    }

    /**
     * Get dashboard data for AJAX requests.
     *
     * @return void
     */
    public function getDashboardData() {
        // Check nonce.
        check_ajax_referer('wpwps_dashboard_nonce', 'nonce');
        
        // Check user capability.
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        // Get date range.
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30d';
        $chart_type = isset($_POST['chart_type']) ? sanitize_text_field($_POST['chart_type']) : 'sales';
        
        // Get chart data.
        $chart_data = $this->getChartData($date_range, $chart_type);
        
        // Get API status.
        $api_status = $this->getApiStatus();
        
        // Get import queue status.
        $queue_status = $this->getQueueStatus();
        
        wp_send_json_success([
            'chart_data' => $chart_data,
            'api_status' => $api_status,
            'queue_status' => $queue_status,
        ]);
    }

    /**
     * Get chart data.
     *
     * @param string $date_range Date range.
     * @param string $chart_type Chart type.
     * @return array
     */
    private function getChartData($date_range, $chart_type) {
        global $wpdb;
        
        // Parse date range.
        $days = 30;
        
        if ('7d' === $date_range) {
            $days = 7;
        } elseif ('90d' === $date_range) {
            $days = 90;
        }
        
        // Calculate start date.
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $labels = [];
        $values = [];
        
        // Prepare data based on chart type.
        if ('sales' === $chart_type) {
            // Get sales data for WC orders with Printify products.
            $results = $this->getSalesData($start_date, $days);
            
            // Format data for Chart.js.
            $data_by_date = [];
            
            foreach ($results as $result) {
                $data_by_date[$result->date] = $result->total;
            }
            
            // Generate date labels and values.
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $labels[] = date_i18n(get_option('date_format'), strtotime($date));
                $values[] = isset($data_by_date[$date]) ? floatval($data_by_date[$date]) : 0;
            }
        } elseif ('orders' === $chart_type) {
            // Get order count data.
            $results = $this->getOrdersData($start_date, $days);
            
            // Format data for Chart.js.
            $data_by_date = [];
            
            foreach ($results as $result) {
                $data_by_date[$result->date] = $result->count;
            }
            
            // Generate date labels and values.
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $labels[] = date_i18n(get_option('date_format'), strtotime($date));
                $values[] = isset($data_by_date[$date]) ? intval($data_by_date[$date]) : 0;
            }
        }
        
        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Get sales data.
     *
     * @param string $start_date Start date.
     * @param int    $days       Number of days.
     * @return array
     */
    private function getSalesData($start_date, $days) {
        global $wpdb;
        
        // Get all orders with Printify products.
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(p.post_date) AS date, SUM(pm.meta_value) AS total
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            JOIN {$wpdb->posts} product ON product.ID = oim.meta_value
            JOIN {$wpdb->postmeta} product_meta ON product.ID = product_meta.post_id AND product_meta.meta_key = '_printify_product_id'
            WHERE p.post_type = 'shop_order'
            AND p.post_date >= %s
            AND oim.meta_key = '_product_id'
            GROUP BY DATE(p.post_date)
            ORDER BY date DESC",
            $start_date
        ));
        
        return $results;
    }

    /**
     * Get orders data.
     *
     * @param string $start_date Start date.
     * @param int    $days       Number of days.
     * @return array
     */
    private function getOrdersData($start_date, $days) {
        global $wpdb;
        
        // Get order counts by date.
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(p.post_date) AS date, COUNT(DISTINCT p.ID) AS count
            FROM {$wpdb->posts} p
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            JOIN {$wpdb->posts} product ON product.ID = oim.meta_value
            JOIN {$wpdb->postmeta} product_meta ON product.ID = product_meta.post_id AND product_meta.meta_key = '_printify_product_id'
            WHERE p.post_type = 'shop_order'
            AND p.post_date >= %s
            AND oim.meta_key = '_product_id'
            GROUP BY DATE(p.post_date)
            ORDER BY date DESC",
            $start_date
        ));
        
        return $results;
    }

    /**
     * Get API status.
     *
     * @return array
     */
    private function getApiStatus() {
        $settings = get_option('wpwps_settings', []);
        
        if (empty($settings['api_key']) || empty($settings['shop_id'])) {
            return [
                'status' => 'not_configured',
                'message' => __('API not configured', 'wp-woocommerce-printify-sync'),
            ];
        }
        
        // Test API connection.
        $result = $this->api->testConnection();
        
        if (is_wp_error($result)) {
            return [
                'status' => 'error',
                'message' => $result->get_error_message(),
            ];
        }
        
        return [
            'status' => 'connected',
            'message' => __('API connected', 'wp-woocommerce-printify-sync'),
        ];
    }

    /**
     * Get import queue status.
     *
     * @return array
     */
    private function getQueueStatus() {
        // Check if Action Scheduler is available.
        if (!function_exists('as_get_scheduled_actions')) {
            return [
                'pending' => 0,
                'running' => 0,
                'completed' => 0,
            ];
        }
        
        // Get count of pending product import actions.
        $pending = as_get_scheduled_actions([
            'hook' => 'wpwps_import_product',
            'status' => 'pending',
            'group' => 'wpwps_product_import',
        ], 'count');
        
        // Get count of running product import actions.
        $running = as_get_scheduled_actions([
            'hook' => 'wpwps_import_product',
            'status' => 'in-progress',
            'group' => 'wpwps_product_import',
        ], 'count');
        
        // Get count of completed product import actions.
        $completed = as_get_scheduled_actions([
            'hook' => 'wpwps_import_product',
            'status' => 'complete',
            'group' => 'wpwps_product_import',
        ], 'count');
        
        return [
            'pending' => $pending,
            'running' => $running,
            'completed' => $completed,
        ];
    }
}
