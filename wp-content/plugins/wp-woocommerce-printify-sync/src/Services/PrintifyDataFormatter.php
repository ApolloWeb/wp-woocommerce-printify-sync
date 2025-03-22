<?php
/**
 * Printify data formatter.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Formats data for Printify API requests
 */
class PrintifyDataFormatter
{
    /**
     * Format shipping request for v2 shipping endpoint.
     *
     * @param array $address    Customer address.
     * @param array $line_items Array of line items.
     * @return array Formatted shipping request.
     */
    public function formatShippingRequest($address, $line_items)
    {
        return [
            'address_to' => $this->formatAddress($address),
            'line_items' => $this->formatLineItems($line_items)
        ];
    }
    
    /**
     * Format address for Printify API.
     *
     * @param array $address Customer address.
     * @return array Formatted address.
     */
    public function formatAddress($address)
    {
        return [
            'first_name' => $address['first_name'] ?? '',
            'last_name' => $address['last_name'] ?? '',
            'email' => $address['email'] ?? '',
            'phone' => $address['phone'] ?? '',
            'country' => $address['country'] ?? '',
            'region' => $address['state'] ?? '',
            'address1' => $address['address_1'] ?? '',
            'address2' => $address['address_2'] ?? '',
            'city' => $address['city'] ?? '',
            'zip' => $address['postcode'] ?? '',
        ];
    }
    
    /**
     * Format line items for Printify API.
     *
     * @param array $items Cart items.
     * @return array Formatted line items.
     */
    public function formatLineItems($items)
    {
        $formatted_items = [];
        
        foreach ($items as $item) {
            if (isset($item['product_id'], $item['variant_id'], $item['quantity'])) {
                $formatted_items[] = [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity']
                ];
            }
        }
        
        return $formatted_items;
    }
    
    /**
     * Format order data for submission to Printify.
     *
     * @param array $order     WooCommerce order data.
     * @param array $printify_data Printify product mappings.
     * @return array Formatted order data.
     */
    public function formatOrderData($order, $printify_data)
    {
        $order_data = [
            'external_id' => $order['id'],
            'label' => 'Order #' . $order['order_number'],
            'address_to' => $this->formatAddress($order['shipping']),
            'shipping_method' => $printify_data['shipping_method'] ?? 1,
            'send_shipping_notification' => true,
            'line_items' => []
        ];
        
        // Add line items
        foreach ($order['line_items'] as $item) {
            $printify_item = $printify_data['items'][$item['product_id']] ?? null;
            
            if ($printify_item) {
                $order_data['line_items'][] = [
                    'product_id' => $printify_item['printify_product_id'],
                    'variant_id' => $printify_item['printify_variant_id'],
                    'quantity' => $item['quantity']
                ];
            }
        }
        
        return $order_data;
    }
}
