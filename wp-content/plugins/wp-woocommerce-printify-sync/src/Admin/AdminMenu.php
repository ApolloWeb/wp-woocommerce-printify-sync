<?php
/**
 * Admin Menu Handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Services\TemplateLoader;
use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;

/**
 * Admin Menu Handler class.
 */
class AdminMenu
{
    /**
     * Template loader.
     *
     * @var TemplateLoader
     */
    private $template;
    
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param TemplateLoader $template Template loader.
     * @param Logger         $logger   Logger instance.
     */
    public function __construct(TemplateLoader $template, Logger $logger)
    {
        $this->template = $template;
        $this->logger = $logger;
    }

    /**
     * Initialize the admin menu.
     *
     * @return void
     */
    public function init()
    {
        add_action('admin_menu', [$this, 'registerMenus']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Register admin menus.
     *
     * @return void
     */
    public function registerMenus()
    {
        // Main menu
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboardPage'],
            'dashicons-admin-appearance',  // Changed from 'dashicons-store' to 'dashicons-admin-appearance' (clothing icon)
            58
        );

        // Dashboard submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboardPage']
        );

        // Settings submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettingsPage']
        );

        // Products submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-products',
            [$this, 'renderProductsPage']
        );

        // Orders submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-orders',
            [$this, 'renderOrdersPage']
        );

        // Shipping submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Shipping', 'wp-woocommerce-printify-sync'),
            __('Shipping', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-shipping',
            [$this, 'renderShippingPage']
        );

        // Tickets submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Tickets', 'wp-woocommerce-printify-sync'),
            __('Tickets', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-tickets',
            [$this, 'renderTicketsPage']
        );

        // Logs submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-logs',
            [$this, 'renderLogsPage']
        );
    }

    /**
     * Register admin settings.
     *
     * @return void
     */
    public function registerSettings()
    {
        // Register API settings
        register_setting('wpwps_settings', 'wpwps_printify_api_endpoint');
        register_setting('wpwps_settings', 'wpwps_printify_shop_id');
        
        // Register ChatGPT settings
        register_setting('wpwps_settings', 'wpwps_chatgpt_temperature');
        register_setting('wpwps_settings', 'wpwps_chatgpt_monthly_budget');
        
        // Register general plugin settings
        register_setting('wpwps_settings', 'wpwps_log_level');
    }

    /**
     * Render the dashboard page.
     *
     * @return void
     */
    public function renderDashboardPage()
    {
        try {
            $data = $this->getDashboardData();
            echo $this->template->render('dashboard.php', $data);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            wp_die(__('Error loading dashboard', 'wp-woocommerce-printify-sync'));
        }
    }

    private function getDashboardData() {
        return [
            'product_count' => $this->productService->getProductCount(),
            'order_count' => $this->orderService->getOrderCount(),
            'pending_syncs' => $this->getPendingSyncs(),
            'recent_activities' => $this->activityService->getRecentActivities(10),
            'revenue_data' => $this->getRevenueData(),
            'queue_stats' => [
                'products' => $this->action_scheduler->getPendingActionsCount('wpwps_sync_product'),
                'orders' => $this->action_scheduler->getPendingActionsCount('wpwps_sync_order'),
                'emails' => $this->action_scheduler->getPendingActionsCount('wpwps_process_email')
            ]
        ];
    }

    /**
     * Render the settings page.
     *
     * @return void
     */
    public function renderSettingsPage()
    {
        $api_endpoint = get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1/');
        $shop_id = get_option('wpwps_printify_shop_id', '');
        $shop_name = get_option('wpwps_printify_shop_name', '');
        $temperature = get_option('wpwps_chatgpt_temperature', 0.7);
        $monthly_budget = get_option('wpwps_chatgpt_monthly_budget', 10000);
        $log_level = get_option('wpwps_log_level', 'info');
        
        $this->template->render('wpwps-settings', [
            'api_endpoint' => $api_endpoint,
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
            'temperature' => $temperature,
            'monthly_budget' => $monthly_budget,
            'log_level' => $log_level,
        ]);
    }

    /**
     * Render the products page.
     *
     * @return void
     */
    public function renderProductsPage()
    {
        $shop_id = get_option('wpwps_printify_shop_id', '');
        $shop_name = get_option('wpwps_printify_shop_name', '');
        
        $this->template->render('wpwps-products', [
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
        ]);
    }

    /**
     * Render the orders page.
     *
     * @return void
     */
    public function renderOrdersPage()
    {
        $shop_id = get_option('wpwps_printify_shop_id', '');
        $shop_name = get_option('wpwps_printify_shop_name', '');
        
        // Get Action Scheduler counts for orders
        global $wpwps_container;
        $action_scheduler = $wpwps_container->get('action_scheduler');
        $pending_order_syncs = $action_scheduler->getPendingActionsCount('wpwps_as_sync_order');
        
        $this->template->render('wpwps-orders', [
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
            'pending_order_syncs' => $pending_order_syncs,
        ]);
    }

    /**
     * Render the shipping page.
     *
     * @return void
     */
    public function renderShippingPage()
    {
        $shop_id = get_option('wpwps_printify_shop_id', '');
        $shop_name = get_option('wpwps_printify_shop_name', '');
        
        $this->template->render('wpwps-shipping', [
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
        ]);
    }

    /**
     * Render the tickets page.
     *
     * @return void
     */
    public function renderTicketsPage()
    {
        $shop_id = get_option('wpwps_printify_shop_id', '');
        $shop_name = get_option('wpwps_printify_shop_name', '');
        
        $this->template->render('wpwps-tickets', [
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
        ]);
    }

    /**
     * Render the logs page.
     *
     * @return void
     */
    public function renderLogsPage()
    {
        $shop_id = get_option('wpwps_printify_shop_id', '');
        $shop_name = get_option('wpwps_printify_shop_name', '');
        
        $this->template->render('wpwps-logs', [
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
        ]);
    }
}
