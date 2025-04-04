<div class="wpwps-dashboard">
    <?php if (!empty($notices)): ?>
        <div class="wpwps-notices mb-4">
            <?php foreach ($notices as $notice): ?>
                <div class="alert alert-<?php echo esc_attr($notice['type']); ?> alert-dismissible fade show" role="alert">
                    <?php echo esc_html($notice['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Products Stats -->
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Products</h5>
                    <div class="d-flex align-items-center">
                        <div class="display-4 me-3"><?php echo esc_html($stats['products']['total']); ?></div>
                        <div class="text-muted">
                            <div><?php echo esc_html($stats['products']['synced']); ?> synced</div>
                            <div><?php echo esc_html($stats['products']['pending']); ?> pending</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Stats -->
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Orders</h5>
                    <div class="d-flex align-items-center">
                        <div class="display-4 me-3"><?php echo esc_html($stats['orders']['total']); ?></div>
                        <div class="text-muted">
                            <div><?php echo esc_html($stats['orders']['pending']); ?> pending</div>
                            <div><?php echo esc_html($stats['orders']['completed']); ?> completed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
