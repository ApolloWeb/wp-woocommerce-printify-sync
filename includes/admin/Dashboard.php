<?php
/**
 * Admin Dashboard Widgets
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class Dashboard {
    private static $instance = null;
    private $timestamp = '2025-03-05 19:02:30';
    private $user = 'ApolloWeb';
    
    /**
     * Get single instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Add dashboard widgets
        add_action('wp_dashboard_setup', [$this, 'addDashboardWidgets']);
        
        // Add admin notices
        add_action('admin_notices', [$this, 'displayAdminNotices']);
    }
    
    /**
     * Add dashboard widgets
     */
    public function addDashboardWidgets() {
        wp_add_dashboard_widget(
            'wpwprintifysync_stats_widget',
            __('Printify Sync Status', 'wp-woocommerce-printify-sync'),
            [$this, 'renderStatsWidget']
        );
        
        wp_add_dashboard_widget(
            'wpwprintifysync_recent_orders_widget',
            __('Printify Recent Orders', 'wp-woocommerce-printify-sync'),
            [$this, 'renderRecentOrdersWidget']
        );
    }
    
    /**
     * Render stats widget
     */
    public function renderStatsWidget() {
        $synced_products = get_option('wpwprintifysync_synced_products', 0);
        $synced_orders = get_option('wpwprintifysync_synced_orders', 0);
        $last_sync = get_option('wpwprintifysync_last_sync', '');
        $api_status = get_transient('wpwprintifysync_api_status') === 'connected';
        
        // Get pending orders that need to be sent to Printify
        $pending_orders = $this->getPendingOrders();
        
        // Recent errors from logs
        $recent_errors = $this->getRecentErrors();
        
        ?>
        <div class="wpwprintifysync-dashboard-stats">
            <div class="wpwprintifysync-stat-item">
                <span class="wpwprintifysync-stat-label"><?php _e('Synced Products:', 'wp-woocommerce-printify-sync'); ?></span>
                <span class="wpwprintifysync-stat-value"><?php echo esc_html($synced_products); ?></span>
            </div>
            
            <div class="wpwprintifysync-stat-item">
                <span class="wpwprintifysync-stat-label"><?php _e('Synced Orders:', 'wp-woocommerce-printify-sync'); ?></span>
                <span class="wpwprintifysync-stat-value"><?php echo esc_html($synced_orders); ?></span>
            </div>
            
            <div class="wpwprintifysync-stat-item">
                <span class="wpwprintifysync-stat-label"><?php _e('Pending Orders:', 'wp-woocommerce-printify-sync'); ?></span>
                <span class="wpwprintifysync-stat-value"><?php echo esc_html($pending_orders); ?></span>
            </div>
            
            <div class="wpwprintifysync-stat-item wpwprintifysync-stat-api">
                <span class="wpwprintifysync-stat-label"><?php _e('API Status:', 'wp-woocommerce-printify-sync'); ?></span>
                <span class="wpwprintifysync-status <?php echo $api_status ? 'success' : 'error'; ?>">
                    <?php echo $api_status ? esc_html__('Connected', 'wp-woocommerce-printify-sync') : esc_html__('Disconnected', 'wp-woocommerce-printify-sync'); ?>
                </span>
            </div>
            
            <?php if (!empty($last_sync)): ?>
                <div class="wpwprintifysync-stat-item wpwprintifysync-stat-last-sync">
                    <span class="wpwprintifysync-stat-label"><?php _e('Last Sync:', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="wpwprintifysync-stat-value"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync))); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="wpwprintifysync-dashboard-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-products')); ?>" class="button button-secondary">
                    <?php _e('Manage Products', 'wp-woocommerce-printify-sync'); ?>
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-orders')); ?>" class="button button-secondary">
                    <?php _e('Manage Orders', 'wp-woocommerce-printify-sync'); ?>
                </a>
            </div>
            
            <?php if (!empty($recent_errors)): ?>
                <div class="wpwprintifysync-recent-errors">
                    <h4><?php _e('Recent Errors:', 'wp-woocommerce-printify-sync'); ?></h4>
                    <ul>
                        <?php foreach ($recent_errors as $error): ?>
                            <li><?php echo esc_html($error->message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-logs')); ?>">
                        <?php _e('View All Logs', 'wp-woocommerce-printify-sync'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render recent orders widget
     */
    public function renderRecentOrdersWidget() {
        // Get recent Printify orders
        $recent_orders = $this->getRecentPrintifyOrders();
        
        if (empty($recent_orders)) {
            echo '<p>' . esc_html__('No recent Printify orders found.', 'wp-woocommerce-printify-sync') . '</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Order', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Printify ID', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->order_id . '&action=edit')); ?>">
                                #<?php echo esc_html($order->order_id); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($order->printify_order_id); ?></td>
                        <td>
                            <span class="wpwprintifysync-status <?php echo esc_attr($order->status); ?>">
                                <?php echo esc_html(ucfirst($order->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($order->updated_at))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="wpwprintifysync-view-all">
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-orders')); ?>">
                <?php _e('View All Orders', 'wp-woocommerce-printify-sync'); ?>
            </a>
        </p>
        <?php
    }
    
    /**
     * Display admin notices
     */
    public function displayAdminNotices() {
        // Check if API key is configured
        $api_key = get_option('wpwprintifysync_printify_api_key');
        if (empty($api_key) && $this->isPluginPage()) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php 
                    echo sprintf(
                        __('Please <a href="%s">configure your Printify API key</a> to start syncing products and orders.', 'wp-woocommerce-printify-sync'), 
                        admin_url('admin.php?page=wpwprintifysync-settings')
                    ); 
                    ?>
                </p>
            </div>
            <?php
        }
        
        // Check for pending orders
        $pending_orders = $this->getPendingOrders();
        if ($pending_orders > 0 && $this->isPluginPage()) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <?php 
                    echo sprintf(
                        _n(
                            'You have %d order waiting to be sent to Printify. <a href="%s">View pending orders</a>.',
                            'You have %d orders waiting to be sent to Printify. <a href="%s">View pending orders</a>.',
                            $pending_orders,
                            'wp-woocommerce-printify-sync'
                        ),
                        $pending_orders,
                        admin_url('admin.php?page=wpwprintifysync-orders&status=pending')
                    ); 
                    ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Check if current page is a plugin page
     */
    private function isPluginPage() {
        $screen = get_current_screen();
        if (!$screen) return false;
        
        return strpos($screen->id, 'wpwprintifysync') !== false;
    }
    
    /**
     * Get pending orders count
     */
    private function getPendingOrders() {
        global $wpdb;
        
        // First check our custom table for performance
        $table_name = $wpdb->prefix . 'wpwprintifysync_order_mapping';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            return (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        }
        
        // Fall back to meta query if custom table doesn't exist
        $orders = wc_get_orders([
            'status' => ['processing'],
            'meta_query' => [
                [
                    'key' => '_printify_order_id',
                    'compare' => 'NOT EXISTS'
                ]
            ],
            'limit' => -1,
            'return' => 'ids',
        ]);
        
        return count($orders);
    }
    
    /**
     * Get recent errors from logs
     */
    private function getRecentErrors() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwprintifysync_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [];
        }
        
        return $wpdb->get_results(
            "SELECT message, created_at FROM $table_name 
            WHERE level = 'error' 
            ORDER BY created_at DESC 
            LIMIT 3"
        );
    }
    
    /**
     * Get recent Printify orders
     */
    private function getRecentPrintifyOrders() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwprintifysync_order_mapping';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [];
        }
        
        return $wpdb->get_results(
            "SELECT order_id, printify_order_id, status, updated_at 
            FROM $table_name 
            ORDER BY updated_at DESC 
            LIMIT 5"
        );
    }
}