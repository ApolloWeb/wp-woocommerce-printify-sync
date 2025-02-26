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
        add_action('wp_ajax_fetch_printify_shops', [ $this, 'fetchPrintifyShops' ]);
        add_action('wp_ajax_fetch_printify_products', [ $this, 'fetchPrintifyProducts' ]);
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
        add_settings_section('printify_sync_api_section', __('Printify API Settings', 'wp-woocommerce-printify-sync'), [ $this, 'apiSectionCallback' ], 'printify-sync');
        add_settings_field($this->option_api_key, __('Printify API Key', 'wp-woocommerce-printify-sync'), [ $this, 'apiKeyFieldCallback' ], 'printify-sync', 'printify_sync_api_section');
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

        $api = new Api($apiKey);
        $shops = $api->getShops();
        if (is_wp_error($shops)) {
            echo '<p style="color:red;">' . __('Error fetching shops: ', 'wp-woocommerce-printify-sync') . esc_html($shops->get_error_message()) . '</p>';
            return;
        }

        $selectedShop = esc_attr(get_option($this->option_shop_id, ''));
        $isSingle = ( count($shops) === 1 );
        echo '<select name="' . $this->option_shop_id . '" class="regular-text" ' . ( $isSingle ? 'disabled' : '' ) . '>';
        foreach ($shops as $shop) {
            $selected = selected($selectedShop, $shop['id'], false);
            echo '<option value="' . esc_attr($shop['id']) . '" ' . $selected . '>' . esc_html($shop['title']) . '</option>';
        }
        echo '</select>';
        if ($isSingle && ! empty($shops)) {
            echo '<input type="hidden" name="' . $this->option_shop_id . '" value="' . esc_attr($shops[0]['id']) . '">';
        }
    }

    public function enqueueAdminStyles()
    {
        wp_enqueue_script('printify-sync-fetch-shops', plugins_url('assets/js/fetch-shops.js', __FILE__), ['jquery'], '1.0.0', true);
        wp_enqueue_script('printify-sync-fetch-products', plugins_url('assets/js/fetch-products.js', __FILE__), ['jquery'], '1.0.0', true);
        wp_enqueue_script('printify-sync-dom', plugins_url('assets/js/dom-updates.js', __FILE__), ['jquery'], '1.0.0', true);
        wp_enqueue_script('printify-sync-events', plugins_url('assets/js/event-handlers.js', __FILE__), ['jquery', 'printify-sync-fetch-shops', 'printify-sync-fetch-products', 'printify-sync-dom'], '1.0.0', true);
        wp_localize_script('printify-sync-events', 'PrintifySync', [
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
            <button id="fetch-printify-shops" class="button button-secondary">
                <?php _e('Fetch Printify Shops', 'wp-woocommerce-printify-sync'); ?>
            </button>
            <div id="printify-shops-results"></div>
            <button id="fetch-printify-products" class="button button-secondary">
                <?php _e('Fetch Printify Products', 'wp-woocommerce-printify-sync'); ?>
            </button>
            <div id="printify-products-results"></div>
        </div>
        <?php
    }

    public function fetchPrintifyShops()
    {
        check_ajax_referer('printify_sync_nonce', 'nonce');
        $apiKey = trim(get_option($this->option_api_key, ''));
        if (empty($apiKey)) {
            wp_send_json_error(['message' => __('API Key is missing', 'wp-woocommerce-printify-sync')]);
        }

        $api = new Api($apiKey);
        $shops = $api->getShops();
        if (is_wp_error($shops)) {
            wp_send_json_error(['message' => $shops->get_error_message()]);
        }

        wp_send_json_success($shops);
    }

    public function fetchPrintifyProducts()
    {
        check_ajax_referer('printify_sync_nonce', 'nonce');
        $apiKey = trim(get_option($this->option_api_key, ''));
        $shopId = trim(get_option($this->option_shop_id, ''));
        if (empty($apiKey) || empty($shopId)) {
            wp_send_json_error(['message' => __('API Key or Shop ID is missing', 'wp-woocommerce-printify-sync')]);
        }

        $api = new Api($apiKey);
        $products = $api->getProducts($shopId);
        if (is_wp_error($products)) {
            wp_send_json_error(['message' => $products->get_error_message()]);
        }

        wp_send_json_success($products);
    }
}