<?php
/**
 * Settings header partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var bool $apiConfigured
 * @var string $shopId
 * @var string $shopName
 * @var string $dashboardUrl
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="wpwps-header mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i class="fas fa-tshirt fa-2x me-3"></i>
            <h1 class="mb-0"><?php echo esc_html__('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
        </div>
        <?php if ($apiConfigured): ?>
        <div class="shop-info-badge">
            <?php if (!empty($shopName)): ?>
                <span class="badge bg-light text-dark border">
                    <i class="fas fa-store me-1"></i> <?php echo esc_html($shopName); ?>
                </span>
            <?php else: ?>
                <button type="button" id="fetch-shop-name" class="badge bg-warning text-dark border shop-badge-link">
                    <i class="fas fa-exclamation-circle me-1"></i> <?php echo esc_html__('Shop Name Not Set', 'wp-woocommerce-printify-sync'); ?>
                    <i class="fas fa-sync-alt ms-1 small"></i>
                </button>
            <?php endif; ?>
            <span class="badge bg-secondary ms-2">
                <i class="fas fa-id-card me-1"></i> <?php echo esc_html($shopId); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="mb-4 d-flex">
    <a href="<?php echo esc_url($dashboardUrl); ?>" class="btn btn-outline-secondary btn-sm me-2">
        <i class="fas fa-tachometer-alt me-1"></i> <?php echo esc_html__('Dashboard', 'wp-woocommerce-printify-sync'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=printify-sync-settings')); ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-cog me-1"></i> <?php echo esc_html__('Settings', 'wp-woocommerce-printify-sync'); ?>
    </a>
</div>

<div id="name-fetch-alert" class="alert d-none mb-3"></div>
