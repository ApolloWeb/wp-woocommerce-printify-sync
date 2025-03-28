@extends('layout')

@section('actions')
    <div class="d-flex">
        <button class="btn btn-primary me-2" id="sync-products">
            <i class="fa fa-sync-alt me-1"></i> Sync Products
        </button>
        <button class="btn btn-secondary" id="sync-orders">
            <i class="fa fa-sync-alt me-1"></i> Sync Orders
        </button>
    </div>
@endsection

@section('content')
<div class="wpwps-dashboard">
    <!-- Stats Overview -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 fade-in fade-in-delay-1">
            <div class="card bg-primary text-white stats-card">
                <div class="card-body">
                    <h5 class="card-title">Products</h5>
                    <h2 class="mb-0 stats-number">{{ $product_count }}</h2>
                    <div class="d-flex align-items-center">
                        <small>{{ $sync_status['products']['synced'] }} synced</small>
                        @if($sync_status['products']['synced'] < $sync_status['products']['total'])
                            <span class="ms-2 badge bg-light text-dark">{{ $sync_status['products']['total'] - $sync_status['products']['synced'] }} pending</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 fade-in fade-in-delay-2">
            <div class="card bg-success text-white stats-card">
                <div class="card-body">
                    <h5 class="card-title">Orders</h5>
                    <h2 class="mb-0 stats-number">{{ $order_count }}</h2>
                    <div class="d-flex align-items-center">
                        <small>{{ $sync_status['orders']['synced'] }} synced</small>
                        @if($sync_status['orders']['synced'] < $sync_status['orders']['total'])
                            <span class="ms-2 badge bg-light text-dark">{{ $sync_status['orders']['total'] - $sync_status['orders']['synced'] }} pending</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 fade-in fade-in-delay-3">
            <div class="card bg-info text-white stats-card">
                <div class="card-body">
                    <h5 class="card-title">Email Queue</h5>
                    <h2 class="mb-0 stats-number">{{ $email_queue }}</h2>
                    <small>Pending emails</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 fade-in fade-in-delay-4">
            <div class="card bg-{{ $webhook_status['active'] ? 'success' : 'warning' }} text-white stats-card">
                <div class="card-body">
                    <h5 class="card-title">Webhooks</h5>
                    <div class="d-flex align-items-center mb-1">
                        <span class="status-indicator status-{{ $webhook_status['active'] ? 'active' : 'inactive' }}"></span>
                        <h2 class="mb-0 stats-number">{{ $webhook_status['active'] ? 'Active' : 'Inactive' }}</h2>
                    </div>
                    <small>{{ $webhook_status['errors'] }} errors</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Sales Chart -->
            <div class="card mb-4 fade-in">
                <div class="card-header card-header-actions bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Sales vs. Cost</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="chartRangeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                Last 30 days
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="chartRangeDropdown">
                                <li><a class="dropdown-item active" href="#">Last 30 days</a></li>
                                <li><a class="dropdown-item" href="#">Last 7 days</a></li>
                                <li><a class="dropdown-item" href="#">This month</a></li>
                                <li><a class="dropdown-item" href="#">This year</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="sales-chart"></canvas>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="row text-center">
                        <div class="col-md-4 border-end">
                            <div class="fw-bold">Total Sales</div>
                            <div class="text-success h5">$<span id="total-sales">0.00</span></div>
                        </div>
                        <div class="col-md-4 border-end">
                            <div class="fw-bold">Total Cost</div>
                            <div class="text-danger h5">$<span id="total-cost">0.00</span></div>
                        </div>
                        <div class="col-md-4">
                            <div class="fw-bold">Profit</div>
                            <div class="text-primary h5">$<span id="total-profit">0.00</span></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sync Status Cards -->
            <div class="row">
                <!-- Product Sync Status -->
                <div class="col-md-6">
                    <div class="card mb-4 fade-in">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Product Sync Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Synced Products</span>
                                <span>{{ $sync_status['products']['synced'] }} / {{ $sync_status['products']['total'] }}</span>
                            </div>
                            <div class="progress mb-3 sync-status">
                                @php
                                    $product_sync_percentage = $sync_status['products']['total'] > 0 
                                        ? round(($sync_status['products']['synced'] / $sync_status['products']['total']) * 100) 
                                        : 0;
                                @endphp
                                <div class="progress-bar" role="progressbar" style="width: {{ $product_sync_percentage }}%" 
                                    aria-valuenow="{{ $product_sync_percentage }}" aria-valuemin="0" aria-valuemax="100">
                                    {{ $product_sync_percentage }}%
                                </div>
                            </div>
                            @if($sync_status['products']['last_sync'])
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-clock text-muted me-2"></i>
                                    <small class="text-muted">Last sync: {{ date('F j, Y, g:i a', $sync_status['products']['last_sync']) }}</small>
                                </div>
                            @else
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-exclamation-circle text-warning me-2"></i>
                                    <small class="text-muted">No sync performed yet</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Order Sync Status -->
                <div class="col-md-6">
                    <div class="card mb-4 fade-in">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Order Sync Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Synced Orders</span>
                                <span>{{ $sync_status['orders']['synced'] }} / {{ $sync_status['orders']['total'] }}</span>
                            </div>
                            <div class="progress mb-3 sync-status">
                                @php
                                    $order_sync_percentage = $sync_status['orders']['total'] > 0 
                                        ? round(($sync_status['orders']['synced'] / $sync_status['orders']['total']) * 100) 
                                        : 0;
                                @endphp
                                <div class="progress-bar" role="progressbar" style="width: {{ $order_sync_percentage }}%" 
                                    aria-valuenow="{{ $order_sync_percentage }}" aria-valuemin="0" aria-valuemax="100">
                                    {{ $order_sync_percentage }}%
                                </div>
                            </div>
                            @if($sync_status['orders']['last_sync'])
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-clock text-muted me-2"></i>
                                    <small class="text-muted">Last sync: {{ date('F j, Y, g:i a', $sync_status['orders']['last_sync']) }}</small>
                                </div>
                            @else
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-exclamation-circle text-warning me-2"></i>
                                    <small class="text-muted">No sync performed yet</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Top Products Chart -->
            <div class="card mb-4 fade-in">
                <div class="card-header card-header-actions bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Top Products</h5>
                        <a href="?page=wpwps-products" class="btn btn-sm btn-link">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="products-chart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card fade-in">
                <div class="card-header card-header-actions bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Recent Activity</h5>
                        <a href="?page=wpwps-logs" class="btn btn-sm btn-link">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="activity-feed">
                        <li class="activity-item">
                            <div class="activity-icon bg-success text-white">
                                <i class="fa fa-check"></i>
                            </div>
                            <div class="activity-content">
                                <div class="fw-bold">5 products synced successfully</div>
                                <div class="activity-time">Today, 10:30 AM</div>
                            </div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon bg-primary text-white">
                                <i class="fa fa-shopping-cart"></i>
                            </div>
                            <div class="activity-content">
                                <div class="fw-bold">New order #1234 received</div>
                                <div class="activity-time">Yesterday, 3:45 PM</div>
                            </div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon bg-warning text-white">
                                <i class="fa fa-exclamation-triangle"></i>
                            </div>
                            <div class="activity-content">
                                <div class="fw-bold">API rate limit warning</div>
                                <div class="activity-time">Yesterday, 2:15 PM</div>
                            </div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon bg-info text-white">
                                <i class="fa fa-sync"></i>
                            </div>
                            <div class="activity-content">
                                <div class="fw-bold">Webhook endpoint updated</div>
                                <div class="activity-time">Feb 27, 2025</div>
                            </div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon bg-danger text-white">
                                <i class="fa fa-times"></i>
                            </div>
                            <div class="activity-content">
                                <div class="fw-bold">Sync failed for product #5678</div>
                                <div class="activity-time">Feb 25, 2025</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Sync buttons event handlers
        $('#sync-products').on('click', function() {
            if (confirm(wpwps.i18n.confirm_sync)) {
                $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Syncing...');
                
                // AJAX call to trigger product sync
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_sync_products',
                        nonce: wpwps.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast('Success', 'Product sync initiated successfully. This may take some time to complete.', 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showToast('Error', response.data.message || 'An unknown error occurred', 'danger');
                            $('#sync-products').prop('disabled', false).html('<i class="fa fa-sync-alt me-1"></i> Sync Products');
                        }
                    },
                    error: function() {
                        showToast('Error', 'An error occurred. Please try again.', 'danger');
                        $('#sync-products').prop('disabled', false).html('<i class="fa fa-sync-alt me-1"></i> Sync Products');
                    }
                });
            }
        });

        $('#sync-orders').on('click', function() {
            if (confirm(wpwps.i18n.confirm_order_sync)) {
                $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Syncing...');
                
                // AJAX call to trigger order sync
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_sync_orders',
                        nonce: wpwps.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast('Success', 'Order sync initiated successfully. This may take some time to complete.', 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showToast('Error', response.data.message || 'An unknown error occurred', 'danger');
                            $('#sync-orders').prop('disabled', false).html('<i class="fa fa-sync-alt me-1"></i> Sync Orders');
                        }
                    },
                    error: function() {
                        showToast('Error', 'An error occurred. Please try again.', 'danger');
                        $('#sync-orders').prop('disabled', false).html('<i class="fa fa-sync-alt me-1"></i> Sync Orders');
                    }
                });
            }
        });

        // Load charts data via AJAX
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_dashboard_stats',
                nonce: wpwps.nonce
            },
            success: function(response) {
                if (response.success) {
                    initCharts(response.data.charts);
                    
                    // Update totals
                    if (response.data.totals) {
                        $('#total-sales').text(response.data.totals.sales.toFixed(2));
                        $('#total-cost').text(response.data.totals.cost.toFixed(2));
                        $('#total-profit').text(response.data.totals.profit.toFixed(2));
                    }
                }
            }
        });

        // Initialize charts with smooth animations
        function initCharts(chartData) {
            // Sales chart with gradient and animation
            if (chartData.sales) {
                var salesCtx = document.getElementById('sales-chart').getContext('2d');
                
                // Create gradient for sales
                var salesGradient = salesCtx.createLinearGradient(0, 0, 0, 400);
                salesGradient.addColorStop(0, 'rgba(54, 162, 235, 0.6)');
                salesGradient.addColorStop(1, 'rgba(54, 162, 235, 0.1)');
                
                // Create gradient for cost
                var costGradient = salesCtx.createLinearGradient(0, 0, 0, 400);
                costGradient.addColorStop(0, 'rgba(255, 99, 132, 0.6)');
                costGradient.addColorStop(1, 'rgba(255, 99, 132, 0.1)');
                
                // Update datasets with gradients
                chartData.sales.datasets[0].backgroundColor = salesGradient;
                chartData.sales.datasets[1].backgroundColor = costGradient;
                
                // Add tension for smoother curves
                chartData.sales.datasets.forEach(dataset => {
                    dataset.tension = 0.4;
                    dataset.fill = true;
                });
                
                new Chart(salesCtx, {
                    type: 'line',
                    data: chartData.sales,
                    options: {
                        responsive: true,
                        animation: {
                            duration: 2000,
                            easing: 'easeOutQuart'
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value;
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    font: {
                                        family: "'Inter', sans-serif"
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                                titleColor: '#333',
                                bodyColor: '#666',
                                borderColor: 'rgba(0, 0, 0, 0.1)',
                                borderWidth: 1,
                                padding: 10,
                                boxPadding: 5,
                                usePointStyle: true,
                                cornerRadius: 8,
                                bodyFont: {
                                    family: "'Inter', sans-serif"
                                },
                                titleFont: {
                                    family: "'Inter', sans-serif",
                                    weight: 'bold'
                                },
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += '$' + context.parsed.y.toFixed(2);
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Products chart with custom colors
            if (chartData.products) {
                var productsCtx = document.getElementById('products-chart').getContext('2d');
                
                // Custom modern colors
                const modernColors = [
                    'rgba(150, 88, 138, 0.8)', // WooCommerce Purple
                    'rgba(58, 58, 126, 0.8)',  // Deep Indigo
                    'rgba(0, 102, 255, 0.8)',  // Electric Blue
                    'rgba(32, 201, 151, 0.8)', // Cool Teal
                    'rgba(248, 249, 250, 0.8)' // Soft Gray
                ];
                
                // Custom border colors
                const borderColors = [
                    'rgba(150, 88, 138, 1)',
                    'rgba(58, 58, 126, 1)',
                    'rgba(0, 102, 255, 1)',
                    'rgba(32, 201, 151, 1)',
                    'rgba(248, 249, 250, 1)'
                ];
                
                // Update dataset colors
                chartData.products.datasets[0].backgroundColor = modernColors;
                chartData.products.datasets[0].borderColor = borderColors;
                
                new Chart(productsCtx, {
                    type: 'doughnut',
                    data: chartData.products,
                    options: {
                        responsive: true,
                        animation: {
                            animateRotate: true,
                            animateScale: true,
                            duration: 2000,
                            easing: 'easeOutQuart'
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        family: "'Inter', sans-serif",
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                                titleColor: '#333',
                                bodyColor: '#666',
                                borderColor: 'rgba(0, 0, 0, 0.1)',
                                borderWidth: 1,
                                padding: 10,
                                boxPadding: 5,
                                cornerRadius: 8,
                                bodyFont: {
                                    family: "'Inter', sans-serif"
                                },
                                titleFont: {
                                    family: "'Inter', sans-serif",
                                    weight: 'bold'
                                }
                            }
                        }
                    });
            }
        }
    });
</script>
@endsection