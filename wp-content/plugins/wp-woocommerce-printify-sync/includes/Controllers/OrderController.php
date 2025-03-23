<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Controllers;

use Automattic\WooCommerce\Utilities\OrderUtil;

class OrderController {
    private $api;
    private $templating;
    private $logger;
    
    public function __construct(PrintifyApi $api, BladeEngine $templating, Logger $logger) {
        $this->api = $api;
        $this->templating = $templating;
        $this->logger = $logger;
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_get_orders', [$this, 'getOrders']);
        add_action('wp_ajax_wpwps_sync_order', [$this, 'syncOrder']);
        add_action('wp_ajax_wpwps_get_order_details', [$this, 'getOrderDetails']);
        
        // Check for HPOS compatibility
        add_action('before_woocommerce_init', function() {
            if (class_exists(OrderUtil::class)) {
                // Declare HPOS compatibility
                OrderUtil::declare_compatibility('custom_order_tables', plugin_basename(WPPS_FILE), true);
            }
        });
    }
    
    public function render(): void {
        $data = [
            'order_count' => $this->getOrderCount(),
            'sync_status' => $this->getSyncStatus(),
            'recent_orders' => $this->getRecentOrders(5)
        ];
        
        echo $this->templating->render('admin/orders', $data);
    }
    
    public function getOrders(): void {
        check_ajax_referer('wpps_admin');
        
        try {
            $page = isset($_GET['page']) ? absint($_GET['page']) : 1;
            $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 20;
            
            $args = [
                'limit' => $per_page,
                'page' => $page,
                'meta_query' => [
                    [
                        'key' => '_printify_linked',
                        'value' => '1',
                        'compare' => '='
                    ]
                ]
            ];
            
            $orders = wc_get_orders($args);
            $total = $this->getOrderCount();
            
            $formatted_orders = array_map(function($order) {
                return $this->formatOrderForResponse($order);
            }, $orders);
            
            wp_send_json_success([
                'orders' => $formatted_orders,
                'total' => $total,
                'pages' => ceil($total / $per_page)
            ]);
        } catch (\Exception $e) {
            $this->logger->log("Error fetching orders: " . $e->getMessage(), 'error');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public function syncOrder(): void {
        check_ajax_referer('wpps_admin');
        
        if (!isset($_POST['order_id'])) {
            wp_send_json_error(['message' => __('No order ID provided', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $order_id = absint($_POST['order_id']);
        
        try {
            $order = wc_get_order($order_id);
            if (!$order) {
                throw new \Exception(__('Order not found', 'wp-woocommerce-printify-sync'));
            }
            
            // Sync order logic goes here
            do_action('wpwps_sync_order', $order_id);
            
            wp_send_json_success([
                'message' => __('Order sync initiated', 'wp-woocommerce-printify-sync')
            ]);
        } catch (\Exception $e) {
            $this->logger->log("Error syncing order: " . $e->getMessage(), 'error');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    private function getOrderCount(): int {
        return wc_order_search(
            [
                'limit' => -1,
                'meta_query' => [
                    [
                        'key' => '_printify_linked',
                        'value' => '1',
                        'compare' => '='
                    ]
                ]
            ]
        )->total;
    }
    
    private function getSyncStatus(): array {
        return [
            'total' => $this->getOrderCount(),
            'synced' => $this->getSyncedOrderCount(),
            'pending' => $this->getPendingOrderCount(),
            'failed' => $this->getFailedOrderCount()
        ];
    }
    
    private function getRecentOrders(int $limit): array {
        $orders = wc_get_orders([
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => '_printify_linked',
                    'value' => '1',
                    'compare' => '='
                ]
            ]
        ]);
        
        return array_map(function($order) {
            return $this->formatOrderForResponse($order);
        }, $orders);
    }
    
    private function formatOrderForResponse(\WC_Order $order): array {
        return [
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'date' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'status' => $order->get_status(),
            'total' => $order->get_formatted_order_total(),
            'printify_id' => $this->getPrintifyId($order),
            'printify_status' => $this->getPrintifyStatus($order),
            'sync_status' => $this->getOrderSyncStatus($order)
        ];
    }
    
    private function getPrintifyId(\WC_Order $order): string {
        return $this->getOrderMeta($order, '_printify_order_id', '');
    }
    
    private function getPrintifyStatus(\WC_Order $order): string {
        return $this->getOrderMeta($order, '_printify_order_status', '');
    }
    
    private function getOrderSyncStatus(\WC_Order $order): string {
        return $this->getOrderMeta($order, '_printify_sync_status', 'pending');
    }
    
    private function getOrderMeta(\WC_Order $order, string $key, $default = null) {
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            return $order->get_meta($key, true, $default);
        }
        return get_post_meta($order->get_id(), $key, true) ?: $default;
    }
    
    private function getSyncedOrderCount(): int {
        return wc_order_search(
            [
                'limit' => -1,
                'meta_query' => [
                    [
                        'key' => '_printify_linked', 
                        'value' => '1'
                    ],
                    [
                        'key' => '_printify_sync_status',
                        'value' => 'synced'
                    ]
                ]
            ]
        )->total;
    }
    
    private function getPendingOrderCount(): int {
        return wc_order_search(
            [
                'limit' => -1,
                'meta_query' => [
                    [
                        'key' => '_printify_linked', 
                        'value' => '1'
                    ],
                    [
                        'key' => '_printify_sync_status',
                        'value' => 'pending'
                    ]
                ]
            ]
        )->total;
    }
    
    private function getFailedOrderCount(): int {
        return wc_order_search(
            [
                'limit' => -1,
                'meta_query' => [
                    [
                        'key' => '_printify_linked', 
                        'value' => '1'
                    ],
                    [
                        'key' => '_printify_sync_status',
                        'value' => 'failed'
                    ]
                ]
            ]
        )->total;
    }
}
