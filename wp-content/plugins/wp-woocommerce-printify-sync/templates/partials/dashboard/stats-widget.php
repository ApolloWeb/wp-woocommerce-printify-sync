<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 bg-primary text-white p-3 rounded">
                    <i class="fas fa-envelope fa-2x"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted mb-1"><?php _e('Queued Emails', 'wp-woocommerce-printify-sync'); ?></h6>
                    <h4 class="card-title mb-0" id="queued-emails">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 bg-info text-white p-3 rounded">
                    <i class="fas fa-sync fa-2x"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted mb-1"><?php _e('Import Progress', 'wp-woocommerce-printify-sync'); ?></h6>
                    <h4 class="card-title mb-0" id="import-queue-progress">
                        <div class="spinner-border spinner-border-sm text-info" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 bg-success text-white p-3 rounded">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted mb-1"><?php _e('Last Sync', 'wp-woocommerce-printify-sync'); ?></h6>
                    <h4 class="card-title mb-0" id="sync-results">
                        <div class="spinner-border spinner-border-sm text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </h4>
                </div>
            </div>
        </div>
    </div>
</div>
