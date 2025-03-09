<?php
/**
 * Plugin Bootstrap
 *
 * Initializes all plugin components and services.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminPages;
use ApolloWeb\WPWooCommercePrintifySync\Api\ApiManager;
use ApolloWeb\WPWooCommercePrintifySync\Services\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Services\ImageProcessor;
use ApolloWeb\WPWooCommercePrintifySync\Queue\ProductSyncQueue;
use ApolloWeb\WPWooCommercePrintifySync\Queue\ImageSyncQueue;
use ApolloWeb\WPWooCommercePrintifySync\Utils\Enqueue;
use ApolloWeb\WPWooCommercePrintifySync\Utils\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Utils\CacheManager;
use ApolloWeb\WPWooCommercePrintifySync\View\ViewManager;

/**
 * Bootstrap Class
 */
class Bootstrap {
    /**
     * API Manager instance
     *
     * @var ApiManager
     */
    private $api_manager;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize logger early
        new Logger();

        // Initialize other components
        $this->initApi();
        $this->initAdminPages();
        $this->initAssets();
        $this->initHooks();
        $this->initQueue();
        $this->initCache();
        $this->initServices();
        $this->initViewManager();

        // Log initialization
        Logger::log( 'Plugin initialized', 'info' );
    }

    /**
     * Initialize API Manager
     *
     * @return void
     */
    private function initApi() {
        $this->api_manager = new ApiManager();
    }

    /**
     * Initialize Admin Pages
     *
     * @return void
     */
    private function initAdminPages() {
        if ( is_admin() ) {
            new AdminPages( $this->api_manager );
        }
    }

    /**
     * Initialize Assets
     *
     * @return void
     */
    private function initAssets() {
        $enqueue = new Enqueue();
        $enqueue->register();
    }

    /**
     * Initialize Hooks
     *
     * @return void
     */
    private function initHooks() {
        // Setup AJAX handlers
        add_action( 'wp_ajax_apolloweb_printify_sync_product', [ $this, 'ajaxSyncProduct' ] );
        add_action( 'wp_ajax_apolloweb_printify_get_products', [ $this, 'ajaxGetProducts' ] );
        add_action( 'wp_ajax_apolloweb_printify_test_connection', [ $this, 'ajaxTestConnection' ] );
        
        // Setup WooCommerce hooks
        add_action( 'woocommerce_order_status_processing', [ $this, 'sendOrderToPrintify' ] );
        add_action( 'woocommerce_order_status_cancelled', [ $this, 'cancelPrintifyOrder' ] );
        
        // Setup webhook listener
        add_action( 'rest_api_init', [ $this, 'registerWebhookEndpoints' ] );
        
        // Setup cron schedules
        add_filter( 'cron_schedules', [ $this, 'addCronSchedules' ] );
        
        // Register cron events if not already registered
        if ( ! wp_next_scheduled( 'apolloweb_printify_sync_products' ) ) {
            wp_schedule_event( time(), 'hourly', 'apolloweb_printify_sync_products' );
        }
        
        // Register cron handlers
        add_action( 'apolloweb_printify_sync_products', [ $this, 'scheduledProductSync' ] );
        
        // Register setup wizard redirect
        add_action( 'admin_init', [ $this, 'setupWizardRedirect' ] );
    }

    /**
     * Initialize Queue system
     *
     * @return void
     */
    private function initQueue() {
        // Make sure Action Scheduler is loaded
        if ( ! function_exists( 'as_schedule_single_action' ) && file_exists( WP_PLUGIN_DIR . '/woocommerce/packages/action-scheduler/action-scheduler.php' ) ) {
            include_once WP_PLUGIN_DIR . '/woocommerce/packages/action-scheduler/action-scheduler.php';
        }

        // Initialize queues
        new ProductSyncQueue();
        new ImageSyncQueue();
    }

    /**
     * Initialize Cache
     *
     * @return void
     */
    private function initCache() {
        new CacheManager();
    }

    /**
     * Initialize Services
     *
     * @return void
     */
    private function initServices() {
        new ProductSync( $this->api_manager );
        new ImageProcessor();
    }

    /**
     * Initialize View Manager
     *
     * @return void
     */
    private function initViewManager() {
        new ViewManager();
    }

    /**
     * Handle the setup wizard redirect
     *
     * @return void
     */
    public function setupWizardRedirect() {
        // Check if we need to redirect to setup wizard
        if ( get_transient( 'apolloweb_printify_setup_wizard_redirect' ) ) {
            // Delete the transient
            delete_transient( 'apolloweb_printify_setup_wizard_redirect' );
            
            // Only redirect if we're activating the plugin
            if ( ! isset( $_GET['activate-multi'] ) ) {
                // Redirect to setup wizard
                wp_safe_redirect( admin_url( 'admin.php?page=wp-woocommerce-printify-sync-wizard' ) );
                exit;
            }
        }
    }

    /**
     * Add custom cron schedules
     *
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function addCronSchedules( $schedules ) {
        $schedules['fifteen_minutes'] = [
            'interval' => 15 * 60,
            'display'  => __( 'Every Fifteen Minutes', 'wp-woocommerce-printify-sync' ),
        ];
        
        return $schedules;
    }

    /**
     * Handle scheduled product sync
     *
     * @return void
     */
    public function scheduledProductSync() {
        // Don't run if automatic sync is disabled
        if ( 'yes' !== get_option( 'apolloweb_printify_auto_sync', 'yes' ) ) {
            Logger::log( 'Automatic sync is disabled, skipping scheduled sync', 'info' );
            return;
        }

        Logger::log( 'Starting scheduled product sync', 'info' );
        
        // Get the product sync service
        $product_sync = new ProductSync( $this->api_manager );
        
        // Enqueue all products for syncing
        $product_sync->enqueueFullSync();
    }

    /**
     * Send order to Printify when status changes to processing
     *
     * @param int $order_id WooCommerce order ID
     * @return void
     */
    public function sendOrderToPrintify( $order_id ) {
        // Get the product sync service
        $product_sync = new ProductSync( $this->api_manager );
        
        // Send order to Printify
        $product_sync->sendOrderToPrintify( $order_id );
    }

    /**
     * Cancel Printify order when WooCommerce order is cancelled
     *
     * @param int $order_id WooCommerce order ID
     * @return void
     */
    public function cancelPrintifyOrder( $order_id ) {
        // Get the product sync service
        $product_sync = new ProductSync( $this->api_manager );
        
        // Cancel order in Printify
        $product_sync->cancelPrintifyOrder( $order_id );
    }

    /**
     * Register webhook endpoints
     *
     * @return void
     */
    public function registerWebhookEndpoints() {
        register_rest_route( 'apolloweb-printify/v1', '/webhooks', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handleWebhook' ],
            'permission_callback' => [ $this, 'verifyWebhookSignature' ],
        ] );
    }

    /**
     * Verify webhook signature
     *
     * @param WP_REST_Request $request Request object
     * @return bool Whether the signature is valid
     */
    public function verifyWebhookSignature( $request ) {
        $webhook_secret = get_option( 'apolloweb_printify_webhook_secret', '' );
        
        if ( empty( $webhook_secret ) ) {
            Logger::log( 'Webhook validation failed: No webhook secret defined', 'error' );
            return false;
        }

        $signature = $request->get_header( 'X-Printify-Signature' );
        
        if ( empty( $signature ) ) {
            Logger::log( 'Webhook validation failed: No signature provided', 'error' );
            return false;
        }

        $payload = $request->get_body();
        $expected_signature = hash_hmac( 'sha256', $payload, $webhook_secret );
        
        if ( ! hash_equals( $expected_signature, $signature ) ) {
            Logger::log( 'Webhook validation failed: Invalid signature', 'error' );
            return false;
        }

        return true;
    }

    /**
     * Handle webhook
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function handleWebhook( $request ) {
        $payload = $request->get_json_params();
        
        if ( empty( $payload ) || ! isset( $payload['type'] ) ) {
            return new \WP_REST_Response( [ 'status' => 'error', 'message' => 'Invalid payload' ], 400 );
        }

        Logger::log( 'Webhook received: ' . $payload['type'], 'info', [ 
            'event_type' => $payload['type'] 
        ] );

        // Process based on event type
        switch ( $payload['type'] ) {
            case 'order.created':
            case 'order.updated':
            case 'order.fulfilled':
                $this->processOrderWebhook( $payload );
                break;
                
            case 'product.updated':
            case 'product.deleted':
                $this->processProductWebhook( $payload );
                break;
                
            default:
                Logger::log( 'Unhandled webhook event type: ' . $payload['type'], 'warning' );
                break;
        }

        return new \WP_REST_Response( [ 'status' => 'success' ], 200 );
    }

    /**
     * Process order webhook
     *
     * @param array $payload Webhook payload
     * @return void
     */
    private function processOrderWebhook( $payload ) {
        if ( ! isset( $payload['data']['external_id'] ) ) {
            Logger::log( 'Order webhook missing external_id', 'error' );
            return;
        }

        $order_id = $payload['data']['external_id'];
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            Logger::log( 'Order not found: ' . $order_id, 'error' );
            return;
        }

        // Update order status based on Printify status
        if ( isset( $payload['data']['status'] ) ) {
            $this->updateOrderStatus( $order, $payload['data']['status'] );
        }

        // Update tracking information if available
        if ( isset( $payload['data']['shipments'] ) && ! empty( $payload['data']['shipments'] ) ) {
            $this->updateOrderTracking( $order, $payload['data']['shipments'] );
        }
    }

    /**
     * Update WooCommerce order status based on Printify status
     *
     * @param WC_Order $order WooCommerce order
     * @param string   $printify_status Printify order status
     * @return void
     */
    private function updateOrderStatus( $order, $printify_status ) {
        // Map Printify status to WooCommerce status
        $status_mapping = [
            'pending'    => 'processing',
            'on-hold'    => 'on-hold',
            'canceled'   => 'cancelled',
            'fulfilled'  => 'completed',
        ];

        if ( isset( $status_mapping[ $printify_status ] ) ) {
            $new_status = $status_mapping[ $printify_status ];
            Logger::log( "Updating order #{$order->get_id()} status to {$new_status}", 'info' );
            $order->update_status( $new_status, __( 'Order status updated by Printify', 'wp-woocommerce-printify-sync' ) );
        }
    }

    /**
     * Update order tracking information
     *
     * @param WC_Order $order WooCommerce order
     * @param array    $shipments Shipment data from Printify
     * @return void
     */
    private function updateOrderTracking( $order, $shipments ) {
        if ( empty( $shipments ) ) {
            return;
        }

        // Process the first shipment (most common case)
        $shipment = $shipments[0];

        if ( isset( $shipment['tracking_number'] ) && isset( $shipment['carrier'] ) ) {
            // Store tracking info as order meta
            $order->update_meta_data( '_printify_tracking_number', $shipment['tracking_number'] );
            $order->update_meta_data( '_printify_carrier', $shipment['carrier'] );
            
            if ( isset( $shipment['tracking_url'] ) ) {
                $order->update_meta_data( '_printify_tracking_url', $shipment['tracking_url'] );
            }
            
            $order->save();

            // Add a note to the order
            $tracking_url = isset( $shipment['tracking_url'] ) ? $shipment['tracking_url'] : '';
            $note = sprintf(
                __( 'Tracking information: %1$s - %2$s %3$s', 'wp-woocommerce-printify-sync' ),
                $shipment['carrier'],
                $shipment['tracking_number'],
                ! empty( $tracking_url ) ? "(<a href='{$tracking_url}' target='_blank'>Track</a>)" : ''
            );
            $order->add_order_note( $note );
            
            // Send tracking email if enabled
            if ( 'yes' === get_option( 'apolloweb_printify_send_tracking_email', 'yes' ) ) {
                // Get WC mailer
                $mailer = WC()->mailer();
                
                // Send tracking email
                do_action( 'apolloweb_printify_send_tracking_email', $order->get_id() );
            }
        }
    }

    /**
     * Process product webhook
     *
     * @param array $payload Webhook payload
     * @return void
     */
    private function processProductWebhook( $payload ) {
        if ( ! isset( $payload['data']['id'] ) ) {
            Logger::log( 'Product webhook missing id', 'error' );
            return;
        }

        $printify_product_id = $payload['data']['id'];

        // Get the WC product ID from meta
        $args = [
            'post_type'      => 'product',
            'meta_key'       => '_printify_product_id',
            'meta_value'     => $printify_product_id,
            'posts_per_page' => 1,
        ];
        
        $products = get_posts( $args );

        if ( empty( $products ) ) {
            Logger::log( "Product not found for Printify ID: {$printify_product_id}", 'warning' );
            return;
        }

        $product_id = $products[0]->ID;

        // Handle based on event type
        if ( $payload['type'] === 'product.updated' ) {
            // Queue product for update
            $product_sync = new ProductSync( $this->api_manager );
            $product_sync->enqueueSingleProductSync( $printify_product_id, $product_id );
            
            Logger::log( "Queued product #{$product_id} for update from webhook", 'info' );
        } elseif ( $payload['type'] === 'product.deleted' ) {
            // Maybe trash the product
            if ( 'yes' === get_option( 'apolloweb_printify_delete_removed_products', 'no' ) ) {
                wp_delete_post( $product_id );
                Logger::log( "Deleted product #{$product_id} based on webhook", 'info' );
            } else {
                // Just log that the product was deleted in Printify
                Logger::log( "Product #{$product_id} was deleted in Printify but kept in WooCommerce", 'info' );
            }
        }
    }

    /**
     * AJAX handler for product sync
     *
     * @return void
     */
    public function ajaxSyncProduct() {
        // Check nonce
        if ( ! check_ajax_referer( 'apolloweb_printify_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed', 'wp-woocommerce-printify-sync' ) ] );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied', 'wp-woocommerce-printify-sync' ) ] );
        }

        $printify_id = isset( $_POST['printify_id'] ) ? sanitize_text_field( $_POST['printify_id'] ) : '';
        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

        if ( empty( $printify_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Missing product ID', 'wp-woocommerce-printify-sync' ) ] );
        }

        // Get product sync service
        $product_sync = new ProductSync( $this->api_manager );

        if ( $product_id > 0 ) {
            // Update existing product
            $result = $product_sync->syncProduct( $printify_id, $product_id );
        } else {
            // Import new product
            $result = $product_sync->importProduct( $printify_id );
        }

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 
                'message' => $result->get_error_message(),
            ] );
        }

        wp_send_json_success( [ 
            'message' => __( 'Product synced successfully', 'wp-woocommerce-printify-sync' ),
            'product_id' => $result,
        ] );
    }

    /**
     * AJAX handler for getting products from Printify
     *
     * @return void
     */
    public function ajaxGetProducts() {
        // Check nonce
        if ( ! check_ajax_referer( 'apolloweb_printify_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed', 'wp-woocommerce-printify-sync' ) ] );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied', 'wp-woocommerce-printify-sync' ) ] );