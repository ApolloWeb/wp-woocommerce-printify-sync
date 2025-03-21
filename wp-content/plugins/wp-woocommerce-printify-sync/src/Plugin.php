<?php
/**
 * Main plugin class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Dashboard;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Products;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Orders;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Settings;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\Shipping;
use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Products\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderSync;
use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderStatuses;
use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderStatusFactory;
use ApolloWeb\WPWooCommercePrintifySync\Orders\PrintifyStatusMapper;
use ApolloWeb\WPWooCommercePrintifySync\Webhooks\WebhookHandler;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Container;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\TemplateRenderer;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin class.
 */
class Plugin {
    /**
     * Container.
     *
     * @var Container
     */
    private $container;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->container = new Container();
        
        $this->registerServices();
        $this->checkDependencies();
        $this->initHooks();
    }

    /**
     * Register services.
     *
     * @return void
     */
    private function registerServices() {
        // Helper services.
        $this->container->register('logger', function () {
            return new Logger();
        });
        
        $this->container->register('template', function () {
            return new TemplateRenderer();
        });
        
        // API services.
        $this->container->register('printify_api', function () {
            return new PrintifyAPI($this->container->get('logger'));
        });
        
        // Admin pages.
        $this->container->register('admin_page_dashboard', function () {
            return new Dashboard(
                $this->container->get('printify_api'),
                $this->container->get('template'),
                $this->container->get('logger')
            );
        });
        
        $this->container->register('admin_page_products', function () {
            return new Products(
                $this->container->get('printify_api'),
                $this->container->get('template'),
                $this->container->get('logger')
            );
        });
        
        $this->container->register('admin_page_orders', function () {
            return new Orders(
                $this->container->get('printify_api'),
                $this->container->get('template'),
                $this->container->get('logger')
            );
        });
        
        $this->container->register('admin_page_settings', function () {
            return new Settings(
                $this->container->get('printify_api'),
                $this->container->get('template'),
                $this->container->get('logger')
            );
        });
        
        $this->container->register('admin_page_shipping', function () {
            return new Shipping(
                $this->container->get('printify_api'),
                $this->container->get('template'),
                $this->container->get('logger')
            );
        });
        
        // Admin menu.
        $this->container->register('admin_menu', function () {
            return new AdminMenu([
                'dashboard' => $this->container->get('admin_page_dashboard'),
                'products'  => $this->container->get('admin_page_products'),
                'orders'    => $this->container->get('admin_page_orders'),
                'settings'  => $this->container->get('admin_page_settings'),
                'shipping'  => $this->container->get('admin_page_shipping'),
            ]);
        });
        
        // Product services.
        $this->container->register('product_sync', function () {
            return new ProductSync(
                $this->container->get('printify_api'),
                $this->container->get('logger')
            );
        });

        // Order status services
        $this->container->register('order_status_factory', function () {
            return new OrderStatusFactory();
        });
        
        $this->container->register('printify_status_mapper', function () {
            return new PrintifyStatusMapper();
        });
        
        $this->container->register('order_statuses', function () {
            return new OrderStatuses(
                $this->container->get('order_status_factory'),
                $this->container->get('printify_status_mapper')
            );
        });

        // Order services
        $this->container->register('order_sync', function () {
            return new OrderSync(
                $this->container->get('printify_api'),
                $this->container->get('logger'),
                $this->container->get('order_statuses')
            );
        });

        // Webhook services.
        $this->container->register('webhook_handler', function () {
            return new WebhookHandler(
                $this->container->get('product_sync'),
                $this->container->get('order_sync'),
                $this->container->get('logger')
            );
        });
    }

    /**
     * Check dependencies.
     *
     * @return void
     */
    private function checkDependencies() {
        add_action('admin_init', function () {
            if (!class_exists('WooCommerce')) {
                add_action('admin_notices', function () {
                    ?>
                    <div class="notice notice-error">
                        <p><?php esc_html_e('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                    <?php
                });
            }
        });
    }

    /**
     * Initialize hooks.
     *
     * @return void
     */
    private function initHooks() {
        add_action('plugins_loaded', [$this, 'loadTextdomain']);
        
        // Initialize admin menu.
        $this->container->get('admin_menu')->init();
        
        // Initialize webhook handler.
        $this->container->get('webhook_handler')->init();
        
        // Initialize product sync.
        $this->container->get('product_sync')->init();
        
        // Initialize order statuses.
        $this->container->get('order_statuses')->init();
        
        // Initialize order sync.
        $this->container->get('order_sync')->init();
    }

    /**
     * Load textdomain.
     *
     * @return void
     */
    public function loadTextdomain() {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(WPWPS_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Plugin activation.
     *
     * @return void
     */
    public static function activate() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(WPWPS_PLUGIN_BASENAME);
            wp_die(
                esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.', 'wp-woocommerce-printify-sync'),
                esc_html__('Plugin Activation Error', 'wp-woocommerce-printify-sync'),
                ['back_link' => true]
            );
        }

        // Create logs directory
        wp_mkdir_p(WPWPS_PLUGIN_DIR . 'logs');

        // Create necessary database tables
        self::createTables();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('wpwps_product_sync')) {
            wp_schedule_event(time(), 'twicedaily', 'wpwps_product_sync');
        }
    }

    /**
     * Create custom database tables.
     *
     * @return void
     */
    private static function createTables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create API log table
        $table_name = $wpdb->prefix . 'wpwps_api_logs';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            endpoint varchar(255) NOT NULL,
            request_data longtext,
            response_data longtext,
            status_code int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Plugin deactivation.
     *
     * @return void
     */
    public static function deactivate() {
        // Remove scheduled cron jobs
        wp_clear_scheduled_hook('wpwps_product_sync');
        
        // Remove all scheduled actions from our groups
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('', [], 'wpwps_product_import');
            as_unschedule_all_actions('', [], 'wpwps_order_sync');
        }
    }

    /**
     * Plugin uninstall.
     *
     * @return void
     */
    public static function uninstall() {
        // Drop custom tables
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwps_api_logs';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // Delete plugin options
        delete_option('wpwps_settings');
        
        // Delete plugin meta data
        $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_printify_%'");
    }
}
