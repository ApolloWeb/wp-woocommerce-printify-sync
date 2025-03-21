<?php
/**
 * Main Plugin class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;
use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu;
use ApolloWeb\WPWooCommercePrintifySync\API\APIController;
use ApolloWeb\WPWooCommercePrintifySync\Webhooks\WebhookController;
use ApolloWeb\WPWooCommercePrintifySync\Products\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderSync;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActionSchedulerService;

/**
 * Main plugin class.
 */
class Plugin
{
    /**
     * Service container instance.
     *
     * @var ServiceContainer
     */
    private $container;
    
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->container = new ServiceContainer();
    }
    
    /**
     * Initialize the plugin.
     *
     * @return void
     */
    public function init()
    {
        $this->registerServices();
        $this->registerHooks();
        
        // Initialize components
        $this->container->get('admin_menu')->init();
        $this->container->get('api_controller')->init();
        $this->container->get('webhook_controller')->init();
        $this->container->get('product_sync')->init();
        $this->container->get('order_sync')->init();
        $this->container->get('order_controller')->init();
        
        // Register assets
        add_action('admin_enqueue_scripts', [$this, 'registerAssets']);
        
        // Initialize Action Scheduler if not already
        if (!did_action('plugins_loaded')) {
            add_action('plugins_loaded', [$this, 'initializeActionScheduler']);
        } else {
            $this->initializeActionScheduler();
        }
    }
    
    /**
     * Register services with the container.
     *
     * @return void
     */
    private function registerServices()
    {
        // Register core services
        $this->container->register('logger', 'ApolloWeb\WPWooCommercePrintifySync\Services\Logger');
        $this->container->register('template', 'ApolloWeb\WPWooCommercePrintifySync\Services\TemplateService');
        $this->container->register('encryption', 'ApolloWeb\WPWooCommercePrintifySync\Services\EncryptionService');
        $this->container->register('email_service', 'ApolloWeb\WPWooCommercePrintifySync\Services\EmailService')
            ->addArgument($this->container->get('logger'));
        
        // Register API services
        $this->container->register('api_client', 'ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPIClient')
            ->addArgument($this->container->get('logger'))
            ->addArgument($this->container->get('encryption'));
        
        $this->container->register('chatgpt_client', 'ApolloWeb\WPWooCommercePrintifySync\API\ChatGPTClient')
            ->addArgument($this->container->get('logger'))
            ->addArgument($this->container->get('encryption'));
            
        // Register controllers
        $this->container->register('api_controller', 'ApolloWeb\WPWooCommercePrintifySync\API\APIController')
            ->addArgument($this->container->get('api_client'))
            ->addArgument($this->container->get('chatgpt_client'))
            ->addArgument($this->container->get('logger'))
            ->addArgument($this->container->get('encryption'));
        
        $this->container->register('webhook_controller', 'ApolloWeb\WPWooCommercePrintifySync\Webhooks\WebhookController')
            ->addArgument($this->container->get('logger'))
            ->addArgument($this->container->get('action_scheduler'));
        
        // Register action scheduler service
        $this->container->register('action_scheduler', 'ApolloWeb\WPWooCommercePrintifySync\Services\ActionSchedulerService')
            ->addArgument($this->container->get('logger'));
        
        // Register admin UI components
        $this->container->register('admin_menu', 'ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu')
            ->addArgument($this->container->get('template'));
        
        // Register order controller
        $this->container->register('order_controller', 'ApolloWeb\WPWooCommercePrintifySync\Admin\OrderController')
            ->addArgument($this->container->get('order_sync'))
            ->addArgument($this->container->get('logger'))
            ->addArgument($this->container->get('action_scheduler'));
            
        // Register sync services
        $this->container->register('product_sync', 'ApolloWeb\WPWooCommercePrintifySync\Products\ProductSync')
            ->addArgument($this->container->get('api_client'))
            ->addArgument($this->container->get('logger'))
            ->addArgument($this->container->get('action_scheduler'));
            
        $this->container->register('order_sync', 'ApolloWeb\WPWooCommercePrintifySync\Orders\OrderSync')
            ->addArgument($this->container->get('api_client'))
            ->addArgument($this->container->get('logger'))
            ->addArgument($this->container->get('action_scheduler'))
            ->addArgument($this->container->get('email_service'));
            
        // Register shipping service
        $this->container->register('shipping_service', 'ApolloWeb\WPWooCommercePrintifySync\Services\ShippingService')
            ->addArgument($this->container->get('api_client'))
            ->addArgument($this->container->get('logger'));
            
        // Register ticket service
        $this->container->register('ticket_service', 'ApolloWeb\WPWooCommercePrintifySync\Services\TicketService')
            ->addArgument($this->container->get('chatgpt_client'))
            ->addArgument($this->container->get('logger'));
            
        // Register shipping services
        $this->container->register('shipping_profiles', 'ApolloWeb\WPWooCommercePrintifySync\Shipping\ShippingProfiles')
            ->addArgument($this->container->get('api_client'))
            ->addArgument($this->container->get('logger'))
            ->addArgument($this->container->get('activity_service'));
            
        // Register ticketing system
        $this->container->register('ticket_service', 'ApolloWeb\WPWooCommercePrintifySync\Tickets\TicketService')
            ->addArgument($this->container->get('logger'))
            ->addArgument($this->container->get('activity_service'))
            ->addArgument($this->container->get('chatgpt_client'));
            
        $this->container->register('ticket_controller', 'ApolloWeb\WPWooCommercePrintifySync\Tickets\TicketController')
            ->addArgument($this->container->get('ticket_service'))
            ->addArgument($this->container->get('logger'))
            ->addArgument($this->container->get('template'));
    }
    
    /**
     * Register hooks.
     *
     * @return void
     */
    private function registerHooks()
    {
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_test_printify_connection', [$this->container->get('api_controller'), 'testPrintifyConnection']);
        add_action('wp_ajax_wpwps_test_chatgpt_connection', [$this->container->get('api_controller'), 'testChatGPTConnection']);
        add_action('wp_ajax_wpwps_save_settings', [$this->container->get('api_controller'), 'saveSettings']);
        add_action('wp_ajax_wpwps_get_revenue_data', [$this->container->get('api_controller'), 'getRevenueData']);
        
        // Order management AJAX handlers
        add_action('wp_ajax_wpwps_sync_all_orders', [$this->container->get('order_controller'), 'syncAllOrders']);
        add_action('wp_ajax_wpwps_sync_single_order', [$this->container->get('order_controller'), 'syncSingleOrder']);
        add_action('wp_ajax_wpwps_get_orders', [$this->container->get('order_controller'), 'getOrders']);
        add_action('wp_ajax_wpwps_get_order_details', [$this->container->get('order_controller'), 'getOrderDetails']);
        
        // Register webhook endpoints
        add_action('woocommerce_api_wpwps_printify_webhook', [$this->container->get('webhook_controller'), 'handlePrintifyWebhook']);
        
        // Register product sync actions
        add_action('wpwps_sync_products', [$this->container->get('product_sync'), 'syncProducts']);
        add_action('wpwps_sync_single_product', [$this->container->get('product_sync'), 'syncSingleProduct'], 10, 1);
        
        // Register order sync actions
        add_action('wpwps_sync_orders', [$this->container->get('order_sync'), 'syncOrders']);
        add_action('wpwps_sync_single_order', [$this->container->get('order_sync'), 'syncSingleOrder'], 10, 1);
        add_action('woocommerce_order_status_changed', [$this->container->get('order_sync'), 'handleOrderStatusChange'], 10, 3);
        
        // Register shipping hooks
        add_action('woocommerce_shipping_init', [$this->container->get('shipping_service'), 'initShippingMethods']);
        add_filter('woocommerce_shipping_methods', [$this->container->get('shipping_service'), 'addShippingMethods']);
        
        // Set up cron schedules
        add_filter('cron_schedules', [$this, 'addCronSchedules']);
        add_action('init', [$this, 'scheduleCronEvents']);
        
        // Register shipping AJAX handlers
        add_action('wp_ajax_wpwps_get_shipping_profiles', [$this->container->get('ticket_controller'), 'getShippingProfiles']);
        add_action('wp_ajax_wpwps_create_shipping_profile', [$this->container->get('ticket_controller'), 'createShippingProfile']);
        add_action('wp_ajax_wpwps_update_shipping_profile', [$this->container->get('ticket_controller'), 'updateShippingProfile']);
        add_action('wp_ajax_wpwps_delete_shipping_profile', [$this->container->get('ticket_controller'), 'deleteShippingProfile']);
        
        // Register ticket AJAX handlers
        add_action('wp_ajax_wpwps_get_tickets', [$this->container->get('ticket_controller'), 'getTickets']);
        add_action('wp_ajax_wpwps_get_ticket', [$this->container->get('ticket_controller'), 'getTicket']);
        add_action('wp_ajax_wpwps_create_ticket', [$this->container->get('ticket_controller'), 'createTicket']);
        add_action('wp_ajax_wpwps_update_ticket', [$this->container->get('ticket_controller'), 'updateTicket']);
        add_action('wp_ajax_wpwps_delete_ticket', [$this->container->get('ticket_controller'), 'deleteTicket']);
        add_action('wp_ajax_wpwps_add_ticket_reply', [$this->container->get('ticket_controller'), 'addTicketReply']);
    }
    
    /**
     * Register admin assets.
     *
     * @param string $hook_suffix The current admin page.
     * @return void
     */
    public function registerAssets($hook_suffix)
    {
        // Only load on our plugin pages
        if (strpos($hook_suffix, 'wpwps') === false) {
            return;
        }
        
        // Register common CSS files
        wp_register_style('wpwps-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', [], '5.3.0');
        wp_register_style('wpwps-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0');
        
        // Register common JS files
        wp_register_script('wpwps-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.0', true);
        wp_register_script('wpwps-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js', [], '4.3.0', true);
        
        // Load page-specific assets
        if (strpos($hook_suffix, 'wpwps-dashboard') !== false) {
            wp_enqueue_style('wpwps-dashboard', WPWPS_PLUGIN_URL . 'assets/css/wpwps-dashboard.css', ['wpwps-bootstrap', 'wpwps-fontawesome'], WPWPS_VERSION);
            wp_enqueue_script('wpwps-dashboard', WPWPS_PLUGIN_URL . 'assets/js/wpwps-dashboard.js', ['jquery', 'wpwps-bootstrap', 'wpwps-chartjs'], WPWPS_VERSION, true);
        } elseif (strpos($hook_suffix, 'wpwps-settings') !== false) {
            wp_enqueue_style('wpwps-settings', WPWPS_PLUGIN_URL . 'assets/css/wpwps-settings.css', ['wpwps-bootstrap', 'wpwps-fontawesome'], WPWPS_VERSION);
            wp_enqueue_script('wpwps-settings', WPWPS_PLUGIN_URL . 'assets/js/wpwps-settings.js', ['jquery', 'wpwps-bootstrap'], WPWPS_VERSION, true);
        } elseif (strpos($hook_suffix, 'wpwps-products') !== false) {
            wp_enqueue_style('wpwps-products', WPWPS_PLUGIN_URL . 'assets/css/wpwps-products.css', ['wpwps-bootstrap', 'wpwps-fontawesome'], WPWPS_VERSION);
            wp_enqueue_script('wpwps-products', WPWPS_PLUGIN_URL . 'assets/js/wpwps-products.js', ['jquery', 'wpwps-bootstrap'], WPWPS_VERSION, true);
        } elseif (strpos($hook_suffix, 'wpwps-orders') !== false) {
            wp_enqueue_style('wpwps-orders', WPWPS_PLUGIN_URL . 'assets/css/wpwps-orders.css', ['wpwps-bootstrap', 'wpwps-fontawesome'], WPWPS_VERSION);
            wp_enqueue_script('wpwps-orders', WPWPS_PLUGIN_URL . 'assets/js/wpwps-orders.js', ['jquery', 'wpwps-bootstrap'], WPWPS_VERSION, true);
            
            // Localize orders script with necessary translations and data
            wp_localize_script('wpwps-orders', 'wpwps_orders', [
                'loading' => __('Loading...', 'wp-woocommerce-printify-sync'),
                'loading_orders' => __('Loading orders...', 'wp-woocommerce-printify-sync'),
                'loading_details' => __('Loading order details...', 'wp-woocommerce-printify-sync'),
                'sync_error' => __('An error occurred while syncing.', 'wp-woocommerce-printify-sync'),
                'load_error' => __('An error occurred while loading orders.', 'wp-woocommerce-printify-sync'),
                'no_orders' => __('No orders found.', 'wp-woocommerce-printify-sync'),
                'syncing' => __('Syncing...', 'wp-woocommerce-printify-sync'),
                'sync_all_orders' => __('Sync All Orders', 'wp-woocommerce-printify-sync'),
                'sync_this_order' => __('Sync This Order', 'wp-woocommerce-printify-sync'),
                'sync_in_progress' => __('Sync in progress, please wait...', 'wp-woocommerce-printify-sync'),
                'order_details' => __('Order Details', 'wp-woocommerce-printify-sync'),
                'order_info' => __('Order Information', 'wp-woocommerce-printify-sync'),
                'customer_info' => __('Customer Information', 'wp-woocommerce-printify-sync'),
                'order_number' => __('Order Number', 'wp-woocommerce-printify-sync'),
                'order_date' => __('Order Date', 'wp-woocommerce-printify-sync'),
                'order_status' => __('Order Status', 'wp-woocommerce-printify-sync'),
                'printify_id' => __('Printify ID', 'wp-woocommerce-printify-sync'),
                'last_synced' => __('Last Synced', 'wp-woocommerce-printify-sync'),
                'customer_name' => __('Customer Name', 'wp-woocommerce-printify-sync'),
                'customer_email' => __('Email', 'wp-woocommerce-printify-sync'),
                'customer_phone' => __('Phone', 'wp-woocommerce-printify-sync'),
                'items' => __('Order Items', 'wp-woocommerce-printify-sync'),
                'product' => __('Product', 'wp-woocommerce-printify-sync'),
                'sku' => __('SKU', 'wp-woocommerce-printify-sync'),
                'quantity' => __('Quantity', 'wp-woocommerce-printify-sync'),
                'price' => __('Price', 'wp-woocommerce-printify-sync'),
                'total' => __('Total', 'wp-woocommerce-printify-sync'),
                'billing_address' => __('Billing Address', 'wp-woocommerce-printify-sync'),
                'shipping_address' => __('Shipping Address', 'wp-woocommerce-printify-sync'),
                'tracking_info' => __('Tracking Information', 'wp-woocommerce-printify-sync'),
                'carrier' => __('Carrier', 'wp-woocommerce-printify-sync'),
                'tracking_number' => __('Tracking Number', 'wp-woocommerce-printify-sync'),
                'shipped_date' => __('Shipped Date', 'wp-woocommerce-printify-sync'),
                'subtotal' => __('Subtotal', 'wp-woocommerce-printify-sync'),
                'shipping' => __('Shipping', 'wp-woocommerce-printify-sync'),
                'tax' => __('Tax', 'wp-woocommerce-printify-sync'),
                'discount' => __('Discount', 'wp-woocommerce-printify-sync'),
                'status_pending' => __('Pending', 'wp-woocommerce-printify-sync'),
                'status_processing' => __('Processing', 'wp-woocommerce-printify-sync'),
                'status_on_hold' => __('On Hold', 'wp-woocommerce-printify-sync'),
                'status_completed' => __('Completed', 'wp-woocommerce-printify-sync'),
                'status_cancelled' => __('Cancelled', 'wp-woocommerce-printify-sync'),
                'status_refunded' => __('Refunded', 'wp-woocommerce-printify-sync'),
            ]);
        } elseif (strpos($hook_suffix, 'wpwps-shipping') !== false) {
            wp_enqueue_style('wpwps-shipping', WPWPS_PLUGIN_URL . 'assets/css/wpwps-shipping.css', ['wpwps-bootstrap', 'wpwps-fontawesome'], WPWPS_VERSION);
            wp_enqueue_script('wpwps-shipping', WPWPS_PLUGIN_URL . 'assets/js/wpwps-shipping.js', ['jquery', 'wpwps-bootstrap'], WPWPS_VERSION, true);
        } elseif (strpos($hook_suffix, 'wpwps-tickets') !== false) {
            wp_enqueue_style('wpwps-tickets', WPWPS_PLUGIN_URL . 'assets/css/wpwps-tickets.css', ['wpwps-bootstrap', 'wpwps-fontawesome'], WPWPS_VERSION);
            wp_enqueue_script('wpwps-tickets', WPWPS_PLUGIN_URL . 'assets/js/wpwps-tickets.js', ['jquery', 'wpwps-bootstrap'], WPWPS_VERSION, true);
        }
        
        // Localize scripts with AJAX URL and nonce
        wp_localize_script('wpwps-settings', 'wpwps_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_ajax_nonce'),
        ]);
        
        wp_localize_script('wpwps-orders', 'wpwps_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_ajax_nonce'),
        ]);
        
        wp_localize_script('wpwps-products', 'wpwps_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_ajax_nonce'),
        ]);
    }
    
    /**
     * Add custom cron schedules.
     *
     * @param array $schedules Existing cron schedules.
     * @return array Modified cron schedules.
     */
    public function addCronSchedules($schedules)
    {
        $schedules['wpwps_5_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => __('Every 5 minutes', 'wp-woocommerce-printify-sync'),
        ];
        
        $schedules['wpwps_6_hours'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __('Every 6 hours', 'wp-woocommerce-printify-sync'),
        ];
        
        $schedules['wpwps_12_hours'] = [
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __('Every 12 hours', 'wp-woocommerce-printify-sync'),
        ];
        
        return $schedules;
    }
    
    /**
     * Schedule custom cron events.
     *
     * @return void
     */
    public function scheduleCronEvents()
    {
        // Schedule stock sync if not already scheduled
        if (!wp_next_scheduled('wpwps_sync_products')) {
            wp_schedule_event(time(), 'wpwps_12_hours', 'wpwps_sync_products');
        }
        
        // Schedule email queue processing if not already scheduled
        if (!wp_next_scheduled('wpwps_process_email_queue')) {
            wp_schedule_event(time(), 'wpwps_5_minutes', 'wpwps_process_email_queue');
        }
    }
    
    /**
     * Initialize Action Scheduler.
     *
     * @return void
     */
    public function initializeActionScheduler()
    {
        $this->container->get('action_scheduler')->init();
    }
}
