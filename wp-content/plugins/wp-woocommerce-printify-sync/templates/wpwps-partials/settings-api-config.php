<?php
/**
 * API Configuration partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var string $apiKey
 * @var string $apiEndpoint
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-key"></i> <?php echo esc_html__('API Configuration', 'wp-woocommerce-printify-sync'); ?></h5>
    </div>
    <div class="card-body">
        <div id="settings-message" class="alert d-none"></div>
    
        <form id="api-settings-form">
            <div class="mb-3">
                <label for="api-key" class="form-label"><?php echo esc_html__('API Key', 'wp-woocommerce-printify-sync'); ?> <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="api-key" value="<?php echo esc_attr($apiKey); ?>" required>
                <div class="form-text"><?php echo esc_html__('Get your API key from Printify dashboard (Account → API keys)', 'wp-woocommerce-printify-sync'); ?></div>
            </div>
            
            <div class="mb-3">
                <label for="api-endpoint" class="form-label"><?php echo esc_html__('API Endpoint', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="url" class="form-control" id="api-endpoint" value="<?php echo esc_url($apiEndpoint); ?>">
                <div class="form-text"><?php echo esc_html__('Default: https://api.printify.com/v1/', 'wp-woocommerce-printify-sync'); ?></div>
            </div>
            
            <div class="mb-3 d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo esc_html__('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <button type="button" id="test-connection" class="btn btn-info">
                    <i class="fas fa-plug"></i> <?php echo esc_html__('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
