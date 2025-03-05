<?php
/**
 * Settings page for the plugin
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiHelper;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\LogHelper;

class Settings {
    private static $instance = null;
    private $timestamp = '2025-03-05 18:59:36';
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
        // Register settings page
        add_action('admin_menu', [$this, 'registerSettingsPage']);
        
        // Register settings
        add_action('admin_init', [$this, 'registerSettings']);
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_wp-woocommerce-printify-sync/wp-woocommerce-printify-sync.php', [$this, 'addSettingsLink']);
    }
    
    /**
     * Register settings page
     */
    public function registerSettingsPage() {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-settings',
            [$this, 'renderSettingsPage'],
            'dashicons-update',
            58
        );
        
        add_submenu_page(
            'wpwprintifysync-settings',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-settings',
            [$this, 'renderSettingsPage']
        );
        
        add_submenu_page(
            'wpwprintifysync-settings',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-products',
            [$this, 'renderProductsPage']
        );
        
        add_submenu_page(
            'wpwprintifysync-settings',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-orders',
            [$this, 'renderOrdersPage']
        );
        
        add_submenu_page(
            'wpwprintifysync-settings',
            __('Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-logs',
            [$this, 'renderLogsPage']
        );
    }
    
    /**
     * Register settings
     */
    public function registerSettings() {
        // API settings section
        add_settings_section(
            'wpwprintifysync_api_settings',
            __('API Settings', 'wp-woocommerce-printify-sync'),
            null,
            'wpwprintifysync-settings'
        );
        
        register_setting('wpwprintifysync-settings', 'wpwprintifysync_api_mode');
        register_setting('wpwprintifysync-settings', 'wpwprintifysync_printify_api_key');
        
        add_settings_field(
            'wpwprintifysync_api_mode',
            __('API Mode', 'wp-woocommerce-printify-sync'),
            [$this, 'renderApiModeField'],
            'wpwprintifysync-settings',
            'wpwprintifysync_api_settings'
        );
        
        add_settings_field(
            'wpwprintifysync_printify_api_key',
            __('Printify API Key', 'wp-woocommerce-printify-sync'),
            [$this, 'renderApiKeyField'],
            'wpwprintifysync-settings',
            'wpwprintifysync_api_settings'
        );
        
        // Sync settings section
        add_settings_section(
            'wpwprintifysync_sync_settings',
            __('Synchronization Settings', 'wp-woocommerce-printify-sync'),
            null,
            'wpwprintifysync-settings'
        );
        
        register_setting('wpwprintifysync-settings', 'wpwprintifysync_shop_id');
        register_setting('wpwprintifysync-settings', 'wpwprintifysync_batch_size');
        register_setting('wpwprintifysync-settings', 'wpwprintifysync_product_deletion');
        register_setting('wpwprintifysync-settings', 'wpwprintifysync_auto_sync');
        register_setting('wpwprintifysync-settings', 'wpwprintifysync_sync_frequency');
        
        add_settings_field(
            'wpwprintifysync_shop_id',
            __('Printify Shop ID', 'wp-woocommerce-printify-sync'),
            [$this, 'renderShopIdField'],
            'wpwprintifysync-settings',
            'wpwprintifysync_sync_settings'
        );
        
        add_settings_field(
            'wpwprintifysync_batch_size',
            __('Batch Size', 'wp-woocommerce-printify-sync'),
            [$this, 'renderBatchSizeField'],
            'wpwprintifysync-settings',
            'wpwprintifysync_sync_settings'
        );
        
        add_settings_field(
            'wpwprintifysync_product_deletion',
            __('Product Deletion Action', 'wp-woocommerce-printify-sync'),
            [$this, 'renderProductDeletionField'],
            'wpwprintifysync-settings',
            'wpwprintifysync_sync_settings'
        );
        
        add_settings_field(
            'wpwprintifysync_auto_sync',
            __('Auto Synchronization', 'wp-woocommerce-printify-sync'),
            [$this, 'renderAutoSyncField'],
            'wpwprintifysync-settings',
            'wpwprintifysync_sync_settings'
        );
        
        add_settings_field(
            'wpwprintifysync_sync_frequency',
            __('Sync Frequency', 'wp-woocommerce-printify-sync'),
            [$this, 'renderSyncFrequencyField'],
            'wpwprintifysync-settings',
            'wpwprintifysync_sync_settings'
        );
        
        // Advanced settings section
        add_settings_section(
            'wpwprintifysync_advanced_settings',
            __('Advanced Settings', 'wp-woocommerce-printify-sync'),
            null,
            'wpwprintifysync-settings'
        );
        
        register_setting('wpwprintifysync-settings', 'wpwprintifysync_log_level');
        register_setting('wpwprintifysync-settings', 'wpwprintifysync_log_retention_days');
        register_setting('wpwprintifysync-settings', 'wpwprintifysync_delete_data_on_uninstall');
        
        add_settings_field(
            'wpwprintifysync_log_level',
            __('Log Level', 'wp-woocommerce-printify-sync'),
            [$this, 'renderLogLevelField'],
            'wpwprintifysync-settings',
            'wpwprintifysync_advanced_settings'
        );
        
        add_settings_field(
            'wpwprintifysync_log_retention_days',
            __('Log Retention (Days)', 'wp-woocommerce-printify-sync'),
            [$this, 'renderLogRetentionField'],
            'wpwprintifysync-settings',
            'wpwprintifysync_advanced_settings'
        );
        
        add_settings_field(
            'wpwprintifysync_delete_data_on_uninstall',
            __('Delete Data on Uninstall', 'wp-woocommerce-printify-sync'),
            [$this, 'renderDeleteDataField'],
            'wpwprintifysync-settings',
            'wpwprintifysync_advanced_settings'
        );
    }
    
    /**
     * Add settings link to plugins page
     */
    public function addSettingsLink($links) {
        $settings_link = '<a href="admin.php?page=wpwprintifysync-settings">' . __('Settings', 'wp-woocommerce-printify-sync') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Render settings page
     */
    public function renderSettingsPage() {
        if (isset($_POST['wpwprintifysync_verify_api'])) {
            $this->verifyApiConnection();
        }
        
        if (isset($_POST['wpwprintifysync_reset_webhook'])) {
            $this->resetWebhook();
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
            
            <div class="wpwprintifysync-status-box">
                <h3><?php echo esc_html__('Connection Status', 'wp-woocommerce-printify-sync'); ?></h3>
                
                <p>
                    <strong><?php echo esc_html__('API Status:', 'wp-woocommerce-printify-sync'); ?></strong>
                    <?php $this->displayConnectionStatus(); ?>
                </p>
                
                <p>
                    <strong><?php echo esc_html__('Webhook Status:', 'wp-woocommerce-printify-sync'); ?></strong>
                    <?php $this->displayWebhookStatus(); ?>
                </p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('wpwprintifysync_verify_api', 'wpwprintifysync_verify_api_nonce'); ?>
                    <input type="submit" name="wpwprintifysync_verify_api" class="button button-secondary" value="<?php echo esc_attr__('Verify API Connection', 'wp-woocommerce-printify-sync'); ?>">
                
                    <?php wp_nonce_field('wpwprintifysync_reset_webhook', 'wpwprintifysync_reset_webhook_nonce'); ?>
                    <input type="submit" name="wpwprintifysync_reset_webhook" class="button button-secondary" value="<?php echo esc_attr__('Reset Webhook', 'wp-woocommerce-printify-sync'); ?>">
                </form>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wpwprintifysync-settings');
                do_settings_sections('wpwprintifysync-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render products page
     */
    public function renderProductsPage() {
        include_once(WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/products-page.php');
    }
    
    /**
     * Render orders page
     */
    public function renderOrdersPage() {
        include_once(WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/orders-page.php');
    }
    
    /**
     * Render logs page
     */
    public function renderLogsPage() {
        include_once(WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/logs-page.php');
    }
    
    /**
     * Display connection status
     */
    private function displayConnectionStatus() {
        $api_key = get_option('wpwprintifysync_printify_api_key');
        
        if (empty($api_key)) {
            echo '<span class="wpwprintifysync-status error">' . esc_html__('Not configured', 'wp-woocommerce-printify-sync') . '</span>';
            return;
        }
        
        $status = get_transient('wpwprintifysync_api_status');
        
        if ($status === false) {
            $response = ApiHelper::getInstance()->sendPrintifyRequest('shops.json');
            $status = $response['success'] ? 'connected' : 'error';
            set_transient('wpwprintifysync_api_status', $status, HOUR_IN_SECONDS);
        }
        
        if ($status === 'connected') {
            echo '<span class="wpwprintifysync-status success">' . esc_html__('Connected', 'wp-woocommerce-printify-sync') . '</span>';
        } else {
            echo '<span class="wpwprintifysync-status error">' . esc_html__('Error', 'wp-woocommerce-printify-sync') . '</span>';
        }
    }
    
    /**
     * Display webhook status
     */
    private function displayWebhookStatus() {
        $webhook_id = get_option('wpwprintifysync_webhook_id');
        
        if (empty($webhook_id)) {
            echo '<span class="wpwprintifysync-status warning">' . esc_html__('Not configured', 'wp-woocommerce-printify-sync') . '</span>';
            return;
        }
        
        $status = get_transient('wpwprintifysync_webhook_status');
        
        if ($status === false) {
            $response = ApiHelper::getInstance()->sendPrintifyRequest('webhooks.json');
            
            if ($response['success']) {
                $webhooks = $response['body'];
                $found = false;
                
                foreach ($webhooks as $webhook) {
                    if ($webhook['id'] == $webhook_id) {
                        $found = true;
                        $status = $webhook['enabled'] ? 'active' : 'inactive';
                        break;
                    }
                }
                
                if (!$found) {
                    $status = 'missing';
                }
            } else {
                $status = 'error';
            }
            
            set_transient('wpwprintifysync_webhook_status', $status, HOUR_IN_SECONDS);
        }
        
        switch ($status) {
            case 'active':
                echo '<span class="wpwprintifysync-status success">' . esc_html__('Active', 'wp-woocommerce-printify-sync') . '</span>';
                break;
            case 'inactive':
                echo '<span class="wpwprintifysync-status warning">' . esc_html__('Inactive', 'wp-woocommerce-printify-sync') . '</span>';
                break;
            case 'missing':
                echo '<span class="wpwprintifysync-status error">' . esc_html__('Missing', 'wp-woocommerce-printify-sync') . '</span>';
                break;
            default:
                echo '<span class="wpwprintifysync-status error">' . esc_html__('Error', 'wp-woocommerce-printify-sync') . '</span>';
                break;
        }
    }
    
    /**
     * Verify API connection
     */
    private function verifyApiConnection() {
        // Check nonce
        if (!isset($_POST['wpwprintifysync_verify_api_nonce']) || !wp_verify_nonce($_POST['wpwprintifysync_verify_api_nonce'], 'wpwprintifysync_verify_api')) {
            add_settings_error('wpwprintifysync-settings', 'wpwprintifysync-api-nonce', __('Security check failed.', 'wp-woocommerce-printify-sync'), 'error');