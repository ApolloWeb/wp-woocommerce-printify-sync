<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Admin Menu handler
 */
class AdminMenu {
    /**
     * Initialize admin menu
     */
    public function init(): void {
        add_action('admin_menu', [$this, 'registerMenus']);
    }
    
    /**
     * Register admin menus
     */
    public function registerMenus(): void {
        // Main menu
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboardPage'],
            'dashicons-store',
            56
        );
        
        // Submenus
        add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboardPage']
        );
        
        add_submenu_page(
            'wpwps-dashboard',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-products',
            [$this, 'renderProductsPage']
        );
        
        add_submenu_page(
            'wpwps-dashboard',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-orders',
            [$this, 'renderOrdersPage']
        );
        
        add_submenu_page(
            'wpwps-dashboard',
            __('Shipping', 'wp-woocommerce-printify-sync'),
            __('Shipping', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-shipping',
            [$this, 'renderShippingPage']
        );
        
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettingsPage']
        );
        
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
     * Render dashboard page
     */
    public function renderDashboardPage(): void {
        $this->renderTemplate('dashboard');
    }
    
    /**
     * Render products page
     */
    public function renderProductsPage(): void {
        $this->renderTemplate('products');
    }
    
    /**
     * Render orders page
     */
    public function renderOrdersPage(): void {
        $this->renderTemplate('orders');
    }
    
    /**
     * Render shipping page
     */
    public function renderShippingPage(): void {
        // Get shipping profiles to display in the template
        $shipping_repository = new \ApolloWeb\WPWooCommercePrintifySync\Repositories\ShippingRepository();
        $profiles = $shipping_repository->getAllShippingProfiles();
        
        $this->renderTemplate('shipping', ['profiles' => $profiles]);
    }
    
    /**
     * Render settings page
     */
    public function renderSettingsPage(): void {
        $this->renderTemplate('settings');
    }
    
    /**
     * Render logs page
     */
    public function renderLogsPage(): void {
        $logger = new \ApolloWeb\WPWooCommercePrintifySync\Core\Logger();
        $logs = $logger->getLogs(100); // Get last 100 logs
        
        $this->renderTemplate('logs', ['logs' => $logs]);
    }
    
    /**
     * Render admin template
     *
     * @param string $template Template name without extension
     * @param array $data Data to pass to the template
     */
    private function renderTemplate(string $template, array $data = []): void {
        $file = WPPS_PATH . "templates/admin/{$template}.php";
        
        if (!file_exists($file)) {
            echo '<div class="notice notice-error"><p>' . 
                sprintf(__('Template file not found: %s', 'wp-woocommerce-printify-sync'), $file) .
                '</p></div>';
            return;
        }
        
        // Extract data variables for use in template
        extract($data);
        
        // Include the template file
        include $file;
    }
}
