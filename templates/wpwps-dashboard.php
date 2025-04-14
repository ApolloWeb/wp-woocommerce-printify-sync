<?php
/**
 * Dashboard template
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

defined('ABSPATH') || exit;
?>

<div class="row">
    <!-- Stats Cards -->
    <div class="col-md-3 mb-4">
        <div class="wpwps-card">
            <div class="wpwps-card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="text-muted">Total Products</h5>
                        <h2>245</h2>
                    </div>
                    <div>
                        <i class="fas fa-box fa-3x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="wpwps-card">
            <div class="wpwps-card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="text-muted">Orders This Month</h5>
                        <h2>35</h2>
                    </div>
                    <div>
                        <i class="fas fa-shopping-cart fa-3x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="wpwps-card">
            <div class="wpwps-card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="text-muted">Revenue</h5>
                        <h2>$1,245</h2>
                    </div>
                    <div>
                        <i class="fas fa-dollar-sign fa-3x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="wpwps-card">
            <div class="wpwps-card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="text-muted">Pending Shipments</h5>
                        <h2>12</h2>
                    </div>
                    <div>
                        <i class="fas fa-truck fa-3x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="col-md-8 mb-4">
        <div class="wpwps-card">
            <div class="wpwps-card-header">
                <h5 class="wpwps-card-title">Sales Overview</h5>
            </div>
            <div class="wpwps-card-body">
                <canvas id="salesChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="wpwps-card">
            <div class="wpwps-card-header">
                <h5 class="wpwps-card-title">Product Categories</h5>
            </div>
            <div class="wpwps-card-body">
                <canvas id="categoryChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="col-md-12 mb-4">
        <div class="wpwps-card">
            <div class="wpwps-card-header d-flex justify-content-between align-items-center">
                <h5 class="wpwps-card-title">Recent Orders</h5>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-orders')); ?>" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="wpwps-card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#ORD-0123</td>
                                <td>John Doe</td>
                                <td>T-shirt Blue (XL)</td>
                                <td>$29.99</td>
                                <td><span class="badge bg-success">Shipped</span></td>
                                <td>2023-09-25</td>
                            </tr>
                            <tr>
                                <td>#ORD-0124</td>
                                <td>Jane Smith</td>
                                <td>Hoodie Black (M)</td>
                                <td>$49.99</td>
                                <td><span class="badge bg-warning">Processing</span></td>
                                <td>2023-09-24</td>
                            </tr>
                            <tr>
                                <td>#ORD-0125</td>
                                <td>Robert Johnson</td>
                                <td>Custom Mug</td>
                                <td>$14.99</td>
                                <td><span class="badge bg-info">Paid</span></td>
                                <td>2023-09-24</td>
                            </tr>
                            <tr>
                                <td>#ORD-0126</td>
                                <td>Emily Davis</td>
                                <td>Phone Case (iPhone 13)</td>
                                <td>$24.99</td>
                                <td><span class="badge bg-danger">Cancelled</span></td>
                                <td>2023-09-23</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    if (typeof Chart !== 'undefined') {
        // Sales Chart
        var salesCtx = document.getElementById('salesChart').getContext('2d');
        var salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
                datasets: [{
                    label: 'Sales',
                    data: [650, 590, 800, 810, 560, 550, 740, 920, 1050],
                    backgroundColor: 'rgba(126, 63, 242, 0.1)',
                    borderColor: '#7e3ff2',
                    borderWidth: 2,
                    pointBackgroundColor: '#7e3ff2',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Category Chart
        var categoryCtx = document.getElementById('categoryChart').getContext('2d');
        var categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['T-shirts', 'Hoodies', 'Mugs', 'Phone Cases', 'Posters'],
                datasets: [{
                    data: [45, 25, 15, 10, 5],
                    backgroundColor: [
                        '#7e3ff2',
                        '#1bcfb4',
                        '#198ae3',
                        '#fed713',
                        '#fe7c96'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>
