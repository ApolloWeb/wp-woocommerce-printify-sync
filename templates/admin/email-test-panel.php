<?php defined('ABSPATH') || exit; ?>

<div class="wpwps-card email-test-panel">
    <h3><?php esc_html_e('Email Testing', 'wp-woocommerce-printify-sync'); ?></h3>
    
    <div class="test-controls">
        <div class="form-group">
            <label for="test-scenario"><?php esc_html_e('Test Scenario', 'wp-woocommerce-printify-sync'); ?></label>
            <select id="test-scenario" class="regular-text">
                <?php foreach ($test_scenarios as $key => $scenario): ?>
                    <option value="<?php echo esc_attr($key); ?>">
                        <?php echo esc_html($scenario['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="test-email"><?php esc_html_e('Test Email Address', 'wp-woocommerce-printify-sync'); ?></label>
            <input type="email" id="test-email" class="regular-text">
        </div>

        <div class="test-actions">
            <button type="button" class="button button-primary" id="send-test">
                <?php esc_html_e('Send Test Email', 'wp-woocommerce-printify-sync'); ?>
            </button>
            <button type="button" class="button" id="preview-test">
                <?php esc_html_e('Preview Email', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </div>
    </div>

    <div id="test-result" class="test-result hidden"></div>
    <div id="template-validation" class="validation-result hidden"></div>
</div>
