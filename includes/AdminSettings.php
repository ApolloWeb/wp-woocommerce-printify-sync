/**
 * AdminSettings class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

declare(strict_types=1);

namespace ApolloWeb\WooCommercePrintifySync;

if (!defined('ABSPATH')) {
    exit;
}


class AdminSettings
{
    public static function init() {
        // Hook into the admin menu
        add_action('admin_menu', [__CLASS__, 'registerSettingsPage']);
        add_action('admin_init', [__CLASS__, 'registerSettings']);
    }

    public static function registerSettingsPage(): void {
        add_options_page(
            'Printify Sync Settings',
            'Printify Sync',
            'manage_options',
            'printify-sync-settings',
            [__CLASS__, 'renderSettingsPage']
        );
    }

    public static function registerSettings(): void {
        register_setting('printify_sync_settings', 'printify_api_token');
    }

    public static function renderSettingsPage(): void {
        ?>
        <div class="wrap">
            <h1>Printify Sync Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('printify_sync_settings');
                do_settings_sections('printify_sync_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Printify API Token</th>
                        <td>
                            <input type="text" name="printify_api_token" value="<?php echo esc_attr(get_option('printify_api_token')); ?>" class="regular-text"/>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
