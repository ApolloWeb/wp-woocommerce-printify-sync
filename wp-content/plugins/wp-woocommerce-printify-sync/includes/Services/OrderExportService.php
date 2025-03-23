<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Core\HPOSCompat;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;

/**
 * Order Export Service
 */
class OrderExportService {
    /**
     * @var PrintifyApiClient
     */
    private $api;
    
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var array Order statuses that should trigger export
     */
    private $export_statuses = ['processing', 'completed'];
    
    /**
     * Constructor
     */
    public function __construct(PrintifyApiClient $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
        
        // Register hooks
        add_action('wp_ajax_wpwps_export_order', [$this, 'exportOrderAjax']);
        add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChange'], 10, 4);
        add_action('woocommerce_checkout_order_processed', [$this, 'handleNewOrder'], 20, 3);
    }
    
    /**
     * Handle new order creation
     * 
     * @param int $order_id Order ID
     * @param array $posted_data Posted checkout data
     * @param \WC_Order $order Order object 
     */
    public function handleNewOrder(int $order_id, array $posted_data, \WC_Order $order): void {
        // Check if auto-export is enabled
        if (!$this->shouldAutoExport()) {
            return;
        }
        
        // Check if order has printify products
        if (!$this->orderHasPrintifyProducts($order)) {
            return;
        }
        
        // Schedule export to avoid checkout delays
        as_schedule_single_action(
            time() + 30, 
            'wpwps_export_order', 
            [$order_id],
            'wp-woocommerce-printify-sync'
        );
    }
    
    /**
     * Handle order status changes
     * 
     * @param int $order_id Order ID
     * @param string $from_status Old status
     * @param string $to_status New status
     * @param \WC_Order $order Order object
     */
    public function handleOrderStatusChange(int $order_id, string $from_status, string $to_status, \WC_Order $order): void {
        // Check if we should export based on new status
        if (!in_array($to_status, $this->export_statuses)) {
            return;
        }
        
        // Check if order has already been exported
        $exported = HPOSCompat::getOrderMeta($order, '_printify_exported');
        if ($exported) {
            return;
        }
        
        // Check if order has printify products
        if (!$this->orderHasPrintifyProducts($order)) {
            return;
        }
        
        // Schedule export
        as_schedule_single_action(
            time(), 
            'wpwps_export_order', 
            [$order_id],
            'wp-woocommerce-printify-sync'
        );
    }
    
    /**
     * AJAX handler for manual order export
     */
    public function exportOrderAjax(): void {
        check_ajax_referer('wpps_admin');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
        }
        
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        
        if (empty($order_id)) {
            wp_send_json_error(['message' => __('Order ID is required', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        try {
            $result = $this->exportOrder($order_id);
            
            if ($result['success']) {
                wp_send_json_success([
                    'message' => __('Order exported successfully to Printify', 'wp-woocommerce-printify-sync'),
                    'order_id' => $order_id,
                    'printify_id' => $result['printify_id']
                ]);
            } else {
                wp_send_json_error(['message' => $result['message']]);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Export WooCommerce order to Printify
     * 
     * @param int $order_id WooCommerce order ID
     * @return array Result with success status and details
     */
    public function exportOrder(int $order_id): array {
        $this->logger->log("Exporting order #{$order_id} to Printify", 'info');
        
        try {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                throw new \Exception("Order #{$order_id} not found");
            }
            
            // Check if order has already been exported
            $printify_id = HPOSCompat::getOrderMeta($order, '_printify_order_id');
            
            if ($printify_id) {
                return [
                    'success' => true,
                    'message' => "Order #{$order_id} already exported to Printify (ID: {$printify_id})",
                    'printify_id' => $printify_id
                ];
            }
            
            // Prepare order data for Printify
            $printify_data = $this->prepareOrderData($order);
            
            // Create order on Printify
            $response = $this->api->createOrder($printify_data);
            
            // Store Printify order ID in WooCommerce order
            HPOSCompat::updateOrderMeta($order, '_printify_order_id', $response['id']);
            HPOSCompat::updateOrderMeta($order, '_printify_exported', 'yes');
            HPOSCompat::updateOrderMeta($order, '_printify_export_date', current_time('mysql'));
            
            // Add order note
            $order->add_order_note(
                sprintf(
                    __('Order exported to Printify (ID: %s)', 'wp-woocommerce-printify-sync'),
                    $response['id']
                )
            );
            
            $this->logger->log("Order #{$order_id} exported successfully to Printify as order {$response['id']}", 'info');
            
            return [
                'success' => true,
                'message' => "Order exported successfully",
                'printify_id' => $response['id']
            ];
            
        } catch (\Exception $e) {
            $this->logger->log("Error exporting order #{$order_id}: " . $e->getMessage(), 'error');
            
            if (isset($order)) {
                $order->add_order_note(
                    sprintf(
                        __('Failed to export order to Printify: %s', 'wp-woocommerce-printify-sync'),
                        $e->getMessage()
                    )
                );
            }
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Prepare order data for Printify
     * 
     * @param \WC_Order $order WooCommerce order
     * @return array Prepared order data
     */
    private function prepareOrderData(\WC_Order $order): array {
        $line_items = [];
        
        // Process each order item
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variant_id = $item->get_variation_id();
            
            // Get Printify IDs
            $printify_product_id = get_post_meta($product_id, '_printify_product_id', true);
            
            // Skip products not linked to Printify
            if (empty($printify_product_id)) {
                continue;
            }
            
            // Get variant ID if available
            $printify_variant_id = '';
            if ($variant_id) {
                $printify_variant_id = get_post_meta($variant_id, '_printify_variant_id', true);
            }
            
            // Add line item
            $line_items[] = [
                'product_id' => $printify_product_id,
                'variant_id' => $printify_variant_id ?: null,
                'quantity' => $item->get_quantity()
            ];
        }
        
        if (empty($line_items)) {
            throw new \Exception("No Printify products found in order");
        }
        
        // Prepare shipping address
        $shipping_address = [
            'first_name' => $order->get_shipping_first_name() ?: $order->get_billing_first_name(),
            'last_name' => $order->get_shipping_last_name() ?: $order->get_billing_last_name(),
            'address1' => $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
            'address2' => $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
            'city' => $order->get_shipping_city() ?: $order->get_billing_city(),
            'country' => $order->get_shipping_country() ?: $order->get_billing_country(),
            'state' => $order->get_shipping_state() ?: $order->get_billing_state(),
            'zip' => $order->get_shipping_postcode() ?: $order->get_billing_postcode(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone()
        ];
        
        // Build complete order data
        return [
            'external_id' => (string)$order->get_id(),
            'label' => sprintf('WC Order #%s', $order->get_order_number()),
            'line_items' => $line_items,
            'shipping_method' => 'standard',
            'shipping_address' => $shipping_address,
            'send_shipping_notification' => true
        ];
    }
    
    /**
     * Check if order has Printify products
     * 
     * @param \WC_Order $order WooCommerce order
     * @return bool Has Printify products
     */
    private function orderHasPrintifyProducts(\WC_Order $order): bool {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $printify_id = get_post_meta($product_id, '_printify_product_id', true);
            
            if (!empty($printify_id)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if auto-export is enabled
     * 
     * @return bool Auto-export enabled
     */
    private function shouldAutoExport(): bool {
        return get_option('wpwps_auto_export_orders', 'yes') === 'yes';
    }
}
