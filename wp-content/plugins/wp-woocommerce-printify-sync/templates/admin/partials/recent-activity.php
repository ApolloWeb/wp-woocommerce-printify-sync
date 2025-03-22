<div class="wpps-card p-4">
    <h3 class="h5 mb-4"><?= __('Recent Activity', 'wp-woocommerce-printify-sync') ?></h3>
    
    <div class="wpps-timeline">
        <div class="wpps-timeline-item">
            <span class="wpps-timeline-icon bg-success">
                <i class="fas fa-check"></i>
            </span>
            <div class="wpps-timeline-content">
                <h6 class="mb-1"><?= __('Order Synced', 'wp-woocommerce-printify-sync') ?></h6>
                <p class="text-muted small mb-0">Order #1234 synced successfully</p>
                <small class="text-muted">2 minutes ago</small>
            </div>
        </div>
        
        <div class="wpps-timeline-item">
            <span class="wpps-timeline-icon bg-warning">
                <i class="fas fa-sync"></i>
            </span>
            <div class="wpps-timeline-content">
                <h6 class="mb-1"><?= __('Product Update', 'wp-woocommerce-printify-sync') ?></h6>
                <p class="text-muted small mb-0">15 products updated</p>
                <small class="text-muted">1 hour ago</small>
            </div>
        </div>
    </div>
</div>
