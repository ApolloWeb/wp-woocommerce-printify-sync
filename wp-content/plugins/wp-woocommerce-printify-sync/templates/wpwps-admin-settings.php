?>
<div class="wrap">
    <h1><?php echo esc_html($title); ?></h1>
    
    <div class="wpwps-settings-container">
        <form id="wpwpps-settings-form" class="wpwps-form">
            <!-- Printify API Settings -->
            <div class="wpwps-section">
                <h2><?php _e('Printify API Settings', 'wp-woocommerce-printify-sync'); ?></h2>
                
                <div class="wpwps-field">
                    <label for="printify_api_key"><?php _e('API Key', 'wp-woocommerce-printify-sync'); ?></label>
                    <input type="password" id="printify_api_key" name="printify_api_key" 
                           value="<?php echo esc_attr($printify_api_key); ?>" required>
                </div>

                <div class="wpwps-field">
                    <label for="api_endpoint"><?php _e('API Endpoint', 'wp-woocommerce-printify-sync'); ?></label>
                    <input type="url" id="api_endpoint" name="api_endpoint" 
                           value="<?php echo esc_url($api_endpoint); ?>" required>
                </div>

                <button type="button" id="test-connection" class="button button-secondary">
                    <?php _e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <div id="test-connection-result" class="wpwps-notice"></div>

                <div class="wpwps-field">
                    <label for="shop_id"><?php _e('Select Shop', 'wp-woocommerce-printify-sync'); ?></label>
                    <select id="shop_id" name="shop_id" <?php echo $shop_id ? 'disabled' : ''; ?>>
                        <?php if ($shop_id): ?>
                            <option value="<?php echo esc_attr($shop_id); ?>" selected>
                                <?php echo esc_html($shop_id); ?>
                            </option>
                        <?php else: ?>
                            <option value=""><?php _e('Test connection to load shops', 'wp-woocommerce-printify-sync'); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- ChatGPT Settings -->
            <div class="wpwps-section">
                <h2><?php _e('ChatGPT Settings', 'wp-woocommerce-printify-sync'); ?></h2>
                
                <div class="wpwps-field">
                    <label for="monthly_spend_cap"><?php _e('Monthly Spend Cap ($)', 'wp-woocommerce-printify-sync'); ?></label>
                    <input type="number" id="monthly_spend_cap" name="monthly_spend_cap" 
                           value="<?php echo esc_attr($monthly_spend_cap); ?>" step="0.01" min="0">
                </div>

                <div class="wpwps-field">
                    <label for="tokens"><?php _e('Number of Tokens', 'wp-woocommerce-printify-sync'); ?></label>
                    <input type="number" id="tokens" name="tokens" 
                           value="<?php echo esc_attr($tokens); ?>" min="1">
                </div>

                <div class="wpwps-field">
                    <label for="temperature"><?php _e('Temperature', 'wp-woocommerce-printify-sync'); ?></label>
                    <input type="number" id="temperature" name="temperature" 
                           value="<?php echo esc_attr($temperature); ?>" step="0.1" min="0" max="1">
                </div>

                <button type="button" id="test-monthly-estimate" class="button button-secondary">
                    <?php _e('Calculate Monthly Estimate', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <div id="monthly-estimate-result" class="wpwps-notice"></div>
            </div>

            <div class="wpwps-submit">
                <button type="submit" class="button button-primary">
                    <?php _e('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <div id="save-settings-result" class="wpwps-notice"></div>
            </div>
        </form>
    </div>
</div>
