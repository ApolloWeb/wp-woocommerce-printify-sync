<?php
/**
 * Core Helper class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class CoreHelper {
    /**
     * @var CoreHelper Instance of this class.
     */
    private static $instance = null;
    
    /**
     * @var string Plugin version
     */
    const VERSION = '1.0.0';
    
    /**
     * @var string Last updated timestamp
     */
    const LAST_UPDATED = '2025-03-05 18:11:46';
    
    /**
     * @var string Author username
     */
    const AUTHOR = 'ApolloWeb';
    
    /**
     * Get single instance of this class
     *
     * @return CoreHelper
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load dependencies
        $this->loadDependencies();
        
        // Register hooks
        $this->registerHooks();
        
        // Add basic admin menu
        add_action('admin_menu', [$this, 'addAdminMenu']);
        
        // Add admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Add plugin action links
        add_filter('plugin_action_links_' . WPWPRINTIFYSYNC_PLUGIN_BASENAME, 
            [$this, 'addActionLinks']);
    }
    
    /**
     * Load dependencies
     */
    private function loadDependencies() {
        // Load helper classes
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/SettingsHelper.php';
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/ApiHelper.php';
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/ProductHelper.php';
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/OrderHelper.php';
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/CurrencyHelper.php';
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/LogHelper.php';
    }
    
    /**
     * Register hooks
     */
    private function registerHooks() {
        // Register custom post types
        add_action('init', [$this, 'registerPostTypes']);
        
        // Register custom order statuses
        add_action('init', [$this, 'registerOrderStatuses']);
        
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        
        // Register scheduled events
        add_action('init', [$this, 'registerScheduledEvents']);
    }
    
    /**
     * Register custom post types
     */
    public function registerPostTypes() {
        // Ticket post type
        register_post_type('wpws_ticket', [
            'labels' => [
                'name' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'singular_name' => __('Support Ticket', 'wp-woocommerce-printify-sync'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'custom-fields'],
            'capability_type' => 'post',
            'hierarchical' => false,
        ]);
    }
    
    /**
     * Register custom order statuses
     */
    public function registerOrderStatuses() {
        register_post_status('wc-printify-processing', [
            'label' => _x('Printify Processing', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Processing <span class="count">(%s)</span>', 'Printify Processing <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-printify-printed', [
            'label' => _x('Printify Printed', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Printed <span class="count">(%s)</span>', 'Printify Printed <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-printify-shipped', [
            'label' => _x('Printify Shipped', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Shipped <span class="count">(%s)</span>', 'Printify Shipped <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-reprint-requested', [
            'label' => _x('Reprint Requested', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Reprint Requested <span class="count">(%s)</span>', 'Reprint Requested <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-refund-requested', [
            'label' => _x('Refund Requested', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Refund Requested <span class="count">(%s)</span>', 'Refund Requested <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * Register REST API endpoints
     */
    public function registerRestRoutes() {
        register_rest_route('wpwprintifysync/v1', '/webhook/printify', [
            'methods' => 'POST',
            'callback' => [ApiHelper::getInstance(), 'handleWebhook'],
            'permission_callback' => [ApiHelper::getInstance(), 'validateWebhookRequest'],
        ]);
    }
    
    /**
     * Register scheduled events
     */
    public function registerScheduledEvents() {
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
        add_action('wpwprintifysync_update_currency_rates', [CurrencyHelper::getInstance(), 'updateRates']);
        add_action('wpwprintifysync_sync_stock', [ProductHelper::getInstance(), 'syncStock']);
        add_action('wpwprintifysync_cleanup_logs', [LogHelper::getInstance(), 'cleanupLogs']);
        add_action('wpwprintifysync_poll_emails', [LogHelper::getInstance(), 'pollEmails']);
    }
    
    /**
     * Add admin menu
     */
    public function addAdminMenu() {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),