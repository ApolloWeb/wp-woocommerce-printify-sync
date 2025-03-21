<?php
/**
 * API Controller for handling API interactions.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\EncryptionService;
use WP_Error;

/**
 * API Controller class.
 */
class APIController
{
    /**
     * Printify API client.
     *
     * @var PrintifyAPIClient
     */
    private $printify_client;

    /**
     * ChatGPT API client.
     *
     * @var ChatGPTClient
     */
    private $chatgpt_client;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Encryption service.
     *
     * @var EncryptionService
     */
    private $encryption;

    /**
     * Constructor.
     *
     * @param PrintifyAPIClient $printify_client Printify API client.
     * @param ChatGPTClient     $chatgpt_client  ChatGPT API client.
     * @param Logger            $logger         Logger instance.
     * @param EncryptionService $encryption     Encryption service.
     */
    public function __construct(
        PrintifyAPIClient $printify_client,
        ChatGPTClient $chatgpt_client,
        Logger $logger,
        EncryptionService $encryption
    ) {
        $this->printify_client = $printify_client;
        $this->chatgpt_client = $chatgpt_client;
        $this->logger = $logger;
        $this->encryption = $encryption;
    }

    /**
     * Initialize the controller.
     *
     * @return void
     */
    public function init()
    {
        // No additional initialization needed at this time
    }

    /**
     * Test Printify API connection via AJAX.
     *
     * @return void
     */
    public function testPrintifyConnection()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? sanitize_text_field(wp_unslash($_POST['api_endpoint'])) : '';

        if (empty($api_key)) {
            wp_send_json_error([
                'message' => __('API key is required.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        // Set the API credentials for this request
        $this->printify_client->setApiKey($api_key);
        if (!empty($api_endpoint)) {
            $this->printify_client->setApiEndpoint($api_endpoint);
        }

        // Test the connection by fetching shops
        $response = $this->printify_client->getShops();

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message(),
            ]);
            return;
        }

        if (empty($response) || !isset($response['shops']) || !is_array($response['shops'])) {
            wp_send_json_error([
                'message' => __('Invalid response from Printify API.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        // Format shops for display
        $shops = [];
        foreach ($response['shops'] as $shop) {
            if (isset($shop['id'], $shop['title'])) {
                $shops[] = [
                    'id' => $shop['id'],
                    'title' => $shop['title'],
                ];
            }
        }

        wp_send_json_success([
            'message' => __('Successfully connected to Printify API.', 'wp-woocommerce-printify-sync'),
            'shops' => $shops,
        ]);
    }

    /**
     * Test ChatGPT API connection via AJAX.
     *
     * @return void
     */
    public function testChatGPTConnection()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
        $temperature = isset($_POST['temperature']) ? (float) $_POST['temperature'] : 0.7;
        $monthly_budget = isset($_POST['monthly_budget']) ? (int) $_POST['monthly_budget'] : 10000;

        if (empty($api_key)) {
            wp_send_json_error([
                'message' => __('API key is required.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        // Set the API settings for this request
        $this->chatgpt_client->setApiKey($api_key);
        $this->chatgpt_client->setTemperature($temperature);
        $this->chatgpt_client->setMonthlyBudget($monthly_budget);

        // Test the connection
        $response = $this->chatgpt_client->testConnection();

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message(),
            ]);
            return;
        }

        if (!isset($response['success']) || !$response['success']) {
            wp_send_json_error([
                'message' => __('Invalid response from ChatGPT API.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        wp_send_json_success([
            'message' => __('Successfully connected to ChatGPT API.', 'wp-woocommerce-printify-sync'),
            'response' => $response['message'],
            'tokens_used' => $response['tokens_used'],
            'estimated_cost' => $response['estimated_cost'],
        ]);
    }

    /**
     * Save settings via AJAX.
     *
     * @return void
     */
    public function saveSettings()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        // Get and sanitize settings
        $printify_api_key = isset($_POST['printify_api_key']) ? sanitize_text_field(wp_unslash($_POST['printify_api_key'])) : '';
        $printify_api_endpoint = isset($_POST['printify_api_endpoint']) ? sanitize_text_field(wp_unslash($_POST['printify_api_endpoint'])) : '';
        $printify_shop_id = isset($_POST['printify_shop_id']) ? sanitize_text_field(wp_unslash($_POST['printify_shop_id'])) : '';
        $printify_shop_name = isset($_POST['printify_shop_name']) ? sanitize_text_field(wp_unslash($_POST['printify_shop_name'])) : '';
        
        $chatgpt_api_key = isset($_POST['chatgpt_api_key']) ? sanitize_text_field(wp_unslash($_POST['chatgpt_api_key'])) : '';
        $chatgpt_temperature = isset($_POST['chatgpt_temperature']) ? (float) $_POST['chatgpt_temperature'] : 0.7;
        $chatgpt_monthly_budget = isset($_POST['chatgpt_monthly_budget']) ? (int) $_POST['chatgpt_monthly_budget'] : 10000;
        
        $log_level = isset($_POST['log_level']) ? sanitize_text_field(wp_unslash($_POST['log_level'])) : 'info';

        // Validate required fields
        if (empty($printify_api_key)) {
            wp_send_json_error([
                'message' => __('Printify API key is required.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        // Save settings
        $this->encryption->storeKey('wpwps_printify_api_key', $printify_api_key);
        update_option('wpwps_printify_api_endpoint', $printify_api_endpoint);
        update_option('wpwps_printify_shop_id', $printify_shop_id);
        update_option('wpwps_printify_shop_name', $printify_shop_name);
        
        if (!empty($chatgpt_api_key)) {
            $this->encryption->storeKey('wpwps_chatgpt_api_key', $chatgpt_api_key);
        }
        
        update_option('wpwps_chatgpt_temperature', $chatgpt_temperature);
        update_option('wpwps_chatgpt_monthly_budget', $chatgpt_monthly_budget);
        update_option('wpwps_log_level', $log_level);

        $this->logger->info('Settings saved', [
            'user_id' => get_current_user_id(),
        ]);

        wp_send_json_success([
            'message' => __('Settings saved successfully.', 'wp-woocommerce-printify-sync'),
        ]);
    }

    /**
     * Get revenue data for dashboard.
     *
     * @return void
     */
    public function getRevenueData()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $period = isset($_GET['period']) ? sanitize_text_field(wp_unslash($_GET['period'])) : 'week';
        
        // Calculate date range based on period
        $now = current_time('timestamp');
        $labels = [];
        $revenue = [];
        $profit = [];
        
        switch ($period) {
            case 'day':
                // Last 24 hours by hour
                for ($i = 23; $i >= 0; $time = strtotime("-{$i} hours", $now)) {
                    $labels[] = date_i18n('g A', $time);
                    
                    // Get orders for this hour
                    $start_time = date('Y-m-d H:00:00', $time);
                    $end_time = date('Y-m-d H:59:59', $time);
                    
                    $sales_data = $this->getSalesData($start_time, $end_time);
                    $revenue[] = $sales_data['revenue'];
                    $profit[] = $sales_data['profit'];
                }
                break;
                
            case 'week':
                // Last 7 days
                for ($i = 6; $i >= 0; $time = strtotime("-{$i} days", $now)) {
                    $labels[] = date_i18n('D', $time);
                    
                    // Get orders for this day
                    $start_time = date('Y-m-d 00:00:00', $time);
                    $end_time = date('Y-m-d 23:59:59', $time);
                    
                    $sales_data = $this->getSalesData($start_time, $end_time);
                    $revenue[] = $sales_data['revenue'];
                    $profit[] = $sales_data['profit'];
                }
                break;
                
            case 'month':
                // Last 30 days by week
                for ($i = 4; $i >= 0; $start_time_ts = strtotime("-" . ($i * 7) . " days", $now)) {
                    $end_time_ts = $i > 0 ? strtotime("-" . (($i - 1) * 7 - 1) . " days", $now) : $now;
                    
                    $labels[] = date_i18n('M d', $start_time_ts) . ' - ' . date_i18n('M d', $end_time_ts);
                    
                    // Get orders for this week
                    $start_time = date('Y-m-d 00:00:00', $start_time_ts);
                    $end_time = date('Y-m-d 23:59:59', $end_time_ts);
                    
                    $sales_data = $this->getSalesData($start_time, $end_time);
                    $revenue[] = $sales_data['revenue'];
                    $profit[] = $sales_data['profit'];
                }
                break;
                
            case 'year':
                // Last 12 months
                for ($i = 11; $i >= 0; $time = strtotime("-{$i} months", $now)) {
                    $labels[] = date_i18n('M', $time);
                    
                    // Get orders for this month
                    $year = date('Y', $time);
                    $month = date('m', $time);
                    $start_time = "{$year}-{$month}-01 00:00:00";
                    
                    $days_in_month = date('t', $time);
                    $end_time = "{$year}-{$month}-{$days_in_month} 23:59:59";
                    
                    $sales_data = $this->getSalesData($start_time, $end_time);
                    $revenue[] = $sales_data['revenue'];
                    $profit[] = $sales_data['profit'];
                }
                break;
                
            default:
                wp_send_json_error([
                    'message' => __('Invalid period.', 'wp-woocommerce-printify-sync'),
                ]);
                return;
        }
        
        wp_send_json_success([
            'labels' => $labels,
            'revenue' => $revenue,
            'profit' => $profit,
        ]);
    }
    
    /**
     * Get sales data for a specific time period.
     *
     * @param string $start_time Start time in MySQL format.
     * @param string $end_time   End time in MySQL format.
     * @return array Revenue and profit data.
     */
    private function getSalesData($start_time, $end_time)
    {
        global $wpdb;
        
        // Get all completed orders within the time period
        $query = $wpdb->prepare(
            "SELECT p.ID as order_id, p.post_date
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing')
            AND p.post_date >= %s
            AND p.post_date <= %s",
            $start_time,
            $end_time
        );
        
        $orders = $wpdb->get_results($query);
        
        $revenue = 0;
        $profit = 0;
        
        if (!empty($orders)) {
            foreach ($orders as $order_obj) {
                $order = wc_get_order($order_obj->order_id);
                
                if (!$order) {
                    continue;
                }
                
                // Add order total to revenue
                $revenue += $order->get_total();
                
                // Calculate profit (revenue - cost)
                $order_profit = $order->get_total();
                
                // Get all items and subtract cost
                $items = $order->get_items();
                
                foreach ($items as $item) {
                    $product = $item->get_product();
                    
                    if (!$product) {
                        continue;
                    }
                    
                    // Get cost price if available
                    $cost_price = 0;
                    
                    if ($product->is_type('variation')) {
                        $cost_price = get_post_meta($product->get_id(), '_printify_cost_price', true);
                        
                        if (empty($cost_price)) {
                            // Try to get from parent
                            $cost_price = get_post_meta($product->get_parent_id(), '_printify_cost_price', true);
                        }
                    } else {
                        $cost_price = get_post_meta($product->get_id(), '_printify_cost_price', true);
                    }
                    
                    if (!empty($cost_price)) {
                        $order_profit -= ($cost_price * $item->get_quantity());
                    }
                }
                
                // Add shipping cost (estimate)
                $shipping_cost = get_post_meta($order->get_id(), '_printify_shipping_cost', true);
                
                if (!empty($shipping_cost)) {
                    $order_profit -= $shipping_cost;
                }
                
                // Add to total profit
                $profit += $order_profit;
            }
        }
        
        // Round to 2 decimal places
        $revenue = round($revenue, 2);
        $profit = round($profit, 2);
        
        // Ensure profit is not negative
        $profit = max(0, $profit);
        
        return [
            'revenue' => $revenue,
            'profit' => $profit,
        ];
    }
}
