<div class="row g-4">
    <!-- API Status Card -->
    <div class="col-md-6 col-xl-3">
        <div class="wpwps-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-cloud fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">API Status</h6>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success me-2">Connected</span>
                            <small><?php echo get_option('wpwps_api_calls_today', 0); ?> calls today</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sync Status Card -->
    <div class="col-md-6 col-xl-3">
        <div class="wpwps-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-sync fa-2x text-info"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">Last Sync</h6>
                        <small><?php echo human_time_diff(get_option('wpwps_last_sync', time())); ?> ago</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Card -->
    <div class="col-md-6 col-xl-3">
        <div class="wpwps-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-tshirt fa-2x text-warning"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">Products</h6>
                        <div class="d-flex align-items-center">
                            <strong class="me-2"><?php echo get_option('wpwps_product_count', 0); ?></strong>
                            <small>Synced</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Card -->
    <div class="col-md-6 col-xl-3">
        <div class="wpwps-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-shopping-cart fa-2x text-success"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">Orders Today</h6>
                        <div class="d-flex align-items-center">
                            <strong class="me-2"><?php echo get_option('wpwps_orders_today', 0); ?></strong>
                            <small><?php echo get_option('wpwps_orders_pending', 0); ?> pending</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="col-xl-8">
        <div class="wpwps-card">
            <div class="card-body">
                <h5 class="card-title">Order Statistics</h5>
                <canvas id="ordersChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Activity Feed -->
    <div class="col-xl-4">
        <div class="wpwps-card">
            <div class="card-body">
                <h5 class="card-title">Recent Activity</h5>
                <div class="wpwps-activity-feed">
                    <!-- Activity items will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Email System Status -->
    <div class="col-xl-6">
        <div class="wpwps-card">
            <div class="card-body">
                <h5 class="card-title d-flex justify-content-between align-items-center">
                    Email System Status
                    <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-email-stats">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </h5>

                <div class="row g-4">
                    <!-- POP3 Status -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded">
                            <h6>POP3 Status</h6>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Connection:</span>
                                <span class="badge bg-success" id="pop3-status">Connected</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Last Check:</span>
                                <span id="pop3-last-check">5 minutes ago</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Messages Found:</span>
                                <span id="pop3-messages">23 today</span>
                            </div>
                        </div>
                    </div>

                    <!-- SMTP Status -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded">
                            <h6>SMTP Status</h6>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Connection:</span>
                                <span class="badge bg-success" id="smtp-status">Connected</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Sent Today:</span>
                                <span id="smtp-sent">145</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Failed Today:</span>
                                <span id="smtp-failed">2</span>
                            </div>
                        </div>
                    </div>

                    <!-- Queue Status -->
                    <div class="col-12">
                        <div class="p-3 border rounded">
                            <h6>Queue Status</h6>
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 60%">
                                            <span class="fw-bold">Processing</span>
                                        </div>
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 30%">
                                            <span class="fw-bold">Pending</span>
                                        </div>
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 10%">
                                            <span class="fw-bold">Failed</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-end">
                                        <div>Next Process: <span id="next-process">2 minutes</span></div>
                                        <div>Rate: <span id="process-rate">50/min</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary me-2" id="process-queue-now">
                                    Process Queue Now
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-failed">
                                    Clear Failed
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
