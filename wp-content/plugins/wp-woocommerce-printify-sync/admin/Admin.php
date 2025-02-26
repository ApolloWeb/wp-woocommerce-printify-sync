<?php

namespace ApolloWeb\WooCommercePrintifySync;

use ApolloWeb\WooCommercePrintifySync\Api;

/**
 * Printify Sync Admin Class
 * 
 * Handles all admin functionality for the plugin
 */
class Admin
{
    /**
     * Option name for API key
     * @var string
     */
    private $option_api_key = 'printify_api_key';
    
    /**
     * Option name for selected shop ID
     * @var string
     */
    private $option_shop_id = 'printify_selected_shop';
    
    /**
     * API instance
     * @var Api
     */
    private $api = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Admin menu and settings
        add_action('admin_menu', [ $this, 'addSettingsPage' ]);
        add_action('admin_init', [ $this, 'registerSettings' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueueAdminScripts' ]);
        
        // AJAX handlers
        add_action('wp_ajax_fetch_printify_shops', [ $this, 'fetchPrintifyShops' ]);
        add_action('wp_ajax_fetch_printify_products', [ $this, 'fetchPrintifyProducts' ]);
        add_action('wp_ajax_save_selected_shop', [ $this, 'saveSelectedShop' ]);
    }

    /**
     * Get the API instance, initializing it if necessary
     *
     * @return Api|null API instance or null if API key not set
     */
    private function getApi()
    {
        if ($this->api === null) {
            $apiKey = trim(get_option($this->option_api_key, ''));
            if (!empty($apiKey)) {
                $this->api = new Api($apiKey);
            }
        }
        return $this->api;
    }

    /**
     * Add settings page to WordPress admin menu
     */
    public function addSettingsPage()
    {
        add_options_page(
            __('Printify Sync Settings', 'wp-woocommerce-printify-sync'), 
            __('Printify Sync', 'wp-woocommerce-printify-sync'), 
            'manage_options', 
            'printify-sync', 
            [ $this, 'renderSettingsPage' ]
        );
    }

    /**
     * Register settings fields
     */
    public function registerSettings()
    {
        register_setting('printify_sync_settings', $this->option_api_key);
        register_setting('printify_sync_settings', $this->option_shop_id);
        
        add_settings_section(
            'printify_sync_api_section', 
            __('Printify API Settings', 'wp-woocommerce-printify-sync'), 
            [ $this, 'apiSectionCallback' ], 
            'printify-sync'
        );
        
        add_settings_field(
            $this->option_api_key, 
            __('Printify API Key', 'wp-woocommerce-printify-sync'), 
            [ $this, 'apiKeyFieldCallback' ], 
            'printify-sync', 
            'printify_sync_api_section'
        );
        
        // Shop ID field is now hidden and managed via JS
    }

    /**
     * API section description
     */
    public function apiSectionCallback()
    {
        echo '<p>' . __('Enter your Printify API key below. You can select your shop from the Shops section.', 'wp-woocommerce-printify-sync') . '</p>';
    }

    /**
     * API key field HTML
     */
    public function apiKeyFieldCallback()
    {
        $apiKey = esc_attr(get_option($this->option_api_key, ''));
        echo '<input type="text" name="' . $this->option_api_key . '" value="' . $apiKey . '" class="regular-text" />';
        echo '<p class="description">' . __('You can find your API key in the Printify account settings', 'wp-woocommerce-printify-sync') . '</p>';
    }

    /**
     * Shop selection dropdown HTML (kept for compatibility but not used directly)
     */
    public function shopDropdownFieldCallback()
    {
        $apiKey = trim(get_option($this->option_api_key, ''));
        if (empty($apiKey)) {
            echo '<p>' . __('Please enter your Printify API key first.', 'wp-woocommerce-printify-sync') . '</p>';
            return;
        }

        $api = $this->getApi();
        if (!$api) {
            echo '<p style="color:red;">' . __('Error initializing API client.', 'wp-woocommerce-printify-sync') . '</p>';
            return;
        }

        $response = $api->getShops();
        if (is_wp_error($response)) {
            echo '<p style="color:red;">' . __('Error fetching shops: ', 'wp-woocommerce-printify-sync') . esc_html($response->get_error_message()) . '</p>';
            return;
        }

        // Handle different response structures
        $shops = [];
        if (isset($response['data'])) {
            $shops = $response['data'];
        } elseif (is_array($response)) {
            $shops = $response;
        }

        if (empty($shops)) {
            echo '<p style="color:red;">' . __('No shops data found.', 'wp-woocommerce-printify-sync') . '</p>';
            return;
        }

        $selectedShop = esc_attr(get_option($this->option_shop_id, ''));
        $isSingle = (count($shops) === 1);
        echo '<select name="' . $this->option_shop_id . '" class="regular-text" ' . ($isSingle ? 'disabled' : '') . '>';
        foreach ($shops as $shop) {
            $selected = selected($selectedShop, $shop['id'], false);
            echo '<option value="' . esc_attr($shop['id']) . '" ' . $selected . '>' . esc_html($shop['title']) . '</option>';
        }
        echo '</select>';
        if ($isSingle && !empty($shops)) {
            echo '<input type="hidden" name="' . $this->option_shop_id . '" value="' . esc_attr($shops[0]['id']) . '">';
        }
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page
     */
    public function enqueueAdminScripts($hook)
    {
        // Only load on our settings page
        if (strpos($hook, 'printify-sync') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'printify-sync-admin', 
            plugins_url('assets/css/admin-styles.css', __FILE__), 
            [], 
            '1.0.4'
        );
        
        // Enqueue main admin script
        wp_enqueue_script(
            'printify-sync-admin', 
            plugins_url('assets/js/admin-script.js', __FILE__), 
            ['jquery'], 
            '1.0.4', 
            true
        );
        
        // Localize script first before dependent scripts
        wp_localize_script('printify-sync-admin', 'PrintifySync', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('printify_sync_nonce'),
        ]);
        
        // Enqueue component scripts
        wp_enqueue_script(
            'printify-sync-shops', 
            plugins_url('assets/js/shops.js', __FILE__), 
            ['jquery', 'printify-sync-admin'], 
            '1.0.4', 
            true
        );
        
        wp_enqueue_script(
            'printify-sync-products', 
            plugins_url('assets/js/products.js', __FILE__), 
            ['jquery', 'printify-sync-admin'], 
            '1.0.4', 
            true
        );
    }

    /**
     * Load and display a template file
     *
     * @param string $template_name Template name without extension
     * @param array $args Arguments to pass to the template
     */
    private function loadTemplate($template_name, $args = [])
    {
        $template_path = __DIR__ . '/templates/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            // Extract args to make them available as variables in the template
            if (!empty($args) && is_array($args)) {
                extract($args);
            }
            
            include $template_path;
        } else {
            // Log error or display notice that template is missing
            error_log('Template not found: ' . $template_path);
        }
    }

    /**
     * Render the settings page
     */
    public function renderSettingsPage()
    {
        // You can pass variables to the template if needed
        $args = [
            'option_api_key' => $this->option_api_key,
            'option_shop_id' => $this->option_shop_id,
        ];
        
        $this->loadTemplate('settings-page', $args);
    }

    /**
     * AJAX handler to fetch shops from Printify
     */
    public function fetchPrintifyShops()
    {
        error_log('Fetch Printify Shops AJAX called');
        
        // Check nonce
        if (!check_ajax_referer('printify_sync_nonce', 'nonce', false)) {
            error_log('Nonce verification failed');
            wp_send_json_error(['message' => __('Security check failed', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $apiKey = trim(get_option($this->option_api_key, ''));
        if (empty($apiKey)) {
            error_log('API Key is missing');
            wp_send_json_error(['message' => __('API Key is missing', 'wp-woocommerce-printify-sync')]);
            return;
        }

        $api = $this->getApi();
        if (!$api) {
            error_log('API initialization failed');
            wp_send_json_error(['message' => __('Error initializing API client', 'wp-woocommerce-printify-sync')]);
            return;
        }

        error_log('Making API request to Printify for shops');
        $response = $api->getShops();
        if (is_wp_error($response)) {
            error_log('API Error: ' . $response->get_error_message());
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }
        
        // Check if response has data directly or in a data property
        $shops = [];
        if (isset($response['data'])) {
            $shops = $response['data'];
        } elseif (is_array($response)) {
            // If response is an array itself, use it directly
            $shops = $response;
        }
        
        error_log('Shops found: ' . count($shops));
        
        if (empty($shops)) {
            wp_send_json_error(['message' => __('No shops data found. Please check your API key.', 'wp-woocommerce-printify-sync')]);
        } else {
            wp_send_json_success($shops);
        }
    }

    /**
     * AJAX handler to fetch products from Printify
     */
    public function fetchPrintifyProducts()
    {
        error_log('Fetch Printify Products AJAX called');
        
        // Check nonce
        if (!check_ajax_referer('printify_sync_nonce', 'nonce', false)) {
            error_log('Products: Nonce verification failed');
            wp_send_json_error(['message' => __('Security check failed', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $apiKey = trim(get_option($this->option_api_key, ''));
        $shopId = trim(get_option($this->option_shop_id, ''));
        if (empty($apiKey) || empty($shopId)) {
            error_log('Products: API Key or Shop ID is missing');
            wp_send_json_error(['message' => __('API Key or Shop ID is missing', 'wp-woocommerce-printify-sync')]);
            return;
        }

        $api = $this->getApi();
        if (!$api) {
            error_log('Products: API initialization failed');
            wp_send_json_error(['message' => __('Error initializing API client', 'wp-woocommerce-printify-sync')]);
            return;
        }

        error_log('Fetching Printify products for shop: ' . $shopId);
        $response = $api->getProducts($shopId, 10);
        
        if (is_wp_error($response)) {
            error_log('Products API Error: ' . $response->get_error_message());
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }

        // Handle different response structures
        $products = [];
        if (isset($response['data'])) {
            $products = $response['data'];
        } elseif (is_array($response)) {
            // If response is an array itself, use it directly
            $products = $response;
        }
        
        error_log('Products found: ' . count($products));
        
        if (empty($products)) {
            wp_send_json_error(['message' => __('No products found in this shop.', 'wp-woocommerce-printify-sync')]);
        } else {
            wp_send_json_success($products);
        }
    }
    
    /**
     * AJAX handler to save selected shop
     */
    public function saveSelectedShop()
    {
        check_ajax_referer('printify_sync_nonce', 'nonce');
        
        if (empty($_POST['shop_id'])) {
            wp_send_json_error(['message' => __('No shop ID provided', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $shop_id = sanitize_text_field($_POST['shop_id']);
        update_option($this->option_shop_id, $shop_id);
        
        wp_send_json_success(['message' => __('Shop selected successfully', 'wp-woocommerce-printify-sync')]);
    }
}