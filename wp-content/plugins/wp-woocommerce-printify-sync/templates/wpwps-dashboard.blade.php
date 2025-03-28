@extends('layout')

@section('actions')
    <button class="btn btn-primary me-2" id="sync-products">
        <i class="fa fa-sync me-1"></i> Sync Products
    </button>
    <button class="btn btn-secondary" id="sync-orders">
        <i class="fa fa-sync me-1"></i> Sync Orders
    </button>
@endsection

@section('content')
    <!-- Stats Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Products</h5>
                    <h2 class="mb-0">{{ $product_count }}</h2>
                    <small>{{ $sync_status['products']['synced'] }} synced</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Orders</h5>
                    <h2 class="mb-0">{{ $order_count }}</h2>
                    <small>{{ $sync_status['orders']['synced'] }} synced</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Email Queue</h5>
                    <h2 class="mb-0">{{ $email_queue }}</h2>
                    <small>Pending emails</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-{{ $webhook_status['active'] ? 'success' : 'warning' }} text-white">
                <div class="card-body">
                    <h5 class="card-title">Webhooks</h5>
                    <h2 class="mb-0">{{ $webhook_status['active'] ? 'Active' : 'Inactive' }}</h2>
                    <small>{{ $webhook_status['errors'] }} errors</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Sales vs. Cost</h5>
                </div>
                <div class="card-body">
                    <canvas id="sales-chart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Top Products</h5>
                </div>
                <div class="card-body">
                    <canvas id="products-chart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sync Status -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Product Sync Status</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Synced Products</span>
                        <span>{{ $sync_status['products']['synced'] }} / {{ $sync_status['products']['total'] }}</span>
                    </div>
                    <div class="progress mb-3">
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
                        <small class="text-muted">Last sync: {{ date('F j, Y, g:i a', $sync_status['products']['last_sync']) }}</small>
                    @else
                        <small class="text-muted">No sync performed yet</small>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Order Sync Status</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Synced Orders</span>
                        <span>{{ $sync_status['orders']['synced'] }} / {{ $sync_status['orders']['total'] }}</span>
                    </div>
                    <div class="progress mb-3">
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
                        <small class="text-muted">Last sync: {{ date('F j, Y, g:i a', $sync_status['orders']['last_sync']) }}</small>
                    @else
                        <small class="text-muted">No sync performed yet</small>
                    @endif
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
                                alert('Product sync initiated successfully. This may take some time to complete.');
                                location.reload();
                            } else {
                                alert('Error: ' + response.data.message);
                                $('#sync-products').prop('disabled', false).html('<i class="fa fa-sync me-1"></i> Sync Products');
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                            $('#sync-products').prop('disabled', false).html('<i class="fa fa-sync me-1"></i> Sync Products');
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
                                alert('Order sync initiated successfully. This may take some time to complete.');
                                location.reload();
                            } else {
                                alert('Error: ' + response.data.message);
                                $('#sync-orders').prop('disabled', false).html('<i class="fa fa-sync me-1"></i> Sync Orders');
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                            $('#sync-orders').prop('disabled', false).html('<i class="fa fa-sync me-1"></i> Sync Orders');
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
                    }
                }
            });

            // Initialize charts
            function initCharts(chartData) {
                // Sales chart
                if (chartData.sales) {
                    var salesCtx = document.getElementById('sales-chart').getContext('2d');
                    new Chart(salesCtx, {
                        type: 'line',
                        data: {
                            labels: chartData.sales.labels,
                            datasets: chartData.sales.datasets
                        },
                        options: {
                            responsive: true,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }

                // Products chart
                if (chartData.products) {
                    var productsCtx = document.getElementById('products-chart').getContext('2d');
                    new Chart(productsCtx, {
                        type: 'doughnut',
                        data: {
                            labels: chartData.products.labels,
                            datasets: chartData.products.datasets
                        },
                        options: {
                            responsive: true
                        }
                    });
                }
            }
        });
    </script>
@endsection