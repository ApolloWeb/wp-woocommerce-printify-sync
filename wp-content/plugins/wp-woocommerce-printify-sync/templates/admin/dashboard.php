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
</div>
