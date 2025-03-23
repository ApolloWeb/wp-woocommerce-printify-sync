<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Core\Settings;
use ApolloWeb\WPWooCommercePrintifySync\Support\EmailQueue;

/**
 * Dashboard widgets for plugin statistics and information
 */
class DashboardWidgets {
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var Settings
     */
    private $settings;
    
    /**
     * @var EmailQueue
     */
    private $email_queue;
    
    /**
     * Constructor
     */
    public function __construct(Logger $logger, Settings $settings, EmailQueue $email_queue = null) {
        $this->logger = $logger;
        $this->settings = $settings;
        $this->email_queue = $email_queue;
    }
    
    /**
     * Initialize dashboard widgets
     */
    public function init(): void {
        add_action('wp_dashboard_setup', [$this, 'registerWidgets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_get_sync_stats', [$this, 'getSyncStatsAjax']);
        add_action('wp_ajax_wpwps_get_queue_stats', [$this, 'getQueueStatsAjax']);
    }
    
    /**
     * Register dashboard widgets
     */
    public function registerWidgets(): void {
        // Only show to users who can manage WooCommerce
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'wpwps_sync_stats_widget',
            __('Printify Sync Statistics', 'wp-woocommerce-printify-sync'),
            [$this, 'renderSyncStatsWidget']
        );
        
        wp_add_dashboard_widget(
            'wpwps_queue_widget',
            __('Printify Queue Status', 'wp-woocommerce-printify-sync'),
            [$this, 'renderQueueWidget']
        );
    }
    
    /**
     * Enqueue assets for dashboard widgets
     *
     * @param string $hook_suffix The current admin page
     */
    public function enqueueAssets(string $hook_suffix): void {
        if ($hook_suffix !== 'index.php') {
            return;
        }
        
        wp_enqueue_style(
            'wpwps-dashboard-widgets',
            WPPS_URL . 'assets/admin/css/dashboard-widgets.css',
            [],
            WPPS_VERSION
        );
        
        wp_enqueue_script(
            'wpwps-dashboard-widgets',
            WPPS_URL . 'assets/admin/js/dashboard-widgets.js',
            ['jquery', 'wp-util'],
            WPPS_VERSION,
            true
        );
        
        wp_localize_script('wpwps-dashboard-widgets', 'wpwpsDashboard', [
            'nonce' => wp_create_nonce('wpwps_admin'),
            'i18n' => [
                'loading' => __('Loading...', 'wp-woocommerce-printify-sync'),
                'error' => __('Error loading data', 'wp-woocommerce-printify-sync'),
                'refreshed' => __('Data refreshed', 'wp-woocommerce-printify-sync'),
                'noData' => __('No data available', 'wp-woocommerce-printify-sync')
            ]
        ]);
    }
    
    /**
     * Render sync statistics widget
     */
    public function renderSyncStatsWidget(): void {
        echo '<div class="wpwps-dashboard-widget wpwps-sync-stats">';
        echo '<div class="wpwps-widget-loading"><span class="spinner is-active"></span> ' . __('Loading sync statistics...', 'wp-woocommerce-printify-sync') . '</div>';
        echo '<div class="wpwps-widget-content" style="display:none;"></div>';
        echo '<div class="wpwps-widget-footer">';
        echo '<a href="' . admin_url('admin.php?page=wpwps-dashboard') . '" class="wpwps-widget-view-all">' . __('View Details', 'wp-woocommerce-printify-sync') . '</a>';
        echo '<button type="button" class="wpwps-refresh-widget button button-small"><span class="dashicons dashicons-update"></span></button>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render queue status widget
     */
    public function renderQueueWidget(): void {
        echo '<div class="wpwps-dashboard-widget wpwps-queue-stats">';
        echo '<div class="wpwps-widget-loading"><span class="spinner is-active"></span> ' . __('Loading queue data...', 'wp-woocommerce-printify-sync') . '</div>';
        echo '<div class="wpwps-widget-content" style="display:none;"></div>';
        echo '<div class="wpwps-widget-footer">';
        
        if ($this->email_queue) {
            echo '<button type="button" class="wpwps-process-queue button button-small">' . __('Process Queues', 'wp-woocommerce-printify-sync') . '</button>';
        }
        
        echo '<button type="button" class="wpwps-refresh-widget button button-small"><span class="dashicons dashicons-update"></span></button>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Get sync statistics via AJAX
     */
    public function getSyncStatsAjax(): void {
        check_ajax_referer('wpwps_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $stats = $this->getSyncStats();
        $html = $this->renderSyncStatsHtml($stats);
        
        wp_send_json_success(['html' => $html, 'stats' => $stats]);
    }
    
    /**
     * Get queue statistics via AJAX
     */
    public function getQueueStatsAjax(): void {
        check_ajax_referer('wpwps_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $stats = $this->getQueueStats();
        $html = $this->renderQueueStatsHtml($stats);
        
        wp_send_json_success(['html' => $html, 'stats' => $stats]);
    }
    
    /**
     * Get sync statistics
     *
     * @return array Sync statistics
     */
    private function getSyncStats(): array {
        // Product sync statistics
        $product_stats = [
            'total' => get_option('wpwps_product_sync_total', 0),
            'success' => get_option('wpwps_product_sync_success', 0),
            'failed' => get_option('wpwps_product_sync_failed', 0),
            'last_sync' => get_option('wpwps_product_sync_last', ''),
            'next_sync' => wp_next_scheduled('wpwps_sync_products')
        ];
        
        // Order sync statistics
        $order_stats = [
            'total' => get_option('wpwps_order_sync_total', 0),
            'success' => get_option('wpwps_order_sync_success', 0),
            'failed' => get_option('wpwps_order_sync_failed', 0),
            'last_sync' => get_option('wpwps_order_sync_last', ''),
            'next_sync' => wp_next_scheduled('wpwps_sync_orders')
        ];
        
        // Stock sync statistics
        $stock_stats = [
            'total' => get_option('wpwps_stock_sync_total', 0),
            'success' => get_option('wpwps_stock_sync_success', 0),
            'failed' => get_option('wpwps_stock_sync_failed', 0),
            'last_sync' => get_option('wpwps_stock_sync_last', ''),
            'next_sync' => wp_next_scheduled('wpwps_sync_stock')
        ];
        
        // API limits
        $api_stats = [
            'calls_today' => get_option('wpwps_api_calls_today', 0),
            'limit' => (int) $this->settings->get('api_daily_limit', 5000),
            'rate_limited' => get_option('wpwps_api_rate_limited', false),
            'reset_time' => $this->getApiRateLimitResetTime()
        ];
        
        return [
            'products' => $product_stats,
            'orders' => $order_stats,
            'stock' => $stock_stats,
            'api' => $api_stats,
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Get queue statistics
     *
     * @return array Queue statistics
     */
    private function getQueueStats(): array {
        global $wpdb;
        
        // Email queue stats
        $email_queue = [
            'pending' => 0,
            'processing' => 0,
            'failed' => 0,
            'sent' => 0,
            'next_process' => wp_next_scheduled('wpwps_process_email_queue')
        ];
        
        // Get email stats if the queue table exists
        $email_table = $wpdb->prefix . 'wpwps_email_queue';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$email_table}'") === $email_table) {
            $email_queue['pending'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$email_table} WHERE status = 'pending'");
            $email_queue['processing'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$email_table} WHERE status = 'processing'");
            $email_queue['failed'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$email_table} WHERE status = 'failed'");
            $email_queue['sent'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$email_table} WHERE status = 'sent' AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        }
        
        // Import queue stats
        $import_queue = [
            'pending' => get_option('wpwps_import_queue_pending', 0),
            'processing' => get_option('wpwps_import_queue_processing', 0),
            'completed' => get_option('wpwps_import_queue_completed', 0),
            'failed' => get_option('wpwps_import_queue_failed', 0),
            'next_process' => wp_next_scheduled('wpwps_process_import_queue')
        ];
        
        // API retry queue
        $retry_queue = [
            'count' => get_option('wpwps_api_retry_queue_count', 0),
            'next_retry' => get_option('wpwps_api_next_retry', 0)
        ];
        
        return [
            'email' => $email_queue,
            'import' => $import_queue,
            'retry' => $retry_queue,
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Get API rate limit reset time
     *
     * @return int Unix timestamp of next reset
     */
    private function getApiRateLimitResetTime(): int {
        $reset_time = get_option('wpwps_api_limit_reset', 0);
        
        if (empty($reset_time) || $reset_time < time()) {
            // Default to next day at midnight UTC
            $reset_time = strtotime('tomorrow midnight UTC');
            update_option('wpwps_api_limit_reset', $reset_time);
        }
        
        return $reset_time;
    }
    
    /**
     * Render sync stats HTML
     *
     * @param array $stats Sync statistics
     * @return string HTML output
     */
    private function renderSyncStatsHtml(array $stats): string {
        ob_start();
        ?>
        <div class="wpwps-widget-grid wpwps-sync-grid">
            <!-- API Stats -->
            <div class="wpwps-widget-card">
                <h3><?php _e('API Usage', 'wp-woocommerce-printify-sync'); ?></h3>
                <div class="wpwps-progress-container">
                    <?php 
                    $percentage = min(100, ($stats['api']['calls_today'] / max(1, $stats['api']['limit'])) * 100);
                    $status_class = $percentage > 90 ? 'danger' : ($percentage > 70 ? 'warning' : 'success');
                    ?>
                    <div class="wpwps-progress-bar <?php echo $status_class; ?>" style="width: <?php echo $percentage; ?>%"></div>
                </div>
                <div class="wpwps-widget-data">
                    <span><?php echo sprintf(__('%d of %d calls today', 'wp-woocommerce-printify-sync'), $stats['api']['calls_today'], $stats['api']['limit']); ?></span>
                    <?php if ($stats['api']['rate_limited']): ?>
                    <span class="wpwps-status-badge danger"><?php _e('Rate Limited', 'wp-woocommerce-printify-sync'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="wpwps-widget-meta">
                    <?php if ($stats['api']['reset_time']): ?>
                    <small><?php echo sprintf(__('Resets in: %s', 'wp-woocommerce-printify-sync'), human_time_diff(time(), $stats['api']['reset_time'])); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Sync -->
            <div class="wpwps-widget-card">
                <h3><?php _e('Product Sync', 'wp-woocommerce-printify-sync'); ?></h3>
                <div class="wpwps-widget-data">
                    <span class="wpwps-big-number"><?php echo $stats['products']['total']; ?></span>
                    <div class="wpwps-status-indicators">
                        <span class="wpwps-status-indicator success"><?php echo $stats['products']['success']; ?> <?php _e('Success', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="wpwps-status-indicator danger"><?php echo $stats['products']['failed']; ?> <?php _e('Failed', 'wp-woocommerce-printify-sync'); ?></span>
                    </div>
                </div>
                <div class="wpwps-widget-meta">
                    <?php if ($stats['products']['last_sync']): ?>
                    <small><?php echo sprintf(__('Last sync: %s ago', 'wp-woocommerce-printify-sync'), human_time_diff(strtotime($stats['products']['last_sync']), current_time('timestamp'))); ?></small>
                    <?php else: ?>
                    <small><?php _e('No previous sync', 'wp-woocommerce-printify-sync'); ?></small>
                    <?php endif; ?>
                    
                    <?php if ($stats['products']['next_sync']): ?>
                    <small><?php echo sprintf(__('Next sync: %s', 'wp-woocommerce-printify-sync'), human_time_diff(time(), $stats['products']['next_sync'])); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Stock Sync -->
            <div class="wpwps-widget-card">
                <h3><?php _e('Stock Sync', 'wp-woocommerce-printify-sync'); ?></h3>
                <div class="wpwps-widget-data">
                    <span class="wpwps-big-number"><?php echo $stats['stock']['total']; ?></span>
                    <div class="wpwps-status-indicators">
                        <span class="wpwps-status-indicator success"><?php echo $stats['stock']['success']; ?> <?php _e('Success', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="wpwps-status-indicator danger"><?php echo $stats['stock']['failed']; ?> <?php _e('Failed', 'wp-woocommerce-printify-sync'); ?></span>
                    </div>
                </div>
                <div class="wpwps-widget-meta">
                    <?php if ($stats['stock']['last_sync']): ?>
                    <small><?php echo sprintf(__('Last sync: %s ago', 'wp-woocommerce-printify-sync'), human_time_diff(strtotime($stats['stock']['last_sync']), current_time('timestamp'))); ?></small>
                    <?php else: ?>
                    <small><?php _e('No previous sync', 'wp-woocommerce-printify-sync'); ?></small>
                    <?php endif; ?>
                    
                    <?php if ($stats['stock']['next_sync']): ?>
                    <small><?php echo sprintf(__('Next sync: %s', 'wp-woocommerce-printify-sync'), human_time_diff(time(), $stats['stock']['next_sync'])); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Sync -->
            <div class="wpwps-widget-card">
                <h3><?php _e('Order Sync', 'wp-woocommerce-printify-sync'); ?></h3>
                <div class="wpwps-widget-data">
                    <span class="wpwps-big-number"><?php echo $stats['orders']['total']; ?></span>
                    <div class="wpwps-status-indicators">
                        <span class="wpwps-status-indicator success"><?php echo $stats['orders']['success']; ?> <?php _e('Success', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="wpwps-status-indicator danger"><?php echo $stats['orders']['failed']; ?> <?php _e('Failed', 'wp-woocommerce-printify-sync'); ?></span>
                    </div>
                </div>
                <div class="wpwps-widget-meta">
                    <?php if ($stats['orders']['last_sync']): ?>
                    <small><?php echo sprintf(__('Last sync: %s ago', 'wp-woocommerce-printify-sync'), human_time_diff(strtotime($stats['orders']['last_sync']), current_time('timestamp'))); ?></small>
                    <?php else: ?>
                    <small><?php _e('No previous sync', 'wp-woocommerce-printify-sync'); ?></small>
                    <?php endif; ?>
                    
                    <?php if ($stats['orders']['next_sync']): ?>
                    <small><?php echo sprintf(__('Next sync: %s', 'wp-woocommerce-printify-sync'), human_time_diff(time(), $stats['orders']['next_sync'])); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="wpwps-widget-refresh-time">
            <small><?php echo sprintf(__('Last updated: %s', 'wp-woocommerce-printify-sync'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($stats['timestamp']))); ?></small>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render queue stats HTML
     *
     * @param array $stats Queue statistics
     * @return string HTML output
     */
    private function renderQueueStatsHtml(array $stats): string {
        ob_start();
        ?>
        <div class="wpwps-widget-grid wpwps-queue-grid">
            <!-- Email Queue -->
            <div class="wpwps-widget-card">
                <h3><?php _e('Email Queue', 'wp-woocommerce-printify-sync'); ?></h3>
                <div class="wpwps-widget-data">
                    <div class="wpwps-status-blocks">
                        <div class="wpwps-status-block">
                            <span class="wpwps-status-number"><?php echo $stats['email']['pending']; ?></span>
                            <span class="wpwps-status-label"><?php _e('Pending', 'wp-woocommerce-printify-sync'); ?></span>
                        </div>
                        <div class="wpwps-status-block">
                            <span class="wpwps-status-number"><?php echo $stats['email']['processing']; ?></span>
                            <span class="wpwps-status-label"><?php _e('Processing', 'wp-woocommerce-printify-sync'); ?></span>
                        </div>
                        <div class="wpwps-status-block">
                            <span class="wpwps-status-number"><?php echo $stats['email']['sent']; ?></span>
                            <span class="wpwps-status-label"><?php _e('Sent (24h)', 'wp-woocommerce-printify-sync'); ?></span>
                        </div>
                        <div class="wpwps-status-block">
                            <span class="wpwps-status-number"><?php echo $stats['email']['failed']; ?></span>
                            <span class="wpwps-status-label"><?php _e('Failed', 'wp-woocommerce-printify-sync'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="wpwps-widget-meta">
                    <?php if ($stats['email']['next_process']): ?>
                    <small><?php echo sprintf(__('Next process: %s', 'wp-woocommerce-printify-sync'), human_time_diff(time(), $stats['email']['next_process'])); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Import Queue -->
            <div class="wpwps-widget-card">
                <h3><?php _e('Import Queue', 'wp-woocommerce-printify-sync'); ?></h3>
                <div class="wpwps-widget-data">
                    <div class="wpwps-status-blocks">
                        <div class="wpwps-status-block">
                            <span class="wpwps-status-number"><?php echo $stats['import']['pending']; ?></span>
                            <span class="wpwps-status-label"><?php _e('Pending', 'wp-woocommerce-printify-sync'); ?></span>
                        </div>
                        <div class="wpwps-status-block">
                            <span class="wpwps-status-number"><?php echo $stats['import']['processing']; ?></span>
                            <span class="wpwps-status-label"><?php _e('Processing', 'wp-woocommerce-printify-sync'); ?></span>
                        </div>
                        <div class="wpwps-status-block">
                            <span class="wpwps-status-number"><?php echo $stats['import']['completed']; ?></span>
                            <span class="wpwps-status-label"><?php _e('Completed', 'wp-woocommerce-printify-sync'); ?></span>
                        </div>
                        <div class="wpwps-status-block">
                            <span class="wpwps-status-number"><?php echo $stats['import']['failed']; ?></span>
                            <span class="wpwps-status-label"><?php _e('Failed', 'wp-woocommerce-printify-sync'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="wpwps-widget-meta">
                    <?php if ($stats['import']['next_process']): ?>
                    <small><?php echo sprintf(__('Next process: %s', 'wp-woocommerce-printify-sync'), human_time_diff(time(), $stats['import']['next_process'])); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- API Retry Queue -->
            <div class="wpwps-widget-card">
                <h3><?php _e('API Retry Queue', 'wp-woocommerce-printify-sync'); ?></h3>
                <div class="wpwps-widget-data">
                    <span class="wpwps-big-number"><?php echo $stats['retry']['count']; ?></span>
                    <span class="wpwps-label"><?php _e('Requests Queued', 'wp-woocommerce-printify-sync'); ?></span>
                </div>
                <div class="wpwps-widget-meta">
                    <?php if ($stats['retry']['next_retry'] && $stats['retry']['next_retry'] > time()): ?>
                    <small><?php echo sprintf(__('Next retry: %s', 'wp-woocommerce-printify-sync'), human_time_diff(time(), $stats['retry']['next_retry'])); ?></small>
                    <?php elseif ($stats['retry']['count'] > 0): ?>
                    <small><?php _e('Retry scheduled soon', 'wp-woocommerce-printify-sync'); ?></small>
                    <?php else: ?>
                    <small><?php _e('No retries pending', 'wp-woocommerce-printify-sync'); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="wpwps-widget-refresh-time">
            <small><?php echo sprintf(__('Last updated: %s', 'wp-woocommerce-printify-sync'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($stats['timestamp']))); ?></small>
        </div>
        <?php
        return ob_get_clean();
    }
}
