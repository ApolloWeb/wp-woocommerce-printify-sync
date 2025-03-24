<?php
/**
 * Settings page class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Pages
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Services\ApiService;
use ApolloWeb\WPWooCommercePrintifySync\Services\LoggerService;
use ApolloWeb\WPWooCommercePrintifySync\Services\OpenAIService;
use ApolloWeb\WPWooCommercePrintifySync\Services\TemplateService;
use ApolloWeb\WPWooCommercePrintifySync\Container;

/**
 * Settings page class.
 */
class SettingsPage {
    /**
     * Container instance
     *
     * @var Container
     */
    private Container $container;

    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Template service
     *
     * @var TemplateService
     */
    private TemplateService $template_service;

    /**
     * Constructor
     *
     * @param Container $container Container instance.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->logger = $container->get('logger');
        $this->template_service = $container->get('template');
        
        // Register settings
        add_action('admin_init', [$this, 'registerSettings']);
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_save_settings', [$this, 'ajaxSaveSettings']);
        add_action('wp_ajax_wpwps_save_shop', [$this, 'ajaxSaveShop']);
        add_action('wp_ajax_wpwps_test_printify_api', [$this, 'ajaxTestPrintifyAPI']);
        add_action('wp_ajax_wpwps_test_openai_api', [$this, 'ajaxTestOpenAIAPI']);
        add_action('wp_ajax_wpwps_reset_plugin', [$this, 'ajaxResetPlugin']);
    }

    /**
     * Register settings
     *
     * @return void
     */
    public function registerSettings(): void
    {
        // Register settings
        register_setting('wpwps_settings', 'wpwps_api_endpoint');
        register_setting('wpwps_settings', 'wpwps_api_key');
        register_setting('wpwps_settings', 'wpwps_shop_id');
        register_setting('wpwps_settings', 'wpwps_shop_name');
        register_setting('wpwps_settings', 'wpwps_sync_interval');
        register_setting('wpwps_settings', 'wpwps_email_queue_interval');
        register_setting('wpwps_settings', 'wpwps_email_queue_batch_size');
        register_setting('wpwps_settings', 'wpwps_log_retention_days');
        register_setting('wpwps_settings', 'wpwps_enable_pop3');
        register_setting('wpwps_settings', 'wpwps_pop3_server');
        register_setting('wpwps_settings', 'wpwps_pop3_port');
        register_setting('wpwps_settings', 'wpwps_pop3_username');
        register_setting('wpwps_settings', 'wpwps_pop3_password');
        register_setting('wpwps_settings', 'wpwps_pop3_ssl');
        register_setting('wpwps_settings', 'wpwps_email_signature');
        register_setting('wpwps_settings', 'wpwps_openai_api_key');
        register_setting('wpwps_settings', 'wpwps_openai_model');
        register_setting('wpwps_settings', 'wpwps_openai_temperature');
        register_setting('wpwps_settings', 'wpwps_openai_monthly_token_cap');
        register_setting('wpwps_settings', 'wpwps_delete_data_on_uninstall');
    }

    /**
     * Render the settings page
     *
     * @return void
     */
    public function render(): void
    {
        // Get settings
        $settings = $this->getSettings();
        
        // Render the settings page
        $this->template_service->render('settings', $settings);
    }

    /**
     * Get settings
     *
     * @return array
     */
    private function getSettings(): array
    {
        // Get general settings
        $api_endpoint = get_option('wpwps_api_endpoint', 'https://api.printify.com/v1/');
        $api_key_encrypted = get_option('wpwps_api_key', '');
        $shop_id = get_option('wpwps_shop_id', '');
        $shop_name = get_option('wpwps_shop_name', '');
        $sync_interval = get_option('wpwps_sync_interval', 6);
        $email_queue_interval = get_option('wpwps_email_queue_interval', 15);
        $email_queue_batch_size = get_option('wpwps_email_queue_batch_size', 20);
        $log_retention_days = get_option('wpwps_log_retention_days', 30);
        $delete_data_on_uninstall = get_option('wpwps_delete_data_on_uninstall', false);
        
        // Get POP3 settings
        $enable_pop3 = get_option('wpwps_enable_pop3', false);
        $pop3_server = get_option('wpwps_pop3_server', '');
        $pop3_port = get_option('wpwps_pop3_port', 110);
        $pop3_username = get_option('wpwps_pop3_username', '');
        $pop3_password_encrypted = get_option('wpwps_pop3_password', '');
        $pop3_ssl = get_option('wpwps_pop3_ssl', false);
        
        // Get email signature
        $email_signature = get_option('wpwps_email_signature', '');
        
        // Get OpenAI settings
        $openai_api_key_encrypted = get_option('wpwps_openai_api_key', '');
        $openai_model = get_option('wpwps_openai_model', 'gpt-3.5-turbo');
        $openai_temperature = get_option('wpwps_openai_temperature', 0.7);
        $openai_monthly_token_cap = get_option('wpwps_openai_monthly_token_cap', 100000);
        
        // Get OpenAI usage stats
        $openai_service = new OpenAIService($this->logger);
        $openai_usage = $openai_service->getMonthlyTokenUsage();
        $current_month = date('Y-m');
        $current_month_usage = $openai_usage[$current_month] ?? 0;
        $current_month_cost = $openai_service->calculateEstimatedCost($current_month_usage);
        
        // API connection status
        $api_service = new ApiService($this->logger);
        $api_connected = !empty($api_key_encrypted) && !empty($shop_id);
        
        return [
            'api_endpoint' => $api_endpoint,
            'api_key_encrypted' => $api_key_encrypted,
            'api_key' => '', // Don't expose the actual key in the form
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
            'sync_interval' => $sync_interval,
            'email_queue_interval' => $email_queue_interval,
            'email_queue_batch_size' => $email_queue_batch_size,
            'log_retention_days' => $log_retention_days,
            'delete_data_on_uninstall' => $delete_data_on_uninstall,
            'enable_pop3' => $enable_pop3,
            'pop3_server' => $pop3_server,
            'pop3_port' => $pop3_port,
            'pop3_username' => $pop3_username,
            'pop3_password_encrypted' => $pop3_password_encrypted,
            'pop3_password' => '', // Don't expose the actual password in the form
            'pop3_ssl' => $pop3_ssl,
            'email_signature' => $email_signature,
            'openai_api_key_encrypted' => $openai_api_key_encrypted,
            'openai_api_key' => '', // Don't expose the actual key in the form
            'openai_model' => $openai_model,
            'openai_temperature' => $openai_temperature,
            'openai_monthly_token_cap' => $openai_monthly_token_cap,
            'openai_current_month_usage' => $current_month_usage,
            'openai_current_month_cost' => $current_month_cost,
            'api_connected' => $api_connected,
        ];
    }

    /**
     * AJAX handler for saving settings
     *
     * @return void
     */
    public function ajaxSaveSettings(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Get settings from request
        $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
        
        if (empty($settings)) {
            wp_send_json_error(['message' => __('No settings provided', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Process and save settings
        $processed = [];
        $api_service = new ApiService($this->logger);
        
        // General settings
        if (isset($settings['api_endpoint'])) {
            $api_endpoint = sanitize_text_field($settings['api_endpoint']);
            update_option('wpwps_api_endpoint', $api_endpoint);
            $processed[] = 'api_endpoint';
        }
        
        if (isset($settings['api_key']) && !empty($settings['api_key'])) {
            $api_key = sanitize_text_field($settings['api_key']);
            $encrypted_key = $api_service->encrypt($api_key);
            update_option('wpwps_api_key', $encrypted_key);
            $processed[] = 'api_key';
        }
        
        if (isset($settings['sync_interval'])) {
            $sync_interval = absint($settings['sync_interval']);
            if ($sync_interval < 1) {
                $sync_interval = 1;
            } elseif ($sync_interval > 24) {
                $sync_interval = 24;
            }
            update_option('wpwps_sync_interval', $sync_interval);
            $processed[] = 'sync_interval';
        }
        
        // Email queue settings
        if (isset($settings['email_queue_interval'])) {
            $email_queue_interval = absint($settings['email_queue_interval']);
            if ($email_queue_interval < 5) {
                $email_queue_interval = 5;
            } elseif ($email_queue_interval > 60) {
                $email_queue_interval = 60;
            }
            update_option('wpwps_email_queue_interval', $email_queue_interval);
            $processed[] = 'email_queue_interval';
        }
        
        if (isset($settings['email_queue_batch_size'])) {
            $email_queue_batch_size = absint($settings['email_queue_batch_size']);
            if ($email_queue_batch_size < 5) {
                $email_queue_batch_size = 5;
            } elseif ($email_queue_batch_size > 100) {
                $email_queue_batch_size = 100;
            }
            update_option('wpwps_email_queue_batch_size', $email_queue_batch_size);
            $processed[] = 'email_queue_batch_size';
        }
        
        // Log settings
        if (isset($settings['log_retention_days'])) {
            $log_retention_days = absint($settings['log_retention_days']);
            if ($log_retention_days < 1) {
                $log_retention_days = 1;
            } elseif ($log_retention_days > 365) {
                $log_retention_days = 365;
            }
            update_option('wpwps_log_retention_days', $log_retention_days);
            $processed[] = 'log_retention_days';
        }
        
        if (isset($settings['delete_data_on_uninstall'])) {
            $delete_data_on_uninstall = (bool) $settings['delete_data_on_uninstall'];
            update_option('wpwps_delete_data_on_uninstall', $delete_data_on_uninstall);
            $processed[] = 'delete_data_on_uninstall';
        }
        
        // POP3 settings
        if (isset($settings['enable_pop3'])) {
            $enable_pop3 = (bool) $settings['enable_pop3'];
            update_option('wpwps_enable_pop3', $enable_pop3);
            $processed[] = 'enable_pop3';
        }
        
        if (isset($settings['pop3_server'])) {
            $pop3_server = sanitize_text_field($settings['pop3_server']);
            update_option('wpwps_pop3_server', $pop3_server);
            $processed[] = 'pop3_server';
        }
        
        if (isset($settings['pop3_port'])) {
            $pop3_port = absint($settings['pop3_port']);
            update_option('wpwps_pop3_port', $pop3_port);
            $processed[] = 'pop3_port';
        }
        
        if (isset($settings['pop3_username'])) {
            $pop3_username = sanitize_text_field($settings['pop3_username']);
            update_option('wpwps_pop3_username', $pop3_username);
            $processed[] = 'pop3_username';
        }
        
        if (isset($settings['pop3_password']) && !empty($settings['pop3_password'])) {
            $pop3_password = sanitize_text_field($settings['pop3_password']);
            $encrypted_password = $api_service->encrypt($pop3_password);
            update_option('wpwps_pop3_password', $encrypted_password);
            $processed[] = 'pop3_password';
        }
        
        if (isset($settings['pop3_ssl'])) {
            $pop3_ssl = (bool) $settings['pop3_ssl'];
            update_option('wpwps_pop3_ssl', $pop3_ssl);
            $processed[] = 'pop3_ssl';
        }
        
        if (isset($settings['email_signature'])) {
            $email_signature = wp_kses_post($settings['email_signature']);
            update_option('wpwps_email_signature', $email_signature);
            $processed[] = 'email_signature';
        }
        
        // OpenAI settings
        if (isset($settings['openai_api_key']) && !empty($settings['openai_api_key'])) {
            $openai_api_key = sanitize_text_field($settings['openai_api_key']);
            $encrypted_key = $api_service->encrypt($openai_api_key);
            update_option('wpwps_openai_api_key', $encrypted_key);
            $processed[] = 'openai_api_key';
        }
        
        if (isset($settings['openai_model'])) {
            $openai_model = sanitize_text_field($settings['openai_model']);
            update_option('wpwps_openai_model', $openai_model);
            $processed[] = 'openai_model';
        }
        
        if (isset($settings['openai_temperature'])) {
            $openai_temperature = (float) $settings['openai_temperature'];
            if ($openai_temperature < 0) {
                $openai_temperature = 0;
            } elseif ($openai_temperature > 1) {
                $openai_temperature = 1;
            }
            update_option('wpwps_openai_temperature', $openai_temperature);
            $processed[] = 'openai_temperature';
        }
        
        if (isset($settings['openai_monthly_token_cap'])) {
            $openai_monthly_token_cap = absint($settings['openai_monthly_token_cap']);
            update_option('wpwps_openai_monthly_token_cap', $openai_monthly_token_cap);
            $processed[] = 'openai_monthly_token_cap';
        }
        
        // Schedule tasks if settings changed
        if (in_array('sync_interval', $processed, true) || in_array('email_queue_interval', $processed, true)) {
            // Get action scheduler service and reschedule tasks
            $action_scheduler = $this->container->get('action_scheduler');
            $action_scheduler->clearScheduledTasks();
            $action_scheduler->setupRecurringTasks();
        }
        
        wp_send_json_success([
            'message' => __('Settings saved successfully', 'wp-woocommerce-printify-sync'),
            'processed' => $processed,
        ]);
    }

    /**
     * AJAX handler for saving shop
     *
     * @return void
     */
    public function ajaxSaveShop(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Get shop data
        $shop_id = isset($_POST['shop_id']) ? sanitize_text_field($_POST['shop_id']) : '';
        $shop_name = isset($_POST['shop_name']) ? sanitize_text_field($_POST['shop_name']) : '';
        
        if (empty($shop_id) || empty($shop_name)) {
            wp_send_json_error(['message' => __('Shop ID and name are required', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Save shop data
        update_option('wpwps_shop_id', $shop_id);
        update_option('wpwps_shop_name', $shop_name);
        
        // Update API service
        $api_service = new ApiService($this->logger);
        $api_service->setShopId($shop_id);
        
        wp_send_json_success([
            'message' => __('Shop saved successfully', 'wp-woocommerce-printify-sync'),
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
        ]);
    }

    /**
     * AJAX handler for testing Printify API
     *
     * @return void
     */
    public function ajaxTestPrintifyAPI(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Get API key
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Test connection
        $api_service = new ApiService($this->logger);
        $api_service->setApiKey($api_key);
        
        $result = $api_service->testConnection();
        
        if ($result['success']) {
            // Get shops
            $shops = $result['data'] ?? [];
            
            // Format shops for dropdown
            $formatted_shops = [];
            foreach ($shops as $shop) {
                if (isset($shop['id'], $shop['title'])) {
                    $formatted_shops[] = [
                        'id' => $shop['id'],
                        'name' => $shop['title'],
                    ];
                }
            }
            
            wp_send_json_success([
                'message' => $result['message'],
                'shops' => $formatted_shops,
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'],
            ]);
        }
    }

    /**
     * AJAX handler for testing OpenAI API
     *
     * @return void
     */
    public function ajaxTestOpenAIAPI(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Get API key
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : 'gpt-3.5-turbo';
        $temperature = isset($_POST['temperature']) ? (float) $_POST['temperature'] : 0.7;
        
        if (empty($api_key)) {
            // Try to use the stored API key
            $encrypted_key = get_option('wpwps_openai_api_key', '');
            if (empty($encrypted_key)) {
                wp_send_json_error(['message' => __('API key is required', 'wp-woocommerce-printify-sync')]);
                return;
            }
        } else {
            // Encrypt and store the API key
            $api_service = new ApiService($this->logger);
            $encrypted_key = $api_service->encrypt($api_key);
            update_option('wpwps_openai_api_key', $encrypted_key);
            
            // Also update model and temperature if provided
            if (!empty($model)) {
                update_option('wpwps_openai_model', $model);
            }
            
            update_option('wpwps_openai_temperature', $temperature);
        }
        
        // Test connection
        $openai_service = new OpenAIService($this->logger);
        $result = $openai_service->testConnection();
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],
                'data' => $result['data'] ?? [],
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'],
            ]);
        }
    }

    /**
     * AJAX handler for resetting plugin data
     *
     * @return void
     */
    public function ajaxResetPlugin(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Delete options
        $options = [
            'wpwps_api_endpoint',
            'wpwps_api_key',
            'wpwps_shop_id',
            'wpwps_shop_name',
            'wpwps_sync_interval',
            'wpwps_email_queue_interval',
            'wpwps_email_queue_batch_size',
            'wpwps_log_retention_days',
            'wpwps_enable_pop3',
            'wpwps_pop3_server',
            'wpwps_pop3_port',
            'wpwps_pop3_username',
            'wpwps_pop3_password',
            'wpwps_pop3_ssl',
            'wpwps_email_signature',
            'wpwps_openai_api_key',
            'wpwps_openai_model',
            'wpwps_openai_temperature',
            'wpwps_openai_monthly_token_cap',
            'wpwps_delete_data_on_uninstall',
        ];
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Delete product meta
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_printify_%'");
        
        // Clear scheduled tasks
        $action_scheduler = $this->container->get('action_scheduler');
        $action_scheduler->clearScheduledTasks();
        
        wp_send_json_success([
            'message' => __('Plugin data reset successfully', 'wp-woocommerce-printify-sync'),
        ]);
    }
}
