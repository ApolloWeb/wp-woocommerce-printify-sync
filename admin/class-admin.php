<?php
/**
 * User: ApolloWeb
 * Timestamp: 2025-02-20 04:03:56
 */

namespace ApolloWeb\WooCommercePrintifySync\Admin;

use ApolloWeb\WooCommercePrintifySync\Includes\PrintifyAPI;

class AdminSettings
{
    private $printify_api;

    public function __construct()
    {
        $api_key = get_option('printify_api_key');
        if ($api_key) {
            $this->printify_api = new PrintifyAPI($api_key);
        }

        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_settings_page()
    {
        add_options_page(
            'Printify Sync Settings',
            'Printify Sync',
            'manage_options',
            'printify-sync-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings()
    {
        register_setting('printify_sync_settings_group', 'printify_api_key');
        register_setting('printify_sync_settings_group', 'printify_selected_shop');

        add_settings_section(
            'printify_sync_settings_section',
            'Printify Sync Settings',
            [$this, 'settings_section_callback'],
            'printify-sync-settings'
        );

        add_settings_field(
            'printify_api_key',
            'Printify API Key',
            [$this, 'api_key_field_callback'],
            'printify-sync-settings',
            'printify_sync_settings_section'
        );

        add_settings_field(
            'printify_selected_shop',
            'Select Shop',
            [$this, 'selected_shop_field_callback'],
            'printify-sync-settings',
            'printify_sync_settings_section'
        );
    }

    public function settings_section_callback()
    {
        echo 'Configure the settings for the Printify Sync plugin.';
    }

    public function api_key_field_callback()
    {
        $api_key = get_option('printify_api_key');
        echo '<input type="text" name="printify_api_key" value="' . esc_attr($api_key) . '" />';
    }

    public function selected_shop_field_callback()
    {
        $selected_shop = get_option('printify_selected_shop');
        $shops = $this->get_printify_shops();

        if (count($shops) === 1) {
            $shop_id = key($shops);
            $shop_name = current($shops);
            echo '<input type="hidden" name="printify_selected_shop" value="' . esc_attr($shop_id) . '" />';
            echo '<p>' . esc_html($shop_name) . '</p>';
        } else {
            echo '<select name="printify_selected_shop">';
            foreach ($shops as $shop_id => $shop_name) {
                echo '<option value="' . esc_attr($shop_id) . '" ' . selected($selected_shop, $shop_id, false) . '>' . esc_html($shop_name) . '</option>';
            }
            echo '</select>';
        }
    }

    public function get_printify_shops()
    {
        if (!$this->printify_api) {
            return [];
        }

        $response = $this->printify_api->get_shops();
        $shops = [];

        if (isset($response['data'])) {
            foreach ($response['data'] as $shop) {
                $shops[$shop['id']] = $shop['title'];
            }
        }

        return $shops;
    }

    public function render_settings_page()
    {
        ?>
        <div class="wrap">
            <h1>Printify Sync Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('printify_sync_settings_group');
                do_settings_sections('printify-sync-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_admin_assets($hook)
    {
        if ($hook !== 'settings_page_printify-sync-settings') {
            return;
        }

        wp_enqueue_script('printify-sync-admin-js', WPS_PLUGIN_URL . 'assets/js/admin.js', [], '1.0.0', true);
        wp_enqueue_style('printify-sync-admin-css', WPS_PLUGIN_URL . 'assets/css/admin.css', [], '1.0.0');
    }
}