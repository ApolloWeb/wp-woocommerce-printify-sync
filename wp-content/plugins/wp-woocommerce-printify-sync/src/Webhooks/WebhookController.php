<?php
/**
 * Webhook Controller for handling Printify webhooks.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Webhooks
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActionSchedulerService;

/**
 * Webhook Controller class.
 */
class WebhookController
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Action Scheduler Service.
     *
     * @var ActionSchedulerService
     */
    private $action_scheduler;

    /**
     * Constructor.
     *
     * @param Logger               $logger          Logger instance.
     * @param ActionSchedulerService $action_scheduler Action Scheduler Service.
     */
    public function __construct(Logger $logger, ActionSchedulerService $action_scheduler)
    {
        $this->logger = $logger;
        $this->action_scheduler = $action_scheduler;
    }

    /**
     * Initialize the controller.
     *
     * @return void
     */
    public function init()
    {
        // No additional initialization needed at this time
    }

    /**
     * Handle incoming Printify webhooks.
     *
     * @return void
     */
    public function handlePrintifyWebhook()
    {
        // Verify that this is a valid request
        $this->logger->info('Webhook received from Printify');

        // Get the request body
        $request_body = file_get_contents('php://input');
        if (empty($request_body)) {
            $this->logger->error('Empty webhook payload received');
            wp_send_json_error(['message' => 'Empty payload'], 400);
            exit;
        }

        // Decode the JSON payload
        $payload = json_decode($request_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Invalid JSON payload received: ' . json_last_error_msg());
            wp_send_json_error(['message' => 'Invalid JSON payload'], 400);
            exit;
        }

        // Validate the payload
        if (!isset($payload['event'])) {
            $this->logger->error('Invalid webhook payload: missing event');
            wp_send_json_error(['message' => 'Invalid payload: missing event'], 400);
            exit;
        }

        // Log the webhook event
        $this->logger->info('Processing webhook event: ' . $payload['event'], [
            'payload' => $payload,
        ]);

        // Handle different webhook events
        switch ($payload['event']) {
            case 'product.update':
                $this->handleProductUpdate($payload);
                break;

            case 'product.delete':
                $this->handleProductDelete($payload);
                break;

            case 'order.created':
                $this->handleOrderCreated($payload);
                break;

            case 'order.update':
                $this->handleOrderUpdate($payload);
                break;

            default:
                $this->logger->warning('Unhandled webhook event: ' . $payload['event']);
                wp_send_json_error(['message' => 'Unhandled event type'], 400);
                exit;
        }

        // Send success response
        wp_send_json_success(['message' => 'Webhook processed successfully']);
    }

    /**
     * Handle product update webhook event.
     *
     * @param array $payload Webhook payload.
     * @return void
     */
    private function handleProductUpdate($payload)
    {
        if (!isset($payload['data']['id'])) {
            $this->logger->error('Product update webhook missing product ID');
            return;
        }

        $product_id = $payload['data']['id'];
        $this->logger->info("Scheduling sync for product {$product_id} from webhook");

        // Schedule the product sync using Action Scheduler
        $this->action_scheduler->scheduleSyncProduct($product_id);
    }

    /**
     * Handle product delete webhook event.
     *
     * @param array $payload Webhook payload.
     * @return void
     */
    private function handleProductDelete($payload)
    {
        if (!isset($payload['data']['id'])) {
            $this->logger->error('Product delete webhook missing product ID');
            return;
        }

        $product_id = $payload['data']['id'];
        $this->logger->info("Handling deletion of product {$product_id} from webhook");

        // Find and delete the WooCommerce product matching this Printify ID
        $args = [
            'post_type' => 'product',
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => '_printify_product_id',
                    'value' => $product_id,
                    'compare' => '=',
                ],
            ],
            'fields' => 'ids',
        ];

        $products = get_posts($args);

        if (!empty($products)) {
            foreach ($products as $wc_product_id) {
                wp_delete_post($wc_product_id, true);
                $this->logger->info("Deleted WooCommerce product {$wc_product_id} linked to Printify product {$product_id}");
            }
        } else {
            $this->logger->warning("No WooCommerce product found for Printify product {$product_id}");
        }
    }

    /**
     * Handle order created webhook event.
     *
     * @param array $payload Webhook payload.
     * @return void
     */
    private function handleOrderCreated($payload)
    {
        if (!isset($payload['data']['id'])) {
            $this->logger->error('Order created webhook missing order ID');
            return;
        }

        $order_id = $payload['data']['id'];
        $this->logger->info("Scheduling sync for new order {$order_id} from webhook");

        // Schedule the order sync using Action Scheduler
        $this->action_scheduler->scheduleSyncOrder($order_id);
    }

    /**
     * Handle order update webhook event.
     *
     * @param array $payload Webhook payload.
     * @return void
     */
    private function handleOrderUpdate($payload)
    {
        if (!isset($payload['data']['id'])) {
            $this->logger->error('Order update webhook missing order ID');
            return;
        }

        $order_id = $payload['data']['id'];
        $this->logger->info("Scheduling sync for updated order {$order_id} from webhook");

        // Schedule the order sync using Action Scheduler
        $this->action_scheduler->scheduleSyncOrder($order_id);
    }
}
