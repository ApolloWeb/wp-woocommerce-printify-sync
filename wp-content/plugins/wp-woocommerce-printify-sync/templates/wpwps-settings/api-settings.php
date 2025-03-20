<?php
/**
 * API Settings template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var string $apiKey
 * @var string $apiEndpoint
 * @var string $shopId
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><i class="fas fa-tshirt"></i> <?php echo esc_html__('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <div class="container-fluid p-0 mt-4">
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-key"></i> <?php echo esc_html__('API Configuration', 'wp-woocommerce-printify-sync'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div id="settings-message" class="alert d-none"></div>
                    
                        <form id="api-settings-form">
                            <div class="mb-3">
                                <label for="api-key" class="form-label"><?php echo esc_html__('API Key', 'wp-woocommerce-printify-sync'); ?> <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="api-key" value="<?php echo esc_attr($apiKey); ?>" required>
                                <div class="form-text"><?php echo esc_html__('Get your API key from Printify dashboard (Account → API keys)', 'wp-woocommerce-printify-sync'); ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="api-endpoint" class="form-label"><?php echo esc_html__('API Endpoint', 'wp-woocommerce-printify-sync'); ?></label>
                                <input type="url" class="form-control" id="api-endpoint" value="<?php echo esc_url($apiEndpoint); ?>">
                                <div class="form-text"><?php echo esc_html__('Default: https://api.printify.com/v1/', 'wp-woocommerce-printify-sync'); ?></div>
                            </div>
                            
                            <div class="mb-3 d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo esc_html__('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                                <button type="button" id="test-connection" class="btn btn-info">
                                    <i class="fas fa-plug"></i> <?php echo esc_html__('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
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
                                <div class="alert alert-info">
                                    <strong><?php echo esc_html__('Current Shop ID:', 'wp-woocommerce-printify-sync'); ?></strong> <?php echo esc_html($shopId); ?>
                                </div>
                                <p class="text-muted"><?php echo esc_html__('Shop ID is locked to prevent accidental changes. Contact your administrator to change it.', 'wp-woocommerce-printify-sync'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
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
            </div>
        </div>
    </div>
</div>
