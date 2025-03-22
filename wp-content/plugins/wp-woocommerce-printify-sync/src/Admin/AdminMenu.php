<?php
/**
 * Admin Menu Handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Services\TemplateLoader;
use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use Exception;

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
     * Product service instance.
     *
     * @var \ApolloWeb\WPWooCommercePrintifySync\Products\ProductSync
     */
    private $productService;

    /**
     * Order service instance.
     *
     * @var \ApolloWeb\WPWooCommercePrintifySync\Orders\OrderSync
     */
    private $orderService;

    /**
     * Activity service instance.
     *
     * @var \ApolloWeb\WPWooCommercePrintifySync\Services\ActivityService
     */
    private $activityService;

    /**
     * Action scheduler instance.
     *
     * @var \ApolloWeb\WPWooCommercePrintifySync\Services\ActionSchedulerService
     */
    private $action_scheduler;

    /**
     * Constructor.
     *
     * @param TemplateLoader $template Template loader.
     * @param Logger         $logger   Logger instance.
     * @param \ApolloWeb\WPWooCommercePrintifySync\Products\ProductSync $productService Product service instance.
     * @param \ApolloWeb\WPWooCommercePrintifySync\Orders\OrderSync $orderService Order service instance.
     * @param \ApolloWeb\WPWooCommercePrintifySync\Services\ActivityService $activityService Activity service instance.
     * @param \ApolloWeb\WPWooCommercePrintifySync\Services\ActionSchedulerService $action_scheduler Action scheduler instance.
     */
    public function __construct(
        TemplateLoader $template,
        Logger $logger,
        $productService,
        $orderService,
        $activityService,
        $action_scheduler
    ) {
        $this->template = $template;
        $this->logger = $logger;
        $this->productService = $productService;
        $this->orderService = $orderService;
        $this->activityService = $activityService;
        $this->action_scheduler = $action_scheduler;
    }

    /**
     * Initialize the admin menu.
     *
     * @return void
     */
    public function init()
    {
        // Higher priority to ensure our menu is registered after other admin menus
        add_action('admin_menu', [$this, 'registerMenus'], 20);
        add_action('admin_init', [$this, 'registerSettings']);
        
        // Debug hook to check if init is being called
        add_action('admin_notices', function() {
            if (current_user_can('manage_options')) {
                error_log('AdminMenu init called');
            }
        });
    }

    /**
     * Register admin menus.
     *
     * @return void
     */
    public function registerMenus()
    {
        // Debug log to check if registerMenus is being called
        error_log('AdminMenu registerMenus called');
        
        // Main menu - Use Font Awesome icon directly
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options', // Changed from manage_woocommerce to core WP capability
            'wpwps-dashboard',
            [$this, 'renderDashboardPage'],
            'dashicons-tshirt', // WP built-in icon that looks like a t-shirt
            30 // Changed position to be higher in the menu
        );

        // Dashboard submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-dashboard',
            [$this, 'renderDashboardPage']
        );

        // Settings submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-settings',
            [$this, 'renderSettingsPage']
        );

        // Products submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-products',
            [$this, 'renderProductsPage']
        );

        // Orders submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-orders',
            [$this, 'renderOrdersPage']
        );

        // Shipping submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Shipping', 'wp-woocommerce-printify-sync'),
            __('Shipping', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-shipping',
            [$this, 'renderShippingPage']
        );

        // Tickets submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Tickets', 'wp-woocommerce-printify-sync'),
            __('Tickets', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-tickets',
            [$this, 'renderTicketsPage']
        );

        // Logs submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_options',
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
     * Render the dashboard page with robust error handling.
     *
     * @return void
     */
    public function renderDashboardPage()
    {
        // Set error reporting to catch all issues
        $original_error_reporting = error_reporting(E_ALL);
        $original_display_errors = ini_get('display_errors');
        ini_set('display_errors', 0);
        
        try {
            // Buffer output to catch any warnings/errors
            ob_start();
            
            // Basic dashboard structure that doesn't rely on templates or complex data
            echo '<div class="wrap" style="font-family: -apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,Cantarell,\'Helvetica Neue\',sans-serif;">';
            echo '<h1 style="margin-bottom: 20px;"><span class="dashicons dashicons-store" style="font-size: 30px; vertical-align: middle; margin-right: 10px;"></span> ' . esc_html__('Printify Sync Dashboard', 'wp-woocommerce-printify-sync') . '</h1>';
            
            // Check if we have shop ID configured
            $shop_id = get_option('wpwps_printify_shop_id', '');
            
            if (empty($shop_id)) {
                echo '<div class="notice notice-warning" style="padding: 15px; border-left-color: #ffb900;">';
                echo '<p>' . esc_html__('Your Printify Shop is not configured yet. Please go to the Settings page and set up your API connection.', 'wp-woocommerce-printify-sync') . ' ';
                echo '<a href="' . esc_url(admin_url('admin.php?page=wpwps-settings')) . '" class="button button-primary">';
                echo esc_html__('Go to Settings', 'wp-woocommerce-printify-sync');
                echo '</a></p></div>';
            } else {
                // Dashboard content for configured shop
                echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-top: 20px;">';
                
                // Quick actions card
                echo '<div style="background: white; border-radius: 5px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
                echo '<h2 style="margin-top: 0;">' . esc_html__('Quick Actions', 'wp-woocommerce-printify-sync') . '</h2>';
                echo '<div style="display: grid; gap: 10px;">';
                echo '<a href="' . esc_url(admin_url('admin.php?page=wpwps-products')) . '" class="button" style="text-align: center;">';
                echo '<span class="dashicons dashicons-products" style="margin-right: 5px;"></span> ' . esc_html__('Manage Products', 'wp-woocommerce-printify-sync');
                echo '</a>';
                echo '<a href="' . esc_url(admin_url('admin.php?page=wpwps-orders')) . '" class="button" style="text-align: center;">';
                echo '<span class="dashicons dashicons-cart" style="margin-right: 5px;"></span> ' . esc_html__('Manage Orders', 'wp-woocommerce-printify-sync');
                echo '</a>';
                echo '<a href="' . esc_url(admin_url('admin.php?page=wpwps-settings')) . '" class="button" style="text-align: center;">';
                echo '<span class="dashicons dashicons-admin-settings" style="margin-right: 5px;"></span> ' . esc_html__('Settings', 'wp-woocommerce-printify-sync');
                echo '</a>';
                echo '</div>';
                echo '</div>';
                
                // Info card
                echo '<div style="background: white; border-radius: 5px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
                echo '<h2 style="margin-top: 0;">' . esc_html__('Getting Started', 'wp-woocommerce-printify-sync') . '</h2>';
                echo '<p>' . esc_html__('Use the Products page to synchronize products from Printify to WooCommerce.', 'wp-woocommerce-printify-sync') . '</p>';
                echo '<p>' . esc_html__('The Orders page allows you to send WooCommerce orders to Printify for fulfillment.', 'wp-woocommerce-printify-sync') . '</p>';
                echo '</div>';
                
                // System Status
                echo '<div style="background: white; border-radius: 5px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
                echo '<h2 style="margin-top: 0;">' . esc_html__('System Status', 'wp-woocommerce-printify-sync') . '</h2>';
                echo '<ul style="margin-left: 0; padding-left: 0; list-style: none;">';
                
                // Show WordPress version
                echo '<li style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0;">';
                echo '<strong>WordPress:</strong> <span style="float: right;">' . esc_html(get_bloginfo('version')) . '</span>';
                echo '</li>';
                
                // Show WooCommerce version if active
                if (defined('WC_VERSION')) {
                    echo '<li style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0;">';
                    echo '<strong>WooCommerce:</strong> <span style="float: right;">' . esc_html(WC_VERSION) . '</span>';
                    echo '</li>';
                }
                
                // Show PHP version
                echo '<li style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0;">';
                echo '<strong>PHP:</strong> <span style="float: right;">' . esc_html(phpversion()) . '</span>';
                echo '</li>';
                
                // Plugin version
                echo '<li style="margin-bottom: 10px;">';
                echo '<strong>Plugin:</strong> <span style="float: right;">' . esc_html(WPWPS_VERSION) . '</span>';
                echo '</li>';
                
                echo '</ul>';
                echo '</div>';
                
                echo '</div>'; // End grid
            }
            
            // Plugin version and support info
            echo '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; font-size: 12px;">';
            echo 'WP WooCommerce Printify Sync v' . esc_html(WPWPS_VERSION);
            echo ' | <a href="https://example.com/support" target="_blank">Support</a>';
            echo '</div>';
            
            echo '</div>'; // End wrap
            
            // Get and clean output buffer
            $output = ob_get_clean();
            echo $output;
            
        } catch (\Throwable $e) {
            // Clean any existing output
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Log the error
            if (method_exists($this, 'logger') && $this->logger) {
                $this->logger->error('Dashboard rendering error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            } else {
                error_log('Printify Sync Dashboard Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            }
            
            // Show friendly error message
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Printify Sync Dashboard', 'wp-woocommerce-printify-sync') . '</h1>';
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('There was an error loading the dashboard. Please check the server logs for more information.', 'wp-woocommerce-printify-sync');
            echo '</p>';
            
            // Only show detailed error to administrators
            if (current_user_can('manage_options')) {
                echo '<p><strong>Error details (visible to admins only):</strong> ' . esc_html($e->getMessage()) . '</p>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        // Restore original error settings
        error_reporting($original_error_reporting);
        ini_set('display_errors', $original_display_errors);
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
