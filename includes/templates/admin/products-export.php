<div class="wrap">
    <h1>Export Products to CSV</h1>
    <form method="post" action="">
        <?php wp_nonce_field('printify_export', 'printify_export_nonce'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Export Options</th>
                <td>
                    <label><input type="checkbox" name="include_stock" value="1" /> Include Stock Levels</label><br>
                    <label><input type="checkbox" name="include_prices" value="1" /> Include Prices</label>
                </td>
            </tr>
        </table>
        <?php submit_button('Export Products'); ?>
    </form>
</div>