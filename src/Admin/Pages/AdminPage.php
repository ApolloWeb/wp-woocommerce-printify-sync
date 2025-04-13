<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

/**
 * Admin Page Class
 */
class AdminPage {
    /**
     * Register admin menus
     * 
     * @return void
     */
    public function registerMenus() {
        // Main menu
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-shirt', // Using the closest dashicon to a t-shirt
            58
        );

        // Dashboard submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-dashboard',
            [$this, 'renderDashboard']
        );

        // Settings submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-settings',
            [$this, 'renderSettings']
        );
        
        // Products submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-products',
            [$this, 'renderProducts']
        );
        
        // Orders submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-orders',
            [$this, 'renderOrders']
        );
        
        // Shipping submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Shipping', 'wp-woocommerce-printify-sync'),
            __('Shipping', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-shipping',
            [$this, 'renderShipping']
        );
        
        // Tickets submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Tickets', 'wp-woocommerce-printify-sync'),
            __('Tickets', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-tickets',
            [$this, 'renderTickets']
        );
    }
    
    /**
     * Render dashboard page
     * 
     * @return void
     */
    public function renderDashboard() {
        $this->loadPageAssets('dashboard');
        $templateEngine = new \ApolloWeb\WPWooCommercePrintifySync\Core\TemplateEngine();
        $templateEngine->render('wpwps-dashboard', [
            'title' => __('Dashboard', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * Render settings page
     * 
     * @return void
     */
    public function renderSettings() {
        $this->loadPageAssets('settings');
        $templateEngine = new \ApolloWeb\WPWooCommercePrintifySync\Core\TemplateEngine();
        $templateEngine->render('wpwps-settings');
    }
    
    /**
     * Render products page
     * 
     * @return void
     */
    public function renderProducts() {
        $this->loadPageAssets('products');
        $templateEngine = new \ApolloWeb\WPWooCommercePrintifySync\Core\TemplateEngine();
        $templateEngine->render('wpwps-products');
    }
    
    /**
     * Render orders page
     * 
     * @return void
     */
    public function renderOrders() {
        $this->loadPageAssets('orders');
        $templateEngine = new \ApolloWeb\WPWooCommercePrintifySync\Core\TemplateEngine();
        $templateEngine->render('wpwps-orders');
    }
    
    /**
     * Render shipping page
     * 
     * @return void
     */
    public function renderShipping() {
        $this->loadPageAssets('shipping');
        $templateEngine = new \ApolloWeb\WPWooCommercePrintifySync\Core\TemplateEngine();
        $templateEngine->render('wpwps-shipping');
    }
    
    /**
     * Render tickets page
     * 
     * @return void
     */
    public function renderTickets() {
        $this->loadPageAssets('tickets');
        $templateEngine = new \ApolloWeb\WPWooCommercePrintifySync\Core\TemplateEngine();
        $templateEngine->render('wpwps-tickets');
    }
    
    /**
     * Load page specific assets
     * 
     * @param string $page Page name
     * @return void
     */
    private function loadPageAssets($page) {
        wp_enqueue_style(
            'wpwps-' . $page,
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-' . $page . '.css',
            [],
            WPWPS_VERSION
        );
        
        wp_enqueue_script(
            'wpwps-' . $page,
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-' . $page . '.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('wpwps-' . $page, 'wpwps_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-' . $page . '-nonce')
        ]);
    }
}
