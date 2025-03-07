<?php
/**
 * Main dashboard tab for WooCommerce Printify Sync
 * Using Shards Dashboard theme with Chart.js
 *
 * @package WP_Woocommerce_Printify_Sync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<!-- Small Stats Blocks -->
<div class="row">
    <!-- Total Products -->
    <div class="col-lg col-md-6 col-sm-6 mb-4">
        <div class="stats-small stats-small--1 card card-small">
            <div class="card-body p-0 d-flex">
                <div class="d-flex flex-column m-auto">
                    <div class="stats-small__data text-center">
                        <span class="stats-small__label text-uppercase">Total Products</span>
                        <h6 class="stats-small__value count my-3">134</h6>
                    </div>
                    <div class="stats-small__data">
                        <span class="stats-small__percentage stats-small__percentage--increase">12.4%</span>
                    </div>
                </div>
                <canvas height="120" class="blog-overview-stats-small-1"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Synced Products -->
    <div class="col-lg col-md-6 col-sm-6 mb-4">
        <div class="stats-small stats-small--1 card card-small">
            <div class="card-body p-0 d-flex">
                <div class="d-flex flex-column m-auto">
                    <div class="stats-small__data text-center">
                        <span class="stats-small__label text-uppercase">Synced Today</span>
                        <h6 class="stats-small__value count my-3">28</h6>
                    </div>
                    <div class="stats-small__data">
                        <span class="stats-small__percentage stats-small__percentage--increase">16.7%</span>
                    </div>
                </div>
                <canvas height="120" class="blog-overview-stats-small-2"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Orders Processed -->
    <div class="col-lg col-md-4 col-sm-6 mb-4">
        <div class="stats-small stats-small--1 card card-small">
            <div class="card-body p-0 d-flex">
                <div class="d-flex flex-column m-auto">
                    <div class="stats-small__data text-center">
                        <span class="stats-small__label text-uppercase">Orders</span>
                        <h6 class="stats-small__value count my-3">87</h6>
                    </div>
                    <div class="stats-small__data">
                        <span class="stats-small__percentage stats-small__percentage--increase">12.2%</span>
                    </div>
                </div>
                <canvas height="120" class="blog-overview-stats-small-3"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Active Tickets -->
    <div class="col-lg col-md-4 col-sm-6 mb-4">
        <div class="stats-small stats-small--1 card card-small">
            <div class="card-body p-0 d-flex">
                <div class="d-flex flex-column m-auto">
                    <div class="stats-small__data text-center">
                        <span class="stats-small__label text-uppercase">Tickets</span>
                        <h6 class="stats-small__value count my-3">14</h6>
                    </div>
                    <div class="stats-small__data">
                        <span class="stats-small__percentage stats-small__percentage--decrease">3.8%</span>
                    </div>
                </div>
                <canvas height="120" class="blog-overview-stats-small-4"></canvas>
            </div>
        </div>
    </div>
    
    <!-- API Rate Limit -->
    <div class="col-lg col-md-4 col-sm-12 mb-4">
        <div class="stats-small stats-small--1 card card-small">
            <div class="card-body p-0 d-flex">
                <div class="d-flex flex-column m-auto">
                    <div class="stats-small__data text-center">
                        <span class="stats-small__label text-uppercase">API Calls</span>
                        <h6 class="stats-small__value count my-3">482</h6>
                    </div>
                    <div class="stats-small__data">
                        <span class="stats-small__percentage stats-small__percentage--increase">2.4%</span>
                    </div>
                </div>
                <canvas height="120" class="blog-overview-stats-small-5"></canvas>
            </div>
        </div>
    </div>
</div>
<!-- End Small Stats Blocks -->

<div class="row">
    <!-- Order Overview -->
    <div class="col-lg-8 col-md-12 col-sm-12 mb-4">
        <div class="card card-small h-100">
            <div class="card-header border-bottom">
                <h6 class="m-0">Orders Overview</h6>
            </div>
            <div class="card-body pt-0">
                <canvas height="130" style="max-width: 100% !important;" class="blog-overview-users"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Product Status -->
    <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
        <div class="card card-small h-100">
            <div class="card-header border-bottom">
                <h6 class="m-0">Product Status</h6>
            </div>
            <div class="card-body d-flex py-0">
                <canvas height="220" class="blog-overview-product-status"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- System Status -->
    <div class="col-lg-6 col-md-6 col-sm-12 mb-4">
        <div class="card card-small h-100">
            <div class="card-header border-bottom">
                <h6 class="m-0">System Status</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-small list-group-flush">
                    <li class="list-group-item d-flex px-3">
                        <span class="text-semibold text-fiord-blue">API Connection</span>
                        <span class="ml-auto text-right text-success">
                            <i class="material-icons">check_circle</i> Connected
                        </span>
                    </li>
                    <li class="list-group-item d-flex px-3">
                        <span class="text-semibold text-fiord-blue">Webhooks</span>
                        <span class="ml-auto text-right text-warning">
                            <i class="material-icons">warning</i> 2 of 3 Active
                        </span>
                    </li>
                    <li class="list-group-item d-flex px-3">
                        <span class="text-semibold text-fiord-blue">Cron Jobs</span>
                        <span class="ml-auto text-right text-success">
                            <i class="material-icons">check_circle</i> Running
                        </span>
                    </li>
                    <li class="list-group-item d-flex px-3">
                        <span class="text-semibold text-fiord-blue">Database Storage</span>
                        <span class="ml-auto text-right text-success">
                            <i class="material-icons">check_circle</i> 42% Used
                        </span>
                    </li>
                    <li class="list-group-item d-flex px-3">
                        <span class="text-semibold text-fiord-blue">Log Retention</span>
                        <span class="ml-auto text-right text-success">
                            <i class="material-icons">check_circle</i> 14 Days
                        </span>
                    </li>
                </ul>
            </div>
            <div class="card-footer border-top">
                <div class="row">
                    <div class="col">
                        <button type="button" id="check-status-all" class="btn btn-sm btn-accent">
                            <i class="material-icons">refresh</i> Check All
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="col-lg-6 col-md-6 col-sm-12 mb-4">
        <div class="card card-small h-100">
            <div class="card-header border-bottom">
                <h6 class="m-0">Recent Activity</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-3">
                        <div class="notification__icon-wrapper">
                            <div class="notification__icon">
                                <i class="material-icons">shopping_cart</i>
                            </div>
                        </div>
                        <div class="notification__content">
                            <span class="notification__category">Orders</span>
                            <p>New order #12345 received from <span class="text-success">John Doe</span></p>
                            <span class="text-muted small">2 minutes ago</span>
                        </div>
                    </li>
                    <li class="list-group-item px-3">
                        <div class="notification__icon-wrapper">
                            <div class="notification__icon">
                                <i class="material-icons">sync</i>
                            </div>
                        </div>
                        <div class="notification__content">
                            <span class="notification__category">Sync</span>
                            <p>Successfully synced <span class="text-success">28 products</span> from Printify</p>
                            <span class="text-muted small">15 minutes ago</span>
                        </div>
                    </li>
                    <li class="list-group-item px-3">
                        <div class="notification__icon-wrapper">
                            <div class="notification__icon">
                                <i class="material-icons">local_shipping</i>
                            </div>
                        </div>
                        <div class="notification__content">
                            <span class="notification__category">Shipping</span>
                            <p>Order <span class="text-success">#12340</span> has been shipped</p>
                            <span class="text-muted small">1 hour ago</span>
                        </div>
                    </li>
                    <li class="list-group-item px-3">
                        <div class="notification__icon-wrapper">
                            <div class="notification__icon">
                                <i class="material-icons">confirmation_number</i>
                            </div>
                        </div>
                        <div class="notification__content">
                            <span class="notification__category">Support</span>
                            <p>New ticket <span class="text-warning">#458</span> opened by customer</p>
                            <span class="text-muted small">2 hours ago</span>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="card-footer border-top">
                <div class="row">
                    <div class="col text-right view-report">
                        <a href="#">View All Activity →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card card-small">
            <div class="card-header border-bottom">
                <h6 class="m-0">Quick Actions</h6>
            </div>
            <div class="card-body p-0">
                <div class="row no-gutters py-2">
                    <div class="col-sm-6 col-md-3 text-center action-item">
                        <button type="button" class="btn" id="sync-products">
                            <i class="material-icons">sync</i>
                            <span>Sync Products</span>
                        </button>
                    </div>
                    <div class="col-sm-6 col-md-3 text-center action-item">
                        <button type="button" class="btn" id="check-orders">
                            <i class="material-icons">shopping_cart</i>
                            <span>Check Orders</span>
                        </button>
                    </div>
                    <div class="col-sm-6 col-md-3 text-center action-item">
                        <button type="button" class="btn" id="clear-cache">
                            <i class="material-icons">cleaning_services</i>