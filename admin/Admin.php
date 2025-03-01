<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'settings_init']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);
        add_action('wp_ajax_printify_sync_import_products', [__CLASS__, 'import_products']);
        add_action('wp_ajax_printify_sync_clear_products', [__CLASS__, 'clear_products']);
    }

    public static function add_admin_menu() {
        add_menu_page(
            'Printify Sync',
            'Printify Sync',
            'manage_options',
            'printify-sync',
            [__CLASS__, 'settings_page'],
            'dashicons-admin-generic'
        );
    }

    public static function settings_init() {
        register_setting('printify-sync', 'wp_woocommerce_printify_sync_api_key', [
            'sanitize_callback' => [__CLASS__, 'sanitize_api_key']
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
            [__CLASS__, 'api_key_render'],
            'printify-sync',
            'printify-sync_section'
        );

        add_settings_field(
            'printify-sync_selected_shop',
            __('Select Shop', 'wp-woocommerce-printify-sync'),
            [__CLASS__, 'shop_selection_render'],
            'printify-sync',
            'printify-sync_section'
        );

        add_settings_field(
            'printify-sync_import_button',
            __('Manual Import', 'wp-woocommerce-printify-sync'),
            [__CLASS__, 'import_button_render'],
            'printify-sync',
            'printify-sync_section'
        );
    }

    public static function sanitize_api_key($api_key) {
        return encrypt_decrypt('encrypt', $api_key);
    }

    public static function api_key_render() {
        $api_key = encrypt_decrypt('decrypt', get_option('wp_woocommerce_printify_sync_api_key'));
        echo '<input type="text" name="wp_woocommerce_printify_sync_api_key" value="' . esc_attr($api_key) . '">';
    }

    public static function shop_selection_render() {
        $api = new PrintifyAPI();
        $shops = $api->get_shops();
        $selected_shop = get_option('wp_woocommerce_printify_sync_selected_shop');

        if (is_wp_error($shops)) {
            echo '<p>' . esc_html__('Unable to fetch shops. Please check your API key.', 'wp-woocommerce-printify-sync') . '</p>';
            return;
        }

        if (empty($shops)) {
            echo '<p>' . esc_html__('No shops available.', 'wp-woocommerce-printify-sync') . '</p>';
            return;
        }

        echo '<select name="wp_woocommerce_printify_sync_selected_shop">';
        foreach ($shops as $shop) {
            $selected = selected($selected_shop, $shop['id'], false);
            echo '<option value="' . esc_attr($shop['id']) . '" ' . $selected . '>' . esc_html($shop['title']) . '</option>';
        }
        echo '</select>';
    }

    public static function import_button_render() {
        echo '<button id="printify-sync-import-btn" class="button button-primary">' . __('Import Products', 'wp-woocommerce-printify-sync') . '</button>';
        echo '<div id="printify-sync-import-progress" style="display:none;">';
        echo '<progress id="printify-sync-import-progress-bar" value="0" max="100"></progress>';
        echo '<span id="printify-sync-import-progress-text"></span>';
        echo '</div>';

        echo '<button id="printify-sync-clear-products-btn" class="button button-secondary">' . __('Clear All Products', 'wp-woocommerce-printify-sync') . '</button>';
    }

    public static function settings_page() {
        require_once plugin_dir_path(__FILE__) . '../templates/settings-page.php';
    }

    public static function enqueue_admin_scripts() {
        wp_enqueue_style('wp-woocommerce-printify-sync-admin', plugin_dir_url(__FILE__) . '../assets/css/admin-styles.css');
        wp_enqueue_script('wp-woocommerce-printify-sync-admin', plugin_dir_url(__FILE__) . '../assets/js/admin.js', ['jquery'], null, true);

        wp_localize_script('wp-woocommerce-printify-sync-admin', 'printifySync', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('printify_sync_nonce')
        ]);
    }

    public static function import_products() {
        check_ajax_referer('printify_sync_nonce', 'security');

        $importer = new ProductImport();
        $result = $importer->import_products();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['total_chunks' => $result]);
    }

    public static function clear_products() {
        check_ajax_referer('printify_sync_nonce', 'security');

        $products = wc_get_products(['limit' => -1]);
        foreach ($products as $product) {
            wp_delete_post($product->get_id(), true);
        }

        wp_send_json_success();
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