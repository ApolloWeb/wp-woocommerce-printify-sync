<div class="wrap">
    <h1>Environment Settings</h1>
    <form method="post" action="options.php">
        <?php settings_fields('environment_settings'); ?>
        <?php do_settings_sections('environment_settings'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Environment Mode</th>
                <td>
                    <select name="environment_mode" id="environment_mode">
                        <option value="live" <?php selected(get_option('environment_mode'), 'live'); ?>>Live</option>
                        <option value="development" <?php selected(get_option('environment_mode'), 'development'); ?>>Development</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>