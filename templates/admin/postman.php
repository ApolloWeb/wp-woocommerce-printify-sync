<div class="wrap">
    <h1>Postman Integration</h1>
    <form method="post" action="">
        <?php wp_nonce_field('postman_integration', 'postman_integration_nonce'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Postman Collection URL</th>
                <td>
                    <input type="url" name="postman_collection_url" value="<?php echo esc_url(get_option('postman_collection_url')); ?>" required />
                    <p class="description">Enter the URL of the Postman collection to run tests.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Postman Environment Variables</th>
                <td>
                    <textarea name="postman_environment_variables" rows="10" cols="50"><?php echo esc_textarea(get_option('postman_environment_variables')); ?></textarea>
                    <p class="description">Enter environment variables in JSON format. Example: {"variable_name": "value"}</p>
                </td>
            </tr>
        </table>
        <?php submit_button('Run Postman Tests'); ?>
    </form>
    <div id="postman-test-results">
        <h2>Test Results</h2>
        <!-- Placeholder for displaying test results -->
    </div>
</div>