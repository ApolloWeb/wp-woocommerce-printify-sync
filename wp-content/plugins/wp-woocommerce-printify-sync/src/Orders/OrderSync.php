<?php
/**
 * Order synchronization functionality.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Orders
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPIClient;
use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActionSchedulerService;
use ApolloWeb\WPWooCommercePrintifySync\Services\EmailService;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActivityService;
use WP_Error;

/**
 * Class for syncing orders between Printify and WooCommerce.
 */
class OrderSync
{
    /**
     * Printify API client.
     *
     * @var PrintifyAPIClient
     */
    private $api_client;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Action Scheduler service.
     *
     * @var ActionSchedulerService
     */
    private $action_scheduler;

    /**
     * Email Service instance.
     *
     * @var EmailService
     */
    private $email_service;

    /**
     * Activity Service instance.
     *
     * @var ActivityService
     */
    private $activity_service;

    /**
     * Order status mapping.
     *
     * @var array
     */
    private $status_mapping = [
        // Pre-production statuses
        'on_hold' => 'on-hold',
        'awaiting_customer_evidence' => 'on-hold',
        'submit_order' => 'processing',
        'action_required' => 'on-hold',
        
        // Production statuses
        'in_production' => 'processing',
        'has_issues' => 'on-hold',
        'canceled_by_provider' => 'cancelled',
        'canceled' => 'cancelled',
        
        // Shipping statuses
        'ready_to_ship' => 'processing',
        'shipped' => 'completed',
        'on_the_way' => 'completed',
        'available_for_pickup' => 'completed',
        'out_for_delivery' => 'completed',
        'delivery_attempt' => 'completed',
        'shipping_issue' => 'on-hold',
        'return_to_sender' => 'on-hold',
        'delivered' => 'completed',
        
        // Refund and reprint statuses
        'refund_awaiting_customer_evidence' => 'on-hold',
        'refund_requested' => 'on-hold',
        'refund_approved' => 'refunded',
        'refund_declined' => 'on-hold',
        'reprint_awaiting_customer_evidence' => 'on-hold',
        'reprint_requested' => 'processing',
        'reprint_approved' => 'processing',
        'reprint_declined' => 'on-hold',
    ];

    /**
     * Constructor.
     *
     * @param PrintifyAPIClient     $api_client       Printify API client.
     * @param Logger                $logger           Logger instance.
     * @param ActionSchedulerService $action_scheduler Action scheduler service.
     * @param EmailService          $email_service    Email service.
     * @param ActivityService       $activity_service Activity service.
     */
    public function __construct(
        PrintifyAPIClient $api_client, 
        Logger $logger, 
        ActionSchedulerService $action_scheduler,
        EmailService $email_service,
        ActivityService $activity_service
    ) {
        $this->api_client = $api_client;
        $this->logger = $logger;
        $this->action_scheduler = $action_scheduler;
        $this->email_service = $email_service;
        $this->activity_service = $activity_service;
    }

    /**
     * Initialize the order sync.
     *
     * @return void
     */
    public function init()
    {
        // Register HPOS compatibility
        add_action('before_woocommerce_init', function() {
            if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', WPWPS_PLUGIN_BASENAME, true);
            }
        });
    }

    /**
     * Sync orders from Printify to WooCommerce.
     *
     * @return array|WP_Error Result of the sync operation.
     */
    public function syncOrders()
    {
        $this->logger->info('Starting order sync from Printify');

        // Check if shop ID is set
        $shop_id = $this->api_client->getShopId();
        if (empty($shop_id)) {
            $this->logger->error('Shop ID not set, cannot sync orders');
            return new WP_Error('missing_shop_id', 'Shop ID is not set. Please configure it in the settings.');
        }

        $page = 1;
        $per_page = 50;
        $total_synced = 0;
        $total_failed = 0;

        // This is a potentially long process, so increase time limit if possible
        if (!ini_get('safe_mode')) {
            set_time_limit(300); // 5 minutes
        }

        do {
            $this->logger->info("Fetching orders page {$page} from Printify");
            
            // Get orders from Printify
            $response = $this->api_client->getOrders($page, $per_page);
            
            if (is_wp_error($response)) {
                $this->logger->error('Error fetching orders: ' . $response->get_error_message());
                return $response;
            }

            // Check if we have orders
            if (empty($response['data'])) {
                $this->logger->info('No more orders found');
                break;
            }

            $orders = $response['data'];
            $this->logger->info('Found ' . count($orders) . ' orders on page ' . $page);

            // Process each order
            foreach ($orders as $order) {
                // Schedule individual order sync
                $this->action_scheduler->scheduleSyncOrder($order['id']);
                $total_synced++;
            }

            // Check if there are more pages
            $total_pages = isset($response['last_page']) ? (int) $response['last_page'] : 1;
            $page++;
        } while ($page <= $total_pages);

        $this->logger->info("Finished scheduling sync for {$total_synced} orders. Failed: {$total_failed}");

        $this->activity_service->log('order_sync', sprintf(
            __('Synced %d orders from Printify', 'wp-woocommerce-printify-sync'),
            $total_synced
        ), [
            'total_synced' => $total_synced,
            'total_failed' => $total_failed,
            'time' => current_time('mysql')
        ]);

        return [
            'success' => true,
            'total_synced' => $total_synced,
            'total_failed' => $total_failed,
        ];
    }

    /**
     * Sync a single order from Printify.
     *
     * @param string $printify_order_id Printify order ID.
     * @return array|WP_Error Result of the sync operation.
     */
    public function syncSingleOrder($printify_order_id)
    {
        $this->logger->info("Syncing order {$printify_order_id} from Printify");

        // Get the order details from Printify
        $order_data = $this->api_client->getOrder($printify_order_id);
        
        if (is_wp_error($order_data)) {
            $this->logger->error("Error fetching order {$printify_order_id}: " . $order_data->get_error_message());
            return $order_data;
        }

        // Check if the order exists in WooCommerce
        $wc_order_id = $this->getWooCommerceOrderIdByPrintifyId($printify_order_id);
        
        if ($wc_order_id) {
            // Update existing order
            $result = $this->updateWooCommerceOrder($wc_order_id, $order_data);
        } else {
            // Create new order
            $result = $this->createWooCommerceOrder($order_data);
        }

        if (is_wp_error($result)) {
            $this->logger->error("Error syncing order {$printify_order_id}: " . $result->get_error_message());
            return $result;
        }

        $this->logger->info("Successfully synced order {$printify_order_id}");

        $this->activity_service->log('order_sync', sprintf(
            __('Synced order #%s from Printify', 'wp-woocommerce-printify-sync'),
            $order_data['id']
        ), [
            'order_id' => $wc_order_id,
            'printify_id' => $order_data['id'],
            'status' => $order_data['status'],
            'time' => current_time('mysql')
        ]);
        
        return [
            'success' => true,
            'order_id' => $result,
            'message' => sprintf(
                __('Order %s successfully synced.', 'wp-woocommerce-printify-sync'),
                $printify_order_id
            ),
        ];
    }

    /**
     * Handle WooCommerce order status change.
     *
     * @param int    $order_id    WooCommerce order ID.
     * @param string $old_status  Old order status.
     * @param string $new_status  New order status.
     * @return void
     */
    public function handleOrderStatusChange($order_id, $old_status, $new_status)
    {
        // Get the Printify order ID
        $printify_order_id = get_post_meta($order_id, '_printify_order_id', true);
        
        if (empty($printify_order_id)) {
            // Not a Printify order
            return;
        }
        
        $this->logger->info("Order {$order_id} status changed from {$old_status} to {$new_status}");
        
        // Check if we need to update Printify
        if ($new_status === 'processing' && $old_status === 'pending') {
            // Order was paid, send to Printify for production
            $this->sendOrderToPrintify($order_id, $printify_order_id);
        }
        
        // Check if we need to notify the customer about specific status changes
        if (in_array($new_status, ['completed', 'on-hold', 'refunded'])) {
            $this->notifyCustomerAboutOrderStatus($order_id, $new_status);
        }

        $this->activity_service->log('order_status', sprintf(
            __('Order #%s status changed from %s to %s', 'wp-woocommerce-printify-sync'),
            $order_id,
            $old_status,
            $new_status
        ), [
            'order_id' => $order_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'time' => current_time('mysql')
        ]);
    }

    /**
     * Get WooCommerce order ID by Printify order ID.
     *
     * @param string $printify_order_id Printify order ID.
     * @return int|false WooCommerce order ID or false if not found.
     */
    private function getWooCommerceOrderIdByPrintifyId($printify_order_id)
    {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_printify_order_id' AND meta_value = %s LIMIT 1",
            $printify_order_id
        );
        
        $order_id = $wpdb->get_var($query);
        
        return $order_id ? (int) $order_id : false;
    }

    /**
     * Create a new WooCommerce order from Printify data.
     *
     * @param array $order_data Printify order data.
     * @return int|WP_Error WooCommerce order ID or error.
     */
    private function createWooCommerceOrder($order_data)
    {
        $this->logger->info("Creating new WooCommerce order from Printify order: {$order_data['id']}");
        
        // Create a new order
        $order = wc_create_order();
        
        // Set order date
        if (!empty($order_data['created_at'])) {
            $order->set_date_created($order_data['created_at']);
        }
        
        // Set customer details
        if (!empty($order_data['address_to'])) {
            $address = $order_data['address_to'];
            
            // Set billing address
            $order->set_billing_first_name($address['first_name'] ?? '');
            $order->set_billing_last_name($address['last_name'] ?? '');
            $order->set_billing_company($address['company'] ?? '');
            $order->set_billing_address_1($address['address1'] ?? '');
            $order->set_billing_address_2($address['address2'] ?? '');
            $order->set_billing_city($address['city'] ?? '');
            $order->set_billing_state($address['state'] ?? '');
            $order->set_billing_postcode($address['zip'] ?? '');
            $order->set_billing_country($address['country'] ?? '');
            $order->set_billing_email($address['email'] ?? '');
            $order->set_billing_phone($address['phone'] ?? '');
            
            // Set shipping address (same as billing for now)
            $order->set_shipping_first_name($address['first_name'] ?? '');
            $order->set_shipping_last_name($address['last_name'] ?? '');
            $order->set_shipping_company($address['company'] ?? '');
            $order->set_shipping_address_1($address['address1'] ?? '');
            $order->set_shipping_address_2($address['address2'] ?? '');
            $order->set_shipping_city($address['city'] ?? '');
            $order->set_shipping_state($address['state'] ?? '');
            $order->set_shipping_postcode($address['zip'] ?? '');
            $order->set_shipping_country($address['country'] ?? '');
        }
        
        // Add line items
        if (!empty($order_data['line_items'])) {
            foreach ($order_data['line_items'] as $item) {
                $this->addLineItem($order, $item);
            }
        }
        
        // Add shipping cost
        if (!empty($order_data['shipping_cost'])) {
            $shipping_item = new \WC_Order_Item_Shipping();
            $shipping_item->set_method_title('Printify Shipping');
            $shipping_item->set_total($order_data['shipping_cost']);
            $order->add_item($shipping_item);
        }
        
        // Add tax if applicable
        if (!empty($order_data['tax_cost'])) {
            $tax_item = new \WC_Order_Item_Tax();
            $tax_item->set_rate_code('PRINTIFY-TAX');
            $tax_item->set_tax_total($order_data['tax_cost']);
            $order->add_item($tax_item);
        }
        
        // Calculate totals
        $order->calculate_totals();
        
        // Set order status based on Printify status
        $printify_status = $order_data['status'] ?? 'pending';
        $wc_status = $this->mapOrderStatus($printify_status);
        $order->set_status($wc_status);
        
        // Add Printify metadata
        $order->update_meta_data('_printify_order_id', $order_data['id']);
        $order->update_meta_data('_printify_last_synced', current_time('mysql'));
        $order->update_meta_data('_printify_status', $printify_status);
        
        // Add tracking information if available
        if (!empty($order_data['shipments'])) {
            $this->addTrackingInfo($order, $order_data['shipments']);
        }
        
        // Add estimated delivery date if available
        if (!empty($order_data['estimated_delivery_date'])) {
            $order->update_meta_data('_printify_estimated_delivery_date', $order_data['estimated_delivery_date']);
        }
        
        // Save the order
        $order_id = $order->save();
        
        if (!$order_id) {
            return new WP_Error('order_creation_failed', __('Failed to create WooCommerce order.', 'wp-woocommerce-printify-sync'));
        }
        
        $this->logger->info("Created WooCommerce order ID: {$order_id}");
        
        // Add order note
        $order->add_order_note(
            sprintf(
                __('Order imported from Printify. Printify Order ID: %s', 'wp-woocommerce-printify-sync'),
                $order_data['id']
            )
        );
        
        return $order_id;
    }

    /**
     * Update an existing WooCommerce order with Printify data.
     *
     * @param int   $order_id   WooCommerce order ID.
     * @param array $order_data Printify order data.
     * @return int|WP_Error WooCommerce order ID or error.
     */
    private function updateWooCommerceOrder($order_id, $order_data)
    {
        $this->logger->info("Updating WooCommerce order ID: {$order_id}");
        
        // Get the WooCommerce order
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('order_not_found', __('WooCommerce order not found.', 'wp-woocommerce-printify-sync'));
        }
        
        // Update order status
        $printify_status = $order_data['status'] ?? 'pending';
        $wc_status = $this->mapOrderStatus($printify_status);
        $current_status = 'wc-' . $order->get_status();
        
        if ('wc-' . $wc_status !== $current_status) {
            $order->set_status($wc_status);
            $order->add_order_note(
                sprintf(
                    __('Order status updated from Printify: %s', 'wp-woocommerce-printify-sync'),
                    $printify_status
                )
            );
        }
        
        // Update Printify metadata
        $order->update_meta_data('_printify_last_synced', current_time('mysql'));
        $order->update_meta_data('_printify_status', $printify_status);
        
        // Update tracking information if available
        if (!empty($order_data['shipments'])) {
            $this->addTrackingInfo($order, $order_data['shipments']);
        }
        
        // Update estimated delivery date if available
        if (!empty($order_data['estimated_delivery_date'])) {
            $order->update_meta_data('_printify_estimated_delivery_date', $order_data['estimated_delivery_date']);
        }
        
        // Save the order
        $order->save();
        
        $this->logger->info("Updated WooCommerce order ID: {$order_id}");
        
        return $order_id;
    }

    /**
     * Add line item to order.
     *
     * @param \WC_Order $order WooCommerce order.
     * @param array     $item  Line item data from Printify.
     * @return void
     */
    private function addLineItem($order, $item)
    {
        // Try to find the WooCommerce product
        $variant_id = null;
        
        if (!empty($item['product_id']) && !empty($item['variant_id'])) {
            // Find the WooCommerce product by Printify product ID
            $product_id = $this->getWooCommerceProductIdByPrintifyId($item['product_id']);
            
            if ($product_id) {
                // Find the WooCommerce variant by Printify variant ID
                $variant_mapping = get_post_meta($product_id, '_printify_variant_ids', true) ?: [];
                
                if (isset($variant_mapping[$item['variant_id']])) {
                    $variant_id = $variant_mapping[$item['variant_id']];
                }
            }
        }
        
        // Create the line item
        $line_item = new \WC_Order_Item_Product();
        
        if ($variant_id) {
            $variant = wc_get_product($variant_id);
            
            if ($variant) {
                $line_item->set_product($variant);
                $line_item->set_variation_id($variant_id);
                $line_item->set_product_id($variant->get_parent_id());
            }
        }
        
        // Set line item data
        $line_item->set_name(isset($item['title']) ? $item['title'] : 'Printify Product');
        $line_item->set_quantity(isset($item['quantity']) ? $item['quantity'] : 1);
        $line_item->set_total(isset($item['price']) ? $item['price'] : 0);
        $line_item->set_subtotal(isset($item['price']) ? $item['price'] : 0);
        
        // Add Printify metadata to line item
        $line_item->add_meta_data('_printify_product_id', $item['product_id'] ?? '', true);
        $line_item->add_meta_data('_printify_variant_id', $item['variant_id'] ?? '', true);
        $line_item->add_meta_data('_printify_cost_price', $item['cost'] ?? 0, true);
        
        // Add the line item to the order
        $order->add_item($line_item);
    }

    /**
     * Get WooCommerce product ID by Printify product ID.
     *
     * @param string $printify_product_id Printify product ID.
     * @return int|false WooCommerce product ID or false if not found.
     */
    private function getWooCommerceProductIdByPrintifyId($printify_product_id)
    {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_printify_product_id' AND meta_value = %s LIMIT 1",
            $printify_product_id
        );
        
        $product_id = $wpdb->get_var($query);
        
        return $product_id ? (int) $product_id : false;
    }

    /**
     * Add tracking information to order.
     *
     * @param \WC_Order $order     WooCommerce order.
     * @param array     $shipments Shipment data from Printify.
     * @return void
     */
    private function addTrackingInfo($order, $shipments)
    {
        if (empty($shipments)) {
            return;
        }
        
        foreach ($shipments as $shipment) {
            if (empty($shipment['tracking_number']) || empty($shipment['carrier'])) {
                continue;
            }
            
            // Check if this tracking info already exists
            $existing_tracking = $order->get_meta('_printify_tracking_' . $shipment['tracking_number']);
            
            if ($existing_tracking) {
                // Already recorded this tracking number
                continue;
            }
            
            // Add tracking info as meta
            $order->update_meta_data('_printify_tracking_' . $shipment['tracking_number'], [
                'carrier' => $shipment['carrier'],
                'tracking_number' => $shipment['tracking_number'],
                'tracking_url' => $shipment['tracking_url'] ?? '',
                'shipped_at' => $shipment['shipped_at'] ?? current_time('mysql'),
            ]);
            
            // Add order note
            $tracking_url = !empty($shipment['tracking_url']) 
                ? '<a href="' . esc_url($shipment['tracking_url']) . '" target="_blank">' . esc_html($shipment['tracking_number']) . '</a>'
                : esc_html($shipment['tracking_number']);
                
            $order->add_order_note(
                sprintf(
                    __('Tracking information added: %1$s - %2$s', 'wp-woocommerce-printify-sync'),
                    esc_html($shipment['carrier']),
                    $tracking_url
                ),
                true // Send to customer
            );
        }
    }

    /**
     * Map Printify order status to WooCommerce status.
     *
     * @param string $printify_status Printify order status.
     * @return string WooCommerce order status.
     */
    private function mapOrderStatus($printify_status)
    {
        $printify_status = strtolower(str_replace(' ', '_', $printify_status));
        
        if (isset($this->status_mapping[$printify_status])) {
            return $this->status_mapping[$printify_status];
        }
        
        // Default status
        return 'processing';
    }

    /**
     * Send order to Printify for production.
     *
     * @param int    $order_id         WooCommerce order ID.
     * @param string $printify_order_id Printify order ID.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    private function sendOrderToPrintify($order_id, $printify_order_id)
    {
        $this->logger->info("Sending order {$order_id} to Printify for production");
        
        $response = $this->api_client->updateOrderStatus($printify_order_id, 'submit_order');
        
        if (is_wp_error($response)) {
            $this->logger->error("Failed to send order {$order_id} to Printify: " . $response->get_error_message());
            return $response;
        }
        
        // Add order note
        $order = wc_get_order($order_id);
        if ($order) {
            $order->add_order_note(
                __('Order sent to Printify for production.', 'wp-woocommerce-printify-sync')
            );
        }
        
        $this->logger->info("Successfully sent order {$order_id} to Printify for production");
        return true;
    }

    /**
     * Notify customer about order status change.
     *
     * @param int    $order_id WooCommerce order ID.
     * @param string $status   New order status.
     * @return void
     */
    private function notifyCustomerAboutOrderStatus($order_id, $status)
    {
        if (!$this->email_service) {
            return;
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $customer_email = $order->get_billing_email();
        
        if (empty($customer_email)) {
            return;
        }
        
        $this->logger->info("Sending order status notification to customer for order {$order_id}");
        
        $subject = sprintf(
            __('Order #%s Status Update', 'wp-woocommerce-printify-sync'),
            $order->get_order_number()
        );
        
        // Prepare email message
        $message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $message .= '<h2 style="color: #333;">' . sprintf(__('Your Order #%s has been updated', 'wp-woocommerce-printify-sync'), $order->get_order_number()) . '</h2>';
        $message .= '<p>' . __('Hello', 'wp-woocommerce-printify-sync') . ' ' . $order->get_billing_first_name() . ',</p>';
        
        switch ($status) {
            case 'completed':
                $message .= '<p>' . __('Your order has been completed and shipped. If tracking information is available, you can find it in your account or in the order details below.', 'wp-woocommerce-printify-sync') . '</p>';
                
                // Add tracking info if available
                $tracking_numbers = $this->getOrderTrackingNumbers($order);
                if (!empty($tracking_numbers)) {
                    $message .= '<h3>' . __('Tracking Information', 'wp-woocommerce-printify-sync') . '</h3>';
                    $message .= '<ul>';
                    foreach ($tracking_numbers as $tracking) {
                        $tracking_url = !empty($tracking['tracking_url']) 
                            ? '<a href="' . esc_url($tracking['tracking_url']) . '">' . esc_html($tracking['tracking_number']) . '</a>'
                            : esc_html($tracking['tracking_number']);
                            
                        $message .= '<li>' . sprintf(
                            __('Carrier: %1$s - Tracking: %2$s', 'wp-woocommerce-printify-sync'),
                            esc_html($tracking['carrier']),
                            $tracking_url
                        ) . '</li>';
                    }
                    $message .= '</ul>';
                }
                break;
                
            case 'on-hold':
                $message .= '<p>' . __('Your order has been placed on hold. This could be due to a payment issue or because we need additional information from you. Please contact us if you have any questions.', 'wp-woocommerce-printify-sync') . '</p>';
                break;
                
            case 'refunded':
                $message .= '<p>' . __('Your order has been refunded. If you have any questions, please contact our customer support.', 'wp-woocommerce-printify-sync') . '</p>';
                break;
                
            default:
                $message .= '<p>' . sprintf(__('Your order status has been updated to: %s', 'wp-woocommerce-printify-sync'), wc_get_order_status_name($status)) . '</p>';
                break;
        }
        
        // Add order details
        $message .= '<h3>' . __('Order Details', 'wp-woocommerce-printify-sync') . '</h3>';
        $message .= '<p><strong>' . __('Order Number', 'wp-woocommerce-printify-sync') . ':</strong> ' . $order->get_order_number() . '</p>';
        $message .= '<p><strong>' . __('Order Date', 'wp-woocommerce-printify-sync') . ':</strong> ' . $order->get_date_created()->date_i18n(get_option('date_format')) . '</p>';
        $message .= '<p><strong>' . __('Order Status', 'wp-woocommerce-printify-sync') . ':</strong> ' . wc_get_order_status_name($status) . '</p>';
        
        // Add footer
        $message .= '<hr style="border: 1px solid #eee; margin: 20px 0;">';
        $message .= '<p style="font-size: 12px; color: #777;">' . __('This email was sent from', 'wp-woocommerce-printify-sync') . ' ' . get_bloginfo('name') . '</p>';
        $message .= '</div>';
        
        // Set up email headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        ];
        
        // Queue the email
        $this->email_service->queueEmail($customer_email, $subject, $message, $headers);
    }

    /**
     * Get tracking numbers for an order.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return array Array of tracking information.
     */
    private function getOrderTrackingNumbers($order)
    {
        $tracking_numbers = [];
        
        // Get all meta that starts with _printify_tracking_
        $meta_data = $order->get_meta_data();
        
        foreach ($meta_data as $meta) {
            $key = $meta->key;
            
            if (strpos($key, '_printify_tracking_') === 0) {
                $tracking_info = $meta->value;
                
                if (is_array($tracking_info) && isset($tracking_info['carrier']) && isset($tracking_info['tracking_number'])) {
                    $tracking_numbers[] = $tracking_info;
                }
            }
        }
        
        return $tracking_numbers;
    }
}
