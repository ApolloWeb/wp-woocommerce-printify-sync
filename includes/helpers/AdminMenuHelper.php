<?php
/**
 * Admin Menu Helper class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class AdminMenuHelper {
    /**
     * Add admin menu
     */
    public static function addAdminMenu() {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync',
            [self::class, 'displayDashboard'],
            'dashicons-store',
            56
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync',
            [self::class, 'displayDashboard']
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-products',
            [ProductHelper::class, 'displayProductsPage']
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-orders',
            [OrderHelper::class, 'displayOrdersPage']
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Shipping', 'wp-woocommerce-printify-sync'),
            __('Shipping', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-shipping',
            [self::class, 'displayShippingPage']
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Currency', 'wp-woocommerce-printify-sync'),
            __('Currency', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-currency',
            [CurrencyHelper::class, 'displayCurrencyPage']
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-logs',
            [LogHelper::class, 'displayLogsPage']
        );
        
        add_submenu_page(
            'wpwprintifysync',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-settings',
            [SettingsHelper::class, 'displaySettingsPage']
        );
    }

    /**
     * Display dashboard page
     */
    public static function displayDashboard() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wpwprintifysync-admin-header">
                <div class="wpwprintifysync-version-info">
                    <p>
                        <?php printf(
                            __('Version: %s | Last Updated: %s | Author: %s', 'wp-woocommerce-printify-sync'),
                            CoreHelper::VERSION,
                            CoreHelper::LAST_UPDATED,
                            CoreHelper::AUTHOR
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
    public static function displayShippingPage() {
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
                    self::displayShippingProfileMapping();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display shipping profile mapping UI
     */
    private static function displayShippingProfileMapping() {
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
}