<div class="wrap">
    <h1><?php _e('Order Sync', 'wp-woocommerce-printify-sync'); ?></h1>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-sync-alt"></i> <?php _e('Sync Orders from Printify', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <button id="sync-orders" class="btn btn-primary"><?php _e('Sync Now', 'wp-woocommerce-printify-sync'); ?></button>
                        <div id="sync-status" class="mt-3">
                            <h5><?php _e('Last Sync:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-info">2025-03-01 12:30:00</span></h5>
                            <h5><?php _e('Total Orders Synced:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-success">250</span></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>