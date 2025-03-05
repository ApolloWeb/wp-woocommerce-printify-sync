<?php
/**
 * Main plugin class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminDashboard;
use ApolloWeb\WPWooCommercePrintifySync\API\ApiManager;
use ApolloWeb\WPWooCommercePrintifySync\Product\ProductSynchronizer;
use ApolloWeb\WPWooCommercePrintifySync\Order\OrderProcessor;
use ApolloWeb\WPWooCommercePrintifySync\Shipping\ShippingManager;
use ApolloWeb\WPWooCommercePrintifySync\Ticketing\TicketManager;
use ApolloWeb\WPWooCommercePrintifySync\Utility\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Currency\CurrencyConverter;
use ApolloWeb\WPWooCommercePrintifySync\Background\BackgroundProcessor;

class Main {
    /**
     * @var Main Singleton instance
     */
    private static $instance = null;

    /**
     * @var ApiManager API Manager instance
     */
    private $apiManager;

    /**
     * @var ProductSynchronizer Product Synchronizer instance
     */
    private $productSynchronizer;

    /**
     * @var OrderProcessor Order Processor instance
     */
    private $orderProcessor;

    /**
     * @var ShippingManager Shipping Manager instance
     */
    private $shippingManager;

    /**
     * @var TicketManager Ticket Manager instance
     */
    private $ticketManager;

    /**
     * @var Logger Logger instance
     */
    private $logger;

    /**
     * @var CurrencyConverter Currency Converter instance
     */
    private $currencyConverter;

    /**
     * @var BackgroundProcessor Background Processor instance
     */
    private $backgroundProcessor;

    /**
     * @var AdminDashboard Admin Dashboard instance
     */
    private $adminDashboard;

    /**
     * Get the singleton instance
     *
     * @return Main
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct creation
     */
    private function __construct() {
        $this->init();
        $this->loadDependencies();
        $this->registerHooks();
    }

    /**
     * Initialize the plugin
     */
    private function init() {
        // Initialize the logger first so it's available for other components
        $this->logger = new Logger();
        
        $this->logger->info('Plugin initialized', [
            'version' => WPWPRINTIFYSYNC_VERSION,
            'time' => current_time('mysql')
        ]);
    }

    /**
     * Load dependencies and initialize components
     */
    private function loadDependencies() {
        // Initialize API Manager
        $this->apiManager = new ApiManager($this->logger);
        
        // Initialize Currency Converter
        $this->currencyConverter = new CurrencyConverter($this->apiManager, $this->logger);
        
        // Initialize Background Processor
        $this->backgroundProcessor = new BackgroundProcessor($this->logger);
        
        // Initialize Product Synchronizer
        $this->productSynchronizer = new ProductSynchronizer(
            $this->apiManager,
            $this->backgroundProcessor,
            $this->currencyConverter,
            $this->logger
        );
        
        // Initialize Shipping Manager
        $this->shippingManager = new ShippingManager(
            $this->apiManager, 
            $this->currencyConverter, 
            $this->logger
        );
        
        // Initialize Order Processor
        $this->orderProcessor = new OrderProcessor(
            $this->apiManager,
            $this->shippingManager,
            $this->backgroundProcessor,
            $this->logger
        );
        
        // Initialize Ticket Manager
        $this->ticketManager = new TicketManager(
            $this->logger,
            $this->apiManager
        );
        
        // Initialize Admin Dashboard
        $this->adminDashboard = new AdminDashboard(
            $this->apiManager,
            $this->productSynchronizer,
            $this->orderProcessor,
            $this->shippingManager,
            $this->ticketManager,
            $this->currencyConverter,
            $this->logger
        );
    }

    /**
     * Register WordPress hooks
     */
    private function registerHooks() {
        // Register scheduled events
        add_action('init', [$this, 'registerScheduledEvents']);

        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }

    /**
     * Register scheduled events
     */
    public function registerScheduledEvents() {
        // Register scheduled events for currency updates, log cleanup, etc.
        if (!wp_next_scheduled('wpwprintifysync_update_currency_rates')) {
            wp_schedule_event(time(), 'every_4_hours', 'wpwprintifysync_update_currency_rates');
        }
        
        if (!wp_next_scheduled('wpwprintifysync_sync_stock')) {
            wp_schedule_event(time(), 'twicedaily', 'wpwprintifysync_sync_stock');
        }
        
        if (!wp_next_scheduled('wpwprintifysync_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'wpwprintifysync_cleanup_logs');
        }
        
        if (!wp_next_scheduled('wpwprintifysync_poll_emails')) {
            wp_schedule_event(time(), 'hourly', 'wpwprintifysync_poll_emails');
        }
        
        // Register action handlers
        add_action('wpwprintifysync_update_currency_rates', [$this->currencyConverter, 'updateRates']);
        add_action('wpwprintifysync_sync_stock', [$this->productSynchronizer, 'syncStock']);
        add_action('wpwprintifysync_cleanup_logs', [$this->logger, 'cleanupLogs']);
        add_action('wpwprintifysync_poll_emails', [$this->ticketManager, 'pollEmails']);
    }

    /**
     * Register REST API endpoints
     */
    public function registerRestRoutes() {
        // Register REST API endpoints for webhooks
        register_rest_route('wpwprintifysync/v1', '/webhook/printify', [
            'methods' => 'POST',
            'callback' => [$this->orderProcessor, 'handleWebhook'],
            'permission_callback' => [$this->apiManager, 'validateWebhookRequest'],
        ]);
    }

    /**
     * Get API Manager
     *
     * @return ApiManager
     */
    public function getApiManager() {
        return $this->apiManager;
    }

    /**
     * Get Product Synchronizer
     *
     * @return ProductSynchronizer
     */
    public function getProductSynchronizer() {
        return $this->productSynchronizer;
    }

    /**
     * Get Order Processor
     *
     * @return OrderProcessor
     */
    public function getOrderProcessor() {
        return $this->orderProcessor;
    }

    /**
     * Get Shipping Manager
     *
     * @return ShippingManager
     */
    public function getShippingManager() {
        return $this->shippingManager;
    }

    /**
     * Get Ticket Manager
     *
     * @return TicketManager
     */
    public function getTicketManager() {
        return $this->ticketManager;
    }

    /**
     * Get Logger
     *
     * @return Logger
     */
    public function getLogger() {
        return $this->logger;
    }

    /**
     * Get Currency Converter
     *
     * @return CurrencyConverter
     */
    public function getCurrencyConverter() {
        return $this->currencyConverter;
    }
    
    /**
     * Get Background Processor
     *
     * @return BackgroundProcessor
     */
    public function getBackgroundProcessor() {
        return $this->backgroundProcessor;
    }
}