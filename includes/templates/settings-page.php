<div class="wrap wwps-settings">
    <h1><?php esc_html_e( 'Printify Sync Settings', 'wwps' ); ?></h1>
    <form method="post" action="options.php">
        <?php settings_fields('wwps-settings-group'); ?>
        <?php do_settings_sections('wwps-settings-group'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Printify API Key', 'wwps'); ?></th>
                <td><input type="text" name="wwps_printify_api_key" value="<?php echo esc_attr(get_option('wwps_printify_api_key')); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Printify API Endpoint', 'wwps'); ?></th>
                <td><input type="text" name="wwps_printify_api_endpoint" value="<?php echo esc_attr(get_option('wwps_printify_api_endpoint', 'https://api.printify.com/v1/')); ?>" /></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
    <?php include_once WWPS_PLUGIN_DIR . 'includes/templates/shops-section.php'; ?>
</div>