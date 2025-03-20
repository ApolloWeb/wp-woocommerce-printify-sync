<?php
/**
 * ChatGPT API card partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var bool $apiConfigured
 * @var string $chatGptApiKey
 * @var string $chatGptApiModel
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold"><i class="fas fa-robot me-2"></i><?php echo esc_html__('ChatGPT Integration', 'wp-woocommerce-printify-sync'); ?></h5>
    </div>
    <div class="card-body">
        <div id="chatgpt-message" class="alert d-none mb-3"></div>
        
        <form id="chatgpt-settings-form">
            <div class="mb-3">
                <label for="chatgpt-api-key" class="form-label"><?php echo esc_html__('OpenAI API Key', 'wp-woocommerce-printify-sync'); ?></label>
                <div class="input-group">
                    <input type="password" class="form-control" id="chatgpt-api-key" value="<?php echo esc_attr($chatGptApiKey); ?>" placeholder="sk-...">
                    <button type="button" class="btn btn-outline-secondary" id="toggle-api-key">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-text"><?php echo esc_html__('Get your API key from OpenAI dashboard', 'wp-woocommerce-printify-sync'); ?></div>
            </div>
            
            <div class="mb-3">
                <label for="chatgpt-model" class="form-label"><?php echo esc_html__('Model', 'wp-woocommerce-printify-sync'); ?></label>
                <select class="form-select" id="chatgpt-model">
                    <option value="gpt-3.5-turbo" <?php selected($chatGptApiModel, 'gpt-3.5-turbo'); ?>><?php echo esc_html__('GPT-3.5-turbo (Recommended)', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="gpt-4" <?php selected($chatGptApiModel, 'gpt-4'); ?>><?php echo esc_html__('GPT-4 (Advanced)', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="gpt-3.5-turbo-16k" <?php selected($chatGptApiModel, 'gpt-3.5-turbo-16k'); ?>><?php echo esc_html__('GPT-3.5-turbo-16k (Extended Context)', 'wp-woocommerce-printify-sync'); ?></option>
                </select>
                <div class="form-text"><?php echo esc_html__('Select the OpenAI model to use', 'wp-woocommerce-printify-sync'); ?></div>
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary" id="save-chatgpt-settings">
                    <i class="fas fa-save me-1"></i> <?php echo esc_html__('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <button type="button" class="btn btn-success" id="test-chatgpt-api">
                    <i class="fas fa-vial me-1"></i> <?php echo esc_html__('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                </button>
            </div>
        </form>
        
        <div id="chatgpt-response-container" class="mt-3 d-none">
            <div class="card bg-light">
                <div class="card-header">
                    <h6 class="mb-0"><?php echo esc_html__('API Response', 'wp-woocommerce-printify-sync'); ?></h6>
                </div>
                <div class="card-body">
                    <p id="chatgpt-response" class="mb-0"></p>
                </div>
            </div>
        </div>
    </div>
</div>
