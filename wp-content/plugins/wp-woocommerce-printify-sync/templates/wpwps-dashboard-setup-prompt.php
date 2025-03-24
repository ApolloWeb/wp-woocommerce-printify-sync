<?php
/**
 * Dashboard setup prompt template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap wpwps-dashboard">
    <h1 class="wp-heading-inline">
        <i class="fas fa-tachometer-alt"></i> <?php echo esc_html__('Printify Sync Dashboard', 'wp-woocommerce-printify-sync'); ?>
    </h1>

    <div class="wpwps-setup-prompt">
        <div class="wpwps-setup-icon">
            <i class="fas fa-plug" style="font-size: 3rem; color: var(--wpwps-primary); margin-bottom: 1.5rem;"></i>
        </div>
        <h2><?php echo esc_html__('Connect to Printify', 'wp-woocommerce-printify-sync'); ?></h2>
        <p><?php echo esc_html__('To get started, you need to connect your Printify account. Please set up your API key in the settings.', 'wp-woocommerce-printify-sync'); ?></p>
        <a href="<?php echo esc_url($settings_url); ?>" class="button button-primary">
            <i class="fas fa-cog"></i> <?php echo esc_html__('Go to Settings', 'wp-woocommerce-printify-sync'); ?>
        </a>
    </div>
</div>
