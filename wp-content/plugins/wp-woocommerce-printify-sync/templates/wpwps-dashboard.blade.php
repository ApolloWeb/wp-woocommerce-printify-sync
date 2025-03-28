@extends('layout')

@section('content')
<div class="row mb-4">
    <!-- Key Stats Cards -->
    <div class="col-md-3">
        <div class="card wpwps-card">
            <div class="card-body text-center">
                <div class="d-inline-block p-3 rounded-circle mb-3" style="background-color: rgba(150, 88, 138, 0.1)">
                    <i class="fas fa-box-open fa-2x text-primary"></i>
                </div>
                <h2 class="display-4 fw-bold">{{ $stats['products']['total'] }}</h2>
                <p class="text-muted mb-0">{{ __('Total Products', 'wp-woocommerce-printify-sync') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card wpwps-card">
            <div class="card-body text-center">
                <div class="d-inline-block p-3 rounded-circle mb-3" style="background-color: rgba(40, 167, 69, 0.1)">
                    <i class="fas fa-sync fa-2x text-success"></i>
                </div>
                <h2 class="display-4 fw-bold">{{ $stats['products']['synced'] }}</h2>
                <p class="text-muted mb-0">{{ __('Synced Products', 'wp-woocommerce-printify-sync') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card wpwps-card">
            <div class="card-body text-center">
                <div class="d-inline-block p-3 rounded-circle mb-3" style="background-color: rgba(220, 53, 69, 0.1)">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                </div>
                <h2 class="display-4 fw-bold">{{ $stats['products']['failed'] }}</h2>
                <p class="text-muted mb-0">{{ __('Failed Products', 'wp-woocommerce-printify-sync') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card wpwps-card">
            <div class="card-body text-center">
                <div class="d-inline-block p-3 rounded-circle mb-3" style="background-color: rgba(0, 180, 216, 0.1)">
                    <i class="fas fa-shopping-cart fa-2x text-info"></i>
                </div>
                <h2 class="display-4 fw-bold">{{ $stats['orders']['pending'] + $stats['orders']['processing'] + $stats['orders']['completed'] }}</h2>
                <p class="text-muted mb-0">{{ __('Total Orders', 'wp-woocommerce-printify-sync') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Products Chart -->
    <div class="col-lg-8">
        <div class="card wpwps-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">
                    <i class="fas fa-chart-line me-2"></i> {{ __('Products Sync Activity', 'wp-woocommerce-printify-sync') }}
                </h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary">{{ __('Day', 'wp-woocommerce-printify-sync') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary active">{{ __('Week', 'wp-woocommerce-printify-sync') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary">{{ __('Month', 'wp-woocommerce-printify-sync') }}</button>
                </div>
            </div>
            <div class="card-body">
                <div class="wpwps-chart">
                    <canvas id="productsActivityChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Products Status -->
    <div class="col-lg-4">
        <div class="card wpwps-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-box-open me-2"></i> {{ __('Products Status', 'wp-woocommerce-printify-sync') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="wpwps-chart mb-4">
                    <canvas id="productsStatusChart" height="200"></canvas>
                </div>
                <div class="wpwps-stats">
                    <div class="wpwps-stats-item">
                        <span>{{ __('Total Products', 'wp-woocommerce-printify-sync') }}</span>
                        <strong>{{ $stats['products']['total'] }}</strong>
                    </div>
                    <div class="wpwps-stats-item">
                        <span>{{ __('Synced', 'wp-woocommerce-printify-sync') }}</span>
                        <strong>{{ $stats['products']['synced'] }}</strong>
                    </div>
                    <div class="wpwps-stats-item">
                        <span>{{ __('Failed', 'wp-woocommerce-printify-sync') }}</span>
                        <strong>{{ $stats['products']['failed'] }}</strong>
                    </div>
                </div>
                <a href="admin.php?page=wpwps-products" class="btn btn-sm wpwps-btn wpwps-btn-primary w-100 mt-3">
                    {{ __('View All Products', 'wp-woocommerce-printify-sync') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Recent Orders -->
    <div class="col-lg-8">
        <div class="card wpwps-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">
                    <i class="fas fa-shopping-cart me-2"></i> {{ __('Recent Orders', 'wp-woocommerce-printify-sync') }}
                </h5>
                <a href="admin.php?page=wpwps-orders" class="btn btn-sm wpwps-btn wpwps-btn-primary">
                    {{ __('View All', 'wp-woocommerce-printify-sync') }}
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table wpwps-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Order', 'wp-woocommerce-printify-sync') }}</th>
                                <th>{{ __('Customer', 'wp-woocommerce-printify-sync') }}</th>
                                <th>{{ __('Status', 'wp-woocommerce-printify-sync') }}</th>
                                <th>{{ __('Date', 'wp-woocommerce-printify-sync') }}</th>
                                <th>{{ __('Total', 'wp-woocommerce-printify-sync') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><a href="#">#WC-10982</a></td>
                                <td>John Doe</td>
                                <td>
                                    <span class="wpwps-status wpwps-status-warning">{{ __('Pending', 'wp-woocommerce-printify-sync') }}</span>
                                </td>
                                <td>{{ date('M d, Y') }}</td>
                                <td>$34.99</td>
                            </tr>
                            <tr>
                                <td><a href="#">#WC-10981</a></td>
                                <td>Jane Smith</td>
                                <td>
                                    <span class="wpwps-status wpwps-status-info">{{ __('Processing', 'wp-woocommerce-printify-sync') }}</span>
                                </td>
                                <td>{{ date('M d, Y', strtotime('-1 day')) }}</td>
                                <td>$78.50</td>
                            </tr>
                            <tr>
                                <td><a href="#">#WC-10980</a></td>
                                <td>Robert Johnson</td>
                                <td>
                                    <span class="wpwps-status wpwps-status-success">{{ __('Completed', 'wp-woocommerce-printify-sync') }}</span>
                                </td>
                                <td>{{ date('M d, Y', strtotime('-2 day')) }}</td>
                                <td>$125.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Status -->
    <div class="col-lg-4">
        <div class="card wpwps-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-chart-pie me-2"></i> {{ __('Order Status', 'wp-woocommerce-printify-sync') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="wpwps-chart mb-4">
                    <canvas id="ordersChart" height="200"></canvas>
                </div>
                <div class="wpwps-stats">
                    <div class="wpwps-stats-item">
                        <span>{{ __('Pending', 'wp-woocommerce-printify-sync') }}</span>
                        <strong>{{ $stats['orders']['pending'] }}</strong>
                    </div>
                    <div class="wpwps-stats-item">
                        <span>{{ __('Processing', 'wp-woocommerce-printify-sync') }}</span>
                        <strong>{{ $stats['orders']['processing'] }}</strong>
                    </div>
                    <div class="wpwps-stats-item">
                        <span>{{ __('Completed', 'wp-woocommerce-printify-sync') }}</span>
                        <strong>{{ $stats['orders']['completed'] }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Quick Actions -->
    <div class="col-12">
        <div class="card wpwps-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-bolt me-2"></i> {{ __('Quick Actions', 'wp-woocommerce-printify-sync') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn wpwps-btn wpwps-btn-primary w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-sync me-2"></i> {{ __('Sync Products', 'wp-woocommerce-printify-sync') }}
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn wpwps-btn wpwps-btn-secondary w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-plus me-2"></i> {{ __('Add New Product', 'wp-woocommerce-printify-sync') }}
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn wpwps-btn wpwps-btn-secondary w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-cog me-2"></i> {{ __('Configure Webhooks', 'wp-woocommerce-printify-sync') }}
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn wpwps-btn wpwps-btn-secondary w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-file-alt me-2"></i> {{ __('View Reports', 'wp-woocommerce-printify-sync') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@wpnonce('wpwps_dashboard_nonce')

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Products Status Chart
    const productsStatusCtx = document.getElementById('productsStatusChart').getContext('2d');
    new Chart(productsStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['{{ __("Synced", "wp-woocommerce-printify-sync") }}', '{{ __("Failed", "wp-woocommerce-printify-sync") }}'],
            datasets: [{
                data: [{{ $stats['products']['synced'] }}, {{ $stats['products']['failed'] }}],
                backgroundColor: ['#28a745', '#dc3545'],
                borderWidth: 0,
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });
    
    // Orders Status Chart
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    new Chart(ordersCtx, {
        type: 'doughnut',
        data: {
            labels: [
                '{{ __("Pending", "wp-woocommerce-printify-sync") }}',
                '{{ __("Processing", "wp-woocommerce-printify-sync") }}',
                '{{ __("Completed", "wp-woocommerce-printify-sync") }}'
            ],
            datasets: [{
                data: [
                    {{ $stats['orders']['pending'] }},
                    {{ $stats['orders']['processing'] }},
                    {{ $stats['orders']['completed'] }}
                ],
                backgroundColor: ['#ffc107', '#00b4d8', '#28a745'],
                borderWidth: 0,
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });
    
    // Products Activity Line Chart
    const productsActivityCtx = document.getElementById('productsActivityChart').getContext('2d');
    new Chart(productsActivityCtx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: '{{ __("Synced Products", "wp-woocommerce-printify-sync") }}',
                data: [5, 8, 12, 7, 15, 10, 9],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#28a745',
                pointBorderWidth: 2,
                pointRadius: 4,
                tension: 0.4,
                fill: true
            }, {
                label: '{{ __("Failed Syncs", "wp-woocommerce-printify-sync") }}',
                data: [2, 1, 3, 1, 2, 0, 1],
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#dc3545',
                pointBorderWidth: 2,
                pointRadius: 4,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(15, 26, 32, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    padding: 12,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        drawBorder: false,
                        display: false
                    }
                }
            },
            interaction: {
                mode: 'index',
                intersect: false
            },
            animation: {
                duration: 2000
            }
        }
    });
});
</script>
@endsection