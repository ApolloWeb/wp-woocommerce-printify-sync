<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Analytics Page
 */
class AnalyticsPage extends AdminPage {
    /**
     * Constructor
     */
    public function __construct() {
        $this->page_title = __('Printify Analytics', 'wp-woocommerce-printify-sync');
        $this->menu_title = __('Analytics', 'wp-woocommerce-printify-sync');
        $this->menu_slug = 'wpwps-analytics';
        $this->parent_slug = 'wpwps-dashboard';
        $this->capability = 'manage_options';
    }
    
    /**
     * Initialize the page
     */
    public function init() {
        parent::init();
        
        // Add any analytics-specific hooks
        add_action('admin_enqueue_scripts', [$this, 'enqueue_analytics_scripts']);
    }
    
    /**
     * Enqueue analytics-specific scripts
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_analytics_scripts($hook) {
        if (strpos($hook, $this->menu_slug) === false) {
            return;
        }
        
        wp_enqueue_script(
            WPWPS_ASSET_PREFIX . 'analytics',
            WPWPS_ASSETS_URL . 'js/' . WPWPS_ASSET_PREFIX . 'analytics.js',
            [WPWPS_ASSET_PREFIX . 'admin-scripts'],
            WPWPS_VERSION,
            true
        );
    }
    
    /**
     * Render the page content
     */
    protected function render_content() {
        ?>
        <div class="container-fluid">
            <!-- Filter Row -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div class="d-flex align-items-center mb-2 mb-md-0">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-primary active">Last 7 Days</button>
                                        <button type="button" class="btn btn-outline-primary">Last 30 Days</button>
                                        <button type="button" class="btn btn-outline-primary">Last Quarter</button>
                                        <button type="button" class="btn btn-outline-primary">This Year</button>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="input-group">
                                        <input type="date" class="form-control" placeholder="From">
                                        <span class="input-group-text bg-white">to</span>
                                        <input type="date" class="form-control" placeholder="To">
                                        <button class="btn btn-outline-primary" type="button">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Stats Row -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm wpwps-stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Sales</h6>
                                    <h2 class="mb-0 fw-bold">$4,385.29</h2>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-dollar-sign fa-2x text-primary"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success">
                                    <i class="fas fa-arrow-up"></i> 18.2%
                                </span>
                                <span class="text-muted ms-2">vs last period</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm wpwps-stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Products Sold</h6>
                                    <h2 class="mb-0 fw-bold">217</h2>
                                </div>
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-box fa-2x text-success"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success">
                                    <i class="fas fa-arrow-up"></i> 24.5%
                                </span>
                                <span class="text-muted ms-2">vs last period</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm wpwps-stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Average Order</h6>
                                    <h2 class="mb-0 fw-bold">$42.65</h2>
                                </div>
                                <div class="bg-info bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-chart-line fa-2x text-info"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-danger">
                                    <i class="fas fa-arrow-down"></i> 3.8%
                                </span>
                                <span class="text-muted ms-2">vs last period</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm wpwps-stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">New Customers</h6>
                                    <h2 class="mb-0 fw-bold">68</h2>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-users fa-2x text-warning"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success">
                                    <i class="fas fa-arrow-up"></i> 12.1%
                                </span>
                                <span class="text-muted ms-2">vs last period</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Sales Overview</h5>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary active">Daily</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary">Weekly</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary">Monthly</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0">Top Products</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="productChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Additional Charts Row -->
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0">Customer Demographics</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="demographicsChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Geographic Distribution</h5>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="showPercentages">
                                <label class="form-check-label" for="showPercentages">Show Percentages</label>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="geoChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
