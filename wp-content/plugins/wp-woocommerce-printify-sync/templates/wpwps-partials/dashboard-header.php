<?php
/**
 * Dashboard header partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var bool $apiConfigured
 * @var string $shopId
 * @var string $settingsUrl
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="wpwps-header mb-4">
    <div class="d-flex align-items-center">
        <i class="fas fa-tshirt fa-2x me-3"></i>
        <h1 class="mb-0"><?php echo esc_html__('Printify Sync Dashboard', 'wp-woocommerce-printify-sync'); ?></h1>
    </div>
</div>

<?php if (!$apiConfigured): ?>
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <div>
            <?php echo esc_html__('You need to configure the Printify API settings to start using this plugin.', 'wp-woocommerce-printify-sync'); ?>
            <a href="<?php echo esc_url($settingsUrl); ?>" class="alert-link ms-2">
                <?php echo esc_html__('Configure now', 'wp-woocommerce-printify-sync'); ?>
            </a>
        </div>
    </div>
<?php endif; ?>
