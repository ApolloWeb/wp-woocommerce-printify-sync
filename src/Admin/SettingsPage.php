<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

defined('ABSPATH') || exit;

class SettingsPage
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_test_printify_connection', [$this, 'ajaxTestPrintifyConnection']);
        add_action('wp_ajax_wpwps_save_settings', [$this, 'ajaxSaveSettings']);
        add_action('wp_ajax_wpwps_test_chatgpt', [$this, 'ajaxTestChatGPT']);
    }

    public function registerMenu()
    {
        // Main menu
        add_menu_page(
            __('Printify', 'wp-woocommerce-printify-sync'),
            __('Printify', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M18 3H2a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm0 12H2V5h16v10zM4 13h2v2H4v-2zm4 0h8v2H8v-2zM4 9h2v2H4V9zm4 0h8v2H8V9zM4 5h2v2H4V5zm4 0h8v2H8V5z"/></svg>'),
            58
        );

        // Submenu pages
        add_submenu_page(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-dashboard',
            [$this, 'renderDashboard']
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-products',
            [$this, 'renderProducts']
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-orders',
            [$this, 'renderOrders']
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-settings',
            [$this, 'renderSettings']
        );
    }

    public function enqueueAssets($hook)
    {
        // Only load on our plugin pages
        if (strpos($hook, 'wpwps-') === false) {
            return;
        }
        
        // Core assets
        wp_enqueue_style('wpwps-bootstrap', plugins_url('../../assets/core/css/bootstrap.min.css', __FILE__));
        wp_enqueue_style('wpwps-fa', plugins_url('../../assets/core/css/fontawesome.min.css', __FILE__));
        wp_enqueue_script('wpwps-bootstrap', plugins_url('../../assets/core/js/bootstrap.bundle.min.js', __FILE__), ['jquery'], null, true);
        
        // Admin layout assets
        wp_enqueue_style('wpwps-admin', plugins_url('../../assets/css/wpwps-admin.css', __FILE__));
        wp_enqueue_script('wpwps-admin', plugins_url('../../assets/js/wpwps-admin.js', __FILE__), ['jquery'], null, true);
        
        // Page specific assets
        $page = str_replace('toplevel_page_', '', $hook);
        $page = str_replace('printify-sync_page_wpwps-', '', $page);
        
        // Check if page specific CSS exists and enqueue it
        $css_file = plugins_url('../../assets/css/wpwps-' . $page . '.css', __FILE__);
        $css_path = WPWPS_PLUGIN_PATH . 'assets/css/wpwps-' . $page . '.css';
        if (file_exists($css_path)) {
            wp_enqueue_style('wpwps-' . $page, $css_file);
        }
        
        // Check if page specific JS exists and enqueue it
        $js_file = plugins_url('../../assets/js/wpwps-' . $page . '.js', __FILE__);
        $js_path = WPWPS_PLUGIN_PATH . 'assets/js/wpwps-' . $page . '.js';
        if (file_exists($js_path)) {
            wp_enqueue_script('wpwps-' . $page, $js_file, ['jquery'], null, true);
            
            // Add specific localizations for each page
            if ($page === 'settings') {
                // Get all saved settings
                $saved_settings = [
                    'printify_api_key' => $this->decryptString(get_option('wpwps_printify_api_key', '')),
                    'printify_api_endpoint' => get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1/'),
                    'printify_shop_id' => get_option('wpwps_printify_shop_id', ''),
                    'chatgpt_api_key' => $this->decryptString(get_option('wpwps_chatgpt_api_key', '')),
                    'chatgpt_monthly_cap' => get_option('wpwps_chatgpt_monthly_cap', 0),
                    'chatgpt_max_tokens' => get_option('wpwps_chatgpt_max_tokens', 1024),
                    'chatgpt_temperature' => get_option('wpwps_chatgpt_temperature', 0.7),
                ];
                
                wp_localize_script('wpwps-settings', 'wpwpsSettings', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce('wpwps_settings_nonce'),
                    'savedValues' => $saved_settings,
                ]);
            }
        }
        
        // Chart.js for dashboard
        if ($page === 'dashboard') {
            wp_enqueue_script('wpwps-chart', plugins_url('../../assets/core/js/chart.min.js', __FILE__), [], null, true);
        }
    }
    
    /**
     * Render layout with content template
     */
    private function renderLayout($content_template, $page_title = '', $current_page = '')
    {
        $template = WPWPS_PLUGIN_PATH . '/templates/layout.php';
        if (!file_exists($template)) {
            echo '<div class="notice notice-error">Layout template missing.</div>';
            return;
        }
        
        if (!file_exists($content_template)) {
            echo '<div class="notice notice-error">Content template missing.</div>';
            return;
        }
        
        include $template;
    }
    
    /**
     * Render dashboard page
     */
    public function renderDashboard()
    {
        $content_template = WPWPS_PLUGIN_PATH . '/templates/wpwps-dashboard.php';
        $page_title = __('Dashboard', 'wp-woocommerce-printify-sync');
        $current_page = 'dashboard';
        $this->renderLayout($content_template, $page_title, $current_page);
    }
    
    /**
     * Render products page
     */
    public function renderProducts()
    {
        $content_template = WPWPS_PLUGIN_PATH . '/templates/wpwps-products.php';
        $page_title = __('Products', 'wp-woocommerce-printify-sync');
        $current_page = 'products';
        $this->renderLayout($content_template, $page_title, $current_page);
    }
    
    /**
     * Render orders page
     */
    public function renderOrders()
    {
        $content_template = WPWPS_PLUGIN_PATH . '/templates/wpwps-orders.php';
        $page_title = __('Orders', 'wp-woocommerce-printify-sync');
        $current_page = 'orders';
        $this->renderLayout($content_template, $page_title, $current_page);
    }
    
    /**
     * Render shipping page
     */
    public function renderShipping()
    {
        $content_template = WPWPS_PLUGIN_PATH . '/templates/wpwps-shipping.php';
        $page_title = __('Shipping', 'wp-woocommerce-printify-sync');
        $current_page = 'shipping';
        $this->renderLayout($content_template, $page_title, $current_page);
    }
    
    /**
     * Render tickets page
     */
    public function renderTickets()
    {
        $content_template = WPWPS_PLUGIN_PATH . '/templates/wpwps-tickets.php';
        $page_title = __('Support Tickets', 'wp-woocommerce-printify-sync');
        $current_page = 'tickets';
        $this->renderLayout($content_template, $page_title, $current_page);
    }
    
    /**
     * Render settings page
     */
    public function renderSettings()
    {
        $content_template = WPWPS_PLUGIN_PATH . '/templates/wpwps-settings.php';
        $page_title = __('Settings', 'wp-woocommerce-printify-sync');
        $current_page = 'settings';
        $this->renderLayout($content_template, $page_title, $current_page);
    }

    // ... existing AJAX methods
    public function ajaxTestPrintifyConnection()
    {
        check_ajax_referer('wpwps_settings_nonce', 'nonce');
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $endpoint = sanitize_url($_POST['endpoint'] ?? 'https://api.printify.com/v1/');
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API Key is required', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Make request to Printify API to get shops
        $response = wp_remote_get($endpoint . 'shops.json', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = isset($data['message']) ? $data['message'] : __('Unknown error occurred', 'wp-woocommerce-printify-sync');
            wp_send_json_error(['message' => $error_message, 'status' => $status_code]);
            return;
        }
        
        wp_send_json_success(['shops' => $data]);
    }

    public function ajaxSaveSettings()
    {
        error_log('Received save settings request');
        error_log('POST data: ' . print_r($_POST, true));

        // Verify nonce and capabilities
        if (!check_ajax_referer('wpwps_settings_nonce', 'nonce', false)) {
            error_log('Nonce verification failed');
            wp_send_json_error(['message' => __('Security check failed', 'wp-woocommerce-printify-sync')]);
            return;
        }

        if (!current_user_can('manage_options')) {
            error_log('Permission check failed');
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')]);
            return;
        }

        try {
            // Get and sanitize all POST data
            $settings = [];
            foreach ($_POST as $key => $value) {
                if ($key === 'action' || $key === 'nonce') continue;
                $settings[$key] = sanitize_text_field($value);
            }

            error_log('Sanitized settings: ' . print_r($settings, true));

            // Save Printify settings
            if (isset($settings['printify_api_key']) && !empty($settings['printify_api_key'])) {
                update_option('wpwps_printify_api_key', $this->encryptString($settings['printify_api_key']));
            }
            if (isset($settings['printify_api_endpoint'])) {
                update_option('wpwps_printify_api_endpoint', esc_url_raw($settings['printify_api_endpoint']));
            }
            if (isset($settings['printify_shop_id']) && !empty($settings['printify_shop_id'])) {
                update_option('wpwps_printify_shop_id', $settings['printify_shop_id']);
            }

            // Save ChatGPT settings
            if (isset($settings['chatgpt_api_key']) && !empty($settings['chatgpt_api_key'])) {
                update_option('wpwps_chatgpt_api_key', $this->encryptString($settings['chatgpt_api_key']));
            }
            
            // Save numeric settings with defaults
            update_option('wpwps_chatgpt_monthly_cap', isset($settings['chatgpt_monthly_cap']) ? absint($settings['chatgpt_monthly_cap']) : 0);
            update_option('wpwps_chatgpt_max_tokens', isset($settings['chatgpt_max_tokens']) ? absint($settings['chatgpt_max_tokens']) : 1024);
            update_option('wpwps_chatgpt_temperature', isset($settings['chatgpt_temperature']) ? (float)$settings['chatgpt_temperature'] : 0.7);

            error_log('Settings saved successfully');
            wp_send_json_success(['message' => __('Settings saved successfully', 'wp-woocommerce-printify-sync')]);

        } catch (\Exception $e) {
            error_log('Settings save error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error saving settings:', 'wp-woocommerce-printify-sync') . ' ' . $e->getMessage()]);
        }
    }
    
    public function ajaxTestChatGPT()
    {
        check_ajax_referer('wpwps_settings_nonce', 'nonce');
        
        $api_key = sanitize_text_field($_POST['chatgpt_api_key'] ?? '');
        $max_tokens = absint($_POST['chatgpt_max_tokens'] ?? 0);
        $monthly_cap = absint($_POST['chatgpt_monthly_cap'] ?? 0);
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('ChatGPT API Key is required', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Calculate estimated cost
        $cost_per_1k_tokens = 0.002; // $0.002 per 1K tokens for gpt-3.5-turbo
        $avg_daily_usage = 1000; // Estimated average tokens per day
        $monthly_usage = $avg_daily_usage * 30;
        $estimated_cost = ($monthly_usage / 1000) * $cost_per_1k_tokens;
        
        // Check if within cap
        $within_cap = $monthly_cap === 0 || $estimated_cost <= $monthly_cap;
        
        wp_send_json_success([
            'estimated_monthly_tokens' => $monthly_usage,
            'estimated_monthly_cost' => $estimated_cost,
            'within_cap' => $within_cap,
        ]);
    }
    
    /**
     * Simple encryption for API keys
     */
    private function encryptString($string)
    {
        if (empty($string)) {
            return '';
        }
        
        // Use WordPress's built-in encryption if available
        if (function_exists('wp_encrypt')) {
            return wp_encrypt($string);
        }
        
        // Simple encryption fallback using base64
        // In a production environment, you'd want to use a more secure method
        return base64_encode($string);
    }
    
    /**
     * Decrypt encrypted string
     */
    private function decryptString($encrypted)
    {
        if (empty($encrypted)) {
            return '';
        }
        
        // Use WordPress's built-in decryption if available
        if (function_exists('wp_decrypt')) {
            return wp_decrypt($encrypted);
        }
        
        // Simple decryption fallback using base64
        return base64_decode($encrypted);
    }
}
