<?php
/**
 * Core Plugin Class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 * @version 1.2.5
 * @date 2025-03-03 13:58:43
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Class Plugin
 * Main plugin functionality
 */
class Plugin {
    /**
     * Register plugin functionality
     * 
     * @return void
     */
    public static function register(): void {
        // Plugin activation/deactivation hooks
        register_activation_hook(PRINTIFY_SYNC_FILE, [self::class, 'activate']);
        register_deactivation_hook(PRINTIFY_SYNC_FILE, [self::class, 'deactivate']);
        
        // Plugin action links
        add_filter('plugin_action_links_' . PRINTIFY_SYNC_BASENAME, [self::class, 'addActionLinks']);
        
        // Plugin row meta
        add_filter('plugin_row_meta', [self::class, 'addPluginRowMeta'], 10, 2);
        
        // Debug logging
        self::debugLog('Plugin core functionality loaded');
    }
    
    /**
     * Plugin activation
     * 
     * @return void
     */
    public static function activate(): void {
        // Create necessary database tables
        self::createDatabaseTables();
        
        // Set default settings
        self::setDefaultSettings();
        
        // Schedule cron jobs
        self::scheduleCronJobs();
        
        // Clear any cached data
        self::clearCache();
        
        self::debugLog('Plugin activated');
    }
    
    /**
     * Plugin deactivation
     * 
     * @return void
     */
    public static function deactivate(): void {
        // Clear scheduled cron jobs
        self::clearScheduledCronJobs();
        
        self::debugLog('Plugin deactivated');
    }
    
    /**
     * Add plugin action links
     * 
     * @param array $links Existing links
     * @return array Modified links
     */
    public static function addActionLinks(array $links): array {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=printify-sync-dashboard') . '">' . __('Dashboard', 'wp-woocommerce-printify-sync') . '</a>',
            '<a href="' . admin_url('admin.php?page=printify-sync-settings') . '">' . __('Settings', 'wp-woocommerce-printify-sync') . '</a>'
        ];
        
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Add plugin row meta
     * 
     * @param array $links Existing links
     * @param string $file Current plugin file
     * @return array Modified links
     */
    public static function addPluginRowMeta(array $links, string $file): array {
        if (PRINTIFY_SYNC_BASENAME === $file) {
            $row_meta = [
                'docs' => '<a href="https://github.com/ApolloWeb/wp-woocommerce-printify-sync/wiki" target="_blank">' . __('Documentation', 'wp-woocommerce-printify-sync') . '</a>',
                'support' => '<a href="https://github.com/ApolloWeb/wp-woocommerce-printify-sync/issues" target="_blank">' . __('Support', 'wp-woocommerce-printify-sync') . '</a>'
            ];
            
            return array_merge($links, $row_meta);
        }
        
        return $links;
    }
    
    /**
     * Create database tables
     * 
     * @return void
     */
    private static function createDatabaseTables(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Sync log table
        $table_name = $wpdb->prefix . 'printify_sync_logs';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context text,
            created_at datetime NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        self::debugLog('Database tables created');
    }
    
    /**
     * Set default settings
     * 
     * @return void
     */
    private static function setDefaultSettings(): void {
        // Only set defaults if they don't exist
        if (!get_option('printify_sync_settings')) {
            $default_settings = [
                'api_key' => '',
                'shop_id' => '',
                'sync_interval' => 'hourly',
                'auto_sync' => true,
                'debug_mode' => true
            ];
            
            update_option('printify_sync_settings', $default_settings);
            self::debugLog('Default settings created');
        }
    }
    
    /**
     * Schedule cron jobs
     * 
     * @return void
     */
    private static function scheduleCronJobs(): void {
        if (!wp_next_scheduled('printify_sync_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'printify_sync_daily_cleanup');
        }
        
        if (!wp_next_scheduled('printify_sync_hourly_products')) {
            wp_schedule_event(time(), 'hourly', 'printify_sync_hourly_products');
        }
    }
    
    /**
     * Clear scheduled cron jobs
     * 
     * @return void
     */
    private static function clearScheduledCronJobs(): void {
        wp_clear_scheduled_hook('printify_sync_daily_cleanup');
        wp_clear_scheduled_hook('printify_sync_hourly_products');
    }
    
    /**
     * Clear cache
     * 
     * @return void
     */
    private static function clearCache(): void {
        // Clear plugin-specific transients
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%printify_sync_cache%'");
    }
    
    /**
     * Debug logging
     * 
     * @param string $message Message to log
     * @return void
     */
    private static function debugLog(string $message): void {
        if (defined('PRINTIFY_SYNC_DEBUG') && PRINTIFY_SYNC_DEBUG) {
            error_log('PrintifySync: ' . $message);
        }
    }
}