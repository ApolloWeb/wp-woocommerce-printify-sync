<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h1><?php echo esc_html__('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <div class="card">
        <div class="card-body">
            <form id="wpwps-settings-form">
                <div class="mb-3">
                    <label for="printify_api_key" class="form-label">
                        <?php echo esc_html__('Printify API Key', 'wp-woocommerce-printify-sync'); ?>
                    </label>
                    <input type="password" class="form-control" id="printify_api_key" name="printify_api_key" required>
                </div>

                <div class="mb-3">
                    <label for="printify_endpoint" class="form-label">
                        <?php echo esc_html__('API Endpoint', 'wp-woocommerce-printify-sync'); ?>
                    </label>
                    <input type="url" class="form-control" id="printify_endpoint" name="printify_endpoint" 
                           value="https://api.printify.com/v1/" required>
                </div>

                <div class="mb-3">
                    <button type="button" id="test-connection" class="btn btn-secondary">
                        <?php echo esc_html__('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                </div>

                <div class="mb-3">
                    <label for="shop_id" class="form-label">
                        <?php echo esc_html__('Select Shop', 'wp-woocommerce-printify-sync'); ?>
                    </label>
                    <select class="form-select" id="shop_id" name="shop_id" disabled>
                        <option value=""><?php echo esc_html__('Test connection first', 'wp-woocommerce-printify-sync'); ?></option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="chatgpt_api_key" class="form-label">
                        <?php echo esc_html__('ChatGPT API Key', 'wp-woocommerce-printify-sync'); ?>
                    </label>
                    <input type="password" class="form-control" id="chatgpt_api_key" name="chatgpt_api_key">
                </div>

                <div class="mb-3">
                    <label for="monthly_spend_cap" class="form-label">
                        <?php echo esc_html__('Monthly Spend Cap ($)', 'wp-woocommerce-printify-sync'); ?>
                    </label>
                    <input type="number" class="form-control" id="monthly_spend_cap" name="monthly_spend_cap" min="0">
                </div>

                <div class="mb-3">
                    <label for="temperature" class="form-label">
                        <?php echo esc_html__('Temperature', 'wp-woocommerce-printify-sync'); ?>
                    </label>
                    <input type="range" class="form-range" id="temperature" name="temperature" min="0" max="1" step="0.1">
                    <span id="temperature-value">0.7</span>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?php echo esc_html__('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                </button>
            </form>
        </div>
    </div>
</div>
