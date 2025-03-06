<div class="wrap">
    <h1><?php _e('Error Logs', 'wp-woocommerce-printify-sync'); ?></h1>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-exclamation-triangle"></i> <?php _e('View and Manage Error Logs', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <button id="clear-logs" class="btn btn-danger"><?php _e('Clear Logs', 'wp-woocommerce-printify-sync'); ?></button>
                        <div id="logs-status" class="mt-3">
                            <h5><?php _e('Total Errors:', 'wp-woocommerce-printify-sync'); ?> <span class="badge badge-danger">5</span></h5>
                            <ul class="list-group mt-2">
                                <li class="list-group-item"><?php _e('Error 1: Failed to sync product ID 12345', 'wp-woocommerce-printify-sync'); ?></li>
                                <li class="list-group-item"><?php _e('Error 2: Failed to sync order ID 67890', 'wp-woocommerce-printify-sync'); ?></li>
                                <li class="list-group-item"><?php _e('Error 3: API key invalid', 'wp-woocommerce-printify-sync'); ?></li>
                                <li class="list-group-item"><?php _e('Error 4: Network timeout', 'wp-woocommerce-printify-sync'); ?></li>
                                <li class="list-group-item"><?php _e('Error 5: Unknown error occurred', 'wp-woocommerce-printify-sync'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>