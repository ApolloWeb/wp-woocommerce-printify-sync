<?php

namespace ApolloWeb\WpWooCommercePrintifySync\Services;

use ApolloWeb\WpWooCommercePrintifySync\Helpers\Logger;
use ApolloWeb\WpWooCommercePrintifySync\Services\ApiClient;

class OrderSyncService
{
    protected $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function syncOrders()
    {
        $orders = $this->apiClient->fetchPrintifyOrders();

        foreach ($orders as $order) {
            // Logic to sync order with WooCommerce
            Logger::log('Syncing order: ' . $order['id']);
            $this->syncOrderWithWooCommerce($order);
        }
    }

    protected function syncOrderWithWooCommerce($order)
    {
        // Example logic to sync order with WooCommerce
        $wc_order = [
            'order_id' => $order['id'],
            'status' => $order['status'],
            'total' => $order['total'],
            'customer' => $order['customer'],
            'items' => $order['items'],
        ];

        // Example function to create or update WooCommerce order
        $this->createOrUpdateWooCommerceOrder($wc_order);
        Logger::log('Order synced: ' . $order['id']);
    }

    protected function createOrUpdateWooCommerceOrder($wc_order)
    {
        // Add logic to create or update WooCommerce order
        // Example: wc_create_order($wc_order);
        Logger::log('Creating/updating WooCommerce order: ' . $wc_order['order_id']);
    }
}