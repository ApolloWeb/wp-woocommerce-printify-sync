<?php
/**
 * Core Helper class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */
 
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class CoreHelper {
    /**
     * @var CoreHelper Instance of this class.
     */
    private static $instance = null;
    
    /**
     * @var string Plugin version
     */
    const VERSION = '1.0.0';
    
    /**
     * @var string Last updated timestamp
     */
    const LAST_UPDATED = '2025-03-05 18:11:46';
    
    /**
     * @var string Author username
     */
    const AUTHOR = 'ApolloWeb';
    
    /**
     * Get single instance of this class
     *
     * @return CoreHelper
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load dependencies
        $this->loadDependencies();
        
        // Register hooks
        $this->registerHooks();
        
        // Add basic admin menu
        add_action('admin_menu', [$this, 'addAdminMenu']);
        
        // Add admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Add plugin action links
        add_filter('plugin_action_links_' . WPWPRINTIFYSYNC_PLUGIN_BASENAME, 
            [$this, 'addActionLinks']);
    }
    
    /**
     * Load dependencies
     */
    private function loadDependencies() {
        // Load helper classes
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/SettingsHelper.php';
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/ApiHelper.php';
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/ProductHelper.php';
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/OrderHelper.php';
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/CurrencyHelper.php';
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/LogHelper.php';
    }
    
    /**
     * Register hooks
     */
    private function registerHooks() {
        // Register custom post types
        add_action('init', [$this, 'registerPostTypes']);
        
        // Register custom order statuses
        add_action('init', [$this, 'registerOrderStatuses']);
        
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        
        // Register scheduled events
        add_action('init', [$this, 'registerScheduledEvents']);
    }
    
    /**
     * Register custom post types
     */
    public function registerPostTypes() {
        // Ticket post type
        register_post_type('wpws_ticket', [
            'labels' => [
                'name' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'singular_name' => __('Support Ticket', 'wp-woocommerce-printify-sync'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'custom-fields'],
            'capability_type' => 'post',
            'hierarchical' => false,
        ]);
    }
    
    /**
     * Register custom order statuses
     */
    public function registerOrderStatuses() {
        register_post_status('wc-printify-processing', [
            'label' => _x('Printify Processing', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Processing <span class="count">(%s)</span>', 'Printify Processing <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-printify-printed', [
            'label' => _x('Printify Printed', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Printed <span class="count">(%s)</span>', 'Printify Printed <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-printify-shipped', [
            'label' => _x('Printify Shipped', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Shipped <span class="count">(%s)</span>', 'Printify Shipped <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-reprint-requested', [
            'label' => _x('Reprint Requested', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Reprint Requested <span class="count">(%s)</span>', 'Reprint Requested <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-refund-requested', [
            'label' => _x('Refund Requested', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Refund Requested <span class="count">(%s)</span>', 'Refund Requested <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * Register REST API endpoints
     */
    public function registerRestRoutes() {
        register_rest_route('wpwprintifysync/v1', '/webhook/printify', [
            'methods' => 'POST',
            'callback' => [ApiHelper::getInstance(), 'handleWebhook'],
            'permission_callback' => [ApiHelper::getInstance(), 'validateWebhookRequest'],
        ]);
    }
    
    /**
     * Register scheduled events
     */
    public function registerScheduledEvents() {
        if (!wp_next_scheduled('wpwprintifysync_update_currency_rates')) {
            wp_schedule_event(time(), 'every_4_hours', 'wpwprintifysync_update_currency_rates');
        }
        
        if (!wp_next_scheduled('wpwprintifysync_sync_stock')) {
            wp_schedule_event(time(), 'twicedaily', 'wpwprintifysync_sync_stock');
        }
        
        if (!wp_next_scheduled('wpwprintifysync_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'wpwprintifysync_cleanup_logs');
        }
        
        if (!wp_next_scheduled('wpwprintifysync_poll_emails')) {
            wp_schedule_event(time(), 'hourly', 'wpwprintifysync_poll_emails');
        }
        
        // Register action handlers
        add_action('wpwprintifysync_update_currency_rates', [CurrencyHelper::getInstance(), 'updateRates']);
        add_action('wpwprintifysync_sync_stock', [ProductHelper::getInstance(), 'syncStock']);
        add_action('wpwprintifysync_cleanup_logs', [LogHelper::getInstance(), 'cleanupLogs']);
        add_action('wpwprintifysync_poll_emails', [LogHelper::getInstance(), 'pollEmails']);
    }
    
    /**
     * Add admin menu
     */
    public function addAdminMenu() {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync',
            [$this, 'displayDashboard'],
            'dashicons-store',
            56
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync',
            [$this, 'displayDashboard']
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-products',
            [ProductHelper::getInstance(), 'displayProductsPage']
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-orders',
            [OrderHelper::getInstance(), 'displayOrdersPage']
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Shipping', 'wp-woocommerce-printify-sync'),
            __('Shipping', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-shipping',
            [$this, 'displayShippingPage']
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Currency', 'wp-woocommerce-printify-sync'),
            __('Currency', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-currency',
            [CurrencyHelper::getInstance(), 'displayCurrencyPage']
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-logs',
            [LogHelper::getInstance(), 'displayLogsPage']
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-settings',
            [SettingsHelper::getInstance(), 'displaySettingsPage']
        );
    }
    
    /**
     * Display dashboard page
     */
    public function displayDashboard() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wpwprintifysync-admin-header">
                <div class="wpwprintifysync-version-info">
                    <p>
                        <?php printf(
                            __('Version: %s | Last Updated: %s | Author: %s', 'wp-woocommerce-printify-sync'),
                            self::VERSION,
                            self::LAST_UPDATED,
                            self::AUTHOR
                        ); ?>
                    </p>
                </div>
            </div>
            
            <div class="wpwprintifysync-flex-container">
                <div class="wpwprintifysync-card wpwprintifysync-flex-item">
                    <h2><?php _e('Plugin Status', 'wp-woocommerce-printify-sync'); ?></h2>
                    <table class="widefat">
                        <tbody>
                            <tr>
                                <th><?php _e('API Mode', 'wp-woocommerce-printify-sync'); ?></th>
                                <td>
                                    <?php 
                                    $api_mode = get_option('wpwprintifysync_api_mode', 'production');
                                    echo '<span class="wpwprintifysync-status-badge ' . ($api_mode === 'production' ? 'success' : 'warning') . '">' . 
                                        ucfirst($api_mode) . 
                                    '</span>'; 
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('API Configuration', 'wp-woocommerce-printify-sync'); ?></th>
                                <td>
                                    <?php 
                                    $api_key = get_option('wpwprintifysync_printify_api_key', '');
                                    echo empty($api_key) ? 
                                        '<span class="wpwprintifysync-status-badge error">' . __('Not configured', 'wp-woocommerce-printify-sync') . '</span>' : 
                                        '<span class="wpwprintifysync-status-badge success">' . __('Configured', 'wp-woocommerce-printify-sync') . '</span>'; 
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Webhooks', 'wp-woocommerce-printify-sync'); ?></th>
                                <td>
                                    <?php 
                                    $webhook_secret = get_option('wpwprintifysync_webhook_secret', '');
                                    echo empty($webhook_secret) ? 
                                        '<span class="wpwprintifysync-status-badge error">' . __('Not configured', 'wp-woocommerce-printify-sync') . '</span>' : 
                                        '<span class="wpwprintifysync-status-badge success">' . __('Configured', 'wp-woocommerce-printify-sync') . '</span>'; 
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="wpwprintifysync-card wpwprintifysync-flex-item">
                    <h2><?php _e('Sync Summary', 'wp-woocommerce-printify-sync'); ?></h2>
                    <table class="widefat">
                        <tbody>
                            <tr>
                                <th><?php _e('Products', 'wp-woocommerce-printify-sync'); ?></th>
                                <td>
                                    <?php 
                                    $synced_products = get_option('wpwprintifysync_synced_products', 0);
                                    echo $synced_products . ' ' . __('synced', 'wp-woocommerce-printify-sync');
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Orders', 'wp-woocommerce-printify-sync'); ?></th>
                                <td>
                                    <?php 
                                    $synced_orders = get_option('wpwprintifysync_synced_orders', 0);
                                    echo $synced_orders . ' ' . __('synced', 'wp-woocommerce-printify-sync');
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Last Sync', 'wp-woocommerce-printify-sync'); ?></th>
                                <td>
                                    <?php 
                                    $last_sync = get_option('wpwprintifysync_last_sync', '');
                                    echo empty($last_sync) ? __('Never', 'wp-woocommerce-printify-sync') : $last_sync;
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="wpwprintifysync-flex-container">
                <div class="wpwprintifysync-card wpwprintifysync-flex-item">
                    <h2><?php _e('Quick Actions', 'wp-woocommerce-printify-sync'); ?></h2>
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=wpwprintifysync-products'); ?>" class="button button-primary">
                            <?php _e('Import Products', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=wpwprintifysync-orders'); ?>" class="button">
                            <?php _e('Manage Orders', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=wpwprintifysync-settings'); ?>" class="button">
                            <?php _e('Configure Settings', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </p>
                </div>
                
                <div class="wpwprintifysync-card wpwprintifysync-flex-item">
                    <h2><?php _e('Support', 'wp-woocommerce-printify-sync'); ?></h2>
                    <p><?php _e('Need help? Contact our support team:', 'wp-woocommerce-printify-sync'); ?></p>
                    <p>
                        <a href="mailto:hello@apollo-web.co.uk" class="button">
                            <?php _e('Email Support', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        <a href="https://app.slack.com/client/T08FNMY2UUC/C08FLP5Q8FL" target="_blank" class="button">
                            <?php _e('Join Slack Community', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display shipping page
     */
    public function displayShippingPage() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wpwprintifysync-card">
                <h2><?php _e('Shipping Profiles', 'wp-woocommerce-printify-sync'); ?></h2>
                <p><?php _e('Map Printify shipping profiles to WooCommerce shipping zones.', 'wp-woocommerce-printify-sync'); ?></p>
                
                <?php 
                // Check if API is configured
                $api_key = get_option('wpwprintifysync_printify_api_key', '');
                if (empty($api_key)) {
                    echo '<div class="notice notice-warning"><p>' . 
                        __('Please configure your Printify API key in the settings before using shipping features.', 'wp-woocommerce-printify-sync') .
                    '</p></div>';
                } else {
                    // Display shipping profile mapping UI
                    $this->displayShippingProfileMapping();
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display shipping profile mapping UI
     */
    private function displayShippingProfileMapping() {
        // This would typically fetch shipping profiles from Printify
        // and WooCommerce shipping zones for mapping
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Printify Shipping Profile', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('WooCommerce Shipping Zone', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="3">
                        <?php _e('Click "Refresh Profiles" to fetch shipping profiles from Printify.', 'wp-woocommerce-printify-sync'); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p class="submit">
            <button class="button button-primary" id="wpwprintifysync-refresh-shipping">
                <?php _e('Refresh Profiles', 'wp-woocommerce-printify-sync'); ?>
            </button>
            <button class="button" id="wpwprintifysync-save-shipping-mapping">
                <?php _e('Save Mapping', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </p>
        <?php
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets($hook) {
        if (strpos($hook, 'wpwprintifysync') !== false) {
            wp_enqueue_style(
                'wpwprintifysync-admin', 
                WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/css/admin.css',
                [], 
                self::VERSION
            );
            
            wp_enqueue_script(
                'wpwprintifysync-admin',
                WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                self::VERSION,
                true
            );
            
            // Add localized script data
            wp_localize_script(
                'wpwprintifysync-admin',
                'wpwprintifysync',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wpwprintifysync-admin'),
                    'i18n' => [
                        'loading' => __('Loading...', 'wp-woocommerce-printify-sync'),
                        'error' => __('Error', 'wp-woocommerce-printify-sync'),
                        'success' => __('Success', 'wp-woocommerce-printify-sync'),
                        'confirm' => __('Are you sure?', 'wp-woocommerce-printify-sync'),
                    ],
                ]
            );
        }
    }
    
    /**
     * Add action links to plugins page
     *
     * @param array $links Existing links
     * @return array Modified links
     */
    public function addActionLinks($links) {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=wpwprintifysync-settings') . '">' . __('Settings', 'wp-woocommerce-printify-sync') . '</a>',
            '<a href="' . admin_url('admin.php?page=wpwprintifysync') . '">' . __('Dashboard', 'wp-woocommerce-printify-sync') . '</a>',
        ];
        
        return array_merge($plugin_links, $links);
    }
}