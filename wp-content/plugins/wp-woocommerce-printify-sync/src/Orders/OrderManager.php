<?php
/**
 * Order Manager
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Orders
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

use ApolloWeb\WPWooCommercePrintifySync\Services\ApiService;
use ApolloWeb\WPWooCommercePrintifySync\Services\LoggerService;
use ApolloWeb\WPWooCommercePrintifySync\Services\Container;

/**
 * Class OrderManager
 *
 * Manages order sync between Printify and WooCommerce
 */
class OrderManager
{
    /**
     * API service
     *
     * @var ApiService
     */
    private ApiService $api_service;

    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Container service
     *
     * @var Container
     */
    private Container $container;

    /**
     * Shipping profile cache
     *
     * @var array
     */
    private array $shipping_profiles = [];

    /**
     * Provider names cache
     * 
     * @var array
     */
    private array $provider_names = [];

    /**
     * Printify status to WooCommerce status mapping
     *
     * @var array
     */
    private array $status_mapping = [
        'draft' => 'pending',
        'processing' => 'processing', 
        'pending' => 'on-hold',
        'on_hold' => 'on-hold',
        'fulfilled' => 'completed',
        'canceled' => 'cancelled',
        'refunded' => 'refunded',
        'failed' => 'failed',
        'refund_requested' => 'refund-requested',
        'refund_approved' => 'refund-processing',
        'refund_declined' => 'refund-declined',
        'reprint_requested' => 'reprint-requested',
        'reprint_approved' => 'reprint-processing',
        'reprint_declined' => 'reprint-declined',
        'awaiting_evidence' => 'awaiting-evidence',
        'return_requested' => 'return-requested',
        'return_approved' => 'return-approved',
        'return_rejected' => 'return-rejected',
        'refund_requested' => 'refund-requested',
        'refund_approved' => 'refund-approved', 
        'refund_rejected' => 'refund-rejected',
        'replacement_created' => 'replacement-processing'
    ];

    private array $shipping_status_mapping = [
        'created' => 'processing',
        'pending' => 'on-hold',
        'in_transit' => 'processing',
        'delivered' => 'completed',
        'failed' => 'failed',
        'exception' => 'on-hold'
    ];

    private array $refund_statuses = [
        'refund_requested',
        'refund_approved', 
        'refund_declined'
    ];

    private array $reprint_statuses = [
        'reprint_requested',
        'reprint_approved',
        'reprint_declined'
    ];

    /**
     * Constructor
     *
     * @param ApiService    $api_service API service.
     * @param LoggerService $logger      Logger service.
     * @param Container     $container   Service container.
     */
    public function __construct(ApiService $api_service, LoggerService $logger, Container $container)
    {
        $this->api_service = $api_service;
        $this->logger = $logger;
        $this->container = $container;
        
        // Initialize shipping profile cache
        $this->shipping_profiles = get_option('wpwps_shipping_profiles', []);
        $this->provider_names = get_option('wpwps_provider_names', []);
    }

    /**
     * Initialize hooks
     *
     * @return void
     */
    public function init(): void
    {
        // Register Action Scheduler hooks
        add_action('wpwps_import_order', [$this, 'importOrder']);
        add_action('wpwps_sync_order', [$this, 'syncExistingOrder']);
        add_action('wpwps_sync_all_orders', [$this, 'syncAllOrders']);
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_import_orders', [$this, 'ajaxImportOrders']);
        add_action('wp_ajax_wpwps_sync_single_order', [$this, 'ajaxSyncSingleOrder']);
        
        // Register webhook handler
        add_action('wpwps_handle_order_webhook', [$this, 'handleOrderWebhook']);
        
        // Register WooCommerce hooks for order creation and update
        add_action('woocommerce_order_status_changed', [$this, 'maybeUpdatePrintifyOrder'], 10, 4);
        add_action('woocommerce_new_order', [$this, 'maybeCreatePrintifyOrder'], 10, 1);
        
        // Add order meta box for Printify order details
        add_action('add_meta_boxes', [$this, 'addPrintifyOrderMetaBox']);
        
        // Register custom order actions
        add_filter('woocommerce_order_actions', [$this, 'addOrderActions']);
        add_action('woocommerce_order_action_send_to_printify', [$this, 'sendOrderToPrintify']);
        
        // Add shipping hooks
        add_filter('woocommerce_shipping_methods', [$this, 'addPrintifyShippingMethods']);
        add_filter('woocommerce_cart_shipping_packages', [$this, 'splitShippingByProvider']);
        add_filter('woocommerce_shipping_rate_label', [$this, 'modifyShippingRateLabel'], 10, 2);
        
        // Shipping profile sync
        add_action('wpwps_sync_shipping_profiles', [$this, 'syncShippingProfiles']);
    }

    /**
     * Sync all orders from Printify to WooCommerce
     *
     * @return void
     */
    public function syncAllOrders(): void
    {
        $this->logger->info('Starting scheduled order sync');
        
        // Check if API credentials are set
        if (!$this->api_service->hasCredentials()) {
            $this->logger->error('API credentials not set');
            return;
        }
        
        // Get the shop ID
        $shop_id = $this->api_service->getShopId();
        
        if (empty($shop_id)) {
            $this->logger->error('Shop ID not set');
            return;
        }
        
        // Get orders from API - paginate if needed
        $page = 1;
        $limit = 50;
        $total_imported = 0;
        $total_updated = 0;
        
        do {
            $endpoint = "shops/{$shop_id}/orders.json?page={$page}&limit={$limit}";
            $response = $this->api_service->get($endpoint);
            
            if (null === $response || !isset($response['orders'])) {
                $this->logger->error('Failed to get orders from API', [
                    'page' => $page,
                    'response' => $response,
                ]);
                break;
            }
            
            $orders = $response['orders'];
            $total_orders = count($orders);
            
            $this->logger->info('Retrieved orders from API', [
                'page' => $page,
                'count' => $total_orders,
            ]);
            
            if (empty($orders)) {
                break;
            }
            
            // Schedule import/sync for each order
            $action_scheduler = $this->container->get('action_scheduler');
            
            foreach ($orders as $order) {
                // Skip orders without ID
                if (empty($order['id'])) {
                    continue;
                }
                
                // Check if order exists in WooCommerce
                $woo_order_id = $this->getWooOrderIdByPrintifyId($order['id']);
                
                if ($woo_order_id) {
                    // Schedule sync for existing order
                    $action_scheduler->scheduleTask('wpwps_sync_order', [
                        'printify_id' => $order['id'],
                        'woo_order_id' => $woo_order_id,
                    ], 0, true);
                    $total_updated++;
                } else {
                    // Schedule import for new order
                    $action_scheduler->scheduleTask('wpwps_import_order', [
                        'printify_id' => $order['id']
                    ], 0, true);
                    $total_imported++;
                }
            }
            
            $page++;
            
        } while ($total_orders >= $limit);
        
        $this->logger->info('Scheduled order imports and syncs', [
            'import' => $total_imported,
            'sync' => $total_updated,
        ]);
        
        // Update last sync time
        update_option('wpwps_last_orders_sync', current_time('mysql'));
    }

    /**
     * Import a single order from Printify
     *
     * @param array $args Arguments
     * @return void
     */
    public function importOrder(array $args): void
    {
        // Check args
        if (empty($args['printify_id'])) {
            $this->logger->error('Missing printify_id in import order arguments');
            return;
        }
        
        $printify_id = $args['printify_id'];
        
        $this->logger->info('Starting order import', ['printify_id' => $printify_id]);
        
        // Check if API credentials are set
        if (!$this->api_service->hasCredentials()) {
            $this->logger->error('API credentials not set');
            return;
        }
        
        // Get the shop ID
        $shop_id = $this->api_service->getShopId();
        
        if (empty($shop_id)) {
            $this->logger->error('Shop ID not set');
            return;
        }
        
        // Check if order already exists in WooCommerce
        $existing_order_id = $this->getWooOrderIdByPrintifyId($printify_id);
        
        if ($existing_order_id) {
            $this->logger->info('Order already exists, scheduling sync instead', [
                'printify_id' => $printify_id,
                'woo_order_id' => $existing_order_id,
            ]);
            
            $action_scheduler = $this->container->get('action_scheduler');
            $action_scheduler->scheduleTask('wpwps_sync_order', [
                'printify_id' => $printify_id,
                'woo_order_id' => $existing_order_id,
            ], 0, true);
            
            return;
        }
        
        // Get order details from API
        $endpoint = "shops/{$shop_id}/orders/{$printify_id}.json";
        $printify_order = $this->api_service->get($endpoint);
        
        if (null === $printify_order) {
            $this->logger->error('Failed to get order details from API', ['printify_id' => $printify_id]);
            return;
        }
        
        // Create WooCommerce order
        $woo_order_id = $this->createWooCommerceOrder($printify_order);
        
        if (!$woo_order_id) {
            $this->logger->error('Failed to create WooCommerce order', ['printify_id' => $printify_id]);
            return;
        }
        
        $this->logger->info('Order imported successfully', [
            'printify_id' => $printify_id,
            'woo_order_id' => $woo_order_id,
        ]);
        
        // Log to database
        $this->logSync('order', $printify_id, 'import', 'success', 'Order imported successfully');
    }

    /**
     * Sync an existing order
     *
     * @param array $args Arguments
     * @return void
     */
    public function syncExistingOrder(array $args): void
    {
        // Check args
        if (empty($args['printify_id']) || empty($args['woo_order_id'])) {
            $this->logger->error('Missing required arguments in sync order task', ['args' => $args]);
            return;
        }
        
        $printify_id = $args['printify_id'];
        $woo_order_id = $args['woo_order_id'];
        
        $this->logger->info('Starting order sync', [
            'printify_id' => $printify_id,
            'woo_order_id' => $woo_order_id,
        ]);
        
        // Check if API credentials are set
        if (!$this->api_service->hasCredentials()) {
            $this->logger->error('API credentials not set');
            return;
        }
        
        // Get the shop ID
        $shop_id = $this->api_service->getShopId();
        
        if (empty($shop_id)) {
            $this->logger->error('Shop ID not set');
            return;
        }
        
        // Get order details from API
        $endpoint = "shops/{$shop_id}/orders/{$printify_id}.json";
        $printify_order = $this->api_service->get($endpoint);
        
        if (null === $printify_order) {
            $this->logger->error('Failed to get order details from API', [
                'printify_id' => $printify_id,
                'woo_order_id' => $woo_order_id,
            ]);
            return;
        }
        
        // Update WooCommerce order
        $success = $this->updateWooCommerceOrder($woo_order_id, $printify_order);
        
        if (!$success) {
            $this->logger->error('Failed to update WooCommerce order', [
                'printify_id' => $printify_id,
                'woo_order_id' => $woo_order_id,
            ]);
            return;
        }
        
        $this->logger->info('Order synced successfully', [
            'printify_id' => $printify_id,
            'woo_order_id' => $woo_order_id,
        ]);
        
        // Log to database
        $this->logSync('order', $printify_id, 'sync', 'success', 'Order synced successfully');
    }

    /**
     * Create a new WooCommerce order from Printify data
     *
     * @param array $printify_order Printify order data
     * @return int|false Order ID on success, false on failure
     */
    private function createWooCommerceOrder(array $printify_order)
    {
        // Check if order has required data
        if (empty($printify_order['id'])) {
            $this->logger->error('Printify order missing required data', [
                'order' => $printify_order,
            ]);
            return false;
        }
        
        try {
            // Create order object with HPOS support
            $order = wc_create_order();
            
            // Set customer details
            if (!empty($printify_order['address_to'])) {
                $address = $printify_order['address_to'];
                
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
                
                // Also set shipping address
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
            
            // Set order currency
            if (!empty($printify_order['currency'])) {
                $order->set_currency($printify_order['currency']);
            }
            
            // Add line items
            if (!empty($printify_order['line_items'])) {
                $this->addOrderLineItems($order, $printify_order['line_items']);
            }
            
            // Add shipping costs
            if (!empty($printify_order['shipping_cost'])) {
                $shipping_item = new \WC_Order_Item_Shipping();
                $shipping_item->set_method_title('Printify Shipping');
                $shipping_item->set_total($printify_order['shipping_cost']);
                $order->add_item($shipping_item);
            }
            
            // Add tax if present
            if (!empty($printify_order['tax_cost']) && $printify_order['tax_cost'] > 0) {
                $tax_item = new \WC_Order_Item_Tax();
                $tax_item->set_label('Printify Tax');
                $tax_item->set_tax_total($printify_order['tax_cost']);
                $order->add_item($tax_item);
            }
            
            // Save Printify metadata
            $order->update_meta_data('_printify_order_id', $printify_order['id']);
            $order->update_meta_data('_printify_order_external_id', $printify_order['external_id'] ?? '');
            $order->update_meta_data('_printify_last_synced', current_time('mysql'));
            
            // Set order status based on Printify status
            if (!empty($printify_order['status'])) {
                $wc_status = $this->mapPrintifyStatusToWooStatus($printify_order['status']);
                $order->set_status($wc_status);
            } else {
                $order->set_status('processing');
            }
            
            // Save tracking information if available
            if (!empty($printify_order['shipments'])) {
                $this->saveTrackingInformation($order, $printify_order['shipments']);
            }
            
            // Save estimated delivery dates if available
            if (!empty($printify_order['delivery_estimate'])) {
                $order->update_meta_data('_printify_delivery_estimate', $printify_order['delivery_estimate']);
                
                // Add order note about estimated delivery
                $min_date = isset($printify_order['delivery_estimate']['min_date']) 
                    ? date_i18n(get_option('date_format'), strtotime($printify_order['delivery_estimate']['min_date'])) 
                    : '';
                    
                $max_date = isset($printify_order['delivery_estimate']['max_date']) 
                    ? date_i18n(get_option('date_format'), strtotime($printify_order['delivery_estimate']['max_date'])) 
                    : '';
                    
                if ($min_date && $max_date) {
                    $order->add_order_note(
                        sprintf(
                            __('Estimated delivery between %s and %s', 'wp-woocommerce-printify-sync'),
                            $min_date,
                            $max_date
                        )
                    );
                }
            }
            
            // If exchange rate is present, store it
            if (!empty($printify_order['exchange_rate'])) {
                $order->update_meta_data('_printify_exchange_rate', $printify_order['exchange_rate']);
            }
            
            // Save order changes
            $order->calculate_totals();
            $order->save();
            
            // Add note that order was imported from Printify
            $order->add_order_note(
                sprintf(
                    __('Order imported from Printify. Printify Order ID: %s', 'wp-woocommerce-printify-sync'),
                    $printify_order['id']
                )
            );
            
            return $order->get_id();
            
        } catch (\Exception $e) {
            $this->logger->error('Error creating WooCommerce order', [
                'printify_id' => $printify_order['id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }

    /**
     * Update an existing WooCommerce order with Printify data
     *
     * @param int   $order_id      WooCommerce order ID
     * @param array $printify_order Printify order data
     * @return bool True on success, false on failure
     */
    private function updateWooCommerceOrder(int $order_id, array $printify_order): bool
    {
        // Get order object with HPOS support
        $order = wc_get_order($order_id);
        
        if (!$order) {
            $this->logger->error('WooCommerce order not found', ['order_id' => $order_id]);
            return false;
        }
        
        try {
            // Update order status if changed
            if (!empty($printify_order['status'])) {
                $current_status = $order->get_status();
                $new_status = $this->mapPrintifyStatusToWooStatus($printify_order['status']);
                
                if ($current_status !== $new_status) {
                    $order->set_status(
                        $new_status,
                        sprintf(
                            __('Printify status updated to: %s', 'wp-woocommerce-printify-sync'),
                            $printify_order['status']
                        )
                    );
                }
            }
            
            // Update tracking information if available
            if (!empty($printify_order['shipments'])) {
                $this->saveTrackingInformation($order, $printify_order['shipments']);
            }
            
            // Update estimated delivery dates if available
            if (!empty($printify_order['delivery_estimate'])) {
                $order->update_meta_data('_printify_delivery_estimate', $printify_order['delivery_estimate']);
            }
            
            // Update last synced timestamp
            $order->update_meta_data('_printify_last_synced', current_time('mysql'));
            
            // Save order changes
            $order->save();
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Error updating WooCommerce order', [
                'order_id' => $order_id,
                'printify_id' => $printify_order['id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }

    /**
     * Add line items to an order from Printify data
     *
     * @param \WC_Order $order      WooCommerce order
     * @param array     $line_items Printify line items
     * @return void
     */
    private function addOrderLineItems(\WC_Order $order, array $line_items): void
    {
        foreach ($line_items as $line_item) {
            try {
                // Create a line item
                $item = new \WC_Order_Item_Product();
                
                // Set line item data
                $item->set_name($line_item['title'] ?? __('Printify Product', 'wp-woocommerce-printify-sync'));
                $item->set_quantity($line_item['quantity'] ?? 1);
                
                // Set product and variant ID if we can find a match
                if (!empty($line_item['product_id']) && !empty($line_item['variant_id'])) {
                    $product_id = $this->getWooProductIdByPrintifyId($line_item['product_id']);
                    
                    if ($product_id) {
                        $item->set_product_id($product_id);
                        
                        // Find the variation ID if this is a variable product
                        $product = wc_get_product($product_id);
                        if ($product && $product->is_type('variable')) {
                            $variation_id = $this->getWooVariationIdByPrintifyVariantId($product_id, $line_item['variant_id']);
                            if ($variation_id) {
                                $item->set_variation_id($variation_id);
                            }
                        }
                    }
                }
                
                // Set prices
                if (isset($line_item['price'])) {
                    $price = (float) $line_item['price'];
                    $item->set_total($price * ($line_item['quantity'] ?? 1));
                    $item->set_subtotal($price * ($line_item['quantity'] ?? 1));
                }
                
                // Save cost price in meta data
                if (isset($line_item['cost'])) {
                    $item->add_meta_data('_printify_cost_price', $line_item['cost']);
                }
                
                // Save Printify line item ID
                if (isset($line_item['id'])) {
                    $item->add_meta_data('_printify_line_item_id', $line_item['id']);
                }
                
                // Save Printify product ID
                if (isset($line_item['product_id'])) {
                    $item->add_meta_data('_printify_product_id', $line_item['product_id']);
                }
                
                // Save Printify variant ID
                if (isset($line_item['variant_id'])) {
                    $item->add_meta_data('_printify_variant_id', $line_item['variant_id']);
                }
                
                // Add the line item to the order
                $order->add_item($item);
                
            } catch (\Exception $e) {
                $this->logger->error('Error adding line item to order', [
                    'order_id' => $order->get_id(),
                    'line_item' => $line_item,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Save tracking information to an order
     *
     * @param \WC_Order $order     WooCommerce order
     * @param array     $shipments Printify shipments
     * @return void
     */
    private function saveTrackingInformation(\WC_Order $order, array $shipments): void
    {
        if (empty($shipments)) {
            return;
        }
        
        // Store shipment data in meta
        $order->update_meta_data('_printify_shipments', $shipments);
        
        // Add tracking as order notes
        foreach ($shipments as $shipment) {
            // Skip if no tracking information
            if (empty($shipment['tracking_number']) || empty($shipment['carrier'])) {
                continue;
            }
            
            $tracking_number = $shipment['tracking_number'];
            $carrier = $shipment['carrier'];
            $tracking_url = $shipment['tracking_url'] ?? '';
            
            // Check if we already have a note for this tracking number
            $notes = wc_get_order_notes([
                'order_id' => $order->get_id(),
                'type' => 'customer',
            ]);
            
            $tracking_note_exists = false;
            foreach ($notes as $note) {
                if (strpos($note->content, $tracking_number) !== false) {
                    $tracking_note_exists = true;
                    break;
                }
            }
            
            // Add note if it doesn't exist
            if (!$tracking_note_exists) {
                $note_message = sprintf(
                    __('Your order has shipped via %s. Tracking number: %s', 'wp-woocommerce-printify-sync'),
                    $carrier,
                    $tracking_number
                );
                
                if (!empty($tracking_url)) {
                    $note_message .= sprintf(
                        ' <a href="%s" target="_blank">%s</a>',
                        esc_url($tracking_url),
                        __('Track your order', 'wp-woocommerce-printify-sync')
                    );
                }
                
                $order->add_order_note($note_message, true);
            }
        }
    }

    /**
     * Map Printify status to WooCommerce status
     *
     * @param string $printify_status Printify status
     * @return string WooCommerce status
     */
    private function mapPrintifyStatusToWooStatus(string $printify_status): string
    {
        // Convert to snake_case if it's not already
        $status_key = strtolower(str_replace(' ', '_', $printify_status));
        
        return $this->status_mapping[$status_key] ?? 'processing';
    }

    /**
     * Get WooCommerce product ID by Printify ID
     *
     * @param string $printify_id Printify product ID
     * @return int|false Product ID or false if not found
     */
    private function getWooProductIdByPrintifyId(string $printify_id)
    {
        global $wpdb;
        
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_printify_product_id' 
                AND meta_value = %s 
                LIMIT 1",
                $printify_id
            )
        );
        
        return $product_id ? (int) $product_id : false;
    }

    /**
     * Get WooCommerce variation ID by Printify variant ID
     *
     * @param int    $product_id   WooCommerce product ID
     * @param string $printify_variant_id Printify variant ID
     * @return int|false Variation ID or false if not found
     */
    private function getWooVariationIdByPrintifyVariantId(int $product_id, string $printify_variant_id)
    {
        global $wpdb;
        
        $variation_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_printify_variant_id' 
                AND meta_value = %s 
                AND post_id IN (
                    SELECT ID FROM {$wpdb->posts} 
                    WHERE post_parent = %d 
                    AND post_type = 'product_variation'
                )
                LIMIT 1",
                $printify_variant_id,
                $product_id
            )
        );
        
        return $variation_id ? (int) $variation_id : false;
    }

    /**
     * Get WooCommerce order ID by Printify ID
     *
     * @param string $printify_id Printify order ID
     * @return int|false Order ID or false if not found
     */
    private function getWooOrderIdByPrintifyId(string $printify_id)
    {
        global $wpdb;
        
        // HPOS compatible query
        if (wc_get_container()->get(\Automattic\WooCommerce\Internal\Features\FeaturesController::class)->feature_is_enabled('custom_order_tables')) {
            $order_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_meta 
                    WHERE meta_key = '_printify_order_id' 
                    AND meta_value = %s 
                    LIMIT 1",
                    $printify_id
                )
            );
        } else {
            // Legacy postmeta query
            $order_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} 
                    WHERE meta_key = '_printify_order_id' 
                    AND meta_value = %s 
                    LIMIT 1",
                    $printify_id
                )
            );
        }
        
        return $order_id ? (int) $order_id : false;
    }

    /**
     * Create or update order in Printify when created in WooCommerce
     *
     * @param int $order_id WooCommerce order ID
     * @return void
     */
    public function maybeCreatePrintifyOrder(int $order_id): void
    {
        // Skip if auto-sync to Printify is disabled
        if (!get_option('wpwps_sync_to_printify', false)) {
            return;
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Check if this is already a Printify order
        $printify_id = $order->get_meta('_printify_order_id');
        
        if ($printify_id) {
            // This is a Printify order, no need to create it
            return;
        }
        
        // Check if the order has Printify products
        $has_printify_products = false;
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            if ($product_id) {
                $printify_product_id = get_post_meta($product_id, '_printify_product_id', true);
                
                if ($printify_product_id) {
                    $has_printify_products = true;
                    break;
                }
            }
        }
        
        if (!$has_printify_products) {
            // No Printify products in the order
            return;
        }
        
        // Create the order in Printify
        $this->createPrintifyOrder($order);
    }

    /**
     * Update order in Printify when updated in WooCommerce
     *
     * @param int      $order_id    Order ID
     * @param string   $status_from Old status
     * @param string   $status_to   New status
     * @param \WC_Order $order       Order object
     * @return void
     */
    public function maybeUpdatePrintifyOrder(int $order_id, string $status_from, string $status_to, \WC_Order $order): void
    {
        // Skip if auto-sync to Printify is disabled
        if (!get_option('wpwps_sync_to_printify', false)) {
            return;
        }
        
        // Check if this is a Printify order
        $printify_id = $order->get_meta('_printify_order_id');
        
        if (!$printify_id) {
            // This is not a Printify order
            return;
        }
        
        // Only handle specific status changes
        $allowed_status_changes = [
            'processing' => ['cancelled', 'refunded'],
            'on-hold' => ['processing', 'cancelled', 'refunded'],
        ];
        
        if (!isset($allowed_status_changes[$status_from]) || !in_array($status_to, $allowed_status_changes[$status_from])) {
            // Not a status change we handle
            return;
        }
        
        // Map WooCommerce status to Printify action
        $action = '';
        
        if ($status_to === 'cancelled') {
            $action = 'cancel';
        } elseif ($status_to === 'refunded') {
            $action = 'refund';
        } elseif ($status_to === 'processing' && $status_from === 'on-hold') {
            $action = 'submit';
        }
        
        if (!$action) {
            return;
        }
        
        // Send the action to Printify
        $this->updatePrintifyOrderStatus($order, $printify_id, $action);
    }

    /**
     * Create order in Printify
     *
     * @param \WC_Order $order WooCommerce order
     * @return bool Success
     */
    private function createPrintifyOrder(\WC_Order $order): bool
    {
        // Check if API credentials are set
        if (!$this->api_service->hasCredentials()) {
            $this->logger->error('API credentials not set');
            return false;
        }
        
        // Get the shop ID
        $shop_id = $this->api_service->getShopId();
        
        if (empty($shop_id)) {
            $this->logger->error('Shop ID not set');
            return false;
        }
        
        // Prepare order data for Printify
        $order_data = [
            'external_id' => (string)$order->get_id(),  // Must be string per API spec
            'label' => sprintf(__('Order #%s', 'wp-woocommerce-printify-sync'), $order->get_order_number()),
            'line_items' => [],
            'address_to' => [
                'first_name' => $order->get_shipping_first_name() ?: $order->get_billing_first_name(),
                'last_name' => $order->get_shipping_last_name() ?: $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'country' => $order->get_shipping_country() ?: $order->get_billing_country(),
                'region' => $order->get_shipping_state() ?: $order->get_billing_state(),
                'address1' => $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
                'address2' => $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
                'city' => $order->get_shipping_city() ?: $order->get_billing_city(),
                'zip' => $order->get_shipping_postcode() ?: $order->get_billing_postcode(),
                'company' => $order->get_shipping_company() ?: $order->get_billing_company(),
            ],
            'shipping_method' => 'standard', // Required by API
            'send_shipping_notification' => true // Enable shipment notifications
        ];
        
        // Add line items
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            
            if (!$product_id) {
                continue;
            }
            
            // Get Printify product ID
            $printify_product_id = get_post_meta($product_id, '_printify_product_id', true);
            
            if (!$printify_product_id) {
                continue;
            }
            
            // Get Printify variant ID
            $printify_variant_id = '';
            
            if ($variation_id) {
                $printify_variant_id = get_post_meta($variation_id, '_printify_variant_id', true);
            }
            
            if (!$printify_variant_id) {
                // Try to get it from the line item meta directly
                $printify_variant_id = $item->get_meta('_printify_variant_id');
            }
            
            if (!$printify_variant_id) {
                // Skip items without variant ID
                continue;
            }
            
            // Add line item
            $order_data['line_items'][] = [
                'product_id' => $printify_product_id,
                'variant_id' => $printify_variant_id,
                'quantity' => $item->get_quantity(),
                'print_provider_id' => get_post_meta($product_id, '_printify_provider_id', true),
                'blueprint_id' => get_post_meta($product_id, '_printify_blueprint_id', true),
                'shipping_method' => 'standard'
            ];
        }
        
        // Skip if no valid line items
        if (empty($order_data['line_items'])) {
            $this->logger->error('No valid Printify line items in order', ['order_id' => $order->get_id()]);
            return false;
        }
        
        // Send order to Printify
        $endpoint = "shops/{$shop_id}/orders.json";
        $response = $this->api_service->post($endpoint, $order_data);
        
        if (null === $response || !isset($response['id'])) {
            $this->logger->error('Failed to create order in Printify', [
                'order_id' => $order->get_id(),
                'response' => $response,
            ]);
            return false;
        }
        
        // Save Printify order ID
        $order->update_meta_data('_printify_order_id', $response['id']);
        $order->update_meta_data('_printify_last_synced', current_time('mysql'));
        $order->save();
        
        // Add order note
        $order->add_order_note(
            sprintf(
                __('Order created in Printify. Printify Order ID: %s', 'wp-woocommerce-printify-sync'),
                $response['id']
            )
        );
        
        $this->logger->info('Order created in Printify', [
            'order_id' => $order->get_id(),
            'printify_id' => $response['id'],
        ]);
        
        return true;
    }

    /**
     * Update order status in Printify
     *
     * @param \WC_Order $order       WooCommerce order
     * @param string    $printify_id Printify order ID
     * @param string    $action      Action to perform (cancel, refund, submit)
     * @return bool Success
     */
    private function updatePrintifyOrderStatus(\WC_Order $order, string $printify_id, string $action): bool
    {
        // Check if API credentials are set
        if (!$this->api_service->hasCredentials()) {
            $this->logger->error('API credentials not set');
            return false;
        }
        
        // Get the shop ID
        $shop_id = $this->api_service->getShopId();
        
        if (empty($shop_id)) {
            $this->logger->error('Shop ID not set');
            return false;
        }
        
        // Prepare endpoint based on action
        $endpoint = "shops/{$shop_id}/orders/{$printify_id}/";
        
        switch ($action) {
            case 'cancel':
                $endpoint .= 'cancel.json';
                break;
                
            case 'refund':
                $endpoint .= 'refund.json';
                break;
                
            case 'submit':
                $endpoint .= 'submit.json';
                break;
                
            default:
                $this->logger->error('Invalid action for Printify order update', [
                    'order_id' => $order->get_id(),
                    'printify_id' => $printify_id,
                    'action' => $action,
                ]);
                return false;
        }
        
        // Send request to Printify
        $response = $this->api_service->post($endpoint, []);
        
        if (null === $response) {
            $this->logger->error('Failed to update order in Printify', [
                'order_id' => $order->get_id(),
                'printify_id' => $printify_id,
                'action' => $action,
                'response' => $response,
            ]);
            return false;
        }
        
        // Add order note
        $order->add_order_note(
            sprintf(
                __('Order %s in Printify', 'wp-woocommerce-printify-sync'),
                $action === 'cancel' ? __('cancelled', 'wp-woocommerce-printify-sync') : 
                ($action === 'refund' ? __('refunded', 'wp-woocommerce-printify-sync') : 
                __('submitted', 'wp-woocommerce-printify-sync'))
            )
        );
        
        $this->logger->info('Order updated in Printify', [
            'order_id' => $order->get_id(),
            'printify_id' => $printify_id,
            'action' => $action,
        ]);
        
        return true;
    }

    /**
     * Handle order webhook from Printify
     *
     * @param array $payload Webhook payload
     * @return void
     */
    public function handleOrderWebhook(array $payload): void
    {
        if (empty($payload['id'])) {
            $this->logger->error('Invalid order webhook payload', ['payload' => $payload]);
            return;
        }
        
        $printify_id = $payload['id'];
        
        $this->logger->info('Received order webhook', ['printify_id' => $printify_id]);
        
        // Get WooCommerce order ID
        $order_id = $this->getWooOrderIdByPrintifyId($printify_id);
        
        if ($order_id) {
            // Order exists, schedule sync
            $action_scheduler = $this->container->get('action_scheduler');
            $action_scheduler->scheduleTask('wpwps_sync_order', [
                'printify_id' => $printify_id,
                'woo_order_id' => $order_id,
            ], 0, true);
            
            $this->logger->info('Scheduled order sync from webhook', [
                'printify_id' => $printify_id,
                'woo_order_id' => $order_id,
            ]);
        } else {
            // Order doesn't exist, schedule import
            $action_scheduler = $this->container->get('action_scheduler');
            $action_scheduler->scheduleTask('wpwps_import_order', ['printify_id' => $printify_id], 0, true);
            
            $this->logger->info('Scheduled order import from webhook', [
                'printify_id' => $printify_id,
            ]);
        }
    }

    /**
     * Add Printify order meta box
     *
     * @return void
     */
    public function addPrintifyOrderMetaBox(): void
    {
        add_meta_box(
            'wpwps-printify-order',
            __('Printify Order', 'wp-woocommerce-printify-sync'),
            [$this, 'renderPrintifyOrderMetaBox'],
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * Render Printify order meta box
     *
     * @param \WP_Post $post Post object
     * @return void
     */
    public function renderPrintifyOrderMetaBox($post): void
    {
        $order = wc_get_order($post->ID);
        
        if (!$order) {
            echo '<p>' . esc_html__('Order not found', 'wp-woocommerce-printify-sync') . '</p>';
            return;
        }
        
        $printify_id = $order->get_meta('_printify_order_id');
        
        if (!$printify_id) {
            echo '<p>' . esc_html__('This is not a Printify order', 'wp-woocommerce-printify-sync') . '</p>';
            echo '<button type="button" class="button" id="wpwps-send-to-printify" data-order-id="' . esc_attr($order->get_id()) . '" data-nonce="' . wp_create_nonce('wpwps-admin-ajax-nonce') . '">';
            echo esc_html__('Send to Printify', 'wp-woocommerce-printify-sync');
            echo '</button>';
            return;
        }
        
        echo '<p><strong>' . esc_html__('Printify Order ID:', 'wp-woocommerce-printify-sync') . '</strong> ' . esc_html($printify_id) . '</p>';
        
        $shop_id = $this->api_service->getShopId();
        if ($shop_id) {
            $printify_order_url = "https://printify.com/app/shop/{$shop_id}/orders/{$printify_id}";
            echo '<p><a href="' . esc_url($printify_order_url) . '" target="_blank" class="button">' . esc_html__('View in Printify', 'wp-woocommerce-printify-sync') . '</a></p>';
        }
        
        $last_synced = $order->get_meta('_printify_last_synced');
        if ($last_synced) {
            echo '<p><strong>' . esc_html__('Last Synced:', 'wp-woocommerce-printify-sync') . '</strong> ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_synced))) . '</p>';
        }
        
        // Show tracking information
        $shipments = $order->get_meta('_printify_shipments');
        if (!empty($shipments)) {
            echo '<h4>' . esc_html__('Tracking Information', 'wp-woocommerce-printify-sync') . '</h4>';
            
            foreach ($shipments as $shipment) {
                if (empty($shipment['tracking_number']) || empty($shipment['carrier'])) {
                    continue;
                }
                
                echo '<p>';
                echo '<strong>' . esc_html__('Carrier:', 'wp-woocommerce-printify-sync') . '</strong> ' . esc_html($shipment['carrier']) . '<br>';
                echo '<strong>' . esc_html__('Tracking:', 'wp-woocommerce-printify-sync') . '</strong> ' . esc_html($shipment['tracking_number']) . '<br>';
                
                if (!empty($shipment['tracking_url'])) {
                    echo '<a href="' . esc_url($shipment['tracking_url']) . '" target="_blank" class="button button-small">' . esc_html__('Track Package', 'wp-woocommerce-printify-sync') . '</a>';
                }
                
                echo '</p>';
            }
        }
        
        // Display estimated delivery
        $delivery_estimate = $order->get_meta('_printify_delivery_estimate');
        if (!empty($delivery_estimate)) {
            echo '<h4>' . esc_html__('Estimated Delivery', 'wp-woocommerce-printify-sync') . '</h4>';
            
            $min_date = isset($delivery_estimate['min_date']) 
                ? date_i18n(get_option('date_format'), strtotime($delivery_estimate['min_date'])) 
                : '';
                
            $max_date = isset($delivery_estimate['max_date']) 
                ? date_i18n(get_option('date_format'), strtotime($delivery_estimate['max_date'])) 
                : '';
                
            if ($min_date && $max_date) {
                echo '<p>' . sprintf(
                    esc_html__('Between %s and %s', 'wp-woocommerce-printify-sync'),
                    '<strong>' . esc_html($min_date) . '</strong>',
                    '<strong>' . esc_html($max_date) . '</strong>'
                ) . '</p>';
            }
        }
        
        // Display exchange rate if available
        $exchange_rate = $order->get_meta('_printify_exchange_rate');
        if (!empty($exchange_rate)) {
            echo '<p><strong>' . esc_html__('Exchange Rate:', 'wp-woocommerce-printify-sync') . '</strong> ' . esc_html($exchange_rate) . '</p>';
        }
        
        // Add sync button
        echo '<p><button type="button" class="button" id="wpwps-sync-order" data-order-id="' . esc_attr($order->get_id()) . '" data-printify-id="' . esc_attr($printify_id) . '" data-nonce="' . wp_create_nonce('wpwps-admin-ajax-nonce') . '">';
        echo esc_html__('Sync with Printify', 'wp-woocommerce-printify-sync');
        echo '</button></p>';
    }

    /**
     * Add custom order actions
     *
     * @param array $actions Order actions
     * @return array Modified actions
     */
    public function addOrderActions(array $actions): array
    {
        global $theorder;
        
        // Only add if not already a Printify order
        if (!$theorder) {
            return $actions;
        }
        
        $printify_id = $theorder->get_meta('_printify_order_id');
        
        if (!$printify_id) {
            $actions['send_to_printify'] = __('Send to Printify', 'wp-woocommerce-printify-sync');
        }
        
        return $actions;
    }

    /**
     * Send order to Printify action handler
     *
     * @param \WC_Order $order Order object
     * @return void
     */
    public function sendOrderToPrintify(\WC_Order $order): void
    {
        $this->createPrintifyOrder($order);
    }

    /**
     * AJAX handler for importing orders
     *
     * @return void
     */
    public function ajaxImportOrders(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Start order sync process
        $action_scheduler = $this->container->get('action_scheduler');
        $action_scheduler->scheduleTask('wpwps_sync_all_orders', [], 0, true);
        
        wp_send_json_success([
            'message' => __('Order import started. Check the dashboard for progress.', 'wp-woocommerce-printify-sync'),
        ]);
    }

    /**
     * AJAX handler for syncing a single order
     *
     * @return void
     */
    public function ajaxSyncSingleOrder(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Get order ID and printify ID
        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $printify_id = isset($_POST['printify_id']) ? sanitize_text_field($_POST['printify_id']) : '';
        
        if (!$order_id || !$printify_id) {
            wp_send_json_error(['message' => __('Missing required parameters', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Schedule sync task
        $action_scheduler = $this->container->get('action_scheduler');
        $action_scheduler->scheduleTask('wpwps_sync_order', [
            'printify_id' => $printify_id,
            'woo_order_id' => $order_id,
        ], 0, true);
        
        wp_send_json_success([
            'message' => __('Order sync scheduled. It will begin shortly.', 'wp-woocommerce-printify-sync'),
        ]);
    }

    /**
     * Log sync to database
     *
     * @param string $entity_type Entity type
     * @param string $entity_id   Entity ID
     * @param string $action      Action performed
     * @param string $status      Status
     * @param string $message     Message
     * @return void
     */
    private function logSync(string $entity_type, string $entity_id, string $action, string $status, string $message): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_sync_logs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
        
        if (!$table_exists) {
            $this->logger->warning('Sync logs table does not exist');
            return;
        }
        
        $wpdb->insert(
            $table_name,
            [
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'action' => $action,
                'status' => $status,
                'message' => $message,
                'created_at' => current_time('mysql', true),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );
    }

    /**
     * Split cart shipping into provider-specific packages
     *
     * @param array $packages Shipping packages
     * @return array Modified packages
     */
    public function splitShippingByProvider(array $packages): array
    {
        $split_packages = [];
        
        foreach ($packages as $package_key => $package) {
            $by_provider = [];
            
            foreach ($package['contents'] as $item_key => $item) {
                $product_id = $item['product_id'];
                $provider_id = get_post_meta($product_id, '_printify_provider_id', true);
                
                if (!$provider_id) {
                    continue;
                }
                
                if (!isset($by_provider[$provider_id])) {
                    $by_provider[$provider_id] = [
                        'contents' => [],
                        'contents_cost' => 0,
                        'provider_id' => $provider_id,
                        'provider_name' => $this->provider_names[$provider_id] ?? 'Unknown Provider',
                    ];
                }
                
                $by_provider[$provider_id]['contents'][$item_key] = $item;
                $by_provider[$provider_id]['contents_cost'] += $item['line_total'];
            }
            
            foreach ($by_provider as $provider_id => $provider_package) {
                $split_packages[] = array_merge($package, $provider_package);
            }
        }
        
        return $split_packages;
    }

    /**
     * Sync shipping profiles from Printify
     *
     * @return void
     */
    public function syncShippingProfiles(): void
    {
        if (!$this->api_service->hasCredentials()) {
            return;
        }

        $shop_id = $this->api_service->getShopId();
        if (!$shop_id) {
            return;
        }

        // Get print providers first
        $providers = $this->api_service->get("shops/{$shop_id}/print-providers.json");
        if (!$providers) {
            return;
        }

        $profiles = [];
        $names = [];

        foreach ($providers as $provider) {
            $provider_id = $provider['id'];
            $names[$provider_id] = $provider['title']; // API uses 'title' not 'name'
            
            // Get shipping profiles for this provider
            $shipping = $this->api_service->get(
                "shops/{$shop_id}/print-providers/{$provider_id}/shipping-info.json" // Correct endpoint
            );
            
            if ($shipping && !empty($shipping['shipping_regions'])) {
                $profiles[$provider_id] = [
                    'regions' => array_map(function($region) {
                        return [
                            'id' => $region['id'],
                            'name' => $region['name'],
                            'countries' => $region['countries'],
                            'rates' => array_map(function($rate) {
                                return [
                                    'id' => $rate['id'],
                                    'name' => $rate['name'],
                                    'carrier' => $rate['carrier'] ?? '',
                                    'first_item_cost' => $rate['first_item_cost'],
                                    'additional_item_cost' => $rate['additional_item_cost'],
                                    'delivery_time' => [
                                        'min' => $rate['min_delivery_time'] ?? 0,
                                        'max' => $rate['max_delivery_time'] ?? 0,
                                    ],
                                    'handling_time' => [
                                        'min' => $rate['min_handling_time'] ?? 0,
                                        'max' => $rate['max_handling_time'] ?? 0,
                                    ]
                                ];
                            }, $region['shipping_rates'] ?? [])
                        ];
                    }, $shipping['shipping_regions'])
                ];
            }
        }

        update_option('wpwps_shipping_profiles', $profiles);
        update_option('wpwps_provider_names', $names);
        
        $this->shipping_profiles = $profiles;
        $this->provider_names = $names;
        
        $this->createShippingZones();
    }

    private function formatShippingRates(array $rates): array 
    {
        $formatted = [];
        
        foreach ($rates as $rate) {
            $formatted[] = [
                'id' => sanitize_text_field($rate['id']),
                'name' => sanitize_text_field($rate['name']),
                'carrier' => sanitize_text_field($rate['carrier'] ?? ''),
                'first_item' => wc_format_decimal($rate['first_item_cost']),
                'additional_items' => wc_format_decimal($rate['additional_item_cost']),
                'delivery_time' => [
                    'min' => absint($rate['delivery_time']['min'] ?? 0),
                    'max' => absint($rate['delivery_time']['max'] ?? 0),
                ],
                'handling_time' => [
                    'min' => absint($rate['handling_time']['min'] ?? 0),
                    'max' => absint($rate['handling_time']['max'] ?? 0),
                ],
                'currency' => 'USD'
            ];
        }
        
        return $formatted;
    }

    /**
     * Convert USD shipping cost to store currency
     *
     * @param float $usd_amount Amount in USD
     * @return float Converted amount
     */
    private function convertShippingCost(float $usd_amount): float 
    {
        // If CURCY plugin is active, use its conversion
        if (function_exists('wmc_get_price')) {
    }

    public function submitReturnRequest(\WC_Order $order, array $return_data): bool {
        $printify_id = $order->get_meta('_printify_order_id');
        $shop_id = $this->api_service->getShopId();

        if (!$printify_id || !$shop_id) {
            return false;
        }

        $formatted_data = [
            'items' => array_map(function($item) {
                return [
                    'line_item_id' => $item['line_item_id'],
                    'quantity' => $item['quantity'],
                    'reason' => $item['reason'],
                ];
            }, $return_data['items']),
            'reason' => $return_data['reason'],
            'comments' => $return_data['comments'],
            'images' => $return_data['images']
        ];

        $response = $this->api_service->submitReturn($shop_id, $printify_id, $formatted_data);

        if ($response) {
            $order->add_order_note(__('Return request submitted to Printify', 'wp-woocommerce-printify-sync'));
            $order->update_meta_data('_printify_return_id', $response['id']);
            $order->save();
            return true;
        }

        return false;
    }

    /**
     * Process shipping packages
     */
    private function processShippingPackages(array $packages): array
    {
        $processed = [];
        
        foreach ($packages as $package) {
            if (!empty($package['provider_id'])) {
                $processed[] = $this->formatShippingPackage($package);
            }
        }
        
        return $processed;
    }

    /**
     * Format shipping package data
     */
    private function formatShippingPackage(array $package): array
    {
        return [
            'provider_id' => $package['provider_id'],
            'method_id' => $package['method_id'] ?? 'standard',
            'cost' => $package['cost'] ?? 0,
        ];
    }
}