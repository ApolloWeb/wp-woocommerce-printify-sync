<?php

namespace ApolloWeb\WooCommercePrintifySync;

class Admin
{
    private $option_api_key = 'printify_api_key';
    private $option_shop_id = 'printify_selected_shop';

    public function __construct()
    {
        add_action('admin_menu', [ $this, 'addSettingsPage' ]);
        add_action('admin_init', [ $this, 'registerSettings' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueueAdminStyles' ]);
        add_action('wp_ajax_test_printify_products', [ $this, 'testPrintifyProducts' ]);
    }

    public function addSettingsPage()
    {
        add_options_page(__('Printify Sync Settings', 'wp-woocommerce-printify-sync'), __('Printify Sync', 'wp-woocommerce-printify-sync'), 'manage_options', 'printify-sync', [ $this, 'renderSettingsPage' ]);
    }

    public function registerSettings()
    {
        // Register settings to store Printify API key and selected shop.
        register_setting('printify_sync_settings', $this->option_api_key);
        register_setting('printify_sync_settings', $this->option_shop_id);
// Add a section for API settings.
        add_settings_section('printify_sync_api_section', __('Printify API Settings', 'wp-woocommerce-printify-sync'), [ $this, 'apiSectionCallback' ], 'printify-sync');
// API Key field.
        add_settings_field($this->option_api_key, __('Printify API Key', 'wp-woocommerce-printify-sync'), [ $this, 'apiKeyFieldCallback' ], 'printify-sync', 'printify_sync_api_section');
// Shop dropdown field.
        add_settings_field($this->option_shop_id, __('Select Shop', 'wp-woocommerce-printify-sync'), [ $this, 'shopDropdownFieldCallback' ], 'printify-sync', 'printify_sync_api_section');
    }

    public function apiSectionCallback()
    {
        echo '<p>' . __('Enter your Printify API key and choose your shop from the dropdown below.', 'wp-woocommerce-printify-sync') . '</p>';
    }

    public function apiKeyFieldCallback()
    {
        $apiKey = esc_attr(get_option($this->option_api_key, ''));
        echo '<input type="text" name="' . $this->option_api_key . '" value="' . $apiKey . '" class="regular-text" />';
    }

    public function shopDropdownFieldCallback()
    {
        $apiKey = trim(get_option($this->option_api_key, ''));
        if (empty($apiKey)) {
            echo '<p>' . __('Please enter your Printify API key first.', 'wp-woocommerce-printify-sync') . '</p>';
            return;
        }

        $shops = $this->getPrintifyShops($apiKey);
        if (is_wp_error($shops)) {
            echo '<p style="color:red;">' . __('Error fetching shops: ', 'wp-woocommerce-printify-sync') . esc_html($shops->get_error_message()) . '</p>';
            return;
        }

        $selectedShop = esc_attr(get_option($this->option_shop_id, ''));
        $isSingle = ( count($shops) === 1 );
        echo '<select name="' . $this->option_shop_id . '" class="regular-text" ' . ( $isSingle ? 'disabled' : '' ) . '>';
        foreach ($shops as $shop) {
        // Assuming the API returns shops with an "id" and "title" (store name).
            $selected = selected($selectedShop, $shop['id'], false);
            echo '<option value="' . esc_attr($shop['id']) . '" ' . $selected . '>' . esc_html($shop['title']) . '</option>';
        }
        echo '</select>';
        if ($isSingle && ! empty($shops)) {
            echo '<input type="hidden" name="' . $this->option_shop_id . '" value="' . esc_attr($shops[0]['id']) . '">';
        }
    }

    /**
     * Fetch shops from the Printify API.
     *
     * @param string $apiKey Your Printify API key.
     * @return array|\WP_Error Array of shops or WP_Error on failure.
     */
    private function getPrintifyShops($apiKey)
    {
        $endpoint = 'https://api.printify.com/v1/shops.json';
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'timeout' => 10,
        ];
        $response = wp_remote_get($endpoint, $args);
        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new \WP_Error('printify_api_error', __('Unexpected response from Printify API', 'wp-woocommerce-printify-sync'));
        }

        $body = wp_remote_retrieve_body($response);
        $shops = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_error', __('Error decoding Printify API response', 'wp-woocommerce-printify-sync'));
        }

        return $shops;
    }

    /**
     * Fetch products from the Printify API.
     *
     * @param string $apiKey Your Printify API key.
     * @param string $shopId Your Printify shop ID.
     * @return array|\WP_Error Array of products or WP_Error on failure.
     */
    private function getPrintifyProducts($apiKey, $shopId)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/products.json?limit=5";
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'timeout' => 10,
        ];
        $response = wp_remote_get($endpoint, $args);
        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new \WP_Error('printify_api_error', __('Unexpected response from Printify API', 'wp-woocommerce-printify-sync'));
        }

        $body = wp_remote_retrieve_body($response);
        $products = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_error', __('Error decoding Printify API response', 'wp-woocommerce-printify-sync'));
        }

        return $products;
    }

    public function enqueueAdminStyles()
    {
        // Enqueue any required styles and scripts.
        wp_enqueue_style('printify-sync-admin', WPS_PLUGIN_URL . 'assets/css/admin-styles.css', [], '1.0.0');
        wp_enqueue_script('printify-sync-admin', WPS_PLUGIN_URL . 'assets/js/admin-scripts.js', ['jquery'], '1.0.0', true);
        wp_localize_script('printify-sync-admin', 'PrintifySync', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('printify_sync_nonce'),
        ]);
    }

    public function renderSettingsPage()
    {
        ?>
        <div class="wrap printify-sync-settings">
            <h1><?php _e('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields('printify_sync_settings');
                do_settings_sections('printify-sync');
                submit_button();
                ?>
            </form>
            <button id="test-printify-products" class="button button-secondary">
                <?php _e('Test Printify Products API', 'wp-woocommerce-printify-sync'); ?>
            </button>
            <div id="printify-products-results"></div>
        </div>
        <?php
    }

    public function testPrintifyProducts()
    {
        check_ajax_referer('printify_sync_nonce', 'nonce');
        $apiKey = trim(get_option($this->option_api_key, ''));
        $shopId = trim(get_option($this->option_shop_id, ''));
        if (empty($apiKey) || empty($shopId)) {
            wp_send_json_error(['message' => __('API Key or Shop ID is missing', 'wp-woocommerce-printify-sync')]);
        }

        $products = $this->getPrintifyProducts($apiKey, $shopId);
        if (is_wp_error($products)) {
            wp_send_json_error(['message' => $products->get_error_message()]);
        }

        wp_send_json_success($products);
    }
}
