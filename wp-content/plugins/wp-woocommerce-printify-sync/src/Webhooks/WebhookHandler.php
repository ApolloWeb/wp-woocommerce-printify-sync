<?php
/**
 * Webhook handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Webhooks
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Products\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderSync;

/**
 * Handles incoming webhooks from Printify.
 */
class WebhookHandler
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Product sync service.
     *
     * @var ProductSync
     */
    private $product_sync;

    /**
     * Order sync service.
     *
     * @var OrderSync
     */
    private $order_sync;

    /**
     * Constructor.
     *
     * @param Logger      $logger       Logger instance.
     * @param ProductSync $product_sync Product sync service.
     * @param OrderSync   $order_sync   Order sync service.
     */
    public function __construct(Logger $logger, ProductSync $product_sync, OrderSync $order_sync)
    {
        $this->logger = $logger;
        $this->product_sync = $product_sync;
        $this->order_sync = $order_sync;
    }

    /**
     * Handle webhook event.
     *
     * @param string $event   Event type.
     * @param array  $payload Event payload.
     */
    public function handleEvent($event, $payload)
    {
        $this->logger->info('Processing webhook event', [
            'event' => $event,
            'payload_summary' => $this->getSummary($payload),
        ]);

        switch ($event) {
            case 'order:created':
                $this->handleOrderCreated($payload);
                break;

            case 'order:update':
                $this->handleOrderUpdate($payload);
                break;

            case 'product:update':
                $this->handleProductUpdate($payload);
                break;

            case 'shop:disconnected':
                $this->handleShopDisconnected($payload);
                break;

            default:
                $this->logger->warning('Unhandled webhook event', ['event' => $event]);
                break;
        }
    }

    /**
     * Handle order created event.
     *
     * @param array $payload Event payload.
     */
    private function handleOrderCreated($payload)
    {
        if (empty($payload['id'])) {
            $this->logger->error('Order created webhook missing order ID');
            return;
        }

        $this->logger->info('Processing order created webhook', ['printify_order_id' => $payload['id']]);
        
        // Find WooCommerce order by external ID
        if (!empty($payload['external_id'])) {
            $wc_order_id = $this->findWooCommerceOrder($payload['external_id']);
            
            if ($wc_order_id) {
                // Update order with Printify data
                $this->order_sync->updateOrderWithPrintifyData($wc_order_id, $payload);
            } else {
                $this->logger->warning('No matching WooCommerce order found for Printify order', [
                    'printify_order_id' => $payload['id'],
                    'external_id' => $payload['external_id'],
                ]);
            }
        }
    }

    /**
     * Handle order update event.
     *
     * @param array $payload Event payload.
     */
    private function handleOrderUpdate($payload)
    {
        if (empty($payload['id'])) {
            $this->logger->error('Order update webhook missing order ID');
            return;
        }

        $this->logger->info('Processing order update webhook', [
            'printify_order_id' => $payload['id'],
            'status' => $payload['status'] ?? 'unknown',
        ]);

        // Find WooCommerce order by Printify order ID
        $wc_order_id = $this->findWooCommerceOrderByPrintifyId($payload['id']);
        
        if ($wc_order_id) {
            // Update order status based on Printify status
            $this->order_sync->updateOrderStatus($wc_order_id, $payload);
            
            // If the order has tracking info, update it
            if (!empty($payload['shipments']) && is_array($payload['shipments'])) {
                $this->order_sync->updateOrderTracking($wc_order_id, $payload['shipments']);
            }
        } else {
            $this->logger->warning('No matching WooCommerce order found for Printify order', [
                'printify_order_id' => $payload['id'],
            ]);
        }
    }

    /**
     * Handle product update event.
     *
     * @param array $payload Event payload.
     */
    private function handleProductUpdate($payload)
    {
        if (empty($payload['id'])) {
            $this->logger->error('Product update webhook missing product ID');
            return;
        }

        $this->logger->info('Processing product update webhook', ['printify_product_id' => $payload['id']]);
        
        // Find WooCommerce product by Printify product ID
        $wc_product_id = $this->findWooCommerceProductByPrintifyId($payload['id']);
        
        if ($wc_product_id) {
            // Update product with latest data from Printify
            $this->product_sync->syncSingleProduct($payload['id'], $wc_product_id);
        } else {
            $this->logger->warning('No matching WooCommerce product found for Printify product', [
                'printify_product_id' => $payload['id'],
            ]);
        }
    }

    /**
     * Handle shop disconnected event.
     *
     * @param array $payload Event payload.
     */
    private function handleShopDisconnected($payload)
    {
        $this->logger->warning('Shop disconnected from Printify', [
            'shop_id' => $payload['shop_id'] ?? 'unknown',
        ]);
        
        // Add admin notice about disconnection
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Admin\NoticeManager')) {
            \ApolloWeb\WPWooCommercePrintifySync\Admin\NoticeManager::addNotice(
                __('Your shop has been disconnected from Printify. Please reconnect to continue receiving updates.', 'wp-woocommerce-printify-sync'),
                'error',
                true,
                'printify_shop_disconnected'
            );
        }
    }

    /**
     * Find WooCommerce order by external ID.
     *
     * @param string $external_id External order ID.
     * @return int|null WooCommerce order ID or null if not found.
     */
    private function findWooCommerceOrder($external_id)
    {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_order_number' AND meta_value = %s LIMIT 1",
            $external_id
        ));
    }

    /**
     * Find WooCommerce order by Printify order ID.
     *
     * @param string $printify_id Printify order ID.
     * @return int|null WooCommerce order ID or null if not found.
     */
    private function findWooCommerceOrderByPrintifyId($printify_id)
    {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_printify_order_id' AND meta_value = %s LIMIT 1",
            $printify_id
        ));
    }

    /**
     * Find WooCommerce product by Printify product ID.
     *
     * @param string $printify_id Printify product ID.
     * @return int|null WooCommerce product ID or null if not found.
     */
    private function findWooCommerceProductByPrintifyId($printify_id)
    {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_printify_product_id' AND meta_value = %s LIMIT 1",
            $printify_id
        ));
    }

    /**
     * Get payload summary for logging.
     *
     * @param array $payload Event payload.
     * @return array Summarized payload.
     */
    private function getSummary($payload)
    {
        $summary = [];
        
        // Extract only essential information for logging
        $keys_to_extract = ['id', 'external_id', 'status', 'shop_id'];
        
        foreach ($keys_to_extract as $key) {
            if (isset($payload[$key])) {
                $summary[$key] = $payload[$key];
            }
        }
        
        return $summary;
    }
}
