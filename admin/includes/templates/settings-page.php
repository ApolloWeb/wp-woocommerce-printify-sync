<div class="wrap">
    <h1><?php esc_html_e( 'Printify Sync Settings', 'wp-woocommerce-printify-sync' ); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields( 'printify-sync' );
        do_settings_sections( 'printify-sync' );
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="printify_api_key"><?php esc_html_e( 'Printify API Key', 'wp-woocommerce-printify-sync' ); ?></label>
                    </th>
                    <td>
                        <input name="printify_api_key" type="text" id="printify_api_key" value="<?php echo esc_attr( get_option('printify_api_key') ); ?>" class="regular-text">
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
        submit_button();
        ?>
    </form>
</div>