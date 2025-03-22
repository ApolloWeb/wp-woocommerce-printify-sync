<?php
/**
 * Webhook Controller for handling Printify webhooks.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Webhooks
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActionSchedulerService;
use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPIClient;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActivityService;

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
     * Printify API Client.
     *
     * @var PrintifyAPIClient
     */
    private $api_client;

    /**
     * Activity Service.
     *
     * @var ActivityService
     */
    private $activity_service;

    /**
     * Constructor.
     *
     * @param Logger               $logger           Logger instance.
     * @param ActionSchedulerService $action_scheduler Action Scheduler Service.
     * @param PrintifyAPIClient    $api_client       Printify API Client.
     * @param ActivityService      $activity_service Activity Service.
     */
    public function __construct(
        Logger $logger, 
        ActionSchedulerService $action_scheduler,
        PrintifyAPIClient $api_client,
        ActivityService $activity_service
    ) {
        $this->logger = $logger;
        $this->action_scheduler = $action_scheduler;
        $this->api_client = $api_client;
        $this->activity_service = $activity_service;
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
     * Register webhook endpoints with Printify.
     * 
     * @return array|WP_Error The array of registered webhooks or WP_Error on failure.
     */
    public function registerWebhooks()
    {
        $this->logger->info('Registering webhooks with Printify');

        // Check if API client is properly configured
        if (empty($this->api_client->getShopId())) {
            $this->logger->error('Failed to register webhooks: Shop ID not set');
            return new \WP_Error('missing_shop_id', 'Shop ID is not set. Please configure it in the settings.');
        }

        // Get existing webhooks to avoid duplicates
        $existing_webhooks = $this->api_client->getWebhooks();
        if (is_wp_error($existing_webhooks)) {
            $this->logger->error('Failed to get existing webhooks: ' . $existing_webhooks->get_error_message());
            return $existing_webhooks;
        }

        // Generate the webhook URL for our site
        $webhook_url = add_query_arg(
            'action', 
            'wpwps_printify_webhook', 
            get_home_url(null, 'wc-api/wpwps_printify_webhook')
        );

        $registered_webhooks = [];
        $events_to_register = [
            'product.update',
            'product.delete',
            'order.created',
            'order.update',
        ];

        // Check if each event is already registered
        $existing_event_urls = [];
        if (!empty($existing_webhooks) && is_array($existing_webhooks)) {
            foreach ($existing_webhooks as $webhook) {
                if (isset($webhook['event']) && isset($webhook['url'])) {
                    $existing_event_urls[$webhook['event']] = $webhook;
                }
            }
        }

        // Register webhooks for events that don't already exist
        foreach ($events_to_register as $event) {
            if (isset($existing_event_urls[$event]) && $existing_event_urls[$event]['url'] === $webhook_url) {
                // Webhook already exists for this event
                $registered_webhooks[$event] = $existing_event_urls[$event];
                $this->logger->info("Webhook for {$event} already registered with ID: {$existing_event_urls[$event]['id']}");
                continue;
            }

            // Delete existing webhook for this event if URL is different
            if (isset($existing_event_urls[$event])) {
                $this->logger->info("Deleting existing webhook for {$event} with different URL");
                $delete_result = $this->api_client->deleteWebhook($existing_event_urls[$event]['id']);
                if (is_wp_error($delete_result)) {
                    $this->logger->error("Failed to delete webhook: " . $delete_result->get_error_message());
                }
            }

            // Register new webhook
            $webhook_data = [
                'event' => $event,
                'url' => $webhook_url,
            ];

            $result = $this->api_client->createWebhook($webhook_data);

            if (is_wp_error($result)) {
                $this->logger->error("Failed to register webhook for {$event}: " . $result->get_error_message());
                continue;
            }

            $registered_webhooks[$event] = $result;
            $this->logger->info("Successfully registered webhook for {$event} with ID: {$result['id']}");
            
            // Log activity
            $this->activity_service->log('api_connection', sprintf(
                __('Registered Printify webhook for %s event', 'wp-woocommerce-printify-sync'),
                $event
            ), [
                'event' => $event,
                'webhook_id' => $result['id'],
                'time' => current_time('mysql')
            ]);
        }

        // Update our database with registered webhook IDs
        update_option('wpwps_registered_webhooks', $registered_webhooks);

        return $registered_webhooks;
    }

    /**
     * Unregister all webhooks from Printify.
     * 
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function unregisterWebhooks()
    {
        $this->logger->info('Unregistering webhooks from Printify');

        // Get existing webhooks
        $existing_webhooks = $this->api_client->getWebhooks();
        if (is_wp_error($existing_webhooks)) {
            $this->logger->error('Failed to get existing webhooks: ' . $existing_webhooks->get_error_message());
            return $existing_webhooks;
        }

        if (empty($existing_webhooks)) {
            $this->logger->info('No webhooks to unregister');
            delete_option('wpwps_registered_webhooks');
            return true;
        }

        $success = true;
        foreach ($existing_webhooks as $webhook) {
            if (!isset($webhook['id'])) {
                continue;
            }
            
            $result = $this->api_client->deleteWebhook($webhook['id']);
            
            if (is_wp_error($result)) {
                $this->logger->error("Failed to delete webhook {$webhook['id']}: " . $result->get_error_message());
                $success = false;
                continue;
            }
            
            $this->logger->info("Successfully deleted webhook {$webhook['id']}");
            
            // Log activity
            $this->activity_service->log('api_connection', sprintf(
                __('Unregistered Printify webhook for %s event', 'wp-woocommerce-printify-sync'),
                $webhook['event'] ?? 'unknown'
            ), [
                'webhook_id' => $webhook['id'],
                'time' => current_time('mysql')
            ]);
        }

        // Clean up our database
        delete_option('wpwps_registered_webhooks');

        return $success;
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
        
        // Log activity
        $this->activity_service->log('product_sync', sprintf(
            __('Received product update webhook for product ID %s', 'wp-woocommerce-printify-sync'),
            $product_id
        ), [
            'product_id' => $product_id,
            'source' => 'webhook',
            'time' => current_time('mysql')
        ]);
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
                
                // Log activity
                $this->activity_service->log('product_sync', sprintf(
                    __('Deleted WooCommerce product ID %s (Printify ID: %s) based on webhook', 'wp-woocommerce-printify-sync'),
                    $wc_product_id,
                    $product_id
                ), [
                    'product_id' => $wc_product_id,
                    'printify_id' => $product_id,
                    'source' => 'webhook',
                    'time' => current_time('mysql')
                ]);
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
        
        // Log activity
        $this->activity_service->log('order_sync', sprintf(
            __('Received order created webhook for order ID %s', 'wp-woocommerce-printify-sync'),
            $order_id
        ), [
            'order_id' => $order_id,
            'source' => 'webhook',
            'time' => current_time('mysql')
        ]);
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
        
        // Log activity
        $this->activity_service->log('order_sync', sprintf(
            __('Received order update webhook for order ID %s', 'wp-woocommerce-printify-sync'),
            $order_id
        ), [
            'order_id' => $order_id,
            'source' => 'webhook',
            'time' => current_time('mysql')
        ]);
    }
}
