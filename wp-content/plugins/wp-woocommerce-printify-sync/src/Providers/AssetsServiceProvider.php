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
     * Enqueue admin scripts and styles.
     * 
     * @param string $hook The current admin page
     * @return void
     */
    public function enqueueAdminAssets(string $hook): void
    {
        // Only load on plugin admin pages
        if (!$this->isPluginPage($hook)) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'wpwps-admin',
            WPWPS_ASSETS_URL . 'admin/css/admin.css',
            [],
            WPWPS_VERSION
        );
        
        // Admin JS
        wp_enqueue_script(
            'wpwps-admin',
            WPWPS_ASSETS_URL . 'admin/js/admin.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
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
                'syncing' => __('Synchronizing...', 'wp-woocommerce-printify-sync'),
                'sync_complete' => __('Synchronization complete!', 'wp-woocommerce-printify-sync'),
                'sync_error' => __('Synchronization failed: ', 'wp-woocommerce-printify-sync'),
                'confirm_sync' => __('Are you sure you want to synchronize all products? This may take some time.', 'wp-woocommerce-printify-sync'),
                'confirm_order_sync' => __('Are you sure you want to send this order to Printify?', 'wp-woocommerce-printify-sync'),
                'loading_shops' => __('Loading available shops...', 'wp-woocommerce-printify-sync'),
                'select_shop' => __('Please select a shop...', 'wp-woocommerce-printify-sync'),
                'shop_error' => __('Error loading shops: ', 'wp-woocommerce-printify-sync'),
                'order_sent' => __('Order sent to Printify!', 'wp-woocommerce-printify-sync'),
                'order_error' => __('Error sending order: ', 'wp-woocommerce-printify-sync'),
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