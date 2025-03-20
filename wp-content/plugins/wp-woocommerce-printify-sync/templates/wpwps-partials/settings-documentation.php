<?php
/**
 * Documentation partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="card shadow-sm">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> <?php echo esc_html__('Documentation', 'wp-woocommerce-printify-sync'); ?></h5>
    </div>
    <div class="card-body">
        <h5><?php echo esc_html__('Getting Started', 'wp-woocommerce-printify-sync'); ?></h5>
        <ol>
            <li><?php echo esc_html__('Enter your Printify API key', 'wp-woocommerce-printify-sync'); ?></li>
            <li><?php echo esc_html__('Click "Test Connection" to verify', 'wp-woocommerce-printify-sync'); ?></li>
            <li><?php echo esc_html__('Select your shop from the dropdown', 'wp-woocommerce-printify-sync'); ?></li>
            <li><?php echo esc_html__('Start syncing your products', 'wp-woocommerce-printify-sync'); ?></li>
        </ol>
        
        <h5 class="mt-4"><?php echo esc_html__('Printify API Resources', 'wp-woocommerce-printify-sync'); ?></h5>
        <p>
            <a href="https://developers.printify.com/" target="_blank" class="text-decoration-none">
                <i class="fas fa-external-link-alt"></i> <?php echo esc_html__('Printify API Documentation', 'wp-woocommerce-printify-sync'); ?>
            </a>
        </p>
    </div>
</div>
