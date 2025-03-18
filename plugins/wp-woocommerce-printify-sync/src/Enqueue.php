<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

/**
 * Handle script and style enqueuing for the plugin
 */
class Enqueue {
    /**
     * Plugin version for cache busting
     */
    private $version;
    
    /**
     * Base URL for assets
     */
    private $assets_url;
    
    /**
     * Current admin page
     */
    private $current_page = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->version = defined('PRINTIFY_SYNC_VERSION') ? PRINTIFY_SYNC_VERSION : '1.0.0';
        $this->assets_url = defined('PRINTIFY_SYNC_URL') ? PRINTIFY_SYNC_URL . 'assets/' : plugin_dir_url(dirname(__FILE__)) . 'assets/';
        
        // Register hooks
        add_action('admin_enqueue_scripts', [$this, 'registerAdminAssets']);
        add_action('wp_enqueue_scripts', [$this, 'registerFrontendAssets']);
        
        // Add admin menu icon CSS
        add_action('admin_head', [$this, 'addAdminMenuIcon']);
    }
    
    /**
     * Register all admin assets
     */
    public function registerAdminAssets($hook) {
        // Store the current page hook
        $this->current_page = $hook;
        
        // Register Bootstrap CSS
        wp_register_style(
            'printify-sync-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
            [],
            '5.2.3'
        );
        
        // Register Font Awesome
        wp_register_style(
            'printify-sync-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );
        
        // Register Animate.css for animations
        wp_register_style(
            'printify-sync-animate',
            'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css',
            [],
            '4.1.1'
        );
        
        // Register admin styles
        wp_register_style(
            'printify-sync-admin-core',
            $this->assets_url . 'css/admin-core.css',
            ['printify-sync-bootstrap', 'printify-sync-fontawesome', 'printify-sync-animate'],
            $this->version
        );
        
        wp_register_style(
            'printify-sync-product-import',
            $this->assets_url . 'css/product-import.css',
            ['printify-sync-admin-core'],
            $this->version
        );
        
        wp_register_style(
            'printify-sync-settings',
            $this->assets_url . 'css/settings.css',
            ['printify-sync-admin-core'],
            $this->version
        );
        
        wp_register_style(
            'printify-sync-dashboard',
            $this->assets_url . 'css/dashboard.css',
            ['printify-sync-admin-core'],
            $this->version
        );
        
        // Register Bootstrap JS (with Popper.js included)
        wp_register_script(
            'printify-sync-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
            [],
            '5.2.3',
            true
        );
        
        // Register UI enhancements script
        wp_register_script(
            'printify-sync-ui-enhancements',
            $this->assets_url . 'js/admin-ui-enhancements.js',
            ['jquery', 'printify-sync-bootstrap'],
            $this->version,
            true
        );
        
        // Register admin scripts
        wp_register_script(
            'printify-sync-admin-core',
            $this->assets_url . 'js/admin-core.js',
            ['jquery', 'printify-sync-bootstrap', 'printify-sync-ui-enhancements'],
            $this->version,
            true
        );
        
        wp_register_script(
            'printify-sync-product-import',
            $this->assets_url . 'js/product-import.js',
            ['printify-sync-admin-core'],
            $this->version,
            true
        );
        
        wp_register_script(
            'printify-sync-settings',
            $this->assets_url . 'js/settings.js',
            ['printify-sync-admin-core'],
            $this->version,
            true
        );
        
        wp_register_script(
            'printify-sync-dashboard',
            $this->assets_url . 'js/dashboard.js',
            ['printify-sync-admin-core', 'jquery'],
            $this->version,
            true
        );
        
        // Localize script data
        wp_localize_script('printify-sync-admin-core', 'printifySync', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('printify_sync_nonce'),
            'i18n'    => [
                'webhookUrlCopied' => __('Webhook URL copied to clipboard', 'wp-woocommerce-printify-sync')
            ]
        ]);
        
        // Enqueue based on current page
        $this->loadAdminPageAssets();
    }
    
    /**
     * Register all frontend assets
     */
    public function registerFrontendAssets() {
        wp_register_style(
            'printify-sync-frontend',
            $this->assets_url . 'css/frontend.css',
            [],
            $this->version
        );
        
        wp_register_script(
            'printify-sync-frontend',
            $this->assets_url . 'js/frontend.js',
            ['jquery'],
            $this->version,
            true
        );
        
        // Conditionally load based on current page
        $this->loadFrontendPageAssets();
    }
    
    /**
     * Load assets based on the current admin page
     */
    private function loadAdminPageAssets() {
        // Core assets for all plugin pages
        if ($this->isPluginPage()) {
            wp_enqueue_style('printify-sync-admin-core');
            wp_enqueue_script('printify-sync-admin-core');
        }

        // Dashboard specific assets
        if ($this->isDashboardPage()) {
            wp_enqueue_style('printify-sync-dashboard');
            wp_enqueue_script('printify-sync-dashboard');
        }

        // Product import specific assets
        if ($this->isProductImportPage()) {
            wp_enqueue_style('printify-sync-product-import');
            wp_enqueue_script('printify-sync-product-import');
        }

        // Settings page specific assets
        if ($this->isPluginSettingsPage()) {
            wp_enqueue_style('printify-sync-settings');
            wp_enqueue_script('printify-sync-settings');
        }
    }
    
    /**
     * Load assets based on the current frontend page
     */
    private function loadFrontendPageAssets() {
        // Only load on single product pages that are Printify products
        if (is_product() && $this->isPrintifyProduct()) {
            wp_enqueue_style('printify-sync-frontend');
            wp_enqueue_script('printify-sync-frontend');
        }
    }
    
    /**
     * Add custom admin menu icon with Font Awesome
     */
    public function addAdminMenuIcon() {
        echo '<style>
            #toplevel_page_printify-sync .wp-menu-image::before {
                font-family: "Font Awesome 6 Free";
                content: "\f553"; /* fa-tshirt */
                font-weight: 900;
            }
            .printify-card {
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
                margin-bottom: 1.5rem;
            }
            .printify-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            }
            .printify-header {
                background: linear-gradient(135deg, #6f42c1 0%, #6610f2 100%);
                color: #fff;
                padding: 1rem;
                border-radius: 8px 8px 0 0;
            }
            .printify-heading {
                font-weight: 600;
                margin-bottom: 0;
                display: flex;
                align-items: center;
            }
            .printify-heading i {
                margin-right: 10px;
            }
            .printify-btn {
                border-radius: 50px;
                padding: 0.5rem 1.5rem;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            .printify-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }
            .printify-stats-card {
                text-align: center;
                padding: 1.5rem;
            }
            .printify-stats-icon {
                font-size: 2rem;
                margin-bottom: 1rem;
                color: #6f42c1;
            }
            .printify-stats-number {
                font-size: 2rem;
                font-weight: 700;
                color: #343a40;
                margin-bottom: 0.5rem;
            }
            .printify-stats-label {
                color: #6c757d;
                font-size: 0.9rem;
            }
            .printify-table {
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .printify-table thead {
                background-color: #f8f9fa;
            }
            .printify-loader {
                text-align: center;
                padding: 2rem;
            }
        </style>';
    }
    
    /**
     * Check if current page is any plugin page
     */
    private function isPluginPage() {
        return strpos($this->current_page, 'printify-sync') !== false;
    }
    
    /**
     * Check if current page is the plugin dashboard
     */
    private function isDashboardPage() {
        return $this->current_page === 'toplevel_page_printify-sync';
    }
    
    /**
     * Check if current page is the settings page
     */
    private function isPluginSettingsPage() {
        return $this->current_page === 'printify-sync_page_printify-sync-settings';
    }
    
    /**
     * Check if current page is the product import page
     */
    private function isProductImportPage() {
        return $this->current_page === 'woocommerce_page_printify-sync-import';
    }
    
    /**
     * Check if current page is a WooCommerce product edit page
     */
    private function isProductEditPage() {
        global $pagenow, $post_type;
        return $pagenow === 'post.php' && $post_type === 'product';
    }
    
    /**
     * Check if current product is a Printify product
     */
    private function isPrintifyProduct() {
        global $product;
        
        if (!$product) {
            return false;
        }
        
        // Check if the product has Printify meta
        return !empty(get_post_meta($product->get_id(), '_printify_product_id', true));
    }
}

new Enqueue();