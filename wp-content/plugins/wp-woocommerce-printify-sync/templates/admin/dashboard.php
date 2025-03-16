<?php
/**
 * Main dashboard template
 */
?>

<div class="printify-dashboard">
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Sales Overview</h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-period="day">Day</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-period="week">Week</button>
                            <button type="button" class="btn btn-outline-primary btn-sm active" data-period="month">Month</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-period="year">Year</button>
                        </div>
                    </div>
                    <canvas id="salesChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <?php $this->include('admin/partials/stats-cards', ['stats' => $stats]); ?>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <?php $this->include('admin/partials/recent-orders', ['orders' => $recentOrders]); ?>
        </div>
        <div class="col-md-6 mb-4">
            <?php $this->include('admin/partials/top-products', ['products' => $topProducts]); ?>
        </div>
    </div>
</div>