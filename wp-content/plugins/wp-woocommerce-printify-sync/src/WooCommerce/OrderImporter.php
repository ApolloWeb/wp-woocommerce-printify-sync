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
