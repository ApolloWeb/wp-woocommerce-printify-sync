<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Sync products and orders between WooCommerce and Printify
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: wp-woocommerce-printify-sync
 */

defined('ABSPATH') || exit;

define('PRINTIFY_SYNC_VERSION', '1.0.0');
define('PRINTIFY_SYNC_FILE', __FILE__);
define('PRINTIFY_SYNC_PATH', plugin_dir_path(PRINTIFY_SYNC_FILE));
define('PRINTIFY_SYNC_URL', plugin_dir_url(PRINTIFY_SYNC_FILE));

// Autoloader
if (file_exists(PRINTIFY_SYNC_PATH . 'vendor/autoload.php')) {
    require_once PRINTIFY_SYNC_PATH . 'vendor/autoload.php';
}

// Initialize plugin
function init_printify_sync() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                 esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync') . 
                 '</p></div>';
        });
        return;
    }

    // Initialize components
    $settings = new \ApolloWeb\WPWooCommercePrintifySync\Admin\Settings();
    $settings->init();

    $product_sync = new \ApolloWeb\WPWooCommercePrintifySync\Products\ProductSync();
    $product_sync->init();
}

add_action('plugins_loaded', 'init_printify_sync');

jQuery(document).ready(function($) {
    const form = $('#printify-settings-form');
    const saveButton = $('#save-settings');
    const testEndpointButton = $('#test-endpoint');
    const testConnectionButton = $('#test-connection');
    const shopSelect = $('#printify_shop_id');

    // Test endpoint
    testEndpointButton.on('click', function() {
        $.ajax({
            url: printifySettings.ajaxurl,
            method: 'POST',
            data: {
                action: 'test_endpoint',
                security: printifySettings.nonce,
                endpoint: $('#printify_api_endpoint').val()
            },
            beforeSend: function() {
                testEndpointButton.text(printifySettings.i18n.testing);
            },
            success: function(response) {
                alert(response.data.message);
            }
        }).always(function() {
            testEndpointButton.text(printifySettings.i18n.testEndpoint);
        });
    });

    // Save settings
    form.on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: printifySettings.ajaxurl,
            method: 'POST',
            data: {
                action: 'save_settings',
                security: printifySettings.nonce,
                api_key: $('#printify_api_key').val(),
                shop_id: $('#printify_shop_id').val()
            },
            beforeSend: function() {
                saveButton.text(printifySettings.i18n.saving);
            },
            success: function(response) {
                alert(response.data.message);
            }
        }).always(function() {
            saveButton.text(printifySettings.i18n.save);
        });
    });

    // Load shops
    function loadShops() {
        $.ajax({
            url: printifySettings.ajaxurl,
            method: 'POST',
            data: {
                action: 'get_shops',
                security: printifySettings.nonce
            },
            success: function(response) {
                shopSelect.empty();
                shopSelect.append($('<option>').val('').text(printifySettings.i18n.selectShop));
                
                response.data.shops.forEach(function(shop) {
                    shopSelect.append($('<option>').val(shop.id).text(shop.title));
                });
            }
        });
    }

    // Load shops when API key is changed
    $('#printify_api_key').on('change', loadShops);
});