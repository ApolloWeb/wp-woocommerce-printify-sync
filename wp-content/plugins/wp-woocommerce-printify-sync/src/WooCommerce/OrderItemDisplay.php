<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce;

/**
 * Class to handle the display of Printify information in WooCommerce orders
 */
class OrderItemDisplay {
    
    /**
     * Initialize hooks
     */
    public function __construct() {
        // Add custom meta data to order items in admin
        add_action('woocommerce_after_order_itemmeta', [$this, 'displayPrintifyItemMeta'], 10, 3);
        
        // Add Printify order total cost to order totals
        add_action('woocommerce_admin_order_totals_after_total', [$this, 'displayPrintifyOrderTotals']);
        
        // Add Printify order ID to order details
        add_action('woocommerce_admin_order_data_after_order_details', [$this, 'displayPrintifyOrderId']);
    }
    
    /**
     * Display Printify meta data for order items
     * 
     * @param int $item_id
     * @param \WC_Order_Item $item
     * @param \WC_Product|null $product
     */
    public function displayPrintifyItemMeta($item_id, $item, $product) {
        // Only for line items (products)
        if (!($item instanceof \WC_Order_Item_Product)) {
            return;
        }
        
        // Get Printify meta data
        $printify_product_id = $item->get_meta('_printify_product_id', true);
        $printify_variant_id = $item->get_meta('_printify_variant_id', true);
        $printify_cost = $item->get_meta('_printify_cost', true);
        $printify_shipping_cost = $item->get_meta('_printify_shipping_cost', true);
        
        // Calculate total cost
        $quantity = $item->get_quantity();
        $total_cost = ($printify_cost * $quantity) + $printify_shipping_cost;
        
        // Only display if we have Printify data
        if (empty($printify_product_id) && empty($printify_variant_id)) {
            return;
        }
        
        echo '<div class="printify-item-meta" style="margin-top: 10px; padding: 5px; background: #f8f8f8; border-left: 3px solid #2a7de1;">';
        echo '<strong>Printify Information:</strong><br>';
        
        if (!empty($printify_product_id)) {
            echo 'Product ID: ' . esc_html($printify_product_id) . '<br>';
        }
        
        if (!empty($printify_variant_id)) {
            echo 'Variant ID: ' . esc_html($printify_variant_id) . '<br>';
        }
        
        echo 'Base Cost: ' . wc_price($printify_cost) . ' × ' . $quantity . ' = ' . wc_price($printify_cost * $quantity) . '<br>';
        echo 'Shipping Cost: ' . wc_price($printify_shipping_cost) . '<br>';
        echo '<strong>Total Item Cost: ' . wc_price($total_cost) . '</strong>';
        echo '</div>';
    }
    
    /**
     * Display Printify order totals in admin
     * 
     * @param int $order_id
     */
    public function displayPrintifyOrderTotals($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Check if this is a Printify order
        $printify_id = $order->get_meta('_printify_id', true);
        if (empty($printify_id)) {
            return;
        }
        
        // Get merchant cost data
        $merchant_cost = $order->get_meta('_printify_merchant_cost', true);
        $profit = $order->get_meta('_printify_profit', true);
        
        if (!empty($merchant_cost)) {
            ?>
            <tr>
                <td class="label">Printify Cost:</td>
                <td width="1%"></td>
                <td class="total">
                    <?php echo wc_price($merchant_cost); ?>
                </td>
            </tr>
            <?php
        }
        
        if (!empty($profit)) {
            ?>
            <tr>
                <td class="label">Profit:</td>
                <td width="1%"></td>
                <td class="total">
                    <?php echo wc_price($profit); ?>
                </td>
            </tr>
            <?php
        }
    }
    
    /**
     * Display Printify order ID in admin
     * 
     * @param \WC_Order $order
     */
    public function displayPrintifyOrderId($order) {
        $printify_id = $order->get_meta('_printify_id', true);
        if (empty($printify_id)) {
            return;
        }
        
        echo '<p class="form-field form-field-wide">';
        echo '<strong>' . esc_html__('Printify Order ID:', 'wp-woocommerce-printify-sync') . '</strong> ';
        echo esc_html($printify_id);
        echo '</p>';
    }
}
