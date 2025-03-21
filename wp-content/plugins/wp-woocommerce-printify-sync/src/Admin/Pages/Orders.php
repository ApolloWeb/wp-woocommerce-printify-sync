<?php
/**
 * Orders page.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Pages
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\TemplateRenderer;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;

/**
 * Orders admin page.
 */
class Orders {
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
     * Initialize Orders page.
     *
     * @return void
     */
    public function init() {
        add_action('wp_ajax_wpwps_get_orders', [$this, 'getOrders']);
        add_action('wp_ajax_wpwps_sync_order', [$this, 'syncOrder']);
    }

    /**
     * Render orders page.
     *
     * @return void
     */
    public function render() {
        $settings = get_option('wpwps_settings', []);
        $shop_name = isset($settings['shop_name']) ? $settings['shop_name'] : '';
        
        // Get order stats.
        $order_stats = $this->getOrderStats();
        
        // Get recent orders.
        $recent_orders = $this->getRecentOrders();
        
        // Render template.
        $this->template->render('orders', [
            'shop_name' => $shop_name,
            'order_stats' => $order_stats,
            'recent_orders' => $recent_orders,
            'nonce' => wp_create_nonce('wpwps_orders_nonce'),
        ]);
    }

    /**
     * Get order statistics.
     *
     * @return array
     */
    private function getOrderStats() {
        global $wpdb;

        // Get orders with Printify variants.
        $stats = [
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'revenue' => 0,
            'last_30_days' => 0,
        ];

        // Get total orders and revenue.
        $results = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT p.ID) as total,
                SUM(pm.meta_value) as revenue
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            JOIN {$wpdb->postmeta} product_meta ON oim.meta_value = product_meta.post_id
            WHERE p.post_type = 'shop_order'
            AND oim.meta_key = '_product_id'
            AND product_meta.meta_key = '_printify_product_id'"
        ));

        $stats['total'] = $results->total ?: 0;
        $stats['revenue'] = $results->revenue ?: 0;

        // Get orders by status.
        $statuses = ['pending', 'processing', 'completed', 'cancelled'];

        foreach ($statuses as $status) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID)
                FROM {$wpdb->posts} p
                JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
                JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
                JOIN {$wpdb->postmeta} product_meta ON oim.meta_value = product_meta.post_id
                WHERE p.post_type = 'shop_order'
                AND p.post_status = %s
                AND oim.meta_key = '_product_id'
                AND product_meta.meta_key = '_printify_product_id'",
                'wc-' . $status
            ));

            $stats[$status] = $count ?: 0;
        }

        // Get last 30 days revenue.
        $last_30_days = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(pm.meta_value)
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            JOIN {$wpdb->postmeta} product_meta ON oim.meta_value = product_meta.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND oim.meta_key = '_product_id'
            AND product_meta.meta_key = '_printify_product_id'"
        ));

        $stats['last_30_days'] = $last_30_days ?: 0;

        return $stats;
    }

    /**
     * Get recent orders.
     *
     * @param int $limit Number of orders to return.
     * @return array
     */
    private function getRecentOrders($limit = 10) {
        global $wpdb;

        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                p.ID as order_id,
                p.post_date,
                p.post_status,
                pm_total.meta_value as total,
                GROUP_CONCAT(DISTINCT oi.order_item_name SEPARATOR ', ') as items,
                GROUP_CONCAT(DISTINCT pmp.meta_value SEPARATOR ', ') as printify_ids
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            JOIN {$wpdb->postmeta} product_meta ON oim.meta_value = product_meta.post_id AND product_meta.meta_key = '_printify_product_id'
            JOIN {$wpdb->postmeta} pmp ON oim.meta_value = pmp.post_id AND pmp.meta_key = '_printify_order_id'
            WHERE p.post_type = 'shop_order'
            AND oim.meta_key = '_product_id'
            GROUP BY p.ID
            ORDER BY p.post_date DESC
            LIMIT %d",
            $limit
        ));

        return $orders ?: [];
    }

    /**
     * Get orders via AJAX.
     *
     * @return void
     */
    public function getOrders() {
        check_ajax_referer('wpwps_orders_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
        }

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        // Build query.
        global $wpdb;
        $where = ["p.post_type = 'shop_order'", "oim.meta_key = '_product_id'"];

        if ($status) {
            $where[] = $wpdb->prepare("p.post_status = %s", 'wc-' . $status);
        }

        if ($search) {
            $where[] = $wpdb->prepare(
                "(p.ID LIKE %s OR oi.order_item_name LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $where = implode(' AND ', $where);

        // Get total count.
        $total = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            JOIN {$wpdb->postmeta} product_meta ON oim.meta_value = product_meta.post_id AND product_meta.meta_key = '_printify_product_id'
            WHERE {$where}
        ");

        // Get orders.
        $offset = ($page - 1) * $per_page;
        $orders = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID as order_id,
                p.post_date,
                p.post_status,
                pm_total.meta_value as total,
                GROUP_CONCAT(DISTINCT oi.order_item_name SEPARATOR ', ') as items,
                GROUP_CONCAT(DISTINCT pmp.meta_value SEPARATOR ', ') as printify_ids
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            JOIN {$wpdb->postmeta} product_meta ON oim.meta_value = product_meta.post_id AND product_meta.meta_key = '_printify_product_id'
            JOIN {$wpdb->postmeta} pmp ON oim.meta_value = pmp.post_id AND pmp.meta_key = '_printify_order_id'
            WHERE {$where}
            GROUP BY p.ID
            ORDER BY p.post_date DESC
            LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        wp_send_json_success([
            'orders' => $orders,
            'total' => $total,
            'pages' => ceil($total / $per_page),
        ]);
    }

    /**
     * Sync order with Printify.
     *
     * @return void
     */
    public function syncOrder() {
        check_ajax_referer('wpwps_orders_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        if (!$order_id) {
            wp_send_json_error(['message' => __('Invalid order ID.', 'wp-woocommerce-printify-sync')]);
        }

        // Schedule order sync.
        as_schedule_single_action(time(), 'wpwps_sync_order', [$order_id], 'wpwps_order_sync');

        wp_send_json_success([
            'message' => __('Order sync scheduled.', 'wp-woocommerce-printify-sync'),
        ]);
    }
}
