<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_printify_sync_import_products', [$this, 'import_products']);
        add_action('wp_ajax_printify_sync_clear_products', [$this, 'clear_products']);
        add_action('wp_ajax_fetch_printify_shops', [$this, 'fetch_printify_shops']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Printify Sync',
            'Printify Sync',
            'manage_options',
            'printify-sync',
            [$this, 'settings_page'],
            'dashicons-admin-generic'
        );
    }

    public function settings_init() {
        register_setting('printify-sync', 'wp_woocommerce_printify_sync_api_key', [
            'sanitize_callback' => [$this, 'sanitize_api_key']
        ]);
        register_setting('printify-sync', 'wp_woocommerce_printify_sync_selected_shop');

        add_settings_section(
            'printify-sync_section',
            __('Printify API Settings', 'wp-woocommerce-printify-sync'),
            null,
            'printify-sync'
        );

        add_settings_field(
            'printify-sync_api_key',
            __('API Key', 'wp-woocommerce-printify-sync'),
            [$this, 'api_key_render'],
            'printify-sync',
            'printify-sync_section'
        );

        add_settings_field(
            'printify-sync_selected_shop',
            __('Select Shop', 'wp-woocommerce-printify-sync'),
            [$this, 'shop_selection_render'],
            'printify-sync',
            'printify-sync_section'
        );

        add_settings_field(
            'printify-sync_import_button',
            __('Manual Import', 'wp-woocommerce-printify-sync'),
            [$this, 'import_button_render'],
            'printify-sync',
            'printify-sync_section'
        );
    }

    public function sanitize_api_key($api_key) {
        return encrypt_decrypt('encrypt', $api_key);
    }

    public function api_key_render() {
        $api_key = encrypt_decrypt('decrypt', get_option('wp_woocommerce_printify_sync_api_key'));
        echo '<input type="text" name="wp_woocommerce_printify_sync_api_key" value="' . esc_attr($api_key) . '">';
    }

    public function shop_selection_render() {
        require_once plugin_dir_path(__FILE__) . '../templates/shops-section.php';
    }

    public function import_button_render() {
        echo '<button id="printify-sync-import-btn" class="button button-primary">' . __('Import Products', 'wp-woocommerce-printify-sync') . '</button>';
        echo '<div id="printify-sync-import-progress" style="display:none;">';
        echo '<progress id="printify-sync-import-progress-bar" value="0" max="100"></progress>';
        echo '<span id="printify-sync-import-progress-text"></span>';
        echo '</div>';

        echo '<button id="printify-sync-clear-products-btn" class="button button-secondary">' . __('Clear All Products', 'wp-woocommerce-printify-sync') . '</button>';
    }

    public function settings_page() {
        require_once plugin_dir_path(__FILE__) . '../templates/settings-page.php';
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('wp-woocommerce-printify-sync-admin', plugin_dir_url(__FILE__) . 'assets/css/admin-styles.css');
        wp_enqueue_script('wp-woocommerce-printify-sync-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], null, true);
        wp_enqueue_script('wp-woocommerce-printify-sync-shops', plugin_dir_url(__FILE__) . 'assets/js/shops.js', ['jquery'], null, true);

        wp_localize_script('wp-woocommerce-printify-sync-admin', 'printifySync', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('printify_sync_nonce')
        ]);
    }

    public function import_products() {
        check_ajax_referer('printify_sync_nonce', 'security');

        $importer = new ProductImport();
        $result = $importer->import_products();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['total_chunks' => $result]);
    }

    public function clear_products() {
        check_ajax_referer('printify_sync_nonce', 'security');

        $products = wc_get_products(['limit' => -1]);
        foreach ($products as $product) {
            wp_delete_post($product->get_id(), true);
        }

        wp_send_json_success();
    }

    public function fetch_printify_shops() {
        check_ajax_referer('printify_sync_nonce', 'security');

        $api = new PrintifyAPI();
        $shops = $api->get_shops();

        if (is_wp_error($shops)) {
            wp_send_json_error($shops->get_error_message());
        }

        wp_send_json_success($shops);
    }
}

function encrypt_decrypt($action, $string) {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = 'YOUR_SECRET_KEY';
    $secret_iv = 'YOUR_SECRET_IV';
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    if ($action == 'encrypt') {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if ($action == 'decrypt') {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }

    return $output;
}