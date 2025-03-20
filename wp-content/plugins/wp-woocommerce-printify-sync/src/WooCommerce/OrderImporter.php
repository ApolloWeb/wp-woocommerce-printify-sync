<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce;

use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\OrderImporterInterface;

class OrderImporter implements OrderImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function importOrder(array $printifyOrder): int
    {
        // Check if order already exists
        $existingOrderId = $this->getWooOrderIdByPrintifyId($printifyOrder['id']);
        if ($existingOrderId) {
            return $existingOrderId;
        }

        // Create order object using WooCommerce CRUD methods
        $order = wc_create_order([
            'status' => 'pending',
        ]);

        if (is_wp_error($order)) {
            throw new \Exception($order->get_error_message());
        }

        // Add order metadata using HPOS-compatible methods
        $order->update_meta_data('_printify_id', $printifyOrder['id']);
        
        // Store the Printify order cost data - always divide by 100 as prices are in cents
        $totalPrice = (float)($printifyOrder['total_price'] ?? 0) / 100;
        $totalShipping = (float)($printifyOrder['total_shipping'] ?? 0) / 100;
        $totalRetail = $totalPrice + $totalShipping;
        
        $merchantCost = $this->calculateMerchantCost($printifyOrder);
        $profit = $totalRetail - $merchantCost;
        
        $order->update_meta_data('_printify_merchant_cost', $merchantCost);
        $order->update_meta_data('_printify_shipping_cost', $totalShipping);
        $order->update_meta_data('_printify_total_tax', ($printifyOrder['total_tax'] ?? 0) / 100);
        $order->update_meta_data('_printify_status', $printifyOrder['status'] ?? '');
        $order->update_meta_data('_printify_total_retail', $totalRetail);
        $order->update_meta_data('_printify_profit', $profit);
        $order->update_meta_data('_printify_imported_from', 'etsy');
        
        // Store shipping information if available
        if (!empty($printifyOrder['shipments'][0])) {
            $shipment = $printifyOrder['shipments'][0];
            $order->update_meta_data('_printify_tracking_carrier', $shipment['carrier'] ?? '');
            $order->update_meta_data('_printify_tracking_number', $shipment['number'] ?? '');
            $order->update_meta_data('_printify_tracking_url', $shipment['url'] ?? '');
        }
        
        // Add line items
        $this->addLineItems($order, $printifyOrder);
        
        // Add customer information
        $this->addCustomerInfo($order, $printifyOrder);
        
        // Add shipping information
        $this->addShippingInfo($order, $printifyOrder);
        
        // Add order notes if needed
        $order->add_order_note(
            sprintf('Order imported from Printify (ID: %s)', $printifyOrder['id']),
            0, // not customer-facing
            true // added by system
        );
        
        // Save the order
        $order->save();
        
        return $order->get_id();
    }
    
    /**
     * Calculate the total merchant cost from line items
     *
     * @param array $printifyOrder
     * @return float
     */
    private function calculateMerchantCost(array $printifyOrder): float
    {
        $merchantCost = 0;
        
        if (!empty($printifyOrder['line_items'])) {
            foreach ($printifyOrder['line_items'] as $item) {
                // Always treat cost and shipping cost as cents and divide by 100
                $itemCost = (float)($item['cost'] ?? 0) / 100;
                $shippingCost = (float)($item['shipping_cost'] ?? 0) / 100;
                $quantity = (int)($item['quantity'] ?? 1);
                
                // Add the product cost (cost price × quantity)
                $merchantCost += $itemCost * $quantity;
                
                // Add the shipping cost
                $merchantCost += $shippingCost;
            }
        }
        
        return $merchantCost;
    }
    
    /**
     * Normalize price value to ensure consistent format
     * 
     * @param mixed $price
     * @return float
     */
    private function normalizePrice($price): float
    {
        // Convert to float first
        $price = (float)$price;
        
        // Check if the price needs to be divided by 100 (whole number without decimal)
        // This is common for prices coming from Etsy/Printify API
        if ($price > 100 && floor($price) == $price) {
            $price = $price / 100;
        }
        
        return $price;
    }
    
    /**
     * Add line items to the order
     *
     * @param \WC_Order $order
     * @param array $printifyOrder
     */
    private function addLineItems(\WC_Order $order, array $printifyOrder): void
    {
        if (empty($printifyOrder['line_items'])) {
            return;
        }
        
        // Add debug log for Etsy order line items
        error_log("Processing line items for Printify order ID: {$printifyOrder['id']}");
        
        foreach ($printifyOrder['line_items'] as $lineItem) {
            $productTitle = $lineItem['metadata']['title'] ?? 'Printify Product';
            $variantLabel = $lineItem['metadata']['variant_label'] ?? '';
            
            if (!empty($variantLabel)) {
                $productTitle .= ' - ' . $variantLabel;
            }
            
            // Debug price values for Etsy/Printify orders
            $rawUnitPrice = $lineItem['metadata']['price'] ?? 0;
            $normalizedPrice = (float)$rawUnitPrice / 100;
            error_log("Line item price - Raw: {$rawUnitPrice}, Normalized: {$normalizedPrice}");
            
            // Create line item
            $item = new \WC_Order_Item_Product();
            $item->set_name($productTitle);
            $item->set_quantity($lineItem['quantity'] ?? 1);
            
            // Handle pricing - note the price in metadata is what the customer paid (in cents)
            $unitPrice = (float)($lineItem['metadata']['price'] ?? 0) / 100;
            $item->set_total($unitPrice * ($lineItem['quantity'] ?? 1));
            $item->set_subtotal($unitPrice * ($lineItem['quantity'] ?? 1));
            
            // Store original Printify data - store costs divided by 100
            $item->add_meta_data('_printify_product_id', $lineItem['product_id'] ?? '');
            $item->add_meta_data('_printify_variant_id', $lineItem['variant_id'] ?? '');
            $item->add_meta_data('_printify_sku', $lineItem['metadata']['sku'] ?? '');
            $item->add_meta_data('_printify_cost', (float)($lineItem['cost'] ?? 0) / 100);
            $item->add_meta_data('_printify_shipping_cost', (float)($lineItem['shipping_cost'] ?? 0) / 100);
            $item->add_meta_data('_printify_source', 'etsy');
            
            $order->add_item($item);
        }
        
        // We'll handle shipping separately in the addShippingInfo method
    }
    
    /**
     * Add customer information to the order
     *
     * @param \WC_Order $order
     * @param array $printifyOrder
     */
    private function addCustomerInfo(\WC_Order $order, array $printifyOrder): void
    {
        if (empty($printifyOrder['address_to'])) {
            return;
        }
        
        $address = $printifyOrder['address_to'];
        
        // Set billing address
        $order->set_billing_first_name($address['first_name'] ?? '');
        $order->set_billing_last_name($address['last_name'] ?? '');
        $order->set_billing_company($address['company'] ?? '');
        $order->set_billing_address_1($address['address1'] ?? '');
        $order->set_billing_address_2($address['address2'] ?? '');
        $order->set_billing_city($address['city'] ?? '');
        $order->set_billing_state($address['region'] ?? '');
        $order->set_billing_postcode($address['zip'] ?? '');
        $order->set_billing_country($address['country'] ?? '');
        $order->set_billing_email($address['email'] ?? '');
        $order->set_billing_phone($address['phone'] ?? '');
        
        // Set shipping address (same as billing)
        $order->set_shipping_first_name($address['first_name'] ?? '');
        $order->set_shipping_last_name($address['last_name'] ?? '');
        $order->set_shipping_company($address['company'] ?? '');
        $order->set_shipping_address_1($address['address1'] ?? '');
        $order->set_shipping_address_2($address['address2'] ?? '');
        $order->set_shipping_city($address['city'] ?? '');
        $order->set_shipping_state($address['region'] ?? '');
        $order->set_shipping_postcode($address['zip'] ?? '');
        $order->set_shipping_country($address['country'] ?? '');
    }
    
    /**
     * Add shipping information to the order
     *
     * @param \WC_Order $order
     * @param array $printifyOrder
     */
    private function addShippingInfo(\WC_Order $order, array $printifyOrder): void
    {
        // Calculate order total (retail price + shipping) with normalized values
        $totalPrice = (float)($printifyOrder['total_price'] ?? 0) / 100;
        $totalShipping = (float)($printifyOrder['total_shipping'] ?? 0) / 100;
        $totalAmount = $totalPrice + $totalShipping;
        
        // Log the price calculation
        error_log("Order totals - Price: {$totalPrice}, Shipping: {$totalShipping}, Total: {$totalAmount}");
        
        $order->set_total($totalAmount);
        
        // Add tiered shipping items instead of one combined shipping line
        if (!empty($printifyOrder['line_items'])) {
            $mainShippingCost = 0;
            $additionalItemsCost = 0;
            
            // Create shipping line items for each product
            $itemCount = count($printifyOrder['line_items']);
            
            // If there's shipping cost and line items, add proper shipping lines
            if ($totalShipping > 0 && $itemCount > 0) {
                if ($itemCount === 1) {
                    // Single item - just add the shipping line
                    $shipping_item = new \WC_Order_Item_Shipping();
                    $shipping_item->set_method_title('Printify Shipping');
                    $shipping_item->set_total($totalShipping);
                    $order->add_item($shipping_item);
                } else {
                    // Multiple items - implement tiered shipping
                    // First item's shipping
                    $firstItemShipping = (float)($printifyOrder['line_items'][0]['shipping_cost'] ?? 0) / 100;
                    
                    // Add shipping for first item
                    $first_shipping_item = new \WC_Order_Item_Shipping();
                    $first_shipping_item->set_method_title('Printify Shipping (First Item)');
                    $first_shipping_item->set_total($firstItemShipping);
                    $order->add_item($first_shipping_item);
                    
                    // Calculate additional items shipping (distribute remaining shipping cost)
                    $additionalShipping = $totalShipping - $firstItemShipping;
                    if ($additionalShipping > 0) {
                        $additional_shipping_item = new \WC_Order_Item_Shipping();
                        $additional_shipping_item->set_method_title('Printify Shipping (Additional Items)');
                        $additional_shipping_item->set_total($additionalShipping);
                        $order->add_item($additional_shipping_item);
                    }
                }
            }
        }
        
        // Add tracking info as order notes if available
        if (!empty($printifyOrder['shipments'][0])) {
            $shipment = $printifyOrder['shipments'][0];
            $carrier = $shipment['carrier'] ?? '';
            $trackingNumber = $shipment['number'] ?? '';
            $trackingUrl = $shipment['url'] ?? '';
            
            if (!empty($trackingNumber)) {
                $trackingNote = sprintf(
                    'Tracking information: %s - %s',
                    strtoupper($carrier),
                    $trackingNumber
                );
                
                if (!empty($trackingUrl)) {
                    $trackingNote .= sprintf(' <a href="%s" target="_blank">Track</a>', esc_url($trackingUrl));
                }
                
                $order->add_order_note($trackingNote, 1); // customer-facing
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getWooOrderIdByPrintifyId(string $printifyId): ?int
    {
        // Use WooCommerce's HPOS-compatible query method
        $orders = wc_get_orders([
            'limit' => 1,
            'meta_key' => '_printify_id',
            'meta_value' => $printifyId,
            'return' => 'ids',
        ]);
        
        return !empty($orders) ? (int)$orders[0] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function updateOrderStatus(int $orderId, string $status): bool
    {
        $order = wc_get_order($orderId);
        
        if (!$order) {
            return false;
        }
        
        // Map Printify status to WooCommerce status if needed
        // This assumes there's a method in CustomOrderStatuses to map them
        $customOrderStatuses = new CustomOrderStatuses();
        $wcStatus = $customOrderStatuses->mapPrintifyStatusToWooStatus($status);
        
        if ($wcStatus) {
            $order->update_status($wcStatus, sprintf('Status updated from Printify to: %s', $status));
            return true;
        }
        
        return false;
    }

    /**
     * Delete all orders imported from Printify
     * 
     * @return int Number of orders deleted
     */
    public function deleteAllPrintifyOrders(): int
    {
        // Find all orders with Printify ID using HPOS-compatible method
        $orders = wc_get_orders([
            'limit' => -1,
            'meta_key' => '_printify_id',
            'return' => 'ids',
        ]);
        
        if (empty($orders)) {
            return 0;
        }
        
        $count = 0;
        foreach ($orders as $orderId) {
            $order = wc_get_order($orderId);
            if ($order && $order->delete(true)) {
                $count++;
            }
        }
        
        return $count;
    }
}
