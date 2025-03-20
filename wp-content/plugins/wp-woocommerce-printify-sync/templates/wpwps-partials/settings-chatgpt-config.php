<?php
/**
 * ChatGPT API Configuration partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var string $chatGptApiKey
 * @var string $chatGptApiModel
 * @var int $chatGptMaxTokens
 * @var float $chatGptTemperature
 * @var bool $chatGptEnableUsageLimit
 * @var float $chatGptMonthlyLimit
 * @var float $chatGptCurrentUsage
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-robot"></i> <?php echo esc_html__('ChatGPT API Configuration', 'wp-woocommerce-printify-sync'); ?></h5>
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
            
            <!-- Cost Control Settings -->
            <div class="mb-3">
                <label for="chatgpt-max-tokens" class="form-label"><?php echo esc_html__('Max Tokens', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="number" class="form-control" id="chatgpt-max-tokens" value="<?php echo esc_attr($chatGptMaxTokens); ?>" min="50" max="4000">
                <div class="form-text"><?php echo esc_html__('Maximum tokens per request (lower = less expensive)', 'wp-woocommerce-printify-sync'); ?></div>
            </div>
            
            <div class="mb-3">
                <label for="chatgpt-temperature" class="form-label"><?php echo esc_html__('Temperature', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="range" class="form-range" id="chatgpt-temperature" value="<?php echo esc_attr($chatGptTemperature); ?>" min="0" max="1" step="0.1">
                <div class="d-flex justify-content-between">
                    <span class="form-text"><?php echo esc_html__('More deterministic (0)', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="form-text text-center" id="temperature-value"><?php echo esc_html($chatGptTemperature); ?></span>
                    <span class="form-text text-end"><?php echo esc_html__('More random (1)', 'wp-woocommerce-printify-sync'); ?></span>
                </div>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="chatgpt-enable-usage-limit" <?php checked($chatGptEnableUsageLimit); ?>>
                    <label class="form-check-label" for="chatgpt-enable-usage-limit">
                        <?php echo esc_html__('Enable Usage Limit', 'wp-woocommerce-printify-sync'); ?>
                    </label>
                </div>
                <div class="input-group mt-2" id="usage-limit-container" style="<?php echo $chatGptEnableUsageLimit ? '' : 'display: none;'; ?>">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="chatgpt-monthly-limit" value="<?php echo esc_attr($chatGptMonthlyLimit); ?>" min="0" step="0.01">
                    <span class="input-group-text"><?php echo esc_html__('per month', 'wp-woocommerce-printify-sync'); ?></span>
                </div>
                <div class="form-text"><?php echo esc_html__('Set a monthly spending limit (recommended for production)', 'wp-woocommerce-printify-sync'); ?></div>
            </div>
            
            <?php if ($chatGptEnableUsageLimit && $chatGptCurrentUsage > 0): ?>
            <div class="mb-3">
                <label class="form-label"><?php echo esc_html__('Current Usage', 'wp-woocommerce-printify-sync'); ?></label>
                <div class="progress">
                    <?php 
                    $percentage = min(100, ($chatGptCurrentUsage / $chatGptMonthlyLimit) * 100);
                    $progressClass = $percentage < 50 ? 'bg-success' : ($percentage < 80 ? 'bg-warning' : 'bg-danger');
                    ?>
                    <div class="progress-bar <?php echo $progressClass; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                         aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="form-text mt-1">
                    $<?php echo number_format($chatGptCurrentUsage, 4); ?> / $<?php echo number_format($chatGptMonthlyLimit, 2); ?>
                    (<?php echo number_format($percentage, 1); ?>%)
                </div>
            </div>
            <?php endif; ?>
            
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><?php echo esc_html__('API Response', 'wp-woocommerce-printify-sync'); ?></h6>
                    <span class="text-muted small" id="token-usage"></span>
                </div>
                <div class="card-body">
                    <p id="chatgpt-response" class="mb-0"></p>
                </div>
            </div>
        </div>
    </div>
</div>
