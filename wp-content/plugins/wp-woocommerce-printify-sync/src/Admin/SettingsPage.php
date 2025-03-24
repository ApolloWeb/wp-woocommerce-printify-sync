<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Interfaces\SettingsInterface;
use ApolloWeb\WPWooCommercePrintifySync\Template\Engine;
use ApolloWeb\WPWooCommercePrintifySync\Traits\HandlesEncryptedOptions;

class SettingsPage implements SettingsInterface {
    use HandlesEncryptedOptions;

    private const OPTION_GROUP = 'wp_woocommerce_printify_sync_settings';
    private const MENU_SLUG = 'wp-woocommerce-printify-sync';

    private AssetsManager $assetsManager;
    private AjaxHandler $ajaxHandler;
    private Engine $template;

    public function __construct(AssetsManager $assetsManager, AjaxHandler $ajaxHandler) {
        $this->assetsManager = $assetsManager;
        $this->ajaxHandler = $ajaxHandler;
        $this->template = new Engine(
            dirname(__DIR__, 2) . '/templates/layouts',
            dirname(__DIR__, 2) . '/templates/admin'
        );
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'addMenuPages']);
        add_action('admin_enqueue_scripts', [$this->assetsManager, 'enqueueAssets']);
        add_action('admin_bar_menu', [$this, 'addAdminBarItems'], 100);
        // Initialize toast notifications on admin pages
        add_action('admin_init', [ToastNotifier::class, 'output']);
        $this->ajaxHandler->init();
    }

    public function addMenuPages(): void {
        // Main menu
        add_menu_page(
            __('Printify Dashboard', 'wp-woocommerce-printify-sync'),
            __('Printify', 'wp-woocommerce-printify-sync'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'renderDashboard'],
            'dashicons-cart', // Changed to dashicons-cart which is similar to a t-shirt/product icon
            25
        );

        // Dashboard submenu - same as parent
        add_submenu_page(
            self::MENU_SLUG,
            __('Printify Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'renderDashboard']
        );

        // Settings submenu
        add_submenu_page(
            self::MENU_SLUG,
            __('Printify Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            self::MENU_SLUG . '-settings', 
            [$this, 'renderSettings']
        );

        // Products submenu
        add_submenu_page(
            self::MENU_SLUG,
            __('Printify Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_options',
            self::MENU_SLUG . '-products', 
            [$this, 'renderProducts']
        );

        // Sync Log submenu
        add_submenu_page(
            self::MENU_SLUG,
            __('Sync Log', 'wp-woocommerce-printify-sync'),
            __('Sync Log', 'wp-woocommerce-printify-sync'),
            'manage_options',
            self::MENU_SLUG . '-log', 
            [$this, 'renderSyncLog']
        );
    }

    /**
     * Get current plugin page if we're on one
     * 
     * @return string|null The current page slug or null if not on plugin page
     */
    private function getCurrentPluginPage(): ?string {
        // Just check the page query parameter directly - don't rely on screen detection
        // This makes our admin bar work on all admin pages
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if (strpos($page, self::MENU_SLUG) === 0) {
            return $page;
        }
        
        return null;
    }

    /**
     * Add menu items to the WordPress admin bar
     * 
     * @param \WP_Admin_Bar $admin_bar The WordPress admin bar object
     * @return void
     */
    public function addAdminBarItems(\WP_Admin_Bar $admin_bar): void {
        // Add admin bar items to all admin pages
        if (!is_admin()) {
            return;
        }

        // Get current page to highlight active menu item
        $current_page = $this->getCurrentPluginPage();

        // Main node title - add "(current)" if on a plugin page
        $title = '<span class="ab-icon dashicons dashicons-cart"></span>' . __('Printify', 'wp-woocommerce-printify-sync');
        if ($current_page) {
            $title .= ' <span class="screen-reader-text">(' . __('current page', 'wp-woocommerce-printify-sync') . ')</span>';
        }

        // Add main Printify node
        $admin_bar->add_node([
            'id'    => 'printify-menu',
            'title' => $title,
            'href'  => admin_url('admin.php?page=' . self::MENU_SLUG),
        ]);

        // Add submenu items with active state indicators
        $admin_bar->add_node([
            'id'     => 'printify-dashboard',
            'parent' => 'printify-menu',
            'title'  => $current_page === self::MENU_SLUG ? 
                '<span class="dashicons dashicons-yes-alt" style="color:#46b450"></span> ' . __('Dashboard', 'wp-woocommerce-printify-sync') : 
                __('Dashboard', 'wp-woocommerce-printify-sync'),
            'href'   => admin_url('admin.php?page=' . self::MENU_SLUG),
        ]);

        $admin_bar->add_node([
            'id'     => 'printify-settings',
            'parent' => 'printify-menu',
            'title'  => $current_page === self::MENU_SLUG . '-settings' ? 
                '<span class="dashicons dashicons-yes-alt" style="color:#46b450"></span> ' . __('Settings', 'wp-woocommerce-printify-sync') : 
                __('Settings', 'wp-woocommerce-printify-sync'),
            'href'   => admin_url('admin.php?page=' . self::MENU_SLUG . '-settings'),
        ]);

        $admin_bar->add_node([
            'id'     => 'printify-products',
            'parent' => 'printify-menu',
            'title'  => $current_page === self::MENU_SLUG . '-products' ? 
                '<span class="dashicons dashicons-yes-alt" style="color:#46b450"></span> ' . __('Products', 'wp-woocommerce-printify-sync') : 
                __('Products', 'wp-woocommerce-printify-sync'),
            'href'   => admin_url('admin.php?page=' . self::MENU_SLUG . '-products'),
        ]);

        $admin_bar->add_node([
            'id'     => 'printify-sync-log',
            'parent' => 'printify-menu',
            'title'  => $current_page === self::MENU_SLUG . '-log' ? 
                '<span class="dashicons dashicons-yes-alt" style="color:#46b450"></span> ' . __('Sync Log', 'wp-woocommerce-printify-sync') : 
                __('Sync Log', 'wp-woocommerce-printify-sync'),
            'href'   => admin_url('admin.php?page=' . self::MENU_SLUG . '-log'),
        ]);

        // Add user profile link in a separate group
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            $admin_bar->add_group([
                'id'     => 'printify-user-actions',
                'parent' => 'printify-menu',
                'meta'   => [
                    'class' => 'ab-sub-secondary',
                ]
            ]);

            $admin_bar->add_node([
                'id'     => 'printify-user-profile',
                'parent' => 'printify-user-actions',
                'title'  => __('Your Profile', 'wp-woocommerce-printify-sync'),
                'href'   => get_edit_profile_url($user_id),
            ]);

            $admin_bar->add_node([
                'id'     => 'printify-help',
                'parent' => 'printify-user-actions',
                'title'  => __('Help', 'wp-woocommerce-printify-sync'),
                'href'   => 'https://github.com/ApolloWeb/wp-woocommerce-printify-sync/wiki',
                'meta'   => [
                    'target' => '_blank',
                    'title'  => __('View Documentation', 'wp-woocommerce-printify-sync'),
                ]
            ]);
        }
    }

    public function renderDashboard(): void {
        try {
            // Get required data
            $current_user = wp_get_current_user();
            $settings = $this->getSettings();
            $stats = $this->getStats();
            $credit_balance = get_option('credit_balance', 0);
            $has_low_credit = get_option('openai_low_credit_alert', false);
            $page_title = __('Printify Dashboard', 'wp-woocommerce-printify-sync');

            // Show a welcome toast on the dashboard
            if (isset($_GET['welcome']) && $_GET['welcome'] === '1') {
                ToastNotifier::add(
                    __('Welcome to Printify Dashboard!', 'wp-woocommerce-printify-sync'),
                    'info',
                    5000
                );
            }
            
            // Direct include approach - no debug banners
            define('WPWPS_HIDE_DEBUG_INFO', true);
            include dirname(__DIR__, 2) . '/templates/admin/wpwps-dashboard-direct.php';

            // Always ensure the force display script is loaded for emergency fallback
            add_action('admin_footer', function() {
                wp_enqueue_script('wpwps-force-display');
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    echo '<script>console.log("WPWPS: Emergency content script loaded");</script>';
                }
            });
        } catch (\Throwable $e) {
            // Log error
            error_log('WPWPS Dashboard Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            // Display basic error message
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Printify Dashboard', 'wp-woocommerce-printify-sync') . '</h1>';
            echo '<div class="notice notice-error"><p>Error loading dashboard: ' . esc_html($e->getMessage()) . '</p></div>';
            echo '</div>';
        }
    }

    public function renderSettings(): void {
        try {
            $current_user = wp_get_current_user();
            $settings = $this->getSettings();
            $credit_balance = get_option('credit_balance', 0);
            $has_low_credit = $credit_balance < 2;
            $page_title = __('Printify Settings', 'wp-woocommerce-printify-sync');
            
            // Skip template system entirely and use direct include - no debug banners
            define('WPWPS_HIDE_DEBUG_INFO', true);
            include dirname(__DIR__, 2) . '/templates/admin/wpwps-settings-direct.php';
        } catch (\Throwable $e) {
            // Log error
            error_log('WPWPS Critical Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            // Display basic error message
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Printify Settings', 'wp-woocommerce-printify-sync') . '</h1>';
            echo '<div class="notice notice-error"><p>Critical error: ' . esc_html($e->getMessage()) . '</p></div>';
            echo '</div>';
        }
    }

    public function renderProducts(): void {
        try {
            $current_user = wp_get_current_user();
            $settings = $this->getSettings();
            $page_title = __('Printify Products', 'wp-woocommerce-printify-sync');
            
            // Display a placeholder for now
            echo '<div class="wrap">';
            echo '<h1>' . esc_html($page_title) . '</h1>';
            echo '<div class="notice notice-info"><p>' . esc_html__('Products management coming soon.', 'wp-woocommerce-printify-sync') . '</p></div>';
            echo '</div>';
        } catch (\Throwable $e) {
            // Log error
            error_log('WPWPS Products Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            // Display basic error message
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Printify Products', 'wp-woocommerce-printify-sync') . '</h1>';
            echo '<div class="notice notice-error"><p>Error: ' . esc_html($e->getMessage()) . '</p></div>';
            echo '</div>';
        }
    }

    public function renderSyncLog(): void {
        try {
            $current_user = wp_get_current_user();
            $page_title = __('Sync Log', 'wp-woocommerce-printify-sync');
            
            // Display a placeholder for now
            echo '<div class="wrap">';
            echo '<h1>' . esc_html($page_title) . '</h1>';
            echo '<div class="notice notice-info"><p>' . esc_html__('Sync log functionality coming soon.', 'wp-woocommerce-printify-sync') . '</p></div>';
            echo '</div>';
        } catch (\Throwable $e) {
            // Log error
            error_log('WPWPS Sync Log Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            // Display basic error message
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Sync Log', 'wp-woocommerce-printify-sync') . '</h1>';
            echo '<div class="notice notice-error"><p>Error: ' . esc_html($e->getMessage()) . '</p></div>';
            echo '</div>';
        }
    }

    private function getSettings(): array {
        return [
            'printify_api_key' => $this->getDecryptedOption('printify_api_key'),
            'printify_api_endpoint' => get_option('printify_api_endpoint', 'https://api.printify.com/'),
            'openai_api_key' => $this->getDecryptedOption('openai_api_key'),
            'token_limit' => get_option('token_limit', 2000),
            'temperature' => get_option('temperature', 0.7),
            'credit_balance' => get_option('credit_balance', 0),
            'monthly_spend_cap' => get_option('monthly_spend_cap', 10),
            'printify_shop' => get_option('printify_shop')
        ];
    }

    private function getStats(): array {
        return [
            'total_products' => 2451,
            'sync_rate' => 98.2,
            'credit_balance' => get_option('credit_balance', 0)
        ];
    }

}