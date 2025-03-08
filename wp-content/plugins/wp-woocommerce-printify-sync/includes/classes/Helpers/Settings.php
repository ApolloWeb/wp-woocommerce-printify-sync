<?php

namespace ApolloWeb\WpWooCommercePrintifySync\Helpers;

class Settings
{
    public static function get($key, $default = null)
    {
        return get_option($key, $default);
    }

    public static function set($key, $value)
    {
        return update_option($key, $value);
    }

    public static function registerSettings()
    {
        register_setting('wpwpsp_settings', 'printify_api_url');
        register_setting('wpwpsp_settings', 'printify_api_key');
    }

    public static function addSettingsPage()
    {
        add_options_page(
            'Printify Sync Settings',
            'Printify Sync',
            'manage_options',
            'wpwpsp_settings',
            [self::class, 'renderSettingsPage']
        );
    }

    public static function renderSettingsPage()
    {
        ?>
        <div class="wrap">
            <h1>Printify Sync Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wpwpsp_settings');
                do_settings_sections('wpwpsp_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Printify API URL</th>
                        <td><input type="text" name="printify_api_url" value="<?php echo esc_attr(self::get('printify_api_url')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Printify API Key</th>
                        <td><input type="text" name="printify_api_key" value="<?php echo esc_attr(self::get('printify_api_key')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

add_action('admin_init', [Settings::class, 'registerSettings']);
add_action('admin_menu', [Settings::class, 'addSettingsPage']);