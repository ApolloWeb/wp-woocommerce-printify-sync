<div class="wrap">
    <h1><?php _e('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
    <form method="post" action="options.php">
        <?php settings_fields('printify_sync_settings'); ?>
        <?php do_settings_sections('printify_sync_settings'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Printify API Key', 'wp-woocommerce-printify-sync'); ?></th>
                <td><input type="text" name="printify_api_key" value="<?php echo esc_attr(get_option('printify_api_key')); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('WooCommerce API Key', 'wp-woocommerce-printify-sync'); ?></th>
                <td><input type="text" name="woocommerce_api_key" value="<?php echo esc_attr(get_option('woocommerce_api_key')); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Geolocation API Key', 'wp-woocommerce-printify-sync'); ?></th>
                <td><input type="text" name="geolocation_api_key" value="<?php echo esc_attr(get_option('geolocation_api_key')); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Currency API Key', 'wp-woocommerce-printify-sync'); ?></th>
                <td><input type="text" name="currency_api_key" value="<?php echo esc_attr(get_option('currency_api_key')); ?>" /></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>