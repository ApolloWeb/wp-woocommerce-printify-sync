<!-- Charts Row -->
<div class="row mt-4">
    <!-- Sales Chart -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle mb-3 text-muted">
                    <i class="fas fa-chart-line me-2"></i>{{ __('Sales Overview', 'wp-woocommerce-printify-sync') }}
                </h6>
                <canvas id="sales-chart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle mb-3 text-muted">
                    <i class="fas fa-history me-2"></i>{{ __('Recent Activity', 'wp-woocommerce-printify-sync') }}
                </h6>
                <div class="activity-feed" id="activity-feed">
                    <div class="placeholder-glow">
                        <div class="placeholder w-100 mb-2"></div>
                        <div class="placeholder w-75 mb-2"></div>
                        <div class="placeholder w-100 mb-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>