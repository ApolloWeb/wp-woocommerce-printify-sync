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
<div class="wrap wpwps-admin-page wpwps-settings-page">
    <h1 class="wp-heading-inline">
        <i class="fas fa-tshirt"></i> <?php esc_html_e('Printify Sync - Settings', 'wp-woocommerce-printify-sync'); ?>
    </h1>
    
    <?php if (!empty($shop_name)) : ?>
        <p class="wpwps-shop-name">
            <i class="fas fa-store"></i> <?php echo esc_html($shop_name); ?>
        </p>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <div class="wpwps-alert-container"></div>
    
    <div class="wpwps-settings-container">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-cogs"></i> <?php esc_html_e('API Configuration', 'wp-woocommerce-printify-sync'); ?></h2>
                    </div>
                    <div class="card-body">
                        <form id="wpwps-settings-form" method="post">
                            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                            
                            <h4><?php esc_html_e('Printify API Settings', 'wp-woocommerce-printify-sync'); ?></h4>
                            
                            <div class="mb-3">
                                <label for="api_key" class="form-label"><?php esc_html_e('API Key', 'wp-woocommerce-printify-sync'); ?> <span class="required">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="api_key">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text"><?php esc_html_e('Enter your Printify API key. Get it from your Printify dashboard.', 'wp-woocommerce-printify-sync'); ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="api_endpoint" class="form-label"><?php esc_html_e('API Endpoint', 'wp-woocommerce-printify-sync'); ?></label>
                                <input type="url" class="form-control" id="api_endpoint" name="api_endpoint" value="<?php echo esc_url($api_endpoint); ?>">
                                <div class="form-text"><?php esc_html_e('Default: https://api.printify.com/v1/', 'wp-woocommerce-printify-sync'); ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" id="test-printify-connection" class="btn btn-info">
                                    <i class="fas fa-plug"></i> <?php esc_html_e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                            </div>
                            
                            <div class="mb-3 shop-selection-container" style="display: none;">
                                <label for="shop_id" class="form-label"><?php esc_html_e('Select Shop', 'wp-woocommerce-printify-sync'); ?> <span class="required">*</span></label>
                                <select class="form-select" id="shop_id" name="shop_id" required>
                                    <option value=""><?php esc_html_e('Select a shop', 'wp-woocommerce-printify-sync'); ?></option>
                                </select>
                                <input type="hidden" id="shop_name" name="shop_name" value="<?php echo esc_attr($shop_name); ?>">
                            </div>
                            
                            <h4 class="mt-4"><?php esc_html_e('Order Sync Settings', 'wp-woocommerce-printify-sync'); ?></h4>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="sync_external_order_id" name="sync_external_order_id" value="1" <?php checked(isset($settings['sync_external_order_id']) && $settings['sync_external_order_id']); ?>>
                                    <label class="form-check-label" for="sync_external_order_id">
                                        <?php esc_html_e('Use WooCommerce Order ID as External ID', 'wp-woocommerce-printify-sync'); ?>
                                    </label>
                                </div>
                                <div class="form-text">
                                    <?php esc_html_e('When enabled, WooCommerce order IDs will be sent as external order IDs to Printify for better order tracking and reference.', 'wp-woocommerce-printify-sync'); ?>
                                </div>
                            </div>
                            
                            <h4 class="mt-4"><?php esc_html_e('ChatGPT API Settings', 'wp-woocommerce-printify-sync'); ?></h4>
                            
                            <div class="mb-3">
                                <label for="gpt_api_key" class="form-label"><?php esc_html_e('OpenAI API Key', 'wp-woocommerce-printify-sync'); ?></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="gpt_api_key" name="gpt_api_key" value="<?php echo esc_attr($gpt_api_key); ?>">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="gpt_api_key">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text"><?php esc_html_e('Enter your OpenAI API key for ChatGPT integration.', 'wp-woocommerce-printify-sync'); ?></div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="gpt_tokens" class="form-label"><?php esc_html_e('Max Tokens', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="number" class="form-control" id="gpt_tokens" name="gpt_tokens" value="<?php echo esc_attr($gpt_tokens); ?>" min="100" max="4000">
                                    <div class="form-text"><?php esc_html_e('Maximum tokens per request (100-4000)', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="gpt_temperature" class="form-label"><?php esc_html_e('Temperature', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="number" class="form-control" id="gpt_temperature" name="gpt_temperature" value="<?php echo esc_attr($gpt_temperature); ?>" min="0" max="1" step="0.1">
                                    <div class="form-text"><?php esc_html_e('Creativity level (0-1)', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="gpt_budget" class="form-label"><?php esc_html_e('Monthly Budget', 'wp-woocommerce-printify-sync'); ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="gpt_budget" name="gpt_budget" value="<?php echo esc_attr($gpt_budget); ?>" min="1" step="1">
                                    </div>
                                    <div class="form-text"><?php esc_html_e('Maximum monthly spending limit', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" id="test-gpt-connection" class="btn btn-info">
                                    <i class="fas fa-plug"></i> <?php esc_html_e('Test GPT Connection', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                            </div>
                            
                            <div class="gpt-cost-estimate-container mt-3" style="display: none;">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php esc_html_e('Estimated GPT Cost', 'wp-woocommerce-printify-sync'); ?></h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-0"><?php esc_html_e('Daily: $', 'wp-woocommerce-printify-sync'); ?> <span id="gpt-cost-daily">0.00</span></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-0"><?php esc_html_e('Monthly: $', 'wp-woocommerce-printify-sync'); ?> <span id="gpt-cost-monthly">0.00</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" id="save-settings" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php esc_html_e('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-info-circle"></i> <?php esc_html_e('Connection Status', 'wp-woocommerce-printify-sync'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="connection-status">
                            <h5><?php esc_html_e('Printify API', 'wp-woocommerce-printify-sync'); ?></h5>
                            <p class="status">
                                <?php if (!empty($api_key) && !empty($shop_id)) : ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> <?php esc_html_e('Connected', 'wp-woocommerce-printify-sync'); ?></span>
                                <?php else : ?>
                                    <span class="badge bg-warning"><i class="fas fa-exclamation-circle"></i> <?php esc_html_e('Not configured', 'wp-woocommerce-printify-sync'); ?></span>
                                <?php endif; ?>
                            </p>
                            
                            <h5 class="mt-4"><?php esc_html_e('ChatGPT API', 'wp-woocommerce-printify-sync'); ?></h5>
                            <p class="status">
                                <?php if (!empty($gpt_api_key)) : ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> <?php esc_html_e('Connected', 'wp-woocommerce-printify-sync'); ?></span>
                                <?php else : ?>
                                    <span class="badge bg-secondary"><i class="fas fa-exclamation-circle"></i> <?php esc_html_e('Not configured', 'wp-woocommerce-printify-sync'); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="mt-4">
                            <h5><?php esc_html_e('Documentation', 'wp-woocommerce-printify-sync'); ?></h5>
                            <p><?php esc_html_e('For help and documentation, please visit the', 'wp-woocommerce-printify-sync'); ?> <a href="https://github.com/ApolloWeb/wp-woocommerce-printify-sync" target="_blank"><?php esc_html_e('GitHub repository', 'wp-woocommerce-printify-sync'); ?></a>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
