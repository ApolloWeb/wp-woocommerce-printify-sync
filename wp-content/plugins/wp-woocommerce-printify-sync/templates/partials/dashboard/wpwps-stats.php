<?php defined('ABSPATH') || exit; ?>

<div class="stats-grid">
    <!-- Products Card -->
    <div class="wpwps-card stats-card">
        <div class="card-body">
            <h5 class="card-title">
                <i class="fas fa-box text-primary"></i> <?php esc_html_e('Products', 'wp-woocommerce-printify-sync'); ?>
            </h5>
            <h2 class="card-stat"><?php echo esc_html($product_count ?? '0'); ?></h2>
            <p class="card-text"><?php esc_html_e('Total synced products', 'wp-woocommerce-printify-sync'); ?></p>
            <div class="stat-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-products')); ?>" class="btn btn-primary btn-sm">
                    <?php esc_html_e('View Products', 'wp-woocommerce-printify-sync'); ?>
                </a>
                <button class="btn btn-outline-primary btn-sm sync-products">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Orders Card -->
    <div class="wpwps-card stats-card">
        <div class="card-body">
            <h5 class="card-title">
                <i class="fas fa-shopping-cart text-success"></i> <?php esc_html_e('Orders', 'wp-woocommerce-printify-sync'); ?>
            </h5>
            <h2 class="card-stat"><?php echo esc_html($order_count ?? '0'); ?></h2>
            <p class="card-text"><?php esc_html_e('Total processed orders', 'wp-woocommerce-printify-sync'); ?></p>
            <div class="stat-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-orders')); ?>" class="btn btn-success btn-sm">
                    <?php esc_html_e('View Orders', 'wp-woocommerce-printify-sync'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Queue Stats -->
    <div class="wpwps-card stats-card">
        <div class="card-body">
            <h5 class="card-title">
                <i class="fas fa-tasks text-info"></i> <?php esc_html_e('Pending Tasks', 'wp-woocommerce-printify-sync'); ?>
            </h5>
            <h2 class="card-stat"><?php echo esc_html($pending_syncs ?? '0'); ?></h2>
            <p class="card-text"><?php esc_html_e('Tasks in queue', 'wp-woocommerce-printify-sync'); ?></p>
            <div class="stat-meta">
                <span class="badge bg-light text-dark">
                    <i class="fas fa-box"></i> <?php echo esc_html($pending_product_syncs ?? '0'); ?>
                </span>
                <span class="badge bg-light text-dark">
                    <i class="fas fa-shopping-cart"></i> <?php echo esc_html($pending_order_syncs ?? '0'); ?>
                </span>
            </div>
        </div>
    </div>
</div>
