<?php
/**
 * Core Helper class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */
 
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class CoreHelper {
    /**
     * @var CoreHelper Instance of this class.
     */
    private static $instance = null;
    
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
        // Add basic admin menu
        add_action('admin_menu', [$this, 'addAdminMenu']);
        
        // Add admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Add plugin action links
        add_filter('plugin_action_links_' . WPWPRINTIFYSYNC_PLUGIN_BASENAME, 
            [$this, 'addActionLinks']);
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
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-settings',
            [$this, 'displaySettings']
        );
    }
    
    /**
     * Display dashboard page
     */
    public function displayDashboard() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="notice notice-info">
                <p><?php _e('Welcome to WP WooCommerce Printify Sync! Configure your settings to start syncing products.', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
            
            <div class="card">
                <h2><?php _e('Plugin Status', 'wp-woocommerce-printify-sync'); ?></h2>
                <table class="widefat">
                    <tbody>
                        <tr>
                            <th><?php _e('Version', 'wp-woocommerce-printify-sync'); ?></th>
                            <td><?php echo WPWPRINTIFYSYNC_VERSION; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('API Mode', 'wp-woocommerce-printify-sync'); ?></th>
                            <td><?php echo ucfirst(get_option('wpwprintifysync_api_mode', 'production')); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('API Configuration', 'wp-woocommerce-printify-sync'); ?></th>
                            <td>
                                <?php 
                                $api_key = get_option('wpwprintifysync_printify_api_key', '');
                                echo empty($api_key) ? 
                                    '<span class="dashicons dashicons-no" style="color:red;"></span> ' . __('Not configured', 'wp-woocommerce-printify-sync') : 
                                    '<span class="dashicons dashicons-yes" style="color:green;"></span> ' . __('Configured', 'wp-woocommerce-printify-sync'); 
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display settings page
     */
    public function displaySettings() {
        if (isset($_POST['wpwprintifysync_settings_submit']) && check_admin_referer('wpwprintifysync_settings')) {
            // Save API mode
            if (isset($_POST['wpwprintifysync_api_mode'])) {
                update_option('wpwprintifysync_api_mode', sanitize_text_field($_POST['wpwprintifysync_api_mode']));
            }
            
            // Save API key (simplified, without encryption for now)
            if (isset($_POST['wpwprintifysync_printify_api_key'])) {
                update_option('wpwprintifysync_printify_api_key', sanitize_text_field($_POST['wpwprintifysync_printify_api_key']));
            }
            
            // Save log level
            if (isset($_POST['wpwprintifysync_log_level'])) {
                update_option('wpwprintifysync_log_level', sanitize_text_field($_POST['wpwprintifysync_log_level']));
            }
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'wp-woocommerce-printify-sync') . '</p></div>';
        }
        
        $api_mode = get_option('wpwprintifysync_api_mode', 'production');
        $api_key = get_option('wpwprintifysync_printify_api_key', '');
        $log_level = get_option('wpwprintifysync_log_level', 'info');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('wpwprintifysync_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('API Mode', 'wp-woocommerce-printify-sync'); ?></th>
                        <td>
                            <select name="wpwprintifysync_api_mode">
                                <option value="production" <?php selected($api_mode, 'production'); ?>><?php _e('Production', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="development" <?php selected($api_mode, 'development'); ?>><?php _e('Development', 'wp-woocommerce-printify-sync'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Printify API Key', 'wp-woocommerce-printify-sync'); ?></th>
                        <td>
                            <input type="password" name="wpwprintifysync_printify_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Log Level', 'wp-woocommerce-printify-sync'); ?></th>
                        <td>
                            <select name="wpwprintifysync_log_level">
                                <option value="debug" <?php selected($log_level, 'debug'); ?>><?php _e('Debug', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="info" <?php selected($log_level, 'info'); ?>><?php _e('Info', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="warning" <?php selected($log_level, 'warning'); ?>><?php _e('Warning', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="error" <?php selected($log_level, 'error'); ?>><?php _e('Error', 'wp-woocommerce-printify-sync'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="wpwprintifysync_settings_submit" class="button button-primary" value="<?php _e('Save Settings', 'wp-woocommerce-printify-sync'); ?>" />
                </p>
            </form>
        </div>
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
                WPWPRINTIFYSYNC_VERSION
            );
            
            wp_enqueue_script(
                'wpwprintifysync-admin',
                WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                WPWPRINTIFYSYNC_VERSION,
                true
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
        ];
        
        return array_merge($plugin_links, $links);
    }
}