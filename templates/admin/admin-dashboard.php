<?php
/**
 * Admin Dashboard Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Include the navigation header.
if (file_exists(PRINTIFY_SYNC_PATH . 'templates/admin/navigation.php')) {
    include PRINTIFY_SYNC_PATH . 'templates/admin/navigation.php';
}
?>

<div class="printify-dashboard-page">
    <div class="printify-content">
        <div class="stats-boxes">
            <div class="stat-box">
                <div class="icon"><i class="fas fa-store"></i></div>
                <div class="label">Active Shops</div>
                <div class="value" id="stat-active-shops">--</div>
                <div class="change"><i class="fas fa-arrow-up"></i> --</div>
            </div>
            <div class="stat-box">
                <div class="icon"><i class="fas fa-shirt"></i></div>
                <div class="label">Synced Products</div>
                <div class="value" id="stat-synced-products">--</div>
                <div class="change"><i class="fas fa-arrow-up"></i> --</div>
            </div>
            <div class="stat-box">
                <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="label">Recent Orders</div>
                <div class="value" id="stat-recent-orders">--</div>
                <div class="change"><i class="fas fa-arrow-up"></i> --</div>
            </div>
            <div class="stat-box">
                <div class="icon"><i class="fas fa-sync"></i></div>
                <div class="label">Last Sync</div>
                <div class="value" id="stat-last-sync">--</div>
                <div class="change">--</div>
            </div>
        </div>
        
        <!-- Sales Graph Widget -->
        <div class="dashboard-widget sales-graph-widget">
            <h3><i class="fas fa-chart-line"></i> Sales Analytics</h3>
            <div class="widget-content">
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="sales-filter">
                    <button class="filter-btn" data-period="day">Day</button>
                    <button class="filter-btn" data-period="week">Week</button>
                    <button class="filter-btn" data-period="month">Month</button>
                    <button class="filter-btn" data-period="year">Year</button>
                </div>
            </div>
        </div>
        
        <!-- Additional widgets can be added here as needed -->
    </div>
</div>