<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\BaseServiceProvider;

class AssetsServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     * 
     * @return void
     */
    public function register(): void
    {
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Initialize admin JS
        add_action('admin_footer', [$this, 'initializeAdminJs']);
    }
    
    /**
     * Enqueue admin assets.
     * 
     * @return void
     */
    public function enqueueAdminAssets(): void
    {
        if (!$this->isPluginPage(get_current_screen()->id)) {
            return;
        }

        // Core styles
        wp_enqueue_style(
            'wpwps-bootstrap',
            WPWPS_ASSETS_URL . 'core/css/bootstrap.min.css',
            [],
            WPWPS_VERSION
        );

        // Admin CSS
        wp_enqueue_style(
            'wpwps-admin',
            WPWPS_ASSETS_URL . 'css/wpwps-dashboard.css',
            ['wpwps-bootstrap'],
            WPWPS_VERSION
        );

        // Page-specific CSS
        $screen = get_current_screen();
        if (strpos($screen->id, 'wpwps-settings') !== false) {
            wp_enqueue_style(
                'wpwps-settings',
                WPWPS_ASSETS_URL . 'css/wpwps-settings.css',
                ['wpwps-admin'],
                WPWPS_VERSION
            );
        }

        // Admin JS
        wp_enqueue_script(
            'wpwps-admin',
            WPWPS_ASSETS_URL . 'js/wpwps-dashboard.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );

        // Page-specific JS
        if (strpos($screen->id, 'wpwps-settings') !== false) {
            wp_enqueue_script(
                'wpwps-settings',
                WPWPS_ASSETS_URL . 'js/wpwps-settings.js',
                ['wpwps-admin'],
                WPWPS_VERSION,
                true
            );
        }
        
        // Add FontAwesome for icons
        wp_enqueue_style(
            'wpwps-fontawesome',
            WPWPS_ASSETS_URL . 'core/css/fontawesome.min.css',
            [],
            '6.4.0'
        );
        
        // Localize script with data and translations
        wp_localize_script('wpwps-admin', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => esc_url_raw(rest_url('wpwps/v1/')),
            'nonce' => wp_create_nonce('wpwps-admin-nonce'),
            'i18n' => [
                'testing_connection' => __('Testing connection...', 'wp-woocommerce-printify-sync'),
                'connection_success' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
                'connection_error' => __('Connection failed: ', 'wp-woocommerce-printify-sync'),
                'confirm_sync' => __('Are you sure you want to synchronize all products? This may take some time.', 'wp-woocommerce-printify-sync'),
                'confirm_order_sync' => __('Are you sure you want to send this order to Printify?', 'wp-woocommerce-printify-sync'),
                'loading_shops' => __('Loading available shops...', 'wp-woocommerce-printify-sync'),
                'select_shop' => __('Please select a shop...', 'wp-woocommerce-printify-sync'),
                'shop_error' => __('Error loading shops: ', 'wp-woocommerce-printify-sync'),
                'order_sent' => __('Order sent to Printify!', 'wp-woocommerce-printify-sync'),
                'order_error' => __('Error sending order: ', 'wp-woocommerce-printify-sync'),
                'saving' => __('Saving...', 'wp-woocommerce-printify-sync'),
                'missing_credentials' => __('Please enter both API Key and Shop ID.', 'wp-woocommerce-printify-sync'),
                'connected' => __('Connected', 'wp-woocommerce-printify-sync'),
                'not_connected' => __('Not Connected', 'wp-woocommerce-printify-sync'),
                'auto_sync_disabled' => __('Automatic synchronization has been disabled.', 'wp-woocommerce-printify-sync'),
            ]
        ]);
    }
    
    /**
     * Initialize admin JS in footer.
     * 
     * @return void
     */
    public function initializeAdminJs(): void
    {
        // Only add to plugin admin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wpwps') === false) {
            return;
        }
        
        // Add inline initialization code
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Initialize plugin admin functionality
                if (typeof WPWPS_Admin !== 'undefined') {
                    WPWPS_Admin.init();
                }
            });
        </script>
        <?php
    }
    
    /**
     * Check if current page is a plugin admin page.
     * 
     * @param string $hook The current admin page hook
     * @return bool
     */
    protected function isPluginPage(string $hook): bool
    {
        $pluginPages = [
            'toplevel_page_wpwps-settings',
            'printify-sync_page_wpwps-product-sync',
            'printify-sync_page_wpwps-logs',
            'post.php', // For product and order edit pages
            'post-new.php', // For new product page
            'edit.php' // For product and order list pages
        ];
        
        // Return true if current hook is in plugin pages list
        if (in_array($hook, $pluginPages)) {
            return true;
        }
        
        // Special check for edit.php and post.php to only load on relevant post types
        if ($hook === 'edit.php' || $hook === 'post.php' || $hook === 'post-new.php') {
            $post_type = $_GET['post_type'] ?? null;
            
            // If no post type in URL but we're editing a post, get its type
            if (!$post_type && isset($_GET['post'])) {
                $post_type = get_post_type($_GET['post']);
            }
            
            // Only return true for product and order post types
            return in_array($post_type, ['product', 'shop_order']);
        }
        
        return false;
    }
    
    /**
     * Generate CSS variables for color scheme.
     * 
     * @return string
     */
    protected function generateCssVariables(): string
    {
        $variables = [
            '--wpwps-primary-color' => '#7e3bd0',
            '--wpwps-secondary-color' => '#f1ebfa',
            '--wpwps-accent-color' => '#3c1d68',
            '--wpwps-text-color' => '#333333',
            '--wpwps-border-color' => '#dddddd',
            '--wpwps-success-color' => '#46b450',
            '--wpwps-error-color' => '#dc3232',
            '--wpwps-warning-color' => '#ffb900',
            '--wpwps-info-color' => '#00a0d2',
        ];
        
        $css = ':root {';
        foreach ($variables as $var => $value) {
            $css .= $var . ': ' . $value . ';';
        }
        $css .= '}';
        
        return $css;
    }
}