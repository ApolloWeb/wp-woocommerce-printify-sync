<?php
/**
 * Settings Helper class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */
 
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class SettingsHelper {
    /**
     * @var SettingsHelper Instance of this class.
     */
    private static $instance = null;
    
    /**
     * Get single instance of this class
     *
     * @return SettingsHelper
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
        // Register AJAX handlers
        add_action('wp_ajax_wpwprintifysync_test_api', [$this, 'ajaxTestApi']);
    }
    
    /**
     * Display settings page
     */
    public function displaySettingsPage() {
        if (isset($_POST['wpwprintifysync_settings_submit']) && check_admin_referer('wpwprintifysync_settings')) {
            $this->saveSettings();
        }
        
        // Get settings
        $api_mode = get_option('wpwprintifysync_api_mode', 'production');
        $api_key = get_option('wpwprintifysync_printify_api_key', '');
        $currency_api_key = get_option('wpwprintifysync_currency_api_key', '');
        $geolocation_api_key = get_option('wpwprintifysync_geolocation_api_key', '');
        $webhook_secret = get_option('wpwprintifysync_webhook_secret', '');
        $log_level = get_option('wpwprintifysync_log_level', 'info');
        $log_retention_days = get_option('wpwprintifysync_log_retention_days', 14);
        $batch_size = get_option('wpwprintifysync_batch_size', 20);
        $notification_email = get_option('wpwprintifysync_notification_email', get_option('admin_email'));
        
        // Display the settings form
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully.', 'wp-woocommerce-printify-sync'); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('wpwprintifysync_settings'); ?>
                
                <div class="wpwprintifysync-card">
                    <h2><?php _e('API Settings', 'wp-woocommerce-printify-sync'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('API Mode', 'wp-woocommerce-printify-sync'); ?></th>
                            <td>
                                <select name="wpwprintifysync_api_mode">
                                    <option value="production" <?php selected($api_mode, 'production'); ?>><?php _e('Production', 'wp-woocommerce-printify-sync'); ?></option>
                                    <option value="development" <?php selected($api_mode, 'development'); ?>><?php _e('Development', 'wp-woocommerce-printify-sync'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Select "Development" for testing with sandbox environment.', 'wp-woocommerce-printify-sync'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Printify API Key', 'wp-woocommerce-printify-sync'); ?></th>
                            <td>
                                <input 
                                    type="password" 
                                    name="wpwprintifysync_printify_api_key" 
                                    id="wpwprintifysync_printify_api_key" 
                                    value="<?php echo esc_attr($api_key); ?>" 
                                    class="regular-text" 
                                />
                                <button type="button" class="button wpwprintifysync-toggle-visibility" data-target="#wpwprintifysync_printify_api_key">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button type="button" class="button wpwprintifysync-test-api" data-api-type="printify">
                                    <?php _e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                                <p class="description">
                                    <?php _e('Enter your Printify API key from your Printify account.', 'wp-woocommerce-printify-sync'); ?>
                                </p>
                                <div id="wpwprintifysync_printify_api_test_result" class="wpwprintifysync-api-test-result"></div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Currency API Key', 'wp-woocommerce-printify-sync'); ?></th>
                            <td>
                                <input 
                                    type="password" 
                                    name="wpwprintifysync_currency_api_key" 
                                    id="wpwprintifysync_currency_api_key" 
                                    value="<?php echo esc_attr($currency_api_key); ?>" 
                                    class="regular-text" 
                                />
                                <button type="button" class="button wpwprintifysync-toggle-visibility" data-target="#wpwprintifysync_currency_api_key">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button type="button" class="button wpwprintifysync-test-api" data-api-type="currency">
                                    <?php _e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                                <p class="description">
                                    <?php _e('Enter your Currency API key for exchange rate conversions.', 'wp-woocommerce-printify-sync'); ?>
                                </p>
                                <div id="wpwprintifysync_currency_api_test_result" class="wpwprintifysync-api-test-result"></div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Geolocation API Key', 'wp-woocommerce-printify-sync'); ?></th>
                            <td>
                                <input 
                                    type="password" 
                                    name="wpwprintifysync_geolocation_api_key" 
                                    id="wpwprintifysync_geolocation_api_key" 
                                    value="<?php echo esc_attr($geolocation_api_key); ?>" 
                                    class="regular-text" 
                                />
                                <button type="button" class="button wpwprintifysync-toggle-visibility" data-target="#wpwprintifysync_geolocation_api_key">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button type="button" class="button wpwprintifysync-test-api" data-api-type="geolocation">
                                    <?php _e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                                <p class="description">
                                    <?php _e('Enter your Geolocation API key for customer location detection.', 'wp-woocommerce-printify-sync'); ?>
                                </p>
                                <div id="wpwprintifysync_geolocation_api_test_result" class="wpwprintifysync-api-test-result"></div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Webhook Secret', 'wp-woocommerce-printify-sync'); ?></th>
                            <td>
                                <input 
                                    type="password" 
                                    name="wpwprintifysync_webhook_secret" 
                                    id="wpwprintifysync_webhook_secret" 
                                    value="<?php echo esc_attr($webhook_secret); ?>" 
                                    class="regular-text" 
                                />
                                <button type="button" class="button wpwprintifysync-toggle-visibility" data-target="#wpwprintifysync_webhook_secret">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button type="button" class="button" id="wpwprintifysync-generate-webhook-secret">
                                    <?php _e('Generate Secret', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                                <p class="description">
                                    <?php _e('Secret key for validating webhook requests from Printify.', 'wp-woocommerce-printify-sync'); ?>
                                </p>
                                <p>
                                    <strong><?php _e('Webhook URL:', 'wp-woocommerce-printify-sync'); ?></strong>
                                    <code><?php echo rest_url('wpwprintifysync/v1/webhook/printify'); ?></code>
                                    <button type="button" class="button" data-clipboard-text="<?php echo esc_url(rest_url('wpwprintifysync/v1/webhook/printify')); ?>">
                                        <span class="dashicons dashicons-clipboard"></span>
                                    </button>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="wpwprintifysync-card">
                    <h2><?php _e('Sync Settings', 'wp-woocommerce-printify-sync'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Batch Size', 'wp-woocommerce-printify-sync'); ?></th>
                            <td>
                                <input 
                                    type="number" 
                                    name="wpwprintifysync_batch_size" 
                                    value="<?php echo esc_attr($batch_size); ?>" 
                                    class="small-text" 
                                    min="1" 
                                    max="100" 
                                />
                                <p class="description">
                                    <?php _e('Number of items to process in each batch (1-100).', 'wp-woocommerce-printify-sync'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="wpwprintifysync-card">
                    <h2><?php _e('Logging Settings', 'wp-woocommerce-printify-sync'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Log Level', 'wp-woocommerce-printify-sync'); ?></th>
                            <td>
                                <select name="wpwprintifysync_log_level">
                                    <option value="debug" <?php selected($log_level, 'debug'); ?>><?php _e('Debug', 'wp-woocommerce-printify-sync'); ?></option>
                                    <option value="info" <?php selected($log_level, 'info'); ?>><?php _e('Info', 'wp-woocommerce-printify-sync'); ?></option>
                                    <option value="warning" <?php selected($log_level, 'warning'); ?>><?php _e('Warning', 'wp-woocommerce-printify-sync'); ?></option>
                                    <option value="error" <?php selected($log_level, 'error'); ?>><?php _e('Error', 'wp-woocommerce-printify-sync'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Select log level for debugging and monitoring.', 'wp-woocommerce-printify-sync'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Log Retention', 'wp-woocommerce-printify-sync'); ?></th>
                            <td>
                                <input 
                                    type="number" 
                                    name="wpwprintifysync_log_retention_days" 
                                    value="<?php echo esc_attr($log_retention_days); ?>" 
                                    class="small-text" 
                                    min="1" 
                                    max="90" 
                                />
                                <p class="description">
                                    <?php _e('Number of days to keep logs (1-90).', 'wp-woocommerce-printify-sync'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="wpwprintifysync-card">
                    <h2><?php _e('Notification Settings', 'wp-woocommerce-printify-sync'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Notification Email', 'wp-woocommerce-printify-sync'); ?></th>
                            <td>
                                <input 
                                    type="email" 
                                    name="wpwprintifysync_notification_email" 
                                    value="<?php echo esc_attr($notification_email); ?>" 
                                    class="regular-text" 
                                />
                                <p class="description">
                                    <?php _e('Email address for important notifications.', 'wp-woocommerce-printify-sync'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <input type="submit" name="wpwprintifysync_settings_submit" class="button button-primary" value="<?php _e('Save Settings', 'wp-woocommerce-printify-sync'); ?>" />
                </p>
            </form>
            
            <div class="wpwprintifysync-card">
                <h2><?php _e('System Information', 'wp-woocommerce-printify-sync'); ?></h2>
                <table class="widefat">
                    <tbody>
                        <tr>
                            <th><?php _e('WordPress Version', 'wp-woocommerce-printify-sync'); ?></th>
                            <td><?php echo get_bloginfo('version'); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('WooCommerce Version', 'wp-woocommerce-printify-sync'); ?></th>
                            <td><?php echo defined('WC_VERSION') ? WC_VERSION : __('Not detected', 'wp-woocommerce-printify-sync'); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('PHP Version', 'wp-woocommerce-printify-sync'); ?></th>
                            <td><?php echo PHP_VERSION; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Plugin Version', 'wp-woocommerce-printify-sync'); ?></th>
                            <td><?php echo CoreHelper::VERSION; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Last Updated', 'wp-woocommerce-printify-sync'); ?></th>
                            <td><?php echo CoreHelper::LAST_UPDATED; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Current User', 'wp-woocommerce-printify-sync'); ?></th>
                            <td><?php echo wp_get_current_user()->user_login; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Current UTC Time', 'wp-woocommerce-printify-sync'); ?></th>
                            <td><?php echo '2025-03-05 18:23:09'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function saveSettings() {
        // API Mode
        if (isset($_POST['wpwprintifysync_api_mode'])) {
            update_option('wpwprintifysync_api_mode', sanitize_text_field($_POST['wpwprintifysync_api_mode']));
        }
        
        // API Keys (simplified storage without encryption for now)
        if (isset($_POST['wpwprintifysync_printify_api_key'])) {
            update_option('wpwprintifysync_printify_api_key', sanitize_text_field($_POST['wpwprintifysync_printify_api_key']));
        }
        
        if (isset($_POST['wpwprintifysync_currency_api_key'])) {
            update_option('wpwprintifysync_currency_api_key', sanitize_text_field($_POST['wpwprintifysync_currency_api_key']));
        }
        
        if (isset($_POST['wpwprintifysync_geolocation_api_key'])) {
            update_option('wpwprintifysync_geolocation_api_key', sanitize_text_field($_POST['wpwprintifysync_geolocation_api_key']));
        }
        
        if (isset($_POST['wpwprintifysync_webhook_secret'])) {
            update_option('wpwprintifysync_webhook_secret', sanitize_text_field($_POST['wpwprintifysync_webhook_secret']));
        }
        
        // Sync settings
        if (isset($_POST['wpwprintifysync_batch_size'])) {
            update_option('wpwprintifysync_batch_size', intval($_POST['wpwprintifysync_batch_size']));
        }
        
        // Log settings
        if (isset($_POST['wpwprintifysync_log_level'])) {
            update_option('wpwprintifysync_log_level', sanitize_text_field($_POST['wpwprintifysync_log_level']));
        }
        
        if (isset($_POST['wpwprintifysync_log_retention_days'])) {
            update_option('wpwprintifysync_log_retention_days', intval($_POST['wpwprintifysync_log_retention_days']));
        }
        
        // Notification settings
        if (isset($_POST['wpwprintifysync_notification_email'])) {
            update_option('wpwprintifysync_notification_email', sanitize_email($_POST['wpwprintifysync_notification_email']));
        }
        
        // Record last settings update
        update_option('wpwprintifysync_settings_last_updated', '2025-03-05 18:23:09');
        update_option('wpwprintifysync_settings_updated_by', 'ApolloWeb');
        
        // Log settings update
        LogHelper::getInstance()->info('Settings updated', [
            'user' => 'ApolloWeb',
            'time' => '2025-03-05 18:23:09'
        ]);
        
        wp_redirect(admin_url('admin.php?page=wpwprintifysync-settings&settings-updated=true'));
        exit;
    }
    
    /**
     * AJAX handler for testing API connections
     */
    public function ajaxTestApi() {
        // Check nonce
        if (!check_ajax_referer('wpwprintifysync-admin', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
        }
        
        $api_type = isset($_POST['api_type']) ? sanitize_text_field($_POST['api_type']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_type)) {
            wp_send_json_error(['message' => __('API type not specified.', 'wp-woocommerce-printify-sync')]);
        }
        
        $result = ApiHelper::getInstance()->testApiConnection($api_type, $api_key);
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'data' => $result['data'] ?? null
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message']
            ]);
        }
    }
}