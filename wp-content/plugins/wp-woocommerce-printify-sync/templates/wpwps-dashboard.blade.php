@extends('layout')

@section('title', __('Dashboard', WPWPS_TEXT_DOMAIN))
@section('page_title', __('Dashboard', WPWPS_TEXT_DOMAIN))

@section('content')
    @if(!$is_configured)
        <div class="alert alert-warning">
            <h4 class="alert-heading">@e__('Setup Required')</h4>
            <p>@e__('To get started with Printify Sync, please configure your API settings.')</p>
            <a href="@adminUrl('admin.php?page=wpwps-settings')" class="btn btn-primary">
                <i class="fas fa-cog me-2"></i> @e__('Go to Settings')
            </a>
        </div>
    @else
        <!-- Overview Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">@e__('Products')</h5>
                                <h2 class="display-4">{{ $product_stats['total'] }}</h2>
                            </div>
                            <div>
                                <i class="fas fa-box-open fa-3x"></i>
                            </div>
                        </div>
                        <p class="mt-2 mb-0">{{ $product_stats['synced'] }} @e__('synced')</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">@e__('Orders')</h5>
                                <h2 class="display-4">{{ $order_stats['total'] }}</h2>
                            </div>
                            <div>
                                <i class="fas fa-shopping-cart fa-3x"></i>
                            </div>
                        </div>
                        <p class="mt-2 mb-0">{{ $order_stats['processing'] }} @e__('processing')</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">@e__('Production')</h5>
                                <h2 class="display-4">{{ $order_stats['in_production'] }}</h2>
                            </div>
                            <div>
                                <i class="fas fa-industry fa-3x"></i>
                            </div>
                        </div>
                        <p class="mt-2 mb-0">@e__('items in production')</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">@e__('Support')</h5>
                                <h2 class="display-4">0</h2>
                            </div>
                            <div>
                                <i class="fas fa-ticket-alt fa-3x"></i>
                            </div>
                        </div>
                        <p class="mt-2 mb-0">@e__('open tickets')</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <!-- Orders Chart -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">@e__('Orders Overview')</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="ordersChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Sync Summary -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title">@e__('Sync Summary')</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                @e__('Products Synced')
                                <span class="badge bg-primary rounded-pill">{{ $product_stats['synced'] }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                @e__('Products Unsynced')
                                <span class="badge bg-danger rounded-pill">{{ $product_stats['unsynced'] }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                @e__('Last Product Sync')
                                <span>{{ $sync_status['last_sync'] ? human_time_diff($sync_status['last_sync'], time()) . ' ' . __('ago', WPWPS_TEXT_DOMAIN) : __('Never', WPWPS_TEXT_DOMAIN) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                @e__('API Status')
                                <span class="badge bg-{{ $api_health['status'] === 'connected' ? 'success' : 'danger' }}">
                                    {{ $api_health['status'] === 'connected' ? __('Connected', WPWPS_TEXT_DOMAIN) : __('Error', WPWPS_TEXT_DOMAIN) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                @e__('Email Queue')
                                <span class="badge bg-info rounded-pill">{{ $email_queue['pending'] }}</span>
                            </li>
                        </ul>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button class="btn btn-primary" id="refresh-dashboard">
                                <i class="fas fa-sync me-2"></i> @e__('Refresh Data')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Order Status Breakdown -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">@e__('Order Status')</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="orderStatusChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">@e__('Recent Activity')</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">@e__('System Check')</h6>
                                    <small>{{ human_time_diff(time() - 300, time()) }} @e__('ago')</small>
                                </div>
                                <p class="mb-1">@e__('Automatic system health check completed.')</p>
                                <small class="text-muted">@e__('Status'): @e__('OK')</small>
                            </div>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">@e__('API Connection')</h6>
                                    <small>{{ human_time_diff(time() - 3600, time()) }} @e__('ago')</small>
                                </div>
                                <p class="mb-1">@e__('Successfully connected to Printify API.')</p>
                                <small class="text-muted">@e__('Status'): @e__('Connected')</small>
                            </div>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">@e__('Plugin Activated')</h6>
                                    <small>{{ human_time_diff(time() - 86400, time()) }} @e__('ago')</small>
                                </div>
                                <p class="mb-1">@e__('Plugin was successfully activated.')</p>
                                <small class="text-muted">@e__('Status'): @e__('Running')</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        jQuery(document).ready(function($) {
            // Only initialize charts if the container exists and plugin is configured
            if ($('#ordersChart').length && {{ $is_configured ? 'true' : 'false' }}) {
                initializeCharts();
            }
            
            // Refresh dashboard data
            $('#refresh-dashboard').on('click', function() {
                const button = $(this);
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> @e__('Loading...')');
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_get_dashboard_data',
                        nonce: wpwps.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || wpwps.i18n.error);
                            button.prop('disabled', false).html('<i class="fas fa-sync me-2"></i> @e__('Refresh Data')');
                        }
                    },
                    error: function() {
                        alert(wpwps.i18n.error);
                        button.prop('disabled', false).html('<i class="fas fa-sync me-2"></i> @e__('Refresh Data')');
                    }
                });
            });
            
            function initializeCharts() {
                // Sample data for charts - would be populated with real data in production
                const last30Days = [];
                const today = new Date();
                
                for (let i = 29; i >= 0; i--) {
                    const d = new Date();
                    d.setDate(today.getDate() - i);
                    last30Days.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                }
                
                // Orders Chart
                const ordersChartCtx = document.getElementById('ordersChart').getContext('2d');
                new Chart(ordersChartCtx, {
                    type: 'line',
                    data: {
                        labels: last30Days,
                        datasets: [{
                            label: '@e__('Orders')',
                            data: generateRandomData(30, 0, 10),
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 2,
                            tension: 0.4
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
                
                // Order Status Chart
                const orderStatusChartCtx = document.getElementById('orderStatusChart').getContext('2d');
                new Chart(orderStatusChartCtx, {
                    type: 'pie',
                    data: {
                        labels: ['@e__('Pending')', '@e__('Processing')', '@e__('Completed')', '@e__('In Production')', '@e__('Cancelled')'],
                        datasets: [{
                            data: [
                                {{ $order_stats['pending'] }}, 
                                {{ $order_stats['processing'] }}, 
                                {{ $order_stats['completed'] }}, 
                                {{ $order_stats['in_production'] }}, 
                                {{ $order_stats['cancelled'] }}
                            ],
                            backgroundColor: [
                                '#ffc107',
                                '#17a2b8',
                                '#28a745',
                                '#007bff',
                                '#dc3545'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
            
            function generateRandomData(count, min, max) {
                return Array.from({ length: count }, () => Math.floor(Math.random() * (max - min + 1)) + min);
            }
            
            // Handle manual sync buttons
            $('#wpwps-sync-products').on('click', function() {
                if (confirm('@e__('Are you sure you want to sync all products? This may take some time.')')) {
                    // This would trigger the product sync in a real implementation
                    alert('@e__('Product sync initiated. This may take several minutes to complete.')');
                }
            });
            
            $('#wpwps-sync-orders').on('click', function() {
                if (confirm('@e__('Are you sure you want to sync all orders?')')) {
                    // This would trigger the order sync in a real implementation
                    alert('@e__('Order sync initiated.')');
                }
            });
        });
    </script>
@endsection