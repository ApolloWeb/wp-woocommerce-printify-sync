<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Services\ApiService;
use ApolloWeb\WPWooCommercePrintifySync\Services\LoggerService;

class Settings {
    private $api_service;
    private $logger_service;

    public function __construct() {
        $this->api_service = new ApiService();
        $this->logger_service = new LoggerService();
        add_action('wp_ajax_wpwps_test_connection', [$this, 'testConnection']);
        add_action('wp_ajax_wpwps_save_settings', [$this, 'saveSettings']);
        add_action('wp_ajax_wpwps_estimate_gpt_cost', [$this, 'estimateGPTCost']);
        add_action('wp_ajax_wpwps_get_logs', [$this, 'getLogs']);
        add_action('wp_ajax_wpwps_get_log_types', [$this, 'getLogTypes']);
        add_action('wp_ajax_wpwps_get_log_endpoints', [$this, 'getLogEndpoints']);
        add_action('wp_ajax_wpwps_clear_logs', [$this, 'clearLogs']);
    }

    public function testConnection(): void {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-woocommerce-printify-sync'));
        }

        $api_key = sanitize_text_field($_POST['api_key']);
        $endpoint = sanitize_text_field($_POST['endpoint']);

        $this->api_service->setApiKey($api_key);
        $this->api_service->setApiEndpoint($endpoint);

        $response = $this->api_service->testConnection();
        
        if ($response['success']) {
            wp_send_json_success([
                'message' => $response['message'],
                'shops' => $response['data']
            ]);
        } else {
            wp_send_json_error([
                'message' => $response['message']
            ]);
        }
    }

    public function saveSettings(): void {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-woocommerce-printify-sync'));
        }

        // API Settings
        $settings = [
            'wpwps_api_endpoint' => esc_url_raw($_POST['endpoint']),
            'wpwps_shop_id' => sanitize_text_field($_POST['shop_id']),
            
            // OpenAI Settings
            'wpwps_openai_max_tokens' => absint($_POST['openai_max_tokens']),
            'wpwps_openai_temperature' => min(2, max(0, floatval($_POST['openai_temperature']))),
            'wpwps_openai_monthly_cap' => max(0, floatval($_POST['openai_monthly_cap'])),

            // Rate Limiting
            'wpwps_max_retries' => min(10, max(1, absint($_POST['max_retries']))),
            'wpwps_retry_delay' => min(30, max(1, absint($_POST['retry_delay']))),
            'wpwps_rate_limit_buffer' => min(100, max(0, absint($_POST['rate_limit_buffer']))),

            // Email Settings
            'wpwps_pop3_host' => sanitize_text_field($_POST['pop3_host']),
            'wpwps_pop3_user' => sanitize_text_field($_POST['pop3_user']),
            'wpwps_smtp_host' => sanitize_text_field($_POST['smtp_host']),
            'wpwps_smtp_user' => sanitize_text_field($_POST['smtp_user']),
            'wpwps_smtp_port' => absint($_POST['smtp_port']),
            'wpwps_smtp_secure' => in_array($_POST['smtp_secure'], ['tls', 'ssl']) ? $_POST['smtp_secure'] : 'tls',
            'wpwps_smtp_from_email' => sanitize_email($_POST['smtp_from_email']),
            'wpwps_smtp_from_name' => sanitize_text_field($_POST['smtp_from_name']),

            // Email Signature Settings
            'wpwps_company_logo' => esc_url_raw($_POST['company_logo']),
            'wpwps_company_website' => esc_url_raw($_POST['company_website']),
            'wpwps_social_facebook' => esc_url_raw($_POST['social_facebook']),
            'wpwps_social_twitter' => esc_url_raw($_POST['social_twitter']),
            'wpwps_social_instagram' => esc_url_raw($_POST['social_instagram'])
        ];

        // Handle sensitive data with encryption
        $sensitive_fields = [
            'api_key' => 'wpwps_api_key',
            'openai_api_key' => 'wpwps_openai_api_key',
            'pop3_pass' => 'wpwps_pop3_pass',
            'smtp_pass' => 'wpwps_smtp_pass'
        ];

        foreach ($sensitive_fields as $field => $option) {
            if (!empty($_POST[$field])) {
                if ($field === 'api_key') {
                    $this->api_service->setApiKey($_POST[$field]);
                } else {
                    update_option($option, $this->api_service->encrypt($_POST[$field]));
                }
            }
        }

        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }

        // Update API service configuration
        $this->api_service->setMaxRetries($settings['wpwps_max_retries']);
        $this->api_service->setRetryDelay($settings['wpwps_retry_delay']);

        wp_send_json_success([
            'message' => __('Settings saved successfully', 'wp-woocommerce-printify-sync'),
            'shop_id' => $settings['wpwps_shop_id']
        ]);
    }

    public function estimateGPTCost(): void {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-woocommerce-printify-sync'));
        }

        $max_tokens = absint($_POST['max_tokens']);
        $monthly_cap = floatval($_POST['monthly_cap']);
        
        // Estimate based on GPT-3.5-turbo pricing ($0.002 per 1K tokens)
        $cost_per_1k = 0.002;
        $estimated_tokens_per_request = $max_tokens * 2; // Input + output tokens
        $max_requests = floor(($monthly_cap / $cost_per_1k) * 1000 / $estimated_tokens_per_request);
        
        wp_send_json_success([
            'estimated_requests' => $max_requests,
            'cost_per_request' => ($estimated_tokens_per_request / 1000) * $cost_per_1k
        ]);
    }

    public function getLogs(): void {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-woocommerce-printify-sync'));
        }

        $logs = $this->logger_service->getLogs([
            'page' => absint($_POST['page']),
            'per_page' => absint($_POST['per_page']),
            'status' => sanitize_text_field($_POST['status']),
            'endpoint' => sanitize_text_field($_POST['endpoint']),
            'date_from' => sanitize_text_field($_POST['date_from']),
            'date_to' => sanitize_text_field($_POST['date_to'])
        ]);

        wp_send_json_success(['logs' => $logs]);
    }

    public function getLogTypes(): void {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-woocommerce-printify-sync'));
        }

        wp_send_json_success([
            'types' => $this->logger_service->getLogTypes()
        ]);
    }

    public function getLogEndpoints(): void {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-woocommerce-printify-sync'));
        }

        wp_send_json_success([
            'endpoints' => $this->logger_service->getEndpoints()
        ]);
    }

    public function clearLogs(): void {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-woocommerce-printify-sync'));
        }

        $this->logger_service->clearLogs();
        wp_send_json_success(__('Logs cleared successfully', 'wp-woocommerce-printify-sync'));
    }

    public function getEmailSignature(): string {
        $logo_url = get_option('wpwps_company_logo');
        $website_url = get_option('wpwps_company_website');
        $company_name = get_option('wpwps_smtp_from_name');
        $facebook = get_option('wpwps_social_facebook');
        $twitter = get_option('wpwps_social_twitter');
        $instagram = get_option('wpwps_social_instagram');

        ob_start();
        ?>
        <table style="border: none; margin-top: 20px;">
            <?php if ($logo_url): ?>
            <tr>
                <td colspan="2">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($company_name); ?>" style="max-height: 50px;">
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td colspan="2">
                    <h3 style="margin: 10px 0;"><?php echo esc_html($company_name); ?></h3>
                </td>
            </tr>
            <?php if ($website_url): ?>
            <tr>
                <td colspan="2">
                    <a href="<?php echo esc_url($website_url); ?>" style="color: #96588a; text-decoration: none;">
                        <?php echo esc_html($website_url); ?>
                    </a>
                </td>
            </tr>
            <?php endif; ?>
            <?php if ($facebook || $twitter || $instagram): ?>
            <tr>
                <td colspan="2" style="padding-top: 10px;">
                    <?php if ($facebook): ?>
                    <a href="<?php echo esc_url($facebook); ?>" style="margin-right: 10px;">
                        <img src="<?php echo esc_url(WPWPS_PLUGIN_URL . 'assets/images/facebook.png'); ?>" alt="Facebook" style="height: 24px;">
                    </a>
                    <?php endif; ?>
                    <?php if ($twitter): ?>
                    <a href="<?php echo esc_url($twitter); ?>" style="margin-right: 10px;">
                        <img src="<?php echo esc_url(WPWPS_PLUGIN_URL . 'assets/images/twitter.png'); ?>" alt="Twitter" style="height: 24px;">
                    </a>
                    <?php endif; ?>
                    <?php if ($instagram): ?>
                    <a href="<?php echo esc_url($instagram); ?>">
                        <img src="<?php echo esc_url(WPWPS_PLUGIN_URL . 'assets/images/instagram.png'); ?>" alt="Instagram" style="height: 24px;">
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
        return ob_get_clean();
    }
}