<div class="wrap wpwps-settings">
    <h1><i class="fas fa-cog"></i> <?php esc_html_e('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <!-- Settings Navigation Tabs -->
    <nav class="nav-tab-wrapper wp-clearfix" id="wpwps-settings-tabs">
        <a href="#printify-settings" class="nav-tab nav-tab-active" data-tab="printify-settings">
            <i class="fas fa-tshirt"></i> <?php esc_html_e('Printify', 'wp-woocommerce-printify-sync'); ?>
        </a>
        <a href="#chatgpt-settings" class="nav-tab" data-tab="chatgpt-settings">
            <i class="fas fa-robot"></i> <?php esc_html_e('ChatGPT Integration', 'wp-woocommerce-printify-sync'); ?>
        </a>
    </nav>
    
    <form id="wpwps-settings-form">
        <!-- Printify Settings Section -->
        <div id="printify-settings" class="wpwps-settings-section active">
            <div class="wpwps-section-header">
                <h2><?php esc_html_e('Printify API Settings', 'wp-woocommerce-printify-sync'); ?></h2>
                <p class="description"><?php esc_html_e('Configure your Printify API credentials and shop settings.', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
            
            <!-- Printify API Key -->
            <div class="mb-3">
                <label for="printify_api_key" class="form-label"><?php esc_html_e('Printify API Key', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="password" class="form-control" id="printify_api_key" name="printify_api_key" required>
            </div>
            
            <!-- API Endpoint -->
            <div class="mb-3">
                <label for="printify_api_endpoint" class="form-label"><?php esc_html_e('API Endpoint', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="text" class="form-control" id="printify_api_endpoint" name="printify_api_endpoint" value="https://api.printify.com/v1/">
            </div>
            
            <!-- Test Connection Button -->
            <button type="button" class="btn btn-primary" id="wpwps-test-connection"><?php esc_html_e('Test Connection', 'wp-woocommerce-printify-sync'); ?></button>
            <div id="wpwps-test-result" class="mt-2"></div>
            
            <!-- Shop Dropdown (populated by JS after test) -->
            <div class="mb-3" id="wpwps-shop-select-container" style="display:none;">
                <label for="printify_shop_id" class="form-label"><?php esc_html_e('Select Shop', 'wp-woocommerce-printify-sync'); ?></label>
                <select class="form-select" id="printify_shop_id" name="printify_shop_id"></select>
            </div>
        </div>
        
        <!-- ChatGPT Settings Section -->
        <div id="chatgpt-settings" class="wpwps-settings-section">
            <div class="wpwps-section-header">
                <h2><?php esc_html_e('ChatGPT Integration', 'wp-woocommerce-printify-sync'); ?></h2>
                <p class="description"><?php esc_html_e('Configure OpenAI ChatGPT for product description generation and other AI features.', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
            
            <!-- ChatGPT API Key -->
            <div class="mb-3">
                <label for="chatgpt_api_key" class="form-label"><?php esc_html_e('ChatGPT API Key', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="password" class="form-control" id="chatgpt_api_key" name="chatgpt_api_key">
            </div>
            
            <!-- Monthly Spend Cap -->
            <div class="mb-3">
                <label class="form-label"><?php esc_html_e('Monthly Spend Cap ($)', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="number" class="form-control" id="chatgpt_monthly_cap" name="chatgpt_monthly_cap" min="0">
                <small class="form-text text-muted"><?php esc_html_e('Set to 0 for no limit. This helps control API usage costs.', 'wp-woocommerce-printify-sync'); ?></small>
            </div>
            
            <!-- Max Tokens -->
            <div class="mb-3">
                <label class="form-label"><?php esc_html_e('Max Tokens', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="number" class="form-control" id="chatgpt_max_tokens" name="chatgpt_max_tokens" min="1" value="1024">
                <small class="form-text text-muted"><?php esc_html_e('Maximum number of tokens per request. Higher values allow longer text generation.', 'wp-woocommerce-printify-sync'); ?></small>
            </div>
            
            <!-- Temperature Slider -->
            <div class="mb-3">
                <label class="form-label" for="chatgpt_temperature">
                    <?php esc_html_e('Temperature', 'wp-woocommerce-printify-sync'); ?>: 
                    <span id="temperature-value">0.7</span>
                </label>
                <input type="range" class="form-range" id="chatgpt_temperature" name="chatgpt_temperature" 
                    min="0" max="1" step="0.1" value="0.7">
                <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted"><?php esc_html_e('More deterministic', 'wp-woocommerce-printify-sync'); ?></small>
                    <small class="text-muted"><?php esc_html_e('More creative', 'wp-woocommerce-printify-sync'); ?></small>
                </div>
                <small class="form-text text-muted"><?php esc_html_e('Controls randomness: Lower values produce more focused and deterministic content, higher values produce more creative and varied content.', 'wp-woocommerce-printify-sync'); ?></small>
            </div>
            
            <!-- Test Button -->
            <button type="button" class="btn btn-secondary" id="wpwps-test-chatgpt"><?php esc_html_e('Test Monthly Estimate', 'wp-woocommerce-printify-sync'); ?></button>
            <div id="wpwps-chatgpt-result" class="mt-2"></div>
        </div>
        
        <hr>
        <button type="submit" class="btn btn-success mt-4"><?php esc_html_e('Save All Settings', 'wp-woocommerce-printify-sync'); ?></button>
    </form>
</div>
