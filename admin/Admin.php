/**
 * Admin class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 * Time: 02:20:39
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

namespace ApolloWeb\WooCommercePrintifySync;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    public function __construct()
    {
        // Load the Helper class
        require_once plugin_dir_path(__FILE__) . 'includes/Helper.php';

        add_action('admin_menu', [$this, 'registerSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    }

    public function registerSettingsPage()
    {
        add_options_page(
            __('Printify Sync Settings', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function registerSettings()
    {
        register_setting('printify_sync_settings', 'printify_api_key');
        register_setting('printify_sync_settings', 'printify_selected_shop');
    }

    public function renderSettingsPage()
    {
        // Corrected template directory path
        $template_dir = plugin_dir_path(__FILE__) . 'templates/';
        $settings_page = $template_dir . 'settings-page.php';
        $shops_section = $template_dir . 'shops-section.php';
        $products_section = $template_dir . 'products-section.php';

        if (file_exists($settings_page)) {
            include $settings_page;
        } else {
            echo '<div class="error"><p>' . esc_html__('Template file missing: settings-page.php', 'wp-woocommerce-printify-sync') . '</p></div>';
        }

        if (file_exists($shops_section)) {
            include $shops_section;
        } else {
            echo '<div class="error"><p>' . esc_html__('Template file missing: shops-section.php', 'wp-woocommerce-printify-sync') . '</p></div>';
        }

        if (file_exists($products_section)) {
            include $products_section;
        } else {
            echo '<div class="error"><p>' . esc_html__('Template file missing: products-section.php', 'wp-woocommerce-printify-sync') . '</p></div>';
        }
    }
}
