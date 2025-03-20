<?php
/**
 * ChatGPT Cost Guide partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-coins"></i> <?php echo esc_html__('ChatGPT API Cost Guide', 'wp-woocommerce-printify-sync'); ?></h5>
    </div>
    <div class="card-body">
        <h5><?php echo esc_html__('Cost Management Tips', 'wp-woocommerce-printify-sync'); ?></h5>
        <ul>
            <li><?php echo esc_html__('OpenAI charges based on tokens (roughly 4 characters = 1 token)', 'wp-woocommerce-printify-sync'); ?></li>
            <li><?php echo esc_html__('GPT-3.5-Turbo is significantly cheaper than GPT-4', 'wp-woocommerce-printify-sync'); ?></li>
            <li><?php echo esc_html__('Limiting max tokens reduces costs by capping response length', 'wp-woocommerce-printify-sync'); ?></li>
            <li><?php echo esc_html__('Set a monthly spending limit to avoid unexpected charges', 'wp-woocommerce-printify-sync'); ?></li>
        </ul>
        
        <h5 class="mt-3"><?php echo esc_html__('Approximate Pricing', 'wp-woocommerce-printify-sync'); ?></h5>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Model', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php echo esc_html__('Input Cost', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php echo esc_html__('Output Cost', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>GPT-3.5-Turbo</td>
                    <td>$0.0015 / 1K tokens</td>
                    <td>$0.002 / 1K tokens</td>
                </tr>
                <tr>
                    <td>GPT-4</td>
                    <td>$0.03 / 1K tokens</td>
                    <td>$0.06 / 1K tokens</td>
                </tr>
            </tbody>
        </table>
        
        <p class="small text-muted mt-3">
            <i class="fas fa-info-circle"></i> <?php echo esc_html__('Prices may change. Check the OpenAI pricing page for the most current rates.', 'wp-woocommerce-printify-sync'); ?>
        </p>
        
        <a href="https://openai.com/pricing" target="_blank" class="btn btn-outline-info btn-sm mt-2">
            <i class="fas fa-external-link-alt me-1"></i> <?php echo esc_html__('View OpenAI Pricing', 'wp-woocommerce-printify-sync'); ?>
        </a>
    </div>
</div>
