<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <i class="fas fa-tshirt mr-2"></i>
        <?php _e('Import Products', 'wp-woocommerce-printify-sync'); ?>
    </h1>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-store mr-2"></i>
                        <?php _e('Available Products', 'wp-woocommerce-printify-sync'); ?>
                    </h3>
                    <div class="card-tools">
                        <button type="button" id="wpwps-import-all" class="btn btn-success">
                            <i class="fas fa-file-import mr-1"></i>
                            <?php _e('Import All', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php echo esc_html($error); ?>
                        </div>
                    <?php endif; ?>

                    <div id="wpwps-bulk-import-status" class="alert alert-info" style="display: none;">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        <span></span>
                    </div>

                    <?php if (empty($products)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <?php _e('No products found in your Printify store.', 'wp-woocommerce-printify-sync'); ?>
                        </div>
                    <?php else: ?>
                        <div class="wpwps-product-grid">
                            <?php foreach ($products as $product): ?>
                                <div class="wpwps-product-card">
                                    <div class="wpwps-product-image">
                                        <?php if (!empty($product['images'][0]['src'])): ?>
                                            <img src="<?php echo esc_url($product['images'][0]['src']); ?>" 
                                                 alt="<?php echo esc_attr($product['title']); ?>"
                                                 class="img-fluid">
                                        <?php else: ?>
                                            <i class="fas fa-tshirt fa-3x text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="wpwps-product-details">
                                        <h5 class="wpwps-product-title"><?php echo esc_html($product['title']); ?></h5>
                                        <div class="wpwps-product-meta">
                                            <span class="badge badge-info">
                                                <i class="fas fa-tags mr-1"></i>
                                                <?php echo sprintf(_n('%s variant', '%s variants', $product['variants_count'], 'wp-woocommerce-printify-sync'), 
                                                    number_format_i18n($product['variants_count'])); ?>
                                            </span>
                                            <?php if (!empty($product['print_provider']['title'])): ?>
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-print mr-1"></i>
                                                    <?php echo esc_html($product['print_provider']['title']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="wpwps-product-actions">
                                            <button class="btn btn-primary wpwps-import-product" 
                                                    data-product-id="<?php echo esc_attr($product['id']); ?>">
                                                <i class="fas fa-download mr-1"></i>
                                                <?php _e('Import', 'wp-woocommerce-printify-sync'); ?>
                                            </button>
                                            <div class="wpwps-import-status"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
