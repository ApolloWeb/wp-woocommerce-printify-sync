<?php
/**
 * Admin email preview template
 */
defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1><?php esc_html_e('Email Template Preview', 'wp-woocommerce-printify-sync'); ?></h1>

    <div class="wpwps-email-preview-container">
        <div class="wpwps-email-controls">
            <select id="wpwps-email-type" class="wpwps-select">
                <?php foreach ($email_types as $type => $label): ?>
                    <option value="<?php echo esc_attr($type); ?>">
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button class="button button-primary" id="wpwps-preview-button">
                <?php esc_html_e('Preview', 'wp-woocommerce-printify-sync'); ?>
            </button>

            <button class="button" id="wpwps-send-test">
                <?php esc_html_e('Send Test Email', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </div>

        <div class="wpwps-preview-frame-container">
            <iframe id="wpwps-preview-frame" name="wpwps-preview-frame"></iframe>
        </div>
    </div>
</div>