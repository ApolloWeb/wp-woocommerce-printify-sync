<div class="wrap">
    <h1>Exchange Rates</h1>
    <form method="post" action="">
        <?php wp_nonce_field('update_exchange_rates', 'update_exchange_rates_nonce'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Base Currency</th>
                <td>
                    <input type="text" name="base_currency" value="<?php echo esc_attr(get_option('base_currency', 'USD')); ?>" required />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Exchange Rates</th>
                <td>
                    <textarea name="exchange_rates" rows="10" cols="50"><?php echo esc_textarea(get_option('exchange_rates')); ?></textarea>
                    <p class="description">Enter exchange rates in JSON format. Example: {"EUR": 0.85, "GBP": 0.75}</p>
                </td>
            </tr>
        </table>
        <?php submit_button('Update Exchange Rates'); ?>
    </form>
</div>