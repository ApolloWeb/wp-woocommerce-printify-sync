<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Sync products from Printify to WooCommerce.
 * Version: 1.0.0
 * Author: ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

if (!defined('WPINC')) {
    die;
}

// Define plugin paths
define('WPWPS_PLUGIN_FILE', __FILE__);
define('WPWPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION', '1.0.0');

// Load the autoloader
require_once WPWPS_PLUGIN_DIR . '/src/Core/Autoloader.php';

// Load debug tools when in debug mode
if (defined('WP_DEBUG') && WP_DEBUG) {
    require_once WPWPS_PLUGIN_DIR . 'includes/debug-tools.php';
}

// Initialize autoloader with correct base directory
$autoloader = new Core\Autoloader(
    'ApolloWeb\\WPWooCommercePrintifySync',
    WPWPS_PLUGIN_DIR
);
$autoloader->register();

// Direct asset loader fallback
require_once WPWPS_PLUGIN_DIR . 'direct-asset-loader.php';

// Initialize plugin
function init() {
    try {
        // Core services
        $container = new Core\ServiceContainer();
        
        // Register base services
        $container->set('loader', new Core\Loader());
        $container->set('template_engine', new Core\TemplateEngine());
        
        // Register API service
        $container->set('printify_api', function() {
            return new API\PrintifyAPI(
                get_option('wpwps_printify_api_key', ''),
                get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1')
            );
        });

        // Register WooCommerce services
        $customOrderStatuses = new WooCommerce\CustomOrderStatuses();
        $container->set('custom_order_statuses', $customOrderStatuses);
        $container->set('product_importer', new WooCommerce\ProductImporter());
        
        // Register AJAX handler
        $container->set('ajax_handler', new Ajax\AjaxHandler($container));

        // Register plugin instance
        $container->set('plugin', function($container) {
            return new Plugin(
                $container->get('loader'),
                $container->get('template_engine'),
                $container
            );
        });

        // Register WooCommerce hooks
        add_action('init', [$customOrderStatuses, 'registerOrderStatuses']);
        add_filter('wc_order_statuses', [$customOrderStatuses, 'addOrderStatusesToWooCommerce']);

        // Register AJAX handlers first - before other services
        add_action('wp_ajax_printify_sync', function() use ($container) {
            check_ajax_referer('wpwps_nonce', 'nonce');
            $handler = $container->get('ajax_handler');
            $handler->handleAjax();
        });
        
        add_action('wp_ajax_nopriv_printify_sync', function() {
            wp_send_json_error(['message' => 'Unauthorized access']);
        });

        // Run the plugin
        $container->get('plugin')->run();

    } catch (\Exception $e) {
        error_log('WP WooCommerce Printify Sync initialization error: ' . $e->getMessage());
    }
}

// Initialize plugin on WordPress init
add_action('plugins_loaded', __NAMESPACE__ . '\\init');

// Add AJAX nonce to JavaScript
add_action('admin_enqueue_scripts', function() {
    wp_localize_script('wpwps-common', 'wpwps_data', [
        'nonce' => wp_create_nonce('wpwps_nonce'),
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}, 999);

// Add emergency fix for button click issues
add_action('admin_footer', function() {
    // Only run on our plugin pages
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'wpwps-') === false) {
        return;
    }
    
    echo <<<HTML
    <script>
    jQuery(document).ready(function($) {
        console.log('Emergency button fix loaded');
        
        // Fix for pagination
        $(document).on('click', '.page-link', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Pagination clicked!');
            
            var page = $(this).data('page');
            if (page && !isNaN(page)) {
                console.log('Fetching page:', page);
                
                // Call the fetchProducts function or reload with page parameter
                if (typeof fetchProducts === 'function') {
                    fetchProducts(parseInt(page), false);
                } else {
                    // Direct AJAX call if function isn't accessible
                    $.ajax({
                        url: wpwps_data.ajax_url,
                        type: 'GET',
                        data: {
                            action: 'printify_sync',
                            action_type: 'fetch_printify_products',
                            nonce: wpwps_data.nonce,
                            page: page,
                            per_page: 10,
                            refresh_cache: false
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            }
                        }
                    });
                }
            }
        });
        
        // Fix for fetch products button
        $('#fetch-products').on('click', function(e) {
            console.log('Fetch products button clicked');
            $.ajax({
                url: wpwps_data.ajax_url,
                type: 'GET',
                data: {
                    action: 'printify_sync',
                    action_type: 'fetch_printify_products',
                    nonce: wpwps_data.nonce,
                    page: 1,
                    per_page: 10,
                    refresh_cache: true
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        });
    });
    </script>
    HTML;
});
