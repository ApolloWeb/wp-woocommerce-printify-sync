<?php
/**
 * Menu Manager
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Core\DataProvider;

/**
 * Manages admin menus for the plugin
 */
class MenuManager {
    /**
     * Data provider
     *
     * @var DataProvider
     */
    private $dataProvider;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->dataProvider = new DataProvider();
        
        // Add styling for menu badges
        add_action('admin_head', [$this, 'addMenuStyles']);
        
        // Add UI enhancement actions
        $this->registerUiActions();
    }
    
    /**
     * Register UI enhancement actions
     *
     * @return void
     */
    private function registerUiActions(): void {
        // Add premium UI body class
        add_action('admin_body_class', [$this, 'addPremiumBodyClass']);
        
        // Implement quick actions system
        add_action('admin_footer', [$this, 'renderQuickActionsMenu']);
        add_action('admin_footer', [$this, 'renderQuickActionsButton']);
    }
    
    /**
     * Register admin menus for the plugin
     * 
     * @return void
     */
    public function registerPluginMenus(): void {
        // Main dashboard
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',  // Require WooCommerce management capability
            'wpwps-dashboard',
            [$this, 'renderDashboardPage'],
            'dashicons-store',
            58  // Position after WooCommerce
        );

        // Add submenus with enhanced UI features
        $submenus = $this->getSubmenus();
        foreach ($submenus as $slug => $submenu) {
            // Skip separator items
            if (isset($submenu['type']) && $submenu['type'] === 'separator') {
                continue;
            }
            
            // Generate enhanced menu title with badges if needed
            $menu_title = $this->formatMenuTitle(
                $submenu['menu'], 
                $submenu['icon'] ?? '',
                $submenu['badge_count'] ?? 0
            );

            add_submenu_page(
                'wpwps-dashboard', // Parent slug
                $submenu['title'],  // Page title
                $menu_title,        // Menu title (now with icon/badge)
                $submenu['capability'], // Capability
                $slug,              // Menu slug
                $submenu['callback'] // Callbacks
            );
        }
    }
    
    /**
     * Format menu title with icon and badge
     * 
     * @param string $title Menu title
     * @param string $icon Icon class (dashicons or fontawesome)
     * @param int $badge_count Badge count (0 = no badge)
     * @return string Formatted title HTML
     */
    private function formatMenuTitle(string $title, string $icon = '', int $badge_count = 0): string {
        $formatted = $title;
        // Add icon if provided
        if (!empty($icon)) {
            if (strpos($icon, 'fa-') === 0) {
                // Font Awesome icon
                $formatted = '<span class="menu-icon"><i class="fa ' . esc_attr($icon) . '"></i></span> ' . $formatted;
            } elseif (strpos($icon, 'dashicons-') === 0) {
                // Dashicons icon
                $formatted = '<span class="menu-icon dashicons ' . esc_attr($icon) . '"></span> ' . $formatted;
            }
        }

        // Add badge if count > 0
        if ($badge_count > 0) {
            $formatted .= ' <span class="wpwps-menu-badge">' . esc_html($badge_count) . '</span>';
        }

        return $formatted;
    }
    
    /**
     * Get submenu definitions with enhanced UI features
     * 
     * @return array Submenu definitions
     */
    private function getSubmenus(): array {
        // Get pending order and sync notification counts
        $pendingOrderCount = $this->dataProvider->getPendingOrderCount();
        $pendingSyncCount = $this->dataProvider->getPendingSyncCount();

        $submenus = [
            'wpwps-dashboard' => [
                'title' => __('Dashboard', 'wp-woocommerce-printify-sync'),
                'menu' => __('Dashboard', 'wp-woocommerce-printify-sync'),
                'icon' => 'dashicons-dashboard',
                'capability' => 'manage_woocommerce',
                'callback' => [$this, 'renderDashboardPage']
            ],
            'wpwps-products' => [
                'title' => __('Products', 'wp-woocommerce-printify-sync'),
                'menu' => __('Products', 'wp-woocommerce-printify-sync'),
                'icon' => 'dashicons-products',
                'badge_count' => $pendingSyncCount, // Show pending sync count
                'capability' => 'manage_woocommerce',
                'callback' => [$this, 'renderProductsPage']
            ],
            'wpwps-orders' => [
                'title' => __('Orders', 'wp-woocommerce-printify-sync'),
                'menu' => __('Orders', 'wp-woocommerce-printify-sync'),
                'icon' => 'dashicons-cart',
                'badge_count' => $pendingOrderCount, // Show pending order count
                'capability' => 'manage_woocommerce',
                'callback' => [$this, 'renderOrdersPage']
            ],
            'wpwps-settings' => [
                'title' => __('Settings', 'wp-woocommerce-printify-sync'),
                'menu' => __('Settings', 'wp-woocommerce-printify-sync'),
                'icon' => 'dashicons-admin-settings',
                'capability' => 'manage_options',
                'callback' => [$this, 'renderSettingsPage']
            ]
        ];

        // Allow other plugins to modify our submenus
        return apply_filters('wpwps_admin_submenus', $submenus);
    }
    
    /**
     * Add custom CSS for enhanced menu styling
     * 
     * @return void
     */
    public function addMenuStyles(): void {
        ?>
        <style>
            #adminmenu .wp-has-current-submenu .wpwps-menu-badge,
            #adminmenu .current .wpwps-menu-badge {
                display: inline-block;
                vertical-align: top;
                box-sizing: border-box;
                margin: 1px 0 -1px 5px;
                padding: 0 5px;
                min-width: 18px;
                height: 18px;
                border-radius: 9px;
                background-color: #ca4a1f;
                color: #fff;
                font-size: 11px;
                line-height: 1.6;
                text-align: center;
                z-index: 26;
            }

            #adminmenu li.menu-top > a .wpwps-menu-badge {
                margin-top: 5px;
            }

            /* Menu icon styling */
            #adminmenu .menu-icon {
                display: inline-block;
                margin-right: 4px;
                text-align: center;
            }

            /* Custom styling for the main Printify menu */
            #toplevel_page_wpwps-dashboard .wp-menu-image::before {
                color: #96588a !important;
            }

            /* Active menu state */
            #adminmenu li.current a.menu-top,
            #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu {
                background: #96588a;
                color: #fff;
            }
        </style>
        <?php
    }
    
    /**
     * Add premium UI body class
     *
     * @param string $classes Current body classes
     * @return string Modified body classes
     */
    public function addPremiumBodyClass(string $classes): string {
        $screen = get_current_screen();
        if (strpos($screen->id, 'wpwps') !== false) {
            $classes .= ' wpwps-premium-ui';
        }
        return $classes;
    }
    
    /**
     * Render Quick Actions menu
     *
     * @return void
     */
    public function renderQuickActionsMenu(): void {
        // Only show on our plugin pages
        $screen = get_current_screen();
        if (strpos($screen->id, 'wpwps') === false) {
            return;
        }

        // Output the Quick Actions menu structure
        ?>
        <div id="wpwps-quick-actions" class="wpwps-quick-actions hidden">
            <div class="wpwps-quick-actions-header">
                <h3><?php echo esc_html__('Quick Actions', 'wp-woocommerce-printify-sync'); ?></h3>
                <button type="button" class="wpwps-quick-actions-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="wpwps-quick-actions-content"></div>
        </div>
        <?php
    }
    
    /**
     * Render Quick Actions button
     *
     * @return void
     */
    public function renderQuickActionsButton(): void {
        // Only show on our plugin pages
        $screen = get_current_screen();
        if (strpos($screen->id, 'wpwps') === false) {
            return;
        }

        // Add background effects container
        echo '<div class="wpwps-bg-shapes"></div>';

        // Output the floating action button with premium styling
        ?>
        <button id="wpwps-quick-actions-toggle" class="wpwps-floating-button premium-glow" title="<?php esc_attr_e('Quick Actions (Alt+Q)', 'wp-woocommerce-printify-sync'); ?>">
            <span class="dashicons dashicons-admin-tools"></span>
        </button>

        <!-- SVG definitions for loaders and gradients -->
        <svg width="0" height="0" style="position:absolute">
            <defs>
                <linearGradient id="gradient-primary" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="var(--wpwps-primary-light)"></stop>
                    <stop offset="100%" stop-color="var(--wpwps-primary-dark)"></stop>
                </linearGradient>
                <linearGradient id="gradient-secondary" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="var(--wpwps-secondary-light)"></stop>
                    <stop offset="100%" stop-color="var(--wpwps-secondary-dark)"></stop>
                </linearGradient>
            </defs>
        </svg>
        <?php
    }
    
    /**
     * Render the dashboard page
     *
     * @return void
     */
    public function renderDashboardPage(): void {
        // Prepare dashboard data
        $dashboardData = $this->dataProvider->prepareDashboardData();
        extract($dashboardData);

        // Set active page for navigation
        $active_page = 'dashboard';
        
        require_once WPWPS_PATH . 'templates/wpwps-dashboard.php';
    }
    
    /**
     * Render the products page
     *
     * @return void
     */
    public function renderProductsPage(): void {
        // Prepare products data
        $productsData = $this->dataProvider->prepareProductsData();
        extract($productsData);

        // Set active page for navigation
        $active_page = 'products';
        
        require_once WPWPS_PATH . 'templates/wpwps-products.php';
    }
    
    /**
     * Render the orders page
     *
     * @return void
     */
    public function renderOrdersPage(): void {
        // Prepare orders data
        $ordersData = $this->dataProvider->prepareOrdersData();
        extract($ordersData);

        // Set active page for navigation
        $active_page = 'orders';
        
        require_once WPWPS_PATH . 'templates/wpwps-orders.php';
    }
    
    /**
     * Render the settings page
     *
     * @return void
     */
    public function renderSettingsPage(): void {
        // Get the settings from the controller to pre-populate form fields
        $controller = new \ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsController();
        $settings = $controller->getSettings();
        
        // Extract settings into variables for the template
        extract($settings);
        
        // Pass controller to template for potential advanced operations
        $settingsController = $controller;
        
        // Set active page for navigation
        $active_page = 'settings';
        
        require_once WPWPS_PATH . 'templates/wpwps-settings.php';
    }
}
