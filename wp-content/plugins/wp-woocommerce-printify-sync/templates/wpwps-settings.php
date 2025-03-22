<?php
/**
 * Settings template.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap wpwps-admin-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="wp-heading-inline">
            <i class="fas fa-cog"></i> <?php echo esc_html__('Printify Sync - Settings', 'wp-woocommerce-printify-sync'); ?>
        </h1>
        
        <?php if (!empty($shop_name)) : ?>
        <div class="wpwps-shop-info">
            <span class="wpwps-shop-badge">
                <i class="fas fa-store"></i> <?php echo esc_html($shop_name); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <hr class="wp-header-end">
    
    <div class="wpwps-settings-container">
        <form id="wpwps-settings-form" method="post">
            <div class="wpwps-card">
                <div class="card-header">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="printify-tab" data-bs-toggle="tab" data-bs-target="#printify" type="button" role="tab" aria-controls="printify" aria-selected="true">
                                <i class="fas fa-plug"></i> <?php esc_html_e('Printify API', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="chatgpt-tab" data-bs-toggle="tab" data-bs-target="#chatgpt" type="button" role="tab" aria-controls="chatgpt" aria-selected="false">
                                <i class="fas fa-robot"></i> <?php esc_html_e('ChatGPT API', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="webhooks-tab" data-bs-toggle="tab" data-bs-target="#webhooks" type="button" role="tab" aria-controls="webhooks" aria-selected="false">
                                <i class="fas fa-bell"></i> <?php esc_html_e('Webhooks', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="false">
                                <i class="fas fa-sliders-h"></i> <?php esc_html_e('General', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="wpwps-settings-content">
                        <!-- Printify API Tab -->
                        <div class="tab-pane fade show active" id="printify" role="tabpanel" aria-labelledby="printify-tab">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="printify_api_key" class="form-label">
                                        <?php esc_html_e('Printify API Key', 'wp-woocommerce-printify-sync'); ?>
                                        <span class="required">*</span>
                                    </label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control" id="printify_api_key" name="printify_api_key" value="<?php echo esc_attr($api_key ?? ''); ?>" required>
                                        <button type="button" class="password-toggle" tabindex="-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text"><?php esc_html_e('Enter your Printify API key from your Printify account.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="printify_api_endpoint" class="form-label"><?php esc_html_e('API Endpoint', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="text" class="form-control" id="printify_api_endpoint" name="printify_api_endpoint" value="<?php echo esc_attr($api_endpoint); ?>">
                                    <div class="form-text"><?php esc_html_e('Default: https://api.printify.com/v1/', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <button type="button" id="test-printify-connection" class="btn btn-primary">
                                        <i class="fas fa-plug"></i> <?php esc_html_e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                    <div id="printify-connection-result" class="mt-2"></div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="printify_shop_id" class="form-label"><?php esc_html_e('Select Shop', 'wp-woocommerce-printify-sync'); ?></label>
                                    <select class="form-select" id="printify_shop_id" name="printify_shop_id" <?php echo !empty($shop_id) ? 'disabled' : ''; ?>>
                                        <option value=""><?php esc_html_e('Select a shop', 'wp-woocommerce-printify-sync'); ?></option>
                                        <?php if (!empty($shop_id)) : ?>
                                            <option value="<?php echo esc_attr($shop_id); ?>" selected><?php echo esc_html($shop_name); ?></option>
                                        <?php endif; ?>
                                    </select>
                                    <div class="form-text"><?php esc_html_e('Select the Printify shop to sync with WooCommerce.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                                
                                <?php if (!empty($shop_id)) : ?>
                                <div class="col-md-6">
                                    <div class="alert alert-info mt-4">
                                        <i class="fas fa-info-circle"></i>
                                        <?php esc_html_e('Shop ID is locked once selected. To change shops, you must reset the plugin.', 'wp-woocommerce-printify-sync'); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <input type="hidden" id="printify_shop_name" name="printify_shop_name" value="<?php echo esc_attr($shop_name); ?>">
                        </div>
                        
                        <!-- ChatGPT API Tab -->
                        <div class="tab-pane fade" id="chatgpt" role="tabpanel" aria-labelledby="chatgpt-tab">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="chatgpt_api_key" class="form-label"><?php esc_html_e('ChatGPT API Key', 'wp-woocommerce-printify-sync'); ?></label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control" id="chatgpt_api_key" name="chatgpt_api_key" value="<?php echo esc_attr($chatgpt_api_key ?? ''); ?>">
                                        <button type="button" class="password-toggle" tabindex="-1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text"><?php esc_html_e('Enter your OpenAI API key for ChatGPT functionality.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="chatgpt_temperature" class="form-label">
                                        <?php esc_html_e('Model Temperature', 'wp-woocommerce-printify-sync'); ?>
                                        <span id="temperature_value"><?php echo esc_html($temperature); ?></span>
                                    </label>
                                    <input type="range" class="range-slider form-range" id="chatgpt_temperature" name="chatgpt_temperature" min="0" max="1" step="0.1" value="<?php echo esc_attr($temperature); ?>">
                                    <div class="form-text"><?php esc_html_e('Controls randomness: lower values give more deterministic results.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="chatgpt_monthly_budget" class="form-label"><?php esc_html_e('Monthly Token Budget', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="number" class="form-control" id="chatgpt_monthly_budget" name="chatgpt_monthly_budget" value="<?php echo esc_attr($monthly_budget); ?>" min="1000">
                                    <div class="form-text"><?php esc_html_e('Set a monthly token budget limit for ChatGPT API usage.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <button type="button" id="test-chatgpt-connection" class="btn btn-primary">
                                        <i class="fas fa-plug"></i> <?php esc_html_e('Test Connection & Estimate Costs', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div id="chatgpt-connection-result" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Webhooks Tab -->
                        <?php require WPWPS_PLUGIN_DIR . 'templates/partials/settings/webhooks-tab.php'; ?>

                        <!-- General Settings Tab -->
                        <div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="log_level" class="form-label"><?php esc_html_e('Log Level', 'wp-woocommerce-printify-sync'); ?></label>
                                    <select class="form-select" id="log_level" name="log_level">
                                        <option value="emergency" <?php selected($log_level, 'emergency'); ?>><?php esc_html_e('Emergency', 'wp-woocommerce-printify-sync'); ?></option>
                                        <option value="alert" <?php selected($log_level, 'alert'); ?>><?php esc_html_e('Alert', 'wp-woocommerce-printify-sync'); ?></option>
                                        <option value="critical" <?php selected($log_level, 'critical'); ?>><?php esc_html_e('Critical', 'wp-woocommerce-printify-sync'); ?></option>
                                        <option value="error" <?php selected($log_level, 'error'); ?>><?php esc_html_e('Error', 'wp-woocommerce-printify-sync'); ?></option>
                                        <option value="warning" <?php selected($log_level, 'warning'); ?>><?php esc_html_e('Warning', 'wp-woocommerce-printify-sync'); ?></option>
                                        <option value="notice" <?php selected($log_level, 'notice'); ?>><?php esc_html_e('Notice', 'wp-woocommerce-printify-sync'); ?></option>
                                        <option value="info" <?php selected($log_level, 'info'); ?>><?php esc_html_e('Info', 'wp-woocommerce-printify-sync'); ?></option>
                                        <option value="debug" <?php selected($log_level, 'debug'); ?>><?php esc_html_e('Debug', 'wp-woocommerce-printify-sync'); ?></option>
                                    </select>
                                    <div class="form-text"><?php esc_html_e('Set the minimum level of messages to log. Debug is most verbose.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" id="save-settings" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php esc_html_e('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                    <span id="save-settings-result" class="ms-2"></span>
                </div>
            </div>
        </form>
    </div>
</div>
