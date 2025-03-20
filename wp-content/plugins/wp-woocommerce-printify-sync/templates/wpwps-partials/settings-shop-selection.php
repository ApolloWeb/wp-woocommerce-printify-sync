<?php
/**
 * Shop Selection partial
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var string $apiKey
 * @var string $shopId
 * @var string $shopName
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="card shadow-sm mt-4" id="shop-selection-card" style="<?php echo empty($apiKey) ? 'display: none;' : ''; ?>">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-store"></i> <?php echo esc_html__('Shop Selection', 'wp-woocommerce-printify-sync'); ?></h5>
    </div>
    <div class="card-body">
        <div id="shop-message" class="alert d-none"></div>
        
        <?php if (empty($shopId)) : ?>
            <div id="shop-selection-container">
                <form id="shop-selection-form">
                    <div class="mb-3">
                        <label for="shop-select" class="form-label"><?php echo esc_html__('Select Shop', 'wp-woocommerce-printify-sync'); ?></label>
                        <select class="form-select" id="shop-select" disabled>
                            <option value=""><?php echo esc_html__('Click "Fetch Shops" to load shops', 'wp-woocommerce-printify-sync'); ?></option>
                        </select>
                    </div>
                    
                    <div class="mb-3 d-flex justify-content-between">
                        <button type="button" id="fetch-shops" class="btn btn-primary">
                            <i class="fas fa-sync"></i> <?php echo esc_html__('Fetch Shops', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                        <button type="submit" class="btn btn-success" id="save-shop" disabled>
                            <i class="fas fa-save"></i> <?php echo esc_html__('Save Selected Shop', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php else : ?>
            <div id="shop-info">
                <div class="alert <?php echo empty($shopName) ? 'alert-warning' : 'alert-info'; ?>">
                    <strong><?php echo esc_html__('Current Shop ID:', 'wp-woocommerce-printify-sync'); ?></strong> <?php echo esc_html($shopId); ?>
                    <?php if (!empty($shopName)) : ?>
                        <br><strong><?php echo esc_html__('Shop Name:', 'wp-woocommerce-printify-sync'); ?></strong> <?php echo esc_html($shopName); ?>
                    <?php else: ?>
                        <br>
                        <div class="mt-2 d-flex align-items-center">
                            <span class="badge bg-warning text-dark me-2">
                                <i class="fas fa-exclamation-circle me-1"></i> <?php echo esc_html__('Shop Name Not Set', 'wp-woocommerce-printify-sync'); ?>
                            </span>
                            <button type="button" id="shop-info-fetch" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-sync-alt me-1"></i> <?php echo esc_html__('Fetch Shop Name', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="text-muted"><?php echo esc_html__('Shop ID is locked to prevent accidental changes. Contact your administrator to change it.', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
