@extends('layout')

@section('content')
<div class="wrap">
    <h1>{{ __('Printify Dashboard', 'wp-woocommerce-printify-sync') }}</h1>

    <div class="row">
        <!-- Sync Status -->
        <div class="col-md-4">
            <div class="wpwps-card">
                <h3>{{ __('Sync Status', 'wp-woocommerce-printify-sync') }}</h3>
                <div class="wpwps-status wpwps-status-{{ $sync_status['synced_products'] == $sync_status['total_products'] ? 'success' : 'warning' }}">
                    {{ $sync_status['synced_products'] }}/{{ $sync_status['total_products'] }} {{ __('Products Synced', 'wp-woocommerce-printify-sync') }}
                </div>
                <p>{{ __('Last Sync:', 'wp-woocommerce-printify-sync') }} {{ $sync_status['last_sync'] ? date('Y-m-d H:i:s', $sync_status['last_sync']) : __('Never', 'wp-woocommerce-printify-sync') }}</p>
            </div>
        </div>

        <!-- API Status -->
        <div class="col-md-4">
            <div class="wpwps-card">
                <h3>{{ __('API Status', 'wp-woocommerce-printify-sync') }}</h3>
                <div class="wpwps-status wpwps-status-{{ $api_status['status'] }}">
                    {{ $api_status['message'] }}
                </div>
            </div>
        </div>

        <!-- Email Queue -->
        <div class="col-md-4">
            <div class="wpwps-card">
                <h3>{{ __('Email Queue', 'wp-woocommerce-printify-sync') }}</h3>
                <p>{{ __('Queued:', 'wp-woocommerce-printify-sync') }} {{ $email_queue['queued'] }}</p>
                <p>{{ __('Sent Today:', 'wp-woocommerce-printify-sync') }} {{ $email_queue['sent_today'] }}</p>
                <p>{{ __('Failed:', 'wp-woocommerce-printify-sync') }} {{ $email_queue['failed'] }}</p>
            </div>
        </div>
    </div>

    <!-- Sales Chart -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="wpwps-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>{{ __('Sales Overview', 'wp-woocommerce-printify-sync') }}</h3>
                    <select id="chart-range" class="form-select" style="width: auto;">
                        <option value="7">{{ __('Last 7 Days', 'wp-woocommerce-printify-sync') }}</option>
                        <option value="30" selected>{{ __('Last 30 Days', 'wp-woocommerce-printify-sync') }}</option>
                        <option value="90">{{ __('Last 90 Days', 'wp-woocommerce-printify-sync') }}</option>
                    </select>
                </div>
                <canvas id="sales-chart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="wpwps-card">
                <h3>{{ __('Recent Orders', 'wp-woocommerce-printify-sync') }}</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>{{ __('Order ID', 'wp-woocommerce-printify-sync') }}</th>
                            <th>{{ __('Printify ID', 'wp-woocommerce-printify-sync') }}</th>
                            <th>{{ __('Status', 'wp-woocommerce-printify-sync') }}</th>
                            <th>{{ __('Total', 'wp-woocommerce-printify-sync') }}</th>
                            <th>{{ __('Date', 'wp-woocommerce-printify-sync') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent_orders as $order)
                            <tr>
                                <td><a href="{{ admin_url('post.php?post=' . $order['id'] . '&action=edit') }}">#{{ $order['id'] }}</a></td>
                                <td>{{ $order['printify_id'] }}</td>
                                <td><span class="wpwps-status wpwps-status-{{ $order['status'] }}">{{ $order['status'] }}</span></td>
                                <td>{{ wc_price($order['total']) }}</td>
                                <td>{{ $order['date'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">{{ __('No recent orders found.', 'wp-woocommerce-printify-sync') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
jQuery(document).ready(function($) {
    let salesChart;
    
    function initChart(data) {
        const ctx = document.getElementById('sales-chart').getContext('2d');
        
        if (salesChart) {
            salesChart.destroy();
        }
        
        salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: '{{ __("Sales", "wp-woocommerce-printify-sync") }}',
                        data: data.sales,
                        borderColor: '#96588a',
                        tension: 0.3
                    },
                    {
                        label: '{{ __("Orders", "wp-woocommerce-printify-sync") }}',
                        data: data.orders,
                        borderColor: '#aaa',
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }
    
    function loadChartData(days = 30) {
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wpwps_get_sales_data',
                nonce: '{{ wp_create_nonce("wpwps_ajax_nonce") }}',
                days: days
            },
            success: function(response) {
                if (response.success) {
                    initChart(response.data);
                }
            }
        });
    }
    
    $('#chart-range').on('change', function() {
        loadChartData($(this).val());
    });
    
    loadChartData();
    
    // Refresh sync status periodically
    setInterval(function() {
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wpwps_get_sync_status',
                nonce: '{{ wp_create_nonce("wpwps_ajax_nonce") }}'
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('.wpwps-status').first().text(
                        data.synced_products + '/' + data.total_products + ' {{ __("Products Synced", "wp-woocommerce-printify-sync") }}'
                    );
                }
            }
        });
    }, 30000);
});
</script>
@endsection