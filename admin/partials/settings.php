<?php
/**
 * Settings template for WooCommerce Printify Sync Plugin
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */
?>
<div class="wrap">
    <h1><?php esc_html_e('Settings', 'wp-woocommerce-printify-sync'); ?></h1>
    <form id="wpwps-settings-form" method="post">
        <div class="card">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="api_key"><?php esc_html_e('Printify API Key', 'wp-woocommerce-printify-sync'); ?></label>
                    </th>
                    <td>
                        <input name="api_key" id="api_key" type="text" class="regular-text" value="<?php echo esc_attr(get_option('wpwps_api_key')); ?>" />
                        <p class="description"><?php esc_html_e('Enter your Printify API Key.', 'wp-woocommerce-printify-sync'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="shop_id"><?php esc_html_e('Printify Shop ID', 'wp-woocommerce-printify-sync'); ?></label>
                    </th>
                    <td>
                        <input name="shop_id" id="shop_id" type="number" class="small-text" value="<?php echo esc_attr(get_option('wpwps_shop_id')); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sync_frequency"><?php esc_html_e('Sync Frequency', 'wp-woocommerce-printify-sync'); ?></label>
                    </th>
                    <td>
                        <select name="sync_frequency" id="sync_frequency">
                            <option value="twicedaily" <?php selected(get_option('wpwps_sync_frequency'), 'twicedaily'); ?>><?php esc_html_e('Twice Daily', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="hourly" <?php selected(get_option('wpwps_sync_frequency'), 'hourly'); ?>><?php esc_html_e('Hourly', 'wp-woocommerce-printify-sync'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" id="save-settings-btn" class="button button-primary">
                    <?php esc_html_e('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                </button>
            </p>
            <?php wp_nonce_field('wpwps_nonce', 'wpwps_nonce_field'); ?>
            <div id="settings-response"></div>
        </div>
    </form>
</div>