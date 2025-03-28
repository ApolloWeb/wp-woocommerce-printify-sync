<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\BaseServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\SettingsPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\SyncPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\LogsPage;

class AdminServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     * 
     * @return void
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerAdminPages']);
        add_action('admin_init', [$this, 'registerSettings']);
        
        // Register admin notices
        add_action('admin_notices', [$this, 'displayAdminNotices']);
        
        // Add plugin action links
        add_filter('plugin_action_links_' . WPWPS_BASENAME, [$this, 'addPluginActionLinks']);
    }
    
    /**
     * Register admin pages.
     * 
     * @return void
     */
    public function registerAdminPages(): void
    {
        // Main menu page
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettingsPage'],
            'dashicons-update',
            56
        );
        
        // Settings submenu page
        add_submenu_page(
            'wpwps-settings',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettingsPage']
        );
        
        // Sync submenu page
        add_submenu_page(
            'wpwps-settings',
            __('Product Sync', 'wp-woocommerce-printify-sync'),
            __('Product Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-product-sync',
            [$this, 'renderSyncPage']
        );
        
        // Logs submenu page
        add_submenu_page(
            'wpwps-settings',
            __('Sync Logs', 'wp-woocommerce-printify-sync'),
            __('Sync Logs', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-logs',
            [$this, 'renderLogsPage']
        );
    }
    
    /**
     * Register settings.
     * 
     * @return void
     */
    public function registerSettings(): void
    {
        register_setting('wpwps_settings', 'wpwps_settings');
        
        // API Settings section
        add_settings_section(
            'wpwps_api_settings',
            __('API Settings', 'wp-woocommerce-printify-sync'),
            [$this, 'apiSettingsCallback'],
            'wpwps_settings'
        );
        
        add_settings_field(
            'wpwps_api_key',
            __('API Key', 'wp-woocommerce-printify-sync'),
            [$this, 'apiKeyCallback'],
            'wpwps_settings',
            'wpwps_api_settings'
        );
        
        add_settings_field(
            'wpwps_shop_id',
            __('Shop ID', 'wp-woocommerce-printify-sync'),
            [$this, 'shopIdCallback'],
            'wpwps_settings',
            'wpwps_api_settings'
        );
        
        // Sync Settings section
        add_settings_section(
            'wpwps_sync_settings',
            __('Sync Settings', 'wp-woocommerce-printify-sync'),
            [$this, 'syncSettingsCallback'],
            'wpwps_settings'
        );
        
        add_settings_field(
            'wpwps_auto_sync',
            __('Auto Sync', 'wp-woocommerce-printify-sync'),
            [$this, 'autoSyncCallback'],
            'wpwps_settings',
            'wpwps_sync_settings'
        );
        
        add_settings_field(
            'wpwps_sync_interval',
            __('Sync Interval', 'wp-woocommerce-printify-sync'),
            [$this, 'syncIntervalCallback'],
            'wpwps_settings',
            'wpwps_sync_settings'
        );
        
        add_settings_field(
            'wpwps_sync_options',
            __('Sync Options', 'wp-woocommerce-printify-sync'),
            [$this, 'syncOptionsCallback'],
            'wpwps_settings',
            'wpwps_sync_settings'
        );
    }
    
    /**
     * Display admin notices.
     * 
     * @return void
     */
    public function displayAdminNotices(): void
    {
        $settings = get_option('wpwps_settings');
        
        // Check if API key and Shop ID are set
        if (empty($settings['api_key']) || empty($settings['shop_id'])) {
            // Only show on plugin pages
            $screen = get_current_screen();
            if (strpos($screen->id, 'wpwps') !== false) {
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p><?php _e('Please configure your Printify API Key and Shop ID to start syncing products.', 'wp-woocommerce-printify-sync'); ?> 
                    <a href="<?php echo admin_url('admin.php?page=wpwps-settings'); ?>"><?php _e('Go to Settings', 'wp-woocommerce-printify-sync'); ?></a></p>
                </div>
                <?php
            }
        }
    }
    
    /**
     * Add action links to plugin page.
     * 
     * @param array $links
     * @return array
     */
    public function addPluginActionLinks(array $links): array
    {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=wpwps-settings') . '">' . __('Settings', 'wp-woocommerce-printify-sync') . '</a>',
        ];
        
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Render the settings page.
     * 
     * @return void
     */
    public function renderSettingsPage(): void
    {
        $settingsPage = new SettingsPage();
        $settingsPage->render();
    }
    
    /**
     * Render the sync page.
     * 
     * @return void
     */
    public function renderSyncPage(): void
    {
        $syncPage = new SyncPage();
        $syncPage->render();
    }
    
    /**
     * Render the logs page.
     * 
     * @return void
     */
    public function renderLogsPage(): void
    {
        $logsPage = new LogsPage();
        $logsPage->render();
    }
    
    /**
     * API Settings section callback.
     * 
     * @return void
     */
    public function apiSettingsCallback(): void
    {
        echo '<p>' . __('Enter your Printify API credentials below. You can find your API key in the Printify dashboard under Settings > API.', 'wp-woocommerce-printify-sync') . '</p>';
    }
    
    /**
     * API Key field callback.
     * 
     * @return void
     */
    public function apiKeyCallback(): void
    {
        $settings = get_option('wpwps_settings');
        $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
        
        echo '<input type="password" id="wpwps_api_key" name="wpwps_settings[api_key]" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your Printify API key.', 'wp-woocommerce-printify-sync') . '</p>';
    }
    
    /**
     * Shop ID field callback.
     * 
     * @return void
     */
    public function shopIdCallback(): void
    {
        $settings = get_option('wpwps_settings');
        $shop_id = isset($settings['shop_id']) ? $settings['shop_id'] : '';
        
        echo '<input type="text" id="wpwps_shop_id" name="wpwps_settings[shop_id]" value="' . esc_attr($shop_id) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your Printify Shop ID.', 'wp-woocommerce-printify-sync') . '</p>';
    }
    
    /**
     * Sync Settings section callback.
     * 
     * @return void
     */
    public function syncSettingsCallback(): void
    {
        echo '<p>' . __('Configure how the plugin synchronizes with Printify.', 'wp-woocommerce-printify-sync') . '</p>';
    }
    
    /**
     * Auto Sync field callback.
     * 
     * @return void
     */
    public function autoSyncCallback(): void
    {
        $settings = get_option('wpwps_settings');
        $auto_sync = isset($settings['auto_sync']) ? (bool)$settings['auto_sync'] : false;
        
        echo '<label for="wpwps_auto_sync">';
        echo '<input type="checkbox" id="wpwps_auto_sync" name="wpwps_settings[auto_sync]" value="1" ' . checked(1, $auto_sync, false) . ' />';
        echo ' ' . __('Enable automatic synchronization with Printify', 'wp-woocommerce-printify-sync');
        echo '</label>';
        echo '<p class="description">' . __('When enabled, the plugin will automatically sync products based on the selected interval.', 'wp-woocommerce-printify-sync') . '</p>';
    }
    
    /**
     * Sync Interval field callback.
     * 
     * @return void
     */
    public function syncIntervalCallback(): void
    {
        $settings = get_option('wpwps_settings');
        $sync_interval = isset($settings['sync_interval']) ? $settings['sync_interval'] : 'hourly';
        
        $intervals = [
            'hourly' => __('Hourly', 'wp-woocommerce-printify-sync'),
            'twicedaily' => __('Twice Daily', 'wp-woocommerce-printify-sync'),
            'daily' => __('Daily', 'wp-woocommerce-printify-sync'),
        ];
        
        echo '<select id="wpwps_sync_interval" name="wpwps_settings[sync_interval]">';
        foreach ($intervals as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($sync_interval, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select how often the plugin should synchronize with Printify.', 'wp-woocommerce-printify-sync') . '</p>';
    }
    
    /**
     * Sync Options field callback.
     * 
     * @return void
     */
    public function syncOptionsCallback(): void
    {
        $settings = get_option('wpwps_settings');
        $sync_products = isset($settings['sync_products']) ? (bool)$settings['sync_products'] : true;
        $sync_inventory = isset($settings['sync_inventory']) ? (bool)$settings['sync_inventory'] : true;
        $sync_orders = isset($settings['sync_orders']) ? (bool)$settings['sync_orders'] : true;
        
        echo '<label for="wpwps_sync_products">';
        echo '<input type="checkbox" id="wpwps_sync_products" name="wpwps_settings[sync_products]" value="1" ' . checked(1, $sync_products, false) . ' />';
        echo ' ' . __('Sync Products', 'wp-woocommerce-printify-sync');
        echo '</label><br>';
        
        echo '<label for="wpwps_sync_inventory">';
        echo '<input type="checkbox" id="wpwps_sync_inventory" name="wpwps_settings[sync_inventory]" value="1" ' . checked(1, $sync_inventory, false) . ' />';
        echo ' ' . __('Sync Inventory', 'wp-woocommerce-printify-sync');
        echo '</label><br>';
        
        echo '<label for="wpwps_sync_orders">';
        echo '<input type="checkbox" id="wpwps_sync_orders" name="wpwps_settings[sync_orders]" value="1" ' . checked(1, $sync_orders, false) . ' />';
        echo ' ' . __('Sync Orders', 'wp-woocommerce-printify-sync');
        echo '</label>';
        
        echo '<p class="description">' . __('Select what data should be synchronized between WooCommerce and Printify.', 'wp-woocommerce-printify-sync') . '</p>';
    }
}