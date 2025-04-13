<?php
/**
 * Settings page template
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$settings_service = new \ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsService();
$printify_settings = $settings_service->getPrintifySettings();
$chatgpt_settings = $settings_service->getChatGptSettings();
?>

<div class="wpwps-settings-container">
    <ul class="nav nav-tabs" id="settingsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="printify-tab" data-bs-toggle="tab" data-bs-target="#printify-content" type="button" role="tab" aria-controls="printify" aria-selected="true">
                <?php esc_html_e('Printify Settings', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="chatgpt-tab" data-bs-toggle="tab" data-bs-target="#chatgpt-content" type="button" role="tab" aria-controls="chatgpt" aria-selected="false">
                <?php esc_html_e('ChatGPT Settings', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="settingsTabContent">
        <!-- Printify Settings Tab -->
        <div class="tab-pane fade show active" id="printify-content" role="tabpanel" aria-labelledby="printify-tab">
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title"><?php esc_html_e('Printify API Configuration', 'wp-woocommerce-printify-sync'); ?></h5>
                </div>
                <div class="card-body">
                    <form id="printify-settings-form">
                        <div class="mb-3">
                            <label for="printify-api-key" class="form-label"><?php esc_html_e('API Key', 'wp-woocommerce-printify-sync'); ?> <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="printify-api-key" name="api_key" value="<?php echo esc_attr($printify_settings['api_key']); ?>" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggle-api-key">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text"><?php esc_html_e('Your Printify API key. You can get this from your Printify account settings.', 'wp-woocommerce-printify-sync'); ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="printify-api-endpoint" class="form-label"><?php esc_html_e('API Endpoint', 'wp-woocommerce-printify-sync'); ?></label>
                            <input type="url" class="form-control" id="printify-api-endpoint" name="api_endpoint" value="<?php echo esc_url($printify_settings['api_endpoint']); ?>" placeholder="https://api.printify.com/v1/">
                            <div class="form-text"><?php esc_html_e('The Printify API endpoint. Leave empty for default value.', 'wp-woocommerce-printify-sync'); ?></div>
                        </div>
                        
                        <div class="mb-3 d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" id="test-printify-connection" class="btn btn-info">
                                <i class="fas fa-plug"></i> <?php esc_html_e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </div>
                        
                        <div id="printify-shops-container" class="mb-3" style="<?php echo empty($printify_settings['shop_id']) ? 'display:none;' : ''; ?>">
                            <label for="printify-shop-id" class="form-label"><?php esc_html_e('Shop', 'wp-woocommerce-printify-sync'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="printify-shop-id" name="shop_id" <?php echo !empty($printify_settings['shop_id']) ? 'disabled' : ''; ?>>
                                <option value=""><?php esc_html_e('Select a shop', 'wp-woocommerce-printify-sync'); ?></option>
                            </select>
                            <div class="form-text"><?php esc_html_e('Select the Printify shop to connect with WooCommerce. This cannot be changed after saving.', 'wp-woocommerce-printify-sync'); ?></div>
                        </div>
                        
                        <?php if (!empty($printify_settings['shop_id'])) : ?>
                            <div class="mb-3">
                                <label class="form-label"><?php esc_html_e('Current Shop ID', 'wp-woocommerce-printify-sync'); ?></label>
                                <input type="text" class="form-control" value="<?php echo esc_attr($printify_settings['shop_id']); ?>" readonly>
                                <div class="form-text"><?php esc_html_e('Your currently connected Printify shop ID. This cannot be changed.', 'wp-woocommerce-printify-sync'); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info" role="alert" id="printify-test-result" style="display:none;"></div>
                        
                        <div class="mb-3 d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" id="save-printify-settings" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php esc_html_e('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- ChatGPT Settings Tab -->
        <div class="tab-pane fade" id="chatgpt-content" role="tabpanel" aria-labelledby="chatgpt-tab">
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title"><?php esc_html_e('ChatGPT API Configuration', 'wp-woocommerce-printify-sync'); ?></h5>
                </div>
                <div class="card-body">
                    <form id="chatgpt-settings-form">
                        <div class="mb-3">
                            <label for="chatgpt-api-key" class="form-label"><?php esc_html_e('API Key', 'wp-woocommerce-printify-sync'); ?> <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="chatgpt-api-key" name="api_key" value="<?php echo esc_attr($chatgpt_settings['api_key']); ?>" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggle-chatgpt-key">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text"><?php esc_html_e('Your ChatGPT API key. You can get this from your OpenAI account.', 'wp-woocommerce-printify-sync'); ?></div>
                        </div>
                        
                        <div class="mb-3 d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" id="test-chatgpt-connection" class="btn btn-info">
                                <i class="fas fa-plug"></i> <?php esc_html_e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="chatgpt-monthly-cap" class="form-label"><?php esc_html_e('Monthly Spend Cap ($)', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="number" class="form-control" id="chatgpt-monthly-cap" name="monthly_cap" min="0" step="0.01" value="<?php echo esc_attr($chatgpt_settings['monthly_cap']); ?>">
                                    <div class="form-text"><?php esc_html_e('Set your maximum monthly spend in USD.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="chatgpt-token-limit" class="form-label"><?php esc_html_e('Token Limit', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="number" class="form-control" id="chatgpt-token-limit" name="token_limit" min="0" value="<?php echo esc_attr($chatgpt_settings['token_limit']); ?>">
                                    <div class="form-text"><?php esc_html_e('Maximum number of tokens per AI request.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="chatgpt-temperature" class="form-label"><?php esc_html_e('Temperature', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="number" class="form-control" id="chatgpt-temperature" name="temperature" min="0" max="1" step="0.1" value="<?php echo esc_attr($chatgpt_settings['temperature']); ?>">
                                    <div class="form-text"><?php esc_html_e('Controls randomness (0-1). Lower is more deterministic.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <?php esc_html_e('Estimate Monthly Cost', 'wp-woocommerce-printify-sync'); ?>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estimated-tickets" class="form-label"><?php esc_html_e('Estimated Monthly Tickets', 'wp-woocommerce-printify-sync'); ?></label>
                                            <input type="number" class="form-control" id="estimated-tickets" name="estimated_tickets" min="0" value="100">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3 d-flex flex-column h-100 justify-content-end">
                                            <button type="button" id="calculate-cost" class="btn btn-secondary mt-4">
                                                <i class="fas fa-calculator"></i> <?php esc_html_e('Calculate', 'wp-woocommerce-printify-sync'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info" role="alert" id="cost-estimate-result" style="display:none;">
                                    <i class="fas fa-info-circle"></i> <?php esc_html_e('Estimated monthly cost: ', 'wp-woocommerce-printify-sync'); ?> <span id="estimated-cost">$0.00</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" role="alert" id="chatgpt-test-result" style="display:none;"></div>
                        
                        <div class="mb-3 d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" id="save-chatgpt-settings" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php esc_html_e('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
