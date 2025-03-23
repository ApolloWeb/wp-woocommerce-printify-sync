<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Core\HPOSCompat;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;

/**
 * WooCommerce Hooks Service
 * 
 * Registers all WooCommerce hooks used to trigger Printify API actions
 */
class WooCommerceHooks {
    // ...existing code...
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        // Order hooks
        add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChange'], 10, 4);
        add_action('woocommerce_checkout_order_processed', [$this, 'handleNewOrder'], 20, 3);
        add_action('woocommerce_payment_complete', [$this, 'handlePaymentComplete'], 10);
        add_action('woocommerce_order_refunded', [$this, 'handleOrderRefund'], 10, 2);
        
        // Exchange rate handling
        add_action('woocommerce_checkout_create_order', [$this, 'lockExchangeRate'], 10, 2);
        
        // Product hooks
        add_action('woocommerce_update_product', [$this, 'handleProductUpdate'], 10, 2);
        add_action('woocommerce_new_product', [$this, 'handleNewProduct'], 10);
        add_action('before_delete_post', [$this, 'handleProductDeletion'], 10);
        
        // Admin AJAX hooks
        add_action('wp_ajax_wpwps_sync_order', [$this->order_sync, 'syncOrderAjax']);
        add_action('wp_ajax_wpwps_import_product', [$this->product_sync, 'importProductAjax']);
        add_action('wp_ajax_wpwps_sync_product', [$this->product_sync, 'syncProductAjax']);
    }
    
    /**
     * Lock exchange rate at checkout
     * 
     * @param \WC_Order $order Order object
     * @param array $data Checkout data
     */
    public function lockExchangeRate(\WC_Order $order, array $data): void {
        // Get current exchange rate - this would be fetched from your chosen currency conversion service
        // For this example, we'll use a simple static value
        $exchange_rate = 1.0; // Default 1:1 (no conversion)
        
        // For multi-currency stores, you'd get the actual rate based on the store's base currency
        // and the customer's selected currency
        if (function_exists('get_woocommerce_currency') && function_exists('get_option')) {
            $store_currency = get_option('woocommerce_currency');
            $order_currency = $order->get_currency();
            
            if ($store_currency !== $order_currency) {
                // In a real implementation, you would get the actual exchange rate
                // from a currency conversion service or plugin
                $exchange_rate = $this->getExchangeRate($store_currency, $order_currency);
            }
        }
        
        // Lock the exchange rate in the order
        HPOSCompat::updateOrderMeta($order, '_printify_exchange_rate', $exchange_rate);
        HPOSCompat::updateOrderMeta($order, '_printify_base_currency', get_option('woocommerce_currency'));
        HPOSCompat::updateOrderMeta($order, '_printify_order_currency', $order->get_currency());
    }
    
    /**
     * Handle new order creation
     * 
     * @param int $order_id Order ID
     * @param array $posted_data Posted checkout data
     * @param \WC_Order $order Order object
     */
    public function handleNewOrder(int $order_id, array $posted_data, \WC_Order $order): void {
        // Check if this order contains Printify products
        $has_printify_products = false;
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && get_post_meta($product->get_id(), '_printify_product_id', true)) {
                $has_printify_products = true;
                break;
            }
        }
        
        if ($has_printify_products) {
            // Mark order as containing Printify products
            HPOSCompat::updateOrderMeta($order, '_printify_linked', '1');
            
            // Calculate estimated delivery date
            $production_days = (int) get_option('wpwps_production_days', 3);
            $shipping_days = (int) get_option('wpwps_shipping_days', 7);
            
            $estimated_delivery = strtotime("+{$production_days} days +{$shipping_days} days");
            HPOSCompat::updateOrderMeta($order, '_printify_estimated_delivery', date('Y-m-d', $estimated_delivery));
            
            // Auto-sync is configurable via settings
            $auto_sync = get_option('wpwps_auto_sync_orders', 'yes');
            
            // For orders that are already paid (direct payment methods)
            if ($auto_sync === 'yes' && $order->is_paid()) {
                $this->order_sync->syncOrder($order_id);
            }
        }
    }
    
    /**
     * Handle payment complete
     * 
     * @param int $order_id Order ID
     */
    public function handlePaymentComplete(int $order_id): void {
        $order = wc_get_order($order_id);
        
        if (!$order || !$this->shouldProcessOrder($order)) {
            return;
        }
        
        $auto_sync = get_option('wpwps_auto_sync_orders', 'yes');
        
        if ($auto_sync === 'yes') {
            $this->order_sync->syncOrder($order_id);
        }
    }
    
    /**
     * Get exchange rate between two currencies
     * 
     * @param string $from_currency From currency code
     * @param string $to_currency To currency code
     * @return float Exchange rate
     */
    private function getExchangeRate(string $from_currency, string $to_currency): float {
        // In a real implementation, you would call an exchange rate API
        // or use a multi-currency plugin's API to get the actual rate
        
        // For this example, we'll just return a dummy value
        return 1.0;
    }
    
    // ...existing code...
}
